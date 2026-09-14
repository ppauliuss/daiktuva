<?php
/**
 * Daiktuva: prekių kategorijos archyvas.
 * Patikimas variantas — naudoja [products] shortcode (su puslapiavimu) ir mūsų išdėstymą,
 * apeina temos/WooCommerce archyvo loop problemą.
 */
defined( 'ABSPATH' ) || exit;

get_header();

$term = get_queried_object();
$slug = ( $term && isset( $term->slug ) ) ? $term->slug : '';
$name = ( $term && isset( $term->name ) ) ? $term->name : 'Skelbimai';
?>
<div class="ast-container dk-archive">
	<div class="dk-home">
		<aside class="dk-sidebar">
			<?php echo do_shortcode( '[daiktuva_cats]' ); ?>
		</aside>
		<div class="dk-main">
			<h1 class="dk-cat-title"><?php echo esc_html( $name ); ?></h1>
			<?php
			if ( $term && ! empty( $term->description ) ) {
				echo '<p class="dk-cat-desc">' . wp_kses_post( $term->description ) . '</p>';
			}
			if ( $term && isset( $term->count ) && (int) $term->count <= 2 ) {
				echo '<div class="dk-empty-cta"><strong>Šioje skiltyje dar mažai skelbimų</strong><p>Būkite pirmasis — įdėkite skelbimą nemokamai, tai užtrunka vos porą minučių.</p><a class="dk-btn dk-btn-primary" href="/vendor-onboarding/">Įdėti skelbimą nemokamai</a></div>';
			}
			echo do_shortcode( '[dk_products category="' . esc_attr( $slug ) . '"]' );
			?>
		</div>
	</div>
</div>
<?php
get_footer();
