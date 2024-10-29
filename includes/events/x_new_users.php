<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function tpapXNewUsers( $unique_id, $workflowInstance ) {
	if ( ! $unique_id ) {

		$startWithWorkflows = $workflowInstance->findWorkflowsStartingWithEvent( 'x_new_users', false );

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

				if ( $firstEventName == 'x_new_users' ) {

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

		if ( $currentStep->event->name !== 'x_new_users' )
			continue;

		if ( $state->current_step === count( $workflowSteps->steps ) - 1 && $currentStep->type === 'event' ) {
			$workflowInstance->logStep( $workflow->unique_id, $state->current_step, 'Failure. event is final step', $currentStep->event->name );
			continue;
		}

		$stepParameters = $currentStep->event->parameters;

		$checkNewUsers = $workflowInstance->checkNewUsers( $stepParameters->amount, $stepParameters->days ); // checkNewUsers() returns true if there were at least X new users in last X days

		if ( $checkNewUsers ) {

			$workflowInstance->logStep( $workflow->unique_id, $state->current_step, 'Success', $currentStep->event->name );

			$workflowInstance->updateStateStep( $state->id, $state->current_step + 1 );

			$workflowInstance->continueWorkflow( $state );

		}

	}

}
