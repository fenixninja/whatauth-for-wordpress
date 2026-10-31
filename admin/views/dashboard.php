<?php
if (!defined('ABSPATH')) {
    exit;
}

$whatauth_settings = get_option('whatauth_settings', array());
$whatauth_api_key  = !empty($whatauth_settings['api_key']) ? $whatauth_settings['api_key'] : '';
$whatauth_domain   = !empty($whatauth_settings['domain']) ? $whatauth_settings['domain'] : wp_parse_url(home_url(), PHP_URL_HOST);
?>
<div class="wrap whatauth-admin-wrap">
    <div class="whatauth-header">
        <div class="whatauth-brand">
            <img src="<?php echo esc_url(WHATAUTH_PLUGIN_URL . 'public/images/whatauth-logo.png'); ?>" alt="WhatAuth" style="width: 42px; height: 42px; object-fit: contain;">
            <div>
                <h1>
                    WhatAuth
                    <?php if (!empty($whatauth_api_key)) : ?>
                        <span class="whatauth-badge whatauth-badge-active"><?php esc_html_e('Conectado', 'whatauth-for-wordpress'); ?></span>
                    <?php else : ?>
                        <span class="whatauth-badge whatauth-badge-inactive"><?php esc_html_e('Sin Configurar', 'whatauth-for-wordpress'); ?></span>
                    <?php endif; ?>
                </h1>
                <p style="margin: 2px 0 0 0; color: var(--wa-text-muted); font-size: 13px;">
                    <?php esc_html_e('Usuarios únicos, fidelización y autenticación passwordless WhatsApp con códigos QR.', 'whatauth-for-wordpress'); ?>
                </p>
            </div>
        </div>
        <div>
            <a href="<?php echo esc_url(admin_url('admin.php?page=whatauth-qrs')); ?>" class="button button-whatauth">
                <svg class="whatauth-btn-icon-svg" viewBox="0 0 24 24">
                    <path d="M4 4h4v4H4V4zm2 2h0v0zm10-2h4v4h-4V4zm2 2h0v0zM4 16h4v4H4v-4zm2 2h0v0zm10-3h2v2h-2v-2zm2 2h2v2h-2v-2zm-2 2h2v2h-2v-2zm4-4h2v2h-2v-2zm0 4h2v2h-2v-2zM2 2v8h8V2H2zm6 6H4V4h4v4zm8-6v8h8V2h-8zm6 6h-4V4h4v4zM2 14v8h8v-8H2zm6 6H4v-4h4v4zm4-12h2v4h-2V8zm0 6h2v2h-2v-2zm0 4h2v2h-2v-2zm2-2h2v2h-2v-2zm2-4h2v4h-2v-4z"/>
                </svg>
                <span><?php esc_html_e('Gestionar Códigos QR', 'whatauth-for-wordpress'); ?></span>
            </a>
        </div>
    </div>

    <!-- Bento Grid de Telemetría con Iconos 3D Oficiales -->
    <div class="whatauth-bento-grid">
        <div class="whatauth-card">
            <div class="whatauth-card-header">
                <span class="whatauth-card-title"><?php esc_html_e('Total QRs Activos', 'whatauth-for-wordpress'); ?></span>
                <div class="whatauth-kpi-icon-wrap">
                    <img src="<?php echo esc_url(WHATAUTH_PLUGIN_URL . 'public/images/kpi-total.png'); ?>" alt="Total" class="whatauth-kpi-icon">
                </div>
            </div>
            <div class="whatauth-card-stat" id="stat-total-qrs">...</div>
            <p class="whatauth-card-desc"><?php esc_html_e('Sincronizados en tiempo real con WhatAuth Cloud.', 'whatauth-for-wordpress'); ?></p>
        </div>

        <div class="whatauth-card">
            <div class="whatauth-card-header">
                <span class="whatauth-card-title"><?php esc_html_e('QRs de Login', 'whatauth-for-wordpress'); ?></span>
                <div class="whatauth-kpi-icon-wrap">
                    <img src="<?php echo esc_url(WHATAUTH_PLUGIN_URL . 'public/images/kpi-login.png'); ?>" alt="Login" class="whatauth-kpi-icon">
                </div>
            </div>
            <div class="whatauth-card-stat" id="stat-login-qrs">...</div>
            <p class="whatauth-card-desc"><?php esc_html_e('Para acceso web rápido Passwordless.', 'whatauth-for-wordpress'); ?></p>
        </div>

        <div class="whatauth-card">
            <div class="whatauth-card-header">
                <span class="whatauth-card-title"><?php esc_html_e('QRs de Pixel (Marketing)', 'whatauth-for-wordpress'); ?></span>
                <div class="whatauth-kpi-icon-wrap">
                    <img src="<?php echo esc_url(WHATAUTH_PLUGIN_URL . 'public/images/kpi-pixel.png'); ?>" alt="Pixel" class="whatauth-kpi-icon">
                </div>
            </div>
            <div class="whatauth-card-stat" id="stat-pixel-qrs">...</div>
            <p class="whatauth-card-desc"><?php esc_html_e('Captura de leads y redirecciones de campaña.', 'whatauth-for-wordpress'); ?></p>
        </div>

        <div class="whatauth-card">
            <div class="whatauth-card-header">
                <span class="whatauth-card-title"><?php esc_html_e('QRs de Print (Cartelería)', 'whatauth-for-wordpress'); ?></span>
                <div class="whatauth-kpi-icon-wrap">
                    <img src="<?php echo esc_url(WHATAUTH_PLUGIN_URL . 'public/images/kpi-print.png'); ?>" alt="Print" class="whatauth-kpi-icon">
                </div>
            </div>
            <div class="whatauth-card-stat" id="stat-print-qrs">...</div>
            <p class="whatauth-card-desc"><?php esc_html_e('Con GeoTag nativo y alta resolución.', 'whatauth-for-wordpress'); ?></p>
        </div>
    </div>

    <!-- Gráfica de Tendencia de Escaneos (Últimos 30 días) -->
    <div class="whatauth-card whatauth-chart-card">
        <div class="whatauth-chart-header">
            <div>
                <h2 style="font-size: 16px; margin: 0; font-weight: 700; color: #111827; display: flex; align-items: center; gap: 8px;">
                    <img src="<?php echo esc_url(WHATAUTH_PLUGIN_URL . 'public/images/whatauth-logo.png'); ?>" alt="WhatAuth" style="width: 20px; height: 20px; object-fit: contain;">
                    <span><?php esc_html_e('Actividad y Escaneos Verificados (Últimos 30 días)', 'whatauth-for-wordpress'); ?></span>
                </h2>
                <p style="margin: 4px 0 0 0; font-size: 12px; color: var(--wa-text-muted);">
                    <?php esc_html_e('Tendencia temporal de lecturas de QR y validaciones de acceso en tu cuenta.', 'whatauth-for-wordpress'); ?>
                </p>
            </div>
            <div style="display: flex; align-items: center; gap: 14px; font-size: 12px; font-weight: 600;">
                <span style="display: inline-flex; align-items: center; gap: 5px;">
                    <span style="width: 10px; height: 10px; background: #25D366; border-radius: 50%; display: inline-block;"></span>
                    <?php esc_html_e('Scans Totales', 'whatauth-for-wordpress'); ?>
                </span>
                <span style="display: inline-flex; align-items: center; gap: 5px;">
                    <span style="width: 10px; height: 10px; background: #0284c7; border-radius: 50%; display: inline-block;"></span>
                    <?php esc_html_e('Login', 'whatauth-for-wordpress'); ?>
                </span>
            </div>
        </div>
        <div class="whatauth-chart-container">
            <canvas id="whatauth-trend-chart" class="whatauth-chart-canvas"></canvas>
            <div id="whatauth-chart-tooltip" class="whatauth-chart-tooltip"></div>
        </div>
    </div>

    <!-- Tarjetas de Información y Uso -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
        <div class="whatauth-card">
            <h2 style="font-size: 16px; margin-top: 0; margin-bottom: 12px;"><?php esc_html_e('Cómo insertar el Login en tu sitio', 'whatauth-for-wordpress'); ?></h2>
            <p><?php esc_html_e('WhatAuth se integra de forma transparente en tus páginas de acceso:', 'whatauth-for-wordpress'); ?></p>
            <table class="form-table" style="margin: 0;">
                <tr>
                    <th style="width: 220px;"><?php esc_html_e('Pantalla wp-login.php:', 'whatauth-for-wordpress'); ?></th>
                    <td>
                        <?php if (!empty($whatauth_settings['enable_wplogin'])) : ?>
                            <span class="whatauth-badge whatauth-badge-active"><?php esc_html_e('Activado de fábrica', 'whatauth-for-wordpress'); ?></span>
                        <?php else : ?>
                            <span class="whatauth-badge whatauth-badge-inactive"><?php esc_html_e('Desactivado en Ajustes', 'whatauth-for-wordpress'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('WooCommerce (Mi Cuenta):', 'whatauth-for-wordpress'); ?></th>
                    <td>
                        <?php if (class_exists('WooCommerce')) : ?>
                            <span class="whatauth-badge whatauth-badge-active"><?php esc_html_e('WooCommerce detectado', 'whatauth-for-wordpress'); ?></span>
                        <?php else : ?>
                            <span class="whatauth-badge whatauth-badge-inactive"><?php esc_html_e('No instalado', 'whatauth-for-wordpress'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Shortcode para cualquier página:', 'whatauth-for-wordpress'); ?></th>
                    <td>
                        <code>[whatauth_login redirect="/mi-cuenta"]</code>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Bloque Gutenberg:', 'whatauth-for-wordpress'); ?></th>
                    <td>
                        <?php esc_html_e('Busca "WhatAuth QR Login" en el editor de bloques.', 'whatauth-for-wordpress'); ?>
                    </td>
                </tr>
            </table>
        </div>

        <div class="whatauth-card" style="background: #fafafa;">
            <h2 style="font-size: 16px; margin-top: 0; margin-bottom: 12px;"><?php esc_html_e('Estado del Dominio', 'whatauth-for-wordpress'); ?></h2>
            <p style="font-size: 13px; color: var(--wa-text-muted); margin-bottom: 16px;">
                <?php esc_html_e('Dominio registrado para este sitio de WordPress:', 'whatauth-for-wordpress'); ?>
                <br>
                <strong style="color: var(--wa-text); font-size: 15px;"><?php echo esc_html($whatauth_domain); ?></strong>
            </p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=whatauth-settings')); ?>" class="button button-secondary" style="width: 100%; text-align: center;">
                <?php esc_html_e('Configurar Ajustes & Roles', 'whatauth-for-wordpress'); ?>
            </a>
            <div style="margin-top: 14px; text-align: center;">
                <a href="https://whatauth.com" target="_blank" rel="noopener noreferrer" style="font-size: 12px; color: var(--wa-primary-hover); text-decoration: none;">
                    <?php esc_html_e('Ir a WhatAuth Cloud ↗', 'whatauth-for-wordpress'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Tabla de Logs de Escaneos y Accesos del Usuario -->
    <div class="whatauth-card" style="margin-top: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
            <div>
                <h2 style="font-size: 16px; margin: 0; font-weight: 700; color: #111827; display: flex; align-items: center; gap: 8px;">
                    <img src="<?php echo esc_url(WHATAUTH_PLUGIN_URL . 'public/images/whatauth-logo.png'); ?>" alt="WhatAuth" style="width: 20px; height: 20px; object-fit: contain;">
                    <span><?php esc_html_e('Histórico de Escaneos y Validaciones Recientes', 'whatauth-for-wordpress'); ?></span>
                </h2>
                <p style="margin: 4px 0 0 0; font-size: 12px; color: var(--wa-text-muted);">
                    <?php esc_html_e('Registro de accesos y eventos de escaneo de WhatsApp para este usuario en tiempo real.', 'whatauth-for-wordpress'); ?>
                </p>
            </div>
            <button type="button" class="button button-small" id="btn-refresh-dashboard" style="display: inline-flex; align-items: center; gap: 4px;">
                <span class="dashicons dashicons-update" style="font-size: 14px; width: 14px; height: 14px;"></span>
                <span><?php esc_html_e('Actualizar', 'whatauth-for-wordpress'); ?></span>
            </button>
        </div>

        <div class="whatauth-logs-table-wrap">
            <table class="whatauth-logs-table">
                <thead>
                    <tr>
                        <th style="width: 140px;"><?php esc_html_e('Fecha / Hora', 'whatauth-for-wordpress'); ?></th>
                        <th style="width: 90px;"><?php esc_html_e('Tipo', 'whatauth-for-wordpress'); ?></th>
                        <th><?php esc_html_e('Campaña / Identificador', 'whatauth-for-wordpress'); ?></th>
                        <th><?php esc_html_e('Dispositivo / Origen', 'whatauth-for-wordpress'); ?></th>
                        <th style="width: 130px; text-align: right;"><?php esc_html_e('Estado', 'whatauth-for-wordpress'); ?></th>
                    </tr>
                </thead>
                <tbody id="whatauth-dashboard-logs-body">
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 26px; color: #888;">
                            <?php esc_html_e('Cargando registros de actividad...', 'whatauth-for-wordpress'); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

