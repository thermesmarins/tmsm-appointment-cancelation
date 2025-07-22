<?php
/**
 * Email template for administrator notification about appointment cancellation.
 *
 * This template is loaded by Tmsm_Appointment_Cancelation_Admin_Email::get_email_content().
 *
 * Available variables:
 * - $appointments (array): Array of cancelled appointment objects (id, appointment_date, appointment, etc.).
 * - $client_email (string): The email address of the client who cancelled.
 * - $site_name (string): Name of the WordPress site.
 * - $site_url (string): URL of the WordPress site.
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Helper function to format date - (ensure tmsm_format_date_for_email is available, e.g., from customer email template or a shared file)
// if (!function_exists('tmsm_format_date_for_email')) {
//     function tmsm_format_date_for_email($date_str) {
//         // Assuming $date_str is in 'YYYYMMDD' format
//         $date_obj = DateTime::createFromFormat('Ymd', $date_str);
//         if ($date_obj) {
//             if (function_exists('strftime') && strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
//                 setlocale(LC_TIME, get_locale() . '.utf8', get_locale());
//                 return strftime('%A %d %B %Y', $date_obj->getTimestamp());
//             } else {
//                 $months = array(1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril', 5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août', 9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre');
//                 $days = array(1 => 'lundi', 2 => 'mardi', 3 => 'mercredi', 4 => 'jeudi', 5 => 'vendredi', 6 => 'samedi', 7 => 'dimanche');
//                 return $days[$date_obj->format('N')] . ' ' . $date_obj->format('d') . ' ' . $months[(int)$date_obj->format('n')] . ' ' . $date_obj->format('Y');
//             }
//         }
//         return $date_str;
//     }
// }
?>

<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title><?php echo esc_html($site_name); ?> - <?php echo esc_html__('Notification d\'annulation de rendez-vous', 'tmsm-appointment-cancelation'); ?></title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
        <h2 style="color:rgb(0, 115, 170); text-align: center;"><?php echo esc_html__('Notification d\'annulation de rendez-vous', 'tmsm-appointment-cancelation'); ?></h2>

        <p><?php echo esc_html__('Cher Aquatonic,', 'tmsm-appointment-cancelation'); ?></p>

        <p><?php echo esc_html__('Les rendez-vous suivants ont été annulés sur votre site', 'tmsm-appointment-cancelation'); ?> <?php echo esc_html($site_name); ?> :</p>

        <ul style="list-style-type: none; padding: 0;">
            <?php foreach ($appointments as $appointment) : ?>
                <?php
                $formatted_date = tmsm_format_date_for_email($appointment->appointment_date);
                $formatted_time = isset($appointment->appointment_hour) ? substr($appointment->appointment_hour, 0, 2) . ':' . substr($appointment->appointment_hour, 2, 2) : '';
                ?>
                <li style="margin-bottom: 10px; padding: 10px; background-color: #f9f9f9; border-left: 3px solid #eee;">
                    <strong><?php echo esc_html($appointment->appointment); ?></strong><br>
                    <?php echo esc_html__('Date :', 'tmsm-appointment-cancelation'); ?> <?php echo esc_html($formatted_date); ?>
                    <?php if (!empty($formatted_time)) : ?>
                        <br><?php echo esc_html__('Heure :', 'tmsm-appointment-cancelation'); ?> <?php echo esc_html($formatted_time); ?>
                    <?php endif; ?>
                    <br><?php echo esc_html__('ID de rendez-vous :', 'tmsm-appointment-cancelation'); ?> <?php echo esc_html($appointment->id); ?>
                    <?php if (isset($appointment->location) && !empty($appointment->location)) : ?>
                        <br><?php echo esc_html__('Emplacement :', 'tmsm-appointment-cancelation'); ?> <?php echo esc_html($appointment->location); ?>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if (!empty($client_email)) : ?>
            <p><?php echo esc_html__('Email du client :', 'tmsm-appointment-cancelation'); ?> <a href="mailto:<?php echo esc_attr($client_email); ?>" style="color:rgb(0, 115, 170); text-decoration: none;"><?php echo esc_html($client_email); ?></a></p>
        <?php endif; ?>        
        <p style="text-align: center; margin-top: 30px;">
            <?php echo esc_html__('Cordialement,', 'tmsm-appointment-cancelation'); ?><br>
            <strong><?php echo esc_html($site_name); ?></strong><br>
            <p><?php echo $site_url; ?></p>
        </p>
        
        <div style="text-align: center; margin-top: 20px; font-size: 12px; color: #888;">
            <?php echo esc_html__('Ceci est une notification automatique de votre site WordPress.', 'tmsm-appointment-cancelation'); ?>
        </div>
    </div>
</body>
</html>