<?php
if (!defined('ABSPATH')) {
    exit;
}

class WhatAuth_QR_Manager {

    public static function init() {
        // Acciones AJAX para el panel de administración
        add_action('wp_ajax_whatauth_get_dashboard_data', array(__CLASS__, 'ajax_get_dashboard_data'));
        add_action('wp_ajax_whatauth_get_qrs', array(__CLASS__, 'ajax_get_qrs'));
        add_action('wp_ajax_whatauth_save_qr', array(__CLASS__, 'ajax_save_qr'));
        add_action('wp_ajax_whatauth_delete_qr', array(__CLASS__, 'ajax_delete_qr'));
        add_action('wp_ajax_whatauth_verify_token', array(__CLASS__, 'ajax_verify_token'));
        add_action('wp_ajax_whatauth_save_settings', array(__CLASS__, 'ajax_save_settings'));
    }

    /**
     * AJAX: Obtener datos completos para el Dashboard (KPIs, Gráfica y Logs de Actividad)
     */
    public static function ajax_get_dashboard_data() {
        check_ajax_referer('whatauth_admin_nonce', 'nonce');

        if (!WhatAuth_Roles::can_manage_qrs()) {
            wp_send_json_error(array('message' => __('Permisos insuficientes.', 'whatauth-for-wordpress')), 403);
        }

        $client = WhatAuth_API_Client::get_instance();

        // 1. Obtener listado de QRs para KPIs inmediatos
        $qrs_res = $client->get_qrs();
        $qrs = ($qrs_res['success'] && !empty($qrs_res['data']['qrs'])) ? $qrs_res['data']['qrs'] : array();

        $total_qrs = count($qrs);
        $login_qrs = 0;
        $pixel_qrs = 0;
        $print_qrs = 0;

        foreach ($qrs as $qr) {
            $t = !empty($qr['type']) ? strtoupper($qr['type']) : 'LOGIN';
            if ($t === 'LOGIN') {
                $login_qrs++;
            } elseif ($t === 'PIXEL') {
                $pixel_qrs++;
            } elseif ($t === 'PRINT') {
                $print_qrs++;
            }
        }

        // 2. Obtener telemetría 360 y serie temporal de scans
        $analytics_res = $client->get_user_analytics();
        $trend30d = array();
        $metrics = array();
        $recent_access = array();

        if ($analytics_res['success'] && !empty($analytics_res['data']['user'])) {
            $user_data = $analytics_res['data']['user'];
            $trend30d = !empty($user_data['trend30d']) ? $user_data['trend30d'] : array();
            $metrics = !empty($user_data['metrics']) ? $user_data['metrics'] : array();
            $recent_access = !empty($user_data['recentAccessLogs']) ? $user_data['recentAccessLogs'] : array();
        }

        // 3. Obtener histórico de escaneos de QRs
        $logs_res = $client->get_qr_logs(null, null, 25);
        $qr_logs = array();
        if ($logs_res['success']) {
            if (!empty($logs_res['data']['logs'])) {
                $qr_logs = $logs_res['data']['logs'];
            } elseif (!empty($logs_res['data']) && is_array($logs_res['data'])) {
                $qr_logs = $logs_res['data'];
            }
        }

        wp_send_json_success(array(
            'stats' => array(
                'total' => $total_qrs,
                'login' => $login_qrs,
                'pixel' => $pixel_qrs,
                'print' => $print_qrs,
            ),
            'trend'         => $trend30d,
            'metrics'       => $metrics,
            'qrLogs'        => $qr_logs,
            'recentAccess'  => $recent_access,
        ));
    }

    /**
     * AJAX: Obtener listado de QRs
     */
    public static function ajax_get_qrs() {
        check_ajax_referer('whatauth_admin_nonce', 'nonce');

        if (!WhatAuth_Roles::can_manage_qrs()) {
            wp_send_json_error(array('message' => __('Permisos insuficientes.', 'whatauth-for-wordpress')), 403);
        }

        $type = isset($_GET['type']) ? sanitize_text_field(wp_unslash($_GET['type'])) : '';
        $client = WhatAuth_API_Client::get_instance();
        $response = $client->get_qrs($type);

        if (!$response['success']) {
            wp_send_json_error(array('message' => $response['error']), $response['status']);
        }

        $qrs = !empty($response['data']['qrs']) ? $response['data']['qrs'] : array();
        wp_send_json_success(array('qrs' => $qrs));
    }

