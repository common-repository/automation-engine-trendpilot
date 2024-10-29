<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function tpapChangeProductBadge( $stepParams, $stateParams ) {

	$product_id = null;

	// Retrieve product_id
	if ( isset( $stepParams['product_id'] ) && $stepParams['product_id'] !== null ) {
		$product_id = $stepParams['product_id'];
	} elseif ( isset( $stateParams['product_id'] ) && $stateParams['product_id'] !== null ) {
		$product_id = $stateParams['product_id'];
	}

	if ( $product_id === null ) {

		return false;
	}

	// Check for product_badge parameter
	if ( ! isset( $stepParams['product_badge'] ) ) {

		return false;
	}

	$product_badge = sanitize_text_field( $stepParams['product_badge'] );

	// Update product badge
	$update_result = update_post_meta( $product_id, 'ae_product_badge', $product_badge );

	// Stores the custom text product meta value
	if ( ! in_array( $product_badge, [ 'sale', 'popular', 'new' ] ) ) {
		$update_result2 = update_post_meta( $product_id, 'ae_product_badge_custom_text', $product_badge );
	}


	// Verify update
	$new_badge_value = get_post_meta( $product_id, 'ae_product_badge', true );
	if ( $new_badge_value == $product_badge ) {

		return [ 'product_id' => $product_id ];
	} else {

		return false;
	}
}
