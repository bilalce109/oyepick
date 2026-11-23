<?php
/**
 * Homepage.
 *
 * Product prices / add-to-cart in the slider and promo are read live from WooCommerce by SKU,
 * so changing a price in the admin updates the homepage automatically.
 *
 * @package OyePick
 */

defined( 'ABSPATH' ) || exit;
get_header();

$c = 'cutouts/';

$slides = oyepick_get_slides(); // edit in admin: Hero Slides
?>
<main id="main">

<!-- ============ HERO ============ -->
<section class="hero">
	<div class="container">
		<form class="search m-search" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" role="search" style="display:none">
			<input type="search" name="s" placeholder="<?php esc_attr_e( 'Search products…', 'oyepick' ); ?>" aria-label="<?php esc_attr_e( 'Search products', 'oyepick' ); ?>">
			<input type="hidden" name="post_type" value="product">
			<button type="submit" aria-label="<?php esc_attr_e( 'Search', 'oyepick' ); ?>"><?php echo oyepick_icon( 'search', 18 ); // phpcs:ignore ?></button>
		</form>
		<div class="hero-grid">
			<div class="slider" id="slider" aria-roledescription="carousel" aria-label="<?php esc_attr_e( 'Featured products', 'oyepick' ); ?>">
				<?php
				foreach ( $slides as $i => $s ) :
					$p   = $s['product'];
					$tag = 0 === $i ? 'h1' : 'h2';
					?>
					<div class="slide <?php echo esc_attr( $s['theme'] . ( 0 === $i ? ' on' : '' ) ); ?>" aria-roledescription="slide" aria-label="<?php echo esc_attr( ( $i + 1 ) . ' / ' . count( $slides ) ); ?>">
						<svg class="ribbon" aria-hidden="true"><use href="#rib"/></svg>
						<div class="copy">
							<span class="eyebrow"><?php echo esc_html( $s['eyebrow'] ); ?></span>
							<<?php echo $tag; // phpcs:ignore ?>><?php echo esc_html( $s['title'] ); ?></<?php echo $tag; // phpcs:ignore ?>>
							<p><?php echo esc_html( $s['text'] ); ?></p>
							<?php if ( $s['chip'][1] ) : ?><span class="feat"><?php if ( $s['chip'][0] ) : ?><i><?php echo esc_html( $s['chip'][0] ); ?></i><?php endif; ?><?php echo esc_html( $s['chip'][1] ); ?></span><?php endif; ?>
							<?php if ( $p && '' !== $p->get_price() ) : ?>
								<div class="now"><b><?php echo esc_html( oyepick_price( $p ) ); ?></b><?php if ( $s['pill'] ) : ?><span><?php echo esc_html( $s['pill'] ); ?></span><?php endif; ?></div>
							<?php endif; ?>
							<div class="btns">
								<?php if ( $p ) : ?>
									<?php echo oyepick_add_to_cart_button( $p, $s['label'] ); // phpcs:ignore ?>
									<?php if ( $s['link'][1] ) : ?><a href="<?php echo esc_url( $s['link'][0] ); ?>" class="btn btn-outline"><?php echo esc_html( $s['link'][1] ); ?></a><?php endif; ?>
								<?php else : ?>
									<a href="<?php echo esc_url( $s['link'][0] ); ?>" class="btn btn-primary"><?php echo esc_html( $s['link'][1] ? $s['link'][1] : __( 'Shop now', 'oyepick' ) ); ?></a>
								<?php endif; ?>
							</div>
						</div>
						<div class="stage <?php echo esc_attr( $s['stage'] ); ?>">
							<?php foreach ( $s['imgs'] as $cls => $src ) : ?>
								<img class="<?php echo esc_attr( $cls ); ?>" src="<?php echo esc_url( $src ); ?>" alt="<?php echo esc_attr( $s['title'] ); ?>" <?php echo 0 === $i ? 'fetchpriority="high"' : 'loading="lazy"'; ?>>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endforeach; ?>
				<div class="slider-ui">
					<div class="dots" id="dots"></div>
					<div class="arrows">
						<button id="prev" type="button" aria-label="<?php esc_attr_e( 'Previous slide', 'oyepick' ); ?>"><?php echo oyepick_icon( 'left', 18 ); // phpcs:ignore ?></button>
						<button id="next" type="button" aria-label="<?php esc_attr_e( 'Next slide', 'oyepick' ); ?>"><?php echo oyepick_icon( 'right', 18 ); // phpcs:ignore ?></button>
					</div>
				</div>
			</div>

			<div class="hero-side">
				<a href="<?php echo esc_url( oyepick_cat_link( 'mobile-accessories' ) ); ?>" class="tile t2">
					<div><span class="eyebrow"><?php esc_html_e( 'Gadgets', 'oyepick' ); ?></span><h3><?php esc_html_e( 'Mobile Accessories', 'oyepick' ); ?></h3><p><?php esc_html_e( 'Cable kits, holders & chargers.', 'oyepick' ); ?></p></div>
					<span class="link"><?php esc_html_e( 'Shop now →', 'oyepick' ); ?></span>
					<img class="cut" src="<?php echo esc_url( oyepick_img( $c . 'cable-kit-box.webp' ) ); ?>" alt="">
				</a>
				<a href="<?php echo esc_url( oyepick_cat_link( 'apparel-clothing' ) ); ?>" class="tile t1 sandy">
					<div><span class="eyebrow"><?php esc_html_e( 'Apparel', 'oyepick' ); ?></span><h3><?php esc_html_e( 'Men & Women Collection', 'oyepick' ); ?></h3><p><?php esc_html_e( 'Silk, lawn & Boski.', 'oyepick' ); ?></p></div>
					<span class="link"><?php esc_html_e( 'Explore →', 'oyepick' ); ?></span>
					<img class="cut m1" src="<?php echo esc_url( oyepick_img( $c . 'baroque-purple.webp' ) ); ?>" alt="">
					<img class="cut m2" src="<?php echo esc_url( oyepick_img( $c . 'boski-cream.webp' ) ); ?>" alt="">
				</a>
			</div>
		</div>

		<div class="trust">
			<div><span class="ic"><?php echo oyepick_icon( 'cash' ); // phpcs:ignore ?></span><div><h4><?php esc_html_e( 'Cash on Delivery', 'oyepick' ); ?></h4><small><?php esc_html_e( 'Pay when you receive', 'oyepick' ); ?></small></div></div>
			<div><span class="ic"><?php echo oyepick_icon( 'truck' ); // phpcs:ignore ?></span><div><h4><?php esc_html_e( 'Nationwide Delivery', 'oyepick' ); ?></h4><small><?php esc_html_e( 'Fast, all over Pakistan', 'oyepick' ); ?></small></div></div>
			<div><span class="ic"><?php echo oyepick_icon( 'shield' ); // phpcs:ignore ?></span><div><h4><?php esc_html_e( 'Quality Checked', 'oyepick' ); ?></h4><small><?php esc_html_e( 'Every order inspected', 'oyepick' ); ?></small></div></div>
			<div><span class="ic"><?php echo oyepick_icon( 'chat' ); // phpcs:ignore ?></span><div><h4><?php esc_html_e( 'WhatsApp Support', 'oyepick' ); ?></h4><small><?php esc_html_e( 'Quick help, 7 days', 'oyepick' ); ?></small></div></div>
		</div>
	</div>
