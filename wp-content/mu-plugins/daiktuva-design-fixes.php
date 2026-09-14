<?php
/**
 * Daiktuva: smulkūs dizaino pataisymai (2026-08-19, dizaino audito išvados).
 *  1) Excerpt'ų "[…]" → tikras daugtaškis "…" (Astra deda literalų "[&hellip;]").
 *  2) /patarimai/ (įrašų archyvas) neturėjo matomas H1 — blogai ir skaitytojui,
 *     ir SEO. H1 dedamas per astra_primary_content_top tik is_home() kontekste.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* 1) Daugtaškis excerpt'uose. */
add_filter( 'excerpt_more', static function () {
	return '&hellip;';
}, 99 );

/* 1b) Kainų ",00 €" → "€" (Woo nulius kerta tik per filtrą, ne opciją). */
add_filter( 'woocommerce_price_trim_zeros', '__return_true' );

/* 2) H1 įrašų archyvui (/patarimai/). */
add_action( 'astra_primary_content_top', static function () {
	if ( ! is_home() ) {
		return;
	}
	$posts_page_id = (int) get_option( 'page_for_posts' );
	$title         = $posts_page_id ? get_the_title( $posts_page_id ) : 'Patarimai';
	echo '<header class="dk-archive-header"><h1 class="dk-archive-title">' . esc_html( $title ) . '</h1></header>';
} );

add_action( 'wp_head', static function () {
	if ( ! is_home() ) {
		return;
	}
	echo '<style id="dk-archive-header">'
		. '.dk-archive-header{margin:4px 0 18px}'
		. '.dk-archive-title{margin:0;font-size:28px;line-height:1.25;font-weight:700}'
		. '@media(max-width:768px){.dk-archive-title{font-size:23px}}'
		. '</style>';
}, 21 );

/* 3) Mobilioje sticky apatinė juosta (.dk-mobile-sticky) uždengdavo footer'io
      galą — rezervuojame vietos visame puslapyje (54px = kompaktiška juosta). */
add_action( 'wp_head', static function () {
	if ( is_admin() ) {
		return;
	}
	echo '<style id="dk-mobile-sticky-pad">'
		. '@media(max-width:768px){body{padding-bottom:54px}}'
		. '</style>';
}, 22 );

/* 4) Mobilieji pataisymai (2026-08-20, pardavėjo pastabos iš telefono):
      a) /dashboard/orders/ select2 pirkėjo filtras (.dokan-w12) turi fiksuotą 390px
         plotį — puslapis išsipletė iki 410px ir atsirado horizontalus scroll
         ("balta juosta šone"). max-width + bendra overflow-x apsauga.
      b) Astra scroll-to-top rodyklė (bottom:30px) 25px užlipo ant sticky juostos —
         keliame virš jos.
      c) WP media modalas: patvirtinimo mygtukas ("Select and Crop" / "Pasirinkti")
         mobiliajame būdavo mažas, prispaustas prie apatinio dešiniojo kampo —
         darome pilno pločio juostą apačioje (patogus nykščiui). */
add_action( 'wp_head', static function () {
	if ( is_admin() ) {
		return;
	}
	echo '<style id="dk-mobile-ux">'
		/* a) */
		. '@media(max-width:768px){html,body{overflow-x:hidden}'
		. 'body.dokan-dashboard #dokan-filter-customer,body.dokan-dashboard select.dokan-w12{max-width:100% !important;width:100% !important}}'
		/* b) */
		. '@media(max-width:760px){#ast-scroll-top{bottom:60px !important;right:10px !important}}'
		/* c) */
		. '@media(max-width:782px){'
		. '.media-modal .media-toolbar{height:auto;padding:6px 10px calc(6px + env(safe-area-inset-bottom))}'
		. '.media-modal .media-toolbar-primary{float:none;display:block}'
		. '.media-modal .media-toolbar-primary .media-button{float:none;display:block;width:100%;height:46px;line-height:44px;font-size:16px;margin:0;text-align:center}'
		. '}'
		. '</style>';
}, 23 );
