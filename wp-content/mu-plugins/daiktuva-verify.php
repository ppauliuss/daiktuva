<?php
/**
 * Daiktuva: vartotojo unikalumo/tapatybės patikros (rinkos standartas, 2026-07-04).
 *
 * 1) EL. PAŠTO PATVIRTINIMAS: naujam vartotojui siunčiamas patvirtinimo laiškas.
 *    Nuo 2026-08-21 (vakaro sprendimas) el. pašto patvirtinimas VĖL yra sąlyga
 *    publikavimui: nepatvirtinto pardavėjo skelbimas lieka „pending" su žyma
 *    `_dk_hold_email_verify` (daiktuva-listing-limits.php #5), o patvirtinus —
 *    sulaikyti skelbimai publikuojami AUTOMATIŠKAI (dk_publish_held_on_verify).
 *    Seni vartotojai (iki 2026-07-04) pažymėti kaip patvirtinti.
 * 2) PRIMINIMŲ SEKA NEPATVIRTINUSIEMS (2026-09-05, vartotojo sprendimas): po
 *    registracijos laiškas primenamas po 1h, po 3h ir po 12h (vienkartiniai cron
 *    įvykiai), toliau silpnėjančiai, kol el. paštas bus patvirtintas:
 *      0–7 d.:  kas rytą 07:00 (Europe/Vilnius);
 *      7–30 d.: kas 7 dienas;
 *      30+ d.:  kas mėnesį, iki 6 kartų;
 *      po 6 mėnesinių (~7 mėn.): paskyra ŠALINAMA kartu su jos skelbimais
 *      (saugiklis: turint publikuotų skelbimų — nešalinama, pranešama adminui).
 *    Patvirtinus: suplanuoti priminimai nuimami, istorija (`dk_ev_reminders_sent`)
 *    išvaloma. Taikoma visiems nepatvirtintiems vartotojams (įsk. senuosius).
 * 3) ĮRENGINIO ID: naršyklei išduodamas atsitiktinis dk_did (localStorage+cookie,
 *    1 m.). Kelios paskyros iš to paties įrenginio su bendrai >20 aktyvių skelbimų
 *    → sulaikymas peržiūrai + laiškas adminui (kaip ir IP patikra — gaudo tuos,
 *    kurie keičia IP per VPN).
 * ROADMAP (neįdiegta, reikia paslaugų tiekėjo): SMS/telefono patvirtinimas ir 2FA
 * įtartinoms paskyroms (Twilio/Vonage ~0,04 EUR/SMS) — įjungti augant srautui.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* -------------------------------------------------- el. pasto patvirtinimas --- */

add_action( 'user_register', static function ( $user_id ) {
	if ( user_can( $user_id, 'manage_woocommerce' ) ) {
		update_user_meta( $user_id, 'dk_email_verified', 1 );
		return;
	}
	$token = wp_generate_password( 24, false, false );
	update_user_meta( $user_id, 'dk_ev_token', $token );
	$user = get_userdata( $user_id );
	if ( ! $user || ! $user->user_email ) {
		return;
	}
	$link = add_query_arg( array( 'dk_verify' => $user_id . '.' . $token ), home_url( '/' ) );
	wp_mail(
		$user->user_email,
		'Patvirtinkite savo el. paštą – Daiktuva.lt',
		"Sveiki!\n\nAčiū, kad prisijungėte prie Daiktuva.lt. Spustelėkite nuorodą, kad patvirtintumėte savo el. pašto adresą:\n\n{$link}\n\nSkelbimus galite dėti jau dabar — jie bus paskelbti iš karto, kai patvirtinsite savo el. paštą.\n\nJei tai ne jūs — tiesiog ignoruokite šį laišką.\n\n— Daiktuva.lt komanda"
	);
}, 20 );

/* ------------------------------------- priminimai nepatvirtinusiems --- */

/** Vienkartiniai priminimai po registracijos: žingsnis => delsa sekundėmis. */
function dk_ev_reminder_steps(): array {
	return array(
		1 => HOUR_IN_SECONDS,
		2 => 3 * HOUR_IN_SECONDS,
		3 => 12 * HOUR_IN_SECONDS,
	);
}

