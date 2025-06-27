<?php
declare(strict_types=1);

/**
 * This file is part of the TMSM Appointment Cancelation plugin.
 * Handles Aquos API connections and appointment cancellation process.
 */
class Tmsm_Appointment_Cancelation_Aquos
{
    // Site IDs as class constants
    const SITE_ID_AQREN = 0;
    const SITE_ID_AQVE = 2;
    const SITE_ID_AQNA = 5;
    const SITE_ID_TEST = 10;

    // Set to true for test mode, false for production
    const TEST_MODE = true; // Change to true for test environment

    private $aquos_cancelation_url;
    private $aquos_appointment_delay;
    private $aquos_daily_appointment_url;
    private $aquos_security_token;
    private $aquos_fonctionnal_id;
    private $aquos_appointment_signature;
    private $aquos_appointment_id;
    private $aquos_site_id;
    private $aquos_sites;
    private $aquos_appointment_date;
    private $customer_identity;

    public function __construct($fonctionnal_id, $aquos_appointment_signature = null, $appointment_date = null)
    {
        $this->aquos_appointment_date = $appointment_date;
        $this->aquos_fonctionnal_id = $fonctionnal_id;
        $this->aquos_appointment_signature = $aquos_appointment_signature;
        $this->aquos_appointment_date = $appointment_date;

        $options = get_option('tmsm_appointment_cancelation_options');
        $this->aquos_cancelation_url = isset($options['aquos_appointment_cancellation_url']) ? esc_url_raw($options['aquos_appointment_cancellation_url']) : '';
        $this->aquos_appointment_delay = isset($options['aquos_appointment_cancellation_deadline']) ? esc_attr($options['aquos_appointment_cancellation_deadline']) : '';
        $this->aquos_daily_appointment_url = isset($options['aquos_appointment_daily_url']) ? esc_url_raw($options['aquos_appointment_daily_url']) : '';
        $this->aquos_security_token = isset($options['aquos_appointment_cancellation_token']) ? esc_attr($options['aquos_appointment_cancellation_token']) : '';

        $this->aquos_sites = array(
            'AQREN' => self::SITE_ID_AQREN,
            'AQVE'  => self::SITE_ID_AQVE,
            'AQNA'  => self::SITE_ID_AQNA,
        );
        // If in test mode, override AQREN site ID for testing
        if (self::TEST_MODE) {
            $this->aquos_sites['AQREN'] = self::SITE_ID_TEST; // Use test site ID
        }
        $this->aquos_appointment_id = $this->extract_appointment_id_from_fonctional_id($this->aquos_fonctionnal_id);
        $site_code_extracted = $this->extract_site_code_from_fonctionnal_id($this->aquos_fonctionnal_id);
        $this->aquos_site_id = $this->get_site_id_from_code($site_code_extracted);
    }

