<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$q = new WP_Query(
	array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => 100,
		'orderby'        => 'ID',
		'order'          => 'ASC',
	)
);

while ( $q->have_posts() ) {
	$q->the_post();
	$product = wc_get_product( get_the_ID() );
	$image = $product ? wp_get_attachment_url( $product->get_image_id() ) : '';
	$categories = wp_get_post_terms( get_the_ID(), 'product_cat', array( 'fields' => 'names' ) );
	printf(
		"%d\t%s\t%s\t%s\n",
		get_the_ID(),
		get_the_title(),
		is_wp_error( $categories ) ? '' : implode( ', ', $categories ),
		$image
	);
}

wp_reset_postdata();
