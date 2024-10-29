<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


function tpapTakeProductOffSale( $stepParams, $stateParams ) {

	// part 1: getting the relevant parameter values

	// Initialize product_id to null
	$product_id = null;

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

	// Fetch the WooCommerce product object by its ID
	$product = wc_get_product( $product_id );

	// If product is not found, return false
	if ( ! $product ) {
		return false;
	}

	// part 2: process the action

	// Remove the sale price
	$product->set_sale_price( '' );

	// Save the changes to update the product state
	$product->save();

	// Re-fetch the product to verify if the sale price was successfully removed
	$updated_product = wc_get_product( $product_id );

	// Check if the sale price was successfully removed
	if ( $updated_product && $updated_product->get_sale_price() === '' ) {
		// Sale price was successfully removed, return the product_id
		return [ 
			'product_id' => $product_id
		];
	} else {
		// Sale price removal failed, return false
		return false;
	}
}