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
     * The Aquos API handler instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Tmsm_Appointment_Cancelation_Aquos    $aquos_api_handler    The Aquos API handler instance.
     */
    private $aquos_api_handler = null;

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
        // Hook pour gérer l'action d'annulation plus tôt dans le cycle de chargement de WordPress
        add_action('init', array($this, 'handle_cancel_appointment_action'));
    }

    // Ajout de la variable de requête pour l'identifiant de l'utilisateur
    public function tmsm_add_query_vars($vars)
    {
        $vars[] = 'fonctionnal_id';
        $vars[] = 'token';
        return $vars;
    }
    // add_filter( 'query_vars', 'tmsm_add_query_vars' );
     /**
     * Gère l'action d'annulation de rendez-vous en utilisant l'action 'init'.
     * Cela assure que la logique d'annulation ne s'exécute qu'une seule fois.
     */
    public function handle_cancel_appointment_action() {
        if ( isset($_GET['action']) && $_GET['action'] === 'annuler_rendez_vous' && isset($_GET['appointment_id']) ) {
            // Vérifier le nonce pour la sécurité
            if (! isset($_GET['nonce']) || ! wp_verify_nonce($_GET['nonce'], 'annuler_rendez_vous_' . $_GET['appointment_id'])) {
                wp_die('<p>Nonce invalide. Action non autorisée.</p>', __('Error', 'tmsm-appointment-cancelation'));
            }

            // Récupérer les données nécessaires depuis l'URL (GET, pas get_query_var ici car nous sommes sur 'init')
            $appointment_id = intval($_GET['appointment_id']);
            $fonctionnal_id = isset($_GET['fonctionnal_id']) ? sanitize_text_field($_GET['fonctionnal_id']) : '';
            $token_from_url = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : ''; // Le token de l'URL, s'il y en a un

            // Récupérer le token de sécurité depuis les options du plugin (le vrai token pour l'API)
            $options = get_option('tmsm_appointment_cancelation_options');
            $plugin_api_token = isset($options['aquos_appointment_cancellation_token']) ? esc_attr($options['aquos_appointment_cancellation_token']) : '';

            // Instancier Tmsm_Appointment_Cancelation_Aquos pour cette action spécifique d'annulation
            $aquos_api_handler_for_action = new Tmsm_Appointment_Cancelation_Aquos($fonctionnal_id, $plugin_api_token);
            
            // Récupérer l'ID numérique de l'utilisateur et l'ID du site pour l'appel API
            // $numeric_user_id = $aquos_api_handler_for_action->get_aquos_numeric_id();
            $site_id_from_token = $aquos_api_handler_for_action->get_aquos_site_id();

            error_log('*** LOGIQUE D\'ANNULATION EXÉCUTÉE (action init) ***'); // Ce log ne s'affichera qu'une seule fois si cette action est déclenchée
            // error_log("Tentative d'annulation du rendez-vous ID: $appointment_id pour utilisateur: $numeric_user_id sur site: $site_id_from_token");

            // TODO: Appeler la méthode d'annulation de l'API
            // Exemple: $aquos_api_handler_for_action->cancel_appointment($appointment_id, $numeric_user_id, $site_id_from_token);

            // Rediriger l'utilisateur après l'action pour éviter les soumissions multiples
            $redirect_url = remove_query_arg( array('action', 'appointment_id', 'nonce', 'fonctionnal_id', 'token') );
            $redirect_url = add_query_arg( 'cancel_status', 'success', $redirect_url );
            wp_redirect( $redirect_url );
            exit; // Très important de terminer l'exécution ici après une redirection
        }
    }

    // Action à exécuter lorsque notre point de terminaison est visité
    public function tmsm_handle_user_appointments_content($content)
    {
        // https://aquatonic.local/rennes/vos-rendez-vous/?fonctionnal_id=12345AQREN&token=1641616111155&date=2025-05-10
        global $wp_query;
        // Varioble à récupérer dans l'url (date de rendez-vous, id fonctionnel, token)


        // if (isset($_GET['action']) && $_GET['action'] === 'annuler_rendez_vous' && isset($_GET['appointment_id'])) {
        //     // Vérifier le nonce pour la sécurité
        //     if (! isset($_GET['nonce']) || ! wp_verify_nonce($_GET['nonce'], 'annuler_rendez_vous_' . $_GET['appointment_id'])) {
        //         return '<p>Nonce invalide. Action non autorisée.</p>';
        //     }
        //     // Todo creation de la fonction pour annuler le rendez-vous
        //     // Récupérer l'ID du rendez-vous à annuler
        //     $appointment_id = intval($_GET['appointment_id']);
        //     $fonctionnal_id = get_query_var('fonctionnal_id') ?: (isset($_GET['fonctionnal_id']) ? sanitize_text_field($_GET['fonctionnal_id']) : '');
        //     $token = get_query_var('token') ?: (isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '');


        //     // Ici, vous devez ajouter la logique pour annuler le rendez-vous
        //     // Par exemple, supprimer le rendez-vous de la base de données
        //     if (is_null($this->aquos_api_handler)) {
        //         $this->aquos_api_handler = new Tmsm_Appointment_Cancelation_Aquos($fonctionnal_id, $token);
        //     }
        //     error_log('2');
        //     // Afficher un message de succès ou d'erreur
        //     return '<p>Rendez-vous annulé avec succès.</p>';
        // }
        if (is_page('vos-rendez-vous')) {
             $fonctionnal_id = get_query_var('fonctionnal_id');
            $token = get_query_var('token');
        if (is_null($this->aquos_api_handler)) {
                $this->aquos_api_handler = new Tmsm_Appointment_Cancelation_Aquos($fonctionnal_id, $token);
                // Messages de log à exécuter une seule fois lors de la création de l'instance
                error_log('ID Fonctionnel Complet : ' . $fonctionnal_id);
                error_log('ID Numérique Extrait : ' . $this->aquos_api_handler->get_aquos_appointment_id());
                error_log('Code de Site Extrait : ' . $this->aquos_api_handler->get_aquos_site_id());
            }
           
            $site_id = $this->aquos_api_handler->get_aquos_site_id();


            if ($fonctionnal_id) {
                // Ici, nous allons récupérer et afficher les rendez-vous de l'utilisateur
                $appointments = $this->tmsm_get_user_appointments($fonctionnal_id); // Fonction à créer
                // todo traitement des ids multiples de rendez-vous
                // $output = '<h2>Vos Rendez-vous</h2>';
                $output = '<p>Voici la liste de vos rendez-vous : pour l\'utilisateur ' . $fonctionnal_id . '  avec le token  : ' . $token . '</p>';
                if (! empty($appointments)) {
                    $output .= '<ul>';
                    foreach ($appointments as $appointment) {
                        $cancel_url = add_query_arg(
                            array(
                                'action'         => 'annuler_rendez_vous',
                                'appointment_id' => $appointment->appointment_id, // Si appointment_id est un tableau, cela doit être géré
                                'nonce'          => wp_create_nonce('annuler_rendez_vous_' . $appointment->appointment_id),
                                'fonctionnal_id' => $fonctionnal_id, // Pour repasser l'ID fonctionnel si besoin
                                'token'          => $token, // Pour repasser le token de l'URL si besoin
                                'site_id'        => $site_id,
                            )
                        );
                        // Formattez ici l'affichage de chaque rendez-vous
                        $output .= '<li>ID du Rendez-vous : ' . esc_html($appointment->ID) . ' le ' . esc_html($appointment->date) . '<a href="' . esc_url($cancel_url) . '" class="cancel-button" style="padding-left: 10px;">' . 'Annuler ce rendez-vous' . '</a></li>';
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
    public function tmsm_get_user_appointments($fonctionnal_id)
    {
        // Dans la réalité, vous interrogeriez la base de données ici
        // en utilisant l'identifiant de l'utilisateur pour récupérer ses rendez-vous.
        // Pour l'exemple, nous allons retourner un tableau vide ou quelques objets factices.
        // Exemple de données factices :
        return [
            (object) ['ID' => 1, 'date' => '2025-05-10', 'appointment_id' => 10],
            (object) ['ID' => 2, 'date' => '2025-05-15', 'appointment_id' => 20],
        ];
        // return []; // Retourner un tableau vide si aucun rendez-vous
    }



    // IMPORTANT : Vous n'avez plus besoin de réenregistrer les permaliens
    // car nous n'utilisons plus de point de terminaison personnalisé de la même manière.
}



// IMPORTANT : Vous n'avez plus besoin de réenregistrer les permaliens
// car nous n'utilisons plus de point de terminaison personnalisé de la même manière.
