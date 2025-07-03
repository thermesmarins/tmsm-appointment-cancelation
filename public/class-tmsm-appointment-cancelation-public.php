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
        if (isset($_POST['action']) && $_POST['action'] === 'annuler_rendez_vous' && isset($_POST['appointment_ids'])) {
            // Vérifier le nonce pour la sécurité
            if (! isset($_POST['tmsm_cancel_nonce']) || ! wp_verify_nonce($_POST['tmsm_cancel_nonce'], 'annuler_rendez_vous_action')) {
                // wp_die('<p>Nonce invalide. Action non autorisée.</p>', __('Error', 'tmsm-appointment-cancelation'));
                wp_redirect(add_query_arg('cancel_status', 'nonce_error', home_url('/rdv/')));
                exit;
            }
            $appointments_details_json = isset($_POST['appointments_details']) ? wp_unslash($_POST['appointments_details']) : '';

            $cancelled_appointments_details = [];

            if (!empty($appointments_details_json)) {
                $decoded_details = json_decode($appointments_details_json);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_details)) {
                    $cancelled_appointments_details = $decoded_details;
                    error_log('Détails des rendez-vous décodés avec succès : ' . print_r($cancelled_appointments_details, true));
                } else {
                    error_log('Erreur lors du décodage des détails des rendez-vous JSON: ' . json_last_error_msg() . ' JSON String: ' . $appointments_details_json);
                    // En cas d'erreur de décodage, vous pouvez choisir de rediriger
                    // ou de simplement ne pas avoir les détails dans l'email, selon la criticité.
                    wp_redirect(add_query_arg('cancel_status', 'json_error', home_url('/rdv/')));
                    exit;
                }
            } else {
                error_log('Aucun détail de rendez-vous JSON n\'a été fourni dans la requête POST. Cela peut indiquer une soumission incorrecte du formulaire.');
                // Si les détails sont obligatoires pour l'e-mail, vous pouvez rediriger ici aussi.
                wp_redirect(add_query_arg('cancel_status', 'missing_details', home_url('/rdv/')));
                exit;
            }
            $appointment_ids_string = isset($_POST['appointment_ids']) ? sanitize_text_field($_POST['appointment_ids']) : '';
            $appointment_ids = array_map('intval', explode(',', $appointment_ids_string));
            $fonctionnal_id = isset($_POST['fonctionnal_id']) ? sanitize_text_field($_POST['fonctionnal_id']) : '';
            $aquos_cancel_appointments = new Tmsm_Appointment_Cancelation_Aquos($fonctionnal_id);
            $site_id = isset($_POST['site_id']) ? sanitize_text_field($_POST['site_id']) : '';
            $cancel_status = false;
            if ($site_id == Tmsm_Appointment_Cancelation_Aquos::SITE_ID_TEST) {
                error_log('Annulation des rendez-vous en mode test');
                $cancel_status = $aquos_cancel_appointments->cancel_appointment($appointment_ids);
            } else {
                $cancel_status = false;
            }
            $user_email = isset($_POST['email_confirm']) ? sanitize_email($_POST['email_confirm']) : '';
            $customer_name = isset($_POST['customer_name']) ? sanitize_text_field($_POST['customer_name']) : '';
            $redirect_url_base = home_url('/rdv/');
            // $cancel_status = true; // Simuler le succès de l'annulation pour les tests
            // Ajouter le statut de l'annulation
            if ($cancel_status) {
                $appointments_details = $cancelled_appointments_details;
                // ALL appointments successfully cancelled.
                // 1. Send email to the client using the new class
                Tmsm_Appointment_Cancelation_Customer_Email::send_cancellation_confirmation($user_email, $appointments_details, $site_id);
                // 2. Send email to the administrator using the new class
                Tmsm_Appointment_Cancelation_Admin_Email::send_cancellation_notification($appointments_details, $user_email, $site_id);
                $redirect_url = add_query_arg('cancel_status', 'success', $redirect_url_base);
            } else {
                $redirect_url = add_query_arg('cancel_status', 'error', $redirect_url_base);
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
        // https://aquatonic.local/rdv/?f=304409AQREN&t=h6Ejsl0wkq&d=2025.06.29
        global $wp_query;
        $output = '';
        if (isset($_GET['cancel_status'])) {
            $cancel_status = sanitize_text_field($_GET['cancel_status']);

            if ('success' === $cancel_status) {
                $output .= '<div class="tmsm-notification tmsm-notification-success">';
                $output .= '<span>' . __('Your appointment has been successfully canceled!', 'tmsm-appointment-cancelation') . '</span>';
                $output .= '</div>';
            } elseif ('error' === $cancel_status) {
                $output .= '<div class="tmsm-notification tmsm-notification-error">';
                $output .= '<span>' . __('An error occurred when cancelling your appointment. Please contact us.', 'tmsm-appointment-cancelation') . '</span>';
                $output .= '</div>';
            }
            return $output;
        }
        if (is_page('vos-rendez-vous') || is_page('rdv') && isset($wp_query->query_vars['f']) && isset($wp_query->query_vars['t'])) {
            $fonctionnal_id = get_query_var('f');
            $aquos_appointment_signature = get_query_var('t');
            if (!empty($aquos_appointment_signature) && strpos($aquos_appointment_signature, ' ') !== false) {
                // Si des espaces sont trouvés, nous les reconvertissons en '+'.
                $aquos_appointment_signature = str_replace(' ', '+', $aquos_appointment_signature);
                error_log('Signature après correction des espaces -> + : ' . $aquos_appointment_signature);
            }
            // $date = get_query_var('d');
            $date_to_show = '';
            if (is_null($this->aquos_api_handler)) {
                $this->aquos_api_handler = new Tmsm_Appointment_Cancelation_Aquos($fonctionnal_id, $aquos_appointment_signature);
                // $this->aquos_api_handler = new Tmsm_Appointment_Cancelation_Aquos($fonctionnal_id, $aquos_appointment_signature, $date);

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
                $date_to_show = ($appointments[0]->appointment_date != null) ? $this->aquos_api_handler->get_formatted_date($appointments[0]->appointment_date) : '';
                $client_name = $this->aquos_api_handler->get_customer_identity() ?? '';
                $appointment_ids = [];
                // $output = !empty($date_to_show) ? '<p>' . sprintf(__('Hello %s <br/> your reservation for %s', 'tmsm-appointment-cancelation'), $client_name, esc_html($date_to_show)) . '</p>' : "";
                if (! empty($appointments) && isset($appointments[0]->id)) {
                    $any_cancellable_appointment = false; // Flag pour savoir si au moins un RDV est annulable
                    $current_user_email = $appointments[0]->email ?? ''; // Récupère l'email du premier RDV pour vérification
                    $options = get_option('tmsm_appointment_cancelation_options');
                    $cancel_delay_hours = isset($options['aquos_appointment_cancellation_deadline']) ? $options['aquos_appointment_cancellation_deadline'] : 24; // Valeur par défaut de 24h
                    $output .= '<p>' . sprintf(__('Hello %s <br/> your reservation for %s', 'tmsm-appointment-cancelation'), $client_name, esc_html($date_to_show)) . '</p>';
                    foreach ($appointments as $appointment) {
                        $output .= '<li>' . esc_html($appointment->appointment) .  '</li>';
                        $appointment_ids[] = $appointment->id;
                        $appointment_time_from_api = isset($appointment->appointment_hour) ? $appointment->appointment_hour : '0000';
                        if ($this->aquos_api_handler->can_appointment_be_cancelled(
                            $appointment->appointment_date,
                            $appointment_time_from_api,
                            $cancel_delay_hours
                        )) {
                            $any_cancellable_appointment = true; // Un rendez-vous est annulable
                        } else {
                            // Afficher un petit message à côté du RDV s'il n'est pas annulable individuellement
                            $output .= ' <span style="color: #999; font-size: 0.9em;">(' . esc_html__('Cancellation deadline passed', 'tmsm-appointment-cancelation') . ')</span><br/>';
                        }
                    }
                    if ($any_cancellable_appointment) {
                        $output .= '<form id="tmsm-cancel-form" method="post" action="' . esc_url(home_url('/rdv/')) . '" style="margin-top: 20px;">';

                        // Tous les champs que vous aviez en hidden sont toujours nécessaires
                        $output .= '<input type="hidden" name="action" value="annuler_rendez_vous">';
                        $output .= '<input type="hidden" name="appointment_ids" value="' . implode(',', $appointment_ids) . '">';
                        $output .= '<input type="hidden" name="site_id" value="' . esc_attr($site_id) . '">';
                        $output .= '<input type="hidden" name="fonctionnal_id" value="' . esc_attr($fonctionnal_id) . '">';
                        $output .= '<input type="hidden" name="aquos_appointment_signature" value="' . esc_attr($aquos_appointment_signature) . '">';
                        $output .= '<input type="hidden" name="date" value="' . esc_attr($this->aquos_api_handler->get_aquos_appointment_date()) . '">';
                        $output .= '<input type="hidden" name="customer_name" value="' . esc_attr($this->aquos_api_handler->get_customer_identity()) . '">';
                        $output .= '<input type="hidden" name="appointments_details" value="' . esc_attr(json_encode($appointments)) . '">';

                        $output .= wp_nonce_field('annuler_rendez_vous_action', 'tmsm_cancel_nonce', true, false); // Nom de l'action pour le nonce, Nom du champ hidden, true pour echo, false pour ne pas être un champ d'admin par défaut
                        $output .= '<p>';
                        $output .= '<label for="tmsm_email_confirm">' . esc_html__('Email for confirmation:', 'tmsm-appointment-cancelation') . '</label><br>';
                        $output .= '<input type="email" id="tmsm_email_confirm" name="email_confirm" value="' . esc_attr($current_user_email) . '" required style="width: 100%; max-width: 300px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">';
                        $output .= '<p class="description">' . esc_html__('Enter the email address where you wish to receive the cancellation confirmation.', 'tmsm-appointment-cancelation') . '</p>';
                        $output .= '</p>';

                        if (count($appointment_ids) > 1) {
                            // todo : ajout du lien d'email pour contacter l'établissement
                            $output .= '<p class="tmsm-notification tmsm-notification-warning">' . __('Clicking on the button below will cancel all appointments for the day. If you have any questions, please contact us', 'tmsm-appointment-cancelation') . '</p><br/>';
                        }
                        $output .= '<button type="submit" class="cancelation-button">' . __('Cancel this appointment', 'tmsm-appointment-cancelation') . '</button>';
                    } else {
                        // Todo : voir pour un lien avec Tel ? ou fiche contact ?
                        $home_url = tmsm_get_site_informations($site_id)['url'];
                        $terms_conditions_url = $home_url . '/conditions-generales-de-vente/';

                        // Le texte du lien pour les CGV
                        $terms_link_text = esc_html__('our Terms & Conditions', 'tmsm-appointment-cancelation');

                        // Créez le lien HTML pour les CGV avec l'URL exacte
                        $terms_html_link = '<a href="' . esc_url($terms_conditions_url) . '" style="color: #0073aa; text-decoration: none;">' . $terms_link_text . '</a>';

                        // Construisez la phrase complète avec le lien en utilisant sprintf
                        $output .= '<p>';
                        $output .= sprintf(
                            /* translators: 1: The reason for not being able to cancel, 2: HTML link to Terms & Conditions page */
                            esc_html__('%1$s. Please refer to %2$s or contact us if needed.', 'tmsm-appointment-cancelation'),
                            __('You cannot cancel this appointment because the cancellation deadline has passed', 'tmsm-appointment-cancelation'),
                            $terms_html_link
                        );
                        $output .= '</p>';
                    }
                } else {
                    $output .= '<p>' . __('There are no appointments on this date, please contact us if needed.', 'tmsm-appointment-cancelation') . '</p>';
                }
                
                return $output;
            } else {
                return '<p>' . __('Your login is invalid.', 'tmsm-appointment-cancelation') . '</p>';
            }
        }
        return  $content;
    }
}
