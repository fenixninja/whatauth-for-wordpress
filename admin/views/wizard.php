<?php
if (!defined('ABSPATH')) {
    exit;
}

$domain = wp_parse_url(home_url(), PHP_URL_HOST);
$available_roles = WhatAuth_Roles::get_available_roles();
?>
<div class="wrap whatauth-admin-wrap" style="max-width: 680px; margin: 40px auto;">
    <div style="text-align: center; margin-bottom: 30px;">
        <img src="<?php echo esc_url(WHATAUTH_PLUGIN_URL . 'public/images/whatauth-logo.png'); ?>" alt="WhatAuth" style="width: 58px; height: 58px; margin-bottom: 10px; object-fit: contain;">
        <h1 style="font-size: 26px; font-weight: 700; margin: 0 0 6px 0;"><?php esc_html_e('Bienvenido a WhatAuth', 'whatauth-for-wordpress'); ?></h1>
        <p style="color: var(--wa-text-muted); font-size: 15px; margin: 0;">
            <?php esc_html_e('Usuarios únicos, trazabilidad y autenticación passwordless WhatsApp con códigos QR.', 'whatauth-for-wordpress'); ?>
        </p>
    </div>

    <div class="whatauth-card">
        <form id="whatauth-wizard-form">
            <h2 style="font-size: 16px; margin-top: 0; margin-bottom: 16px; border-bottom: 1px solid var(--wa-border); padding-bottom: 10px;">
                <?php esc_html_e('Paso 1: Conecta tu Service Token de WhatAuth', 'whatauth-for-wordpress'); ?>
            </h2>

            <p style="font-size: 13px; color: var(--wa-text-muted);">
                <?php esc_html_e('Introduce tu Service Token (prefijo wst_live_...). Lo encuentras en tu panel de WhatAuth en Ajustes > API Keys & Tokens.', 'whatauth-for-wordpress'); ?>
            </p>

            <div style="margin-bottom: 16px;">
                <label for="wizard-api-key" style="display: block; font-weight: 600; margin-bottom: 6px;"><?php esc_html_e('Service Token', 'whatauth-for-wordpress'); ?></label>
                <input type="text" id="wizard-api-key" style="width: 100%; font-size: 14px; padding: 8px 12px;" placeholder="wst_live_..." required>
            </div>

            <div style="margin-bottom: 20px;">
                <label for="wizard-domain" style="display: block; font-weight: 600; margin-bottom: 6px;"><?php esc_html_e('Dominio Autorizado de este WordPress', 'whatauth-for-wordpress'); ?></label>
                <input type="text" id="wizard-domain" value="<?php echo esc_attr($domain); ?>" style="width: 100%;" required>
            </div>

            <div style="margin-bottom: 20px;">
                <label for="wizard-admin-phone" style="display: block; font-weight: 600; margin-bottom: 6px;"><?php esc_html_e('Número de WhatsApp del Administrador', 'whatauth-for-wordpress'); ?></label>
                <input type="text" id="wizard-admin-phone" style="width: 100%;" placeholder="Ej: 34627118121">
                <p style="font-size: 12px; color: var(--wa-text-muted); margin: 4px 0 0 0;">
                    <?php esc_html_e('Se autocompleta con tu número al verificar el Token. Este número tendrá rol Administrador siempre.', 'whatauth-for-wordpress'); ?>
                </p>
            </div>

            <div style="margin-bottom: 20px;">
                <label for="wizard-default-role" style="display: block; font-weight: 600; margin-bottom: 6px;"><?php esc_html_e('Rol para Nuevos Registros por WhatsApp', 'whatauth-for-wordpress'); ?></label>
                <select id="wizard-default-role" style="width: 100%;">
                    <?php foreach ($available_roles as $role_key => $role_name) : ?>
                        <option value="<?php echo esc_attr($role_key); ?>" <?php selected('subscriber', $role_key); ?>>
                            <?php echo esc_html($role_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="wizard-status-box" style="margin-bottom: 16px; display: none;"></div>

            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--wa-border); padding-top: 16px;">
                <a href="<?php echo esc_url(admin_url('admin.php?page=whatauth')); ?>" class="button">
                    <?php esc_html_e('Omitir por ahora', 'whatauth-for-wordpress'); ?>
                </a>
                <button type="submit" class="button button-whatauth button-hero" id="btn-submit-wizard">
                    <?php esc_html_e('Verificar y Activar WhatAuth', 'whatauth-for-wordpress'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#whatauth-wizard-form').on('submit', function(e) {
        e.preventDefault();
        const apiKey = $('#wizard-api-key').val().trim();
        const domain = $('#wizard-domain').val().trim();
        const adminPhone = $('#wizard-admin-phone').val().trim();
        const role = $('#wizard-default-role').val();
        const $status = $('#wizard-status-box');
        const $btn = $('#btn-submit-wizard');

        $status.show().html('<div style="padding:10px; background:#f0f9ff; color:#0369a1; border-radius:6px;">Verificando credencial con WhatAuth Cloud...</div>');
        $btn.prop('disabled', true);

        // 1. Probar token
        $.ajax({
            url: whatauthAdminData.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'whatauth_verify_token',
                nonce: whatauthAdminData.nonce,
                api_key: apiKey
            },
            success: function(res) {
                if (res.success) {
                    const verifiedPhone = res.data?.data?.profile?.phone ? res.data.data.profile.phone.replace(/[^0-9]/g, '') : '';
                    if (verifiedPhone && !$('#wizard-admin-phone').val()) {
                        $('#wizard-admin-phone').val(verifiedPhone);
                    }
                    const finalAdminPhone = $('#wizard-admin-phone').val() || verifiedPhone;
                    const detectedPlan = res.data?.data?.plan || 'STARTER';

                    // 2. Guardar ajustes
                    $.ajax({
                        url: whatauthAdminData.ajaxUrl,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'whatauth_save_settings',
                            nonce: whatauthAdminData.nonce,
                            api_key: apiKey,
                            domain: domain,
                            admin_phone: finalAdminPhone,
                            default_role: role,
                            plan: detectedPlan,
                            allow_registration: 1,
                            hide_wp_credentials: 0,
                            desktop_show_walink: 1,
                            mobile_show_walink_only: 1,
                            enable_wplogin: 1,
                            enable_woocommerce: 1
                        },
                        success: function() {
                            $status.html('<div style="padding:10px; background:#dcfce7; color:#15803d; border-radius:6px; font-weight:600;">✓ ¡Configuración completada con éxito! Redirigiendo...</div>');
                            setTimeout(function() {
                                window.location.href = '<?php echo esc_url(admin_url('admin.php?page=whatauth-qrs')); ?>';
                            }, 1200);
                        }
                    });
                } else {
                    $btn.prop('disabled', false);
                    $status.html('<div style="padding:10px; background:#fee2e2; color:#b91c1c; border-radius:6px;">✗ ' + (res.data?.message || 'Token no válido. Comprueba el valor.') + '</div>');
                }
            },
            error: function() {
                $btn.prop('disabled', false);
                $status.html('<div style="padding:10px; background:#fee2e2; color:#b91c1c; border-radius:6px;">✗ Error conectando con el servidor WhatAuth.</div>');
            }
        });
    });
});
</script>

