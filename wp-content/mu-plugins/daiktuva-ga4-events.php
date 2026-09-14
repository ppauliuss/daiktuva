<?php
/**
 * Daiktuva: GA4 konversijų įvykiai (2026-07-04).
 * Įvykiai siunčiami TIK jei lankytojas sutiko su analitika (gtag įkrautas per daiktuva-consent.php):
 *  - sign_up        — po sėkmingos registracijos (per trumpalaikį slapuką dk_evt)
 *  - listing_added  — pardavėjas įdėjo skelbimą
 *  - call_click     — paspaustas telefono („Skambinti") mygtukas/nuoroda
 *  - whatsapp_click — paspausta WhatsApp nuoroda
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function dk_evt_cookie( string $val ) {
	if ( headers_sent() ) { return; }
	setcookie( 'dk_evt', $val, array(
		'expires'  => time() + 300,
		'path'     => '/',
		'secure'   => is_ssl(),
		'httponly' => false,
		'samesite' => 'Lax',
	) );
}

add_action( 'user_register', function () { dk_evt_cookie( 'sign_up' ); }, 5 );
add_action( 'dokan_new_product_added', function () { dk_evt_cookie( 'listing_added' ); }, 30 );

add_action( 'wp_footer', function () {
	?>
<script>
(function(){
  function send(name,params){ if(window.dkGaLoaded&&window.gtag){ gtag('event',name,params||{}); return true; } return false; }
  // 1) Ivykiai is slapuko (registracija, skelbimas)
  var m=document.cookie.match(/(?:^|; )dk_evt=([^;]*)/);
  if(m){
    var v=decodeURIComponent(m[1]);
	if(v==='sign_up'){
		send('sign_up',{method:'seller_registration'});
		send('registration_complete',{form_type:'seller'});
	}else if(v==='listing_added'){ send(v); }
    document.cookie='dk_evt=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/';
  }
  // 2) Skambinti / WhatsApp paspaudimai
  document.addEventListener('click',function(e){
    var el=e.target&&e.target.closest?e.target.closest('a[href^="tel:"],a[href*="wa.me"],a[href*="api.whatsapp.com"]'):null;
    if(!el)return;
    var h=el.getAttribute('href')||'';
    if(h.indexOf('tel:')===0){ send('call_click',{link_url:h}); }
    else{ send('whatsapp_click',{link_url:h}); }
  },true);
})();
</script>
	<?php
}, 99 );
