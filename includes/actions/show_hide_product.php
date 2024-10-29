<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function tpapShowHideProduct( $stepParams, $stateParams ) {

	// Initialize product_id
	$product_id = null;

	// Determine the product_id
	if ( isset( $stepParams['product_id'] ) && $stepParams['product_id'] !== null ) {
		$product_id = absint( $stepParams['product_id'] );
	} elseif ( isset( $stateParams['product_id'] ) && $stateParams['product_id'] !== null ) {
		$product_id = absint( $stateParams['product_id'] );
	}

	// If product_id is null or zero, return false
	if ( $product_id === 0 ) {
		return false;
	}

	// Check if show_hide parameter is set
	if ( ! isset( $stepParams['show_hide'] ) ) {
		return false;
	}

	$show_hide = sanitize_text_field( $stepParams['show_hide'] );

	// Initialize result variable
	$result = false;

	// Load the product object
	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		return false; // If product doesn't exist, return false
	}

	// Perform the show/hide action
	if ( $show_hide === 'show' ) {
		// Show the product by setting catalog visibility to 'visible'
		$product->set_catalog_visibility( 'visible' ); // 'Shop and search results'
		$product->save();

		// Check the product visibility
		$result = ( $product->get_catalog_visibility() === 'visible' );
	} else if ( $show_hide === 'hide' ) {
		// Hide the product by setting catalog visibility to 'hidden'
		$product->set_catalog_visibility( 'hidden' ); // 'Hidden from shop and search'
		$product->save();

		// Check the product visibility
		$result = ( $product->get_catalog_visibility() === 'hidden' );
	}

	// Check the result of the operation
	if ( ! $result ) {
		return false;
	} else {
		return [ 'product_id' => $product_id ];
	}
}

