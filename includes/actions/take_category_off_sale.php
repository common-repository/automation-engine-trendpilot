<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function tpapTakeCategoryOffSale( $stepParams, $stateParams ) {

	// Initialize cat_id to null
	$cat_id = null;

	// Check if cat_id is set in step parameters
	if ( isset( $stepParams['cat_id'] ) ) {
		$cat_id = absint( $stepParams['cat_id'] );
	}

	// If cat_id is null, return false
	// Check if cat_id is set in step parameters
	if ( isset( $stepParams['cat_id'] ) && $stepParams['cat_id'] !== null ) {
		$cat_id = absint( $stepParams['cat_id'] );
	} elseif ( isset( $stateParams['cat_id'] ) && $stateParams['cat_id'] !== null ) {
		$cat_id = absint( $stateParams['cat_id'] );
	}

	// Fetch all product IDs from the WooCommerce store belonging to the category
	$args = array(
		'limit' => -1,
		'return' => 'ids',
		'tax_query' => array(
			array(
				'taxonomy' => 'product_cat',
				'field' => 'term_id',
				'terms' => $cat_id,
			),
		),
	);

	$product_ids = wc_get_products( $args );

	// If no products are found, return false
	if ( empty( $product_ids ) ) {

		return false;
	}


	// Initialize a variable to keep track of the operation's success
	$all_successful = true;

	// Loop through each product ID and take it off sale
	foreach ( $product_ids as $product_id ) {
		$product = wc_get_product( $product_id );
		if ( $product ) {
			// Remove the sale price
			$product->set_sale_price( '' );

			// Save the changes to update the product state
			$product->save();

			// Re-fetch the product to verify if the sale price was successfully removed
			$updated_product = wc_get_product( $product_id );

			// If any product fails to update, set $all_successful to false
			if ( ! ( $updated_product && $updated_product->get_sale_price() === '' ) ) {
				$all_successful = false;

			}
		} else {
			// If the product is not found, set $all_successful to false
			$all_successful = false;

		}
	}

	return $all_successful;
}
