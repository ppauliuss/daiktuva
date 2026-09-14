<?php
/**
 * Plugin Name: Daiktuva – merchant feed be canonical redirect
 * Description: WP redirect_canonical vercia /merchant-feed.xml -> /merchant-feed.xml/ (301). Google Merchant Center jam pakanka, bet tiesioginis 200 svarnesnis – isjungiam redirect siam endpoint'ui.
 */
add_filter('redirect_canonical', function ($redirect, $request) {
    if (strpos($request, '/merchant-feed.xml') !== false) {
        return false;
    }
    return $redirect;
}, 10, 2);
