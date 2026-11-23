<?php
/**
 * Small helpers shared by templates.
 *
 * @package OyePick
 */

defined( 'ABSPATH' ) || exit;

/**
 * Theme option (Customizer) with defaults.
 */
function oyepick_opt( $key ) {
	$defaults = array(
		'topbar_left'    => 'CASH ON DELIVERY',
		'topbar_right'   => 'FAST DELIVERY ALL OVER PAKISTAN',
		'whatsapp'       => '923233901906',
		'instagram'      => 'https://www.instagram.com/oyepicstore',
		'facebook'       => 'https://www.facebook.com/share/1A73b7x7w9/',
		'tiktok'         => 'https://www.tiktok.com/@oyepick13',
		'address'        => '', // add later in Customizer → OyePick Store; footer/homepage show it automatically
		'popup_enabled'  => 1,
		'popup_interval' => 25,
	);
	$value = get_theme_mod( 'oyepick_' . $key, null );
	return null === $value ? ( $defaults[ $key ] ?? '' ) : $value;
}

function oyepick_img( $path ) {
	// ?v= busts browser/CDN caches (Hostinger caches images for 7 days) whenever the theme version changes.
	return OYEPICK_URI . '/assets/images/' . ltrim( $path, '/' ) . '?v=' . OYEPICK_VERSION;
}

function oyepick_wa_link( $text = '' ) {
	$num = preg_replace( '/\D+/', '', (string) oyepick_opt( 'whatsapp' ) );
	return 'https://wa.me/' . $num . ( $text ? '?text=' . rawurlencode( $text ) : '' );
}

/**
 * Category link by slug, falling back to the shop page.
 */
function oyepick_cat_link( $slug ) {
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( $term && ! is_wp_error( $term ) ) {
		return get_term_link( $term );
	}
	return function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
}

function oyepick_shop_url() {
	return function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
}

/**
 * Product by SKU (or null).
 *
 * @return WC_Product|null
 */
function oyepick_product( $sku ) {
	if ( ! function_exists( 'wc_get_product_id_by_sku' ) ) {
		return null;
	}
	$id = wc_get_product_id_by_sku( $sku );
	return $id ? wc_get_product( $id ) : null;
}

/**
 * AJAX-capable "Add to cart" link for any product (used outside the loop, e.g. the slider).
 */
