<?php
/**
 * Daiktuva: Google organic and Merchant Center helpers.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'init', function () {
	add_rewrite_rule( '^merchant-feed\.xml$', 'index.php?dk_merchant_feed=1', 'top' );
} );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'dk_merchant_feed';
	return $vars;
} );

function dk_google_utility_page_ids(): array {
	$ids = array();
	foreach ( array( 'cart', 'checkout', 'my-account', 'dashboard', 'my-orders', 'vendor-onboarding', 'mano-daiktuva' ) as $path ) {
		$page = get_page_by_path( $path, OBJECT, 'page' );
		if ( $page ) {
			$ids[] = (int) $page->ID;
		}
	}

	return array_values( array_unique( array_filter( $ids ) ) );
}

/* 2026-08-18: kanoninis sitemap — Rank Math (su lastmod ir image zymomis).
 * WP core wp-sitemap.xml isjungtas, kad nedubliuotu. Utility puslapiai
 * (cart/checkout/my-account/...) Rank Math sitemap'e paslepti per ju
 * rank_math_robots=noindex meta (ir taip teisingiau — Google gauna tiesiogini signala). */
add_filter( 'wp_sitemaps_enabled', '__return_false' );

/* 2026-08-18: Rank Math atgavo pilnai (po rank_math_registration_skip), bet jo
 * frontend isvestis dubliuotu musu rankomis derintas zymes (og, twitter, schema).
 * Kanonines lieka musu; RM paliekamas sitemap'ui + puslapiu meta description. */
add_action( 'wp_head', function () {
	// RM OG/Twitter/Slack zymiu spausdintuvai (og:title/url/type/... musu:
	// daiktuva-og.php + produktu/irasu blokai siame faile).
	remove_all_actions( 'rank_math/opengraph/facebook' );
	remove_all_actions( 'rank_math/opengraph/twitter' );
	remove_all_actions( 'rank_math/opengraph/slack' );
}, 0 );

/* Meta description: RM tegu spausdina tik paprastiems puslapiams (apie, taisykles...);
 * homepage/produktai/irasai turi rankomis rasytus description'us zemiau.
 * Prio 99: RM Woo modulis ta pati filtra uzsikabina veliau (pluginai kraunami po mu-pluginu). */
add_filter( 'rank_math/frontend/description', function ( $desc ) {
	if ( is_front_page() || is_singular( array( 'product', 'post' ) ) ) {
		return '';
	}
	return $desc;
}, 99 );

/* RM konstruktoriuje remove_all_filters('wp_robots') numusa ir Woo cart/checkout
 * noindex — graziname noindex visiems utility puslapiams per RM filtra (prio 99,
 * po RM Woo modulio robots callback'o; RM laukia rakto 'index' => 'noindex'). */
add_filter( 'rank_math/frontend/robots', function ( $robots ) {
	if ( is_singular() && in_array( get_queried_object_id(), dk_google_utility_page_ids(), true ) ) {
		$robots['index'] = 'noindex';
	}
	return $robots;
}, 99 );

add_filter( 'wp_sitemaps_posts_query_args', function ( $args, $post_type ) {
	if ( 'page' !== $post_type ) {
		return $args;
	}

	$exclude = dk_google_utility_page_ids();
	if ( ! $exclude ) {
		return $args;
	}

	$args['post__not_in'] = array_values( array_unique( array_merge( $args['post__not_in'] ?? array(), $exclude ) ) );
	return $args;
}, 10, 2 );

add_filter( 'wp_robots', function ( $robots ) {
	if ( ! is_page( dk_google_utility_page_ids() ) ) {
		return $robots;
	}

	$robots['noindex'] = true;
	$robots['follow'] = true;
	return $robots;
} );

add_action( 'template_redirect', function () {
	if ( get_query_var( 'dk_merchant_feed' ) ) {
		dk_render_merchant_feed();
		exit;
	}
} );

