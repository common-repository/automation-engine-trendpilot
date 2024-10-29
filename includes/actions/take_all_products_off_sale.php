<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


// returns true if the products were taken off sale, and false if they weren't (or if any product fails to be taken off sale)

function tpapTakeAllProductsOffSale( $stepParams, $stateParams ) {
	// Fetch all product IDs from the WooCommerce store
	$args = array(
		'status' => 'publish',
		'limit' => -1,
		'return' => 'ids',
	);
	$product_ids = wc_get_products( $args );

	// If no products are found, return false
	if ( empty( $product_ids ) ) {
		return false;
	}

	// Initialize a variable to keep track of the operation's success
	$all_successful = true;

	// Loop through each product ID and take it off sale
	foreach ( $product_ids as $product_id ) {
		$product = wc_get_product( $product_id );
		if ( $product ) {
			$product->set_sale_price( '' );
			$product->save();

			// Re-fetch the product to verify if the sale price was successfully removed
			$updated_product = wc_get_product( $product_id );

			// If any product fails to update, set $all_successful to false
			if ( ! ( $updated_product && $updated_product->get_sale_price() === '' ) ) {
				$all_successful = false;
			}
		} else {
			// If the product is not found, set $all_successful to false
			$all_successful = false;
		}
	}

	return $all_successful;
}