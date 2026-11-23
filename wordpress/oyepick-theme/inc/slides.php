<?php
/**
 * Homepage slider — editable in the admin under "Homepage Slider".
 *
 * Made to be simple for the shop owner:
 *   - Products → hover a product → "Add to homepage slider" creates a slide in one click.
 *   - Or Homepage Slider → Add new slide → choose the product → Publish.
 *   - Only the product is needed. Heading, text, picture, price and buttons come from the
 *     product unless they are filled in under "More options".
 *   - The slide list has "Move up / Move down" and "Hide / Show" links.
 *
 * Each slide is a post of type op_slide:
 *   title        = big heading (empty = product name)
 *   menu_order   = position (lower shows first)
 *   status Draft = hidden from the homepage
 *   meta         = linked product, images, and optional text, badge, colour, buttons
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
				'name'               => __( 'Homepage Slider', 'oyepick' ),
				'singular_name'      => __( 'Slide', 'oyepick' ),
				'menu_name'          => __( 'Homepage Slider', 'oyepick' ),
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
			'supports'        => array( 'title' ),
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
		'_op_color'      => '',
		'_op_stage'      => '',
	);
}

/** All slides (shown and hidden) in homepage order. */
function oyepick_slides_all() {
	return get_posts(
		array(
			'post_type'   => 'op_slide',
			'post_status' => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'numberposts' => -1,
			'orderby'     => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
		)
	);
}

/** Position for a new slide: after the last one. */
function oyepick_slides_next_order() {
	$max = -1;
	foreach ( oyepick_slides_all() as $s ) {
		$max = max( $max, (int) $s->menu_order );
	}
	return $max + 1;
}

/** Slide that already shows this product (0 if none). */
function oyepick_slide_for_product( $product_id ) {
	$found = get_posts(
		array(
			'post_type'   => 'op_slide',
			'post_status' => array( 'publish', 'draft' ),
			'numberposts' => 1,
			'fields'      => 'ids',
			'meta_key'    => '_op_product', // phpcs:ignore
			'meta_value'  => (int) $product_id, // phpcs:ignore
		)
	);
	return $found ? (int) $found[0] : 0;
}

/** Create a ready-to-show slide for a product. */
function oyepick_slide_create_for_product( $product_id ) {
	$id = wp_insert_post(
		array(
			'post_type'   => 'op_slide',
			'post_status' => 'publish',
			'post_title'  => get_post_field( 'post_title', $product_id, 'raw' ),
			'menu_order'  => oyepick_slides_next_order(),
		)
	);
	if ( $id && ! is_wp_error( $id ) ) {
		update_post_meta( $id, '_op_product', (int) $product_id );
		return (int) $id;
	}
	return 0;
}

/* ------------------------------------------------------------------ *
 * Saving: heading may be empty (product name is used), new slides go last
 * ------------------------------------------------------------------ */
add_filter(
	'wp_insert_post_empty_content',
	function ( $maybe_empty, $postarr ) {
		return ( isset( $postarr['post_type'] ) && 'op_slide' === $postarr['post_type'] ) ? false : $maybe_empty;
	},
	10,
	2
);

add_filter(
	'wp_insert_post_data',
	function ( $data, $postarr ) {
		if ( 'op_slide' !== $data['post_type'] ) {
			return $data;
		}
		$id = empty( $postarr['ID'] ) ? 0 : (int) $postarr['ID'];
		if ( '' === trim( (string) $data['post_title'] ) && 'auto-draft' !== $data['post_status'] ) {
			// phpcs:ignore WordPress.Security.NonceVerification -- only reads a product id to name the slide; meta is saved with a nonce check below.
			$pid = isset( $_POST['_op_product'] ) ? absint( $_POST['_op_product'] ) : (int) get_post_meta( $id, '_op_product', true );
			if ( $pid ) {
				$data['post_title'] = wp_slash( get_post_field( 'post_title', $pid, 'raw' ) );
			}
		}
		$was = $id ? get_post_status( $id ) : 'new';
		if ( in_array( $was, array( 'new', 'auto-draft' ), true ) && 'auto-draft' !== $data['post_status'] && 0 === (int) $data['menu_order'] ) {
			$data['menu_order'] = oyepick_slides_next_order();
		}
		return $data;
	},
	10,
	2
);

/* ------------------------------------------------------------------ *
 * Admin edit screen
 * ------------------------------------------------------------------ */
