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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    // Nouvelle méthode dans Tmsm_Appointment_Cancelation_Public
    public function enqueue_styles()
    {
        wp_enqueue_style(
            $this->plugin_name . '-public',
            plugin_dir_url(__FILE__) . 'assets/css/tmsm-appointment-cancelation-public.css', // Assurez-vous que le chemin est correct
            array(),
            $this->version,
            'all'
        );
    }

    // Nouvelle méthode dans Tmsm_Appointment_Cancelation_Public
    public function enqueue_scripts()
    {
        wp_enqueue_script(
            $this->plugin_name . '-public',
            plugin_dir_url(__FILE__) . '/assets/js/tmsm-appointment-cancelation-public.js', // Assurez-vous que le chemin est correct
            array('jquery'), // Dépendance jQuery si vous l'utilisez
            $this->version,
            true // Charger le script dans le footer
        );
    }
    // Ajout de la variable de requête pour l'identifiant de l'utilisateur
    public function tmsm_add_query_vars($vars)
    {
        $vars[] = 'fonctionnal_id';
        $vars[] = 'aquos_appointment_signature';
        $vars[] = 'date';
        return $vars;
    }
    // add_filter( 'query_vars', 'tmsm_add_query_vars' );
    /**
     * Gère l'action d'annulation de rendez-vous en utilisant l'action 'init'.
     * Cela assure que la logique d'annulation ne s'exécute qu'une seule fois.
     */
    public function handle_cancel_appointment_action()
    {
        if (isset($_GET['action']) && $_GET['action'] === 'annuler_rendez_vous' && isset($_GET['appointment_id'])) {
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
            $cancel_status = false; // Initialiser le statut d'annulation

            // TODO: Appeler la méthode d'annulation de l'API
            // Exemple: $aquos_api_handler_for_action->cancel_appointment($appointment_id, $numeric_user_id, $site_id_from_token);

            // Rediriger l'utilisateur après l'action pour éviter les soumissions multiples
            $redirect_url = remove_query_arg(array('action', 'appointment_id', 'nonce'));
            // Ajouter le statut de l'annulation
            if ($cancel_status) {
                $redirect_url = add_query_arg('cancel_status', 'success', $redirect_url);
            } else {
                $redirect_url = add_query_arg('cancel_status', 'error', $redirect_url);
            }
            wp_redirect($redirect_url);
            exit; // Très important de terminer l'exécution ici après une redirection
        }
    }

    // Action à exécuter lorsque notre point de terminaison est visité
    public function tmsm_handle_user_appointments_content($content)
    {
        // https://aquatonic.local/rennes/vos-rendez-vous/?fonctionnal_id=12345AQREN&aquos_appointment_signature=1641616111155&date=2025-05-10
        global $wp_query;
        // Varioble à récupérer dans l'url (date de rendez-vous, id fonctionnel, token)

        $output = ''; // Initialiser la sortie
        if (isset($_GET['cancel_status'])) {
            $cancel_status = sanitize_text_field($_GET['cancel_status']);

            if ('success' === $cancel_status) {
                $output .= '<div class="tmsm-notification tmsm-notification-success">';
                $output .= '<p>Votre rendez-vous a été annulé avec succès !</p>';
                $output .= '</div>';
            } elseif ('error' === $cancel_status) {
                $output .= '<div class="tmsm-notification tmsm-notification-error">';
                $output .= '<p>Une erreur est survenue lors de l\'annulation de votre rendez-vous. Veuillez réessayer.</p>';
                $output .= '</div>';
            }
            // Nettoyer le paramètre d'URL pour qu'il ne reste pas si la page est rafraîchie manuellement
            // Note: Une redirection est une meilleure pratique pour cela, mais si vous voulez qu'il disparaisse après un rafraîchissement manuel
            // vous devriez faire une redirection JavaScript après l'affichage, ou utiliser les transients comme discuté précédemment
            // mais sur la page de destination et pas sur la page d'accueil.
            return $output;
        }
        if (is_page('vos-rendez-vous')) {
            $fonctionnal_id = get_query_var('fonctionnal_id');
            $aquos_appointment_signature = get_query_var('aquos_appointment_signature');
            $date = get_query_var('date');
            error_log('Date de rendez-vous : ' . $date . ' est de type ' . gettype($date));
            if (is_null($this->aquos_api_handler)) {
                $this->aquos_api_handler = new Tmsm_Appointment_Cancelation_Aquos($fonctionnal_id, $aquos_appointment_signature, $date);
                // Messages de log à exécuter une seule fois lors de la création de l'instance
                error_log('ID Fonctionnel Complet : ' . $fonctionnal_id);
                error_log('ID Numérique Extrait : ' . $this->aquos_api_handler->get_aquos_appointment_id());
                error_log('Code de Site Extrait : ' . $this->aquos_api_handler->get_aquos_site_id());
                error_log('Date Extrait : ' . $this->aquos_api_handler->get_aquos_appointment_date());
            }

            $site_id = $this->aquos_api_handler->get_aquos_site_id();


// todo: vérifier la présence de la date et de la signature dans l'url
            if ($fonctionnal_id) {
                // Ici, nous allons récupérer et afficher les rendez-vous de l'utilisateur
                // $appointments = $this->tmsm_get_user_appointments($fonctionnal_id); // Fonction à créer
                $appointments = $this->aquos_api_handler->get_user_appointments();
                error_log('Rendez-vous récupérés : ' . print_r($appointments, true)); // Log pour vérifier les rendez-vous récupérés
                // todo traitement des ids multiples de rendez-vous
                // $output = '<h2>Vos Rendez-vous</h2>';
                $output = '<p>Voici la liste de vos rendez-vous : pour l\'utilisateur ' . $fonctionnal_id . '  avec le token  : ' . $aquos_appointment_signature . '</p>';
                if (! empty($appointments)) {
                    $output .= '<ul>';
                    foreach ($appointments as $appointment) {
                        $cancel_url = add_query_arg(
                            array(
                                'action'         => 'annuler_rendez_vous',
                                'appointment_id' => $appointment->appointment_id, // Si appointment_id est un tableau, cela doit être géré
                                'nonce'          => wp_create_nonce('annuler_rendez_vous_' . $appointment->appointment_id),
                                'fonctionnal_id' => $fonctionnal_id, // Pour repasser l'ID fonctionnel si besoin
                                'aquos_appointment_signature'=> $aquos_appointment_signature, // Pour repasser le token de l'URL si besoin
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


}

