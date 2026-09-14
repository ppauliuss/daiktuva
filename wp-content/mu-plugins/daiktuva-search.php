<?php
/**
 * Daiktuva paieška (2026-07-04).
 * Pagrindinis variklis: Meilisearch (klaidų tolerancija, prefiksai, momentinė paieška).
 * Atsarginis: MariaDB FULLTEXT (MATCH ... AGAINST) — jei Meilisearch nepasiekiamas.
 * Kraštutinis: numatytoji WP paieška (labai trumpiems žodžiams).
 *
 * Meilisearch pasiekiamas TIK vidiniu docker tinklu (meilisearch:7700) — į internetą
 * neatvertas; naršyklės momentinė paieška eina per WP AJAX proxy (dk_suggest).
 *
 * Config iš aplinkos: MEILI_HOST, MEILI_MASTER_KEY (docker-compose).
 * Reindeksavimas: `wp dk-search setup && wp dk-search reindex`.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

const DK_MS_INDEX = 'products';

function dk_ms_host(): string { return rtrim( (string) getenv( 'MEILI_HOST' ), '/' ); }
function dk_ms_key(): string  { return (string) getenv( 'MEILI_MASTER_KEY' ); }
function dk_ms_ready(): bool   { return dk_ms_host() !== '' && dk_ms_key() !== ''; }

/** HTTP į Meilisearch. Grąžina [code, body-array] arba [0, null] jei nepasiekiamas. */
function dk_ms_req( string $method, string $path, $body = null, float $timeout = 4.0 ): array {
	if ( ! dk_ms_ready() ) { return array( 0, null ); }
	$args = array(
		'method'  => $method,
		'timeout' => $timeout,
		'headers' => array(
			'Authorization' => 'Bearer ' . dk_ms_key(),
			'Content-Type'  => 'application/json',
		),
	);
	if ( null !== $body ) { $args['body'] = wp_json_encode( $body ); }
	$r = wp_remote_request( dk_ms_host() . $path, $args );
	if ( is_wp_error( $r ) ) { return array( 0, null ); }
	$code = (int) wp_remote_retrieve_response_code( $r );
	$data = json_decode( wp_remote_retrieve_body( $r ), true );
	return array( $code, $data );
}

/* ---------------------------------------------------------- dokumentas --- */
function dk_ms_doc( int $post_id ): ?array {
	$p = get_post( $post_id );
	if ( ! $p || 'product' !== $p->post_type || 'publish' !== $p->post_status ) { return null; }
	$product = function_exists( 'wc_get_product' ) ? wc_get_product( $post_id ) : null;
	if ( ! $product ) { return null; }

	$cats = wp_get_post_terms( $post_id, 'product_cat', array( 'fields' => 'names' ) );
	$slug = wp_get_post_terms( $post_id, 'product_cat', array( 'fields' => 'slugs' ) );
	$author = (int) $p->post_author;
	$store  = '';
	if ( function_exists( 'dokan_get_store_info' ) ) {
		$si = dokan_get_store_info( $author );
		$store = ! empty( $si['store_name'] ) ? $si['store_name'] : '';
	}
	$img = get_the_post_thumbnail_url( $post_id, 'woocommerce_thumbnail' );

	return array(
		'id'            => $post_id,
		'title'         => $p->post_title,
		'description'   => wp_strip_all_tags( $p->post_content ),
		'category'      => is_array( $cats ) ? implode( ', ', $cats ) : '',
		'category_slug' => is_array( $slug ) ? array_values( $slug ) : array(),
		'location'      => (string) get_post_meta( $post_id, '_dk_location', true ),
		'store'         => $store,
		'status'        => (string) get_post_meta( $post_id, '_daiktuva_status', true ) ?: 'active',
		'price'         => (float) $product->get_price(),
		'price_html'    => $product->get_price_html(),
		'permalink'     => get_permalink( $post_id ),
		'image'         => $img ? $img : '',
		'created'       => (int) get_post_time( 'U', true, $post_id ),
	);
}

/* --------------------------------------------------------- sinchronas --- */
function dk_ms_index_product( int $post_id ): void {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) { return; }
	$doc = dk_ms_doc( $post_id );
	if ( null === $doc ) { dk_ms_delete_product( $post_id ); return; }
	dk_ms_req( 'PUT', '/indexes/' . DK_MS_INDEX . '/documents', array( $doc ), 8.0 );
}
function dk_ms_delete_product( int $post_id ): void {
	dk_ms_req( 'DELETE', '/indexes/' . DK_MS_INDEX . '/documents/' . $post_id, null, 6.0 );
}

