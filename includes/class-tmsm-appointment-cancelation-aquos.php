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
            'AQREN' => 0,
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
        $date = new DateTime($this->aquos_appointment_date);
        return $date->format('Ymd');
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
        if (empty($appointments)) {
            return [
            (object) ['ID' => 1, 'date' => '2025-05-10', 'appointment_id' => 10],
            (object) ['ID' => 2, 'date' => '2025-05-15', 'appointment_id' => 20],
        ];
        } else {
            return $appointments;
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
            $date =  $this->aquos_appointment_date;
			$url = $this->aquos_daily_appointment_url;
            $appointment_signature = $this->aquos_appointment_signature;
			$delete_appointment_array = array(
				'id_site' => $site_id,
				'appointment_id' => $appointment_id,
                'appointment_date' => $date,
                'appointment_signature' => $appointment_signature,
			);
			$json_body = json_encode($delete_appointment_array);
			$signature =  $this->generate_hmac_signature($json_body);

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
   private function get_appointments_from_aquos($json_body, $signature)
    {
        $response = wp_remote_get($this->aquos_daily_appointment_url, array(
            'method' => 'GET',
            'body' => $json_body,
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-Signature' => $signature,
            ),
        ));

        if (is_wp_error($response)) {
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body,true);
    }

    // todo annulation des rendez-vous méthode delete
}
