<?php
/**
 * Daiktuva pašto antraščių higiena (2026-07-04).
 * Slepia serverio/įrankio pėdsakus išsiunčiamuose laiškuose:
 *  - Message-ID domenas -> @daiktuva.lt (vietoj Docker konteinerio hash'o).
 *  - Pašalina "X-Mailer: PHPMailer ..." pirštų atspaudą.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'phpmailer_init', function ( $phpmailer ) {
	$phpmailer->Hostname = 'daiktuva.lt';   // Message-ID dešinioji pusė -> @daiktuva.lt
	$phpmailer->XMailer  = ' ';             // tuščias = PHPMailer neprideda X-Mailer antraštės
}, 20 );
