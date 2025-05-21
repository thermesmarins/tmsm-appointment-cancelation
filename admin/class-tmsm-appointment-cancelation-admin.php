<?php
if (! defined('ABSPATH')) {
    exit; // Sortir si l'accès direct est détecté.
}
class Tmsm_Appointment_Cancelation_Admin
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
        add_action('admin_init', array($this, 'tmsm_appointment_cancelation_admin_init'));
    }

    public function tmsm_appointment_cancelation_admin_init()
    {
        register_setting(
            'tmsm_options_group', // Le nom du groupe de réglages (utilisé dans settings_fields())
            'tmsm_appointment_cancelation_options', // Le nom de l'option dans la base de données
            array($this, 'sanitize_options') // Fonction de sanitisation (facultatif mais recommandé)
        );

        add_settings_section(
            'tmsm_general_settings_section', // ID unique de la section
            __('Global Settings', 'tmsm-appointment-cancelation'), // Titre de la section
            array($this, 'tmsm_general_settings_section_callback'), // Fonction de callback pour afficher la description de la section (facultatif)
            'tmsm-appointment-cancelation-settings' // Le slug de la page où afficher cette section (doit correspondre à do_settings_sections())
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
    }

    public function sanitize_options($input)
    {
        // Sanitize les données entrantes ici
        // Sanitize l'URL
        if (isset($input['aquos_appointment_cancellation_url'])) {
            $sanitized_input['aquos_appointment_cancellation_url'] = esc_url_raw($input['aquos_appointment_cancellation_url']);
        }
        if (isset($input['aquos_appointment_daily_url'])) {
            $sanitized_input['aquos_appointment_daily_url'] = esc_url_raw($input['aquos_appointment_daily_url']);
        }
        if (isset($input['aquos_appointment_api_days'])) {
            $sanitized_input['aquos_appointment_api_days'] = absint($input['aquos_appointment_api_days']);
        }
        if (isset($input['aquos_appointment_cancellation_token'])) {
            $sanitized_input['aquos_appointment_cancellation_token'] = sanitize_text_field($input['aquos_appointment_cancellation_token']);
        }
        if (isset($input['aquos_appointment_cancellation_deadline'])) {
            $sanitized_input['aquos_appointment_cancellation_deadline'] = sanitize_text_field($input['aquos_appointment_cancellation_deadline']);
        }
        return $sanitized_input;
    }

    public function section_callback($args)
    {
        // Affiche la description de la section
    }

    public function field_callback($args)
    {
        // Affiche le champ de formulaire (input, textarea, select, etc.)
        $options = get_option('tmsm_appointment_cancelation_options');
        $value = isset($options[$args['id']]) ? esc_attr($options[$args['id']]) : '';
        echo '<input type="text" id="' . esc_attr($args['id']) . '" name="tmsm_appointment_cancelation_options[' . esc_attr($args['id']) . ']" value="' . esc_attr($value) . '">';
    }
    function tmsm_appointment_cancelation_options_page()
    {
        add_submenu_page(
            'options-general.php',
            __('Appointment Cancelation', 'tmsm-appointment-cancelation'), // Titre de la sous-page
            __('Appointment Cancelation', 'tmsm-appointment-cancelation'), // Titre dans le menu
            'manage_options',
            'tmsm-appointment-cancelation',
            array($this, 'tmsm_appointment_cancelation_setting_page'),
            'dashicons-calendar-alt',
            6
        );
    }

    function tmsm_appointment_cancelation_setting_page()
    {
        if (! current_user_can('manage_options')) {
            return;
        }
?>
        <div class="wrap" >
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
    function tmsm_general_settings_section_callback()
    {
        echo '<p>' . __('Global settings options for appointment cancelation', 'tmsm-appointment-cancelation') . '</p>';
    }
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
}
