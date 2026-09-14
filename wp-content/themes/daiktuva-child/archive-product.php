<?php
/**
 * Daiktuva: WooCommerce produktų archyvas (shop + kategorijos + žymos).
 * Patikimas variantas per [dk_products], kad veiktu rusiavimas ir iskeltieji.
 */
defined( 'ABSPATH' ) || exit;

get_header();

$is_cat    = function_exists( 'is_product_category' ) && is_product_category();
$is_tag    = function_exists( 'is_product_tag' ) && is_product_tag();
$is_search = is_search();
$term      = ( $is_cat || $is_tag ) ? get_queried_object() : null;
$slug      = $term ? $term->slug : '';
if ( $term ) {
	$name = $term->name;
} elseif ( $is_search ) {
	$name = sprintf( 'Paieškos rezultatai: „%s“', get_search_query() );
} else {
	$name = 'Visi skelbimai';
}

$attr = '';
if ( $is_cat && $slug ) {
	$attr = 'category="' . esc_attr( $slug ) . '"';
} elseif ( $is_tag && $slug ) {
	$attr = 'tag="' . esc_attr( $slug ) . '"';
}
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
			echo do_shortcode( '[dk_products ' . $attr . ']' );
			?>
		</div>
	</div>
</div>
<?php
get_footer();
