<?php
/**
 * Set recolored Daiktuva logo and site icon.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$uploads = array(
	'logo' => '/scripts/brand-recolor/daiktuva-logo-0f4c81.png',
	'icon' => '/scripts/brand-recolor/daiktuva-icon-0f4c81.png',
);

foreach ( $uploads as $key => $path ) {
	if ( ! file_exists( $path ) ) {
		WP_CLI::error( "{$path} not found." );
	}

	$id = media_handle_sideload(
		array(
			'name'     => basename( $path ),
			'tmp_name' => $path,
		),
		0
	);

	if ( is_wp_error( $id ) ) {
		WP_CLI::error( $id->get_error_message() );
	}

	update_post_meta( $id, '_wp_attachment_image_alt', 'Daiktuva' );

	if ( 'logo' === $key ) {
		set_theme_mod( 'custom_logo', $id );
		WP_CLI::log( "custom_logo={$id}" );
	} else {
		update_option( 'site_icon', $id );
		WP_CLI::log( "site_icon={$id}" );
	}
}

WP_CLI::success( 'Brand assets updated.' );
