<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* Stiliai: tevine (Astra) + vaikine */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'astra-parent-style', get_template_directory_uri() . '/style.css' );
	wp_enqueue_style( 'daiktuva-child-style', get_stylesheet_uri(), array( 'astra-parent-style' ), (string) filemtime( get_stylesheet_directory() . '/style.css' ) );
}, 15 );

/* Evergreen straipsniai: nerodyti datu/metos straipsniu puslapiuose ir sarasuose. */
add_filter( 'astra_single_post_meta_enabled', function ( $enabled ) {
	return is_singular( 'post' ) ? false : $enabled;
} );
add_filter( 'astra_blog_post_meta_enabled', '__return_false' );

/* 2026-08-20: pagrindinis puslapis yra rankomis rašomas HTML (+shortcode'ai) —
 * wpautop ten tik gadina markup'ą (prikiša <br /> formos viduje ir palikta </p>).
 * Turinyje visos pastraipos jau su <p> žymomis, todel front page'ui wpautop
 * isjungiamas. Jei kada nors puslapis bus perrasomas blokiniu redaktoriumi —
 * sia eilutę galima šalinti. */
add_action( 'wp', function () {
	if ( is_front_page() ) {
		remove_filter( 'the_content', 'wpautop' );
		remove_filter( 'the_content', 'shortcode_unautop' );
	}
} );

add_action( 'template_redirect', function () {
	if ( ! is_singular( 'post' ) ) {
		return;
	}

	ob_start( function ( $html ) {
		return preg_replace( '#<div class="entry-meta">.*?</div>#su', '', $html );
	} );
} );

/* WooCommerce: 4 stulpeliai, 12 prekiu puslapyje + galerijos slider/lightbox */
add_filter( 'loop_shop_columns', function () { return 4; }, 999 );
add_filter( 'loop_shop_per_page', function () { return 12; }, 999 );
add_action( 'after_setup_theme', function () {
	add_theme_support( 'wc-product-gallery-slider' );
	add_theme_support( 'wc-product-gallery-lightbox' );
}, 20 );

/* Kategoriju ikonos (lucide-stiliaus inline SVG, currentColor) */
function dk_cat_icon( string $slug ): string {
	$paths = array(
		'elektronika'           => '<path d="M13 2 4 14h6l-1 8 9-12h-6l1-8z"/>',
		'kompiuteriai'          => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>',
		'namai-ir-sodas'        => '<path d="M3 11 12 3l9 8"/><path d="M5 10v10h14V10"/>',
		'statyba-ir-irankiai'   => '<path d="M21 6.5a4.5 4.5 0 0 1-6 4.2L7 19a2 2 0 0 1-3-3l8.3-8A4.5 4.5 0 0 1 21 6.5z"/>',
		'telefonai-ir-ismanieji' => '<rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/>',
		'transportas'           => '<path d="M5 11l1.5-4.5A2 2 0 0 1 8.4 5h7.2a2 2 0 0 1 1.9 1.5L19 11"/><rect x="4" y="11" width="16" height="6" rx="1.5"/><path d="M7 17v2M17 17v2"/>',
		'vaikams'               => '<circle cx="12" cy="12" r="9"/><path d="M8.5 10h.01M15.5 10h.01M8 14c1 1.5 2.5 2 4 2s3-.5 4-2"/>',
		'zemes-ukis'            => '<path d="M12 21V9"/><path d="M12 9C12 5 9 3 5 3c0 4 3 6 7 6zM12 13c0-4 3-6 7-6 0 4-3 6-7 6z"/>',
		'kita'                  => '<path d="M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9-9-9z"/><circle cx="8" cy="8" r="1.5"/>',
	);
	$inner = isset( $paths[ $slug ] ) ? $paths[ $slug ] : $paths['kita'];
	return '<svg class="dk-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $inner . '</svg>';
}

/* Shortcode [daiktuva_cats] - kategoriju sarasas su skaiciais (skelbiu principu) */
add_shortcode( 'daiktuva_cats', function () {
	$terms = get_terms( array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'parent'     => 0,
		'orderby'    => 'name',
	) );
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return '';
	}
	$out = '<h3>Kategorijos</h3><ul class="dk-cats">';
	foreach ( $terms as $t ) {
		if ( 'uncategorized' === $t->slug ) {
			continue;
		}
		$out .= '<li><a href="' . esc_url( get_term_link( $t ) ) . '">'
			. dk_cat_icon( $t->slug )
			. '<span class="dk-cat-name">' . esc_html( $t->name ) . '</span>'
			. ' <span class="count">' . intval( $t->count ) . '</span></a></li>';
	}
	$out .= '</ul>';
	return $out;
} );

/* Paieskos juosta po header (vidiniuose puslapiuose; pradiniame paslepta CSS) */
add_action( 'astra_header_after', function () {
	if ( is_admin() ) {
		return;
	}
	$home = esc_url( home_url( '/' ) );
	$q    = esc_attr( get_search_query() );
	echo '<div class="dk-searchband"><div class="dk-sb-inner">'
		. '<form class="dk-search" role="search" method="get" action="' . $home . '">'
		. '<input type="search" name="s" aria-label="Ieškoti skelbimų" autocomplete="off" placeholder="Ko ieškote? Pvz. traktorius, dviratis, telefonas..." value="' . $q . '" />'
		. '<input type="hidden" name="post_type" value="product" />'
		. '<button type="submit">Ieškoti</button>'
		. '</form></div></div>';
} );

/* Footerio Astra/WordPress kreditas uzmaskuojamas per CSS (.ast-footer-copyright) - zr. style.css */

/* Reklamos zona ateičiai. Dabar išjungta, kad puslapyje nesimatytų tuščių reklamos blokų. */
add_shortcode( 'dk_ad', function ( $atts ) {
	return '';
	/*
	$atts  = shortcode_atts( array( 'type' => 'leader' ), $atts );
	$type  = preg_replace( '/[^a-z]/', '', strtolower( $atts['type'] ) );
	$code  = get_option( 'dk_adsense_' . $type, '' );
	$has_consent = isset( $_COOKIE['dk_consent'] ) && 'accepted' === sanitize_text_field( wp_unslash( $_COOKIE['dk_consent'] ) );
	$inner = ( $code && $has_consent ) ? $code : '<span class="dk-ad-label">Reklama</span>';
	return '<div class="dk-ad dk-ad-' . esc_attr( $type ) . '">' . $inner . '</div>';
	*/
} );

function dk_saved_storage_key(): string {
	if ( is_user_logged_in() ) {
		return 'user_' . get_current_user_id();
	}

	$visitor = isset( $_COOKIE['dk_visitor_id'] ) ? sanitize_key( wp_unslash( $_COOKIE['dk_visitor_id'] ) ) : '';
	if ( ! $visitor ) {
		$visitor = 'anon';
	}
	return 'visitor_' . $visitor;
}

function dk_get_saved_product_ids(): array {
	if ( is_user_logged_in() ) {
		$ids = get_user_meta( get_current_user_id(), 'dk_saved_products', true );
	} else {
		$ids = get_transient( 'dk_saved_' . dk_saved_storage_key() );
	}
	if ( ! is_array( $ids ) ) {
		$ids = array();
	}
	return array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
}

