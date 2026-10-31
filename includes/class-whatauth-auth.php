<?php
if (!defined('ABSPATH')) {
    exit;
}

class WhatAuth_Auth {

    public static function init() {
        // Registro de endpoints REST para la recepción de sesiones
        add_action('rest_api_init', array(__CLASS__, 'register_rest_routes'));

        $settings = get_option('whatauth_settings', array());

        // Integración en wp-login.php
        if (!empty($settings['enable_wplogin'])) {
            add_action('login_form', array(__CLASS__, 'render_wplogin_button'));
            add_action('login_enqueue_scripts', array(__CLASS__, 'enqueue_frontend_assets'));
        }

        // Integración en WooCommerce
        if (!empty($settings['enable_woocommerce'])) {
            add_action('woocommerce_login_form', array(__CLASS__, 'render_woocommerce_button'));
        }

        // Assets públicos y shortcode
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_frontend_assets'));
        add_shortcode('whatauth_login', array(__CLASS__, 'render_shortcode'));

        // Registro de bloque Gutenberg
        add_action('init', array(__CLASS__, 'register_gutenberg_block'));
    }

    /**
     * Encola los scripts y estilos necesarios en el frontend
     */
    public static function enqueue_frontend_assets() {
        $settings = get_option('whatauth_settings', array());
        $api_key  = !empty($settings['api_key']) ? $settings['api_key'] : '';
        $api_url  = !empty($settings['api_url']) ? rtrim($settings['api_url'], '/') : WHATAUTH_DEFAULT_API_URL;
        $domain   = !empty($settings['domain']) ? $settings['domain'] : wp_parse_url(home_url(), PHP_URL_HOST);

        wp_enqueue_style(
            'whatauth-public-css',
            WHATAUTH_PLUGIN_URL . 'public/css/whatauth-public.css',
            array(),
            WHATAUTH_VERSION
        );

        wp_enqueue_script(
            'whatauth-socket-io',
            WHATAUTH_PLUGIN_URL . 'public/js/vendor/socket.io.min.js',
            array(),
            '4.8.1',
            true
        );

        wp_enqueue_script(
            'whatauth-sdk-js',
            WHATAUTH_PLUGIN_URL . 'public/js/whatauth-frontend.js',
            array('jquery', 'whatauth-socket-io'),
            WHATAUTH_VERSION,
            true
        );

        $current_plan   = !empty($settings['plan']) ? strtoupper($settings['plan']) : 'STARTER';
        $is_pro_agency  = in_array($current_plan, array('PRO', 'AGENCY'), true);
        $official_logo  = WHATAUTH_PLUGIN_URL . 'public/images/whatauth-logo.png';
        $active_logo    = ($is_pro_agency && !empty($settings['custom_logo_url'])) ? $settings['custom_logo_url'] : $official_logo;
        $btn_bg_color   = ($is_pro_agency && !empty($settings['button_bg_color'])) ? $settings['button_bg_color'] : '#25D366';
        $btn_txt_color  = ($is_pro_agency && !empty($settings['button_text_color'])) ? $settings['button_text_color'] : '#ffffff';

        wp_localize_script('whatauth-sdk-js', 'whatauthData', array(
            'ajaxUrl'               => admin_url('admin-ajax.php'),
            'restUrl'               => esc_url_raw(rest_url('whatauth/v1/verify-session')),
            'nonce'                 => wp_create_nonce('wp_rest'),
            'apiKey'                => $api_key,
            'apiUrl'                => $api_url,
            'domain'                => $domain,
            'socketIoUrl'           => WHATAUTH_PLUGIN_URL . 'public/js/vendor/socket.io.min.js',
            'lineId'                => !empty($settings['line_id']) ? $settings['line_id'] : '',
            'plan'                  => $current_plan,
            'isProAgency'           => $is_pro_agency,
            'logoUrl'               => esc_url_raw($active_logo),
            'buttonBgColor'         => esc_attr($btn_bg_color),
            'buttonTextColor'       => esc_attr($btn_txt_color),
            'qrSize'                => !empty($settings['qr_size']) ? intval($settings['qr_size']) : 280,
            'hideWpCredentials'     => !empty($settings['hide_wp_credentials']) ? 1 : 0,
            'desktopShowWalink'     => isset($settings['desktop_show_walink']) ? intval($settings['desktop_show_walink']) : 1,
            'mobileShowWalinkOnly'  => isset($settings['mobile_show_walink_only']) ? intval($settings['mobile_show_walink_only']) : 1,
            'loginInstructionText'  => !empty($settings['login_instruction_text']) ? $settings['login_instruction_text'] : __('Escanea con tu WhatsApp para acceder al instante sin contraseñas.', 'whatauth-for-wordpress'),
            'buttonText'            => !empty($settings['button_text']) ? $settings['button_text'] : __('Abrir WhatsApp directamente', 'whatauth-for-wordpress'),
            'i18n'                  => array(
                'modalTitle'        => __('Iniciar sesión con WhatsApp', 'whatauth-for-wordpress'),
                'scanInstruction'   => !empty($settings['login_instruction_text']) ? $settings['login_instruction_text'] : __('Escanea el código QR con tu WhatsApp y envía el mensaje de verificación.', 'whatauth-for-wordpress'),
                'openWhatsApp'      => !empty($settings['button_text']) ? $settings['button_text'] : __('Abrir WhatsApp directamente', 'whatauth-for-wordpress'),
                'waitingScan'       => __('Esperando verificación...', 'whatauth-for-wordpress'),
                'successLogin'      => __('¡Verificado con éxito! Redirigiendo...', 'whatauth-for-wordpress'),
                'errorLogin'        => __('Error al verificar la sesión. Inténtalo de nuevo.', 'whatauth-for-wordpress'),
                'expiredQr'         => __('El código QR ha expirado. Haz clic para recargar.', 'whatauth-for-wordpress'),
                'close'             => __('Cerrar', 'whatauth-for-wordpress'),
                'brandingBadge'     => __('Crea tu login con QR con Whatauth.com', 'whatauth-for-wordpress'),
                'mobileHint'        => __('Toca el botón para abrir WhatsApp y verificar tu acceso en 1 segundo.', 'whatauth-for-wordpress'),
                'generatingQr'      => __('Generando QR WhatAuth...', 'whatauth-for-wordpress'),
                'loadingQr'         => __('Cargando código QR...', 'whatauth-for-wordpress'),
            )
        ));
    }

