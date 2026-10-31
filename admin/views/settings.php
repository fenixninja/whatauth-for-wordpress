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
$whatauth_btn_text = !empty($whatauth_settings['button_text']) ? $whatauth_settings['button_text'] : __('Abrir WhatsApp directamente', 'whatauth-for-wordpress');
$whatauth_login_instruction_text = !empty($whatauth_settings['login_instruction_text']) ? $whatauth_settings['login_instruction_text'] : __('Escanea con tu WhatsApp para acceder al instante sin contraseñas.', 'whatauth-for-wordpress');
$whatauth_plan = !empty($whatauth_settings['plan']) ? strtoupper($whatauth_settings['plan']) : 'STARTER';
$whatauth_is_pro_agency = in_array($whatauth_plan, array('PRO', 'AGENCY'), true);
$whatauth_button_bg_color = !empty($whatauth_settings['button_bg_color']) ? $whatauth_settings['button_bg_color'] : '#25D366';
$whatauth_button_text_color = !empty($whatauth_settings['button_text_color']) ? $whatauth_settings['button_text_color'] : '#ffffff';
$whatauth_custom_logo_url = !empty($whatauth_settings['custom_logo_url']) ? $whatauth_settings['custom_logo_url'] : '';

$whatauth_available_roles = WhatAuth_Roles::get_available_roles();
?>
<div class="wrap whatauth-admin-wrap">
    <div class="whatauth-header">
        <div class="whatauth-brand">
            <img src="<?php echo esc_url(WHATAUTH_PLUGIN_URL . 'public/images/whatauth-logo.png'); ?>" alt="WhatAuth" style="width: 42px; height: 42px; object-fit: contain;">
            <div>
                <h1><?php esc_html_e('Ajustes y Roles de WhatAuth', 'whatauth-for-wordpress'); ?></h1>
                <p style="margin: 2px 0 0 0; color: var(--wa-text-muted); font-size: 13px;">
                    <?php esc_html_e('Configura tu conexión con WhatAuth Cloud, tu línea de WhatsApp y las reglas de acceso de WordPress.', 'whatauth-for-wordpress'); ?>
                </p>
            </div>
        </div>
    </div>

    <form id="whatauth-settings-form">
        <input type="hidden" id="whatauth-plan-hidden" name="plan" value="<?php echo esc_attr($whatauth_plan); ?>">
        <!-- Bloque 1: Conexión con WhatAuth -->
        <div class="whatauth-card" style="margin-bottom: 20px;">
            <h2 style="margin-top: 0; font-size: 16px; border-bottom: 1px solid var(--wa-border); padding-bottom: 10px;">
                <?php esc_html_e('1. Conexión y Credenciales', 'whatauth-for-wordpress'); ?>
            </h2>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="whatauth-api-key"><?php esc_html_e('Service Token (API Key)', 'whatauth-for-wordpress'); ?></label></th>
                    <td>
                        <input type="password" id="whatauth-api-key" name="api_key" value="<?php echo esc_attr($whatauth_api_key); ?>" class="regular-text" placeholder="wst_live_..." autocomplete="off">
                        <button type="button" class="button" id="btn-test-token"><?php esc_html_e('Probar Conexión', 'whatauth-for-wordpress'); ?></button>
                        <span id="whatauth-test-status" style="margin-left: 10px;"></span>
                        <p class="description">
                            <?php esc_html_e('Puedes obtener tu Service Token en el panel de WhatAuth en Ajustes > API Keys & Tokens.', 'whatauth-for-wordpress'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="whatauth-api-url"><?php esc_html_e('URL de la API', 'whatauth-for-wordpress'); ?></label></th>
                    <td>
                        <input type="url" id="whatauth-api-url" name="api_url" value="<?php echo esc_attr($whatauth_api_url); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e('Por defecto: https://api.whatauth.com (o http://localhost:3000 para desarrollo local).', 'whatauth-for-wordpress'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="whatauth-domain"><?php esc_html_e('Dominio del Sitio Web', 'whatauth-for-wordpress'); ?></label></th>
                    <td>
                        <input type="text" id="whatauth-domain" name="domain" value="<?php echo esc_attr($whatauth_domain); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e('Debe coincidir con la lista de dominios autorizados de tu cuenta WhatAuth.', 'whatauth-for-wordpress'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="whatauth-line-select"><?php esc_html_e('Línea de WhatsApp Activa', 'whatauth-for-wordpress'); ?></label></th>
                    <td>
                        <select id="whatauth-line-select" name="line_id" style="min-width: 350px;">
                            <option value=""><?php esc_html_e('Bot Anfitrión Oficial (+34 631 490 990 - Starter)', 'whatauth-for-wordpress'); ?></option>
                            <?php if (!empty($whatauth_line_id)) : ?>
                                <option value="<?php echo esc_attr($whatauth_line_id); ?>" selected><?php esc_html_e('Línea Dedicada Configurada', 'whatauth-for-wordpress'); ?> (<?php echo esc_html($whatauth_line_id); ?>)</option>
                            <?php endif; ?>
                        </select>
                        <p class="description"><?php esc_html_e('Elige si el login y los QRs se resuelven por el Bot Oficial o por tu línea dedicada (PRO / Agency).', 'whatauth-for-wordpress'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Bloque 2: Comportamiento de Login y Registro -->
        <div class="whatauth-card" style="margin-bottom: 20px;">
            <h2 style="margin-top: 0; font-size: 16px; border-bottom: 1px solid var(--wa-border); padding-bottom: 10px;">
                <?php esc_html_e('2. Autenticación y Registro de Usuarios', 'whatauth-for-wordpress'); ?>
            </h2>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Auto-Registro', 'whatauth-for-wordpress'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="allow_registration" value="1" <?php checked($whatauth_allow_reg); ?>>
                            <?php esc_html_e('Crear automáticamente cuenta en WordPress si el número de WhatsApp no existe aún.', 'whatauth-for-wordpress'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="whatauth-admin-phone"><?php esc_html_e('Número de WhatsApp del Administrador', 'whatauth-for-wordpress'); ?></label></th>
                    <td>
                        <input type="text" id="whatauth-admin-phone" name="admin_phone" value="<?php echo esc_attr($whatauth_admin_phone); ?>" class="regular-text" placeholder="34627118121">
                        <p class="description"><?php esc_html_e('El usuario que inicie sesión con este número de WhatsApp tendrá siempre permisos de Administrador.', 'whatauth-for-wordpress'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="whatauth-default-role"><?php esc_html_e('Rol por Defecto para Nuevos Registros', 'whatauth-for-wordpress'); ?></label></th>
                    <td>
                        <select id="whatauth-default-role" name="default_role">
                            <?php foreach ($whatauth_available_roles as $whatauth_role_key => $whatauth_role_name) : ?>
                                <option value="<?php echo esc_attr($whatauth_role_key); ?>" <?php selected($whatauth_default_role, $whatauth_role_key); ?>>
                                    <?php echo esc_html($whatauth_role_name); ?> (<?php echo esc_html($whatauth_role_key); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php esc_html_e('Rol asignado a los visitantes que inicien sesión por primera vez con su WhatsApp (excepto el número de Administrador).', 'whatauth-for-wordpress'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Visibilidad del Formulario Nativo', 'whatauth-for-wordpress'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="hide_wp_credentials" value="1" <?php checked($whatauth_hide_wp_credentials); ?>>
                            <?php esc_html_e('Ocultar campos de usuario y contraseña de WordPress (dejar exclusivamente acceso con WhatAuth).', 'whatauth-for-wordpress'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Comportamiento en Escritorio (Desktop)', 'whatauth-for-wordpress'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="desktop_show_walink" value="1" <?php checked($whatauth_desktop_show_walink); ?>>
                            <?php esc_html_e('Mostrar botón de enlace directo a WhatsApp (WaLink) además del código QR.', 'whatauth-for-wordpress'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Comportamiento en Móviles', 'whatauth-for-wordpress'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="mobile_show_walink_only" value="1" <?php checked($whatauth_mobile_show_walink_only); ?>>
                            <?php esc_html_e('En dispositivos móviles, mostrar botón de WaLink directo en lugar del código QR.', 'whatauth-for-wordpress'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Ubicación en el Sitio', 'whatauth-for-wordpress'); ?></th>
                    <td>
                        <label style="display: block; margin-bottom: 6px;">
                            <input type="checkbox" name="enable_wplogin" value="1" <?php checked($whatauth_enable_wplogin); ?>>
                            <?php esc_html_e('Activar WhatAuth directamente en la pantalla oficial wp-login.php', 'whatauth-for-wordpress'); ?>
                        </label>
                        <label style="display: block;">
                            <input type="checkbox" name="enable_woocommerce" value="1" <?php checked($whatauth_enable_woo); ?>>
                            <?php esc_html_e('Activar WhatAuth en el formulario de acceso de WooCommerce (Mi Cuenta / Checkout)', 'whatauth-for-wordpress'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="whatauth-instruction-text"><?php esc_html_e('Texto de Instrucción del Login', 'whatauth-for-wordpress'); ?></label></th>
                    <td>
                        <input type="text" id="whatauth-instruction-text" name="login_instruction_text" value="<?php echo esc_attr($whatauth_login_instruction_text); ?>" class="large-text" <?php echo !$whatauth_is_pro_agency ? 'readonly style="background:#f3f4f6; color:#6b7280; cursor:not-allowed;"' : ''; ?>>
                        <p class="description wa-plan-starter-view" <?php echo $whatauth_is_pro_agency ? 'style="display:none;"' : ''; ?>>
                            <span style="background:#fef3c7; color:#92400e; padding:2px 8px; border-radius:4px; font-weight:600; font-size:11px;"><span class="dashicons dashicons-lock" style="font-size:12px;width:12px;height:12px;vertical-align:middle;margin-right:2px;"></span> STARTER (Estándar WhatAuth)</span>
                            <?php esc_html_e('El texto oficial de instrucción está protegido en el plan Starter. Para personalizarlo actualiza a PRO o Agency.', 'whatauth-for-wordpress'); ?>
                        </p>
                        <p class="description wa-plan-pro-view" <?php echo !$whatauth_is_pro_agency ? 'style="display:none;"' : ''; ?>>
                            <span style="background:#dbeafe; color:#1e40af; padding:2px 8px; border-radius:4px; font-weight:600; font-size:11px;"><span class="dashicons dashicons-star-filled" style="font-size:12px;width:12px;height:12px;vertical-align:middle;margin-right:2px;"></span> PRO / AGENCY</span>
                            <?php esc_html_e('Texto explicativo visible sobre el código QR en la pantalla de login.', 'whatauth-for-wordpress'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="whatauth-btn-text"><?php esc_html_e('Texto del Botón WaLink', 'whatauth-for-wordpress'); ?></label></th>
                    <td>
                        <input type="text" id="whatauth-btn-text" name="button_text" value="<?php echo esc_attr($whatauth_btn_text); ?>" class="regular-text" <?php echo !$whatauth_is_pro_agency ? 'readonly style="background:#f3f4f6; color:#6b7280; cursor:not-allowed;"' : ''; ?>>
                        <p class="description wa-plan-starter-view" <?php echo $whatauth_is_pro_agency ? 'style="display:none;"' : ''; ?>>
                            <span style="background:#fef3c7; color:#92400e; padding:2px 8px; border-radius:4px; font-weight:600; font-size:11px;"><span class="dashicons dashicons-lock" style="font-size:12px;width:12px;height:12px;vertical-align:middle;margin-right:2px;"></span> STARTER (Estándar WhatAuth)</span>
                            <?php esc_html_e('El botón utiliza el texto verificado oficial de WhatAuth. Desbloquea textos personalizados en PRO o Agency.', 'whatauth-for-wordpress'); ?>
                        </p>
                        <p class="description wa-plan-pro-view" <?php echo !$whatauth_is_pro_agency ? 'style="display:none;"' : ''; ?>>
                            <span style="background:#dbeafe; color:#1e40af; padding:2px 8px; border-radius:4px; font-weight:600; font-size:11px;"><span class="dashicons dashicons-star-filled" style="font-size:12px;width:12px;height:12px;vertical-align:middle;margin-right:2px;"></span> PRO / AGENCY</span>
                            <?php esc_html_e('Texto del botón para abrir WhatsApp directamente.', 'whatauth-for-wordpress'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Personalización de Colores del Botón', 'whatauth-for-wordpress'); ?></th>
                    <td>
                        <div class="wa-plan-starter-view" <?php echo $whatauth_is_pro_agency ? 'style="display:none;"' : ''; ?>>
                            <div style="display:flex; align-items:center; gap:12px; margin-bottom:6px;">
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span style="display:inline-block; width:22px; height:22px; background:#25D366; border-radius:4px; border:1px solid #dcdcde;"></span>
                                    <span style="font-size:12px; color:#4b5563;"><?php esc_html_e('Fondo: #25D366 (Oficial)', 'whatauth-for-wordpress'); ?></span>
                                </div>
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span style="display:inline-block; width:22px; height:22px; background:#ffffff; border-radius:4px; border:1px solid #dcdcde;"></span>
                                    <span style="font-size:12px; color:#4b5563;"><?php esc_html_e('Texto: #FFFFFF', 'whatauth-for-wordpress'); ?></span>
                                </div>
                            </div>
                            <p class="description">
                                <span style="background:#fef3c7; color:#92400e; padding:2px 8px; border-radius:4px; font-weight:600; font-size:11px;"><span class="dashicons dashicons-lock" style="font-size:12px;width:12px;height:12px;vertical-align:middle;margin-right:2px;"></span> <?php esc_html_e('STARTER (Estándar WhatAuth)', 'whatauth-for-wordpress'); ?></span>
                                <?php esc_html_e('En la versión Starter el botón utiliza exclusivamente los colores y contrastes verificados de WhatAuth. Desbloquea colores de marca en PRO o Agency.', 'whatauth-for-wordpress'); ?>
                            </p>
                        </div>
                        <div class="wa-plan-pro-view" <?php echo !$whatauth_is_pro_agency ? 'style="display:none;"' : ''; ?>>
                            <div style="display:flex; align-items:center; gap:18px; margin-bottom:8px;">
                                <div>
                                    <label for="whatauth-btn-bg-color" style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;"><?php esc_html_e('Color de Fondo', 'whatauth-for-wordpress'); ?></label>
                                    <input type="color" id="whatauth-btn-bg-color" name="button_bg_color" value="<?php echo esc_attr($whatauth_button_bg_color); ?>" style="height:36px; width:60px; padding:2px; cursor:pointer;">
                                </div>
                                <div>
                                    <label for="whatauth-btn-txt-color" style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;"><?php esc_html_e('Color de Texto', 'whatauth-for-wordpress'); ?></label>
                                    <input type="color" id="whatauth-btn-txt-color" name="button_text_color" value="<?php echo esc_attr($whatauth_button_text_color); ?>" style="height:36px; width:60px; padding:2px; cursor:pointer;">
                                </div>
                            </div>
                            <p class="description">
                                <span style="background:#dbeafe; color:#1e40af; padding:2px 8px; border-radius:4px; font-weight:600; font-size:11px;"><span class="dashicons dashicons-star-filled" style="font-size:12px;width:12px;height:12px;vertical-align:middle;margin-right:2px;"></span> <?php esc_html_e('PRO / AGENCY', 'whatauth-for-wordpress'); ?></span>
                                <?php esc_html_e('Personaliza la paleta del botón de acceso para adaptarlo a la identidad visual de tu marca o tienda.', 'whatauth-for-wordpress'); ?>
                            </p>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="whatauth-custom-logo"><?php esc_html_e('Logo del Login / Tarjeta', 'whatauth-for-wordpress'); ?></label></th>
                    <td>
                        <div class="wa-plan-starter-view" <?php echo $whatauth_is_pro_agency ? 'style="display:none;"' : ''; ?>>
                            <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
                                <img src="<?php echo esc_url(WHATAUTH_PLUGIN_URL . 'public/images/whatauth-logo.png'); ?>" alt="WhatAuth" style="width:34px; height:34px; object-fit:contain;">
                                <span style="font-size:12px; color:#4b5563; font-weight:600;"><?php esc_html_e('Logo Oficial WhatAuth (Caja Fuerte Verde 3D)', 'whatauth-for-wordpress'); ?></span>
                            </div>
                            <p class="description">
                                <span style="background:#fef3c7; color:#92400e; padding:2px 8px; border-radius:4px; font-weight:600; font-size:11px;"><span class="dashicons dashicons-lock" style="font-size:12px;width:12px;height:12px;vertical-align:middle;margin-right:2px;"></span> <?php esc_html_e('STARTER (Estándar WhatAuth)', 'whatauth-for-wordpress'); ?></span>
                                <?php esc_html_e('Muestra el logo 3D oficial de WhatAuth. En planes PRO y Agency puedes reemplazarlo por el logotipo de tu propia empresa.', 'whatauth-for-wordpress'); ?>
                            </p>
                        </div>
                        <div class="wa-plan-pro-view" <?php echo !$whatauth_is_pro_agency ? 'style="display:none;"' : ''; ?>>
                            <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
                                <input type="url" id="whatauth-custom-logo" name="custom_logo_url" value="<?php echo esc_attr($whatauth_custom_logo_url); ?>" class="large-text" placeholder="https://tudominio.com/wp-content/uploads/logo.png">
                                <button type="button" class="button" id="btn-upload-custom-logo"><?php esc_html_e('Seleccionar de Medios', 'whatauth-for-wordpress'); ?></button>
                            </div>
                            <p class="description">
                                <span style="background:#dbeafe; color:#1e40af; padding:2px 8px; border-radius:4px; font-weight:600; font-size:11px;">✨ <?php esc_html_e('PRO / AGENCY', 'whatauth-for-wordpress'); ?></span>
                                <?php esc_html_e('URL de tu logotipo personalizado para la tarjeta de login móvil y el botón. Si se deja vacío se utiliza el logo WhatAuth por defecto.', 'whatauth-for-wordpress'); ?>
                            </p>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="whatauth-qr-size"><?php esc_html_e('Tamaño del Código QR (px)', 'whatauth-for-wordpress'); ?></label></th>
                    <td>
                        <input type="number" id="whatauth-qr-size" name="qr_size" value="<?php echo esc_attr($whatauth_qr_size); ?>" min="180" max="600" step="10"> px
                    </td>
                </tr>
            </table>
        </div>

        <!-- Bloque 3: Roles con acceso a la gestión de QRs -->
        <div class="whatauth-card" style="margin-bottom: 24px;">
            <h2 style="margin-top: 0; font-size: 16px; border-bottom: 1px solid var(--wa-border); padding-bottom: 10px;">
                <?php esc_html_e('3. Permisos de Gestión de QRs en WP-Admin', 'whatauth-for-wordpress'); ?>
            </h2>
            <p><?php esc_html_e('Selecciona qué roles de WordPress tienen permiso para ver y crear códigos QR de WhatAuth (el rol Administrador siempre tiene acceso total):', 'whatauth-for-wordpress'); ?></p>

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
                <?php esc_html_e('Guardar Cambios', 'whatauth-for-wordpress'); ?>
            </button>
        </div>
    </form>
</div>

