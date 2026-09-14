<?php
/**
 * Plugin Name: Daiktuva – skelbimų peržiūrų skaitiklis
 * Description: 2026-08-20. AJAX beacon (admin-ajax), nes svečiams puslapis
 *   aptarnaujamas iš full page cache — template_redirect PHP tada nevykdomas.
 *   Apsaugos: tik publikuoti produktai, bot'ų UA atmesti, 6h dedupe per IP+skelbimą.
 *   Rodoma skelbimo puslapyje („Peržiūrų: N", child tema functions.php).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'wp_footer', static function () {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	$pid = (int) get_queried_object_id();
	if ( ! $pid ) {
		return;
	}
	?>
<script>
(function () {
	'use strict';
	if (navigator.webdriver) { return; } // headless naršyklės (mūsų smoke irgi)
	var xhr = new XMLHttpRequest();
	xhr.open('POST', <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, true);
	xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	xhr.send('action=dk_view_count&p=<?php echo (int) $pid; ?>');
})();
</script>
	<?php
}, 40 );

function dk_view_count_handler(): void {
	$pid = isset( $_POST['p'] ) ? absint( $_POST['p'] ) : 0;
	if ( ! $pid || 'product' !== get_post_type( $pid ) || 'publish' !== get_post_status( $pid ) ) {
		wp_send_json_error( null, 400 );
	}
	$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
	if ( preg_match( '/bot|crawl|spider|slurp|headless|lighthouse|pingdom/i', $ua ) ) {
		wp_send_json_success( 'bot' );
	}
	// Skelbimo autorius/admin — neskaičiuojame.
	if ( is_user_logged_in() ) {
		$author = (int) get_post_field( 'post_author', $pid );
		if ( get_current_user_id() === $author || current_user_can( 'manage_options' ) ) {
			wp_send_json_success( 'self' );
		}
	}
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
	$key = 'dkv_' . md5( $ip . '|' . $pid );
	if ( ! get_transient( $key ) ) {
		set_transient( $key, 1, 6 * HOUR_IN_SECONDS );
		update_post_meta( $pid, '_dk_views', (int) get_post_meta( $pid, '_dk_views', true ) + 1 );
	}
	wp_send_json_success( 'counted' );
}
add_action( 'wp_ajax_dk_view_count', 'dk_view_count_handler' );
add_action( 'wp_ajax_nopriv_dk_view_count', 'dk_view_count_handler' );