function dk_schema_url_with_fragment( string $path, string $fragment ): string {
	return home_url( trailingslashit( $path ) . '#' . $fragment );
}

function dk_merchant_return_policy_id(): string {
	return dk_schema_url_with_fragment( '/taisykles/', 'merchant-return-policy' );
}

function dk_shipping_service_id(): string {
	return dk_schema_url_with_fragment( '/pristatymas-ir-grazinimas/', 'shipping-service' );
}

function dk_merchant_return_policy_schema(): array {
	return array(
		'@type'                => 'MerchantReturnPolicy',
		'@id'                  => dk_merchant_return_policy_id(),
		'applicableCountry'    => 'LT',
		'returnPolicyCategory' => 'https://schema.org/MerchantReturnNotPermitted',
	);
}

function dk_shipping_service_schema(): array {
	return array(
		'@type'              => 'ShippingService',
		'@id'                => dk_shipping_service_id(),
		'name'               => 'Daiktuva skelbimu pristatymo politika',
		'description'        => 'Daiktuva yra skelbimu platforma. Prekes atsiemimas arba pristatymas derinamas tiesiogiai su pardaveju.',
		'fulfillmentType'    => 'https://schema.org/FulfillmentTypeDelivery',
		'shippingConditions' => array(
			array(
				'@type'               => 'ShippingConditions',
				'shippingDestination' => array(
					'@type'          => 'DefinedRegion',
					'addressCountry' => 'LT',
				),
				'doesNotShip'         => true,
			),
		),
	);
}

function dk_merchant_organization_schema(): array {
	$logo = '';
	$logo_id = (int) get_theme_mod( 'custom_logo' );
	if ( $logo_id ) {
		$logo = wp_get_attachment_image_url( $logo_id, 'full' );
	}
	if ( ! $logo && function_exists( 'get_site_icon_url' ) ) {
		$logo = get_site_icon_url( 512 );
	}

	$schema = array(
		'@context'                => 'https://schema.org',
		'@type'                   => 'Organization',
		'@id'                     => home_url( '/#organization' ),
		'name'                    => 'Daiktuva',
		'url'                     => home_url( '/' ),
		'description'             => 'Pirk ir parduok naudotus daiktus paprastai.',
		'email'                   => 'info@daiktuva.lt',
		'areaServed'              => array(
			'@type' => 'Country',
			'name'  => 'Lietuva',
		),
		'contactPoint'            => array(
			'@type'             => 'ContactPoint',
			'contactType'       => 'customer support',
			'email'             => 'info@daiktuva.lt',
			'areaServed'        => 'LT',
			'availableLanguage' => array( 'Lithuanian', 'lt' ),
		),
		'hasMerchantReturnPolicy' => dk_merchant_return_policy_schema(),
		'hasShippingService'      => dk_shipping_service_schema(),
	);

	if ( $logo ) {
		$schema['logo']  = $logo;
		$schema['image'] = $logo;
	}

	return $schema;
}

function dk_enrich_offer_schema( array $offer ): array {
	if ( empty( $offer['hasMerchantReturnPolicy'] ) ) {
		$offer['hasMerchantReturnPolicy'] = array(
			'@id' => dk_merchant_return_policy_id(),
		);
	}

	if ( empty( $offer['shippingDetails'] ) ) {
		$offer['shippingDetails'] = array(
			'@type'              => 'OfferShippingDetails',
			'hasShippingService' => array(
				'@id' => dk_shipping_service_id(),
			),
		);
	}

	if ( empty( $offer['itemCondition'] ) ) {
		$offer['itemCondition'] = 'https://schema.org/UsedCondition';
	}

	return $offer;
}

