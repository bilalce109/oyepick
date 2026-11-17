<?php
/**
 * OyePick Store theme.
 *
 * @package OyePick
 */

defined( 'ABSPATH' ) || exit;

define( 'OYEPICK_VERSION', '1.1.5' );
define( 'OYEPICK_URI', get_template_directory_uri() );
define( 'OYEPICK_DIR', get_template_directory() );

require OYEPICK_DIR . '/inc/helpers.php';
require OYEPICK_DIR . '/inc/customizer.php';
require OYEPICK_DIR . '/inc/recent-orders.php';
require OYEPICK_DIR . '/inc/woocommerce.php';

/**
 * Theme supports & menus.
 */
function oyepick_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 64,
			'width'       => 264,
			'flex-width'  => true,
			'flex-height' => true,
		)
	);
	add_theme_support( 'woocommerce', array( 'thumbnail_image_width' => 600, 'single_image_width' => 900 ) );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	register_nav_menus(
		array(
			'primary' => __( 'Main menu', 'oyepick' ),
			'footer'  => __( 'Footer menu', 'oyepick' ),
		)
	);
}
add_action( 'after_setup_theme', 'oyepick_setup' );

/**
 * Styles & scripts.
 */
function oyepick_assets() {
	wp_enqueue_style( 'oyepick-fonts', 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap', array(), null );
	wp_enqueue_style( 'oyepick', OYEPICK_URI . '/assets/css/theme.css', array(), OYEPICK_VERSION );
	wp_enqueue_style( 'oyepick-wc', OYEPICK_URI . '/assets/css/woocommerce.css', array( 'oyepick' ), OYEPICK_VERSION );

	$deps = array( 'jquery' );
	if ( class_exists( 'WooCommerce' ) ) {
		// Since WooCommerce 7.8 cart fragments are not loaded everywhere; the header cart + drawer need them.
		wp_enqueue_script( 'wc-cart-fragments' );
		wp_enqueue_script( 'wc-add-to-cart' );
		$deps[] = 'wc-add-to-cart';
	}
	wp_enqueue_script( 'oyepick', OYEPICK_URI . '/assets/js/theme.js', $deps, OYEPICK_VERSION, true );
	wp_localize_script(
		'oyepick',
		'OYEPICK',
		array(
			'ordersUrl'    => esc_url_raw( rest_url( 'oyepick/v1/recent-orders' ) ),
			'popup'        => (bool) oyepick_opt( 'popup_enabled' ),
			'interval'     => max( 10, (int) oyepick_opt( 'popup_interval' ) ),
			'firstDelay'   => 6,
			'i18n'         => array(
				'from'      => __( 'from', 'oyepick' ),
				'purchased' => __( 'purchased', 'oyepick' ),
				'minsAgo'   => __( 'minutes ago', 'oyepick' ),
				'justNow'   => __( 'just now', 'oyepick' ),
				'hoursAgo'  => __( 'hours ago', 'oyepick' ),
				'verified'  => __( 'Verified order', 'oyepick' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'oyepick_assets', 20 ); // after WooCommerce registers its scripts

/**
 * Favicon fallback when no Site Icon is set in the Customizer.
 */
function oyepick_favicon() {
	if ( has_site_icon() ) {
		return;
	}
	$b = OYEPICK_URI . '/assets/images/brand/';
	echo '<link rel="icon" href="' . esc_url( $b . 'oyepick-mark.svg' ) . '" type="image/svg+xml">' . "\n";
	echo '<link rel="icon" href="' . esc_url( $b . 'favicon-32.png' ) . '" sizes="32x32">' . "\n";
	echo '<link rel="apple-touch-icon" href="' . esc_url( $b . 'apple-touch-icon.png' ) . '">' . "\n";
}
add_action( 'wp_head', 'oyepick_favicon', 2 );

function oyepick_theme_color() {
	echo '<meta name="theme-color" content="#1d5c63">' . "\n";
}
add_action( 'wp_head', 'oyepick_theme_color', 1 );
