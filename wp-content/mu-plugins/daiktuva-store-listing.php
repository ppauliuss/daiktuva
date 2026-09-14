<?php
/**
 * Daiktuva: pardavėjų sąrašo (/store-listing/) sutvarkymas (2026-08-19, dizaino auditas).
 *  1) Rodomi tik pardavėjai su ≥1 publikuotu skelbimu — testinės/tuščios paskyros
 *     (curl_seller, „Vilnius" ir t.t.) nebeatsiduria viešame sąraše. Veikia ir
 *     AJAX paieškai/filtrams (dokan_seller_listing_search_args).
 *  2) Adresas ir telefonas kortelėse paslėpti (privatumas) — CSS; papildomai
 *     dokan_appearance.hide_vendor_info nustatyta DB lygmeniu (store puslapiams).
 *  3) Violetinė Dokan akcentų spalva perdažyta į Daiktuva mėlyną.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* 1) Tik pardavėjai su bent vienu publikuotu skelbimu. */
add_filter( 'dokan_seller_listing_args', static function ( $args ) {
	$args['has_published_posts'] = array( 'product' );
	return $args;
} );
add_filter( 'dokan_seller_listing_search_args', static function ( $args ) {
	$args['has_published_posts'] = array( 'product' );
	return $args;
} );

/** Naujausio aktyvaus skelbimo santrauka pardavėjo kortelei. */
function dk_store_listing_meta( int $uid ): array {
	$ids = get_posts( array(
		'post_type' => 'product', 'post_status' => 'publish', 'author' => $uid,
		'posts_per_page' => 1, 'orderby' => 'date', 'order' => 'DESC', 'fields' => 'ids',
	) );
	if ( ! $ids ) { return array(); }
	$id   = (int) $ids[0];
	$city = trim( (string) get_post_meta( $id, '_dk_location', true ) );
	$cats = wp_get_post_terms( $id, 'product_cat', array( 'fields' => 'names' ) );
	return array( 'city' => $city, 'category' => ! is_wp_error( $cats ) && $cats ? (string) $cats[0] : '' );
}

/* Anoniminiams pardavėjams suteikiame atskiriamą, bet privataus vardo neatskleidžiantį pavadinimą. */
add_filter( 'dokan_vendor_shop_data', static function ( $shop_info, $vendor = null ) {
	if ( ! is_page( array( 'store-listing', 'pardavejai' ) ) || empty( $shop_info['store_name'] ) || 'Privatus pardavėjas' !== trim( $shop_info['store_name'] ) ) {
		return $shop_info;
	}
	$uid  = is_object( $vendor ) && method_exists( $vendor, 'get_id' ) ? (int) $vendor->get_id() : 0;
	$meta = $uid ? dk_store_listing_meta( $uid ) : array();
	if ( ! empty( $meta['city'] ) ) {
		$shop_info['store_name'] = 'Pardavėjas • ' . $meta['city'];
	} elseif ( ! empty( $meta['category'] ) ) {
		$shop_info['store_name'] = 'Pardavėjas • ' . $meta['category'];
	} else {
		$shop_info['store_name'] = 'Daiktuva pardavėjas';
	}
	return $shop_info;
}, 20, 2 );

/* 1b) „Total stores showing: %s" eina per _n() → 'ngettext' filtrą
       (ne 'gettext' — todėl lt-strings žemėlapis jo nepagauna). */
add_filter( 'ngettext', static function ( $translation, $single, $plural, $number, $domain ) {
	if ( 'dokan-lite' === $domain && 'Total store showing: %s' === $single ) {
		return 'Iš viso parduotuvių: %s';
	}
	return $translation;
}, 20, 5 );

/* 2)+3) Adresas/telefonas paslėpti, violetinė → Daiktuva mėlyna. */
add_action( 'wp_head', static function () {
	if ( is_admin() ) {
		return;
	}
	echo '<style id="dk-store-listing">'
		. '#dokan-seller-listing-wrap .store-address,'
		. '#dokan-seller-listing-wrap .store-phone{display:none !important}'
		. '.dokan-store-list-filter-button.dokan-btn-theme{background:#1e3a5f !important;border-color:#1e3a5f !important}'
		. '.dokan-store-list-filter-button.dokan-btn-theme:hover{background:#152c48 !important;border-color:#152c48 !important}'
		. '#dokan-seller-listing-wrap .store-footer .dokan-btn-theme,'
		. '#dokan-seller-listing-wrap .store-footer a.dokan-btn-theme{background:#1e3a5f !important;border-color:#1e3a5f !important;color:#fff !important}'
		. '#dokan-seller-listing-wrap .store-footer .dokan-btn-theme:hover{background:#152c48 !important;border-color:#152c48 !important}'
		. '</style>';
}, 20 );

