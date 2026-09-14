<?php
/**
 * Daiktuva laiškų šablonai + automatiniai laiškai (2026-07-04).
 *
 * 1) ŠABLONINIAI ATSAKYMAI: watcher'is (hermes-mail) apie naują laišką praneša į Telegram
 *    su mygtukais (po vieną kiekvienam šablonui). Mygtukas = URL nuoroda į šį WP endpointą
 *    `/?dk_reply=TOKEN&tpl=KEY` — paspaudus, atsakymas iškart išsiunčiamas kaip Re: originalui.
 *    Watcher'is prieš tai per REST `dk/v1/pending` užregistruoja pending atsakymą (su X-DK-Secret).
 *    Jokio Telegram getUpdates -> jokio konflikto su Hermes botu; siunčia pati WP (turi SMTP).
 *
 * 2) AUTOMATINIS pasveikinimo laiškas užsiregistravus (user_register).
 *
 * Šablonus redaguoti lengva — masyvas dk_mail_templates() žemiau.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ------------------------------------------------------ ŠABLONAI --- */
function dk_mail_templates(): array {
	$sig = "\n\nGeros dienos,\nDaiktuva.lt komanda\nhttps://daiktuva.lt";
	return array(
		'gauta' => array(
			'label' => '✅ Gavome, atsakysim',
			'body'  => "Sveiki,\n\načiū už Jūsų žinutę! Ją gavome ir netrukus atsakysime plačiau." . $sig,
		),
		'kaip_ideti' => array(
			'label' => '📋 Kaip įdėti skelbimą',
			'body'  => "Sveiki,\n\nskelbimą įdėti paprasta ir nemokama:\n1. Prisiregistruokite arba prisijunkite daiktuva.lt.\n2. Spauskite „+ Įdėti skelbimą“.\n3. Įkelkite nuotrauką, aprašymą, kainą ir miestą — ir paskelbkite.\n\nJei kiltų klausimų, mielai padėsime." . $sig,
		),
		'verslo' => array(
			'label' => '🏢 Verslo paskyra',
			'body'  => "Sveiki,\n\nverslo paskyra leidžia talpinti iki 20 nemokamų aktyvių skelbimų (privatiems — 10). Norėdami verslo paskyrą, atrašykite mums įmonės pavadinimą ir el. paštą — įjungsime.\n\nDaugiau skelbimų nei limitas galima talpinti pagal atskirą pasiūlymą." . $sig,
		),
		'daugiau_skelbimu' => array(
			'label' => '➕ Daugiau skelbimų',
			'body'  => "Sveiki,\n\nviršijus nemokamų skelbimų limitą, papildomą talpinimą galime pasiūlyti atskirai — parašykite, kiek skelbimų planuojate, ir atsiųsime tinkamą pasiūlymą." . $sig,
		),
		'aktualu' => array(
			'label' => '👍 Dar aktualu',
			'body'  => "Sveiki,\n\ntaip, skelbimas dar aktualus. Dėl detalių ar susitikimo drąsiai rašykite arba skambinkite skelbime nurodytu numeriu." . $sig,
		),
		'parduota' => array(
			'label' => '❌ Jau parduota',
			'body'  => "Sveiki,\n\ndeja, prekė jau parduota. Ačiū už susidomėjimą ir sėkmės ieškant — daiktuva.lt nuolat atsiranda naujų skelbimų!" . $sig,
		),
	);
}

/* -------------------------------- REST: watcher registruoja pending --- */
add_action( 'rest_api_init', function () {
	register_rest_route( 'dk/v1', '/pending', array(
		'methods'             => 'POST',
		'permission_callback' => '__return_true',
		'callback'            => 'dk_reply_register',
	) );
} );

function dk_reply_register( $req ) {
	$secret = (string) get_option( 'dk_reply_secret', '' );
	$given  = (string) $req->get_header( 'x-dk-secret' );
	if ( '' === $secret || ! hash_equals( $secret, $given ) ) {
		return new WP_Error( 'forbidden', 'bad secret', array( 'status' => 403 ) );
	}
	$to = sanitize_email( (string) $req->get_param( 'to' ) );
	if ( ! is_email( $to ) ) {
		return new WP_Error( 'bad_to', 'no valid to', array( 'status' => 400 ) );
	}
	$token = wp_generate_password( 32, false );
	set_transient( 'dk_reply_' . $token, array(
		'to'         => $to,
		'name'       => sanitize_text_field( (string) $req->get_param( 'name' ) ),
		'subject'    => sanitize_text_field( (string) $req->get_param( 'subject' ) ),
		'message_id' => sanitize_text_field( (string) $req->get_param( 'message_id' ) ),
	), 7 * DAY_IN_SECONDS );

	$links = array();
	foreach ( dk_mail_templates() as $k => $t ) {
		$links[] = array(
			'key'   => $k,
			'label' => $t['label'],
			'url'   => home_url( '/?dk_reply=' . rawurlencode( $token ) . '&tpl=' . rawurlencode( $k ) ),
		);
	}
	return array( 'token' => $token, 'links' => $links );
}

