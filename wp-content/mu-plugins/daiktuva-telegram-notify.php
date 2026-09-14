<?php
/**
 * Daiktuva: Telegram pranešimai apie svetainės įvykius (2026-07-05).
 * Įvykiai: nauja registracija (vardas/el.paštas/BŪDAS/rolė), naujas skelbimas, būsenos keitimas
 * (Parduota/Rezervuota), skelbimo trynimas. Siunčia per Hermes botą (token wp option `dk_tg_token`,
 * chat `dk_tg_chat`). SVARBU: blocking=TRUE — fire-and-forget (blocking=false) PHP-FPM procesui
 * pasibaigus nutrūkdavo, pranešimai praslysdavo. Telegram API ~0,3 s.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function dk_tg_notify( string $text ): void {
	$token = getenv( 'DAIKTUVA_TG_TOKEN' ) ?: (string) get_option( 'dk_tg_token' );
	$chat  = getenv( 'DAIKTUVA_TG_CHAT' ) ?: (string) get_option( 'dk_tg_chat' );
	if ( ! $token || ! $chat ) {
		return;
	}
	wp_remote_post( 'https://api.telegram.org/bot' . $token . '/sendMessage', array(
		'timeout'  => 6,
		'blocking' => true,
		'body'     => array(
			'chat_id'                  => $chat,
			'text'                     => $text,
			'parse_mode'               => 'HTML',
			'disable_web_page_preview' => 'true',
		),
	) );
}

/* -------- 1) NAUJAS VARTOTOJAS — per shutdown, kad Nextend (Google) social saitas jau egzistuotų. */
add_action( 'user_register', static function ( $user_id ) {
	$GLOBALS['dk_tg_new_users'][] = (int) $user_id;
}, 100 );

add_action( 'shutdown', static function () {
	if ( empty( $GLOBALS['dk_tg_new_users'] ) ) {
		return;
	}
	global $wpdb;
	foreach ( array_unique( (array) $GLOBALS['dk_tg_new_users'] ) as $uid ) {
		$u = get_userdata( $uid );
		if ( ! $u ) {
			continue;
		}
		$provider = $wpdb->get_var( $wpdb->prepare( "SELECT type FROM {$wpdb->prefix}social_users WHERE ID = %d LIMIT 1", $uid ) );
		$method   = $provider ? ( 'per ' . ucfirst( $provider ) . ' paskyrą' ) : 'el. paštu (registracijos forma)';
		$text  = "👤 <b>Naujas vartotojas — Daiktuva.lt</b>\n";
		$text .= "Vardas: " . esc_html( $u->display_name ?: $u->user_login ) . "\n";
		$text .= "El. paštas: " . esc_html( $u->user_email ) . "\n";
		$text .= "Registravosi: " . esc_html( $method ) . "\n";
		$text .= "Rolė: " . esc_html( implode( ', ', (array) $u->roles ) ) . " · ID " . (int) $uid . "\n";
		$text .= "Data: " . esc_html( date_i18n( 'Y-m-d H:i' ) );
		dk_tg_notify( $text );
	}
}, 100 );

