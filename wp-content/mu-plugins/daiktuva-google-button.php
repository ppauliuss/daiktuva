<?php
/*
Plugin Name: Daiktuva Google mygtukas
Description: Rodo "Tęsti su Google" mygtuką WooCommerce prisijungimo ir registracijos formose. Naudoja Nextend shortcode per WC hook, nes native WC integracija nerenderina su custom My Account tema.
Version: 1.0
*/
if (!defined("ABSPATH")) exit;

function daiktuva_google_button($context = "login") {
    if (!shortcode_exists("nextend_social_login")) return;
    $or = ($context === "register") ? "Arba registruokitės su" : "Arba prisijunkite su";
    echo '<div class="dk-social-login" style="margin:16px 0 4px;text-align:center;">';
    echo '<div style="display:flex;align-items:center;gap:10px;color:#888;font-size:13px;margin-bottom:10px;"><span style="flex:1;height:1px;background:#e2e2e2;"></span>'.esc_html($or).'<span style="flex:1;height:1px;background:#e2e2e2;"></span></div>';
    $button = do_shortcode("[nextend_social_login]");
    // Nextend į aria-label įterpia <b> žymas kaip tekstą; ekrano skaitytuvui paliekame švarų pavadinimą.
    echo str_replace(
        array('aria-label="Tęsti su <b>Google</b>"', 'aria-label="Tęsti su &lt;b&gt;Google&lt;/b&gt;"'),
        'aria-label="Tęsti su Google"',
        $button
    );
    echo '</div>';
}
add_action("woocommerce_login_form_end",    function(){ daiktuva_google_button("login"); });
add_action("woocommerce_register_form_end", function(){ daiktuva_google_button("register"); });
