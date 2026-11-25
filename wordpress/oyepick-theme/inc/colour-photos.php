<?php
/**
 * Colour swatches on the product page.
 *
 *  - Colours come from the product's "Colour" attribute (Products → Edit → Attributes).
 *  - Product edit screen → "Colour photos" box: pick which photo belongs to each colour.
 *  - Product page: clicking a colour shows its photo in the gallery (and the soft backdrop).
 *  - The chosen colour goes with the order: cart, checkout, order screen and emails
 *    show "Colour: …", and the WhatsApp order message includes it.
 *
 * @package OyePick
 */

defined( 'ABSPATH' ) || exit;

/** Colours of a product (from its "Colour" attribute). */
function oyepick_product_colours( $product ) {
	if ( ! $product ) {
		return array();
	}
	$raw = $product->get_attribute( 'Colour' );
	return $raw ? array_values( array_unique( array_filter( array_map( 'trim', preg_split( '/[|,]/', $raw ) ) ) ) ) : array();
}

/** Dot colour for a colour name (unknown names get a neutral dot). */
function oyepick_colour_hex( $name ) {
	$map = array(
		'black'      => '#1f1f1f',
		'white'      => '#ffffff',
		'off white'  => '#f4f1ea',
		'cream'      => '#efe3c4',
		'beige'      => '#e3d2b0',
		'khaki'      => '#c19a62',
		'brown'      => '#7a5232',
		'grey'       => '#9aa3a6',
		'gray'       => '#9aa3a6',
		'silver'     => '#c9ced3',
		'sky blue'   => '#8ec3ea',
		'light blue' => '#a9c6ef',
		'blue'       => '#2f6fd0',
		'navy'       => '#1d2f5e',
		'teal'       => '#0f5e5e',
		'green'      => '#2f8f4e',
		'mint'       => '#a8dcc7',
		'purple'     => '#6b2b7c',
		'maroon'     => '#6e1f2b',
		'red'        => '#c83a3a',
		'pink'       => '#e58fb3',
		'peach'      => '#f4b99a',
		'orange'     => '#ee8a2c',
		'yellow'     => '#f2cf3b',
		'gold'       => '#c9a54a',
	);
	$k = strtolower( trim( $name ) );
	return $map[ $k ] ?? '#d9d4ca';
}

/** Photo chosen for each colour: [ colour => attachment id ]. */
function oyepick_colour_images( $product ) {
	$saved = (array) get_post_meta( $product->get_id(), '_op_colour_imgs', true );
	$ids   = oyepick_gallery_ids( $product );
	$out   = array();
	foreach ( oyepick_product_colours( $product ) as $c ) {
		$aid = isset( $saved[ $c ] ) ? (int) $saved[ $c ] : 0;
		if ( $aid && in_array( $aid, $ids, true ) ) {
			$out[ $c ] = $aid;
		}
	}
	return $out;
}

/** Main image + gallery images, in the order the product gallery shows them. */
function oyepick_gallery_ids( $product ) {
	return array_values( array_unique( array_filter( array_map( 'intval', array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() ) ) ) ) );
}

/* ------------------------------------------------------------------ *
 * Admin: "Colour photos" box on the product edit screen
 * ------------------------------------------------------------------ */
add_action(
	'add_meta_boxes_product',
	function () {
		add_meta_box( 'op_colour_imgs', __( 'Colour photos', 'oyepick' ), 'oyepick_colour_metabox', 'product', 'normal', 'default' );
	}
);

