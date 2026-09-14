<?php
/**
 * Daiktuva: skelbimų limitai, dublikatų blokavimas ir draudžiamo turinio filtras (2026-07-04).
 *
 * 1) Aktyvių skelbimų limitas: privatus pardavėjas 10, verslas 20.
 *    Verslas žymimas user meta `dk_business` = 1 (adminas: wp user meta update <ID> dk_business 1).
 * 2) Dublikatai: tas pats pardavėjas negali turėti dviejų aktyvių skelbimų vienodu
 *    ar beveik vienodu (>=92% panašumo) pavadinimu — kad verslas neužkimštų paieškos.
 * 3) Draudžiamas turinys (sekso paslaugos, ginklai, narkotikai, klastotės ir pan.):
 *    skelbimas NEblokuojamas, bet pažymimas `_dk_flag_reason`, paliekamas „pending"
 *    ir adminas gauna laišką „būtina žmogaus peržiūra". Adminui paskelbus — žyma nuimama.
 *    (Nuotraukų turinį dengia rankinė peržiūra flagintiems; automatinis NSFW filtras — plane.)
 * 4) IP sekimas prieš limitų apėjimą: kiekvieno pateikimo IP (Cloudflare CF-Connecting-IP)
 *    rašomas prie vartotojo (`dk_ips`) ir skelbimo (`_dk_submit_ip`). Jei kelios paskyros
 *    dalinasi IP ir jų bendras aktyvių skelbimų skaičius > 20 — skelbimas sulaikomas
 *    peržiūrai ir adminas informuojamas (ne kietas blokas: gali būti šeima/biuro NAT).
 * 5) AUTO-PUBLIKAVIMAS (2026-08-21, vartotojo sprendimas): naujo pardavėjo skelbimas
 *    publikuojamas IŠ KARTO, jei pardavėjas PATVIRTINĘS el. paštą. Nepatvirtinus —
 *    skelbimas lieka „pending" su žyma `_dk_hold_email_verify` (adminui laiškas
 *    NESIUNČIAMAS); vartotojui patvirtinus el. paštą, sulaikyti skelbimai
 *    publikuojami automatiškai (daiktuva-verify.php). Administratoriaus peržiūros
 *    papildomai reikalaujama kai pardavėjas jau turi >5 aktyvių skelbimų (6-tas ir
 *    tolesni -> pending + `_dk_flag_reason` + laiškas adminui). Žmogaus peržiūros
 *    visada laukia pažymėtieji (draudžiamas turinys, IP/įrenginio apėjimai, tempas).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ------------------------------------------------------------- limitai --- */

function dk_ll_limit( int $user_id ): int {
	$business = (bool) get_user_meta( $user_id, 'dk_business', true );
	$limit    = $business ? 20 : 10;
	return max( 1, (int) apply_filters( 'dk_active_listing_limit', $limit, $user_id, $business ) );
}

function dk_ll_active_count( int $user_id ): int {
	$q = new WP_Query( array(
		'post_type'      => 'product',
		'author'         => $user_id,
		'post_status'    => array( 'publish', 'pending' ),
		'posts_per_page' => 1,
		'fields'         => 'ids',
	) );
	return (int) $q->found_posts;
}

function dk_ll_limit_error( int $user_id ): string {
	if ( ! $user_id || user_can( $user_id, 'manage_woocommerce' ) ) {
		return '';
	}
	$limit = dk_ll_limit( $user_id );
	if ( dk_ll_active_count( $user_id ) < $limit ) {
		return '';
	}
	return sprintf(
		'Pasiektas nemokamų aktyvių skelbimų limitas (%d). Norėdami talpinti daugiau skelbimų, parašykite mums info@daiktuva.lt — atsiųsime papildomų skelbimų talpinimo pasiūlymą. Taip pat galite pašalinti arba užbaigti senus skelbimus ir įdėti naują nemokamai.',
		$limit
	);
}

/* ---------------------------------------------------------- dublikatai --- */

function dk_ll_norm( string $s ): string {
	$s = mb_strtolower( trim( $s ) );
	$s = strtr( $s, array(
		'ą' => 'a', 'č' => 'c', 'ę' => 'e', 'ė' => 'e', 'į' => 'i',
		'š' => 's', 'ų' => 'u', 'ū' => 'u', 'ž' => 'z',
	) );
	$s = preg_replace( '/[^a-z0-9]+/u', ' ', $s );
	return trim( preg_replace( '/\s+/', ' ', (string) $s ) );
}

