<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Main function for handling the event where a product is viewed.
function tpapProductOlderThan( $unique_id, $workflowInstance ) {

	if ( ! $unique_id ) {

		$startWithWorkflows = $workflowInstance->findWorkflowsStartingWithEvent( 'product_older_than', false );

		if ( ! empty( $startWithWorkflows ) ) {

			foreach ( $startWithWorkflows as $workflow ) {

				$workflowInstance->createState( $workflow->unique_id, null, null, 0 );

			}
		}

		$inProgressStates = $workflowInstance->findInProgressStates();

	} else if ( $unique_id ) {

		if ( ! $workflowInstance->findStatesByWorkflowId( $unique_id ) ) {

			$workflow = $workflowInstance->findWorkflowByUniqueId( $unique_id );

			if ( ! empty( $workflow ) ) {

				$workflowSteps = json_decode( $workflow->steps );
				if ( json_last_error() !== JSON_ERROR_NONE ) {

					error_log( "Error decoding workflow steps" );

					return false;
				}

				$firstEventName = $workflowSteps->steps[0]->event->name;

				if ( $firstEventName == 'product_older_than' ) {

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

			error_log( "Error decoding workflow steps within inProgressStates progression" );

			continue;
		}
		$currentStep = $workflowSteps->steps[ $state->current_step ];
		if ( $currentStep->event->name !== 'product_older_than' )
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

		if ( $stepParameters->product_id !== null ) {
			$productOlderThan = $workflowInstance->checkProductOlderThan( $stepParameters->product_id, $stepParameters->days );
			if ( $productOlderThan ) {

				$params = [ 'product_id' => (int) $stepParameters->product_id ];
				$workflowInstance->logStep( $workflow->unique_id, $state->current_step, 'Success', $currentStep->event->name );
				$workflowInstance->updateStateStep( $state->id, $state->current_step + 1 );
				$workflowInstance->updateStateParams( $state, $params );
				$workflowInstance->continueWorkflow( $state );
			}
		} else if ( $stepParameters->product_id === null && isset( $stateParameters->product_id ) && $stateParameters->product_id !== null ) {
			$productOlderThan = $workflowInstance->checkProductOlderThan( $stateParameters->product_id, $stepParameters->days );
			if ( $productOlderThan ) {

				$params = [ 'product_id' => (int) $stateParameters->product_id ];
				$workflowInstance->logStep( $workflow->unique_id, $state->current_step, 'Success', $currentStep->event->name );
				$workflowInstance->updateStateStep( $state->id, $state->current_step + 1 );
				$workflowInstance->updateStateParams( $state, $params );
				$workflowInstance->continueWorkflow( $state );
			}
		}
	}
}


function tpapAnyProductOlderThan( $unique_id, $workflowInstance ) {

	$finalStates = [];

	if ( ! $unique_id ) {
		// Get all product IDs
		$allProducts = $workflowInstance->getAllProducts();

		// Find workflows that start with the event 'any_product_older_than'
		$workflows = $workflowInstance->findWorkflowsStartingWithEvent( 'any_product_older_than', true ); // getting ALL workflows that start with event, regardless of if a state exists already for that workflow (as a state could exist as a child for a different product)

		// Loop through each workflow
		foreach ( $workflows as $workflow ) {
			// Decode the JSON steps of the workflow
			$steps = json_decode( $workflow->steps );

			// Extract the 'days' and 'amount' parameters from the first step
			$days = (int) $steps[0]->event->parameters->days;
			$productId = (int) $steps[0]->event->parameters->product_id;

			// Initialize an empty array to store matching product IDs
			$matchingProducts = [];

			// Check each product to see if it meets the criteria
			foreach ( $allProducts as $productID ) {
				if ( $workflowInstance->checkProductOlderThan( $productId, $days ) ) { // returns true if the product being iterated is older than X days
					$matchingProducts[] = (int) $productID; // adds the product that IS older than x days to the array
				}
			}

			// Create child states for matching products
			foreach ( $matchingProducts as $matchingProduct ) {
				// Check if a child state already exists
				/// returns true if there is a child state for this workflow that has a 'product_id' value of $matchingProduct at step 0. if true is returned, there is an existing child state for this event+parameter combination, meaning there is already a branch in progress for this event+parameter combination
				$matchingChildState = $workflowInstance->findChildState( $workflow->unique_id, 'product_id', $matchingProduct, 0 );

				// If no existing child state, create a new one. 
				if ( ! $matchingChildState ) {
					$params = [ 'product_id' => $matchingProduct ];

					$finalStates[] = $workflowInstance->createChildState( $workflow->unique_id, $params, 0, $steps );
				}
			}
		}

		// Find all in-progress states
		$inProgressStates = $workflowInstance->findInProgressStates();

	} else if ( $unique_id ) {

		$allProducts = $workflowInstance->getAllProducts();
		$workflow = $workflowInstance->findWorkflowByUniqueId( $unique_id );
		$steps = json_decode( $workflow->steps );

		$firstEventName = $steps->steps[0]->event->name;

		// check if this workflow has the first step as this event (because the code below is initiating a state, if it is not the first step it will have been initiated elsewhere )
		if ( $firstEventName == 'any_product_older_than' ) {
			$days = (int) $steps->steps[0]->event->parameters->days;
			$productId = (int) $steps->steps[0]->event->parameters->product_id;

			$matchingProducts = [];

			// run the check on all of the products for this event's particular parameters
			foreach ( $allProducts as $productID ) {
				if ( $workflowInstance->checkProductOlderThan( $productId, $days ) ) {
					$matchingProducts[] = (int) $productID;
				}
			}

			// for each product that passed the test, see if there is a branch already for this workflow, with this product at its first step. If ther isn't create the branch, if there is, a branch is in progress so don't create the branch.
			foreach ( $matchingProducts as $matchingProduct ) {

				$matchingChildState = $workflowInstance->findChildState( $workflow->unique_id, 'product_id', $matchingProduct, 0 );

				if ( ! $matchingChildState ) {
					$params = [ 'product_id' => $matchingProduct ];

					$finalStates[] = $workflowInstance->createChildState( $workflow->unique_id, $params, 0, $steps );
				}
			}
		}

		$inProgressStates = $workflowInstance->findInProgressStates( $unique_id );

	}

	// Loop through each in-progress state
	foreach ( $inProgressStates as $state ) {

		// Get the workflow corresponding to the state
		$workflow = $workflowInstance->findWorkflowByUniqueId( $state->unique_id );
		if ( $workflow->status === 'inactive' ) {

			continue;
		}

		// Decode the JSON steps of the workflow
		$workflowSteps = json_decode( $workflow->steps );

		// Get the current step based on the state
		$currentStep = $workflowSteps->steps[ $state->current_step ];

		// Skip if the current step doesn't match the event name
		if ( $currentStep->event->name !== 'any_product_older_than' ) {
			continue;
		}

		if ( $state->current_step === count( $workflowSteps->steps ) - 1 && $currentStep->type === 'event' ) {
			$workflowInstance->logStep( $workflow->unique_id, $state->current_step, 'Failure. event is final step', $currentStep->event->name );
			continue;
		}

		// Extract the 'days' and 'product_id' parameters from the current step
		$days = (int) $currentStep->event->parameters->days;

		// Initialize an empty array to store product IDs older than x days
		$productsOlderThan = [];

		// Check each product to see if it meets the criteria
		foreach ( $allProducts as $productID ) {
			if ( $workflowInstance->checkProductOlderThan( $productID, $days ) ) { /// checkProductOlderThan() returns true if the product is older than x days, so if its true for this product, add it to the array.
				$productsOlderThan[] = $productID;
			}
		}

		// Check if the productsOlderThan array is empty
		if ( empty( $productsOlderThan ) ) {

			// Handle the case when no products are older than specified days, if necessary
			// For example, you might continue to the next iteration, return from the function, etc.
			continue;
		}

		// Create child states for products that passed the test
		foreach ( $productsOlderThan as $matchingProduct ) {

			// Check if a child state already exists
			$matchingChildState = $workflowInstance->findChildState( $workflow->unique_id, 'product_id', $matchingProduct, $state->current_step ); // returns true if a branch does exist for this event+param combo.

			// If no existing child state, create a new one
			if ( ! $matchingChildState ) {

				$params = [ 'product_id' => $matchingProduct ];

				$finalStates[] = $workflowInstance->createChildState( $workflow->unique_id, $params, $state->current_step, $workflowSteps ); // create a new branch for this event+param combo. Set the current step to the same current step as the parent state being iterated (the 'current step' gets iterated next). 

			}
		}
	}

	foreach ( $finalStates as $finalState ) {
		$workflowInstance->logStep( $finalState->unique_id, $finalState->current_step, 'Success', 'any_product_older_than', $finalState->user_id );
		$workflowInstance->updateStateStep( $finalState->id, $finalState->current_step + 1 );
		$workflowInstance->continueWorkflow( $finalState );
	}
}