function oyepick_colour_metabox( $post ) {
	$product = wc_get_product( $post->ID );
	$colours = oyepick_product_colours( $product );
	$ids     = $product ? oyepick_gallery_ids( $product ) : array();
	$saved   = (array) get_post_meta( $post->ID, '_op_colour_imgs', true );
	wp_nonce_field( 'op_colour_imgs', 'op_colour_nonce' );
	echo '<style>.op-ci-row{display:flex;gap:14px;align-items:flex-start;padding:10px 0;border-bottom:1px solid #f0f0f1}.op-ci-row:last-child{border:0}.op-ci-name{width:130px;font-weight:600;padding-top:16px;display:flex;gap:8px;align-items:center}.op-ci-dot{width:16px;height:16px;border-radius:50%;border:1px solid #c3c4c7;flex:none}.op-ci-imgs{display:flex;gap:8px;flex-wrap:wrap}.op-ci-imgs label{cursor:pointer;display:block;position:relative}.op-ci-imgs input{position:absolute;opacity:0}.op-ci-imgs img{width:58px;height:58px;object-fit:cover;border-radius:8px;border:3px solid transparent;display:block}.op-ci-imgs input:checked+img{border-color:#2271b1}.op-ci-imgs .none{width:58px;height:58px;border-radius:8px;border:3px solid transparent;background:#f6f7f7;display:grid;place-items:center;font-size:11px;color:#646970;text-align:center}.op-ci-imgs input:checked+.none{border-color:#2271b1}</style>';
	if ( ! $colours ) {
		echo '<p>' . esc_html__( 'This product has no colours. To add colours: Product data → Attributes → name "Colour", values like Black | White → Save attributes → Update. Then choose a photo for each colour here.', 'oyepick' ) . '</p>';
		return;
	}
	if ( ! $ids ) {
		echo '<p>' . esc_html__( 'Add the product image and gallery photos first, click Update, then choose a photo for each colour here.', 'oyepick' ) . '</p>';
		return;
	}
	echo '<p class="description">' . esc_html__( 'Click the photo that shows each colour. On the website, choosing that colour shows this photo. Photos come from Product image + Product gallery.', 'oyepick' ) . '</p>';
	foreach ( $colours as $c ) {
		$cur = (int) ( $saved[ $c ] ?? 0 );
		echo '<div class="op-ci-row"><div class="op-ci-name"><span class="op-ci-dot" style="background:' . esc_attr( oyepick_colour_hex( $c ) ) . '"></span>' . esc_html( $c ) . '</div><div class="op-ci-imgs">';
		echo '<label title="' . esc_attr__( 'No photo', 'oyepick' ) . '"><input type="radio" name="op_colour_imgs[' . esc_attr( $c ) . ']" value="0"' . checked( 0, $cur, false ) . '><span class="none">' . esc_html__( 'No photo', 'oyepick' ) . '</span></label>';
		foreach ( $ids as $aid ) {
			echo '<label><input type="radio" name="op_colour_imgs[' . esc_attr( $c ) . ']" value="' . (int) $aid . '"' . checked( $aid, $cur, false ) . '>' . wp_get_attachment_image( $aid, array( 58, 58 ) ) . '</label>';
		}
		echo '</div></div>';
	}
}

