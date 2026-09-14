<?php
/**
 * Plugin Name: Daiktuva SMTP Mail
 * Description: Sends WordPress email through configured SMTP credentials.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dk_smtp_env( string $key, string $default = '' ): string {
	$value = getenv( $key );
	if ( false === $value || '' === $value ) {
		return $default;
	}
	return (string) $value;
}

function dk_mail_from_email(): string {
	return sanitize_email( dk_smtp_env( 'DAIKTUVA_SMTP_FROM_EMAIL', 'info@daiktuva.lt' ) );
}

function dk_mail_from_name(): string {
	return sanitize_text_field( dk_smtp_env( 'DAIKTUVA_SMTP_FROM_NAME', 'Daiktuva' ) );
}

add_filter( 'wp_mail_from', function () {
	return dk_mail_from_email();
} );

add_filter( 'wp_mail_from_name', function () {
	return dk_mail_from_name();
} );

add_action( 'phpmailer_init', function ( $phpmailer ) {
	$host = dk_smtp_env( 'DAIKTUVA_SMTP_HOST' );
	$user = dk_smtp_env( 'DAIKTUVA_SMTP_USER', dk_mail_from_email() );
	$pass = dk_smtp_env( 'DAIKTUVA_SMTP_PASSWORD' );

	if ( '' === $host || '' === $user || '' === $pass ) {
		return;
	}

	$port   = (int) dk_smtp_env( 'DAIKTUVA_SMTP_PORT', '587' );
	$secure = strtolower( dk_smtp_env( 'DAIKTUVA_SMTP_SECURE', 'tls' ) );

	$phpmailer->isSMTP();
	$phpmailer->Host       = $host;
	$phpmailer->SMTPAuth   = true;
	$phpmailer->Port       = $port > 0 ? $port : 587;
	$phpmailer->Username   = $user;
	$phpmailer->Password   = $pass;
	$phpmailer->SMTPSecure = in_array( $secure, array( 'tls', 'ssl' ), true ) ? $secure : '';
	$phpmailer->From       = dk_mail_from_email();
	$phpmailer->FromName   = dk_mail_from_name();
}, 20 );