</section>

<!-- ============ CATEGORIES ============ -->
<?php
$cats = array(
	array( 'apparel-clothing', 'c-apparel sandy', 'APPAREL', array( 'm1' => 'baroque-teal', 'm2' => 'boski-khaki' ), __( 'Shop apparel →', 'oyepick' ) ),
	array( 'gadgets-electronics', 'c-gadgets', 'GADGETS', array( '' => 'nohon-pair' ), __( 'Shop gadgets →', 'oyepick' ) ),
	array( 'home-kitchen', 'c-home', 'KITCHEN', array( '' => 'hand-cutter' ), __( 'Shop home →', 'oyepick' ) ),
);
?>
<section class="section" id="categories">
	<div class="container">
		<div class="sec-head reveal">
			<div><span class="eyebrow"><?php esc_html_e( 'Browse', 'oyepick' ); ?></span><h2><?php esc_html_e( 'Shop by category', 'oyepick' ); ?></h2></div>
			<a href="<?php echo esc_url( oyepick_shop_url() ); ?>"><?php esc_html_e( 'View all products →', 'oyepick' ); ?></a>
		</div>
		<div class="cats">
			<?php
			foreach ( $cats as $cat ) :
				$term = get_term_by( 'slug', $cat[0], 'product_cat' );
				if ( ! $term ) {
					continue;
				}
				$kids  = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => $term->term_id, 'hide_empty' => false ) );
				$count = 0;
				$ids   = get_term_children( $term->term_id, 'product_cat' );
				$q     = new WP_Query( array( 'post_type' => 'product', 'post_status' => 'publish', 'fields' => 'ids', 'posts_per_page' => -1, 'tax_query' => array( array( 'taxonomy' => 'product_cat', 'terms' => array_merge( array( $term->term_id ), $ids ) ) ) ) ); // phpcs:ignore
				$count = $q->found_posts;
				?>
				<a href="<?php echo esc_url( get_term_link( $term ) ); ?>" class="cat <?php echo esc_attr( $cat[1] ); ?> reveal">
					<span class="bgword" aria-hidden="true"><span><?php echo esc_html( $cat[2] ); ?></span><span><?php echo esc_html( $cat[2] ); ?></span><span><?php echo esc_html( $cat[2] ); ?></span></span>
					<span class="count"><?php echo esc_html( sprintf( _n( '%d product', '%d products', $count, 'oyepick' ), $count ) ); ?></span>
					<h3><?php echo esc_html( $term->name ); ?></h3>
					<?php if ( $kids && ! is_wp_error( $kids ) ) : ?>
						<ul><?php foreach ( $kids as $k ) : ?><li><span class="chip"><?php echo esc_html( $k->name ); ?></span></li><?php endforeach; ?></ul>
					<?php endif; ?>
					<span class="go"><?php echo esc_html( $cat[4] ); ?></span>
					<?php foreach ( $cat[3] as $cls => $file ) : ?>
						<img class="cut <?php echo esc_attr( $cls ); ?>" src="<?php echo esc_url( oyepick_img( $c . $file . '.webp' ) ); ?>" alt="" loading="lazy">
					<?php endforeach; ?>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<!-- ============ TRENDING PRODUCTS ============ -->