/* ------------------------------- Nuorodos paspaudimas -> siunčia atsakymą --- */
add_action( 'init', function () {
	if ( ! isset( $_GET['dk_reply'], $_GET['tpl'] ) ) { return; }
	$token = sanitize_text_field( wp_unslash( $_GET['dk_reply'] ) );
	$tpl   = sanitize_key( wp_unslash( $_GET['tpl'] ) );
	$ctx   = get_transient( 'dk_reply_' . $token );
	$tpls  = dk_mail_templates();

	if ( ! $ctx || ! isset( $tpls[ $tpl ] ) ) {
		dk_reply_page( false, 'Nuoroda nebegalioja arba jau panaudota.', '', '' );
	}

	$subject = (string) $ctx['subject'];
	if ( stripos( $subject, 're:' ) !== 0 ) { $subject = 'Re: ' . $subject; }
	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
	if ( ! empty( $ctx['message_id'] ) ) {
		$headers[] = 'In-Reply-To: ' . $ctx['message_id'];
		$headers[] = 'References: ' . $ctx['message_id'];
	}
	$body = $tpls[ $tpl ]['body'];
	$ok   = wp_mail( $ctx['to'], $subject, $body, $headers );
	if ( $ok ) {
		dk_imap_append_sent( $ctx['to'], $subject, $body, $ctx['message_id'] ); // kopija į Sent (best-effort)
	}
	delete_transient( 'dk_reply_' . $token ); // vienkartinis
	dk_reply_page( $ok, $ok ? ( 'Atsakymas išsiųstas: ' . $tpls[ $tpl ]['label'] ) : 'Nepavyko išsiųsti (bandykite dar kartą).', $ctx['to'], $body );
}, 1 );

function dk_reply_page( bool $ok, string $msg, string $to, string $body ) {
	status_header( 200 );
	nocache_headers();
	$color = $ok ? '#16a34a' : '#dc2626';
	$icon  = $ok ? '✅' : '⚠️';
	echo '<!doctype html><html lang="lt"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Daiktuva — atsakymas</title>';
	echo '<style>body{font-family:-apple-system,Segoe UI,Roboto,sans-serif;background:#f1f5f9;margin:0;padding:24px;color:#1f2430}';
	echo '.card{max-width:520px;margin:8vh auto;background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:28px;box-shadow:0 12px 40px rgba(20,23,33,.10)}';
	echo '.h{font-size:20px;font-weight:800;color:' . esc_attr( $color ) . ';margin:0 0 6px}';
	echo '.to{color:#64748b;font-size:14px;margin:0 0 16px}';
	echo 'pre{white-space:pre-wrap;background:#f8fafc;border:1px solid #eef2f7;border-radius:10px;padding:14px;font:14px/1.5 -apple-system,Segoe UI,sans-serif;color:#334155}';
	echo 'a{display:inline-block;margin-top:18px;color:#2563eb;font-weight:700;text-decoration:none}</style></head><body><div class="card">';
	echo '<p class="h">' . $icon . ' ' . esc_html( $msg ) . '</p>';
	if ( $to ) { echo '<p class="to">Gavėjas: ' . esc_html( $to ) . '</p>'; }
	if ( $body ) { echo '<pre>' . esc_html( $body ) . '</pre>'; }
	$mailbox = getenv( 'DAIKTUVA_MAILBOX_URL' ) ?: '';
	if ( '' !== $mailbox ) {
		echo '<a href="' . esc_url( $mailbox ) . '">Atidaryti pašto dėžutę →</a>';
	}
	echo '</div></body></html>';
	exit;
}

