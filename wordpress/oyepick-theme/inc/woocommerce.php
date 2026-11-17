<?php
/**
 * WooCommerce integration.
 *
 * @package OyePick
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/**
 * Header cart count + drawer contents refresh after AJAX add to cart.
 * (The drawer body uses WooCommerce's own `div.widget_shopping_cart_content` fragment.)
 */
add_filter(
	'woocommerce_add_to_cart_fragments',
	function ( $fragments ) {
		$count                              = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
		$fragments['span.op-cart-count']    = '<span class="badge op-cart-count">' . (int) $count . '</span>';
		$fragments['span.op-drawer-count']  = '<span class="op-drawer-count">(' . (int) $count . ')</span>';
		return $fragments;
	}
);

// Shop grid: 12 per page, 4 columns.
add_filter( 'loop_shop_per_page', fn() => 12 );
add_filter( 'loop_shop_columns', fn() => 4 );
add_filter(
	'woocommerce_output_related_products_args',
	function ( $args ) {
		$args['posts_per_page'] = 4;
		$args['columns']        = 4;
		return $args;
	}
);

// Our cards print their own add-to-cart markup; keep Woo's "View cart" link hidden via CSS.
add_filter(
	'woocommerce_loop_add_to_cart_args',
	function ( $args ) {
		$args['class'] = trim( ( $args['class'] ?? '' ) . ' add' );
		return $args;
	}
);

/**
 * Single product: show reviews as their own full-width "Customer reviews" column
 * instead of hiding them in a tab.
 */
add_filter(
	'woocommerce_product_tabs',
	function ( $tabs ) {
		unset( $tabs['reviews'] );
		return $tabs;
	},
	98
);
add_action(
	'woocommerce_after_single_product_summary',
	function () {
		if ( ! comments_open() && ! get_comments_number() ) {
			return;
		}
		echo '<section class="op-product-reviews" id="reviews">';
		comments_template();
		echo '</section>';
	},
	12
);

// "New" badge for products added in the last 30 days.
function oyepick_is_new( $product ) {
	$created = $product->get_date_created();
	return $created && ( time() - $created->getTimestamp() ) < 30 * DAY_IN_SECONDS;
}

/**
 * First-level category of a product (for the filter tabs) and the deepest label.
 */
function oyepick_product_cats( $product ) {
	$terms  = get_the_terms( $product->get_id(), 'product_cat' );
	$parent = '';
	$label  = '';
	if ( $terms && ! is_wp_error( $terms ) ) {
		foreach ( $terms as $t ) {
			if ( $t->parent ) {
				$label = $t->name;
				$top   = get_term( $t->parent, 'product_cat' );
				$parent = $top && ! is_wp_error( $top ) ? $top->slug : $parent;
			} elseif ( ! $parent ) {
				$parent = $t->slug;
				$label  = $label ? $label : $t->name;
			}
		}
	}
	return array( $parent, $label );
}
