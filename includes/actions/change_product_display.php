<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function tpapChangeProductDisplay( $stepParams, $stateParams ) {

	$product_id = null;

	// Retrieve product_id
	if ( isset( $stepParams['product_id'] ) && $stepParams['product_id'] !== null ) {
		$product_id = (int) $stepParams['product_id'];
	} elseif ( isset( $stateParams['product_id'] ) && $stateParams['product_id'] !== null ) {
		$product_id = (int) $stateParams['product_id'];
	}

	if ( $product_id === null ) {
		return false;
	}

	// Check for display_id parameter
	if ( ! isset( $stepParams['display_id'] ) ) {
		return false;
	}

	$displayId = (int) ( sanitize_text_field( $stepParams['display_id'] ) );

	// Get product display post
	$product_display_post = get_post( $displayId );
	if ( ! $product_display_post || $product_display_post->post_type !== 'tp_product_display' ) {
		return false;
	}

	// Change its postmeta value for 'product_id' to $product_id
	update_post_meta( $displayId, 'product_id', $product_id );

	// Get the current product_id value of the display
	$new_display_product_id = get_post_meta( $displayId, 'product_id', true );

	if ( $new_display_product_id == $product_id ) {
		return [ 'product_id' => $product_id ];
	} else {
		return false;
	}
}