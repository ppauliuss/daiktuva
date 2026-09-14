<?php
/**
 * Daiktuva: pardavėjo būsenos valdymas + dalinimosi paskata + parduotų archyvavimas (2026-07-04).
 * 1) Dokan formose (naujas/redaguoti) pardavėjas pats nustato būseną (Aktyvi/Rezervuota/Parduota),
 *    telefono numerį ir miestą (_daiktuva_status/_daiktuva_phone/_dk_location).
 *    Miestas 2026-08-20: rodomas kortelėje ir skelbime (pirkėjai renkasi pagal atstumą).
 * 2) Redagavimo puslapyje po publikavimo rodomas „Pasidalinkite skelbimu" blokas (FB/Messenger/WhatsApp/kopijuoti).
 * 3) Kasdienis cron `dk_sold_cleanup`: „Parduota" skelbimai po 30 d. automatiškai suarchyvuojami (→ draft)
 *    ir pardavėjas informuojamas el. paštu. „Rezervuota" nesensta.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

const DK_SOLD_KEEP_DAYS = 10;

/* ---------------------------------------------- laukai Dokan formose --- */
function dk_ss_fields( $post = null, $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : ( $post instanceof WP_Post ? $post->ID : 0 );
	$status  = $post_id ? (string) get_post_meta( $post_id, '_daiktuva_status', true ) : 'active';
	$phone   = $post_id ? (string) get_post_meta( $post_id, '_daiktuva_phone', true ) : '';
	if ( ! in_array( $status, array( 'active', 'reserved', 'sold' ), true ) ) { $status = 'active'; }
	?>
	<div class="dokan-form-group dk-status-group">
		<label class="form-label" for="dk_daiktuva_status">Skelbimo būsena</label>
		<select name="_daiktuva_status" id="dk_daiktuva_status" class="dokan-form-control">
			<option value="active" <?php selected( $status, 'active' ); ?>>Aktyvi — daiktas parduodamas</option>
			<option value="reserved" <?php selected( $status, 'reserved' ); ?>>Rezervuota — sutarta su pirkėju</option>
			<option value="sold" <?php selected( $status, 'sold' ); ?>>Parduota — daiktas parduotas</option>
		</select>
		<p class="dk-field-hint">Pažymėjus „Parduota", skelbimas dar <?php echo (int) DK_SOLD_KEEP_DAYS; ?> d. bus matomas su ženklu „Parduota", po to automatiškai suarchyvuojamas.</p>
	</div>
	<div class="dokan-form-group dk-phone-group">
		<label class="form-label" for="dk_daiktuva_phone">Telefonas skelbime</label>
		<input type="text" name="_daiktuva_phone" id="dk_daiktuva_phone" class="dokan-form-control" placeholder="+370..." value="<?php echo esc_attr( $phone ); ?>">
		<p class="dk-field-hint">Šiuo numeriu pirkėjai skambins ir rašys per WhatsApp.</p>
	</div>
	<div class="dokan-form-group dk-location-group">
		<label class="form-label" for="dk_location">Miestas / vieta</label>
		<input type="text" name="_dk_location" id="dk_location" class="dokan-form-control" placeholder="Pvz.: Vilnius" value="<?php echo esc_attr( $post_id ? (string) get_post_meta( $post_id, '_dk_location', true ) : '' ); ?>">
		<p class="dk-field-hint">Miestas matomas skelbimo kortelėje — pirkėjai dažnai renkasi pagal atstumą.</p>
	</div>
	<?php
}
add_action( 'dokan_new_product_after_product_tags', 'dk_ss_fields' );
add_action( 'dokan_product_edit_after_product_tags', 'dk_ss_fields', 10, 2 );

function dk_ss_save( $product_id ) {
	// phpcs:disable WordPress.Security.NonceVerification -- Dokan formos nonce patikrintas prieš šiuos hook'us.
	if ( isset( $_POST['_daiktuva_status'] ) ) {
		$status = sanitize_key( wp_unslash( $_POST['_daiktuva_status'] ) );
		if ( in_array( $status, array( 'active', 'reserved', 'sold' ), true ) ) {
			$old = (string) get_post_meta( $product_id, '_daiktuva_status', true );
			update_post_meta( $product_id, '_daiktuva_status', $status );
			if ( 'sold' === $status && 'sold' !== $old ) {
				update_post_meta( $product_id, '_dk_sold_at', time() );
			}
		}
	}
	if ( isset( $_POST['_daiktuva_phone'] ) ) {
		update_post_meta( $product_id, '_daiktuva_phone', sanitize_text_field( wp_unslash( $_POST['_daiktuva_phone'] ) ) );
	}
	if ( isset( $_POST['_dk_location'] ) ) {
		update_post_meta( $product_id, '_dk_location', sanitize_text_field( wp_unslash( $_POST['_dk_location'] ) ) );
	}
	// phpcs:enable
}
add_action( 'dokan_new_product_added', 'dk_ss_save', 12 );
add_action( 'dokan_product_updated', 'dk_ss_save', 12 );

