<?php

namespace AETrendpilot;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function aetp_get_workflow_templates() {
	global $wpdb;
	$current_user_id = get_current_user_id();
	$table_name = $wpdb->prefix . 'trendpilot_workflow_templates';
	$results = $wpdb->get_results( $wpdb->prepare(
		"SELECT name, json, unique_id FROM $table_name WHERE user_id = %d",
		$current_user_id
	), ARRAY_A ); // db call ok

	$template_list = array();
	foreach ( $results as $row ) {
		// Sanitize the JSON data (already a JSON-encoded string)
		$sanitized_json = sanitize_json_data( $row['json'] );

		// No need to decode and re-encode; just use the sanitized JSON string directly
		$template_list[] = array(
			'name' => $row['name'],
			'json' => $sanitized_json,  // Directly use the sanitized JSON string
			'listItem' => $row['unique_id'],
		);
	}

	return $template_list;
}

add_filter( 'safe_style_css', 'AETrendpilot\extend_allowed_inline_styles', 10 );

function extend_allowed_inline_styles( $allowed_styles ) {

	// Add additional allowed CSS properties for inline styles in SVG elements
	$additional_styles = array(
		'stroke',
		'stroke-width',
		'stroke-dasharray',
		'stroke-linecap',
		'stroke-linejoin',
		'fill',
		'fill-rule',
		'opacity',
		'stroke-dashoffset',
		'stroke-miterlimit',
	);

	// Merge the additional styles with the existing allowed styles
	$allowed_styles = array_merge( $allowed_styles, $additional_styles );

	return $allowed_styles;
}

