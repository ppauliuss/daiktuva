<?php
/**
 * Daiktuva: našumas — išjungti nereikalingą WooCommerce Admin (2026-07-04).
 * Dokan pardavėjo skydelis (/dashboard/) frontende be reikalo įkeldavo WooCommerce Admin
 * analitikos React programą → ~10 wc-analytics/wc-admin REST užklausų, skydelis kraudavosi ~9,5 s
 * („vartotojo meniu ilgai sukasi"). Klasifikuotam portalui (kontaktas telefonu, nėra užsakymų)
 * analitika/rinkodara nereikalinga. Išjungiam funkcijas + neleidžiam wc-admin assetų frontende.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* 1) Išjungti nereikalingas WooCommerce Admin funkcijas. */
add_filter( 'woocommerce_admin_features', static function ( $features ) {
	$disable = array(
		'analytics', 'activity-panels', 'analytics-scheduled-import',
		'marketing', 'mobile-app-banner', 'store-alerts', 'wc-pay-promotion',
		'remote-inbox-notifications', 'remote-free-extensions', 'payment-gateway-suggestions',
		'shipping-label-banner', 'printful', 'woo-mobile-welcome',
	);
	return array_values( array_filter( (array) $features, static function ( $f ) use ( $disable ) {
		return ! in_array( $f, $disable, true );
	} ) );
}, 20 );

/* 2) Visai NEkrauti WooCommerce Admin (wc-admin React) FRONTENDE — wp-admin lieka nepaliestas. */
add_filter( 'woocommerce_admin_disabled', static function ( $disabled ) {
	return is_admin() ? $disabled : true;
}, 20 );

/* 3) Pagrindinis lėtiklis: Dokan „Analytics / Customizable Dashboard" pardavėjo skydelyje tempė
 *    VISĄ WP blokų redaktorių + wc-components (355 užklausų, ~9 s). Klasifikuotam portalui be
 *    pardavimų nereikalinga → išjungiam šiuos assetus FRONTENDE. (Naujo skelbimo klasikinis
 *    TinyMCE redaktorius = `editor`/`tinymce`, NE `wp-editor`, tad nepaliečiamas.) */
add_action( 'wp_print_scripts', static function () {
	if ( is_admin() ) {
		return;
	}
	$drop = array(
		'dokan_analytics_customizable-dashboard', 'dokan_analytics_dashboard-charts',
		'dokan_analytics_dashboard', 'dokan_analytics_store-performance', 'vendor_analytics_script',
		'wc-components', 'wc-admin-layout', 'wc-admin-app', 'wc-admin-navigation',
		'wp-block-editor', 'wp-blocks', 'wp-block-library', 'wp-edit-post', 'wp-format-library',
	);
	foreach ( $drop as $h ) {
		wp_dequeue_script( $h );
		wp_dequeue_style( $h );
	}
}, 100 );

/* 4) Klasifikuotam portalui be krepšelio nereikalingi WooCommerce skriptai (greitina VISUS puslapius). */
add_action( 'wp_enqueue_scripts', static function () {
	if ( is_admin() ) {
		return;
	}
	foreach ( array( 'wc-cart-fragments', 'wc-add-to-cart', 'woocommerce-add-to-cart', 'wc-order-attribution', 'sourcebuster-js', 'sourcebuster' ) as $h ) {
		wp_dequeue_script( $h );
	}
}, 99 );

/* 5) Emoji detekcijos skriptas — nereikalingas (emoji rodomi natyviai), lėtina kiekvieną puslapį. */
add_action( 'init', static function () {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	add_filter( 'emoji_svg_url', '__return_false' );
} );