/* 4) 2026-08-20: firminiai default baneris/avataras (SVG su parduotuvės
 *    pavadinimu ir inicialais). Anksčiau kortelės rodydavo pilką Dokan
 *    placeholder'į (default-store-banner.png + mystery-person.jpg) — puslapis
 *    atrodydavo "sugadintas". Pardavėjui įkėlus savo banerį/avatarą —
 *    rodomas jo (filtrai gauna default'ą tik kai savo nėra). */
function dk_store_svg_uri( string $svg ): string {
	return 'data:image/svg+xml;base64,' . base64_encode( $svg );
}

function dk_store_initials( string $name ): string {
	$parts = preg_split( '/\s+/u', trim( $name ) ) ?: array();
	$i = '';
	foreach ( array_slice( $parts, 0, 2 ) as $w ) {
		$i .= mb_strtoupper( mb_substr( $w, 0, 1 ) );
	}
	return $i !== '' ? $i : 'D';
}

add_filter( 'dokan_get_banner_url', static function ( $url, $vendor ) {
	$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="260" viewBox="0 0 800 260">'
		. '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
		. '<stop offset="0" stop-color="#1e3a5f"/><stop offset="1" stop-color="#2f619c"/></linearGradient></defs>'
		. '<rect width="800" height="260" fill="url(#g)"/>'
		. '<circle cx="700" cy="-50" r="160" fill="#ffffff" opacity="0.07"/>'
		. '<circle cx="60" cy="300" r="180" fill="#ea7a1f" opacity="0.16"/>'
		. '<path d="M330 90h140a18 18 0 0 1 18 18v75H312v-75a18 18 0 0 1 18-18z" fill="#fff" opacity=".12"/>'
		. '<path d="M350 90V72h100v18" fill="none" stroke="#fff" stroke-width="12" opacity=".18"/>'
		. '</svg>';
	return dk_store_svg_uri( $svg );
}, 10, 2 );

add_filter( 'dokan_get_avatar_url', static function ( $url, $vendor ) {
	$name = 'D';
	if ( $vendor instanceof \WeDevs\Dokan\Vendor\Vendor ) {
		$n = $vendor->get_shop_name();
		if ( $n ) { $name = $n; }
	}
	$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="150" height="150" viewBox="0 0 150 150">'
		. '<circle cx="75" cy="75" r="75" fill="#ea7a1f"/>'
		. '<text x="75" y="97" font-family="Arial,Helvetica,sans-serif" font-size="56" font-weight="700" fill="#ffffff" text-anchor="middle">'
		. htmlspecialchars( dk_store_initials( $name ), ENT_QUOTES ) . '</text></svg>';
	return dk_store_svg_uri( $svg );
}, 10, 2 );

/* 5) Informatyvesnė kortelė: skelbimų skaičius, vieta, kategorija ir paskyros metai. */
add_action( 'dokan_seller_listing_after_store_data', static function ( $seller, $store_info ) {
	$uid = is_object( $seller ) && ! empty( $seller->ID ) ? (int) $seller->ID : 0;
	if ( ! $uid ) {
		return;
	}
	$n = (int) count_user_posts( $uid, 'product', true );
	$meta = dk_store_listing_meta( $uid );
	$user = get_userdata( $uid );
	$year = $user ? wp_date( 'Y', strtotime( $user->user_registered ) ) : '';
	$bits = array();
	if ( ! empty( $meta['city'] ) ) { $bits[] = $meta['city']; }
	if ( ! empty( $meta['category'] ) ) { $bits[] = $meta['category']; }
	echo '<div class="dk-store-card-meta">';
	if ( $n > 0 ) {
		$word = function_exists( 'dk_skelbimai_lt' ) ? dk_skelbimai_lt( $n ) : 'skelb.';
		echo '<strong>' . esc_html( $n . ' ' . $word ) . '</strong>';
	}
	if ( $bits ) { echo '<span>' . esc_html( implode( ' • ', array_unique( $bits ) ) ) . '</span>'; }
	if ( $year ) { echo '<span>Daiktuvoje nuo ' . esc_html( $year ) . ' m.</span>'; }
	if ( get_user_meta( $uid, 'dk_email_verified', true ) ) {
		echo '<span class="dk-verified-seller" title="Pardavėjas patvirtino savo el. pašto adresą">✓ El. paštas patvirtintas</span>';
	}
	echo '</div>';
}, 10, 2 );
