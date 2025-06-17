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

// Helper function to format date - you might want to put this in a shared helper file
// if (!function_exists('tmsm_format_date_for_email')) {
//     function tmsm_format_date_for_email($date_str) {
//         // Assuming $date_str is in 'YYYYMMDD' format
//         $date_obj = DateTime::createFromFormat('Ymd', $date_str);
//         if ($date_obj) {
//             // Check for strftime support (deprecated in PHP 8.1, removed in PHP 9.0)
//             if (function_exists('strftime') && strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') { // strftime locale issues on Windows
//                 setlocale(LC_TIME, get_locale() . '.utf8', get_locale()); // Set locale based on WordPress
//                 return strftime('%A %d %B %Y', $date_obj->getTimestamp()); // Example: "vendredi 13 juin 2025"
//             } else {
//                 // Fallback for newer PHP versions or Windows
//                 $months = array(
//                     1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril', 5 => 'mai', 6 => 'juin',
//                     7 => 'juillet', 8 => 'août', 9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre'
//                 );
//                 $days = array(
//                     1 => 'lundi', 2 => 'mardi', 3 => 'mercredi', 4 => 'jeudi', 5 => 'vendredi', 6 => 'samedi', 7 => 'dimanche'
//                 );
//                 return $days[$date_obj->format('N')] . ' ' . $date_obj->format('d') . ' ' . $months[(int)$date_obj->format('n')] . ' ' . $date_obj->format('Y');
//             }
//         }
//         return $date_str; // Return original if format is unexpected
//     }
// }
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
        <p><?php echo esc_html__('Dear ' . $appointments[0]->customer . ',', 'tmsm-appointment-cancelation'); ?></p>

        <p><?php echo esc_html__('This email confirms that your appointment(s) have been successfully cancelled with', 'tmsm-appointment-cancelation'); ?> **<?php echo esc_html($site_name); ?>**:</p>
        
        <ul style="list-style-type: none; padding: 0;">
            <?php foreach ($appointments as $appointment) : ?>
                <?php
                $formatted_date = tmsm_format_date_for_email($appointment->appointment_date);
                $formatted_time = isset($appointment->appointment_time) ? substr($appointment->appointment_time, 0, 2) . ':' . substr($appointment->appointment_time, 2, 2) : '';
                ?>
                <li style="margin-bottom: 10px; padding: 10px; background-color: #f9f9f9; border-left: 3px solid #d9534f;">
                    <strong><?php echo esc_html($appointment->appointment); ?></strong><br>
                    <?php echo esc_html__('Date:', 'tmsm-appointment-cancelation'); ?> **<?php echo esc_html($formatted_date); ?>**
                    <?php if (!empty($formatted_time)) : ?>
                        <br><?php echo esc_html__('Time:', 'tmsm-appointment-cancelation'); ?> **<?php echo esc_html($formatted_time); ?>**
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <p><?php echo esc_html__('If you have any questions or need to reschedule, please do not hesitate to contact us.', 'tmsm-appointment-cancelation'); ?></p>
        
        <p style="text-align: center; margin-top: 30px;">
            <?php echo esc_html__('Sincerely,', 'tmsm-appointment-cancelation'); ?><br>
            <strong><?php echo esc_html($site_name); ?></strong><br>
            <a href="<?php echo esc_url($site_url); ?>" style="color: #0073aa; text-decoration: none;"><?php echo esc_url($site_url); ?></a>
        </p>
        
        <div style="text-align: center; margin-top: 20px; font-size: 12px; color: #888;">
            <?php echo esc_html__('This is an automated email, please do not reply directly to this message.', 'tmsm-appointment-cancelation'); ?>
        </div>
    </div>
</body>
</html>