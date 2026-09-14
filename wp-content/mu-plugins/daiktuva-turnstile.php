<?php
/**
 * Daiktuva: registracijos apsauga (2026-07-04).
 * 1) Honeypot laukas — botai, kurie užpildo paslėptą lauką, atmetami (veikia visada).
 * 2) Tempo limitas — daugiausia 3 registracijos per valandą iš vieno IP (veikia visada).
 * 3) Cloudflare Turnstile — įsijungia, kai nustatytos opcijos:
 *    wp option update dk_turnstile_sitekey <SITE_KEY>
 *    wp option update dk_turnstile_secret  <SECRET_KEY>
 * Dokan pardavėjų registracija eina per WooCommerce formą, tad woocommerce_* kabliukai dengia ir ją.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function dk_ts_sitekey(): string { return (string) get_option( 'dk_turnstile_sitekey', '' ); }
function dk_ts_secret(): string { return (string) get_option( 'dk_turnstile_secret', '' ); }

function dk_ts_ip(): string {
	if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
		return sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
	}
	return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
}

/* --- Laukai registracijos formose --- */
function dk_ts_field() {
	echo '<p style="position:absolute;left:-9999px;top:-9999px;" aria-hidden="true"><label>Palikite tuščią<input type="text" name="dk_website_hp" value="" tabindex="-1" autocomplete="off"></label></p>';
	if ( dk_ts_sitekey() ) {
		echo '<div class="cf-turnstile" data-sitekey="' . esc_attr( dk_ts_sitekey() ) . '" data-language="lt" style="margin:10px 0;"></div>';
	}
}
add_action( 'register_form', 'dk_ts_field' );
add_action( 'woocommerce_register_form', 'dk_ts_field', 25 );

add_action( 'wp_enqueue_scripts', function () {
	if ( ! dk_ts_sitekey() ) { return; }
	wp_enqueue_script( 'cf-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', array(), null, true );
} );
add_filter( 'script_loader_tag', function ( $tag, $handle ) {
	if ( 'cf-turnstile' === $handle ) {
		$tag = str_replace( ' src=', ' async defer src=', $tag );
	}
	return $tag;
}, 10, 2 );

/* --- Tikrinimas registruojantis --- */
function dk_ts_validate( $errors ) {
	$err = is_wp_error( $errors ) ? $errors : new WP_Error();

	if ( ! empty( $_POST['dk_website_hp'] ) ) {
		$err->add( 'dk_bot', 'Registracija atmesta.' );
		return $err;
	}

	$ip = dk_ts_ip();
	if ( $ip ) {
		$n = (int) get_transient( 'dk_reg_' . md5( $ip ) );
		if ( $n >= 3 ) {
			$err->add( 'dk_rate', 'Per daug registracijų iš šio tinklo per valandą. Pabandykite vėliau.' );
			return $err;
		}
	}

	if ( dk_ts_secret() ) {
		$tok = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : '';
		$ok  = false;
		if ( $tok ) {
			$r = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', array(
				'timeout' => 10,
				'body'    => array( 'secret' => dk_ts_secret(), 'response' => $tok, 'remoteip' => $ip ),
			) );
			if ( ! is_wp_error( $r ) ) {
				$j  = json_decode( wp_remote_retrieve_body( $r ), true );
				$ok = ! empty( $j['success'] );
			}
		}
		if ( ! $ok ) {
			$err->add( 'dk_ts', 'Patvirtinkite, kad nesate robotas, ir bandykite dar kartą.' );
			return $err;
		}
	}

	return $err;
}
add_filter( 'registration_errors', 'dk_ts_validate', 20 );
add_filter( 'woocommerce_registration_errors', 'dk_ts_validate', 20 );

add_action( 'user_register', function () {
	$ip = dk_ts_ip();
	if ( ! $ip ) { return; }
	$k = 'dk_reg_' . md5( $ip );
	set_transient( $k, (int) get_transient( $k ) + 1, HOUR_IN_SECONDS );
} );
