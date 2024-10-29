<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function tpapPutAllProductsOnSale( $stepParams, $stateParams ) {

	// Initialize percentage to null
	$percentage = null;

	// Check if percentage is set in step parameters
	if ( isset( $stepParams['percentage'] ) ) {
		$percentage = abs( floatval( $stepParams['percentage'] ) );
	}

	// If percentage is null, return false
	if ( $percentage === null ) {

		return false;
	}

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

	// Loop through each product ID and put it on sale
	foreach ( $product_ids as $product_id ) {
		$product = wc_get_product( $product_id );
		if ( $product ) {
			// Calculate the sale price based on the regular price and given percentage
			$regular_price = $product->get_regular_price();
			$sale_price = $regular_price - ( ( $percentage / 100 ) * $regular_price );

			try {
				// Set the calculated sale price
				$product->set_sale_price( $sale_price );

				// Save the changes to update the product state
				$product->save();

				// Re-fetch the product to verify if the sale price was successfully updated
				$updated_product = wc_get_product( $product_id );

				$epsilon = 0.01;
				// If any product fails to update, set $all_successful to false
				if ( ! ( $updated_product && abs( floatval( $updated_product->get_sale_price() ) - floatval( $sale_price ) ) < $epsilon ) ) {
					$all_successful = false;

				}
			} catch (Exception $e) {

				$all_successful = false;
			}
		} else {
			// If the product is not found, set $all_successful to false
			$all_successful = false;

		}
	}

	return $all_successful;
}
