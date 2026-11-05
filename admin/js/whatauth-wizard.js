/**
 * WhatAuth Setup Wizard Script
 */
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

                    // 2. Guardar ajustes en modo Starter
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
                            plan: 'STARTER',
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
                                if (whatauthAdminData.redirectUrl) {
                                    window.location.href = whatauthAdminData.redirectUrl;
                                } else {
                                    window.location.reload();
                                }
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