function dk_ll_duplicate_error( int $user_id, string $title, int $exclude_id = 0 ): string {
	if ( ! $user_id || user_can( $user_id, 'manage_woocommerce' ) ) {
		return '';
	}
	$norm = dk_ll_norm( $title );
	if ( '' === $norm || mb_strlen( $norm ) < 6 ) {
		return '';
	}
	$ids = get_posts( array(
		'post_type'      => 'product',
		'author'         => $user_id,
		'post_status'    => array( 'publish', 'pending' ),
		'posts_per_page' => 100,
		'fields'         => 'ids',
	) );
	foreach ( $ids as $pid ) {
		if ( $exclude_id && (int) $pid === $exclude_id ) {
			continue;
		}
		$t = dk_ll_norm( get_the_title( $pid ) );
		if ( '' === $t ) {
			continue;
		}
		$pct = 0.0;
		similar_text( $t, $norm, $pct );
		if ( $t === $norm || $pct >= 92 ) {
			return 'Labai panašų skelbimą jau turite („' . get_the_title( $pid ) . '"). Vietoj naujo dublikato atnaujinkite arba iškelkite esamą skelbimą — dublikatai apsunkina pirkėjų paiešką ir yra šalinami.';
		}
	}
	return '';
}

/* -------------------------------------------- draudžiamas turinys (flag) --- */

function dk_ll_banned_terms(): array {
	return (array) apply_filters( 'dk_banned_terms', array(
		// intymios paslaugos
		'sekso paslaug', 'intymios paslaug', 'intymias paslaug', 'erotinis masaz', 'erotini masaz',
		'eskort', 'escort', 'prostitut', 'onlyfans', 'porno', 'pornograf', 'xxx',
		// ginklai / sprogmenys
		'saunamas', 'pistolet', 'revolver', 'soviniai', 'amunicij', 'granat', 'sprogmen', 'trotil',
		// narkotikai / vaistai
		'marihuan', 'hasis', 'amfetamin', 'metamfetamin', 'mdma', 'ekstazi', 'kokain', 'heroin',
		'steroid', 'anabolik', 'receptini',
		// klastotės / dokumentai / kontrabanda
		'padirbt', 'klastot', 'kontraband', 'pilstuk', 'naminuk', 'cigaret',
	) );
}

function dk_ll_banned_match( string $text ): string {
	$t = dk_ll_norm( $text );
	foreach ( dk_ll_banned_terms() as $term ) {
		if ( false !== mb_strpos( $t, $term ) ) {
			return $term;
		}
	}
	return '';
}

/** Po pateikimo: pažymėti įtartiną skelbimą ir pranešti adminui. */
add_action( 'dokan_new_product_added', static function ( $product_id ) {
	$post = get_post( $product_id );
	if ( ! $post || user_can( (int) $post->post_author, 'manage_woocommerce' ) ) {
		return;
	}
	$hit = dk_ll_banned_match( $post->post_title . ' ' . $post->post_content . ' ' . $post->post_excerpt );
	if ( ! $hit ) {
		return;
	}
	update_post_meta( $product_id, '_dk_flag_reason', 'draudžiamo turinio raktažodis: ' . $hit );
	if ( 'pending' !== $post->post_status ) {
		wp_update_post( array( 'ID' => $product_id, 'post_status' => 'pending' ) );
	}
	$admin = get_option( 'admin_email' );
	if ( $admin ) {
		$user = get_userdata( (int) $post->post_author );
		$body = "Skelbimas sulaikytas ŽMOGAUS PERŽIŪRAI dėl galimo draudžiamo turinio.\n\n"
			. 'Skelbimas: ' . $post->post_title . "\n"
			. 'Priežastis: raktažodis „' . $hit . "\"\n"
			. 'Pardavėjas: ' . ( $user ? $user->user_login . ' <' . $user->user_email . '>' : $post->post_author ) . "\n\n"
			. 'Peržiūra: ' . admin_url( 'post.php?post=' . $product_id . '&action=edit' ) . "\n\n"
			. 'Jei skelbimas tvarkingas — tiesiog paskelbkite (žyma nusiims automatiškai). Jei ne — ištrinkite.';
		wp_mail( $admin, '[Daiktuva] ĮTARTINAS skelbimas - butina perziura', $body );
	}
}, 8 );

/** Adminui paskelbus — peržiūra atlikta, žyma nuimama. */
add_action( 'transition_post_status', static function ( $new, $old, $post ) {
	if ( $post && 'product' === $post->post_type && 'publish' === $new && 'publish' !== $old
		&& current_user_can( 'manage_woocommerce' ) ) {
		delete_post_meta( $post->ID, '_dk_flag_reason' );
	}
}, 10, 3 );

/* ------------------------------------------------ IP sekimas / apėjimas --- */

