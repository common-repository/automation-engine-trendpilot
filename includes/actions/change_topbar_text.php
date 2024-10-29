<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function tpapChangeTopbarText( $stepParams, $stateParams ) {

	// Initialize message to null
	$message = null;

	// Check if message is set in step parameters
	if ( isset( $stepParams['message'] ) && $stepParams['message'] !== null ) {
		$message = $stepParams['message'];
	} else {

		return false;
	}

	// Process message for <product_from_previous_step>
	if ( strpos( $message, '<product_from_previous_step>' ) !== false ) {
		$product_id = null;
		if ( isset( $stepParams['product_id'] ) && $stepParams['product_id'] !== null ) {
			$product_id = $stepParams['product_id'];
		} elseif ( isset( $stateParams['product_id'] ) && $stateParams['product_id'] !== null ) {
			$product_id = $stateParams['product_id'];
		}

		if ( $product_id === null ) {
			$product_name = '<error = product not set in previous step>';

		} else {
			$product = wc_get_product( $product_id );
			$product_name = $product ? $product->get_name() : '<error = product not found>';
		}

		$message = str_replace( '<product_from_previous_step>', esc_html( $product_name ), $message );
	}

	// Process message for <category_from_previous_step>
	if ( strpos( $message, '<category_from_previous_step>' ) !== false ) {
		$cat_id = null;
		if ( isset( $stepParams['cat_id'] ) && $stepParams['cat_id'] !== null ) {
			$cat_id = $stepParams['cat_id'];
		} elseif ( isset( $stateParams['cat_id'] ) && $stateParams['cat_id'] !== null ) {
			$cat_id = $stateParams['cat_id'];
		}

		if ( $cat_id === null ) {
			$category_name = '<error = category not set in previous step>';

		} else {
			$category = get_term_by( 'id', $cat_id, 'product_cat' );
			$category_name = $category ? $category->name : '<error = category not found>';
		}

		$message = str_replace( '<category_from_previous_step>', esc_html( $category_name ), $message );
	}

	// If message is null, return false
	if ( $message === null ) {
		return false;
	}

	// Check if the new message is the same as the current one
	$current_message = get_option( 'aetp_top_bar_message' );
	if ( $message === $current_message ) {

		return true; // The desired state is already achieved
	}

	// Update the top bar message
	$is_updated = update_option( 'aetp_top_bar_message', esc_html( $message ) );

	return $is_updated;
}
