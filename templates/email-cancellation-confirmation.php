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
    <title><?php echo esc_html($site_name); ?> - <?php echo esc_html__('Confirmation d\'annulation de Rendez-vous', 'tmsm-appointment-cancelation'); ?></title>
</head>


<body style="font-family: Arial, sans-serif; line-height: 1.6; color:black; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
        <div style="text-align: center; margin-bottom: 20px;">
            <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($site_name); ?> Logo" style="max-width: 300px; height: auto; display: inline-block;">
        </div>
        <h2 style="color: #0073aa; text-align: center;"><?php echo esc_html__('Annulation de votre rendez-vous.', 'tmsm-appointment-cancelation'); ?></h2>
        <p><?php echo esc_html__('Bonjour ' . tmsm_set_formatted_identity(
                $appointments[0]->customer_civility ?? '',
                $appointments[0]->customer_lastname ?? '',
                $appointments[0]->customer_firstname ?? ''
            ) . ',', 'tmsm-appointment-cancelation'); ?></p>

        <p><?php echo esc_html__('Suite à votre demande, nous vous confirmons que votre rendez-vous à l\'', 'tmsm-appointment-cancelation'); ?><?php echo esc_html($site_name); ?><?php echo esc_html__(' est annulé.', 'tmsm-appointment-cancelation'); ?></p>

        <ul style="list-style-type: none; padding: 0;">
            <?php foreach ($appointments as $appointment) : ?>
                <?php
                $formatted_date = tmsm_format_date_for_email($appointment->appointment_date);
                $formatted_time = isset($appointment->appointment_hour) ? substr($appointment->appointment_hour, 0, 2) . ':' . substr($appointment->appointment_hour, 2, 2) : '';
                ?>
                <li style="margin-bottom: 10px; padding: 10px; background-color: #f9f9f9;">
                    <strong><?php echo esc_html($appointment->appointment); ?></strong><br>
                    <?php echo esc_html__('Date:', 'tmsm-appointment-cancelation'); ?> <?php echo esc_html($formatted_date); ?>
                    <?php if (!empty($formatted_time)) : ?>
                        <br><?php echo esc_html__('Heure:', 'tmsm-appointment-cancelation'); ?> <?php echo esc_html($formatted_time); ?>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <p><?php
            // Le texte du lien
            $link_text = esc_html__('suivez-ce lien', 'tmsm-appointment-cancelation');

            // Créez le lien HTML simple
            $reschedule_html_link = '<a href="' . esc_url($resaspa_url) . '" style="color: #0073aa; text-decoration: none;">' . $link_text . '</a>';

            // Construisez la phrase avec sprintf et le lien HTML
            $translated_text = sprintf(
                /* translators: %s: HTML link to the booking/rescheduling page */
                esc_html__('Pour programmer un nouveau soin, %s.', 'tmsm-appointment-cancelation'),
                $reschedule_html_link
            );
            echo $translated_text;
            ?></p>
        <p><?php
            $link_to_admin_text = esc_html__('Contactez-nous', 'tmsm-appointment-cancelation');
            $admin_email_link = '<a href="mailto:' . esc_html($shop_email) . '" style="color: #0073aa; text-decoration: none;">' . $link_to_admin_text . '</a>';
            $translated_contact_text = sprintf(
                esc_html__('Une question ? %s.', 'tmsm-appointment-cancelation'),
                $admin_email_link
            );
            echo $translated_contact_text;
            // echo esc_html__('If you have any questions, please do not hesitate to contact us.', 'tmsm-appointment-cancelation'); 

            ?></p>

        <p style="text-align: center; margin-top: 30px;">
            <?php echo esc_html__('A très bientôt', 'tmsm-appointment-cancelation'); ?><br>
            <?php echo esc_html__('L\'équipe du spa', 'tmsm-appointment-cancelation'); ?>
            <strong><?php echo esc_html($site_name); ?></strong><br>
            <a href="<?php echo esc_url($site_url); ?>" style="color: #0073aa; text-decoration: none;"><?php echo esc_url($site_url); ?></a>
        </p>

        <div style="text-align: center; margin-top: 20px; font-size: 14px; color: #888;">
            <?php echo esc_html__('Ceci est un email automatique merci de ne pas y répondre directement', 'tmsm-appointment-cancelation'); ?>
        </div>
    </div>
</body>

</html>