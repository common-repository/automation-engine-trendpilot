<?php

namespace AETrendpilot;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class AETrendpilotCron {

	public $hook_name;
	public $cron_time_str;

	function __construct() {


		$this->hook_name = 'tpap_daily_workflow_checker';
		$this->cron_time_str = get_option( 'aetp_cron_job_time', '00:00' );
		$this->createCronJob();
	}

	public function registerHooks() {

		add_action( $this->hook_name, [ $this, 'execute_in_progress_workflows' ] );
		add_action( $this->hook_name, [ $this, 'handle_cleanup_csv_logs' ] );

	}

	public function createCronJob() {


		if ( empty( $this->cron_time_str ) ) {
			$this->cron_time_str = '00:00';
			update_option( 'aetp_cron_job_time', $this->cron_time_str );
		}


		list( $hour, $minute ) = explode( ':', $this->cron_time_str );

		$next_run_time = strtotime( "today {$hour}:{$minute}" );
		if ( $next_run_time < time() ) {
			$next_run_time = strtotime( "tomorrow {$hour}:{$minute}" );
		}

		if ( ! wp_next_scheduled( $this->hook_name ) ) {
			wp_schedule_event( $next_run_time, 'daily', $this->hook_name );
		}
	}

	public function execute_in_progress_workflows() {

		global $wpdb;
		$table_name = $wpdb->prefix . 'trendpilot_automation_engine_workflows';

		// Query to fetch 'in-progress' workflows
		$workflows = $wpdb->get_results( "SELECT unique_id FROM {$table_name} WHERE status = 'active'", ARRAY_A ); // db call ok
		$count = 1;

		// Iterate over workflows and trigger the function
		foreach ( $workflows as $workflow ) {

			$workflowInstance = new AETrendpilotWorkflow();
			$workflowInstance->on_workflow_create_update( sanitize_text_field( $workflow['unique_id'] ) );

			$count++;
		}

	}

	public function handle_cleanup_csv_logs() {
		$workflow = new AETrendpilotWorkflow();
		$workflow->cleanup_old_workflow_logs();
	}

}