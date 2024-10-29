<?php

namespace AETrendpilot;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AETrendpilotRecommended {

	private $tpae_enable_disable_recommended;

	function __construct() {
		$this->tpae_enable_disable_recommended = get_option( 'tpae_enable_disable_recommended' );
	}

	public function registerHooks() {

		add_action( 'woocommerce_before_shop_loop_item', [ $this, 'modify_recommended_product_urls' ] );
		add_action( 'template_redirect', [ $this, 'record_recommended_clicks' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_recommended_clicks_script' ] );


		if ( $this->tpae_enable_disable_recommended ) {
			add_filter( 'woocommerce_default_catalog_orderby_options', [ $this, 'addRecommendedToAdmin' ] );
			add_filter( 'woocommerce_catalog_orderby', [ $this, 'addRecommendedToAdmin' ], 10, 1 );
			add_filter( 'woocommerce_catalog_orderby', [ $this, 'addRecommendedOrderOption' ], 10, 1 );
			add_action( 'pre_get_posts', [ $this, 'sort_products_by_recommended' ] );
			//add_action( 'woocommerce_product_query', [ $this, 'sort_products_by_recommended' ] );

		}

	}

	public function addRecommendedToAdmin( $sortby ) {
		$sortby['recommended'] = 'Recommended';
		return $sortby;
	}

	public function addRecommendedOrderOption( $sortby ) {
		$sortby = [ 'recommended' => 'Recommended' ] + $sortby;
		return $sortby;
	}

	public function sort_products_by_recommended( $query ) {
		global $wpdb;

		$is_archive = false;

		// Check if it's the WooCommerce Shop page based on page_id
		$shop_page_id = wc_get_page_id( 'shop' );
		if ( isset( $query->query_vars['page_id'] ) && $query->query_vars['page_id'] == $shop_page_id ) {
			$is_archive = true;
		}

		// Check if it's a WooCommerce archive (product category, tag, or post type archive)
		if (
			( function_exists( 'is_product_category' ) && is_product_category() ) ||
			( function_exists( 'is_product_tag' ) && is_product_tag() ) ||
			( function_exists( 'is_post_type_archive' ) && is_post_type_archive( 'product' ) )
		) {
			$is_archive = true;
		}

		// Check if the 'orderby' query parameter is set to 'recommended'
		// Nonce verification not required as this is a WooCommerce URL parameter
		if ( isset( $_GET['orderby'] ) && $_GET['orderby'] === 'recommended' ) {
			$is_archive = true;
		}

		// Check if we're in the main query, not in the admin, and on a WooCommerce archive page
		if ( ( ! is_admin() && $query->is_main_query() ) && $is_archive ) {

			// Retrieve the sorting option for remaining products
			$remaining_orderby_option = get_option( 'tpae_remaining_products_orderby', 'menu_order' );

			// Set up WooCommerce default ordering based on admin selection
			switch ( $remaining_orderby_option ) {
				case 'date':
					$default_orderby = "$wpdb->posts.post_date DESC";
					break;
				case 'title':
					$default_orderby = "$wpdb->posts.post_title ASC";
					break;
				case 'popularity':
					$meta_key = 'total_sales';
					$default_orderby = "pm.meta_value+0 DESC, $wpdb->posts.post_date DESC";
					$query->set( 'meta_key', $meta_key );
					$query->set( 'orderby', 'meta_value_num' );
					$query->set( 'meta_type', 'NUMERIC' );

					// Add the necessary join to include the wp_postmeta table
					add_filter( 'posts_join', function ($join) use ($wpdb, $meta_key) {
						return $join . " INNER JOIN $wpdb->postmeta AS pm ON ($wpdb->posts.ID = pm.post_id AND pm.meta_key = '$meta_key')";
					} );
					break;
				case 'rating':
					$default_orderby = "meta_value_num DESC"; // Assuming WooCommerce is sorting by '_wc_average_rating'
					$query->set( 'meta_key', '_wc_average_rating' );
					break;
				case 'menu_order':
				default:
					$default_orderby = "$wpdb->posts.menu_order ASC, $wpdb->posts.post_date DESC"; // Default WooCommerce sorting
					break;
			}

			// Nonce verification not required as this is a WooCommerce URL parameter
			$current_orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : get_option( 'woocommerce_default_catalog_orderby' );

			if ( $current_orderby === 'recommended' ) {
				$table_name = sanitize_text_field( $wpdb->prefix . 'trendpilot_automation_engine_recommended_products' );

				add_filter( 'posts_fields', function ($fields) use ($wpdb, $table_name) {
					// Add is_recommended and date_added (timestamp) to query
					$fields .= ", IF($wpdb->posts.ID IN (SELECT product_id FROM $table_name), 1, 0) as is_recommended, 
								(SELECT date_added FROM $table_name WHERE product_id = $wpdb->posts.ID) as recommended_date_added";
					return $fields;
				} );

				add_filter( 'posts_orderby', function ($orderby) use ($default_orderby) {
					// Order by recommended status, then by the date_added column (newer recommendations first)
					$orderby = "is_recommended DESC, recommended_date_added DESC, " . $default_orderby;
					return $orderby;
				} );

			}

		}
	}

	public function is_woocommerce_archive_page( $query ) {
		// Check WooCommerce specific functions
		if ( function_exists( 'is_shop' ) && is_shop() ) {
			return true;
		}

		if ( function_exists( 'is_product_category' ) && is_product_category() ) {
			return true;
		}

		if ( function_exists( 'is_product_tag' ) && is_product_tag() ) {
			return true;
		}

		if ( function_exists( 'is_post_type_archive' ) && is_post_type_archive( 'product' ) ) {
			return true;
		}

		// Fallback: check if the queried post type is 'product' and if it's an archive
		if ( isset( $query->query_vars['post_type'] ) && $query->query_vars['post_type'] === 'product' && $query->is_archive() ) {
			return true;
		}

		return false; // None of the conditions match
	}


	public function add_to_recommended( $product_id ) {

		global $wpdb;
		$table_name = $wpdb->prefix . 'trendpilot_automation_engine_recommended_products';
		$product_id = absint( $product_id );
		// Check if the product is already in the recommended list
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE product_id = %d", $product_id ) ); // db call ok

		// If the product already exists, return true
		if ( $exists > 0 ) {
			return true;
		}

		// Get the value of 'tpae_total_recommended_products' option
		$max_recommended_products = get_option( 'tpae_total_recommended_products', 5 ); // default is 5

		// Get the current count of recommended products
		$total_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" ); // db call ok

		// Remove the oldest recommended product if adding a new one exceeds the max limit
		if ( $total_count >= $max_recommended_products ) {
			$wpdb->query( "DELETE FROM $table_name ORDER BY date_added ASC LIMIT 1" ); // db call ok
		}

		// Add the new product to recommended
		$result = $wpdb->insert(
			$table_name,
			array( 'product_id' => (int) $product_id, 'date_added' => current_time( 'mysql' ) ),
			array( '%d', '%s' )
		);// db call ok

		return $result !== false;
	}

	function tpae_remove_from_recommended( $product_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'trendpilot_automation_engine_recommended_products';

		// Remove the product from the recommended list
		$result = $wpdb->delete(
			$table_name,
			array( 'product_id' => (int) $product_id ),
			array( '%d' )
		); // db call ok

		// Check if the product was successfully removed
		return $result !== false;
	}

	// Step 1: Add query parameter to URLs of recommended products in the shop loop
	//add_action('woocommerce_before_shop_loop_item', 'modify_recommended_product_urls');

	function modify_recommended_product_urls() {
		global $product, $wpdb;

		// Get the ID of the current product in the loop
		$product_id = $product->get_id();

		// Check if the product is recommended by querying your custom table
		$table_name = $wpdb->prefix . 'trendpilot_automation_engine_recommended_products';
		$is_recommended = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE product_id = %d", $product_id ) ); // db call ok

		if ( $is_recommended ) {
			add_filter( 'woocommerce_loop_product_link', [ $this, 'add_rec_click_query_arg' ], 10, 1 );
			remove_filter( 'woocommerce_loop_product_link', [ $this, 'remove_rec_click_query_arg' ], 10 );
		} else {
			add_filter( 'woocommerce_loop_product_link', [ $this, 'remove_rec_click_query_arg' ], 10, 1 );
			remove_filter( 'woocommerce_loop_product_link', [ $this, 'add_rec_click_query_arg' ], 10 );
		}
	}

	function add_rec_click_query_arg( $link ) {
		$nonce = wp_create_nonce( 'rec_click_nonce' );
		$link = add_query_arg( 'rec_click', '1', $link );
		$link = add_query_arg( '_wpnonce', $nonce, $link );
		return $link;
	}


	function remove_rec_click_query_arg( $link ) {
		return $link;
	}

	// Step 2: Hook into template_redirect to capture and record clicks
	//add_action('template_redirect', 'record_recommended_clicks');

	public function record_recommended_clicks() {
		global $wpdb;
		if ( is_single() && 'product' == get_post_type() ) {
			if ( isset( $_GET['rec_click'] ) && $_GET['rec_click'] == 1 ) {
				// Verify nonce
				if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'rec_click_nonce' ) ) {
					wp_die( 'Security check failed' );
				}

				$product_id = get_the_ID();
				$clicked_date = current_time( 'Y-m-d' );

				// Step 3: Check and update the table
				$table_name = $wpdb->prefix . 'trendpilot_automation_engine_recommended_loop_clicks';
				$existing_entry = $wpdb->get_row( $wpdb->prepare(
					"SELECT * FROM $table_name WHERE product_id = %d AND clicked_date = %s",
					intval( $product_id ),
					$clicked_date
				) ); // db call ok

				if ( $existing_entry ) {
					$wpdb->update(
						$table_name,
						array( 'clicks' => $existing_entry->clicks + 1 ),
						array( 'id' => $existing_entry->id ),
						array( '%d' ),
						array( '%d' )
					); // db call ok
				} else {
					$wpdb->insert(
						$table_name,
						array( 'product_id' => $product_id, 'clicks' => 1, 'clicked_date' => $clicked_date ),
						array( '%d', '%d', '%s' )
					); // db call ok
				}
			}
		}
	}

	public function enqueue_recommended_clicks_script() {
		wp_enqueue_script(
			'recommended-clicks-js',
			plugin_dir_url( __FILE__ ) . '../public/js/recommended-clicks.js',
			array(),
			'1.0',
			true
		);
	}

}
