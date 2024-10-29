<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function tpapUpsellClicked( $unique_id, $workflowInstance ) {

	if ( ! $unique_id ) {

		$startWithWorkflows = $workflowInstance->findWorkflowsStartingWithEvent( 'upsell_clicked', false ); // false indicates no state yet.

		if ( ! empty( $startWithWorkflows ) ) { // if we find workflows that start with 'upsell_clicked' but don't have a state yet, they need to be initiated / have a state created for them. This state will then be picked up by $inProgressStates for testing and progressing. 

			foreach ( $startWithWorkflows as $workflow ) {

				$workflowInstance->createState( $workflow->unique_id, null, null, 0 );
			}
		}

		$inProgressStates = $workflowInstance->findInProgressStates();

	} else if ( $unique_id ) {

		if ( ! $workflowInstance->findStatesByWorkflowId( $unique_id ) ) { // if no state exists yet for this workflow, that means it has not been initiated yet. Therefore, we must create a state for it, but only if its first step's event is 'upsell_clicked' and it passes the condition. 
			// After we create the state/initiate the workflow, it will be picked up by $inProgressStates as a 'state in progress' for this specific unique_id.

			$workflow = $workflowInstance->findWorkflowByUniqueId( $unique_id );

			if ( ! empty( $workflow ) ) {

				$workflowSteps = json_decode( $workflow->steps );
				if ( json_last_error() !== JSON_ERROR_NONE ) {

					error_log( "Error decoding workflow steps" );

					return false;
				}

				$firstEventName = $workflowSteps[0]->event->name;

				if ( $firstEventName == 'upsell_clicked' ) {
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

		if ( $currentStep->event->name !== 'upsell_clicked' )
			continue;

		if ( $state->current_step === count( $workflowSteps->steps ) - 1 && $currentStep->type === 'event' ) {
			$workflowInstance->logStep( $workflow->unique_id, $state->current_step, 'Failure. event is final step', $currentStep->event->name );
			continue;
		}

		$stepParameters = $currentStep->event->parameters;

		$upsellDateChanged = get_option( 'aetp_upsell_date_last_change' ); // get the date the upsell offer was last changed, so we only measure the clicks AFTER that point.

		$upsellClicked = $workflowInstance->checkUpsellClicks( $upsellDateChanged, $stepParameters->amount, $stepParameters->days );

		if ( $upsellClicked ) { // if the step evaluates to true, and needs to therefore be continued, continue the workflow for that state. 

			$productID = get_option( 'aetp_upsell_product_id' );

			$params = [ 'product_id' => (int) $productID ];

			$workflowInstance->logStep( $workflow->unique_id, $state->current_step, 'Success', $currentStep->event->name );

			$workflowInstance->updateStateStep( $state->id, $state->current_step + 1 );
			$workflowInstance->updateStateParams( $state, $params );
			$workflowInstance->continueWorkflow( $state );

		}

	}

}