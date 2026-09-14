<?php
$page = get_page_by_path( 'pradzia', OBJECT, 'page' );
if ( ! $page ) {
	fwrite( STDERR, "Homepage page not found.\n" );
	exit( 1 );
}

$content = <<<'HTML'
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

<div class="dk-home">
  <aside class="dk-sidebar">
    [daiktuva_cats]
  </aside>
  <div class="dk-main">
    <h2>Skelbimai</h2>
    [dk_products per_page="12"]
  </div>
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
HTML;

$updated = wp_update_post( array(
	'ID'           => $page->ID,
	'post_content' => $content,
), true );

if ( is_wp_error( $updated ) ) {
	fwrite( STDERR, $updated->get_error_message() . "\n" );
	exit( 1 );
}

echo get_permalink( $page->ID ) . "\n";
