<?php
/**
 * Daiktuva: Lithuanian overrides for untranslated Dokan (vendor dashboard) strings.
 *
 * Dokan Lite ships only partial lt_LT translations, so the seller product form and
 * product list show English. We override those msgids via the gettext filter,
 * scoped strictly to the 'dokan-lite' text domain so nothing else is affected.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function dk_dokan_lt_map(): array {
	static $map = null;
	if ( null !== $map ) {
		return $map;
	}
	$map = array(
		// Product form – basics
		'Title'                          => 'Pavadinimas',
		'Title :'                        => 'Pavadinimas :',
		'Downloadable'                   => 'Atsisiunčiama',
		'Virtual'                        => 'Virtuali',
		' You Earn : '                   => ' Jūs uždirbsite : ',
		'Discounted Price'               => 'Akcijos kaina',
		'Schedule'                       => 'Tvarkaraštis',
		'Brand'                          => 'Prekės ženklas',
		'Select brand'                   => 'Pasirinkite prekės ženklą',
		'Select product tags'            => 'Pasirinkite prekės žymas',
		'Upload a product cover image'   => 'Įkelkite prekės viršelio nuotrauką',
		'Categories'                     => 'Kategorijos',
		'Select a category'              => 'Pasirinkite kategoriją',
		'- Select a category -'          => '– Pasirinkite kategoriją –',
		'Search category'                => 'Ieškoti kategorijos',
		'Done'                           => 'Atlikta',

		// Inventory / Atsargos
		'Manage inventory for this product'  => 'Tvarkykite šios prekės atsargas',
		'Manage inventory for this product.' => 'Tvarkykite šios prekės atsargas.',
		'Stock Keeping Unit'             => 'Prekės kodas (SKU)',
		'(Stock Keeping Unit)'           => '(prekės kodas)',
		'Stock Status'                   => 'Atsargų būsena',
		'In Stock'                       => 'Yra sandėlyje',
		'Out of Stock'                   => 'Nėra sandėlyje',
		'On Backorder'                   => 'Galimas išankstinis užsakymas',
		'Allow Backorders'               => 'Leisti išankstinius užsakymus',
		'Enable product stock management' => 'Įjungti prekės atsargų valdymą',
		'Allow only one quantity of this product to be bought in a single order'  => 'Leisti pirkti tik vieną šios prekės vienetą viename užsakyme',
		'Allow only one quantity of this product to be bought in a single order.' => 'Leisti pirkti tik vieną šios prekės vienetą viename užsakyme.',

		// Other Options
		'Other Options'                  => 'Kitos parinktys',
		'Set your extra product options' => 'Nustatykite papildomas prekės parinktis',
		'Product Status'                 => 'Prekės būsena',
		'Visibility'                     => 'Matomumas',
		'Visible'                        => 'Matoma',
		'Catalog'                        => 'Katalogas',
		'Hidden'                         => 'Paslėpta',
		'Enable product reviews'         => 'Įjungti prekės atsiliepimus',

		// Product list / filters
		'All'                            => 'Visi',
		'All (%s)'                       => 'Visi (%s)',
		'Publish'                        => 'Paskelbti',
		'Reset'                          => 'Atstatyti',
		'Search Products'                => 'Ieškoti prekių',
		'Last Modified'                  => 'Paskutinį kartą keista',

		// Product list – table columns & bulk actions
		'Name'                           => 'Pavadinimas',
		'Image'                          => 'Nuotrauka',
		'Earning'                        => 'Uždarbis',
		'Type'                           => 'Tipas',
		'Views'                          => 'Peržiūros',
		'Date'                           => 'Data',
		'All dates'                      => 'Visos datos',
		'Bulk Actions'                   => 'Masiniai veiksmai',
		'Select bulk action'             => 'Pasirinkite masinį veiksmą',
		'Delete Permanently'             => 'Ištrinti negrįžtamai',

		// Save result / notices
		'Success!'                       => 'Pavyko!',
		'Error!'                         => 'Klaida!',
		'No Products Found!'             => 'Skelbimų nerasta!',
		'Error! Your account is not enabled for selling, please contact the admin' => 'Klaida! Jūsų paskyrai pardavimas dar neįjungtas. Susisiekite su administratoriumi.',
		'Your account is not enabled for selling, please contact the admin'        => 'Jūsų paskyrai pardavimas dar neįjungtas. Susisiekite su administratoriumi.',
		'Vendor is not enabled for selling, please contact site admin'             => 'Pardavėjui pardavimas neįjungtas. Susisiekite su administratoriumi.',
		'The product has been saved successfully.' => 'Prekė sėkmingai išsaugota.',

		// Vendor setup wizard
		'Vendor &rsaquo; Setup Wizard'   => 'Pardavėjas › Sąranka',
		'Welcome to the Marketplace!'    => 'Sveiki atvykę į prekyvietę!',
		'Introduction'                   => 'Įvadas',
		'Store Setup'                    => 'Parduotuvės sąranka',
		'Payment Setup'                  => 'Mokėjimų sąranka',
		'Payment'                        => 'Mokėjimas',
		'Store'                          => 'Parduotuvė',
		'Map'                            => 'Žemėlapis',
		'Ready!'                         => 'Paruošta!',
		'Your Store is Ready!'           => 'Jūsų parduotuvė paruošta!',
		'Go to your Store Dashboard!'    => 'Eiti į parduotuvės skydelį!',
		'Return to the Marketplace'      => 'Grįžti į prekyvietę',
		'Continue'                       => 'Tęsti',
		'Not right now'                  => 'Ne dabar',
		'Skip this step'                 => 'Praleisti šį žingsnį',
		'This is required'               => 'Šis laukas privalomas',
		'Select an option&hellip;'       => 'Pasirinkite…',
		'City'                           => 'Miestas',
		'Country'                        => 'Šalis',
		'State'                          => 'Apskritis',
		'State Name'                     => 'Apskrities pavadinimas',
		'Street'                         => 'Gatvė',
		'Street 2'                       => 'Gatvė 2',
		'Post/Zip Code'                  => 'Pašto kodas',
		'Email'                          => 'El. paštas',
		'Show email address in store'    => 'Rodyti el. pašto adresą parduotuvėje',
		'No time right now? If you don’t want to go through the wizard, you can skip and return to the Store!' => 'Neturite laiko dabar? Jei nenorite eiti per sąrankos vediklį, galite praleisti ir grįžti į parduotuvę!',

		// Store listing (pardavėjų puslapis)
		'Total store showing: %s'        => 'Iš viso parduotuvių: %s',
		'Total stores showing: %s'       => 'Iš viso parduotuvių: %s',
		'Search Vendors'                 => 'Ieškoti pardavėjų',
		// Užsakymų filtrai skydelyje
		'All'                            => 'Visi',
	);
	return $map;
}

function dk_dokan_lt_translate( $translation, $text, $domain ) {
	if ( 'dokan-lite' !== $domain ) {
		return $translation;
	}
	$map = dk_dokan_lt_map();
	return $map[ $text ] ?? $translation;
}

add_filter( 'gettext', 'dk_dokan_lt_translate', 20, 3 );

add_filter( 'gettext_with_context', function ( $translation, $text, $context, $domain ) {
	return dk_dokan_lt_translate( $translation, $text, $domain );
}, 20, 4 );
