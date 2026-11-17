<?php
/**
 * Product card (used on the homepage and in all WooCommerce product loops).
 *
 * Expects the global $product.
 *
 * @package OyePick
 */

defined( 'ABSPATH' ) || exit;

global $product;
if ( ! $product || ! $product->is_visible() ) {
	return;
}

list( $top_cat, $label ) = oyepick_product_cats( $product );
$link   = $product->get_permalink();
$has_px = '' !== $product->get_price();
$note   = wp_strip_all_tags( $product->get_short_description() );
$note   = $note ? wp_trim_words( $note, 9, '…' ) : '';

$badge = '';
if ( $product->is_on_sale() && $product->get_regular_price() ) {
	$pct   = round( 100 - ( (float) $product->get_sale_price() / (float) $product->get_regular_price() ) * 100 );
	$badge = '<span class="tag">-' . (int) $pct . '%</span>';
} elseif ( oyepick_is_new( $product ) ) {
	$badge = '<span class="tag new">' . esc_html__( 'New', 'oyepick' ) . '</span>';
}
$rating = (float) $product->get_average_rating();
?>
<article class="op-card reveal" data-cat="<?php echo esc_attr( $top_cat ); ?>">
	<div class="media">
		<a href="<?php echo esc_url( $link ); ?>" class="full">
			<?php echo $product->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy' ) ); // phpcs:ignore ?>
		</a>
		<?php echo $badge; // phpcs:ignore ?>
		<button class="wish" type="button" aria-label="<?php esc_attr_e( 'Add to wishlist', 'oyepick' ); ?>"><?php echo oyepick_icon( 'heart', 18 ); // phpcs:ignore ?></button>
	</div>
	<div class="body">
		<?php if ( $label ) : ?><span class="cat-name"><?php echo esc_html( $label ); ?></span><?php endif; ?>
		<h3><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $product->get_name() ); ?></a></h3>
		<?php if ( $rating > 0 ) : ?>
			<div class="card-rating"><?php echo oyepick_stars( $rating ); // phpcs:ignore ?><small>(<?php echo (int) $product->get_review_count(); ?>)</small></div>
		<?php elseif ( $note ) : ?>
			<p class="note"><?php echo esc_html( $note ); ?></p>
		<?php endif; ?>
		<div class="foot">
			<?php if ( $has_px ) : ?>
				<span class="price"><?php echo $product->get_price_html(); // phpcs:ignore ?></span>
				<?php woocommerce_template_loop_add_to_cart(); ?>
			<?php else : ?>
				<span class="price ask"><?php esc_html_e( 'Price on WhatsApp', 'oyepick' ); ?></span>
				<a class="add" href="<?php echo esc_url( oyepick_wa_link( 'Assalam o Alaikum, price batayein: ' . $product->get_name() ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Ask', 'oyepick' ); ?></a>
			<?php endif; ?>
		</div>
	</div>
</article>
