<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function tpapProductNotViewed( $unique_id, $workflowInstance ) {
	if ( ! $unique_id ) {

		$startWithWorkflows = $workflowInstance->findWorkflowsStartingWithEvent( 'product_not_viewed', false );

		if ( ! empty( $startWithWorkflows ) ) {

			foreach ( $startWithWorkflows as $workflow ) {

				$workflowInstance->createState( $workflow->unique_id, null, null, 0 );

			}
		}

		$inProgressStates = $workflowInstance->findInProgressStates();
	} else if ( $unique_id ) {

		if ( ! $workflowInstance->findStatesByWorkflowId( $unique_id ) ) { // if a unique_id is passed in (ie. a specific workflow wants to be initiated), and there is no state yet for that workflow, and that workflow's first step is 'product_not_viewed', initiate it. 

			$workflow = $workflowInstance->findWorkflowByUniqueId( $unique_id );

			if ( ! empty( $workflow ) ) {

				$workflowSteps = json_decode( $workflow->steps );
				if ( json_last_error() !== JSON_ERROR_NONE ) {

					error_log( "Error decoding workflow steps" );

					return false;
				}

				$firstEventName = $workflowSteps[0]->event->name;

				if ( $firstEventName == 'product_not_viewed' ) {

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

		if ( $currentStep->event->name !== 'product_not_viewed' )
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

			$productViewed = $workflowInstance->checkProductViews( $stepParameters->product_id, $stepParameters->days );

			if ( $productViewed == false ) {

				$productID = (int) $stepParameters->product_id;
				$params = [ 'product_id' => $productID ];

				$workflowInstance->logStep( $workflow->unique_id, $state->current_step, 'Success', $currentStep->event->name );

				$workflowInstance->updateStateStep( $state->id, $state->current_step + 1 );

				$workflowInstance->updateStateParams( $state, $params );
				$workflowInstance->continueWorkflow( $state );

			}
		} else if ( $stepParameters->product_id === null && isset( $stateParameters->product_id ) && $stateParameters->product_id !== null ) { // so if the step params set to 'product from previous step' AND there is a product_id actually available from a prevous step...

			$productViewed = $workflowInstance->checkProductViews( $stateParameters->product_id, $stepParameters->days );

			if ( $productViewed == false ) {

				$productID = (int) $stateParameters->product_id;
				$params = [ 'product_id' => $productID ];

				$workflowInstance->logStep( $workflow->unique_id, $state->current_step, 'Success', $currentStep->event->name );

				$workflowInstance->updateStateStep( $state->id, $state->current_step + 1 );
				$workflowInstance->updateStateParams( $state, $params );
				$workflowInstance->continueWorkflow( $state );

			} else
				continue;

		}
	}
}


function tpapAnyProductNotViewed( $unique_id, $workflowInstance ) {

	$finalStates = [];

	if ( ! $unique_id ) {

		$allProducts = $workflowInstance->getAllProducts();
		$workflows = $workflowInstance->findWorkflowsStartingWithEvent( 'any_product_not_viewed', true );

		foreach ( $workflows as $workflow ) {
			$steps = json_decode( $workflow->steps );
			$days = (int) $steps[0]->event->parameters->days;
			$matchingProducts = [];
			foreach ( $allProducts as $productID ) {
				if ( ! $workflowInstance->checkProductViews( $productID, $days ) ) {
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

		$firstEventName = $steps[0]->event->name;

		if ( $firstEventName == 'any_product_not_viewed' ) {
			$days = (int) $steps[0]->event->parameters->days;
			$matchingProducts = [];
			foreach ( $allProducts as $productID ) {
				if ( ! $workflowInstance->checkProductViews( $productID, $days ) ) {
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
		$currentStep = $workflowSteps->steps[ $state->current_step ];

		if ( $currentStep->event->name !== 'any_product_not_viewed' ) {
			continue;
		}

		if ( $state->current_step === count( $workflowSteps->steps ) - 1 && $currentStep->type === 'event' ) {
			$workflowInstance->logStep( $workflow->unique_id, $state->current_step, 'Failure. event is final step', $currentStep->event->name );
			continue;
		}

		$days = (int) $currentStep->event->parameters->days;
		$productsNotViewed = [];
		foreach ( $allProducts as $productID ) {
			if ( ! $workflowInstance->checkProductViews( $productID, $days ) ) {
				$productsNotViewed[] = (int) $productID;
			}
		}

		foreach ( $productsNotViewed as $matchingProduct ) {

			$matchingChildState = $workflowInstance->findChildState( $workflow->unique_id, 'product_id', $matchingProduct, $state->current_step );

			if ( ! $matchingChildState ) {
				$params = [ 'product_id' => $matchingProduct ];
				$finalStates[] = $workflowInstance->createChildState( $workflow->unique_id, $params, $state->current_step, $workflowSteps );

			}
		}
	}

	foreach ( $finalStates as $finalState ) {

		$workflowInstance->logStep( $finalState->unique_id, $finalState->current_step, 'Success', 'any_product_not_viewed', $finalState->user_id );
		$workflowInstance->updateStateStep( $finalState->id, $finalState->current_step + 1 );
		$workflowInstance->continueWorkflow( $finalState );
	}
}
