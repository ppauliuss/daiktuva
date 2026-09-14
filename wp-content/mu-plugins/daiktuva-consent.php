<?php
/**
 * Daiktuva: GDPR slapuku sutikimas + Google Analytics 4 (Consent Mode v2).
 * - Analitika ir reklamos slapukai NUMATYTAI uzdrausti, kol naudotojas nesutinka.
 * - GA4 ID irasomas: Nustatymai > Bendri > "Google Analytics 4 ID" (option dk_ga4_id).
 * - Tas pats sutikimas valdo ir busima AdSense (ad_storage).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* GA4 kraunamas tik po sutikimo. */
add_action( 'wp_head', function () {
	$ga = trim( (string) get_option( 'dk_ga4_id', '' ) );
	?>
<script>
window.dkGa4Id=<?php echo wp_json_encode( $ga ); ?>;
window.dkLoadGa=function(){
  if(!window.dkGa4Id||window.dkGaLoaded){return;}
  window.dkGaLoaded=true;
  window.dataLayer=window.dataLayer||[];
  window.gtag=function(){dataLayer.push(arguments);}
  gtag('consent','default',{'ad_storage':'granted','ad_user_data':'granted','ad_personalization':'granted','analytics_storage':'granted'});
  gtag('js',new Date());
  gtag('config',window.dkGa4Id,{'anonymize_ip':true});
  var s=document.createElement('script');s.async=true;s.src='https://www.googletagmanager.com/gtag/js?id='+encodeURIComponent(window.dkGa4Id);document.head.appendChild(s);
};
(function(){try{if(document.cookie.indexOf('dk_consent=accepted')>-1){window.dkLoadGa();}}catch(e){}})();
</script>
	<?php
}, 1 );

/* Sutikimo baneris + CSS + JS (footer) */
add_action( 'wp_footer', function () {
	?>
<div id="dk-cookie" class="dk-cookie" role="dialog" aria-live="polite" aria-label="Slapukų pasirinkimas" hidden>
  <div class="dk-cookie-inner">
    <p>Naudojame slapukus, kad svetainė tinkamai veiktų ir matytume lankomumo statistiką. Daugiau – <a href="/privatumo-politika/">privatumo politikoje</a>.</p>
    <div class="dk-cookie-btns">
      <button type="button" class="dk-c-essential">Tik būtini</button>
      <button type="button" class="dk-c-accept">Sutinku</button>
    </div>
  </div>
</div>
<style>
.dk-cookie{position:fixed;left:16px;right:16px;bottom:16px;z-index:99999;background:#fff;border:1px solid #e7e9ee;border-radius:14px;box-shadow:0 10px 40px rgba(20,23,33,.18);}
.dk-cookie-inner{max-width:1100px;margin:0 auto;padding:16px 18px;display:flex;align-items:center;gap:18px;flex-wrap:wrap;}
.dk-cookie p{margin:0;flex:1;min-width:240px;font-size:14px;color:#3a4150;line-height:1.5;}
.dk-cookie a{color:#e85d00;font-weight:600;}
.dk-cookie-btns{display:flex;gap:10px;}
.dk-cookie button{cursor:pointer;border-radius:999px;padding:10px 22px;font-weight:700;font-size:14px;border:1px solid #e7e9ee;}
.dk-c-essential{background:#fff;color:#3a4150;}
.dk-c-essential:hover{border-color:#c9d0db;}
.dk-c-accept{background:#ff6900;border-color:#ff6900;color:#fff;box-shadow:0 2px 8px rgba(255,105,0,.35);}
.dk-c-accept:hover{background:#e85d00;}
@media(max-width:560px){.dk-cookie-btns{width:100%;}.dk-cookie button{flex:1;min-height:44px;}}
@media(max-width:760px){.dk-cookie{bottom:calc(8px + env(safe-area-inset-bottom));left:8px;right:8px;border-radius:10px;}
body.dk-consent-open{padding-bottom:calc(var(--dk-consent-height, 0px) + 16px + env(safe-area-inset-bottom))!important;}
body.dk-consent-open .dk-mobile-sticky{display:none!important;}
.dk-cookie-inner{padding:10px 12px;gap:8px;}
.dk-cookie p{font-size:12px;line-height:1.4;min-width:0;}
.dk-cookie button{padding:8px 14px;font-size:13px;}}
</style>
<script>
(function(){
  var box=document.getElementById('dk-cookie');if(!box)return;
  function has(v){return document.cookie.indexOf('dk_consent='+v)>-1;}
  function set(v){var d=new Date(),sec=location.protocol==='https:'?';Secure':'';d.setTime(d.getTime()+180*864e5);document.cookie='dk_consent='+v+';expires='+d.toUTCString()+';path=/;SameSite=Lax'+sec;}
  function syncHeight(){if(!box.hidden){document.body.style.setProperty('--dk-consent-height',box.offsetHeight+'px');}}
  function open(){box.hidden=false;document.body.classList.add('dk-consent-open');requestAnimationFrame(syncHeight);}
  function close(){box.hidden=true;document.body.classList.remove('dk-consent-open');document.body.style.removeProperty('--dk-consent-height');}
  if(!has('accepted')&&!has('essential')){open();}
  if(window.ResizeObserver){new ResizeObserver(syncHeight).observe(box);}else{window.addEventListener('resize',syncHeight);}
  var a=box.querySelector('.dk-c-accept'),e=box.querySelector('.dk-c-essential');
  a&&a.addEventListener('click',function(){set('accepted');window.dkLoadGa&&window.dkLoadGa();close();});
  e&&e.addEventListener('click',function(){set('essential');close();});
})();
</script>
	<?php
} );

/* Admin laukelis GA4 ID: Nustatymai > Bendri */
add_action( 'admin_init', function () {
	register_setting( 'general', 'dk_ga4_id', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ) );
	add_settings_field( 'dk_ga4_id', 'Google Analytics 4 ID', function () {
		$v = esc_attr( get_option( 'dk_ga4_id', '' ) );
		echo '<input type="text" name="dk_ga4_id" value="' . $v . '" placeholder="G-XXXXXXXXXX" class="regular-text" />';
		echo '<p class="description">GA4 matavimo ID. Tuščia = analitika neįkraunama. Slapukai įsijungia tik po lankytojo sutikimo.</p>';
	}, 'general' );
} );
