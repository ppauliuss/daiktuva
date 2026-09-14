<?php
/**
 * Daiktuva: skelbimo formos supaprastinimas (2026-07-05).
 * Vartotoja pastebėjo, kad mobilioje „Pridėti prekę" formoje per daug laukų ir netvarkinga —
 * realus vartotojas (#56) nesugebėjo pateikti skelbimo. Supaprastiname iki esminių laukų:
 *   PALIEKAM: nuotrauka, pavadinimas, kaina, kategorija, būsena, telefonas, aprašymas.
 *   SLEPIAM: akcijos kaina, prekės ženklas, žymos, trumpas aprašymas.
 * 2026-08-19: trumpasis aprašymas (post_excerpt) paslėptas ir REDAGAVIMO formoje
 *   (.dokan-product-short-description — ji NĖRA po .dokan-product-meta, todėl ankstesnė
 *   taisyklė jos nepagaudavo) bei „add product" popup'e. Vartotojas mato vienintelį
 *   aprašymo lauką. Excerpt sąmoningai lieka tuščias — produkto puslapis tada rodo
 *   tik pilną aprašymą (dublikuoto teksto viršuje nereikia).
 * Papildomai: slepiam temos „Skydelis" antraštę ir mobilią apatinę nav juostą skydelio
 * puslapiuose (jos tik trukdo pildyti formą), supaprastinam aprašymo (TinyMCE) įrankyną.
 * Paslėpti laukai lieka DOM'e (pateikiami tušti = neprivalomi), todėl pateikimas nesulaužomas.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* -------- 1) CSS: antraštė, mobili juosta, nereikalingi laukai. */
add_action( 'wp_head', static function () {
	if ( is_admin() ) {
		return;
	}
	echo '<style id="dk-form-simplify">'
		/* Temos „Skydelis" antraštė (itemprop=headline) — nereikalinga; Dokan turi savo „Pridėti naują prekę". */
		. 'body.dokan-dashboard h1.entry-title[itemprop="headline"]{display:none !important}'
		/* Mobili apatinė nav juosta skydelyje tik trukdo pildyti formą. */
		. 'body.dokan-dashboard .dk-mobile-sticky{display:none !important}'
		/* Supaprastinta forma: paslėpti nereikalingus laukus (klasės/name pagal realų DOM). */
		. '.dokan-product-meta .content-half-part.sale-price{display:none !important}'
		. '.dokan-product-meta .content-half-part.regular-price{width:100% !important;float:none !important;padding-right:0 !important}'
		. '.dokan-product-meta .dokan-product-regular-price{width:100% !important}'
		. '.dokan-product-meta .dokan-form-group:has([name="post_excerpt"]){display:none !important}'
		/* Trumpojo aprašymo blokas REDAGAVIMO formoje (edit-product-single.php) — nėra po .dokan-product-meta. */
		. '.dokan-product-short-description{display:none !important}'
		/* Popup forma (tmpl-add-product-popup.php) — excerpt textarea taip pat šaknyje. */
		. '.dokan-dashboard .dokan-form-group:has(textarea[name="post_excerpt"]){display:none !important}'
		. '.dokan-product-meta .dokan-form-group:has(#product_brand){display:none !important}'
		. '.dokan-product-meta .dokan-form-group:has([name="product_tag[]"]){display:none !important}'
		/* Aprašymo (TinyMCE) įrankynas: palikti tik pirmą eilutę — mažiau vizualaus triukšmo. */
		. '.dokan-product-meta .mce-toolbar-grp .mce-toolbar:not(.mce-first){display:none !important}'
		. '.dokan-product-meta .mce-statusbar{display:none !important}'
		/* Šiek tiek erdvės tarp laukų telefone. */
		. '@media(max-width:768px){.dokan-product-meta .dokan-form-group{margin-bottom:18px}}'
		. '</style>';
} );

/* -------- 2) JS atsarga naršyklėms be :has() (paslepia tuos pačius laukus). */
add_action( 'wp_footer', static function () {
	if ( is_admin() ) {
		return;
	}
	?>
<script>
(function(){
  function hide(){
    ['[name="post_excerpt"]','#product_brand','[name="product_tag[]"]'].forEach(function(sel){
      var el=document.querySelector('.dokan-product-meta '+sel); if(!el)return;
      var g=el.closest ? el.closest('.dokan-form-group') : null; if(g)g.style.display='none';
    });
    /* Redagavimo forma + popup: trumpojo aprašymo blokai ne po .dokan-product-meta. */
    document.querySelectorAll('.dokan-product-short-description').forEach(function(g){g.style.display='none';});
    document.querySelectorAll('.dokan-dashboard textarea[name="post_excerpt"]').forEach(function(el){
      var g=el.closest ? el.closest('.dokan-form-group') : null; if(g)g.style.display='none';
    });
    var sp=document.querySelector('.dokan-product-meta .content-half-part.sale-price'); if(sp)sp.style.display='none';
    var rp=document.querySelector('.dokan-product-meta .content-half-part.regular-price'); if(rp){rp.style.width='100%';rp.style.cssFloat='none';}
  }
  if(document.readyState!=='loading'){hide();}else{document.addEventListener('DOMContentLoaded',hide);}
})();
</script>
	<?php
} );
