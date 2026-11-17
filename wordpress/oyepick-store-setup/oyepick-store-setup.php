<?php
/**
 * Plugin Name: OyePick Store Setup
 * Description: One-click setup for the OyePick store: WooCommerce settings (PKR, Cash on Delivery, reviews), product categories, the 12 OyePick products with photos, main menu and basic pages. Safe to run more than once — existing products (same SKU or name) are skipped.
 * Version: 1.0.0
 * Requires Plugins: woocommerce
 * Author: OyePick
 * License: GPL-2.0-or-later
 * Text Domain: oyepick-setup
 *
 * @package OyePickSetup
 */

defined( 'ABSPATH' ) || exit;

define( 'OYEPICK_SETUP_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Run the setup. Returns a log array.
 *
 * @param array $args { demo_orders: bool — create a few clearly-labelled test orders (demo sites only). }
 */
function oyepick_setup_run( $args = array() ) {
	$log = array();
	if ( ! class_exists( 'WooCommerce' ) ) {
		return array( 'WooCommerce is not active — install & activate it first.' );
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	/* ---------- WooCommerce settings ---------- */
	$opts = array(
		'woocommerce_currency'                   => 'PKR',
		'woocommerce_currency_pos'               => 'left_space',
		'woocommerce_price_thousand_sep'         => ',',
		'woocommerce_price_decimal_sep'          => '.',
		'woocommerce_price_num_decimals'         => '0',
		'woocommerce_default_country'            => 'PK:SD',
		'woocommerce_store_city'                 => 'Karachi',
		'woocommerce_allowed_countries'          => 'specific',
		'woocommerce_specific_allowed_countries' => array( 'PK' ),
		'woocommerce_ship_to_countries'          => '',
		'woocommerce_enable_reviews'             => 'yes',
		'woocommerce_enable_review_rating'       => 'yes',
		'woocommerce_review_rating_required'     => 'yes',
		'woocommerce_review_rating_verification_label' => 'yes',
		'woocommerce_enable_ajax_add_to_cart'    => 'yes',
		'woocommerce_cart_redirect_after_add'    => 'no',
		'woocommerce_enable_guest_checkout'      => 'yes',
		'woocommerce_calc_taxes'                 => 'no',
		'woocommerce_coming_soon'                => 'no',
		'woocommerce_store_pages_only'           => 'no',
		'woocommerce_task_list_hidden'           => 'yes',
	);
	foreach ( $opts as $k => $v ) {
		update_option( $k, $v );
	}
	$cod = get_option( 'woocommerce_cod_settings', array() );
	update_option(
		'woocommerce_cod_settings',
		array_merge(
			(array) $cod,
			array(
				'enabled'      => 'yes',
				'title'        => 'Cash on Delivery',
				'description'  => 'Pay with cash when your parcel arrives.',
				'instructions' => 'Pay with cash upon delivery.',
			)
		)
	);
	update_option( 'woocommerce_onboarding_profile', array( 'skipped' => true, 'completed' => true ) );
	update_option( 'woocommerce_ship_to_destination', 'billing_only' );
	update_option( 'woocommerce_checkout_phone_field', 'required' );
	update_option( 'woocommerce_checkout_company_field', 'hidden' );
	// Shipping zone for Pakistan (checkout needs at least one shipping method). Set real delivery charges in WooCommerce → Settings → Shipping.
	$has_pk = false;
	foreach ( WC_Shipping_Zones::get_zones() as $z ) {
		foreach ( $z['zone_locations'] as $loc ) {
			if ( 'PK' === $loc->code ) {
				$has_pk = true;
			}
		}
	}
	if ( ! $has_pk ) {
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'Pakistan' );
		$zone->add_location( 'PK', 'country' );
		$zone->save();
		$iid = $zone->add_shipping_method( 'flat_rate' );
		update_option( 'woocommerce_flat_rate_' . $iid . '_settings', array( 'title' => 'Delivery all over Pakistan', 'tax_status' => 'none', 'cost' => '0' ) );
		$log[] = 'Shipping zone "Pakistan" added (delivery charge Rs 0 — change it in WooCommerce → Settings → Shipping).';
	}
	$log[] = 'WooCommerce: PKR currency, Cash on Delivery on, reviews + ratings on, AJAX add to cart on.';

	/* ---------- Categories ---------- */
	$tree = array(
		'apparel-clothing'    => array( 'Apparel & Clothing', array( 'men' => 'Men', 'women' => 'Women' ) ),
		'gadgets-electronics' => array( 'Gadgets & Electronics', array( 'audio' => 'Audio', 'chargers-power-banks' => 'Chargers & Power Banks', 'mobile-accessories' => 'Mobile Accessories', 'personal-care' => 'Personal Care' ) ),
		'home-kitchen'        => array( 'Home & Kitchen', array( 'kitchen-tools' => 'Kitchen Tools' ) ),
	);
	$cat_ids = array();
	foreach ( $tree as $slug => $node ) {
		$parent            = oyepick_setup_term( $node[0], $slug, 0 );
		$cat_ids[ $slug ]  = $parent;
		foreach ( $node[1] as $cslug => $cname ) {
			$cat_ids[ $cslug ] = oyepick_setup_term( $cname, $cslug, $parent );
		}
	}
	$log[] = 'Categories ready: ' . count( $cat_ids ) . '.';

	/* ---------- Products ---------- */
	$products = include OYEPICK_SETUP_DIR . 'data/products.php';
	$made     = 0;
	$skipped  = 0;
	foreach ( $products as $order => $p ) {
		$existing = wc_get_product_id_by_sku( $p['sku'] );
		if ( ! $existing ) {
			$same = get_posts( array( 'post_type' => 'product', 'title' => $p['name'], 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) );
			$existing = $same ? $same[0] : 0;
		}
		if ( $existing ) {
			$skipped++;
			continue;
		}

		$prod = new WC_Product_Simple();
		$prod->set_name( $p['name'] );
		$prod->set_sku( $p['sku'] );
		$prod->set_status( 'publish' );
		$prod->set_catalog_visibility( 'visible' );
		$prod->set_menu_order( $order );
		$prod->set_featured( ! empty( $p['featured'] ) );
		$prod->set_short_description( $p['short'] );
		$prod->set_description( $p['desc'] );
		$prod->set_reviews_allowed( true );
		$prod->set_stock_status( 'instock' );
		if ( '' !== $p['price'] ) {
			$prod->set_regular_price( $p['price'] );
		}
		$prod->set_category_ids( array_map( fn( $c ) => $cat_ids[ $c ], $p['cats'] ) );

		if ( ! empty( $p['colors'] ) ) {
			$attr = new WC_Product_Attribute();
			$attr->set_name( 'Colour' );
			$attr->set_options( $p['colors'] );
			$attr->set_visible( true );
			$attr->set_variation( false );
			$prod->set_attributes( array( $attr ) );
		}
		$id = $prod->save();

		$images = glob( OYEPICK_SETUP_DIR . 'products/' . $p['folder'] . '/*.jpg' );
		natsort( $images );
		$att = array();
		foreach ( array_values( $images ) as $i => $file ) {
			$aid = oyepick_setup_attach( $file, $id, $p['name'] . ( $i ? ' ' . ( $i + 1 ) : '' ) );
			if ( $aid ) {
				$att[] = $aid;
			}
		}
		if ( $att ) {
			$prod = wc_get_product( $id );
			$prod->set_image_id( array_shift( $att ) );
			$prod->set_gallery_image_ids( $att );
			$prod->save();
		}
		$made++;
	}
	$log[] = "Products created: $made, skipped (already existed): $skipped.";

	/* ---------- Pages ---------- */
	$pages = array(
		'about-us'   => array( 'About us', '<p>OyePick is an online store from Karachi bringing you trending styles, tech accessories and home essentials — with cash on delivery and fast delivery all over Pakistan.</p><p>Visit us: Shop No. 1, Maheen Arcade, BMCHS, Bahadurabad, Shaheed-e-Millat Road, Karachi.</p>' ),
		'contact-us' => array( 'Contact us', '<p><strong>WhatsApp:</strong> <a href="https://wa.me/923233901906">0323 3901906</a></p><p><strong>Address:</strong> Shop No. 1, Maheen Arcade, BMCHS, Bahadurabad, Shaheed-e-Millat Road, Karachi</p><p>Follow us on <a href="https://www.instagram.com/oyepicstore">Instagram</a>, <a href="https://www.tiktok.com/@oyepick13">TikTok</a> and <a href="https://www.facebook.com/share/1A73b7x7w9/">Facebook</a>.</p>' ),
	);
	foreach ( $pages as $slug => $pg ) {
		if ( ! get_page_by_path( $slug ) ) {
			wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $pg[0], 'post_content' => $pg[1] ) );
		}
	}
	// WooCommerce core pages (shop, cart, checkout, my account).
	if ( class_exists( 'WC_Install' ) ) {
		WC_Install::create_pages();
	}
	update_option( 'show_on_front', 'posts' ); // the theme's front-page.php renders the homepage.
	$log[] = 'Pages ready (About, Contact, Shop, Cart, Checkout, My account).';

	/* ---------- Main menu ---------- */
	if ( ! wp_get_nav_menu_object( 'Main menu' ) ) {
		$menu_id = wp_create_nav_menu( 'Main menu' );
		$add     = function ( $args ) use ( $menu_id ) {
			return wp_update_nav_menu_item( $menu_id, 0, array_merge( array( 'menu-item-status' => 'publish' ), $args ) );
		};
		$add( array( 'menu-item-title' => 'Home', 'menu-item-url' => home_url( '/' ), 'menu-item-type' => 'custom' ) );
		$add( array( 'menu-item-title' => 'Shop All', 'menu-item-object' => 'page', 'menu-item-object-id' => wc_get_page_id( 'shop' ), 'menu-item-type' => 'post_type' ) );
		foreach ( $tree as $slug => $node ) {
			$parent_item = $add( array( 'menu-item-object' => 'product_cat', 'menu-item-object-id' => $cat_ids[ $slug ], 'menu-item-type' => 'taxonomy' ) );
			foreach ( array_keys( $node[1] ) as $cslug ) {
				$add( array( 'menu-item-object' => 'product_cat', 'menu-item-object-id' => $cat_ids[ $cslug ], 'menu-item-type' => 'taxonomy', 'menu-item-parent-id' => $parent_item ) );
			}
		}
		$contact = get_page_by_path( 'contact-us' );
		if ( $contact ) {
			$add( array( 'menu-item-object' => 'page', 'menu-item-object-id' => $contact->ID, 'menu-item-type' => 'post_type' ) );
		}
		$locations            = get_theme_mod( 'nav_menu_locations', array() );
		$locations['primary'] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );
		$log[] = 'Main menu created and assigned.';
	}

	/* ---------- Demo-only test orders (for previewing the purchase notification) ---------- */
	if ( ! empty( $args['demo_orders'] ) ) {
		$log[] = oyepick_setup_demo_orders();
		$log[] = oyepick_setup_demo_reviews();
	}

	flush_rewrite_rules();
	return $log;
}

