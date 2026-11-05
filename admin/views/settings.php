<?php
if (!defined('ABSPATH')) {
    exit;
}

$whatauth_settings = get_option('whatauth_settings', array());
$whatauth_api_key  = !empty($whatauth_settings['api_key']) ? $whatauth_settings['api_key'] : '';
$whatauth_api_url  = !empty($whatauth_settings['api_url']) ? $whatauth_settings['api_url'] : WHATAUTH_DEFAULT_API_URL;
$whatauth_domain   = !empty($whatauth_settings['domain']) ? $whatauth_settings['domain'] : wp_parse_url(home_url(), PHP_URL_HOST);
$whatauth_line_id  = !empty($whatauth_settings['line_id']) ? $whatauth_settings['line_id'] : '';
$whatauth_admin_phone = !empty($whatauth_settings['admin_phone']) ? $whatauth_settings['admin_phone'] : '';
$whatauth_allow_reg = !empty($whatauth_settings['allow_registration']);
$whatauth_default_role = !empty($whatauth_settings['default_role']) ? $whatauth_settings['default_role'] : 'subscriber';
$whatauth_hide_wp_credentials = !empty($whatauth_settings['hide_wp_credentials']);
$whatauth_desktop_show_walink = isset($whatauth_settings['desktop_show_walink']) ? !empty($whatauth_settings['desktop_show_walink']) : true;
$whatauth_mobile_show_walink_only = isset($whatauth_settings['mobile_show_walink_only']) ? !empty($whatauth_settings['mobile_show_walink_only']) : true;
$whatauth_enable_wplogin = !empty($whatauth_settings['enable_wplogin']);
$whatauth_enable_woo = !empty($whatauth_settings['enable_woocommerce']);
$whatauth_qr_size = !empty($whatauth_settings['qr_size']) ? intval($whatauth_settings['qr_size']) : 280;
$whatauth_available_roles = WhatAuth_Roles::get_available_roles();
?>
<div class="wrap whatauth-admin-wrap">
    <div class="whatauth-header">
        <div class="whatauth-brand">
            <img src="<?php echo esc_url(WHATAUTH_PLUGIN_URL . 'public/images/whatauth-logo.png'); ?>" alt="WhatAuth" style="width: 42px; height: 42px; object-fit: contain;">
            <div>
                <h1><?php esc_html_e('Ajustes y Roles de WhatAuth', 'whatauth'); ?></h1>
                <p style="margin: 2px 0 0 0; color: var(--wa-text-muted); font-size: 13px;">
                    <?php esc_html_e('Configura tu conexión con WhatAuth Cloud, tu línea de WhatsApp y las reglas de acceso de WordPress.', 'whatauth'); ?>
                </p>
            </div>
        </div>
    </div>

    <form id="whatauth-settings-form">
        <!-- Bloque 1: Conexión con WhatAuth -->
        <div class="whatauth-card" style="margin-bottom: 20px;">
            <h2 style="margin-top: 0; font-size: 16px; border-bottom: 1px solid var(--wa-border); padding-bottom: 10px;">
                <?php esc_html_e('1. Conexión y Credenciales', 'whatauth'); ?>
            </h2>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="whatauth-api-key"><?php esc_html_e('Service Token (API Key)', 'whatauth'); ?></label></th>
                    <td>
                        <input type="password" id="whatauth-api-key" name="api_key" value="<?php echo esc_attr($whatauth_api_key); ?>" class="regular-text" placeholder="wst_live_..." autocomplete="off">
                        <button type="button" class="button" id="btn-test-token"><?php esc_html_e('Probar Conexión', 'whatauth'); ?></button>
                        <span id="whatauth-test-status" style="margin-left: 10px;"></span>
                        <p class="description">
                            <?php esc_html_e('Puedes obtener tu Service Token en el panel de WhatAuth en Ajustes > API Keys & Tokens.', 'whatauth'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="whatauth-api-url"><?php esc_html_e('URL de la API', 'whatauth'); ?></label></th>
                    <td>
                        <input type="url" id="whatauth-api-url" name="api_url" value="<?php echo esc_attr($whatauth_api_url); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e('Por defecto: https://api.whatauth.com (o http://localhost:3000 para desarrollo local).', 'whatauth'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="whatauth-domain"><?php esc_html_e('Dominio del Sitio Web', 'whatauth'); ?></label></th>
                    <td>
                        <input type="text" id="whatauth-domain" name="domain" value="<?php echo esc_attr($whatauth_domain); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e('Debe coincidir con la lista de dominios autorizados de tu cuenta WhatAuth.', 'whatauth'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="whatauth-line-select"><?php esc_html_e('Línea de WhatsApp Activa', 'whatauth'); ?></label></th>
                    <td>
                        <select id="whatauth-line-select" name="line_id" style="min-width: 350px;">
                            <option value=""><?php esc_html_e('Bot Anfitrión Oficial (+34 631 490 990 - Starter)', 'whatauth'); ?></option>
                            <?php if (!empty($whatauth_line_id)) : ?>
                                <option value="<?php echo esc_attr($whatauth_line_id); ?>" selected><?php esc_html_e('Línea Dedicada Configurada', 'whatauth'); ?> (<?php echo esc_html($whatauth_line_id); ?>)</option>
                            <?php endif; ?>
                        </select>
                        <p class="description"><?php esc_html_e('Elige si el login y los QRs se resuelven por el Bot Oficial o por tu línea dedicada (PRO / Agency).', 'whatauth'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Bloque 2: Comportamiento de Login y Registro -->
        <div class="whatauth-card" style="margin-bottom: 20px;">
            <h2 style="margin-top: 0; font-size: 16px; border-bottom: 1px solid var(--wa-border); padding-bottom: 10px;">
                <?php esc_html_e('2. Autenticación y Registro de Usuarios', 'whatauth'); ?>
            </h2>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Auto-Registro', 'whatauth'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="allow_registration" value="1" <?php checked($whatauth_allow_reg); ?>>
                            <?php esc_html_e('Crear automáticamente cuenta en WordPress si el número de WhatsApp no existe aún.', 'whatauth'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="whatauth-admin-phone"><?php esc_html_e('Número de WhatsApp del Administrador', 'whatauth'); ?></label></th>
                    <td>
                        <input type="text" id="whatauth-admin-phone" name="admin_phone" value="<?php echo esc_attr($whatauth_admin_phone); ?>" class="regular-text" placeholder="34627118121">
                        <p class="description"><?php esc_html_e('El usuario que inicie sesión con este número de WhatsApp tendrá siempre permisos de Administrador.', 'whatauth'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="whatauth-default-role"><?php esc_html_e('Rol por Defecto para Nuevos Registros', 'whatauth'); ?></label></th>
                    <td>
                        <select id="whatauth-default-role" name="default_role">
                            <?php foreach ($whatauth_available_roles as $whatauth_role_key => $whatauth_role_name) : ?>
                                <option value="<?php echo esc_attr($whatauth_role_key); ?>" <?php selected($whatauth_default_role, $whatauth_role_key); ?>>
                                    <?php echo esc_html($whatauth_role_name); ?> (<?php echo esc_html($whatauth_role_key); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php esc_html_e('Rol asignado a los visitantes que inicien sesión por primera vez con su WhatsApp (excepto el número de Administrador).', 'whatauth'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Visibilidad del Formulario Nativo', 'whatauth'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="hide_wp_credentials" value="1" <?php checked($whatauth_hide_wp_credentials); ?>>
                            <?php esc_html_e('Ocultar campos de usuario y contraseña de WordPress (dejar exclusivamente acceso con WhatAuth).', 'whatauth'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Comportamiento en Escritorio (Desktop)', 'whatauth'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="desktop_show_walink" value="1" <?php checked($whatauth_desktop_show_walink); ?>>
                            <?php esc_html_e('Mostrar botón de enlace directo a WhatsApp (WaLink) además del código QR.', 'whatauth'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Comportamiento en Móviles', 'whatauth'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="mobile_show_walink_only" value="1" <?php checked($whatauth_mobile_show_walink_only); ?>>
                            <?php esc_html_e('En dispositivos móviles, mostrar botón de WaLink directo en lugar del código QR.', 'whatauth'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Ubicación en el Sitio', 'whatauth'); ?></th>
                    <td>
                        <label style="display: block; margin-bottom: 6px;">
                            <input type="checkbox" name="enable_wplogin" value="1" <?php checked($whatauth_enable_wplogin); ?>>
                            <?php esc_html_e('Activar WhatAuth directamente en la pantalla oficial wp-login.php', 'whatauth'); ?>
                        </label>
                        <label style="display: block;">
                            <input type="checkbox" name="enable_woocommerce" value="1" <?php checked($whatauth_enable_woo); ?>>
                            <?php esc_html_e('Activar WhatAuth en el formulario de acceso de WooCommerce (Mi Cuenta / Checkout)', 'whatauth'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="whatauth-qr-size"><?php esc_html_e('Tamaño del Código QR (px)', 'whatauth'); ?></label></th>
                    <td>
                        <input type="number" id="whatauth-qr-size" name="qr_size" value="<?php echo esc_attr($whatauth_qr_size); ?>" min="180" max="600" step="10"> px
                    </td>
                </tr>
            </table>
        </div>

        <!-- Bloque 3: Roles con acceso a la gestión de QRs -->
        <div class="whatauth-card" style="margin-bottom: 24px;">
            <h2 style="margin-top: 0; font-size: 16px; border-bottom: 1px solid var(--wa-border); padding-bottom: 10px;">
                <?php esc_html_e('3. Permisos de Gestión de QRs en WP-Admin', 'whatauth'); ?>
            </h2>
            <p><?php esc_html_e('Selecciona qué roles de WordPress tienen permiso para ver y crear códigos QR de WhatAuth (el rol Administrador siempre tiene acceso total):', 'whatauth'); ?></p>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin-top: 10px;">
                <?php foreach ($whatauth_available_roles as $whatauth_role_key => $whatauth_role_name) :
                    if ($whatauth_role_key === 'administrator') continue;
                    $whatauth_role_obj = get_role($whatauth_role_key);
                    $whatauth_has_cap = $whatauth_role_obj && $whatauth_role_obj->has_cap(WhatAuth_Roles::CAP_MANAGE_QRS);
                ?>
                    <label style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="qr_allowed_roles[]" value="<?php echo esc_attr($whatauth_role_key); ?>" <?php checked($whatauth_has_cap); ?>>
                        <span><?php echo esc_html($whatauth_role_name); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div>
            <button type="submit" class="button button-whatauth button-hero">
                <?php esc_html_e('Guardar Cambios', 'whatauth'); ?>
            </button>
        </div>
    </form>
</div>

