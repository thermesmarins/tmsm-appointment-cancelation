<?php

/**
 * Fired during plugin activation
 *
 * @link       http://github.com/nicomollet/
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
 * @author     Aranud Flament <aflament.dev@gmail.com>
 */
class Tmsm_Appointment_Cancelation_Activator {

	/**
	 * Activate the plugin.
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	/**
     * Méthode appelée lors de l'activation du plugin.
     * C'est ici que vous mettriez la logique d'initialisation,
     * par exemple la création de tables de base de données, la définition d'options par défaut, etc.
     *
     * @since    1.0.0
     */
    public static function activate() {
        // Exemple : Définir une option par défaut lors de l'activation
        // add_option( 'tmsm_appointment_cancelation_version', TMSM_APPOINTMENT_CANCELATION_VERSION );
        
        // Log pour vérifier que la fonction est appelée
        error_log('Plugin TMSM Appointment Cancelation activé !');
    }

}