function sanitize_json_data( $json_data ) {

	// Decode the JSON string into a PHP array
	$decoded_json = json_decode( $json_data, true );

	// Check if decoding was successful
	if ( $decoded_json !== null ) {

		// Define allowed HTML tags and attributes
		$allowed_html = array(
			'div' => array( 'class' => true, 'id' => true, 'style' => true ),
			'input' => array( 'type' => true, 'name' => true, 'value' => true, 'class' => true, 'id' => true ),
			'button' => array( 'type' => true, 'class' => true ),
			'img' => array( 'src' => true, 'alt' => true, 'class' => true, 'width' => true, 'height' => true ),
			'p' => array( 'class' => true ),
			'span' => array( 'class' => true ),
			'svg' => array( 'xmlns' => true, 'xmlns:xlink' => true, 'version' => true, 'viewbox' => true, 'xml:space' => true, 'width' => true, 'height' => true, 'style' => true ),
			'g' => array( 'transform' => true, 'id' => true, 'fill' => true, 'style' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-dasharray' => true ),
			'path' => array( 'd' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'class' => true, 'style' => true, 'transform' => true, 'opacity' => true, 'fill-rule' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'stroke-dasharray' => true ),
		);

		// Recursively sanitize each value in the array
		array_walk_recursive( $decoded_json, function (&$value, $key) use ($allowed_html) {
			// Check for the type and handle accordingly
			if ( is_int( $value ) ) {
				// Ensure it's an integer
				$value = intval( $value );
			} elseif ( is_float( $value ) ) {
				// Ensure it's a float
				$value = floatval( $value );
			} elseif ( is_bool( $value ) ) {
				// Ensure it's a boolean
				$value = boolval( $value );
			} elseif ( is_string( $value ) ) {
				// Process 'html' fields
				if ( $key === 'html' ) {
					// Temporarily escape specific tags
					$value = str_replace(
						array( '<product_from_previous_step>', '<category_from_previous_step>' ),
						array( '__PRODUCT_FROM_PREVIOUS_STEP__', '__CATEGORY_FROM_PREVIOUS_STEP__' ),
						$value
					);

					// Sanitize HTML with allowed tags/attributes
					$value = wp_kses( $value, $allowed_html );

					// Restore specific tags after sanitization
					$value = str_replace(
						array( '__PRODUCT_FROM_PREVIOUS_STEP__', '__CATEGORY_FROM_PREVIOUS_STEP__' ),
						array( '<product_from_previous_step>', '<category_from_previous_step>' ),
						$value
					);
				} else {
					// First, remove all potential XSS vectors by sanitizing with wp_kses_post()
					$value = wp_kses_post( $value );

					// Additionally, escape any characters that could be used in script injection or XSS
					$value = esc_html( $value );
				}
			}
		} );

		// Encode the sanitized array back into JSON format
		$sanitized_json = wp_json_encode( $decoded_json );


		return $sanitized_json;
	} else {
		error_log( "JSON decoding failed." );
		return false; // Return false if JSON decoding fails
	}
}

function aetp_save_template_callback() {

	// Check the nonce for security 
	check_ajax_referer( 'automation_engine_nonce', 'nonce' );

	// Check if the user has the appropriate permissions
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => 'You do not have permission to perform this action' ) );
		wp_die();
	}

	global $wpdb;

	// Extract the POST data
	if ( isset( $_POST['currentJsonWorkflow'] ) && isset( $_POST['workflowName'] ) ) {

		//$currentJsonWorkflow is being sanitized after retrieval.
		$currentJsonWorkflow = wp_unslash( $_POST['currentJsonWorkflow'] );
		$sanitizedJson = sanitize_json_data( $currentJsonWorkflow );
		// Check if the JSON data is valid
		if ( $sanitizedJson === false ) {
			wp_send_json_error( array( 'message' => 'Invalid JSON data' ) );
			wp_die();
		}

		$workflowName = sanitize_text_field( wp_unslash( $_POST['workflowName'] ) );
		$status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'inactive';
		$repeat = isset( $_POST['repeat'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['repeat'] ) ) : 0;
		$saveAsNew = isset( $_POST['saveAsNew'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['saveAsNew'] ) ) : 0;
		$current_user_id = get_current_user_id();

		//$finalWorkflowJSON is being sanitized and validated after retrieval.
		$finalWorkflowJSON = isset( $_POST['finalWorkflowJSON'] ) ? wp_unslash( $_POST['finalWorkflowJSON'] ) : '';
		// Parse and validate the steps data
		$stepsData = json_decode( $finalWorkflowJSON, true );
		if ( ! isset( $stepsData['steps'] ) || ! is_array( $stepsData['steps'] ) ) {
			wp_send_json_error( array( 'message' => 'Invalid or missing steps data' ) );
			wp_die();
		}

		// Validate each step
		foreach ( $stepsData['steps'] as $step ) {
			if ( ! tpap_validate_step( $step ) ) {
				wp_send_json_error( array( 'message' => 'One or more steps contain invalid data' ) );
				wp_die();
			}

			// Sanitize the strings within each step
			array_walk_recursive( $step, function (&$item, $key) {
				if ( is_string( $item ) ) {
					$item = sanitize_text_field( $item );
				}
			} );
		}

		// Generate a new unique_id if saveAsNew is set or no unique_id is provided
		$unique_id = ( isset( $_POST['unique_id'] ) && $saveAsNew === 0 )
			? sanitize_text_field( wp_unslash( $_POST['unique_id'] ) )
			: aetp_generate_unique_id();

		// Insert or update the data in the trendpilot_workflow_templates table
		$table_name = $wpdb->prefix . 'trendpilot_workflow_templates';
		$existing_template = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE unique_id = %s AND user_id = %d",
			$unique_id,
			$current_user_id
		) );  // db call ok

		if ( $existing_template && $saveAsNew === 0 ) {
			// Update the existing template
			$wpdb->update(
				$table_name,
				array(
					'name' => $workflowName ? $workflowName : $existing_template->name,
					'json' => $sanitizedJson
				),
				array(
					'unique_id' => $unique_id,
					'user_id' => $current_user_id
				),
				array( '%s', '%s' ), // Ensure data types
				array( '%s', '%d' ) // Ensure data types for where clause
			); // db call ok
		} else {
			// Insert a new template
			if ( ! $workflowName ) {
				wp_send_json_error( array( 'message' => 'Workflow name is required for new templates' ) );
				wp_die();
			}
			$wpdb->insert(
				$table_name,
				array(
					'unique_id' => $unique_id,
					'name' => $workflowName,
					'json' => $sanitizedJson,
					'user_id' => $current_user_id
				),
				array( '%s', '%s', '%s', '%d' ) // Ensure data types
			); // db call ok
		}

		// Now, create or update the entry in the trendpilot_automation_engine_workflows table
		$workflow_table = $wpdb->prefix . 'trendpilot_automation_engine_workflows';
		$existing_workflow = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $workflow_table WHERE unique_id = %s",
			$unique_id
		) ); // db call ok 


		if ( $existing_workflow && $saveAsNew === 0 ) {
			// Update the existing workflow
			$wpdb->update(
				$workflow_table,
				array(
					'name' => $workflowName,
					'steps' => $finalWorkflowJSON, // Use workflowJSON for steps
					'status' => $status, // Use status from AJAX call
					'is_repeat' => $repeat, // Use repeat from AJAX call
					'updated_at' => current_time( 'mysql' )
				),
				array( 'unique_id' => $unique_id ),
				array( '%s', '%s', '%s', '%d', '%s' ), // Ensure data types
				array( '%s' ) // Where clause data type
			);  // db call ok
		} else {
			// Insert a new workflow
			$wpdb->insert(
				$workflow_table,
				array(
					'unique_id' => $unique_id,
					'name' => $workflowName,
					'steps' => $finalWorkflowJSON, // Use workflowJSON for steps
					'status' => $status, // Use status from AJAX call
					'is_repeat' => $repeat, // Use repeat from AJAX call
					'created_at' => current_time( 'mysql' ),
					'updated_at' => current_time( 'mysql' )
				),
				array( '%s', '%s', '%s', '%s', '%d', '%s', '%s' ) // Ensure data types
			);  // db call ok
		}
	} else {
		wp_send_json_error( array( 'message' => 'currentJsonWorkflow, workflowName, or unique_id not set!' ) );
		wp_die();
	}

	// Prepare the response
	$response = array(
		'message' => 'Template received and saved',
		'data' => array(
			'workflow' => json_decode( $sanitizedJson ),
			'name' => $workflowName,
			'unique_id' => $unique_id,
		)
	);

	wp_send_json_success( $response );
	wp_die();
}