add_action( 'save_post_product', 'dk_ms_index_product', 20 );
add_action( 'woocommerce_update_product', 'dk_ms_index_product', 20 );
add_action( 'woocommerce_new_product', 'dk_ms_index_product', 20 );
add_action( 'dokan_new_product_added', 'dk_ms_index_product', 40 );
add_action( 'dokan_product_updated', 'dk_ms_index_product', 40 );
add_action( 'before_delete_post', 'dk_ms_delete_product' );
add_action( 'wp_trash_post', 'dk_ms_delete_product' );
add_action( 'transition_post_status', function ( $new, $old, $post ) {
	if ( $post->post_type === 'product' ) {
		if ( 'publish' === $new ) { dk_ms_index_product( $post->ID ); }
		else { dk_ms_delete_product( $post->ID ); }
	}
}, 10, 3 );

/* ---------------------------------------------- indekso nustatymai --- */
function dk_ms_setup(): array {
	dk_ms_req( 'POST', '/indexes', array( 'uid' => DK_MS_INDEX, 'primaryKey' => 'id' ), 8.0 );
	list( $code ) = dk_ms_req( 'PATCH', '/indexes/' . DK_MS_INDEX . '/settings', array(
		'searchableAttributes'  => array( 'title', 'category', 'store', 'location', 'description' ),
		'filterableAttributes'  => array( 'category_slug', 'status', 'price' ),
		'sortableAttributes'    => array( 'price', 'created' ),
		'rankingRules'          => array( 'words', 'typo', 'proximity', 'attribute', 'sort', 'exactness' ),
		'typoTolerance'         => array( 'enabled' => true, 'minWordSizeForTypos' => array( 'oneTypo' => 4, 'twoTypos' => 8 ) ),
		'synonyms'              => array(
			'tel'     => array( 'telefonas', 'telefonai' ),
			'pc'      => array( 'kompiuteris', 'kompiuteriai' ),
			'tv'      => array( 'televizorius' ),
			'dviratis'=> array( 'dviračiai', 'dviratukas' ),
			'auto'    => array( 'automobilis', 'mašina' ),
		),
		'stopWords'             => array( 'ir', 'bei', 'su', 'be', 'ar', 'kaip', 'nuo', 'iki' ),
	), 8.0 );
	return array( 'settings_code' => $code );
}

function dk_ms_reindex(): array {
	$ids  = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids' ) );
	$docs = array();
	foreach ( $ids as $id ) { $d = dk_ms_doc( (int) $id ); if ( $d ) { $docs[] = $d; } }
	if ( $docs ) { dk_ms_req( 'PUT', '/indexes/' . DK_MS_INDEX . '/documents', $docs, 20.0 ); }
	return array( 'sent' => count( $docs ) );
}

/* -------------------------------------------------------- paieška --- */
/** Grąžina prekių ID pagal svarbą, arba null jei Meilisearch nepasiekiamas. */
function dk_ms_search_ids( string $term, int $limit = 60, array $opts = array() ): ?array {
	$body = array( 'q' => $term, 'limit' => $limit, 'attributesToRetrieve' => array( 'id' ) );
	if ( ! empty( $opts['filter'] ) ) { $body['filter'] = $opts['filter']; }
	if ( ! empty( $opts['sort'] ) )   { $body['sort']   = $opts['sort']; }
	list( $code, $data ) = dk_ms_req( 'POST', '/indexes/' . DK_MS_INDEX . '/search', $body, 2.5 );
	if ( 200 !== $code || ! is_array( $data ) || ! isset( $data['hits'] ) ) { return null; }
	return array_map( static function ( $h ) { return (int) $h['id']; }, $data['hits'] );
}

/* Pagrindinė rezultatų paieška: Meilisearch → post__in; kitaip FULLTEXT fallback. */
add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() || ! $q->is_main_query() || ! $q->is_search() ) { return; }
	$term = trim( (string) $q->get( 's' ) );
	if ( '' === $term ) { return; }
	$q->set( 'post_type', 'product' );

	$ids = dk_ms_search_ids( $term, 120 );
	if ( null !== $ids ) {
		$GLOBALS['dk_ms_active'] = true;             // pažymim, kad matchinam per post__in
		$q->set( 'post__in', $ids ? $ids : array( 0 ) );
		$q->set( 'orderby', 'post__in' );
		return;
	}
	$GLOBALS['dk_ft_term'] = $term;                  // Meilisearch down → FULLTEXT fallback
} );

