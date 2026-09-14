<?php
/**
 * Daiktuva: skambinimo mygtukas desktop'e (2026-07-04).
 * Mobiliuose `tel:` atidaro skambinimą. Desktop'e telefono programos nėra, tad
 * mygtukas atrodydavo „neaktyvus". Čia: desktop'e paspaudus „Skambinti" — atskleidžia
 * numerį (antras paspaudimas nebeblokuoja). Mobiliuose elgsena nesikeičia.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'wp_footer', static function () {
	if ( is_admin() ) {
		return;
	}
	?>
<script>
(function(){
  var canDial = window.matchMedia && window.matchMedia('(pointer: coarse)').matches;
  if (canDial) return; // mobilus/liečiamas ekranas — palikti tel: kaip yra
  document.addEventListener('click', function(e){
    var a = e.target.closest ? e.target.closest('.daiktuva-call-button') : null;
    if (!a) return;
    if (a.dataset.dkRevealed) return; // jau atskleista — leisti numatytą veiksmą
    var num = (a.getAttribute('href') || '').replace(/^tel:/i, '').trim();
    if (!num) return;
    e.preventDefault();
    a.dataset.dkRevealed = '1';
    a.textContent = '📞 ' + num;
    a.setAttribute('title', 'Skambinkite šiuo numeriu');
  }, true);
})();
</script>
	<?php
}, 100 );
