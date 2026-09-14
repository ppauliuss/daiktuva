<?php
/**
 * Daiktuva: skelbimo įkėlimo kelionės UX (2026-08-14).
 *
 * Rasta per pilną Puppeteer kelionę mobiliuoju (guest -> registracija -> dashboard -> forma -> submit):
 *   1) naujas pardavėjas matydavo TUŠČIĄ dashboard'ą be krypties -> sveikinimo kortelė su CTA;
 *   2) formos validacijos klaidos pasimeta -> kliento pusės validacija su LT pranešimais prie laukų;
 *   3) nebuvo "privaloma" žymenų ir placeholder'ių -> pridėta;
 *   4) kategorija default'u būdavo "Kita" -> priverstinas sąmoningas pasirinkimas;
 *   5) po submit neaišku kas įvyko -> sėkmės juosta su "kas toliau" (moderacija/el. pašto patvirtinimas);
 *   6) telefono laukas tuščias nors vartotojas jį įvedė registruodamasis -> autofill;
 *   7) registracijos forma: paslėpti "Parduotuvės pavadinimas/URL" (užpildomi automatiškai);
 *   8) GA4 funnel: listing_form_view/start/submit/created.
 *
 * 2026-08-18 (pastabos po pirmo realaus pardavėjo kelionės):
 *   9) po registracijos vartotojas ~3,5 min klaidžiojo po /, /my-account, /vendor-onboarding,
 *      kol rado formą -> pardavėją po registracijos nukreipiame tiesiai į /dashboard/new-product/
 *      (perrašo Dokan setup wizard / dashboard redirect'ą);
 *  10) mobiliajame Woo tab'ai susiklodavo kaip eilutės antraštės, o aprašymas atrodydavo
 *      esąs po "Daugiau pardavėjo prekių" -> tab'ai lieka vienoje eilutėje, o tuščias
 *      "Daugiau pardavėjo prekių" tab'as paslėpiamas, kai pardavėjas turi tik 1 skelbimą.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Ar esame Dokan skydelio puslapyje. */
function dk_lux_is_dashboard(): bool {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
	return strpos( $uri, '/dashboard' ) !== false && is_user_logged_in();
}

/* -------- 1) Tuščio dashboard pataisa: sveikinimas + CTA pirmajam skelbimui. -------- */
add_action( 'dokan_dashboard_content_inside_before', static function () {
	$uid = get_current_user_id();
	if ( ! $uid || ( function_exists( 'dokan_is_user_seller' ) && ! dokan_is_user_seller( $uid ) ) ) {
		return;
	}
	if ( (int) count_user_posts( $uid, 'product' ) > 0 ) {
		return;
	}
	$url = function_exists( 'dokan_get_navigation_url' ) ? dokan_get_navigation_url( 'new-product' ) : '/dashboard/new-product/';
	echo '<div class="dk-welcome" style="background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:20px;margin:4px 0 18px">'
		. '<h2 style="margin:0 0 6px;font-size:20px">Sveiki! Pradėkite nuo pirmo skelbimo</h2>'
		. '<p style="margin:0 0 12px;color:#555">Tai užtruks ~1 minutę: nuotrauka, pavadinimas, kaina — ir skelbimas jau keliauja pas pirkėjus.</p>'
		. '<a href="' . esc_url( $url ) . '" style="display:inline-block;background:#ea580c;color:#fff;font-weight:700;padding:12px 22px;border-radius:8px;text-decoration:none">Įkelti pirmą skelbimą — nemokamai</a>'
		. '</div>';
} );

/* -------- 1b) Skydelio suvestinė pardavėjui SU skelbimais (2026-09-03). --------
 * Dokan Lite /dashboard/ widget'ų stulpeliai (dokan-dash-left/right) lieka TUŠTI —
 * dideli balti plotai atrodė kaip nebaigtas puslapis. Rodome kompaktišką suvestinę:
 * aktyvūs skelbimai / limitas + greiti mygtukai. Pardavėjui be skelbimų viršuje
 * lieka pirmojo skelbimo sveikinimas (1). */