function dk_set_saved_product_ids( array $ids ): void {
	$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
	if ( is_user_logged_in() ) {
		update_user_meta( get_current_user_id(), 'dk_saved_products', $ids );
		return;
	}
	set_transient( 'dk_saved_' . dk_saved_storage_key(), $ids, 180 * DAY_IN_SECONDS );
}

function dk_saved_products_payload(): array {
	$items = array();
	foreach ( dk_get_saved_product_ids() as $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product || 'publish' !== get_post_status( $product_id ) ) {
			continue;
		}
		$items[] = array(
			'id'    => $product_id,
			'title' => get_the_title( $product_id ),
			'url'   => get_permalink( $product_id ),
		);
	}
	return $items;
}

function dk_commented_products_payload(): array {
	if ( ! is_user_logged_in() ) {
		return array();
	}
	$comments = get_comments( array(
		'user_id' => get_current_user_id(),
		'status'  => 'approve',
		'number'  => 30,
	) );
	$product_ids = array();
	foreach ( $comments as $comment ) {
		if ( 'product' === get_post_type( (int) $comment->comment_post_ID ) ) {
			$product_ids[] = (int) $comment->comment_post_ID;
		}
	}
	$product_ids = array_values( array_unique( $product_ids ) );
	$items = array();
	foreach ( $product_ids as $product_id ) {
		if ( 'publish' !== get_post_status( $product_id ) ) {
			continue;
		}
		$items[] = array(
			'id'    => $product_id,
			'title' => get_the_title( $product_id ),
			'url'   => get_permalink( $product_id ),
		);
	}
	return $items;
}

function dk_posted_products_payload(): array {
	if ( ! is_user_logged_in() ) {
		return array();
	}
	$q = new WP_Query( array(
		'post_type'      => 'product',
		'post_status'    => array( 'publish', 'pending', 'draft', 'private' ),
		'author'         => get_current_user_id(),
		'posts_per_page' => 30,
		'orderby'        => 'modified',
		'order'          => 'DESC',
		'fields'         => 'ids',
	) );

	$status_labels = array(
		'publish' => 'Paskelbtas',
		'pending' => 'Laukia patvirtinimo',
		'draft'   => 'Juodraštis',
		'private' => 'Privatus',
	);
	$items = array();
	foreach ( $q->posts as $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			continue;
		}
		$status = get_post_status( $product_id );
		$items[] = array(
			'id'     => (int) $product_id,
			'title'  => get_the_title( $product_id ),
			'url'    => 'publish' === $status ? get_permalink( $product_id ) : home_url( '/dashboard/' ),
			'price'  => $product->get_price_html(),
			'status' => $status_labels[ $status ] ?? $status,
		);
	}
	wp_reset_postdata();
	return $items;
}

function dk_saved_ajax_response(): void {
	wp_send_json_success( array(
		'saved'     => dk_saved_products_payload(),
		'posted'    => dk_posted_products_payload(),
		'commented' => dk_commented_products_payload(),
		'loggedIn'   => is_user_logged_in(),
	) );
}

add_action( 'wp_ajax_dk_saved_list', 'dk_saved_ajax_response' );
add_action( 'wp_ajax_nopriv_dk_saved_list', 'dk_saved_ajax_response' );

add_action( 'wp_ajax_dk_saved_sync', 'dk_saved_sync_ajax' );
add_action( 'wp_ajax_nopriv_dk_saved_sync', 'dk_saved_sync_ajax' );
function dk_saved_sync_ajax(): void {
	$existing = dk_get_saved_product_ids();
	$incoming = isset( $_POST['ids'] ) ? explode( ',', sanitize_text_field( wp_unslash( $_POST['ids'] ) ) ) : array();
	$ids = array_values( array_unique( array_merge( $existing, array_map( 'absint', $incoming ) ) ) );
	dk_set_saved_product_ids( $ids );
	dk_saved_ajax_response();
}

function dk_account_hub_items_html( array $items, string $empty ): string {
	if ( ! $items ) {
		return '<p class="dk-account-empty">' . esc_html( $empty ) . '</p>';
	}
	$out = '<div class="dk-account-list">';
	foreach ( $items as $item ) {
		$out .= '<a class="dk-account-item" href="' . esc_url( $item['url'] ) . '">';
		$out .= '<strong>' . esc_html( $item['title'] ) . '</strong>';
		if ( ! empty( $item['status'] ) || ! empty( $item['price'] ) ) {
			$out .= '<span>';
			if ( ! empty( $item['status'] ) ) {
				$out .= esc_html( $item['status'] );
			}
			if ( ! empty( $item['price'] ) ) {
				$out .= ( ! empty( $item['status'] ) ? ' · ' : '' ) . wp_kses_post( $item['price'] );
			}
			$out .= '</span>';
		}
		$out .= '</a>';
	}
	$out .= '</div>';
	return $out;
}

add_shortcode( 'dk_account_hub', function () {
	$saved     = dk_saved_products_payload();
	$posted    = dk_posted_products_payload();
	$commented = dk_commented_products_payload();

	ob_start();
	?>
	<section class="dk-account-hub">
		<div class="dk-account-head">
			<div>
				<h1>Mano Daiktuva</h1>
				<p>Čia matysite įsimintus, savo patalpintus ir komentuotus skelbimus.</p>
			</div>
			<div class="dk-account-actions">
				<?php if ( is_user_logged_in() ) : ?>
					<a href="<?php echo esc_url( function_exists( 'dk_add_listing_url' ) ? dk_add_listing_url() : home_url( '/dashboard/new-product/' ) ); ?>">Įdėti skelbimą</a>
					<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">Atsijungti</a>
				<?php else : ?>
					<a href="<?php echo esc_url( home_url( '/prisijungimas-dk7/' ) ); ?>">Prisijungti</a>
					<a href="<?php echo esc_url( function_exists( 'dk_add_listing_url' ) ? dk_add_listing_url() : home_url( '/vendor-onboarding/' ) ); ?>">Įdėti skelbimą</a>
				<?php endif; ?>
			</div>
		</div>
		<?php if ( ! is_user_logged_in() ) : ?>
			<p class="dk-account-note">Neprisijungus įsiminti skelbimai saugomi šiame įrenginyje. Prisijungus jie bus susieti su paskyra.</p>
		<?php endif; ?>
		<div class="dk-account-grid">
			<section class="dk-account-section">
				<h2>Įsiminti skelbimai</h2>
				<div data-dk-account-saved>
					<?php echo dk_account_hub_items_html( $saved, 'Įsimintų skelbimų dar nėra.' ); ?>
				</div>
			</section>
			<section class="dk-account-section">
				<h2>Patalpinti skelbimai</h2>
				<div data-dk-account-posted>
					<?php echo dk_account_hub_items_html( $posted, is_user_logged_in() ? 'Dar nepatalpinote skelbimų.' : 'Prisijunkite, kad matytumėte savo patalpintus skelbimus.' ); ?>
				</div>
			</section>
			<section class="dk-account-section">
				<h2>Komentuoti skelbimai</h2>
				<div data-dk-account-commented>
					<?php echo dk_account_hub_items_html( $commented, is_user_logged_in() ? 'Komentuotų skelbimų dar nėra.' : 'Prisijunkite, kad matytumėte komentuotus skelbimus.' ); ?>
				</div>
			</section>
		</div>
	</section>
	<?php
	return ob_get_clean();
} );

