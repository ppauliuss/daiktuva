<?php
// WooCommerce registracijos/apmokejimo privatumo tekstas -> lietuviskai.
// [privacy_policy] WooCommerce automatiskai pakeicia nuoroda i privatumo politikos puslapi.
$txt = 'Jūsų asmens duomenys bus naudojami jūsų patirčiai šioje svetainėje gerinti, prieigai prie paskyros valdyti ir kitais tikslais, aprašytais mūsų [privacy_policy].';
update_option( 'woocommerce_registration_privacy_policy_text', $txt );
update_option( 'woocommerce_checkout_privacy_policy_text', $txt );
echo "WC privatumo tekstai nustatyti (LT)\n";
echo "registration: " . get_option( 'woocommerce_registration_privacy_policy_text' ) . "\n";
