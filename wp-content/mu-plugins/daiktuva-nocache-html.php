<?php
/**
 * Daiktuva: dinaminių sąrašų puslapiuose (pradinis, parduotuvė, kategorijos) neleisti naršyklei
 * laikyti seno HTML — kitaip po dizaino/turinio keitimų lankytojas mato pasenusią versiją, kol
 * neišvalo kešo (2026-07-05). Liečia TIK naršyklės kešą (Cache-Control) — serverio Redis full-page
 * cache ir Cloudflare veikia kaip anksčiau. Prisijungusiems ir admin — nieko nekeičia.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'send_headers', static function () {
	if ( is_admin() || is_user_logged_in() ) {
		return;
	}
	$dynamic = is_front_page() || is_home()
		|| ( function_exists( 'is_shop' ) && is_shop() )
		|| ( function_exists( 'is_product_category' ) && is_product_category() )
		|| is_post_type_archive( 'product' );
	if ( ! $dynamic ) {
		return;
	}
	header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
	header( 'Pragma: no-cache' );
}, 20 );
