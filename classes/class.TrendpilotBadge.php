<?php

namespace AETrendpilot;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class TrendpilotBadge {

	public function registerHooks() {

		add_action( 'wp_enqueue_scripts', [ $this, 'ae_enqueue_badge_styles' ] );
		add_action( 'add_meta_boxes', [ $this, 'ae_add_badge_meta_box' ] );
		add_action( 'save_post', [ $this, 'ae_save_product_badge_meta_box' ] );
		add_action( 'woocommerce_before_shop_loop_item', [ $this, 'ae_display_product_badge' ] );

		add_action( 'woocommerce_product_thumbnails', [ $this, 'ae_display_product_badge_on_image_single' ], 5 );

		if ( get_option( 'aetp_enable_badges', 1 ) ) {
			add_filter( 'woocommerce_sale_flash', '__return_false' );
			$this->ae_hide_woocommerce_blocks_sale_badge(); // Call the new method to hide block sale badges
		}
	}

	public function ae_display_product_badge_on_image_single() {
		if ( ! is_product() || ! get_option( 'aetp_enable_badges', 1 ) ) {
			return;
		}

		global $product;
		$badge_text = get_post_meta( $product->get_id(), 'ae_product_badge', true );
		$is_on_sale = $product->is_on_sale();

		// Use 'Sale' if the product is on sale and no custom badge is set
		if ( $is_on_sale && ( $badge_text === '' || $badge_text === 'none' ) ) {
			$badge_text = 'Sale';
		} elseif ( ! $is_on_sale && strtolower( $badge_text ) === 'sale' ) {
			$badge_text = 'none';
		}

		if ( $badge_text && $badge_text !== 'none' ) {
			echo '<div style="top: 5px;left: 5px;" class="ae-product-badge-single">' . esc_html( ucfirst( $badge_text ) ) . '</div>';
		}
	}

	// Method to hide WooCommerce Blocks sale badge with CSS
	private function ae_hide_woocommerce_blocks_sale_badge() {
		add_action( 'wp_enqueue_scripts', function () {
			wp_add_inline_style(
				'wp-block-library',
				'.wc-block-components-product-sale-badge { display: none !important; }'
			);
		} );
	}

	public function ae_add_badge_meta_box() {
		add_meta_box(
			'ae_product_badge',
			'Product Badge',
			[ $this, 'ae_product_badge_meta_box_callback' ],
			'product',
			'side',
			'high'
		);
	}
	//add_action('add_meta_boxes', 'ae_add_badge_meta_box');

	function ae_product_badge_meta_box_callback( $post ) {
		// Add nonce for security and authentication.
		wp_nonce_field( basename( __FILE__ ), 'ae_product_badge_nonce' );

		// Retrieve current badge and custom text if any.
		$current_badge = get_post_meta( $post->ID, 'ae_product_badge', true );
		$current_custom_text = get_post_meta( $post->ID, 'ae_product_badge_custom_text', true );

		// Options for badges.
		$badges = array(
			'none' => 'None',
			'sale' => 'Sale',
			'popular' => 'Popular',
			'new' => 'New',
			'custom' => 'Custom'
		);

		if ( ! array_key_exists( $current_badge, $badges ) && ! empty( $current_badge ) ) {
			$current_badge = 'custom';
		}

		// Dropdown for selecting badge.
		echo '<select name="ae_product_badge" id="ae_product_badge">';
		foreach ( $badges as $value => $label ) {

			echo '<option value="' . esc_attr( $value ) . '"' . selected( $current_badge, $value, false ) . '>' . esc_html( $label ) . '</option>';

		}
		echo '</select>';

		// Input for custom badge text.
		echo '<p>';
		echo '<label for="ae_product_badge_custom_text">Custom Badge Text:</label>';
		echo '<input type="text" id="ae_product_badge_custom_text" name="ae_product_badge_custom_text" value="' . esc_attr( $current_custom_text ) . '" />';
		echo '</p>';
	}

	function ae_save_product_badge_meta_box( $post_id ) {
		// Verify nonce.
		if ( ! isset( $_POST['ae_product_badge_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ae_product_badge_nonce'] ) ), basename( __FILE__ ) ) ) {
			return $post_id;
		}

		// Check autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Check permissions.
		if ( 'product' !== $_POST['post_type'] || ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		// Define the badge text as the dropdown value initially
		$badge_text = isset( $_POST['ae_product_badge'] ) ? sanitize_text_field( $_POST['ae_product_badge'] ) : '';

		// If 'custom' is selected, override the badge text with the custom input text
		if ( $badge_text === 'custom' && isset( $_POST['ae_product_badge_custom_text'] ) ) {
			$badge_text = sanitize_text_field( $_POST['ae_product_badge_custom_text'] );

		}

		// Update the badge text meta field in the database.
		update_post_meta( $post_id, 'ae_product_badge', $badge_text );

		// Update the custom text
		update_post_meta( $post_id, 'ae_product_badge_custom_text', sanitize_text_field( $_POST['ae_product_badge_custom_text'] ) );
	}
	//add_action('save_post', 'ae_save_product_badge_meta_box');

	public function ae_display_product_badge( $bypass_option_check = false ) {
		if ( ! $bypass_option_check && ! get_option( 'aetp_enable_badges', 1 ) )
			return;  // Exit if badges are disabled and not bypassing the option check

		global $product;
		$badge_text = get_post_meta( $product->get_id(), 'ae_product_badge', true );
		$is_on_sale = $product->is_on_sale();

		if ( $is_on_sale && ( $badge_text === '' || $badge_text === 'none' ) ) {
			$badge_text = 'Sale';
		} elseif ( ! $is_on_sale && strtolower( $badge_text ) === 'sale' ) {
			$badge_text = 'none';
		}

		if ( $badge_text && $badge_text !== 'none' ) {
			echo '<div style="margin-top: 5px;margin-left: 5px;" class="ae-product-badge">' . esc_html( ucfirst( $badge_text ) ) . '</div>';
		}
	}

	// Function to get an option with a default value
	function get_ae_option( $option_name, $default = '' ) {
		$option = get_option( $option_name );
		return ( $option !== false ) ? $option : $default;
	}

	public function ae_enqueue_badge_styles() {
		wp_register_style( 'ae-custom-badge-styles', false );
		wp_enqueue_style( 'ae-custom-badge-styles' );

		// Retrieve options with defaults and fallback if empty
		$font_size = esc_attr( $this->get_ae_option( 'aetp_badge_font_size', '' ) ) ?: '14';
		$badge_color = esc_attr( $this->get_ae_option( 'aetp_badge_color', '' ) ) ?: '#FFA500';
		$border_radius = esc_attr( $this->get_ae_option( 'aetp_badge_border_radius', '' ) ) ?: '100';
		$font_color = esc_attr( $this->get_ae_option( 'aetp_badge_font_color', '' ) ) ?: '#FFFFFF';

		// Create the dynamic CSS with retrieved or default values
		$custom_css = "
			.ae-product-badge, .ae-product-badge-single {
				position: absolute;
				background-color: {$badge_color};
				color: {$font_color};
				font-size: {$font_size}px;
				z-index: 100;
				font-weight: 600;
				border-radius: {$border_radius}px;
				padding: 14px 11px;
				text-align: center;
			}";

		// Add inline CSS to the registered style
		wp_add_inline_style( 'ae-custom-badge-styles', wp_strip_all_tags( $custom_css ) );
	}

}