function oyepick_add_to_cart_button( $product, $label = '', $class = 'btn btn-primary' ) {
	if ( ! $product ) {
		return '<a class="' . esc_attr( $class ) . '" href="' . esc_url( oyepick_shop_url() ) . '">' . esc_html__( 'Shop now', 'oyepick' ) . '</a>';
	}
	if ( '' === $product->get_price() ) {
		return '<a class="' . esc_attr( $class ) . '" href="' . esc_url( oyepick_wa_link( 'Assalam o Alaikum, price batayein: ' . $product->get_name() ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'Ask price on WhatsApp', 'oyepick' ) . '</a>';
	}
	$ajax = $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock();
	return sprintf(
		'<a href="%s" data-quantity="1" data-product_id="%d" data-product_sku="%s" class="%s" rel="nofollow">%s</a>',
		esc_url( $product->add_to_cart_url() ),
		$product->get_id(),
		esc_attr( $product->get_sku() ),
		esc_attr( $class . ( $ajax ? ' add_to_cart_button ajax_add_to_cart' : '' ) ),
		esc_html( $label ? $label : $product->add_to_cart_text() )
	);
}

function oyepick_price( $product, $fallback = '' ) {
	if ( $product && '' !== $product->get_price() ) {
		return wp_strip_all_tags( wc_price( $product->get_price() ) );
	}
	return $fallback;
}

/**
 * Star rating markup (0–5).
 */
function oyepick_stars( $rating ) {
	$rating = max( 0, min( 5, (float) $rating ) );
	$out    = '<span class="stars" role="img" aria-label="' . esc_attr( sprintf( __( 'Rated %s out of 5', 'oyepick' ), $rating ) ) . '">';
	for ( $i = 1; $i <= 5; $i++ ) {
		$out .= '<i class="' . ( $rating >= $i - 0.25 ? 'on' : '' ) . '">★</i>';
	}
	return $out . '</span>';
}

function oyepick_icon( $name, $size = 22 ) {
	$paths = array(
		'cash'     => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/>',
		'truck'    => '<path d="M1 3h15v13H1zM16 8h4l3 3v5h-7z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
		'shield'   => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/>',
		'chat'     => '<path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>',
		'star'     => '<path d="M12 2l3 6.5 7 .8-5.2 4.8 1.5 7L12 17.6 5.7 21l1.5-7L2 9.3l7-.8z"/>',
		'pin'      => '<path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>',
		'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0116 0"/>',
		'heart'    => '<path d="M20.8 4.6a5.5 5.5 0 00-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 00-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 000-7.8z"/>',
		'cart'     => '<path d="M6 6h15l-1.5 9h-12z"/><path d="M6 6L5 3H2"/><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/>',
		'search'   => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',
		'menu'     => '<path d="M3 6h18M3 12h18M3 18h18"/>',
		'close'    => '<path d="M6 6l12 12M18 6L6 18"/>',
		'arrow'    => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'left'     => '<path d="M15 6l-6 6 6 6"/>',
		'right'    => '<path d="M9 6l6 6-6 6"/>',
		'down'     => '<path d="M6 9l6 6 6-6"/>',
		'bolt'     => '<path d="M13 2L3 14h9l-1 8 10-12h-9z"/>',
		'check'    => '<path d="M20 6L9 17l-5-5"/>',
		'plus'     => '<path d="M12 5v14M5 12h14"/>',
	);
	return '<svg width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ( $paths[ $name ] ?? '' ) . '</svg>';
}

function oyepick_social_icon( $name, $size = 18 ) {
	$icons = array(
		'instagram' => '<svg width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>',
		'facebook'  => '<svg width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M14 9h3V5h-3a4 4 0 00-4 4v2H8v4h2v7h4v-7h3l1-4h-4V9.5c0-.3.2-.5.5-.5z"/></svg>',
		'tiktok'    => '<svg width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16.6 5.8A4.3 4.3 0 0115.5 3h-3.1v12.4a2.6 2.6 0 11-1.8-2.5V9.7a5.7 5.7 0 104.9 5.7V9.1a7.3 7.3 0 004.3 1.4V7.4a4.3 4.3 0 01-3.2-1.6z"/></svg>',
		'whatsapp'  => '<svg width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.5 14.4c-.3-.1-1.8-.9-2-1-.3-.1-.5-.1-.7.1-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.1-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6l.4-.5c.2-.2.2-.3.3-.5.1-.2 0-.4 0-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.1.2 2.1 3.2 5.1 4.5 2.5 1 3 .8 3.6.7.6-.1 1.8-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.1-.3-.2-.6-.4zM12 2a10 10 0 00-8.6 15.1L2 22l5-1.3A10 10 0 1012 2z"/></svg>',
	);
	return sprintf( $icons[ $name ] ?? '', (int) $size );
}

/**
 * The logo: Customizer custom logo if set, else the bundled SVG.
 */
function oyepick_logo( $variant = 'dark' ) {
	if ( 'dark' === $variant && has_custom_logo() ) {
		$id  = get_theme_mod( 'custom_logo' );
		$src = wp_get_attachment_image_url( $id, 'full' );
		return '<a href="' . esc_url( home_url( '/' ) ) . '" class="logo" rel="home"><img class="logo-full" src="' . esc_url( $src ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '"></a>';
	}
	$file = 'white' === $variant ? 'oyepick-logo-white.svg' : 'oyepick-logo.svg';
	return '<a href="' . esc_url( home_url( '/' ) ) . '" class="logo" rel="home"><img class="logo-full" src="' . esc_url( oyepick_img( 'brand/' . $file ) ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '" width="259" height="64"></a>';
}

/**
 * Menu built from product categories, used until a "Main menu" is assigned.
 */
function oyepick_fallback_menu( $class = 'menu' ) {
	echo '<ul class="' . esc_attr( $class ) . '">';
	echo '<li class="' . ( is_front_page() ? 'current-menu-item' : '' ) . '"><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'oyepick' ) . '</a></li>';
	echo '<li><a href="' . esc_url( oyepick_shop_url() ) . '">' . esc_html__( 'Shop All', 'oyepick' ) . '</a></li>';
	$parents = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => 0, 'hide_empty' => false, 'exclude' => array( (int) get_option( 'default_product_cat' ) ) ) );
	if ( ! is_wp_error( $parents ) ) {
		foreach ( $parents as $p ) {
			$kids = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => $p->term_id, 'hide_empty' => false ) );
			echo '<li class="' . ( $kids ? 'menu-item-has-children' : '' ) . '"><a href="' . esc_url( get_term_link( $p ) ) . '">' . esc_html( $p->name ) . '</a>';
			if ( $kids && ! is_wp_error( $kids ) ) {
				echo '<ul class="sub-menu">';
				foreach ( $kids as $k ) {
					echo '<li><a href="' . esc_url( get_term_link( $k ) ) . '">' . esc_html( $k->name ) . '</a></li>';
				}
				echo '</ul>';
			}
			echo '</li>';
		}
	}
	$contact = get_page_by_path( 'contact-us' );
	if ( $contact ) {
		echo '<li><a href="' . esc_url( get_permalink( $contact ) ) . '">' . esc_html__( 'Contact us', 'oyepick' ) . '</a></li>';
	}
	echo '</ul>';
}
