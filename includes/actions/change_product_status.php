<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


function tpapChangeProductStatus( $stepParams, $stateParams ) {

	$product_id = null;
	$product_status = null;

	if ( isset( $stepParams['product_id'] ) && $stepParams['product_id'] !== null ) {
		$product_id = absint( $stepParams['product_id'] );
	} elseif ( isset( $stateParams['product_id'] ) && $stateParams['product_id'] !== null ) {
		$product_id = absint( $stateParams['product_id'] );
	}

	if ( $product_id === null ) {

		return false;
	}

	if ( isset( $stepParams['product_status'] ) && $stepParams['product_status'] !== null ) {
		$product_status = sanitize_text_field( $stepParams['product_status'] );
	}

	if ( $product_status === null ) {

		return false;
	}

	$product = wc_get_product( $product_id );

	if ( ! $product ) {

		return false;
	}

	$product->set_status( $product_status );
	$product->save();

	$updated_product = wc_get_product( $product_id );
	if ( $updated_product && $updated_product->get_status() === $product_status ) {


		return [ 'product_id' => $product_id ];
	} else {

		return false;
	}
}
