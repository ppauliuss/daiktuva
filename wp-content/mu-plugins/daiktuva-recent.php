<?php
/**
 * Plugin Name: Daiktuva – Neseniai ziureta
 * Description: localStorage pagrindu: skelbimo atidarymas issaugomas, virs footerio rodoma juosta "Neseniai ziureta" (be serverio apkrovos).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'astra_footer_before', function () {
	if ( function_exists( 'is_product' ) && is_product() ) {
		global $product;
		if ( $product instanceof WC_Product ) {
			$img = wp_get_attachment_image_src( get_post_thumbnail_id( $product->get_id() ), 'woocommerce_thumbnail' );
			printf(
				'<script>var dkRP={id:%d,title:%s,url:%s,img:%s};</script>',
				(int) $product->get_id(),
				wp_json_encode( get_the_title( $product->get_id() ) ),
				wp_json_encode( get_permalink( $product->get_id() ) ),
				wp_json_encode( $img ? $img[0] : '' )
			);
		}
	}
	?>
<style id="dk-recent-css">
.dk-recent { max-width: 1200px; margin: 24px auto 0; padding: 0 16px; }
.dk-recent > strong { display: block; margin-bottom: 10px; font-size: 16px; }
.dk-recent-row { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 6px; }
.dk-recent-item { flex: 0 0 130px; text-decoration: none !important; color: inherit; }
.dk-recent-item img { width: 130px; height: 88px; object-fit: contain; border-radius: 8px; display: block; background: #f1f5f9; }
.dk-recent-item span { display: block; font-size: 12.5px; line-height: 1.3; margin-top: 5px; color: #334155; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
</style>
<div id="dk-recent-wrap" class="dk-recent" hidden>
	<strong>Neseniai žiūrėta</strong>
	<div id="dk-recent-row" class="dk-recent-row"></div>
</div>
<script>
(function(){
  var KEY='dk_recent_v1';
  function load(){ try{ return JSON.parse(localStorage.getItem(KEY))||[]; }catch(e){ return []; } }
  function save(a){ try{ localStorage.setItem(KEY, JSON.stringify(a.slice(0,8))); }catch(e){} }
  if (window.dkRP){
    var a = load().filter(function(x){ return x && x.id !== dkRP.id; });
    a.unshift(dkRP); save(a);
  }
  var wrap = document.getElementById('dk-recent-wrap'), row = document.getElementById('dk-recent-row');
  if (!wrap || !row) return;
  var items = load().filter(function(x){ return !window.dkRP || x.id !== dkRP.id; });
  if (!items.length) return;
  row.innerHTML = items.map(function(x){
    return '<a class="dk-recent-item" href="'+x.url+'">'
      + (x.img ? '<img src="'+x.img+'" alt="" loading="lazy">' : '')
      + '<span>'+x.title+'</span></a>';
  }).join('');
  wrap.hidden = false;
})();
</script>
	<?php
}, 30 );
