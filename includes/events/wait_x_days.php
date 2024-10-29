<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function tpapWaitXDays( $unique_id, $workflowInstance ) {

	if ( ! $unique_id ) {

		$startWithWorkflows = $workflowInstance->findWorkflowsStartingWithEvent( 'wait_x_days', false );  // false here returns workflows that do not have an existing state. If they have an existing state, that means they have already been initiated and we dont want to create a new state for them.

		if ( ! empty( $startWithWorkflows ) ) {

			foreach ( $startWithWorkflows as $workflow ) {

				$params = [ 
					'start_date' => wp_date( 'Y-m-d' ),
					'wait_x_days_set_for_step' => 0
				];

				$workflowInstance->createState( $workflow->unique_id, $params, null, 0 );

			}
		}

		// part 2: continue workflows

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

				if ( $firstEventName == 'wait_x_days' ) {

					$params = [ 
						'start_date' => wp_date( 'Y-m-d' ),
						'wait_x_days_set_for_step' => 0
					];

					$workflowInstance->createState( $workflow->unique_id, $params, null, 0 );
				}

			}

			// part 2: continue workflows
		}

		$inProgressStates = $workflowInstance->findInProgressStates( $unique_id );


	}
	if ( empty( $inProgressStates ) )
		return;

	foreach ( $inProgressStates as $state ) {

		// Refresh the state to get the latest data
		$state = $workflowInstance->refreshState( $state->id );

		$parameters = json_decode( $state->parameters, true );

		// Check for JSON decoding errors and handle invalid data
		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $parameters ) ) {
			$parameters = [];
		}

		if ( ! isset( $parameters['wait_x_days_set_for_step'] ) || $parameters['wait_x_days_set_for_step'] != $state->current_step ) {

			// Set the start date and log the step at which 'wait_x_days' was set
			$params = [ 
				'start_date' => wp_date( 'Y-m-d' ),
				'wait_x_days_set_for_step' => (int) $state->current_step
			];

			// Update the state with new parameters
			$workflowInstance->updateStateParams( $state, $params );
		}

		//get workflows to be tested and progressed
		$workflow = $workflowInstance->findWorkflowByUniqueId( $state->unique_id );
		if ( $workflow->status === 'inactive' ) {
			continue;
		}
		$workflowSteps = json_decode( $workflow->steps );
		if ( json_last_error() !== JSON_ERROR_NONE ) {

			continue;
		}

		$currentStep = $workflowSteps->steps[ $state->current_step ];

		if ( $currentStep->event->name !== 'wait_x_days' )
			continue;

		if ( $state->current_step === count( $workflowSteps->steps ) - 1 && $currentStep->type === 'event' ) {
			$workflowInstance->logStep( $workflow->unique_id, $state->current_step, 'Failure. event is final step', $currentStep->event->name );
			continue;
		}

		//get the parameters
		$stepParameters = $currentStep->event->parameters;
		$stateParameters = json_decode( $state->parameters, true );

		// Check for JSON decoding errors and handle invalid data
		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $stateParameters ) ) {
			$stateParameters = [];
		}

		// Convert state parameters to an object
		$stateParameters = (object) $stateParameters;

		if ( empty( $stateParameters->start_date ) )
			continue;

		$dateCheck = $workflowInstance->checkSinceDate( $stateParameters->start_date, $stepParameters->days ); // checkSinceDate() returns true if current date IS at least 'days' later than start date. 

		if ( $dateCheck ) { // if the current date is X days later than start date, continue the workflow. 

			$workflowInstance->logStep( $workflow->unique_id, $state->current_step, 'Success', $currentStep->event->name );

			$workflowInstance->updateStateStep( $state->id, $state->current_step + 1 );

			$workflowInstance->continueWorkflow( $state );

		}

	}

}
