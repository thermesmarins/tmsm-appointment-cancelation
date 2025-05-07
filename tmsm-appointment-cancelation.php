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

// Votre code personnalisé commencera ici.
// Enregistrement du point de terminaison personnalisé
function tmsm_add_endpoint() {
    add_rewrite_endpoint( 'user_appointments', EP_PAGES );
}
add_action( 'init', 'tmsm_add_endpoint' );

// Ajout de la variable de requête pour l'identifiant de l'utilisateur
function tmsm_add_query_vars( $vars ) {
    $vars[] = 'user_id';
    return $vars;
}
add_filter( 'query_vars', 'tmsm_add_query_vars' );

// Action à exécuter lorsque notre point de terminaison est visité
function tmsm_handle_user_appointments_page() {
    global $wp_query;
    error_log('$wp_query->query_vars: ' . print_r($wp_query->query_vars, true)); // Debugging line
    if (  is_page( 'vos-rendez-vous' ) ) {
        // Récupérer l'identifiant de l'utilisateur depuis la variable de requête
        $user_id = get_query_var( 'user_id' );

        if ( $user_id ) {
            // Ici, nous allons récupérer et afficher les rendez-vous de l'utilisateur
            echo '<h2>Vos Rendez-vous</h2>';
            echo '<p>Identifiant de l\'utilisateur : ' . esc_html( $user_id ) . '</p>';
            // Nous ajouterons ici la logique pour récupérer et afficher les rendez-vous
        } else {
            echo '<p>Identifiant d\'utilisateur non valide.</p>';
        }
        exit; // Important pour arrêter l'exécution normale de WordPress
    }
}
add_action( 'template_redirect', 'tmsm_handle_user_appointments_page' );

// IMPORTANT : Après avoir ajouté ou modifié les règles de réécriture,
// vous devez enregistrer à nouveau les permaliens dans votre tableau de bord WordPress.
// Allez dans "Réglages" > "Permaliens" et cliquez sur "Enregistrer les modifications".


?>