/* posts_search: išjungiam LIKE kai naudojam Meilisearch; įjungiam FULLTEXT kai fallback. */
add_filter( 'posts_search', function ( $search, $q ) {
	if ( is_admin() || ! $q->is_main_query() || ! $q->is_search() ) { return $search; }
	global $wpdb;
	if ( ! empty( $GLOBALS['dk_ms_active'] ) ) { return ''; }               // Meilisearch tvarko match'ą
	$term = $GLOBALS['dk_ft_term'] ?? '';
	if ( $term !== '' && mb_strlen( $term ) >= 3 ) {                        // FULLTEXT (min token 3)
		$safe = $wpdb->prepare( '%s', dk_ms_ft_query( $term ) );
		return " AND MATCH({$wpdb->posts}.post_title, {$wpdb->posts}.post_content) AGAINST ($safe IN BOOLEAN MODE) ";
	}
	return $search;                                                        // trumpi žodžiai → default LIKE
}, 10, 2 );

add_filter( 'posts_orderby', function ( $orderby, $q ) {
	if ( is_admin() || ! $q->is_main_query() || ! $q->is_search() ) { return $orderby; }
	global $wpdb;
	$term = $GLOBALS['dk_ft_term'] ?? '';
	if ( empty( $GLOBALS['dk_ms_active'] ) && $term !== '' && mb_strlen( $term ) >= 3 ) {
		$safe = $wpdb->prepare( '%s', dk_ms_ft_query( $term ) );
		return " MATCH({$wpdb->posts}.post_title, {$wpdb->posts}.post_content) AGAINST ($safe IN BOOLEAN MODE) DESC ";
	}
	return $orderby;
}, 10, 2 );

/** FULLTEXT boolean užklausa: kiekvienas žodis su prefiksu (dvira*). */
function dk_ms_ft_query( string $term ): string {
	$words = preg_split( '/\s+/', trim( $term ) );
	$out   = array();
	foreach ( $words as $w ) {
		$w = preg_replace( '/[+\-><\(\)~*\"@]+/', '', $w );
		if ( mb_strlen( $w ) >= 3 ) { $out[] = '+' . $w . '*'; }
	}
	return $out ? implode( ' ', $out ) : $term;
}

/* ------------------------------------------- momentinė paieška (AJAX) --- */
add_action( 'wp_ajax_dk_suggest', 'dk_suggest_handler' );
add_action( 'wp_ajax_nopriv_dk_suggest', 'dk_suggest_handler' );
function dk_suggest_handler() {
	$term = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
	if ( mb_strlen( $term ) < 2 ) { wp_send_json( array( 'items' => array() ) ); }
	$body = array(
		'q'                    => $term,
		'limit'                => 6,
		'attributesToRetrieve' => array( 'id', 'title', 'price', 'permalink', 'image', 'category' ),
		'attributesToSearchOn' => array( 'title', 'category', 'store', 'location' ), // dropdown'e be aprašymo — švariau
	);
	list( $code, $data ) = dk_ms_req( 'POST', '/indexes/' . DK_MS_INDEX . '/search', $body, 2.5 );
	$items = array();
	if ( 200 === $code && ! empty( $data['hits'] ) ) {
	foreach ( $data['hits'] as $h ) {
			$raw_price = isset( $h['price'] ) ? (float) $h['price'] : null;
			$decimals  = null !== $raw_price && floor( $raw_price ) !== $raw_price ? 2 : 0;
			$items[] = array(
				'title' => $h['title'] ?? '',
				'price' => null !== $raw_price ? number_format_i18n( $raw_price, $decimals ) . ' €' : '',
				'url'   => $h['permalink'] ?? '',
				'img'   => $h['image'] ?? '',
				'cat'   => $h['category'] ?? '',
			);
		}
	}
	wp_send_json( array( 'items' => $items, 'more' => home_url( '/?s=' . rawurlencode( $term ) . '&post_type=product' ) ) );
}

