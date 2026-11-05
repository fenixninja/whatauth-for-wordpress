<?php
if (!defined('ABSPATH')) {
    exit;
}

$whatauth_settings = get_option('whatauth_settings', array());
$whatauth_current_plan = !empty($whatauth_settings['plan']) ? strtoupper($whatauth_settings['plan']) : 'STARTER';
$whatauth_is_pro_agency = in_array($whatauth_current_plan, array('PRO', 'AGENCY'), true);
?>
<div class="wrap whatauth-admin-wrap">
    <div class="whatauth-header">
        <div class="whatauth-brand">
            <img src="<?php echo esc_url(WHATAUTH_PLUGIN_URL . 'public/images/whatauth-logo.png'); ?>" alt="WhatAuth" style="width: 42px; height: 42px; object-fit: contain;">
            <div>
                <h1><?php esc_html_e('Gestor de Códigos QR', 'whatauth'); ?></h1>
                <p style="margin: 2px 0 0 0; color: var(--wa-text-muted); font-size: 13px;">
                    <?php esc_html_e('Crea y administra tus campañas de Login, Pixel y Print con respuestas automáticas.', 'whatauth'); ?>
                </p>
            </div>
        </div>
        <div>
            <button type="button" class="button button-whatauth" id="btn-open-create-qr">
                <svg class="whatauth-btn-icon-svg" viewBox="0 0 24 24">
                    <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                </svg>
                <span><?php esc_html_e('Crear Nuevo Código QR', 'whatauth'); ?></span>
            </button>
        </div>
    </div>

    <!-- Pestañas de filtrado -->
    <div class="whatauth-tabs">
        <button type="button" class="whatauth-tab-btn active" data-tab="ALL"><?php esc_html_e('Todos los QRs', 'whatauth'); ?></button>
        <button type="button" class="whatauth-tab-btn" data-tab="LOGIN"><?php esc_html_e('Login', 'whatauth'); ?></button>
        <button type="button" class="whatauth-tab-btn" data-tab="PIXEL"><?php esc_html_e('Pixel (Marketing)', 'whatauth'); ?></button>
        <button type="button" class="whatauth-tab-btn" data-tab="PRINT"><?php esc_html_e('Print (Cartelería)', 'whatauth'); ?></button>
    </div>

    <!-- Tabla interactiva -->
    <table class="whatauth-table">
        <thead>
            <tr>
                <th style="width: 50px;"><?php esc_html_e('QR', 'whatauth'); ?></th>
                <th><?php esc_html_e('Título / Identificador', 'whatauth'); ?></th>
                <th><?php esc_html_e('Tipo', 'whatauth'); ?></th>
                <th><?php esc_html_e('Auto-Reply', 'whatauth'); ?></th>
                <th><?php esc_html_e('Webhook', 'whatauth'); ?></th>
                <th style="text-align: right;"><?php esc_html_e('Acciones', 'whatauth'); ?></th>
            </tr>
        </thead>
        <tbody id="whatauth-qr-table-body">
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px; color: #888;">
                    <?php esc_html_e('Cargando códigos QR desde WhatAuth Cloud...', 'whatauth'); ?>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Modal Studio de 2 Columnas de Creación / Edición de QR -->
