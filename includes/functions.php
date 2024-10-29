<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function tpae_manual_flush_page_views() {
	global $wpdb;
	$page_views_table_name = $wpdb->prefix . 'trendpilot_automation_engine_page_views';

	$wpdb->query( "DELETE FROM `{$page_views_table_name}` WHERE 1=1" ); // db call ok

	echo '<div class="updated"><p>Successfully flushed page views.</p></div>';
}

function tpae_manual_flush_recommended_data() {
	global $wpdb;
	$click_data_table_name = $wpdb->prefix . 'trendpilot_automation_engine_recommended_loop_clicks';

	$wpdb->query( "DELETE FROM `{$click_data_table_name}` WHERE 1=1" ); // db call ok

	echo '<div class="updated"><p>Successfully flushed recommended data.</p></div>';
}

//$timezone = get_option( 'timezone_string' ) ?: 'UTC';

function tpae_record_page_view() {
	global $wpdb;
	$page_views_table_name = $wpdb->prefix . 'trendpilot_automation_engine_page_views';

	// Check if the current request is for an upsell popup
	if ( isset( $_GET['show_atc_modal'] ) && $_GET['show_atc_modal'] == '1' ) {
		// Verify the nonce before proceeding
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'show_atc_modal_nonce' ) ) {
			wp_die( 'Nonce verification failed' );
		}

		return;
	}

	$is_product_page = is_product();
	$is_category_page = is_product_category();
	$item_id = 0;

	if ( $is_product_page ) {
		$item_id = get_the_ID();
	} elseif ( $is_category_page ) {
		$term = get_queried_object();
		$item_id = $term->term_id;
	} else {
		return;
	}

	$today = current_time( 'Y-m-d' );

	$existing_entry = $wpdb->get_var( $wpdb->prepare(
		"SELECT id FROM $page_views_table_name 
         WHERE ((product_id = %d AND product_id IS NOT NULL) OR 
                (category_id = %d AND category_id IS NOT NULL)) AND 
               viewed_date = %s",
		$is_product_page ? $item_id : 0,
		$is_category_page ? $item_id : 0,
		$today
	) ); // db call ok

	if ( $existing_entry ) {
		$wpdb->query( $wpdb->prepare(
			"UPDATE $page_views_table_name SET views = views + 1 
             WHERE id = %d",
			$existing_entry
		) ); // db call ok
	} else {
		$wpdb->insert(
			$page_views_table_name,
			array(
				'product_id' => $is_product_page ? $item_id : NULL,
				'category_id' => $is_category_page ? $item_id : NULL,
				'views' => 1,
				'viewed_date' => $today
			),
			array( '%d', '%d', '%d', '%s' )
		); // db call ok
	}
}

// Function to flush old page views data
function tpae_flush_old_page_views() {
	global $wpdb;
	$page_views_table_name = $wpdb->prefix . 'trendpilot_automation_engine_page_views';
	$days_to_keep = get_option( 'tpae_page_view_flush_period', 30 );

	$delete_before_date = wp_date( 'Y-m-d', strtotime( "-$days_to_keep days" ) );

	// Prepare and execute the delete query
	$result = $wpdb->query( $wpdb->prepare( "DELETE FROM $page_views_table_name WHERE viewed_date <= %s", $delete_before_date ) );  // db call ok

}
add_action( 'tpap_daily_workflow_checker', 'tpae_flush_old_page_views' );

// Function to flush old recommended data
function tpae_flush_old_recommended_data() {
	global $wpdb;
	$recommended_data_table_name = $wpdb->prefix . 'trendpilot_automation_engine_recommended_loop_clicks';
	$days_to_keep = get_option( 'tpae_recommended_data_flush_period', 30 );

	$delete_before_date = wp_date( 'Y-m-d', strtotime( "-$days_to_keep days" ) );

	// Prepare and execute the delete query
	$result = $wpdb->query( $wpdb->prepare( "DELETE FROM $recommended_data_table_name WHERE clicked_date <= %s", $delete_before_date ) ); // db call ok


}
add_action( 'tpap_daily_workflow_checker', 'tpae_flush_old_recommended_data' );