add_action( 'wp_ajax_aetp_save_template', 'AETrendpilot\aetp_save_template_callback' );

function aetp_generate_unique_id() {
	// Generate a random string of numbers
	$unique_id = '';
	$length = 32; // Length of the unique ID
	for ( $i = 0; $i < $length; $i++ ) {
		$unique_id .= wp_rand( 0, 9 ); // Use wp_rand instead of rand for better security
	}

	return $unique_id;
}

function aetp_save_steps_to_db() {

	// Verify nonce for security
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'automation_engine_nonce' ) ) {
		wp_send_json_error( array( 'message' => 'Nonce verification failed.' ) );
	}

	// Check if the user has the appropriate permissions
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => 'You do not have permission to perform this action' ) );
		wp_die();
	}

	global $wpdb;
	$workflow = new AETrendpilotWorkflow(); // Assuming this class is already included elsewhere in your code

	// Get current user ID
	$current_user_id = get_current_user_id();

	// Retrieve and sanitize POST data
	$templateID = isset( $_POST['templateID'] ) ? sanitize_text_field( wp_unslash( $_POST['templateID'] ) ) : '';

	// sanitizing finalJsonOverwrite immediately after retrieval
	$finalJsonOverwrite = isset( $_POST['finalJsonOverwrite'] ) ? sanitize_json_data( wp_unslash( $_POST['finalJsonOverwrite'] ) ) : '';

	$status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
	$repeat = isset( $_POST['repeat'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['repeat'] ) ) : 0;

	// Validate necessary fields
	if ( empty( $templateID ) || ! isset( $finalJsonOverwrite ) || ! is_string( $finalJsonOverwrite ) || empty( $status ) || ! in_array( $status, [ 'active', 'inactive' ], true ) ) {
		wp_send_json_error( array( 'message' => 'Required data is missing or invalid.' ) );
	}

	// Decode the JSON steps
	$stepsData = json_decode( $finalJsonOverwrite, true );
	if ( ! isset( $stepsData['steps'] ) || ! is_array( $stepsData['steps'] ) ) {
		wp_send_json_error( array( 'message' => 'Invalid or missing steps data.' ) );
	}

	// Validate and sanitize each step
	foreach ( $stepsData['steps'] as $step ) {
		if ( ! tpap_validate_step( $step ) ) { // Assuming `tpap_validate_step()` exists
			wp_send_json_error( array( 'message' => 'One or more steps contain invalid data.' ) );
		}

		array_walk_recursive( $step, function (&$item) {
			if ( is_string( $item ) ) {
				$item = sanitize_text_field( $item );
			}
		} );
	}

	// Define the table name
	$table_name = $wpdb->prefix . 'trendpilot_automation_engine_workflows';
	$unique_id = sanitize_text_field( $templateID );

	// Attempt to retrieve the existing entry
	$existing_entry = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE unique_id = %s", $unique_id ) ); // db call ok

	// Determine if there are changes in the step count
	$oldEntrySteps = isset( $existing_entry->steps ) ? count( json_decode( $existing_entry->steps, true )['steps'] ) : 0;
	$newEntrySteps = count( $stepsData['steps'] );

	// Retrieve the state for the existing workflow (if it exists)
	$existing_entry_state = $existing_entry ? $workflow->findParentStateByWorkflowId( sanitize_text_field( $existing_entry->unique_id ) ) : null;

	// Manage state based on step count changes
	$stateStatus = 'no-state';
	if ( $existing_entry && $oldEntrySteps !== $newEntrySteps && is_object( $existing_entry_state ) && $existing_entry_state->status === 'completed' ) {
		$workflow->changeStateStatus( $existing_entry_state->id, 'in-progress' );
		$stateStatus = 'in-progress';
	}

	// Prepare data for database insertion
	$is_repeat_new_value = isset( $_POST['repeat'] ) ? (int) $_POST['repeat'] : 0; // Default to 0 if not set
	$insert_data = array(
		'unique_id' => $unique_id,
		'steps' => wp_json_encode( $stepsData ),
		'status' => $status,
		'is_repeat' => $is_repeat_new_value
	);
	$format = array( '%s', '%s', '%s', '%d' );

	// Insert or update the workflow in the database
	if ( $existing_entry ) {
		$result = $wpdb->update( $table_name, $insert_data, array( 'unique_id' => $unique_id ), $format ); // db call ok
		if ( false === $result ) {
			wp_send_json_error( array( 'message' => 'Failed to update data in the database.' ) );
		}
	} else {
		$result = $wpdb->insert( $table_name, $insert_data, $format ); // db call ok
		if ( false === $result ) {
			wp_send_json_error( array( 'message' => 'Failed to insert data into the database.' ) );
		}
	}

	// Retrieve and return the updated status
	$updated_status = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM $table_name WHERE unique_id = %s", $unique_id ) ); // db call ok

	wp_send_json_success( array(
		'message' => 'Data successfully saved.',
		'status' => $updated_status,
		'stateStatus' => $stateStatus
	) );
}

