<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


function tpapDisplayProductEverywhere( $stepParams, $stateParams ) {

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

	// Add the product to the recommended list and get the result
	$recommended = new AETrendpilot\AETrendpilotRecommended();
	$is_added_to_recommended = $recommended->add_to_recommended( $product_id );

	// Add the product to the upsell list by updating the 'upsell_product_id' option
	$current_upsell_product_id = get_option( 'aetp_upsell_product_id' );

	if ( $current_upsell_product_id == $product_id ) {
		// If the current value is the same as the new value
		$is_added_to_upsell = true;
	} else {
		// If the current value is different from the new value
		$is_added_to_upsell = update_option( 'aetp_upsell_product_id', $product_id );
	}

	// Mark the product as featured (Assuming you have a function for this)
	$workflow = new AETrendpilot\AETrendpilotWorkflow;
	$is_marked_featured = $workflow->mark_as_featured( $product_id );

	// Check if all actions were successful
	if ( $is_added_to_recommended && $is_added_to_upsell && $is_marked_featured ) {
		return [ 
			'product_id' => $product_id
		];
	} else {
		return false;
	}
}