add_action( 'dokan_dashboard_content_inside_before', static function () {
	$uid = get_current_user_id();
	if ( ! $uid || ( function_exists( 'dokan_is_user_seller' ) && ! dokan_is_user_seller( $uid ) ) ) {
		return;
	}
	$active = function_exists( 'dk_ll_active_count' ) ? dk_ll_active_count( $uid ) : (int) count_user_posts( $uid, 'product' );
	if ( $active < 1 ) {
		return; // be skelbimų — rodomas sveikinimas (1)
	}
	$limit    = function_exists( 'dk_ll_limit' ) ? dk_ll_limit( $uid ) : 10;
	$url_new  = function_exists( 'dokan_get_navigation_url' ) ? dokan_get_navigation_url( 'new-product' ) : '/dashboard/new-product/';
	$url_list = function_exists( 'dokan_get_navigation_url' ) ? dokan_get_navigation_url( 'products' ) : '/dashboard/products/';
	echo '<div class="dk-dash-summary" style="background:#f0f7ff;border:1px solid #bfdbfe;border-radius:12px;padding:20px;margin:4px 0 18px">'
		. '<h2 style="margin:0 0 6px;font-size:20px">Mano skelbimai</h2>'
		. '<p style="margin:0 0 12px;color:#475569">Aktyvūs skelbimai: <strong>' . (int) $active . '</strong> iš ' . (int) $limit . '</p>'
		. '<a href="' . esc_url( $url_new ) . '" style="display:inline-block;background:#ea580c;color:#fff;font-weight:700;padding:11px 20px;border-radius:8px;text-decoration:none;margin:0 8px 8px 0">+ Įkelti skelbimą</a>'
		. '<a href="' . esc_url( $url_list ) . '" style="display:inline-block;background:#1e3a5f;color:#fff;font-weight:700;padding:11px 20px;border-radius:8px;text-decoration:none;margin:0 0 8px">Mano skelbimai</a>'
		. '</div>';
}, 6 );

/* -------- 5) Sėkmės juosta po skelbimo pateikimo. --------
 * Kabinama ant DVIJŲ hook'ų: `dokan_dashboard_content_inside_before` (pagr. skydelis)
 * IR `dokan_before_new_product_inside_content_area` — Dokan po insert redirect'ina į
 * new-product?created_product=N, kur pirmasis hook'as NESHOTEINA (jis tik
 * templates/dashboard/new-dashboard.php). */
function dk_lux_created_banner() {
	if ( empty( $_GET['created_product'] ) ) {
		return;
	}
	// Nuo 2026-08-21 publikavimo sąlyga — patvirtintas el. paštas; moderaciją iššaukia
	// >5 aktyvūs arba flag'ai. Juosta rodo realų statusą (el. pašto hold'ui — kita žinutė).
	$pid        = absint( $_GET['created_product'] );
	$pending    = $pid && 'pending' === get_post_status( $pid );
	$email_hold = $pending && get_post_meta( $pid, '_dk_hold_email_verify', true );
	if ( $email_hold ) {
		$resend = esc_url( add_query_arg( 'dk_resend_verify', '1', wc_get_page_permalink( 'myaccount' ) ) );
		echo '<div class="dk-created" style="background:#fff7ed;border:1px solid #fdba74;border-radius:12px;padding:18px 20px;margin:4px 0 18px">'
			. '<h2 style="margin:0 0 6px;font-size:19px;color:#9a3412">Skelbimas gautas — liko vienas žingsnis!</h2>'
			. '<p style="margin:0;color:#9a3412">Patvirtinkite savo el. paštą — išsiuntėme laišką su nuoroda (patikrinkite ir šlamšto/„Spam" aplanką). '
			. 'Kai patvirtinsite, skelbimas bus paskelbtas iš karto. <a href="' . $resend . '" style="color:#9a3412;font-weight:700">Siųsti laišką dar kartą</a></p></div>';
		return;
	}
	echo '<div class="dk-created" style="background:#ecfdf5;border:1px solid #6ee7b7;border-radius:12px;padding:18px 20px;margin:4px 0 18px">'
		. '<h2 style="margin:0 0 6px;font-size:19px;color:#065f46">' . ( $pending ? 'Skelbimas gautas!' : 'Skelbimas paskelbtas!' ) . '</h2>'
		. '<p style="margin:0;color:#065f46">'
		. ( $pending
			? 'Jis bus paskelbtas po trumpo administratoriaus patikrinimo.'
			: 'Jis jau matomas portale. Sėkmės pardavinėjant!' )
		. '</p></div>';
}
add_action( 'dokan_dashboard_content_inside_before', 'dk_lux_created_banner' );
add_action( 'dokan_before_new_product_inside_content_area', 'dk_lux_created_banner' );

