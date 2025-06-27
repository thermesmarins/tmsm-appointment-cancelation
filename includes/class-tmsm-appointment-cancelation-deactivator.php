<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://github.com/ArnaudFlament35/tmsm-appointment-cancelation
 * @since      1.0.0
 *
 * @package    Tmsm_Appointment_Cancelation
 * @subpackage Tmsm_Appointment_Cancelation/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Tmsm_Appointment_Cancelation
 * @subpackage Tmsm_Appointment_Cancelation/includes
 * @author     Arnaud Flament <aflament.dev@gmail.com>
 */
class Tmsm_Appointment_Cancelation_Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * This method is called when the plugin is deactivated. It handles
	 * cleanup tasks such as removing temporary data, clearing caches,
	 * and optionally removing plugin data.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public static function deactivate() {
		// Clear any scheduled events
		self::clear_scheduled_events();

		// Clear plugin cache
		self::clear_cache();

		// Remove activation flag
		delete_option( 'tmsm_appointment_cancelation_activated' );

		// Optionally remove plugin data (uncomment if needed)
		// self::remove_plugin_data();

		// Log deactivation
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'TMSM Appointment Cancelation plugin deactivated successfully.' );
		}
	}

	/**
	 * Clear any scheduled events created by the plugin.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	private static function clear_scheduled_events() {
		$scheduled_events = array(
			'tmsm_appointment_cancelation_cleanup_expired_tokens',
			'tmsm_appointment_cancelation_sync_appointments',
		);

		foreach ( $scheduled_events as $event ) {
			wp_clear_scheduled_hook( $event );
		}
	}

	/**
	 * Clear plugin cache and temporary data.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	private static function clear_cache() {
		// Clear WordPress object cache for plugin data
		wp_cache_delete( 'tmsm_appointment_cancelation_settings', 'options' );
		wp_cache_delete( 'tmsm_appointment_cancelation_api_cache', 'transient' );

		// Clear transients
		delete_transient( 'tmsm_appointment_cancelation_api_cache' );
		delete_transient( 'tmsm_appointment_cancelation_settings_cache' );

		// Clear any custom cache files
		$cache_dir = WP_CONTENT_DIR . '/cache/tmsm-appointment-cancelation/';
		if ( is_dir( $cache_dir ) ) {
			self::delete_directory( $cache_dir );
		}
	}

	/**
	 * Remove all plugin data (use with caution).
	 *
	 * This method removes all plugin options, database tables, and files.
	 * Only uncomment this if you want to completely remove all plugin data.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	private static function remove_plugin_data() {
		// Remove all plugin options
		$options_to_remove = array(
			'tmsm_appointment_cancelation_api_endpoint',
			'tmsm_appointment_cancelation_api_key',
			'tmsm_appointment_cancelation_token_expiry',
			'tmsm_appointment_cancelation_admin_email',
			'tmsm_appointment_cancelation_enable_notifications',
			'tmsm_appointment_cancelation_debug_mode',
			'tmsm_appointment_cancelation_version',
		);

		foreach ( $options_to_remove as $option ) {
			delete_option( $option );
		}

		// Remove database tables
		global $wpdb;
		$tables_to_remove = array(
			$wpdb->prefix . 'tmsm_appointment_cancellations',
		);

		foreach ( $tables_to_remove as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS $table" );
		}

		// Remove uploaded files
		$upload_dir = wp_upload_dir();
		$plugin_upload_dir = $upload_dir['basedir'] . '/tmsm-appointment-cancelation/';
		if ( is_dir( $plugin_upload_dir ) ) {
			self::delete_directory( $plugin_upload_dir );
		}
	}

	/**
	 * Recursively delete a directory and its contents.
	 *
	 * @since    1.0.0
	 * @param    string $dir Directory path to delete.
	 * @return   bool   True on success, false on failure.
	 */
	private static function delete_directory( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return false;
		}

		$files = array_diff( scandir( $dir ), array( '.', '..' ) );
		foreach ( $files as $file ) {
			$path = $dir . '/' . $file;
			if ( is_dir( $path ) ) {
				self::delete_directory( $path );
			} else {
				unlink( $path );
			}
		}

		return rmdir( $dir );
	}
}
