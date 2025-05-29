<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://github.com/nicomollet/
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
 * @author     Nicolas Mollet <nico.mollet@gmail.com>
 */
if (! defined('ABSPATH')) {
	exit; // Sortir si l'accès direct est détecté.
}

class Tmsm_Appointment_Cancelation
{
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
	 * Le constructeur de la classe.
	 */
	public function __construct()
	{

		if (defined('TMSM_APPOINTMENT_CANCELATION_VERSION')) {
			$this->version = TMSM_APPOINTMENT_CANCELATION_VERSION;
		} else {
			$this->version = '1.0.0';
		}

		$this->plugin_name = 'tmsm-appointment-cancelation';

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
	 * - Tmsm_Aquos_Spa_Booking_Loader. Orchestrates the hooks of the plugin.
	 * - Tmsm_Aquos_Spa_Booking_i18n. Defines internationalization functionality.
	 * - Tmsm_Aquos_Spa_Booking_Admin. Defines all hooks for the admin area.
	 * - Tmsm_Aquos_Spa_Booking_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies()
	{
		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-tmsm-appointment-cancelation-loader.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-tmsm-appointment-cancelation-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-tmsm-appointment-cancelation-public.php';
		/**
		 * The class responsible for handling appointment cancelation with Aquos.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-tmsm-appointment-cancelation-aquos.php';
		/**
		 * The class responsible for handling appointment cancelation with Aquos.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-tmsm-appointment-cancelation-i18n.php';

		$this->loader = new Tmsm_Appointment_Cancelation_Loader();
	}


	private function set_locale()
	{
		$plugin_i18n = new Tmsm_Appointment_Cancelation_i18n();

		$this->loader->add_action('init', $plugin_i18n, 'load_plugin_textdomain');
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks()
	{
		$plugin_admin = new Tmsm_Appointment_Cancelation_Admin($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('admin_menu', $plugin_admin, 'tmsm_appointment_cancelation_options_page');
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks()
	{
		$plugin_public = new Tmsm_Appointment_Cancelation_Public($this->get_plugin_name(), $this->get_version());

		$this->loader->add_filter('the_content', $plugin_public, 'tmsm_handle_user_appointments_content');

		$this->loader->add_filter('query_vars', $plugin_public, 'tmsm_add_query_vars');

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run()
	{
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Tmsm_Appointment_Cancelation_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version()
	{
		return $this->version;
	}
}