/* -------- 7) Aprašymo redaktorius: paprastas textarea (be TinyMCE). --------
 * 2026-08-20 (#14): visi skelbimai rašomi mūsų nustatytu šriftu/dydžiu, todėl
 * formatavimo įrankių juosta bereikalinga — pardavėjui paliekamas paprastas
 * teksto laukas (tinymce=false => wp_editor atiduoda gryną <textarea>).
 * Grąžinti TinyMCE = atkurti buvusį bloką iš git istorijos. */
add_filter( 'wp_editor_settings', static function ( $settings ) {
	if ( ! dk_lux_is_dashboard() ) {
		return $settings;
	}
	$settings['tinymce']       = false;
	$settings['quicktags']     = false;
	$settings['media_buttons'] = false;
	$settings['textarea_rows'] = 8;
	return $settings;
} );

/* -------- 2,3,4,6,8) Formos JS: placeholderiai, * ženklai, kategorija, validacija, tel autofill, GA4. -------- */
add_action( 'wp_footer', static function () {
	if ( ! dk_lux_is_dashboard() ) {
		return;
	}
	$is_form     = strpos( (string) $_SERVER['REQUEST_URI'], 'new-product' ) !== false || strpos( (string) $_SERVER['REQUEST_URI'], 'edit' ) !== false;
	$is_new      = strpos( (string) $_SERVER['REQUEST_URI'], 'new-product' ) !== false;
	$phone       = '';
	$s           = get_user_meta( get_current_user_id(), 'dokan_profile_settings', true );
	if ( is_array( $s ) && ! empty( $s['phone'] ) ) {
		$phone = $s['phone'];
	}
	$cfg = array(
		'isForm'        => $is_form,
		'isNew'         => $is_new,
		'phone'         => $phone,
		'created'       => ! empty( $_GET['created_product'] ),
		'isFirst'       => $is_new && 0 === (int) count_user_posts( get_current_user_id(), 'product' ),
	);
	?>
<script>
(function () {
	'use strict';
	var CFG = <?php echo wp_json_encode( $cfg ); ?>;

	function gtagEvent(name, params) {
		if (typeof window.gtag === 'function') { window.gtag('event', name, params || {}); }
	}

	/* ---- GA4 funnel ---- */
	if (CFG.isForm) {
		gtagEvent('listing_form_view');
		var started = false;
		document.addEventListener('input', function h(e) {
			if (!started && e.target.closest && e.target.closest('form.dokan-form-container')) {
				started = true; gtagEvent('listing_form_start');
				if (CFG.isFirst) { gtagEvent('first_listing_started'); }
			}
		}, true);
	}
	if (CFG.created) { gtagEvent('listing_created'); }

	if (!CFG.isForm) { return; }

	function ready(fn) {
		if (document.readyState !== 'loading') { fn(); } else { document.addEventListener('DOMContentLoaded', fn); }
	}

	ready(function () {
		var form = document.querySelector('form.dokan-form-container');
		if (!form) { return; }

		/* ---- Placeholderiai (3) ---- */
		var title = form.querySelector('input[name=post_title]');
		if (title && !title.placeholder) { title.placeholder = 'Ką parduodate? Pvz.: gręžtuvas Bosch GSB 13'; }
		var price = form.querySelector('input[name=_regular_price]');
		if (price) { price.placeholder = '150'; }

		/* ---- * ženklai privalomiems (2) ---- */
		function markRequired(input) {
			if (!input) { return; }
			var g = input.closest('.dokan-form-group');
			var l = g ? g.querySelector('label') : null;
			if (l && !l.querySelector('.dk-req')) {
				var s = document.createElement('span');
				s.className = 'dk-req'; s.textContent = ' *';
				s.style.color = '#dc2626'; s.style.fontWeight = '700';
				l.appendChild(s);
			}
		}
		markRequired(price);
		markRequired(form.querySelector('[name=post_content]'));
		var catLabel = form.querySelector('label[for=chosen_product_cat]');
		if (catLabel && !catLabel.querySelector('.dk-req')) {
			var rs = document.createElement('span');
			rs.className = 'dk-req'; rs.textContent = ' *';
			rs.style.color = '#dc2626'; rs.style.fontWeight = '700';
			catLabel.appendChild(rs);
		}
		// pavadinimas/neturi label — dedam ženklą ant photo bloko ir title input apvado
		var featBtn = document.querySelector('.dokan-feat-image-btn, .instruction-inside');
		if (featBtn && !document.querySelector('.dk-req-photo')) {
			var sp = document.createElement('div');
			sp.className = 'dk-req-photo';
			sp.style.cssText = 'color:#dc2626;font-size:12px;font-weight:600;margin-top:4px';
			sp.textContent = '* privaloma — bent viena nuotrauka';
			featBtn.parentNode.appendChild(sp);
		}

		/* ---- Kategorija: priverstinas pasirinkimas (4). Dokan 5 modalui:
		 * hidden input'ai chosen_product_cat[] + span#dokan_product_cat_res.
		 * Naujam skelbimui Dokan pats užpildo "Kita" (term 31) — išvalome,
		 * kad vartotojas PRIVALETŲ pasirinkti sąmoningai. Edit puslapyje neliečiame. ---- */
		var catRes = document.getElementById('dokan_product_cat_res');
		if (CFG.isNew) {
			form.querySelectorAll('input[name="chosen_product_cat[]"]').forEach(function (h) { h.remove(); });
			if (catRes) { catRes.textContent = 'Pasirinkite kategoriją…'; }
		}

		/* ---- Telefono autofill (6) ---- */
		var phoneInput = form.querySelector('input[name=_daiktuva_phone]');
		if (phoneInput && !phoneInput.value && CFG.phone) { phoneInput.value = CFG.phone; }

		/* ---- Validacija su LT pranešimais (2) ---- */
		function errBox() {
			var b = document.getElementById('dk-form-errors');
			if (!b) {
				b = document.createElement('div');
				b.id = 'dk-form-errors';
				b.style.cssText = 'display:none;background:#fef2f2;border:1px solid #fca5a5;color:#991b1b;border-radius:10px;padding:12px 16px;margin:12px 0;font-size:14px';
				form.insertBefore(b, form.firstChild);
			}
			return b;
		}
		form.addEventListener('submit', function (e) {
			var errs = [];
			var feat = form.querySelector('input.dokan-feat-image-id');
			if (!feat || !feat.value || feat.value === '0') { errs.push('Pridėkite bent vieną nuotrauką.'); }
			// Serverio limitas dk_pr_limits()['photos']=5 — gaudome ČIA, kad vartotojas
			// neprarastų visų laukų (serverio klaida = Dokan forma perrenderinama tuščia).
			var photoCount = document.querySelectorAll('#product_images_container ul.product_images li.image').length;
			if (photoCount > 5) {
				errs.push('Galima įkelti ne daugiau kaip 5 nuotraukas viename skelbime (dabar: ' + photoCount + '). Pašalinkite perteklines.');
			}
			if (!title || !title.value.trim()) { errs.push('Įveskite skelbimo pavadinimą.'); }
			var catOk = false;
			form.querySelectorAll('input[name="chosen_product_cat[]"]').forEach(function (h) { if (h.value) { catOk = true; } });
			if (!catOk && form.querySelector('select[name=product_cat]')) {
				var sv = form.querySelector('select[name=product_cat]').value;
				if (sv && sv !== '-1') { catOk = true; }
			}
			if (!catOk) { errs.push('Pasirinkite kategoriją.'); }
			if (price && price.value.trim() !== '' && isNaN(parseFloat(price.value.replace(',', '.')))) { errs.push('Kaina turi būti skaičius (pvz.: 150).'); }
			var b = errBox();
			if (errs.length) {
				e.preventDefault(); e.stopPropagation();
				b.innerHTML = '<strong>Skelbimas dar neišsaugotas:</strong><ul style="margin:6px 0 0;padding-left:18px"><li>' + errs.join('</li><li>') + '</li></ul>';
				b.style.display = 'block';
				b.scrollIntoView({ behavior: 'smooth', block: 'center' });
				gtagEvent('listing_validation_failed', { count: errs.length });
			} else {
				b.style.display = 'none';
				gtagEvent('listing_submit');
			}
		}, true);
	});
})();
</script>
	<?php
}, 30 );