    /**
     * AJAX: Crear o actualizar un código QR con Auto-Reply y Webhooks
     */
    public static function ajax_save_qr() {
        check_ajax_referer('whatauth_admin_nonce', 'nonce');

        if (!WhatAuth_Roles::can_manage_qrs()) {
            wp_send_json_error(array('message' => __('Permisos insuficientes.', 'whatauth-for-wordpress')), 403);
        }

        $type = isset($_POST['type']) ? strtoupper(sanitize_text_field(wp_unslash($_POST['type']))) : 'LOGIN';
        $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
        $prefilled = isset($_POST['prefilledMessage']) ? sanitize_textarea_field(wp_unslash($_POST['prefilledMessage'])) : '';
        $line_id = isset($_POST['lineId']) ? sanitize_text_field(wp_unslash($_POST['lineId'])) : null;
        $id = !empty($_POST['id']) ? sanitize_text_field(wp_unslash($_POST['id'])) : null;

        $settings = get_option('whatauth_settings', array());
        $current_plan = !empty($settings['plan']) ? strtoupper($settings['plan']) : 'STARTER';
        $is_pro_agency = in_array($current_plan, array('PRO', 'AGENCY'), true);

        // Regla: si se elige el Bot Anfitrión (line_id vacío), el Auto-Reply no está disponible
        // da igual si el usuario es PRO o AGENCY. Solo se activa si tiene línea propia y plan PRO/AGENCY.
        $has_dedicated_line = !empty($line_id);
        $can_autoreply      = ($is_pro_agency && $has_dedicated_line);
        $auto_reply_enabled = ($can_autoreply && !empty($_POST['autoReplyEnabled']));

        $auto_reply_text     = $can_autoreply && isset($_POST['autoReplyText']) ? sanitize_textarea_field(wp_unslash($_POST['autoReplyText'])) : (isset($_POST['autoReplyContent']) ? sanitize_textarea_field(wp_unslash($_POST['autoReplyContent'])) : '');
        $auto_reply_image    = $can_autoreply && isset($_POST['autoReplyImage']) ? esc_url_raw(wp_unslash($_POST['autoReplyImage'])) : '';
        $auto_reply_audio    = $can_autoreply && isset($_POST['autoReplyAudio']) ? esc_url_raw(wp_unslash($_POST['autoReplyAudio'])) : '';
        $auto_reply_doc      = $can_autoreply && isset($_POST['autoReplyDocument']) ? esc_url_raw(wp_unslash($_POST['autoReplyDocument'])) : '';
        $auto_reply_doc_name = $can_autoreply && isset($_POST['autoReplyDocumentName']) ? sanitize_text_field(wp_unslash($_POST['autoReplyDocumentName'])) : '';
        $auto_reply_url      = $can_autoreply && isset($_POST['autoReplyUrl']) ? esc_url_raw(wp_unslash($_POST['autoReplyUrl'])) : '';

        $webhook_url = isset($_POST['webhookUrl']) ? esc_url_raw(wp_unslash($_POST['webhookUrl'])) : '';
        $webhook_enabled = !empty($_POST['webhookEnabled']);

        if (empty($title)) {
            wp_send_json_error(array('message' => __('El título del código QR es obligatorio.', 'whatauth-for-wordpress')), 400);
        }

        $payload = array(
            'type'             => $type,
            'title'            => $title,
            'name'             => $title,
            'prefilledMessage' => $prefilled,
            'text'             => $prefilled,
            'lineId'           => !empty($line_id) ? $line_id : null,
            'autoReply'        => array(
                'enabled'      => $auto_reply_enabled,
                'mode'         => $auto_reply_enabled ? 'STATIC' : 'NONE',
                'content'      => $auto_reply_text,
                'text'         => $auto_reply_text,
                'image'        => $auto_reply_image,
                'audio'        => $auto_reply_audio,
                'document'     => $auto_reply_doc,
                'documentName' => $auto_reply_doc_name,
                'url'          => $auto_reply_url,
            ),
            'autoReplyEnabled'      => $auto_reply_enabled,
            'autoReplyText'         => $auto_reply_text,
            'autoReplyImage'        => $auto_reply_image,
            'autoReplyAudio'        => $auto_reply_audio,
            'autoReplyDocument'     => $auto_reply_doc,
            'autoReplyDocumentName' => $auto_reply_doc_name,
            'autoReplyUrl'          => $auto_reply_url,
            'webhook'          => array(
                'url'     => $webhook_url,
                'enabled' => $webhook_enabled,
            ),
        );

        // Configuración integral de branding para PRO y Agency (o valores por defecto WhatAuth para Starter)
        if ($is_pro_agency) {
            $payload['branding'] = array(
                'logoUrl'              => isset($_POST['logoUrl']) ? esc_url_raw(wp_unslash($_POST['logoUrl'])) : '',
                'logoMargin'           => isset($_POST['logoMargin']) ? intval(wp_unslash($_POST['logoMargin'])) : 6,
                'logoSize'             => isset($_POST['logoSize']) ? floatval(wp_unslash($_POST['logoSize'])) : (isset($_POST['logoScale']) ? floatval(wp_unslash($_POST['logoScale'])) : 0.22),
                'dotsColor'            => isset($_POST['dotsColor']) ? sanitize_hex_color(wp_unslash($_POST['dotsColor'])) : '#1a2517',
                'dotsType'             => isset($_POST['dotsType']) ? sanitize_text_field(wp_unslash($_POST['dotsType'])) : (isset($_POST['dotsShape']) ? sanitize_text_field(wp_unslash($_POST['dotsShape'])) : 'square'),
                'errorCorrectionLevel' => isset($_POST['errorCorrectionLevel']) ? sanitize_text_field(wp_unslash($_POST['errorCorrectionLevel'])) : 'Q',
                'cornersSquareColor'   => isset($_POST['cornersSquareColor']) ? sanitize_hex_color(wp_unslash($_POST['cornersSquareColor'])) : (isset($_POST['cornersColor']) ? sanitize_hex_color(wp_unslash($_POST['cornersColor'])) : '#1a2517'),
                'cornersSquareType'    => isset($_POST['cornersSquareType']) ? sanitize_text_field(wp_unslash($_POST['cornersSquareType'])) : (isset($_POST['cornersShape']) ? sanitize_text_field(wp_unslash($_POST['cornersShape'])) : 'extra-rounded'),
                'cornersDotColor'      => isset($_POST['cornersDotColor']) ? sanitize_hex_color(wp_unslash($_POST['cornersDotColor'])) : '#6f8065',
                'cornersDotType'       => isset($_POST['cornersDotType']) ? sanitize_text_field(wp_unslash($_POST['cornersDotType'])) : 'dot',
                'bgColor'              => isset($_POST['bgColor']) ? sanitize_hex_color(wp_unslash($_POST['bgColor'])) : '#ffffff',
            );
        }

        if (!empty($id)) {
            $payload['id'] = $id;
        }

        // Parámetros específicos para PRINT QR (geolocalización y dirección física)
        if ($type === 'PRINT') {
            $payload['address']   = isset($_POST['address']) ? sanitize_text_field(wp_unslash($_POST['address'])) : '';
            $payload['city']      = isset($_POST['city']) ? sanitize_text_field(wp_unslash($_POST['city'])) : '';
            $payload['country']   = isset($_POST['country']) ? sanitize_text_field(wp_unslash($_POST['country'])) : '';
            $payload['latitude']  = isset($_POST['latitude']) ? floatval(wp_unslash($_POST['latitude'])) : null;
            $payload['longitude'] = isset($_POST['longitude']) ? floatval(wp_unslash($_POST['longitude'])) : null;
        }

        $client = WhatAuth_API_Client::get_instance();
        $response = $client->save_qr($payload);

        if (!$response['success']) {
            wp_send_json_error(array('message' => $response['error']), $response['status']);
        }

        wp_send_json_success(array(
            'message' => __('Código QR guardado correctamente.', 'whatauth-for-wordpress'),
            'qr'      => !empty($response['data']) ? $response['data'] : array(),
        ));
    }