    private function extract_site_code_from_fonctionnal_id($aquos_fonctionnal_id): ?string
    {
        $pattern = '/([A-Z]+)$/';
        if (preg_match($pattern, $aquos_fonctionnal_id, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function extract_appointment_id_from_fonctional_id($fonctional_id): ?int
    {
        $pattern = '/^([0-9]+)/';
        if (preg_match($pattern, $fonctional_id, $matches)) {
            return (int) $matches[1];
        }
        return null;
    }

    public function get_aquos_appointment_id(): ?int
    {
        return $this->aquos_appointment_id;
    }

    private function get_site_id_from_code($site_code): ?int
    {
        if ($site_code !== null && isset($this->aquos_sites[$site_code])) {
            return $this->aquos_sites[$site_code];
        }
        return null;
    }

    public function get_aquos_site_id(): ?int
    {
        return $this->aquos_site_id;
    }

    public function get_aquos_security_token(): string
    {
        return $this->aquos_security_token;
    }

    public function get_customer_identity(): ?string
    {
        return $this->customer_identity;
    }

    public function get_aquos_appointment_date(): ?string
    {
        $date = DateTime::createFromFormat('Y.m.d', $this->aquos_appointment_date);
        if (!$date) {
            $this->log('Invalid appointment date format: ' . $this->aquos_appointment_date, 'error');
            return null;
        }
        return $date->format('Ymd');
    }

    public function get_formatted_date($date): ?string
    {
        $date_obj = DateTime::createFromFormat('Ymd', $date);
        if ($date_obj) {
            $mois_fr = array(
                1  => 'janvier',
                2  => 'février',
                3  => 'mars',
                4  => 'avril',
                5  => 'mai',
                6  => 'juin',
                7  => 'juillet',
                8  => 'août',
                9  => 'septembre',
                10 => 'octobre',
                11 => 'novembre',
                12 => 'décembre'
            );

            $jour = $date_obj->format('d');
            $mois_numero = (int)$date_obj->format('n'); // 'n' pour le mois sans zéro initial (1 à 12)
            $annee = $date_obj->format('Y');

            return $jour . ' ' . $mois_fr[$mois_numero] . ' ' . $annee;
        } else {
            $this->log('Date format error for: ' . $date, 'error');
            return null;
        }
    }

    public function get_user_appointments(): array
    {
        $appointments = $this->get_daily_appointments();
        $this->log('Appointments from Aquos: ' . print_r($appointments, true), 'info');
        if (empty($appointments) || isset($appointments->ErrorMessage)) {
            return [];
        } else {
            $this->customer_identity =  tmsm_set_formatted_identity(
                $appointments->appointments[0]->customer_civility ?? '',
                $appointments->appointments[0]->customer_lastname ?? '',
                $appointments->appointments[0]->customer_firstname ?? ''
            );
            $this->log('Customer identity from Aquos: ' . $this->customer_identity, 'info');
            return $appointments->appointments;
        }
    }

    private function get_daily_appointments()
    {
        $site_id =  $this->aquos_site_id;
        $appointment_id =  $this->aquos_appointment_id;
        $date =  $this->get_aquos_appointment_date();
        $appointment_signature = $this->aquos_appointment_signature;
        $appointment_array = array(
            'id_site' => $site_id,
            'appointment_id' => $appointment_id,
            'appointment_date' => $date,
            'appointment_signature' => $appointment_signature,
        );
        $json_body = json_encode($appointment_array);
        $signature =  $this->generate_hmac_signature($json_body);
        $response = $this->_make_aquos_api_request($json_body, $signature);
        return $response;
    }

    public function can_appointment_be_cancelled(
        string $appointment_date_str,
        string $appointment_time_str,
        int $delay_hours
    ): bool {
        $now = new DateTime();
        $appointment_datetime_combined = $appointment_date_str . $appointment_time_str . '00';
        $appointment_start_time = DateTime::createFromFormat('YmdHis', $appointment_datetime_combined);
        if (!$appointment_start_time) {
            $this->log('Error: Unable to parse appointment date/time: ' . $appointment_datetime_combined, 'error');
            return false;
        }
        $cancel_deadline_interval = new DateInterval('PT' . $delay_hours . 'H');
        $cancellation_deadline = (clone $appointment_start_time)->sub($cancel_deadline_interval);
        if ($now < $cancellation_deadline) {
            $this->log(
                'Appointment (starts at ' . $appointment_start_time->format('Y-m-d H:i') . ') can be cancelled. Cancellation deadline: ' . $cancellation_deadline->format('Y-m-d H:i') . '. Current time: ' . $now->format('Y-m-d H:i'),
                'info'
            );
            return true;
        } else {
            $this->log(
                'Appointment (starts at ' . $appointment_start_time->format('Y-m-d H:i') . ') cannot be cancelled. Cancellation deadline: ' . $cancellation_deadline->format('Y-m-d H:i') . '. Current time: ' . $now->format('Y-m-d H:i'),
                'info'
            );
            return false;
        }
    }

    public function cancel_appointment(array $appointment_id)
    {
        $site_id = $this->aquos_site_id;
        $this->log('Aquos site ID cancel method: ' . $site_id, 'info');
        $this->log('Appointment ID to cancel: ' . print_r($appointment_id, true), 'info');
        if (empty($this->aquos_appointment_id)) {
            $this->log('Appointment ID is empty or not set.', 'error');
            return new WP_Error('invalid_appointment_id', 'Appointment ID is empty or not set.');
        }
        if (
            (!self::TEST_MODE && !in_array($site_id, $this->aquos_sites, true)) ||
            (self::TEST_MODE && $site_id !== self::SITE_ID_TEST)
        ) {
            $this->log('Invalid Aquos site ID: ' . $site_id, 'error');
            return new WP_Error('invalid_site_id', 'Invalid Aquos site ID.');
        }
        $response = array();
        foreach ($appointment_id as $id) {
            $id_int = intval($id);
            if ($id_int <= 0) {
                $this->log('Appointment ID must be a positive integer. Provided: ' . $id, 'error');
                return new WP_Error('invalid_appointment_id', 'Appointment ID must be a positive integer.');
            }
            $data = array(
                'id_site' => $this->aquos_site_id,
                'appointment_id' => $id_int,
            );
            $json_body = json_encode($data);
            $signature = $this->generate_hmac_signature($json_body);
            $method = 'DELETE';
            $response[] = $this->_make_aquos_api_request($json_body, $signature, $method);
        }
        $error = array();
        foreach ($response as $res) {
            $status = is_object($res) && isset($res->Status) ? $res->Status : false;
            if ($status === true) {
                $error[] = false;
            } else {
                $error[] = true;
                $this->log('Error cancelling appointment: ' . print_r($res, true), 'error');
                return new WP_Error('cancellation_error', 'Error cancelling appointment: ' . print_r($res, true));
            }
        }
        if (in_array(true, $error, true)) {
            $this->log('One or more cancellations failed.', 'error');
            return false;
        } else {
            $this->log('All cancellations succeeded.', 'info');
            return true;
        }
    }

    private function generate_hmac_signature($json_body): string
    {
        $hmacSignature = hash_hmac('sha256', $json_body, $this->aquos_security_token, true);
        return base64_encode($hmacSignature);
    }

    private function _make_aquos_api_request($json_body, $signature, $method = 'POST')
    {
        if ($method === 'POST') {
            $url = $this->aquos_daily_appointment_url;
        } elseif ($method === 'DELETE') {
            $url = $this->aquos_cancelation_url;
        } else {
            $this->log('Unsupported HTTP method: ' . $method, 'error');
            return new WP_Error('unsupported_method', 'Unsupported HTTP method: ' . $method);
        }
        $this->log('Aquos API URL: ' . $url, 'info');
        $headers = [
            'Content-Type' => 'application/json; charset=utf-8',
            'X-Signature' => $signature,
            'Cache-Control' => 'no-cache',
        ];
        $sslverify = true;
        if (self::TEST_MODE) {
            $sslverify = false;
        }
        $response = wp_remote_request($url, array(
            'method' => $method,
            'body' => $json_body,
            'headers' => $headers,
            'timeout' => 30,
            'sslverify' => $sslverify,
        ));
        if (is_wp_error($response)) {
            $this->log('Error calling Aquos API: ' . $response->get_error_message(), 'error');
            return new WP_Error('api_request_error', 'Error calling Aquos API: ' . $response->get_error_message());
        }
        $http_code = wp_remote_retrieve_response_code($response);
        if ($http_code < 200 || $http_code >= 300) {
            $this->log('Aquos API returned HTTP error code ' . $http_code . '. Raw response: ' . wp_remote_retrieve_body($response), 'error');
            return new WP_Error('api_response_error', 'Aquos API returned HTTP error: ' . $http_code);
        }
        $content_type = wp_remote_retrieve_header($response, 'content-type');
        $response_body = wp_remote_retrieve_body($response);
        if (strpos($content_type, 'application/json') === false) {
            $this->log('Unexpected content type from Aquos API: ' . $content_type . '. Response: ' . $response_body, 'error');
            return new WP_Error('unexpected_content_type', 'Unexpected content type from Aquos API.');
        }
        $data = json_decode($response_body);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log('JSON decode error from Aquos API: ' . json_last_error_msg() . '. Response: ' . $response_body, 'error');
            return new WP_Error('json_decode_error', 'Unable to decode JSON response from Aquos API.');
        }
        return $data;
    }

    private function log($message, $level = 'info')
    {
        if (function_exists('wc_get_logger')) {
            $logger = wc_get_logger();
            $logger->log($level, $message, array('source' => 'tmsm-appointment-cancelation-aquos'));
        } else {
            error_log('[' . strtoupper($level) . '] TMSM Appointment Cancelation Aquos: ' . $message);
        }
    }
}
