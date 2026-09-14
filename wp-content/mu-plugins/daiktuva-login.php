<?php
/**
 * Daiktuva login puslapio prekes zenklas + WordPress uzmaskavimas.
 * - vietoj WP "W" logotipo rodom Daiktuva ikona
 * - logotipo nuoroda i svetaine (ne i wordpress.org)
 * - is <title> ir teksto pasaliname "WordPress"
 * - Daiktuva akcento mygtukas
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* Browser tab title: "Prisijungti ‹ Daiktuva — WordPress" -> be WordPress */
add_filter( 'login_title', function ( $login_title ) {
	$login_title = preg_replace( '/\s*(&#8212;|&mdash;|—|-)\s*WordPress/u', '', $login_title );
	return str_replace( 'WordPress', 'Daiktuva', $login_title );
}, 10, 1 );

/* Logotipo nuoroda i pradini, alt tekstas "Daiktuva" */
add_filter( 'login_headerurl', function () { return home_url( '/' ); } );
add_filter( 'login_headertext', function () { return 'Daiktuva'; } );

/* Login stilius: Daiktuva ikona vietoj WP logo + akcentas */
add_action( 'login_enqueue_scripts', function () {
	$icon = '';
	$icon_id = (int) get_option( 'site_icon' );
	if ( $icon_id ) {
		$icon = wp_get_attachment_image_url( $icon_id, 'full' );
	}
	echo '<style>';
	echo 'body.login{background:#f3f4f7;}';
	if ( $icon ) {
		echo 'body.login h1 a{background-image:url(' . esc_url( $icon ) . ') !important;background-size:contain !important;width:88px !important;height:88px !important;}';
	}
	echo '#login h1 a{pointer-events:auto;}';
	echo '.login form{border-radius:12px;border:1px solid #e7e9ee;box-shadow:0 6px 24px rgba(20,23,33,.06);}';
	echo '.wp-core-ui .button-primary{background:#ff6900 !important;border-color:#e85d00 !important;color:#fff !important;box-shadow:0 2px 8px rgba(255,105,0,.35) !important;}';
	echo '.wp-core-ui .button-primary:hover{background:#e85d00 !important;}';
	echo '.login #backtoblog a,.login #nav a{color:#7a828e !important;}';
	echo '.login #backtoblog a:hover,.login #nav a:hover{color:#e85d00 !important;}';
	echo '.login input[type=text]:focus,.login input[type=password]:focus{border-color:#ff6900;box-shadow:0 0 0 2px rgba(255,105,0,.2);}';
	echo '</style>';
} );
