<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function tpapPutProductOnSale( $stepParams, $stateParams ) {

	// Initialize product_id and percentage to null
	$product_id = null;
	$percentage = null;

	// Check if product_id is set in step parameters
	if ( isset( $stepParams['product_id'] ) && $stepParams['product_id'] !== null ) {
		$product_id = absint( $stepParams['product_id'] );
	}
	// If product_id is not set in step parameters, check state parameters
	elseif ( isset( $stateParams['product_id'] ) && $stateParams['product_id'] !== null ) {
		$product_id = absint( $stateParams['product_id'] );
	}

	// If product_id is still null, return false
	if ( $product_id === null ) {

		return false;
	}

	// Check if percentage is set in step parameters
	if ( isset( $stepParams['percentage'] ) ) {
		$percentage = abs( floatval( $stepParams['percentage'] ) );
	}

	// If percentage is null, return false
	if ( $percentage === null ) {

		return false;
	}

	// Fetch the WooCommerce product object by its ID
	$product = wc_get_product( $product_id );

	// If product is not found, return false
	if ( ! $product ) {

		return false;
	}

	// Calculate the sale price based on the regular price and given percentage
	$regular_price = $product->get_regular_price();
	$sale_price = $regular_price - ( ( $percentage / 100 ) * $regular_price );

	// Set the calculated sale price (as a string)
	$product->set_sale_price( strval( $sale_price ) );

	// Save the changes to the product
	$product->save();

	// Re-fetch the product to verify if the sale price was successfully updated
	$updated_product = wc_get_product( $product_id );

	$tolerance = 0.01;  // Adjust the tolerance as needed

	// Check if the sale price was successfully updated
	if ( $updated_product && abs( floatval( $updated_product->get_sale_price() ) - floatval( $sale_price ) ) < $tolerance ) {

		// Sale price was successfully updated, return the product_id
		return [ 
			'product_id' => $product_id
		];
	} else {

		// Sale price update failed, return false
		return false;
	}
}

