<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function tpapProductStock( $unique_id, $workflowInstance ) {

	if ( ! $unique_id ) {

		$startWithWorkflows = $workflowInstance->findWorkflowsStartingWithEvent( 'product_stock', false );

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

				$firstEventName = $workflowSteps[0]->event->name;

				if ( $firstEventName == 'product_stock' ) {
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

		if ( $currentStep->event->name !== 'product_stock' )
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

		// Set amount & above_below variable
		if ( isset( $stepParameters->amount ) ) {
			$amount = (int) $stepParameters->amount;
		} else {
			return false;
		}
		if ( isset( $stepParameters->above_below ) ) {
			$above_below = sanitize_text_field( $stepParameters->above_below );
		} else {
			return false;
		}

		if ( $stepParameters->product_id !== null ) { // if the product_id to evaluate is a specific product...

			// will return true if the stock is above or below the amount
			$productStock = $workflowInstance->checkProductStock( $stepParameters->product_id, $amount, $above_below );

			if ( $productStock ) { // will be true if the product has passed the check

				// update the state's params with the product just assessed.
				$productID = (int) $stepParameters->product_id;
				$params = [ 'product_id' => $productID ];

				$workflowInstance->logStep( $workflow->unique_id, $state->current_step, 'Success', $currentStep->event->name );

				$workflowInstance->updateStateStep( $state->id, $state->current_step + 1 );

				$workflowInstance->updateStateParams( $state, $params );
				$workflowInstance->continueWorkflow( $state );
			}
		} else if ( $stepParameters->product_id === null && isset( $stateParameters->product_id ) && $stateParameters->product_id !== null ) { /// if using 'product from previous step'

			// will return true if the stock is above or below the amount
			$productStock = $workflowInstance->checkProductStock( $stateParameters->product_id, $amount, $above_below );

			if ( $productStock ) {
				$productID = (int) $stateParameters->product_id;
				$params = [ 'product_id' => $productID ];

				$workflowInstance->logStep( $workflow->unique_id, $state->current_step, 'Success', $currentStep->event->name );

				$workflowInstance->updateStateStep( $state->id, $state->current_step + 1 );
				$workflowInstance->updateStateParams( $state, $params );
				$workflowInstance->continueWorkflow( $state );
			}
		}
	}
}


function tpapAnyProductStock( $unique_id, $workflowInstance ) {

	$finalStates = [];

	if ( ! $unique_id ) {
		$allProducts = $workflowInstance->getAllProducts();
		$workflows = $workflowInstance->findWorkflowsStartingWithEvent( 'any_product_stock', true );

		foreach ( $workflows as $workflow ) {
			$steps = json_decode( $workflow->steps );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				error_log( "Error decoding workflow steps" );
				continue;
			}

			$firstStep = $steps[0];
			$amount = isset( $firstStep->event->parameters->amount ) ? (int) $firstStep->event->parameters->amount : 0;
			$above_below = isset( $firstStep->event->parameters->above_below ) ? sanitize_text_field( $firstStep->event->parameters->above_below ) : 0;
			$matchingProducts = [];

			foreach ( $allProducts as $productID ) {

				if ( $workflowInstance->checkProductStock( $productID, $amount, $above_below ) ) {
					$matchingProducts[] = (int) $productID;
				}

			}

			foreach ( $matchingProducts as $matchingProduct ) {

				$matchingChildState = $workflowInstance->findChildState( $workflow->unique_id, 'product_id', $matchingProduct, 0 );

				if ( ! $matchingChildState ) {
					$params = [ 'product_id' => $matchingProduct ];
					$finalStates[] = $workflowInstance->createChildState( $workflow->unique_id, $params, 0, $steps );
				}
			}
		}
		$inProgressStates = $workflowInstance->findInProgressStates();

	} else if ( $unique_id ) {

		$allProducts = $workflowInstance->getAllProducts();

		$workflow = $workflowInstance->findWorkflowByUniqueId( $unique_id );

		$steps = json_decode( $workflow->steps );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			error_log( "Error decoding workflow steps" );
			return false;
		}

		$firstEventName = $steps->steps[0]->event->name;

		if ( $firstEventName == 'any_product_stock' ) {
			$amount = isset( $steps->steps[0]->event->parameters->amount ) ? (int) $steps->steps[0]->event->parameters->amount : 0;
			$above_below = isset( $steps->steps[0]->event->parameters->above_below ) ? sanitize_text_field( $steps->steps[0]->event->parameters->above_below ) : 0;
			$matchingProducts = [];

			foreach ( $allProducts as $productID ) {

				if ( $workflowInstance->checkProductStock( $productID, $amount, $above_below ) ) {
					$matchingProducts[] = (int) $productID;
				}

			}

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

		if ( $currentStep->event->name !== 'any_product_stock' ) {
			continue;
		}

		if ( $state->current_step === count( $workflowSteps->steps ) - 1 && $currentStep->type === 'event' ) {
			$workflowInstance->logStep( $workflow->unique_id, $state->current_step, 'Failure. event is final step', $currentStep->event->name );
			continue;
		}

		$amount = isset( $currentStep->event->parameters->amount ) ? (int) $currentStep->event->parameters->amount : 0;
		$above_below = isset( $currentStep->event->parameters->above_below ) ? sanitize_text_field( $currentStep->event->parameters->above_below ) : 0;

		$matchingProducts = [];

		foreach ( $allProducts as $productID ) {

			if ( $workflowInstance->checkProductStock( $productID, $amount, $above_below ) ) {
				$matchingProducts[] = (int) $productID;
			}

		}

		foreach ( $matchingProducts as $matchingProduct ) {

			$matchingChildState = $workflowInstance->findChildState( $workflow->unique_id, 'product_id', $matchingProduct, $state->current_step );

			if ( ! $matchingChildState ) {
				$params = [ 'product_id' => $matchingProduct ];
				$finalStates[] = $workflowInstance->createChildState( $workflow->unique_id, $params, $state->current_step, $workflowSteps );
			}
		}
	}

	foreach ( $finalStates as $finalState ) {
		$workflowInstance->logStep( $finalState->unique_id, $finalState->current_step, 'Success', 'any_product_stock', $finalState->user_id );
		$workflowInstance->updateStateStep( $finalState->id, $finalState->current_step + 1 );
		$workflowInstance->continueWorkflow( $finalState );
	}
}