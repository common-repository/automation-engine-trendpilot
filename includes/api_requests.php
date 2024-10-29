<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! function_exists( 'aetp_get_all_products_api' ) ) {
	function aetp_get_all_products_api() {

		// Get all products using WooCommerce's product query
		$args = array(
			'status' => 'publish',  // Only get published products
			'limit' => -1,         // Get all products
			'orderby' => 'date',     // Order by date
			'order' => 'DESC',     // Descending order
			'return' => 'objects',  // Return product objects
		);

		$products = wc_get_products( $args );

		// If there are no products, return an error
		if ( empty( $products ) ) {
			return new WP_Error( 'no_products', 'No products found.' );
		}

		$all_products = [];

		// Loop through products and store relevant data
		foreach ( $products as $product ) {
			$all_products[] = array(
				'id' => $product->get_id(),
				'name' => $product->get_name(),
				'price' => $product->get_price(),
				'regular_price' => $product->get_regular_price(),
				'sale_price' => $product->get_sale_price(),
				'stock_status' => $product->get_stock_status(),
				'categories' => wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) ),
			);
		}

		// Return all the products data
		return $all_products;


	}
}

if ( ! function_exists( 'aetp_get_all_categories_api' ) ) {
	function aetp_get_all_categories_api() {

		// Arguments for retrieving WooCommerce product categories
		$args = array(
			'taxonomy' => 'product_cat', // WooCommerce product categories
			'orderby' => 'name',        // Order by name
			'order' => 'ASC',         // Ascending order
			'hide_empty' => false,         // Include empty categories
			'number' => 0,             // Retrieve all categories
		);

		// Get all product categories
		$categories = get_terms( $args );

		// If no categories were found, return an error
		if ( empty( $categories ) || is_wp_error( $categories ) ) {
			return new WP_Error( 'no_categories', 'No categories found or an error occurred.' );
		}

		$all_categories = [];

		// Loop through categories and store relevant data in the array format
		foreach ( $categories as $category ) {
			// Loop through categories and store relevant data in the array format
			$all_categories[] = array(
				'id' => $category->term_id,
				'name' => $category->name
			);
		}

		// Return the formatted array
		return $all_categories;
	}
}