    /**
     * AJAX: Eliminar un código QR
     */
    public static function ajax_delete_qr() {
        check_ajax_referer('whatauth_admin_nonce', 'nonce');

        if (!WhatAuth_Roles::can_manage_qrs()) {
            wp_send_json_error(array('message' => __('Permisos insuficientes.', 'whatauth-for-wordpress')), 403);
        }

        // Restricción por Plan: Cuentas Starter no pueden eliminar códigos QR
        $settings = get_option('whatauth_settings', array());
        $current_plan = !empty($settings['plan']) ? strtoupper($settings['plan']) : 'STARTER';
        if (!in_array($current_plan, array('PRO', 'AGENCY'), true)) {
            wp_send_json_error(array(
                'message' => __('La eliminación de códigos QR está reservada para cuentas PRO y Agency.', 'whatauth-for-wordpress')
            ), 403);
        }

        $qr_id = isset($_POST['id']) ? sanitize_text_field(wp_unslash($_POST['id'])) : '';
        if (empty($qr_id)) {
            wp_send_json_error(array('message' => __('Identificador de QR inválido.', 'whatauth-for-wordpress')), 400);
        }

        $client = WhatAuth_API_Client::get_instance();
        $response = $client->delete_qr($qr_id);

        if (!$response['success']) {
            wp_send_json_error(array('message' => $response['error']), $response['status']);
        }

        wp_send_json_success(array('message' => __('Código QR eliminado.', 'whatauth-for-wordpress')));
    }

