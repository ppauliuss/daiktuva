<?php
/**
 * Daiktuva: product submission guards for vendors.
 *
 * 1) Photo cap: at most N images (cover + gallery) per listing (default 5).
 * 2) Rate limit: caps new submissions per vendor (default 20/hour, 60/day) to
 *    protect the moderation queue. Public spam is already impossible (all
 *    listings are pending). Admins/shop managers are exempt.
 * 3) Bot escalation: when a vendor blows the daily cap (clear abuse signal), the
 *    vendor is flagged, their currently-published listings are suspended back to
 *    "pending" for admin review, and the admin is emailed. A flagged vendor stays
 *    blocked from new submissions until an admin re-publishes one of the suspended
 *    listings (which clears the flag).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function dk_pr_limits(): array {
	return array(
		'hour'   => max( 1, (int) apply_filters( 'dk_product_submit_limit_hour', 20 ) ),
		'day'    => max( 1, (int) apply_filters( 'dk_product_submit_limit_day', 60 ) ),
		'photos' => max( 1, (int) apply_filters( 'dk_product_max_photos', 5 ) ),
	);
}

/* ---------------------------------------------------------------- photos --- */

function dk_pr_submitted_photo_count(): int {
	$n = 0;
	if ( ! empty( $_POST['feat_image_id'] ) && absint( $_POST['feat_image_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$n++;
	}
	if ( ! empty( $_POST['product_image_gallery'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$ids = array_filter( array_map( 'trim', explode( ',', (string) wp_unslash( $_POST['product_image_gallery'] ) ) ) ); // phpcs:ignore
		$n  += count( $ids );
	}
	return $n;
}

function dk_pr_photo_error(): string {
	$max   = dk_pr_limits()['photos'];
	$count = dk_pr_submitted_photo_count();
	if ( $count > $max ) {
		/* translators: %d: maximum number of photos */
		return sprintf( __( 'Galima įkelti ne daugiau kaip %d nuotraukas viename skelbime.', 'daiktuva' ), $max );
	}
	return '';
}

/* ------------------------------------------------------------ rate limit --- */

function dk_pr_get_times( int $user_id ): array {
	$times = get_user_meta( $user_id, 'dk_product_submit_times', true );
	if ( ! is_array( $times ) ) {
		$times = array();
	}
	$cutoff = time() - DAY_IN_SECONDS;
	return array_values( array_filter( $times, static function ( $ts ) use ( $cutoff ) {
		return (int) $ts >= $cutoff;
	} ) );
}

/** Error string if the user is over a limit or flagged, else ''. */
function dk_pr_limit_error( int $user_id ): string {
	if ( ! $user_id || user_can( $user_id, 'manage_woocommerce' ) ) {
		return '';
	}

	if ( get_user_meta( $user_id, 'dk_vendor_flagged_review', true ) ) {
		return __( 'Jūsų paskyra laikinai apribota dėl įtartino aktyvumo. Skelbimai pateikti administratoriaus peržiūrai.', 'daiktuva' );
	}

	$times   = dk_pr_get_times( $user_id );
	$limits  = dk_pr_limits();
	$now     = time();
	$in_hour = count( array_filter( $times, static function ( $ts ) use ( $now ) {
		return (int) $ts >= $now - HOUR_IN_SECONDS;
	} ) );
	$in_day  = count( $times );

	if ( $in_day >= $limits['day'] ) {
		// Egregious: flag the vendor, suspend their live listings, notify admin.
		dk_pr_escalate( $user_id );
		return __( 'Jūsų paskyra laikinai apribota dėl įtartino aktyvumo. Skelbimai pateikti administratoriaus peržiūrai.', 'daiktuva' );
	}

	if ( $in_hour >= $limits['hour'] ) {
		return __( 'Pasiekėte naujų skelbimų ribą trumpam laikui. Pabandykite vėliau arba kreipkitės į administratorių', 'daiktuva' );
	}

	return '';
}

/** Flag a vendor and suspend their published listings to pending (once). */
function dk_pr_escalate( int $user_id ): void {
	if ( get_user_meta( $user_id, 'dk_vendor_flagged_review', true ) ) {
		return; // already handled
	}
	update_user_meta( $user_id, 'dk_vendor_flagged_review', time() );

	$ids = get_posts( array(
		'post_type'      => 'product',
		'author'         => $user_id,
		'post_status'    => 'publish',
		'posts_per_page' => 500,
		'fields'         => 'ids',
	) );

	foreach ( $ids as $pid ) {
		wp_update_post( array( 'ID' => $pid, 'post_status' => 'pending' ) );
	}
	if ( $ids ) {
		update_user_meta( $user_id, 'dk_suspended_products', array_map( 'intval', $ids ) );
	}

	$user  = get_userdata( $user_id );
	$admin = get_option( 'admin_email' );
	if ( $user && $admin ) {
		$queue   = admin_url( 'edit.php?post_type=product&post_status=pending' );
		$subject = '[Daiktuva] Pardavėjas apribotas – įtartinas aktyvumas';
		$body    = "Pardavėjas viršijo dienos skelbimų ribą ir buvo apribotas (galimas botas / pažeista paskyra).\n\n";
		$body   .= "Vartotojas: {$user->user_login} <{$user->user_email}>\n";
		$body   .= 'Suspenduota paskelbtų skelbimų: ' . count( $ids ) . " (perkelti į „Laukia patvirtinimo“)\n\n";
		$body   .= "Peržiūrėkite ir, jei viskas tvarkoje, vėl paskelbkite (tai panaikins apribojimą):\n{$queue}\n";
		wp_mail( $admin, $subject, $body );
	}
}

/** When an admin re-publishes a flagged vendor's listing, treat it as reviewed. */
add_action( 'transition_post_status', static function ( $new, $old, $post ) {
	if ( ! $post || 'product' !== $post->post_type ) {
		return;
	}
	if ( 'publish' !== $new || 'publish' === $old ) {
		return;
	}
	$author = (int) $post->post_author;
	if ( ! $author || ! get_user_meta( $author, 'dk_vendor_flagged_review', true ) ) {
		return;
	}
	if ( current_user_can( 'manage_woocommerce' ) ) {
		delete_user_meta( $author, 'dk_vendor_flagged_review' );
		delete_user_meta( $author, 'dk_suspended_products' );
	}
}, 10, 3 );

/* ----------------------------------------------------------------- gates --- */

function dk_pr_collect_errors( $errors ) {
	$photo = dk_pr_photo_error();
	if ( $photo ) {
		$errors[] = $photo;
	}
	$rate = dk_pr_limit_error( get_current_user_id() );
	if ( $rate ) {
		$errors[] = $rate;
	}
	return $errors;
}

add_filter( 'dokan_can_add_product', 'dk_pr_collect_errors', 20 );

add_filter( 'dokan_can_edit_product', function ( $errors ) {
	$photo = dk_pr_photo_error();
	if ( $photo ) {
		$errors[] = $photo;
	}
	// Rate limit only for brand-new submissions (auto-draft -> pending), not edits.
	$post_id = isset( $_POST['dokan_product_id'] ) ? absint( $_POST['dokan_product_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
	if ( $post_id && 'auto-draft' === get_post_status( $post_id ) ) {
		$rate = dk_pr_limit_error( get_current_user_id() );
		if ( $rate ) {
			$errors[] = $rate;
		}
	} elseif ( get_user_meta( get_current_user_id(), 'dk_vendor_flagged_review', true ) && ! user_can( get_current_user_id(), 'manage_woocommerce' ) ) {
		// Flagged vendors can't edit/publish either until reviewed.
		$errors[] = __( 'Jūsų paskyra laikinai apribota dėl įtartino aktyvumo. Skelbimai pateikti administratoriaus peržiūrai.', 'daiktuva' );
	}
	return $errors;
}, 20 );

/** Gate: REST API product creation. */
add_filter( 'rest_pre_insert_product', static function ( $prepared, $request ) {
	if ( ! empty( $prepared->ID ) ) {
		return $prepared;
	}
	$err = dk_pr_limit_error( get_current_user_id() );
	if ( $err ) {
		return new WP_Error( 'dk_product_rate_limited', $err, array( 'status' => 429 ) );
	}
	return $prepared;
}, 20, 2 );

/** Counter: record each successful new product submission. */
add_action( 'dokan_new_product_added', static function ( $product_id ) {
	$product = get_post( $product_id );
	if ( ! $product ) {
		return;
	}
	$user_id = (int) $product->post_author;
	if ( ! $user_id || user_can( $user_id, 'manage_woocommerce' ) ) {
		return;
	}
	$times   = dk_pr_get_times( $user_id );
	$times[] = time();
	update_user_meta( $user_id, 'dk_product_submit_times', $times );
}, 5 );

/* ------------------------------------------------- in-form photo hint (JS) --- */

add_action( 'wp_footer', function () {
	if ( ! function_exists( 'dokan_is_seller_dashboard' ) || ! dokan_is_seller_dashboard() ) {
		return;
	}
	$max = dk_pr_limits()['photos'];
	?>
	<style>
		.dk-photo-hint{margin:10px 0 0;font-size:13px;font-weight:600;color:#0F4C81;}
		.dk-photo-hint--full{color:#c0392b;}
	</style>
	<script>
	(function(){
		var MAX = <?php echo (int) $max; ?>;
		function featCount(){
			var i = document.querySelector('.dokan-feat-image-id, input[name="feat_image_id"]');
			return (i && String(i.value).trim() && i.value !== '0') ? 1 : 0;
		}
		// 2026-08-20: vieningoje juostoje (dk-multi-photo.js) viršelis yra li.image su .dk-is-cover —
		// jis skaičiuojamas per featCount(), todėl čia išmetamas, kad nebūtų dvigubo skaičiaus.
		function galleryCount(){ return document.querySelectorAll('ul.product_images li.image:not(.dk-is-cover)').length; }
		function setStyle(el, val){ if(el && el.style.display !== val){ el.style.display = val; } }
		function update(){
			var ul = document.querySelector('ul.product_images');
			if(!ul){ return; }
			var total = featCount() + galleryCount();
			var hint = document.getElementById('dk-photo-hint');
			if(!hint){
				hint = document.createElement('p');
				hint.id = 'dk-photo-hint';
				ul.parentNode.insertBefore(hint, ul.nextSibling);
			}
			var addG = ul.querySelector('li.add-image');
			var addF = document.querySelector('.dokan-feat-image-btn');
			var cls, txt;
			if(total >= MAX){
				setStyle(addG, 'none');
				if(featCount() === 0){ setStyle(addF, 'none'); } else { setStyle(addF, ''); }
				cls = 'dk-photo-hint dk-photo-hint--full';
				txt = 'Pasiektas nuotraukų limitas (' + MAX + '). Norėdami pridėti kitą, pirma pašalinkite vieną.';
			} else {
				setStyle(addG, '');
				setStyle(addF, '');
				cls = 'dk-photo-hint';
				txt = 'Galima įkelti iki ' + MAX + ' nuotraukų (viršelis + galerija). Liko: ' + (MAX - total) + '.';
			}
			if(hint.className !== cls){ hint.className = cls; }
			if(hint.textContent !== txt){ hint.textContent = txt; }
		}
		function init(){
			if(!document.querySelector('ul.product_images')){ return; }
			update();
			var mo = new MutationObserver(function(){ update(); });
			mo.observe(document.body, { childList:true, subtree:true });
			document.addEventListener('click', function(){ setTimeout(update, 300); });
		}
		if(document.readyState !== 'loading'){ init(); } else { document.addEventListener('DOMContentLoaded', init); }
	})();
	</script>
	<?php
} );