//Validates the recieved JSON data being stored in the 'steps' field for a workflow.
function tpap_validate_step( $step ) {

	$schema = [ 
		"product_id" => "integer",
		"days" => "integer",
		"amount" => "integer",
		"perc_amount" => "integer",
		"amount_type" => "string",
		"add_remove" => [ "add", "remove" ],
		"product_tag" => "string",
		"cat_id" => "integer",
		"percentage" => "integer",
		"price" => "float",
		"enable_disable" => [ "enable", "disable", "Enable", "Disable" ],
		"above_below" => [ "above", "below", "Above", "Below" ],
		"show_hide" => [ "show", "hide", "Show", "Hide" ],
		"product_status" => "string",
		"email" => "string",
		"message" => "string",
		"product_badge" => "string",
		"start_date" => "date",
		"coupon_id" => "string",
		"datapoint" => "string",
		"product_filters" => "object",
		"display_id" => "string",
		"trigger_type" => "string",
		"usertags" => "string",
		"revert_after" => "integer",
		"sortby" => "string",
	];

	$productFiltersSchema = [ 
		"sortby" => [ "most_recent", "most_viewed", "oldest", "least_viewed", "most_purchased", "highest_revenue", "random", "most_viewed_this_session", "most_purchased_by_user" ],
		"on_sale" => "boolean",
		"featured" => "boolean",
		"show_in_category" => "boolean",
		"show_with_tag" => "boolean",
		"in_category" => "string",
		"with_tag" => "string"
	];

	$allowedUserTags = [ 
		"high_spender",
		"recently_abandoned_cart",
		"returning_visitor",
		"not_purchased",
		"has_purchased",
		"active_cart",
		"price_sensitive",
		"first_time_visitor",
		"everyone"
	];



	if ( ! isset( $step['type'] ) || ! in_array( $step['type'], [ 'event', 'action', 'if_condition' ] ) ) {
		error_log( "Validation Error: Invalid or missing 'type' in step." );
		return false;
	}

	if ( ! isset( $step[ $step['type'] ]['name'] ) || ! is_string( $step[ $step['type'] ]['name'] ) ) {
		error_log( "Validation Error: Missing or invalid 'name'" );
		return false;
	}

	$details = $step[ $step['type'] ]['parameters'] ?? [];
	if ( ! is_array( $details ) ) {
		error_log( "Validation Error: 'parameters' must be an array" );
		return false;
	}

	// Params allowed to be empty
	if ( empty( $details ) ) {
		return true;
	}

	foreach ( $details as $param => $value ) {
		if ( ! array_key_exists( $param, $schema ) ) {
			error_log( "Validation Error: Unexpected parameter" );
			return false;
		}

		$type = $schema[ $param ];
		if ( $param === 'product_filters' ) {
			// Validate product_filters as an object
			if ( ! is_array( $value ) ) {
				error_log( "Validation Error: product_filters must be an object." );
				return false;
			}
			foreach ( $value as $filterKey => $filterValue ) {
				if ( ! array_key_exists( $filterKey, $productFiltersSchema ) ) {
					error_log( "Validation Error: Unexpected filter parameter in product_filters." );
					return false;
				}

				$filterType = $productFiltersSchema[ $filterKey ];
				if ( is_array( $filterType ) && ! in_array( $filterValue, $filterType ) ) {
					error_log( "Validation Error: Value for filter parameter is not an acceptable value." );
					return false;
				} else if ( $filterType === 'boolean' ) {
					if ( ! in_array( $filterValue, [ "0", "1" ] ) ) {
						error_log( "Validation Error: Value for filter parameter is not a boolean." );
						return false;
					}
				} else if ( $filterType === 'string' && ! is_string( $filterValue ) ) {
					error_log( "Validation Error: Value for filter parameter is not a string." );
					return false;
				}
			}
		}

		// Validate usertags
		if ( $param === 'usertags' ) {
			if ( ! is_string( $value ) ) {
				error_log( "Validation Error: usertags must be a string." );
				return false;
			}

			// Split the string into an array of usertags
			$userTagsArray = explode( ',', $value );
			foreach ( $userTagsArray as $tag ) {
				$tag = trim( $tag ); // Trim any extra spaces
				if ( ! in_array( $tag, $allowedUserTags ) ) {
					error_log( "Validation Error: Invalid user tag '$tag' found." );
					return false;
				}
			}
		}

		// Validate other fields
		else {
			if ( is_array( $type ) && ! in_array( $value, $type ) ) {
				error_log( "Validation Error: Value for parameter is not an acceptable value." );
				return false;
			} else if ( $type === 'integer' ) {
				if ( ! is_null( $value ) && ( ! is_numeric( $value ) || (int) $value != $value ) ) {
					error_log( "Validation Error: Value for parameter is not an integer or NULL." );
					return false;
				}
			} else if ( $type === 'string' && ! is_string( $value ) ) {
				error_log( "Validation Error: Value for parameter is not a string." );
				return false;
			} else if ( $type === 'date' && ! tpap_validate_date( $value ) ) {
				error_log( "Validation Error: Value for parameter is not a valid date." );
				return false;
			}
		}
	}

	return true;
}


