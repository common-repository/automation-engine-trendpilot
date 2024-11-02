<?php

namespace AETrendpilot;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AETrendpilotAdmin {

	public function __construct() {
		$this->register_settings();
	}

	public function registerHooks() {
		add_action( 'admin_menu', [ $this, 'add_pro_main_menu' ] );
		add_action( 'admin_menu', [ $this, 'add_pro_submenus' ] );
		add_action( 'admin_init', [ $this, 'handle_pro_post_requests' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_styles' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_automation_engine_scripts' ] );
	}

	// Register settings method
	public function register_settings() {
		register_setting(
			'aetp_options_group',
			'tpae_analytics_period',
			array(
				'type' => 'integer',
				'sanitize_callback' => 'absint',
				'default' => 7,
				'capability' => 'manage_options'
			)
		);

		register_setting( 'tpae_flush_settings', 'tpae_page_view_flush_period', [ 
			'sanitize_callback' => 'absint',
			'capability' => 'manage_options'
		] );
		register_setting( 'tpae_flush_settings', 'tpae_recommended_data_flush_period', [ 
			'sanitize_callback' => 'absint',
			'capability' => 'manage_options'
		] );
		register_setting( 'tpae_recommended_settings', 'tpae_total_recommended_products', [ 
			'sanitize_callback' => 'absint',
			'capability' => 'manage_options'
		] );

		register_setting( 'tpae_flush_settings', 'aetp_click_data_flush_period', [ 
			'sanitize_callback' => 'absint',
			'capability' => 'manage_options'
		] );
		register_setting( 'aetp_upsell_settings', 'aetp_enable_upsell_page', [ 
			'sanitize_callback' => 'sanitize_text_field',
			'capability' => 'manage_options'
		] );
		register_setting( 'aetp_upsell_settings', 'aetp_upsell_product_id', [ 
			'sanitize_callback' => 'absint',
			'capability' => 'manage_options'
		] );

		register_setting( 'aetp_topbar_settings', 'aetp_top_bar_message', [ 
			'sanitize_callback' => 'sanitize_text_field',
			'capability' => 'manage_options'
		] );
		register_setting( 'aetp_topbar_settings', 'aetp_top_bar_background_color', [ 
			'sanitize_callback' => 'sanitize_hex_color',
			'capability' => 'manage_options'
		] );
		register_setting( 'aetp_topbar_settings', 'aetp_top_bar_text_color', [ 
			'sanitize_callback' => 'sanitize_hex_color',
			'capability' => 'manage_options'
		] );
		register_setting( 'aetp_topbar_settings', 'aetp_top_bar_active', [ 
			'sanitize_callback' => array( $this, 'my_plugin_sanitize_checkbox' ),
			'capability' => 'manage_options'
		] );

		register_setting( 'aetp_badge_settings', 'aetp_badge_font_size', [ 
			'sanitize_callback' => 'absint',
			'default' => 12,
			'capability' => 'manage_options'
		] );
		register_setting( 'aetp_badge_settings', 'aetp_badge_color', [ 
			'sanitize_callback' => 'sanitize_hex_color',
			'default' => '#FFFFFF',
			'capability' => 'manage_options'
		] );
		register_setting( 'aetp_badge_settings', 'aetp_badge_border_radius', [ 
			'sanitize_callback' => 'absint',
			'default' => 0,
			'capability' => 'manage_options'
		] );
		register_setting( 'aetp_badge_settings', 'aetp_badge_font_color', [ 
			'sanitize_callback' => 'sanitize_hex_color',
			'default' => '#000000',
			'capability' => 'manage_options'
		] );
		register_setting( 'aetp_badge_settings', 'aetp_enable_badges', [ 
			'sanitize_callback' => array( $this, 'my_plugin_sanitize_checkbox' ),
			'default' => 1,
			'capability' => 'manage_options'
		] );

		$this->ensure_tpae_analytics_period_is_set();
	}

