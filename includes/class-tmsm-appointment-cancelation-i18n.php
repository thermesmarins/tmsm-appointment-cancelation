<?php
/**
 * Class for handling internationalization (i18n) for the TMSM Appointment Cancelation plugin.
 *
 * @package    TMSM_Appointment_Cancelation
 * @subpackage Includes
 * @author     Your Name
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 */
class TMSM_Appointment_Cancelation_i18n {



    /**
     * Load the plugin text domain for translation.
     *
     * @since 1.0.0
     */
    public function load_plugin_textdomain() {
        // Définir le fuseau horaire pour le plugin
        // date_default_timezone_set('Europe/Paris');
        load_plugin_textdomain(
            'tmsm-appointment-cancelation',
            false,
           dirname( dirname( plugin_basename( __FILE__ ) )) . '/languages/'
        );
    }
}