<?php
$products = function_exists( 'wc_get_products' ) ? wc_get_products( array( 'status' => 'publish', 'limit' => 12, 'orderby' => 'menu_order', 'order' => 'ASC', 'visibility' => 'catalog' ) ) : array();
?>
<section class="section" id="trending" style="padding-top:0">
	<div class="container">
		<div class="sec-head reveal">
			<div><span class="eyebrow"><?php esc_html_e( 'Hot right now', 'oyepick' ); ?></span><h2><?php esc_html_e( 'Trending products', 'oyepick' ); ?></h2></div>
			<div class="tabs" role="tablist">
				<button class="on" type="button" data-f="all"><?php esc_html_e( 'All', 'oyepick' ); ?></button>
				<button type="button" data-f="gadgets-electronics"><?php esc_html_e( 'Gadgets', 'oyepick' ); ?></button>
				<button type="button" data-f="apparel-clothing"><?php esc_html_e( 'Apparel', 'oyepick' ); ?></button>
				<button type="button" data-f="home-kitchen"><?php esc_html_e( 'Home & Kitchen', 'oyepick' ); ?></button>
			</div>
		</div>
		<div class="op-grid" id="products">
			<?php
			foreach ( $products as $prod ) {
				$GLOBALS['product'] = $prod; // phpcs:ignore
				$GLOBALS['post']    = get_post( $prod->get_id() ); // phpcs:ignore
				setup_postdata( $GLOBALS['post'] );
				get_template_part( 'template-parts/product-card' );
			}
			wp_reset_postdata();
			?>
		</div>
		<div style="text-align:center;margin-top:40px"><a href="<?php echo esc_url( oyepick_shop_url() ); ?>" class="btn btn-primary"><?php esc_html_e( 'View all products', 'oyepick' ); ?></a></div>
	</div>
</section>

<!-- ============ PROMO ============ -->
<?php $k8 = oyepick_product( 'OP-K8-66W' ); ?>
<section class="section" style="padding-top:0">
	<div class="container">
		<div class="promo reveal">
			<div class="txt">
				<span class="eyebrow"><?php esc_html_e( 'Power up', 'oyepick' ); ?></span>
				<h2><?php esc_html_e( 'Never run out of charge again', 'oyepick' ); ?></h2>
				<p><?php esc_html_e( 'The FoneStop K8 66W power bank charges your phone fast, with 20,000mAh capacity to keep you going all week.', 'oyepick' ); ?></p>
				<div class="stats">
					<div><b>66W</b><span><?php esc_html_e( 'Fast charging', 'oyepick' ); ?></span></div>
					<div><b>20,000</b><span><?php esc_html_e( 'mAh capacity', 'oyepick' ); ?></span></div>
					<?php if ( $k8 && '' !== $k8->get_price() ) : ?><div><b><?php echo esc_html( oyepick_price( $k8 ) ); ?></b><span><?php esc_html_e( 'Cash on delivery', 'oyepick' ); ?></span></div><?php endif; ?>
				</div>
				<?php echo oyepick_add_to_cart_button( $k8, __( 'Add to cart', 'oyepick' ), 'btn btn-light' ); // phpcs:ignore ?>
			</div>
			<div class="pics" aria-hidden="true"><img class="k8" src="<?php echo esc_url( oyepick_img( $c . 'fonestop-k8-box.webp' ) ); ?>" alt="" loading="lazy"></div>
		</div>
	</div>