add_action( 'wp_ajax_aetp_save_steps_to_db', 'AETrendpilot\aetp_save_steps_to_db' );

function aetp_activate_workflow() {

	// Verify nonce for security
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'automation_engine_nonce' ) ) {
		wp_send_json_error( array( 'message' => 'Nonce verification failed.' ) );
	}

	// Check if the user has the appropriate permissions
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => 'You do not have permission to perform this action' ) );
		wp_die();
	}

	global $wpdb;

	// Retrieve POST data
	$templateID = isset( $_POST['templateID'] ) ? sanitize_text_field( wp_unslash( $_POST['templateID'] ) ) : '';
	$status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';

	// Check if required data is present
	if ( empty( $templateID ) || empty( $status ) ) {
		wp_send_json_error( array( 'message' => 'Missing required data.' ) );
	}

	// Validate the status field to ensure it is either "active" or "inactive"
	if ( $status !== 'active' && $status !== 'inactive' ) {
		wp_send_json_error( array( 'message' => 'Invalid status value.' ) );
	}

	// Define the table name
	$table_name = $wpdb->prefix . 'trendpilot_automation_engine_workflows';

	// Check if the workflow exists
	$existing_workflow = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM $table_name WHERE unique_id = %s",
		$templateID
	) ); // db call ok

	if ( ! $existing_workflow ) {
		wp_send_json_error( array( 'message' => 'Workflow not found.' ) );
	}

	// Update the status field for the workflow
	$result = $wpdb->update(
		$table_name,
		array( 'status' => $status ),
		array( 'unique_id' => $templateID ),
		array( '%s' ),
		array( '%s' )
	); // db call ok

	if ( false === $result ) {
		wp_send_json_error( array( 'message' => 'Failed to update workflow status.' ) );
	}

	// Return the updated status to the AJAX success handler
	wp_send_json_success( array(
		'message' => 'Status updated successfully.',
		'status' => $status // Return the updated status
	) );
}
add_action( 'wp_ajax_aetp_activate_workflow', 'AETrendpilot\aetp_activate_workflow' );

function aetp_get_workflow_status_handler() {

	// Verify nonce for security
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'automation_engine_nonce' ) ) {
		wp_send_json_error( array( 'message' => 'Nonce verification failed.' ) );
		wp_die();
	}

	// Check if the user has the appropriate permissions
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => 'You do not have permission to perform this action' ) );
		wp_die();
	}

	global $wpdb;

	$templateID = isset( $_POST['templateID'] ) ? sanitize_text_field( wp_unslash( $_POST['templateID'] ) ) : '';

	// Check if required data is present
	if ( empty( $templateID ) ) {
		wp_send_json_error( array( 'message' => 'Template ID is missing.' ) );
	}

	// Define the table name
	$table_name = $wpdb->prefix . 'trendpilot_automation_engine_workflows';

	// Query the workflow status and is_repeat fields
	$workflow = $wpdb->get_row( $wpdb->prepare(
		"SELECT status, is_repeat FROM $table_name WHERE unique_id = %s",
		$templateID
	) ); // db call ok

	if ( ! $workflow ) {
		wp_send_json_error( array( 'message' => 'Workflow not found.' ) );
	}

	// Validate the status field to ensure it contains only allowed values
	$valid_statuses = array( 'active', 'inactive' );
	if ( ! in_array( $workflow->status, $valid_statuses, true ) ) {
		wp_send_json_error( array( 'message' => 'Invalid workflow status.' ) );
	}

	// Return the status and is_repeat data
	wp_send_json_success( array(
		'message' => 'Data retrieved successfully.',
		'status' => $workflow->status,  // The workflow status (active/inactive)
		'is_repeat' => $workflow->is_repeat // The is_repeat field (0 or 1)
	) );
}

