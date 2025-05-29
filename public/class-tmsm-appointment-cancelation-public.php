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
     * Cache for user appointments.
     * This is used to store the appointments fetched from the API to avoid multiple calls.
     * It is initialized to null and will be populated when the appointments are loaded.
     * @since    1.0.0
     * @access   private
     * @var      array|WP_Error|null $user_appointments_cache
     */
    private $user_appointments_cache = null;

    /**
     * To know if the appointments have been loaded.
     * This is used to avoid multiple API calls for the same appointments.
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
            $date = isset($_GET['d']) ? sanitize_text_field($_GET['d']) : '';
            $options = get_option('tmsm_appointment_cancelation_options');
            $plugin_api_token = isset($options['aquos_appointment_cancellation_token']) ? esc_attr($options['aquos_appointment_cancellation_token']) : '';
            $aquos_cancel_appointments = new Tmsm_Appointment_Cancelation_Aquos($fonctionnal_id, $plugin_api_token);
            $site_id = isset($_GET['site_id']) ? sanitize_text_field($_GET['site_id']) : '';
           $can_cancel = $aquos_cancel_appointments->can_cancel_appointment($date);
            if (! $can_cancel) {
                wp_die(__('<p>You can not cancel this appointment because the limit is overdue. Please contact us.</p>', 'tmsm-appointment-cancelation'), __('Error', 'tmsm-appointment-cancelation'));
            }
            $cancel_status = false; 
            $cancel_status = $aquos_cancel_appointments->cancel_appointment($appointment_ids);
            // Rediriger l'utilisateur après l'action pour éviter les soumissions multiples
            $redirect_url = remove_query_arg(array('action', 'appointment_id', 'nonce', 'f', 't', 'd', 'site_id'));
            // Ajouter le statut de l'annulation
            if ($cancel_status) {
                $redirect_url = add_query_arg('cancel_status', 'success', $redirect_url);
            } else {
                $redirect_url = add_query_arg('cancel_status', 'error', $redirect_url);
            }
            wp_redirect($redirect_url);
            exit; 
        }
    }

    /**
     * Handle the content for the user appointments page.
     * This method checks if the current page is the user appointments page,
     * retrieves the appointment data, and formats it for display.
     *
     * @param string $content The original content of the page.
     * @return string The modified content with appointment information.
     */
    public function tmsm_handle_user_appointments_content($content)
    {
        // https://aquatonic.local/rennes/vos-rendez-vous/?f=304555AQREN&t=btwHqtVtGZ&d=2025.05.25
        global $wp_query;
        $output = ''; 
        if (isset($_GET['cancel_status'])) {
            $cancel_status = sanitize_text_field($_GET['cancel_status']);

            if ('success' === $cancel_status) {
                $output .= '<div class="tmsm-notification tmsm-notification-success">';
                $output .= '<p>' . __('Your appointment has been successfully canceled!', 'tmsm-appointment-cancelation') . '</p>';
                $output .= '</div>';
               
            } elseif ('error' === $cancel_status) {
                $output .= '<div class="tmsm-notification tmsm-notification-error">';
                $output .= '<p>' . __('An error occurred when cancelling your appointment. Please contact us.', 'tmsm-appointment-cancelation') . '</p>';
                $output .= '</div>';
            }
            return $output;
        }
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
            if ($fonctionnal_id) {
                if (! $this->appointments_loaded) {
                    $this->user_appointments_cache = $this->aquos_api_handler->get_user_appointments();
                    $this->appointments_loaded = true;
                }
                // Utiliser les rendez-vous en cache
                $appointments = $this->user_appointments_cache;
                $date_to_show = $this->aquos_api_handler->get_formatted_date($this->aquos_api_handler->get_aquos_appointment_date());
                $appointments_ids = [];
                $output = __('<h3> Reservation for ', 'tmsm-appointment-cancelation') . esc_html($date_to_show) . '</h3>';
                if (! empty($appointments) && isset($appointments[0]->id)) {
                    $output .= '<ul>';
                    foreach ($appointments as $appointment) {
                        $output .= '<li>' . esc_html($appointment->appointment) .  '</li>';
                        $appointment_ids[] = $appointment->id; 
                    }
                    $cancel_url = add_query_arg(
                        array(
                            'action'         => 'annuler_rendez_vous',
                            'appointment_id' => implode(',', $appointment_ids),
                            'nonce'          => wp_create_nonce('annuler_rendez_vous_' . implode(',', $appointment_ids)),
                            'site_id'        => $site_id,
                        )
                    );
                    if (count($appointment_ids) > 1) {
                        $output .= '<p class="tmsm-notification-warning">' . __('Clicking on the button below will cancel all appointments for the day. If you have any questions, please contact us', 'tmsm-appointment-cancelation') .'</p>';
                    } 
                    $output .= '<a href="' . esc_url($cancel_url) . '" class="elementor-button elementor-size-sm " style="margin-top: 10px;">' . __('Cancel this appointment','tmsm-appointment-cancelation') . '</a>';
                    $output .= '</ul>';
                } else {
                    $output .= '<p>' . __('There are no appointments on this date, please contact us if needed.', 'tmsm-appointment-cancelation') . '</p>';
                }
                return $output;
            } else {
                return '<p>'. __('Your login is invalid.', 'tmsm-appointment-cancelation').'</p>';
            }
        }
        return  $content;
    }
}
