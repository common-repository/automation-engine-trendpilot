<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function tpapAnyRecProductClicked( $unique_id, $workflowInstance ) {

	$finalStates = [];

	// part 1a
	if ( ! $unique_id ) {

		$allRecProducts = $workflowInstance->getAllRecProducts();
		$workflows = $workflowInstance->findWorkflowsStartingWithEvent( 'any_rec_product_clicked', true );

		foreach ( $workflows as $workflow ) {
			$steps = json_decode( $workflow->steps );
			$days = (int) $steps[0]->event->parameters->days;
			$amount = (int) $steps[0]->event->parameters->amount;
			$matchingRecProducts = [];

			foreach ( $allRecProducts as $productID ) {
				if ( $workflowInstance->checkRecProdClicked( $productID, $days, $amount ) ) {
					$matchingRecProducts[] = $productID;
				}
			}

			foreach ( $matchingRecProducts as $matchingRecProduct ) {

				$matchingChildState = $workflowInstance->findChildState( $workflow->unique_id, 'product_id', $matchingRecProduct, 0 );

				if ( ! $matchingChildState ) {

					$params = [ 'product_id' => $matchingRecProduct ];
					$finalStates[] = $workflowInstance->createChildState( $workflow->unique_id, $params, 0, $steps );

				}
			}
		}

		$inProgressStates = $workflowInstance->findInProgressStates();

	} else if ( $unique_id ) {

		$allRecProducts = $workflowInstance->getAllRecProducts();
		$workflow = $workflowInstance->findWorkflowByUniqueId( $unique_id );
		$steps = json_decode( $workflow->steps );

		$firstEventName = $steps->steps[0]->event->name;

		if ( $firstEventName == 'any_rec_product_clicked' ) {
			$days = (int) $steps->steps[0]->event->parameters->days;
			$amount = (int) $steps->steps[0]->event->parameters->amount;
			$matchingRecProducts = [];

			foreach ( $allRecProducts as $productID ) {
				if ( $workflowInstance->checkRecProdClicked( $productID, $days, $amount ) ) {
					$matchingRecProducts[] = $productID;
				}
			}

			foreach ( $matchingRecProducts as $matchingRecProduct ) {

				$matchingChildState = $workflowInstance->findChildState( $workflow->unique_id, 'product_id', $matchingRecProduct, 0 );

				if ( ! $matchingChildState ) {

					$params = [ 'product_id' => $matchingRecProduct ];
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

		if ( $currentStep->event->name !== 'any_rec_product_clicked' ) {
			continue;
		}

		if ( $state->current_step === count( $workflowSteps->steps ) - 1 && $currentStep->type === 'event' ) {
			$workflowInstance->logStep( $workflow->unique_id, $state->current_step, 'Failure. event is final step', $currentStep->event->name );
			continue;
		}

		$days = (int) $currentStep->event->parameters->days;
		$amount = (int) $currentStep->event->parameters->amount;

		$recProductsClicked = [];

		foreach ( $allRecProducts as $productID ) {
			if ( $workflowInstance->checkRecProdClicked( $productID, $days, $amount ) ) {

				$recProductsClicked[] = $productID;
			}
		}


		foreach ( $recProductsClicked as $matchingProduct ) {

			$matchingChildState = $workflowInstance->findChildState( $workflow->unique_id, 'product_id', $matchingProduct, $state->current_step );

			if ( ! $matchingChildState ) {
				$params = [ 'product_id' => $matchingProduct ];
				$finalStates[] = $workflowInstance->createChildState( $workflow->unique_id, $params, $state->current_step, $workflowSteps );

			}
		}
	}

	foreach ( $finalStates as $finalState ) {

		$workflowInstance->logStep( $finalState->unique_id, $finalState->current_step, 'Success', 'any_rec_product_clicked', $finalState->user_id );
		$workflowInstance->updateStateStep( $finalState->id, $finalState->current_step + 1 );
		$workflowInstance->continueWorkflow( $finalState );

	}
}