<?php
/**
 * Daiktuva: mažiau laiškų registruojantis (2026-07-04).
 * Vartotojas skundėsi per dideliu laiškų kiekiu. Paliekame VIENĄ prasmingą — el. pašto
 * patvirtinimo laišką (jis ir pasveikina, ir turi veiksmą). WooCommerce „naujos paskyros"
 * laiškas išjungtas atskirai (option). Čia pašalinam atskirą „Sveiki atvykę" laišką.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_filter( 'pre_wp_mail', static function ( $short, $atts ) {
	$subject = is_array( $atts ) && isset( $atts['subject'] ) ? (string) $atts['subject'] : '';
	if ( '' !== $subject && false !== mb_strpos( $subject, 'Sveiki atvykę į Daiktuva' ) ) {
		return false; // neišsiųsti dublikato — patvirtinimo laiškas jau pasveikina
	}
	return $short;
}, 10, 2 );