add_action( 'wp_ajax_aetp_get_workflow_status', 'AETrendpilot\aetp_get_workflow_status_handler' );

function aetp_get_state_by_id_handler() {

	// Check if the user has the appropriate permissions
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => 'You do not have permission to perform this action' ) );
		wp_die();
	}

	global $wpdb;

	// Verify nonce for security
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'automation_engine_nonce' ) ) {
		wp_send_json_error( array( 'message' => 'Nonce verification failed.' ) );
		wp_die();
	}

	// Get the template ID (unique_id) from the POST request
	$templateID = isset( $_POST['templateID'] ) ? sanitize_text_field( wp_unslash( $_POST['templateID'] ) ) : '';
	if ( empty( $templateID ) ) {
		wp_send_json_error( array( 'message' => 'No template ID provided.' ) );
	}

	// Query the `trendpilot_automation_engine_states` table for the state
	$table_name = $wpdb->prefix . 'trendpilot_automation_engine_states';

	$state = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE unique_id = %s AND is_child = 0", $templateID ), ARRAY_A ); // db call ok

	if ( ! $state ) {
		// If no state is found, return a response with default "N/A" values
		$defaultResponse = [ 
			"id" => "N/A",
			"unique_id" => "N/A",
			"user_id" => "N/A",
			"current_step" => "N/A",
			"parameters" => "N/A",
			"steps" => "N/A",
			"status" => "N/A",
			"is_child" => "N/A"
		];
		wp_send_json_success( $defaultResponse );
	}

	// Count child states (if any)
	$child_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE unique_id = %s AND is_child = 1", $templateID ) ); // db call ok

	// Decode and format parameters if they exist
	if ( isset( $state['parameters'] ) && ! empty( $state['parameters'] ) ) {
		$decodedParameters = json_decode( $state['parameters'], true );

		if ( json_last_error() === JSON_ERROR_NONE && is_array( $decodedParameters ) ) {
			$formattedParameters = [];
			foreach ( $decodedParameters as $key => $value ) {
				if ( $key === 'product_id' && function_exists( 'wc_get_product' ) ) {
					$product = wc_get_product( intval( $value ) );
					$productName = $product ? $product->get_name() : 'Unknown Product';
					$formattedParameters[] = "Product: " . sanitize_text_field( $productName );
				} elseif ( $key === 'cat_id' && function_exists( 'get_term' ) ) {
					$term = get_term( intval( $value ), 'product_cat' );
					$catName = $term && ! is_wp_error( $term ) ? $term->name : 'Unknown Category';
					$formattedParameters[] = "Product Category: " . sanitize_text_field( $catName );
				} elseif ( $key === 'coupon_id' ) {
					$formattedParameters[] = "Coupon Code: " . sanitize_text_field( $value );
				} else {
					$formattedParameters[] = sanitize_text_field( $key ) . ": " . sanitize_text_field( $value );
				}
			}
			$state['parameters'] = implode( ', ', $formattedParameters );
		} else {
			$state['parameters'] = 'Invalid JSON data';
		}
	}

	// Add child count to the state array
	$state['child_count'] = $child_count;

	// Send the response with the state data
	wp_send_json_success( array( 'message' => 'State retrieved successfully.', 'data' => $state ) );
}

add_action( 'wp_ajax_aetp_get_state_by_id', 'AETrendpilot\aetp_get_state_by_id_handler' );

function aetp_run_button_click_handler() {

	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'automation_engine_nonce' ) ) {
		wp_send_json_error( array( 'message' => 'Nonce verification failed.' ) );
		wp_die();
	}

	// Check if the user has the appropriate permissions
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => 'You do not have permission to perform this action' ) );
		wp_die();
	}

	// Get the current user ID
	$current_user_id = get_current_user_id();

	// Retrieve the POST data
	$templateID = isset( $_POST['templateID'] ) ? sanitize_text_field( wp_unslash( $_POST['templateID'] ) ) : '';

	// Check if template ID is provided
	if ( empty( $templateID ) ) {
		wp_send_json_error( array( 'message' => 'No template ID provided.' ) );
	}

	global $wpdb;

	// Check if a workflow exists for the given unique_id (templateID)
	$workflow_table = $wpdb->prefix . 'trendpilot_automation_engine_workflows';
	$workflow = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $workflow_table WHERE unique_id = %s", $templateID ) ); // db call ok

	if ( ! $workflow ) {
		wp_send_json_error( array( 'message' => 'Workflow not found. Please save or activate the workflow.' ) );
	}

	// Check if the workflow status is 'active'
	if ( $workflow->status === 'active' ) {

		// Run the workflow using your existing `AETrendpilotWorkflow` class
		$workflowInstance = new AETrendpilotWorkflow();

		// Call the relevant function to run the workflow
		$workflowInstance->on_workflow_create_update( $templateID );

		// Send a success response indicating the workflow was run successfully
		wp_send_json_success( array( 'message' => 'Workflow successfully run.', 'unique_id' => $templateID ) );
	} else {
		// Return success but indicate that the workflow is inactive
		wp_send_json_success( array( 'message' => 'Workflow is inactive. Please activate it before running.', 'unique_id' => $templateID ) );
	}
}

