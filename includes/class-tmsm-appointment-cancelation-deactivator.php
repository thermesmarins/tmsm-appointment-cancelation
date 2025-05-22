<?php

/**
 * Fired during plugin deactivation
 *
 * @link       http://github.com/nicomollet/
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
 * @author     Aranud Flament <aflament.dev@gmail.com>
 */
class Tmsm_Appointment_Cancelation_Deactivator {

   /**
     * Méthode appelée lors de la désactivation du plugin.
     * C'est ici que vous mettriez la logique de nettoyage,
     * par exemple la suppression d'options ou de tables créées par le plugin.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Exemple : Supprimer une option lors de la désactivation
        // delete_option( 'tmsm_appointment_cancelation_version' );

        // Log pour vérifier que la fonction est appelée
        error_log('Plugin TMSM Appointment Cancelation désactivé !');
    }
}