/* -------- 9) Po registracijos — tiesiai į skelbimo formą. --------
 * Dokan default: dashboard arba setup wizard (?page=dokan-seller-setup); mūsų
 * onboarding'as ir taip automatinis (7), tad vedlys — bereikalinga trintis. */
add_filter( 'woocommerce_registration_redirect', static function ( $url ) {
	$user = wp_get_current_user();
	if ( $user && in_array( 'seller', (array) $user->roles, true ) ) {
		$form_url = function_exists( 'dokan_get_navigation_url' ) ? dokan_get_navigation_url( 'new-product' ) : '';
		return $form_url ? $form_url : home_url( '/dashboard/new-product/' );
	}
	return $url;
}, 99 );

/* -------- 10a) Skelbimo puslapis: dubliuojančio Dokan pardavėjo prekių tab'o nerodyti. --------
 * Pardavėjo kortelė veda į visą parduotuvę, o apačioje jau turime lengvesnį
 * susijusių skelbimų bloką. Dokan tab'as dubliavo turinį ir krovė paslėptas nuotraukas. */
add_filter( 'woocommerce_product_tabs', static function ( $tabs ) {
	if ( isset( $tabs['more_seller_product'] ) ) {
		unset( $tabs['more_seller_product'] );
	}
	return $tabs;
}, 99 );

