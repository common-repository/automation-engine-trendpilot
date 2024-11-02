<?php

namespace AETrendpilot;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class AETrendpilotPlugin {

	protected static $loader;

	public static function init() {
		self::get_loader()->run();
	}

	// Get the loader instance
	protected static function get_loader() {
		if ( null === self::$loader ) {
			self::$loader = new AETrendpilotLoader();
		}
		return self::$loader;
	}

	public static function activate() {
		global $wpdb;

		// Database table names
		$workflows_table_name = $wpdb->prefix . 'trendpilot_automation_engine_workflows';
		$states_table_name = $wpdb->prefix . 'trendpilot_automation_engine_states';
		$page_views_table_name = $wpdb->prefix . 'trendpilot_automation_engine_page_views';
		$click_data_table_name = $wpdb->prefix . 'trendpilot_automation_engine_click_data';
		$recommended_loop_clicks_table_name = $wpdb->prefix . 'trendpilot_automation_engine_recommended_loop_clicks';
		$recommended_products_table_name = $wpdb->prefix . 'trendpilot_automation_engine_recommended_products';
		$logger_table_name = $wpdb->prefix . 'trendpilot_automation_engine_workflow_logger';
		$ab_tests_table_name = $wpdb->prefix . 'trendpilot_automation_engine_ab_tests';
		$workflow_templates_table_name = $wpdb->prefix . 'trendpilot_workflow_templates';

		$charset_collate = $wpdb->get_charset_collate();

		// SQL statements for existing tables
		$sql_workflows = "CREATE TABLE $workflows_table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				unique_id varchar(255) DEFAULT NULL,
				name varchar(255) NOT NULL,
				steps longtext,
				is_repeat tinyint(1) NOT NULL DEFAULT 0,
				status varchar(50) DEFAULT 'active' NOT NULL,
				created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
				updated_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
				PRIMARY KEY (id)
			) $charset_collate;";

