<?php
/**
 * Add a few realistic price-change examples for visual QA.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$changes = array(
	68 => array(
		'old' => 520,
		'new' => 480,
		'note' => 'Generatorius 5 kW atpigo, nes pardavėjas nori greičiau parduoti.',
	),
	76 => array(
		'old' => 280,
		'new' => 240,
		'note' => 'Sodo baldų komplektas atpigo prieš savaitgalį.',
	),
	77 => array(
		'old' => 290,
		'new' => 320,
		'note' => 'iPhone 12 pabrango po komplektacijos patikslinimo.',
	),
);

foreach ( $changes as $product_id => $data ) {
	$product = wc_get_product( $product_id );
	if ( ! $product instanceof WC_Product ) {
		WP_CLI::warning( "Product {$product_id} not found." );
		continue;
	}

	$old = (float) $data['old'];
	$new = (float) $data['new'];
	$percent = round( ( $new - $old ) / $old * 100, 1 );

	update_post_meta( $product_id, '_regular_price', $new );
	update_post_meta( $product_id, '_price', $new );
	update_post_meta( $product_id, '_dk_previous_price', $old );
	update_post_meta( $product_id, '_dk_price_change_percent', $percent );
	update_post_meta( $product_id, '_dk_price_changed_at', current_time( 'mysql' ) );
	update_post_meta( $product_id, '_dk_price_change_note', $data['note'] );

	wc_delete_product_transients( $product_id );

	WP_CLI::log(
		sprintf(
			'%d %s: %.2f -> %.2f (%s%%)',
			$product_id,
			get_the_title( $product_id ),
			$old,
			$new,
			$percent
		)
	);
}

WP_CLI::success( 'Demo price changes added.' );