/* -------- 10b) Skelbimo puslapis: mobiliajame tab'ai vienoje eilutėje (ne antraštės stulpelyje). -------- */
add_action( 'wp_head', static function () {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	echo '<style>@media (max-width:768px){'
		. '.single-product .woocommerce-tabs ul.tabs{display:flex;flex-wrap:wrap;gap:6px;padding:0}'
		. '.single-product .woocommerce-tabs ul.tabs li{display:inline-block;width:auto;float:none;margin:0;padding:0}'
		. '.single-product .woocommerce-tabs ul.tabs li a{display:inline-block;padding:8px 14px}'
		. '}</style>';
} );

/* -------- 7) Registracija: paslėpti parduotuvės laukus, užpildyti automatiškai. -------- */add_action( 'wp_footer', static function () {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
	if ( is_user_logged_in() || ( strpos( $uri, 'vendor-onboarding' ) === false && strpos( $uri, 'my-account' ) === false ) ) {
		return;
	}
	?>
<script>
(function () {
	'use strict';
	function hideRow(input) {
		if (!input) { return; }
		var row = input.closest('.form-row, .dokan-form-group, p') || input.parentNode;
		if (row) { row.style.display = 'none'; }
		input.removeAttribute('required');
	}
	var shop = document.getElementById('company-name');
	var url = document.getElementById('seller-url');
	if (!shop && !url) { return; }
	hideRow(shop); hideRow(url);
	function autofill() {
		// 2026-08-21: parduotuvės pavadinimas NE vienodas vardui/login'ui (privatumas) —
		// naujas pardavėjas viešai = "Privatus pardavėjas"; verslą galės įvesti pats.
		if (shop && !shop.value) { shop.value = 'Privatus pardavėjas'; }
		if (url && !url.value) {
			url.value = 'pardavejas-' + Math.random().toString(36).slice(2, 10);
		}
	}
	['first-name', 'last-name', 'reg_email'].forEach(function (id) {
		var el = document.getElementById(id);
		if (el) { el.addEventListener('input', autofill); }
	});
	document.addEventListener('submit', autofill, true);
})();
</script>
	<?php
}, 30 );

/* -------- 11) 2026-08-20: nuimamas Dokan angliškas pranešimas savo skelbime --------
 * Dokan (Product/Hooks.php own_product_not_purchasable_notice) autorui savo skelbimo
 * puslapyje spausdindavo: 'As this is your own product, the "Add to Cart" button has
 * been removed...' — beprasmis (catalog-mode vis tiek visiems nuima krepšelį) ir angliškas.
 * Vietoj jo — LT kortelė su aiškiu "Redaguoti skelbimą" įėjimu (12). */
add_action( 'wp', static function () {
	if ( ! function_exists( 'dokan_get_container' ) ) {
		return;
	}
	try {
		$hooks = dokan_get_container()->get( \WeDevs\Dokan\Product\Hooks::class );
		if ( $hooks ) {
			remove_action( 'woocommerce_before_single_product', array( $hooks, 'own_product_not_purchasable_notice' ) );
		}
	} catch ( \Throwable $e ) { // container nepasiekiamas — nieko baisaus, pranešimas liktų
	}
}, 1 );

