<?php
/**
 * Ensure Google/Merchant policy pages are visible and internally linked.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$page = get_page_by_path( 'pristatymas-ir-grazinimas', OBJECT, 'page' );
$content = <<<HTML
<!-- wp:heading -->
<h2 class="wp-block-heading">Pristatymas ir grąžinimas</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Daiktuva yra skelbimų platforma, kurioje prekes parduoda atskiri pardavėjai. Pristatymo, atsiėmimo ir apmokėjimo sąlygas pirkėjas derina tiesiogiai su konkrečiu pardavėju prieš pirkimą.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Atsiėmimas ir pristatymas</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Dažniausiai prekės perduodamos susitikus gyvai arba siunčiamos pirkėjo ir pardavėjo sutartu būdu. Skelbime arba susirašinėjime rekomenduojama aiškiai susitarti dėl miesto, pristatymo kainos, siuntimo būdo ir atsakomybės už siuntą.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Prekės būklė</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Dauguma prekių yra naudotos, todėl prieš perkant verta paprašyti papildomų nuotraukų, patikslinti komplektaciją, defektus ir realią būklę. Pardavėjas turi pateikti teisingą informaciją apie parduodamą prekę.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Grąžinimas</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Grąžinimo sąlygos priklauso nuo konkretaus pardavėjo ir sandorio pobūdžio. Prieš perkant rekomenduojama iš anksto susitarti, ar prekė gali būti grąžinta, per kiek laiko ir kokiomis sąlygomis.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Jeigu kyla klausimų dėl platformos naudojimo, susisiekite per kontaktų puslapį.</p>
<!-- /wp:paragraph -->
HTML;

$postarr = array(
	'post_title'   => 'Pristatymas ir grąžinimas',
	'post_name'    => 'pristatymas-ir-grazinimas',
	'post_content' => $content,
	'post_status'  => 'publish',
	'post_type'    => 'page',
);

if ( $page ) {
	$postarr['ID'] = $page->ID;
	$page_id = wp_update_post( $postarr, true );
} else {
	$page_id = wp_insert_post( $postarr, true );
}

if ( is_wp_error( $page_id ) ) {
	WP_CLI::error( $page_id->get_error_message() );
}

$menu = wp_get_nav_menu_object( 'Pagrindinis' );
if ( ! $menu ) {
	WP_CLI::warning( 'Menu "Pagrindinis" not found.' );
	WP_CLI::success( 'Policy page updated.' );
	return;
}

$wanted = array(
	array( 'title' => 'Taisyklės', 'url' => home_url( '/taisykles/' ) ),
	array( 'title' => 'Pristatymas', 'url' => home_url( '/pristatymas-ir-grazinimas/' ) ),
	array( 'title' => 'Kontaktai', 'url' => home_url( '/kontaktai/' ) ),
);

$items = wp_get_nav_menu_items( $menu->term_id );
$existing_urls = array();
foreach ( $items ?: array() as $item ) {
	$existing_urls[] = untrailingslashit( $item->url );
}

foreach ( $wanted as $item ) {
	if ( in_array( untrailingslashit( $item['url'] ), $existing_urls, true ) ) {
		continue;
	}

	wp_update_nav_menu_item(
		$menu->term_id,
		0,
		array(
			'menu-item-title'  => $item['title'],
			'menu-item-url'    => $item['url'],
			'menu-item-status' => 'publish',
			'menu-item-type'   => 'custom',
		)
	);
}

WP_CLI::success( 'Policy page and menu links updated.' );
