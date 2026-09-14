<?php
/**
 * Daiktuva: likę neišversti Dokan tekstai (2026-07-04).
 * Pagrindiniai Dokan skydelio tekstai jau išversti, bet keli pralindo angliškai
 * (skelbimo sukūrimo pranešimas, „Create & Add New" mygtukas). Verčiame juos per
 * gettext filtrą ir suvienodiname terminą į „skelbimas" (ne „prekė").
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_filter( 'gettext', static function ( $translated, $text, $domain ) {
	if ( 'dokan-lite' !== $domain && 'dokan' !== $domain ) {
		return $translated;
	}
	static $map = array(
		'You have successfully created %s product' => 'Skelbimas %s sėkmingai sukurtas!',
		'Create & Add New'                         => 'Sukurti ir pridėti kitą',
		'Add New Product'                          => 'Įkelti skelbimą',
		'Add new product'                          => 'Įkelti skelbimą',
		'Create product'                           => 'Sukurti skelbimą',
		'No product has been found!'                => 'Daugiau pardavėjo prekių nėra.',
		'Draft'                                     => 'Juodraštis',
		'Are you sure?'                            => 'Ar tikrai norite ištrinti?',
		'Edit'                                     => 'Redaguoti',
		'View'                                     => 'Peržiūrėti',
		'Delete'                                   => 'Ištrinti',
		'Delete Permanently'                       => 'Ištrinti visam laikui',
		/* 2026-08-14: vendor onboarding puslapio pralindę angliški tekstai + validacija */
		'Login'                                     => 'Prisijungimas',
		'Registration'                              => 'Registracija',
		'Register'                                  => 'Registruotis',
		'A link to set a new password will be sent to your email address.'
		                                          => 'Slaptažodžio nustatymo nuoroda bus atsiųsta jūsų el. paštu.',
		'You are already logged in'                 => 'Jau esate prisijungęs',
		'Please enter product title'                => 'Įveskite skelbimo pavadinimą',
		'Please select a category'                  => 'Pasirinkite kategoriją',
		'Please select at least one category'       => 'Pasirinkite bent vieną kategoriją',
		'Add New Category'                          => 'Pasirinkite kategoriją',
		'Edit Product'                              => 'Redaguoti skelbimą',
		/* 2026-08-20: nuotraukų pasirinkimo modalas (wp.media per Dokan) pralindę angliški */
		'Select and Crop'                           => 'Pasirinkti',
		'Upload featured image'                     => 'Viršelio nuotrauka',
		'Upload Product Image'                      => 'Įkelti viršelio nuotrauką',
		'Set featured image'                        => 'Naudoti kaip viršelį',
		'Add Images to Product Gallery'             => 'Pridėti nuotraukas į galeriją',
		'Add to gallery'                            => 'Pridėti į galeriją',
		'Add gallery image'                         => 'Pridėti galerijos nuotrauką',
		'View Product'                              => 'Peržiūrėti skelbimą',
		/* 2026-09-03: skelbimų lentelės filtro gamintojo dropdown (listing-filter.php) */
		'- Select a brand -'                        => '— Gamintojas —',
		'Most Popular'                              => 'Populiariausi',
		'Random'                                    => 'Atsitiktinai',
	);
	return isset( $map[ $text ] ) ? $map[ $text ] : $translated;
}, 20, 3 );

/* LT paketas kai kur verčia „product" -> „prekė"; svetainėje terminą vienodinam į „skelbimas". */
add_filter( 'gettext', static function ( $translated, $text, $domain ) {
	/* 2026-09-03: Dokan skydelyje „Prekės" -> „Skelbimai" (meniu, paieškos laukas).
	   Ribota TIK dokan domenams — Woo kitur „Prekės" turi likti (pvz. parduotuvės kontekstai). */
	if ( in_array( $domain, array( 'dokan-lite', 'dokan' ), true ) ) {
		if ( 'Prekės' === $translated ) {
			return 'Skelbimai';
		}
		if ( 'Ieškoti prekių' === $translated ) {
			return 'Ieškoti skelbimų';
		}
	}
	static $lt_map = array(
		'Pridėti naują prekę'  => 'Įkelti skelbimą',
		'Pridėti naują produktą' => 'Įkelti skelbimą',
		'Redaguoti prekę'        => 'Redaguoti skelbimą',
		'Redaguoti produktą'     => 'Redaguoti skelbimą',
		'Įkelkite prekės viršelio nuotrauką' => 'Įkelkite viršelio nuotrauką',
	);
	return isset( $lt_map[ $translated ] ) ? $lt_map[ $translated ] : $translated;
}, 30, 3 );