/* Momentinės paieškos JS + CSS (tik jei Meilisearch veikia). */
add_action( 'wp_footer', function () {
	if ( is_admin() || ! dk_ms_ready() ) { return; }
	$ajax = admin_url( 'admin-ajax.php' );
	?>
<style>
.dk-ac-wrap{position:relative;}
.dk-ac{position:absolute;left:0;right:0;top:calc(100% + 6px);background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 12px 40px rgba(20,23,33,.16);z-index:9999;overflow:hidden;display:none;}
.dk-ac.open{display:block;}
.dk-ac a{display:flex;gap:10px;align-items:center;padding:9px 12px;text-decoration:none;color:#1f2430;border-bottom:1px solid #f1f5f9;}
.dk-ac a:last-child{border-bottom:0;}
.dk-ac a:hover,.dk-ac a.active{background:#eaf2fd;}
.dk-ac img{width:42px;height:42px;object-fit:cover;border-radius:8px;flex:none;background:#f1f5f9;}
.dk-ac .dk-ac-t{font-weight:600;font-size:14px;line-height:1.2;}
.dk-ac .dk-ac-c{font-size:12px;color:#64748b;}
.dk-ac .dk-ac-p{margin-left:auto;font-weight:800;color:#d97706;font-size:13.5px;white-space:nowrap;}
.dk-ac .dk-ac-more{justify-content:center;font-weight:700;color:#2563eb;}
.dk-ac .dk-ac-empty{padding:12px;color:#64748b;font-size:13.5px;}
</style>
<script>
(function(){
 var AJAX=<?php echo wp_json_encode( $ajax ); ?>;
 var inputs=document.querySelectorAll('input[type="search"][name="s"], .dk-search input[name="s"]');
 inputs.forEach(function(inp){
   if(inp.dataset.dkAc)return; inp.dataset.dkAc=1;
   var form=inp.closest('form')||inp.parentNode;
   form.classList.add('dk-ac-wrap');
   var box=document.createElement('div'); box.className='dk-ac'; box.id='dk-ac-'+Math.random().toString(36).slice(2,9); box.setAttribute('role','listbox'); box.setAttribute('aria-label','Paieškos pasiūlymai'); form.appendChild(box);
   inp.setAttribute('role','combobox'); inp.setAttribute('aria-autocomplete','list'); inp.setAttribute('aria-controls',box.id); inp.setAttribute('aria-expanded','false');
   var t=null, items=[], sel=-1, lastQ='';
   function close(){box.classList.remove('open');inp.setAttribute('aria-expanded','false');inp.removeAttribute('aria-activedescendant');sel=-1;}
   function render(data){
     items=data.items||[];
     if(!lastQ){close();return;}
     if(!items.length){box.innerHTML='<div class="dk-ac-empty">Nieko nerasta pagal „'+esc(lastQ)+'"</div>';box.classList.add('open');return;}
     var h=items.map(function(it,i){
       return '<a href="'+esc(it.url)+'" id="'+box.id+'-option-'+i+'" role="option" aria-selected="false" data-i="'+i+'">'+(it.img?'<img loading="lazy" src="'+esc(it.img)+'" alt="">':'<img alt="">')+
         '<span><span class="dk-ac-t">'+esc(it.title)+'</span><br><span class="dk-ac-c">'+esc(it.cat)+'</span></span>'+
         '<span class="dk-ac-p">'+esc(it.price||'')+'</span></a>';
     }).join('');
     h+='<a class="dk-ac-more" id="'+box.id+'-option-'+items.length+'" role="option" aria-selected="false" href="'+esc(data.more)+'">Visi rezultatai →</a>';
     box.innerHTML=h; box.classList.add('open'); inp.setAttribute('aria-expanded','true');
   }
   function esc(s){return String(s||'').replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c];});}
   function go(){
     var q=inp.value.trim(); lastQ=q;
     if(q.length<2){close();return;}
     fetch(AJAX+'?action=dk_suggest&q='+encodeURIComponent(q),{credentials:'same-origin'})
       .then(function(r){return r.json();}).then(render).catch(function(){close();});
   }
   inp.addEventListener('input',function(){clearTimeout(t);t=setTimeout(go,180);});
   inp.addEventListener('focus',function(){if(inp.value.trim().length>=2&&items.length){box.classList.add('open');inp.setAttribute('aria-expanded','true');}});
   inp.addEventListener('keydown',function(e){
     var links=box.querySelectorAll('a');
     if(e.key==='ArrowDown'){e.preventDefault();sel=Math.min(sel+1,links.length-1);}
     else if(e.key==='ArrowUp'){e.preventDefault();sel=Math.max(sel-1,0);}
     else if(e.key==='Enter'){if(sel>=0&&links[sel]){e.preventDefault();location.href=links[sel].href;}return;}
     else if(e.key==='Escape'){close();return;}
     else return;
     links.forEach(function(a,i){var active=i===sel;a.classList.toggle('active',active);if(a.getAttribute('role')==='option')a.setAttribute('aria-selected',active?'true':'false');});
     if(links[sel])inp.setAttribute('aria-activedescendant',links[sel].id||'');
   });
   document.addEventListener('click',function(e){if(!form.contains(e.target))close();});
 });
})();
</script>
	<?php
}, 97 );

/* ----------------------------------------------------------- WP-CLI --- */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'dk-search', new class {
		public function setup() { $r = dk_ms_setup(); WP_CLI::success( 'Meili setup: ' . wp_json_encode( $r ) ); }
		public function reindex() { $r = dk_ms_reindex(); WP_CLI::success( 'Reindeksuota dokumentų: ' . $r['sent'] ); }
		public function ping() {
			list( $code, $data ) = dk_ms_req( 'GET', '/health', null, 3.0 );
			WP_CLI::log( 'Meili health: HTTP ' . $code . ' ' . wp_json_encode( $data ) );
		}
	} );
}