function oyepick_setup_term( $name, $slug, $parent ) {
	$t = get_term_by( 'slug', $slug, 'product_cat' );
	if ( $t ) {
		return (int) $t->term_id;
	}
	$r = wp_insert_term( $name, 'product_cat', array( 'slug' => $slug, 'parent' => $parent ) );
	return is_wp_error( $r ) ? 0 : (int) $r['term_id'];
}

function oyepick_setup_attach( $file, $post_id, $title ) {
	$up = wp_upload_bits( basename( $file ), null, file_get_contents( $file ) ); // phpcs:ignore
	if ( ! empty( $up['error'] ) ) {
		return 0;
	}
	$aid = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/jpeg',
			'post_title'     => $title,
			'post_status'    => 'inherit',
		),
		$up['file'],
		$post_id
	);
	if ( is_wp_error( $aid ) ) {
		return 0;
	}
	wp_update_attachment_metadata( $aid, wp_generate_attachment_metadata( $aid, $up['file'] ) );
	update_post_meta( $aid, '_wp_attachment_image_alt', $title );
	return $aid;
}

/**
 * Creates 3 test orders named "Demo Customer" so the purchase notification can be previewed
 * on a demo site. Never use on the live store — real orders feed the notification there.
 */
function oyepick_setup_demo_orders() {
	if ( get_option( 'oyepick_demo_orders_done' ) ) {
		return 'Demo orders already exist.';
	}
	$rows = array( array( 'OP-BUDS-PRO-9', 'Lahore', 4 ), array( 'OP-K8-66W', 'Karachi', 11 ), array( 'OP-BAROQUE-SILK', 'Islamabad', 23 ) );
	$n    = 0;
	foreach ( $rows as $r ) {
		$pid = wc_get_product_id_by_sku( $r[0] );
		if ( ! $pid ) {
			continue;
		}
		$order = wc_create_order();
		$order->add_product( wc_get_product( $pid ), 1 );
		$order->set_address( array( 'first_name' => 'Demo', 'last_name' => 'Customer', 'city' => $r[1], 'country' => 'PK', 'phone' => '00000000000' ), 'billing' );
		$order->set_payment_method( 'cod' );
		$order->set_payment_method_title( 'Cash on Delivery' );
		$order->set_customer_note( 'TEST ORDER — created by OyePick Store Setup for the demo.' );
		$order->set_date_created( time() - $r[2] * 60 );
		$order->calculate_totals();
		$order->update_status( 'processing', 'Demo order' );
		$order->update_meta_data( '_oyepick_demo', 1 );
		$order->save();
		$n++;
	}
	update_option( 'oyepick_demo_orders_done', 1 );
	return "Demo test orders created: $n (customer name 'Demo').";
}

