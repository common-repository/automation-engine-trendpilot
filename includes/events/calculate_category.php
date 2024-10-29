<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


//calculateDatapoint event
function tpapCalculateCategory( $unique_id, $workflowInstance ) {

	if ( ! $unique_id ) {

		$startWithWorkflows = $workflowInstance->findWorkflowsStartingWithEvent( 'calculate_category', false );

		if ( ! empty( $startWithWorkflows ) ) {

			foreach ( $startWithWorkflows as $workflow ) {

				$workflowInstance->createState( $workflow->unique_id, null, null, 0 );

			}
		}

		$inProgressStates = $workflowInstance->findInProgressStates();

	} else if ( $unique_id ) {

		//3, THIRD UPDATE FOR ALL FUNCTIONS: make the below function plural. It now find multiple states
		if ( ! $workflowInstance->findStatesByWorkflowId( $unique_id ) ) {

			$workflow = $workflowInstance->findWorkflowByUniqueId( $unique_id );

			if ( ! empty( $workflow ) ) {

				$workflowSteps = json_decode( $workflow->steps );
				if ( json_last_error() !== JSON_ERROR_NONE ) {

					return false;
				}

				$firstEventName = $workflowSteps[0]->event->name;

				if ( $firstEventName == 'calculate_category' ) {

					$workflowInstance->createState( $workflow->unique_id, null, null, 0 );

				}
			}
		}

		$inProgressStates = $workflowInstance->findInProgressStates( $unique_id );

	}

	if ( empty( $inProgressStates ) )
		return;

	foreach ( $inProgressStates as $state ) {



		$workflow = $workflowInstance->findWorkflowByUniqueId( $state->unique_id );
		if ( $workflow->status === 'inactive' ) {

			continue;
		}

		$workflowSteps = json_decode( $workflow->steps );
		if ( json_last_error() !== JSON_ERROR_NONE ) {

			continue;
		}

		$currentStep = $workflowSteps->steps[ $state->current_step ];

		if ( $currentStep->event->name !== 'calculate_category' )
			continue;

		if ( $state->current_step === count( $workflowSteps->steps ) - 1 && $currentStep->type === 'event' ) {
			$workflowInstance->logStep( $workflow->unique_id, $state->current_step, 'Failure. event is final step', $currentStep->event->name );
			continue;
		}

		$stepParameters = $currentStep->event->parameters;
		$stateParameters = json_decode( $state->parameters, true );

		// Check for JSON errors and handle invalid data
		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $stateParameters ) ) {
			$stateParameters = [];
		}

		// Convert $stateParameters to an object if it's an array
		$stateParameters = (object) $stateParameters;


		// Check if days is set in step parameters
		if ( isset( $stepParameters->days ) && $stepParameters->days !== null ) {
			$days = $stepParameters->days;
		}

		// Check if days is set in step parameters
		if ( isset( $stepParameters->datapoint ) && $stepParameters->datapoint !== null ) {
			$datapoint = $stepParameters->datapoint;
		}

		if ( $datapoint !== null ) {

			// Initialize result to null
			$result = null;

			// Perform calculations based on the datapoint
			switch ( $datapoint ) {
				case 'highest_revenue_product':
					global $wpdb;

					// Prepare the start and end dates
					$endDate = wp_date( 'Y-m-d' );
					$startDate = wp_date( 'Y-m-d', strtotime( "-{$days} days +1 day" ) );
					$startDateTime = $startDate . ' 00:00:00';
					$endDateTime = $endDate . ' 23:59:59';

					$result = $wpdb->get_row( $wpdb->prepare( "
					SELECT woim.meta_value as product_id, SUM(woim2.meta_value) as total_revenue
					FROM {$wpdb->prefix}woocommerce_order_itemmeta as woim
					INNER JOIN {$wpdb->prefix}woocommerce_order_items as woi ON woim.order_item_id = woi.order_item_id
					INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta as woim2 ON woim.order_item_id = woim2.order_item_id
					INNER JOIN {$wpdb->prefix}posts as wp ON woi.order_id = wp.ID
					WHERE woim.meta_key = '_product_id'
					AND woim2.meta_key = '_line_total'
					AND wp.post_type = 'shop_order'
					AND wp.post_status = 'wc-completed'
					AND wp.ID IN (
						SELECT post_id
						FROM {$wpdb->prefix}postmeta
						WHERE meta_key = '_completed_date'
						AND meta_value BETWEEN %s AND %s
					)
					GROUP BY woim.meta_value
					ORDER BY total_revenue DESC
					LIMIT 1;
				", $startDateTime, $endDateTime ), ARRAY_A ); // db call ok

					break;


				case 'lowest_revenue_product':
					global $wpdb;

					// Prepare the start and end dates
					$endDate = wp_date( 'Y-m-d' );
					$startDate = wp_date( 'Y-m-d', strtotime( "-{$days} days +1 day" ) );
					$startDateTime = $startDate . ' 00:00:00';
					$endDateTime = $endDate . ' 23:59:59';

					// Query to fetch product ID with the lowest revenue based on completed date
					$result = $wpdb->get_row( $wpdb->prepare( "
							SELECT woim.meta_value as product_id, SUM(woim2.meta_value) as total_revenue
							FROM {$wpdb->prefix}woocommerce_order_itemmeta as woim
							INNER JOIN {$wpdb->prefix}woocommerce_order_items as woi ON woim.order_item_id = woi.order_item_id
							INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta as woim2 ON woim.order_item_id = woim2.order_item_id
							INNER JOIN {$wpdb->prefix}posts as wp ON woi.order_id = wp.ID
							WHERE woim.meta_key = '_product_id'
							AND woim2.meta_key = '_line_total'
							AND wp.post_type = 'shop_order'
							AND wp.post_status = 'wc-completed'
							AND wp.ID IN (
								SELECT post_id
								FROM {$wpdb->prefix}postmeta
								WHERE meta_key = '_completed_date'
								AND meta_value BETWEEN %s AND %s
							)
							GROUP BY woim.meta_value
							HAVING SUM(woim2.meta_value) > 0
							ORDER BY total_revenue ASC, woim.meta_value ASC
							LIMIT 1;
						", $startDateTime, $endDateTime ), ARRAY_A ); // db call ok

					break;


				case 'most_viewed_category':
					global $wpdb;

					// Prepare the start and end dates
					$endDate = wp_date( 'Y-m-d' );
					$startDate = wp_date( 'Y-m-d', strtotime( "-{$days} days +1 day" ) );

					// Query to find the most viewed category
					$mostViewedCategory = $wpdb->get_row( $wpdb->prepare( "
							SELECT category_id, SUM(views) as total_views
							FROM {$wpdb->prefix}trendpilot_automation_engine_page_views
							WHERE viewed_date BETWEEN %s AND %s
							AND category_id IS NOT NULL
							GROUP BY category_id
							ORDER BY total_views DESC
							LIMIT 1;
						", $startDate, $endDate ), ARRAY_A ); // db call ok

					if ( ! $mostViewedCategory ) {
						error_log( "no data found for most_viewed_category" );
					} else {
						$result = [ 'cat_id' => $mostViewedCategory['category_id'] ];
					}
					break;


				case 'least_viewed_category':
					global $wpdb;

					// Prepare the start and end dates
					$endDate = wp_date( 'Y-m-d' );
					$startDate = wp_date( 'Y-m-d', strtotime( "-{$days} days +1 day" ) );

					// Query to find the least viewed category
					$leastViewedCategory = $wpdb->get_row( $wpdb->prepare( "
							SELECT category_id, SUM(views) as total_views
							FROM {$wpdb->prefix}trendpilot_automation_engine_page_views
							WHERE viewed_date BETWEEN %s AND %s
							AND category_id IS NOT NULL
							GROUP BY category_id
							ORDER BY total_views ASC
							LIMIT 1;
						", $startDate, $endDate ), ARRAY_A ); // db call ok

					if ( ! $leastViewedCategory ) {
						error_log( "no data found for least_viewed_category" );
					} else {
						$result = [ 'cat_id' => $leastViewedCategory['category_id'] ];
					}
					break;


				case 'most_recent_product':
					global $wpdb;

					// Prepare the start and end dates
					$endDate = wp_date( 'Y-m-d' );
					$startDate = wp_date( 'Y-m-d', strtotime( "-{$days} days +1 day" ) );
					$startDateTime = $startDate . ' 00:00:00';
					$endDateTime = $endDate . ' 23:59:59';

					// Query to fetch the most recent product added within the last $days days
					$mostRecentProduct = $wpdb->get_row( $wpdb->prepare( "
							SELECT ID as product_id, post_date
							FROM {$wpdb->prefix}posts
							WHERE post_type = 'product'
							AND post_status = 'publish'
							AND post_date BETWEEN %s AND %s
							ORDER BY post_date DESC
							LIMIT 1;
						", $startDateTime, $endDateTime ), ARRAY_A ); // db call ok

					if ( ! $mostRecentProduct ) {
						error_log( "No recent products found in the last {$days} days. startDate: {$startDate}, endDate: {$endDate}" );
					} else {
						$result = [ 'product_id' => $mostRecentProduct['product_id'] ];
					}
					break;


				case 'oldest_product':
					global $wpdb;

					// Prepare the start and end dates
					$endDate = wp_date( 'Y-m-d' );
					$startDate = wp_date( 'Y-m-d', strtotime( "-{$days} days +1 day" ) );

					// Query to fetch the oldest product added within the last $days days, excluding 'trash' status
					$oldestProduct = $wpdb->get_row( $wpdb->prepare( "
							SELECT ID as product_id, post_date
							FROM {$wpdb->prefix}posts
							WHERE post_type = 'product'
							AND post_status != 'trash'
							AND post_date BETWEEN %s AND %s
							ORDER BY post_date ASC
							LIMIT 1;
						", $startDate, $endDate ), ARRAY_A ); // db call ok

					if ( ! $oldestProduct ) {
						error_log( "No products found in the last X days" );
					} else {
						$result = [ 'product_id' => $oldestProduct['product_id'] ];
					}
					break;


				case 'most_viewed_product':
					global $wpdb;

					// Prepare the start and end dates
					$endDate = wp_date( 'Y-m-d' );
					$startDate = wp_date( 'Y-m-d', strtotime( "-{$days} days +1 day" ) );

					// Query to find the product with the most views
					$mostViewedProduct = $wpdb->get_row( $wpdb->prepare( "
							SELECT product_id, SUM(views) as total_views
							FROM {$wpdb->prefix}trendpilot_automation_engine_page_views
							WHERE viewed_date BETWEEN %s AND %s
							AND product_id IS NOT NULL
							GROUP BY product_id
							ORDER BY total_views DESC
							LIMIT 1;
						", $startDate, $endDate ), ARRAY_A ); // db call ok

					if ( ! $mostViewedProduct ) {
						error_log( "No data found for most_viewed_product" );
					} else {
						$result = [ 'product_id' => $mostViewedProduct['product_id'] ];
					}
					break;


				case 'least_viewed_product':
					global $wpdb;

					// Prepare the start and end dates
					$endDate = wp_date( 'Y-m-d' );
					$startDate = wp_date( 'Y-m-d', strtotime( "-{$days} days +1 day" ) );

					// Query to find the product with the least views
					$leastViewedProduct = $wpdb->get_row( $wpdb->prepare( "
							SELECT product_id, SUM(views) as total_views
							FROM {$wpdb->prefix}trendpilot_automation_engine_page_views
							WHERE viewed_date BETWEEN %s AND %s
							AND product_id IS NOT NULL
							GROUP BY product_id
							HAVING SUM(views) > 0
							ORDER BY total_views ASC
							LIMIT 1;
						", $startDate, $endDate ), ARRAY_A ); // db call ok

					if ( ! $leastViewedProduct ) {
						error_log( "No data found for least_viewed_product" );
					} else {
						$result = [ 'product_id' => $leastViewedProduct['product_id'] ];
					}
					break;


				// ... Add more cases for each datapoint

				default:
					return false;
			}


			if ( $result !== null ) { // will be true if the product has passed the check

				// update the state's params with the product just assessed.
				//$productID = (int) $stepParameters->product_id;
				$params = $result;

				$workflowInstance->logStep( $workflow->unique_id, $state->current_step, 'Success', $currentStep->event->name );

				$workflowInstance->updateStateStep( $state->id, $state->current_step + 1 );

				$workflowInstance->updateStateParams( $state, $params );
				$workflowInstance->continueWorkflow( $state );

			} else {
				return false;
			}

		}
	}
}