<?php

/**
 * Fired during plugin activation
 *
 * @link       https://github.com/ArnaudFlament35/tmsm-appointment-cancelation
 * @since      1.0.0
 *
 * @package    Tmsm_Appointment_Cancelation
 * @subpackage Tmsm_Appointment_Cancelation/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Tmsm_Appointment_Cancelation
 * @subpackage Tmsm_Appointment_Cancelation/includes
 * @author     Arnaud Flament <aflament.dev@gmail.com>
 */
class Tmsm_Appointment_Cancelation_Activator {

	/**
	 * Activate the plugin.
	 *
	 * This method is called when the plugin is activated. It handles
	 * initialization tasks such as creating database tables, setting
	 * default options, and checking system requirements.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public static function activate() {
		// Check system requirements
		self::check_requirements();

		// Set default options
		self::set_default_options();

		// Create database tables if needed
		self::create_tables();

		// Set activation flag
		update_option( 'tmsm_appointment_cancelation_activated', true );
		update_option( 'tmsm_appointment_cancelation_version', TMSM_APPOINTMENT_CANCELATION_VERSION );

		// Log activation
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'TMSM Appointment Cancelation plugin activated successfully.' );
		}
	}

	/**
	 * Check system requirements before activation.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	private static function check_requirements() {
		$errors = array();

		// Check WordPress version
		if ( version_compare( get_bloginfo( 'version' ), '5.0', '<' ) ) {
			$errors[] = __( 'WordPress 5.0 or higher is required.', 'tmsm-appointment-cancelation' );
		}

		// Check PHP version
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			$errors[] = __( 'PHP 7.4 or higher is required.', 'tmsm-appointment-cancelation' );
		}

		// Check if required functions exist
		if ( ! function_exists( 'curl_init' ) ) {
			$errors[] = __( 'cURL extension is required for API communication.', 'tmsm-appointment-cancelation' );
		}

		// If there are errors, deactivate the plugin and show error message
		if ( ! empty( $errors ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die(
				'<h1>' . __( 'Plugin Activation Error', 'tmsm-appointment-cancelation' ) . '</h1>' .
				'<p>' . __( 'The TMSM Appointment Cancelation plugin could not be activated due to the following issues:', 'tmsm-appointment-cancelation' ) . '</p>' .
				'<ul><li>' . implode( '</li><li>', $errors ) . '</li></ul>' .
				'<p><a href="' . admin_url( 'plugins.php' ) . '">' . __( 'Return to plugins page', 'tmsm-appointment-cancelation' ) . '</a></p>'
			);
		}
	}

	/**
	 * Set default plugin options.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	private static function set_default_options() {
		$default_options = array(
			'tmsm_appointment_cancelation_api_endpoint' => '',
			'tmsm_appointment_cancelation_api_key' => '',
			'tmsm_appointment_cancelation_token_expiry' => 24, // hours
			'tmsm_appointment_cancelation_admin_email' => get_option( 'admin_email' ),
			'tmsm_appointment_cancelation_enable_notifications' => true,
			'tmsm_appointment_cancelation_debug_mode' => false,
		);

		foreach ( $default_options as $option_name => $default_value ) {
			if ( false === get_option( $option_name ) ) {
				add_option( $option_name, $default_value );
			}
		}
	}

	/**
	 * Create database tables if needed.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	private static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Table for storing cancellation requests
		$table_name = $wpdb->prefix . 'tmsm_appointment_cancellations';

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			appointment_id varchar(255) NOT NULL,
			customer_email varchar(255) NOT NULL,
			cancellation_date datetime DEFAULT CURRENT_TIMESTAMP,
			status varchar(50) DEFAULT 'pending',
			api_response text,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY appointment_id (appointment_id),
			KEY status (status)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
