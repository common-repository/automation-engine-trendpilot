<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function tpapAddRemoveProductTag( $stepParams, $stateParams ) {

	// Initialize product_id
	$product_id = null;

	// Determine the product_id
	if ( isset( $stepParams['product_id'] ) && $stepParams['product_id'] !== null ) {
		$product_id = absint( $stepParams['product_id'] );
	} elseif ( isset( $stateParams['product_id'] ) && $stateParams['product_id'] !== null ) {
		$product_id = absint( $stateParams['product_id'] );
	}

	// If product_id is null, return false
	if ( $product_id === 0 ) {

		return false;
	}

	// Check if product_tag and add_remove are set
	if ( ! isset( $stepParams['product_tag'] ) || ! isset( $stepParams['add_remove'] ) ) {

		return false;
	}

	// Set product_tag and add_remove
	$product_tag = sanitize_text_field( $stepParams['product_tag'] );
	$add_remove = sanitize_text_field( $stepParams['add_remove'] );

	// Perform the add/remove action
	if ( $add_remove === 'add' ) {
		$result = wp_set_object_terms( (int) $product_id, $product_tag, 'product_tag', true );
	} else if ( $add_remove === 'remove' ) {
		// Check if the tag exists before attempting to remove it
		if ( ! term_exists( $product_tag, 'product_tag' ) ) {

			return [ 'product_id' => $product_id ];
		}
		$result = wp_remove_object_terms( (int) $product_id, $product_tag, 'product_tag' );
	}

	// Check the result of the operation
	if ( is_wp_error( $result ) ) {

		return false;
	} else {

		return [ 'product_id' => $product_id ];
	}
}
