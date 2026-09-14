<?php
/**
 * Daiktuva: IndexNow (Bing, Yandex, Seznam, Naver ir kt. paieškos varikliai).
 * Publikavus/atnaujinus skelbimą, straipsnį ar puslapį — URL akimirksniu
 * pranešamas per api.indexnow.org. Raktas: DK_INDEXNOW_KEY, failas
 * https://daiktuva.lt/<raktas>.txt (guli wordpress šakniniame kataloge).
 * Masinis pateikimas: wp eval 'dk_indexnow_submit_all();'
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

const DK_INDEXNOW_KEY = 'dk7e91f4a2c85b36d0f1a4b7c2e95d38';

function dk_indexnow_ping( array $urls ): bool {
	$urls = array_values( array_filter( array_unique( $urls ) ) );
	if ( ! $urls ) {
		return false;
	}
	$r = wp_remote_post( 'https://api.indexnow.org/indexnow', array(
		'timeout' => 15,
		'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
		'body'    => wp_json_encode( array(
			'host'        => 'daiktuva.lt',
			'key'         => DK_INDEXNOW_KEY,
			'keyLocation' => home_url( '/' . DK_INDEXNOW_KEY . '.txt' ),
			'urlList'     => array_slice( $urls, 0, 10000 ),
		) ),
	) );
	return ! is_wp_error( $r ) && in_array( (int) wp_remote_retrieve_response_code( $r ), array( 200, 202 ), true );
}

/** Automatinis ping publikuojant (skelbimai, straipsniai, puslapiai). */
add_action( 'transition_post_status', static function ( $new, $old, $post ) {
	if ( ! $post || ! in_array( $post->post_type, array( 'product', 'post', 'page' ), true ) ) {
		return;
	}
	if ( 'publish' !== $new || 'publish' === $old ) {
		return;
	}
	$url = get_permalink( $post->ID );
	if ( $url ) {
		wp_schedule_single_event( time() + 30, 'dk_indexnow_delayed', array( array( $url ) ) );
	}
}, 20, 3 );

add_action( 'dk_indexnow_delayed', static function ( $urls ) {
	dk_indexnow_ping( (array) $urls );
} );

/** Masinis visų viešų URL pateikimas (rankiniam paleidimui). */
function dk_indexnow_submit_all(): string {
	$urls = array( home_url( '/' ) );
	foreach ( get_posts( array( 'post_type' => array( 'product', 'post', 'page' ), 'post_status' => 'publish', 'posts_per_page' => 500, 'fields' => 'ids' ) ) as $id ) {
		$urls[] = get_permalink( $id );
	}
	foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) ) as $t ) {
		$urls[] = get_term_link( $t );
	}
	$urls = array_filter( $urls, 'is_string' );
	$ok   = dk_indexnow_ping( $urls );
	return ( $ok ? 'OK ' : 'NEPAVYKO ' ) . count( $urls ) . ' URL';
}
