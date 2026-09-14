<?php
// Pradinio puslapio turinys su nauju [dk_products] (rusiavimas + puslapiavimas).
$fp = (int) get_option( 'page_on_front' );
if ( ! $fp ) { echo "NERA front page\n"; return; }

$content = <<<'HTML'
[dk_ad type="leader"]
<div class="dk-hero">
  <div class="dk-hero-inner">
    <h1>Pirk ir parduok paprastai</h1>
    <p>Daiktuva – skelbimai ir prekės iš pirmų rankų visoje Lietuvoje.</p>
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
    [dk_ad type="side"]
  </aside>
  <div class="dk-main">
    <h2>Skelbimai</h2>
    [dk_products per_page="12"]
    [dk_ad type="infeed"]
  </div>
</div>
HTML;

wp_update_post( array( 'ID' => $fp, 'post_content' => $content ) );
echo "Homepage (id $fp) atnaujintas su [dk_products]\n";