add_action( 'user_register', static function ( $user_id ) {
	if ( user_can( $user_id, 'manage_woocommerce' ) ) {
		return;
	}
	foreach ( dk_ev_reminder_steps() as $step => $delay ) {
		wp_schedule_single_event( time() + $delay, 'dk_ev_reminder', array( (int) $user_id, (int) $step ) );
	}
}, 30 );

/**
 * Išsiunčia priminimą. $step: '1'|'2'|'3' (vienkartiniai — čia pat tikrinama
 * idempotencija) arba 'daily'|'weekly'|'monthly' (tempą valdo dk_ev_daily_tick).
 * Sėkmės atveju įrašoma $sent[$step], $sent['_last'] ir (monthly) $sent['monthly_count'].
 */
function dk_ev_send_reminder( int $uid, string $step ): void {
	if ( ! $uid || get_user_meta( $uid, 'dk_email_verified', true ) ) {
		return;
	}
	$user = get_userdata( $uid );
	if ( ! $user || ! $user->user_email ) {
		return;
	}
	$sent = get_user_meta( $uid, 'dk_ev_reminders_sent', true );
	$sent = is_array( $sent ) ? $sent : array();
	if ( in_array( $step, array( '1', '2', '3' ), true ) && isset( $sent[ $step ] ) ) {
		return; // vienkartinis žingsnis jau išsiųstas
	}
	$token = (string) get_user_meta( $uid, 'dk_ev_token', true );
	if ( ! $token ) {
		$token = wp_generate_password( 24, false, false );
		update_user_meta( $uid, 'dk_ev_token', $token );
	}
	$link    = add_query_arg( array( 'dk_verify' => $uid . '.' . $token ), home_url( '/' ) );
	$subject = 'Primename: patvirtinkite savo el. paštą – Daiktuva.lt';
	$body    = "Sveiki!\n\nPrimename, kad užsiregistravote Daiktuva.lt, bet el. pašto adresas dar nepatvirtintas. Spustelėkite nuorodą:\n\n{$link}\n\n";
	switch ( $step ) {
		case 'daily':
			$body .= "Kol el. paštas nepatvirtintas, jūsų skelbimai nėra publikuojami. Priminsime kiekvieną rytą, kol patvirtinsite.\n\n";
			break;
		case 'monthly':
			$body .= "Kol el. paštas nepatvirtintas, jūsų skelbimai nėra publikuojami. Jei el. pašto nepatvirtinsite, po kurio laiko paskyra bus pašalinta.\n\n";
			break;
		default:
			$body .= "Kol el. paštas nepatvirtintas, jūsų skelbimai nėra publikuojami — patvirtinus jie bus paskelbti automatiškai.\n\n";
	}
	$body .= "Jei tai ne jūs — tiesiog ignoruokite šį laišką.\n\n— Daiktuva.lt komanda";
	if ( wp_mail( $user->user_email, $subject, $body ) ) {
		$sent[ $step ]  = time();
		$sent['_last']  = time();
		if ( 'monthly' === $step ) {
			$sent['monthly_count'] = ( isset( $sent['monthly_count'] ) ? (int) $sent['monthly_count'] : 0 ) + 1;
		}
		update_user_meta( $uid, 'dk_ev_reminders_sent', $sent );
	}
}

add_action( 'dk_ev_reminder', static function ( $uid, $step ) {
	dk_ev_send_reminder( (int) $uid, (string) $step );
}, 10, 2 );

/** Kasrytinis 07:00 (Europe/Vilnius) cron — tempą kiekvienam sprendžia dk_ev_daily_tick. */
add_action( 'init', static function () {
	if ( wp_next_scheduled( 'dk_ev_reminder_daily' ) ) {
		return;
	}
	$tz   = wp_timezone();
	$now  = new DateTimeImmutable( 'now', $tz );
	$next = $now->setTime( 7, 0 );
	if ( $next <= $now ) {
		$next = $next->modify( '+1 day' );
	}
	wp_schedule_event( $next->getTimestamp(), 'daily', 'dk_ev_reminder_daily' );
} );

/** Registracijos laikas (wp-cli kurtiems seniesiems dk_vendor_reg_time gali nebūti). */
function dk_ev_registration_ts( WP_User $user ): int {
	$t = (int) get_user_meta( $user->ID, 'dk_vendor_reg_time', true );
	if ( $t ) {
		return $t;
	}
	$t = strtotime( $user->user_registered . ' UTC' );
	return $t ? $t : time();
}

