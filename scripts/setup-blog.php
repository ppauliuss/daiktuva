<?php
// Daiktuva: ijungti bloga (Patarimai), header meniu nuoroda, istrinti Hello world.
// 1) Patarimai puslapis (irasu sarasas)
$pg = get_page_by_path( 'patarimai', OBJECT, 'page' );
if ( ! $pg ) {
	$pid = wp_insert_post( array(
		'post_title'  => 'Patarimai',
		'post_name'   => 'patarimai',
		'post_status' => 'publish',
		'post_type'   => 'page',
		'post_content'=> '',
	) );
	echo "Sukurtas Patarimai puslapis id $pid\n";
} else {
	$pid = $pg->ID;
	echo "Patarimai jau yra id $pid\n";
}
update_option( 'page_for_posts', $pid );
update_option( 'show_on_front', 'page' );
echo "page_for_posts=$pid\n";

// 2) istrinti Hello world demo
$hw = get_post( 1 );
if ( $hw && $hw->post_type === 'post' ) {
	wp_delete_post( 1, true );
	echo "Hello world istrintas\n";
}

// 3) header meniu "Pagrindinis" -> prideti Patarimai (jei dar nera)
$menu = wp_get_nav_menu_object( 'Pagrindinis' );
if ( $menu ) {
	$items = wp_get_nav_menu_items( $menu->term_id );
	$have  = false;
	foreach ( (array) $items as $it ) {
		if ( (int) $it->object_id === (int) $pid || $it->title === 'Patarimai' ) { $have = true; break; }
	}
	if ( ! $have ) {
		wp_update_nav_menu_item( $menu->term_id, 0, array(
			'menu-item-title'     => 'Patarimai',
			'menu-item-object'    => 'page',
			'menu-item-object-id' => $pid,
			'menu-item-type'      => 'post_type',
			'menu-item-status'    => 'publish',
			'menu-item-position'  => 2,
		) );
		echo "Patarimai pridetas i header meniu\n";
	} else {
		echo "Patarimai jau meniu\n";
	}
}
echo "DONE\n";
