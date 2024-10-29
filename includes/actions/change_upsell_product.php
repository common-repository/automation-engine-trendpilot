<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


function tpapChangeUpsellProduct( $stepParams, $stateParams ) {

	// Part 1: Getting the relevant parameter values

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

	// Part 2: Process the action

	// Update the aetp_upsell_product_id in the options table
	update_option( 'aetp_upsell_product_id', $product_id );

	// Re-fetch the option to verify if it was successfully updated
	$updated_upsell_product_id = get_option( 'aetp_upsell_product_id' );

	// Check if the upsell_product_id was successfully updated
	if ( $updated_upsell_product_id == $product_id ) {
		// It was successfully updated, return the product_id
		return [ 
			'product_id' => $product_id
		];
	} else {
		// Update failed, return false
		return false;
	}
}