		$sql_states = "CREATE TABLE $states_table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				unique_id varchar(255) DEFAULT NULL,
				user_id mediumint(9) DEFAULT NULL,
				current_step mediumint(9),
				parameters longtext,
				steps longtext,
				status varchar(50),
				is_child tinyint(1) NOT NULL DEFAULT 0,
				PRIMARY KEY (id)
			) $charset_collate;";

		$sql_page_views = "CREATE TABLE $page_views_table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				product_id mediumint(9) DEFAULT NULL,
				category_id mediumint(9) DEFAULT NULL,
				views mediumint(9) DEFAULT 0,
				viewed_date date DEFAULT NULL,
				PRIMARY KEY (id)
			) $charset_collate;";

		$sql_click_data = "CREATE TABLE $click_data_table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				upsell_clicks int DEFAULT 0 NOT NULL,
				homepage_banner int DEFAULT 0 NOT NULL,
				clicked_date date NOT NULL,
				PRIMARY KEY (id)
			) $charset_collate;";

		$sql_recommended_loop_clicks = "CREATE TABLE $recommended_loop_clicks_table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				product_id mediumint(9) NOT NULL,
				clicks mediumint(9) NOT NULL,
				clicked_date date NOT NULL,
				PRIMARY KEY (id)
			) $charset_collate;";

		$sql_recommended_products = "CREATE TABLE $recommended_products_table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				product_id mediumint(9) NOT NULL,
				date_added DATETIME NOT NULL,
				PRIMARY KEY (id)
			) $charset_collate;";

		$sql_logger = "CREATE TABLE $logger_table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				workflow_id varchar(255) NOT NULL,
				user_id mediumint(9),
				step text NOT NULL,
				description text NOT NULL,
				step_name text NOT NULL,
				timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				PRIMARY KEY (id)
			) $charset_collate;";

		$sql_ab_tests = "CREATE TABLE $ab_tests_table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				selector varchar(255) NOT NULL,
				type varchar(100) NOT NULL,
				goal_type varchar(100) NOT NULL,
				variation_a text NOT NULL,
				variation_b text NOT NULL,
				variation_a_count mediumint(9) NOT NULL DEFAULT 0,
				variation_b_count mediumint(9) NOT NULL DEFAULT 0,
				status varchar(50) NOT NULL,
				product_id mediumint(9),
				start_date date NOT NULL,
				end_date date NOT NULL,
				PRIMARY KEY (id)
			) $charset_collate;";

		// SQL to create the new 'trendpilot_workflow_templates' table
		$sql_workflow_templates = "CREATE TABLE $workflow_templates_table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				user_id bigint(20) UNSIGNED NOT NULL,
				unique_id varchar(32) NOT NULL,
				name varchar(255) NOT NULL,
				JSON longtext NOT NULL,
				created datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
				modified datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
				PRIMARY KEY  (id),
				KEY user_id (user_id),
				UNIQUE KEY unique_id (unique_id)
			) $charset_collate;";

		// Include WordPress upgrade library
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		// Run dbDelta for the table creation
		dbDelta( $sql_workflows );
		dbDelta( $sql_states );
		dbDelta( $sql_page_views );
		dbDelta( $sql_click_data );
		dbDelta( $sql_recommended_loop_clicks );
		dbDelta( $sql_recommended_products );
		dbDelta( $sql_logger );
		dbDelta( $sql_ab_tests );
		dbDelta( $sql_workflow_templates );

		// Set the default value for 'aetp_cron_job_time' if not already set
		if ( get_option( 'aetp_cron_job_time' ) === false ) {
			update_option( 'aetp_cron_job_time', '00:00' );
		}

		// Call the methods to import data from CSV files
		self::import_workflow_templates();
		self::import_workflows();

		// Flush the rewrite rules
		flush_rewrite_rules();

		update_option( 'woocommerce_default_catalog_orderby', 'recommended' );
	}

	/**
	 * Imports workflow templates from CSV file into the table, using pre-defined JSON constants.
	 */
	private static function import_workflow_templates() {
		global $wpdb;

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have permission to perform this action.', 'automation-engine-trendpilot' ) );
		}

		// File path for workflow templates CSV
		$workflow_templates_csv = AETRENDPILOT_PLUGIN_PATH . 'assets/data/default_workflow_templates.csv';

		// Ensure the CSV exists
		if ( file_exists( $workflow_templates_csv ) ) {
			// Open the CSV file for reading
			if ( ( $handle = fopen( $workflow_templates_csv, 'r' ) ) !== false ) {

				fgetcsv( $handle );

				// Get the current user ID
				$current_user_id = get_current_user_id();

				// Read each line as a CSV row
				while ( ( $data = fgetcsv( $handle ) ) !== false ) {
					$unique_id = $data[2];
					$name = $data[3];

					// Retrieve the JSON from the constant array using the unique ID
					$json = AETP_DEFAULT_WORKFLOW_TEMPLATES[ $unique_id ] ?? null;

					$created = $data[5];
					$modified = $data[6];

					// Check if the unique_id already exists in the database
					$existing = $wpdb->get_var( $wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->prefix}trendpilot_workflow_templates WHERE unique_id = %s",
						$unique_id
					) );

					if ( $existing == 0 && $json !== null ) { // Only insert if it doesn't exist
						// Insert each row into the database
						$wpdb->insert(
							$wpdb->prefix . 'trendpilot_workflow_templates',
							[ 
								'user_id' => $current_user_id,
								'unique_id' => $unique_id,
								'name' => $name,
								'JSON' => $json,
								'created' => $created,
								'modified' => $modified,
							],
							[ '%d', '%s', '%s', '%s', '%s', '%s' ]
						);
					}
				}
				fclose( $handle );
			}
		}
	}


	/**
	 * Imports workflows from CSV file into the table.
	 */
	private static function import_workflows() {
		global $wpdb;

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have permission to perform this action.', 'automation-engine-trendpilot' ) );
		}

		// File path for workflows CSV
		$workflows_csv = AETRENDPILOT_PLUGIN_PATH . 'assets/data/default_workflows.csv';

		// Import workflows
		if ( file_exists( $workflows_csv ) ) {
			if ( ( $handle = fopen( $workflows_csv, 'r' ) ) !== false ) {
				// Skip the first row if there is a header
				// fgetcsv( $handle );

				// Insert each row into the database
				while ( ( $data = fgetcsv( $handle ) ) !== false ) {
					$unique_id = $data[1];

					// Check if the unique_id already exists in the database
					$existing = $wpdb->get_var( $wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->prefix}trendpilot_automation_engine_workflows WHERE unique_id = %s",
						$unique_id
					) );

					if ( $existing == 0 ) { // Only insert if it doesn't exist
						$wpdb->insert(
							$wpdb->prefix . 'trendpilot_automation_engine_workflows',
							[ 
								'unique_id' => $unique_id,
								'name' => $data[2],
								'steps' => $data[3],
								'is_repeat' => intval( $data[4] ),
								'status' => $data[5],
								'created_at' => $data[6],
								'updated_at' => $data[7],
							],
							[ '%s', '%s', '%s', '%d', '%s', '%s', '%s' ]  // Field formats
						);
					}
				}
				fclose( $handle );
			}
		}
	}

	// Deactivation method
	public static function deactivate() {
		// Remove custom rewrite rules
		flush_rewrite_rules();
	}

}