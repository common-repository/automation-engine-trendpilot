<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


function tpapMakeProductFeatured( $stepParams, $stateParams ) {
	$workflow = new AETrendpilot\AETrendpilotWorkflow;

	$product_id = null;

	if ( isset( $stepParams['product_id'] ) && $stepParams['product_id'] !== null ) {
		$product_id = absint( $stepParams['product_id'] );
	} elseif ( isset( $stateParams['product_id'] ) && $stateParams['product_id'] !== null ) {
		$product_id = absint( $stateParams['product_id'] );
	}

	if ( $product_id === null ) {

		return false;
	}

	$product = wc_get_product( $product_id );
	if ( ! $product ) {

		return false;
	}

	$is_marked_featured = $workflow->mark_as_featured( $product_id );

	if ( $is_marked_featured ) {

		return [ 'product_id' => $product_id ];
	} else {

		return false;
	}
}