add_action(
	'save_post_product',
	function ( $post_id ) {
		if ( ! isset( $_POST['op_colour_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['op_colour_nonce'] ), 'op_colour_imgs' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$in  = isset( $_POST['op_colour_imgs'] ) ? (array) wp_unslash( $_POST['op_colour_imgs'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$out = array();
		foreach ( $in as $c => $aid ) {
			$c = sanitize_text_field( $c );
			if ( '' !== $c && absint( $aid ) ) {
				$out[ $c ] = absint( $aid );
			}
		}
		update_post_meta( $post_id, '_op_colour_imgs', $out );
	}
);

/* ------------------------------------------------------------------ *
 * Product page: swatches (replace the plain colour list)
 * ------------------------------------------------------------------ */
add_action(
	'woocommerce_single_product_summary',
	function () {
		global $product;
		$colours = oyepick_product_colours( $product );
		if ( ! $colours ) {
			return;
		}
		$imgs  = oyepick_colour_images( $product );
		$order = oyepick_gallery_ids( $product );
		echo '<div class="op-colours" data-product="' . (int) $product->get_id() . '"><span>' . esc_html__( 'Colour', 'oyepick' ) . ': <b class="op-colour-name">' . esc_html__( 'choose', 'oyepick' ) . '</b></span><ul>';
		foreach ( $colours as $c ) {
			$aid   = $imgs[ $c ] ?? 0;
			$attrs = '';
			if ( $aid ) {
				$attrs = ' data-index="' . (int) array_search( $aid, $order, true ) . '" data-large="' . esc_url( (string) wp_get_attachment_image_url( $aid, 'large' ) ) . '"';
			}
			$light = in_array( strtolower( $c ), array( 'white', 'off white', 'cream' ), true ) ? ' light' : '';
			echo '<li><button type="button" class="op-sw' . esc_attr( $light ) . '" data-colour="' . esc_attr( $c ) . '"' . $attrs . ' aria-pressed="false"><i style="background:' . esc_attr( oyepick_colour_hex( $c ) ) . '"></i>' . esc_html( $c ) . '</button></li>'; // phpcs:ignore
		}
		echo '</ul></div>';
	},
	25
);

/* The chosen colour travels with the "Add to cart" form. */
add_action(
	'woocommerce_before_add_to_cart_button',
	function () {
		global $product;
		if ( oyepick_product_colours( $product ) ) {
			echo '<input type="hidden" name="op_colour" class="op-colour-input" value="">';
		}
	}
);

add_filter(
	'woocommerce_add_cart_item_data',
	function ( $data, $product_id ) {
		// phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce add-to-cart form
		$c = isset( $_POST['op_colour'] ) ? sanitize_text_field( wp_unslash( $_POST['op_colour'] ) ) : '';
		if ( '' !== $c && in_array( $c, oyepick_product_colours( wc_get_product( $product_id ) ), true ) ) {
			$data['op_colour'] = $c;
		}
		return $data;
	},
	10,
	2
);

add_filter(
	'woocommerce_get_item_data',
	function ( $item_data, $cart_item ) {
		if ( ! empty( $cart_item['op_colour'] ) ) {
			$item_data[] = array( 'key' => __( 'Colour', 'oyepick' ), 'value' => $cart_item['op_colour'] );
		}
		return $item_data;
	},
	10,
	2
);

add_action(
	'woocommerce_checkout_create_order_line_item',
	function ( $item, $cart_item_key, $values ) {
		if ( ! empty( $values['op_colour'] ) ) {
			$item->add_meta_data( __( 'Colour', 'oyepick' ), $values['op_colour'], true );
		}
	},
	10,
	3
);

/* Cart item photo = the chosen colour's photo. */
add_filter(
	'woocommerce_cart_item_thumbnail',
	function ( $thumb, $cart_item ) {
		if ( ! empty( $cart_item['op_colour'] ) && ! empty( $cart_item['data'] ) ) {
			$imgs = oyepick_colour_images( wc_get_product( $cart_item['product_id'] ) );
			if ( ! empty( $imgs[ $cart_item['op_colour'] ] ) ) {
				return wp_get_attachment_image( $imgs[ $cart_item['op_colour'] ], 'woocommerce_thumbnail' );
			}
		}
		return $thumb;
	},
	10,
	2
);

/* Cart / checkout blocks (Store API) also show the chosen colour's photo. */
add_filter(
	'woocommerce_store_api_cart_item_images',
	function ( $images, $cart_item ) {
		if ( empty( $cart_item['op_colour'] ) || empty( $images ) ) {
			return $images;
		}
		$imgs = oyepick_colour_images( wc_get_product( $cart_item['product_id'] ) );
		$aid  = $imgs[ $cart_item['op_colour'] ] ?? 0;
		if ( $aid && isset( $images[0] ) && is_object( $images[0] ) ) {
			$first            = clone $images[0];
			$first->id        = $aid;
			$first->src       = (string) wp_get_attachment_image_url( $aid, 'full' );
			$first->thumbnail = (string) wp_get_attachment_image_url( $aid, 'woocommerce_thumbnail' );
			$first->srcset    = (string) wp_get_attachment_image_srcset( $aid, 'full' );
			$first->sizes     = (string) wp_get_attachment_image_sizes( $aid, 'full' );
			$images[0]        = $first;
		}
		return $images;
	},
	10,
	2
);

/* Script: click a swatch → gallery shows its photo, form + WhatsApp message get the colour. */
add_action(
	'wp_footer',
	function () {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		?>
		<script>
		(function () {
			var box = document.querySelector('.op-colours[data-product]');
			if (!box) return;
			var nameEl = box.querySelector('.op-colour-name');
			var wa = document.querySelector('.op-wa-order');
			var waBase = wa ? wa.getAttribute('href') : '';
			var goTo = function (i) {
				var thumbs = document.querySelectorAll('.woocommerce-product-gallery .flex-control-thumbs li img');
				if (thumbs[i]) { thumbs[i].click(); return; }
				var $g = window.jQuery && jQuery('.woocommerce-product-gallery');
				if ($g && $g.data('flexslider')) { $g.flexslider(i); }
			};
			box.addEventListener('click', function (e) {
				var b = e.target.closest('.op-sw');
				if (!b) return;
				box.querySelectorAll('.op-sw').forEach(function (x) { x.classList.toggle('on', x === b); x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
				var c = b.getAttribute('data-colour');
				nameEl.textContent = c;
				document.querySelectorAll('.op-colour-input').forEach(function (i) { i.value = c; });
				if (b.hasAttribute('data-index')) {
					goTo(parseInt(b.getAttribute('data-index'), 10));
					var bg = document.querySelector('.op-product-bg span');
					if (bg && b.getAttribute('data-large')) bg.style.backgroundImage = 'url("' + b.getAttribute('data-large') + '")';
				}
				if (wa && waBase) {
					var u = new URL(waBase, location.href);
					var t = (u.searchParams.get('text') || '').replace(/ \(http/, ' - Colour: ' + c + ' (http');
					wa.setAttribute('href', u.origin + u.pathname + '?text=' + encodeURIComponent(t)); // %20, not "+"
				}
			});
		})();
		</script>
		<?php
	},
	31
);
