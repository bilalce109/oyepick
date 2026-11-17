<?php
/**
 * Product in a loop — wraps the theme's card.
 *
 * @package OyePick
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;
if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}
?>
<li <?php wc_product_class( 'op-cell', $product ); ?>>
	<?php get_template_part( 'template-parts/product-card' ); ?>
</li>
