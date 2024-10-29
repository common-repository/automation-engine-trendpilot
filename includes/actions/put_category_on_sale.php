<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function tpapPutCategoryOnSale( $stepParams, $stateParams ) {

	// Initialize cat_id and percentage to null
	$cat_id = null;
	$percentage = null;

	// Check if cat_id is set in step parameters
	if ( isset( $stepParams['cat_id'] ) && $stepParams['cat_id'] !== null ) {
		$cat_id = absint( $stepParams['cat_id'] );
	} elseif ( isset( $stateParams['cat_id'] ) && $stateParams['cat_id'] !== null ) {
		$cat_id = absint( $stateParams['cat_id'] );
	}

	// Check if percentage is set in step parameters
	if ( isset( $stepParams['percentage'] ) ) {
		$percentage = abs( floatval( $stepParams['percentage'] ) );
	}

	if ( $cat_id === null || $percentage === null || $percentage > 100 ) {
		return false;
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
	$tolerance = 0.01; // Define a small tolerance for floating-point comparison

	// Loop through each product ID and put it on sale
	foreach ( $product_ids as $product_id ) {
		$product = wc_get_product( $product_id );
		if ( $product ) {
			$regular_price = $product->get_regular_price();
			$sale_price = $regular_price - ( ( $percentage / 100 ) * $regular_price );

			$product->set_sale_price( $sale_price );
			$product->save();

			$updated_product = wc_get_product( $product_id );
			$updated_sale_price = floatval( $updated_product->get_sale_price() );
			$expected_sale_price = floatval( $sale_price );

			if ( abs( $updated_sale_price - $expected_sale_price ) > $tolerance ) {
				$all_successful = false;

			}
		} else {
			$all_successful = false;
		}
	}

	return $all_successful;
}