add_action( 'wp_ajax_dk_saved_toggle', 'dk_saved_toggle_ajax' );
add_action( 'wp_ajax_nopriv_dk_saved_toggle', 'dk_saved_toggle_ajax' );
function dk_saved_toggle_ajax(): void {
	$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
	if ( ! $product_id || 'product' !== get_post_type( $product_id ) ) {
		wp_send_json_error( array( 'message' => 'Neteisingas skelbimas.' ), 400 );
	}
	$ids = dk_get_saved_product_ids();
	if ( in_array( $product_id, $ids, true ) ) {
		$ids = array_values( array_diff( $ids, array( $product_id ) ) );
	} else {
		array_unshift( $ids, $product_id );
	}
	dk_set_saved_product_ids( $ids );
	dk_saved_ajax_response();
}

function dk_get_product_quality_badges( WC_Product $product ): array {
	// 2026-07-19: isjungta – "Yra nuotrauka"/"Aiski kaina" ir pan. buvo akivaizdus
	// vizualus triuksmas kortelese ir skelbime. Kainu kitimo zenkla paliekame.
	return array();
}

add_filter( 'update_post_metadata', function ( $check, $object_id, $meta_key, $meta_value ) {
	if ( '_price' !== $meta_key || 'product' !== get_post_type( $object_id ) ) {
		return $check;
	}

	$old = get_post_meta( $object_id, '_price', true );
	$new = is_scalar( $meta_value ) ? (string) $meta_value : '';
	if ( '' === $old || '' === $new || (float) $old <= 0 || (float) $old === (float) $new ) {
		return $check;
	}

	$percent = round( ( (float) $new - (float) $old ) / (float) $old * 100, 1 );
	update_post_meta( $object_id, '_dk_previous_price', (float) $old );
	update_post_meta( $object_id, '_dk_price_change_percent', $percent );
	update_post_meta( $object_id, '_dk_price_changed_at', current_time( 'mysql' ) );
	return $check;
}, 10, 4 );

function dk_price_change_badge( int $product_id ): string {
	$percent = get_post_meta( $product_id, '_dk_price_change_percent', true );
	if ( '' === $percent || ! is_numeric( $percent ) || 0.0 === (float) $percent ) {
		return '';
	}

	$value = (float) $percent;
	$class = $value < 0 ? 'is-down' : 'is-up';
	$label = $value < 0 ? 'Atpigo ' : 'Pabrango +';
	$shown = $value < 0 ? abs( $value ) : $value;
	return '<span class="dk-price-change ' . esc_attr( $class ) . '">' . esc_html( $label . rtrim( rtrim( number_format( $shown, 1, ',', '' ), '0' ), ',' ) . '%' ) . '</span>';
}

function dk_skelbimai_lt( int $n ): string {
	if ( 1 === $n % 10 && 11 !== $n % 100 ) {
		return 'skelbimas';
	}
	if ( $n % 10 >= 2 && $n % 10 <= 9 && ( $n % 100 < 10 || $n % 100 >= 20 ) ) {
		return 'skelbimai';
	}
	return 'skelbimų';
}

/* Pardavejo (Dokan parduotuves) nuoroda prekes korteleje */
add_action( 'woocommerce_after_shop_loop_item_title', function () {
	if ( ! function_exists( 'dokan_get_store_url' ) ) {
		return;
	}
	global $product;
	if ( ! $product ) {
		return;
	}
	$author_id = (int) get_post_field( 'post_author', $product->get_id() );
	// 2026-08-21: viešai niekada login'o/vardo — helper'is maskuoja auto-pavadinimus.
	// Kortelėje prie pardavėjo — mažas avataras (gražiau + atpažįstamumas).
	$name      = function_exists( 'dk_public_seller_name' ) ? dk_public_seller_name( $author_id ) : 'Privatus pardavėjas';
	$avatar    = function_exists( 'dk_avatar_url_for_user' ) ? dk_avatar_url_for_user( $author_id ) : '';
	echo '<a class="dk-seller" href="' . esc_url( dokan_get_store_url( $author_id ) ) . '">'
		. esc_html( $name )
		. ( $avatar ? ' <img class="dk-seller__avatar" src="' . esc_url( $avatar ) . '" alt="" width="20" height="20" style="border-radius:6px;vertical-align:-4px;">' : '' )
		. '</a>';
}, 9 );

/* Vieta/miestas + idejimo data prekes korteleje (2026-08-20: prideta data) */
add_action( 'woocommerce_after_shop_loop_item_title', function () {
	global $product;
	if ( ! $product ) {
		return;
	}
	$loc = get_post_meta( $product->get_id(), '_dk_location', true );
	if ( $loc ) {
		echo '<span class="dk-loc">' . esc_html( $loc ) . '</span>';
	}
	$ts   = get_post_time( 'U', true, $product->get_id() );
	$days = $ts ? (int) floor( ( time() - $ts ) / DAY_IN_SECONDS ) : 0;
	if ( $days <= 0 ) {
		$when = 'šiandien';
	} elseif ( 1 === $days ) {
		$when = 'vakar';
	} elseif ( $days < 31 ) {
		$when = 'prieš ' . $days . ' d.';
	} else {
		$when = get_the_date( 'Y-m-d', $product->get_id() );
	}
	echo '<span class="dk-date">' . esc_html( $when ) . '</span>';
}, 11 );

/* Kokybes zymos ir issaugojimo mygtukas prekes korteleje */
add_action( 'woocommerce_after_shop_loop_item_title', function () {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$badges = array_slice( dk_get_product_quality_badges( $product ), 0, 3 );
	$change = dk_price_change_badge( $product->get_id() );
	if ( $badges || $change ) {
		echo '<div class="dk-quality-badges">';
		foreach ( $badges as $badge ) {
			echo '<span>' . esc_html( $badge ) . '</span>';
		}
		echo $change; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}
	echo '<button type="button" class="dk-fav dk-fav-corner" data-dk-save="' . esc_attr( (string) $product->get_id() ) . '" data-dk-title="' . esc_attr( get_the_title( $product->get_id() ) ) . '" data-dk-url="' . esc_url( get_permalink( $product->get_id() ) ) . '" aria-pressed="false" aria-label="Įsiminti daiktą" title="Įsiminti"><svg viewBox="0 0 100 100" aria-hidden="true"><path class="dkf-fill" d="M52 18 c18 0 30 14 30 32 c0 18 -12 32 -30 32 h-24 a8 8 0 0 1 -8 -8 v-18 h32 a8 8 0 0 0 0 -16 v-22 Z"/></svg></button>';
}, 12 );

