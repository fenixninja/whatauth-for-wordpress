<?php
if (!defined('ABSPATH')) {
    exit;
}

$whatauth_domain          = wp_parse_url(home_url(), PHP_URL_HOST);
$whatauth_available_roles = WhatAuth_Roles::get_available_roles();
?>
<div class="wrap whatauth-admin-wrap" style="max-width: 680px; margin: 40px auto;">
    <div style="text-align: center; margin-bottom: 30px;">
        <img src="<?php echo esc_url(WHATAUTH_PLUGIN_URL . 'public/images/whatauth-logo.png'); ?>" alt="WhatAuth" style="width: 58px; height: 58px; margin-bottom: 10px; object-fit: contain;">
        <h1 style="font-size: 26px; font-weight: 700; margin: 0 0 6px 0;"><?php esc_html_e('Bienvenido a WhatAuth', 'whatauth'); ?></h1>
        <p style="color: var(--wa-text-muted); font-size: 15px; margin: 0;">
            <?php esc_html_e('Usuarios únicos, trazabilidad y autenticación passwordless WhatsApp con códigos QR.', 'whatauth'); ?>
        </p>
    </div>

    <div class="whatauth-card">
        <form id="whatauth-wizard-form">
            <h2 style="font-size: 16px; margin-top: 0; margin-bottom: 16px; border-bottom: 1px solid var(--wa-border); padding-bottom: 10px;">
                <?php esc_html_e('Paso 1: Conecta tu Service Token de WhatAuth', 'whatauth'); ?>
            </h2>

            <p style="font-size: 13px; color: var(--wa-text-muted);">
                <?php esc_html_e('Introduce tu Service Token (prefijo wst_live_...). Lo encuentras en tu panel de WhatAuth en Ajustes > API Keys & Tokens.', 'whatauth'); ?>
            </p>

            <div style="margin-bottom: 16px;">
                <label for="wizard-api-key" style="display: block; font-weight: 600; margin-bottom: 6px;"><?php esc_html_e('Service Token', 'whatauth'); ?></label>
                <input type="text" id="wizard-api-key" style="width: 100%; font-size: 14px; padding: 8px 12px;" placeholder="wst_live_..." required>
            </div>

            <div style="margin-bottom: 20px;">
                <label for="wizard-domain" style="display: block; font-weight: 600; margin-bottom: 6px;"><?php esc_html_e('Dominio Autorizado de este WordPress', 'whatauth'); ?></label>
                <input type="text" id="wizard-domain" value="<?php echo esc_attr($whatauth_domain); ?>" style="width: 100%;" required>
            </div>

            <div style="margin-bottom: 20px;">
                <label for="wizard-admin-phone" style="display: block; font-weight: 600; margin-bottom: 6px;"><?php esc_html_e('Número de WhatsApp del Administrador', 'whatauth'); ?></label>
                <input type="text" id="wizard-admin-phone" style="width: 100%;" placeholder="Ej: 34627118121">
                <p style="font-size: 12px; color: var(--wa-text-muted); margin: 4px 0 0 0;">
                    <?php esc_html_e('Se autocompleta con tu número al verificar el Token. Este número tendrá rol Administrador siempre.', 'whatauth'); ?>
                </p>
            </div>

            <div style="margin-bottom: 20px;">
                <label for="wizard-default-role" style="display: block; font-weight: 600; margin-bottom: 6px;"><?php esc_html_e('Rol para Nuevos Registros por WhatsApp', 'whatauth'); ?></label>
                <select id="wizard-default-role" style="width: 100%;">
                    <?php foreach ($whatauth_available_roles as $whatauth_role_key => $whatauth_role_name) : ?>
                        <option value="<?php echo esc_attr($whatauth_role_key); ?>" <?php selected('subscriber', $whatauth_role_key); ?>>
                            <?php echo esc_html($whatauth_role_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="wizard-status-box" style="margin-bottom: 16px; display: none;"></div>

            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--wa-border); padding-top: 16px;">
                <a href="<?php echo esc_url(admin_url('admin.php?page=whatauth')); ?>" class="button">
                    <?php esc_html_e('Omitir por ahora', 'whatauth'); ?>
                </a>
                <button type="submit" class="button button-whatauth button-hero" id="btn-submit-wizard">
                    <?php esc_html_e('Verificar y Activar WhatAuth', 'whatauth'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

