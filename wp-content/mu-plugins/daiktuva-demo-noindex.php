<?php
/**
 * Plugin Name: Daiktuva – demo pardaveju noindex
 * Description: Demo pardaveju (pardavejasN) Dokan parduotuvems ideda robots noindex. Isimti, kai demo pardavejai bus pasalinti arba atsiras realiu.
 */
add_action('wp_head', function () {
    if (!function_exists('dokan_is_store_page') || !dokan_is_store_page()) {
        return;
    }
    $store = get_query_var('store');
    if ($store && preg_match('/^pardavejas[0-9]*$/i', $store)) {
        echo "<meta name='robots' content='noindex,follow' />\n";
    }
}, 1);