/* Vieno skelbimo meta: vieta + pardavejas */
add_action( 'woocommerce_single_product_summary', function () {
	global $product;
	if ( ! $product ) {
		return;
	}
	$pid    = $product->get_id();
	$loc    = get_post_meta( $pid, '_dk_location', true );
	$author = (int) get_post_field( 'post_author', $pid );
	// 2026-08-21: viešai niekada login'o/vardo; avataras — procedūrinis (dk_avatar_url_for_user).
	$name   = function_exists( 'dk_public_seller_name' ) ? dk_public_seller_name( $author ) : '';
	$url    = function_exists( 'dokan_get_store_url' ) ? dokan_get_store_url( $author ) : '';
	if ( ! $loc && ! $name ) {
		return;
	}
	echo '<div class="dk-single-meta">';
	if ( $loc ) {
		echo '<span class="dk-loc">Vieta: ' . esc_html( $loc ) . '</span>';
	}
	if ( $name ) {
		$count      = (int) count_user_posts( $author, 'product', true );
		$registered = get_the_author_meta( 'user_registered', $author );
		$since      = $registered ? date_i18n( 'Y \\m\\. F', strtotime( $registered ) ) : '';
		$avatar     = function_exists( 'dk_avatar_url_for_user' ) ? dk_avatar_url_for_user( $author ) : '';
		echo '<div class="dk-seller-card">';
		if ( $avatar ) {
			echo '<img class="dk-seller-card__avatar" src="' . esc_url( $avatar ) . '" alt="" width="44" height="44" style="border-radius:10px;float:left;margin:2px 10px 2px 0;">';
		}
		echo '<a class="dk-seller-card__name" href="' . esc_url( $url ) . '">' . esc_html( $name ) . '</a>';
		if ( get_user_meta( $author, 'dk_email_verified', true ) ) {
			echo '<span class="dk-verified-seller" title="Pardavėjas patvirtino savo el. pašto adresą">✓ El. paštas patvirtintas</span>';
		}
		echo '<div class="dk-seller-card__meta">' . esc_html( sprintf( '%d %s', $count, dk_skelbimai_lt( $count ) ) . ( $since ? ' · su Daiktuva nuo ' . $since : '' ) ) . '</div>';
		echo '<a class="dk-seller-card__link" href="' . esc_url( $url ) . '">Visi pardavėjo skelbimai →</a>';
		echo '</div>';
	}
	echo '</div>';
}, 25 );

/* Skelbimo metaduomenys: idejimo data, perziuru skaicius, numeris (2026-08-20).
 * Pasitikejimo signalai kaip skelbiu.lt. Perziuras skaiciuoja mu-plugin
 * daiktuva-views.php (AJAX beacon — full page cache PHP nevykdo). */
add_action( 'woocommerce_single_product_summary', function () {
	global $product;
	if ( ! $product ) {
		return;
	}
	$pid   = $product->get_id();
	$views = (int) get_post_meta( $pid, '_dk_views', true );
	echo '<div class="dk-listing-meta">Skelbimas įdėtas ' . esc_html( get_the_date( 'Y-m-d', $pid ) )
		. ' &middot; Peržiūrų: ' . (int) $views
		. ' &middot; Skelbimo nr. ' . (int) $pid . '</div>';
}, 26 );

/* Produktu tabu tvarkymas: "More Products" i LT, tusciu atsiliepimu tab pasleptas */
add_filter( 'woocommerce_product_tabs', function ( $tabs ) {
	foreach ( $tabs as $key => $tab ) {
		if ( isset( $tab['title'] ) && 'More Products' === $tab['title'] ) {
			$tabs[ $key ]['title'] = 'Daugiau pardavėjo prekių';
		}
	}
	global $product;
	if ( isset( $tabs['reviews'] ) && $product instanceof WC_Product && 0 === (int) $product->get_review_count() ) {
		unset( $tabs['reviews'] );
	}
	return $tabs;
}, 98 );

/* Vieno skelbimo pasitikejimo/saugumo blokai */
add_action( 'woocommerce_single_product_summary', function () {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$badges = dk_get_product_quality_badges( $product );
	$change = dk_price_change_badge( $product->get_id() );
	echo '<div class="dk-single-trust">';
	if ( $badges || $change ) {
		echo '<div class="dk-single-trust__badges">';
		foreach ( $badges as $badge ) {
			echo '<span>' . esc_html( $badge ) . '</span>';
		}
		echo $change; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}
	echo '<button type="button" class="dk-save-btn dk-save-btn--single" data-dk-save="' . esc_attr( (string) $product->get_id() ) . '" data-dk-title="' . esc_attr( get_the_title( $product->get_id() ) ) . '" data-dk-url="' . esc_url( get_permalink( $product->get_id() ) ) . '" aria-pressed="false">Įsiminti skelbimą</button>';
	echo '</div>';
}, 26 );

add_action( 'woocommerce_single_product_summary', function () {
	global $product;
	$report_url = home_url( '/kontaktai/' );
	if ( $product instanceof WC_Product ) {
		$report_url = add_query_arg( 'skelbimas', $product->get_id(), $report_url );
	}
	echo '<aside class="dk-safety-box"><strong>Pirk saugiai</strong>'
		. '<p class="dk-safety-box__notice">Daiktuva neprašo mokėti per kurjerio ar banko nuorodas ir nėra mokėjimo tarpininkas.</p>'
		. '<ul><li>Apžiūrėkite prekę gyvai, jei sandoris brangesnis.</li><li>Nespauskite įtartinų mokėjimo ar kurjerio nuorodų.</li><li>Avansą mokėkite tik tada, kai pasitikite pardavėju ir aiškiai sutarta dėl sąlygų.</li></ul>'
		. '<a class="dk-report-listing" href="' . esc_url( $report_url ) . '">Pranešti apie skelbimą</a></aside>';
}, 55 );

/* Pardavejo puslapio sustiprinimas */
add_action( 'astra_content_before', function () {
	if ( ! function_exists( 'dokan_is_store_page' ) || ! dokan_is_store_page() ) {
		return;
	}
	$store_user = function_exists( 'dokan_get_current_user_id' ) ? (int) dokan_get_current_user_id() : 0;
	if ( ! $store_user ) {
		return;
	}
	$store = function_exists( 'dokan_get_store_info' ) ? dokan_get_store_info( $store_user ) : array();
	// 2026-08-21: login'o/vardo viešai nerodyti — maskuojantis helper'is.
	$name = function_exists( 'dk_public_seller_name' )
		? dk_public_seller_name( $store_user )
		: ( ! empty( $store['store_name'] ) ? $store['store_name'] : 'Privatus pardavėjas' );
	$count = count_user_posts( $store_user, 'product', true );
	$registered = get_the_author_meta( 'user_registered', $store_user );
	$since = $registered ? date_i18n( 'Y', strtotime( $registered ) ) : '';
	$url = function_exists( 'dokan_get_store_url' ) ? dokan_get_store_url( $store_user ) : '';

	echo '<section class="dk-store-trust ast-container">';
	echo '<div><strong>' . esc_html( $name ) . '</strong><p>Pardavėjo skelbimai vienoje vietoje. Prieš pirkdami pasitikslinkite būklę, komplektaciją ir atsiėmimo sąlygas.</p></div>';
	echo '<ul>';
	echo '<li><span>' . intval( $count ) . '</span> ' . esc_html( dk_skelbimai_lt( (int) $count ) ) . '</li>';
	if ( $since ) {
		echo '<li><span>' . esc_html( $since ) . '</span> pardavėjas nuo</li>';
	}
	echo '<li><span>Patogu</span> dalintis nuoroda</li>';
	echo '</ul>';
	if ( $url ) {
		echo '<button type="button" class="dk-copy-store" data-dk-copy-link="' . esc_url( $url ) . '">Kopijuoti pardavėjo nuorodą</button>';
	}
	echo '</section>';
}, 9 );