</section>

<!-- ============ REVIEWS ============ -->
<?php get_template_part( 'template-parts/reviews' ); ?>

<!-- ============ WHY US ============ -->
<section class="section" style="padding-top:0">
	<div class="container">
		<div class="sec-head reveal"><div><span class="eyebrow"><?php esc_html_e( 'Why OyePick', 'oyepick' ); ?></span><h2><?php esc_html_e( 'Shopping made simple & safe', 'oyepick' ); ?></h2></div></div>
		<div class="why">
			<div class="reveal"><span class="ic"><?php echo oyepick_icon( 'cash', 24 ); // phpcs:ignore ?></span><h3><?php esc_html_e( 'Pay on delivery', 'oyepick' ); ?></h3><p><?php esc_html_e( 'No advance payment needed. Check your parcel and pay in cash at your door.', 'oyepick' ); ?></p></div>
			<div class="reveal"><span class="ic"><?php echo oyepick_icon( 'star', 24 ); // phpcs:ignore ?></span><h3><?php esc_html_e( 'Handpicked products', 'oyepick' ); ?></h3><p><?php esc_html_e( 'We only stock items we would use ourselves, from trending fashion to everyday gadgets.', 'oyepick' ); ?></p></div>
			<div class="reveal"><span class="ic"><?php echo oyepick_icon( 'truck', 24 ); // phpcs:ignore ?></span><h3><?php esc_html_e( 'Fast nationwide shipping', 'oyepick' ); ?></h3><p><?php esc_html_e( 'Orders dispatched quickly from Karachi to cities all over Pakistan.', 'oyepick' ); ?></p></div>
			<?php if ( oyepick_opt( 'address' ) ) : ?>
				<div class="reveal"><span class="ic"><?php echo oyepick_icon( 'pin', 24 ); // phpcs:ignore ?></span><h3><?php esc_html_e( 'Visit our shop', 'oyepick' ); ?></h3><p><?php echo esc_html( oyepick_opt( 'address' ) ); ?></p></div>
			<?php else : ?>
				<div class="reveal"><span class="ic"><?php echo oyepick_icon( 'chat', 24 ); // phpcs:ignore ?></span><h3><?php esc_html_e( 'Help on WhatsApp', 'oyepick' ); ?></h3><p><?php esc_html_e( 'Questions about size, colour or delivery? Message us on WhatsApp and we reply quickly.', 'oyepick' ); ?></p></div>
			<?php endif; ?>
		</div>
	</div>
</section>

<!-- ============ CONNECT ============ -->
<section class="section" style="padding-top:0">
	<div class="container connect">
		<div class="box follow reveal">
			<span class="eyebrow"><?php esc_html_e( 'Follow us', 'oyepick' ); ?></span>
			<h2><?php esc_html_e( 'See new drops first', 'oyepick' ); ?></h2>
			<p><?php esc_html_e( 'New arrivals, unboxings and offers. Follow OyePick on your favourite platform.', 'oyepick' ); ?></p>
			<div class="soc-row">
				<?php
				$names = array( 'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'facebook' => 'Facebook' );
				foreach ( $names as $k => $label ) :
					if ( ! oyepick_opt( $k ) ) {
						continue;
					}
					?>
					<a href="<?php echo esc_url( oyepick_opt( $k ) ); ?>" target="_blank" rel="noopener"><?php echo oyepick_social_icon( $k, 20 ); // phpcs:ignore ?><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</div>
		</div>
		<div class="box wa reveal">
			<span class="eyebrow"><?php esc_html_e( 'Need help?', 'oyepick' ); ?></span>
			<h2><?php esc_html_e( 'Order on WhatsApp', 'oyepick' ); ?></h2>
			<p><?php esc_html_e( "Have a question about a product, size or delivery? Message us and we'll reply quickly.", 'oyepick' ); ?></p>
			<a href="<?php echo esc_url( oyepick_wa_link() ); ?>" class="btn wa-btn" target="_blank" rel="noopener"><?php echo oyepick_social_icon( 'whatsapp', 20 ); // phpcs:ignore ?><?php esc_html_e( 'WhatsApp us', 'oyepick' ); ?></a>
		</div>
	</div>
</section>
</main>
<?php
get_footer();