/* ------------ Raw IMAP APPEND: išsiųsto atsakymo kopija į Sent aplanką --- */
function dk_imap_append_sent( string $to, string $subject, string $body, string $in_reply_to = '' ): bool {
	$host = getenv( 'DAIKTUVA_IMAP_HOST' ) ?: '';
	$user = getenv( 'DAIKTUVA_SMTP_USER' ) ?: 'info@daiktuva.lt';
	$pass = getenv( 'DAIKTUVA_SMTP_PASSWORD' ) ?: '';
	if ( '' === $host || '' === $pass ) { return false; }

	$ctx = stream_context_create( array( 'ssl' => array( 'verify_peer' => false, 'verify_peer_name' => false ) ) );
	$fp  = @stream_socket_client( "ssl://$host:993", $eno, $estr, 10, STREAM_CLIENT_CONNECT, $ctx );
	if ( ! $fp ) { return false; }
	stream_set_timeout( $fp, 10 );

	$read_until = function ( $tag ) use ( $fp ) {
		$out = '';
		while ( ! feof( $fp ) ) {
			$l = fgets( $fp, 8192 );
			if ( false === $l ) { break; }
			$out .= $l;
			if ( preg_match( '/^' . preg_quote( $tag, '/' ) . ' (OK|NO|BAD)/i', $l ) ) { break; }
		}
		return $out;
	};

	fgets( $fp, 8192 ); // greeting
	fwrite( $fp, 'a1 LOGIN "' . addcslashes( $user, '"\\' ) . '" "' . addcslashes( $pass, '"\\' ) . "\"\r\n" );
	if ( ! preg_match( '/a1 OK/i', $read_until( 'a1' ) ) ) { fclose( $fp ); return false; }

	$enc_subject = function_exists( 'mb_encode_mimeheader' ) ? mb_encode_mimeheader( $subject, 'UTF-8', 'B' ) : $subject;
	$msg  = 'From: Daiktuva.lt <info@daiktuva.lt>' . "\r\n";
	$msg .= 'To: ' . $to . "\r\n";
	$msg .= 'Subject: ' . $enc_subject . "\r\n";
	$msg .= 'Date: ' . gmdate( 'D, d M Y H:i:s' ) . " +0000\r\n";
	$msg .= 'Message-ID: <' . wp_generate_uuid4() . '@daiktuva.lt>' . "\r\n";
	if ( $in_reply_to ) {
		$msg .= 'In-Reply-To: ' . $in_reply_to . "\r\n";
		$msg .= 'References: ' . $in_reply_to . "\r\n";
	}
	$msg .= "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n" . $body;
	$msg  = str_replace( "\n", "\r\n", str_replace( "\r\n", "\n", $msg ) ); // normalizuojam į CRLF

	$folder = getenv( 'DAIKTUVA_SENT_FOLDER' ) ?: 'Sent';
	fwrite( $fp, 'a2 APPEND "' . $folder . '" (\\Seen) {' . strlen( $msg ) . "}\r\n" );
	$cont = fgets( $fp, 8192 );
	if ( false === $cont || '+' !== substr( ltrim( $cont ), 0, 1 ) ) { fclose( $fp ); return false; }
	fwrite( $fp, $msg . "\r\n" );
	$res = $read_until( 'a2' );
	fwrite( $fp, "a3 LOGOUT\r\n" );
	fclose( $fp );
	return (bool) preg_match( '/a2 OK/i', $res );
}

/* ------------------------------- AUTOMATINIS pasveikinimo laiškas --- */
add_action( 'user_register', function ( $user_id ) {
	$u = get_userdata( $user_id );
	if ( ! $u || ! is_email( $u->user_email ) ) { return; }
	if ( get_user_meta( $user_id, '_dk_welcomed', true ) ) { return; }
	update_user_meta( $user_id, '_dk_welcomed', 1 );

	$name = $u->display_name ? $u->display_name : 'sveiki';
	$body = "Sveiki, {$name},\n\n"
		. "sveiki atvykę į Daiktuva.lt — skelbimų portalą iš pirmų rankų, be komisinių!\n\n"
		. "Ką galite daryti iškart:\n"
		. "• Įdėti skelbimą nemokamai — spauskite „+ Įdėti skelbimą“.\n"
		. "• Naršyti ir įsiminti patikusius daiktus.\n"
		. "• Susisiekti su pardavėjais tiesiogiai.\n\n"
		. "Jei registruodamiesi gavote patvirtinimo laišką — patvirtinkite el. paštą, kad skelbimai būtų skelbiami iškart.\n\n"
		. "Gerų sandorių!\nDaiktuva.lt komanda\nhttps://daiktuva.lt";
	wp_mail( $u->user_email, 'Sveiki atvykę į Daiktuva.lt', $body, array( 'Content-Type: text/plain; charset=UTF-8' ) );
}, 30 );