// Helper function to validate date formats
function tpap_validate_date( $date, $format = 'Y-m-d' ) {
	$d = DateTime::createFromFormat( $format, $date );
	return $d && $d->format( $format ) === $date;
}


function aetp_manual_flush_click_data() {
	global $wpdb;
	$click_data_table_name = $wpdb->prefix . 'trendpilot_automation_engine_click_data';

	$wpdb->query( "DELETE FROM `{$click_data_table_name}` WHERE 1=1" ); // db call ok

	echo '<div class="updated"><p>Successfully flushed click data.</p></div>';
}

/// when aetp_upsell_product_id changes, this function changes 'aetp_upsell_date_last_change' to todays date
// it also clears the upsell click data table if the value of aetp_upsell_product_id has changed
function trendpilot_handle_aetp_upsell_product_id_change( $option, $old_value, $value ) {
	global $wpdb;

	// This function should only act on 'aetp_upsell_product_id' changes
	if ( $option !== 'aetp_upsell_product_id' ) {
		return;
	}

	// Check if the value has changed
	if ( $old_value === $value ) {
		return; // Exit the function if the product ID has not changed
	}

	// Update the datetime for aetp_upsell_date_last_change
	$current_date = current_time( 'Y-m-d' ); // This will get the current date without time
	$existing_date = get_option( 'aetp_upsell_date_last_change' );

	if ( $current_date !== $existing_date ) {
		update_option( 'aetp_upsell_date_last_change', $current_date );
	}

	// Clear data from the upsell clicks table
	$table_name = $wpdb->prefix . 'trendpilot_automation_engine_click_data';
	$cleared = $wpdb->query( "DELETE FROM {$table_name}" );
}
add_action( 'updated_option', 'trendpilot_handle_aetp_upsell_product_id_change', 10, 3 );

// Flush function for old click data
function trendpilot_flush_old_click_data() {
	global $wpdb;
	$click_data_table_name = $wpdb->prefix . 'trendpilot_automation_engine_click_data';
	$days_to_keep = get_option( 'aetp_click_data_flush_period', 30 );

	// Calculate the date before which click data will be deleted
	$delete_before_date = wp_date( 'Y-m-d', strtotime( "-$days_to_keep days" ) );

	// Delete the old click data
	$result = $wpdb->query( $wpdb->prepare( "DELETE FROM $click_data_table_name WHERE clicked_date <= %s", $delete_before_date ) );  // db call ok

}
add_action( 'tpap_daily_workflow_checker', 'trendpilot_flush_old_click_data' );

