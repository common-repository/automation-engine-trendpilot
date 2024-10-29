<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function tpapProductCatViewed( $unique_id, $workflowInstance ) {

	if ( ! $unique_id ) {
		$startWithWorkflows = $workflowInstance->findWorkflowsStartingWithEvent( 'product_cat_viewed', false );

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

					return false;
				}

				$firstEventName = $workflowSteps->steps[0]->event->name;

				if ( $firstEventName == 'product_cat_viewed' ) {
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

		if ( $currentStep->event->name !== 'product_cat_viewed' )
			continue;

		if ( $state->current_step === count( $workflowSteps->steps ) - 1 && $currentStep->type === 'event' ) {
			$workflowInstance->logStep( $workflow->unique_id, $state->current_step, 'Failure. event is final step', $currentStep->event->name );
			continue;
		}

		$stepParameters = $currentStep->event->parameters;
		$stateParameters = json_decode( $state->parameters, true );

		// Check for JSON decoding errors and ensure the result is an array
		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $stateParameters ) ) {
			$stateParameters = [];
		}

		// Convert array to an object
		$stateParameters = (object) $stateParameters;

		// Determine the amount based on perc_amount.
		if ( isset( $stepParameters->perc_amount ) ) {
			$amount = $stepParameters->perc_amount;
		} else {
			return false;
		}

		if ( $stepParameters->cat_id !== null ) { // if the cat_id to evaluate is a specific category...
			if ( $stepParameters->amount_type === 'percentage' ) {
				$catViewed = $workflowInstance->checkProductCatViewedGrowth( $stepParameters->cat_id, $amount, $stepParameters->days );
			} else {
				$catViewed = $workflowInstance->checkProductCatViewed( $stepParameters->cat_id, $stepParameters->days, $amount );
			}

			if ( $catViewed ) {

				// update the state's params with the category just assessed.
				$catID = sanitize_text_field( $stepParameters->cat_id );
				$params = [ 'cat_id' => $catID ];

				$workflowInstance->logStep( $workflow->unique_id, $state->current_step, 'Success', $currentStep->event->name );
				$workflowInstance->updateStateStep( $state->id, $state->current_step + 1 );
				$workflowInstance->updateStateParams( $state, $params );
				$workflowInstance->continueWorkflow( $state );

			}
		} else if ( $stepParameters->cat_id === null && isset( $stateParameters->cat_id ) && $stateParameters->cat_id !== null ) {
			if ( $stepParameters->amount_type === 'percentage' ) {
				$catViewed = $workflowInstance->checkProductCatViewedGrowth( $stateParameters->cat_id, $amount, $stepParameters->days );
			} else {
				$catViewed = $workflowInstance->checkProductCatViewed( $stateParameters->cat_id, $stepParameters->days, $amount );
			}

			if ( $catViewed ) {
				$catID = sanitize_text_field( $stateParameters->cat_id );
				$params = [ 'cat_id' => $catID ];

				$workflowInstance->logStep( $workflow->unique_id, $state->current_step, 'Success', $currentStep->event->name );
				$workflowInstance->updateStateStep( $state->id, $state->current_step + 1 );
				$workflowInstance->updateStateParams( $state, $params );
				$workflowInstance->continueWorkflow( $state );

			}
		}
	}
}

