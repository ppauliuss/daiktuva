<?php
/**
 * Daiktuva: scoped full-page cache for product SEARCH results (Redis object cache).
 *
 * Search (`?s=`) bypasses Cache Enabler and re-renders the whole WP+WooCommerce+Dokan
 * page. We cache the full rendered HTML in Redis and serve it as early as possible.
 *
 * Two phases:
 *  - STORE: at `template_redirect` (full render), buffer the page and save to Redis.
 *  - EARLY SERVE: at `muplugins_loaded` (before WC/Dokan/theme load — the heavy part),
 *    serve the cached HTML directly. This is the safe equivalent of an advanced-cache.php
 *    drop-in (which we can't use — Cache Enabler owns advanced-cache.php).
 *
 * Tightly scoped so nothing personalized is served: GET only, logged-OUT only, bypassed
 * on any WooCommerce session/cart cookie, never in admin/ajax/cron/rest. Keyed by
 * query+page+sort+post_type, namespaced by the shared `dk_search_ver` counter (bumped on
 * product changes) + a short TTL — approved listings show immediately.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Cache key computed purely from $_GET, so the early-serve and store phases agree. */
function dk_fpsc_key(): string {
	$ver = (int) wp_cache_get( 'dk_search_ver', 'dk_search' );
	$s   = isset( $_GET['s'] ) ? trim( (string) wp_unslash( $_GET['s'] ) ) : '';
	$s   = function_exists( 'mb_strtolower' ) ? mb_strtolower( $s ) : strtolower( $s );
	$psl = isset( $_GET['psl'] ) ? max( 1, (int) $_GET['psl'] ) : 1;
	$rus = isset( $_GET['rus'] ) ? preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $_GET['rus'] ) ) : 'naujausi';
	$pt  = isset( $_GET['post_type'] ) ? preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $_GET['post_type'] ) ) : 'product';
	return 'full_' . $ver . '_' . md5( $s . '|' . $psl . '|' . $rus . '|' . $pt );
}

/** Eligibility checkable without WP query/pluggable (works at muplugins_loaded). */
function dk_fpsc_raw_eligible(): bool {
	if ( ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) !== 'GET' ) {
		return false;
	}
	if ( ! isset( $_GET['s'] ) ) {
		return false; // search requests only
	}
	if ( ( defined( 'WP_ADMIN' ) && WP_ADMIN )
		|| ( defined( 'DOING_AJAX' ) && DOING_AJAX )
		|| ( defined( 'DOING_CRON' ) && DOING_CRON )
		|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
		|| ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ) {
		return false;
	}
	$uri = $_SERVER['REQUEST_URI'] ?? '';
	if ( false !== strpos( $uri, '/wp-admin' ) || false !== strpos( $uri, '/wp-json' )
		|| false !== strpos( $uri, '/wp-login' ) || false !== strpos( $uri, 'wp-cron.php' ) ) {
		return false;
	}
	foreach ( array_keys( $_COOKIE ) as $name ) {
		if ( 0 === strpos( $name, 'wordpress_logged_in_' )
			|| 0 === strpos( $name, 'wp_woocommerce_session_' )
			|| 0 === strpos( $name, 'woocommerce_' )
			|| 0 === strpos( $name, 'comment_author_' ) ) {
			return false;
		}
	}
	return true;
}

/* EARLY SERVE — before WooCommerce/Dokan/theme load. */
add_action( 'muplugins_loaded', function () {
	if ( ! function_exists( 'wp_cache_get' ) || ! dk_fpsc_raw_eligible() ) {
		return;
	}
	$html = wp_cache_get( dk_fpsc_key(), 'dk_search' );
	if ( is_string( $html ) && '' !== $html ) {
		header( 'Content-Type: text/html; charset=UTF-8' );
		header( 'Cache-Control: no-store, private' );
		header( 'X-Daiktuva-Cache: HIT-early' );
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput
		exit;
	}
}, 0 );

/** Full eligibility for the STORE phase (query is parsed by now). */
function dk_fpsc_eligible(): bool {
	if ( is_admin() || wp_doing_ajax() || ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) ) {
		return false;
	}
	if ( ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) !== 'GET' || is_user_logged_in() ) {
		return false;
	}
	if ( ! is_search() || is_feed() ) {
		return false;
	}
	foreach ( array_keys( $_COOKIE ) as $name ) {
		if ( 0 === strpos( $name, 'wp_woocommerce_session_' ) || 0 === strpos( $name, 'woocommerce_' ) ) {
			return false;
		}
	}
	if ( function_exists( 'WC' ) ) {
		$wc = WC();
		if ( isset( $wc->cart ) && is_callable( array( $wc->cart, 'is_empty' ) ) && ! $wc->cart->is_empty() ) {
			return false;
		}
	}
	return true;
}

/* STORE (and template-level serve fallback) — at full render. */
add_action( 'template_redirect', function () {
	if ( ! dk_fpsc_eligible() ) {
		return;
	}
	$key  = dk_fpsc_key();
	$html = wp_cache_get( $key, 'dk_search' );

	header( 'Cache-Control: no-store, private' );

	if ( is_string( $html ) && '' !== $html ) {
		header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		header( 'X-Daiktuva-Cache: HIT' );
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput
		exit;
	}

	header( 'X-Daiktuva-Cache: MISS' );
	ob_start( function ( $buf ) use ( $key ) {
		$code = function_exists( 'http_response_code' ) ? http_response_code() : 200;
		if ( 200 === (int) $code && strlen( $buf ) > 500 ) {
			wp_cache_set( $key, $buf, 'dk_search', (int) apply_filters( 'dk_search_cache_ttl', 300 ) );
		}
		return $buf;
	} );
}, 99 );