/**
 * Demo-only reviews so the review layout can be previewed on a staging site.
 * Author "Demo Reviewer", flagged with _oyepick_demo (the theme shows a "Demo review" badge).
 * Never use on the live store — remove with oyepick_remove_demo_content() / `wp oyepick remove-demo`.
 */
function oyepick_setup_demo_reviews() {
	if ( get_option( 'oyepick_demo_reviews_done' ) ) {
		return 'Demo reviews already exist.';
	}
	$rows = array(
		array( 'OP-BUDS-PRO-9', 5, 'Sound is clear and the case is compact. Touch controls work well and pairing was quick.' ),
		array( 'OP-BUDS-PRO-9', 4, 'Good value for the price. Battery lasts a full day of normal use.' ),
		array( 'OP-BAROQUE-SILK', 5, 'The embroidery looks rich and the silk feels soft. Dupatta border is very neat.' ),
		array( 'OP-BOSKI', 5, 'Fabric is soft and comfortable. Colour was the same as in the pictures.' ),
		array( 'OP-K8-66W', 4, 'Charges my phone fast and the display shows the battery percentage clearly.' ),
		array( 'OP-PINK-LAWN', 5, 'Print colours are bright and the lace work on the dupatta is lovely.' ),
		array( 'OP-CABLE-KIT-60W', 4, 'Handy kit for travel, all adapters fit in the small case.' ),
		array( 'OP-HAND-CUTTER', 3, 'Works well for onions and garlic. Needs a few extra pulls for harder vegetables.' ),
	);
	$n = 0;
	foreach ( $rows as $i => $r ) {
		$pid = wc_get_product_id_by_sku( $r[0] );
		if ( ! $pid ) {
			continue;
		}
		$cid = wp_insert_comment(
			array(
				'comment_post_ID'      => $pid,
				'comment_author'       => 'Demo Reviewer',
				'comment_author_email' => 'demo-review@example.com',
				'comment_content'      => $r[2],
				'comment_type'         => 'review',
				'comment_approved'     => 1,
				'comment_date'         => gmdate( 'Y-m-d H:i:s', time() - ( $i + 1 ) * 2 * DAY_IN_SECONDS ),
			)
		);
		if ( $cid ) {
			update_comment_meta( $cid, 'rating', $r[1] );
			update_comment_meta( $cid, '_oyepick_demo', 1 );
			WC_Comments::clear_transients( $pid );
			$n++;
		}
	}
	update_option( 'oyepick_demo_reviews_done', 1 );
	return "Demo reviews created: $n (author 'Demo Reviewer', shown with a 'Demo review' badge).";
}

