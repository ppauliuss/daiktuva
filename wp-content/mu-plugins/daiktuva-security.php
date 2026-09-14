<?php
/**
 * Daiktuva: public hardening that should survive theme/plugin changes.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* Do not expose login names through public REST user endpoints. */
add_filter( 'rest_pre_dispatch', function ( $result, $server, $request ) {
	$route = $request->get_route();
	$dk_is_own_me = is_user_logged_in() && preg_match( '#^/wp/v2/users/me(?:/|$)#', $route );
	if ( preg_match( '#^/wp/v2/users(?:/|$)#', $route ) && ! current_user_can( 'list_users' ) && ! $dk_is_own_me ) {
		return new WP_Error(
			'rest_no_route',
			__( 'No route was found matching the URL and request method.', 'daiktuva' ),
			array( 'status' => 404 )
		);
	}
	return $result;
}, 10, 3 );

/* Remove user endpoints from the public REST index too. */
add_filter( 'rest_endpoints', function ( $endpoints ) {
	if ( current_user_can( 'list_users' ) ) {
		return $endpoints;
	}
	foreach ( array_keys( $endpoints ) as $route ) {
		if ( '/wp/v2/users/me' === $route ) {
			continue;
		}
		if ( preg_match( '#^/wp/v2/users(?:/|$)#', $route ) ) {
			unset( $endpoints[ $route ] );
		}
	}
	return $endpoints;
} );

/* Author archives reveal login slugs; Dokan store pages remain the seller profile. */
add_action( 'template_redirect', function () {
	if ( is_author() ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
		include get_query_template( '404' );
		exit;
	}
}, 1 );

/* Keep the old human-readable sellers URL working. */
add_action( 'template_redirect', function () {
	$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
	$redirects = array(
		'pardavejai'      => '/store-listing/',
		'mano-paskyra'   => '/my-account/',
		'tapk-pardaveju' => '/vendor-onboarding/',
		'mano-uzsakymai' => '/my-orders/',
		'skydelis'       => '/dashboard/',
		'parduotuve'     => '/shop/',
		'krepselis'      => '/cart/',
		'apmokejimas'    => '/checkout/',
	);
	if ( isset( $redirects[ $path ] ) ) {
		wp_safe_redirect( home_url( $redirects[ $path ] ), 301 );
		exit;
	}
}, 2 );

/* Simple login throttling by IP. WPS Hide Login hides the form; this limits guessing. */
if ( ! function_exists( 'dk_login_rate_key' ) ) {
	function dk_login_rate_key(): string {
		$ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
		return 'dk_login_fail_' . md5( (string) $ip );
	}
}

add_filter( 'authenticate', function ( $user ) {
	$fails = (int) get_transient( dk_login_rate_key() );
	if ( $fails >= 8 ) {
		return new WP_Error(
			'too_many_login_attempts',
			__( 'Per daug bandymu prisijungti. Bandykite veliau.', 'daiktuva' )
		);
	}
	return $user;
}, 1 );

add_action( 'wp_login_failed', function () {
	$key   = dk_login_rate_key();
	$fails = (int) get_transient( $key );
	set_transient( $key, $fails + 1, 15 * MINUTE_IN_SECONDS );
} );

add_action( 'wp_login', function () {
	delete_transient( dk_login_rate_key() );
} );

/* ---------------------------------------------------------------------------
 * CSP (2026-08-18): Report-Only režimas — pirmiausia stebime pažeidimus,
 * tada (peržiūrėjus log'us) pereisime prie griežto CSP.
 * Siunciama per muplugins_loaded prio 1, kad veiktu ir su page-fullcache
 * EARLY SERVE (jis isveda HTML ir exit'ina dar pries send_headers).
 * Pranesimu rinkimas: POST /wp-json/dk/v1/csp-report -> docker logs.
 * ------------------------------------------------------------------------- */
function dk_csp_policy(): string {
	return implode( '; ', array(
		"default-src 'self'",
		"script-src 'self' 'unsafe-inline' https://www.googletagmanager.com https://www.google-analytics.com https://challenges.cloudflare.com https://static.cloudflareinsights.com https://connect.facebook.net",
		"style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
		"img-src 'self' data: https:",
		"font-src 'self' data: https://fonts.gstatic.com",
		"connect-src 'self' https://www.google-analytics.com https://region1.google-analytics.com https://challenges.cloudflare.com https://www.facebook.com",
		"frame-src https://challenges.cloudflare.com https://www.facebook.com https://web.facebook.com",
		"frame-ancestors 'self'",
		"base-uri 'self'",
		"form-action 'self'",
	) );
}

function dk_csp_is_frontend_html(): bool {
	if ( ( defined( 'WP_ADMIN' ) && WP_ADMIN )
		|| ( defined( 'DOING_AJAX' ) && DOING_AJAX )
		|| ( defined( 'DOING_CRON' ) && DOING_CRON )
		|| ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}
	$uri = (string) ( $_SERVER['REQUEST_URI'] ?? '' );
	if ( ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) !== 'GET' ) {
		return false;
	}
	foreach ( array( '/wp-admin', '/wp-json', '/wp-login', 'wp-cron.php', '/feed' ) as $frag ) {
		if ( false !== strpos( $uri, $frag ) ) {
			return false;
		}
	}
	return true;
}

function dk_csp_send_header(): void {
	header( 'Content-Security-Policy-Report-Only: ' . dk_csp_policy() . "; report-uri " . home_url( '/wp-json/dk/v1/csp-report' ) );
}

add_action( 'muplugins_loaded', function () {
	if ( ! dk_csp_is_frontend_html() ) {
		return;
	}
	dk_csp_send_header();
}, 1 );

/* CSP pažeidimų rinktuvas -> docker logs (su lubomis: 30/min, kad nespamdintų). */
add_action( 'rest_api_init', function () {
	register_rest_route( 'dk/v1', '/csp-report', array(
		'methods'             => 'POST',
		'permission_callback' => '__return_true',
		'callback'            => function () {
			$n = (int) get_transient( 'dk_csp_rep_min' );
			if ( $n < 30 ) {
				set_transient( 'dk_csp_rep_min', $n + 1, MINUTE_IN_SECONDS );
				$body = (string) file_get_contents( 'php://input' );
				error_log( 'CSP-REPORT: ' . substr( $body, 0, 1000 ) ); // phpcs:ignore
			}
			return new WP_REST_Response( null, 204 );
		},
	) );
} );

/* security.txt (RFC 9116) — skeneriai jo klausia, tegul gauna tvarkingą atsakymą. */
add_action( 'template_redirect', function () {
	$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
	if ( '.well-known/security.txt' !== $path ) {
		return;
	}
	status_header( 200 ); // WP šiam keliui jau užstatė 404 — grąžiname 200.
	header( 'Content-Type: text/plain; charset=UTF-8' );
	header( 'Cache-Control: public, max-age=86400' );
	echo "Contact: mailto:info@daiktuva.lt\n";
	echo "Expires: 2027-08-18T00:00:00.000Z\n";
	echo "Preferred-Languages: lt, en\n";
	exit;
}, 3 );
