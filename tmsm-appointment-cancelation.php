<?php
/**
 * Plugin Name: TMSM Appointment Cancelation
 * Plugin URI: https://github.com/thermesmarins/tmsm-appointment-cancelation
 * Description: Allows users to cancel appointments made by phone through the Aquos API integration.
 * Version: 1.0.5
 * Author: Arnaud Flament
 * Author URI: https://github.com/ArnaudFlament35
 * License: GPL v2 or later
 * Text Domain: tmsm-appointment-cancelation
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * Github Plugin URI: http://github.com/thermesmarins/tmsm-appointment-cancelation
 * Github Branch:     master
 */

// Prevent direct access to this file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'TMSM_APPOINTMENT_CANCELATION_VERSION', '1.0.5' );
define( 'TMSM_APPOINTMENT_CANCELATION_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TMSM_APPOINTMENT_CANCELATION_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'TMSM_APPOINTMENT_CANCELATION_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin initialization function
 */
function run_tmsm_appointment_cancelation() {
	// Check if required files exist before including them
	$required_files = array(
		'includes/class-tmsm-appointment-cancelation-activator.php',
		'includes/class-tmsm-appointment-cancelation-deactivator.php',
		'includes/class-tmsm-appointment-cancelation.php'
	);

	foreach ( $required_files as $file ) {
		$file_path = TMSM_APPOINTMENT_CANCELATION_PLUGIN_DIR . $file;
		if ( ! file_exists( $file_path ) ) {
			add_action( 'admin_notices', function() use ( $file ) {
				echo '<div class="notice notice-error"><p>';
				printf(
					/* translators: %s: missing file name */
					esc_html__( 'TMSM Appointment Cancelation plugin error: Required file %s is missing.', 'tmsm-appointment-cancelation' ),
					esc_html( $file )
				);
				echo '</p></div>';
			});
			return;
		}
	}

	// Include required files
	require_once TMSM_APPOINTMENT_CANCELATION_PLUGIN_DIR . 'includes/class-tmsm-appointment-cancelation-activator.php';
	require_once TMSM_APPOINTMENT_CANCELATION_PLUGIN_DIR . 'includes/class-tmsm-appointment-cancelation-deactivator.php';
	require_once TMSM_APPOINTMENT_CANCELATION_PLUGIN_DIR . 'includes/class-tmsm-appointment-cancelation.php';

	// Register activation and deactivation hooks
	register_activation_hook( __FILE__, array( 'Tmsm_Appointment_Cancelation_Activator', 'activate' ) );
	register_deactivation_hook( __FILE__, array( 'Tmsm_Appointment_Cancelation_Deactivator', 'deactivate' ) );

	// Initialize the plugin
	try {
		$plugin = new Tmsm_Appointment_Cancelation();
		$plugin->run();
	} catch ( Exception $e ) {
		// Log error and display admin notice
		error_log( 'TMSM Appointment Cancelation plugin initialization error: ' . $e->getMessage() );
		add_action( 'admin_notices', function() use ( $e ) {
			echo '<div class="notice notice-error"><p>';
			printf(
				/* translators: %s: error message */
				esc_html__( 'TMSM Appointment Cancelation plugin error: %s', 'tmsm-appointment-cancelation' ),
				esc_html( $e->getMessage() )
			);
			echo '</p></div>';
		});
	}
}

// Initialize the plugin
run_tmsm_appointment_cancelation();
?>