/* Mobile sticky navigacija + isiminti skelbimai localStorage */
add_action( 'wp_footer', function () {
	if ( is_admin() ) {
		return;
	}
	$dk_add_listing_url = function_exists( 'dk_add_listing_url' ) ? dk_add_listing_url() : home_url( '/vendor-onboarding/' );
	?>
	<div class="dk-saved-panel" hidden>
		<div class="dk-saved-panel__inner">
			<div class="dk-saved-panel__head">
				<strong>Įsiminti skelbimai</strong>
				<button type="button" data-dk-saved-close>Uždaryti</button>
			</div>
			<div class="dk-saved-list"></div>
		</div>
	</div>
	<nav class="dk-mobile-sticky" aria-label="Greita navigacija">
		<a href="/">Ieškoti</a>
		<a href="/shop/">Skelbimai</a>
		<a class="dk-mobile-sticky__primary" href="<?php echo esc_url( $dk_add_listing_url ); ?>">Įdėti</a>
		<button type="button" data-dk-saved-open>Įsiminti <span data-dk-saved-count>0</span></button>
	</nav>
	<script>
	(function(){
		var key='dk_saved_listings';
		var ajaxUrl=<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
		function randomId(){return 'v_'+Math.random().toString(36).slice(2)+Date.now().toString(36);}
		function cookie(name){var m=document.cookie.match(new RegExp('(?:^|; )'+name+'=([^;]*)'));return m?decodeURIComponent(m[1]):'';}
		function setCookie(name,value){var d=new Date();d.setTime(d.getTime()+180*864e5);document.cookie=name+'='+encodeURIComponent(value)+';expires='+d.toUTCString()+';path=/;SameSite=Lax'+(location.protocol==='https:'?';Secure':'');}
		function ensureVisitor(){var id=cookie('dk_visitor_id');if(!id){id=randomId();setCookie('dk_visitor_id',id);}return id;}
		function read(){try{return JSON.parse(localStorage.getItem(key)||'{}')||{};}catch(e){return {};}}
		function write(v){localStorage.setItem(key,JSON.stringify(v));}
		function values(){var saved=read();return Object.keys(saved).map(function(id){return saved[id];});}
		function ids(){return Object.keys(read()).filter(function(id){return /^\d+$/.test(id);});}
		function ajax(action,data){
			data=data||{};data.action=action;ensureVisitor();
			return fetch(ajaxUrl,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams(data).toString()})
				.then(function(r){return r.json();});
		}
		function applyServer(payload){
			if(!payload||!payload.success||!payload.data){return;}
			var saved={};
			(payload.data.saved||[]).forEach(function(item){saved[String(item.id)]={title:item.title,url:item.url};});
			write(saved);
			window.dkPostedListings=payload.data.posted||[];
			window.dkCommentedListings=payload.data.commented||[];
			update();
		}
		function update(){
			var saved=read(), count=Object.keys(saved).length;
			document.querySelectorAll('[data-dk-saved-count]').forEach(function(el){el.textContent=String(count);});
			document.querySelectorAll('[data-dk-save]').forEach(function(btn){
				var active=!!saved[btn.getAttribute('data-dk-save')];
				btn.classList.toggle('is-saved',active);
				btn.setAttribute('aria-pressed',active?'true':'false');
				if(!btn.classList.contains('dk-fav-corner')){btn.textContent=active?(btn.classList.contains('dk-save-btn--single')?'Įsiminta':'Įsiminta'):(btn.classList.contains('dk-save-btn--single')?'Įsiminti skelbimą':'Įsiminti');}
			});
			renderPanel();
			renderAccountHub();
		}
		function renderPanel(){
			var list=document.querySelector('.dk-saved-list');if(!list){return;}
			var items=values();
			var posted=window.dkPostedListings||[];
			var commented=window.dkCommentedListings||[];
			var html='';
			if(!items.length){html+='<p>Įsimintų skelbimų dar nėra.</p>';}else{
				html+='<strong class="dk-saved-list__title">Įsiminti</strong>';
				html+=items.map(function(item){
				return '<a href="'+esc(item.url)+'">'+esc(item.title)+'</a>';
				}).join('');
			}
			if(posted.length){
				html+='<strong class="dk-saved-list__title">Patalpinti skelbimai</strong>';
				html+=posted.map(function(item){return '<a href="'+esc(item.url)+'">'+esc(item.title)+'</a>';}).join('');
			}
			if(commented.length){
				html+='<strong class="dk-saved-list__title">Paskelbti komentarai</strong>';
				html+=commented.map(function(item){return '<a href="'+esc(item.url)+'">'+esc(item.title)+'</a>';}).join('');
			}
			list.innerHTML=html;
		}
		function esc(value){
			return String(value||'').replace(/[&<>"']/g,function(ch){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch];});
		}
		function renderAccountGroup(selector,items,empty){
			var el=document.querySelector(selector);if(!el){return;}
			if(!items||!items.length){el.innerHTML='<p class="dk-account-empty">'+esc(empty)+'</p>';return;}
			el.innerHTML='<div class="dk-account-list">'+items.map(function(item){
				var meta='';
				if(item.status||item.price){meta='<span>'+esc(item.status||'')+(item.status&&item.price?' · ':'')+(item.price||'')+'</span>';}
				return '<a class="dk-account-item" href="'+esc(item.url)+'"><strong>'+esc(item.title)+'</strong>'+meta+'</a>';
			}).join('')+'</div>';
		}
		function renderAccountHub(){
			renderAccountGroup('[data-dk-account-saved]',values(),'Įsimintų skelbimų dar nėra.');
			renderAccountGroup('[data-dk-account-posted]',window.dkPostedListings||[],'Prisijungus čia matysite savo patalpintus skelbimus.');
			renderAccountGroup('[data-dk-account-commented]',window.dkCommentedListings||[],'Prisijungus čia matysite komentuotus skelbimus.');
		}
		function injectStoreTrust(){
			if(!document.body.classList.contains('dokan-store') || document.querySelector('.dk-store-trust')){return;}
			var wrap=document.querySelector('.dokan-store-wrap');if(!wrap){return;}
			var crumb=document.querySelector('.woocommerce-breadcrumb, .ast-breadcrumb, nav[aria-label="Breadcrumb"]');
			var name='Pardavėjas';
			if(crumb){var parts=crumb.textContent.split('/').map(function(v){return v.trim();}).filter(Boolean); if(parts.length){name=parts[parts.length-1];}}
			var count=document.querySelectorAll('ul.products li.product, .products .product').length || '';
			var section=document.createElement('section');
			section.className='dk-store-trust ast-container';
			section.innerHTML='<div><strong>'+name+'</strong><p>Pardavėjo skelbimai vienoje vietoje. Prieš pirkdami pasitikslinkite būklę, komplektaciją ir atsiėmimo sąlygas.</p></div><ul><li><span>'+(count||'')+'</span> skelbimai šiame puslapyje</li><li><span>Patogu</span> dalintis nuoroda</li><li><span>Saugiau</span> tikrinti prieš mokant</li></ul><button type="button" class="dk-copy-store" data-dk-copy-link="'+location.href.split('#')[0]+'">Kopijuoti pardavėjo nuorodą</button>';
			wrap.parentNode.insertBefore(section,wrap);
		}
		document.addEventListener('click',function(e){
			var btn=e.target.closest('[data-dk-save]');
			if(btn){
				e.preventDefault();
				var saved=read(), id=btn.getAttribute('data-dk-save');
				if(saved[id]){delete saved[id];}else{saved[id]={title:btn.getAttribute('data-dk-title')||'Skelbimas',url:btn.getAttribute('data-dk-url')||location.href};}
				write(saved);update();
				ajax('dk_saved_toggle',{product_id:id}).then(applyServer).catch(function(){});
				return;
			}
			if(e.target.closest('[data-dk-saved-open]')){var p=document.querySelector('.dk-saved-panel');if(p){p.hidden=false;renderPanel();}}
			if(e.target.closest('[data-dk-saved-close]')){var c=document.querySelector('.dk-saved-panel');if(c){c.hidden=true;}}
		});
		injectStoreTrust();
		ajax('dk_saved_sync',{ids:ids().join(',')}).then(applyServer).catch(function(){update();});
		update();
	})();
	</script>
	<?php
} );

/* Panašūs skelbimai vieno skelbimo apačioje.
 * 2026-08-20: WooCommerce numatytąją "Panašios prekės" sekciją nuimame —
 * ji dubliavosi su mūsų "Panašūs skelbimai" (tas pats skelbimas apačioje
 * rodydavosi 2 kartus, ypač kai skelbimų mažai). */
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
add_action( 'woocommerce_after_single_product_summary', function () {
	if ( ! is_product() ) {
		return;
	}

	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$terms = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'ids' ) );
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return;
	}

	$q = new WP_Query( array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'posts_per_page'      => 4,
		'post__not_in'        => array( $product->get_id() ),
		'ignore_sticky_posts' => 1,
		'tax_query'           => array(
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'term_id',
				'terms'    => $terms,
			),
			array(
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => array( 'exclude-from-catalog' ),
				'operator' => 'NOT IN',
			),
		),
		'orderby'            => 'date',
		'order'              => 'DESC',
	) );

	if ( ! $q->have_posts() ) {
		return;
	}

	echo '<section class="dk-related-products"><h2>Panašūs skelbimai</h2><div class="woocommerce columns-4"><ul class="products columns-4">';
	while ( $q->have_posts() ) {
		$q->the_post();
		$GLOBALS['product'] = wc_get_product( get_the_ID() );
		wc_get_template_part( 'content', 'product' );
	}
	echo '</ul></div></section>';
	wp_reset_postdata();
}, 18 );

