<?php
/**
 * Daiktuva: Google Search Console nuosavybes patvirtinimas (HTML tag metodas).
 * Kodas irasomas: Nustatymai > Bendri > "Google Search Console kodas" (option dk_gsc_verify).
 * Po irasymo BUTINA isvalyti Cache Enabler cache (pradinis psl kesuojamas).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'wp_head', function () {
	$v = trim( (string) get_option( 'dk_gsc_verify', '' ) );
	if ( $v !== '' ) {
		echo '<meta name="google-site-verification" content="' . esc_attr( $v ) . '" />' . "\n";
	}
}, 1 );

add_action( 'admin_init', function () {
	register_setting( 'general', 'dk_gsc_verify', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ) );
	add_settings_field( 'dk_gsc_verify', 'Google Search Console kodas', function () {
		$v = esc_attr( get_option( 'dk_gsc_verify', '' ) );
		echo '<input type="text" name="dk_gsc_verify" value="' . $v . '" placeholder="verifikacijos kodas" class="regular-text" />';
		echo '<p class="description">Iš Search Console „HTML žyma" metodo – tik <code>content</code> reikšmė (be viso &lt;meta&gt;).</p>';
	}, 'general' );
} );
