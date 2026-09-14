<?php
$page = get_page_by_path( 'mano-daiktuva', OBJECT, 'page' );
$data = array(
	'post_title'   => 'Mano Daiktuva',
	'post_name'    => 'mano-daiktuva',
	'post_type'    => 'page',
	'post_status'  => 'publish',
	'post_content' => '[dk_account_hub]',
);

if ( $page ) {
	$data['ID'] = $page->ID;
	$page_id = wp_update_post( $data, true );
} else {
	$page_id = wp_insert_post( $data, true );
}

if ( is_wp_error( $page_id ) ) {
	fwrite( STDERR, $page_id->get_error_message() . PHP_EOL );
	exit( 1 );
}

$menus = wp_get_nav_menus();
$menu = null;
foreach ( $menus as $candidate ) {
	if ( 'pagrindinis' === $candidate->slug || 'Pagrindinis' === $candidate->name ) {
		$menu = $candidate;
		break;
	}
}

if ( ! $menu ) {
	fwrite( STDERR, 'Primary menu not found.' . PHP_EOL );
	exit( 1 );
}

$items = wp_get_nav_menu_items( $menu->term_id );
$found = false;
foreach ( $items as $item ) {
	if ( (int) $item->object_id === (int) $page_id || 'Mano Daiktuva' === $item->title ) {
		wp_update_nav_menu_item( $menu->term_id, $item->ID, array(
			'menu-item-title'     => 'Mano Daiktuva',
			'menu-item-object-id' => $page_id,
			'menu-item-object'    => 'page',
			'menu-item-type'      => 'post_type',
			'menu-item-status'    => 'publish',
			'menu-item-classes'   => 'dk-user-menu',
		) );
		$found = true;
		break;
	}
}

if ( ! $found ) {
	wp_update_nav_menu_item( $menu->term_id, 0, array(
		'menu-item-title'     => 'Mano Daiktuva',
		'menu-item-object-id' => $page_id,
		'menu-item-object'    => 'page',
		'menu-item-type'      => 'post_type',
		'menu-item-status'    => 'publish',
		'menu-item-classes'   => 'dk-user-menu',
	) );
}

echo home_url( '/mano-daiktuva/' ) . PHP_EOL;