/* Footerio nuorodu sekcija (virs copyright) */
add_action( 'astra_footer_before', function () {
	$dk_add_listing_url = function_exists( 'dk_add_listing_url' ) ? dk_add_listing_url() : home_url( '/vendor-onboarding/' );
	$cats = get_terms( array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'parent'     => 0,
		'number'     => 8,
		'orderby'    => 'count',
		'order'      => 'DESC',
	) );
	echo '<div class="dk-footer-links"><div class="dk-fl-inner">';
	echo '<div class="dk-fcol"><h4>Daiktuva</h4><p>Skelbimai ir prekės iš pirmų rankų visoje Lietuvoje.</p></div>';
	echo '<div class="dk-fcol"><h4>Kategorijos</h4><ul>';
	if ( ! is_wp_error( $cats ) ) {
		foreach ( $cats as $c ) {
			if ( 'uncategorized' === $c->slug ) {
				continue;
			}
			echo '<li><a href="' . esc_url( get_term_link( $c ) ) . '">' . esc_html( $c->name ) . '</a></li>';
		}
	}
	echo '</ul></div>';
	echo '<div class="dk-fcol"><h4>Informacija</h4><ul>'
		. '<li><a href="/apie/">Apie mus</a></li>'
		. '<li><a href="/patarimai/">Patarimai</a></li>'
		. '<li><a href="/taisykles/">Taisyklės</a></li>'
		. '<li><a href="/privatumo-politika/">Privatumo politika</a></li>'
		. '<li><a href="/kontaktai/">Kontaktai</a></li>'
		. '</ul></div>';
	echo '<div class="dk-fcol"><h4>Pardavėjams</h4><ul>'
		. '<li><a href="' . esc_url( $dk_add_listing_url ) . '">Įdėti skelbimą</a></li>'
		. '<li><a href="/store-listing/">Visi pardavėjai</a></li>'
		. '</ul></div>';
	echo '</div></div>';
} );

/* ------------------------------------------------------------------
 * Pradinio puslapio prekiu tinklelis: rusiavimas + puslapiavimas (12/psl)
 * Iskeltieji (WooCommerce "Featured" / busimas mokamas "bump") rodomi PIRMA,
 * po to pagal pasirinkta rusiavima (naujausi / pigiausi / brangiausi).
 * Naudoja nuosava URL parametra ?psl=N (kad nesikirstu su static front page).
 * ------------------------------------------------------------------ */
