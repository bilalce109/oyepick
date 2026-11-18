<?php
/**
 * Homepage hero slides — editable in the admin under "Hero Slides".
 *
 * Each slide is a post of type op_slide:
 *   title        = big heading
 *   menu_order   = position (lower shows first)
 *   status Draft = hidden from the homepage
 *   meta         = small heading, text, linked product, images, badge, colour, buttons
 *
 * If no slide is published the built-in default slides are shown.
 *
 * @package OyePick
 */

defined( 'ABSPATH' ) || exit;

/* ------------------------------------------------------------------ *
 * Post type
 * ------------------------------------------------------------------ */
function oyepick_register_slides() {
	register_post_type(
		'op_slide',
		array(
			'labels'          => array(
				'name'               => __( 'Hero Slides', 'oyepick' ),
				'singular_name'      => __( 'Hero Slide', 'oyepick' ),
				'menu_name'          => __( 'Hero Slides', 'oyepick' ),
				'add_new'            => __( 'Add new slide', 'oyepick' ),
				'add_new_item'       => __( 'Add new slide', 'oyepick' ),
				'edit_item'          => __( 'Edit slide', 'oyepick' ),
				'new_item'           => __( 'New slide', 'oyepick' ),
				'all_items'          => __( 'All slides', 'oyepick' ),
				'not_found'          => __( 'No slides yet. Click "Add new slide".', 'oyepick' ),
				'not_found_in_trash' => __( 'No slides in trash.', 'oyepick' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_position'   => 21,
			'menu_icon'       => 'dashicons-images-alt2',
			'supports'        => array( 'title', 'page-attributes' ),
			'capability_type' => 'page',
			'map_meta_cap'    => true,
		)
	);
}
add_action( 'init', 'oyepick_register_slides' );

function oyepick_slide_fields() {
	return array(
		'_op_eyebrow'    => '',
		'_op_text'       => '',
		'_op_product'    => 0,
		'_op_pill'       => '',
		'_op_btn'        => '',
		'_op_link_text'  => '',
		'_op_link_url'   => '',
		'_op_imgs'       => array(),
		'_op_chip_icon'  => '',
		'_op_chip_text'  => '',
		'_op_color'      => 'mint',
		'_op_stage'      => '',
	);
}

/* ------------------------------------------------------------------ *
 * Admin edit screen
 * ------------------------------------------------------------------ */
add_action(
	'add_meta_boxes_op_slide',
	function () {
		add_meta_box( 'op_slide_box', __( 'Slide content', 'oyepick' ), 'oyepick_slide_metabox', 'op_slide', 'normal', 'high' );
	}
);

add_filter(
	'enter_title_here',
	function ( $text, $post ) {
		return 'op_slide' === $post->post_type ? __( 'Big heading, e.g. Buds Pro 9 Wireless Earbuds', 'oyepick' ) : $text;
	},
	10,
	2
);

function oyepick_slide_metabox( $post ) {
	wp_nonce_field( 'op_slide_save', 'op_slide_nonce' );
	$v = array();
	foreach ( oyepick_slide_fields() as $k => $d ) {
		$m       = get_post_meta( $post->ID, $k, true );
		$v[ $k ] = ( '' === $m || null === $m ) ? $d : $m;
	}
	$imgs     = array_values( array_filter( array_map( 'intval', (array) $v['_op_imgs'] ) ) );
	$products = get_posts(
		array(
			'post_type'   => 'product',
			'post_status' => 'publish',
			'numberposts' => -1,
			'orderby'     => 'title',
			'order'       => 'ASC',
		)
	);
	$row = function ( $label, $html, $help = '' ) {
		echo '<tr><th scope="row" style="width:190px"><label>' . esc_html( $label ) . '</label></th><td>' . $html; // phpcs:ignore
		if ( $help ) {
			echo '<p class="description">' . esc_html( $help ) . '</p>';
		}
		echo '</td></tr>';
	};
	$input = function ( $name, $val, $ph = '' ) {
		return '<input type="text" class="large-text" name="' . esc_attr( $name ) . '" value="' . esc_attr( $val ) . '" placeholder="' . esc_attr( $ph ) . '">';
	};
	?>
	<style>
		.op-imgs{display:flex;gap:14px;flex-wrap:wrap}
		.op-img{width:150px;border:1px dashed #c3c4c7;border-radius:10px;padding:8px;text-align:center;background:#f6f7f7}
		.op-img .pv{height:120px;display:flex;align-items:center;justify-content:center;margin-bottom:8px}
		.op-img .pv img{max-width:100%;max-height:120px}
		.op-img .pv span{color:#8c8f94}
		.op-tip{background:#f0f6fc;border-left:4px solid #2271b1;padding:10px 14px;margin:6px 0 10px}
	</style>
	<div class="op-tip">
		<?php esc_html_e( 'Order: use the "Order" box on the right (0 shows first, then 1, 2…). To hide a slide without deleting it, set it to Draft. Slides change every 2 seconds on the homepage.', 'oyepick' ); ?>
	</div>
	<table class="form-table" role="presentation">
		<?php
		$row( __( 'Small heading', 'oyepick' ), $input( '_op_eyebrow', $v['_op_eyebrow'], 'Audio · New arrival' ), __( 'Short line shown above the big heading.', 'oyepick' ) );
		$row( __( 'Description', 'oyepick' ), '<textarea class="large-text" rows="3" name="_op_text" placeholder="One or two short sentences about the product.">' . esc_textarea( $v['_op_text'] ) . '</textarea>' );

		$sel = '<select name="_op_product" style="min-width:320px"><option value="0">' . esc_html__( '— No product (just show the button link) —', 'oyepick' ) . '</option>';
		foreach ( $products as $pp ) {
			$sel .= '<option value="' . (int) $pp->ID . '"' . selected( (int) $v['_op_product'], $pp->ID, false ) . '>' . esc_html( $pp->post_title ) . '</option>';
		}
		$sel .= '</select>';
		$row( __( 'Product', 'oyepick' ), $sel, __( 'Price and the "Add to cart" button come from this product automatically.', 'oyepick' ) );

		$row( __( 'Label next to price', 'oyepick' ), $input( '_op_pill', $v['_op_pill'], 'Cash on delivery' ) );
		$row( __( 'Main button text', 'oyepick' ), $input( '_op_btn', $v['_op_btn'], 'Add to cart' ), __( 'Leave empty for "Add to cart".', 'oyepick' ) );
		$row( __( 'Second button text', 'oyepick' ), $input( '_op_link_text', $v['_op_link_text'], 'Shop audio' ) );
		$row( __( 'Second button link', 'oyepick' ), $input( '_op_link_url', $v['_op_link_url'], 'https://…/product-category/audio/' ), __( 'Copy a category or page address from your browser. Leave empty to open the shop.', 'oyepick' ) );

		ob_start();
		echo '<div class="op-imgs">';
		for ( $n = 0; $n < 3; $n++ ) {
			$id  = $imgs[ $n ] ?? 0;
			$src = $id ? wp_get_attachment_image_url( $id, 'medium' ) : '';
			echo '<div class="op-img"><div class="pv">' . ( $src ? '<img src="' . esc_url( $src ) . '" alt="">' : '<span>' . esc_html__( 'No image', 'oyepick' ) . '</span>' ) . '</div>';
			echo '<input type="hidden" name="_op_imgs[]" value="' . (int) $id . '">';
			echo '<button type="button" class="button op-pick">' . esc_html__( 'Choose', 'oyepick' ) . '</button> ';
			echo '<button type="button" class="button-link op-clear" style="color:#b32d2e">' . esc_html__( 'Remove', 'oyepick' ) . '</button></div>';
		}
		echo '</div>';
		$row( __( 'Images (1 to 3)', 'oyepick' ), ob_get_clean(), __( 'Best: product photos with a transparent background (PNG/WebP cut-outs). Normal photos also work.', 'oyepick' ) );

		$row( __( 'Badge', 'oyepick' ), '<input type="text" name="_op_chip_icon" value="' . esc_attr( $v['_op_chip_icon'] ) . '" style="width:60px" placeholder="✓"> ' . '<input type="text" name="_op_chip_text" value="' . esc_attr( $v['_op_chip_text'] ) . '" style="width:260px" placeholder="5 colours">', __( 'Small white tag on the picture (icon/emoji + text). Leave empty to hide.', 'oyepick' ) );

		$col = '';
		foreach ( array( 'mint' => __( 'Mint green', 'oyepick' ), 'sand' => __( 'Sand / beige', 'oyepick' ) ) as $k => $l ) {
			$col .= '<label style="margin-right:18px"><input type="radio" name="_op_color" value="' . esc_attr( $k ) . '"' . checked( $v['_op_color'], $k, false ) . '> ' . esc_html( $l ) . '</label>';
		}
		$row( __( 'Background colour', 'oyepick' ), $col );
		?>
	</table>
	<?php
}

add_action(
	'admin_enqueue_scripts',
	function () {
		$s = get_current_screen();
		if ( ! $s || 'op_slide' !== $s->post_type || 'post' !== $s->base ) {
			return;
		}
		wp_enqueue_media();
		wp_add_inline_script(
			'media-editor',
			"jQuery(function($){
				$(document).on('click','.op-pick',function(e){
					e.preventDefault(); var box=$(this).closest('.op-img');
					var f=wp.media({title:'Choose slide image',library:{type:'image'},button:{text:'Use this image'},multiple:false});
					f.on('select',function(){ var a=f.state().get('selection').first().toJSON();
						var u=(a.sizes&&(a.sizes.medium||a.sizes.full))?(a.sizes.medium||a.sizes.full).url:a.url;
						box.find('input').val(a.id); box.find('.pv').html('<img src=\"'+u+'\" alt=\"\">'); });
					f.open();
				});
				$(document).on('click','.op-clear',function(e){ e.preventDefault(); var box=$(this).closest('.op-img');
					box.find('input').val(0); box.find('.pv').html('<span>No image</span>'); });
			});"
		);
	}
);

add_action(
	'save_post_op_slide',
	function ( $post_id ) {
		if ( ! isset( $_POST['op_slide_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['op_slide_nonce'] ), 'op_slide_save' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput
		update_post_meta( $post_id, '_op_eyebrow', sanitize_text_field( wp_unslash( $_POST['_op_eyebrow'] ?? '' ) ) );
		update_post_meta( $post_id, '_op_text', sanitize_textarea_field( wp_unslash( $_POST['_op_text'] ?? '' ) ) );
		update_post_meta( $post_id, '_op_product', absint( $_POST['_op_product'] ?? 0 ) );
		update_post_meta( $post_id, '_op_pill', sanitize_text_field( wp_unslash( $_POST['_op_pill'] ?? '' ) ) );
		update_post_meta( $post_id, '_op_btn', sanitize_text_field( wp_unslash( $_POST['_op_btn'] ?? '' ) ) );
		update_post_meta( $post_id, '_op_link_text', sanitize_text_field( wp_unslash( $_POST['_op_link_text'] ?? '' ) ) );
		update_post_meta( $post_id, '_op_link_url', esc_url_raw( wp_unslash( $_POST['_op_link_url'] ?? '' ) ) );
		update_post_meta( $post_id, '_op_imgs', array_values( array_filter( array_map( 'absint', (array) ( $_POST['_op_imgs'] ?? array() ) ) ) ) );
		update_post_meta( $post_id, '_op_chip_icon', sanitize_text_field( wp_unslash( $_POST['_op_chip_icon'] ?? '' ) ) );
		update_post_meta( $post_id, '_op_chip_text', sanitize_text_field( wp_unslash( $_POST['_op_chip_text'] ?? '' ) ) );
		update_post_meta( $post_id, '_op_color', 'sand' === ( $_POST['_op_color'] ?? '' ) ? 'sand' : 'mint' );
		// phpcs:enable
	}
);

/* Admin list: picture, product and order columns, sorted by order. */
add_filter(
	'manage_op_slide_posts_columns',
	function ( $cols ) {
		return array(
			'cb'       => $cols['cb'],
			'op_img'   => __( 'Image', 'oyepick' ),
			'title'    => __( 'Heading', 'oyepick' ),
			'op_prod'  => __( 'Product', 'oyepick' ),
			'op_order' => __( 'Order', 'oyepick' ),
			'date'     => $cols['date'],
		);
	}
);
add_action(
	'manage_op_slide_posts_custom_column',
	function ( $col, $id ) {
		if ( 'op_img' === $col ) {
			$imgs = (array) get_post_meta( $id, '_op_imgs', true );
			if ( ! empty( $imgs[0] ) ) {
				echo wp_get_attachment_image( (int) $imgs[0], array( 60, 60 ), false, array( 'style' => 'width:60px;height:60px;object-fit:contain;background:#f0f0f1;border-radius:8px' ) );
			}
		} elseif ( 'op_prod' === $col ) {
			$pid = (int) get_post_meta( $id, '_op_product', true );
			echo $pid ? esc_html( get_the_title( $pid ) ) : '—';
		} elseif ( 'op_order' === $col ) {
			echo (int) get_post_field( 'menu_order', $id );
		}
	},
	10,
	2
);
add_action(
	'pre_get_posts',
	function ( $q ) {
		if ( is_admin() && $q->is_main_query() && 'op_slide' === $q->get( 'post_type' ) && ! $q->get( 'orderby' ) ) {
			$q->set( 'orderby', array( 'menu_order' => 'ASC', 'date' => 'ASC' ) );
		}
	}
);

/* ------------------------------------------------------------------ *
 * Front end
 * ------------------------------------------------------------------ */

/**
 * Slides ready for the homepage template.
 *
 * @return array[] Each: theme, eyebrow, title, text, pill, product, label, link[url,text], stage, imgs[class=>url], chip[icon,text].
 */
function oyepick_get_slides() {
	$posts = get_posts(
		array(
			'post_type'   => 'op_slide',
			'post_status' => 'publish',
			'numberposts' => 20,
			'orderby'     => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
		)
	);
	$out = array();
	foreach ( $posts as $sp ) {
		$m       = function ( $k ) use ( $sp ) {
			return get_post_meta( $sp->ID, $k, true );
		};
		$product = ( $m( '_op_product' ) && function_exists( 'wc_get_product' ) ) ? wc_get_product( (int) $m( '_op_product' ) ) : null;
		$imgs    = array();
		$cls     = array( 'a', 'b', 'c' );
		foreach ( array_slice( array_filter( array_map( 'intval', (array) $m( '_op_imgs' ) ) ), 0, 3 ) as $n => $aid ) {
			$url = wp_get_attachment_image_url( $aid, 'large' );
			if ( $url ) {
				$imgs[ $cls[ count( $imgs ) ] ] = $url;
			}
		}
		if ( ! $imgs && $product && $product->get_image_id() ) {
			$imgs['a'] = wp_get_attachment_image_url( $product->get_image_id(), 'large' );
		}
		$stage = $m( '_op_stage' );
		if ( ! $stage ) {
			$stage = 'st-auto st-n' . max( 1, count( $imgs ) );
		}
		$out[] = array(
			'theme'   => 'sand' === $m( '_op_color' ) ? 's-sand' : 's-mint',
			'eyebrow' => (string) $m( '_op_eyebrow' ),
			'title'   => get_the_title( $sp ),
			'text'    => (string) $m( '_op_text' ),
			'pill'    => (string) $m( '_op_pill' ),
			'product' => $product ?: null,
			'label'   => $m( '_op_btn' ) ? $m( '_op_btn' ) : __( 'Add to cart', 'oyepick' ),
			'link'    => array( $m( '_op_link_url' ) ? $m( '_op_link_url' ) : oyepick_shop_url(), (string) $m( '_op_link_text' ) ),
			'stage'   => $stage,
			'imgs'    => $imgs,
			'chip'    => array( (string) $m( '_op_chip_icon' ), (string) $m( '_op_chip_text' ) ),
		);
	}
	return $out ? $out : oyepick_default_slides();
}

/**
 * Built-in slides (also used to create the first editable slides).
 *
 * @param bool $raw Return theme image file names instead of resolved data.
 */
function oyepick_default_slides( $raw = false ) {
	$d = array(
		array(
			'sku'     => 'OP-BUDS-PRO-9',
			'color'   => 'mint',
			'eyebrow' => 'Audio · New arrival',
			'title'   => 'Buds Pro 9 Wireless Earbuds',
			'text'    => 'ANC/ENC noise reduction, touch controls and a compact charging case.',
			'pill'    => 'Cash on delivery',
			'cat'     => 'audio',
			'linktxt' => 'Shop audio',
			'stage'   => 'st-buds',
			'imgs'    => array( 'buds-pro-9-box', 'buds-pro-9-pair' ),
			'chip'    => array( '♪', 'Black & Purple' ),
			'btn'     => '',
		),
		array(
			'sku'     => 'OP-BAROQUE-SILK',
			'color'   => 'sand',
			'eyebrow' => 'Women · Party wear',
			'title'   => 'Baroque Embroidered Silk',
			'text'    => 'Embroidered silk shirt, chiffon dupatta with embroidered border and silk trouser, with accessories.',
			'pill'    => '3-piece',
			'cat'     => 'women',
			'linktxt' => 'Shop women',
			'stage'   => 'st-baroque',
			'imgs'    => array( 'baroque-teal', 'baroque-purple' ),
			'chip'    => array( '✦', 'Teal & Purple' ),
			'btn'     => '',
		),
		array(
			'sku'     => 'OP-BOSKI',
			'color'   => 'mint',
			'eyebrow' => 'Men · Unstitched',
			'title'   => 'Premium Gents Boski Suits',
			'text'    => 'Soft, comfortable and classy Boski fabric in elegant colours. Perfect for every occasion.',
			'pill'    => 'Unstitched',
			'cat'     => 'men',
			'linktxt' => 'Shop men',
			'stage'   => 'st-boski',
			'imgs'    => array( 'boski-blue', 'boski-white', 'boski-khaki' ),
			'chip'    => array( '✓', '5 colours' ),
			'btn'     => '',
		),
		array(
			'sku'     => 'OP-K8-66W',
			'color'   => 'sand',
			'eyebrow' => 'Power banks',
			'title'   => 'Stay charged, everywhere',
			'text'    => 'From the 66W FoneStop K8 to solar and compact 22.5W power banks. Power for every day.',
			'pill'    => 'FoneStop K8 · 66W',
			'cat'     => 'chargers-power-banks',
			'linktxt' => 'All power banks',
			'stage'   => 'st-power',
			'imgs'    => array( 'fonestop-k8-box', 'solar-pair', 'nohon-pair' ),
			'chip'    => array( '⚡', '66W fast charge' ),
			'btn'     => 'Add K8 to cart',
		),
	);
	if ( $raw ) {
		return $d;
	}
	$out = array();
	foreach ( $d as $s ) {
		$imgs = array();
		foreach ( $s['imgs'] as $n => $f ) {
			$imgs[ array( 'a', 'b', 'c' )[ $n ] ] = oyepick_img( 'cutouts/' . $f . '.webp' );
		}
		$out[] = array(
			'theme'   => 's-' . $s['color'],
			'eyebrow' => $s['eyebrow'],
			'title'   => $s['title'],
			'text'    => $s['text'],
			'pill'    => $s['pill'],
			'product' => oyepick_product( $s['sku'] ),
			'label'   => $s['btn'] ? $s['btn'] : __( 'Add to cart', 'oyepick' ),
			'link'    => array( oyepick_cat_link( $s['cat'] ), $s['linktxt'] ),
			'stage'   => $s['stage'],
			'imgs'    => $imgs,
			'chip'    => $s['chip'],
		);
	}
	return $out;
}

/* ------------------------------------------------------------------ *
 * First run: turn the built-in slides into editable "Hero Slides"
 * (images copied into the Media Library). Runs once.
 * ------------------------------------------------------------------ */
function oyepick_slides_seed() {
	if ( get_option( 'oyepick_slides_seeded' ) || ! function_exists( 'wc_get_product_id_by_sku' ) ) {
		return;
	}
	update_option( 'oyepick_slides_seeded', 1, false );
	if ( get_posts( array( 'post_type' => 'op_slide', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) ) ) {
		return;
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	foreach ( oyepick_default_slides( true ) as $order => $s ) {
		$ids = array();
		foreach ( $s['imgs'] as $f ) {
			$ids[] = oyepick_slides_import_image( OYEPICK_DIR . '/assets/images/cutouts/' . $f . '.webp' );
		}
		$pid = wp_insert_post(
			array(
				'post_type'   => 'op_slide',
				'post_status' => 'publish',
				'post_title'  => $s['title'],
				'menu_order'  => $order,
			)
		);
		if ( ! $pid || is_wp_error( $pid ) ) {
			continue;
		}
		$link = oyepick_cat_link( $s['cat'] );
		$meta = array(
			'_op_eyebrow'   => $s['eyebrow'],
			'_op_text'      => $s['text'],
			'_op_product'   => (int) wc_get_product_id_by_sku( $s['sku'] ),
			'_op_pill'      => $s['pill'],
			'_op_btn'       => $s['btn'],
			'_op_link_text' => $s['linktxt'],
			'_op_link_url'  => $link,
			'_op_imgs'      => array_values( array_filter( $ids ) ),
			'_op_chip_icon' => $s['chip'][0],
			'_op_chip_text' => $s['chip'][1],
			'_op_color'     => $s['color'],
			'_op_stage'     => $s['stage'], // hand-tuned picture layout for these four
		);
		foreach ( $meta as $k => $val ) {
			update_post_meta( $pid, $k, $val );
		}
	}
}
add_action( 'admin_init', 'oyepick_slides_seed' );
add_action( 'after_switch_theme', 'oyepick_slides_seed' );

function oyepick_slides_import_image( $path ) {
	if ( ! file_exists( $path ) ) {
		return 0;
	}
	$found = get_posts(
		array(
			'post_type'   => 'attachment',
			'post_status' => 'inherit',
			'numberposts' => 1,
			'fields'      => 'ids',
			'meta_key'    => '_oyepick_src', // phpcs:ignore
			'meta_value'  => basename( $path ), // phpcs:ignore
		)
	);
	if ( $found ) {
		return (int) $found[0];
	}
	$up = wp_upload_bits( basename( $path ), null, file_get_contents( $path ) ); // phpcs:ignore
	if ( ! empty( $up['error'] ) ) {
		return 0;
	}
	$id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/webp',
			'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $path ) ),
			'post_status'    => 'inherit',
		),
		$up['file']
	);
	if ( $id && ! is_wp_error( $id ) ) {
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $up['file'] ) );
		update_post_meta( $id, '_oyepick_src', basename( $path ) );
		return (int) $id;
	}
	return 0;
}
