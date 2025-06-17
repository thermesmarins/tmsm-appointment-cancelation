<?php

/**
 * Email template for client cancellation confirmation.
 *
 * This template is loaded by Tmsm_Appointment_Cancelation_Customer_Email::get_email_content().
 *
 * Available variables:
 * - $appointments (array): Array of cancelled appointment objects (id, appointment_date, appointment, etc.).
 * - $site_name (string): Name of the WordPress site.
 * - $site_url (string): URL of the WordPress site.
 * - $options (array): Plugin options, potentially useful for custom text or links.
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

?>

<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title><?php echo esc_html($site_name); ?> - <?php echo esc_html__('Appointment Cancellation Confirmation', 'tmsm-appointment-cancelation'); ?></title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
        <h2 style="color: #d9534f; text-align: center;"><?php echo esc_html__('Your Appointment Cancellation Has Been Confirmed', 'tmsm-appointment-cancelation'); ?></h2>
        <p><?php echo esc_html__('Hello ' . $appointments[0]->customer . ',', 'tmsm-appointment-cancelation'); ?></p>

        <p><?php echo esc_html__('This email confirms that your appointment(s) have been successfully cancelled with', 'tmsm-appointment-cancelation'); ?> <?php echo esc_html($site_name); ?>:</p>

        <ul style="list-style-type: none; padding: 0;">
            <?php foreach ($appointments as $appointment) : ?>
                <?php
                $formatted_date = tmsm_format_date_for_email($appointment->appointment_date);
                $formatted_time = isset($appointment->appointment_hour) ? substr($appointment->appointment_hour, 0, 2) . ':' . substr($appointment->appointment_hour, 2, 2) : '';
                ?>
                <li style="margin-bottom: 10px; padding: 10px; background-color: #f9f9f9; border-left: 3px solid #d9534f;">
                    <strong><?php echo esc_html($appointment->appointment); ?></strong><br>
                    <?php echo esc_html__('Date:', 'tmsm-appointment-cancelation'); ?> <?php echo esc_html($formatted_date); ?>
                    <?php if (!empty($formatted_time)) : ?>
                        <br><?php echo esc_html__('Time:', 'tmsm-appointment-cancelation'); ?> <?php echo esc_html($formatted_time); ?>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <p><?php
            // Le texte du lien
            $link_text = esc_html__('our booking page', 'tmsm-appointment-cancelation');

            // Créez le lien HTML simple
            $reschedule_html_link = '<a href="' . esc_url($resaspa_url) . '" style="color: #0073aa; text-decoration: none;">' . $link_text . '</a>';

            // Construisez la phrase avec sprintf et le lien HTML
            $translated_text = sprintf(
                /* translators: %s: HTML link to the booking/rescheduling page */
                esc_html__('If you want to reschedule your appointment, please visit %s.', 'tmsm-appointment-cancelation'),
                $reschedule_html_link
            );
            echo $translated_text;
            ?></p>
        <p><?php
            $link_to_admin_text = esc_html__('contact us', 'tmsm-appointment-cancelation');
            $admin_email_link = '<a href="mailto:' . esc_html($shop_email) . '" style="color: #0073aa; text-decoration: none;">' . $link_to_admin_text . '</a>';
            $translated_contact_text = sprintf(
                esc_html__('If you have any questions, please do not hesitate to %s.', 'tmsm-appointment-cancelation'),
                $admin_email_link
            );
            echo $translated_contact_text;
            // echo esc_html__('If you have any questions, please do not hesitate to contact us.', 'tmsm-appointment-cancelation'); 

            ?></p>

        <p style="text-align: center; margin-top: 30px;">
            <?php echo esc_html__('Sincerely,', 'tmsm-appointment-cancelation'); ?><br>
            <strong><?php echo esc_html($site_name); ?></strong><br>
            <a href="<?php echo esc_url($site_url); ?>" style="color: #0073aa; text-decoration: none;"><?php echo esc_url($site_url); ?></a>
        </p>

        <div style="text-align: center; margin-top: 20px; font-size: 14px; color: #888;">
            <?php echo esc_html__('This is an automated email, please do not reply directly to this message.', 'tmsm-appointment-cancelation'); ?>
        </div>
    </div>
</body>

</html>