<?php
/**
 * Daiktuva: kliento pusės nuotraukų suspaudimas Dokan formose (2026-08-14).
 *
 * Problema: telefonų 4–12 MB foto keliami žali (teste 4.3 MB = 25.9 s per LAN,
 * per 4G — minutė+), be progreso indikacijos, + Dokan priverstinis crop žingsnis.
 * Sprendimas: dk-upload-optimize.js suspaudžia iki ~1920px JPEG naršyklėje
 * (compressorjs, EXIF orientacija įkepama), HEIC konvertuojamas (heic2any, tingiai),
 * crop ekranas praleidžiamas automatiškai. Serverio pipeline (dydžiai, webp, EXIF)
 * lieka kaip apsauga. Kraunama TIK Dokan dashboard puslapiuose.
 *
 * 2026-08-20:
 *  - kokybė 0.82 → 0.75 (vakarų foto 1920px sverdavo 1–1.5 MB; su 0.75 ~0.4–0.7 MB);
 *  - dk-multi-photo.js: mygtukas „Pridėti kelias nuotraukas iš karto" (pirmoji →
 *    viršelis, kitos → galerija) + auto-viršelis, kai galerija pildoma be viršelio;
 *  - touch-punch.js: jQuery UI sortable (galerijos rikiavimas) neveikė liečiant
 *    ekraną — tiltas touch→mouse.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'wp_enqueue_scripts', static function () {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
	if ( strpos( $uri, '/dashboard/' ) === false || ! is_user_logged_in() ) {
		return;
	}
	$base = content_url( 'mu-plugins/dk-upload' );
	$dir  = WP_CONTENT_DIR . '/mu-plugins/dk-upload';
	wp_enqueue_script( 'dk-compressor', $base . '/compressor.min.js', array(), '1.2.1', true );
	wp_enqueue_script(
		'dk-upload-optimize',
		$base . '/dk-upload-optimize.js',
		array( 'dk-compressor' ),
		(string) @filemtime( $dir . '/dk-upload-optimize.js' ),
		true
	);
	wp_localize_script( 'dk-upload-optimize', 'dkUploadOpt', array(
		'maxDim'      => 1920,
		'quality'     => 0.75,
		'minBytes'    => 400 * 1024,
		'heic2anyUrl' => $base . '/heic2any.min.js',
	) );
	// Galerijos vilkimas telefone (jQuery UI sortable pats touch nepalaiko).
	wp_enqueue_script(
		'dk-touch-punch',
		$base . '/touch-punch.js',
		array( 'jquery-ui-sortable' ),
		(string) @filemtime( $dir . '/touch-punch.js' ),
		true
	);
	// Kelių nuotraukų pažymėjimas vienu kartu.
	wp_enqueue_script(
		'dk-multi-photo',
		$base . '/dk-multi-photo.js',
		array(),
		(string) @filemtime( $dir . '/dk-multi-photo.js' ),
		true
	);
}, 20 );