    /**
     * AJAX: Probar validez de API Key o Service Token y persistir plan y líneas
     */
    public static function ajax_verify_token() {
        check_ajax_referer('whatauth_admin_nonce', 'nonce');

        if (!WhatAuth_Roles::can_manage_settings()) {
            wp_send_json_error(array('message' => __('Permisos insuficientes.', 'whatauth-for-wordpress')), 403);
        }

        $token   = isset($_POST['api_key']) ? trim(sanitize_text_field(wp_unslash($_POST['api_key']))) : '';
        $api_url = isset($_POST['api_url']) ? rtrim(sanitize_text_field(wp_unslash($_POST['api_url'])), '/') : null;

        if (empty($token)) {
            wp_send_json_error(array('message' => __('Introduce un token para verificar.', 'whatauth-for-wordpress')), 400);
        }

        $client = WhatAuth_API_Client::get_instance();
        if ($api_url) {
            $client->set_credentials($token, $api_url);
        }

        $response = $client->verify_token($token);

        if (!$response['success'] || empty($response['data']['valid'])) {
            $err_msg = !empty($response['error']) ? $response['error'] : __('Credencial inválida o no reconocida.', 'whatauth-for-wordpress');
            wp_send_json_error(array('message' => $err_msg));
            return;
        }

        $api_data = $response['data'];
        $plan     = !empty($api_data['plan']) ? strtoupper(sanitize_text_field($api_data['plan'])) : 'STARTER';
        $lines    = !empty($api_data['lines']) && is_array($api_data['lines']) ? $api_data['lines'] : array();

        // Persistir inmediatamente el plan y credenciales en WordPress
        $settings = get_option('whatauth_settings', array());
        $settings['api_key'] = $token;
        if ($api_url) {
            $settings['api_url'] = $api_url;
        }
        $settings['plan'] = $plan;
        $settings['lines'] = $lines;

        // Auto-asignación inteligente de línea dedicada para planes PRO y AGENCY
        $active_line_id = !empty($settings['line_id']) ? $settings['line_id'] : '';
        if (in_array($plan, array('PRO', 'AGENCY'), true) && !empty($lines)) {
            $line_found = false;
            foreach ($lines as $l) {
                if (!empty($l['id']) && $l['id'] === $active_line_id) {
                    $line_found = true;
                    break;
                }
            }
            // Si la línea actual no es válida o estaba vacía (bot oficial), auto-seleccionar la primera línea propia
            if (!$line_found && !empty($lines[0]['id'])) {
                $active_line_id = sanitize_text_field($lines[0]['id']);
                $settings['line_id'] = $active_line_id;
            }
        }

        // Si la API retorna teléfono de perfil y no teníamos teléfono de admin configurado, autocompletar
        if (!empty($api_data['profile']['phone']) && empty($settings['admin_phone'])) {
            $settings['admin_phone'] = preg_replace('/[^0-9]/', '', $api_data['profile']['phone']);
        }

        update_option('whatauth_settings', $settings);

        wp_send_json_success(array(
            'message'        => __('Conexión exitosa con WhatAuth.', 'whatauth-for-wordpress'),
            'data'           => $api_data,
            'plan'           => $plan,
            'lines'          => $lines,
            'active_line_id' => $active_line_id,
            'settings'       => $settings,
        ));
    }

