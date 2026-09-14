<?php
/**
 * Daiktuva: scoped full-page cache for anonymous CATALOG / HOMEPAGE / PRODUCT / TERM pages.
 *
 * Companion to daiktuva-search-fullcache.php (which handles `?s=` search). Same proven
 * Redis early-serve mechanism: STORE the rendered HTML at `template_redirect`, then EARLY
 * SERVE it at `muplugins_loaded` (before WooCommerce/Dokan/theme load — the heavy part).
 *
 * Why this exists: on this install Cache Enabler writes its disk cache but its
 * advanced-cache.php delivery never engages behind the cloudflared tunnel, so every
 * dynamic page was fully re-rendered (~10 req/s CPU ceiling). This serves the same role
 * we already use for search, lifting catalog/product capacity to the search path's level.
 *
 * Tightly scoped so nothing personalized is served: GET + logged-OUT only, bypassed on any
 * WooCommerce session/cart cookie, never admin/ajax/cron/rest/feed/login, never cart/
 * checkout/account. `Cache-Control: no-store` so Redis is the only cache layer (instant
 * invalidation). Keyed by path + page/sort; tracking params (utm_*, fbclid…) are ignored so
 * campaign traffic still hits. Namespaced by `dk_page_ver` (bumped on any content change)
 * + a 300s TTL backstop, so approved listings and edits appear within seconds.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

const DK_PFC_GROUP  = 'dk_search';
const DK_PFC_VERKEY = 'dk_page_ver';

function dk_pfc_ver(): int {
	$v = wp_cache_get( DK_PFC_VERKEY, DK_PFC_GROUP );
	if ( false === $v ) { $v = 1; wp_cache_set( DK_PFC_VERKEY, $v, DK_PFC_GROUP ); }
	return (int) $v;
}

function dk_pfc_bump(): void {
	if ( ! function_exists( 'wp_cache_incr' ) ) { return; }
	if ( false === wp_cache_incr( DK_PFC_VERKEY, 1, DK_PFC_GROUP ) ) {
		wp_cache_set( DK_PFC_VERKEY, dk_pfc_ver() + 1, DK_PFC_GROUP );
	}
}

/** Key from the raw request: path + all content-affecting catalog params. Tracking params ignored. */
function dk_pfc_key(): string {
	$uri  = $_SERVER['REQUEST_URI'] ?? '/';
	$path = parse_url( $uri, PHP_URL_PATH );
	$path = is_string( $path ) ? $path : '/';
	$params = array(
		'psl'     => isset( $_GET['psl'] ) ? max( 1, (int) $_GET['psl'] ) : ( isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1 ),
		'rus'     => isset( $_GET['rus'] ) ? sanitize_key( wp_unslash( $_GET['rus'] ) ) : '',
		'kn'      => isset( $_GET['kn'] ) ? (float) str_replace( ',', '.', sanitize_text_field( wp_unslash( $_GET['kn'] ) ) ) : 0,
		'ki'      => isset( $_GET['ki'] ) ? (float) str_replace( ',', '.', sanitize_text_field( wp_unslash( $_GET['ki'] ) ) ) : 0,
		'miestas' => isset( $_GET['miestas'] ) ? sanitize_text_field( wp_unslash( $_GET['miestas'] ) ) : '',
		'kat'     => isset( $_GET['kat'] ) ? sanitize_title( wp_unslash( $_GET['kat'] ) ) : '',
	);
	return 'page_' . dk_pfc_ver() . '_' . md5( $path . '|' . wp_json_encode( $params ) );
}