function dk_ev_last_reminder_ts( array $sent ): int {
	if ( ! empty( $sent['_last'] ) ) {
		return (int) $sent['_last'];
	}
	// Suderinamumas su pirmąja versija: 'daily' buvo 'Y-m-d' tekstas.
	if ( ! empty( $sent['daily'] ) ) {
		return is_numeric( $sent['daily'] ) ? (int) $sent['daily'] : (int) strtotime( (string) $sent['daily'] );
	}
	return 0;
}

/**
 * Silpnėjanti seka pagal paskyros amžių: <7 d. kasdien, 7–30 d. kas 7 dienas,
 * 30+ d. kas mėnesį (iki 6 kartų), po to — paskyra šalinama.
 */
function dk_ev_daily_tick( int $uid ): void {
	if ( ! $uid || get_user_meta( $uid, 'dk_email_verified', true ) || user_can( $uid, 'manage_woocommerce' ) ) {
		return;
	}
	$user = get_userdata( $uid );
	if ( ! $user || ! $user->user_email ) {
		return;
	}
	$sent = get_user_meta( $uid, 'dk_ev_reminders_sent', true );
	$sent = is_array( $sent ) ? $sent : array();
	$now  = time();
	$last = dk_ev_last_reminder_ts( $sent );
	$age  = $now - dk_ev_registration_ts( $user );

	if ( $age < 7 * DAY_IN_SECONDS ) {
		if ( $last && wp_date( 'Y-m-d', $last ) === wp_date( 'Y-m-d', $now ) ) {
			return; // šiandien jau išsiųsta
		}
		dk_ev_send_reminder( $uid, 'daily' );
		return;
	}
	if ( $age < 30 * DAY_IN_SECONDS ) {
		if ( $last && ( $now - $last ) < 7 * DAY_IN_SECONDS ) {
			return;
		}
		dk_ev_send_reminder( $uid, 'weekly' );
		return;
	}
	$count = isset( $sent['monthly_count'] ) ? (int) $sent['monthly_count'] : 0;
	if ( $count >= 6 ) {
		if ( $last && ( $now - $last ) >= 30 * DAY_IN_SECONDS ) {
			dk_ev_delete_unverified_user( $uid );
		}
		return; // 6-asis išsiųstas — mėnuo paskutinei galimybei, po to šalinama
	}
	if ( $last && ( $now - $last ) < 30 * DAY_IN_SECONDS ) {
		return;
	}
	dk_ev_send_reminder( $uid, 'monthly' );
}

add_action( 'dk_ev_reminder_daily', static function () {
	foreach ( array( 'seller', 'customer' ) as $role ) {
		$users = get_users( array( 'role' => $role, 'fields' => 'ID', 'number' => 500 ) );
		foreach ( $users as $uid ) {
			dk_ev_daily_tick( (int) $uid );
		}
	}
} );

/**
 * Nepatvirtintos paskyros šalinimas (~7 mėn. be atsako). Saugiklis: turint
 * PUBLIKUOTŲ skelbimų nešalinama — adminas informuojamas vieną kartą.
 */
