<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function tpapRunWorkflowNow( $unique_id, $workflowInstance ) {
	//part1. handling workflows without a state yet and initiating them. 

	if ( ! $unique_id ) {

		$startWithWorkflows = $workflowInstance->findWorkflowsStartingWithEvent( 'run_workflow_now', false );

		if ( ! empty( $startWithWorkflows ) ) {

			foreach ( $startWithWorkflows as $workflow ) {

				$workflowInstance->createState( $workflow->unique_id, null, null, 0 );

			}
		}

		$inProgressStates = $workflowInstance->findInProgressStates();

	} else if ( $unique_id ) {

		//3, THIRD UPDATE FOR ALL FUNCTIONS: make the below function plural. It now finds multiple states
		if ( ! $workflowInstance->findStatesByWorkflowId( $unique_id ) ) {

			$workflow = $workflowInstance->findWorkflowByUniqueId( $unique_id );

			if ( ! empty( $workflow ) ) {

				$workflowSteps = json_decode( $workflow->steps );
				if ( json_last_error() !== JSON_ERROR_NONE ) {

					error_log( "Error decoding workflow steps" );

					return false;
				}

				// 1, FIRST UPDATE FOR ALL FUNCTIONS: $firstEventName was not defined correctly.
				// $firstEventName = $workflowSteps[0]->event->name; (THISE CODE WAS WRONG, NOTED FOR OTHER FUNCTIONS THAT CALL IT IN THE SAME WAY)
				$firstEventName = $workflowSteps->steps[0]->event->name;

				if ( $firstEventName == 'run_workflow_now' ) {

					$workflowInstance->createState( $workflow->unique_id, null, null, 0 );

				}
			}
		}

		$inProgressStates = $workflowInstance->findInProgressStates( $unique_id );


	}

	// part 2. continue workflows with the current step's event set to 'on_set_date'.

	if ( empty( $inProgressStates ) ) {

		return;

	}


	foreach ( $inProgressStates as $state ) {


		$workflow = $workflowInstance->findWorkflowByUniqueId( $state->unique_id );

		// Check if the workflow status is 'inactive'
		if ( $workflow->status === 'inactive' ) {

			continue;
		}

		/// new code ends here ////

		$workflowSteps = json_decode( $workflow->steps );
		if ( json_last_error() !== JSON_ERROR_NONE ) {

			error_log( "Error decoding workflow steps within inProgressStates progression" );

			continue;
		}

		$currentStep = $workflowSteps->steps[ $state->current_step ];

		if ( $currentStep->event->name !== 'run_workflow_now' )
			continue;

		if ( $state->current_step === count( $workflowSteps->steps ) - 1 && $currentStep->type === 'event' ) {
			$workflowInstance->logStep( $workflow->unique_id, $state->current_step, 'Failure. event is final step', $currentStep->event->name );
			continue;
		}

		$workflowInstance->logStep( $workflow->unique_id, $state->current_step, 'Workflow initiated', $currentStep->event->name );

		$workflowInstance->updateStateStep( $state->id, $state->current_step + 1 );

		$workflowInstance->continueWorkflow( $state );

	}

}