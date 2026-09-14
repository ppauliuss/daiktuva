<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$q = new WP_Query(
	array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => 20,
		'orderby'        => 'ID',
		'order'          => 'ASC',
	)
);

while ( $q->have_posts() ) {
	$q->the_post();
	$content = get_post_field( 'post_content', get_the_ID() );
	printf( "### %d %s\n%s\n\n", get_the_ID(), get_the_title(), $content );
}

wp_reset_postdata();