/**
 * Removes every demo review and demo order created by this plugin.
 */
function oyepick_remove_demo_content() {
	$removed = 0;
	$ids     = get_comments( array( 'meta_key' => '_oyepick_demo', 'meta_value' => 1, 'fields' => 'ids', 'type' => 'review', 'status' => 'all' ) );
	foreach ( $ids as $cid ) {
		$pid = (int) get_comment( $cid )->comment_post_ID;
		wp_delete_comment( $cid, true );
		WC_Comments::clear_transients( $pid );
		$removed++;
	}
	$orders = wc_get_orders( array( 'limit' => -1, 'meta_key' => '_oyepick_demo', 'meta_value' => 1, 'return' => 'ids', 'status' => 'any' ) );
	foreach ( $orders as $oid ) {
		$o = wc_get_order( $oid );
		if ( $o ) {
			$o->delete( true );
			$removed++;
		}
	}
	delete_option( 'oyepick_demo_reviews_done' );
	delete_option( 'oyepick_demo_orders_done' );
	delete_transient( 'oyepick_recent_orders' );
	return "Removed $removed demo reviews/orders.";
}

/* ---------- Admin page: Tools → OyePick Setup ---------- */
add_action(
	'admin_menu',
	function () {
		add_management_page( 'OyePick Store Setup', 'OyePick Setup', 'manage_woocommerce', 'oyepick-setup', 'oyepick_setup_page' );
	}
);