add_action( 'wp_ajax_aetp_run_button_click', 'AETrendpilot\aetp_run_button_click_handler' );

function reset_button_clicked_handler() {

	// Check if the user has the appropriate permissions
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => 'You do not have permission to perform this action' ) );
		wp_die();
	}

	global $wpdb;

	// Check for nonce security
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'automation_engine_nonce' ) ) {
		wp_send_json_error( array( 'message' => 'Nonce verification failed.' ) );
	}

	// Get the current user ID
	$current_user_id = get_current_user_id();

	// Retrieve the template ID (unique_id) from the POST request
	$unique_id = isset( $_POST['templateID'] ) ? sanitize_text_field( wp_unslash( $_POST['templateID'] ) ) : '';

	// Check for a valid unique_id
	if ( empty( $unique_id ) ) {
		wp_send_json_error( array( 'message' => 'No template ID provided.' ) );
	}

	// Attempt to delete states with the provided unique_id from the `trendpilot_automation_engine_states` table
	$states_table = $wpdb->prefix . 'trendpilot_automation_engine_states';
	$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM $states_table WHERE unique_id = %s", $unique_id ) ); // db call ok

	// Handle errors in deletion
	if ( $deleted === false ) {
		wp_send_json_error( array( 'message' => 'Error deleting states.' ) );
	}

	if ( $deleted === 0 ) {
		wp_send_json_success( array( 'message' => 'No states found for the provided unique_id.', 'unique_id' => $unique_id ) );
	}

	// Success: return the number of deleted states
	wp_send_json_success( array( 'message' => 'Workflow progression reset.', 'unique_id' => $unique_id, 'deleted_count' => $deleted ) );
}
add_action( 'wp_ajax_aetp_reset_button_clicked', 'AETrendpilot\reset_button_clicked_handler' );

//AJAX handler to handle 'load child states'
function aetp_handle_load_child_states() {

	// Verify nonce for security
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'automation_engine_nonce' ) ) {
		wp_send_json_error( array( 'message' => 'Nonce verification failed.' ) );
		wp_die();
	}

	// Check if the user has the appropriate permissions
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => 'You do not have permission to perform this action' ) );
		wp_die();
	}

	// Fetch the template ID and ensure it's set
	$template_id = isset( $_POST['templateID'] ) ? sanitize_text_field( wp_unslash( $_POST['templateID'] ) ) : null;
	if ( ! $template_id ) {
		wp_send_json_error( array( 'message' => 'Template ID is required.' ) );
	}

	global $wpdb;

	// Query to get child states including the steps column
	$states_table = $wpdb->prefix . 'trendpilot_automation_engine_states';

	$child_states = $wpdb->get_results( $wpdb->prepare( "SELECT id, current_step, parameters, steps, status FROM $states_table WHERE unique_id = %s AND is_child = 1", $template_id ), ARRAY_A ); // db call ok

	if ( ! $child_states ) {
		wp_send_json_success( array( 'message' => 'No child states found.' ) );
	}

	// Process and format each child state
	$formatted_states = array_map( function ($state) {
		// Decode the steps JSON
		$steps = json_decode( $state['steps'], true );

		// Initialize default values
		$product_name = $category_name = $coupon_name = '';

		// Find the step with 'any_' event
		foreach ( $steps['steps'] as $step ) {
			if ( isset( $step['event']['name'] ) && strpos( $step['event']['name'], 'any_' ) === 0 ) {
				// Extract parameters from the found step
				$parameters = $step['event']['parameters'];

				if ( isset( $parameters['product_id'] ) && $parameters['product_id'] && function_exists( 'wc_get_product' ) ) {
					$product = wc_get_product( $parameters['product_id'] );
					$product_name = $product ? $product->get_name() : '';
				}
				if ( isset( $parameters['cat_id'] ) && is_numeric( $parameters['cat_id'] ) ) {
					$category = get_term( $parameters['cat_id'], 'product_cat' );
					$category_name = $category && ! is_wp_error( $category ) ? $category->name : '';
				}
				if ( isset( $parameters['coupon_id'] ) ) {
					$coupon_name = sanitize_text_field( $parameters['coupon_id'] );
				}
				break; // Stop after finding the 'any_' event
			}
		}

		return [ 
			'id' => intval( $state['id'] ),
			'current_step' => intval( $state['current_step'] ),
			'product_name' => sanitize_text_field( $product_name ),
			'category_name' => sanitize_text_field( $category_name ),
			'coupon_name' => sanitize_text_field( $coupon_name ),
			'status' => sanitize_text_field( $state['status'] )
		];
	}, $child_states );

	// Send the formatted states as a response
	wp_send_json_success( $formatted_states );
}