/* -------- 2) NAUJAS SKELBIMAS (prio 50 — po limitų/verify, kad būsena galutinė). */
add_action( 'dokan_new_product_added', static function ( $product_id ) {
	$post = get_post( $product_id );
	if ( ! $post || 'product' !== $post->post_type ) {
		return;
	}
	$author    = get_userdata( $post->post_author );
	$status    = get_post_status( $product_id );
	$flag      = get_post_meta( $product_id, '_dk_flag_reason', true );
	$hold_mail = get_post_meta( $product_id, '_dk_hold_email_verify', true );
	$price     = get_post_meta( $product_id, '_regular_price', true );
	$text  = "🆕 <b>Naujas skelbimas — Daiktuva.lt</b>\n";
	$text .= "Pavadinimas: " . esc_html( get_the_title( $product_id ) ) . "\n";
	$text .= "Pardavėjas: " . esc_html( $author ? ( $author->display_name ?: $author->user_login ) : '—' ) . "\n";
	if ( $price ) {
		$text .= "Kaina: " . esc_html( $price ) . " €\n";
	}
	/* 2026-09-03 (vartotojo sprendimas): veiksmo reikalaujanti nuoroda siunčiama TIK kai
	   skelbimas tikrai laukia admino patvirtinimo (6-tas+ skelbimas arba flag'as).
	   El. pašto hold'as publikuojamas automatiškai — tai INFORMACINIS pranešimas be nuorodos
	   (pending skelbimo permalink'as viešai vis tiek 404, tad ji tik klaidino). */
	if ( 'publish' === $status ) {
		$text .= "Būsena: ✅ paskelbtas\n";
		$text .= "Nuoroda: " . esc_url( get_permalink( $product_id ) );
	} elseif ( $hold_mail && 'pending' === $status ) {
		$text .= "Būsena: ⌛ laukia, kol pardavėjas patvirtins el. paštą — paskelbs AUTOMATIŠKAI.\n";
		$text .= "Tavo veiksmo nereikia (informacinis pranešimas).";
	} elseif ( 'pending' === $status ) {
		$text .= "Būsena: ⏳ <b>LAUKIA TAVO PATVIRTINIMO</b>\n";
		if ( $flag ) {
			$text .= "Priežastis: " . esc_html( $flag ) . "\n";
		}
		$text .= "Peržiūra: " . admin_url( 'post.php?post=' . $product_id . '&action=edit' );
	} else {
		$text .= "Būsena: " . esc_html( $status );
	}
	dk_tg_notify( $text );
}, 50 );

/* -------- 3) BŪSENOS KEITIMAS -> Parduota / Rezervuota. */
function dk_tg_status_changed( $meta_id, $post_id, $key, $value ): void {
	if ( '_daiktuva_status' !== $key || ( defined( 'WP_CLI' ) && WP_CLI ) || wp_doing_cron() ) {
		return; // cron = demo rotatorius, ne realus pardavimas
	}
	if ( ! in_array( $value, array( 'reserved', 'sold' ), true ) || 'product' !== get_post_type( $post_id ) ) {
		return;
	}
	if ( get_post_meta( $post_id, '_dk_demo_sold', true ) ) {
		return; // demo skelbimai — be Telegram pranešimų
	}
	static $seen = array();
	$k = $post_id . ':' . $value;
	if ( isset( $seen[ $k ] ) ) {
		return;
	}
	$seen[ $k ] = 1;
	$author = get_userdata( (int) get_post_field( 'post_author', $post_id ) );
	$label  = 'sold' === $value ? '💰 <b>Parduota</b>' : '🔖 <b>Rezervuota</b>';
	$text  = $label . " — Daiktuva.lt\n";
	$text .= "Skelbimas: " . esc_html( get_the_title( $post_id ) ) . "\n";
	$text .= "Pardavėjas: " . esc_html( $author ? ( $author->display_name ?: $author->user_login ) : '—' ) . "\n";
	$text .= "Nuoroda: " . esc_url( get_permalink( $post_id ) );
	dk_tg_notify( $text );
}
add_action( 'added_post_meta', 'dk_tg_status_changed', 10, 4 );
add_action( 'updated_post_meta', 'dk_tg_status_changed', 10, 4 );

/* -------- 4) SKELBIMO TRYNIMAS (tik pardavėjo frontend veiksmas, NE adminas/CLI). */
function dk_tg_product_deleted( $post_id ): void {
	if ( ( defined( 'WP_CLI' ) && WP_CLI ) || ! is_user_logged_in() || current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	$post = get_post( $post_id );
	if ( ! $post || 'product' !== $post->post_type ) {
		return;
	}
	static $done = array();
	if ( isset( $done[ $post_id ] ) ) {
		return;
	}
	$done[ $post_id ] = 1;
	$author = get_userdata( (int) $post->post_author );
	$text  = "🗑 <b>Skelbimas ištrintas — Daiktuva.lt</b>\n";
	$text .= "Pavadinimas: " . esc_html( $post->post_title ) . "\n";
	$text .= "Pardavėjas: " . esc_html( $author ? ( $author->display_name ?: $author->user_login ) : '—' );
	dk_tg_notify( $text );
}
add_action( 'wp_trash_post', 'dk_tg_product_deleted' );
add_action( 'before_delete_post', 'dk_tg_product_deleted' );
