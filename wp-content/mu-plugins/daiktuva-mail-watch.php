<?php
/**
 * Plugin Name: Daiktuva Mail Watch
 * Description: Fiksuoja wp_mail klaidas ir atiduoda sveikatos suvestinę per
 *   token'u apsaugotą REST endpoint'ą, kurį kas 15 min tikrina Hermes VPS
 *   (hermes-daiktuva-mail-alerts.sh -> Telegram). 2026-08-21.
 *
 * Klaidų logas: wp-content/uploads/dk-mail-watch/mail-failures.jsonl
 *   (katalogas apsaugotas .htaccess "Require all denied"; saugomi paskutiniai ~200 įrašų).
 * Endpoint'as: GET /wp-json/daiktuva/v1/mail-health
 *   Header: X-DK-Watch-Token: <DAIKTUVA_WATCH_TOKEN iš .env>
 *   Grąžina: last_success (ts), last_attempt (ts), failures_24h, failures[].
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function dk_mw_dir(): string {
	$up = wp_upload_dir();
	return trailingslashit( $up['basedir'] ) . 'dk-mail-watch';
}

function dk_mw_logfile(): string {
	return dk_mw_dir() . '/mail-failures.jsonl';
}

/** Užtikrina, kad logų katalogas egzistuoja ir nėra pasiekiamas per web. */
function dk_mw_ensure_dir(): void {
	$dir = dk_mw_dir();
	if ( ! is_dir( $dir ) ) {
		wp_mkdir_p( $dir );
	}
	$ht = $dir . '/.htaccess';
	if ( ! file_exists( $ht ) ) {
		// Apache 2.4 + 2.2 sintaksė — failai per HTTP nepasiekiami.
		file_put_contents( $ht, "Require all denied\n<IfModule !mod_authz_core.c>\nOrder allow,deny\nDeny from all\n</IfModule>\n" );
	}
	$idx = $dir . '/index.php';
	if ( ! file_exists( $idx ) ) {
		file_put_contents( $idx, "<?php // Silence is golden.\n" );
	}
}

add_action( 'wp_mail_succeeded', static function ( $mail_data ) {
	update_option( 'dk_mail_last_success', time(), false );
} );

add_action( 'wp_mail_failed', static function ( $wp_error ) {
	try {
		dk_mw_ensure_dir();
		$file  = dk_mw_logfile();
		$to    = '';
		$error = ( $wp_error instanceof WP_Error ) ? $wp_error->get_error_message() : 'unknown';
		$data  = ( $wp_error instanceof WP_Error ) ? $wp_error->get_error_data() : null;
		if ( is_array( $data ) && ! empty( $data['to'] ) ) {
			$to = is_array( $data['to'] ) ? implode( ',', $data['to'] ) : (string) $data['to'];
		}
		// Saugome tik gavėjo DOMENĄ (be vardo) — pakanka diagnozei, mažiau PII loguose.
		$domain = '';
		if ( $to && false !== strpos( $to, '@' ) ) {
			$domain = substr( $to, strrpos( $to, '@' ) + 1 );
		}
		$row = array(
			'ts'      => time(),
			'domain'  => $domain,
			'subject' => ( is_array( $data ) && ! empty( $data['subject'] ) ) ? mb_substr( (string) $data['subject'], 0, 80 ) : '',
			'error'   => mb_substr( $error, 0, 300 ),
		);
		file_put_contents( $file, wp_json_encode( $row ) . "\n", FILE_APPEND | LOCK_EX );
		// Rotacija: paliekam paskutinius 200 įrašų.
		$lines = file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		if ( is_array( $lines ) && count( $lines ) > 200 ) {
			file_put_contents( $file, implode( "\n", array_slice( $lines, -200 ) ) . "\n", LOCK_EX );
		}
	} catch ( Throwable $e ) {
		// Mail-watch niekada neturi sugadinti pačio laiško siuntimo.
	}
} );

add_action( 'rest_api_init', static function () {
	register_rest_route( 'daiktuva/v1', '/mail-health', array(
		'methods'             => 'GET',
		'permission_callback' => static function ( WP_REST_Request $req ) {
			$token = getenv( 'DAIKTUVA_WATCH_TOKEN' );
			$given = (string) $req->get_header( 'x-dk-watch-token' );
			if ( ! $token || ! $given || ! hash_equals( (string) $token, $given ) ) {
				return new WP_Error( 'dk_mw_forbidden', 'Forbidden', array( 'status' => 403 ) );
			}
			return true;
		},
		'callback'            => static function () {
			$since    = time() - DAY_IN_SECONDS;
			$failures = array();
			$file     = dk_mw_logfile();
			if ( file_exists( $file ) ) {
				foreach ( (array) file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) as $line ) {
					$row = json_decode( $line, true );
					if ( is_array( $row ) && ! empty( $row['ts'] ) && $row['ts'] >= $since ) {
						$failures[] = $row;
					}
				}
			}
			return array(
				'last_success'  => (int) get_option( 'dk_mail_last_success', 0 ),
				'now'           => time(),
				'failures_24h'  => count( $failures ),
				'failures'      => $failures,
			);
		},
	) );
} );