add_filter( 'woocommerce_structured_data_product', function ( $markup, $product ) {
	if ( ! $product instanceof WC_Product || ! is_array( $markup ) ) {
		return $markup;
	}

	$product_id = $product->get_id();
	$categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'names' ) );

	$markup['itemCondition'] = 'https://schema.org/UsedCondition';
	$markup['brand'] = array(
		'@type' => 'Brand',
		'name'  => 'Naudotas daiktas',
	);

	if ( ! is_wp_error( $categories ) && $categories ) {
		$markup['category'] = implode( ' > ', $categories );
	}

	if ( isset( $markup['offers'] ) && is_array( $markup['offers'] ) ) {
		if ( array_is_list( $markup['offers'] ) ) {
			$markup['offers'] = array_map(
				static fn ( $offer ) => is_array( $offer ) ? dk_enrich_offer_schema( $offer ) : $offer,
				$markup['offers']
			);
		} else {
			$markup['offers'] = dk_enrich_offer_schema( $markup['offers'] );
		}
	}

	return $markup;
}, 20, 2 );

add_action( 'wp_head', function () {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	$product_id = get_queried_object_id();
	$product = $product_id ? wc_get_product( $product_id ) : null;
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$description = wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() );
	if ( $description ) {
		$description = mb_substr( $description, 0, 160 );
		echo '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $description ) . '" />' . "\n";
		echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '" />' . "\n";
	}

	$image = wp_get_attachment_image_url( $product->get_image_id(), 'full' );
	if ( $image ) {
		echo '<meta property="og:image" content="' . esc_url( $image ) . '" />' . "\n";
		echo '<meta name="twitter:image" content="' . esc_url( $image ) . '" />' . "\n";
	}
}, 2 );

add_action( 'wp_head', function () {
	if ( ! is_singular( 'post' ) ) {
		return;
	}

	$post_id = get_queried_object_id();
	if ( ! $post_id ) {
		return;
	}

	$title = wp_strip_all_tags( get_the_title( $post_id ) );
	$description = get_the_excerpt( $post_id );
	if ( ! $description ) {
		$description = wp_strip_all_tags( get_post_field( 'post_content', $post_id ) );
	}
	$description = mb_substr( trim( preg_replace( '/\s+/', ' ', $description ) ), 0, 160 );
	$url = get_permalink( $post_id );
	$image = get_the_post_thumbnail_url( $post_id, 'large' );

	if ( $description ) {
		echo '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $description ) . '" />' . "\n";
		echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '" />' . "\n";
	}

	echo '<meta property="og:type" content="article" />' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
	echo '<meta property="og:url" content="' . esc_url( $url ) . '" />' . "\n";
	echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '" />' . "\n";

	if ( $image ) {
		echo '<meta property="og:image" content="' . esc_url( $image ) . '" />' . "\n";
		echo '<meta name="twitter:image" content="' . esc_url( $image ) . '" />' . "\n";
	}
}, 3 );

add_action( 'wp_head', function () {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	$schema = dk_merchant_organization_schema();
	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}, 30 );