/* -------- 12) Savo skelbimo puslapyje: "Tai jūsų skelbimas" + Redaguoti mygtukas. -------- */
add_action( 'woocommerce_before_single_product', static function () {
	if ( ! is_user_logged_in() ) {
		return;
	}
	global $post;
	if ( ! $post || 'product' !== $post->post_type ) {
		return;
	}
	if ( (int) $post->post_author !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$edit = function_exists( 'dokan_edit_product_url' ) ? dokan_edit_product_url( $post->ID ) : '';
	if ( ! $edit ) {
		$edit = home_url( '/dashboard/products/' );
	}
	echo '<div class="dk-own-product">'
		. '<span class="dk-own-product__label">Tai jūsų skelbimas</span>'
		. '<span class="dk-own-product__actions">'
		. '<a class="dk-own-product__edit" href="' . esc_url( $edit ) . '">Redaguoti skelbimą</a>'
		. '<a class="dk-own-product__list" href="' . esc_url( home_url( '/dashboard/products/' ) ) . '">Mano skelbimai</a>'
		. '</span></div>';
}, 5 );

add_action( 'wp_head', static function () {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	echo '<style id="dk-own-product">'
		. '.dk-own-product{display:flex;flex-wrap:wrap;align-items:center;gap:10px;background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:10px 14px;margin:0 0 14px}'
		. '.dk-own-product__label{font-weight:700;color:#9a3412}'
		. '.dk-own-product__actions{display:flex;gap:8px;margin-left:auto;flex-wrap:wrap}'
		. '.dk-own-product__edit{display:inline-block;background:#0F4C81;color:#fff !important;font-weight:700;padding:9px 16px;border-radius:8px;text-decoration:none !important}'
		. '.dk-own-product__edit:hover{background:#0d3f6b}'
		. '.dk-own-product__list{display:inline-block;color:#0F4C81 !important;border:1px solid #cfd8e3;font-weight:600;padding:8px 14px;border-radius:8px;text-decoration:none !important}'
		. '@media(max-width:640px){.dk-own-product__actions{margin-left:0;width:100%}.dk-own-product__edit{flex:1;text-align:center}}'
		. '</style>';
}, 21 );

/* -------- 13) 2026-08-20: lipni "Išsaugoti pakeitimus" juosta redagavimo formoje. --------
 * Pardavėjas: pakeitus foto (ar kitą lauką) tikras submit mygtukas yra pačioje
 * apačioje — reikia skrolinti. Juosta atsiranda TIK po pirmo pakeitimo (dirty):
 * input/change formoje, foto juostos veiksmai (dk:photos-dirty iš dk-multi-photo.js),
 * DOM pokyčiai formoje (pvz. kategorijos modalas). Paspaudus — kviečiamas tikras
 * Dokan submit (#publish). */
add_action( 'wp_footer', static function () {
	if ( ! dk_lux_is_dashboard() ) {
		return;
	}
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
	if ( strpos( $uri, 'action=edit' ) === false ) {
		return;
	}
	?>
<style id="dk-sticky-save-css">
#dk-sticky-save{position:fixed;left:0;right:0;bottom:0;z-index:99998;background:#fff;border-top:1px solid #e2e8f0;
  box-shadow:0 -8px 24px rgba(20,23,33,.14);padding:10px 14px calc(10px + env(safe-area-inset-bottom));}
#dk-sticky-save[hidden]{display:none;}
#dk-sticky-save-btn{display:block;width:100%;max-width:420px;margin:0 auto;padding:13px 20px;border:0;border-radius:10px;
  background:#ea7a1f;color:#fff;font-size:16px;font-weight:800;cursor:pointer;text-align:center}
#dk-sticky-save-btn:hover{background:#cf6a15}
#dk-sticky-save-btn:disabled{background:#94a3b8;cursor:default}
body.dk-has-sticky-save{padding-bottom:78px !important}
@media(min-width:769px){
  #dk-sticky-save{left:auto;right:24px;bottom:24px;border:1px solid #e2e8f0;border-radius:14px;padding:10px}
  #dk-sticky-save-btn{width:auto;padding:12px 26px}
  body.dk-has-sticky-save{padding-bottom:0 !important}
}
</style>
<script>
(function () {
	'use strict';
	function ready(fn) {
		if (document.readyState !== 'loading') { fn(); } else { document.addEventListener('DOMContentLoaded', fn); }
	}
	ready(function () {
		var real = document.getElementById('publish'); // Dokan edit submit (dokan_update_product)
		if (!real) { return; }
		var form = real.form || real.closest('form');
		if (!form) { return; }

		var bar = document.createElement('div');
		bar.id = 'dk-sticky-save';
		bar.hidden = true;
		var btn = document.createElement('button');
		btn.type = 'button';
		btn.id = 'dk-sticky-save-btn';
		btn.textContent = 'Išsaugoti pakeitimus';
		bar.appendChild(btn);
		document.body.appendChild(bar);

		var shown = false;
		function show() {
			if (shown) { return; }
			shown = true;
			bar.hidden = false;
			document.body.classList.add('dk-has-sticky-save');
		}

		form.addEventListener('input', show, true);
		form.addEventListener('change', show, true);
		document.addEventListener('dk:photos-dirty', show);

		// DOM pokyčiai formoje (kategorijos modalas, foto juosta) — po pradinio įsikrovimo,
		// ignoruojant mūsų pačių dekoracijas (dk-* klasės).
		setTimeout(function () {
			new MutationObserver(function (muts) {
				for (var i = 0; i < muts.length; i++) {
					var m = muts[i];
					var nodes = Array.prototype.slice.call(m.addedNodes).concat(Array.prototype.slice.call(m.removedNodes));
					for (var j = 0; j < nodes.length; j++) {
						var n = nodes[j];
						if (n.nodeType !== 1) { continue; }
						var cls = n.className && n.className.baseVal !== undefined ? '' : (n.className || '');
						if (typeof cls === 'string' && cls.indexOf('dk-') !== -1) { continue; }
						show();
						return;
					}
				}
			}).observe(form, { childList: true, subtree: true });
		}, 2500);

		btn.addEventListener('click', function () {
			btn.disabled = true;
			btn.textContent = 'Saugoma…';
			real.click();
		});
	});
})();
</script>
	<?php
}, 31 );

