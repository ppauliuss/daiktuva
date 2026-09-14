<?php
/**
 * Daiktuva: vienas aiškus kelias į skelbimo įdėjimą svečiui ir pardavėjui.
 * Svečias registruojasi /vendor-onboarding/, prisijungęs vartotojas eina į formą.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function dk_add_listing_url(): string {
	return is_user_logged_in() ? home_url( '/dashboard/new-product/' ) : home_url( '/vendor-onboarding/' );
}

/* WordPress meniu CTA saugiai pakeičiamas pagal prisijungimo būseną. */
add_filter( 'nav_menu_link_attributes', static function ( $atts ) {
	$href = isset( $atts['href'] ) ? (string) $atts['href'] : '';
	if ( false !== strpos( $href, '/dashboard/new-product/' ) || false !== strpos( $href, '/vendor-onboarding/' ) ) {
		$atts['href'] = dk_add_listing_url();
	}
	return $atts;
}, 30 );

/* Atsarginis saugiklis senoms/įrašytoms nuorodoms ir pasidalintiems URL. */
add_action( 'template_redirect', static function () {
	if ( is_user_logged_in() || is_admin() || wp_doing_ajax() ) { return; }
	$path = (string) parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
	if ( 0 === strpos( $path, '/dashboard/new-product' ) ) {
		wp_safe_redirect( home_url( '/vendor-onboarding/' ), 302 );
		exit;
	}
}, 1 );

