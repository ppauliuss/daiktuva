<?php
/**
 * Daiktuva: vendor approval policy + VISI registruoti tampa pardavėjais (2026-07-04).
 *
 * Daiktuva = „parduok savo daiktus" (nėra krepšelio, kontaktas telefonu), tad kiekvienas
 * registruotas vartotojas turi galėti dėti skelbimus IŠ KARTO. Naujas vartotojas (įsk. Google/
 * Nextend, kurie kuria `customer`) automatiškai paverčiamas `seller` su įjungtu pardavimu — ir per
 * `user_register`, ir per `wp_login` (safety net, jei registracijos hook'as praslydo, pvz. Google).
 * Pardavimas VISADA įjungtas (jokio flood-hold — kad neblokuotų šeimos su bendru IP). Spam apsauga
 * NEblokuoja kūrimo: el. pašto patvirtinimas (nepatvirtinti -> moderacija), Turnstile, tempo/limitai.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function dk_va_cfg(): array {
	return array(
		'delay'     => max( 60, (int) apply_filters( 'dk_vendor_autoenable_delay', 5 * MINUTE_IN_SECONDS ) ),
		'ip_window' => max( 60, (int) apply_filters( 'dk_vendor_ip_window', DAY_IN_SECONDS ) ),
		'ip_max'    => max( 1, (int) apply_filters( 'dk_vendor_ip_threshold', 3 ) ),
	);
}

/** Best-effort real client IP (site is behind a Cloudflare tunnel). */
function dk_va_client_ip(): string {
	$candidates = array();
	if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
		$candidates[] = $_SERVER['HTTP_CF_CONNECTING_IP'];
	}
	if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$parts        = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
		$candidates[] = trim( $parts[0] );
	}
	if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
		$candidates[] = $_SERVER['REMOTE_ADDR'];
	}
	foreach ( $candidates as $ip ) {
		$ip = trim( (string) $ip );
		if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return $ip;
		}
	}
	return '';
}

/** Record this registration and report whether the IP is over the threshold. */
function dk_va_ip_is_flooding( string $ip ): bool {
	if ( '' === $ip ) {
		return false;
	}
	$cfg    = dk_va_cfg();
	$key    = 'dk_vreg_' . md5( $ip );
	$now    = time();
	$times  = get_transient( $key );
	if ( ! is_array( $times ) ) {
		$times = array();
	}
	$cutoff = $now - $cfg['ip_window'];
	$times  = array_values( array_filter( $times, static function ( $t ) use ( $cutoff ) {
		return (int) $t >= $cutoff;
	} ) );
	$times[] = $now;
	set_transient( $key, $times, $cfg['ip_window'] );
	return count( $times ) > $cfg['ip_max'];
}

function dk_va_notify_admin_manual( int $user_id, string $ip ): void {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return;
	}
	$admin   = get_option( 'admin_email' );
	$edit    = admin_url( 'user-edit.php?user_id=' . $user_id );
	$subject = '[Daiktuva] Naujas pardavėjas laukia rankinio patvirtinimo';
	$body    = "Naujas pardavėjas sulaikytas rankiniam patvirtinimui (per daug registracijų iš to paties IP per trumpą laiką).\n\n";
	$body   .= "Vartotojas: {$user->user_login} <{$user->user_email}>\n";
	$body   .= "IP: " . ( $ip ?: 'nežinomas' ) . "\n\n";
	$body   .= "Norėdami patvirtinti, įjunkite pardavimą Dokan pardavėjų skiltyje arba vartotojo profilyje:\n{$edit}\n";
	wp_mail( $admin, $subject, $body );
}

/**
 * Politika: pardavimas VISADA įjungtas, kad realus vartotojas galėtų dėti skelbimą IŠ KARTO.
 * Flood sulaikymas PAŠALINTAS (rizika blokuoti šeimą su bendru IP / testus). Spam apsauga lieka
 * daugiasluoksnė ir NEblokuoja kūrimo: el. pašto patvirtinimas (nepatvirtinti skelbimai eina į
 * MODERACIJĄ, ne auto-publish), Turnstile registracijoje, IP/įrenginio/tempo limitai skelbimams.
 * Esant IP flood — tik INFORMUOJAM adminą (neblokuojam).
 */
function dk_va_apply_policy( int $user_id ): void {
	if ( ! $user_id ) {
		return;
	}
	$ip = dk_va_client_ip();
	update_user_meta( $user_id, 'dk_vendor_reg_ip', $ip );
	update_user_meta( $user_id, 'dk_vendor_reg_time', time() );

	// Pardavimas visada įjungtas.
	update_user_meta( $user_id, 'dokan_enable_selling', 'yes' );

	// Informacinis pranešimas adminui, jei iš to paties IP plūsta registracijos (NEblokuoja).
	if ( ! current_user_can( 'manage_woocommerce' ) && dk_va_ip_is_flooding( $ip ) ) {
		dk_va_notify_admin_manual( $user_id, $ip );
	}
}

/** Užtikrina, kad vartotojas yra pardavėjas su įjungtu pardavimu (naudojama registracijoje IR prisijungime). */
function dk_va_ensure_seller( int $user_id ): void {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return;
	}
	if ( user_can( $user_id, 'manage_woocommerce' ) ) {
		return; // adminai / shop managers
	}
	if ( ! in_array( 'seller', (array) $user->roles, true ) ) {
		$user->set_role( 'seller' );
	}
	if ( ! get_user_meta( $user_id, 'dokan_profile_settings', true ) ) {
		update_user_meta( $user_id, 'dokan_profile_settings', array(
			// 2026-08-21: NE display_name/user_login — viešai asmens duomenų nerašome;
			// verslo pavadinimą pardavėjas galės įvesti pats nustatymuose.
			'store_name'     => 'Privatus pardavėjas',
			'social'         => array(),
			'payment'        => array( 'paypal' => array( 'email' => '' ), 'bank' => array() ),
			'phone'          => '',
			'show_email'     => 'no',
			'address'        => array(),
			'location'       => '',
			'find_address'   => '',
			'dokan_category' => '',
			'banner'         => 0,
			'gravatar'       => 0,
			'enable_tnc'     => 'off',
		) );
	}
	if ( 'yes' !== get_user_meta( $user_id, 'dokan_enable_selling', true ) ) {
		dk_va_apply_policy( $user_id );
	}
}

/** Dokan vendor-form registracija. */
add_action( 'dokan_new_seller_created', function ( $user_id, $dokan_settings = array() ) {
	dk_va_apply_policy( (int) $user_id );
}, 99, 2 );

/** VISI nauji vartotojai (įsk. Google/Nextend customer) -> pardavėjai. */
add_action( 'user_register', function ( $user_id ) {
	dk_va_ensure_seller( (int) $user_id );
}, 99 );

/** Safety net: prisijungus (pvz. per Google, kur registracijos hook'as galėjo praslysti) — užtikrinam pardavėją. */
add_action( 'wp_login', function ( $login, $user ) {
	if ( $user instanceof WP_User ) {
		dk_va_ensure_seller( (int) $user->ID );
	}
}, 10, 2 );
