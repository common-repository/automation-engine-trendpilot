<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


function tpapEnableDisableTopbar( $stepParams, $stateParams ) {


	// Initialize the 'enable_disable' variable to null
	$enable_disable = null;

	// Check if 'enable_disable' exists in $stepParams
	if ( isset( $stepParams['enable_disable'] ) && $stepParams['enable_disable'] !== null ) {
		$enable_disable = sanitize_text_field( $stepParams['enable_disable'] );
	} else {

		return false;
	}

	if ( $enable_disable === null ) {

		return false;
	}

	// Get the current value of the option
	$current_value = get_option( 'aetp_top_bar_active' );

	// Determine the new value based on 'enable_disable'
	$new_value = $enable_disable === 'Enable' ? '1' : '0';

	// Check if the current value already matches the new value
	if ( $current_value === $new_value ) {

		return true;
	}

	// Update the 'aetp_top_bar_active' option
	$result = update_option( 'aetp_top_bar_active', $new_value );

	if ( $result ) {

		return true;
	} else {

		return false;
	}
}