/* -------- 14) 2026-08-20: atsargų skydeliai ir sėkmės juosta. --------
 * a) "Atsargos" skydelis (SKU, atsargų būsena, kiekis) kol kas neaktyvus —
 *    Dokan turi tam paruoštą filtrą; KAI turėsime aktyvių pardavėjų —
 *    tiesiog ištrinti šį filtrą ir skydelis grįš.
 * b) po išsaugojimo Dokan rodydavo juostą "Pavyko! Prekė sėkmingai išsaugota.
 *    View product" — nereikalinga (lipni juosta pati dingsta, o klaidų juosta
 *    .dokan-alert-danger lieka — ji svarbi).
 * c) "Kitos parinktys" skydelyje — Purchase Note laukas (su EN paaiškinimu)
 *    paslėptas; grįš kartu su atsargų skydeliu, kai bus aktyvių pardavėjų.
 *    Skydelis pats LIEKA (jame prekės būsena / matomumas / atsiliepimai). */
add_filter( 'dokan_hide_inventory_template', '__return_true' );

add_action( 'wp_head', static function () {
	if ( ! dk_lux_is_dashboard() ) {
		return;
	}
	echo '<style id="dk-lux-edit-cleanup">'
		. '.product-edit-container > .dokan-message{display:none}'
		. '.dokan-other-options .dokan-form-group:has([name=_purchase_note]){display:none}'
		. '</style>';
}, 22 );

/* -------- 15) 2026-08-20: "Mano skelbimai" — pirmiausia vėliausiai redaguoti. --------
 * Dokan default rikiuoja pagal sukūrimo datą (post_date DESC); pardavėjui
 * svarbiau matyti, ką neseniai redagavo/atnaujino. Filtras veikia TIK
 * pardavėjo skydelio sąrašą (viešo katalogo rikiavimo neliečia). */
add_filter( 'dokan_product_listing_arg', static function ( $args ) {
	$args['orderby'] = 'modified';
	$args['order']   = 'DESC';
	return $args;
} );
