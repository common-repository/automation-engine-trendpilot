<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function tpapProductOffRecommended( $stepParams, $stateParams ) {

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

	// Remove the product from the recommended list and get the result
	$recommended = new AETrendpilot\AETrendpilotRecommended();
	$is_removed = $recommended->tpae_remove_from_recommended( $product_id );
	//$is_removed = tpae_remove_from_recommended( $product_id );

	// Check if the product was successfully removed from the recommended list
	if ( $is_removed ) {
		return [ 
			'product_id' => $product_id
		];
	} else {
		return true;

	}
}