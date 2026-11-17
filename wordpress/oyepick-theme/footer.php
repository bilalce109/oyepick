<?php
/**
 * Footer, cart drawer, mobile menu, WhatsApp button and purchase notification.
 *
 * @package OyePick
 */

defined( 'ABSPATH' ) || exit;
$cart_count = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
$address    = oyepick_opt( 'address' );
?>
<footer class="site-footer">
	<div class="container">
		<div class="foot-grid">
			<div>
				<?php echo oyepick_logo( 'white' ); // phpcs:ignore ?>
				<p style="margin-top:14px;max-width:320px"><?php esc_html_e( 'Trending styles, tech accessories and home essentials. Cash on delivery, fast delivery all over Pakistan.', 'oyepick' ); ?></p>
				<div class="f-soc">
					<?php foreach ( array( 'instagram', 'facebook', 'tiktok' ) as $k ) : ?>
						<?php if ( oyepick_opt( $k ) ) : ?>
							<a href="<?php echo esc_url( oyepick_opt( $k ) ); ?>" aria-label="<?php echo esc_attr( ucfirst( $k ) ); ?>" target="_blank" rel="noopener"><?php echo oyepick_social_icon( $k ); // phpcs:ignore ?></a>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			</div>
			<div>
				<h5><?php esc_html_e( 'Shop', 'oyepick' ); ?></h5>
				<ul>
					<li><a href="<?php echo esc_url( oyepick_shop_url() ); ?>"><?php esc_html_e( 'All products', 'oyepick' ); ?></a></li>
					<li><a href="<?php echo esc_url( oyepick_cat_link( 'apparel-clothing' ) ); ?>"><?php esc_html_e( 'Apparel & Clothing', 'oyepick' ); ?></a></li>
					<li><a href="<?php echo esc_url( oyepick_cat_link( 'gadgets-electronics' ) ); ?>"><?php esc_html_e( 'Gadgets & Electronics', 'oyepick' ); ?></a></li>
					<li><a href="<?php echo esc_url( oyepick_cat_link( 'home-kitchen' ) ); ?>"><?php esc_html_e( 'Home & Kitchen', 'oyepick' ); ?></a></li>
				</ul>
			</div>
			<div>
				<h5><?php esc_html_e( 'Information', 'oyepick' ); ?></h5>
				<?php
				if ( has_nav_menu( 'footer' ) ) {
					wp_nav_menu( array( 'theme_location' => 'footer', 'container' => false, 'depth' => 1 ) );
				} else {
					echo '<ul>';
					foreach ( array( 'about-us' => __( 'About us', 'oyepick' ), 'contact-us' => __( 'Contact us', 'oyepick' ), 'privacy-policy' => __( 'Privacy Policy', 'oyepick' ) ) as $slug => $label ) {
						$page = get_page_by_path( $slug );
						if ( $page ) {
							echo '<li><a href="' . esc_url( get_permalink( $page ) ) . '">' . esc_html( $label ) . '</a></li>';
						}
					}
					if ( function_exists( 'wc_get_page_permalink' ) ) {
						echo '<li><a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '">' . esc_html__( 'My account', 'oyepick' ) . '</a></li>';
					}
					echo '</ul>';
				}
				?>
			</div>
			<div>
				<h5><?php esc_html_e( 'Visit our store', 'oyepick' ); ?></h5>
				<?php if ( $address ) : ?>
					<a class="addr" href="<?php echo esc_url( 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $address ) ); ?>" target="_blank" rel="noopener">
						<?php echo oyepick_icon( 'pin', 18 ); // phpcs:ignore ?><span><?php echo esc_html( $address ); ?></span>
					</a>
				<?php endif; ?>
				<div class="pay"><span><?php esc_html_e( 'Cash on Delivery', 'oyepick' ); ?></span><span><?php esc_html_e( 'Nationwide Shipping', 'oyepick' ); ?></span></div>
			</div>
		</div>
		<div class="foot-bottom">
			<span>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'All rights reserved.', 'oyepick' ); ?></span>
			<?php if ( get_privacy_policy_url() ) : ?><span><a href="<?php echo esc_url( get_privacy_policy_url() ); ?>"><?php esc_html_e( 'Privacy Policy', 'oyepick' ); ?></a></span><?php endif; ?>
		</div>
	</div>
</footer>

<a href="<?php echo esc_url( oyepick_wa_link() ); ?>" class="float-wa" aria-label="<?php esc_attr_e( 'Chat on WhatsApp', 'oyepick' ); ?>" target="_blank" rel="noopener"><?php echo oyepick_social_icon( 'whatsapp', 28 ); // phpcs:ignore ?></a>

<div class="sale-pop" id="salePop" role="status" aria-live="polite"></div>

<div class="overlay" id="overlay"></div>
<aside class="drawer" id="drawer" aria-label="<?php esc_attr_e( 'Shopping cart', 'oyepick' ); ?>">
	<div class="dh">
		<h3><?php esc_html_e( 'Your cart', 'oyepick' ); ?> <span class="op-drawer-count">(<?php echo (int) $cart_count; ?>)</span></h3>
		<button class="icon-btn" id="closeCart" type="button" aria-label="<?php esc_attr_e( 'Close cart', 'oyepick' ); ?>"><?php echo oyepick_icon( 'close' ); // phpcs:ignore ?></button>
	</div>
	<div class="drawer-body">
		<div class="widget_shopping_cart_content"><?php if ( function_exists( 'woocommerce_mini_cart' ) ) { woocommerce_mini_cart(); } ?></div>
	</div>
	<p class="drawer-note"><?php esc_html_e( 'Shipping calculated at checkout. Pay cash on delivery.', 'oyepick' ); ?></p>
</aside>

<aside class="mnav" id="mnav" aria-label="<?php esc_attr_e( 'Mobile menu', 'oyepick' ); ?>">
	<?php echo oyepick_logo(); // phpcs:ignore ?>
	<form class="search" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" role="search" style="margin:18px 0 6px">
		<input type="search" name="s" placeholder="<?php esc_attr_e( 'Search products…', 'oyepick' ); ?>" aria-label="<?php esc_attr_e( 'Search products', 'oyepick' ); ?>">
		<input type="hidden" name="post_type" value="product">
		<button type="submit" aria-label="<?php esc_attr_e( 'Search', 'oyepick' ); ?>"><?php echo oyepick_icon( 'search', 18 ); // phpcs:ignore ?></button>
	</form>
	<?php
	if ( has_nav_menu( 'primary' ) ) {
		wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'menu_class' => 'm-menu', 'depth' => 2 ) );
	} else {
		oyepick_fallback_menu( 'm-menu' );
	}
	?>
	<div class="f-soc" style="margin-top:22px">
		<?php foreach ( array( 'instagram', 'facebook', 'tiktok' ) as $k ) : ?>
			<?php if ( oyepick_opt( $k ) ) : ?><a href="<?php echo esc_url( oyepick_opt( $k ) ); ?>" aria-label="<?php echo esc_attr( ucfirst( $k ) ); ?>" target="_blank" rel="noopener"><?php echo oyepick_social_icon( $k ); // phpcs:ignore ?></a><?php endif; ?>
		<?php endforeach; ?>
	</div>
</aside>

<?php wp_footer(); ?>
</body>
</html>