<div class="whatauth-modal-backdrop" id="whatauth-qr-modal">
    <div class="whatauth-modal-dialog whatauth-modal-dialog-studio">
        <div class="whatauth-modal-header">
            <div style="display: flex; align-items: center; gap: 8px;">
                <img src="<?php echo esc_url(WHATAUTH_PLUGIN_URL . 'public/images/whatauth-logo.png'); ?>" alt="WhatAuth" style="width: 24px; height: 24px; object-fit: contain;">
                <h3 id="wa-modal-title"><?php esc_html_e('Configurar Código QR', 'whatauth'); ?></h3>
            </div>
            <button type="button" class="button-link whatauth-modal-close" style="font-size: 24px; line-height: 1; text-decoration: none; color: #888;">&times;</button>
        </div>

        <div class="whatauth-studio-grid">
            <!-- COLUMNA IZQUIERDA: Pestañas de configuración -->
            <div class="whatauth-studio-left">
                <input type="hidden" id="wa-field-qr-id" value="">

                <!-- Navegación por sub-pestañas -->
                <div class="whatauth-studio-tabs">
                    <button type="button" class="whatauth-studio-tab-btn active" data-studio-pane="pane-content">
                        <?php esc_html_e('1. Campaña', 'whatauth'); ?>
                    </button>
                    <button type="button" class="whatauth-studio-tab-btn" data-studio-pane="pane-autoreply">
                        <?php esc_html_e('2. Auto-Reply', 'whatauth'); ?>
                    </button>
                    <button type="button" class="whatauth-studio-tab-btn" data-studio-pane="pane-branding">
                        <?php esc_html_e('3. Estilo & Logo', 'whatauth'); ?>
                    </button>
                </div>

                <!-- SUB-PESTAÑA 1: CONTENIDO & CAMPAÑA -->
                <div class="whatauth-studio-pane active" id="pane-content">
                    <div>
                        <label for="wa-field-type" style="display: block; font-weight: 600; margin-bottom: 4px;"><?php esc_html_e('Tipo de Código QR', 'whatauth'); ?></label>
                        <select id="wa-field-type" style="width: 100%;">
                            <option value="LOGIN"><?php esc_html_e('LOGIN – Acceso Web / Sesión Passwordless', 'whatauth'); ?></option>
                            <option value="PIXEL"><?php esc_html_e('PIXEL – Captura de Leads & Redirección Web', 'whatauth'); ?></option>
                            <option value="PRINT"><?php esc_html_e('PRINT – Cartelería Física & GeoTagging', 'whatauth'); ?></option>
                        </select>
                    </div>

                    <div>
                        <label for="wa-field-line-id" style="display: block; font-weight: 600; margin-bottom: 4px;"><?php esc_html_e('Línea de WhatsApp del QR', 'whatauth'); ?></label>
                        <select id="wa-field-line-id" style="width: 100%;">
                            <option value=""><?php esc_html_e('Bot Anfitrión Oficial (+34 631 490 990 - Starter / Básico)', 'whatauth'); ?></option>
                        </select>
                        <p class="description" style="margin: 4px 0 0 0; font-size: 11px; color: #64748b;">
                            <?php esc_html_e('El Bot Oficial compartido opera en modo básico. Para activar respuestas automáticas (Auto-Reply), selecciona tu línea dedicada de WhatsApp.', 'whatauth'); ?>
                        </p>
                    </div>

                    <div>
                        <label for="wa-field-title" style="display: block; font-weight: 600; margin-bottom: 4px;"><?php esc_html_e('Título de la Campaña / QR', 'whatauth'); ?></label>
                        <input type="text" id="wa-field-title" style="width: 100%;" placeholder="<?php esc_attr_e('Ej. Promoción Primavera o Acceso Clientes', 'whatauth'); ?>">
                    </div>

                    <div>
                        <label for="wa-field-prefilled" style="display: block; font-weight: 600; margin-bottom: 4px;"><?php esc_html_e('Texto del Mensaje que enviará el usuario por WhatsApp', 'whatauth'); ?></label>
                        <input type="text" id="wa-field-prefilled" style="width: 100%;" placeholder="<?php esc_attr_e('Ej. Iniciar sesión en Mi Tienda', 'whatauth'); ?>">
                    </div>

                    <!-- Campos PRINT -->
                    <div class="wa-field-print-only" style="display: none; background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 12px; text-transform: uppercase; color: #475569;"><?php esc_html_e('Ubicación Física (Print QR)', 'whatauth'); ?></label>
                        <input type="text" id="wa-field-print-address" style="width: 100%; margin-bottom: 6px;" placeholder="<?php esc_attr_e('Dirección (ej. Calle Mayor 12)', 'whatauth'); ?>">
                        <div style="display: flex; gap: 8px;">
                            <input type="text" id="wa-field-print-city" style="flex: 1;" placeholder="<?php esc_attr_e('Ciudad', 'whatauth'); ?>">
                            <input type="text" id="wa-field-print-country" style="flex: 1;" placeholder="<?php esc_attr_e('País', 'whatauth'); ?>">
                        </div>
                    </div>

                    <!-- Webhook Saliente -->
                    <div style="border-top: 1px solid #e2e8f0; padding-top: 12px;">
                        <label style="font-weight: 600; display: block; margin-bottom: 4px;"><?php esc_html_e('Webhook Saliente al Escanear (Opcional)', 'whatauth'); ?></label>
                        <input type="url" id="wa-field-webhook-url" style="width: 100%;" placeholder="https://mi-webhook.com/evento">
                        <label style="display: inline-flex; align-items: center; gap: 6px; margin-top: 6px; font-size: 13px;">
                            <input type="checkbox" id="wa-field-webhook-enabled" value="1">
                            <span><?php esc_html_e('Activar envío de webhook para este código QR', 'whatauth'); ?></span>
                        </label>
                    </div>
                </div>

                <!-- SUB-PESTAÑA 2: AUTO-REPLY -->
                <div class="whatauth-studio-pane" id="pane-autoreply">
                    <!-- Alerta si se selecciona el Bot Anfitrión -->
                    <div id="wa-autoreply-hostbot-notice" style="display: none; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 12px; color: #1e40af; font-size: 13px; margin-bottom: 12px;">
                        <strong><svg style="display:inline-block;vertical-align:-2px;margin-right:4px;" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg><?php esc_html_e('Línea Anfitriona Compartida Seleccionada', 'whatauth'); ?></strong>
                        <p style="margin: 4px 0 0 0; font-size: 12px;">
                            <?php esc_html_e('El Bot Anfitrión Oficial (+34 631 490 990) solo procesa accesos estándar. Para activar respuestas automáticas (Auto-Reply), selecciona tu línea de WhatsApp dedicada en la Pestaña 1 (Campaña).', 'whatauth'); ?>
                        </p>
                    </div>

                    <div class="wa-autoreply-content-wrapper">
                        <!-- Switch general de Auto-Reply con tarjeta estilizada -->
                        <div class="wa-autoreply-toggle-box">
                            <label style="display: flex; align-items: center; gap: 10px; font-weight: 600; font-size: 13px; cursor: pointer; margin: 0;">
                                <input type="checkbox" id="wa-field-autoreply-enabled" value="1" style="width: 18px; height: 18px; accent-color: #25D366; cursor: pointer;">
                                <span><?php esc_html_e('Activar Respuesta Automática (Auto-Reply) al Escanear', 'whatauth'); ?></span>
                            </label>
                            <p style="margin: 4px 0 0 28px; font-size: 11px; color: #16a34a;">
                                <?php esc_html_e('El usuario recibirá este mensaje y los medios adjuntos instantáneamente tras enviar su mensaje por WhatsApp.', 'whatauth'); ?>
                            </p>
                        </div>

                        <!-- Editor WhatsApp Message Composer -->
                        <div class="wa-chat-composer">
                            <div class="wa-composer-toolbar">
                                <div class="wa-composer-title-wrap">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" style="color: #25D366;">
                                        <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
                                    </svg>
                                    <span><?php esc_html_e('Mensaje de Respuesta Automática', 'whatauth'); ?></span>
                                    <span style="font-size: 10px; background: #e0f2fe; color: #0284c7; padding: 1px 6px; border-radius: 4px; font-weight: 700; text-transform: uppercase;">WYSIWYG</span>
                                </div>
                                <div class="wa-composer-tools wa-studio-pro-controls" style="<?php echo !$whatauth_is_pro_agency ? 'display:none;' : 'display:flex;'; ?>">
                                    <button type="button" class="wa-toolbar-btn wa-wysiwyg-btn" data-syntax="*" title="<?php esc_attr_e('Negrita WhatsApp (*texto*)', 'whatauth'); ?>"><strong>B</strong></button>
                                    <button type="button" class="wa-toolbar-btn wa-wysiwyg-btn" data-syntax="_" title="<?php esc_attr_e('Cursiva WhatsApp (_texto_)', 'whatauth'); ?>"><em>I</em></button>
                                    <button type="button" class="wa-toolbar-btn wa-wysiwyg-btn" data-syntax="~" title="<?php esc_attr_e('Tachado WhatsApp (~texto~)', 'whatauth'); ?>"><del>S</del></button>
                                    <button type="button" class="wa-toolbar-btn wa-wysiwyg-btn" data-syntax="```" title="<?php esc_attr_e('Monoespaciado (```texto```)', 'whatauth'); ?>"><code>&lt;&gt;</code></button>
                                </div>
                            </div>
                            <input type="hidden" id="wa-field-autoreply-mode" value="TEXT">
                            <textarea id="wa-field-autoreply-text" class="wa-composer-textarea" rows="4" placeholder="<?php esc_attr_e('Escribe el mensaje que recibirá el usuario inmediatamente tras escanear...', 'whatauth'); ?>" <?php echo !$whatauth_is_pro_agency ? 'readonly' : ''; ?>></textarea>
                            <div class="wa-composer-footer">
                                <span class="wa-composer-hint">
                                    <span style="color:#25D366;">●</span> <?php esc_html_e('Admite formato WhatsApp: *negrita*, _cursiva_, ~tachado~', 'whatauth'); ?>
                                </span>
                                <span id="wa-composer-counter" class="wa-composer-counter">0 <?php esc_html_e('caracteres', 'whatauth'); ?></span>
                            </div>
                        </div>

                        <!-- Píldoras de selección de archivo multimedia -->
                        <div style="margin-top: 14px; margin-bottom: 8px;">
                            <label style="font-weight: 600; font-size: 12px; display: block; margin-bottom: 6px; text-transform: uppercase; color: #475569; letter-spacing: 0.5px;">
                                <?php esc_html_e('Contenido Multimedia Adjunto', 'whatauth'); ?>
                            </label>
                            <div class="wa-media-type-pills" style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <button type="button" class="button button-small wa-media-pill-btn active" data-media="none"><svg style="display:inline-block;vertical-align:-2px;margin-right:4px;" width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg><?php esc_html_e('Solo Texto', 'whatauth'); ?></button>
                                <button type="button" class="button button-small wa-media-pill-btn" data-media="image"><svg style="display:inline-block;vertical-align:-2px;margin-right:4px;" width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg><?php esc_html_e('Imagen', 'whatauth'); ?></button>
                                <button type="button" class="button button-small wa-media-pill-btn" data-media="audio"><svg style="display:inline-block;vertical-align:-2px;margin-right:4px;" width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M12 14c1.66 0 2.99-1.34 2.99-3L15 5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm5.3-3c0 3-2.54 5.1-5.3 5.1S6.7 14 6.7 11H5c0 3.41 2.72 6.23 6 6.72V21h2v-3.28c3.28-.48 6-3.3 6-6.72h-1.7z"/></svg><?php esc_html_e('Nota de Voz / Audio', 'whatauth'); ?></button>
                                <button type="button" class="button button-small wa-media-pill-btn" data-media="document"><svg style="display:inline-block;vertical-align:-2px;margin-right:4px;" width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg><?php esc_html_e('Documento / PDF', 'whatauth'); ?></button>
                            </div>
                        </div>

                        <!-- Sub-panel Imagen -->
                        <div id="wa-media-box-image" class="wa-media-box" style="display: none; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 12px;">
                            <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: #334155;"><?php esc_html_e('Imagen Adjunta (JPG/PNG/WEBP)', 'whatauth'); ?></label>
                            <div style="display: flex; gap: 8px;">
                                <input type="text" id="wa-field-autoreply-img-url" placeholder="<?php esc_attr_e('https://... o selecciona de medios', 'whatauth'); ?>" style="flex: 1;">
                                <button type="button" class="button button-secondary" id="btn-upload-autoreply-img" style="white-space: nowrap;"><?php esc_html_e('Medios WP', 'whatauth'); ?></button>
                            </div>
                        </div>

                        <!-- Sub-panel Audio -->
                        <div id="wa-media-box-audio" class="wa-media-box" style="display: none; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 12px;">
                            <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: #334155;"><?php esc_html_e('Nota de Voz o Audio (MP3/OGG)', 'whatauth'); ?></label>
                            <div style="display: flex; gap: 8px;">
                                <input type="text" id="wa-field-autoreply-audio-url" placeholder="<?php esc_attr_e('https://... o selecciona de medios', 'whatauth'); ?>" style="flex: 1;">
                                <button type="button" class="button button-secondary" id="btn-upload-autoreply-audio" style="white-space: nowrap;"><?php esc_html_e('Medios WP', 'whatauth'); ?></button>
                            </div>
                        </div>

                        <!-- Sub-panel Documento -->
                        <div id="wa-media-box-document" class="wa-media-box" style="display: none; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 12px;">
                            <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: #334155;"><?php esc_html_e('Documento o Catálogo (PDF/DOCX)', 'whatauth'); ?></label>
                            <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                                <input type="text" id="wa-field-autoreply-doc-url" placeholder="<?php esc_attr_e('https://... o selecciona de medios', 'whatauth'); ?>" style="flex: 1;">
                                <button type="button" class="button button-secondary" id="btn-upload-autoreply-doc" style="white-space: nowrap;"><?php esc_html_e('Medios WP', 'whatauth'); ?></button>
                            </div>
                            <input type="text" id="wa-field-autoreply-doc-name" placeholder="<?php esc_attr_e('Nombre visible del archivo (ej. catalogo-2026.pdf)', 'whatauth'); ?>" style="width: 100%;">
                        </div>

                        <!-- Enlace CTA Opcional -->
                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: #334155;"><?php esc_html_e('Enlace de Destino / Botón CTA (Opcional)', 'whatauth'); ?></label>
                            <input type="url" id="wa-field-autoreply-url" placeholder="https://tudominio.com/oferta" style="width: 100%;">
                        </div>

                        <div>
                            <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;"><?php esc_html_e('Previsualización en WhatsApp:', 'whatauth'); ?></span>
                            <div class="whatauth-preview-bubble" id="wa-autoreply-preview-box">
                                <em><?php esc_html_e('Escribe tu mensaje en la caja superior para previsualizarlo como en WhatsApp...', 'whatauth'); ?></em>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SUB-PESTAÑA 3: ESTILO & LOGO (BRANDING) -->
                <div class="whatauth-studio-pane" id="pane-branding">
                    <!-- 1. Logotipo Central -->
                    <div style="margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid #e2e8f0;">
                        <label style="font-weight: 600; display: block; margin-bottom: 4px; font-size: 13px;"><?php esc_html_e('1. Logotipo Central del QR', 'whatauth'); ?></label>
                        <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 8px;">
                            <input type="text" id="wa-field-qr-logo" style="flex: 1; <?php echo !$whatauth_is_pro_agency ? 'background:#f3f4f6;' : ''; ?>" placeholder="<?php esc_attr_e('URL del logotipo personalizado', 'whatauth'); ?>" value="" <?php echo !$whatauth_is_pro_agency ? 'readonly' : ''; ?>>
                            <button type="button" class="button wa-studio-pro-controls" id="btn-upload-qr-logo" <?php echo !$whatauth_is_pro_agency ? 'style="display:none;"' : ''; ?>><?php esc_html_e('Medios WP', 'whatauth'); ?></button>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div>
                                <label for="wa-field-qr-logo-margin" style="font-size: 11px; font-weight: 600; color: #64748b; display: block; margin-bottom: 2px;"><?php esc_html_e('Margen del Logo (px)', 'whatauth'); ?></label>
                                <input type="number" id="wa-field-qr-logo-margin" min="0" max="25" value="6" style="width: 100%;" <?php echo !$whatauth_is_pro_agency ? 'disabled' : ''; ?>>
                            </div>
                            <div>
                                <label for="wa-field-qr-logo-scale" style="font-size: 11px; font-weight: 600; color: #64748b; display: block; margin-bottom: 2px;"><?php esc_html_e('Escala del Logo', 'whatauth'); ?></label>
                                <input type="number" id="wa-field-qr-logo-scale" min="0.10" max="0.50" step="0.02" value="0.22" style="width: 100%;" <?php echo !$whatauth_is_pro_agency ? 'disabled' : ''; ?>>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Módulos y Puntos (Dots) -->
                    <div style="margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid #e2e8f0;">
                        <label style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 13px;"><?php esc_html_e('2. Módulos y Puntos (Dots)', 'whatauth'); ?></label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 8px;">
                            <div>
                                <label for="wa-field-qr-color-dots" style="font-size: 11px; font-weight: 600; color: #64748b; display: block; margin-bottom: 2px;"><?php esc_html_e('Color Puntos', 'whatauth'); ?></label>
                                <input type="color" id="wa-field-qr-color-dots" value="#1a2517" style="width: 100%; height: 36px; padding: 2px; cursor: pointer;" <?php echo !$whatauth_is_pro_agency ? 'disabled' : ''; ?>>
                            </div>
                            <div>
                                <label for="wa-field-qr-shape-dots" style="font-size: 11px; font-weight: 600; color: #64748b; display: block; margin-bottom: 2px;"><?php esc_html_e('Forma de Puntos', 'whatauth'); ?></label>
                                <select id="wa-field-qr-shape-dots" style="width: 100%;" <?php echo !$whatauth_is_pro_agency ? 'disabled' : ''; ?>>
                                    <option value="square"><?php esc_html_e('Cuadrados (Square)', 'whatauth'); ?></option>
                                    <option value="dots"><?php esc_html_e('Puntos (Dots)', 'whatauth'); ?></option>
                                    <option value="rounded"><?php esc_html_e('Redondeados (Rounded)', 'whatauth'); ?></option>
                                    <option value="classy"><?php esc_html_e('Classy', 'whatauth'); ?></option>
                                    <option value="classy-rounded"><?php esc_html_e('Classy Redondeado', 'whatauth'); ?></option>
                                    <option value="extra-rounded"><?php esc_html_e('Extra Redondeado', 'whatauth'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label for="wa-field-qr-ec-level" style="font-size: 11px; font-weight: 600; color: #64748b; display: block; margin-bottom: 2px;"><?php esc_html_e('Nivel de Corrección y Nitidez', 'whatauth'); ?></label>
                            <select id="wa-field-qr-ec-level" style="width: 100%;" <?php echo !$whatauth_is_pro_agency ? 'disabled' : ''; ?>>
                                <option value="Q"><?php esc_html_e('Alto - Q (25% • Recomendado para logos)', 'whatauth'); ?></option>
                                <option value="H"><?php esc_html_e('Máximo - H (30%)', 'whatauth'); ?></option>
                                <option value="M"><?php esc_html_e('Medio - M (15%)', 'whatauth'); ?></option>
                                <option value="L"><?php esc_html_e('Bajo - L (7%)', 'whatauth'); ?></option>
                            </select>
                        </div>
                    </div>

                    <!-- 3. Marco Exterior de Esquina -->
                    <div style="margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid #e2e8f0;">
                        <label style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 13px;"><?php esc_html_e('3. Marco Exterior de Esquina', 'whatauth'); ?></label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div>
                                <label for="wa-field-qr-color-corners" style="font-size: 11px; font-weight: 600; color: #64748b; display: block; margin-bottom: 2px;"><?php esc_html_e('Color Esquinas', 'whatauth'); ?></label>
                                <input type="color" id="wa-field-qr-color-corners" value="#1a2517" style="width: 100%; height: 36px; padding: 2px; cursor: pointer;" <?php echo !$whatauth_is_pro_agency ? 'disabled' : ''; ?>>
                            </div>
                            <div>
                                <label for="wa-field-qr-shape-corners" style="font-size: 11px; font-weight: 600; color: #64748b; display: block; margin-bottom: 2px;"><?php esc_html_e('Forma Marco Exterior', 'whatauth'); ?></label>
                                <select id="wa-field-qr-shape-corners" style="width: 100%;" <?php echo !$whatauth_is_pro_agency ? 'disabled' : ''; ?>>
                                    <option value="extra-rounded"><?php esc_html_e('Extra Redondeado', 'whatauth'); ?></option>
                                    <option value="square"><?php esc_html_e('Cuadrado', 'whatauth'); ?></option>
                                    <option value="dot"><?php esc_html_e('Círculo (Dot)', 'whatauth'); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Punto Interior de Esquina -->
                    <div style="margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid #e2e8f0;">
                        <label style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 13px;"><?php esc_html_e('4. Punto Interior de Esquina', 'whatauth'); ?></label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div>
                                <label for="wa-field-qr-color-corner-dot" style="font-size: 11px; font-weight: 600; color: #64748b; display: block; margin-bottom: 2px;"><?php esc_html_e('Color Punto Interior', 'whatauth'); ?></label>
                                <input type="color" id="wa-field-qr-color-corner-dot" value="#6f8065" style="width: 100%; height: 36px; padding: 2px; cursor: pointer;" <?php echo !$whatauth_is_pro_agency ? 'disabled' : ''; ?>>
                            </div>
                            <div>
                                <label for="wa-field-qr-shape-corner-dot" style="font-size: 11px; font-weight: 600; color: #64748b; display: block; margin-bottom: 2px;"><?php esc_html_e('Forma Punto Interior', 'whatauth'); ?></label>
                                <select id="wa-field-qr-shape-corner-dot" style="width: 100%;" <?php echo !$whatauth_is_pro_agency ? 'disabled' : ''; ?>>
                                    <option value="dot"><?php esc_html_e('Círculo (Dot)', 'whatauth'); ?></option>
                                    <option value="square"><?php esc_html_e('Cuadrado', 'whatauth'); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Lienzo y Fondo -->
                    <div>
                        <label style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 13px;"><?php esc_html_e('5. Lienzo y Fondo', 'whatauth'); ?></label>
                        <div>
                            <label for="wa-field-qr-color-bg" style="font-size: 11px; font-weight: 600; color: #64748b; display: block; margin-bottom: 2px;"><?php esc_html_e('Color de Fondo (bgColor)', 'whatauth'); ?></label>
                            <input type="color" id="wa-field-qr-color-bg" value="#ffffff" style="width: 100%; height: 36px; padding: 2px; cursor: pointer;" <?php echo !$whatauth_is_pro_agency ? 'disabled' : ''; ?>>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COLUMNA DERECHA: Vista Previa del QR en Tiempo Real -->
            <div class="whatauth-studio-right">
                <div class="whatauth-studio-qr-card">
                    <span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748b; letter-spacing: 0.5px;">
                        <?php esc_html_e('Vista Previa del QR (En Vivo)', 'whatauth'); ?>
                    </span>
                    
                    <div class="whatauth-studio-qr-canvas-wrap" id="wa-studio-qr-container">
                        <!-- El QR se renderiza dinámicamente con QRCodeStyling -->
                    </div>

                    <div style="font-size: 12px; color: #16a34a; font-weight: 600; display: flex; align-items: center; gap: 4px; margin-top: 4px;">
                        <span style="width: 8px; height: 8px; background: #22c55e; border-radius: 50%; display: inline-block;"></span>
                        <?php esc_html_e('Sincronizado en tiempo real', 'whatauth'); ?>
                    </div>
                </div>

                <!-- Botones de Acción Rápida -->
                <div style="width: 100%; max-width: 310px; display: flex; flex-direction: column; gap: 8px; margin-top: 14px;">
                    <button type="button" class="button button-whatauth" id="btn-save-qr-modal" style="width: 100%; justify-content: center; padding: 10px;">
                        <svg class="whatauth-btn-icon-svg" viewBox="0 0 24 24">
                            <path d="M17 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/>
                        </svg>
                        <span><?php esc_html_e('Guardar Código QR', 'whatauth'); ?></span>
                    </button>

                    <div style="display: flex; gap: 6px;">
                        <a href="#" target="_blank" rel="noopener noreferrer" class="button button-secondary" id="btn-studio-test-qr" style="flex: 1; text-align: center; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none;">
                            <span><?php esc_html_e('Probar QR ↗', 'whatauth'); ?></span>
                        </a>
                        <button type="button" class="button button-secondary" id="btn-studio-download-png" style="flex: 1; font-size: 12px;">
                            <span><?php esc_html_e('Descargar PNG', 'whatauth'); ?></span>
                        </button>
                    </div>

                    <button type="button" class="button button-link whatauth-modal-close" style="font-size: 12px; color: #64748b; margin-top: 4px;">
                        <?php esc_html_e('Cerrar', 'whatauth'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