add_action( 'wp_ajax_aetp_load_child_states', 'AETrendpilot\aetp_handle_load_child_states' );

//AJAX handler to delete a branch
function aetp_handle_delete_branch() {

	// Verify the nonce for security
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'automation_engine_nonce' ) ) {
		wp_send_json_error( array( 'message' => 'Nonce verification failed.' ) );
		return;
	}

	// Check if the user has the appropriate permissions
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => 'You do not have permission to perform this action' ) );
		wp_die();
	}

	// Sanitize and retrieve the branch (state) ID
	$state_id = isset( $_POST['branchId'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['branchId'] ) ) ) : '';

	if ( empty( $state_id ) ) {
		wp_send_json_error( array( 'message' => 'State ID is required.' ) );
		return;
	}

	global $wpdb;
	$states_table = $wpdb->prefix . 'trendpilot_automation_engine_states';

	// Delete the state with the provided state_id
	$delete_result = $wpdb->delete( $states_table, [ 'id' => $state_id ], [ '%d' ] ); // db call ok

	if ( false === $delete_result ) {
		wp_send_json_error( array( 'message' => 'Error deleting the state.' ) );
		return;
	}

	// Success response
	wp_send_json_success( array( 'message' => 'Branch successfully deleted', 'state_id' => $state_id ) );
}

// Register the AJAX handler for logged-in users
add_action( 'wp_ajax_aetp_delete_branch', 'AETrendpilot\aetp_handle_delete_branch' );

function aetp_handle_get_cron_time() {

	// Verify nonce for security
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'automation_engine_nonce' ) ) {
		wp_send_json_error( array( 'message' => 'Invalid nonce.' ) );
		return;
	}

	// Check if the user has the appropriate permissions
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => 'You do not have permission to perform this action' ) );
		wp_die();
	}

	// Get the cron job time from the WordPress options table
	$cron_time = get_option( 'aetp_cron_job_time' );

	// Check if the cron time option exists
	if ( $cron_time === false ) {
		wp_send_json_error( array( 'message' => 'Cron job time option not found.' ) );
		return;
	}

	// Get the current time in the same format (H:i)
	$current_time = current_time( 'H:i' );

	// Compare the current time with the cron time
	if ( $cron_time <= $current_time ) {
		$cron_job_time_data = $cron_time . ' tomorrow';
	} else {
		$cron_job_time_data = $cron_time . ' today';
	}

	// Return the result as a success response
	wp_send_json_success( array( 'cron_job_time_data' => $cron_job_time_data ) );
}
add_action( 'wp_ajax_aetp_get_cron_time', 'AETrendpilot\aetp_handle_get_cron_time' );

// AJAX handler to delete workflow from our server (both local template and workflow data)
function aetp_handle_delete_local_workflow() {

	// Check nonce for security
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'automation_engine_nonce' ) ) {
		wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
	}

	// Check if the user has the appropriate permissions
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => 'You do not have permission to perform this action' ) );
		wp_die();
	}

	// Get the unique_id (listItemNum) from the AJAX request
	$listItemNum = isset( $_POST['listItemNum'] ) ? sanitize_text_field( wp_unslash( $_POST['listItemNum'] ) ) : 0;
	if ( $listItemNum <= 0 ) {
		wp_send_json_error( array( 'message' => 'Invalid list item number' ) );
	}

	global $wpdb;

	// 1. Delete from `trendpilot_workflow_templates`
	$table_name_templates = $wpdb->prefix . 'trendpilot_workflow_templates';
	$deleted_template = $wpdb->delete(
		$table_name_templates,
		array( 'unique_id' => $listItemNum ),
		array( '%s' )
	); // db call ok

	// 2. Delete from `trendpilot_automation_engine_workflows`
	$table_name_workflows = $wpdb->prefix . 'trendpilot_automation_engine_workflows';
	$deleted_workflow = $wpdb->delete(
		$table_name_workflows,
		array( 'unique_id' => $listItemNum ),
		array( '%s' )
	); // db call ok

	// 3. Delete from `trendpilot_automation_engine_states` (only if an entry exists)
	$table_name_states = $wpdb->prefix . 'trendpilot_automation_engine_states';
	$state_exists = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM $table_name_states WHERE unique_id = %s",
			$listItemNum
		)
	); // db call ok

	$deleted_states = 0;
	if ( $state_exists ) {
		$deleted_states = $wpdb->delete(
			$table_name_states,
			array( 'unique_id' => $listItemNum ),
			array( '%s' )
		); // db call ok
	}

	// Check if the deletion was successful from the first two tables (ignore the state table if no entry exists)
	if ( ( $deleted_template !== false && $deleted_template > 0 ) &&
		( $deleted_workflow !== false && $deleted_workflow > 0 ) ) {
		wp_send_json_success( array( 'message' => 'Workflow deleted successfully' ) );
	} else {
		// Log detailed error information for debugging
		$error_message = $wpdb->last_error;
		$sql_query = $wpdb->last_query;

		wp_send_json_error( array(
			'message' => 'Error deleting workflow',
			'error_message' => $error_message,
			'sql_query' => $sql_query,
			'listItemNum' => $listItemNum
		) );
	}
}