	public function handle_pro_post_requests() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have sufficient permissions to access this page.' );
		}

		if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			if ( isset( $_POST['aetp_cron_job_time'], $_POST['aetp_update_cron_time_nonce'] ) && check_admin_referer( 'aetp_update_cron_time_action', 'aetp_update_cron_time_nonce' ) ) {
				// Process updating the cron job time
				$new_time = sanitize_text_field( wp_unslash( $_POST['aetp_cron_job_time'] ) );
				list( $hour, $minute ) = explode( ':', $new_time );

				// Convert local time to UTC before scheduling
				$timezone_string = get_option( 'timezone_string' );
				$timezone = new \DateTimeZone( $timezone_string ? $timezone_string : 'UTC' );

				$date = new \DateTime( "today {$hour}:{$minute}", $timezone );
				$date->setTimezone( new \DateTimeZone( 'UTC' ) );
				$next_run_time = $date->getTimestamp();

				if ( $next_run_time < time() ) {
					$date->modify( '+1 day' );
					$next_run_time = $date->getTimestamp();
				}

				// Retrieve the current scheduled time for the cron job
				$current_timestamp = wp_next_scheduled( 'tpap_daily_workflow_checker' );

				// Unschedule the existing cron job if it exists
				if ( $current_timestamp ) {
					wp_unschedule_event( $current_timestamp, 'tpap_daily_workflow_checker' );
				}

				// Schedule the new cron job
				wp_schedule_event( $next_run_time, 'daily', 'tpap_daily_workflow_checker' );

				// Update the cron_job_time in the options table
				update_option( 'aetp_cron_job_time', $new_time );
			}

			if ( isset( $_POST['aetp_trigger_daily_cron'], $_POST['aetp_trigger_cron_nonce'] ) && check_admin_referer( 'aetp_trigger_cron_action', 'aetp_trigger_cron_nonce' ) ) {
				// Trigger the cron job manually
				do_action( 'tpap_daily_workflow_checker' );
				echo '<div class="updated"><p>Daily cron job has been manually triggered.</p></div>';
			}

			// Handle flush page views
			if ( isset( $_POST['aetp_flush_page_views'] ) && check_admin_referer( 'aetp_flush_page_views_action', 'aetp_flush_page_views_nonce' ) ) {
				tpae_manual_flush_page_views();
			}

			// Handle flush click data
			if ( isset( $_POST['aetp_flush_recommended_data'] ) && check_admin_referer( 'aetp_flush_recommended_data_action', 'aetp_flush_recommended_data_nonce' ) ) {
				tpae_manual_flush_recommended_data();
			}

			// Handle add to recommended product
			if ( ! empty( $_POST['aetp_add_to_recommended_product_id'] ) && check_admin_referer( 'aetp_add_to_recommended_nonce_action', 'aetp_add_to_recommended_nonce' ) ) {
				$product_id_to_add = sanitize_text_field( wp_unslash( $_POST['aetp_add_to_recommended_product_id'] ) );
				$recommended = new AETrendpilotRecommended();
				$recommended->add_to_recommended( $product_id_to_add );
				echo '<div class="updated"><p>Product added to the recommended list.</p></div>';
			}

			// Check and handle the enable/disable recommended section
			if ( isset( $_POST['tpae_enable_disable_recommended'] ) ) {
				$enable_disable_recommended = intval( $_POST['tpae_enable_disable_recommended'] );
				update_option( 'tpae_enable_disable_recommended', $enable_disable_recommended ? 1 : 0 );
			}

			// Handle the total recommended products setting
			if ( isset( $_POST['tpae_total_recommended_products'] ) ) {
				$total_recommended = intval( $_POST['tpae_total_recommended_products'] );
				update_option( 'tpae_total_recommended_products', $total_recommended );
			}

			// NEW: Handle the sorting method for remaining products
			if ( isset( $_POST['tpae_remaining_products_orderby'] ) ) {
				$remaining_orderby = sanitize_text_field( wp_unslash( $_POST['tpae_remaining_products_orderby'] ) );
				update_option( 'tpae_remaining_products_orderby', $remaining_orderby );
			}

			// Handle flush click data
			if ( isset( $_POST['aetp_flush_click_data'] ) && check_admin_referer( 'aetp_flush_click_data_action', 'aetp_flush_click_data_nonce' ) ) {
				aetp_manual_flush_click_data();
			}
		}
	}
	// Add main menu
	public function add_pro_main_menu() {

		// The icon in Base64 format
		$icon_base64 = 'PHN2ZyB2ZXJzaW9uPSIxLjIiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgdmlld0JveD0iMCAwIDM4MCA0MDEiIHdpZHRoPSIxOSIgaGVpZ2h0PSIyMCI+CiAgPHBhdGggZmlsbD0iY3VycmVudENvbG9yIiBkPSJtMjI1LjkgMjM0YzAtNyA1LjYtMTIuNiAxMi42LTEyLjYgNi45IDAgMTIuNiA1LjYgMTIuNiAxMi42djQ3LjhjMCAzMy40LTI3LjEgNjAuNC02MC41IDYwLjQtMzMuMyAwLTYwLjQtMjctNjAuNC02MC40IDAtNTkuOCA0OC41LTEwOC4zIDEwOC4zLTEwOC4zaDIzLjljMzEuMyAwIDQ3LTM4IDI0LjktNjAuMS02LjQtNi40LTE1LjItMTAuMy0yNC45LTEwLjNoLTE0My41Yy0zMS4zIDAtNDcgMzgtMjQuOSA2MC4xIDYuNCA2LjQgMTUuMiAxMC4zIDI0LjkgMTAuM2gyMy45YzcgMCAxMi42IDUuNyAxMi42IDEyLjYgMCA3LTUuNiAxMi43LTEyLjYgMTIuN2gtMjMuOWMtMzMuNCAwLTYwLjQtMjcuMS02MC40LTYwLjUgMC0zMy40IDI3LTYwLjQgNjAuNC02MC40aDE0My41YzMzLjQgMCA2MC40IDI3IDYwLjQgNjAuNCAwIDMzLjQtMjcgNjAuNS02MC40IDYwLjVoLTIzLjljLTQ1LjkgMC04My4xIDM3LjEtODMuMSA4MyAwIDMxLjMgMzggNDcgNjAuMSAyNC45IDYuNC02LjQgMTAuNC0xNS4yIDEwLjQtMjQuOXoiLz4KPC9zdmc+Cg==';

		// The icon in the data URI scheme
		$icon_data_uri = 'data:image/svg+xml;base64,' . $icon_base64;

		// Main menu item
		add_menu_page(
			'Automation Engine by Trendpilot',   // Page title (what shows in header when viewing the page)
			'Automation Engine by Trendpilot',   // Menu title (sidebar label)
			'manage_options',                    // Capability
			'trendpilot_dashboard',              // Menu slug
			[ $this, 'trendpilot_dashboard_callback' ], // Callback function for the main page
			$icon_data_uri,
			3
		);

		// Explicitly add "Dashboard" as the first submenu item
		add_submenu_page(
			'trendpilot_dashboard',              // Parent slug
			'Dashboard',                         // Page title
			'Dashboard',                         // Menu title in sidebar
			'manage_options',                    // Capability
			'trendpilot_dashboard',              // Menu slug (same as main menu to show this by default)
			[ $this, 'trendpilot_dashboard_callback' ] // Callback function for Dashboard
		);
	}

	// Define other submenus
	public function add_pro_submenus() {
		$workflow = new AETrendpilotWorkflow();

		// Automations submenu
		add_submenu_page(
			'trendpilot_dashboard',              // Parent slug
			'Automations',                       // Page title
			'Automations',                       // Menu title in sidebar
			'manage_options',                    // Capability
			'aetp_automations',                  // Menu slug
			[ $this, 'aetp_automations_callback' ] // Callback function
		);

		// Analytics submenu
		add_submenu_page(
			'trendpilot_dashboard',              // Parent slug
			'Analytics',                         // Page title
			'Analytics',                         // Menu title
			'manage_options',                    // Capability
			'aetp_analytics',                    // Menu slug
			[ $this, 'aetp_analytics_callback' ] // Callback function
		);

		// Storefront Features submenu
		add_submenu_page(
			'trendpilot_dashboard',              // Parent slug
			'Storefront Features',               // Page title
			'Storefront Features',               // Menu title
			'manage_options',                    // Capability
			'aetp_feature_settings',             // Menu slug
			[ $this, 'my_plugin_display_settings_page' ] // Callback function
		);

		// Product Displays submenu
		add_submenu_page(
			'trendpilot_dashboard',              // Parent slug
			'Product Displays',                  // Page title
			'Product Displays',                  // Menu title
			'manage_options',                    // Capability
			'edit.php?post_type=tp_product_display' // Menu slug linking to custom post type list table
		);

		// Settings submenu
		add_submenu_page(
			'trendpilot_dashboard',              // Parent slug
			'Settings',                          // Page title
			'Settings',                          // Menu title
			'manage_options',                    // Capability
			'aetp_settings',                     // Menu slug
			[ $this, 'aetrendpilot_settings' ]   // Callback function
		);

		// Workflow Logs submenu
		add_submenu_page(
			'trendpilot_dashboard',              // Parent slug
			'Workflow Logs',                     // Page title
			'Workflow Logs',                     // Menu title
			'manage_options',                    // Capability
			'workflow_logs',                     // Menu slug
			[ $workflow, 'display_workflow_log' ] // Callback function
		);
	}

	// Callback function for the Dashboard page
	public function trendpilot_dashboard_callback() {

		wp_enqueue_style(
			'automation_engine_styles',
			plugin_dir_url( __FILE__ ) . '../admin/css/trendpilot-pro-signup.css',
			array(),
			'1.0'
		);

		wp_enqueue_style(
			'bootstrap-css',
			esc_url( AETRENDPILOT_PLUGIN_URL . 'admin/css/bootstrap.min.css' ),
			array(),
			'5.1.3'
		);

		include plugin_dir_path( __FILE__ ) . '../admin/trendpilot-pro-signup.php';

	}

	// Callback function for the 'Settings' submenu
	public function my_plugin_display_settings_page() {
		include plugin_dir_path( __FILE__ ) . '../admin/feature_settings.php';
	}

	// Callback function for the 'Analytics' submenu
	public function aetp_analytics_callback() {

		include plugin_dir_path( __FILE__ ) . '../admin/analytics-page.php';

	}

	public function aetp_automations_callback() {

		require_once plugin_dir_path( __FILE__ ) . '../admin/templates/trendpilot-automation-engine-view.php';

	}


	public function aetrendpilot_settings() {


		// Check for user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have sufficient permissions to access this page.' );
		}

		//include the main settings html
		require_once plugin_dir_path( __FILE__ ) . '../admin/templates/aetrendpilot-settings-view.php';

	}


	public function enqueue_automation_engine_scripts( $hook ) {

		if ( $hook == 'automation-engine-by-trendpilot_page_aetp_analytics' ) {

			wp_enqueue_style(
				'bootstrap-css',
				esc_url( AETRENDPILOT_PLUGIN_URL . 'admin/css/bootstrap.min.css' ),
				array(),
				'5.1.3'
			);

			//Enqueue Bootstrap CSS
			wp_enqueue_style(
				'custom-bootstrap-css',
				esc_url( AETRENDPILOT_PLUGIN_URL . 'admin/css/tp-bootstrap-custom.css' ),
				array(),
				'5.1.3'
			);

			// Enqueue Chart.js
			wp_enqueue_script(
				'chart-js',
				esc_url( AETRENDPILOT_PLUGIN_URL . 'admin/js/chart.js' ),
				array(),
				4.4,
				true
			);

		}

	}

	// Enqueue admin styles
	public function enqueue_admin_styles( $hook ) {

		// Array of slugs for the pages where the stylesheet should be enqueued
		$settings_page_slugs = [ 
			'automation-engine-by-trendpilot_page_aetp_settings',
			'toplevel_page_trendpilot_automation_engine',
			'toplevel_page_aetp_settings',
			'automation-engine-by-trendpilot_page_aetp_feature_settings',
		];

		// Check if we're on one of the correct pages
		if ( ! in_array( $hook, $settings_page_slugs ) ) {
			return;
		}

		// The URL to the stylesheet
		$css_url = plugin_dir_url( __FILE__ ) . '../admin/css/aetrendpilot-settings.css';

		// Enqueue the stylesheet
		wp_enqueue_style( 'tp_automation_engine_styles', $css_url );
	}

	public function ensure_tpae_analytics_period_is_set() {
		if ( false === get_option( 'tpae_analytics_period' ) ) {
			update_option( 'tpae_analytics_period', 7 );
		}
	}

	public function my_plugin_sanitize_checkbox( $input ) {
		// If checkbox is checked, return 1 (true), otherwise return 0 (false)
		return ( $input ? 1 : 0 );
	}

}