function dk_render_merchant_feed(): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		status_header( 404 );
		return;
	}

	header( 'Content-Type: application/rss+xml; charset=' . get_option( 'blog_charset' ) );

	$q = new WP_Query( array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => 500,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'meta_query'     => array(
			array(
				'key'     => '_price',
				'value'   => '',
				'compare' => '!=',
			),
		),
	) );

	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	echo '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">' . "\n";
	echo '<channel>' . "\n";
	echo '<title>' . esc_xml( get_bloginfo( 'name' ) ) . ' produktai</title>' . "\n";
	echo '<link>' . esc_url( home_url( '/' ) ) . '</link>' . "\n";
	echo '<description>Daiktuva naudotu daiktu skelbimai</description>' . "\n";

	while ( $q->have_posts() ) {
		$q->the_post();
		$product = wc_get_product( get_the_ID() );
		if ( ! $product instanceof WC_Product ) {
			continue;
		}

		$image = wp_get_attachment_image_url( $product->get_image_id(), 'full' );
		$price = $product->get_price();
		if ( ! $image || $price === '' ) {
			continue;
		}

		$description = wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() );
		$description = $description ?: get_the_title();
		$categories = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) );
		$product_type = ( ! is_wp_error( $categories ) && $categories ) ? implode( ' > ', $categories ) : 'Naudoti daiktai';
		$availability = $product->is_in_stock() ? 'in_stock' : 'out_of_stock';

		echo '<item>' . "\n";
		echo '<g:id>' . esc_xml( (string) $product->get_id() ) . '</g:id>' . "\n";
		echo '<title>' . esc_xml( get_the_title() ) . '</title>' . "\n";
		echo '<description>' . esc_xml( mb_substr( $description, 0, 5000 ) ) . '</description>' . "\n";
		echo '<link>' . esc_url( get_permalink() ) . '</link>' . "\n";
		echo '<g:image_link>' . esc_url( $image ) . '</g:image_link>' . "\n";
		echo '<g:availability>' . esc_xml( $availability ) . '</g:availability>' . "\n";
		echo '<g:price>' . esc_xml( number_format( (float) $price, 2, '.', '' ) . ' EUR' ) . '</g:price>' . "\n";
		echo '<g:condition>used</g:condition>' . "\n";
		echo '<g:product_type>' . esc_xml( $product_type ) . '</g:product_type>' . "\n";
		echo '<g:identifier_exists>no</g:identifier_exists>' . "\n";
		echo '</item>' . "\n";
	}

	wp_reset_postdata();

	echo '</channel>' . "\n";
	echo '</rss>' . "\n";
}

add_action( 'wp_head', function () {
	if ( ! is_front_page() ) {
		return;
	}

	$title       = wp_strip_all_tags( get_bloginfo( 'name' ) );
	$tagline     = wp_strip_all_tags( get_bloginfo( 'description' ) );
	$description = 'Skelbimų portalas visai Lietuvai: žemės ūkio technika, įrankiai, buitinė technika, elektronika ir kiti naudoti daiktai. Įdėk skelbimą nemokamai!';
	$description = mb_substr( trim( preg_replace( '/\s+/', ' ', $description ) ), 0, 160 );
	$url         = home_url( '/' );

	$image   = '';
	$logo_id = (int) get_theme_mod( 'custom_logo' );
	if ( $logo_id ) {
		$image = wp_get_attachment_image_url( $logo_id, 'full' );
	}
	if ( ! $image && function_exists( 'get_site_icon_url' ) ) {
		$image = get_site_icon_url( 512 );
	}

	echo '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\n";
	echo '<meta property="og:type" content="website" />' . "\n";
	echo '<meta property="og:site_name" content="' . esc_attr( $title ) . '" />' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( $description ) . '" />' . "\n";
	echo '<meta property="og:url" content="' . esc_url( $url ) . '" />' . "\n";
	echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '" />' . "\n";
	echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '" />' . "\n";

	if ( $image ) {
		echo '<meta property="og:image" content="' . esc_url( $image ) . '" />' . "\n";
		echo '<meta name="twitter:image" content="' . esc_url( $image ) . '" />' . "\n";
	}
}, 2 );

add_action( 'wp_head', function () {
	if ( ! is_front_page() ) {
		return;
	}

	$website = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'WebSite',
		'@id'             => home_url( '/#website' ),
		'name'            => get_bloginfo( 'name' ),
		'url'             => home_url( '/' ),
		'inLanguage'      => 'lt-LT',
		'publisher'       => array( '@id' => home_url( '/#organization' ) ),
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => array(
				'@type'       => 'EntryPoint',
				'urlTemplate' => home_url( '/?s={search_term_string}&post_type=product' ),
			),
			'query-input' => 'required name=search_term_string',
		),
	);

	echo '<script type="application/ld+json">' . wp_json_encode( $website, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	echo '<script type="application/ld+json">' . wp_json_encode( dk_merchant_organization_schema(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}, 30 );
