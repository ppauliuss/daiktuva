<?php
/**
 * Daiktuva UX patobulinimai: prieinamumas, mobilus katalogas, našumas,
 * vieninga paskyros kelionė ir aiškesnės pardavėjų kortelės (2026-09-04).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* Produkto nuotrauka lieka atidaroma lightbox'e, bet nekraunamas pilnas failas vien zoom efektui. */
add_action( 'after_setup_theme', static function () {
	remove_theme_support( 'wc-product-gallery-zoom' );
}, 100 );

/* /my-account/ yra prisijungimo vieta. Pardavėjo registracija turi vieną aiškų kelią. */
add_filter( 'option_woocommerce_enable_myaccount_registration', static function ( $value ) {
	return function_exists( 'is_account_page' ) && is_account_page() && ! is_user_logged_in() ? 'no' : $value;
}, 99 );

add_action( 'woocommerce_after_customer_login_form', static function () {
	if ( is_user_logged_in() ) { return; }
	echo '<section class="dk-account-seller" aria-labelledby="dk-account-seller-title">'
		. '<h2 id="dk-account-seller-title">Norite parduoti?</h2>'
		. '<p>Pardavėjo paskyrą susikursite atskiroje trumpoje formoje. Pirmą skelbimą įkelti nemokama.</p>'
		. '<a class="button" href="' . esc_url( home_url( '/vendor-onboarding/' ) ) . '">Sukurti pardavėjo paskyrą</a>'
		. '</section>';
} );

/* Trumpesnis, lengviau peržvelgiamas Patarimų archyvas. */
add_action( 'pre_get_posts', static function ( $query ) {
	if ( ! is_admin() && $query->is_main_query() && $query->is_home() ) {
		$query->set( 'posts_per_page', 6 );
	}
}, 999 );

/* Tikslesni puslapių pavadinimai ir aprašymai paieškos rezultatams. */
function dk_ux_seo_data(): array {
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return array( 'title' => 'Skelbimai – Daiktuva', 'description' => 'Nauji ir naudoti daiktai iš pardavėjų visoje Lietuvoje. Raskite skelbimą pagal kainą, miestą ar kategoriją.' );
	}
	if ( is_page( array( 'store-listing', 'pardavejai' ) ) ) {
		return array( 'title' => 'Pardavėjai – Daiktuva', 'description' => 'Peržiūrėkite aktyvius Daiktuva pardavėjus, jų skelbimus, miestus ir parduodamų daiktų kategorijas.' );
	}
	if ( is_home() ) {
		return array( 'title' => 'Patarimai – Daiktuva', 'description' => 'Praktiški patarimai, kaip saugiai pirkti, parduoti, fotografuoti ir parengti aiškų daikto skelbimą.' );
	}
	return array();
}

add_filter( 'document_title_parts', static function ( $parts ) {
	$data = dk_ux_seo_data();
	if ( ! empty( $data['title'] ) ) {
		$parts['title'] = preg_replace( '/\s+[–-]\s+Daiktuva$/u', '', $data['title'] );
		$parts['site']  = 'Daiktuva';
	}
	return $parts;
}, 99 );
add_filter( 'rank_math/frontend/title', static function ( $title ) {
	$data = dk_ux_seo_data();
	return ! empty( $data['title'] ) ? $data['title'] : $title;
}, 99 );
add_filter( 'rank_math/frontend/description', static function ( $description ) {
	$data = dk_ux_seo_data();
	return ! empty( $data['description'] ) ? $data['description'] : $description;
}, 99 );
add_action( 'wp_head', static function () {
	if ( defined( 'RANK_MATH_VERSION' ) ) { return; }
	$data = dk_ux_seo_data();
	if ( ! empty( $data['description'] ) ) {
		echo '<meta name="description" content="' . esc_attr( $data['description'] ) . '">' . "\n";
	}
}, 2 );

