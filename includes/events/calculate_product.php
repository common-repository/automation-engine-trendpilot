<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//calculateDatapoint event
function tpapCalculateProduct( $unique_id, $workflowInstance ) {

	if ( ! $unique_id ) {

		$startWithWorkflows = $workflowInstance->findWorkflowsStartingWithEvent( 'calculate_product', false );

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

				if ( $firstEventName == 'calculate_product' ) {

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

		if ( $currentStep->event->name !== 'calculate_product' )
			continue;

		if ( $state->current_step === count( $workflowSteps->steps ) - 1 && $currentStep->type === 'event' ) {
			$workflowInstance->logStep( $workflow->unique_id, $state->current_step, 'Failure. event is final step', $currentStep->event->name );
			continue;
		}

		$stepParameters = $currentStep->event->parameters;
		$stateParameters = json_decode( $state->parameters, true );

		// Check for JSON decoding errors and handle invalid data
		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $stateParameters ) ) {
			$stateParameters = [];
		}

		// Convert $stateParameters to an object
		$stateParameters = (object) $stateParameters;

		// Create variables for product_filters
		$productFilters = isset( $stepParameters->product_filters ) ? $stepParameters->product_filters : new stdClass();

		// Create state parameter variables if they exist
		if ( isset( $stateParameters->product_id ) ) {
			$stateProduct = $stateParameters->product_id;
		}
		if ( isset( $stateParameters->cat_id ) ) {
			$stateCategory = $stateParameters->cat_id;
		}

		// Check if days is set in step parameters
		if ( isset( $stepParameters->product_filters ) && $stepParameters->product_filters !== null ) {
			$product_filters = $stepParameters->product_filters;
		}

		if ( $product_filters !== null ) {
			global $wpdb;

			// Initialize the query arguments
			$args = [ 
				'post_type' => 'product',
				'post_status' => 'publish',
				'posts_per_page' => -1, // Retrieve all products initially
			];

			// Apply $stateCategory only if $productFilters->in_category exists and is empty (null or "")
			if ( isset( $stateCategory ) && ! empty( $stateCategory ) && array_key_exists( 'in_category', (array) $productFilters ) && empty( $productFilters->in_category ) ) {
				$productFilters->in_category = $stateCategory;
			}

			// Handle category filter using the (potentially updated) $productFilters->in_category
			if ( isset( $productFilters->in_category ) && ! empty( $productFilters->in_category ) ) {
				$args['tax_query'][] = [ 
					'taxonomy' => 'product_cat',
					'field' => 'id',
					'terms' => $productFilters->in_category,
				];
			}

			// Handle tag filter
			if ( isset( $productFilters->with_tag ) && ! empty( $productFilters->with_tag ) ) {
				$args['tax_query'][] = [ 
					'taxonomy' => 'product_tag',
					'field' => 'slug',
					'terms' => $productFilters->with_tag,
				];
			}

			// Handle featured filter
			if ( isset( $productFilters->featured ) && $productFilters->featured == '1' ) {
				$args['tax_query'][] = [ 
					'taxonomy' => 'product_visibility',
					'field' => 'name',
					'terms' => 'featured',
					'operator' => 'IN',
				];
			}

			// Handle on-sale filter
			if ( isset( $productFilters->on_sale ) && $productFilters->on_sale == '1' ) {
				$args['meta_query'][] = [ 
					'key' => '_sale_price',
					'value' => 0,
					'compare' => '>',
					'type' => 'NUMERIC'
				];
			}

			// Query products
			$query = new WP_Query( $args );
			$products = $query->posts;

			// Log if no products found
			if ( empty( $products ) ) {
				return false;
			}

			// After querying products and logging the query arguments

			$daysParam = (int) $stepParameters->days;

			// Handle sortby
			if ( isset( $productFilters->sortby ) ) {
				if ( $productFilters->sortby == 'most_viewed' ) {
					// Get the product IDs
					$product_ids = array_map( function ($product) {
						return (int) $product->ID;
					}, $products );

					// Accumulate views from the page views table with daysParam
					// Prepare the query arguments securely
					$placeholders = implode( ',', array_fill( 0, count( $product_ids ), '%d' ) );

					// no user-inputted data being used for variables inside the prepare statement
					$most_viewed_product = $wpdb->get_row( $wpdb->prepare(
						"
						SELECT product_id, SUM(views) as total_views
						FROM {$wpdb->prefix}trendpilot_automation_engine_page_views
						WHERE product_id IN ($placeholders)
						AND viewed_date >= DATE_SUB(NOW(), INTERVAL %d DAY)
						GROUP BY product_id
						ORDER BY total_views DESC
						LIMIT 1;
						",
						array_merge( $product_ids, [ $daysParam ] )
					), ARRAY_A ); // db call ok


					if ( ! empty( $most_viewed_product ) ) {
						$product_id = $most_viewed_product['product_id'];
					} else {
						return false;
					}
				} elseif ( $productFilters->sortby == 'most_recent' ) {
					$filtered_products = array_filter( $products, function ($product) use ($daysParam) {
						return strtotime( $product->post_date ) >= strtotime( "-{$daysParam} days" );
					} );

					if ( ! empty( $filtered_products ) ) {
						usort( $filtered_products, function ($a, $b) {
							return strtotime( $b->post_date ) - strtotime( $a->post_date ); // Descending order
						} );
						$product_id = (int) $filtered_products[0]->ID;
					} else {
						return false;
					}
				} elseif ( $productFilters->sortby == 'oldest' ) {
					// Filter products within the last $daysParam days, including the exact day that is $daysParam days ago
					$filtered_products = array_filter( $products, function ($product) use ($daysParam) {
						return strtotime( $product->post_date ) >= strtotime( "-{$daysParam} days" . " 00:00:00" );
					} );

					if ( ! empty( $filtered_products ) ) {
						// Sort products by post date in ascending order (oldest first)
						usort( $filtered_products, function ($a, $b) {
							return strtotime( $a->post_date ) - strtotime( $b->post_date ); // Ascending order
						} );

						// Select the first product in the sorted array (the oldest within the filtered set)
						$product_id = (int) $filtered_products[0]->ID;
					} else {
						return false;
					}
				} elseif ( $productFilters->sortby == 'least_viewed' ) {
					// Get the product IDs
					$product_ids = array_map( function ($product) {
						return (int) $product->ID;
					}, $products );

					// Accumulate views from the page views table with daysParam
					// Prepare the query arguments securely
					$placeholders = implode( ',', array_fill( 0, count( $product_ids ), '%d' ) );

					// no user-inputted data being used for variables inside the prepare statement
					$least_viewed_product = $wpdb->get_row( $wpdb->prepare(
						"
						SELECT product_id, SUM(views) as total_views
						FROM {$wpdb->prefix}trendpilot_automation_engine_page_views
						WHERE product_id IN ($placeholders)
						AND viewed_date >= DATE_SUB(NOW(), INTERVAL %d DAY)
						GROUP BY product_id
						ORDER BY total_views ASC
						LIMIT 1;
						",
						array_merge( $product_ids, [ $daysParam ] )
					), ARRAY_A ); // db call ok


					if ( ! empty( $least_viewed_product ) ) {
						$product_id = $least_viewed_product['product_id'];
					} else {
						return false;
					}
				} elseif ( $productFilters->sortby == 'most_purchased' ) {
					// Get the product IDs
					$product_ids = array_map( function ($product) {
						return (int) $product->ID;
					}, $products );

					// Accumulate purchases from the orders table with daysParam
					// Prepare the query arguments securely
					$placeholders = implode( ',', array_fill( 0, count( $product_ids ), '%d' ) );

					// no user-inputted data being used for variables inside the prepare statement
					$most_purchased_product = $wpdb->get_row( $wpdb->prepare(
						"
						SELECT order_itemmeta_product.meta_value as product_id, SUM(order_itemmeta_qty.meta_value) as purchase_count
						FROM {$wpdb->prefix}woocommerce_order_items AS order_items
						JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_itemmeta_product ON order_items.order_item_id = order_itemmeta_product.order_item_id
						JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_itemmeta_qty ON order_items.order_item_id = order_itemmeta_qty.order_item_id
						JOIN {$wpdb->prefix}posts AS posts ON order_items.order_id = posts.ID
						WHERE order_itemmeta_product.meta_key = '_product_id'
						AND order_itemmeta_product.meta_value IN ($placeholders)
						AND order_itemmeta_qty.meta_key = '_qty'
						AND posts.post_date >= DATE_SUB(NOW(), INTERVAL %d DAY)
						AND posts.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
						GROUP BY order_itemmeta_product.meta_value
						ORDER BY purchase_count DESC
						LIMIT 1;
						",
						array_merge( $product_ids, [ $daysParam ] )
					), ARRAY_A ); // db call ok


					if ( ! empty( $most_purchased_product ) ) {
						$product_id = $most_purchased_product['product_id'];
					} else {
						return false;
					}

				} elseif ( $productFilters->sortby == 'highest_revenue' ) {
					// Get the product IDs
					$product_ids = array_map( function ($product) {
						return (int) $product->ID;
					}, $products );

					// Accumulate revenue from the orders table with daysParam
					// Prepare the query arguments securely
					$placeholders = implode( ',', array_fill( 0, count( $product_ids ), '%d' ) );

					// no user-inputted data being used for variables inside the prepare statement
					$highest_revenue_product = $wpdb->get_row( $wpdb->prepare(
						"
						SELECT order_itemmeta_product.meta_value as product_id, SUM(order_itemmeta_total.meta_value) as total_revenue
						FROM {$wpdb->prefix}woocommerce_order_items AS order_items
						JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_itemmeta_product ON order_items.order_item_id = order_itemmeta_product.order_item_id
						JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_itemmeta_total ON order_items.order_item_id = order_itemmeta_total.order_item_id
						JOIN {$wpdb->prefix}posts AS posts ON order_items.order_id = posts.ID
						WHERE order_itemmeta_product.meta_key = '_product_id'
						AND order_itemmeta_product.meta_value IN ($placeholders)
						AND order_itemmeta_total.meta_key = '_line_total'
						AND posts.post_date >= DATE_SUB(NOW(), INTERVAL %d DAY)
						AND posts.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
						GROUP BY order_itemmeta_product.meta_value
						ORDER BY total_revenue DESC
						LIMIT 1;
						",
						array_merge( $product_ids, [ $daysParam ] )
					), ARRAY_A ); // db call ok

					if ( ! empty( $highest_revenue_product ) ) {
						$product_id = $highest_revenue_product['product_id'];
					} else {
						return false;
					}
				} elseif ( $productFilters->sortby == 'random' ) {
					// Select a random product from the filtered products
					$product_id = (int) $products[ array_rand( $products ) ]->ID;
				}
			}

			// Set the result with the product_id
			$result = [ 'product_id' => $product_id ];

			if ( $result !== null ) {
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