add_action( 'wp_ajax_aetp_delete_local_workflow', 'AETrendpilot\aetp_handle_delete_local_workflow' );

//AJAX handler to delete workflow and state from users site.
function aetp_handle_delete_external_workflow() {

	// Check nonce for security
	check_ajax_referer( 'automation_engine_nonce', 'nonce' );

	// Check if the user has the appropriate permissions
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => 'You do not have permission to perform this action' ) );
		wp_die();
	}

	// Get current user ID
	$user_id = get_current_user_id();

	// Get user meta for site URL and API key
	$site_url = get_user_meta( $user_id, 'mepr_your_website_url', true );
	$api_key = get_user_meta( $user_id, 'mepr_plugin_api_key', true );

	// Ensure we have the necessary data
	if ( empty( $site_url ) || empty( $api_key ) ) {
		wp_send_json_error( 'Missing site URL or API key' );
	}

	// Get the unique_id from the AJAX request
	if ( ! isset( $_POST['listItemNum'] ) ) {
		wp_send_json_error( 'Missing list item number' );
	}
	$unique_id = isset( $_POST['listItemNum'] ) ? sanitize_text_field( wp_unslash( $_POST['listItemNum'] ) ) : '';

	// Prepare the API request
	$api_url = trailingslashit( $site_url ) . 'wp-json/bubble/v1/delete-workflow-and-state';

	$request_body = wp_json_encode( [ 
		'api_key' => $api_key,
		'unique_id' => $unique_id
	] );

	$response = wp_remote_post( $api_url, [ 
		'method' => 'POST',
		'headers' => [ 
			'Content-Type' => 'application/json',
		],
		'body' => $request_body
	] );

	// Handle the response
	if ( is_wp_error( $response ) ) {
		wp_send_json_error( 'API request failed: ' . $response->get_error_message() );
	}

	$response_code = wp_remote_retrieve_response_code( $response );
	$response_body = wp_remote_retrieve_body( $response );
	$response_data = json_decode( $response_body, true );

	if ( $response_code !== 200 ) {
		wp_send_json_error( 'API request failed with status code: ' . $response_code . ' - ' . $response_body );
	}

	wp_send_json_success( $response_data );
}
add_action( 'wp_ajax_aetp_delete_external_workflow', 'AETrendpilot\aetp_handle_delete_external_workflow' );

function tpae_remove_recommended_product() {

	// Nonce check
	if ( ! check_ajax_referer( 'tpae_remove_recommended_product_nonce', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => 'Security check failed' ) );
		return; // Exit if nonce check fails
	}

	// Capability check
	if ( ! current_user_can( 'delete_posts' ) ) { // Adjust capability as necessary
		wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		return;
	}

	// Validate product ID
	$product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
	if ( ! $product_id ) {
		wp_send_json_error( array( 'message' => 'Invalid product ID' ) );
		return;
	}

	// Attempt to delete the product
	global $wpdb;
	$recommended_table_name = $wpdb->prefix . 'trendpilot_automation_engine_recommended_products';
	$result = $wpdb->delete( $recommended_table_name, array( 'product_id' => $product_id ) ); // db call ok

	if ( $result ) {
		wp_send_json_success( array( 'message' => 'Product successfully deleted' ) );
	} else {
		wp_send_json_error( array( 'message' => 'Could not delete the product' ) );
	}
}
add_action( 'wp_ajax_aetp_remove_recommended_product', 'AETrendpilot\tpae_remove_recommended_product' );
