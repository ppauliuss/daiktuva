<?php
/**
 * Homepage trust/process blocks and evergreen article internal links.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$homepage = <<<HTML
<div class="dk-hero">
  <div class="dk-hero-inner">
    <h1>Pirk ir parduok paprastai</h1>
    <p>Daiktuva - skelbimai ir prekės iš pirmų rankų visoje Lietuvoje.</p>
    <form class="dk-search dk-search-lg" role="search" method="get" action="/">
      <input type="search" name="s" placeholder="Ko ieškote? Pvz. traktorius, dviratis, telefonas..." />
      <input type="hidden" name="post_type" value="product" />
      <button type="submit">Ieškoti</button>
    </form>
  </div>
</div>

<div class="dk-home-info" aria-label="Kaip veikia Daiktuva">
  <div class="dk-info-card">
    <strong>1. Rask reikalingą daiktą</strong>
    <p>Ieškok pagal pavadinimą, kategoriją arba kainą. Skelbimuose matosi nuotraukos, miestas, pardavėjas ir pagrindinė informacija.</p>
  </div>
  <div class="dk-info-card">
    <strong>2. Susisiek su pardavėju</strong>
    <p>Prieš pirkimą pasitikslink būklę, komplektaciją, atsiėmimo vietą ir ar galima daiktą apžiūrėti gyvai.</p>
  </div>
  <div class="dk-info-card">
    <strong>3. Susitark saugiai</strong>
    <p>Brangesnius daiktus apžiūrėk prieš mokėdamas, o siuntimo sąlygas ir atsakomybę suderink iš anksto.</p>
  </div>
</div>

<div class="dk-trust-strip">
  <span>Aiškios kategorijos ir realistiški skelbimai</span>
  <span>Patogus dalinimasis per Facebook ir WhatsApp</span>
  <span>Patarimai saugesniam pirkimui ir pardavimui</span>
</div>

<div class="dk-popular">
  <strong>Populiarios paieškos</strong>
  <div class="dk-popular-links">
    <a href="/?s=traktorius&amp;post_type=product">traktorius</a>
    <a href="/?s=generatorius&amp;post_type=product">generatorius</a>
    <a href="/?s=suktuvas&amp;post_type=product">suktuvas</a>
    <a href="/?s=šaldytuvas&amp;post_type=product">šaldytuvas</a>
    <a href="/?s=priekaba&amp;post_type=product">priekaba</a>
    <a href="/?s=vejapjovė&amp;post_type=product">vejapjovė</a>
    <a href="/?s=iphone&amp;post_type=product">iphone</a>
    <a href="/?s=dviratis&amp;post_type=product">dviratis</a>
  </div>
</div>

<div class="dk-home">
  <aside class="dk-sidebar">
    [daiktuva_cats]
  </aside>
  <div class="dk-main">
    <h2>Skelbimai</h2>
    [dk_products per_page="12"]
  </div>
</div>
HTML;

wp_update_post(
	array(
		'ID'           => 23,
		'post_content' => $homepage,
	)
);

$link_marker = '<!-- daiktuva-internal-links-20260607 -->';
$blocks = array(
	170 => '<aside class="dk-article-links"><strong>Naudinga toliau:</strong> <a href="/dashboard/">Įdėti skelbimą</a> <a href="/patarimai/">Visi patarimai</a> <a href="/kaip-saugiai-pirkti-parduoti-internetu/">Kaip pirkti saugiau</a></aside>',
	183 => '<aside class="dk-article-links"><strong>Susiję:</strong> <a href="/kategorija/zemes-ukis/">Žemės ūkio skelbimai</a> <a href="/?s=traktorius&amp;post_type=product">Traktoriai</a> <a href="/taisykles/">Taisyklės</a></aside>',
	184 => '<aside class="dk-article-links"><strong>Naudinga:</strong> <a href="/taisykles/">Taisyklės</a> <a href="/kontaktai/">Kontaktai</a> <a href="/pristatymas-ir-grazinimas/">Pristatymas ir grąžinimas</a></aside>',
	185 => '<aside class="dk-article-links"><strong>Susiję skelbimai:</strong> <a href="/kategorija/namai-ir-sodas/">Namai ir sodas</a> <a href="/?s=šaldytuvas&amp;post_type=product">Šaldytuvai</a> <a href="/?s=skalbyklė&amp;post_type=product">Skalbyklės</a></aside>',
	186 => '<aside class="dk-article-links"><strong>Po nuotraukų:</strong> <a href="/dashboard/">Įdėti skelbimą</a> <a href="/kaip-greiciau-parduoti-naudota-daikta/">Kaip greičiau parduoti</a> <a href="/kategorija/statyba-ir-irankiai/">Statyba ir įrankiai</a></aside>',
);

foreach ( $blocks as $post_id => $block ) {
	$content = get_post_field( 'post_content', $post_id );
	if ( ! $content || false !== strpos( $content, $link_marker ) ) {
		continue;
	}

	wp_update_post(
		array(
			'ID'           => $post_id,
			'post_content' => $content . "\n\n" . $link_marker . "\n" . $block,
		)
	);
}

WP_CLI::success( 'Homepage and article links updated.' );