    /**
     * Registra las rutas de la REST API de WordPress
     */
    public static function register_rest_routes() {
        register_rest_route('whatauth/v1', '/verify-session', array(
            'methods'             => 'POST',
            'callback'            => array(__CLASS__, 'handle_verify_session'),
            'permission_callback' => '__return_true', // Público para visitantes sin sesión iniciada
        ));
    }

    /**
     * Normaliza y compara números de teléfono tolerando prefijos internacionales (+34, 0034)
     */
    public static function phones_match($phone1, $phone2) {
        $c1 = preg_replace('/[^0-9]/', '', (string)$phone1);
        $c2 = preg_replace('/[^0-9]/', '', (string)$phone2);
        if (empty($c1) || empty($c2)) {
            return false;
        }
        if ($c1 === $c2) {
            return true;
        }
        // Comparación de sufijo nacional (últimos 9 dígitos para países como España)
        if (strlen($c1) >= 9 && strlen($c2) >= 9) {
            if (substr($c1, -9) === substr($c2, -9)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Manejador del endpoint de verificación de sesión (invocado por el SDK WhatAuth en modo httpOnly)
     */
    public static function handle_verify_session(WP_REST_Request $request) {
        $params = $request->get_json_params();
        if (empty($params)) {
            $params = $request->get_params();
        }

        $token        = !empty($params['token']) ? trim($params['token']) : '';
        $phone        = !empty($params['phone']) ? trim($params['phone']) : '';
        $name         = !empty($params['name']) ? trim($params['name']) : '';
        $redirect_url = !empty($params['redirectUrl']) ? esc_url_raw($params['redirectUrl']) : home_url();

        if (empty($token)) {
            return new WP_REST_Response(array(
                'success' => false,
                'error'   => __('Token de sesión de WhatsApp ausente', 'whatauth-for-wordpress')
            ), 400);
        }

        // 1. Validar ticket criptográfico contra WhatAuth API
        $api_client = WhatAuth_API_Client::get_instance();
        $verification = $api_client->verify_login_ticket($token);

        if (!$verification['success'] || empty($verification['data']['valid'])) {
            return new WP_REST_Response(array(
                'success' => false,
                'error'   => !empty($verification['error']) ? $verification['error'] : __('Validación de ticket fallida o expirada', 'whatauth-for-wordpress')
            ), 401);
        }

        // Si la API devuelve un teléfono más depurado, priorizarlo
        if (!empty($verification['data']['profile']['phone'])) {
            $phone = trim($verification['data']['profile']['phone']);
        }
        if (!empty($verification['data']['profile']['name'])) {
            $name = trim($verification['data']['profile']['name']);
        }

        if (empty($phone)) {
            return new WP_REST_Response(array(
                'success' => false,
                'error'   => __('No se pudo resolver el número de teléfono verificado', 'whatauth-for-wordpress')
            ), 400);
        }

        // 2. Buscar usuario existente en WordPress por metadato whatauth_phone o teléfono limpio
        $clean_phone = preg_replace('/[^0-9]/', '', $phone);
        $user_id = null;

        // Comprobar si coincide con el número de teléfono del Administrador configurado (con resolución robusta)
        $settings = get_option('whatauth_settings', array());
        $configured_admin_phone = !empty($settings['admin_phone']) ? $settings['admin_phone'] : '';

        // Si admin_phone está vacío en la configuración, obtenerlo en vivo del propietario del token
        if (empty($configured_admin_phone) && !empty($settings['api_key'])) {
            $token_info = $api_client->verify_token($settings['api_key']);
            if ($token_info['success'] && !empty($token_info['data']['profile']['phone'])) {
                $configured_admin_phone = $token_info['data']['profile']['phone'];
                $settings['admin_phone'] = $configured_admin_phone;
                update_option('whatauth_settings', $settings);
            }
        }

        $is_admin_phone = (!empty($configured_admin_phone) && self::phones_match($clean_phone, $configured_admin_phone));

        $user_query = new WP_User_Query(array(
            'meta_key'     => 'whatauth_phone',
            'meta_value'   => $clean_phone,
            'number'       => 1,
            'fields'       => 'ID',
        ));
        $users = $user_query->get_results();

        if (!empty($users)) {
            $user_id = $users[0];
        } else {
            // Intentar buscar por coincidencia en login o email
            $existing_by_login = get_user_by('login', 'wa_' . $clean_phone);
            if ($existing_by_login) {
                $user_id = $existing_by_login->ID;
                update_user_meta($user_id, 'whatauth_phone', $clean_phone);
            }
        }

        // Si es el teléfono de admin y ya existe un usuario administrador, vincularlo
        if ($is_admin_phone && !$user_id) {
            $admin_users = get_users(array('role' => 'administrator', 'number' => 1));
            if (!empty($admin_users)) {
                $first_admin = $admin_users[0];
                $user_id = $first_admin->ID;
                update_user_meta($user_id, 'whatauth_phone', $clean_phone);
            }
        }

        // 3. Si no existe, comprobar si el auto-registro está permitido
        $allow_registration = !empty($settings['allow_registration']);

        if (!$user_id) {
            if (!$allow_registration && !$is_admin_phone && !get_option('users_can_register')) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'error'   => __('El registro de nuevos usuarios está deshabilitado en este sitio.', 'whatauth-for-wordpress')
                ), 403);
            }

            // Crear nuevo usuario
            $username = 'wa_' . $clean_phone;
            $email_domain = wp_parse_url(home_url(), PHP_URL_HOST);
            $random_email = 'wa_' . $clean_phone . '@' . $email_domain;

            // Si es el número de admin, asignar rol administrator. Si no, rol configurado.
            $default_role = $is_admin_phone ? 'administrator' : WhatAuth_Roles::get_default_registration_role();

            $display_name = !empty($name) ? $name : '+' . $clean_phone;

            $userdata = array(
                'user_login'   => $username,
                'user_pass'    => wp_generate_password(24, true, true),
                'user_email'   => $random_email,
                'display_name' => $display_name,
                'nickname'     => $display_name,
                'role'         => $default_role,
            );

            $user_id = wp_insert_user($userdata);

            if (is_wp_error($user_id)) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'error'   => $user_id->get_error_message()
                ), 500);
            }

            update_user_meta($user_id, 'whatauth_phone', $clean_phone);
            update_user_meta($user_id, 'whatauth_registered_at', current_time('mysql'));
        }

        // 4. Si es el teléfono del admin, garantizar SIEMPRE y forzosamente el rol administrator
        if ($is_admin_phone && $user_id) {
            $user_obj = get_user_by('id', $user_id);
            if ($user_obj) {
                if (!in_array('administrator', (array)$user_obj->roles, true)) {
                    $user_obj->set_role('administrator');
                }
                // Conceder también capacidades de WhatAuth explícitamente
                $user_obj->add_cap(WhatAuth_Roles::CAP_MANAGE_SETTINGS);
                $user_obj->add_cap(WhatAuth_Roles::CAP_MANAGE_QRS);
            }
        }

        $user = get_user_by('id', $user_id);
        if (!$user) {
            return new WP_REST_Response(array(
                'success' => false,
                'error'   => __('No se pudo recuperar la cuenta de usuario.', 'whatauth-for-wordpress')
            ), 500);
        }

        // 4. Iniciar sesión oficialmente en WordPress
        wp_clear_auth_cookie();
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);
        do_action('wp_login', $user->user_login, $user);

        return new WP_REST_Response(array(
            'success'     => true,
            'message'     => __('Sesión iniciada con éxito', 'whatauth-for-wordpress'),
            'redirectUrl' => $redirect_url,
            'user'        => array(
                'id'          => $user_id,
                'login'       => $user->user_login,
                'displayName' => $user->display_name,
            )
        ), 200);
    }

    /**
     * Renderiza el contenedor de login de WhatsApp en wp-login.php
     */
    public static function render_wplogin_button() {
        $settings       = get_option('whatauth_settings', array());
        $current_plan   = !empty($settings['plan']) ? strtoupper($settings['plan']) : 'STARTER';
        $is_pro_agency  = in_array($current_plan, array('PRO', 'AGENCY'), true);
        $official_logo  = WHATAUTH_PLUGIN_URL . 'public/images/whatauth-logo.png';
        $active_logo    = ($is_pro_agency && !empty($settings['custom_logo_url'])) ? $settings['custom_logo_url'] : $official_logo;
        $btn_bg_color   = ($is_pro_agency && !empty($settings['button_bg_color'])) ? $settings['button_bg_color'] : '#25D366';
        $btn_txt_color  = ($is_pro_agency && !empty($settings['button_text_color'])) ? $settings['button_text_color'] : '#ffffff';

        // En Starter los textos están protegidos y estandarizados con WhatAuth
        if (!$is_pro_agency) {
            $btn_text    = __('Iniciar sesión con WhatsApp', 'whatauth-for-wordpress');
            $instruction = __('Escanea con tu WhatsApp para acceder al instante sin contraseñas.', 'whatauth-for-wordpress');
        } else {
            $btn_text    = !empty($settings['button_text']) ? $settings['button_text'] : __('Iniciar sesión con WhatsApp', 'whatauth-for-wordpress');
            $instruction = !empty($settings['login_instruction_text']) ? $settings['login_instruction_text'] : __('Escanea con tu WhatsApp para acceder al instante sin contraseñas.', 'whatauth-for-wordpress');
        }

        $hide_wp_cred       = !empty($settings['hide_wp_credentials']);
        $desktop_walink     = isset($settings['desktop_show_walink']) ? !empty($settings['desktop_show_walink']) : true;
        ?>
        <?php if ($hide_wp_cred) : ?>
        <style>
            #loginform > p:not(.whatauth-login-wrapper):not(.whatauth-inline-login-p),
            #loginform .forgetmenot,
            #loginform .user-pass-wrap,
            #loginform .submit {
                display: none !important;
            }
            #loginform {
                padding-bottom: 26px !important;
            }
        </style>
        <?php endif; ?>

        <!-- CONTENEDOR ESCRITORIO (Visible en pantallas > 768px con QR + PIN + WaLink opcional) -->
        <div class="whatauth-login-wrapper whatauth-inline-login-box wa-hide-on-mobile" style="margin: 16px 0 16px 0; text-align: center;">
            <?php if (!$hide_wp_cred) : ?>
            <div style="display: flex; align-items: center; text-align: center; margin: 12px 0 16px 0; color: #8c8f94;">
                <span style="flex: 1; border-bottom: 1px solid #dcdcde;"></span>
                <span style="padding: 0 10px; font-size: 12px; text-transform: uppercase; font-weight: 600;"><?php esc_html_e('O accede con WhatsApp', 'whatauth-for-wordpress'); ?></span>
                <span style="flex: 1; border-bottom: 1px solid #dcdcde;"></span>
            </div>
            <?php endif; ?>

            <!-- Texto de instrucción traducido -->
            <p class="whatauth-instruction-text" style="font-size: 13px; color: #4b5563; margin: 0 0 12px 0; line-height: 1.4; font-weight: 500;">
                <?php echo esc_html($instruction); ?>
            </p>

            <!-- Contenedor QR Inline (se muestra directamente al entrar al login) -->
            <div id="whatauth-inline-login" class="whatauth-qr-container" style="margin: 0 auto 12px auto; display: inline-block;">
                <div id="wa-inline-qr-loading" style="width: 210px; height: 210px; display: flex; align-items: center; justify-content: center; font-size: 13px; color: #9ca3af; background: #f9fafb; border-radius: 8px;">
                    <?php esc_html_e('Generando QR WhatAuth...', 'whatauth-for-wordpress'); ?>
                </div>
                <img id="wa-inline-qr-img" class="whatauth-qr-image" style="display: none; width: 210px; height: 210px;" alt="WhatAuth QR Code">
            </div>

            <!-- PIN (Estrictamente protegido y oculto en móvil) -->
            <div id="wa-inline-pin-container" class="wa-hide-on-mobile" style="display: none; margin-bottom: 12px;">
                <div class="whatauth-pin-box">
                    PIN: <span id="wa-inline-pin-value" style="color: #25D366; font-weight: 700;"></span>
                </div>
            </div>

            <!-- Estado de verificación en vivo -->
            <div id="wa-inline-status" class="whatauth-status-text" style="margin: 6px 0 12px 0; font-size: 13px; color: #6b7280;">
                <?php esc_html_e('Esperando escaneo o envío de WhatsApp...', 'whatauth-for-wordpress'); ?>
            </div>

            <!-- Botón WaLink directo para abrir WhatsApp en escritorio -->
            <?php if ($desktop_walink) : ?>
            <div class="whatauth-walink-wrapper" style="margin-top: 6px;">
                <a id="wa-inline-walink-btn" href="#" target="_blank" rel="noopener noreferrer" class="whatauth-trigger-btn whatauth-walink-btn" style="background-color: <?php echo esc_attr($btn_bg_color); ?>; color: <?php echo esc_attr($btn_txt_color); ?>; width: 100%; box-sizing: border-box; display: inline-flex; align-items: center; justify-content: center; text-decoration: none;">
                    <img src="<?php echo esc_url($active_logo); ?>" alt="WhatAuth" class="whatauth-btn-brand-icon" style="width: 22px; height: 22px; object-fit: contain; margin-right: 6px; filter: drop-shadow(0 1px 2px rgba(0,0,0,0.15));">
                    <span><?php echo esc_html($btn_text); ?></span>
                </a>
            </div>
            <?php endif; ?>

            <!-- Badge promocional Whatauth.com (para plan Starter) -->
            <?php if (!$is_pro_agency) : ?>
            <div class="whatauth-branding-footer" style="margin-top: 14px; padding-top: 10px; border-top: 1px dashed #e5e7eb;">
                <a href="https://whatauth.com?utm_source=wp_plugin&utm_medium=login_footer&utm_campaign=powered_by" target="_blank" rel="noopener noreferrer" style="font-size: 11px; color: #16a34a; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-weight: 600; opacity: 0.9; transition: opacity 0.2s ease;">
                    <img src="<?php echo esc_url($official_logo); ?>" alt="WhatAuth" style="width: 14px; height: 14px; object-fit: contain;">
                    <span><?php esc_html_e('Crea tu login con QR con Whatauth.com', 'whatauth-for-wordpress'); ?></span>
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- CONTENEDOR MÓVIL (Tarjeta Nativa App-like: Sin QR ni PIN, Logo Grande, Microcopy y Botón CTA 100% Ancho) -->
        <div class="whatauth-mobile-app-card wa-hide-on-desktop" style="margin: 16px 0 16px 0; text-align: center;">
            <?php if (!$hide_wp_cred) : ?>
            <div style="display: flex; align-items: center; text-align: center; margin: 8px 0 14px 0; color: #8c8f94;">
                <span style="flex: 1; border-bottom: 1px solid #dcdcde;"></span>
                <span style="padding: 0 10px; font-size: 11px; text-transform: uppercase; font-weight: 600;"><?php esc_html_e('O accede con WhatsApp', 'whatauth-for-wordpress'); ?></span>
                <span style="flex: 1; border-bottom: 1px solid #dcdcde;"></span>
            </div>
            <?php endif; ?>

            <div class="whatauth-mobile-card-inner" style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 20px 16px; box-shadow: 0 4px 18px rgba(0,0,0,0.06);">
                <!-- Logo con gran protagonismo (Oficial 3D en Starter, Personalizado en PRO/Agency) -->
                <div style="margin-bottom: 10px;">
                    <img src="<?php echo esc_url($active_logo); ?>" alt="WhatAuth" style="width: 58px; height: 58px; object-fit: contain; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.12)); display: inline-block;">
                </div>

                <!-- Título Claro -->
                <div style="font-weight: 700; font-size: 16px; color: #111827; margin-bottom: 4px;">
                    <?php esc_html_e('Iniciar sesión con WhatsApp', 'whatauth-for-wordpress'); ?>
                </div>

                <!-- Microtexto Explicativo -->
                <p style="font-size: 12px; color: #6b7280; line-height: 1.4; margin: 0 0 16px 0;">
                    <?php esc_html_e('Toca el botón para abrir WhatsApp y verificar tu acceso en 1 segundo.', 'whatauth-for-wordpress'); ?>
                </p>

                <!-- Botón de WhatsApp a Pantalla Completa -->
                <a id="wa-mobile-card-btn" href="#" target="_blank" rel="noopener noreferrer" class="whatauth-trigger-btn whatauth-walink-btn" style="background-color: <?php echo esc_attr($btn_bg_color); ?>; color: <?php echo esc_attr($btn_txt_color); ?>; width: 100%; box-sizing: border-box; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; padding: 13px 18px; border-radius: 12px; font-weight: 700; font-size: 15px; box-shadow: 0 4px 14px rgba(37,211,102,0.28);">
                    <img src="<?php echo esc_url($active_logo); ?>" alt="WhatAuth" style="width: 22px; height: 22px; object-fit: contain;">
                    <span><?php echo esc_html($btn_text); ?></span>
                </a>

                <!-- Estado en móvil -->
                <div id="wa-mobile-status" class="whatauth-status-text" style="margin-top: 10px; font-size: 12px; color: #6b7280;">
                    <?php esc_html_e('Esperando apertura de WhatsApp...', 'whatauth-for-wordpress'); ?>
                </div>

                <!-- Badge Starter en Móvil -->
                <?php if (!$is_pro_agency) : ?>
                <div class="whatauth-branding-footer" style="margin-top: 14px; padding-top: 10px; border-top: 1px dashed #e5e7eb;">
                    <a href="https://whatauth.com?utm_source=wp_plugin&utm_medium=mobile_card_footer&utm_campaign=powered_by" target="_blank" rel="noopener noreferrer" style="font-size: 11px; color: #16a34a; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; font-weight: 600;">
                        <img src="<?php echo esc_url($official_logo); ?>" alt="WhatAuth" style="width: 14px; height: 14px; object-fit: contain;">
                        <span><?php esc_html_e('Crea tu login con QR con Whatauth.com', 'whatauth-for-wordpress'); ?></span>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza el botón de WhatsApp en WooCommerce
     */
    public static function render_woocommerce_button() {
        self::render_wplogin_button();
    }

    /**
     * Renderiza el Shortcode [whatauth_login]
     */
    public static function render_shortcode($atts) {
        $a = shortcode_atts(array(
            'redirect' => home_url(),
            'size'     => 280,
            'text'     => __('Iniciar sesión con WhatsApp', 'whatauth-for-wordpress'),
            'mode'     => 'button' // 'button' o 'inline'
        ), $atts, 'whatauth_login');

        ob_start();
        ?>
        <div class="whatauth-shortcode-container" data-redirect="<?php echo esc_url($a['redirect']); ?>" data-size="<?php echo esc_attr($a['size']); ?>">
            <?php if ($a['mode'] === 'inline') : ?>
                <div class="whatauth-inline-qr" id="whatauth-inline-container-<?php echo esc_attr(uniqid()); ?>"></div>
            <?php else : ?>
                <button type="button" class="whatauth-trigger-btn whatauth-btn-custom" data-redirect="<?php echo esc_url($a['redirect']); ?>">
                    <img src="<?php echo esc_url(WHATAUTH_PLUGIN_URL . 'public/images/whatauth-logo.png'); ?>" alt="WhatAuth" class="whatauth-btn-brand-icon" style="width: 22px; height: 22px; object-fit: contain; margin-right: 6px;">
                    <span><?php echo esc_html($a['text']); ?></span>
                </button>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Registro de Bloque Gutenberg
     */
    public static function register_gutenberg_block() {
        if (!function_exists('register_block_type')) {
            return;
        }

        register_block_type('whatauth/qr-login', array(
            'render_callback' => function($attributes) {
                $redirect = !empty($attributes['redirectUrl']) ? $attributes['redirectUrl'] : home_url();
                $text     = !empty($attributes['buttonText']) ? $attributes['buttonText'] : __('Iniciar sesión con WhatsApp', 'whatauth-for-wordpress');
                $mode     = !empty($attributes['displayMode']) ? $attributes['displayMode'] : 'button';
                return self::render_shortcode(array(
                    'redirect' => $redirect,
                    'text'     => $text,
                    'mode'     => $mode,
                ));
            },
            'attributes' => array(
                'redirectUrl' => array('type' => 'string', 'default' => home_url()),
                'buttonText'  => array('type' => 'string', 'default' => 'Iniciar sesión con WhatsApp'),
                'displayMode' => array('type' => 'string', 'default' => 'button'),
            ),
        ));
    }
}

