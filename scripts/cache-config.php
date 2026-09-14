<?php
// Cache Enabler: valyti cache redaguojant turini + 8h galiojimas (saugiklis).
$o = get_option( 'cache_enabler', array() );
$o['clear_site_cache_on_saved_post']    = 1;
$o['clear_site_cache_on_saved_comment'] = 1;
$o['clear_site_cache_on_saved_term']    = 1;
$o['clear_site_cache_on_changed_plugin']= 1;
$o['cache_expires']     = 1;
$o['cache_expiry_time'] = 8;
update_option( 'cache_enabler', $o );
echo "Cache Enabler konfiguruota (clear-on-save, 8h)\n";
if ( class_exists( 'Cache_Enabler' ) && method_exists( 'Cache_Enabler', 'clear_complete_cache' ) ) {
	Cache_Enabler::clear_complete_cache();
	echo "Cache isvalytas\n";
}