add_shortcode( 'dk_products', function ( $atts ) {
	$atts  = shortcode_atts( array( 'per_page' => 12, 'category' => '', 'tag' => '', 'search' => '' ), $atts );
	$per   = max( 1, (int) $atts['per_page'] );
	$paged = isset( $_GET['psl'] ) ? max( 1, (int) $_GET['psl'] ) : 1;
	$sort  = isset( $_GET['rus'] ) ? sanitize_key( $_GET['rus'] ) : 'naujausi';
	$category = sanitize_title( (string) $atts['category'] );
	$tag      = sanitize_title( (string) $atts['tag'] );
	$search   = trim( (string) $atts['search'] );
	if ( '' === $search && is_search() ) {
		$search = get_search_query( false );
	}
	$f_nuo = isset( $_GET['kn'] ) ? (float) str_replace( ',', '.', sanitize_text_field( wp_unslash( $_GET['kn'] ) ) ) : 0;
	$f_iki = isset( $_GET['ki'] ) ? (float) str_replace( ',', '.', sanitize_text_field( wp_unslash( $_GET['ki'] ) ) ) : 0;
	$f_m   = isset( $_GET['miestas'] ) ? sanitize_text_field( wp_unslash( $_GET['miestas'] ) ) : '';
	$f_cat = isset( $_GET['kat'] ) ? sanitize_title( wp_unslash( $_GET['kat'] ) ) : '';

	// Search bypasses page cache and runs a heavy WP_Query; cache the rendered
	// fragment in Redis (object cache), keyed by query+page+sort, invalidated on product changes.
	$dk_cache_search = ( '' !== $search );
	$dk_cache_key    = '';
	if ( $dk_cache_search ) {
		$dk_ver       = (int) wp_cache_get( 'dk_search_ver', 'dk_search' );
		$dk_norm      = function_exists( 'mb_strtolower' ) ? mb_strtolower( $search ) : strtolower( $search );
		$dk_cache_key = 'html_' . $dk_ver . '_' . md5( implode( '|', array( $dk_norm, $paged, $per, $sort, $category, $tag, $f_nuo, $f_iki, $f_m, $f_cat ) ) );
		$dk_cached    = wp_cache_get( $dk_cache_key, 'dk_search' );
		if ( is_string( $dk_cached ) ) {
			return $dk_cached;
		}
	}

	$args = array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'posts_per_page'      => $per,
		'paged'               => $paged,
		'dk_featured_first'   => 1,
		'ignore_sticky_posts' => 1,
		'tax_query'           => array(
			array(
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => array( 'exclude-from-catalog' ),
				'operator' => 'NOT IN',
			),
		),
	);
	$active_category = $category ? $category : $f_cat;
	if ( $active_category ) {
		$args['tax_query'][] = array(
			'taxonomy' => 'product_cat',
			'field'    => 'slug',
			'terms'    => array( $active_category ),
		);
	}
	if ( $tag ) {
		$args['tax_query'][] = array(
			'taxonomy' => 'product_tag',
			'field'    => 'slug',
			'terms'    => array( $tag ),
		);
	}
	if ( '' !== $search ) {
		$args['s'] = $search;
	}

	/* Filtrai: kaina (kn/ki) ir miestas (miestas) */
	if ( $f_nuo > 0 ) {
		$args['meta_query'][] = array( 'key' => '_price', 'value' => $f_nuo, 'type' => 'NUMERIC', 'compare' => '>=' );
	}
	if ( $f_iki > 0 ) {
		$args['meta_query'][] = array( 'key' => '_price', 'value' => $f_iki, 'type' => 'NUMERIC', 'compare' => '<=' );
	}
	if ( '' !== $f_m ) {
		$args['meta_query'][] = array( 'key' => '_dk_location', 'value' => $f_m, 'compare' => '=' );
	}
	switch ( $sort ) {
		case 'atpigo':
			$args['meta_key'] = '_dk_price_change_percent';
			$args['orderby']  = 'meta_value_num';
			$args['order']    = 'ASC';
			$args['meta_query'][] = array(
				'key'     => '_dk_price_change_percent',
				'value'   => 0,
				'type'    => 'NUMERIC',
				'compare' => '<',
			);
			break;
		case 'pigiausi':
			$args['meta_key'] = '_price';
			$args['orderby']  = 'meta_value_num';
			$args['order']    = 'ASC';
			break;
		case 'brangiausi':
			$args['meta_key'] = '_price';
			$args['orderby']  = 'meta_value_num';
			$args['order']    = 'DESC';
			break;
		default:
			/* 2026-08-20: numatytoji tvarka = vėliausiai ATNAUJINTI pirmiausia
			 * (anksčiau buvo pagal paskelbimo datą). */
			$sort            = 'naujausi';
			$args['orderby'] = 'modified';
			$args['order']   = 'DESC';
	}

	$q    = new WP_Query( $args );
	$base = remove_query_arg( array( 'psl', 'rus' ) );

	ob_start();

	/* Rusiavimo juosta */
	echo '<div class="dk-sortbar">';
	echo '<span class="dk-sort-label">Rūšiuoti:</span>';
	$opts = array( 'naujausi' => 'Naujausi', 'atpigo' => 'Atpigo', 'pigiausi' => 'Pigiausi', 'brangiausi' => 'Brangiausi' );
	foreach ( $opts as $k => $lbl ) {
		$cls = ( $sort === $k ) ? ' is-active' : '';
		$url = esc_url( add_query_arg( array( 'rus' => $k ), remove_query_arg( 'psl', $base ) ) );
		echo '<a class="dk-sort' . $cls . '" href="' . $url . '">' . esc_html( $lbl ) . '</a>';
	}
	echo '<span class="dk-result-count">' . intval( $q->found_posts ) . ' ' . esc_html( dk_skelbimai_lt( (int) $q->found_posts ) ) . '</span>';
	echo '</div>';

	/* Filtru juosta: kaina nuo-iki + miestas (miestu sarasas is _dk_location, 10 min cache) */
	$dk_cities = get_transient( 'dk_cities' );
	if ( ! is_array( $dk_cities ) ) {
		global $wpdb;
		$dk_cities = $wpdb->get_col( "SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = '_dk_location' AND pm.meta_value <> '' AND p.post_type = 'product' AND p.post_status = 'publish' ORDER BY pm.meta_value ASC LIMIT 200" );
		set_transient( 'dk_cities', $dk_cities, 10 * MINUTE_IN_SECONDS );
	}
	echo '<form class="dk-filters" method="get" action="">';
	if ( '' !== $search ) {
		echo '<input type="hidden" name="s" value="' . esc_attr( $search ) . '"><input type="hidden" name="post_type" value="product">';
	}
	if ( 'naujausi' !== $sort ) {
		echo '<input type="hidden" name="rus" value="' . esc_attr( $sort ) . '">';
	}
	echo '<input type="number" name="kn" min="0" step="1" inputmode="numeric" aria-label="Minimali kaina eurais" placeholder="Kaina nuo €" value="' . ( $f_nuo ? esc_attr( $f_nuo ) : '' ) . '">';
	echo '<input type="number" name="ki" min="0" step="1" inputmode="numeric" aria-label="Maksimali kaina eurais" placeholder="iki €" value="' . ( $f_iki ? esc_attr( $f_iki ) : '' ) . '">';
	echo '<select name="miestas" aria-label="Pasirinkti miestą"><option value="">Visi miestai</option>';
	foreach ( (array) $dk_cities as $c ) {
		echo '<option value="' . esc_attr( $c ) . '"' . selected( $f_m, $c, false ) . '>' . esc_html( $c ) . '</option>';
	}
	echo '</select>';
	if ( ! is_front_page() && ! $category ) {
		$dk_filter_cats = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'orderby' => 'name', 'order' => 'ASC' ) );
		if ( ! is_wp_error( $dk_filter_cats ) && $dk_filter_cats ) {
			echo '<select name="kat" aria-label="Pasirinkti kategoriją"><option value="">Visos kategorijos</option>';
			foreach ( $dk_filter_cats as $dk_filter_cat ) {
				echo '<option value="' . esc_attr( $dk_filter_cat->slug ) . '"' . selected( $f_cat, $dk_filter_cat->slug, false ) . '>' . esc_html( $dk_filter_cat->name ) . '</option>';
			}
			echo '</select>';
		}
	}
	echo '<button type="submit">Filtruoti</button>';
	if ( $f_nuo || $f_iki || '' !== $f_m || '' !== $f_cat ) {
		echo '<a class="dk-filters-reset" href="' . esc_url( remove_query_arg( array( 'kn', 'ki', 'miestas', 'kat', 'psl' ) ) ) . '">Išvalyti visus</a>';
	}
	echo '</form>';

	$dk_active_filters = array();
	if ( $f_nuo ) { $dk_active_filters['kn'] = 'Nuo ' . wc_price( $f_nuo ); }
	if ( $f_iki ) { $dk_active_filters['ki'] = 'Iki ' . wc_price( $f_iki ); }
	if ( '' !== $f_m ) { $dk_active_filters['miestas'] = $f_m; }
	if ( '' !== $f_cat ) {
		$dk_cat_obj = get_term_by( 'slug', $f_cat, 'product_cat' );
		$dk_active_filters['kat'] = $dk_cat_obj && ! is_wp_error( $dk_cat_obj ) ? $dk_cat_obj->name : $f_cat;
	}
	if ( $dk_active_filters ) {
		echo '<div class="dk-active-filters" aria-label="Aktyvūs filtrai">';
		foreach ( $dk_active_filters as $dk_filter_key => $dk_filter_label ) {
			$dk_filter_url = remove_query_arg( array( $dk_filter_key, 'psl' ) );
			echo '<a href="' . esc_url( $dk_filter_url ) . '" aria-label="Pašalinti filtrą: ' . esc_attr( wp_strip_all_tags( $dk_filter_label ) ) . '">'
				. wp_kses_post( $dk_filter_label ) . ' <span aria-hidden="true">×</span></a>';
		}
		echo '</div>';
	}

	if ( $q->have_posts() ) {
		echo '<div class="woocommerce columns-4">';
		echo '<ul class="products columns-4">';
		while ( $q->have_posts() ) {
			$q->the_post();
			$GLOBALS['product'] = wc_get_product( get_the_ID() );
			wc_get_template_part( 'content', 'product' );
		}
		echo '</ul>';
		echo '</div>';

		$total = (int) $q->max_num_pages;
		if ( $total > 1 ) {
			echo '<nav class="dk-pagination" aria-label="Puslapiavimas">';
			if ( $paged > 1 ) {
				$u = esc_url( add_query_arg( array( 'rus' => $sort, 'psl' => $paged - 1 ), $base ) );
				echo '<a class="dk-page dk-prev" aria-label="Ankstesnis puslapis" href="' . $u . '">‹</a>';
			}
			for ( $i = 1; $i <= $total; $i++ ) {
				$u   = esc_url( add_query_arg( array( 'rus' => $sort, 'psl' => $i ), $base ) );
				$cls = ( $i === $paged ) ? ' current' : '';
				echo '<a class="dk-page' . $cls . '"' . ( $i === $paged ? ' aria-current="page"' : '' ) . ' aria-label="Puslapis ' . $i . '" href="' . $u . '">' . $i . '</a>';
			}
			if ( $paged < $total ) {
				$u = esc_url( add_query_arg( array( 'rus' => $sort, 'psl' => $paged + 1 ), $base ) );
				echo '<a class="dk-page dk-next" aria-label="Kitas puslapis" href="' . $u . '">›</a>';
			}
			echo '</nav>';
		}
	} else {
		echo '<p class="dk-empty">Skelbimų nerasta.</p>';
	}
	wp_reset_postdata();

	$dk_out = ob_get_clean();
	if ( $dk_cache_search ) {
		wp_cache_set( $dk_cache_key, $dk_out, 'dk_search', (int) apply_filters( 'dk_search_cache_ttl', 300 ) );
	}
	return $dk_out;
} );

