<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function tpapCouponUsed( $unique_id, $workflowInstance ) {

	if ( ! $unique_id ) {

		$startWithWorkflows = $workflowInstance->findWorkflowsStartingWithEvent( 'coupon_used', false );

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

				$firstEventName = $workflowSteps[0]->event->name;

				if ( $firstEventName == 'coupon_used' ) {

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

			return false;
		}

		$currentStep = $workflowSteps->steps[ $state->current_step ];

		if ( $currentStep->event->name !== 'coupon_used' )
			continue;

		if ( $state->current_step === count( $workflowSteps->steps ) - 1 && $currentStep->type === 'event' ) {
			$workflowInstance->logStep( $workflow->unique_id, $state->current_step, 'Failure. event is final step', $currentStep->event->name );
			continue;
		}

		$stepParameters = $currentStep->event->parameters;
		$stateParameters = unserialize( $state->parameters );
		if ( is_array( $stateParameters ) ) {
			$stateParameters = (object) $stateParameters;
		}

		if ( $stepParameters->coupon_id !== null ) { // ie. if coupon_id is not 'coupon from previous step'

			$couponUsed = $workflowInstance->checkCouponUsage( $stepParameters->coupon_id, $stepParameters->amount, $stepParameters->days ); // use the STEP parameters if not using 'coupon from previous step'

			if ( $couponUsed ) { // if coupon check rings true, and coupon was used X times in last X days, proceed the workflow.

				// store the coupon_id from this step in the state for future steps.
				$couponID = sanitize_text_field( $stepParameters->coupon_id );
				$params = [ 'coupon_id' => $couponID ];

				$workflowInstance->logStep( $workflow->unique_id, $state->current_step, 'Success', $currentStep->event->name );

				$workflowInstance->updateStateStep( $state->id, $state->current_step + 1 );

				$workflowInstance->updateStateParams( $state, $params );
				$workflowInstance->continueWorkflow( $state );

			}

		} else if ( $stepParameters->coupon_id === null && isset( $stateParameters->coupon_id ) && $stateParameters->coupon_id !== null ) {  // ie. if using 'coupon from previous' and the state actually has a coupon_id stored. 

			$couponUsed = $workflowInstance->checkCouponUsage( $stateParameters->coupon_id, $stepParameters->amount, $stepParameters->days ); // use the STATE parameters if we are using 'coupon from previous step'. amount and days is unique to this step, so cant use from previous step, so these remain as from STEP.

			if ( $couponUsed ) { // if coupon check rings true, and coupon was used X times in last X days, proceed the workflow.

				// store the coupon_id from this step in the state for future steps.
				$couponID = sanitize_text_field( $stateParameters->coupon_id );
				$params = [ 'coupon_id' => $couponID ];

				$workflowInstance->logStep( $workflow->unique_id, $state->current_step, 'Success', $currentStep->event->name );

				$workflowInstance->updateStateStep( $state->id, $state->current_step + 1 );

				$workflowInstance->updateStateParams( $state, $params );
				$workflowInstance->continueWorkflow( $state );

			}

		}

	}
}

