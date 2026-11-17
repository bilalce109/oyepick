<?php
/**
 * Header.
 *
 * @package OyePick
 */

defined( 'ABSPATH' ) || exit;
$cart_count = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
$account    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<svg width="0" height="0" style="position:absolute" aria-hidden="true">
	<symbol id="rib" viewBox="0 0 700 560" preserveAspectRatio="xMidYMid slice">
		<path d="M-60 520 C 110 520 150 330 300 318 S 520 420 568 262 S 640 40 780 26" fill="none" stroke="currentColor" stroke-width="66" stroke-linecap="round" opacity=".92"/>
		<path d="M-60 506 C 110 506 150 316 300 304 S 520 406 568 248 S 640 26 780 12" fill="none" stroke="#fff" stroke-width="10" stroke-linecap="round" opacity=".28"/>
		<path d="M40 150 C 190 70 300 230 410 160 S 600 20 720 110" fill="none" stroke="currentColor" stroke-width="2.5" opacity=".55"/>
		<circle cx="40" cy="150" r="8" fill="currentColor" opacity=".8"/>
		<circle cx="640" cy="64" r="6" fill="currentColor" opacity=".7"/>
		<circle cx="610" cy="500" r="9" fill="currentColor" opacity=".5"/>
	</symbol>
</svg>

<div class="topbar">
	<div class="container">
		<div class="msg">
			<span><?php echo oyepick_icon( 'cash', 15 ); // phpcs:ignore ?><?php echo esc_html( oyepick_opt( 'topbar_left' ) ); ?></span>
			<span><?php echo oyepick_icon( 'truck', 15 ); // phpcs:ignore ?><?php echo esc_html( oyepick_opt( 'topbar_right' ) ); ?></span>
		</div>
		<div class="social">
			<?php foreach ( array( 'instagram' => 'Instagram', 'facebook' => 'Facebook', 'tiktok' => 'TikTok' ) as $k => $label ) : ?>
				<?php if ( oyepick_opt( $k ) ) : ?><a href="<?php echo esc_url( oyepick_opt( $k ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $label ); ?></a><?php endif; ?>
			<?php endforeach; ?>
		</div>
	</div>
</div>

<header class="site-header">
	<div class="container head">
		<button class="icon-btn menu-btn" id="menuBtn" type="button" aria-label="<?php esc_attr_e( 'Open menu', 'oyepick' ); ?>"><?php echo oyepick_icon( 'menu', 24 ); // phpcs:ignore ?></button>
		<?php echo oyepick_logo(); // phpcs:ignore ?>
		<form class="search search-desk" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" role="search">
			<input type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search for chargers, suits, earbuds…', 'oyepick' ); ?>" aria-label="<?php esc_attr_e( 'Search products', 'oyepick' ); ?>">
			<input type="hidden" name="post_type" value="product">
			<button type="submit" aria-label="<?php esc_attr_e( 'Search', 'oyepick' ); ?>"><?php echo oyepick_icon( 'search', 18 ); // phpcs:ignore ?></button>
		</form>
		<div class="head-icons">
			<a href="<?php echo esc_url( $account ); ?>" class="icon-btn" aria-label="<?php esc_attr_e( 'My account', 'oyepick' ); ?>"><?php echo oyepick_icon( 'user' ); // phpcs:ignore ?></a>
			<button class="icon-btn" id="cartBtn" type="button" aria-label="<?php esc_attr_e( 'Open cart', 'oyepick' ); ?>"><?php echo oyepick_icon( 'cart' ); // phpcs:ignore ?><span class="badge op-cart-count"><?php echo (int) $cart_count; ?></span></button>
		</div>
	</div>
	<nav class="nav" aria-label="<?php esc_attr_e( 'Main', 'oyepick' ); ?>">
		<div class="container">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'menu',
						'depth'          => 2,
						'fallback_cb'    => false,
					)
				);
			} else {
				oyepick_fallback_menu();
			}
			?>
			<a class="pill-link" href="<?php echo esc_url( oyepick_wa_link() ); ?>" target="_blank" rel="noopener"><?php echo oyepick_icon( 'bolt', 16 ); // phpcs:ignore ?><?php esc_html_e( 'Order on WhatsApp', 'oyepick' ); ?></a>
		</div>
	</nav>
</header>
