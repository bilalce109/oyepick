<?php
/**
 * REST endpoint feeding the "X from City purchased Y" notification.
 *
 * Only REAL WooCommerce orders are returned, and only a customer's first name + city
 * (no surname, phone, address or email). Mention this in your privacy policy.
 *
 * GET /wp-json/oyepick/v1/recent-orders
 *
 * @package OyePick
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'oyepick/v1',
			'/recent-orders',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => 'oyepick_recent_orders',
			)
		);
	}
);

function oyepick_recent_orders() {
	if ( ! function_exists( 'wc_get_orders' ) || ! oyepick_opt( 'popup_enabled' ) ) {
		return rest_ensure_response( array() );
	}

	$cached = get_transient( 'oyepick_recent_orders' );
	if ( false !== $cached ) {
		return rest_ensure_response( $cached );
	}

	$orders = wc_get_orders(
		array(
			'limit'        => 12,
			'status'       => array( 'wc-processing', 'wc-completed', 'wc-on-hold' ),
			'orderby'      => 'date',
			'order'        => 'DESC',
			'date_created' => '>' . ( time() - 14 * DAY_IN_SECONDS ),
			'type'         => 'shop_order',
		)
	);

	$out = array();
	foreach ( $orders as $order ) {
		$items = $order->get_items();
		$item  = reset( $items );
		if ( ! $item ) {
			continue;
		}
		$product = $item->get_product();
		if ( ! $product ) {
			continue;
		}
		$first = trim( strtok( (string) $order->get_billing_first_name(), ' ' ) );
		$city  = trim( (string) $order->get_billing_city() );
		$img   = wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_thumbnail' );
		$out[] = array(
			'name'    => $first ? $first : __( 'A customer', 'oyepick' ),
			'city'    => $city,
			'product' => wp_strip_all_tags( $product->get_name() ),
			'url'     => get_permalink( $product->get_id() ),
			'image'   => $img ? $img : wc_placeholder_img_src(),
			'mins'    => max( 0, (int) round( ( time() - $order->get_date_created()->getTimestamp() ) / 60 ) ),
		);
	}

	set_transient( 'oyepick_recent_orders', $out, MINUTE_IN_SECONDS );
	return rest_ensure_response( $out );
}

// Fresh data as soon as an order comes in.
add_action( 'woocommerce_new_order', fn() => delete_transient( 'oyepick_recent_orders' ) );
add_action( 'woocommerce_order_status_changed', fn() => delete_transient( 'oyepick_recent_orders' ) );
