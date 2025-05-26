<?php
if (! defined('ABSPATH')) {
    exit;
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
     * Cache pour stocker les rendez-vous de l'utilisateur après le premier appel API.
     * @since    1.0.0
     * @access   private
     * @var      array|WP_Error|null $user_appointments_cache
     */
    private $user_appointments_cache = null;

    /**
     * Indicateur pour savoir si les rendez-vous ont déjà été chargés depuis l'API.
     * @since    1.0.0
     * @access   private
     * @var      bool $appointments_loaded
     */
    private $appointments_loaded = false;
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
    /**
     * Enqueue the public-facing styles for the plugin.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {
        wp_enqueue_style(
            $this->plugin_name . '-public',
            plugin_dir_url(__FILE__) . 'assets/css/tmsm-appointment-cancelation-public.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Enqueue the public-facing JavaScript for the plugin.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
        wp_enqueue_script(
            $this->plugin_name . '-public',
            plugin_dir_url(__FILE__) . '/assets/js/tmsm-appointment-cancelation-public.js',
            array('jquery'),
            $this->version,
            true // Charger le script dans le footer
        );
    }
    /**
     * Enregistre le point de terminaison pour les rendez-vous de l'utilisateur.
     * Cela permet d'utiliser des variables personnalisées dans l'URL.
     *
     * @since    1.0.0
     */
    public function tmsm_add_query_vars($vars)
    {
        $vars[] = 'f';
        $vars[] = 't';
        $vars[] = 'd';
        return $vars;
    }
    /**
     * Manage cancelation of appointments when the action is triggered.
     * This method is hooked to the 'init' action, which runs early in the WordPress loading process.
     * It checks for the 'action' and 'appointment_id' parameters in the URL,
     * verifies the nonce for security, and then processes the cancellation of the appointment(s).
     */
    public function handle_cancel_appointment_action()
    {
        if (isset($_GET['action']) && $_GET['action'] === 'annuler_rendez_vous' && isset($_GET['appointment_id'])) {
            // Vérifier le nonce pour la sécurité
            if (! isset($_GET['nonce']) || ! wp_verify_nonce($_GET['nonce'], 'annuler_rendez_vous_' . $_GET['appointment_id'])) {
                wp_die('<p>Nonce invalide. Action non autorisée.</p>', __('Error', 'tmsm-appointment-cancelation'));
            }
           

            $appointment_ids = explode(',', $_GET['appointment_id']);
            $fonctionnal_id = isset($_GET['f']) ? sanitize_text_field($_GET['f']) : '';
            $options = get_option('tmsm_appointment_cancelation_options');
            $plugin_api_token = isset($options['aquos_appointment_cancellation_token']) ? esc_attr($options['aquos_appointment_cancellation_token']) : '';
            $aquos_cancel_appointments = new Tmsm_Appointment_Cancelation_Aquos($fonctionnal_id, $plugin_api_token);
            $site_id = isset($_GET['site_id']) ? sanitize_text_field($_GET['site_id']) : '';
           

            error_log('*** LOGIQUE D\'ANNULATION EXÉCUTÉE (action init) ***'); // Ce log ne s'affichera qu'une seule fois si cette action est déclenchée
            $cancel_status = false; // Initialiser le statut d'annulation
            $aquos_cancel_appointments->cancel_appointment($appointment_ids); // Appeler la méthode d'annulation de l'API
            // foreach ($appointment_ids as $appointment_id) {
            //     $appointment_id = intval($appointment_id); // Assurez-vous que l'ID est un entier
                // TODO: Appeler la méthode d'annulation de l'API
                // if ($appointment_id > 0 && !empty($site_id)) {
                    // Appeler la méthode d'annulation de l'API
                    // Récuperer le statut de l'annulation vérifier qu'il n'y pas d'erreur et valider le succès
                    // $cancel_status = $aquos_cancel_appointments->cancel_appointment($appointment_id, $site_id);
                    // creer la méthode get_errors dans Tmsm_Appointment_Cancelation_Aquos
                    // $cancel_errors [] = $aquos_cancel_appointments->get_errors();
                    // error_log("Annulation du rendez-vous ID: $appointment_id pour utilisateur: $fonctionnal_id sur site: $site_id. Statut: " . ($cancel_status ? 'Succès' : 'Échec'));
                // } else {
                //     error_log("ID de rendez-vous invalide ou site ID manquant pour l'annulation.");
                // }
            // }
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
        // https://aquatonic.local/rennes/vos-rendez-vous/?f=304555AQREN&t=btwHqtVtGZ&d=2025.05.25
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
        // todo gerer la page direct 
        if (is_page('vos-rendez-vous') || is_page('rdv') && isset($wp_query->query_vars['f']) && isset($wp_query->query_vars['t']) && isset($wp_query->query_vars['d'])) {
            $fonctionnal_id = get_query_var('f');
            $aquos_appointment_signature = get_query_var('t');
            if (!empty($aquos_appointment_signature) && strpos($aquos_appointment_signature, ' ') !== false) {
                // Si des espaces sont trouvés, nous les reconvertissons en '+'.
                $aquos_appointment_signature = str_replace(' ', '+', $aquos_appointment_signature);
                error_log('Signature après correction des espaces -> + : ' . $aquos_appointment_signature);
            }
            $date = get_query_var('d');
            $date_to_show = '';
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
                if (! $this->appointments_loaded) {
                    $this->user_appointments_cache = $this->aquos_api_handler->get_user_appointments();
                    $this->appointments_loaded = true;
                }
                // Utiliser les rendez-vous en cache
                $appointments = $this->user_appointments_cache;
                $date_to_show = $this->aquos_api_handler->get_formatted_date($this->aquos_api_handler->get_aquos_appointment_date());
                $appointments_ids = [];
                $output = '<h3>Réservation du ' . esc_html($date_to_show) . '</h3>';
                if (! empty($appointments) && isset($appointments[0]->id)) {
                    $output .= '<ul>';
                    foreach ($appointments as $appointment) {
                        $output .= '<li>' . esc_html($appointment->appointment) .  '</li>';
                        $appointment_ids[] = $appointment->id; // Collecter les IDs des rendez-vous
                    }
                    $cancel_url = add_query_arg(
                        array(
                            'action'         => 'annuler_rendez_vous',
                            'appointment_id' => implode(',', $appointment_ids),
                            'nonce'          => wp_create_nonce('annuler_rendez_vous_' . implode(',', $appointment_ids)),
                            'site_id'        => $site_id,
                        )
                    );
                    $output .= '<a href="' . esc_url($cancel_url) . '" class="elementor-button elementor-size-sm " style="margin-top: 10px;">' . 'Annuler ce rendez-vous' . '</a>';
                    $output .= '</ul>';
                } else {
                    $output .= '<p>Il n\' y a pas de rendez-vous à cette date, veuillez nous contacter si besoin.</p>';
                }
                return $output;
            } else {
                return '<p>Identifiant d\'utilisateur non valide.</p>';
            }
        }
        return  $output = '<p>Cette page n\'est pas accessible directement ! Vous devez disposez d\'un lien valide pour y accéder.</p>';
    }
}