function dk_ll_client_ip(): string {
	foreach ( array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ) as $k ) {
		if ( ! empty( $_SERVER[ $k ] ) ) {
			$ip = trim( explode( ',', (string) $_SERVER[ $k ] )[0] );
			if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				return $ip;
			}
		}
	}
	return '';
}

function dk_ll_record_ip( int $user_id, string $ip ): void {
	if ( ! $ip || ! $user_id ) {
		return;
	}
	$ips = get_user_meta( $user_id, 'dk_ips', true );
	$ips = is_array( $ips ) ? $ips : array();
	$ips[ $ip ] = time();
	if ( count( $ips ) > 20 ) {
		$ips = array_slice( $ips, -20, null, true );
	}
	update_user_meta( $user_id, 'dk_ips', $ips );
	update_user_meta( $user_id, 'dk_ips_flat', implode( ',', array_keys( $ips ) ) );
}

function dk_ll_same_ip_users( string $ip, int $exclude ): array {
	$q = new WP_User_Query( array(
		'meta_key'     => 'dk_ips_flat',
		'meta_value'   => $ip,
		'meta_compare' => 'LIKE',
		'exclude'      => array( $exclude ),
		'fields'       => 'ID',
		'number'       => 20,
	) );
	return array_map( 'intval', (array) $q->get_results() );
}

add_action( 'dokan_new_product_added', static function ( $product_id ) {
	$post = get_post( $product_id );
	if ( ! $post ) {
		return;
	}
	$uid = (int) $post->post_author;
	if ( ! $uid || user_can( $uid, 'manage_woocommerce' ) ) {
		return;
	}
	$ip = dk_ll_client_ip();
	if ( ! $ip ) {
		return;
	}
	dk_ll_record_ip( $uid, $ip );
	update_post_meta( $product_id, '_dk_submit_ip', $ip );
	$others = dk_ll_same_ip_users( $ip, $uid );
	if ( ! $others ) {
		return;
	}
	$total = dk_ll_active_count( $uid );
	foreach ( $others as $o ) {
		$total += dk_ll_active_count( $o );
	}
	if ( $total <= 20 ) {
		return;
	}
	update_post_meta( $product_id, '_dk_flag_reason',
		'galimas limitų apėjimas: IP ' . $ip . ' dalinasi ' . ( count( $others ) + 1 ) . ' paskyros, bendrai aktyvių skelbimų: ' . $total );
	if ( 'pending' !== $post->post_status ) {
		wp_update_post( array( 'ID' => $product_id, 'post_status' => 'pending' ) );
	}
	$admin = get_option( 'admin_email' );
	if ( $admin ) {
		wp_mail( $admin, '[Daiktuva] Galimas limitu apejimas per kelias paskyras',
			"Skelbimas sulaikytas perziurai.\n\nSkelbimas: {$post->post_title}\nIP: {$ip}\nPaskyru su siuo IP: " . ( count( $others ) + 1 ) . "\nBendras aktyviu skelbimu sk.: {$total}\n\nPerziura: " . admin_url( 'post.php?post=' . $product_id . '&action=edit' ) );
	}
}, 9 );

/* ------------------------------------------- auto-publikavimas (svarus) --- */

add_action( 'dokan_new_product_added', static function ( $product_id ) {
	$post = get_post( $product_id );
	if ( ! $post || 'pending' !== $post->post_status ) {
		return;
	}
	$uid = (int) $post->post_author;
	if ( ! $uid ) {
		return;
	}
	if ( get_post_meta( $product_id, '_dk_flag_reason', true ) ) {
		return; // sulaikyta žmogaus peržiūrai
	}
	if ( get_user_meta( $uid, 'dk_vendor_flagged_review', true ) ) {
		return; // pardavėjas apribotas (ratelimit eskalacija)
	}
	if ( function_exists( 'dokan_is_seller_enabled' ) && ! dokan_is_seller_enabled( $uid ) ) {
		return; // pardavimas dar neįjungtas (vendor-approval delsa)
	}
	// El. paštas nepatvirtintas -> sulaikome (be laiško adminui); patvirtinus el. paštą
	// daiktuva-verify.php publikuoja automatiškai. Adminai praleidžiami.
	if ( ! user_can( $uid, 'manage_woocommerce' ) && ! get_user_meta( $uid, 'dk_email_verified', true ) ) {
		update_post_meta( $product_id, '_dk_hold_email_verify', 1 );
		return;
	}
	// >5 aktyvūs skelbimai -> 6-tas ir tolesni laukia administratoriaus patvirtinimo
	// (2026-08-21 vartotojo sprendimas: pirmi 5 publikuojami iš karto).
	$auto_max = (int) apply_filters( 'dk_auto_publish_max_active', 5 );
	$active   = dk_ll_active_count( $uid );
	if ( $active > $auto_max ) {
		update_post_meta( $product_id, '_dk_flag_reason',
			'viršyta savaiminio publikavimo riba: pardavėjas turi ' . $active . ' aktyvių skelbimų (>' . $auto_max . ') — reikalinga administratoriaus peržiūra' );
		$admin = get_option( 'admin_email' );
		if ( $admin ) {
			$user = get_userdata( $uid );
			wp_mail( $admin, '[Daiktuva] Skelbimas laukia patvirtinimo (>5 aktyvus)',
				"Skelbimas sulaikytas perziurai: pardavejas virsyjo 5 aktyviu skelbimu savaiminio publikavimo riba.\n\nSkelbimas: {$post->post_title}\nPardavejas: " . ( $user ? $user->user_login . ' <' . $user->user_email . '>' : (string) $uid ) . "\nAktyviu skelbimu: {$active}\n\nPerziura: " . admin_url( 'post.php?post=' . $product_id . '&action=edit' ) );
		}
		return;
	}
	wp_update_post( array( 'ID' => $product_id, 'post_status' => 'publish' ) );
	update_post_meta( $product_id, '_dk_auto_published', time() );
}, 20 );

