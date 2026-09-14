<?php
/**
 * Daiktuva: paskyros navigacija header'yje (2026-07-04).
 * Vartotojas skundėsi, kad web'e (desktop) NEmato savo paskyros skilties (mobili sticky juosta
 * yra, bet desktop header — ne). Pridedam į pagrindinį meniu (rodomas ir desktop header'yje, ir
 * mobiliame išskleidžiamame meniu): prisijungus — ryškų „Mano paskyra" mygtuką + „Atsijungti";
 * atsijungus — „Prisijungti".
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_filter( 'wp_nav_menu_items', static function ( $items, $args ) {
	$loc = isset( $args->theme_location ) ? $args->theme_location : '';
	if ( 'primary' !== $loc ) {
		return $items;
	}
	if ( is_user_logged_in() ) {
		// 2026-08-21: „Mano paskyra" -> tikroji Woo paskyra (/my-account/, ten avataras,
		// nustatymai, el. pašto patvirtinimas); „Mano Daiktuva" hub'as (/mano-daiktuva/)
		// pasiekiamas savo meniu punktu — anksčiau ABU vedė į hub'ą.
		$acc = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
		$items .= '<li class="menu-item dk-acc dk-acc--btn"><a href="' . esc_url( $acc ) . '">Mano paskyra</a></li>';
		$items .= '<li class="menu-item dk-acc dk-acc--ghost"><a href="' . esc_url( wp_logout_url( home_url( '/' ) ) ) . '">Atsijungti</a></li>';
	} else {
		$items .= '<li class="menu-item dk-acc dk-acc--btn"><a href="' . esc_url( home_url( '/my-account/' ) ) . '">Prisijungti</a></li>';
	}
	return $items;
}, 20, 2 );

add_action( 'wp_head', static function () {
	echo '<style id="dk-acc-nav-css">'
		. '.dk-acc{display:flex !important;align-items:center;}'
		. '.dk-acc>a{font-weight:700;display:inline-flex !important;align-items:center;line-height:1;}'
		. '.dk-acc--btn>a{background:#0F4C81;color:#fff !important;border-radius:9px;padding:9px 18px !important;transition:background .15s;}'
		. '.dk-acc--btn>a:hover{background:#0d3f6b;color:#fff !important;}'
		. '.dk-acc--ghost>a{color:#0F4C81 !important;border:1px solid #cfd8e3;border-radius:9px;padding:8px 14px !important;font-weight:600;transition:background .15s,border-color .15s;}'
		. '.dk-acc--ghost>a:hover{background:#f2f5f9;border-color:#0F4C81;}'
		. '@media(max-width:921px){.dk-acc{margin:4px 20px;}.dk-acc--btn>a,.dk-acc--ghost>a{width:100%;justify-content:center;}}'
		. '</style>';
}, 20 );