    /**
     * AJAX: Guardar ajustes generales y de roles
     */
    public static function ajax_save_settings() {
        check_ajax_referer('whatauth_admin_nonce', 'nonce');

        if (!WhatAuth_Roles::can_manage_settings()) {
            wp_send_json_error(array('message' => __('Permisos insuficientes.', 'whatauth-for-wordpress')), 403);
        }

        $api_key            = isset($_POST['api_key']) ? trim(sanitize_text_field(wp_unslash($_POST['api_key']))) : '';
        $api_url                 = isset($_POST['api_url']) ? rtrim(sanitize_text_field(wp_unslash($_POST['api_url'])), '/') : WHATAUTH_DEFAULT_API_URL;
        $domain                  = isset($_POST['domain']) ? sanitize_text_field(wp_unslash($_POST['domain'])) : wp_parse_url(home_url(), PHP_URL_HOST);
        $line_id                 = isset($_POST['line_id']) ? sanitize_text_field(wp_unslash($_POST['line_id'])) : '';
        $admin_phone             = isset($_POST['admin_phone']) ? sanitize_text_field(wp_unslash($_POST['admin_phone'])) : '';
        $allow_registration      = !empty($_POST['allow_registration']) ? 1 : 0;
        $default_role            = isset($_POST['default_role']) ? sanitize_text_field(wp_unslash($_POST['default_role'])) : 'subscriber;
        $hide_wp_credentials     = !empty($_POST['hide_wp_credentials']) ? 1 : 0;
        $desktop_show_walink     = !empty($_POST['desktop_show_walink']) ? 1 : 0;
        $mobile_show_walink_only = !empty($_POST['mobile_show_walink_only']) ? 1 : 0;
        $enable_wplogin          = !empty($_POST['enable_wplogin']) ? 1 : 0;
        $enable_woocommerce      = !empty($_POST['enable_woocommerce']) ? 1 : 0;
        $qr_size                 = isset($_POST['qr_size']) ? intval(wp_unslash($_POST['qr_size'])) : 280;
        $button_text             = isset($_POST['button_text']) ? sanitize_text_field(wp_unslash($_POST['button_text'])) : __('Abrir WhatsApp directamente', 'whatauth-for-wordpress');
        $login_instruction_text  = isset($_POST['login_instruction_text']) ? sanitize_text_field(wp_unslash($_POST['login_instruction_text'])) : __('Escanea con tu WhatsApp para acceder al instante sin contraseñas.', 'whatauth-for-wordpress');

        $existing_settings       = get_option('whatauth_settings', array());
        $current_plan            = isset($_POST['plan']) ? sanitize_text_field(wp_unslash($_POST['plan'])) : (!empty($existing_settings['plan']) ? $existing_settings['plan'] : 'STARTER');
        $is_pro_agency           = in_array(strtoupper($current_plan), array('PRO', 'AGENCY'), true);

        // Si es STARTER, forzar valores oficiales de WhatAuth
        if (!$is_pro_agency) {
            $button_text            = __('Abrir WhatsApp directamente', 'whatauth-for-wordpress');
            $login_instruction_text = __('Escanea con tu WhatsApp para acceder al instante sin contraseñas.', 'whatauth-for-wordpress');
            $button_bg_color        = '#25D366';
            $button_text_color      = '#ffffff';
            $custom_logo_url        = '';
        } else {
            $button_bg_color        = isset($_POST['button_bg_color']) ? sanitize_hex_color(wp_unslash($_POST['button_bg_color'])) : (!empty($existing_settings['button_bg_color']) ? $existing_settings['button_bg_color'] : '#25D366');
            $button_text_color      = isset($_POST['button_text_color']) ? sanitize_hex_color(wp_unslash($_POST['button_text_color'])) : (!empty($existing_settings['button_text_color']) ? $existing_settings['button_text_color'] : '#ffffff');
            $custom_logo_url        = isset($_POST['custom_logo_url']) ? esc_url_raw(wp_unslash($_POST['custom_logo_url'])) : (!empty($existing_settings['custom_logo_url']) ? $existing_settings['custom_logo_url'] : '');
        }

        $settings = array(
            'api_key'                 => $api_key,
            'api_url'                 => $api_url,
            'domain'                  => $domain,
            'line_id'                 => $line_id,
            'admin_phone'             => $admin_phone,
            'allow_registration'      => $allow_registration,
            'default_role'            => $default_role,
            'hide_wp_credentials'     => $hide_wp_credentials,
            'desktop_show_walink'     => $desktop_show_walink,
            'mobile_show_walink_only' => $mobile_show_walink_only,
            'enable_wplogin'          => $enable_wplogin,
            'enable_woocommerce'      => $enable_woocommerce,
            'qr_size'                 => $qr_size,
            'button_text'             => $button_text,
            'login_instruction_text'  => $login_instruction_text,
            'button_bg_color'         => $button_bg_color,
            'button_text_color'       => $button_text_color,
            'custom_logo_url'         => $custom_logo_url,
            'plan'                    => $current_plan,
            'setup_completed'         => 1,
        );

        update_option('whatauth_settings', $settings);

        // Actualizar permisos de roles para gestión de QRs
        if (isset($_POST['qr_allowed_roles']) && is_array($_POST['qr_allowed_roles'])) {
            $allowed_roles = array_map('sanitize_text_field', wp_unslash($_POST['qr_allowed_roles']));
            WhatAuth_Roles::update_role_permissions($allowed_roles);
        }

        wp_send_json_success(array(
            'message'  => __('Ajustes guardados correctamente.', 'whatauth-for-wordpress'),
            'settings' => $settings,
        ));
    }
}

