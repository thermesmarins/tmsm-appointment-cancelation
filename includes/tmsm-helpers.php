<?php 
/**
 * TMSM Helpers
 * 
 * This file contains helper functions for the TMSM Appointment Cancelation plugin.
 * 
 * @package    Tmsm_Appointment_Cancelation
 */

if (!(defined('ABSPATH'))) {
    exit; // Exit if accessed directly.
}

/**
 * Retrieves site information based on the site ID.
 *
 * @param int $site_id The ID of the site.
 * @return array An associative array containing site information.
 */
function tmsm_get_site_informations(int $site_id): array {
      $site_emails = array(
            array(
                'email' => 'rennes@aquatonic.fr',
                'id' => 0,
                'name' => 'Aquatonic Rennes',
                'url' => 'https://aquatonic.fr/rennes',
                'shop_email' => 'rennes@aquatonic.fr',
                'resaspa_url' => 'https://aquatonic.fr/rennes/prendre-rdv',
                'logo_url' => 'https://aquatonic.fr/rennes/wp-content/uploads/sites/6/2017/05/Aquatonic-rennes-logo.png',
            ),
            array(
                'email' => 'paris@aquatonic.fr',
                'id' => 2,
                'name' => 'Aquatonic Paris',
                'url' => 'https://aquatonic.fr/paris',
                'shop_email' => 'paris@aquatonic.fr',
                'resaspa_url' => 'https://aquatonic.fr/paris/prendre-rdv',
                'logo_url' => 'https://aquatonic.fr/paris/wp-content/uploads/sites/9/2017/11/logo_aquatonic-paris-600-300.png',
            ),
            array(
                'email' => 'nantes@aquatonic.fr',
                'id' => 5,
                'name' => 'Aquatonic Nantes',
                'url' => 'https://aquatonic.fr/nantes',
                'shop_email' => 'nantes@aquatonic.fr',
                'resaspa_url' => 'https://aquatonic.fr/nantes/prendre-rdv',
                'logo_url' => 'https://aquatonic.fr/nantes/wp-content/uploads/sites/8/2017/11/logo_aquatonic-nantes-600-300.png',
            ),
            array(
                'email' => 'rennes@aquatonic.local',
                'id' => 10,
                'name' => 'Aquatonic Rennes Local',
                'url' => 'https://aquatonic.local/rennes',
                'shop_email' => 'aflament.dev@gmail.com', // Local development email
                'resaspa_url' => 'https://aquatonic.local/rennes/prendre-rdv',
                'logo_url' => 'https://aquatonic.fr/rennes/wp-content/uploads/sites/6/2017/05/Aquatonic-rennes-logo.png',
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
                    'shop_email' => $site_email['shop_email'],
                    'resaspa_url' => $site_email['resaspa_url'],
                    'logo_url' => $site_email['logo_url']
                );
                break;
            }
        }
        return $site_informations;
}
/**
 * Formats a date string (YYYYMMDD) into a human-readable format.
 * Includes French localization for days and months.
 *
 * @param string $date_str The date string in YYYYMMDD format.
 * @return string The formatted date string.
 */
if (!function_exists('tmsm_format_date_for_email')) {
    function tmsm_format_date_for_email(string $date_str): string {
        // Assuming $date_str is in 'YYYYMMDD' format
        $date_obj = DateTime::createFromFormat('Ymd', $date_str);
        if ($date_obj) {
            // Set locale for strftime (if available and not on Windows for best results)
             $date_obj = DateTime::createFromFormat('Ymd', $date_str);
        if (!$date_obj) {
            return $date_str; // Return original if format is unexpected
        }

        // Try to use IntlDateFormatter for robust localization (requires intl extension)
        // if (class_exists('IntlDateFormatter')) {
        //     // Get WordPress locale
        //     $locale = get_locale(); // e.g., 'fr_FR'

        //     // Create a formatter for full date
        //     $formatter = new IntlDateFormatter(
        //         $locale,
        //         IntlDateFormatter::FULL, // Full format (e.g., "lundi 1 janvier 2024")
        //         IntlDateFormatter::NONE, // No time
        //         null, // Default timezone
        //         IntlDateFormatter::GREGORIAN
        //     );
        //     return $formatter->format($date_obj);
        //     } else {
                // Fallback for newer PHP versions (strftime deprecated) or Windows
                $months = array(
                    1 => __('janvier', 'tmsm-appointment-cancelation'),
                    2 => __('février', 'tmsm-appointment-cancelation'),
                    3 => __('mars', 'tmsm-appointment-cancelation'),
                    4 => __('avril', 'tmsm-appointment-cancelation'),
                    5 => __('mai', 'tmsm-appointment-cancelation'),
                    6 => __('juin', 'tmsm-appointment-cancelation'),
                    7 => __('juillet', 'tmsm-appointment-cancelation'),
                    8 => __('août', 'tmsm-appointment-cancelation'),
                    9 => __('septembre', 'tmsm-appointment-cancelation'),
                    10 => __('octobre', 'tmsm-appointment-cancelation'),
                    11 => __('novembre', 'tmsm-appointment-cancelation'),
                    12 => __('décembre', 'tmsm-appointment-cancelation')
                );
                $days = array(
                    1 => __('lundi', 'tmsm-appointment-cancelation'),
                    2 => __('mardi', 'tmsm-appointment-cancelation'),
                    3 => __('mercredi', 'tmsm-appointment-cancelation'),
                    4 => __('jeudi', 'tmsm-appointment-cancelation'),
                    5 => __('vendredi', 'tmsm-appointment-cancelation'),
                    6 => __('samedi', 'tmsm-appointment-cancelation'),
                    7 => __('dimanche', 'tmsm-appointment-cancelation')
                );
                return $days[$date_obj->format('N')] . ' ' . $date_obj->format('d') . ' ' . $months[(int)$date_obj->format('n')] . ' ' . $date_obj->format('Y');
        //     }
        }
        return $date_str; // Return original if format is unexpected
    }
    /**
     * Set formatted identity for the customer.
     *
     * @param string $customer_civility
     * @param string $customer_lastname
     * @param string $customer_firstname
     * @return string
     */
    function tmsm_set_formatted_identity($customer_civility, $customer_lastname, $customer_firstname)
    {

        // Formater l'identité du client
        $formatted_identity = '';
        if (!empty($customer_civility)) {
            $formatted_identity .= $customer_civility . ' ';
            if (!empty($customer_lastname)) {
                $formatted_identity .= $customer_lastname . ' ';
            }
        } else if (!empty($customer_firstname)) {
                $formatted_identity .= $customer_firstname;
            // Si la civilité est vide, on n'ajoute pas d'espace
            if (!empty($customer_lastname)) {
                $formatted_identity .= $customer_lastname . ' ';
            }
        }
        return trim($formatted_identity); // Retirer les espaces superflus
    }
}
