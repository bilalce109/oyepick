<?php
/**
 * Customizer: Appearance → Customize → OyePick Store.
 *
 * @package OyePick
 */

defined( 'ABSPATH' ) || exit;

function oyepick_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'oyepick_store',
		array(
			'title'    => __( 'OyePick Store', 'oyepick' ),
			'priority' => 30,
		)
	);

	$fields = array(
		'topbar_left'    => array( __( 'Top bar text (left)', 'oyepick' ), 'text' ),
		'topbar_right'   => array( __( 'Top bar text (right)', 'oyepick' ), 'text' ),
		'whatsapp'       => array( __( 'WhatsApp number (country code, digits only, e.g. 923001234567)', 'oyepick' ), 'text' ),
		'instagram'      => array( __( 'Instagram URL', 'oyepick' ), 'url' ),
		'facebook'       => array( __( 'Facebook URL', 'oyepick' ), 'url' ),
		'tiktok'         => array( __( 'TikTok URL', 'oyepick' ), 'url' ),
		'address'        => array( __( 'Store address', 'oyepick' ), 'textarea' ),
		'popup_enabled'  => array( __( 'Show "recent purchase" notifications (real orders only)', 'oyepick' ), 'checkbox' ),
		'popup_interval' => array( __( 'Seconds between purchase notifications', 'oyepick' ), 'number' ),
	);

	foreach ( $fields as $key => $f ) {
		$sanitize = array(
			'url'      => 'esc_url_raw',
			'checkbox' => 'absint',
			'number'   => 'absint',
			'textarea' => 'sanitize_textarea_field',
		)[ $f[1] ] ?? 'sanitize_text_field';

		$wp_customize->add_setting(
			'oyepick_' . $key,
			array(
				'default'           => oyepick_opt( $key ),
				'sanitize_callback' => $sanitize,
			)
		);
		$wp_customize->add_control(
			'oyepick_' . $key,
			array(
				'label'       => $f[0],
				'section'     => 'oyepick_store',
				'type'        => $f[1],
				'input_attrs' => 'number' === $f[1] ? array( 'min' => 10, 'max' => 300, 'step' => 1 ) : array(),
			)
		);
	}
}
add_action( 'customize_register', 'oyepick_customize_register' );
