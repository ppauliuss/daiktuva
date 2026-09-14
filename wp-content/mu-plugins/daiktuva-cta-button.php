<?php
/**
 * Plugin Name: Daiktuva – CTA mygtuku stiliai
 * Description: Oranzinis CTA stilius: meniu punktas "Ideti skelbima" ir hero mygtukas (.dk-btn-primary).
 */
add_action('wp_head', function () {
    ?>
<style id="dk-cta-css">
/* CTA meniu punkte (primary + mobile). Astra stato .menu-link{height:100%} —
   kartu su padding'u tas isplitdavo mygtuka virs header'io, todel height:auto. */
.site-header a[href*="/dashboard/new-product"],
.site-header a[href*="/vendor-onboarding"],
#astra-mobile-menu a[href*="/vendor-onboarding"],
#astra-mobile-menu a[href*="/dashboard/new-product"] {
  background: #ea7a1f !important; color: #fff !important; border-radius: 8px;
  padding: 8px 16px !important; font-weight: 700; line-height: 1.2;
  height: auto !important; display: inline-flex !important; align-items: center;
}
.site-header a[href*="/dashboard/new-product"]:hover,
.site-header a[href*="/vendor-onboarding"]:hover,
#astra-mobile-menu a[href*="/vendor-onboarding"]:hover,
#astra-mobile-menu a[href*="/dashboard/new-product"]:hover { background: #cf6a15; }
/* CTA hero bloke (.dk-hero p taisyklė turi didesnį specificity — nurodome pilną kelią) */
.dk-hero .dk-hero-cta { margin: 16px 0 0; }
a.dk-btn.dk-btn-primary {
  display: inline-block; background: #ea7a1f; color: #fff !important;
  padding: 14px 28px; border-radius: 10px; font-size: 18px; font-weight: 700;
  text-decoration: none !important; box-shadow: 0 2px 6px rgba(0,0,0,.18);
}
a.dk-btn.dk-btn-primary:hover { background: #cf6a15; }
/* Tuscios skilties CTA (archive sablonai) */
.dk-empty-cta { background: #fff7ed; border: 1px solid #fed7aa; border-radius: 12px; padding: 18px 20px; margin: 14px 0 20px; }
.dk-empty-cta strong { font-size: 18px; }
.dk-empty-cta p { margin: 6px 0 12px; color: #555; }
/* Pardavejo kortele skelbime */
.dk-seller-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 16px; display: inline-block; min-width: 240px; margin-top: 6px; }
.dk-seller-card__name { font-weight: 700; font-size: 16px; text-decoration: none !important; }
.dk-seller-card__meta { color: #64748b; font-size: 13px; margin: 4px 0 6px; }
.dk-seller-card__link { font-size: 14px; }
/* Kategoriju ikonos */
.dk-cats a { display: flex; align-items: center; gap: 8px; }
.dk-cats .dk-ic { width: 17px; height: 17px; flex: 0 0 17px; color: #64748b; }
.dk-cats a:hover .dk-ic { color: #ea7a1f; }
.dk-cats .dk-cat-name { flex: 1 1 auto; }
/* Filtru juosta */
.dk-filters { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin: 10px 0 16px; }
.dk-filters input[type="number"] { width: 110px; padding: 8px 10px; border: 1px solid #e2e8f0; border-radius: 8px; }
.dk-filters select { padding: 8px 10px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; max-width: 180px; }
.dk-filters button { background: #1e3a5f; color: #fff; border: 0; border-radius: 8px; padding: 9px 16px; cursor: pointer; }
.dk-filters button:hover { background: #152c48; }
.dk-filters-reset { font-size: 13px; color: #64748b; }
@media (max-width: 640px) { .dk-filters input[type="number"] { width: calc(50% - 60px); min-width: 100px; } }
</style>
    <?php
}, 20);
