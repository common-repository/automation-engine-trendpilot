<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function tpapProductPurchased( $unique_id, $workflowInstance ) {

	if ( ! $unique_id ) {
		$startWithWorkflows = $workflowInstance->findWorkflowsStartingWithEvent( 'product_purchased', false );

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

				if ( $firstEventName == 'product_purchased' ) {
					$workflowInstance->createState( $workflow->unique_id, null, null, 0 );
				}
			}
		}

		$inProgressStates = $workflowInstance->findInProgressStates( $unique_id );
	}

	// Exit if no in-progress states found.
	if ( empty( $inProgressStates ) )
		return;

	// Step 2: Loop through each 'in-progress' state.
	foreach ( $inProgressStates as $state ) {
		// Step 3: Get the current, relevant JSON step for the state.
		$workflow = $workflowInstance->findWorkflowByUniqueId( $state->unique_id );
		$workflowSteps = json_decode( $workflow->steps );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			error_log( "Error decoding workflow steps within inProgressStates progression" );
			continue;
		}

		$currentStep = $workflowSteps->steps[ $state->current_step ];

		// Step 4: Check if this step's event name is 'product_purchased'.
		if ( $currentStep->event->name !== 'product_purchased' )
			continue;

		if ( $state->current_step === count( $workflowSteps->steps ) - 1 && $currentStep->type === 'event' ) {
			$workflowInstance->logStep( $workflow->unique_id, $state->current_step, 'Failure. event is final step', $currentStep->event->name );
			continue;
		}

		// Step 5: Extract parameters from both the JSON step and the state.
		$stepParameters = $currentStep->event->parameters;
		$stateParameters = json_decode( $state->parameters, true );

		// Check for JSON decoding errors and handle invalid data
		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $stateParameters ) ) {
			$stateParameters = [];
		}

		// Convert $stateParameters to an object
		$stateParameters = (object) $stateParameters;

		// Step 6: Determine the amount based on perc_amount.
		if ( isset( $stepParameters->perc_amount ) ) {
			$amount = $stepParameters->perc_amount;
		} else {
			return false;
		}

		// Step 7: Test the event accordingly, considering 'null' values.
		if ( $stepParameters->product_id !== null ) {
			if ( $stepParameters->amount_type === 'percentage' ) {
				$productPurchased = $workflowInstance->checkProductPurchasesGrowth( $stepParameters->product_id, $amount, $stepParameters->days );
			} else {
				$productPurchased = $workflowInstance->checkProductPurchases( $stepParameters->product_id, $amount, $stepParameters->days );
			}

			if ( $productPurchased ) { // if the product WAS purchased X times in X days, continue the workflow.
				$productID = (int) $stepParameters->product_id; // use the productID from the WORKFLOW table.
				$params = [ 'product_id' => $productID ];

				$workflowInstance->logStep( $workflow->unique_id, $state->current_step, 'Success', $currentStep->event->name );

				$workflowInstance->updateStateStep( $state->id, $state->current_step + 1 );

				$workflowInstance->updateStateParams( $state, $params );
				$workflowInstance->continueWorkflow( $state );
			}
		} else if ( $stepParameters->product_id === null && isset( $stateParameters->product_id ) && $stateParameters->product_id !== null ) {
			if ( $stepParameters->amount_type === 'percentage' ) {
				$productPurchased = $workflowInstance->checkProductPurchasesGrowth( $stateParameters->product_id, $amount, $stepParameters->days );
			} else {
				$productPurchased = $workflowInstance->checkProductPurchases( $stateParameters->product_id, $amount, $stepParameters->days );
			}

			if ( $productPurchased ) { // if the product WAS purchased X times in X days, continue the workflow.
				$productID = (int) $stateParameters->product_id; // use the productID from the STATES table.
				$params = [ 'product_id' => $productID ];

				$workflowInstance->logStep( $workflow->unique_id, $state->current_step, 'Success', $currentStep->event->name );

				$workflowInstance->updateStateStep( $state->id, $state->current_step + 1 );

				$workflowInstance->updateStateParams( $state, $params );
				$workflowInstance->continueWorkflow( $state );
			}
		}
	}
}


function tpapAnyProductPurchased( $unique_id, $workflowInstance ) {

	$finalStates = [];

	if ( ! $unique_id ) {
		$allProducts = $workflowInstance->getAllProducts();
		$workflows = $workflowInstance->findWorkflowsStartingWithEvent( 'any_product_purchased', true );
		$inProgressStates = $workflowInstance->findInProgressStates();
	} else if ( $unique_id ) {
		$allProducts = $workflowInstance->getAllProducts();
		$inProgressStates = $workflowInstance->findInProgressStates( $unique_id );
	}

	$countingStateProgressions = 1;
	$countStates = count( $inProgressStates );

	foreach ( $inProgressStates as $state ) {
		$workflow = $workflowInstance->findWorkflowByUniqueId( $state->unique_id );
		$workflowSteps = json_decode( $workflow->steps );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			error_log( "Error decoding workflow steps within inProgressStates progression" );
			continue;
		}
		$currentStep = $workflowSteps->steps[ $state->current_step ];

		if ( $currentStep->event->name !== 'any_product_purchased' ) {
			continue;
		}

		if ( $state->current_step === count( $workflowSteps->steps ) - 1 && $currentStep->type === 'event' ) {
			$workflowInstance->logStep( $workflow->unique_id, $state->current_step, 'Failure. event is final step', $currentStep->event->name );
			continue;
		}

		$days = (int) $currentStep->event->parameters->days;
		$amountType = isset( $currentStep->event->parameters->amount_type ) ? $currentStep->event->parameters->amount_type : 'specific';

		if ( isset( $currentStep->event->parameters->perc_amount ) ) {
			$amount = (int) $currentStep->event->parameters->perc_amount;
		} else {
			continue; // Skip this state if perc_amount is not set
		}

		$productsNotViewed = [];

		foreach ( $allProducts as $productID ) {
			if ( $amountType === 'percentage' ) {
				if ( $workflowInstance->checkProductPurchasesGrowth( $productID, $amount, $days ) ) {
					$productsNotViewed[] = (int) $productID;
				}
			} else {
				if ( $workflowInstance->checkProductPurchases( $productID, $amount, $days ) ) {
					$productsNotViewed[] = (int) $productID;
				}
			}
		}

		foreach ( $productsNotViewed as $matchingProduct ) {
			$matchingChildState = $workflowInstance->findChildState( $workflow->unique_id, 'product_id', $matchingProduct, $state->current_step ); // identifies whether a branch has happened yet

			if ( ! $matchingChildState ) {
				$params = [ 'product_id' => $matchingProduct ];
				$finalStates[] = $workflowInstance->createChildState( $workflow->unique_id, $params, $state->current_step, $workflowSteps );
			}
		}

		$countingStateProgressions++;
	}

	// now we have an array of child states that need to be advanced, so advance them
	foreach ( $finalStates as $finalState ) {
		$workflowInstance->logStep( $finalState->unique_id, $finalState->current_step, 'Success', 'any_product_purchased', $finalState->user_id );
		$workflowInstance->updateStateStep( $finalState->id, $finalState->current_step + 1 );
		$workflowInstance->continueWorkflow( $finalState );
	}
}
