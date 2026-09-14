<?php
/**
 * Daiktuva: papildomos OG/Twitter žymos, kurių neišveda Rank Math
 * (svetainėje buvo tik og:description ir og:image — be og:title dalinimasis
 * FB/Messenger rodydavo prastesnę kortelę). Jei kada Rank Math pradės vesti
 * og:title — šį failą išjungti, kad nesidubliuotų.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'wp_head', function () {
	// Pradinį ir tinklaraščio įrašus dengia daiktuva-google-organic.php — ten nekišame (nesidubliuoti)
	if ( is_admin() || is_front_page() || is_singular( 'post' ) ) { return; }
	$title = wp_get_document_title();
	$url   = is_singular() ? get_permalink() : home_url( $_SERVER['REQUEST_URI'] ?? '/' );
	$type  = is_singular( 'product' ) ? 'product' : ( is_singular() ? 'article' : 'website' );
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
	echo '<meta property="og:type" content="' . esc_attr( $type ) . '" />' . "\n";
	echo '<meta property="og:url" content="' . esc_url( $url ) . '" />' . "\n";
	echo '<meta property="og:site_name" content="Daiktuva" />' . "\n";
	echo '<meta property="og:locale" content="lt_LT" />' . "\n";
	echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '" />' . "\n";
}, 6 );
