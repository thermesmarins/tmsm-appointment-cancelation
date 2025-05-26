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

    public function __construct($fonctionnal_id, $aquos_appointment_signature, $appointment_date = null)
    {
        $this->aquos_appointment_date = $appointment_date;
        $this->aquos_fonctionnal_id = $fonctionnal_id;
        $this->aquos_appointment_signature = $aquos_appointment_signature;
        
        error_log('Aquos appointment signature: ' . $this->aquos_appointment_signature);
        $this->aquos_appointment_date = $appointment_date;
        $options = get_option('tmsm_appointment_cancelation_options');
        $this->aquos_cancelation_url = isset($options['aquos_appointment_cancellation_url']) ? esc_attr($options['aquos_appointment_cancellation_url']) : '';
        $this->aquos_appointment_delay = isset($options['aquos_appointment_cancellation_deadline']) ? esc_attr($options['aquos_appointment_cancellation_deadline']) : '';
        $this->aquos_daily_appointment_url = isset($options['aquos_appointment_daily_url']) ? esc_attr($options['aquos_appointment_daily_url']) : '';
        $this->aquos_security_token = isset($options['aquos_appointment_cancellation_token']) ? esc_attr($options['aquos_appointment_cancellation_token']) : '';

        // Initialiser le tableau des sites ici, car il est lié à cette logique
        $this->aquos_sites = array(
            // Mettre 10 pour les tests
            'AQREN' => 10,
            'AQVE'  => 2,
            'AQNA'  => 5,
        );
        // Extraire l'ID numérique de l'ID fonctionnel
        $this->aquos_appointment_id = $this->extract_numeric_id_from_fonctional_id($this->aquos_fonctionnal_id);
        $site_code_extracted = $this->extract_site_code_from_token($this->aquos_fonctionnal_id);
        $this->aquos_site_id = $this->get_site_id_from_code($site_code_extracted);
    }
    /**
     * Extrait le code du site (lettres majuscules) d'un token.
     * C'est une méthode privée car elle est une aide interne à la classe.
     *
     * @param string $token_full Le token complet (ex: "125456AQREN").
     * @return string|null Le code du site (ex: "AQREN") ou null si non trouvé.
     */
    private function extract_site_code_from_token($aquos_fonctionnal_id)
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
    private function extract_numeric_id_from_fonctional_id($fonctional_id)
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
        //  Faire la requête à l'API Aquos pour récupérer les rendez-vous de l'utilisateur
        // Récuperer le résultat de l'API et le transformer en tableau d'objets
        
        $appointments = $this->get_daily_appointments();
        error_log('Appointments from Aquos: ' . print_r($appointments, true));
        if (empty($appointments) || isset($appointments->ErrorMessage)) {
            return []; // Si pas de rendez-vous ou erreur, retourner un tableau vide
        } else {
            return $appointments->appointments ;
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
            $appointment_id =  $this->aquos_appointment_id; // Faire les appels dans postman pour récupérer les rendez-vous
            $date =  $this->get_aquos_appointment_date(); // date du jour
			$url = $this->aquos_daily_appointment_url;
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
    /** Generate HMAC signature
	 *
	 * @param string $json_body
	 * @return string
	 */
	private function generate_hmac_signature($json_body)
	{
		$secret_token = get_option('tmsm_aquos_spa_booking_deleteaquossecret');
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
        $url = $this->aquos_daily_appointment_url;
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

    // todo annulation des rendez-vous méthode delete
}