function dk_ev_delete_unverified_user( int $uid ): void {
	if ( user_can( $uid, 'manage_woocommerce' ) ) {
		return;
	}
	$user = get_userdata( $uid );
	if ( ! $user ) {
		return;
	}
	$published = get_posts( array(
		'post_type'      => 'product',
		'author'         => $uid,
		'post_status'    => 'publish',
		'fields'         => 'ids',
		'posts_per_page' => 1,
	) );
	if ( $published ) {
		if ( ! get_user_meta( $uid, 'dk_ev_delete_flagged', true ) ) {
			update_user_meta( $uid, 'dk_ev_delete_flagged', time() );
			$admin = get_option( 'admin_email' );
			if ( $admin ) {
				wp_mail( $admin, '[Daiktuva] Nepatvirtinta paskyra NEpasalinta (turi publikuotu skelbimu)',
					"Vartotojas {$user->user_login} <{$user->user_email}> (ID {$uid}) virsyjo priminimu seka (~7 men. nepatvirtinto el. pasto), bet turi PUBLIKUOTU skelbimu, todel automatiskai NEpasalintas.\n\nPerziura: " . admin_url( 'user-edit.php?user_id=' . $uid ) );
			}
		}
		return;
	}
	$email = $user->user_email;
	$login = $user->user_login;
	foreach ( get_posts( array( 'post_type' => 'product', 'author' => $uid, 'post_status' => 'any', 'fields' => 'ids', 'posts_per_page' => 200 ) ) as $pid ) {
		wp_delete_post( (int) $pid, true );
	}
	require_once ABSPATH . 'wp-admin/includes/user.php';
	wp_delete_user( $uid );
	$admin = get_option( 'admin_email' );
	if ( $admin ) {
		wp_mail( $admin, '[Daiktuva] Pasalinta nepatvirtinta paskyra',
			"Automatiskai pasalinta paskyra {$login} <{$email}> (ID {$uid}): el. pastas nepatvirtintas ~7 men. (6 menesiniai priminimai be atsako). Visi jos skelbimai taip pat pasalinti." );
	}
	if ( function_exists( 'dk_tg_notify' ) ) {
		dk_tg_notify( "🗑 <b>Pašalinta nepatvirtinta paskyra</b>\n{$login} &lt;" . esc_html( $email ) . "&gt; — el. paštas nepatvirtintas ~7 mėn." );
	}
}

/** Patvirtinus el. paštą — nuimami suplanuoti priminimai ir išvaloma istorija. */
function dk_ev_clear_reminders( int $uid ): void {
	foreach ( dk_ev_reminder_steps() as $step => $delay ) {
		wp_clear_scheduled_hook( 'dk_ev_reminder', array( $uid, (int) $step ) );
	}
	delete_user_meta( $uid, 'dk_ev_reminders_sent' );
	delete_user_meta( $uid, 'dk_ev_delete_flagged' );
}

/** Po el. pašto patvirtinimo — publikuoja vartotojo sulaikytus skelbimus (`_dk_hold_email_verify`). */
function dk_publish_held_on_verify( int $uid ): void {
	if ( ! function_exists( 'dk_ll_active_count' ) ) {
		return;
	}
	$held = get_posts( array(
		'post_type'      => 'product',
		'author'         => $uid,
		'post_status'    => 'pending',
		'meta_key'       => '_dk_hold_email_verify',
		'fields'         => 'ids',
		'posts_per_page' => 50,
		'orderby'        => 'date',
		'order'          => 'ASC',
	) );
	if ( ! $held ) {
		return;
	}
	$auto_max = (int) apply_filters( 'dk_auto_publish_max_active', 5 );
	foreach ( $held as $pid ) {
		$pid = (int) $pid;
		delete_post_meta( $pid, '_dk_hold_email_verify' );
		if ( get_post_meta( $pid, '_dk_flag_reason', true ) ) {
			continue; // sulaikyta dėl kitos priežasties — laukia žmogaus peržiūros
		}
		if ( get_user_meta( $uid, 'dk_vendor_flagged_review', true ) ) {
			continue;
		}
		if ( function_exists( 'dokan_is_seller_enabled' ) && ! dokan_is_seller_enabled( $uid ) ) {
			continue;
		}
		if ( dk_ll_active_count( $uid ) > $auto_max ) {
			update_post_meta( $pid, '_dk_flag_reason',
				'viršyta savaiminio publikavimo riba po el. pašto patvirtinimo (>' . $auto_max . ' aktyvių) — reikalinga administratoriaus peržiūra' );
			$admin = get_option( 'admin_email' );
			if ( $admin ) {
				$user = get_userdata( $uid );
				wp_mail( $admin, '[Daiktuva] Skelbimas laukia patvirtinimo (>5 aktyvus)',
					"Skelbimas sulaikytas perziurai po el. pasto patvirtinimo: pardavejas virsyjo 5 aktyviu skelbimu riba.\n\nSkelbimas: " . get_the_title( $pid ) . "\nPardavejas: " . ( $user ? $user->user_login . ' <' . $user->user_email . '>' : (string) $uid ) . "\n\nPerziura: " . admin_url( 'post.php?post=' . $pid . '&action=edit' ) );
			}
			continue;
		}
		wp_update_post( array( 'ID' => $pid, 'post_status' => 'publish' ) );
		update_post_meta( $pid, '_dk_auto_published', time() );
		/* 2026-09-03: informuojam adminą, kad hold'as išsipildė pats (įskaitant viešą nuorodą —
		   ji pradeda veikti tik dabar, nes pending permalink'as viešai buvo 404). */
		if ( function_exists( 'dk_tg_notify' ) ) {
			dk_tg_notify( "✅ <b>Skelbimas paskelbtas automatiškai</b> (pardavėjas patvirtino el. paštą)\n"
				. "Skelbimas: " . esc_html( get_the_title( $pid ) ) . "\n"
				. "Nuoroda: " . esc_url( get_permalink( $pid ) ) );
		}
	}
}

