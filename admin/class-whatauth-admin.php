<?php
if (!defined('ABSPATH')) {
    exit;
}

class WhatAuth_Admin {

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'register_admin_menus'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_assets'));
        add_filter('plugin_action_links_' . plugin_basename(WHATAUTH_PLUGIN_FILE), array(__CLASS__, 'add_plugin_action_links'));
        add_action('admin_notices', array(__CLASS__, 'render_setup_notice'));
    }

    /**
     * Registra los menús de WhatAuth en WP-Admin
     */
    public static function register_admin_menus() {
        $icon_url = WHATAUTH_PLUGIN_URL . 'public/images/whatauth-menu-icon.png';

        add_menu_page(
            'WhatAuth',
            'WhatAuth',
            WhatAuth_Roles::CAP_MANAGE_QRS,
            'whatauth',
            array(__CLASS__, 'render_dashboard_page'),
            $icon_url,
            30
        );

        add_submenu_page(
            'whatauth',
            __('WhatAuth – Resumen', 'whatauth-for-wordpress'),
            __('Resumen', 'whatauth-for-wordpress'),
            WhatAuth_Roles::CAP_MANAGE_QRS,
            'whatauth',
            array(__CLASS__, 'render_dashboard_page')
        );

        add_submenu_page(
            'whatauth',
            __('WhatAuth – Gestor de QRs', 'whatauth-for-wordpress'),
            __('Gestor de QRs', 'whatauth-for-wordpress'),
            WhatAuth_Roles::CAP_MANAGE_QRS,
            'whatauth-qrs',
            array(__CLASS__, 'render_qr_manager_page')
        );

        add_submenu_page(
            'whatauth',
            __('WhatAuth – Ajustes & Roles', 'whatauth-for-wordpress'),
            __('Ajustes & Roles', 'whatauth-for-wordpress'),
            WhatAuth_Roles::CAP_MANAGE_SETTINGS,
            'whatauth-settings',
            array(__CLASS__, 'render_settings_page')
        );

        // Submenú oculto para el asistente de instalación
        add_submenu_page(
            null,
            __('WhatAuth – Asistente de Configuración', 'whatauth-for-wordpress'),
            __('Asistente', 'whatauth-for-wordpress'),
            WhatAuth_Roles::CAP_MANAGE_SETTINGS,
            'whatauth-wizard',
            array(__CLASS__, 'render_wizard_page')
        );
    }

    /**
     * Encola estilos y scripts en el panel de administración
     */
    public static function enqueue_admin_assets($hook) {
        if (strpos($hook, 'whatauth') === false) {
            return;
        }

        // Habilitar Media Uploader para selección de logos personalizados
        wp_enqueue_media();

        wp_enqueue_style(
            'whatauth-admin-css',
            WHATAUTH_PLUGIN_URL . 'admin/css/whatauth-admin.css',
            array(),
            WHATAUTH_VERSION
        );

        wp_enqueue_script(
            'qr-code-styling',
            WHATAUTH_PLUGIN_URL . 'admin/js/vendor/qr-code-styling.js',
            array(),
            '1.5.0',
            true
        );

        wp_enqueue_script(
            'whatauth-admin-js',
            WHATAUTH_PLUGIN_URL . 'admin/js/whatauth-admin.js',
            array('jquery', 'qr-code-styling'),
            WHATAUTH_VERSION,
            true
        );

        $settings = get_option('whatauth_settings', array());
        $current_plan  = !empty($settings['plan']) ? strtoupper($settings['plan']) : 'STARTER';

        // Auto-sincronización inteligente: si hay token configurado pero el plan local figura como STARTER, verificar con WhatAuth Cloud
        if ($current_plan === 'STARTER' && !empty($settings['api_key'])) {
            $transient_key = 'whatauth_plan_sync_' . md5($settings['api_key']);
            if (!get_transient($transient_key)) {
                $client = WhatAuth_API_Client::get_instance();
                $verify = $client->verify_token($settings['api_key']);
                if (!empty($verify['success']) && !empty($verify['data']['valid']) && !empty($verify['data']['plan'])) {
                    $new_plan = strtoupper($verify['data']['plan']);
                    if ($new_plan !== 'STARTER') {
                        $current_plan = $new_plan;
                        $settings['plan'] = $current_plan;
                        if (!empty($verify['data']['lines'])) {
                            $settings['lines'] = $verify['data']['lines'];
                        }
                        if (!empty($verify['data']['lines']) && empty($settings['line_id'])) {
                            $settings['line_id'] = $verify['data']['lines'][0]['id'];
                        }
                        update_option('whatauth_settings', $settings);
                    }
                }
                set_transient($transient_key, 1, 120);
            }
        }

        $is_pro_agency = in_array($current_plan, array('PRO', 'AGENCY'), true);

        wp_localize_script('whatauth-admin-js', 'whatauthAdminData', array(
            'ajaxUrl'     => admin_url('admin-ajax.php'),
            'nonce'       => wp_create_nonce('whatauth_admin_nonce'),
            'apiKey'        => !empty($settings['api_key']) ? $settings['api_key'] : '',
            'apiUrl'        => !empty($settings['api_url']) ? $settings['api_url'] : WHATAUTH_DEFAULT_API_URL,
            'plan'          => $current_plan,
            'isProAgency'   => $is_pro_agency,
            'lines'         => !empty($settings['lines']) && is_array($settings['lines']) ? $settings['lines'] : array(),
            'defaultLineId' => !empty($settings['line_id']) ? $settings['line_id'] : '',
            'defaultLogo'   => WHATAUTH_PLUGIN_URL . 'public/images/whatauth-logo.png',
            'i18n'    => array(
                'confirmDelete'       => __('¿Estás seguro de que deseas eliminar este código QR?', 'whatauth-for-wordpress'),
                'deleting'            => __('Eliminando...', 'whatauth-for-wordpress'),
                'saving'              => __('Guardando...', 'whatauth-for-wordpress'),
                'saved'               => __('¡Guardado con éxito!', 'whatauth-for-wordpress'),
                'verifying'           => __('Verificando conexión...', 'whatauth-for-wordpress'),
                'verifiedSuccess'     => __('¡Credencial verificada correctamente!', 'whatauth-for-wordpress'),
                'verifiedFailed'      => __('Fallo de verificación.', 'whatauth-for-wordpress'),
                'errorGeneric'        => __('Ha ocurrido un error inesperado.', 'whatauth-for-wordpress'),
                'noQrsFound'          => __('No se encontraron códigos QR en esta categoría.', 'whatauth-for-wordpress'),
                'loadingQrs'          => __('Cargando códigos QR desde WhatAuth Cloud...', 'whatauth-for-wordpress'),
                'loadingLogs'         => __('Cargando registros de actividad...', 'whatauth-for-wordpress'),
                'noLogsFound'         => __('No hay escaneos registrados aún. Cuando un usuario escanee un QR de WhatAuth, la actividad se mostrará aquí en tiempo real.', 'whatauth-for-wordpress'),
                'verifiedBadge'       => __('Verificado ✓', 'whatauth-for-wordpress'),
                'edit'                => __('Editar', 'whatauth-for-wordpress'),
                'delete'              => __('Eliminar', 'whatauth-for-wordpress'),
                'saveQr'              => __('Guardar Código QR', 'whatauth-for-wordpress'),
                'createQr'            => __('Crear Nuevo Código QR', 'whatauth-for-wordpress'),
                'editQr'              => __('Editar Código QR: ', 'whatauth-for-wordpress'),
                'noActivity'          => __('Sin actividad registrada en los últimos 30 días', 'whatauth-for-wordpress'),
                'scansTotal'          => __('Scans Totales', 'whatauth-for-wordpress'),
                'loginScans'          => __('Login', 'whatauth-for-wordpress'),
                'configured'          => __('✓ Configurado', 'whatauth-for-wordpress'),
                'active'              => __('✓ Activo', 'whatauth-for-wordpress'),
                'untitled'            => __('Sin título', 'whatauth-for-wordpress'),
                'deleteLockedTooltip' => __('Eliminar QRs requiere plan PRO o Agency', 'whatauth-for-wordpress'),
                'titleRequired'       => __('El título del código QR es obligatorio.', 'whatauth-for-wordpress'),
                'qrDeleted'           => __('Código QR eliminado correctamente.', 'whatauth-for-wordpress'),
                'enterToken'          => __('Introduce tu Service Token de WhatAuth.', 'whatauth-for-wordpress'),
                'configTokenToSync'   => __('Configura tu Service Token en Ajustes para sincronizar la telemetría en vivo.', 'whatauth-for-wordpress'),
                'saveChanges'         => __('Guardar Cambios', 'whatauth-for-wordpress'),
                'errorSaving'         => __('Error guardando ajustes', 'whatauth-for-wordpress'),
                'errorDeleting'       => __('Error al eliminar', 'whatauth-for-wordpress'),
                'apiConnectionError'  => __('Error de conexión con la API', 'whatauth-for-wordpress'),
                'loadingEngine'       => __('Cargando motor de QR...', 'whatauth-for-wordpress'),
                'previewDefaultText'  => __('Escribe tu mensaje en la caja superior para previsualizarlo como en WhatsApp...', 'whatauth-for-wordpress'),
                'connectedPlan'       => __('✓ Conectado: Plan', 'whatauth-for-wordpress'),
                'officialHostBot'     => __('Bot Anfitrión Oficial (+34 631 490 990 - Starter)', 'whatauth-for-wordpress'),
                'linePrefix'          => __('Línea: ', 'whatauth-for-wordpress'),
                'errorLoadingData'    => __('Error cargando datos', 'whatauth-for-wordpress'),
                'mediaSelectLogo'     => __('Seleccionar Logotipo para el Código QR', 'whatauth-for-wordpress'),
                'mediaUseLogo'        => __('Usar este Logo', 'whatauth-for-wordpress'),
                'characters'          => __('caracteres', 'whatauth-for-wordpress'),
                'openLink'            => __('Abrir enlace', 'whatauth-for-wordpress'),
                'previewHostBotNotice'=> __('El Bot Anfitrión Oficial no emite Auto-Reply. Selecciona una línea propia para configurar respuestas automáticas.', 'whatauth-for-wordpress'),
                'previewInactive'     => __('Respuesta automática inactiva. Marca la casilla "Activar Respuesta Automática" superior para habilitarla.', 'whatauth-for-wordpress'),
            )
        ));
    }

    /**
     * Renderiza banner si el asistente no ha sido completado
     */
    public static function render_setup_notice() {
        if (!WhatAuth_Roles::can_manage_settings()) {
            return;
        }

        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'whatauth') !== false) {
            return;
        }

        $settings = get_option('whatauth_settings', array());
        if (empty($settings['api_key']) || empty($settings['setup_completed'])) {
            ?>
            <div class="notice notice-info is-dismissible" style="border-left-color: #25D366;">
                <p>
                    <strong>WhatAuth:</strong> <?php esc_html_e('Conecta tu WhatsApp oficial o tu línea dedicada para activar el login Passwordless y los códigos QR.', 'whatauth-for-wordpress'); ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=whatauth-wizard')); ?>" class="button button-primary" style="margin-left: 10px; background: #25D366; border-color: #20ba5a;">
                        <?php esc_html_e('Configurar en 1 minuto', 'whatauth-for-wordpress'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }

    public static function add_plugin_action_links($links) {
        $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=whatauth-settings')) . '">' . __('Ajustes', 'whatauth-for-wordpress') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public static function render_dashboard_page() {
        require_once WHATAUTH_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    public static function render_qr_manager_page() {
        require_once WHATAUTH_PLUGIN_DIR . 'admin/views/qr-manager.php';
    }

    public static function render_settings_page() {
        require_once WHATAUTH_PLUGIN_DIR . 'admin/views/settings.php';
    }

    public static function render_wizard_page() {
        require_once WHATAUTH_PLUGIN_DIR . 'admin/views/wizard.php';
    }
}

