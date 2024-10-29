<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function tpapChangeDefaultPrice( $stepParams, $stateParams ) {
	// part 1: getting the relevant parameter values

	// Initialize product_id and price to null
	$product_id = null;
	$new_price = null;

	// Check if product_id is set in step parameters
	if ( isset( $stepParams['product_id'] ) && is_numeric( $stepParams['product_id'] ) ) {
		$product_id = (int) $stepParams['product_id'];
	}
	// If product_id is not set in step parameters, check state parameters
	elseif ( isset( $stateParams['product_id'] ) && is_numeric( $stateParams['product_id'] ) ) {
		$product_id = (int) $stateParams['product_id'];
	}

	// If product_id is still null, return false
	if ( $product_id === null ) {
		return false;
	}

	// Check if price is set in step parameters
	if ( isset( $stepParams['price'] ) && is_numeric( $stepParams['price'] ) ) {
		$new_price = floatval( $stepParams['price'] );
	}

	// If price is null, return false
	if ( $new_price === null ) {
		return false;
	}

	// part 2: process the action

	// Fetch the WooCommerce product object by its ID
	$product = wc_get_product( $product_id );

	// If product is not found, return false
	if ( ! $product ) {
		return false;
	}

	// Set the new regular price
	$product->set_regular_price( $new_price );

	// Save the changes to the product
	$product->save();

	// Re-fetch the product to verify if the regular price was successfully updated
	$updated_product = wc_get_product( $product_id );

	// Check if the regular price was successfully updated
	if ( $updated_product && floatval( $updated_product->get_regular_price() ) === $new_price ) {
		// Regular price was successfully updated, return the product_id
		return [ 
			'product_id' => $product_id
		];
	} else {
		// Regular price update failed, return false
		return false;
	}
}