add_action( 'template_redirect', static function () {
	if ( empty( $_GET['dk_verify'] ) ) {
		return;
	}
	$myaccount = wc_get_page_permalink( 'myaccount' );
	if ( ! $myaccount ) {
		$myaccount = home_url( '/my-account/' );
	}
	$parts = explode( '.', sanitize_text_field( wp_unslash( $_GET['dk_verify'] ) ), 2 );
	$uid   = ( 2 === count( $parts ) ) ? absint( $parts[0] ) : 0;
	$token = ( 2 === count( $parts ) ) ? $parts[1] : '';

	// Galiojantis token'as → patvirtinam ir aiškiai pranešam.
	if ( $uid && $token && hash_equals( (string) get_user_meta( $uid, 'dk_ev_token', true ), $token ) ) {
		update_user_meta( $uid, 'dk_email_verified', 1 );
		delete_user_meta( $uid, 'dk_ev_token' );
		dk_publish_held_on_verify( $uid );
		dk_ev_clear_reminders( $uid );
		wp_safe_redirect( add_query_arg( 'dk_verified', '1', $myaccount ) );
		exit;
	}
	// Token'as nebegalioja: jei jau patvirtinta — pasakom tai; kitaip — pasiūlom siųsti iš naujo.
	// (VISADA nukreipiam į paskyrą, kad nenumestų į pradinį puslapį.)
	if ( $uid && get_user_meta( $uid, 'dk_email_verified', true ) ) {
		dk_ev_clear_reminders( $uid );
		wp_safe_redirect( add_query_arg( 'dk_verified', 'already', $myaccount ) );
		exit;
	}
	wp_safe_redirect( add_query_arg( 'dk_verify_failed', '1', $myaccount ) );
	exit;
}, 5 );

/** Pranešimas paskyroje (prisijungus). */
add_action( 'woocommerce_account_content', static function () {
	$uid = get_current_user_id();
	if ( isset( $_GET['dk_verified'] ) ) {
		if ( 'already' === $_GET['dk_verified'] ) {
			echo '<div class="woocommerce-message" style="font-size:16px;border-left-color:#0F4C81;">✅ Jūsų el. paštas jau patvirtintas — viskas tvarkoje.</div>';
		} else {
			echo '<div class="woocommerce-message" style="font-size:16px;">✅ Puiku! El. paštas patvirtintas.</div>';
		}
		return;
	}
	if ( ! empty( $_GET['dk_verify_failed'] ) ) {
		echo '<div class="woocommerce-error" style="font-size:16px;">Patvirtinimo nuoroda nebegalioja arba jau panaudota. <a href="' . esc_url( add_query_arg( 'dk_resend_verify', '1', wc_get_page_permalink( 'myaccount' ) ) ) . '">Siųsti naują laišką</a>.</div>';
		return;
	}
	if ( $uid && ! get_user_meta( $uid, 'dk_email_verified', true ) ) {
		echo '<div class="woocommerce-info" style="font-size:16px;">Svarbu: kol nepatvirtinsite savo el. pašto, jūsų skelbimai nebus publikuojami. Išsiuntėme laišką su patvirtinimo nuoroda (patikrinkite ir šlamšto/„Spam" aplanką). <a href="' . esc_url( add_query_arg( 'dk_resend_verify', '1' ) ) . '">Siųsti laišką dar kartą</a></div>';
	}
}, 5 );