add_action( 'wp_head', static function () {
	if ( is_admin() ) { return; }
	?>
<style id="dk-ux-phase3-css">
:where(a,button,input,select,textarea,summary):focus-visible{outline:3px solid #f59e0b!important;outline-offset:3px!important}
.dk-date,.dk-listing-meta{color:#64748b!important}
.dk-account-seller{max-width:560px;margin:28px auto 0;padding:22px;background:#f0f7ff;border:1px solid #bfdbfe;border-radius:14px;text-align:center}
.dk-account-seller h2{margin:0 0 7px;font-size:22px}.dk-account-seller p{margin:0 0 14px;color:#475569}
.dk-account-seller .button{min-height:46px;padding:12px 20px!important;background:#ea580c!important;color:#fff!important;border-radius:9px!important;font-weight:700}
.woocommerce-account:not(.logged-in) .woocommerce-form-login{max-width:560px;margin-left:auto!important;margin-right:auto!important}
.dk-store-card-meta{display:flex;flex-wrap:wrap;gap:4px 10px;margin-top:5px;color:#475569;font-size:13px;line-height:1.35}
.dk-store-card-meta strong{color:#1e3a5f}.dk-store-card-meta span+span:before{content:'•';margin-right:10px;color:#94a3b8}
#dokan-seller-listing-wrap .store-content .dk-store-card-meta{color:#e2e8f0!important;text-shadow:0 1px 2px rgba(0,0,0,.35)}
#dokan-seller-listing-wrap .store-content .dk-store-card-meta strong{color:#fff!important}
#dokan-seller-listing-wrap .store-content .dk-store-card-meta span+span:before{color:#cbd5e1}
#dokan-seller-listing-wrap .store-footer a{min-width:44px;min-height:44px;display:inline-flex!important;align-items:center;justify-content:center}
.dk-verified-seller{display:inline-flex;align-items:center;gap:4px;width:max-content;padding:3px 7px;border-radius:999px;background:#dcfce7;color:#166534;font-size:12px;font-weight:750;line-height:1.35}
#dokan-seller-listing-wrap .store-content .dk-verified-seller{background:rgba(220,252,231,.95);color:#14532d!important;text-shadow:none}
.dk-safety-box__notice{margin:8px 0 10px;padding:9px 11px;border-left:3px solid #ea7a1f;background:#fff7ed;color:#7c2d12;font-size:14px}
.dk-report-listing{display:inline-flex;align-items:center;min-height:44px;margin-top:4px;font-weight:700;color:#9a3412!important}
.dk-active-filters{display:flex;flex-wrap:wrap;gap:7px;margin:-7px 0 15px}
.dk-active-filters a{display:inline-flex;align-items:center;min-height:36px;padding:6px 10px;border-radius:999px;background:#eaf2fd;color:#1e3a5f!important;font-size:13px;font-weight:650;text-decoration:none!important}
.dk-active-filters a:hover{background:#dbeafe}.dk-active-filters a span{margin-left:5px;font-size:18px;line-height:1}
.dk-mobile-categories{border:0}.dk-mobile-categories>summary{display:none}
@media(max-width:880px){
  :where(.dk-search button,.dk-filters button,.dk-filters-reset,.dk-sort,.dk-page,.dk-cta a,.dk-mobile-sticky a,.dokan-store-list-filter-button){min-height:44px;display:inline-flex;align-items:center;justify-content:center}
  .dk-home{display:flex!important;flex-direction:column!important}.dk-sidebar{order:1!important;padding:0!important;border:0!important;background:transparent!important}.dk-main{order:2!important}
  .dk-mobile-categories{display:block;background:#fff;border:1px solid #d8e0e8;border-radius:12px;margin:0 0 14px;overflow:hidden}
  .dk-mobile-categories>summary{display:flex;min-height:48px;align-items:center;padding:0 15px;font-weight:750;color:#1e3a5f;cursor:pointer;list-style:none}
  .dk-mobile-categories>summary::-webkit-details-marker{display:none}.dk-mobile-categories>summary:after{content:'+';margin-left:auto;font-size:22px}.dk-mobile-categories[open]>summary:after{content:'−'}
  .dk-mobile-categories>.dk-mobile-categories-body{padding:0 8px 10px}
  .dk-main ul.products,.woocommerce ul.products{grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:10px!important}
  .dk-main ul.products li.product,.woocommerce ul.products li.product{min-width:0!important;margin:0!important;width:auto!important}
  .dk-main .astra-shop-summary-wrap,.woocommerce ul.products li.product .astra-shop-summary-wrap{padding:10px!important}
  .dk-main .woocommerce-loop-product__title,.woocommerce ul.products li.product .woocommerce-loop-product__title{font-size:14px!important;line-height:1.25!important;min-height:35px;margin-bottom:5px!important}
  .dk-main ul.products li.product .ast-woo-product-category,.dk-main ul.products li.product .dk-date,.dk-main ul.products li.product .dk-seller{display:none!important}
  .dk-main ul.products li.product .price{font-size:15px!important}.dk-main ul.products li.product .button{min-height:42px;padding:9px 8px!important;font-size:13px!important}
  body.home .dk-main ul.products li.product:nth-child(n+7){display:none!important}
  body.home .dk-pagination{display:none!important}
  #dokan-seller-listing-wrap .store-wrapper{min-height:0!important}
  .dk-contact-whatsapp,.single-product .woocommerce-tabs ul.tabs li a,.nsl-button,.navigation.pagination .page-numbers{min-height:44px!important;display:inline-flex!important;align-items:center;justify-content:center}
  .dk-active-filters a{min-height:44px}
}
@media(min-width:370px) and (max-width:680px){
  #dokan-seller-listing-wrap ul.dokan-seller-wrap{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin:0!important}
  #dokan-seller-listing-wrap ul.dokan-seller-wrap:before,#dokan-seller-listing-wrap ul.dokan-seller-wrap:after{display:none!important}
  #dokan-seller-listing-wrap li.dokan-single-seller{float:none!important;width:auto!important;margin:0!important;padding:0!important;min-width:0}
  #dokan-seller-listing-wrap .store-content .store-data h2{font-size:14px!important;line-height:1.2!important;margin-bottom:4px!important}
  #dokan-seller-listing-wrap .store-content .store-data h2 a{display:block;min-height:34px}
  #dokan-seller-listing-wrap .dk-store-card-meta{display:flex;flex-direction:column;gap:2px;font-size:11px;line-height:1.25}
  #dokan-seller-listing-wrap .dk-store-card-meta span+span:before{display:none}
  #dokan-seller-listing-wrap .dk-store-card-meta>span:nth-of-type(2){display:none}
  #dokan-seller-listing-wrap .seller-avatar img{width:48px!important;height:48px!important}
}
@media(max-width:420px){
  .dk-main ul.products,.woocommerce ul.products{gap:8px!important}
  .dk-main .astra-shop-summary-wrap,.woocommerce ul.products li.product .astra-shop-summary-wrap{padding:8px!important}
}
</style>
	<?php
}, 50 );

/* Mobiliajame kategorijos suskleidžiamos puslapio viršuje; papildomi ARIA saugikliai. */
add_action( 'wp_footer', static function () {
	if ( is_admin() ) { return; }
	?>
<script id="dk-ux-phase3-js">
(function(){
 'use strict';
 function init(){
   document.querySelectorAll('input[type="search"]').forEach(function(el){if(!el.getAttribute('aria-label'))el.setAttribute('aria-label','Ieškoti skelbimų');if(!el.getAttribute('autocomplete'))el.setAttribute('autocomplete','off');});
   document.querySelectorAll('#dokan-seller-listing-wrap .store-footer a').forEach(function(a){a.setAttribute('aria-label','Atidaryti pardavėjo skelbimus');a.setAttribute('title','Atidaryti pardavėjo skelbimus');});
   document.querySelectorAll('a[aria-label^="Read:"]').forEach(function(a){a.setAttribute('aria-label',a.getAttribute('aria-label').replace(/^Read:/,'Skaityti:'));});
   var side=document.querySelector('.dk-home .dk-sidebar');
   if(side&&!side.querySelector('.dk-mobile-categories')){
     var details=document.createElement('details');details.className='dk-mobile-categories';
     var summary=document.createElement('summary');summary.textContent='Kategorijos';
     var body=document.createElement('div');body.className='dk-mobile-categories-body';
     while(side.firstChild)body.appendChild(side.firstChild);
     details.appendChild(summary);details.appendChild(body);side.appendChild(details);
   }
 }
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
</script>
	<?php
}, 98 );
