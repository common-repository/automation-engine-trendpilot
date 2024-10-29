<?php

namespace AETrendpilot;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AETrendpilotWorkflow {


	function continueWorkflow( $state ) {
		$state = $this->refreshState( $state->id );

		// Fetch the current step from the state object
		$current_step = $state->current_step;

		$stepParameters = [];
		$stateParameters = [];

		// Retrieve the workflow associated with the state
		$workflow = $this->findWorkflow( $state->unique_id );

		// Decode the JSON in the 'steps' column of the workflow entry
		$workflowSteps = json_decode( $workflow->steps );
		if ( ! is_object( $workflowSteps ) || ! isset( $workflowSteps->steps ) || ! is_array( $workflowSteps->steps ) ) {
			return;  // Exit the function if validation fails
		}

		// Sanitize any strings within the steps
		foreach ( $workflowSteps as $step ) {
			array_walk_recursive( $step, function (&$item, $key) {
				if ( is_string( $item ) ) {
					$item = sanitize_text_field( $item );
				}
			} );
		}

		// Iterate through the steps starting from the current step
		for ( $i = $current_step; $i < count( $workflowSteps->steps ); $i++ ) {

			$step = $workflowSteps->steps[ $i ];

			if ( $step->type == 'action' ) {
				$state = $this->refreshState( $state->id );

				$stepParameters = $step->action->parameters;

				$state = $this->refreshState( $state->id );

				$stateParameters = null;

				// Retrieve the 'parameters' field from the state using json_decode
				if ( ! is_null( $state->parameters ) && is_string( $state->parameters ) ) {
					$stateParameters = json_decode( $state->parameters, true ); // true for associative array
				}

				if ( $stateParameters === false || $stateParameters === null ) {
					$stateParameters = [];
				} else if ( ! is_array( $stateParameters ) ) {
					return;
				}

				// Execute the action and pass the state parameters
				$actionReturnParams = $this->executeAction( $step->action, $stateParameters );

				if ( $actionReturnParams !== false ) { // if the action completed successfully...
					if ( ! is_array( $actionReturnParams ) ) {
						$actionReturnParams = [];
					}

					// If this IS the last step ( no need to store params here)
					if ( $i == count( $workflowSteps->steps ) - 1 ) {
						if ( $workflow->is_repeat == true ) {
							if ( $state->is_child ) {
								$this->deleteState( $state->id ); // delete the child state if is_repeat is set to true
							} else {
								$this->changeStateStatus( $state->id, 'in-progress' ); // mark the state as 'in-progress'
								$this->updateStateStep( $state->id, 0 );

								// Remove all state parameters
								$this->clearStateParams( $state );

								$this->continueWorkflow( $state ); // Run continueWorkflow again
							}
						} else {
							$state = $this->refreshState( $state->id );
							$this->changeStateStatus( $state->id, 'completed' ); // mark the state as completed
							$this->updateStateParams( $state, $actionReturnParams );
						}

						// If this is NOT the last step
					} else {
						$state = $this->refreshState( $state->id );

						// Log the step here
						$this->logStep( $workflow->unique_id, $i, 'Success', $step->action->name, $state->user_id );

						$this->updateStateStep( $state->id, $current_step + 1 );
						$current_step++;

						// Save the returned parameters from the action to the state
						$this->updateStateParams( $state, $actionReturnParams );
					}

				} else {
					$this->logStep( $workflow->unique_id, $i, 'Failure. Action did not complete successfully', $step->action->name, $state->user_id );
					break;  // exit the loop
				}

			} elseif ( $step->type == 'event' ) {

				$stepParameters = $step->event->parameters;

				if ( $step->event->name === 'end_workflow' ) {
					if ( $workflow->is_repeat ) {
						if ( $state->is_child ) {
							$this->deleteState( $state->id );
						} else {
							$this->changeStateStatus( $state->id, 'in-progress' );
							$this->updateStateStep( $state->id, 0 );

							// Remove all state parameters
							$this->clearStateParams( $state );

							$this->continueWorkflow( $state ); // Run continueWorkflow again
						}
					} else {
						$this->changeStateStatus( $state->id, 'completed' );
						$this->logStep( $workflow->unique_id, $state->current_step, 'Workflow completed', $step->event->name );
					}

				} else if ( $i == count( $workflowSteps->steps ) - 1 ) {
					$this->logStep( $workflow->unique_id, $i, 'Failure. event at final step of workflow', $step->event->name, $state->user_id );
					break;
				} else {
					$eventName = $step->event->name;
					$functionToCall = $this->getCorrespondingFunction( $eventName );

					if ( $functionToCall !== null ) {
						$functionToCall( sanitize_text_field( $workflow->unique_id ), $this );
					} else {
						// Handle error: No corresponding function found
					}
				}

				break;
			}
		}
	}


