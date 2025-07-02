<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://github.com/ArnaudFlament35/tmsm-appointment-cancelation
 * @since      1.0.0
 *
 * @package    Tmsm_Appointment_Cancelation
 * @subpackage Tmsm_Appointment_Cancelation/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
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

class Tmsm_Appointment_Cancelation {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Tmsm_Appointment_Cancelation_Loader   $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * The constructor of the class.
	 *
	 * Initializes the plugin by setting the plugin name, version, and loading dependencies.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		// Set plugin version
		if ( defined( 'TMSM_APPOINTMENT_CANCELATION_VERSION' ) ) {
			$this->version = TMSM_APPOINTMENT_CANCELATION_VERSION;
		} else {
			$this->version = '1.0.0';
		}

		// Set plugin name
		$this->plugin_name = 'tmsm-appointment-cancelation';

		// Load dependencies and set up the plugin
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Tmsm_Appointment_Cancelation_Config. Manages plugin configuration.
	 * - Tmsm_Appointment_Cancelation_Logger. Provides logging capabilities.
	 * - Tmsm_Appointment_Cancelation_Loader. Orchestrates the hooks of the plugin.
	 * - Tmsm_Appointment_Cancelation_i18n. Defines internationalization functionality.
	 * - Tmsm_Appointment_Cancelation_Admin. Defines all hooks for the admin area.
	 * - Tmsm_Appointment_Cancelation_Public. Defines all hooks for the public side of the site.
	 * - Tmsm_Appointment_Cancelation_Aquos. Handles Aquos API integration.
	 * - Email classes for customer and admin notifications.
	 * - Helper functions.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   void
	 */
	private function load_dependencies() {
		$plugin_dir = TMSM_APPOINTMENT_CANCELATION_PLUGIN_DIR;

		// Define required files with their descriptions (in dependency order)
		$required_files = array(
			'includes/class-tmsm-appointment-cancelation-config.php' => 'The class responsible for managing plugin configuration.',
			'includes/class-tmsm-appointment-cancelation-logger.php' => 'The class responsible for logging functionality.',
			'includes/class-tmsm-appointment-cancelation-loader.php' => 'The class responsible for orchestrating the actions and filters of the core plugin.',
			'includes/class-tmsm-appointment-cancelation-i18n.php' => 'The class responsible for defining internationalization functionality.',
			'admin/class-tmsm-appointment-cancelation-admin.php' => 'The class responsible for defining all actions that occur in the admin area.',
			'public/class-tmsm-appointment-cancelation-public.php' => 'The class responsible for defining all actions that occur in the public-facing side of the site.',
			'includes/class-tmsm-appointment-cancelation-aquos.php' => 'The class responsible for handling appointment cancellation with Aquos API.',
			'includes/class-tmsm-appointment-cancelation-customer-email.php' => 'The class responsible for handling customer email notifications.',
			'includes/class-tmsm-appointment-cancelation-admin-email.php' => 'The class responsible for handling admin email notifications.',
			'includes/tmsm-helpers.php' => 'Helper functions for the plugin.',
		);

		// Load each required file
		foreach ( $required_files as $file_path => $description ) {
			$full_path = $plugin_dir . $file_path;
			
			if ( ! file_exists( $full_path ) ) {
				// Log error and display admin notice
				error_log( 'TMSM Appointment Cancelation: Required file missing: ' . $full_path );
				add_action( 'admin_notices', function() use ( $file_path ) {
					echo '<div class="notice notice-error"><p>';
					printf(
						/* translators: %s: missing file path */
						esc_html__( 'TMSM Appointment Cancelation plugin error: Required file %s is missing.', 'tmsm-appointment-cancelation' ),
						esc_html( $file_path )
					);
					echo '</p></div>';
				});
				return;
			}

			require_once $full_path;
		}

		// Create loader instance
		$this->loader = new Tmsm_Appointment_Cancelation_Loader();

		// Initialize logger
		Tmsm_Appointment_Cancelation_Logger::init();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Tmsm_Appointment_Cancelation_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new Tmsm_Appointment_Cancelation_i18n();
		$this->loader->add_action( 'init', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   void
	 */
	private function define_admin_hooks() {
		$plugin_admin = new Tmsm_Appointment_Cancelation_Admin( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'tmsm_appointment_cancelation_options_page' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   void
	 */
	private function define_public_hooks() {
		$plugin_public = new Tmsm_Appointment_Cancelation_Public( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_filter( 'the_content', $plugin_public, 'tmsm_handle_user_appointments_content' );
		$this->loader->add_filter( 'query_vars', $plugin_public, 'tmsm_add_query_vars' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Tmsm_Appointment_Cancelation_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
