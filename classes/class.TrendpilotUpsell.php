<?php

namespace AETrendpilot;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class TrendpilotUpsell {
	private $timezone;

	// Constructor to set the timezone property
	public function __construct() {
		$this->timezone = get_option( 'timezone_string' ) ?: 'UTC';
	}

	public function registerHooks() {
		add_action( 'template_redirect', [ $this, 'record_upsell_click' ] );
		add_action( 'wp_footer', [ $this, 'add_upsell_product_script' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_upsell_modal_styles' ] );
		add_action( 'woocommerce_after_single_product_summary', [ $this, 'add_empty_upsell_modal_to_product_page' ] );
		add_filter( 'woocommerce_add_to_cart_redirect', [ $this, 'trendpilot_prevent_redirect_for_upsell' ] );

	}

	public function trendpilot_prevent_redirect_for_upsell( $url ) {

		if ( get_option( 'woocommerce_cart_redirect_after_add' ) === 'no' ) {
			return $url;
		}

		// Get the last added product in the cart
		$cart = WC()->cart->get_cart();
		if ( ! empty( $cart ) ) {
			// Retrieve the last added item
			$cart_item = end( $cart );
			$product_id = $cart_item['product_id'];
			$product = wc_get_product( $product_id );
			$aetp_upsell_product_id = get_option( 'aetp_upsell_product_id', '' );

			if ( (int) $product->get_id() === (int) $aetp_upsell_product_id ) {
				return $url . '?up_pg=1&upsell_nonce=' . wp_create_nonce( 'record_upsell_click' );
			}

			if ( $product ) {
				$permalink = $product->get_permalink();
				return $permalink;
			}
		}

		// Fallback: Return the original URL if no product is found
		return $url;
	}

	public function add_upsell_product_script() {
		if ( is_product() ) {
			$isUpsellEnabled = get_option( 'aetp_enable_upsell_page', false );
			$aetp_upsell_product_id = get_option( 'aetp_upsell_product_id', '' );

			global $product;
			$current_product_id = $product->get_id();
			$redirect_to_cart_after_add = get_option( 'woocommerce_cart_redirect_after_add' );

			if ( ! empty( $aetp_upsell_product_id ) && $isUpsellEnabled && $current_product_id != $aetp_upsell_product_id ) {
				$upsellProduct = wc_get_product( $aetp_upsell_product_id );
				if ( $upsellProduct ) {
					if ( $upsellProduct->is_type( 'variable' ) ) {
						// For variable products, show price range and set 'View Product' button
						$price_html = $upsellProduct->get_price_html();
						$formatted_price = "<span class='price'>{$price_html}</span>";
						$cart_url = get_permalink( $aetp_upsell_product_id ); // Link to the product page
					} else {
						$regular_price = wc_price( $upsellProduct->get_regular_price() );
						$sale_price = wc_price( $upsellProduct->get_sale_price() );
						$formatted_price = $upsellProduct->is_on_sale() ? "<del style='color:#989898'>{$regular_price}</del> <ins>{$sale_price}</ins>" : $regular_price;
						if ( $redirect_to_cart_after_add === 'yes' ) {
							$cart_url = esc_url( wc_get_cart_url() . '?add-to-cart=' . (int) $aetp_upsell_product_id . '&up_pg=1&upsell_nonce=' . wp_create_nonce( 'record_upsell_click' ) );
						} else {
							$permalink = $product->get_permalink();
							$cart_url = esc_url( $permalink . '?add-to-cart=' . (int) $aetp_upsell_product_id . '&up_pg=1&upsell_nonce=' . wp_create_nonce( 'record_upsell_click' ) );
						}
					}

					// Enqueue the upsell modal script
					wp_enqueue_script( 'upsell-modal', plugin_dir_url( __FILE__ ) . '../public/js/upsell-modal.js', array( 'jquery' ), null, true );

					// Localize the upsell product data for JavaScript
					wp_localize_script( 'upsell-modal', 'upsellProductData', array(
						'name' => sanitize_text_field( $upsellProduct->get_name() ),
						'price' => $formatted_price,
						'imageUrl' => esc_url( wp_get_attachment_url( (int) $upsellProduct->get_image_id() ) ),
						'cartUrl' => esc_url( $cart_url ),
						'cartUrlRaw' => esc_url( wc_get_cart_url() ),
						'isVariable' => $upsellProduct->is_type( 'variable' ),
						'redirect_to_cart_after_add' => $redirect_to_cart_after_add,
						'current_product_url' => $product->get_permalink(),
					) );
				}
			}
		}
	}

	public function record_upsell_click() {

		global $wpdb;
		$click_data_table_name = $wpdb->prefix . 'trendpilot_automation_engine_click_data';

		if ( isset( $_GET['up_pg'] ) && $_GET['up_pg'] == '1' ) {


			if ( ! isset( $_GET['upsell_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['upsell_nonce'] ) ), 'record_upsell_click' ) ) {
				wp_die( 'Security check failed' );
			}


			// Get the current date in the site's timezone
			$today = current_time( 'Y-m-d' );

			$existing_entry = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM $click_data_table_name WHERE clicked_date = %s",
					$today
				)
			);

			if ( $existing_entry ) {
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE $click_data_table_name SET upsell_clicks = upsell_clicks + 1 WHERE id = %d",
						$existing_entry
					)
				);
			} else {
				$wpdb->insert(
					$click_data_table_name,
					array(
						'upsell_clicks' => 1,
						'clicked_date' => $today
					),
					array( '%d', '%s' )
				);
			}
		}
	}


	public function enqueue_upsell_modal_styles() {
		wp_enqueue_style( 'upsell-modal-styles', plugin_dir_url( __FILE__ ) . '../public/css/upsell-modal.css' );
	}

	public function add_empty_upsell_modal_to_product_page() {
		?>
		<div id="upsellModal" class="modal" style="display:none;">
			<div class="aetp-modal-content">
				<!-- Content will be loaded here by JavaScript -->
			</div>
			<button class="modal-close">Close</button>
		</div>
		<?php
	}

}
