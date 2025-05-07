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
 */


 define( 'TMSM_APPOINTMENT_CANCELATION_VERSION', '1.0.0' );
 define( 'TMSM_APPOINTMENT_CANCELATION_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
 define( 'TMSM_APPOINTMENT_CANCELATION_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

 register_activation_hook( __FILE__, 'activate_tmsm_aquos_spa_booking' );
register_deactivation_hook( __FILE__, 'deactivate_tmsm_aquos_spa_booking' );

require plugin_dir_path( __FILE__ ) . 'includes/class-tmsm-appointment-cancelation.php';

function run_tmsm_appointment_cancelation() {

	$plugin = new Tmsm_Appointment_Cancelation();
	$plugin->run();

}
run_tmsm_appointment_cancelation();
?>