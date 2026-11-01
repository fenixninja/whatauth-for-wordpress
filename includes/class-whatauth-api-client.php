<?php
if (!defined('ABSPATH')) {
    exit;
}

class WhatAuth_API_Client {

    private static $instance = null;
    private $api_key;
    private $api_url;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $settings = get_option('whatauth_settings', array());
        $this->api_key = !empty($settings['api_key']) ? trim($settings['api_key']) : '';
        $this->api_url = !empty($settings['api_url']) ? rtrim($settings['api_url'], '/') : WHATAUTH_DEFAULT_API_URL;
    }

    public function set_credentials($api_key, $api_url = null) {
        $this->api_key = trim($api_key);
        if ($api_url) {
            $this->api_url = rtrim($api_url, '/');
        }
    }

    public function get_api_key() {
        return $this->api_key;
    }

    public function get_api_url() {
        return $this->api_url;
    }

    private function request($method, $endpoint, $body = null, $custom_token = null) {
        $url = $this->api_url . '/' . ltrim($endpoint, '/');
        $token = $custom_token ? $custom_token : $this->api_key;

        $headers = array(
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent'   => 'WhatAuth-WordPress/' . WHATAUTH_VERSION . ' (PHP/' . PHP_VERSION . '; ' . home_url() . ')',
        );

        if (!empty($token)) {
            $headers['Authorization'] = 'Bearer ' . $token;
            if (strpos($token, 'wk_') === 0) {
                $headers['x-api-key'] = $token;
            }
        }

        $args = array(
            'method'      => $method,
            'headers'     => $headers,
            'timeout'     => 15,
            'redirection' => 5,
            'sslverify'   => (strpos($this->api_url, 'localhost') === false && strpos($this->api_url, '127.0.0.1') === false),
        );

        if ($body !== null && in_array($method, array('POST', 'PUT', 'PATCH'))) {
            $args['body'] = is_array($body) ? wp_json_encode($body) : $body;
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'status'  => 500,
                'error'   => $response->get_error_message(),
            );
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $raw_body    = wp_remote_retrieve_body($response);
        $data        = json_decode($raw_body, true);

        if ($status_code >= 200 && $status_code < 300) {
            return array(
                'success' => true,
                'status'  => $status_code,
                'data'    => $data,
            );
        }

        $error_message = __('Error desconocido en la API de WhatAuth', 'whatauth');
        if (is_array($data) && !empty($data['error'])) {
            $error_message = $data['error'];
        } elseif (is_array($data) && !empty($data['message'])) {
            $error_message = $data['message'];
        }

        return array(
            'success' => false,
            'status'  => $status_code,
            'error'   => $error_message,
            'data'    => $data,
        );
    }

    /**
     * Verifica la validez de un Service Token o clave y devuelve el perfil y líneas
     */
    public function verify_token($custom_token = null) {
        return $this->request('POST', '/api/auth/verify', array(), $custom_token);
    }

    /**
     * Lista los códigos QR del usuario (LOGIN, PIXEL, PRINT)
     */
    public function get_qrs($type = '') {
        $endpoint = '/api/qr';
        if (!empty($type)) {
            $endpoint .= '?type=' . urlencode(strtoupper($type));
        }
        return $this->request('GET', $endpoint);
    }

    /**
     * Crea o actualiza un código QR con su Auto-Reply y Webhook
     */
    public function save_qr($payload) {
        return $this->request('POST', '/api/qr', $payload);
    }

    /**
     * Elimina un código QR por su identificador
     */
    public function delete_qr($qr_id) {
        return $this->request('DELETE', '/api/qr/' . urlencode($qr_id));
    }

    /**
     * Obtiene el historial de escaneos y logs de validación
     */
    public function get_qr_logs($qr_id = null, $type = null, $limit = 50) {
        $params = array('limit' => $limit);
        if ($qr_id) {
            $params['qrId'] = $qr_id;
        }
        if ($type) {
            $params['type'] = strtoupper($type);
        }
        return $this->request('GET', '/api/qr/logs?' . http_build_query($params));
    }

    /**
     * Obtiene telemetría 360 y serie temporal de escaneos del usuario
     */
    public function get_user_analytics() {
        return $this->request('GET', '/api/user/analytics');
    }

    /**
     * Verifica un ticket de login recibido desde el frontend
     */
    public function verify_login_ticket($ticket) {
        return $this->request('POST', '/api/auth/verify', array('token' => $ticket));
    }
}

