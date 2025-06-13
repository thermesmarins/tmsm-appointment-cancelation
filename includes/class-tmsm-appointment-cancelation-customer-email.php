<?php
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Tmsm_Appointment_Cancelation_Customer_Email {

    /**
     * Sends the cancellation confirmation email to the client.
     *
     * @param string $to The client's email address.
     * @param array $appointment_details Array of cancelled appointment objects.
     * @return bool True if email sent successfully, false otherwise.
     */
    public static function send_cancellation_confirmation(string $to, array $appointment_details): bool {
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

        $subject = isset($options['email_subject_client_confirmation']) ? $options['email_subject_client_confirmation'] : __('Your Appointment Cancellation Confirmation', 'tmsm-appointment-cancelation');
        // Todo : voir comment gérer les expéditeurs d'email
        $from_name = isset($options['email_from_name']) ? $options['email_from_name'] : get_bloginfo('name');
        $from_email = isset($options['email_from_email']) ? $options['email_from_email'] : get_bloginfo('admin_email');

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
        );

        $message_body = self::get_email_content($appointment_details);

        $sent = wp_mail($to, $subject, $message_body, $headers);

        if ($sent) {
            error_log('Client cancellation confirmation email sent to: ' . $to);
        } else {
            error_log('Failed to send client cancellation confirmation email to: ' . $to);
        }
        return $sent;
    }

    /**
     * Generates the HTML content for the client cancellation confirmation email.
     *
     * @param array $appointments Array of appointment objects.
     * @return string The HTML content for the email.
     */
    private static function get_email_content(array $appointments): string {
        ob_start();
        $data = array(
            'appointments' => $appointments,
            'site_name'    => get_bloginfo('name'), // Todo : Quel site ? 
            'site_url'     => home_url(),
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
}