/* Invalidate cached search fragments whenever a product changes (so approved listings show at once). */
function dk_bump_search_cache_version() {
	$v = (int) wp_cache_get( 'dk_search_ver', 'dk_search' );
	wp_cache_set( 'dk_search_ver', $v + 1, 'dk_search' );
}
add_action( 'save_post_product', 'dk_bump_search_cache_version' );
add_action( 'woocommerce_update_product', 'dk_bump_search_cache_version' );
add_action( 'trashed_post', function ( $id ) { if ( 'product' === get_post_type( $id ) ) { dk_bump_search_cache_version(); } } );
add_action( 'untrashed_post', function ( $id ) { if ( 'product' === get_post_type( $id ) ) { dk_bump_search_cache_version(); } } );

/* Iskeltuju (featured) rikiavimas PIRMA - veikia tik dk_products uzklausai */
add_filter( 'posts_clauses', function ( $clauses, $q ) {
	if ( ! $q->get( 'dk_featured_first' ) ) {
		return $clauses;
	}
	global $wpdb;
	$clauses['fields'] .= ", (SELECT COUNT(*) FROM {$wpdb->postmeta} dkspm WHERE dkspm.post_id = {$wpdb->posts}.ID AND dkspm.meta_key = '_daiktuva_status' AND dkspm.meta_value = 'sold') AS dk_sold";
	$ob = isset( $clauses['orderby'] ) && $clauses['orderby'] !== '' ? $clauses['orderby'] : "{$wpdb->posts}.post_date DESC";
	$term = get_term_by( 'name', 'featured', 'product_visibility' );
	if ( $term && ! is_wp_error( $term ) ) {
		$ttid               = (int) $term->term_taxonomy_id;
		$clauses['fields'] .= ", (SELECT COUNT(*) FROM {$wpdb->term_relationships} dktr WHERE dktr.object_id = {$wpdb->posts}.ID AND dktr.term_taxonomy_id = {$ttid}) AS dk_feat";
		$clauses['orderby'] = "dk_sold ASC, dk_feat DESC, " . $ob;
	} else {
		$clauses['orderby'] = "dk_sold ASC, " . $ob;
	}
	return $clauses;
}, 10, 2 );

/* SEO/saugumas: pasalinti vartotoju (author) sitemap'a - kad nesimatytu prisijungimo vardu */
add_filter( 'wp_sitemaps_add_provider', function ( $provider, $name ) {
	return ( 'users' === $name ) ? false : $provider;
}, 10, 2 );
/* antras saugiklis: net jei users sitemap pasiekiamas - jis tuscias (jokiu vardu) */
add_filter( 'wp_sitemaps_user_pre_url_list', '__return_empty_array' );
add_filter( 'wp_sitemaps_max_urls', function ( $max, $type ) {
	return ( 'user' === $type ) ? 0 : $max;
}, 10, 2 );

/* Saugumas: paslepti WP versija + isjungti XML-RPC (brute-force/DDoS vektorius) */
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );
add_filter( 'xmlrpc_enabled', '__return_false' );

/* SEO: priekiniame puslapyje turinys jau turi savo H1 ("Pirk ir parduok paprastai"),
   todel slepiame Astra puslapio antrastes H1 ("Pradzia"), kad homepage liktu vienas H1. */
add_filter( 'astra_the_title_enabled', function ( $enabled ) {
	if ( is_front_page() ) {
		return false;
	}
	return $enabled;
} );
