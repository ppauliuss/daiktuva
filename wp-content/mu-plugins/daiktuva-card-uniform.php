<?php
/**
 * Daiktuva: vienodo aukščio prekių kortelės + „Parduota" ženkliuko spalva (2026-07-05).
 * Vartotoja pastebėjo: demo skelbimai aukštesni už vartotojų (demo turi papildomą miesto
 * eilutę + daugiau metaduomenų) → kainos/„Skambinti" mygtukai nesulygiuoti. Sprendimas:
 *   - kortelė = flex stulpelis, kaina+mygtukas prisegti prie apačios (margin-top:auto);
 *   - visų eilučių aukštis vienodas (grid-auto-rows:1fr) — nuo 2 stulpelių pločio.
 * Taip pat: „Parduota" ženkliukas žalias (#16a34a) → RAUDONAS (#475569), kad atkreiptų dėmesį
 * (parduota = nebeaktualu); „Rezervuota" lieka gintarinė (#d97706). Prio 99 — po katalogo CSS.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'wp_head', static function () {
	if ( is_admin() ) {
		return;
	}
	echo '<style id="dk-card-uniform">'
		/* Kortelė = flex stulpelis (nuotrauka viršuje, Astra turinio blokas užpildo likusią vietą). */
		. 'ul.products li.product{display:flex !important;flex-direction:column !important;height:100% !important}'
		. 'ul.products li.product > .astra-shop-summary-wrap{flex:1 1 auto !important;display:flex !important;flex-direction:column !important}'
		/* Kaina prisegama turinio bloko apačioje → kaina + „Skambinti" mygtukas vienoje linijoje visose kortelėse. */
		. 'ul.products li.product .astra-shop-summary-wrap .price{margin-top:auto !important}'
		/* Visų eilučių aukštis vienodas (kai ≥2 stulpeliai). */
		. '@media(min-width:461px){.dk-main ul.products{grid-auto-rows:1fr !important;align-items:stretch !important}}'
		/* „Parduota" ženkliukas – grafitas (neutralus, nuslopina); „Rezervuota" lieka gintarinė. */
		. '.daiktuva-listing-status.is-sold{background:#475569 !important}'
		. '.daiktuva-status-sold{color:#475569 !important}'
		/* Ženkliuko tekstas BALTAS — tema per !important verčia navy (#0F4C81), ant grafito nematomas. */
		. '.daiktuva-listing-status,.daiktuva-listing-status *{color:#fff !important}'
		/* Parduotos prekės nuotrauka subtiliai nublankinama = „parduota" pojūtis (Vinted stilius). */
		. 'ul.products li.product:has(.daiktuva-listing-status.is-sold) img{filter:grayscale(.4) brightness(.95) !important}'
		. '</style>';
}, 99 );
