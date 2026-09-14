<?php
/**
 * Plugin Name: Daiktuva – XML-RPC uzdraudimas (atsarga)
 * Description: Pagrindinis blokavimas – .htaccess RedirectMatch 404 (WP 7.0 core neturi xmlrpc_enabled filtro – pasalinta). Sis filtras paliktas kaip atsarga, jei core filtra grazintu.
 */
add_filter("xmlrpc_enabled", "__return_false");
