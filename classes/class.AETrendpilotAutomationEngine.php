<?php

namespace AETrendpilot;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AETrendpilotAutomationEngine {
	public function registerHooks() {
		add_action( 'admin_enqueue_scripts', [ $this, 'conditionally_enqueue_scripts' ] );
	}

	public function conditionally_enqueue_scripts( $hook ) {

		// Only enqueue for the Automations page
		if ( $hook == 'toplevel_page_aetp_automations' ) {

			// Load DOMPurify.min.js for sanitization
			wp_enqueue_script(
				'tp_automation_engine_scripts',
				AETRENDPILOT_PLUGIN_URL . 'admin/js/purify.min.js',
				array( 'jquery' ),
				'1.0',
				true
			);

			// Load Variable handler as a dependency
			wp_enqueue_script(
				'automation_engine_variables_handler',
				plugin_dir_url( __FILE__ ) . '../admin/automation_engine/constants/variables.js',
				array( 'jquery' ),
				'1.0',
				true
			);

			wp_localize_script(
				'automation_engine_variables_handler',
				'automationEngineAssets',
				array(
					'assetUrl' => esc_url( AETRENDPILOT_PLUGIN_URL . "admin/automation_engine/" ),
				)
			);

			// Conditionally enqueue Pro-specific scripts if the Pro plugin is active
			if ( AETRENDPILOT_PRO_ACTIVESTATUS ) {
				// Get the URL for the Pro plugin's directory dynamically
				$pro_plugin_url = plugins_url( '/', 'trendpilot-pro/trendpilot-pro.php' );

				wp_enqueue_script(
					'automation_engine_pro_actions',
					$pro_plugin_url . 'admin/pro-blocks/actionsMainBlockPro.js',
					array( 'automation_engine_script_main' ),
					'1.0',
					true
				);

				wp_enqueue_script(
					'automation_engine_pro_events',
					$pro_plugin_url . 'admin/pro-blocks/eventsMainBlockPro.js',
					array( 'automation_engine_script_main' ),
					'1.0',
					true
				);
			}

			// Load Constants before Main.js as a dependency
			wp_enqueue_script(
				'automation_engine_master_array',
				plugin_dir_url( __FILE__ ) . '../admin/automation_engine/constants/masterArray.js',
				array( 'jquery' ),
				'1.0',
				true
			);

			wp_enqueue_script(
				'automation_engine_events_constants',
				plugin_dir_url( __FILE__ ) . '../admin/automation_engine/constants/events.js',
				array( 'jquery' ),
				'1.0',
				true
			);

			wp_enqueue_script(
				'automation_engine_action_properties_constants',
				plugin_dir_url( __FILE__ ) . '../admin/automation_engine/constants/actionProperties.js',
				array( 'jquery' ),
				'1.0',
				true
			);

			wp_enqueue_script(
				'automation_engine_data_points',
				plugin_dir_url( __FILE__ ) . '../admin/automation_engine/constants/datapoints.js',
				array( 'jquery' ),
				'1.0',
				true
			);

			// Load Helper functions as a dependency
			wp_enqueue_script(
				'automation_engine_helper_functions',
				plugin_dir_url( __FILE__ ) . '../admin/automation_engine/helpers/helperFunctions.js',
				array( 'jquery' ),
				'1.0',
				true
			);

			// Load Main Ajax request handler as a dependency
			wp_enqueue_script(
				'automation_engine_ajax_requests_handler',
				plugin_dir_url( __FILE__ ) . '../admin/automation_engine/helpers/ajaxHandler.js',
				array( 'jquery' ),
				'1.0',
				true
			);

			// Load templates before Main.js as a dependency
			wp_enqueue_script(
				'automation_engine_events_main_block_template',
				plugin_dir_url( __FILE__ ) . '../admin/automation_engine/templates/eventsMainBlock.js',
				array( 'jquery' ),
				'1.0',
				true
			);

			wp_enqueue_script(
				'automation_engine_actions_main_block_template',
				plugin_dir_url( __FILE__ ) . '../admin/automation_engine/templates/actionsMainBlock.js',
				array( 'automation_engine_events_main_block_template' ),
				'1.0',
				true
			);

			// Load functions before Main.js as a dependency
			wp_enqueue_script(
				'automation_engine_tooltip_functions',
				plugin_dir_url( __FILE__ ) . '../admin/automation_engine/functions/tooltip.js',
				array( 'automation_engine_action_properties_constants' ),
				'1.0',
				true
			);

			wp_enqueue_script(
				'automation_engine_properties_panel',
				plugin_dir_url( __FILE__ ) . '../admin/automation_engine/functions/propertiesPanel.js',
				array( 'automation_engine_data_points' ),
				'1.0',
				true
			);

			wp_enqueue_script(
				'automation_engine_elements_snapping',
				plugin_dir_url( __FILE__ ) . '../admin/automation_engine/functions/elementSnapping.js',
				array( 'automation_engine_data_points' ),
				'1.0',
				true
			);

			wp_enqueue_script(
				'automation_engine_load_workflow_content',
				plugin_dir_url( __FILE__ ) . '../admin/automation_engine/functions/workflowEvents.js',
				array(
					'automation_engine_script_main',
					'automation_engine_ajax_requests_handler'
				),
				'1.0',
				true
			);

			wp_enqueue_script(
				'automation_engine_cron_time_functions',
				plugin_dir_url( __FILE__ ) . '../admin/automation_engine/functions/cronTime.js',
				array(
					'automation_engine_script_main',
					'automation_engine_ajax_requests_handler'
				),
				'1.0',
				true
			);

			wp_enqueue_script(
				'automation_engine_state_manager',
				plugin_dir_url( __FILE__ ) . '../admin/automation_engine/functions/stateManager.js',
				array(
					'automation_engine_script_main',
					'automation_engine_ajax_requests_handler'
				),
				'1.0',
				true
			);

			wp_enqueue_script(
				'automation_engine_block_actions',
				plugin_dir_url( __FILE__ ) . '../admin/automation_engine/functions/blockActions.js',
				array(
					'automation_engine_script_main',
					'automation_engine_ajax_requests_handler'
				),
				'1.0',
				true
			);



			wp_enqueue_script(
				'automation_engine_script_min',
				plugin_dir_url( __FILE__ ) . '../admin/automation_engine/flowy.min.js',
				array( 'jquery' ),
				'1.0',
				true
			);

			// Enqueue your styles
			wp_enqueue_style(
				'automation_engine_styles',
				plugin_dir_url( __FILE__ ) . '../admin/automation_engine/tp_automation_engine_styles.css',
				array(),
				'1.0'
			);

			// Enqueue the main script file
			wp_enqueue_script(
				'automation_engine_script_main',
				plugin_dir_url( __FILE__ ) . '../admin/automation_engine/main.js',
				array( 'jquery' ),
				'1.0',
				true
			);

			// Prepare dynamic data for localization
			$products = aetp_get_all_products_api();
			$product_categories = aetp_get_all_categories_api();
			$template_list = aetp_get_workflow_templates();

			// Prepare arrays for localization
			$bubble_dynamic_data = array( array( 'id' => '', 'name' => 'Product from previous step', 'price' => 100 ) );
			foreach ( $products as $product ) {
				$bubble_dynamic_data[] = array( 'id' => $product['id'], 'name' => $product['name'], 'price' => $product['price'] );
			}

			$product_categories_data = array( array( 'id' => '', 'name' => 'Category from previous step' ) );
			foreach ( $product_categories as $category ) {
				$product_categories_data[] = array( 'id' => $category['id'], 'name' => $category['name'] );
			}

			wp_localize_script(
				'automation_engine_script_main',
				'automationEngine',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce' => wp_create_nonce( 'automation_engine_nonce' ),
					'bubbleDynamicData' => $bubble_dynamic_data,
					'productCategories' => $product_categories_data,
					'templateList' => $template_list,
					'siteUrl' => esc_url( get_site_url() ),
					'AETPActiveStatus' => AETRENDPILOT_PRO_ACTIVESTATUS,
					'assetsUrl' => AETRENDPILOT_PLUGIN_URL . "admin/automation_engine/assets",
				)
			);



		}
	}
}
