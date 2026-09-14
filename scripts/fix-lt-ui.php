<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$widgets = get_option( 'widget_block', array() );
foreach ( $widgets as &$widget ) {
	if ( ! is_array( $widget ) || empty( $widget['content'] ) ) {
		continue;
	}
	$widget['content'] = str_replace(
		array( 'Recent Posts', 'Recent Comments', 'Archives', 'Categories' ),
		array( 'Naujausi straipsniai', 'Naujausi komentarai', 'Archyvas', 'Kategorijos' ),
		$widget['content']
	);
}
unset( $widget );
update_option( 'widget_block', $widgets );

$terms = array(
	'zemes-ukis'              => 'Žemės ūkis',
	'statyba-ir-irankiai'    => 'Statyba ir įrankiai',
	'namai-ir-sodas'         => 'Namai ir sodas',
	'telefonai-ir-ismanieji' => 'Telefonai ir išmanieji',
	'drabuziai-ir-avalyne'   => 'Drabužiai ir avalynė',
	'grozis-ir-sveikata'     => 'Grožis ir sveikata',
	'gyvunai'                => 'Gyvūnai',
	'verslo-iranga'          => 'Verslo įranga',
);

foreach ( $terms as $slug => $name ) {
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( $term && ! is_wp_error( $term ) ) {
		wp_update_term( $term->term_id, 'product_cat', array( 'name' => $name ) );
	}
}

update_user_meta( 10, 'dokan_store_name', 'Vilniaus įrankiai' );
$profile = get_user_meta( 10, 'dokan_profile_settings', true );
if ( is_array( $profile ) ) {
	$profile['store_name'] = 'Vilniaus įrankiai';
	update_user_meta( 10, 'dokan_profile_settings', $profile );
}
wp_update_user( array(
	'ID'           => 10,
	'display_name' => 'Vilniaus įrankiai',
) );

echo "LT_UI_FIXED\n";
