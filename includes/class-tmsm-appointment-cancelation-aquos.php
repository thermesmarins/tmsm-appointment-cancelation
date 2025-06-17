<?php

/**
 * This file is part of the TMSM Appointment Cancelation plugin.
 * 
 * It handles the Aquos connexions and the appointment cancelation process.
 * 
 */
class Tmsm_Appointment_Cancelation_Aquos
{
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
    private $customer_name;

    public function __construct($fonctionnal_id, $aquos_appointment_signature = null, $appointment_date = null)
    {
        $this->aquos_appointment_date = $appointment_date;
        $this->aquos_fonctionnal_id = $fonctionnal_id;
        $this->aquos_appointment_signature = $aquos_appointment_signature;
        $this->aquos_appointment_date = $appointment_date;

        $options = get_option('tmsm_appointment_cancelation_options');
        $this->aquos_cancelation_url = isset($options['aquos_appointment_cancellation_url']) ? esc_attr($options['aquos_appointment_cancellation_url']) : '';
        $this->aquos_appointment_delay = isset($options['aquos_appointment_cancellation_deadline']) ? esc_attr($options['aquos_appointment_cancellation_deadline']) : '';
        $this->aquos_daily_appointment_url = isset($options['aquos_appointment_daily_url']) ? esc_attr($options['aquos_appointment_daily_url']) : '';
        $this->aquos_security_token = isset($options['aquos_appointment_cancellation_token']) ? esc_attr($options['aquos_appointment_cancellation_token']) : '';

        $this->aquos_sites = array(
            // TODO Mettre 10 pour les tests
            'AQREN' => 10,
            'AQVE'  => 2,
            'AQNA'  => 5,
        );
        // Extraire l'ID numérique de l'ID fonctionnel
        $this->aquos_appointment_id = $this->extract_appointment_id_from_fonctional_id($this->aquos_fonctionnal_id);
        $site_code_extracted = $this->extract_site_code_from_fonctionnal_id($this->aquos_fonctionnal_id);
        $this->aquos_site_id = $this->get_site_id_from_code($site_code_extracted);
    }
    /**
     * Extrait le code du site (lettres majuscules) d'un token.
     * C'est une méthode privée car elle est une aide interne à la classe.
     *
     * @param string $aquos_fonctionnal_id Le token complet (ex: "125456AQREN").
     * @return string|null Le code du site (ex: "AQREN") ou null si non trouvé.
     */
    private function extract_site_code_from_fonctionnal_id($aquos_fonctionnal_id)
    {
        // L'expression régulière cherche une ou plusieurs lettres majuscules (A-Z)
        // à la fin de la chaîne ($).
        $pattern = '/([A-Z]+)$/';

        if (preg_match($pattern, $aquos_fonctionnal_id, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Extrait la partie numérique d'un ID fonctionnel.
     *
     * @param string $fonctional_id Le ID fonctionnel complet (ex: "156786AQREN").
     * @return int|null La partie numérique (ex: 156786) ou null si non trouvée.
     */
    private function extract_appointment_id_from_fonctional_id($fonctional_id)
    {
        // L'expression régulière cherche une ou plusieurs chiffres (0-9)
        // au début de la chaîne (^)
        $pattern = '/^([0-9]+)/';
        if (preg_match($pattern, $fonctional_id, $matches)) {
            return (int) $matches[1]; // Cast en entier
        }
        return null;
    }
    public function get_aquos_appointment_id()
    {
        return $this->aquos_appointment_id;
    }
    /**
     * Récupère l'ID numérique du site à partir de son code (lettres).
     * C'est une méthode privée car elle est une aide interne à la classe.
     *
     * @param string|null $site_code Le code du site (ex: "AQREN").
     * @return int|null L'ID numérique du site (ex: 0) ou null si non trouvé.
     */
    private function get_site_id_from_code($site_code)
    {
        if ($site_code !== null && isset($this->aquos_sites[$site_code])) {
            return $this->aquos_sites[$site_code];
        }
        return null;
    }
    /**
     * Méthode publique pour récupérer l'ID du site, si nécessaire depuis l'extérieur.
     *
     * @return int|null L'ID du site ou null.
     */
    public function get_aquos_site_id()
    {
        return $this->aquos_site_id;
    }
    /**
     * Méthode publique pour récupérer l'URL d'annulation Aquos.
     *
     * @return string L'URL d'annulation Aquos.
     */
    public function get_aquos_security_token()
    {
        return $this->aquos_security_token;
    }
    public function get_customer_name()
    {
        return $this->customer_name;
    }
    /**
     * Méthode publique pour récupérer l'URL d'annulation Aquos.
     *
     * @return string L'URL d'annulation Aquos.
     */
    public function get_aquos_appointment_date()
    {
        $date = DateTime::createFromFormat('Y.m.d', $this->aquos_appointment_date);
        return $date->format('Ymd');
    }
    public function get_formatted_date($date)
    {
        $date_obj = DateTime::createFromFormat('Ymd', $date);
        // return $date->format('d-m-Y');
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
            error_log("Erreur de format de date pour: " . $date);
            return null;
        }
    }

    /**
     * Methode publique pour récupérer les rendez-vous de l'utilsateur de la journée depuis Aquos
     *
     * @return array
     */
    public function get_user_appointments()
    {
        $appointments = $this->get_daily_appointments();
        error_log('Appointments from Aquos: ' . print_r($appointments, true));
        if (empty($appointments) || isset($appointments->ErrorMessage)) {
            return []; // Si pas de rendez-vous ou erreur, retourner un tableau vide
        } else {
           
            $this->customer_name = $appointments->appointments[0]->customer; // Récupérer le nom du client
            error_log('Customer name from Aquos: ' . $this->customer_name);
            return $appointments->appointments;
        }
    }
    /**
     * Methode privée pour récupérer la signature du rendez-vous pour l'appel vers Aquos
     *
     * @return void
     */
    private function get_daily_appointments()
    {
        $site_id =  $this->aquos_site_id; // mettre 10 pour les tests
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

    /**
     * Vérifie si un rendez-vous peut être annulé en fonction d'un délai horaire.
     *
     * @param string $appointment_date_str La date du rendez-vous au format YYYYMMDD (ex: '20250613').
     * @param string $appointment_time_str L'heure du rendez-vous au format HHMM (ex: '1400').
     * @param int    $delay_hours Le délai en heures avant le rendez-vous (ex: 24 ou 48).
     * @return bool True si le rendez-vous peut être annulé, false sinon.
     */
    public function can_appointment_be_cancelled(
        $appointment_date_str,
        $appointment_time_str,
        $delay_hours
    ) {
        // 1. Obtenir l'heure actuelle
        $now = new DateTime();

        // 2. Créer l'objet DateTime complet pour le rendez-vous
        // Assure-toi que appointment_time_str est bien HHMM (ex: "1400")
        $appointment_datetime_combined = $appointment_date_str . $appointment_time_str . '00'; // Ajoute les secondes si manquantes

        // Le format 'YmdHis' correspond à YYYYMMDDHHMMSS
        $appointment_start_time = DateTime::createFromFormat('YmdHis', $appointment_datetime_combined);

        if (!$appointment_start_time) {
            error_log('Erreur: Impossible de parser la date/heure du rendez-vous: ' . $appointment_datetime_combined);
            return false; // Date ou heure de rendez-vous invalide
        }

        // 3. Calculer la date limite d'annulation
        // On clone l'objet pour ne pas modifier l'original $appointment_start_time
        // On soustrait le délai en heures du temps du rendez-vous.
        $cancel_deadline_interval = new DateInterval('PT' . $delay_hours . 'H');
        $cancellation_deadline = (clone $appointment_start_time)->sub($cancel_deadline_interval);
        // 4. Comparer l'heure actuelle avec la date limite d'annulation
        if ($now < $cancellation_deadline) {
            // L'heure actuelle est AVANT la date limite d'annulation
            error_log(
                'Le rendez-vous (débute le ' . $appointment_start_time->format('Y-m-d H:i') . ') ' .
                    'peut être annulé. Date limite d\'annulation: ' . $cancellation_deadline->format('Y-m-d H:i') .
                    '. Heure actuelle: ' . $now->format('Y-m-d H:i')
            );
            return true;
        } else {
            // L'heure actuelle est APRES ou ÉGALE à la date limite d'annulation
            error_log(
                'Le rendez-vous (débute le ' . $appointment_start_time->format('Y-m-d H:i') . ') ' .
                    'ne peut PAS être annulé. Date limite d\'annulation: ' . $cancellation_deadline->format('Y-m-d H:i') .
                    '. Heure actuelle: ' . $now->format('Y-m-d H:i')
            );
            return false;
        }
    }
    /**
     * Méthode publique pour annuler un ou plusieurs rendez-vous
     *
     * @param array $appointment_id
     * @return bool|WP_Error
     */
    public function cancel_appointment(array $appointment_id)
    {
        $site_id = $this->aquos_site_id;
        error_log('Aquos site ID cancel method: ' . $site_id);
        error_log('Appointment ID to cancel: ' . print_r($appointment_id, true));
        // Vérifier si l'ID de rendez-vous est défini
        if (empty($this->aquos_appointment_id)) {
            error_log('L\'ID de rendez-vous est vide ou non défini.');
            return new WP_Error('invalid_appointment_id', 'L\'ID de rendez-vous est vide ou non défini.');
        }
        // todo : modifier pour la prod site = 10 pour les tests
        if ($site_id != 10 && !isset($this->aquos_sites[$site_id])) {
            error_log('L\'ID de site Aquos est invalide: ' . $site_id);
            return new WP_Error('invalid_site_id', 'L\'ID de site Aquos est invalide.');
        }
        $ids = array();
        $response = array();
        foreach ($appointment_id as $id) {
            $ids[] = $id;
            // Vérifier si l'ID de rendez-vous est un entier
            $id_int = intval($id);
            if ($id_int <= 0) {
                error_log('L\'ID de rendez-vous doit être un entier positif. ID fourni: ' . $id);
                return new WP_Error('invalid_appointment_id', 'L\'ID de rendez-vous doit être un entier positif.');
            }
            // Préparer les données pour l'annulation
            $data = array(
                'id_site' => $this->aquos_site_id,
                'appointment_id' => $id_int,
            );
            $json_body = json_encode($data);
            $signature = $this->generate_hmac_signature($json_body);
            $method = 'DELETE';
            $response[] = $this->_make_aquos_api_request($json_body, $signature, $method);
        }
        error_log('Response from Aquos API after cancellation: ' . print_r($response, true));
        $error = array();
        foreach ($response as $res) {
            error_log('response error: ' . print_r($res->Status, true) . gettype($res->Status));
            if ($res->Status == true) {
                $error[] = false;
            } else {
                $error[] = true;
                error_log('Erreur lors de l\'annulation du rendez-vous: ' . print_r($res, true));
                return new WP_Error('cancellation_error', 'Erreur lors de l\'annulation du rendez-vous: ' . print_r($res, true));
            }
        }
        if (in_array(true, $error)) {
            error_log('Une ou plusieurs annulations ont échoué.');
            return false; // Au moins une annulation a échoué
        } else {
           
            error_log('Toutes les annulations ont réussi.');
            return true; // Toutes les annulations ont réussi
        }
    }
    /** Generate HMAC signature
     *
     * @param string $json_body
     * @return string
     */
    private function generate_hmac_signature($json_body)
    {
        $secret_token = $this->aquos_security_token;
        $hmacSignature = hash_hmac('sha256', $json_body, $this->aquos_security_token, true);
        return base64_encode($hmacSignature);
    }
    /**
     * Private function to get the appointments from Aquos
     *
     * @param [string] $json_body
     * @param [string] $signature
     * @return void
     */
    private function _make_aquos_api_request($json_body, $signature, $method = 'POST')
    {
        if ($method === 'POST') {
            $url = $this->aquos_daily_appointment_url;
        } elseif ($method === 'DELETE') {
            $url = $this->aquos_cancelation_url;
        } else {
            error_log('Méthode HTTP non supportée: ' . $method);
            return new WP_Error('unsupported_method', 'Méthode HTTP non supportée: ' . $method);
        }
        error_log('Aquos API URL: ' . $url);
        $headers = [
            'Content-Type' => 'application/json; charset=utf-8',
            'X-Signature' => $signature,
            'Cache-Control' => 'no-cache',
        ];


        $response = wp_remote_request($url, array(
            'method' => $method,
            'body' => $json_body,
            'headers' => $headers,
            'timeout' => 30, // Timeout de 30 secondes
            'sslverify' => false, // Désactiver la vérification SSL pour les environnements de développement
        ));
        // Vérifier si la requête a échoué
        if (is_wp_error($response)) {
            error_log('Erreur lors de l\'appel API Aquos: ' . $response->get_error_message());
            return new WP_Error('api_request_error', 'Erreur lors de l\'appel API Aquos: ' . $response->get_error_message());
        }
        // Vérifier le code de statut HTTP
        $http_code = wp_remote_retrieve_response_code($response);
        if ($http_code < 200 || $http_code >= 300) {
            error_log("L'API Aquos a retourné un code d'erreur HTTP $http_code. Réponse brute: " . wp_remote_retrieve_body($response));
            return new WP_Error('api_response_error', 'L\'API Aquos a retourné une erreur HTTP: ' . $http_code);
        }
        // Décoder la réponse JSON
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Erreur de décodage JSON de la réponse API Aquos: ' . json_last_error_msg());
            error_log('Réponse reçue (non-JSON): ' . $response_body); // Log la réponse complète pour débogage
            return new WP_Error('json_decode_error', 'Impossible de décoder la réponse JSON de l\'API Aquos.');
        }
        // Retourner les données décodées
        return $data;
    }
}
