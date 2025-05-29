<?php
/**
 * Plugin Name: TMSM Appointment Cancelation
 * Plugin URI: https://example.com/tmsm-appointment-cancelation
 * Description: Permet aux utilisateurs d'annuler leurs rendez-vous.
 * Version: 1.0.0
 * Author: Arnaud Flament
 * Author URI: https://github.com/ArnaudFlament35
 * License: GPL v2 or later
 * Text Domain: tmsm-appointment-cancelation
 * Domain Path: /languages
 */


 define( 'TMSM_APPOINTMENT_CANCELATION_VERSION', '1.0.0' );
 define( 'TMSM_APPOINTMENT_CANCELATION_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
 define( 'TMSM_APPOINTMENT_CANCELATION_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

 require_once plugin_dir_path( __FILE__ ) . 'includes/class-tmsm-appointment-cancelation-activator.php';
 // Enregistrement du hook d'activation
// On appelle la méthode statique 'activate' de la classe 'Tmsm_Appointment_Cancelation_Activator'
register_activation_hook( __FILE__, array( 'Tmsm_Appointment_Cancelation_Activator', 'activate' ) );
// --- Correction pour la désactivation aussi ---
// Vous devrez aussi définir la méthode 'deactivate' dans votre classe Activator
// ou dans une nouvelle classe Deactivator si vous voulez suivre cette structure.
// Pour l'instant, je vais supposer que vous allez la mettre dans Tmsm_Appointment_Cancelation_Activator
require_once plugin_dir_path( __FILE__ ) . 'includes/class-tmsm-appointment-cancelation-deactivator.php';
register_deactivation_hook( __FILE__, array( 'Tmsm_Appointment_Cancelation_deactivator', 'deactivate' ) );

require plugin_dir_path( __FILE__ ) . 'includes/class-tmsm-appointment-cancelation.php';

function run_tmsm_appointment_cancelation() {

	$plugin = new Tmsm_Appointment_Cancelation();
	$plugin->run();

}
run_tmsm_appointment_cancelation();
?>