/* ------------------------------- „Pasidalinkite" blokas po publikavimo --- */
add_action( 'dokan_product_edit_after_main', function ( $post, $post_id ) {
	if ( ! $post_id || 'publish' !== get_post_status( $post_id ) ) { return; }
	$url   = get_permalink( $post_id );
	$title = get_the_title( $post_id );
	$enc_u = rawurlencode( $url );
	$enc_t = rawurlencode( $title . ' — ' . $url );
	?>
	<div class="dk-share-nudge">
		<strong>🎉 Skelbimas paskelbtas! Pasidalinkite juo — parduosite greičiau.</strong>
		<p>Facebook grupės ir žinutės draugams — geriausias būdas greitai rasti pirkėją.</p>
		<div class="dk-share-nudge__btns">
			<a class="dk-sn dk-sn--fb" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_attr( $enc_u ); ?>">Facebook</a>
			<a class="dk-sn dk-sn--msgr" href="fb-messenger://share/?link=<?php echo esc_attr( $enc_u ); ?>">Messenger</a>
			<a class="dk-sn dk-sn--wa" target="_blank" rel="noopener" href="https://api.whatsapp.com/send?text=<?php echo esc_attr( $enc_t ); ?>">WhatsApp</a>
			<a class="dk-sn" href="<?php echo esc_url( $url ); ?>" data-dk-copy-link="<?php echo esc_url( $url ); ?>">Kopijuoti nuorodą</a>
		</div>
	</div>
	<style>
	.dk-share-nudge{margin:18px 0;padding:16px 18px;border:1px solid #bfdbfe;background:#eaf2fd;border-radius:12px;}
	.dk-share-nudge p{margin:6px 0 12px;font-size:13.5px;color:#3a4150;}
	.dk-share-nudge__btns{display:flex;gap:8px;flex-wrap:wrap;}
	.dk-sn{display:inline-block;padding:8px 16px;border-radius:8px;background:#fff;border:1px solid #cbd5e1;font-weight:600;font-size:13.5px;color:#0f4c81;text-decoration:none;}
	.dk-sn--fb{background:#1877f2;border-color:#1877f2;color:#fff;}
	.dk-sn--wa{background:#25d366;border-color:#25d366;color:#fff;}
	.dk-sn--msgr{background:#a334fa;border-color:#a334fa;color:#fff;}
	@media(hover:hover) and (pointer:fine){.dk-sn--msgr{display:none;}}
	</style>
	<?php
}, 10, 2 );

/* ----------------------------------- parduotų skelbimų archyvavimas --- */
add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'dk_sold_cleanup' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'dk_sold_cleanup' );
	}
} );

add_action( 'dk_sold_cleanup', function () {
	$cutoff = time() - DK_SOLD_KEEP_DAYS * DAY_IN_SECONDS;
	$ids    = get_posts( array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => 50,
		'fields'         => 'ids',
		'meta_query'     => array( array( 'key' => '_daiktuva_status', 'value' => 'sold' ) ),
	) );
	foreach ( $ids as $pid ) {
		$sold_at = (int) get_post_meta( $pid, '_dk_sold_at', true );
		if ( ! $sold_at ) {
			// Senas įrašas be žymos — pradedam skaičiuoti nuo dabar.
			update_post_meta( $pid, '_dk_sold_at', time() );
			continue;
		}
		if ( $sold_at > $cutoff ) { continue; }
		wp_update_post( array( 'ID' => $pid, 'post_status' => 'draft' ) );
		$author = get_userdata( (int) get_post_field( 'post_author', $pid ) );
		if ( $author && $author->user_email ) {
			wp_mail(
				$author->user_email,
				'Daiktuva: parduotas skelbimas suarchyvuotas',
				'Sveiki,' . "\n\n" . 'jūsų skelbimas „' . get_the_title( $pid ) . '" buvo pažymėtas kaip parduotas prieš ' . DK_SOLD_KEEP_DAYS . ' d., todėl automatiškai suarchyvuotas ir nebematomas svetainėje.' . "\n" . 'Jį bet kada rasite savo skydelyje: ' . dokan_get_navigation_url( 'products' ) . "\n\n" . 'Daiktuva.lt komanda'
			);
		}
	}
} );