/** Eligibility checkable without the WP query (works at muplugins_loaded). */
function dk_pfc_raw_eligible(): bool {
	if ( ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) !== 'GET' ) {
		return false;
	}
	if ( isset( $_GET['s'] ) ) {
		return false; // search is handled by daiktuva-search-fullcache.php
	}
	foreach ( array( 'dk_reply', 'tpl', 'dk_verify', 'dk_resend_verify', 'dk_verified', 'add-to-cart', 'wc-ajax', 'preview', 'replytocom', 'unapproved', 'customize_changeset_uuid', 'p', 'page_id' ) as $k ) {
		if ( isset( $_GET[ $k ] ) ) {
			return false;
		}
	}
	if ( ( defined( 'WP_ADMIN' ) && WP_ADMIN )
		|| ( defined( 'DOING_AJAX' ) && DOING_AJAX )
		|| ( defined( 'DOING_CRON' ) && DOING_CRON )
		|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
		|| ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ) {
		return false;
	}
	$uri  = $_SERVER['REQUEST_URI'] ?? '';
	$path = (string) ( parse_url( $uri, PHP_URL_PATH ) ?: '/' );
	foreach ( array( '/wp-admin', '/wp-json', '/wp-login', 'wp-cron.php', '/feed', 'prisijungimas' ) as $frag ) {
		if ( false !== strpos( $uri, $frag ) ) {
			return false;
		}
	}
	if ( preg_match( '#\.(xml|txt|ico|json|rss|atom|css|js|png|jpe?g|gif|webp|svg|pdf)$#i', $path ) ) {
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
	if ( ! function_exists( 'wp_cache_get' ) || ! dk_pfc_raw_eligible() ) {
		return;
	}
	$html = wp_cache_get( dk_pfc_key(), DK_PFC_GROUP );
	if ( is_string( $html ) && '' !== $html ) {
		header( 'Content-Type: text/html; charset=UTF-8' );
		header( 'Cache-Control: no-store, private' );
		header( 'X-Daiktuva-Cache: HIT-page-early' );
		// CSP Report-Only (daiktuva-security.php): sis kelias eina prio 0, pries muplugins_loaded prio 1.
		if ( function_exists( 'dk_csp_send_header' ) ) { dk_csp_send_header(); }
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput
		exit;
	}
}, 0 );

/** Full eligibility for the STORE phase (the WP query is parsed by now). */
function dk_pfc_store_eligible(): bool {
	if ( is_admin() || wp_doing_ajax() || ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) ) {
		return false;
	}
	if ( ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) !== 'GET' || is_user_logged_in() ) {
		return false;
	}
	if ( is_search() || is_feed() || is_404() || is_preview() ) {
		return false;
	}
	if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) {
		return false; // personalized / stateful WooCommerce pages
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
	$ok = is_front_page() || is_home()
		|| is_singular( 'product' ) || is_post_type_archive( 'product' )
		|| ( function_exists( 'is_product_category' ) && ( is_product_category() || is_product_tag() ) )
		|| is_page() || is_tax() || is_category() || is_tag() || is_archive() || is_singular();
	return (bool) $ok;
}

add_action( 'template_redirect', function () {
	if ( ! dk_pfc_store_eligible() ) {
		return;
	}
	$key  = dk_pfc_key();
	$html = wp_cache_get( $key, DK_PFC_GROUP );

	header( 'Cache-Control: no-store, private' );

	if ( is_string( $html ) && '' !== $html ) {
		header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		header( 'X-Daiktuva-Cache: HIT-page' );
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput
		exit;
	}

	header( 'X-Daiktuva-Cache: MISS-page' );
	ob_start( function ( $buf ) use ( $key ) {
		$code = function_exists( 'http_response_code' ) ? http_response_code() : 200;
		if ( 200 === (int) $code && strlen( $buf ) > 500 ) {
			wp_cache_set( $key, $buf, DK_PFC_GROUP, (int) apply_filters( 'dk_page_cache_ttl', 300 ) );
		}
		return $buf;
	} );
}, 99 );

/* INVALIDATION — bump the namespace on any content change so new/edited content shows fast. */
foreach ( array(
	'save_post', 'deleted_post', 'trashed_post', 'untrashed_post', 'woocommerce_update_product',
	'created_term', 'edited_term', 'delete_term', 'switch_theme', 'customize_save_after',
) as $dk_pfc_hook ) {
	add_action( $dk_pfc_hook, 'dk_pfc_bump' );
}