/* --------------------------------------------------------------- gates --- */

add_filter( 'dokan_can_add_product', function ( $errors ) {
	$uid = get_current_user_id();
	$e   = dk_ll_limit_error( $uid );
	if ( $e ) {
		$errors[] = $e;
	}
	$title = isset( $_POST['post_title'] ) ? sanitize_text_field( wp_unslash( $_POST['post_title'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$d     = $title ? dk_ll_duplicate_error( $uid, $title ) : '';
	if ( $d ) {
		$errors[] = $d;
	}
	return $errors;
}, 25 );

add_filter( 'dokan_can_edit_product', function ( $errors ) {
	$uid     = get_current_user_id();
	$post_id = isset( $_POST['dokan_product_id'] ) ? absint( $_POST['dokan_product_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
	// Limitas tik visiškai naujiems (auto-draft) pateikimams — redagavimo neblokuojam.
	if ( $post_id && 'auto-draft' === get_post_status( $post_id ) ) {
		$e = dk_ll_limit_error( $uid );
		if ( $e ) {
			$errors[] = $e;
		}
	}
	$title = isset( $_POST['post_title'] ) ? sanitize_text_field( wp_unslash( $_POST['post_title'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$d     = $title ? dk_ll_duplicate_error( $uid, $title, $post_id ) : '';
	if ( $d ) {
		$errors[] = $d;
	}
	return $errors;
}, 25 );

add_filter( 'rest_pre_insert_product', static function ( $prepared, $request ) {
	if ( ! empty( $prepared->ID ) ) {
		return $prepared;
	}
	$uid = get_current_user_id();
	$e   = dk_ll_limit_error( $uid );
	if ( $e ) {
		return new WP_Error( 'dk_listing_limit', $e, array( 'status' => 403 ) );
	}
	if ( ! empty( $prepared->post_title ) ) {
		$d = dk_ll_duplicate_error( $uid, (string) $prepared->post_title );
		if ( $d ) {
			return new WP_Error( 'dk_listing_duplicate', $d, array( 'status' => 409 ) );
		}
	}
	return $prepared;
}, 25, 2 );

/* Anti-bot tempas: daugiau nei 10 nauju skelbimu per valanda is to paties vartotojo -> sulaikoma perziurai (pries auto-publish prio 20). */
add_action( 'dokan_new_product_added', function ( $product_id ) {
	$author = (int) get_post_field( 'post_author', $product_id );
	if ( ! $author ) { return; }
	$recent = get_posts( array(
		'post_type'      => 'product',
		'author'         => $author,
		'post_status'    => array( 'publish', 'pending', 'draft' ),
		'date_query'     => array( array( 'after' => '1 hour ago' ) ),
		'fields'         => 'ids',
		'posts_per_page' => 20,
	) );
	if ( count( $recent ) > 10 ) {
		update_post_meta( $product_id, '_dk_flag_reason', 'Per didelis skelbimu tempas (>10 per valanda) - galimas botas' );
		wp_update_post( array( 'ID' => $product_id, 'post_status' => 'pending' ) );
		$admin = get_option( 'admin_email' );
		if ( $admin ) { wp_mail( $admin, 'Daiktuva: skelbimas sulaikytas (tempas)', 'Skelbimas #' . $product_id . ' sulaikytas: per greitas kelimo tempas.' ); }
	}
}, 15 );