function tpapAnyProductCatViewed( $unique_id, $workflowInstance ) {

	$finalStates = [];

	if ( ! $unique_id ) {

		// part 1

		$allCategories = $workflowInstance->getAllCategories();
		$workflows = $workflowInstance->findWorkflowsStartingWithEvent( 'any_product_cat_viewed', true );


		foreach ( $workflows as $workflow ) {
			$steps = json_decode( $workflow->steps );
			$days = (int) $steps[0]->event->parameters->days;
			$amount = isset( $steps[0]->event->parameters->perc_amount ) ? (int) $steps[0]->event->parameters->perc_amount : (int) $steps[0]->event->parameters->amount;
			$amount_type = isset( $steps[0]->event->parameters->amount_type ) ? $steps[0]->event->parameters->amount_type : 'specific';
			$matchingCategories = [];

			foreach ( $allCategories as $catID ) {
				if ( $amount_type === 'percentage' ) {
					if ( $workflowInstance->checkProductCatViewedGrowth( $catID, $amount, $days ) ) {
						$matchingCategories[] = sanitize_text_field( $catID );
					}
				} else {
					if ( $workflowInstance->checkProductCatViewed( $catID, $days, $amount ) ) {
						$matchingCategories[] = sanitize_text_field( $catID );
					}
				}
			}

			foreach ( $matchingCategories as $matchingCat ) {

				$matchingChildState = $workflowInstance->findChildState( $workflow->unique_id, 'cat_id', $matchingCat, 0 );

				if ( ! $matchingChildState ) { // if a branch not in progress already for this product+workflow combo.

					$params = [ 'cat_id' => $matchingCat ];
					$finalStates[] = $workflowInstance->createChildState( $workflow->unique_id, $params, 0, $steps );  // initiate a branch, passing in the matching product to state params.

				}
			}
		}
		$inProgressStates = $workflowInstance->findInProgressStates();

	}
	if ( $unique_id ) {

		$allCategories = $workflowInstance->getAllCategories();
		$workflow = $workflowInstance->findWorkflowByUniqueId( $unique_id );
		$steps = json_decode( $workflow->steps );
		$firstEventName = $steps->steps[0]->event->name;

		if ( $firstEventName == 'any_product_cat_viewed' ) {

			$days = (int) $steps->steps[0]->event->parameters->days;
			$amount = isset( $steps->steps[0]->event->parameters->perc_amount ) ? (int) $steps->steps[0]->event->parameters->perc_amount : (int) $steps->steps[0]->event->parameters->amount;
			$amount_type = isset( $steps->steps[0]->event->parameters->amount_type ) ? $steps->steps[0]->event->parameters->amount_type : 'specific';
			$matchingCategories = [];

			foreach ( $allCategories as $catID ) {
				if ( $amount_type === 'percentage' ) {
					if ( $workflowInstance->checkProductCatViewedGrowth( $catID, $amount, $days ) ) {
						$matchingCategories[] = sanitize_text_field( $catID );
					}
				} else {
					if ( $workflowInstance->checkProductCatViewed( $catID, $days, $amount ) ) {
						$matchingCategories[] = sanitize_text_field( $catID );
					}
				}
			}

			foreach ( $matchingCategories as $matchingCat ) {

				$matchingChildState = $workflowInstance->findChildState( $workflow->unique_id, 'cat_id', $matchingCat, 0 );

				if ( ! $matchingChildState ) { // if a branch not in progress already for this product+workflow combo.

					$params = [ 'cat_id' => $matchingCat ];
					$finalStates[] = $workflowInstance->createChildState( $workflow->unique_id, $params, 0, $steps );  // initiate a branch, passing in the matching product to state params.
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

		if ( $currentStep->event->name !== 'any_product_cat_viewed' ) {
			continue;
		}

		if ( $state->current_step === count( $workflowSteps->steps ) - 1 && $currentStep->type === 'event' ) {
			$workflowInstance->logStep( $workflow->unique_id, $state->current_step, 'Failure. event is final step', $currentStep->event->name );
			continue;
		}

		$days = (int) $currentStep->event->parameters->days;
		$amount = isset( $currentStep->event->parameters->perc_amount ) ? (int) $currentStep->event->parameters->perc_amount : (int) $currentStep->event->parameters->amount;
		$amount_type = isset( $currentStep->event->parameters->amount_type ) ? $currentStep->event->parameters->amount_type : 'specific';

		$categoriesViewed = [];

		foreach ( $allCategories as $catID ) {
			if ( $amount_type === 'percentage' ) {
				if ( $workflowInstance->checkProductCatViewedGrowth( $catID, $amount, $days ) ) {
					$categoriesViewed[] = sanitize_text_field( $catID );
				}
			} else {
				if ( $workflowInstance->checkProductCatViewed( $catID, $days, $amount ) ) {
					$categoriesViewed[] = sanitize_text_field( $catID );
				}
			}
		}

		foreach ( $categoriesViewed as $matchingCat ) { // for each of the products that passed the check for this step, see if a child state exists already, if not create one. then continue the child states. if a child state does exist, leave it because it has already branched off previously. 

			$matchingChildState = $workflowInstance->findChildState( $workflow->unique_id, 'cat_id', $matchingCat, $state->current_step );

			if ( ! $matchingChildState ) {
				$params = [ 'cat_id' => $matchingCat ];
				$finalStates[] = $workflowInstance->createChildState( $workflow->unique_id, $params, $state->current_step, $workflowSteps );


			}
		}
	}

	foreach ( $finalStates as $finalState ) {

		$workflowInstance->logStep( $finalState->unique_id, $finalState->current_step, 'Success', 'any_product_cat_viewed', $finalState->user_id );
		$workflowInstance->updateStateStep( $finalState->id, $finalState->current_step + 1 );
		$workflowInstance->continueWorkflow( $finalState );
	}
}