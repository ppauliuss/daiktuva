<?php
// Astra footer builder kredito pakeitimas (uzmaskuoja "Powered by Astra")
$o = get_option( 'astra-settings', array() );
$o['footer-sml-section-1-credit'] = '<span class="dk-copy">&copy; ' . gmdate( 'Y' ) . ' Daiktuva. Visos teisės saugomos.</span>';
$o['footer-sml-section-2-credit'] = '';
update_option( 'astra-settings', $o );
echo "footer option set\n";