/** Pranešimas prie prisijungimo formos (jei el. paštą patvirtino NEprisijungęs — pvz. paspaudė nuorodą kitame įrenginyje). */
add_action( 'woocommerce_before_customer_login_form', static function () {
	if ( isset( $_GET['dk_verified'] ) ) {
		echo '<div class="woocommerce-message" style="font-size:16px;">✅ El. paštas patvirtintas! Prisijunkite ir galėsite iš karto dėti skelbimus.</div>';
	} elseif ( ! empty( $_GET['dk_verify_failed'] ) ) {
		echo '<div class="woocommerce-error" style="font-size:16px;">Patvirtinimo nuoroda nebegalioja. Prisijunkite ir paspauskite „Siųsti laišką dar kartą".</div>';
	}
} );

add_action( 'template_redirect', static function () {
	if ( empty( $_GET['dk_resend_verify'] ) || ! is_user_logged_in() ) {
		return;
	}
	$uid = get_current_user_id();
	if ( get_user_meta( $uid, 'dk_email_verified', true ) ) {
		return;
	}
	$token = (string) get_user_meta( $uid, 'dk_ev_token', true );
	if ( ! $token ) {
		$token = wp_generate_password( 24, false, false );
		update_user_meta( $uid, 'dk_ev_token', $token );
	}
	$user = get_userdata( $uid );
	$link = add_query_arg( array( 'dk_verify' => $uid . '.' . $token ), home_url( '/' ) );
	wp_mail( $user->user_email, 'Patvirtinkite savo el. paštą – Daiktuva.lt', "Patvirtinimo nuoroda:\n\n{$link}\n\n— Daiktuva.lt" );
	wp_safe_redirect( remove_query_arg( 'dk_resend_verify' ) );
	exit;
}, 6 );

/* ------------------------------------------------------------ irenginio ID --- */

add_action( 'wp_footer', static function () {
	if ( is_admin() ) {
		return;
	}
	?><script>(function(){try{var k='dk_did',v=localStorage.getItem(k);if(!v){v='d'+Date.now().toString(36)+Math.random().toString(36).slice(2,12);localStorage.setItem(k,v);}document.cookie=k+'='+v+';path=/;max-age=31536000;SameSite=Lax;Secure';}catch(e){}})();</script><?php
}, 99 );

function dk_did_current(): string {
	$v = isset( $_COOKIE['dk_did'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['dk_did'] ) ) : '';
	return preg_match( '/^d[a-z0-9]{8,40}$/', $v ) ? $v : '';
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
	$did = dk_did_current();
	if ( ! $did ) {
		return;
	}
	$dids = get_user_meta( $uid, 'dk_dids', true );
	$dids = is_array( $dids ) ? $dids : array();
	$dids[ $did ] = time();
	if ( count( $dids ) > 10 ) {
		$dids = array_slice( $dids, -10, null, true );
	}
	update_user_meta( $uid, 'dk_dids', $dids );
	update_user_meta( $uid, 'dk_dids_flat', implode( ',', array_keys( $dids ) ) );
	update_post_meta( $product_id, '_dk_submit_did', $did );

	$q = new WP_User_Query( array(
		'meta_key'     => 'dk_dids_flat',
		'meta_value'   => $did,
		'meta_compare' => 'LIKE',
		'exclude'      => array( $uid ),
		'fields'       => 'ID',
		'number'       => 20,
	) );
	$others = array_map( 'intval', (array) $q->get_results() );
	if ( ! $others || ! function_exists( 'dk_ll_active_count' ) ) {
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
		'galimas limitų apėjimas: tas pats ĮRENGINYS naudojamas ' . ( count( $others ) + 1 ) . ' paskyrų, bendrai aktyvių skelbimų: ' . $total );
	if ( 'pending' !== get_post_status( $product_id ) ) {
		wp_update_post( array( 'ID' => $product_id, 'post_status' => 'pending' ) );
	}
	$admin = get_option( 'admin_email' );
	if ( $admin ) {
		wp_mail( $admin, '[Daiktuva] Galimas limitu apejimas (tas pats irenginys)',
			"Skelbimas sulaikytas perziurai.\n\nSkelbimas: {$post->post_title}\nIrenginio ID: {$did}\nPaskyru su siuo irenginiu: " . ( count( $others ) + 1 ) . "\nBendras aktyviu skelbimu sk.: {$total}\n\nPerziura: " . admin_url( 'post.php?post=' . $product_id . '&action=edit' ) );
	}
}, 9 );
