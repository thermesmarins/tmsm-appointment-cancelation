<?php
if (! defined('ABSPATH')) {
    exit; // Sortir si l'accès direct est détecté.
}

class Tmsm_Appointment_Cancelation_Admin
{
    /**
     * The unique identifier of this plugin.
     *
     * @since     1.0.0
     * @access    protected
     * @var       string      $plugin_name      The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since     1.0.0
     * @access    protected
     * @var       string      $version          The current version of the plugin.
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
        
        // Hook pour l'initialisation des réglages admin
        add_action('admin_init', array($this, 'tmsm_appointment_cancelation_admin_init'));
        // Hook pour ajouter la page de menu admin
        // add_action('admin_menu', array($this, 'tmsm_appointment_cancelation_options_page'));
    }

    public function tmsm_appointment_cancelation_admin_init()
    {
        register_setting(
            'tmsm_options_group', // Le nom du groupe de réglages (utilisé dans settings_fields())
            'tmsm_appointment_cancelation_options', // Le nom de l'option dans la base de données
            array($this, 'sanitize_options') // Fonction de sanitisation
        );

        // --- Section: Global Settings (Existante) ---
        add_settings_section(
            'tmsm_general_settings_section', // ID unique de la section
            __('Global Settings', 'tmsm-appointment-cancelation'), // Titre de la section
            array($this, 'tmsm_general_settings_section_callback'), // Fonction de callback pour afficher la description de la section
            'tmsm-appointment-cancelation-settings' // Le slug de la page où afficher cette section
        );

        add_settings_field(
            'aquos_appointment_cancellation_deadline', // ID unique du champ
            __('Deadline Cancelation', 'tmsm-appointment-cancelation'), // Titre du champ
            array($this, 'tmsm_cancellation_deadline_field_callback'), // Fonction de callback pour afficher le champ
            'tmsm-appointment-cancelation-settings', // Le slug de la page où afficher ce champ
            'tmsm_general_settings_section' // L'ID de la section à laquelle ce champ appartient
        );
        add_settings_field(
            'aquos_appointment_cancellation_url', // ID unique du champ
            __('Cancelation Url', 'tmsm-appointment-cancelation'), // Titre du champ
            array($this, 'tmsm_cancellation_url_field_callback'), // Fonction de callback pour afficher le champ
            'tmsm-appointment-cancelation-settings', // Le slug de la page où afficher ce champ
            'tmsm_general_settings_section' // L'ID de la section à laquelle ce champ appartient
        );
        add_settings_field(
            'aquos_appointment_daily_url', // ID unique du champ
            __('Daily Url', 'tmsm-appointment-cancelation'), // Titre du champ
            array($this, 'tmsm_appointment_daily_url_field_callback'), // Fonction de callback pour afficher le champ
            'tmsm-appointment-cancelation-settings', // Le slug de la page où afficher ce champ
            'tmsm_general_settings_section' // L'ID de la section à laquelle ce champ appartient
        );
        add_settings_field(
            'aquos_appointment_cancelation_token', // ID unique du champ
            __('Cancelation Token', 'tmsm-appointment-cancelation'), // Titre du champ
            array($this, 'tmsm_cancellation_token_field_callback'), // Fonction de callback pour afficher le champ
            'tmsm-appointment-cancelation-settings', // Le slug de la page où afficher ce champ
            'tmsm_general_settings_section' // L'ID de la section à laquelle ce champ appartient
        );

        // --- NOUVELLE SECTION: Paramètres généraux des E-mails ---
        add_settings_section(
            'tmsm_email_general_settings_section',
            __('General Email Settings', 'tmsm-appointment-cancelation'),
            array($this, 'email_general_settings_section_callback'),
            'tmsm-appointment-cancelation-settings'
        );

        add_settings_field(
            'email_from_name',
            __('Sender Name', 'tmsm-appointment-cancelation'),
            array($this, 'text_input_callback_with_options'), // Réutilisation d'une callback plus générique
            'tmsm-appointment-cancelation-settings',
            'tmsm_email_general_settings_section',
            array(
                'name'      => 'email_from_name',
                'description' => __('The name that appears as the sender of cancellation emails.', 'tmsm-appointment-cancelation'),
                'default'   => get_bloginfo('name'),
            )
        );

        add_settings_field(
            'email_from_email',
            __('Sender Email Address', 'tmsm-appointment-cancelation'),
            array($this, 'text_input_callback_with_options'), // Utiliser une callback générique pour email aussi
            'tmsm-appointment-cancelation-settings',
            'tmsm_email_general_settings_section',
            array(
                'name'      => 'email_from_email',
                'description' => __('The email address from which cancellation emails are sent. Use a valid email, preferably from your domain, to improve deliverability.', 'tmsm-appointment-cancelation'),
                'default'   => get_bloginfo('admin_email'),
                'type'      => 'email', // Indice pour la sanitization si elle était plus complexe
            )
        );

        // --- NOUVELLE SECTION: E-mail de confirmation client ---
        add_settings_section(
            'tmsm_email_client_settings_section',
            __('Client Email Confirmation', 'tmsm-appointment-cancelation'),
            array($this, 'email_client_settings_section_callback'),
            'tmsm-appointment-cancelation-settings'
        );

        add_settings_field(
            'email_enable_client_confirmation',
            __('Enable Client Email', 'tmsm-appointment-cancelation'),
            array($this, 'checkbox_input_callback_with_options'),
            'tmsm-appointment-cancelation-settings',
            'tmsm_email_client_settings_section',
            array(
                'name'      => 'email_enable_client_confirmation',
                'description' => __('Send a confirmation email to the client after cancellation.', 'tmsm-appointment-cancelation'),
                'default'   => true, // Enabled by default
            )
        );

        add_settings_field(
            'email_subject_client_confirmation',
            __('Client Email Subject', 'tmsm-appointment-cancelation'),
            array($this, 'text_input_callback_with_options'),
            'tmsm-appointment-cancelation-settings',
            'tmsm_email_client_settings_section',
            array(
                'name'      => 'email_subject_client_confirmation',
                'description' => __('Subject line for the client cancellation confirmation email.', 'tmsm-appointment-cancelation'),
                'default'   => __('Your Appointment Cancellation Confirmation', 'tmsm-appointment-cancelation'),
            )
        );

        // --- NOUVELLE SECTION: Notification E-mail Administrateur ---
        add_settings_section(
            'tmsm_email_admin_settings_section',
            __('Admin Email Notification', 'tmsm-appointment-cancelation'),
            array($this, 'email_admin_settings_section_callback'),
            'tmsm-appointment-cancelation-settings'
        );

        add_settings_field(
            'email_enable_admin_notification',
            __('Enable Admin Email', 'tmsm-appointment-cancelation'),
            array($this, 'checkbox_input_callback_with_options'),
            'tmsm-appointment-cancelation-settings',
            'tmsm_email_admin_settings_section',
            array(
                'name'      => 'email_enable_admin_notification',
                'description' => __('Send a notification email to the administrator after cancellation.', 'tmsm-appointment-cancelation'),
                'default'   => true, // Enabled by default
            )
        );

        add_settings_field(
            'email_admin_recipient',
            __('Admin Recipient Email(s)', 'tmsm-appointment-cancelation'),
            array($this, 'text_input_callback_with_options'), // Peut être du texte pour plusieurs emails séparés par des virgules
            'tmsm-appointment-cancelation-settings',
            'tmsm_email_admin_settings_section',
            array(
                'name'      => 'email_admin_recipient',
                'description' => __('Email address(es) to receive cancellation notifications. Use commas for multiple addresses.', 'tmsm-appointment-cancelation'),
                'default'   => get_bloginfo('admin_email'),
                'type'      => 'text', // Ce n'est pas un type email standard si c'est pour plusieurs
            )
        );
        
        add_settings_field(
            'email_subject_admin_notification',
            __('Admin Email Subject', 'tmsm-appointment-cancelation'),
            array($this, 'text_input_callback_with_options'),
            'tmsm-appointment-cancelation-settings',
            'tmsm_email_admin_settings_section',
            array(
                'name'      => 'email_subject_admin_notification',
                'description' => __('Subject line for the admin cancellation notification email.', 'tmsm-appointment-cancelation'),
                'default'   => __('Appointment Cancellation Notification', 'tmsm-appointment-cancelation'),
            )
        );
    }

    public function sanitize_options($input)
    {
        $sanitized_input = array();

        // Sanitize existing fields
        if (isset($input['aquos_appointment_cancellation_url'])) {
            $sanitized_input['aquos_appointment_cancellation_url'] = esc_url_raw($input['aquos_appointment_cancellation_url']);
        }
        if (isset($input['aquos_appointment_daily_url'])) {
            $sanitized_input['aquos_appointment_daily_url'] = esc_url_raw($input['aquos_appointment_daily_url']);
        }
        if (isset($input['aquos_appointment_api_days'])) { // Ceci n'est pas dans add_settings_field, mais présent dans sanitize_options. Vérifiez si ce champ est utilisé.
            $sanitized_input['aquos_appointment_api_days'] = absint($input['aquos_appointment_api_days']);
        }
        if (isset($input['aquos_appointment_cancellation_token'])) {
            $sanitized_input['aquos_appointment_cancellation_token'] = sanitize_text_field($input['aquos_appointment_cancellation_token']);
        }
        if (isset($input['aquos_appointment_cancellation_deadline'])) {
            $sanitized_input['aquos_appointment_cancellation_deadline'] = absint($input['aquos_appointment_cancellation_deadline']);
        }

        // --- Sanitize NOUVEAUX champs E-mail ---
        if (isset($input['email_from_name'])) {
            $sanitized_input['email_from_name'] = sanitize_text_field($input['email_from_name']);
        }
        if (isset($input['email_from_email'])) {
            $sanitized_input['email_from_email'] = sanitize_email($input['email_from_email']);
        }

        $sanitized_input['email_enable_client_confirmation'] = isset($input['email_enable_client_confirmation']) ? (bool) $input['email_enable_client_confirmation'] : false;
        if (isset($input['email_subject_client_confirmation'])) {
            $sanitized_input['email_subject_client_confirmation'] = sanitize_text_field($input['email_subject_client_confirmation']);
        }

        $sanitized_input['email_enable_admin_notification'] = isset($input['email_enable_admin_notification']) ? (bool) $input['email_enable_admin_notification'] : false;
        if (isset($input['email_admin_recipient'])) {
            // Sanitize multiple emails if comma separated
            $emails = explode(',', $input['email_admin_recipient']);
            $sanitized_emails = array_filter(array_map('sanitize_email', $emails)); // Filtre les adresses non valides
            $sanitized_input['email_admin_recipient'] = implode(',', $sanitized_emails);
        }
        if (isset($input['email_subject_admin_notification'])) {
            $sanitized_input['email_subject_admin_notification'] = sanitize_text_field($input['email_subject_admin_notification']);
        }

        return $sanitized_input;
    }

    // --- Callback pour afficher les descriptions de sections ---
    public function tmsm_general_settings_section_callback()
    {
        echo '<p>' . __('Global settings options for appointment cancelation', 'tmsm-appointment-cancelation') . '</p>';
    }
    public function email_general_settings_section_callback() {
        echo '<p>' . esc_html__('Configure the general settings for cancellation emails.', 'tmsm-appointment-cancelation') . '</p>';
    }
    public function email_client_settings_section_callback() {
        echo '<p>' . esc_html__('Configure the email sent to clients after a cancellation.', 'tmsm-appointment-cancelation') . '</p>';
    }
    public function email_admin_settings_section_callback() {
        echo '<p>' . esc_html__('Configure the email sent to administrators after a cancellation.', 'tmsm-appointment-cancelation') . '</p>';
    }

    // --- Callback pour afficher les champs existants ---
    function tmsm_cancellation_deadline_field_callback()
    {
        $options = get_option('tmsm_appointment_cancelation_options');
        $value = isset($options['aquos_appointment_cancellation_deadline']) ? esc_attr($options['aquos_appointment_cancellation_deadline']) : '';
        echo '<input type="number" id="aquos_appointment_cancellation_deadline" name="tmsm_appointment_cancelation_options[aquos_appointment_cancellation_deadline]" value="' . esc_attr($value) . '" >';
    }
    function tmsm_cancellation_url_field_callback()
    {
        $options = get_option('tmsm_appointment_cancelation_options');
        $value = isset($options['aquos_appointment_cancellation_url']) ? esc_attr($options['aquos_appointment_cancellation_url']) : '';
        echo '<input type="text" id="aquos_appointment_cancellation_url" name="tmsm_appointment_cancelation_options[aquos_appointment_cancellation_url]" value="' . esc_attr($value) . '"style="width: 500px;">';
    }
    function tmsm_appointment_daily_url_field_callback()
    {
        $options = get_option('tmsm_appointment_cancelation_options');
        $value = isset($options['aquos_appointment_daily_url']) ? esc_attr($options['aquos_appointment_daily_url']) : '';
        echo '<input type="text" id="aquos_appointment_daily_url" name="tmsm_appointment_cancelation_options[aquos_appointment_daily_url]" value="' . esc_attr($value) . '"style="width: 500px;">';
    }
    function tmsm_cancellation_token_field_callback()
    {
        $options = get_option('tmsm_appointment_cancelation_options');
        $value = isset($options['aquos_appointment_cancellation_token']) ? esc_attr($options['aquos_appointment_cancellation_token']) : '';
        echo '<input type="text" id="aquos_appointment_cancellation_token" name="tmsm_appointment_cancelation_options[aquos_appointment_cancellation_token]" value="' . esc_attr($value) . '"style="width: 500px;">';
    }

    // --- NOUVELLE: Callback générique pour les champs texte (utilisée pour les emails) ---
    public function text_input_callback_with_options($args) {
        $options = get_option('tmsm_appointment_cancelation_options'); // Utilisez le nom de votre option
        $value = isset($options[$args['name']]) ? esc_attr($options[$args['name']]) : '';
        if (isset($args['default']) && empty($value)) { // Applique la valeur par défaut si non définie
            $value = esc_attr($args['default']);
        }
        $type = isset($args['type']) ? esc_attr($args['type']) : 'text'; // Permet de définir le type (text, email, etc.)
        echo '<input type="' . $type . '" id="' . esc_attr($args['name']) . '" name="tmsm_appointment_cancelation_options[' . esc_attr($args['name']) . ']" value="' . $value . '" class="regular-text">';
        if (!empty($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }

    // --- NOUVELLE: Callback générique pour les champs checkbox (utilisée pour les emails) ---
    public function checkbox_input_callback_with_options($args) {
        $options = get_option('tmsm_appointment_cancelation_options'); // Utilisez le nom de votre option
        // La valeur peut être booléenne ou '1'/'0' selon comment elle est sauvegardée
        $checked = isset($options[$args['name']]) ? (bool) $options[$args['name']] : (bool) $args['default'];
        echo '<label>';
        echo '<input type="checkbox" id="' . esc_attr($args['name']) . '" name="tmsm_appointment_cancelation_options[' . esc_attr($args['name']) . ']" value="1"' . checked(1, $checked, false) . '>';
        echo ' ' . esc_html($args['description']);
        echo '</label>';
    }
    
    // --- Fonction d'ajout de la page de menu (existante, mais corrigée) ---
    function tmsm_appointment_cancelation_options_page()
    {
        // Votre slug de page est 'tmsm-appointment-cancelation-settings' dans register_settings et do_settings_sections
        add_submenu_page(
            'options-general.php', // Parent slug
            __('Appointment Cancellation Settings', 'tmsm-appointment-cancelation'), // Page title
            __('Appointment Cancellation', 'tmsm-appointment-cancelation'), // Menu title
            'manage_options', // Capability
            'tmsm-appointment-cancelation-settings', // Page slug (doit correspondre à do_settings_sections)
            array($this, 'tmsm_appointment_cancelation_setting_page') // Callback function
        );
    }

    // --- Fonction d'affichage de la page de réglages (existante) ---
    function tmsm_appointment_cancelation_setting_page()
    {
        if (! current_user_can('manage_options')) {
            return;
        }
?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                // Output security fields for the registered setting group
                settings_fields('tmsm_options_group');
                // Output setting sections and their fields for your page slug
                do_settings_sections('tmsm-appointment-cancelation-settings');
                // Output save settings button
                submit_button(__('Save Parameters', 'tmsm-appointment-cancelation'));
                ?>
            </form>
        </div>
<?php
    }
}