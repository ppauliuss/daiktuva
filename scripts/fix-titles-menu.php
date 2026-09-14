<?php
// UTF-8 saugus pavadinimu taisymas + meniu apzvalga.
$fix_pages = array(
	'my-orders'         => 'Mano užsakymai',
	'vendor-onboarding' => 'Tapk pardavėju',
	'taisykles'         => 'Taisyklės',
);
foreach ( $fix_pages as $slug => $title ) {
	$p = get_page_by_path( $slug, OBJECT, 'page' );
	if ( $p ) {
		wp_update_post( array( 'ID' => $p->ID, 'post_title' => $title ) );
		echo "PAGE $slug -> $title\n";
	}
}
// meniu elementas "+ Ideti skelbima"
$items = get_posts( array( 'post_type' => 'nav_menu_item', 'numberposts' => -1, 'post_status' => 'any' ) );
foreach ( $items as $it ) {
	if ( strpos( $it->post_title, 'Ideti skelbima' ) !== false ) {
		wp_update_post( array( 'ID' => $it->ID, 'post_title' => '+ Įdėti skelbimą' ) );
		echo "MENU item {$it->ID} -> + Įdėti skelbimą\n";
	}
}

echo "=== MENIU APZVALGA ===\n";
foreach ( wp_get_nav_menus() as $m ) {
	$cnt = count( wp_get_nav_menu_items( $m->term_id ) ?: array() );
	echo "Menu: '{$m->name}' (id {$m->term_id}) elementu: $cnt\n";
}
echo "Priskirti location'ai:\n";
$locs = get_nav_menu_locations();
print_r( $locs );
echo "Astra primary header menu vieta: " . ( isset( $locs['primary'] ) ? $locs['primary'] : 'NEPRISKIRTA (fallback=visi puslapiai!)' ) . "\n";
