<?php
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Tmsm_Appointment_Cancelation_Customer_Email
{


    /**
     * Sends the cancellation confirmation email to the client.
     *
     * @param string $to The client's email address.
     * @param array $appointment_details Array of cancelled appointment objects.
     * @return bool True if email sent successfully, false otherwise.
     */
    public static function send_cancellation_confirmation(string $to, array $appointment_details, int $site_id): bool
    {
        $options = get_option('tmsm_appointment_cancelation_options');


        $enable_email = isset($options['email_enable_client_confirmation']) ? (bool)$options['email_enable_client_confirmation'] : false;
        if (!$enable_email) {
            error_log('Client cancellation confirmation email disabled.');
            return false;
        }

        if (empty($to) || !is_email($to)) {
            error_log('Invalid or missing client email address for confirmation.');
            return false;
        }

        // $site_informations = self::get_site_informations($site_id);
        $site_informations = tmsm_get_site_informations($site_id);


        $subject = isset($options['email_subject_client_confirmation']) ? $options['email_subject_client_confirmation'] : __('Your Appointment Cancellation Confirmation', 'tmsm-appointment-cancelation');
        // // Todo : voir comment gérer les expéditeurs d'email
        // $from_name = isset($options['email_from_name']) ? $options['email_from_name'] : get_bloginfo('name');
        // $from_email = isset($options['email_from_email']) ? $options['email_from_email'] : get_bloginfo('admin_email');
        $from_name = $site_informations['name'];
        $from_email = $site_informations['email'];

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
        );
        // --- NOUVEAU: Définir l'expéditeur via le filtre Mailjet ---
        // Cette fonction temporaire sera appelée par le filtre Mailjet.
        $set_mailjet_sender = function ($email_data) use ($from_email, $from_name) {
            error_log('Mailjet filter activated. Attempting to set sender: ' . $from_name . ' <' . $from_email . '>');

            // Assurez-vous que l'index 'From' est un tableau si Mailjet l'attend ainsi
            // ou vérifiez la structure exacte attendue par le plugin Mailjet
            // (souvent un tableau associatif avec 'Email' et 'Name').
            // La documentation Mailjet suggère souvent un tableau d'objets ou un tableau simple.
            // Pour le plugin WordPress, c'est généralement un tableau associatif simple.
            $email_data['From'] = ['Email' => $from_email, 'Name' => $from_name];

            // Alternative si Mailjet s'attend à 'Headers' array pour From
            // $email_data['Headers']['From'] = $from_name . ' <' . $from_email . '>';

            return $email_data;
        };

        // Ajouter le filtre AVANT d'appeler wp_mail()
        // La priorité par défaut est 10, un nombre plus grand donne une priorité plus basse
        // mais le plus important est de s'assurer que c'est le bon filtre.
        add_filter('mailjet_send_email_data', $set_mailjet_sender, 100);


        $message_body = self::get_email_content($appointment_details, $site_informations);
        error_log('Sending client cancellation confirmation email to: ' . $to);
        error_log('Email subject: ' . $subject);
        error_log('Email headers: ' . print_r($headers, true));
        error_log('Email body: ' . $message_body);
        // Todo : uncomment the next line to actually send the email
        $sent = wp_mail($to, $subject, $message_body, $headers);

        if ($sent) {
            error_log('Client cancellation confirmation email sent to: ' . $to);
        } else {
            error_log('Failed to send client cancellation confirmation email to: ' . $to);
        }
        return $sent;
        //return true; // For testing purposes, always return true
    }

    /**
     * Generates the HTML content for the client cancellation confirmation email.
     *
     * @param array $appointments Array of appointment objects.
     * @return string The HTML content for the email.
     */
    private static function get_email_content(array $appointments, array $site_informations): string
    {
        ob_start();
        $data = array(
            'appointments' => $appointments,
            'site_name'    => $site_informations['name'],
            'site_url'     => $site_informations['url'],
            'options'      => get_option('tmsm_appointment_cancelation_options'),
        );

        $template_path = plugin_dir_path(dirname(__FILE__)) . 'templates/email-cancellation-confirmation.php';

        if (file_exists($template_path)) {
            extract($data); // Makes $appointments, $site_name, etc. available in the template.
            include $template_path;
        } else {
            error_log('Error: Client email template file not found: ' . $template_path);
            // Fallback content (ensure this fallback is robust)
            echo '<p>' . esc_html__('Dear customer,', 'tmsm-appointment-cancelation') . '</p>';
            echo '<p>' . esc_html__('This email confirms that your appointment(s) have been successfully cancelled:', 'tmsm-appointment-cancelation') . '</p>';
            echo '<ul>';
            foreach ($appointments as $appointment) {
                // You'll need a way to format the date here if your template relies on it.
                // Assuming tmsm_format_date_for_email function is globally available or in template
                $formatted_date = function_exists('tmsm_format_date_for_email') ? tmsm_format_date_for_email($appointment->appointment_date) : $appointment->appointment_date;
                $formatted_time = isset($appointment->appointment_time) ? substr($appointment->appointment_time, 0, 2) . ':' . substr($appointment->appointment_time, 2, 2) : '';
                echo '<li>' . esc_html($appointment->appointment) . ' - ' . esc_html($formatted_date) . ' ' . esc_html($formatted_time) . '</li>';
            }
            echo '</ul>';
            echo '<p>' . esc_html__('If you have any questions, please do not hesitate to contact us.', 'tmsm-appointment-cancelation') . '</p>';
        }
        return ob_get_clean();
    }
    private static function get_site_informations(int $site_id): array
    {
        $site_emails = array(
            array(
                'email' => 'rennes@aquatonic.fr',
                'id' => 0,
                'name' => 'Aquatonic Rennes',
                'url' => 'https://aquatonic.fr/rennes',
            ),
            array(
                'email' => 'paris@aquatonic.fr',
                'id' => 2,
                'name' => 'Aquatonic Paris',
                'url' => 'https://aquatonic.fr/paris',
            ),
            array(
                'email' => 'nantes@aquatonic.fr',
                'id' => 5,
                'name' => 'Aquatonic Nantes',
                'url' => 'https://aquatonic.fr/nantes',
            ),
            array(
                'email' => 'rennes@aquatonic.local',
                'id' => 10,
                'name' => 'Aquatonic Rennes Local',
                'url' => 'https://aquatonic.local/rennes',
            )
        );

        $site_informations = "";
        foreach ($site_emails as $site_email) {
            if ($site_email['id'] === $site_id) {
                $site_informations = array(
                    'email' => $site_email['email'],
                    'name' => $site_email['name'],
                    'id' => $site_email['id'],
                    'url' => $site_email['url'],
                );
                break;
            }
        }
        return $site_informations;
    }
}