	function refreshState( $state_id ) {

		global $wpdb;

		// Define the table name for states
		$table_name = $wpdb->prefix . 'trendpilot_automation_engine_states';

		// Fetch the updated state from the database
		$updated_state = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $state_id ) );

		if ( $updated_state ) {
			return $updated_state; // Return the updated state object
		} else {
			// Handle the error appropriately
			return null; // Return null or handle it differently based on your application's needs
		}
	}

	// we will pass into the function a string, the event name. The function will return the corresponding function to that string.
	function getCorrespondingFunction( $eventName ) {
		$functionMap = [ 
			'any_rec_product_clicked' => 'tpapAnyRecProductClicked',
			'coupon_used' => 'tpapCouponUsed',
			'on_set_date' => 'tpapOnSetDate',
			'product_cat_viewed' => 'tpapProductCatViewed',
			'product_not_viewed' => 'tpapProductNotViewed',
			'product_older_than' => 'tpapProductOlderThan',
			'product_purchased' => 'tpapProductPurchased',
			'product_viewed' => 'tpapProductViewed',
			'run_workflow_now' => 'tpapRunWorkflowNow',
			'upsell_clicked' => 'tpapUpsellClicked',
			'wait_x_days' => 'tpapWaitXDays',
			'x_new_users' => 'tpapXNewUsers',
			'x_purchases_made' => 'tpapXPurchasesMade',
			'calculate_category' => 'tpapCalculateCategory',
			'calculate_product' => 'tpapCalculateProduct',
			'change_default_price' => 'tpapChangeDefaultPrice',
			'change_product_status' => 'tpapChangeProductStatus',
			'change_upsell_product' => 'tpapChangeUpsellProduct',
			'display_product_everywhere' => 'tpapDisplayProductEverywhere',
			'enable_disable_upsell' => 'tpapEnableDisableUpsell',
			'make_product_featured' => 'tpapMakeProductFeatured',
			'make_product_not_featured' => 'tpapMakeProductNotFeatured',
			'product_off_recommended' => 'tpapProductOffRecommended',
			'product_to_recommended' => 'tpapProductToRecommended',
			'put_all_products_on_sale' => 'tpapPutAllProductsOnSale',
			'put_category_on_sale' => 'tpapPutCategoryOnSale',
			'put_product_on_sale' => 'tpapPutProductOnSale',
			'send_admin_alert' => 'tpapSendAdminAlert',
			'take_all_products_off_sale' => 'tpapTakeAllProductsOffSale',
			'take_category_off_sale' => 'tpapTakeCategoryOffSale',
			'take_product_off_sale' => 'tpapTakeProductOffSale',
			'any_coupon_used' => 'tpapAnyCouponUsed',
			'any_product_not_viewed' => 'tpapAnyProductNotViewed',
			'any_product_older_than' => 'tpapAnyProductOlderThan',
			'any_product_purchased' => 'tpapAnyProductPurchased',
			'any_product_viewed' => 'tpapAnyProductViewed',
			'any_product_cat_viewed' => 'tpapAnyProductCatViewed',
			'add_remove_tag' => 'tpapAddRemoveProductTag',
			'change_topbar_text' => 'tpapChangeTopbarText',
			'enable_disable_topbar' => 'tpapEnableDisableTopbar',
			'change_product_badge' => 'tpapChangeProductBadge',
			'change_product_display' => 'tpapChangeProductDisplay',
			'product_stock' => 'tpapProductStock',
			'any_product_stock' => 'tpapAnyProductStock',
			'show_hide_product' => 'tpapShowHideProduct'
			// ... any additional mappings
		];

		return $functionMap[ $eventName ] ?? null;
	}


	/**
	 * Creates a new workflow state in the database.
	 *
	 * @param string $unique_id ID of the associated workflow.
	 * @param array $parameters Parameters associated with the workflow state.
	 * @param int $user_id ID of the user who triggered the workflow.
	 * @param string $currentStep Current step of the workflow.
	 * @return object|null The newly created state object or null if the operation fails.
	 */
	function createState( $unique_id, $parameters, $user_id = null, $currentStep = 0 ) {
		global $wpdb;

		// Sanitize and validate inputs
		$unique_id = sanitize_text_field( $unique_id );
		$currentStep = sanitize_text_field( $currentStep );
		$user_id = isset( $user_id ) ? intval( $user_id ) : null;

		$table_name = $wpdb->prefix . 'trendpilot_automation_engine_states';

		// Prepare data
		$data = array(
			'unique_id' => $unique_id,
			'user_id' => $user_id,
			'current_step' => $currentStep,
			'status' => 'in-progress',
			'is_child' => false
		);

		// Insert into the database using wpdb->insert
		$wpdb->insert( $table_name, $data, array( '%s', '%d', '%s', '%s', '%d' ) );

		// Get the insert ID safely
		$insert_id = (int) $wpdb->insert_id;

		// Retrieve the new state safely using a prepared statement
		$new_state = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $insert_id ) );

		return $new_state;
	}


	/**
	 * Marks a workflow state as complete.
	 *
	 * @param int $state_id ID of the state to be marked as complete.
	 * @return void
	 */
	function markStateAsComplete( $state_id ) {
		global $wpdb;

		// Cast $state_id to an integer
		$state_id = (int) $state_id;

		$data = array(
			'status' => 'completed' // Marking the status as completed
		);

		$where = array( 'id' => $state_id );

		$wpdb->update( 'workflow_states', $data, $where ); // Execute the update
	}


	/**
	 * Retrieves a workflow based on the given workflow ID.
	 *
	 * @param int $workflow_id ID of the workflow to retrieve.
	 * @return object|null Returns the matching workflow or null if not found.
	 */
	function findWorkflow( $unique_id ) {
		global $wpdb;

		// Correct table name with WordPress database prefix
		$table_name = $wpdb->prefix . 'trendpilot_automation_engine_workflows';

		// Prepare the SQL query using the correct table name
		$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE unique_id = %s", $unique_id ) );

		return $result;
	}


	/**
	 * Finds workflows that start with the specified event name.
	 *
	 * @param string $eventName      The name of the event to search for.
	 * @param bool   $includeStates  Whether to include states in the result (default is false).
	 *
	 * @return object[] An array of objects representing the workflows that start with the specified event.
	 */
	function findWorkflowsStartingWithEvent( $eventName, $includeStates = false ) {
		global $wpdb;

		// Validate and sanitize $includeStates and $eventName
		if ( ! is_bool( $includeStates ) ) {
			throw new \InvalidArgumentException( 'The includeStates parameter must be a boolean.' );
		}
		$eventName = sanitize_text_field( $eventName );

		// Construct the full table name with the WPDB prefix
		$workflowTableName = $wpdb->prefix . 'trendpilot_automation_engine_workflows';

		// Prepare SQL statement to find all workflows with status 'active'
		$stateCondition = $includeStates ? '' : 'AND s.unique_id IS NULL';

		$results = $wpdb->get_results( $wpdb->prepare( "SELECT w.* FROM {$workflowTableName} w
		LEFT JOIN states s ON w.unique_id = s.unique_id
		WHERE w.status = %s {$stateCondition}", 'active' ) );

		// Filter the results based on the event name in the 'steps' column
		$workflows = array_filter( $results, function ($row) use ($eventName) {
			$steps = json_decode( $row->steps );
			return isset( $steps[0] ) && isset( $steps[0]->event ) && $steps[0]->event == $eventName;
		} );

		return $workflows;
	}


	/**
	 * Updates the current step in a workflow state.
	 *
	 * @param int $state_id ID of the state to be updated.
	 * @param int $step_index Index of the current step within the workflow.
	 * @return void
	 */
	function updateStateStep( $state_id, $step_index ) {
		global $wpdb;

		// The table where states are stored
		$table_name = $wpdb->prefix . 'trendpilot_automation_engine_states';

		// Explicitly cast step index to integer
		$step_index = (int) $step_index;
		$state_id = (int) $state_id;

		$data = array(
			'current_step' => $step_index // Updating the current step
		);

		$where = array( 'id' => $state_id );

		$wpdb->update(
			$table_name,  // Correct table name
			$data,        // Data to update
			$where,       // WHERE clause
			array( '%d' ),  // Data format for 'current_step'
			array( '%d' )   // WHERE format for 'id'
		);

	}

	/**
	 * Executes a specific action based on the provided action details.
	 *
	 * @param object $action The action object containing information about the action to execute.
	 * @param  array $eventData. the parameters from the current workflow's step.
	 * @return 
	 */
	function executeAction( $action, $stateParams ) {
		// Extracting step parameters and state parameters
		$stepParams = $action->parameters;

		// Convert stepParams to array if it's an object
		if ( is_object( $stepParams ) ) {
			$stepParams = get_object_vars( $stepParams );
		}

		// Get the action name and find the corresponding function
		$actionName = $action->name;
		$functionToCall = $this->getCorrespondingFunction( $actionName );

		if ( $functionToCall !== null ) {
			// If the corresponding function is found, call it with stepParams and stateParams
			return $functionToCall( $stepParams, $stateParams ); // Execute the function and return its result
		} else {
			// Handle error: No corresponding function found
			// Appropriate error handling or logging
			return false;
		}
	}

	/**
	 * This find states whose status is either not 'complete' or 'repeat'.
	 * // we will only be processing states that are not completed (as completed states have already run once), or are set to repeat (as states with status 'repeat' are going to run repeatedly).
	 */
	function findInProgressStates( $uniqueId = null ) {
		global $wpdb;

		// The table where states are stored
		$table_name = $wpdb->prefix . 'trendpilot_automation_engine_states';

		// Base query to find all states where the status is 'in-progress'
		if ( $uniqueId !== null ) {
			// Safely prepare the query with the uniqueId to prevent SQL injection
			$sql = $wpdb->prepare( "SELECT * FROM $table_name WHERE status = 'in-progress' AND unique_id = %s", $uniqueId );
		} else {
			// Prepare the base query without the uniqueId
			$sql = $wpdb->prepare( "SELECT * FROM $table_name WHERE status = %s", 'in-progress' );
		}

		// $sql is prepared
		$results = $wpdb->get_results( $sql );

		return $results;
	}

	/**
	 * This function finds a workflow via its unique_id.
	 *  * @param string $unique_id A string that we will find the correspnding workflow for.
	 */
	function findWorkflowByUniqueId( $unique_id ) {
		global $wpdb;

		// The table where workflows are stored
		$table_name = $wpdb->prefix . 'trendpilot_automation_engine_workflows';

		// Execute the query
		$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE unique_id = %s", $unique_id ) );

		return $result;
	}


	/**
	 * This function checks the products views in the last X days.
	 *
	 * @param int $productID is the ID of the product to check
	 * @param int $days the amount of days to check its view count for.
	 *
	 * @return mixed returns the number of views if the product ID was viewed in the last $days days, or false otherwise
	 */
	function checkProductViews( $productID, $days ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'trendpilot_automation_engine_page_views';  // Adjust to your table name

		// Calculate the date $days ago, including today
		$days_ago = wp_date( 'Y-m-d', strtotime( "-$days days +1 day" ) );

		// Execute the query
		$result = $wpdb->get_var( $wpdb->prepare(
			"SELECT SUM(views) as total_views FROM $table_name WHERE product_id = %d AND viewed_date >= %s",
			$productID,
			$days_ago
		) );

		// Check and return the result
		if ( $result > 0 ) {
			return $result;
		} else {
			return false;
		}
	}


	/**
	 * Checks if a product has been viewed a specific number of times within a given number of days.
	 *
	 * @param int $productID  The ID of the product to check.
	 * @param int $days       The number of days within which to check for views.
	 * @param int $amount     The minimum number of views required for the function to return true.
	 *                        If set to 0, function checks for exactly 0 views.
	 *
	 * @return bool           Returns true if the conditions are met, otherwise returns false.
	 */
	function checkProductViewedXTimes( $productID, $days, $amount ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'trendpilot_automation_engine_page_views';  // Adjust to your table name

		// Calculate the date $days ago, including today
		$days_ago = wp_date( 'Y-m-d', strtotime( "-$days days +1 day" ) );

		// Execute the query
		$result = $wpdb->get_var( $wpdb->prepare(
			"SELECT SUM(views) as total_views FROM $table_name WHERE product_id = %d AND viewed_date >= %s",
			(int) $productID,
			$days_ago  // Ensure this remains a string representing a date
		) );

		// Handle NULL result as 0 views
		$result = $result !== null ? intval( $result ) : 0;

		if ( $wpdb->last_error ) {
			error_log( "Database error in checkProductViewedXTimes" );
		}

		// Handle the 'amount' parameter differently for 0
		if ( $amount === 0 ) {
			$is_zero = $result === 0;
			return $is_zero;
		} else {
			$meets_amount = $result >= $amount;
			return $meets_amount;
		}
	}


	function checkProductViewedGrowth( $productID, $perc_amount, $days ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'trendpilot_automation_engine_page_views';

		// Calculate the dates for the recent and previous periods
		$recent_end_date = wp_date( 'Y-m-d 23:59:59' );
		$recent_start_date = wp_date( 'Y-m-d 00:00:00', strtotime( "-$days days", strtotime( $recent_end_date ) ) + 1 );
		$previous_end_date = wp_date( 'Y-m-d 23:59:59', strtotime( $recent_start_date ) - 1 );
		$previous_start_date = wp_date( 'Y-m-d 00:00:00', strtotime( "-$days days", strtotime( $previous_end_date ) ) + 1 );

		// Execute the query
		$recent_views = $wpdb->get_var( $wpdb->prepare(
			"SELECT SUM(views) as total_views FROM $table_name WHERE product_id = %d AND viewed_date BETWEEN %s AND %s",
			(int) $productID,
			$recent_start_date,
			$recent_end_date
		) );

		// Execute the query
		$previous_views = $wpdb->get_var( $wpdb->prepare(
			"SELECT SUM(views) as total_views FROM $table_name WHERE product_id = %d AND viewed_date BETWEEN %s AND %s",
			(int) $productID,
			$previous_start_date,
			$previous_end_date
		) );

		// Handle NULL results as 0 views
		$recent_views = $recent_views !== null ? intval( $recent_views ) : 0;
		$previous_views = $previous_views !== null ? intval( $previous_views ) : 0;

		if ( $wpdb->last_error ) {

			error_log( "Database error in checkProductViewedGrowth" );

		}

		// Calculate growth percentage
		if ( $previous_views > 0 ) {
			$growth_percentage = ( ( $recent_views - $previous_views ) / $previous_views ) * 100;
		} else {
			$growth_percentage = $recent_views > 0 ? 100 : 0;
		}


		// Compare growth percentage to perc_amount
		if ( $perc_amount < 0 ) {

			$result = $growth_percentage <= $perc_amount;

			return $growth_percentage <= $perc_amount;


		} else {

			$result = $growth_percentage >= $perc_amount;

			return $growth_percentage >= $perc_amount;

		}


	}

	/**
	 * Creates a new child workflow state in the database.
	 *
	 * @param string $unique_id Unique ID of the associated workflow.
	 * @param array $parameters Parameters that differentiate this child state.
	 * @param int $currentStep The current step of the state.
	 * @param object $steps A 'steps' object. 
	 * @param int $user_id ID of the user who triggered the workflow (optional).
	 * @return object The newly created child state.
	 */
	function createChildState( $unique_id, $parameters, $currentStep, $steps, $user_id = null ) {
		global $wpdb;

		// Update the parameters of the current step in the steps array. 
		foreach ( $parameters as $key => $value ) {
			$steps->steps[ $currentStep ]->event->parameters->$key = $value;
		}

		// Sanitize any strings within the steps
		foreach ( $steps as $step ) {
			array_walk_recursive( $step, function (&$item, $key) {
				if ( is_string( $item ) ) {
					$item = sanitize_text_field( $item );
				}
			} );
		}

		// Encode the updated steps array to JSON
		$json_steps = wp_json_encode( $steps );

		// Define the table name and use json_encode for parameters
		$table_name = $wpdb->prefix . 'trendpilot_automation_engine_states';
		$parameters = array_map( [ $this, 'sanitize_parameters' ], $parameters );
		$json_parameters = wp_json_encode( $parameters ); // Changed to JSON encoding

		// Prepare data to insert
		$data = array(
			'unique_id' => sanitize_text_field( $unique_id ),
			'user_id' => $user_id,
			'current_step' => (int) $currentStep,
			'parameters' => $json_parameters, // JSON encoded parameters
			'steps' => $json_steps,
			'status' => 'in-progress',
			'is_child' => true
		);

		// Insert into the database
		$wpdb->insert( $table_name, $data );

		// Retrieve and return the newly created child state
		$insert_id = (int) $wpdb->insert_id;
		$new_child_state = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $insert_id ) );

		return $new_child_state;
	}


	function sanitize_parameters( $value ) {
		if ( is_array( $value ) ) {
			return array_map( [ $this, 'sanitize_parameters' ], $value );
		} elseif ( is_string( $value ) ) {
			return sanitize_text_field( $value );
		} elseif ( is_numeric( $value ) ) {
			return (int) $value;
		}
		return $value;
	}


	function updateStateParams( $state, $params ) {

		$params = array_map( [ $this, 'sanitize_parameters' ], $params );
		$allowed_params = [ 'product_id', 'cat_id', 'tag_id', 'start_date', 'wait_x_days_set_for_step', 'coupon_id' ];

		global $wpdb;
		$table_name = $wpdb->prefix . 'trendpilot_automation_engine_states';
		$state_id = (int) $state->id;

		// Fetch the existing parameters from the database for the given state
		$existingParamsJSON = $wpdb->get_var( $wpdb->prepare( "SELECT parameters FROM $table_name WHERE id = %d", $state_id ) );

		// Decode the JSON string to an associative array
		$existingParams = json_decode( $existingParamsJSON, true );

		// Initialize as an empty array if decoding fails or parameters are not set
		if ( ! is_array( $existingParams ) ) {
			$existingParams = array();
		}

		$existingParams = array_map( [ $this, 'sanitize_parameters' ], $existingParams );

		// Update the parameters based on the incoming $params array, ensuring only allowed keys are updated
		foreach ( $params as $key => $value ) {
			if ( ! in_array( $key, $allowed_params ) ) {
				continue; // Skip any parameters not in the whitelist
			}
			if ( is_object( $value ) ) {
				// Skip object values to prevent object injection
				continue;
			}
			// Safely update with validated non-object values
			$existingParams[ $key ] = $value;
		}

		// Encode the updated parameters back to a JSON string
		$updatedParamsJSON = wp_json_encode( $existingParams );

		// Update the 'parameters' column in the database
		$update_result = $wpdb->update(
			$table_name,
			array( 'parameters' => $updatedParamsJSON ), // JSON encoded parameters
			array( 'id' => $state_id ),
			array( '%s' ),
			array( '%d' )
		);
	}


	function clearStateParams( $state ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'trendpilot_automation_engine_states';

		// Instead of fetching and modifying, directly set the parameters to NULL or a serialized empty array
		$clearedParamsSerialized = null; // or use NULL directly if your column supports it

		// Update the 'parameters' column in the database to be empty
		$wpdb->update(
			$table_name,
			array( 'parameters' => $clearedParamsSerialized ), // or 'parameters' => NULL
			array( 'id' => (int) $state->id ),
			array( '%s' ), // or '%s' if using NULL
			array( '%d' )
		);
	}


	function deleteState( $state_id ) {
		global $wpdb;

		// The table where states are stored
		$table_name = $wpdb->prefix . 'trendpilot_automation_engine_states';

		// Delete the state where the 'id' matches the given state_id
		$wpdb->delete(
			$table_name,
			array( 'id' => $state_id ),
			array( '%d' )
		);
	}

	function changeStateStatus( $state_id, $new_status ) {
		global $wpdb;

		// The table where states are stored
		$table_name = $wpdb->prefix . 'trendpilot_automation_engine_states';

		// Update the 'status' column where the 'id' matches the given state_id
		$wpdb->update(
			$table_name,
			array( 'status' => $new_status ),
			array( 'id' => $state_id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	function getAllProducts() {
		$args = array(
			'post_type' => 'product',
			'posts_per_page' => -1,
			'post_status' => array( 'publish', 'pending', 'draft', 'future', 'private', 'inherit' ),
			'fields' => 'ids'
		);

		$product_ids = get_posts( $args );
		return $product_ids;
	}

	function logStep( $workflowId, $step, $description, $step_name, $userId = null ) {
		global $wpdb;
		$logger_table_name = $wpdb->prefix . 'trendpilot_automation_engine_workflow_logger';

		$data = array(
			'workflow_id' => sanitize_text_field( $workflowId ),
			'user_id' => (int) $userId,
			'step' => sanitize_text_field( $step ),
			'description' => sanitize_text_field( $description ),
			'step_name' => sanitize_text_field( $step_name ),
			'timestamp' => current_time( 'mysql', 1 )  // Use GMT time
		);

		$format = array( '%s', '%d', '%s', '%s', '%s', '%s' );
		$wpdb->insert( $logger_table_name, $data, $format );
	}


	function display_workflow_log() {
		global $wpdb;
		$logger_table_name = $wpdb->prefix . 'trendpilot_automation_engine_workflow_logger';

		$this->cleanup_old_workflow_logs();

		// Check for user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have sufficient permissions to access this page.' );
		}

		// Handle form submission for flushing logs
		if ( isset( $_POST['flush_logs'] ) && check_admin_referer( 'flush_logs_action', 'flush_logs_nonce' ) ) {
			$this->flush_workflow_logs();
			echo '<p>Logs have been flushed.</p>';
		}

		// Display flush button
		echo '<form method="post">';
		wp_nonce_field( 'flush_logs_action', 'flush_logs_nonce' );
		echo '<h2>Workflow logs for the last 2 days</h2>';
		echo '<input style="margin-bottom:20px;margin-top:10px" type="submit" name="flush_logs" value="Flush/Delete Logs">';
		echo '</form>';

		// Optionally, you might want to call cleanup_old_csv_logs() here or ensure it's adapted for database use

		// Fetch logs from the database
		$logs = $wpdb->get_results( "SELECT * FROM $logger_table_name ORDER BY timestamp DESC", ARRAY_A );

		if ( ! empty( $logs ) ) {
			echo '<table border="1">';
			echo '<thead><tr>';
			foreach ( array_keys( $logs[0] ) as $col ) {
				echo '<th>' . esc_html( $col ) . '</th>';
			}
			echo '</tr></thead>';
			echo '<tbody>';
			foreach ( $logs as $row ) {
				echo '<tr>';
				foreach ( $row as $col ) {
					echo '<td>' . esc_html( $col ) . '</td>';
				}
				echo '</tr>';
			}
			echo '</tbody>';
			echo '</table>';
		} else {
			echo '<p>No logs created yet.</p>';
		}
	}

	function flush_workflow_logs() {
		global $wpdb;
		$logger_table_name = $wpdb->prefix . 'trendpilot_automation_engine_workflow_logger';

		// Optional: Adjust the WHERE clause to target logs older than a certain date
		$wpdb->query( "DELETE FROM $logger_table_name WHERE 1=1" );  // This deletes all entries
	}


	function cleanup_old_workflow_logs() {

		global $wpdb;

		$logger_table_name = $wpdb->prefix . 'trendpilot_automation_engine_workflow_logger';

		// Define the cleanup condition, e.g., deleting logs older than 2 days
		$two_days_ago = current_time( 'mysql', 1 ); // Get the date in GMT for consistency
		$two_days_ago = wp_date( 'Y-m-d H:i:s', strtotime( $two_days_ago . ' -2 days' ) );

		// Execute the cleanup query
		$rows_affected = $wpdb->query( $wpdb->prepare( "DELETE FROM $logger_table_name WHERE timestamp < %s", $two_days_ago ) );

	}

	function getAllChildStatesForWorkflow( $workflow_unique_id ) {
		global $wpdb;

		// Define the table name
		$table_name = $wpdb->prefix . 'trendpilot_automation_engine_states';

		// Execute the query and get results
		$states = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE unique_id = %s AND is_child = 1",
			$workflow_unique_id
		) );

		// Return the fetched states
		return $states;
	}

	// function is used to see if a branch exists for a current item
	function findChildState( $workflow_unique_id, $parameterKey, $parameterValue, $currentStepIndex ) {

		$allStates = $this->getAllChildStatesForWorkflow( $workflow_unique_id );

		if ( empty( $allStates ) ) {
			return null;
		}

		foreach ( $allStates as $state ) {
			$stateSteps = json_decode( $state->steps );

			// Check if the current step index exists in the steps array
			if ( isset( $stateSteps->steps[ $currentStepIndex ] ) ) {
				// Check if the parameter in the current step matches
				if ( isset( $stateSteps->steps[ $currentStepIndex ]->event->parameters->$parameterKey ) &&
					$stateSteps->steps[ $currentStepIndex ]->event->parameters->$parameterKey == $parameterValue ) {
					// Matching child state found
					return $state;
				}
			}
		}

		// No matching child state found
		return null;
	}

	function getPurchasesForProduct( $product_id, $days, $offsetDays = 0 ) {
		global $wpdb;

		// Check if WooCommerce is active
		if ( ! function_exists( 'wc_get_orders' ) ) {

			return 'WooCommerce is not active';
		}

		// Calculate the start and end dates for the query
		// Adjust start and end dates to ensure no overlap
		$endDate = wp_date( 'Y-m-d 23:59:59', strtotime( "-{$offsetDays} days" ) );
		$startDate = wp_date( 'Y-m-d 00:00:00', strtotime( "-{$days} days", strtotime( $endDate ) ) + 1 );

		$purchase_count = $wpdb->get_var( $wpdb->prepare( "
		SELECT SUM(order_item_meta_qty.meta_value) as purchase_count
		FROM {$wpdb->prefix}woocommerce_order_items as order_items
		JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
		JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta_qty ON order_items.order_item_id = order_item_meta_qty.order_item_id
		JOIN {$wpdb->prefix}posts as posts ON order_items.order_id = posts.ID
		WHERE posts.post_status = 'wc-completed'
		AND order_item_meta.meta_key = '_product_id'
		AND order_item_meta.meta_value = %d
		AND order_item_meta_qty.meta_key = '_qty'
		AND posts.post_date BETWEEN %s AND %s",
			(int) $product_id,
			$startDate,
			$endDate
		) ); // db call ok

		// Check for database error
		if ( $wpdb->last_error ) {

			error_log( "Database error in getPurchasesForProduct: " . $wpdb->last_error );

		}

		return intval( $purchase_count );
	}



	function checkProductPurchases( $product_id, $amount, $days ) {

		$purchases = $this->getPurchasesForProduct( (int) $product_id, (int) $days );

		$result = ( $amount == 0 ) ? ( $purchases == 0 ) : ( $purchases >= $amount );


		return $result;
	}

	function checkProductPurchasesGrowth( $product_id, $amount, $days ) {


		// Get the purchases for the recent period
		$recentPurchases = $this->getPurchasesForProduct( (int) $product_id, (int) $days );


		// Get the purchases for the previous period
		$previousPurchases = $this->getPurchasesForProduct( (int) $product_id, (int) $days, (int) $days );



		// Calculate the growth percentage
		if ( $previousPurchases == 0 ) {
			// If there were no purchases in the previous period, avoid division by zero
			$growthPercentage = ( $recentPurchases > 0 ) ? 100 : 0;
		} else {
			$growthPercentage = ( ( $recentPurchases - $previousPurchases ) / $previousPurchases ) * 100;
		}


		// Compare the growth percentage to the amount
		if ( $amount >= 0 ) {
			// Check for at least the specified positive growth
			$result = ( $growthPercentage >= $amount );
		} else {
			// Check for at least the specified negative growth (decrease)
			$result = ( $growthPercentage <= $amount );
		}

		return $result;
	}

	function mark_as_featured( $product_id ) {

		$product_id = absint( $product_id );
		$product = wc_get_product( $product_id );

		if ( ! $product ) {

			return false;
		}

		// Set the product as featured using WooCommerce's method
		$product->set_featured( true );

		// Save the changes
		$product->save();

		// Re-fetch the product to verify
		$product = wc_get_product( $product_id );
		$is_featured = $product->is_featured();

		return $is_featured;
	}

	function unmark_as_featured( $product_id ) {

		// Fetch the WooCommerce product object by its ID
		$product = wc_get_product( $product_id );

		// If the product is not found, log the error and return false
		if ( ! $product ) {

			return false;
		}

		// Set the product as not featured using WooCommerce's method
		$product->set_featured( false );

		// Save the changes
		$product->save();

		// Re-fetch the product to verify
		$product = wc_get_product( $product_id );
		$is_not_featured = ! $product->is_featured();

		return $is_not_featured;
	}


	function findStatesByWorkflowId( $uniqueId ) {
		global $wpdb; // Access the global $wpdb object

		// Ensure that $uniqueId is safe for use in a database query
		$uniqueId = esc_sql( $uniqueId );

		// Execute the query and get all results
		$states = $wpdb->get_results( $wpdb->prepare(
			"SELECT states.*
         FROM {$wpdb->prefix}trendpilot_automation_engine_states AS states
         INNER JOIN {$wpdb->prefix}trendpilot_automation_engine_workflows AS workflows
         ON states.unique_id = workflows.unique_id
         WHERE workflows.unique_id = %s",
			$uniqueId
		) ); // db call ok

		// Check for errors in query execution or if no states are found
		if ( $wpdb->last_error ) {
			// Log the error

			error_log( "Error finding states by workflow ID" );

			return false; // Return false in case of an error
		}

		if ( empty( $states ) ) {
			return null; // Return null if no states are found
		}

		// Return the array of state objects
		return $states;
	}

	function findParentStateByWorkflowId( $uniqueId ) {
		global $wpdb; // Access the global $wpdb object

		// Ensure that $uniqueId is safe for use in a database query
		$uniqueId = esc_sql( $uniqueId );



		// Execute the query and get the result
		$state = $wpdb->get_row( $wpdb->prepare(
			"SELECT states.*
         FROM {$wpdb->prefix}trendpilot_automation_engine_states AS states
         INNER JOIN {$wpdb->prefix}trendpilot_automation_engine_workflows AS workflows
         ON states.unique_id = workflows.unique_id
         WHERE workflows.unique_id = %s AND states.is_child = 0",
			$uniqueId
		) ); // db call ok

		// Check for errors in query execution or if no state is found
		if ( $wpdb->last_error ) {
			// Log the error

			error_log( "Error finding parent state by workflow ID " );

			return false; // Return false in case of an error
		}

		if ( empty( $state ) ) {
			return null; // Return null if no parent state is found
		}

		// Return the state object
		return $state;
	}

	function on_workflow_create_update( $unique_id ) {

		$unique_id = sanitize_text_field( $unique_id );

		$states = $this->findStatesByWorkflowId( $unique_id );

		if ( $states ) {
			// If states are found, iterate through each state and continue the workflow
			foreach ( $states as $state ) {
				if ( $state->status == 'in-progress' ) {

					$this->continueWorkflow( $state );
				}
			}
		} else {

			$this->createState( $unique_id, [], null, 0 );


			$workflow = $this->findWorkflowByUniqueId( $unique_id );

			if ( ! $workflow || empty( $workflow->steps ) ) {

				return;
			}

			// Decode steps as an associative array
			$decodedData = json_decode( $workflow->steps, true );

			if ( json_last_error() !== JSON_ERROR_NONE || empty( $decodedData['steps'] ) ) {

				error_log( "Error in decoding workflow steps or steps are empty" );

				return;
			}

			// Access the steps from the decoded data
			$workflowSteps = $decodedData['steps'];

			if ( empty( $workflowSteps ) ) {

				error_log( "No steps found in the workflow" );

				return;
			}

			// Now process the first step as before
			$firstStep = $workflowSteps[0];

			// Use array syntax to access elements
			if ( isset( $firstStep['type'] ) && $firstStep['type'] == 'event' ) {
				$eventName = $firstStep['event']['name'] ?? null;
				$stepParams = $firstStep['event']['parameters'] ?? [];

			} else if ( isset( $firstStep['type'] ) && $firstStep['type'] == 'action' ) {
				$actionName = $firstStep['action']['name'] ?? null;
				$stepParams = $firstStep['action']['parameters'] ?? [];

			} else {

				return;
			}

			$stateParams = [];

			if ( $eventName ) {

				$functionToCall = $this->getCorrespondingFunction( $eventName );


				if ( $functionToCall !== null && function_exists( $functionToCall ) ) {
					$functionToCall( sanitize_text_field( $unique_id ), $this );

				} else {

					error_log( "No corresponding function found for event" );

				}

			} else if ( $actionName ) {

				$functionToCall = $this->getCorrespondingFunction( $actionName );


				if ( $functionToCall !== null && function_exists( $functionToCall ) ) {
					$functionToCall( $stepParams, $stateParams );

				} else {

					error_log( "No corresponding function found for action" );

				}
			}
		}
	}

	function getAllCoupons() {
		$args = array(
			'post_type' => 'shop_coupon',
			'posts_per_page' => -1
		);

		// Get coupon posts
		$coupon_posts = get_posts( $args );

		// Initialize an array to hold the coupon codes
		$coupon_codes = array();

		// Loop through the posts and extract coupon codes
		foreach ( $coupon_posts as $post ) {
			// Get the coupon code from the post title
			$coupon_code = $post->post_title; // Coupon code is typically stored in the post title
			array_push( $coupon_codes, sanitize_text_field( $coupon_code ) );
		}

		return $coupon_codes;
	}

	function checkProductCatViewed( $catId, $days, $amount ) {
		global $wpdb;

		// The table where category views are stored
		$table_name = $wpdb->prefix . 'trendpilot_automation_engine_page_views';

		// Calculate the start date for the query, including today
		$date = new \DateTime();
		$date->modify( '-' . intval( $days ) . ' days +1 day' ); // Adjusted to include today
		$start_date = $date->format( 'Y-m-d' );


		// Execute the query and get the result
		$result = $wpdb->get_row( $wpdb->prepare(
			"SELECT SUM(views) as total_views
			 FROM $table_name
			 WHERE category_id = %d AND 
				   viewed_date >= %s",
			$catId,
			$start_date
		) ); // db call ok

		// Check for database error
		if ( $wpdb->last_error ) {
			error_log( "Database error in checkProductCatViewed" );
			return false;
		}

		// Assign total views
		$total_views = 0;
		if ( $result ) {
			$total_views = intval( $result->total_views );
		}

		// Check if the total views match the condition
		if ( $amount == 0 ) {
			$conditionMet = $total_views == 0;
		} else {
			$conditionMet = $total_views >= $amount;
		}

		return $conditionMet;
	}


	function checkProductCatViewedGrowth( $catId, $perc_amount, $days ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'trendpilot_automation_engine_page_views';  // Adjust to your table name

		// Calculate the dates for the recent and previous periods
		$recent_end_date = wp_date( 'Y-m-d 23:59:59' );
		$recent_start_date = wp_date( 'Y-m-d 00:00:00', strtotime( "-$days days", strtotime( $recent_end_date ) ) + 1 );
		$previous_end_date = wp_date( 'Y-m-d 23:59:59', strtotime( $recent_start_date ) - 1 );
		$previous_start_date = wp_date( 'Y-m-d 00:00:00', strtotime( "-$days days", strtotime( $previous_end_date ) ) + 1 );

		// Execute the query
		$recent_views = $wpdb->get_var( $wpdb->prepare(
			"SELECT SUM(views) as total_views FROM $table_name WHERE category_id = %d AND viewed_date BETWEEN %s AND %s",
			(int) $catId,
			$recent_start_date,
			$recent_end_date
		) );// db call ok

		// Execute the query
		$previous_views = $wpdb->get_var( $wpdb->prepare(
			"SELECT SUM(views) as total_views FROM $table_name WHERE category_id = %d AND viewed_date BETWEEN %s AND %s",
			(int) $catId,
			$previous_start_date,
			$previous_end_date
		) ); // db call ok


		// Handle NULL results as 0 views
		$recent_views = $recent_views !== null ? intval( $recent_views ) : 0;
		$previous_views = $previous_views !== null ? intval( $previous_views ) : 0;

		if ( $wpdb->last_error ) {
			error_log( "Database error in checkProductCatViewedGrowth: " . $wpdb->last_error );
		}

		// Calculate growth percentage
		if ( $previous_views > 0 ) {
			$growth_percentage = ( ( $recent_views - $previous_views ) / $previous_views ) * 100;
		} else {
			$growth_percentage = $recent_views > 0 ? 100 : 0;
		}

		// Compare growth percentage to perc_amount
		if ( $perc_amount < 0 ) {
			return $growth_percentage <= $perc_amount;
		} else {
			return $growth_percentage >= $perc_amount;
		}
	}


	function getAllRecProducts() {
		global $wpdb;

		// The table where recommended products are stored
		$table_name = $wpdb->prefix . 'trendpilot_automation_engine_recommended_products';


		// Execute the query and get the result
		$results = $wpdb->get_col( "SELECT product_id FROM $table_name" ); // db call ok

		// Return the array of product IDs
		return $results;
	}

	function checkRecProdClicked( $productID, $days, $amount ) {
		global $wpdb;

		// The table where clicks are stored
		$table_name = $wpdb->prefix . 'trendpilot_automation_engine_recommended_loop_clicks';

		// Calculate the start date for the query, including today
		$date = new \DateTime();
		$date->modify( '-' . intval( $days ) . ' days +1 day' ); // Adjusted to include today
		$start_date = $date->format( 'Y-m-d' );

		// Execute the query and get the result
		$result = $wpdb->get_row( $wpdb->prepare(
			"SELECT SUM(clicks) as total_clicks
			 FROM $table_name
			 WHERE product_id = %d AND 
				   clicked_date >= %s",
			(int) $productID,
			$start_date
		) ); // db call ok
		$total_clicks = $result ? intval( $result->total_clicks ) : 0;

		// Check if the total clicks match the condition
		if ( $amount == 0 ) {
			return $total_clicks == 0;
		} else {
			return $total_clicks >= $amount;
		}
	}

	function checkUpsellClicks( $upsellDateChanged, $amount, $days ) {
		global $wpdb;

		// The table where upsell clicks are stored
		$table_name = $wpdb->prefix . 'trendpilot_automation_engine_click_data';

		// Calculate the start date for the query (current date minus $days), include today
		$end_date = new \DateTime();
		$end_date->modify( '-' . intval( $days ) . ' days +1 day' ); // Adjusted to include today
		$formatted_end_date = $end_date->format( 'Y-m-d' );

		// Check if $upsellDateChanged is more recent than $formatted_end_date
		if ( $upsellDateChanged < $formatted_end_date ) {
			$upsellDateChanged = $formatted_end_date;
		}

		// Execute the query and get the result
		$result = $wpdb->get_row( $wpdb->prepare(
			"SELECT SUM(upsell_clicks) as total_clicks
			 FROM $table_name
			 WHERE clicked_date >= %s AND
				   clicked_date <= CURDATE()",
			$upsellDateChanged
		) ); // db call ok
		$total_clicks = $result ? intval( $result->total_clicks ) : 0;

		if ( $amount == 0 ) {
			return $total_clicks == 0;
		} else {
			return $total_clicks >= $amount;
		}
	}


	function checkProductOlderThan( $productID, $days ) {
		// Access the WooCommerce product
		$product = wc_get_product( (int) $productID );
		if ( ! $product ) {
			// Return false if the product does not exist
			return false;
		}

		// Get the product creation date
		$productDate = new \DateTime( $product->get_date_created()->date( 'Y-m-d' ) );

		// Calculate the date threshold
		$dateThreshold = new \DateTime();
		$dateThreshold->modify( '-' . intval( $days ) . ' days' );

		// Compare the product creation date with the threshold
		return $productDate < $dateThreshold;
	}


	function checkSinceDate( $startDate, $days ) {
		// Convert the start date string to a DateTime object
		$startDate = new \DateTime( $startDate );

		// Calculate the date threshold by adding the specified number of days to the start date
		$thresholdDate = clone $startDate;
		$thresholdDate->modify( '+' . intval( $days ) . ' days' );

		// Get the current date
		$currentDate = new \DateTime();

		// Check if the current date is on or after the threshold date
		return $currentDate >= $thresholdDate;
	}

	function checkNewUsers( $amount, $days ) {
		global $wpdb;

		// The table where user data is stored
		$table_name = $wpdb->prefix . 'users';

		// Calculate the start date for the query (current date minus $days), include today
		$start_date = new \DateTime();
		$start_date->modify( '-' . intval( $days ) . ' days +1 day' ); // Adjusted to include today
		$formatted_start_date = $start_date->format( 'Y-m-d' );

		// Execute the query and get the result
		$newUserCount = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(ID) FROM $table_name WHERE user_registered >= %s",
			$formatted_start_date
		) ); // db call ok

		// If $amount is 0, return true if the new user count is exactly 0
		// For other values of $amount, return true if the new user count meets or exceeds $amount
		if ( $amount == 0 ) {
			return intval( $newUserCount ) === 0;
		} else {
			return intval( $newUserCount ) >= $amount;
		}
	}



	function checkTodayDate( $startDate ) {
		// Convert the start date string to a DateTime object and reset time to 00:00:00
		$startDate = new \DateTime( $startDate );
		$startDate->setTime( 0, 0, 0 );

		// Get the current date and reset time to 00:00:00
		$currentDate = new \DateTime();
		$currentDate->setTime( 0, 0, 0 );

		// Compare the dates
		return $startDate == $currentDate;
	}



	function checkCouponUsage( $couponCode, $amount, $days ) {

		$couponCode = sanitize_text_field( $couponCode );
		$amount = (int) $amount;
		$days = (int) $days;

		if ( ! $this->couponExists( $couponCode ) ) {
			return false;
		}

		// Calculate the date $days ago, including today
		$date = new \DateTime();
		$date->modify( "-$days days +1 day" ); // Adjusted to include today
		$date->setTime( 0, 0, 0 ); // Set time to the start of the day
		$formattedDate = $date->format( 'Y-m-d' );

		// Get all orders within the date range
		$wc_orders = wc_get_orders( array(
			'limit' => -1,
			'status' => array( 'wc-completed' ),
			'date_created' => '>=' . $formattedDate,
		) );

		$count = 0;
		foreach ( $wc_orders as $order ) {
			foreach ( $order->get_items( 'coupon' ) as $item_id => $item ) {
				if ( strtolower( $item->get_code() ) === strtolower( $couponCode ) ) {
					$count++;
					break; // Stop checking other items once the coupon is found
				}
			}
		}

		// Check for exact match or greater than based on $amount
		if ( $amount == 0 ) {
			return $count === 0;
		} else {
			return $count >= $amount;
		}
	}



	function couponExists( $couponCode ) {

		$couponCode = sanitize_text_field( $couponCode );

		$coupon = new \WC_Coupon( $couponCode );
		$exists = ! empty( $coupon->get_id() );


		return $exists;
	}

	function checkOverallPurchases( $amount, $days ) {
		global $wpdb;

		// The table where WooCommerce order data is stored
		$table_name = $wpdb->prefix . 'posts';

		// Calculate the start date for the query (current date minus $days), including today
		$start_date = new \DateTime();
		$start_date->modify( '-' . intval( $days ) . ' days +1 day' ); // Adjusted to include today
		$formatted_start_date = $start_date->format( 'Y-m-d H:i:s' );

		// Execute the query and get the result
		$purchaseCount = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(ID) FROM $table_name 
			 WHERE post_type = 'shop_order' 
			 AND post_status = 'wc-completed' 
			 AND post_date >= %s",
			$formatted_start_date
		) ); // db call ok

		// Check the number of purchases based on $amount
		if ( $amount == 0 ) {
			return intval( $purchaseCount ) === 0;
		} else {
			return intval( $purchaseCount ) >= $amount;
		}
	}

	function checkProductStock( $product_id, $amount, $above_below ) {
		// Get the WooCommerce product object
		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			// If the product doesn't exist, return false
			return false;
		}

		// Get the stock quantity for the product
		$stock_quantity = $product->get_stock_quantity();

		if ( $stock_quantity === null ) {
			// If stock is not managed or doesn't exist, return false
			return false;
		}

		// Check if the stock quantity is above or below the threshold
		if ( $above_below === 'above' ) {
			// Return true if the stock is above the threshold
			return $stock_quantity >= $amount;
		} elseif ( $above_below === 'below' ) {
			// Return true if the stock is below the threshold
			return $stock_quantity < $amount;
		}

		// If $above_below is not recognized, return false
		return false;
	}

	function getAllCategories() {
		$args = array(
			'taxonomy' => 'product_cat',
			'hide_empty' => false, // Set to true if you want to exclude categories that have no products
			'fields' => 'ids', // Retrieve only the IDs
		);

		$category_ids = get_terms( $args );

		// Check for a WP_Error object
		if ( is_wp_error( $category_ids ) ) {

			return array(); // Return an empty array in case of error
		}

		return $category_ids;
	}

}