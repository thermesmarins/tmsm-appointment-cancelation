<?php
if (! defined('ABSPATH')) {
    exit; // Sortir si l'accès direct est détecté.
}
class Tmsm_Appointment_Cancelation_Public
{
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
     * Constructor.
     *
     * @param string $plugin_name The name of the plugin.
     * @param string $version The version of the plugin.
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    // Ajout de la variable de requête pour l'identifiant de l'utilisateur
    public function tmsm_add_query_vars($vars)
    {
        $vars[] = 'user_id';
        return $vars;
    }
    // add_filter( 'query_vars', 'tmsm_add_query_vars' );

    // Action à exécuter lorsque notre point de terminaison est visité
    public function tmsm_handle_user_appointments_content($content)
    {
        global $wp_query;
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'annuler_rendez_vous' && isset( $_GET['appointment_id'] ) ) {
            // Vérifier le nonce pour la sécurité
            if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( $_GET['nonce'], 'annuler_rendez_vous_' . $_GET['appointment_id'] ) ) {
                return '<p>Nonce invalide. Action non autorisée.</p>';
            }
            // Todo creation de la fonction pour annuler le rendez-vous
            

            // Récupérer l'ID du rendez-vous à annuler
            $appointment_id = intval($_GET['appointment_id']);

            // Ici, vous devez ajouter la logique pour annuler le rendez-vous
            // Par exemple, supprimer le rendez-vous de la base de données

            // Afficher un message de succès ou d'erreur
            return '<p>Rendez-vous annulé avec succès.</p>';
        }
        if (is_page('vos-rendez-vous')) {
            // Récupérer l'identifiant de l'utilisateur depuis la variable de requête
            $user_id = get_query_var('user_id');

            if ($user_id) {
                // Ici, nous allons récupérer et afficher les rendez-vous de l'utilisateur
                $appointments = $this->tmsm_get_user_appointments($user_id); // Fonction à créer
                // todo traitement des ids multiples de rendez-vous
                // $output = '<h2>Vos Rendez-vous</h2>';
                $output = '<p>Voici la liste de vos rendez-vous :</p>';
                if (! empty($appointments)) {
                    $output .= '<ul>';
                    foreach ($appointments as $appointment) {
                        // Formattez ici l'affichage de chaque rendez-vous
                        $output .= '<li>ID du Rendez-vous : ' . esc_html($appointment->ID) . ' le ' . esc_html($appointment->date) . '<a href="' . esc_url( add_query_arg( array( 'action' => 'annuler_rendez_vous', 'appointment_id' => $appointment->appointment_id, 'nonce' => wp_create_nonce( 'annuler_rendez_vous_' . $appointment->appointment_id) ) ) ) . '" class="cancel-button" style="padding-left: 10px;">' . 'Annuler ce rendez-vous' . '</a></li>';
                    }
                    $output .= '</ul>';
                } else {
                    $output .= '<p>Aucun rendez-vous trouvé pour cet utilisateur.</p>';
                }
                return $output;
            } else {
                return '<p>Identifiant d\'utilisateur non valide.</p>';
            }
        }

        return $content; // Retourner le contenu original si ce n'est pas notre page
    }

    // Fonction temporaire pour simuler la récupération des rendez-vous
    public function tmsm_get_user_appointments($user_id)
    {
        // Dans la réalité, vous interrogeriez la base de données ici
        // en utilisant l'identifiant de l'utilisateur pour récupérer ses rendez-vous.
        // Pour l'exemple, nous allons retourner un tableau vide ou quelques objets factices.
        // Exemple de données factices :
        return [
            (object) ['ID' => 1, 'date' => '2025-05-10', 'appointment_id' => [10, 11]],
            (object) ['ID' => 2, 'date' => '2025-05-15', 'appointment_id' => [20]],
        ];
        // return []; // Retourner un tableau vide si aucun rendez-vous
    }



    // IMPORTANT : Vous n'avez plus besoin de réenregistrer les permaliens
    // car nous n'utilisons plus de point de terminaison personnalisé de la même manière.
}



// IMPORTANT : Vous n'avez plus besoin de réenregistrer les permaliens
// car nous n'utilisons plus de point de terminaison personnalisé de la même manière.
