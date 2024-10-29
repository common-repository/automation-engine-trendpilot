<?php
namespace AETrendpilot;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class AETrendpilotLoader {

	public function run() {

		if ( is_admin() ) {
			$this->register_admin_hooks();
		}

		$this->register_function_hooks();
		$this->register_feature_hooks();
		$this->load_files();


	}

	private function load_files() {
		// Load only specific files from the root of the 'includes' folder
		$specific_files = [ 
			AETRENDPILOT_PLUGIN_PATH . 'includes/analytics-page-functions.php',
			AETRENDPILOT_PLUGIN_PATH . 'includes/api_requests.php',
			AETRENDPILOT_PLUGIN_PATH . 'includes/functions.php',
			AETRENDPILOT_PLUGIN_PATH . 'assets/data/default_templates.php',
		];

		foreach ( $specific_files as $file ) {
			if ( file_exists( $file ) ) {
				require_once $file;
			} else {
				error_log( "File not found: " . $file );
			}
		}

		// Continue to load all PHP files from subdirectories of 'includes'
		foreach ( glob( AETRENDPILOT_PLUGIN_PATH . 'includes/**/*.php' ) as $file ) {
			require_once $file;
		}
	}

	private function register_feature_hooks() {

		$features = [ 
			'AETrendpilot\AETrendpilotWorkflow',
			'AETrendpilot\AETrendpilotCron',
			'AETrendpilot\AETrendpilotAutomationEngine',
			'AETrendpilot\AETrendpilotRecommended',
			'AETrendpilot\TrendpilotUpsell',
			'AETrendpilot\TrendpilotBadge',
			'AETrendpilot\TrendpilotTopBar',
			'AETrendpilot\TrendpilotProductDisplay',
		];

		foreach ( $features as $feature ) {
			if ( class_exists( $feature ) ) {

				$instance = new $feature();

				if ( method_exists( $instance, 'registerHooks' ) ) {
					$instance->registerHooks();
				}
			}
		}
	}


	public function register_function_hooks() {
		add_action( 'template_redirect', 'tpae_record_page_view' );
	}

	public function register_admin_hooks() {
		$tpAdmin = new AETrendpilotAdmin();
		$tpAdmin->registerHooks();
	}

}