function tpapAnyCouponUsed( $unique_id, $workflowInstance ) {

	$finalStates = [];

	if ( ! $unique_id ) {

		$allCoupons = $workflowInstance->getAllCoupons();
		$workflows = $workflowInstance->findWorkflowsStartingWithEvent( 'any_coupon_used', true ); // true here means we get all workflows that start with the event, regardless of whether they have a state or not yet.
		//  This is because there may already be a branch for the same workflow for a different coupon, and the workflow would be overlooked entirely if we only got workflows without states yet.

		foreach ( $workflows as $workflow ) {
			$steps = json_decode( $workflow->steps );
			$days = (int) $steps[0]->event->parameters->days;
			$amount = (int) $steps[0]->event->parameters->amount;
			$matchingCoupons = [];

			foreach ( $allCoupons as $couponID ) {
				if ( $workflowInstance->checkCouponUsage( $couponID, $amount, $days ) ) // checkCouponUses() will return true if the coupon was used X times or more in X days. It will also return true if it was used EXACTLY 0 times in last X days (for when user wants to see if a coupon was not used in X days)
					$matchingCoupons[] = $couponID;
			}
		}

		foreach ( $matchingCoupons as $matchingCoupon ) {

			$matchingChildState = $workflowInstance->findChildState( $workflow->unique_id, 'coupon_id', $matchingCoupon, 0 );

			if ( ! $matchingChildState ) {
				$params = [ 'coupon_id' => $matchingCoupon ];
				$finalStates[] = $workflowInstance->createChildState( $workflow->unique_id, $params, 0, $steps );
			}
		}


		$inProgressStates = $workflowInstance->findInProgressStates();

	} else if ( $unique_id ) {

		$allCoupons = $workflowInstance->getAllCoupons();
		$workflow = $workflowInstance->findWorkflowByUniqueId( $unique_id );
		$steps = json_decode( $workflow->steps );

		$firstEventName = $steps->steps[0]->event->name;

		if ( $firstEventName == 'any_coupon_used' ) {
			$days = (int) $steps->steps[0]->event->parameters->days;
			$amount = (int) $steps->steps[0]->event->parameters->amount;
			$matchingCoupons = [];

			foreach ( $allCoupons as $couponID ) {
				if ( $workflowInstance->checkCouponUsage( $couponID, $amount, $days ) ) // checkCouponUses() will return true if the coupon was used X times or more in X days. It will also return true if it was used EXACTLY 0 times in last X days (for when user wants to see if a coupon was not used in X days)
					$matchingCoupons[] = $couponID;
			}

			foreach ( $matchingCoupons as $matchingCoupon ) {

				$matchingChildState = $workflowInstance->findChildState( $workflow->unique_id, 'coupon_id', $matchingCoupon, 0 );

				if ( ! $matchingChildState ) { // if a branch doesn't exist already with this coupon_id at the first step, that means it hasnt initiated for this coupon yet, so initiate it by creating a state.
					// why are we creating a child state here not just a state? because we need to create a branch-off for this particular coupon. 
					// so there will never actually be a normal 'state' (that isnt a child) created for this workflow, if the first step is an 'any' event? no, because it will always branch off from step 1, so a parent state is redundant. 
					// when we come to continue workflows in part 2 below, we are finding in progress states. This means workflows that are currently being progressed. They dont need to be child states, we just look for all states that are in progress. 

					$params = [ 'coupon_id' => $matchingCoupon ];
					$finalStates[] = $workflowInstance->createChildState( $workflow->unique_id, $params, 0, $steps );

				}
			}
		}

		$inProgressStates = $workflowInstance->findInProgressStates( $unique_id );

	}

	foreach ( $inProgressStates as $state ) {

		$workflow = $workflowInstance->findWorkflowByUniqueId( $state->unique_id );
		$workflowSteps = json_decode( $workflow->steps );
		$currentStep = $workflowSteps->steps[ $state->current_step ];

		if ( $currentStep->event->name !== 'any_coupon_used' ) {
			continue;
		}

		if ( $state->current_step === count( $workflowSteps->steps ) - 1 && $currentStep->type === 'event' ) {
			$workflowInstance->logStep( $workflow->unique_id, $state->current_step, 'Failure. event is final step', $currentStep->event->name );
			continue;
		}

		$days = (int) $currentStep->event->parameters->days;
		$amount = (int) $currentStep->event->parameters->amount;
		$couponsUsed = [];

		foreach ( $allCoupons as $couponID ) {
			if ( $workflowInstance->checkCouponUsage( $couponID, $amount, $days ) ) { // check if coupon was used X times in X days. Again, if amount is 0 here, it will return true also. 0 will be treated differently by the checkCouponUses() function, it will check that the usage was EXACTLY 0, wheras any other number will check that number OR MORE.
				$couponsUsed[] = $couponID; // add the coupon that passed the check to the couponsUsed array.
			}
		}

		foreach ( $couponsUsed as $matchingCoupon ) {

			$matchingChildState = $workflowInstance->findChildState( $workflow->unique_id, 'coupon_id', $matchingCoupon, $state->current_step );  // check if any branches exist with this coupon at the same step in their 'steps' record.
			// note: the state being iterated's current step will be the same step we will find thecoupon that caused the branch. It cannot proceed beyong the current step, as it is an 'any' event. The workflow will always branch off here for any matching coupons, so the parent state will never be advanced.

			if ( ! $matchingChildState ) { //if no branches exist with this coupon at the same step in their 'steps' record... that means a nbranch has not been created for this workflow with this coupon yet, so create a branch.

				$params = [ 'coupon_id' => $matchingCoupon ];
				$finalStates[] = $workflowInstance->createChildState( $workflow->unique_id, $params, $state->current_step, $workflowSteps ); // add the new branch to the states we want to advance. make a record of the steps with this couponID in it so we do not create any duplicates later. 

			}

		}
	}

	foreach ( $finalStates as $finalState ) {

		$workflowInstance->logStep( $finalState->unique_id, $finalState->current_step, 'Success', 'any_coupon_used', $finalState->user_id );
		$workflowInstance->updateStateStep( $finalState->id, $finalState->current_step + 1 );
		$workflowInstance->continueWorkflow( $finalState );
	}
}

