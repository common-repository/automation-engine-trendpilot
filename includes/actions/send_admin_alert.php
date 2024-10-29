<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function tpapSendAdminAlert( $stepParams, $stateParams ) {

	// Initialize email and message to null
	$email = null;
	$message = null;

	// Check if email is set in step parameters
	if ( isset( $stepParams['email'] ) && $stepParams['email'] !== null ) {
		$email = sanitize_email( $stepParams['email'] );
	}

	// Check if message is set in step parameters
	if ( isset( $stepParams['message'] ) && $stepParams['message'] !== null ) {
		$message = $stepParams['message'];
	}

	if ( strpos( $message, '<product_from_previous_step>' ) !== false ) {
		$product_id = null;

		// Check if product_id is set in step parameters
		if ( isset( $stepParams['product_id'] ) && $stepParams['product_id'] !== null ) {
			$product_id = absint( $stepParams['product_id'] );
		}
		// If product_id is not set in step parameters, check state parameters
		elseif ( isset( $stateParams['product_id'] ) && $stateParams['product_id'] !== null ) {
			$product_id = absint( $stateParams['product_id'] );
		}

		// If product_id is still null, log an error
		if ( $product_id === null ) {
			$product_name = '<error = product not set in previous step>';
		} else {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				$product_name = sanitize_text_field( $product->get_name() );
			}
		}

		// Replace the substring
		$message = str_replace( '<product_from_previous_step>', $product_name, $message );
	}

	if ( strpos( $message, '<category_from_previous_step>' ) !== false ) {
		$cat_id = null;

		if ( isset( $stepParams['cat_id'] ) && $stepParams['cat_id'] !== null ) {
			$cat_id = absint( $stepParams['cat_id'] );
		}
		// If cat_id is not set in step parameters, check state parameters
		elseif ( isset( $stateParams['cat_id'] ) && $stateParams['cat_id'] !== null ) {
			$cat_id = absint( $stateParams['cat_id'] );
		}

		// If cat_id is still null, log an error
		if ( $cat_id === null ) {
			$category_name = '<error = category not set in previous step>';
		} else {
			$category = get_term_by( 'id', $cat_id, 'product_cat' );
			if ( $category ) {
				$category_name = sanitize_text_field( $category->name );
			}
		}

		// Replace the substring
		$message = str_replace( '<category_from_previous_step>', $category_name, $message );
	}

	// If email or message is null, log an error and return false
	if ( $email === null || $message === null ) {
		return false;
	}

	// Send the email using wp_mail and capture the result
	$is_sent = wp_mail( $email, 'Admin Alert', esc_html( $message ) );

	// Return whether the email was successfully sent
	return $is_sent;
}
