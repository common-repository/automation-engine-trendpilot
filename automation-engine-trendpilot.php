<?php

/*
Plugin Name: Automation Engine for Woocommerce
Description: Product & store automation for Woocommerce
Author: Trendpilot
Version: 1.2.1
Plugin URI: https://trendpilot.io/downloads/
Author URI: https://trendpilot.io
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Text Domain: automation-engine-trendpilot
Requires Plugins: woocommerce
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( is_admin() && ! function_exists( 'is_plugin_active' ) ) {
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

define( 'AETRENDPILOT_VERSION', '1.2' );
define( 'AETRENDPILOT_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'AETRENDPILOT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Function to check if the Pro plugin is active.
 */
function aetp_pro_active_status() {
	if ( is_admin() && function_exists( 'is_plugin_active' ) ) {
		// Replace 'plugin-directory/plugin-file.php' with the actual Pro plugin path
		return is_plugin_active( 'trendpilot-pro/trendpilot-pro.php' );
	}
	return false;
}

/**
 * Hook to initialize Pro active status after plugins have loaded.
 */
add_action( 'admin_init', function () {
	define( 'AETRENDPILOT_PRO_ACTIVESTATUS', aetp_pro_active_status() );
}, 10 );

include_once AETRENDPILOT_PLUGIN_PATH . 'includes/ajax-handlers.php';

/**
 * Autoloader function for the AETrendpilot classes.
 */
function aetp_autoloader( $class ) {
	if ( strpos( $class, 'AETrendpilot\\' ) === 0 ) {
		$class = str_replace( 'AETrendpilot\\', '', $class );
		$file = plugin_dir_path( __FILE__ ) . 'classes/class.' . $class . '.php';
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
spl_autoload_register( 'aetp_autoloader' );

// Register activation and deactivation hooks
register_activation_hook( __FILE__, [ 'AETrendpilot\AETrendpilotPlugin', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'AETrendpilot\AETrendpilotPlugin', 'deactivate' ] );

// Initialize the plugin
AETrendpilot\AETrendpilotPlugin::init();