function oyepick_setup_page() {
	$log = array();
	if ( isset( $_POST['oyepick_run'] ) && check_admin_referer( 'oyepick_setup' ) && current_user_can( 'manage_woocommerce' ) ) {
		$log = oyepick_setup_run( array( 'demo_orders' => ! empty( $_POST['oyepick_demo_orders'] ) ) );
	}
	?>
	<div class="wrap">
		<h1>OyePick Store Setup</h1>
		<p>Creates the OyePick categories, 12 products with photos, main menu and pages, and sets WooCommerce to PKR with Cash on Delivery and product reviews.</p>
		<p><strong>Safe to re-run:</strong> products that already exist (same SKU or name) are skipped, nothing is deleted.</p>
		<?php if ( $log ) : ?>
			<div class="notice notice-success"><ul style="list-style:disc;margin-left:20px"><?php foreach ( $log as $l ) { echo '<li>' . esc_html( $l ) . '</li>'; } ?></ul></div>
		<?php endif; ?>
		<form method="post">
			<?php wp_nonce_field( 'oyepick_setup' ); ?>
			<p><label><input type="checkbox" name="oyepick_demo_orders" value="1"> Also create demo content — 3 test orders named "Demo" and 8 "Demo review" reviews — so the purchase notification and reviews can be previewed — <strong>staging/demo sites only, never on the live store</strong>.</label></p>
			<p><button class="button button-primary button-hero" name="oyepick_run" value="1">Run setup</button></p>
		</form>
	</div>
	<?php
}

/* ---------- WP-CLI: wp oyepick setup [--demo-orders] ---------- */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'oyepick remove-demo',
		function () {
			WP_CLI::success( oyepick_remove_demo_content() );
		}
	);
	WP_CLI::add_command(
		'oyepick setup',
		function ( $args, $assoc ) {
			foreach ( oyepick_setup_run( array( 'demo_orders' => isset( $assoc['demo-orders'] ) ) ) as $line ) {
				WP_CLI::log( $line );
			}
			WP_CLI::success( 'OyePick store ready.' );
		}
	);
}
