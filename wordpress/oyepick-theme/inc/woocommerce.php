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

/* ---------- Single product: richer summary ---------- */

// No duplicate "Description" / "Additional information" headings inside the tabs.
add_filter( 'woocommerce_product_description_heading', '__return_empty_string' );
add_filter( 'woocommerce_product_additional_information_heading', '__return_empty_string' );

// Colour chips (from the product's "Colour" attribute) under the price.
add_action(
	'woocommerce_single_product_summary',
	function () {
		global $product;
		$colours = $product ? $product->get_attribute( 'Colour' ) : '';
		if ( ! $colours ) {
			return;
		}
		echo '<div class="op-colours"><span>' . esc_html__( 'Available colours', 'oyepick' ) . '</span><ul>';
		foreach ( array_filter( array_map( 'trim', preg_split( '/[|,]/', $colours ) ) ) as $c ) {
			echo '<li>' . esc_html( $c ) . '</li>';
		}
		echo '</ul></div>';
	},
	25
);

// WhatsApp order button + trust points after "Add to cart".
add_action(
	'woocommerce_single_product_summary',
	function () {
		global $product;
		if ( ! $product ) {
			return;
		}
		$msg = sprintf( 'Assalam o Alaikum, I want to order: %s (%s)', $product->get_name(), get_permalink( $product->get_id() ) );
		echo '<a class="op-wa-order" href="' . esc_url( oyepick_wa_link( $msg ) ) . '" target="_blank" rel="noopener">' . oyepick_social_icon( 'whatsapp', 20 ) . esc_html__( 'Order on WhatsApp', 'oyepick' ) . '</a>'; // phpcs:ignore
		echo '<ul class="op-trust-mini">';
		echo '<li>' . oyepick_icon( 'cash', 20 ) . '<div><b>' . esc_html__( 'Cash on Delivery', 'oyepick' ) . '</b><small>' . esc_html__( 'Pay when you receive', 'oyepick' ) . '</small></div></li>'; // phpcs:ignore
		echo '<li>' . oyepick_icon( 'truck', 20 ) . '<div><b>' . esc_html__( 'Delivery all over Pakistan', 'oyepick' ) . '</b><small>' . esc_html__( 'Fast dispatch from Karachi', 'oyepick' ) . '</small></div></li>'; // phpcs:ignore
		echo '<li>' . oyepick_icon( 'shield', 20 ) . '<div><b>' . esc_html__( 'Quality checked', 'oyepick' ) . '</b><small>' . esc_html__( 'Every order inspected', 'oyepick' ) . '</small></div></li>'; // phpcs:ignore
		echo '</ul>';
	},
	32
);

// Always show 4 related products (fill with other products when a category is small).
add_filter(
	'woocommerce_related_products',
	function ( $related, $product_id ) {
		if ( count( $related ) >= 4 ) {
			return $related;
		}
		$more = wc_get_products(
			array(
				'status'  => 'publish',
				'limit'   => 4 - count( $related ),
				'exclude' => array_merge( array( $product_id ), $related ),
				'orderby' => 'rand',
				'return'  => 'ids',
			)
		);
		return array_merge( $related, $more );
	},
	10,
	2
);

/* ---------- Single product: soft product-image backdrop behind the top section ---------- */
add_action(
	'woocommerce_before_single_product',
	function () {
		global $product;
		if ( ! $product || ! $product->get_image_id() ) {
			return;
		}
		// Optional: a specific (more colourful) gallery photo chosen for the backdrop.
		$bg_id = (int) get_post_meta( $product->get_id(), '_oyepick_bg_id', true );
		$src   = wp_get_attachment_image_url( $bg_id ? $bg_id : $product->get_image_id(), 'large' );
		if ( $src ) {
			echo '<div class="op-product-bg" aria-hidden="true"><span style="background-image:url(' . esc_url( $src ) . ')"></span></div>';
		}
	},
	1
);

/**
 * Hide the big "Shop" heading on the main shop page (category pages keep their name).
 */
add_filter(
	'woocommerce_show_page_title',
	function ( $show ) {
		return is_shop() ? false : $show;
	}
);