add_action(
	'add_meta_boxes_op_slide',
	function () {
		add_meta_box( 'op_slide_box', __( 'Slide', 'oyepick' ), 'oyepick_slide_metabox', 'op_slide', 'normal', 'high' );
	}
);

add_filter(
	'enter_title_here',
	function ( $text, $post ) {
		return 'op_slide' === $post->post_type ? __( 'Heading (optional — leave empty to use the product name)', 'oyepick' ) : $text;
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
		.op-step{display:flex;gap:16px;padding:18px 4px;border-bottom:1px solid #f0f0f1}
		.op-step .n{flex:0 0 34px;height:34px;border-radius:50%;background:#1f5f5b;color:#fff;font-weight:700;font-size:16px;display:grid;place-items:center}
		.op-step h3{margin:4px 0 4px;font-size:15px}
		.op-step h3 em{font-weight:400;color:#646970}
		.op-step p{margin:0 0 10px;color:#50575e}
		.op-step select{min-width:340px;max-width:100%;font-size:15px;padding:4px 8px}
		.op-imgs{display:flex;gap:14px;flex-wrap:wrap}
		.op-img{width:150px;border:1px dashed #c3c4c7;border-radius:10px;padding:8px;text-align:center;background:#f6f7f7}
		.op-img .pv{height:120px;display:flex;align-items:center;justify-content:center;margin-bottom:8px}
		.op-img .pv img{max-width:100%;max-height:120px}
		.op-img .pv span{color:#8c8f94}
		.op-more{margin-top:16px;border:1px solid #dcdcde;border-radius:8px;background:#fbfbfc}
		.op-more summary{cursor:pointer;padding:12px 16px;font-weight:600}
		.op-more .form-table{margin:0 16px 10px;width:calc(100% - 32px)}
	</style>
	<div class="op-step">
		<span class="n">1</span>
		<div>
			<h3><?php esc_html_e( 'Choose the product', 'oyepick' ); ?></h3>
			<p><?php esc_html_e( 'The name, price, picture and "Add to cart" button come from the product automatically.', 'oyepick' ); ?></p>
			<select name="_op_product">
				<option value="0"><?php esc_html_e( '— Choose a product —', 'oyepick' ); ?></option>
				<?php foreach ( $products as $pp ) : ?>
					<option value="<?php echo (int) $pp->ID; ?>" <?php selected( (int) $v['_op_product'], $pp->ID ); ?>><?php echo esc_html( $pp->post_title ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
	<div class="op-step">
		<span class="n">2</span>
		<div>
			<h3><?php esc_html_e( 'Picture', 'oyepick' ); ?> <em><?php esc_html_e( '(optional)', 'oyepick' ); ?></em></h3>
			<p><?php esc_html_e( 'Leave empty to use the product photo. A photo with a transparent background (PNG) looks best. You can add up to 3.', 'oyepick' ); ?></p>
			<div class="op-imgs">
				<?php
				for ( $n = 0; $n < 3; $n++ ) {
					$id  = $imgs[ $n ] ?? 0;
					$src = $id ? wp_get_attachment_image_url( $id, 'medium' ) : '';
					echo '<div class="op-img"><div class="pv">' . ( $src ? '<img src="' . esc_url( $src ) . '" alt="">' : '<span>' . esc_html__( 'No picture', 'oyepick' ) . '</span>' ) . '</div>';
					echo '<input type="hidden" name="_op_imgs[]" value="' . (int) $id . '">';
					echo '<button type="button" class="button op-pick">' . esc_html__( 'Choose', 'oyepick' ) . '</button> ';
					echo '<button type="button" class="button-link op-clear" style="color:#b32d2e">' . esc_html__( 'Remove', 'oyepick' ) . '</button></div>';
				}
				?>
			</div>
		</div>
	</div>
	<div class="op-step" style="border-bottom:0">
		<span class="n">3</span>
		<div>
			<h3><?php esc_html_e( 'Click the blue "Publish" button on the right', 'oyepick' ); ?></h3>
			<p><?php esc_html_e( 'The slide shows on the homepage straight away. That\'s it!', 'oyepick' ); ?></p>
		</div>
	</div>

	<details class="op-more">
		<summary><?php esc_html_e( 'More options (optional) — change the text, buttons or colour', 'oyepick' ); ?></summary>
		<p class="description" style="margin:0 16px"><?php esc_html_e( 'Anything left empty is filled in from the product.', 'oyepick' ); ?></p>
		<table class="form-table" role="presentation">
			<?php
			$row( __( 'Small heading', 'oyepick' ), $input( '_op_eyebrow', $v['_op_eyebrow'], __( 'Empty = product category, e.g. Audio', 'oyepick' ) ), __( 'Short line shown above the big heading.', 'oyepick' ) );
			$row( __( 'Description', 'oyepick' ), '<textarea class="large-text" rows="3" name="_op_text" placeholder="' . esc_attr__( 'Empty = the product\'s short description', 'oyepick' ) . '">' . esc_textarea( $v['_op_text'] ) . '</textarea>' );
			$row( __( 'Label next to price', 'oyepick' ), $input( '_op_pill', $v['_op_pill'], 'Cash on delivery' ) );
			$row( __( 'Main button text', 'oyepick' ), $input( '_op_btn', $v['_op_btn'], 'Add to cart' ), __( 'Leave empty for "Add to cart".', 'oyepick' ) );
			$row( __( 'Second button text', 'oyepick' ), $input( '_op_link_text', $v['_op_link_text'], __( 'Empty = "View details"', 'oyepick' ) ), __( 'Keep it short (1–2 words).', 'oyepick' ) );
			$row( __( 'Second button link', 'oyepick' ), $input( '_op_link_url', $v['_op_link_url'], __( 'Empty = the product page', 'oyepick' ) ), __( 'Copy a category or page address from your browser.', 'oyepick' ) );
			$row( __( 'Badge', 'oyepick' ), '<input type="text" name="_op_chip_icon" value="' . esc_attr( $v['_op_chip_icon'] ) . '" style="width:60px" placeholder="✓"> <input type="text" name="_op_chip_text" value="' . esc_attr( $v['_op_chip_text'] ) . '" style="width:260px" placeholder="5 colours">', __( 'Small badge shown under the description (icon/emoji + text). Leave empty to hide.', 'oyepick' ) );
			$col = '';
			foreach ( array( '' => __( 'Automatic', 'oyepick' ), 'mint' => __( 'Mint green', 'oyepick' ), 'sand' => __( 'Sand / beige', 'oyepick' ) ) as $k => $l ) {
				$col .= '<label style="margin-right:18px"><input type="radio" name="_op_color" value="' . esc_attr( $k ) . '"' . checked( $v['_op_color'], $k, false ) . '> ' . esc_html( $l ) . '</label>';
			}
			$row( __( 'Background colour', 'oyepick' ), $col, __( 'Automatic switches between green and beige.', 'oyepick' ) );
			?>
		</table>
	</details>
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
					var f=wp.media({title:'Choose slide picture',library:{type:'image'},button:{text:'Use this picture'},multiple:false});
					f.on('select',function(){ var a=f.state().get('selection').first().toJSON();
						var u=(a.sizes&&(a.sizes.medium||a.sizes.full))?(a.sizes.medium||a.sizes.full).url:a.url;
						box.find('input').val(a.id); box.find('.pv').html('<img src=\"'+u+'\" alt=\"\">'); });
					f.open();
				});
				$(document).on('click','.op-clear',function(e){ e.preventDefault(); var box=$(this).closest('.op-img');
					box.find('input').val(0); box.find('.pv').html('<span>No picture</span>'); });
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
		$color = sanitize_key( $_POST['_op_color'] ?? '' );
		update_post_meta( $post_id, '_op_color', in_array( $color, array( 'mint', 'sand' ), true ) ? $color : '' );
		// phpcs:enable
	}
);

/* ------------------------------------------------------------------ *
 * Slide list: picture, product, shown/hidden, Move up/down, Hide/Show
 * ------------------------------------------------------------------ */
add_filter(
	'manage_op_slide_posts_columns',
	function ( $cols ) {
		return array(
			'cb'        => $cols['cb'],
			'op_img'    => __( 'Picture', 'oyepick' ),
			'title'     => __( 'Heading', 'oyepick' ),
			'op_prod'   => __( 'Product', 'oyepick' ),
			'op_status' => __( 'On homepage?', 'oyepick' ),
		);
	}
);
add_action(
	'manage_op_slide_posts_custom_column',
	function ( $col, $id ) {
		if ( 'op_img' === $col ) {
			$imgs = array_filter( (array) get_post_meta( $id, '_op_imgs', true ) );
			$aid  = $imgs ? (int) reset( $imgs ) : 0;
			if ( ! $aid && ( $pid = (int) get_post_meta( $id, '_op_product', true ) ) ) { // phpcs:ignore
				$aid = (int) get_post_thumbnail_id( $pid );
			}
			if ( $aid ) {
				echo wp_get_attachment_image( $aid, array( 64, 64 ), false, array( 'style' => 'width:64px;height:64px;object-fit:contain;background:#f0f0f1;border-radius:8px' ) );
			}
		} elseif ( 'op_prod' === $col ) {
			$pid = (int) get_post_meta( $id, '_op_product', true );
			$p   = ( $pid && function_exists( 'wc_get_product' ) ) ? wc_get_product( $pid ) : null;
			echo $p ? esc_html( $p->get_name() ) . '<br><span style="color:#646970">' . wp_kses_post( $p->get_price_html() ) . '</span>' : '—';
		} elseif ( 'op_status' === $col ) {
			echo 'publish' === get_post_status( $id )
				? '<span style="color:#1f8f4e;font-weight:600">' . esc_html__( '✓ Showing', 'oyepick' ) . '</span>'
				: '<span style="color:#996800;font-weight:600">' . esc_html__( 'Hidden', 'oyepick' ) . '</span>';
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

function oyepick_slide_action_url( $action, $args ) {
	return wp_nonce_url( add_query_arg( array_merge( array( 'action' => $action ), $args ), admin_url( 'admin-post.php' ) ), $action . '_' . reset( $args ) );
}

add_filter(
	'post_row_actions',
	function ( $actions, $post ) {
		if ( 'op_slide' === $post->post_type ) {
			unset( $actions['inline hide-if-no-js'] ); // no Quick Edit, it only confuses
			if ( current_user_can( 'edit_post', $post->ID ) ) {
				$new = array(
					'op_up'   => '<a href="' . esc_url( oyepick_slide_action_url( 'op_slide_move', array( 'slide' => $post->ID, 'dir' => 'up' ) ) ) . '">' . esc_html__( '↑ Move up', 'oyepick' ) . '</a>',
					'op_down' => '<a href="' . esc_url( oyepick_slide_action_url( 'op_slide_move', array( 'slide' => $post->ID, 'dir' => 'down' ) ) ) . '">' . esc_html__( '↓ Move down', 'oyepick' ) . '</a>',
					'op_vis'  => '<a href="' . esc_url( oyepick_slide_action_url( 'op_slide_toggle', array( 'slide' => $post->ID ) ) ) . '">' . ( 'publish' === $post->post_status ? esc_html__( 'Hide', 'oyepick' ) : esc_html__( 'Show on homepage', 'oyepick' ) ) . '</a>',
				);
				$actions = array_merge( array_slice( $actions, 0, 1, true ), $new, array_slice( $actions, 1, null, true ) );
			}
		} elseif ( 'product' === $post->post_type && 'publish' === $post->post_status && current_user_can( 'edit_pages' ) ) {
			$sid = oyepick_slide_for_product( $post->ID );
			$actions['op_slide'] = $sid
				? '<a href="' . esc_url( get_edit_post_link( $sid ) ) . '">' . esc_html__( '✓ In homepage slider', 'oyepick' ) . '</a>'
				: '<a href="' . esc_url( oyepick_slide_action_url( 'op_slide_add', array( 'product' => $post->ID ) ) ) . '" style="font-weight:600">' . esc_html__( '+ Add to homepage slider', 'oyepick' ) . '</a>';
		}
		return $actions;
	},
	20,
	2
);

/* Products list: always-visible "Homepage slider" column with a one-click button */
add_filter(
	'manage_edit-product_columns',
	function ( $cols ) {
		$out = array();
		foreach ( $cols as $k => $v ) {
			$out[ $k ] = $v;
			if ( 'price' === $k ) {
				$out['op_slider'] = __( 'Homepage slider', 'oyepick' );
			}
		}
		if ( ! isset( $out['op_slider'] ) ) {
			$out['op_slider'] = __( 'Homepage slider', 'oyepick' );
		}
		return $out;
	},
	20
);
add_action(
	'manage_product_posts_custom_column',
	function ( $col, $id ) {
		if ( 'op_slider' !== $col || ! current_user_can( 'edit_pages' ) ) {
			return;
		}
		if ( 'publish' !== get_post_status( $id ) ) {
			echo '—';
			return;
		}
		$sid = oyepick_slide_for_product( $id );
		if ( $sid ) {
			$shown = 'publish' === get_post_status( $sid );
			echo '<a href="' . esc_url( get_edit_post_link( $sid ) ) . '" style="color:' . ( $shown ? '#1f8f4e' : '#996800' ) . ';font-weight:600">' . ( $shown ? esc_html__( '✓ In slider', 'oyepick' ) : esc_html__( 'Hidden', 'oyepick' ) ) . '</a>';
		} else {
			echo '<a class="button button-small" href="' . esc_url( oyepick_slide_action_url( 'op_slide_add', array( 'product' => $id ) ) ) . '">' . esc_html__( '+ Add', 'oyepick' ) . '</a>';
		}
	},
	10,
	2
);
add_action(
	'admin_head-edit.php',
	function () {
		if ( 'product' === get_current_screen()->post_type ) {
			echo '<style>.column-op_slider{width:110px}</style>';
		}
	}
);

/* Products → "+ Add to homepage slider" */
add_action(
	'admin_post_op_slide_add',
	function () {
		$pid = absint( $_GET['product'] ?? 0 ); // phpcs:ignore
		check_admin_referer( 'op_slide_add_' . $pid );
		if ( ! current_user_can( 'edit_pages' ) || 'product' !== get_post_type( $pid ) ) {
			wp_die( esc_html__( 'Sorry, you cannot do that.', 'oyepick' ) );
		}
		$sid = oyepick_slide_for_product( $pid );
		if ( ! $sid ) {
			$sid = oyepick_slide_create_for_product( $pid );
		}
		wp_safe_redirect( admin_url( 'edit.php?post_type=op_slide&op_msg=added&op_slide=' . $sid ) );
		exit;
	}
);

/* Slide list → Move up / Move down */
add_action(
	'admin_post_op_slide_move',
	function () {
		$id = absint( $_GET['slide'] ?? 0 ); // phpcs:ignore
		check_admin_referer( 'op_slide_move_' . $id );
		if ( ! current_user_can( 'edit_post', $id ) ) {
			wp_die( esc_html__( 'Sorry, you cannot do that.', 'oyepick' ) );
		}
		$ids = wp_list_pluck( oyepick_slides_all(), 'ID' );
		$pos = array_search( $id, $ids, true );
		if ( false !== $pos ) {
			$to = 'up' === ( $_GET['dir'] ?? '' ) ? $pos - 1 : $pos + 1; // phpcs:ignore
			if ( $to >= 0 && $to < count( $ids ) ) {
				$ids[ $pos ] = $ids[ $to ];
				$ids[ $to ]  = $id;
			}
			global $wpdb;
			foreach ( $ids as $order => $sid ) {
				$wpdb->update( $wpdb->posts, array( 'menu_order' => $order ), array( 'ID' => $sid ) ); // phpcs:ignore
				clean_post_cache( $sid );
			}
		}
		wp_safe_redirect( admin_url( 'edit.php?post_type=op_slide&op_msg=moved' ) );
		exit;
	}
);

/* Slide list → Hide / Show on homepage */
add_action(
	'admin_post_op_slide_toggle',
	function () {
		$id = absint( $_GET['slide'] ?? 0 ); // phpcs:ignore
		check_admin_referer( 'op_slide_toggle_' . $id );
		if ( ! current_user_can( 'edit_post', $id ) || 'op_slide' !== get_post_type( $id ) ) {
			wp_die( esc_html__( 'Sorry, you cannot do that.', 'oyepick' ) );
		}
		$show = 'publish' !== get_post_status( $id );
		wp_update_post( array( 'ID' => $id, 'post_status' => $show ? 'publish' : 'draft' ) );
		wp_safe_redirect( admin_url( 'edit.php?post_type=op_slide&op_msg=' . ( $show ? 'shown' : 'hidden' ) ) );
		exit;
	}
);

/* Help box + messages on the slide list */
add_action(
	'admin_notices',
	function () {
		$s = get_current_screen();
		if ( ! $s || 'edit-op_slide' !== $s->id ) {
			return;
		}
		$msgs = array(
			'added'  => __( 'Slide added. It is now showing on the homepage (as the last slide).', 'oyepick' ),
			'moved'  => __( 'Order saved.', 'oyepick' ),
			'shown'  => __( 'The slide is showing on the homepage again.', 'oyepick' ),
			'hidden' => __( 'The slide is hidden from the homepage. Click "Show on homepage" to bring it back.', 'oyepick' ),
		);
		$msg = sanitize_key( $_GET['op_msg'] ?? '' ); // phpcs:ignore
		if ( isset( $msgs[ $msg ] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $msgs[ $msg ] ) . '</p></div>';
		}
		echo '<div class="notice notice-info"><p><strong>' . esc_html__( 'How to add a slide:', 'oyepick' ) . '</strong> '
			. esc_html__( 'go to', 'oyepick' ) . ' <a href="' . esc_url( admin_url( 'edit.php?post_type=product' ) ) . '">' . esc_html__( 'Products', 'oyepick' ) . '</a>, '
			. esc_html__( 'and click "+ Add" in the "Homepage slider" column next to the product. Or click "Add new slide" above and choose the product.', 'oyepick' ) . '<br>'
			. esc_html__( 'Use "↑ Move up / ↓ Move down" to change the order and "Hide" to remove a slide from the homepage without deleting it.', 'oyepick' ) . '</p></div>';
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
			'numberposts' => 30,
			'orderby'     => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
		)
	);
	$out = array();
	foreach ( $posts as $sp ) {
		$m       = function ( $k ) use ( $sp ) {
			return get_post_meta( $sp->ID, $k, true );
		};
		$product = ( $m( '_op_product' ) && function_exists( 'wc_get_product' ) ) ? wc_get_product( (int) $m( '_op_product' ) ) : null;
		if ( $product && 'publish' !== $product->get_status() ) {
			continue; // product deleted or unpublished: skip its slide
		}
		$imgs  = array();
		$photo = true; // only normal photos (no transparent cut-out) -> shown as a rounded card
		$cls   = array( 'a', 'b', 'c' );
		foreach ( array_slice( array_filter( array_map( 'intval', (array) $m( '_op_imgs' ) ) ), 0, 3 ) as $aid ) {
			$url = wp_get_attachment_image_url( $aid, 'large' );
			if ( $url ) {
				$imgs[ $cls[ count( $imgs ) ] ] = $url;
				if ( 'image/jpeg' !== get_post_mime_type( $aid ) ) {
					$photo = false;
				}
			}
		}
		if ( ! $imgs && $product && $product->get_image_id() ) {
			$imgs['a'] = wp_get_attachment_image_url( $product->get_image_id(), 'large' );
			$photo     = 'image/jpeg' === get_post_mime_type( $product->get_image_id() );
		}
		$stage = $m( '_op_stage' );
		if ( ! $stage ) {
			$stage = 'st-auto st-n' . max( 1, count( $imgs ) ) . ( $imgs && $photo ? ' st-photo' : '' );
		}

		$color = $m( '_op_color' );
		if ( ! in_array( $color, array( 'mint', 'sand' ), true ) ) {
			$color = count( $out ) % 2 ? 'sand' : 'mint';
		}
		$eyebrow = (string) $m( '_op_eyebrow' );
		if ( '' === $eyebrow && $product ) {
			$terms   = get_the_terms( $product->get_id(), 'product_cat' );
			$eyebrow = ( $terms && ! is_wp_error( $terms ) ) ? end( $terms )->name : '';
		}
		$text = (string) $m( '_op_text' );
		if ( '' === $text && $product ) {
			$text = wp_trim_words( wp_strip_all_tags( $product->get_short_description() ? $product->get_short_description() : $product->get_description() ), 22 );
		}
		$link_url  = (string) $m( '_op_link_url' );
		$link_text = (string) $m( '_op_link_text' );
		if ( $product && '' === $link_url && '' === $link_text ) {
			$link_url  = get_permalink( $product->get_id() );
			$link_text = __( 'View details', 'oyepick' );
		} elseif ( '' === $link_url ) {
			$link_url = $product ? get_permalink( $product->get_id() ) : oyepick_shop_url();
		}
		$title = get_the_title( $sp );
		if ( '' === trim( $title ) && $product ) {
			$title = $product->get_name();
		}

		$out[] = array(
			'theme'   => 'sand' === $color ? 's-sand' : 's-mint',
			'eyebrow' => $eyebrow,
			'title'   => $title,
			'text'    => $text,
			'pill'    => (string) $m( '_op_pill' ),
			'product' => $product ?: null,
			'label'   => $m( '_op_btn' ) ? $m( '_op_btn' ) : __( 'Add to cart', 'oyepick' ),
			'link'    => array( $link_url, $link_text ),
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
