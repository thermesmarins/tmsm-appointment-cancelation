<?php
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Tmsm_Appointment_Cancelation_Admin_Email {

    /**
     * Sends the cancellation notification email to the administrator.
     *
     * @param array $appointment_details Array of cancelled appointment objects.
     * @param string $client_email The email address of the client who cancelled.
     * @return bool True if email sent successfully, false otherwise.
     */
    public static function send_cancellation_notification(array $appointment_details, string $client_email, int $site_id): bool {
        $options = get_option('tmsm_appointment_cancelation_options');

        $enable_email = isset($options['email_enable_admin_notification']) ? (bool)$options['email_enable_admin_notification'] : false;
        if (!$enable_email) {
            error_log('Admin cancellation notification email disabled.');
            return false;
        }
// Todo : voir comment gérer les emails pour les différents sites
        $site_informations = tmsm_get_site_informations($site_id);
        $to = isset($site_informations['admin_email']) ? sanitize_email($site_informations['admin_email']) : get_bloginfo('admin_email');
        if (empty($to) || !is_email($to)) {
            error_log('Invalid or missing admin email address for notification.');
            return false;
        }
        error_log('De qui : ' . $to);

        $subject = isset($options['email_subject_admin_notification']) ? $options['email_subject_admin_notification'] : __('Appointment Cancellation Notification', 'tmsm-appointment-cancelation');
        $from_name = isset($options['email_from_name']) ? $options['email_from_name'] : get_bloginfo('name');
        $from_email = isset($options['email_from_email']) ? $options['email_from_email'] : get_bloginfo('admin_email');

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
        );

        $message_body = self::get_email_content($appointment_details, $client_email);
        error_log('Sending admin cancellation notification email to: ' . $to);  
        error_log('Email admin subject: ' . $subject);
        error_log('Email admin headers: ' . print_r($headers, true));
        error_log('Email admin body: ' . $message_body);
        $sent = wp_mail($to, $subject, $message_body, $headers);

        if ($sent) {
            error_log('Admin cancellation notification email sent to: ' . $to);
        } else {
            error_log('Failed to send admin cancellation notification email to: ' . $to);
        }
        return $sent;
        // return true; // For testing purposes, always return true
    }

    /**
     * Generates the HTML content for the administrator cancellation notification email.
     *
     * @param array $appointments Array of appointment objects.
     * @param string $client_email The email address of the client.
     * @return string The HTML content for the email.
     */
    private static function get_email_content(array $appointments, string $client_email): string {
        ob_start();
        $data = array(
            'appointments' => $appointments,
            'client_email' => $client_email,
            'site_name'    => get_bloginfo('name'),
            'site_url'     => home_url(),
        );

        $template_path = plugin_dir_path(dirname(__FILE__)) . 'templates/email-admin-cancellation-notification.php';

        if (file_exists($template_path)) {
            extract($data); // Makes $appointments, $client_email, etc. available in the template.
            include $template_path;
        } else {
            error_log('Error: Admin email template file not found: ' . $template_path);
            // Fallback content (ensure this fallback is robust)
            echo '<p>' . esc_html__('An appointment has been cancelled on your website.', 'tmsm-appointment-cancelation') . '</p>';
            echo '<p>' . esc_html__('Client Email:', 'tmsm-appointment-cancelation') . ' ' . esc_html($client_email) . '</p>';
            echo '<p>' . esc_html__('Cancelled appointments:', 'tmsm-appointment-cancelation') . '</p>';
            echo '<ul>';
            foreach ($appointments as $appointment) {
                // You'll need a way to format the date here if your template relies on it.
                $formatted_date = function_exists('tmsm_format_date_for_email') ? tmsm_format_date_for_email($appointment->appointment_date) : $appointment->appointment_date;
                $formatted_time = isset($appointment->appointment_time) ? substr($appointment->appointment_time, 0, 2) . ':' . substr($appointment->appointment_time, 2, 2) : '';
                echo '<li>' . esc_html($appointment->appointment) . ' - ' . esc_html($formatted_date) . ' ' . esc_html($formatted_time) . '</li>';
            }
            echo '</ul>';
        }
        return ob_get_clean();
    }
}