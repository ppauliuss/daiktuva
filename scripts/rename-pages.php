<?php
// Daiktuva: pervadinu WC/Dokan puslapius + meniu elementus i lietuviska kalba.
$pages = array(
	'cart'          => 'Krepšelis',
	'checkout'      => 'Apmokėjimas',
	'my-account'    => 'Mano paskyra',
	'shop'          => 'Parduotuvė',
	'dashboard'     => 'Skydelis',
	'store-listing' => 'Pardavėjai',
	'stores'        => 'Pardavėjai',
);
foreach ( $pages as $slug => $title ) {
	$p = get_page_by_path( $slug, OBJECT, 'page' );
	if ( $p && $p->post_title !== $title ) {
		wp_update_post( array( 'ID' => $p->ID, 'post_title' => $title ) );
		echo "PAGE $slug -> $title (id {$p->ID})\n";
	}
}

// "Pradzia" -> "Pradžia" (front page id 23)
$fp = (int) get_option( 'page_on_front' );
if ( $fp ) {
	wp_update_post( array( 'ID' => $fp, 'post_title' => 'Pradžia' ) );
	echo "FRONT page id $fp -> Pradžia\n";
}

// Nav meniu elementu etiketes (post_title nav_menu_item)
$menu_fix = array(
	'Pradzia'  => 'Pradžia',
	'Pradžia'  => 'Pradžia',
	'Cart'     => 'Krepšelis',
	'Shop'     => 'Parduotuvė',
	'Checkout' => 'Apmokėjimas',
);
$items = get_posts( array( 'post_type' => 'nav_menu_item', 'numberposts' => -1, 'post_status' => 'any' ) );
foreach ( $items as $it ) {
	$lbl = $it->post_title;
	if ( $lbl && isset( $menu_fix[ $lbl ] ) && $menu_fix[ $lbl ] !== $lbl ) {
		wp_update_post( array( 'ID' => $it->ID, 'post_title' => $menu_fix[ $lbl ] ) );
		echo "MENU '{$lbl}' -> '{$menu_fix[$lbl]}' (id {$it->ID})\n";
	}
}

// istrinti default "Sample Page" jei yra
$sp = get_page_by_path( 'sample-page', OBJECT, 'page' );
if ( $sp ) {
	wp_delete_post( $sp->ID, true );
	echo "DELETED Sample Page (id {$sp->ID})\n";
}
echo "DONE\n";
