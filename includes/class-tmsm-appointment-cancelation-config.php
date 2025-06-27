<?php

/**
 * Plugin configuration management class
 *
 * @link       https://github.com/ArnaudFlament35/tmsm-appointment-cancelation
 * @since      1.0.0
 *
 * @package    Tmsm_Appointment_Cancelation
 * @subpackage Tmsm_Appointment_Cancelation/includes
 */

/**
 * Plugin configuration management.
 *
 * This class handles all plugin configuration settings, default values,
 * and provides methods to retrieve and update settings.
 *
 * @since      1.0.0
 * @package    Tmsm_Appointment_Cancelation
 * @subpackage Tmsm_Appointment_Cancelation/includes
 * @author     Arnaud Flament <aflament.dev@gmail.com>
 */

// Prevent direct access to this file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tmsm_Appointment_Cancelation_Config {

	/**
	 * Plugin option prefix
	 *
	 * @since    1.0.0
	 * @var      string
	 */
	const OPTION_PREFIX = 'tmsm_appointment_cancelation_';

	/**
	 * Default configuration values
	 *
	 * @since    1.0.0
	 * @var      array
	 */
	private static $defaults = array(
		// API Configuration
		'api_endpoint' => '',
		'api_key' => '',
		'api_timeout' => 30,
		'api_retry_attempts' => 3,

		// Security Configuration
		'token_expiry' => 24, // hours
		'token_length' => 32,
		'max_attempts_per_hour' => 5,
		'enable_rate_limiting' => true,

		// Email Configuration
		'admin_email' => '',
		'enable_notifications' => true,
		'email_from_name' => '',
		'email_from_address' => '',

		// Display Configuration
		'page_title' => 'Appointment Cancellation',
		'success_message' => 'Your appointment has been successfully cancelled.',
		'error_message' => 'An error occurred while cancelling your appointment.',
		'expired_message' => 'This cancellation link has expired or is invalid.',

		// Debug Configuration
		'debug_mode' => false,
		'log_level' => 'error', // error, warning, info, debug

		// Database Configuration
		'cleanup_expired_tokens_interval' => 24, // hours
		'retain_cancellation_logs_days' => 90,
	);

	/**
	 * Get a configuration value
	 *
	 * @since    1.0.0
	 * @param    string $key Configuration key.
	 * @param    mixed  $default Default value if key doesn't exist.
	 * @return   mixed  Configuration value.
	 */
	public static function get( $key, $default = null ) {
		$option_name = self::OPTION_PREFIX . $key;
		$value = get_option( $option_name, null );

		// If no value is set, use default
		if ( null === $value ) {
			$value = isset( self::$defaults[ $key ] ) ? self::$defaults[ $key ] : $default;
		}

		return $value;
	}

	/**
	 * Set a configuration value
	 *
	 * @since    1.0.0
	 * @param    string $key Configuration key.
	 * @param    mixed  $value Configuration value.
	 * @return   bool   True on success, false on failure.
	 */
	public static function set( $key, $value ) {
		$option_name = self::OPTION_PREFIX . $key;
		return update_option( $option_name, $value );
	}

	/**
	 * Delete a configuration value
	 *
	 * @since    1.0.0
	 * @param    string $key Configuration key.
	 * @return   bool   True on success, false on failure.
	 */
	public static function delete( $key ) {
		$option_name = self::OPTION_PREFIX . $key;
		return delete_option( $option_name );
	}

	/**
	 * Get all configuration values
	 *
	 * @since    1.0.0
	 * @return   array  All configuration values.
	 */
	public static function get_all() {
		$config = array();
		
		foreach ( array_keys( self::$defaults ) as $key ) {
			$config[ $key ] = self::get( $key );
		}

		return $config;
	}

	/**
	 * Set multiple configuration values
	 *
	 * @since    1.0.0
	 * @param    array $config Configuration array.
	 * @return   bool   True on success, false on failure.
	 */
	public static function set_multiple( $config ) {
		$success = true;

		foreach ( $config as $key => $value ) {
			if ( ! self::set( $key, $value ) ) {
				$success = false;
			}
		}

		return $success;
	}

	/**
	 * Reset configuration to defaults
	 *
	 * @since    1.0.0
	 * @return   bool   True on success, false on failure.
	 */
	public static function reset_to_defaults() {
		return self::set_multiple( self::$defaults );
	}

	/**
	 * Get default values
	 *
	 * @since    1.0.0
	 * @return   array  Default configuration values.
	 */
	public static function get_defaults() {
		return self::$defaults;
	}

	/**
	 * Validate configuration values
	 *
	 * @since    1.0.0
	 * @param    array $config Configuration array to validate.
	 * @return   array  Array of validation errors.
	 */
	public static function validate( $config ) {
		$errors = array();

		// Validate API endpoint
		if ( ! empty( $config['api_endpoint'] ) && ! filter_var( $config['api_endpoint'], FILTER_VALIDATE_URL ) ) {
			$errors['api_endpoint'] = __( 'API endpoint must be a valid URL.', 'tmsm-appointment-cancelation' );
		}

		// Validate email addresses
		if ( ! empty( $config['admin_email'] ) && ! is_email( $config['admin_email'] ) ) {
			$errors['admin_email'] = __( 'Admin email must be a valid email address.', 'tmsm-appointment-cancelation' );
		}

		if ( ! empty( $config['email_from_address'] ) && ! is_email( $config['email_from_address'] ) ) {
			$errors['email_from_address'] = __( 'From email address must be a valid email address.', 'tmsm-appointment-cancelation' );
		}

		// Validate numeric values
		$numeric_fields = array( 'api_timeout', 'api_retry_attempts', 'token_expiry', 'token_length', 'max_attempts_per_hour' );
		foreach ( $numeric_fields as $field ) {
			if ( isset( $config[ $field ] ) && ( ! is_numeric( $config[ $field ] ) || $config[ $field ] < 0 ) ) {
				$errors[ $field ] = sprintf( __( '%s must be a positive number.', 'tmsm-appointment-cancelation' ), ucfirst( str_replace( '_', ' ', $field ) ) );
			}
		}

		// Validate boolean values
		$boolean_fields = array( 'enable_notifications', 'enable_rate_limiting', 'debug_mode' );
		foreach ( $boolean_fields as $field ) {
			if ( isset( $config[ $field ] ) && ! is_bool( $config[ $field ] ) ) {
				$errors[ $field ] = sprintf( __( '%s must be a boolean value.', 'tmsm-appointment-cancelation' ), ucfirst( str_replace( '_', ' ', $field ) ) );
			}
		}

		return $errors;
	}

	/**
	 * Get configuration for admin settings page
	 *
	 * @since    1.0.0
	 * @return   array  Configuration sections for admin page.
	 */
	public static function get_admin_sections() {
		return array(
			'api' => array(
				'title' => __( 'API Configuration', 'tmsm-appointment-cancelation' ),
				'fields' => array(
					'api_endpoint' => array(
						'label' => __( 'API Endpoint', 'tmsm-appointment-cancelation' ),
						'type' => 'url',
						'description' => __( 'The Aquos API endpoint URL.', 'tmsm-appointment-cancelation' ),
						'required' => true,
					),
					'api_key' => array(
						'label' => __( 'API Key', 'tmsm-appointment-cancelation' ),
						'type' => 'password',
						'description' => __( 'Your Aquos API authentication key.', 'tmsm-appointment-cancelation' ),
						'required' => true,
					),
					'api_timeout' => array(
						'label' => __( 'API Timeout (seconds)', 'tmsm-appointment-cancelation' ),
						'type' => 'number',
						'description' => __( 'Timeout for API requests in seconds.', 'tmsm-appointment-cancelation' ),
						'min' => 5,
						'max' => 120,
					),
				),
			),
			'security' => array(
				'title' => __( 'Security Settings', 'tmsm-appointment-cancelation' ),
				'fields' => array(
					'token_expiry' => array(
						'label' => __( 'Token Expiry (hours)', 'tmsm-appointment-cancelation' ),
						'type' => 'number',
						'description' => __( 'How long cancellation tokens remain valid.', 'tmsm-appointment-cancelation' ),
						'min' => 1,
						'max' => 168, // 1 week
					),
					'enable_rate_limiting' => array(
						'label' => __( 'Enable Rate Limiting', 'tmsm-appointment-cancelation' ),
						'type' => 'checkbox',
						'description' => __( 'Limit the number of cancellation attempts per hour.', 'tmsm-appointment-cancelation' ),
					),
					'max_attempts_per_hour' => array(
						'label' => __( 'Max Attempts per Hour', 'tmsm-appointment-cancelation' ),
						'type' => 'number',
						'description' => __( 'Maximum cancellation attempts allowed per hour per IP.', 'tmsm-appointment-cancelation' ),
						'min' => 1,
						'max' => 100,
					),
				),
			),
			'email' => array(
				'title' => __( 'Email Settings', 'tmsm-appointment-cancelation' ),
				'fields' => array(
					'admin_email' => array(
						'label' => __( 'Admin Email', 'tmsm-appointment-cancelation' ),
						'type' => 'email',
						'description' => __( 'Email address for admin notifications.', 'tmsm-appointment-cancelation' ),
					),
					'enable_notifications' => array(
						'label' => __( 'Enable Email Notifications', 'tmsm-appointment-cancelation' ),
						'type' => 'checkbox',
						'description' => __( 'Send email notifications for cancellations.', 'tmsm-appointment-cancelation' ),
					),
					'email_from_name' => array(
						'label' => __( 'From Name', 'tmsm-appointment-cancelation' ),
						'type' => 'text',
						'description' => __( 'Name to use in email from field.', 'tmsm-appointment-cancelation' ),
					),
					'email_from_address' => array(
						'label' => __( 'From Email', 'tmsm-appointment-cancelation' ),
						'type' => 'email',
						'description' => __( 'Email address to use in from field.', 'tmsm-appointment-cancelation' ),
					),
				),
			),
			'display' => array(
				'title' => __( 'Display Settings', 'tmsm-appointment-cancelation' ),
				'fields' => array(
					'page_title' => array(
						'label' => __( 'Page Title', 'tmsm-appointment-cancelation' ),
						'type' => 'text',
						'description' => __( 'Title displayed on the cancellation page.', 'tmsm-appointment-cancelation' ),
					),
					'success_message' => array(
						'label' => __( 'Success Message', 'tmsm-appointment-cancelation' ),
						'type' => 'textarea',
						'description' => __( 'Message displayed when cancellation is successful.', 'tmsm-appointment-cancelation' ),
					),
					'error_message' => array(
						'label' => __( 'Error Message', 'tmsm-appointment-cancelation' ),
						'type' => 'textarea',
						'description' => __( 'Message displayed when cancellation fails.', 'tmsm-appointment-cancelation' ),
					),
				),
			),
			'debug' => array(
				'title' => __( 'Debug Settings', 'tmsm-appointment-cancelation' ),
				'fields' => array(
					'debug_mode' => array(
						'label' => __( 'Enable Debug Mode', 'tmsm-appointment-cancelation' ),
						'type' => 'checkbox',
						'description' => __( 'Enable detailed logging for debugging.', 'tmsm-appointment-cancelation' ),
					),
					'log_level' => array(
						'label' => __( 'Log Level', 'tmsm-appointment-cancelation' ),
						'type' => 'select',
						'options' => array(
							'error' => __( 'Error', 'tmsm-appointment-cancelation' ),
							'warning' => __( 'Warning', 'tmsm-appointment-cancelation' ),
							'info' => __( 'Info', 'tmsm-appointment-cancelation' ),
							'debug' => __( 'Debug', 'tmsm-appointment-cancelation' ),
						),
						'description' => __( 'Minimum log level to record.', 'tmsm-appointment-cancelation' ),
					),
				),
			),
		);
	}
} 