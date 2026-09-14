<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$stamp = gmdate( 'YmdHis' );
$login = 'test_pardavejas_' . $stamp;
$email = 'test-' . $stamp . '@daiktuva.lt';
$pass  = getenv( 'DEMO_SELLER_PASSWORD' ) ?: wp_generate_password( 24, true );

$user_id = wp_insert_user( array(
	'user_login'   => $login,
	'user_email'   => $email,
	'user_pass'    => $pass,
	'display_name' => 'TEST pardavejas ' . $stamp,
	'role'         => 'seller',
) );

if ( is_wp_error( $user_id ) ) {
	fwrite( STDERR, 'USER_ERROR=' . $user_id->get_error_message() . PHP_EOL );
	exit( 1 );
}

update_user_meta( $user_id, 'dokan_enable_selling', 'yes' );
update_user_meta( $user_id, 'dokan_profile_settings', array(
	'store_name' => 'TEST pardavejas',
	'phone'      => '+37060000000',
	'show_email' => 'no',
	'address'    => array( 'city' => 'Vilnius' ),
) );

$title = 'TEST skelbimas - akumuliatorinis suktuvas ' . $stamp;

$product_id = wp_insert_post( array(
	'post_type'    => 'product',
	'post_status'  => 'publish',
	'post_author'  => $user_id,
	'post_title'   => $title,
	'post_content' => 'TEST skelbimas sukurtas automatiniu Daiktuva funkciniu testu. Aprasyme tikrinama, ar pardavejo sukurta preke su nuotrauka, kaina, vieta ir telefonu matoma gyvoje svetaineje.',
	'post_excerpt' => 'Automatinio testo skelbimas. Galima istrinti po patikros.',
) );

if ( is_wp_error( $product_id ) || ! $product_id ) {
	fwrite( STDERR, 'PRODUCT_ERROR=' . ( is_wp_error( $product_id ) ? $product_id->get_error_message() : 'empty product id' ) . PHP_EOL );
	exit( 1 );
}

foreach ( array(
	'_regular_price'   => '149',
	'_price'           => '149',
	'_stock_status'    => 'instock',
	'_visibility'      => 'visible',
	'_daiktuva_phone'  => '+37060000000',
	'_dk_location'     => 'Vilnius',
	'_daiktuva_status' => 'active',
) as $key => $value ) {
	update_post_meta( $product_id, $key, $value );
}

wp_set_object_terms( $product_id, 'elektronika', 'product_cat' );
wp_set_object_terms( $product_id, 'simple', 'product_type' );

$image = '/scripts/daiktuva-test-preke.png';
$attachment_id = 0;
if ( file_exists( $image ) ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	$tmp = wp_tempnam( basename( $image ) );
	copy( $image, $tmp );
	$file = array(
		'name'     => 'daiktuva-test-preke-' . $stamp . '.png',
		'tmp_name' => $tmp,
	);

	$attachment_id = media_handle_sideload( $file, $product_id, 'TEST skelbimo nuotrauka ' . $stamp );
	if ( is_wp_error( $attachment_id ) ) {
		@unlink( $tmp );
		fwrite( STDERR, 'MEDIA_ERROR=' . $attachment_id->get_error_message() . PHP_EOL );
		$attachment_id = 0;
	} else {
		set_post_thumbnail( $product_id, $attachment_id );
	}
}

if ( function_exists( 'wc_delete_product_transients' ) ) {
	wc_delete_product_transients( $product_id );
}

echo wp_json_encode( array(
	'login'         => $login,
	'user_id'       => $user_id,
	'product_id'    => $product_id,
	'attachment_id' => $attachment_id,
	'url'           => get_permalink( $product_id ),
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
