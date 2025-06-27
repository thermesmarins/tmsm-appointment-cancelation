<?php

/**
 * Plugin logging utility class
 *
 * @link       https://github.com/ArnaudFlament35/tmsm-appointment-cancelation
 * @since      1.0.0
 *
 * @package    Tmsm_Appointment_Cancelation
 * @subpackage Tmsm_Appointment_Cancelation/includes
 */

/**
 * Plugin logging utility.
 *
 * This class provides structured logging capabilities for the plugin,
 * including different log levels, file rotation, and admin interface integration.
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

class Tmsm_Appointment_Cancelation_Logger {

	/**
	 * Log levels
	 *
	 * @since    1.0.0
	 * @var      array
	 */
	const LOG_LEVELS = array(
		'error'   => 0,
		'warning' => 1,
		'info'    => 2,
		'debug'   => 3,
	);

	/**
	 * Log file path
	 *
	 * @since    1.0.0
	 * @var      string
	 */
	private static $log_file;

	/**
	 * Maximum log file size in bytes (5MB)
	 *
	 * @since    1.0.0
	 * @var      int
	 */
	const MAX_LOG_SIZE = 5242880;

	/**
	 * Maximum number of backup log files
	 *
	 * @since    1.0.0
	 * @var      int
	 */
	const MAX_BACKUP_FILES = 5;

	/**
	 * Initialize the logger
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public static function init() {
		$upload_dir = wp_upload_dir();
		$log_dir = $upload_dir['basedir'] . '/tmsm-appointment-cancelation/logs/';
		
		// Create log directory if it doesn't exist
		if ( ! is_dir( $log_dir ) ) {
			wp_mkdir_p( $log_dir );
			
			// Create .htaccess to protect log files
			$htaccess_content = "Order deny,allow\nDeny from all\n";
			file_put_contents( $log_dir . '.htaccess', $htaccess_content );
			
			// Create index.php to prevent directory listing
			file_put_contents( $log_dir . 'index.php', '<?php // Silence is golden' );
		}

		self::$log_file = $log_dir . 'appointment-cancelation.log';
	}

	/**
	 * Log a message
	 *
	 * @since    1.0.0
	 * @param    string $level   Log level (error, warning, info, debug).
	 * @param    string $message Log message.
	 * @param    array  $context Additional context data.
	 * @return   bool   True on success, false on failure.
	 */
	public static function log( $level, $message, $context = array() ) {
		// Initialize logger if not already done
		if ( ! self::$log_file ) {
			self::init();
		}

		// Check if logging is enabled and level is appropriate
		if ( ! self::should_log( $level ) ) {
			return false;
		}

		// Prepare log entry
		$log_entry = self::format_log_entry( $level, $message, $context );

		// Write to log file
		$result = self::write_to_file( $log_entry );

		// Also log to WordPress debug log if enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[TMSM Appointment Cancelation] ' . $log_entry );
		}

		return $result;
	}

	/**
	 * Log an error message
	 *
	 * @since    1.0.0
	 * @param    string $message Log message.
	 * @param    array  $context Additional context data.
	 * @return   bool   True on success, false on failure.
	 */
	public static function error( $message, $context = array() ) {
		return self::log( 'error', $message, $context );
	}

	/**
	 * Log a warning message
	 *
	 * @since    1.0.0
	 * @param    string $message Log message.
	 * @param    array  $context Additional context data.
	 * @return   bool   True on success, false on failure.
	 */
	public static function warning( $message, $context = array() ) {
		return self::log( 'warning', $message, $context );
	}

	/**
	 * Log an info message
	 *
	 * @since    1.0.0
	 * @param    string $message Log message.
	 * @param    array  $context Additional context data.
	 * @return   bool   True on success, false on failure.
	 */
	public static function info( $message, $context = array() ) {
		return self::log( 'info', $message, $context );
	}

	/**
	 * Log a debug message
	 *
	 * @since    1.0.0
	 * @param    string $message Log message.
	 * @param    array  $context Additional context data.
	 * @return   bool   True on success, false on failure.
	 */
	public static function debug( $message, $context = array() ) {
		return self::log( 'debug', $message, $context );
	}

	/**
	 * Check if we should log at the given level
	 *
	 * @since    1.0.0
	 * @param    string $level Log level.
	 * @return   bool   True if should log, false otherwise.
	 */
	private static function should_log( $level ) {
		// Check if debug mode is enabled
		if ( ! Tmsm_Appointment_Cancelation_Config::get( 'debug_mode', false ) ) {
			return false;
		}

		// Check log level
		$current_level = Tmsm_Appointment_Cancelation_Config::get( 'log_level', 'error' );
		$current_level_num = isset( self::LOG_LEVELS[ $current_level ] ) ? self::LOG_LEVELS[ $current_level ] : 0;
		$message_level_num = isset( self::LOG_LEVELS[ $level ] ) ? self::LOG_LEVELS[ $level ] : 0;

		return $message_level_num <= $current_level_num;
	}

	/**
	 * Format a log entry
	 *
	 * @since    1.0.0
	 * @param    string $level   Log level.
	 * @param    string $message Log message.
	 * @param    array  $context Additional context data.
	 * @return   string Formatted log entry.
	 */
	private static function format_log_entry( $level, $message, $context = array() ) {
		$timestamp = current_time( 'Y-m-d H:i:s' );
		$level_upper = strtoupper( $level );
		
		// Get user information
		$user_id = get_current_user_id();
		$user_info = $user_id ? "User ID: $user_id" : 'Guest';
		
		// Get request information
		$ip = self::get_client_ip();
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';
		
		// Format context data
		$context_str = '';
		if ( ! empty( $context ) ) {
			$context_str = ' | Context: ' . json_encode( $context );
		}

		return sprintf(
			'[%s] [%s] [%s] [IP: %s] [%s] %s%s',
			$timestamp,
			$level_upper,
			$user_info,
			$ip,
			$user_agent,
			$message,
			$context_str
		);
	}

	/**
	 * Write log entry to file
	 *
	 * @since    1.0.0
	 * @param    string $log_entry Log entry to write.
	 * @return   bool   True on success, false on failure.
	 */
	private static function write_to_file( $log_entry ) {
		// Check if we need to rotate the log file
		self::maybe_rotate_log();

		// Write to file
		$result = file_put_contents( self::$log_file, $log_entry . PHP_EOL, FILE_APPEND | LOCK_EX );

		return $result !== false;
	}

	/**
	 * Rotate log file if it's too large
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	private static function maybe_rotate_log() {
		if ( ! file_exists( self::$log_file ) ) {
			return;
		}

		$file_size = filesize( self::$log_file );
		if ( $file_size < self::MAX_LOG_SIZE ) {
			return;
		}

		// Rotate existing backup files
		for ( $i = self::MAX_BACKUP_FILES - 1; $i >= 1; $i-- ) {
			$old_file = self::$log_file . '.' . $i;
			$new_file = self::$log_file . '.' . ( $i + 1 );
			
			if ( file_exists( $old_file ) ) {
				if ( $i === self::MAX_BACKUP_FILES - 1 ) {
					unlink( $old_file );
				} else {
					rename( $old_file, $new_file );
				}
			}
		}

		// Move current log file to .1
		rename( self::$log_file, self::$log_file . '.1' );
	}

	/**
	 * Get client IP address
	 *
	 * @since    1.0.0
	 * @return   string Client IP address.
	 */
	private static function get_client_ip() {
		$ip_keys = array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );
		
		foreach ( $ip_keys as $key ) {
			if ( array_key_exists( $key, $_SERVER ) === true ) {
				foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
					$ip = trim( $ip );
					if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
						return $ip;
					}
				}
			}
		}

		return isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : 'Unknown';
	}

	/**
	 * Get log file contents
	 *
	 * @since    1.0.0
	 * @param    int $lines Number of lines to retrieve (0 for all).
	 * @return   array Log entries.
	 */
	public static function get_log_entries( $lines = 100 ) {
		if ( ! self::$log_file || ! file_exists( self::$log_file ) ) {
			return array();
		}

		$log_content = file_get_contents( self::$log_file );
		if ( ! $log_content ) {
			return array();
		}

		$log_lines = explode( PHP_EOL, trim( $log_content ) );
		
		if ( $lines > 0 ) {
			$log_lines = array_slice( $log_lines, -$lines );
		}

		return array_filter( $log_lines );
	}

	/**
	 * Clear log file
	 *
	 * @since    1.0.0
	 * @return   bool   True on success, false on failure.
	 */
	public static function clear_log() {
		if ( ! self::$log_file ) {
			return false;
		}

		return file_put_contents( self::$log_file, '' ) !== false;
	}

	/**
	 * Get log file size
	 *
	 * @since    1.0.0
	 * @return   int    Log file size in bytes.
	 */
	public static function get_log_size() {
		if ( ! self::$log_file || ! file_exists( self::$log_file ) ) {
			return 0;
		}

		return filesize( self::$log_file );
	}

	/**
	 * Get log file path
	 *
	 * @since    1.0.0
	 * @return   string Log file path.
	 */
	public static function get_log_file_path() {
		return self::$log_file;
	}

	/**
	 * Log API request/response
	 *
	 * @since    1.0.0
	 * @param    string $method   HTTP method.
	 * @param    string $url      Request URL.
	 * @param    array  $request  Request data.
	 * @param    array  $response Response data.
	 * @param    int    $status   HTTP status code.
	 * @return   void
	 */
	public static function log_api_request( $method, $url, $request = array(), $response = array(), $status = 0 ) {
		$context = array(
			'method' => $method,
			'url' => $url,
			'request' => $request,
			'response' => $response,
			'status' => $status,
		);

		$level = ( $status >= 200 && $status < 300 ) ? 'info' : 'error';
		$message = sprintf( 'API %s request to %s returned status %d', $method, $url, $status );

		self::log( $level, $message, $context );
	}

	/**
	 * Log appointment cancellation attempt
	 *
	 * @since    1.0.0
	 * @param    string $appointment_id Appointment ID.
	 * @param    string $customer_email Customer email.
	 * @param    bool   $success        Whether cancellation was successful.
	 * @param    string $error_message  Error message if failed.
	 * @return   void
	 */
	public static function log_cancellation_attempt( $appointment_id, $customer_email, $success, $error_message = '' ) {
		$context = array(
			'appointment_id' => $appointment_id,
			'customer_email' => $customer_email,
			'success' => $success,
		);

		if ( ! $success && $error_message ) {
			$context['error_message'] = $error_message;
		}

		$level = $success ? 'info' : 'error';
		$message = sprintf(
			'Appointment cancellation attempt for ID %s (%s) - %s',
			$appointment_id,
			$customer_email,
			$success ? 'SUCCESS' : 'FAILED'
		);

		self::log( $level, $message, $context );
	}
} 