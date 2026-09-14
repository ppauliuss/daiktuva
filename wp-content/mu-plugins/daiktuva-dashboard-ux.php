<?php
/**
 * Daiktuva: pardavėjo skydelio UX (2026-07-05).
 * Skelbimų lentelėje veiksmai (Redaguoti / Peržiūrėti / Ištrinti) Dokan'e rodomi TIK užvedus pelę
 * (hover) → telefone/planšetėje nematomi, vartotojas nerasdavo „Ištrinti". Padarom juos VISADA
 * matomus, o „Ištrinti" — ryškų (raudonas + šiukšliadėžės ikona). Trynimas turi patvirtinimą.
 *
 * 2026-08-19 (dizaino audito raundas 3 — skydelis):
 *  - Dokan violetinė (#7047EB) perdažyta į Daiktuva paletę: aktyvus meniu punktas
 *    oranžinis (kaip CTA), mygtukai tamsiai mėlyni. Dokan spalva įkoduota tiesiogiai
 *    (32 kartus CSS'e, be kintamojo) — override'ai su specifiškumu + !important.
 *  - Paslėpti nereikalingi punktai skelbimų svetainėje: „Atsiimti lėšas" (withdraw),
 *    „Nustatymai → Mokėjimas" (mokėjimų nėra), „Atsisiuntimai" My Account meniu.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* Užsakymų būsenų juostoje „All (0)" — Dokan ją deda HARDCODED (be __()),
   bet turi filtrą: pakeičiam į „Visi". */
add_filter( 'dokan_vendor_dashboard_order_listing_statuses', static function ( $statuses ) {
	if ( isset( $statuses['all'] ) && 'All' === $statuses['all'] ) {
		$statuses['all'] = 'Visi';
	}
	return $statuses;
} );

add_action( 'wp_head', static function () {
	if ( is_admin() ) {
		return;
	}
	echo '<style id="dk-dashboard-ux">'
		. '.dokan-product-listing .dokan-product-listing-area .row-actions,'
		. '.dokan-product-listing .product-listing-table .row-actions{'
		. 'visibility:visible !important;opacity:1 !important;position:static !important;left:auto !important;height:auto !important;margin-top:6px;font-size:13px;line-height:1.7}'
		. '.dokan-product-listing .row-actions .delete a{color:#dc2626 !important;font-weight:800}'
		. '.dokan-product-listing .row-actions .delete a:before{content:"\\1F5D1  "}'
		. '@media(max-width:768px){.dokan-product-listing .row-actions{margin-top:8px}}'
		/* Dokan violetinė → Daiktuva paletė (aktyvus meniu = CTA oranžinė, mygtukai = tamsiai mėlyna). */
		. '.dokan-dashboard .dokan-dash-sidebar ul.dokan-dashboard-menu li.active{background:#ea7a1f}'
		. '.dokan-dashboard .dokan-btn-theme,'
		. '.dokan-dashboard input[type="submit"].dokan-btn-theme,'
		. '.woocommerce-account .dokan-btn-theme,'
		. '.woocommerce-account a.dokan-btn-theme{background:#1e3a5f !important;border-color:#1e3a5f !important;color:#fff !important}'
		. '.dokan-dashboard .dokan-btn-theme:hover,'
		. '.woocommerce-account .dokan-btn-theme:hover{background:#152c48 !important;border-color:#152c48 !important;color:#fff !important}'
		/* Nereikalingi punktai skelbimų svetainėje (mokėjimų ir atsisiuntimų nėra).
		   SVARBU: tiesioginis vaikas (>), kitaip :has() pagaudtų ir tėvinį „Nustatymai" li. */
		. '.dokan-dashboard .dokan-dashboard-menu li:has(> a[href*="withdraw"]){display:none !important}'
		. '.dokan-dashboard .dokan-dashboard-menu li:has(> a[href*="settings/payment"]){display:none !important}'
		. '.woocommerce-MyAccount-navigation-link--downloads{display:none !important}'
		. '</style>';
} );
