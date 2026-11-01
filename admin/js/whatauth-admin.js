/**
 * WhatAuth Admin JS - Lógica asíncrona, modales y editor WYSIWYG
 */

(function ($) {
  'use strict';

  const App = {
    currentTab: 'ALL',
    qrs: [],
    trendChartData: null,
    qrStylingInstance: null,

    init: function () {
      this.bindEvents();
      this.applyPlanState(whatauthAdminData.plan, null, null);
      if ($('#stat-total-qrs').length || $('#whatauth-trend-chart').length) {
        this.loadDashboardData();
      }
      if ($('#whatauth-qr-table-body').length) {
        this.loadQrs();
      }
    },

    bindEvents: function () {
      const self = this;

      // Refrescar dashboard
      $('#btn-refresh-dashboard').on('click', function (e) {
        e.preventDefault();
        self.loadDashboardData();
      });

      // Redibujar gráfica en redimensionamiento de ventana
      $(window).on('resize', function () {
        if (self.trendChartData) {
          self.renderTrendChart(self.trendChartData);
        }
      });

      // Navegación de sub-pestañas en el modal Studio
      $('.whatauth-studio-tab-btn').on('click', function (e) {
        e.preventDefault();
        $('.whatauth-studio-tab-btn').removeClass('active');
        $(this).addClass('active');
        const targetPane = $(this).data('studio-pane');
        $('.whatauth-studio-pane').removeClass('active');
        $('#' + targetPane).addClass('active');
      });

      // Subir logo para el código QR desde Medios WP
      $('#btn-upload-qr-logo').on('click', function (e) {
        e.preventDefault();
        if (typeof wp !== 'undefined' && wp.media) {
          const uploader = wp.media({
            title: whatauthAdminData.i18n.mediaSelectLogo || 'Seleccionar Logotipo para el Código QR',
            button: { text: whatauthAdminData.i18n.mediaUseLogo || 'Usar este Logo' },
            multiple: false
          });
          uploader.on('select', function () {
            const attachment = uploader.state().get('selection').first().toJSON();
            $('#wa-field-qr-logo').val(attachment.url);
            self.updateStudioQrPreview();
          });
          uploader.open();
        }
      });

      // Subir imagen para Auto-Reply desde Medios WP
      $('#btn-upload-autoreply-img').on('click', function (e) {
        e.preventDefault();
        if (typeof wp !== 'undefined' && wp.media) {
          const uploader = wp.media({
            title: 'Seleccionar Imagen para Auto-Reply',
            button: { text: 'Adjuntar Imagen' },
            library: { type: 'image' },
            multiple: false
          });
          uploader.on('select', function () {
            const attachment = uploader.state().get('selection').first().toJSON();
            $('#wa-field-autoreply-img-url').val(attachment.url);
            self.updatePreviewBubble($('#wa-field-autoreply-text').val());
          });
          uploader.open();
        }
      });

      // Subir audio para Auto-Reply desde Medios WP
      $('#btn-upload-autoreply-audio').on('click', function (e) {
        e.preventDefault();
        if (typeof wp !== 'undefined' && wp.media) {
          const uploader = wp.media({
            title: 'Seleccionar Audio para Auto-Reply',
            button: { text: 'Adjuntar Audio' },
            library: { type: 'audio' },
            multiple: false
          });
          uploader.on('select', function () {
            const attachment = uploader.state().get('selection').first().toJSON();
            $('#wa-field-autoreply-audio-url').val(attachment.url);
            self.updatePreviewBubble($('#wa-field-autoreply-text').val());
          });
          uploader.open();
        }
      });

      // Subir documento para Auto-Reply desde Medios WP
      $('#btn-upload-autoreply-doc').on('click', function (e) {
        e.preventDefault();
        if (typeof wp !== 'undefined' && wp.media) {
          const uploader = wp.media({
            title: 'Seleccionar Documento para Auto-Reply',
            button: { text: 'Adjuntar Documento' },
            multiple: false
          });
          uploader.on('select', function () {
            const attachment = uploader.state().get('selection').first().toJSON();
            $('#wa-field-autoreply-doc-url').val(attachment.url);
            if (!$('#wa-field-autoreply-doc-name').val()) {
              $('#wa-field-autoreply-doc-name').val(attachment.filename || attachment.title || 'documento.pdf');
            }
            self.updatePreviewBubble($('#wa-field-autoreply-text').val());
          });
          uploader.open();
        }
      });

      // Píldoras de selección de tipo multimedia en Auto-Reply
      $(document).on('click', '.wa-media-pill-btn', function (e) {
        e.preventDefault();
        $('.wa-media-pill-btn').removeClass('active');
        $(this).addClass('active');

        const media = $(this).data('media');
        $('.wa-media-box').hide();

        if (media === 'image') {
          $('#wa-media-box-image').slideDown(150);
        } else if (media === 'audio') {
          $('#wa-media-box-audio').slideDown(150);
        } else if (media === 'document') {
          $('#wa-media-box-document').slideDown(150);
        }

        self.updatePreviewBubble($('#wa-field-autoreply-text').val());
      });

      // Cambio de línea de WhatsApp en Pestaña 1
      $('#wa-field-line-id').on('change', function () {
        self.handleLineSelection();
      });

      // Checkbox general de Auto-Reply
      $('#wa-field-autoreply-enabled').on('change', function () {
        const isEnabled = $(this).is(':checked');
        const isHostBot = !$('#wa-field-line-id').val();
        if (isHostBot) {
          $(this).prop('checked', false);
          return;
        }
        self.toggleAutoReplyControls(isEnabled);
        self.updatePreviewBubble($('#wa-field-autoreply-text').val());
      });

      // Campos de auto-reply que refrescan la burbuja
      $('#wa-field-autoreply-img-url, #wa-field-autoreply-audio-url, #wa-field-autoreply-doc-url, #wa-field-autoreply-doc-name, #wa-field-autoreply-url').on('input change', function () {
        self.updatePreviewBubble($('#wa-field-autoreply-text').val());
      });

      // Cambios reactivos en el formulario que actualizan el QR en vivo
      $('#wa-field-prefilled, #wa-field-type, #wa-field-title, #wa-field-line-id, #wa-field-qr-logo, #wa-field-qr-logo-margin, #wa-field-qr-logo-scale, #wa-field-qr-color-dots, #wa-field-qr-shape-dots, #wa-field-qr-ec-level, #wa-field-qr-color-corners, #wa-field-qr-shape-corners, #wa-field-qr-color-corner-dot, #wa-field-qr-shape-corner-dot, #wa-field-qr-color-bg').on('input change', function () {
        self.updateStudioQrPreview();
      });

      // Descarga de QR en PNG
      $('#btn-studio-download-png').on('click', function (e) {
        e.preventDefault();
        if (self.qrStylingInstance) {
          const fileName = ($('#wa-field-title').val() || 'whatauth-qr').toLowerCase().replace(/[^a-z0-9]/g, '-');
          self.qrStylingInstance.download({ name: fileName, extension: 'png' });
        }
      });

      // Navegación de pestañas de QRs
      $('.whatauth-tab-btn').on('click', function (e) {
        e.preventDefault();
        $('.whatauth-tab-btn').removeClass('active');
        $(this).addClass('active');
        self.currentTab = $(this).data('tab');
        self.renderQrs();
      });

      // Abrir modal de creación de QR
      $('#btn-open-create-qr').on('click', function (e) {
        e.preventDefault();
        self.openQrModal();
      });

      // Cerrar modal
      $('.whatauth-modal-close, .whatauth-modal-backdrop').on('click', function (e) {
        if (e.target === this) {
          self.closeQrModal();
        }
      });

      // Cambio de tipo de QR en el formulario
      $('#wa-field-type').on('change', function () {
        const type = $(this).val();
        if (type === 'PRINT') {
          $('.wa-field-print-only').show();
        } else {
          $('.wa-field-print-only').hide();
        }
      });

      // Editor WYSIWYG de Auto-Reply y Contador en Vivo
      $('#wa-field-autoreply-text').on('input', function () {
        const val = $(this).val();
        $('#wa-composer-counter').text(val.length + ' ' + (whatauthAdminData.i18n.characters || 'caracteres'));
        self.updatePreviewBubble(val);
      });

      $('.wa-wysiwyg-btn').on('click', function (e) {
        e.preventDefault();
        const syntax = $(this).data('syntax'); // '*', '_', '~', '```'
        const textarea = document.getElementById('wa-field-autoreply-text');
        if (!textarea) return;

        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;
        const selected = text.substring(start, end) || 'texto';

        textarea.value = text.substring(0, start) + syntax + selected + syntax + text.substring(end);
        textarea.focus();
        $('#wa-composer-counter').text(textarea.value.length + ' ' + (whatauthAdminData.i18n.characters || 'caracteres'));
        self.updatePreviewBubble(textarea.value);
      });

      // Selector de medios de WordPress para logo personalizado
      $('#btn-upload-custom-logo').on('click', function (e) {
        e.preventDefault();
        if (typeof wp !== 'undefined' && wp.media) {
          const mediaUploader = wp.media({
            title: whatauthAdminData.i18n.mediaSelectLogo || 'Seleccionar Logotipo para WhatAuth',
            button: { text: whatauthAdminData.i18n.mediaUseLogo || 'Usar este Logo' },
            multiple: false
          });
          mediaUploader.on('select', function () {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#whatauth-custom-logo').val(attachment.url);
          });
          mediaUploader.open();
        }
      });

      // Guardar QR desde el modal
      $('#btn-save-qr-modal').on('click', function (e) {
        e.preventDefault();
        self.saveQr();
      });

      // Eliminar QR
      $(document).on('click', '.btn-delete-qr', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        self.deleteQr(id);
      });

      // Editar QR
      $(document).on('click', '.btn-edit-qr', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        const qr = self.qrs.find(item => item.id === id);
        if (qr) {
          self.openQrModal(qr);
        }
      });

      // Probar API Key en Ajustes / Wizard
      $('#btn-test-token').on('click', function (e) {
        e.preventDefault();
        self.testConnection();
      });

      // Guardar formulario de Ajustes
      $('#whatauth-settings-form').on('submit', function (e) {
        e.preventDefault();
        self.saveSettings();
      });
    },

    showToast: function (message, type = 'success') {
      let $toast = $('#whatauth-toast');
      if (!$toast.length) {
        $toast = $('<div id="whatauth-toast" class="whatauth-toast"></div>').appendTo('body');
      }

      $toast
        .removeClass('toast-success toast-error')
        .addClass(type === 'error' ? 'toast-error' : 'toast-success')
        .text(message)
        .addClass('is-visible');

      setTimeout(function () {
        $toast.removeClass('is-visible');
      }, 3500);
    },

    loadQrs: function () {
      const self = this;
      const $tbody = $('#whatauth-qr-table-body');
      $tbody.html('<tr><td colspan="6" style="text-align:center; padding:30px; color:#888;">' + (whatauthAdminData.i18n.loadingQrs || 'Cargando códigos QR desde WhatAuth Cloud...') + '</td></tr>');

      $.ajax({
        url: whatauthAdminData.ajaxUrl,
        type: 'GET',
        dataType: 'json',
        data: {
          action: 'whatauth_get_qrs',
          nonce: whatauthAdminData.nonce
        },
        success: function (res) {
          if (res.success && res.data && res.data.qrs) {
            self.qrs = res.data.qrs;
            self.renderQrs();
            self.updateDashboardStats();
          } else {
            $tbody.html('<tr><td colspan="6" style="text-align:center; color:#c00;">' + (res.data?.message || whatauthAdminData.i18n.errorGeneric) + '</td></tr>');
          }
        },
        error: function () {
          $tbody.html('<tr><td colspan="6" style="text-align:center; color:#c00;">' + whatauthAdminData.i18n.errorGeneric + '</td></tr>');
        }
      });
    },

    renderQrs: function () {
      const self = this;
      const $tbody = $('#whatauth-qr-table-body');
      const filtered = this.qrs.filter(qr => {
        if (self.currentTab === 'ALL') return true;
        return (qr.type || '').toUpperCase() === self.currentTab;
      });

      if (!filtered.length) {
        $tbody.html('<tr><td colspan="6" style="text-align:center; padding:30px; color:#888;">' + whatauthAdminData.i18n.noQrsFound + '</td></tr>');
        return;
      }

      const isProAgency = Boolean(whatauthAdminData.isProAgency);
      const isAgency = (whatauthAdminData.plan || '').toUpperCase() === 'AGENCY';

      let html = '';
      filtered.forEach(qr => {
        const type = (qr.type || 'LOGIN').toUpperCase();
        let badgeClass = 'whatauth-badge-starter';
        if (type === 'PIXEL') badgeClass = 'whatauth-badge-pro';
        if (type === 'PRINT') badgeClass = 'whatauth-badge-agency';

        const thumbUrl = qr.branding?.logoUrl || 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="%2325D366" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>';

        let lineInfo = '';
        const targetLine = qr.lineId || qr.whatsappInstanceId;
        if (targetLine) {
          let lineLabel = targetLine;
          if (Array.isArray(whatauthAdminData.lines)) {
            const found = whatauthAdminData.lines.find(l => l.id === targetLine);
            if (found) lineLabel = found.name || found.phoneNumber;
          }
          lineInfo = '<br><span style="display:inline-block; font-size:10px; background:#ede9fe; color:#6d28d9; padding:1px 6px; border-radius:4px; font-weight:600; margin-top:3px;">' + (whatauthAdminData.i18n.linePrefix || 'Línea: ') + self.escapeHtml(lineLabel) + '</span>';
        }

        let deleteBtn = '';
        if (isProAgency) {
          deleteBtn = '<button type="button" class="button button-small button-link-delete btn-delete-qr" data-id="' + qr.id + '">' + (whatauthAdminData.i18n.delete || 'Eliminar') + '</button>';
        } else {
          deleteBtn = '<button type="button" class="button button-small disabled" style="opacity:0.45; cursor:not-allowed;" title="' + (whatauthAdminData.i18n.deleteLockedTooltip || 'Eliminar QRs requiere plan PRO o Agency') + '" disabled><span class="dashicons dashicons-lock" style="font-size:12px;width:12px;height:12px;vertical-align:middle;margin-right:2px;"></span>' + (whatauthAdminData.i18n.delete || 'Eliminar') + '</button>';
        }

        // Resumen enriquecido de Auto-Reply
        let autoReplyBadge = '<span style="color:#888;">—</span>';
        if (qr.autoReply?.enabled || qr.autoReply?.content || qr.autoReply?.text || qr.autoReply?.image || qr.autoReply?.audio || qr.autoReply?.document) {
          let badges = [];
          if (qr.autoReply?.content || qr.autoReply?.text) badges.push('Texto');
          if (qr.autoReply?.image) badges.push('Img');
          if (qr.autoReply?.audio) badges.push('Audio');
          if (qr.autoReply?.document) badges.push('Doc');
          autoReplyBadge = '<span style="color:#15803d; font-weight:500;">✓ ' + (badges.join('+') || (whatauthAdminData.i18n.configured || 'Configurado')) + '</span>';
        }

        html += '<tr>';
        html += '<td style="width: 50px;"><img src="' + thumbUrl + '" class="whatauth-qr-thumb" alt="QR" onerror="this.src=\'data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2248%22 height=%2248%22 fill=%22%2325D366%22 viewBox=%220 0 24 24%22><path d=%22M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z%22/></svg>\'"></td>';
        html += '<td><strong>' + self.escapeHtml(qr.title || qr.name || (whatauthAdminData.i18n.untitled || 'Sin título')) + '</strong>' + lineInfo + '<br><small style="color:#888;">ID: ' + self.escapeHtml(qr.id) + '</small></td>';
        html += '<td><span class="whatauth-badge ' + badgeClass + '">' + type + '</span></td>';
        html += '<td>' + autoReplyBadge + '</td>';
        html += '<td>' + (qr.webhook?.url ? '<span style="color:#15803d; font-weight:500;">' + (whatauthAdminData.i18n.active || '✓ Activo') + '</span>' : '<span style="color:#888;">—</span>') + '</td>';
        html += '<td style="text-align:right;">' +
          '<button type="button" class="button button-small btn-edit-qr" data-id="' + qr.id + '">' + (whatauthAdminData.i18n.edit || 'Editar') + '</button> ' +
          deleteBtn +
          '</td>';
        html += '</tr>';
      });

      $tbody.html(html);
    },

    loadDashboardData: function () {
      const self = this;
      $('#stat-total-qrs, #stat-login-qrs, #stat-pixel-qrs, #stat-print-qrs').text('...');

      $.ajax({
        url: whatauthAdminData.ajaxUrl,
        type: 'GET',
        dataType: 'json',
        data: {
          action: 'whatauth_get_dashboard_data',
          nonce: whatauthAdminData.nonce
        },
        success: function (res) {
          if (res.success && res.data) {
            const stats = res.data.stats || {};
            $('#stat-total-qrs').text(stats.total ?? 0);
            $('#stat-login-qrs').text(stats.login ?? 0);
            $('#stat-pixel-qrs').text(stats.pixel ?? 0);
            $('#stat-print-qrs').text(stats.print ?? 0);

            if (res.data.trend && res.data.trend.length) {
              self.trendChartData = res.data.trend;
              self.renderTrendChart(res.data.trend);
            } else {
              self.renderEmptyChart();
            }

            self.renderDashboardLogs(res.data.qrLogs, res.data.recentAccess);
          } else {
            $('#stat-total-qrs, #stat-login-qrs, #stat-pixel-qrs, #stat-print-qrs').text('0');
            $('#whatauth-dashboard-logs-body').html('<tr><td colspan="5" style="text-align:center; padding:20px; color:#c00;">' + (res.data?.message || (whatauthAdminData.i18n.errorLoadingData || 'Error cargando datos')) + '</td></tr>');
            self.renderEmptyChart();
          }
        },
        error: function () {
          $('#stat-total-qrs, #stat-login-qrs, #stat-pixel-qrs, #stat-print-qrs').text('0');
          $('#whatauth-dashboard-logs-body').html('<tr><td colspan="5" style="text-align:center; padding:20px; color:#888;">' + (whatauthAdminData.i18n.configTokenToSync || 'Configura tu Service Token en Ajustes para sincronizar la telemetría en vivo.') + '</td></tr>');
          self.renderEmptyChart();
        }
      });
    },

    renderEmptyChart: function () {
      const canvas = document.getElementById('whatauth-trend-chart');
      if (!canvas) return;
      const ctx = canvas.getContext('2d');
      const rect = canvas.getBoundingClientRect();
      const dpr = window.devicePixelRatio || 1;
      canvas.width = rect.width * dpr;
      canvas.height = (rect.height || 220) * dpr;
      ctx.scale(dpr, dpr);

      ctx.clearRect(0, 0, rect.width, rect.height || 220);
      ctx.fillStyle = '#9ca3af';
      ctx.font = '13px sans-serif';
      ctx.textAlign = 'center';
      ctx.fillText(whatauthAdminData.i18n.noActivity || 'Sin actividad registrada en los últimos 30 días', rect.width / 2, (rect.height || 220) / 2);
    },

    renderTrendChart: function (trend) {
      const canvas = document.getElementById('whatauth-trend-chart');
      if (!canvas || !trend || !trend.length) return;

      const rect = canvas.getBoundingClientRect();
      const width = rect.width;
      const height = rect.height || 220;
      const dpr = window.devicePixelRatio || 1;

      canvas.width = width * dpr;
      canvas.height = height * dpr;
      const ctx = canvas.getContext('2d');
      ctx.scale(dpr, dpr);

      const paddingLeft = 40;
      const paddingRight = 20;
      const paddingTop = 25;
      const paddingBottom = 35;
      const chartWidth = width - paddingLeft - paddingRight;
      const chartHeight = height - paddingTop - paddingBottom;

      const pointsTotal = trend.map(d => (d.loginScans || 0) + (d.pixelScans || 0) + (d.printScans || 0));
      const pointsLogin = trend.map(d => (d.loginScans || 0));

      const maxVal = Math.max(5, ...pointsTotal, ...pointsLogin);

      // Limpiar lienzo
      ctx.clearRect(0, 0, width, height);

      // Líneas de cuadrícula horizontales
      ctx.strokeStyle = '#f1f5f9';
      ctx.lineWidth = 1;
      const gridSteps = 4;
      for (let i = 0; i <= gridSteps; i++) {
        const y = paddingTop + (chartHeight / gridSteps) * i;
        ctx.beginPath();
        ctx.moveTo(paddingLeft, y);
        ctx.lineTo(width - paddingRight, y);
        ctx.stroke();

        const labelVal = Math.round(maxVal - (maxVal / gridSteps) * i);
        ctx.fillStyle = '#94a3b8';
        ctx.font = '11px sans-serif';
        ctx.textAlign = 'right';
        ctx.fillText(labelVal.toString(), paddingLeft - 8, y + 4);
      }

      // Función de coordenadas
      const stepX = chartWidth / (trend.length - 1);
      const getCoord = (index, value) => {
        const x = paddingLeft + index * stepX;
        const y = paddingTop + chartHeight - (value / maxVal) * chartHeight;
        return { x, y };
      };

      // 1. Dibujar área con gradiente verde WhatAuth para total scans
      const grad = ctx.createLinearGradient(0, paddingTop, 0, paddingTop + chartHeight);
      grad.addColorStop(0, 'rgba(37, 211, 102, 0.32)');
      grad.addColorStop(1, 'rgba(37, 211, 102, 0.01)');

      ctx.beginPath();
      const firstCoord = getCoord(0, pointsTotal[0]);
      ctx.moveTo(firstCoord.x, firstCoord.y);

      for (let i = 0; i < trend.length - 1; i++) {
        const p0 = getCoord(i, pointsTotal[i]);
        const p1 = getCoord(i + 1, pointsTotal[i + 1]);
        const cpX = (p0.x + p1.x) / 2;
        ctx.bezierCurveTo(cpX, p0.y, cpX, p1.y, p1.x, p1.y);
      }

      const lastCoord = getCoord(trend.length - 1, pointsTotal[trend.length - 1]);
      ctx.lineTo(lastCoord.x, paddingTop + chartHeight);
      ctx.lineTo(firstCoord.x, paddingTop + chartHeight);
      ctx.closePath();
      ctx.fillStyle = grad;
      ctx.fill();

      // 2. Línea total scans (#25D366)
      ctx.beginPath();
      ctx.moveTo(firstCoord.x, firstCoord.y);
      for (let i = 0; i < trend.length - 1; i++) {
        const p0 = getCoord(i, pointsTotal[i]);
        const p1 = getCoord(i + 1, pointsTotal[i + 1]);
        const cpX = (p0.x + p1.x) / 2;
        ctx.bezierCurveTo(cpX, p0.y, cpX, p1.y, p1.x, p1.y);
      }
      ctx.strokeStyle = '#25D366';
      ctx.lineWidth = 2.5;
      ctx.lineCap = 'round';
      ctx.stroke();

      // 3. Línea login scans (#0284c7)
      ctx.beginPath();
      const firstLoginCoord = getCoord(0, pointsLogin[0]);
      ctx.moveTo(firstLoginCoord.x, firstLoginCoord.y);
      for (let i = 0; i < trend.length - 1; i++) {
        const p0 = getCoord(i, pointsLogin[i]);
        const p1 = getCoord(i + 1, pointsLogin[i + 1]);
        const cpX = (p0.x + p1.x) / 2;
        ctx.bezierCurveTo(cpX, p0.y, cpX, p1.y, p1.x, p1.y);
      }
      ctx.strokeStyle = '#0284c7';
      ctx.lineWidth = 2;
      ctx.stroke();

      // Etiquetas eje X (cada 5 días)
      ctx.fillStyle = '#94a3b8';
      ctx.font = '10px sans-serif';
      ctx.textAlign = 'center';
      for (let i = 0; i < trend.length; i += 5) {
        const c = getCoord(i, 0);
        ctx.fillText(trend[i].label || '', c.x, height - 10);
      }

      // Interactividad con tooltip
      const $canvas = $(canvas);
      const $tooltip = $('#whatauth-chart-tooltip');

      $canvas.off('mousemove mouseleave').on('mousemove', function (e) {
        const mouseX = e.offsetX;
        const rawIdx = Math.round((mouseX - paddingLeft) / stepX);
        const idx = Math.max(0, Math.min(trend.length - 1, rawIdx));
        const d = trend[idx];
        const total = (d.loginScans || 0) + (d.pixelScans || 0) + (d.printScans || 0);

        const pos = getCoord(idx, total);
        $tooltip
          .html('<strong>' + (d.label || d.date) + '</strong><br>' +
                '<span style="color:#25D366;">●</span> ' + (whatauthAdminData.i18n.scansTotal || 'Scans Totales') + ': ' + total + '<br>' +
                '<span style="color:#0284c7;">●</span> ' + (whatauthAdminData.i18n.loginScans || 'Login') + ': ' + (d.loginScans || 0))
          .css({
            left: pos.x + 'px',
            top: pos.y + 'px',
            display: 'block'
          });
      }).on('mouseleave', function () {
        $tooltip.hide();
      });
    },

    renderDashboardLogs: function (qrLogs, recentAccess) {
      const $tbody = $('#whatauth-dashboard-logs-body');
      let combined = [];

      if (Array.isArray(qrLogs) && qrLogs.length) {
        combined = combined.concat(qrLogs);
      }
      if (Array.isArray(recentAccess) && recentAccess.length) {
        recentAccess.forEach(acc => {
          combined.push({
            id: acc.id,
            type: 'LOGIN',
            title: acc.domain || 'Acceso wp-login',
            device: [acc.city, acc.country].filter(Boolean).join(', ') || acc.userAgent || acc.remoteAddress || 'Navegador Web',
            verifiedAt: acc.authenticatedAt || acc.createdAt,
            status: acc.status || 'SUCCESS'
          });
        });
      }

      // Ordenar por fecha descendente
      combined.sort((a, b) => {
        const da = new Date(a.verifiedAt || a.createdAt || 0).getTime();
        const db = new Date(b.verifiedAt || b.createdAt || 0).getTime();
        return db - da;
      });

      if (!combined.length) {
        $tbody.html('<tr><td colspan="5" style="text-align:center; padding:28px; color:#888;">' + (whatauthAdminData.i18n.noLogsFound || 'No hay escaneos registrados aún. Cuando un usuario escanee un QR de WhatAuth, la actividad se mostrará aquí en tiempo real.') + '</td></tr>');
        return;
      }

      let html = '';
      const displayLogs = combined.slice(0, 15);
      displayLogs.forEach(log => {
        const dateStr = log.verifiedAt || log.createdAt;
        let formattedDate = '—';
        if (dateStr) {
          try {
            const d = new Date(dateStr);
            formattedDate = d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
          } catch (e) {
            formattedDate = dateStr;
          }
        }

        const type = (log.type || 'LOGIN').toUpperCase();
        let badgeClass = 'whatauth-badge-starter';
        if (type === 'PIXEL') badgeClass = 'whatauth-badge-pro';
        if (type === 'PRINT') badgeClass = 'whatauth-badge-agency';

        const campaign = log.title || log.clientBranding?.name || log.name || ('QR #' + (log.qrId || log.id || ''));
        const device = log.device || [log.city, log.country].filter(Boolean).join(', ') || log.remoteAddress || 'WhatsApp Web';

        html += '<tr>';
        html += '<td style="color:#64748b; font-size:12px; font-weight:500;">' + formattedDate + '</td>';
        html += '<td><span class="whatauth-badge ' + badgeClass + '">' + type + '</span></td>';
        html += '<td><strong>' + this.escapeHtml(campaign) + '</strong></td>';
        html += '<td style="color:#475569; font-size:12px;">' + this.escapeHtml(device) + '</td>';
        html += '<td style="text-align:right;"><span class="whatauth-badge whatauth-badge-active">' + (whatauthAdminData.i18n.verifiedBadge || 'Verificado ✓') + '</span></td>';
        html += '</tr>';
      });

      $tbody.html(html);
    },

    updateDashboardStats: function () {
      const total = this.qrs.length;
      const logins = this.qrs.filter(q => (q.type || '').toUpperCase() === 'LOGIN').length;
      const pixels = this.qrs.filter(q => (q.type || '').toUpperCase() === 'PIXEL').length;
      const prints = this.qrs.filter(q => (q.type || '').toUpperCase() === 'PRINT').length;

      $('#stat-total-qrs').text(total);
      $('#stat-login-qrs').text(logins);
      $('#stat-pixel-qrs').text(pixels);
      $('#stat-print-qrs').text(prints);
    },

    handleLineSelection: function () {
      const self = this;
      const selectedLineId = $('#wa-field-line-id').val();
      const isProAgency = Boolean(whatauthAdminData.isProAgency);
      const $notice = $('#wa-autoreply-hostbot-notice');
      const $autoreplyCheck = $('#wa-field-autoreply-enabled');

      if (!selectedLineId) {
        // Bot anfitrión oficial: NO Auto-Reply
        $notice.slideDown(150);
        $autoreplyCheck.prop('checked', false).prop('disabled', true);
        self.toggleAutoReplyControls(false);
      } else {
        // Línea dedicada de WhatsApp
        $notice.slideUp(150);
        if (isProAgency) {
          $autoreplyCheck.prop('disabled', false);
          self.toggleAutoReplyControls($autoreplyCheck.is(':checked'));
        } else {
          $autoreplyCheck.prop('checked', false).prop('disabled', true);
          self.toggleAutoReplyControls(false);
        }
      }
      self.updatePreviewBubble($('#wa-field-autoreply-text').val());
      self.updateStudioQrPreview();
    },

    toggleAutoReplyControls: function (enabled) {
      const isProAgency = Boolean(whatauthAdminData.isProAgency);
      const canEdit = enabled && isProAgency;

      $('#wa-field-autoreply-text').prop('readonly', !canEdit);
      if (canEdit) {
        $('#wa-field-autoreply-text').removeAttr('style');
        $('.wa-media-pill-btn').prop('disabled', false).css({ opacity: 1, pointerEvents: 'auto' });
        $('#wa-field-autoreply-img-url, #wa-field-autoreply-audio-url, #wa-field-autoreply-doc-url, #wa-field-autoreply-doc-name, #wa-field-autoreply-url').prop('disabled', false);
        $('#btn-upload-autoreply-img, #btn-upload-autoreply-audio, #btn-upload-autoreply-doc').prop('disabled', false);
      } else {
        $('#wa-field-autoreply-text').css({ background: '#f3f4f6', cursor: 'not-allowed' });
        $('.wa-media-pill-btn').prop('disabled', true).css({ opacity: 0.6, pointerEvents: 'none' });
        $('#wa-field-autoreply-img-url, #wa-field-autoreply-audio-url, #wa-field-autoreply-doc-url, #wa-field-autoreply-doc-name, #wa-field-autoreply-url').prop('disabled', true);
        $('#btn-upload-autoreply-img, #btn-upload-autoreply-audio, #btn-upload-autoreply-doc').prop('disabled', true);
      }
    },

    openQrModal: function (qr = null) {
      const self = this;
      this.applyPlanState(whatauthAdminData.plan, whatauthAdminData.lines, whatauthAdminData.defaultLineId);

      $('#wa-modal-title').text(qr ? ((whatauthAdminData.i18n.editQr || 'Editar Código QR: ') + (qr.title || qr.name)) : (whatauthAdminData.i18n.createQr || 'Crear Nuevo Código QR'));
      $('#wa-field-qr-id').val(qr ? qr.id : '');
      $('#wa-field-title').val(qr ? (qr.title || qr.name) : '');
      $('#wa-field-type').val(qr ? (qr.type || 'LOGIN').toUpperCase() : 'LOGIN').trigger('change');
      $('#wa-field-prefilled').val(qr ? (qr.prefilledMessage || qr.text || '') : '');

      // Poblar selector de línea de WhatsApp en el modal
      let lineOptions = '<option value="">' + (whatauthAdminData.i18n.officialHostBot || 'Bot Anfitrión Oficial (+34 631 490 990 - Starter / Básico)') + '</option>';
      const availableLines = Array.isArray(whatauthAdminData.lines) ? whatauthAdminData.lines : [];
      let targetLineId = qr ? (qr.lineId || qr.whatsappInstanceId || '') : (whatauthAdminData.defaultLineId || '');

      availableLines.forEach(function (l) {
        const isSelected = (l.id === targetLineId) ? ' selected' : '';
        lineOptions += '<option value="' + l.id + '"' + isSelected + '>' + (l.name || l.phoneNumber) + ' (' + l.provider + ')</option>';
      });
      $('#wa-field-line-id').html(lineOptions);
      if (targetLineId) {
        $('#wa-field-line-id').val(targetLineId);
      }

      // Datos de Auto-Reply
      const arEnabled = qr ? Boolean(qr.autoReply?.enabled ?? (qr.autoReply?.content || qr.autoReply?.text)) : false;
      $('#wa-field-autoreply-enabled').prop('checked', arEnabled);
      $('#wa-field-autoreply-mode').val(qr && qr.autoReply?.mode ? qr.autoReply.mode : 'TEXT');
      const loadedArText = qr && (qr.autoReply?.content || qr.autoReply?.text) ? (qr.autoReply.content || qr.autoReply.text) : '';
      $('#wa-field-autoreply-text').val(loadedArText);
      $('#wa-composer-counter').text(loadedArText.length + ' ' + (whatauthAdminData.i18n.characters || 'caracteres'));
      $('#wa-field-autoreply-img-url').val(qr?.autoReply?.image || '');
      $('#wa-field-autoreply-audio-url').val(qr?.autoReply?.audio || '');
      $('#wa-field-autoreply-doc-url').val(qr?.autoReply?.document || '');
      $('#wa-field-autoreply-doc-name').val(qr?.autoReply?.documentName || '');
      $('#wa-field-autoreply-url').val(qr?.autoReply?.url || '');

      // Activar la píldora multimedia adecuada
      $('.wa-media-pill-btn').removeClass('active');
      $('.wa-media-box').hide();
      if (qr?.autoReply?.document) {
        $('.wa-media-pill-btn[data-media="document"]').addClass('active');
        $('#wa-media-box-document').show();
      } else if (qr?.autoReply?.audio) {
        $('.wa-media-pill-btn[data-media="audio"]').addClass('active');
        $('#wa-media-box-audio').show();
      } else if (qr?.autoReply?.image) {
        $('.wa-media-pill-btn[data-media="image"]').addClass('active');
        $('#wa-media-box-image').show();
      } else {
        $('.wa-media-pill-btn[data-media="none"]').addClass('active');
      }

      // Webhook saliente
      $('#wa-field-webhook-url').val(qr && qr.webhook?.url ? qr.webhook.url : '');
      $('#wa-field-webhook-enabled').prop('checked', qr ? Boolean(qr.webhook?.enabled) : false);

      // Campos PRINT
      $('#wa-field-print-address').val(qr?.address || '');
      $('#wa-field-print-city').val(qr?.city || '');
      $('#wa-field-print-country').val(qr?.country || '');

      // Campos de Branding / Estilo de QR
      $('#wa-field-qr-logo').val(qr?.branding?.logoUrl || qr?.branding?.logo || '');
      $('#wa-field-qr-logo-margin').val(qr?.branding?.logoMargin ?? 6);
      $('#wa-field-qr-logo-scale').val(qr?.branding?.logoSize ?? qr?.branding?.logoScale ?? 0.22);
      $('#wa-field-qr-color-dots').val(qr?.branding?.dotsColor || '#1a2517');
      $('#wa-field-qr-shape-dots').val(qr?.branding?.dotsType || qr?.branding?.dotsShape || 'square');
      $('#wa-field-qr-ec-level').val(qr?.branding?.errorCorrectionLevel || 'Q');
      $('#wa-field-qr-color-corners').val(qr?.branding?.cornersSquareColor || qr?.branding?.cornersColor || '#1a2517');
      $('#wa-field-qr-shape-corners').val(qr?.branding?.cornersSquareType || qr?.branding?.cornersShape || 'extra-rounded');
      $('#wa-field-qr-color-corner-dot').val(qr?.branding?.cornersDotColor || '#6f8065');
      $('#wa-field-qr-shape-corner-dot').val(qr?.branding?.cornersDotType || 'dot');
      $('#wa-field-qr-color-bg').val(qr?.branding?.bgColor || '#ffffff');

      // Resetear sub-pestañas a la primera
      $('.whatauth-studio-tab-btn').removeClass('active');
      $('.whatauth-studio-tab-btn[data-studio-pane="pane-content"]').addClass('active');
      $('.whatauth-studio-pane').removeClass('active');
      $('#pane-content').addClass('active');

      // Evaluar estado de la línea y auto-reply
      this.handleLineSelection();
      $('#whatauth-qr-modal').addClass('is-open');

      // Generar o refrescar la previsualización del QR en vivo
      this.updateStudioQrPreview();
    },

    closeQrModal: function () {
      $('#whatauth-qr-modal').removeClass('is-open');
    },

    updateStudioQrPreview: function () {
      const self = this;
      const $container = $('#wa-studio-qr-container');
      if (!$container.length) return;

      const prefilled = $('#wa-field-prefilled').val() || 'Acceso WhatAuth';
      const selectedLineId = $('#wa-field-line-id').val();
      
      // Determinar el número de WhatsApp de destino (Línea dedicada o Bot Anfitrión)
      let linePhone = '34631490990';
      if (selectedLineId && Array.isArray(whatauthAdminData.lines)) {
        const found = whatauthAdminData.lines.find(l => l.id === selectedLineId);
        if (found && found.phoneNumber) {
          linePhone = String(found.phoneNumber).replace(/[^0-9]/g, '');
        }
      }

      // Construir WaLink
      const waLink = 'https://wa.me/' + linePhone + '?text=' + encodeURIComponent(prefilled);
      $('#btn-studio-test-qr').attr('href', waLink);

      const qrData = waLink;
      const isProAgency = Boolean(whatauthAdminData.isProAgency);

      const logoUrl = (isProAgency && $('#wa-field-qr-logo').val()) ? $('#wa-field-qr-logo').val() : (whatauthAdminData.defaultLogo || '');
      const logoMargin = isProAgency ? (parseInt($('#wa-field-qr-logo-margin').val(), 10) || 6) : 6;
      const logoScale = isProAgency ? (parseFloat($('#wa-field-qr-logo-scale').val()) || 0.22) : 0.22;
      const dotsColor = isProAgency ? ($('#wa-field-qr-color-dots').val() || '#1a2517') : '#1a2517';
      const dotsShape = isProAgency ? ($('#wa-field-qr-shape-dots').val() || 'square') : 'square';
      const ecLevel = isProAgency ? ($('#wa-field-qr-ec-level').val() || 'Q') : 'Q';
      const cornersSquareColor = isProAgency ? ($('#wa-field-qr-color-corners').val() || '#1a2517') : '#1a2517';
      const cornersSquareShape = isProAgency ? ($('#wa-field-qr-shape-corners').val() || 'extra-rounded') : 'extra-rounded';
      const cornersDotColor = isProAgency ? ($('#wa-field-qr-color-corner-dot').val() || '#6f8065') : '#6f8065';
      const cornersDotShape = isProAgency ? ($('#wa-field-qr-shape-corner-dot').val() || 'dot') : 'dot';
      const bgColor = isProAgency ? ($('#wa-field-qr-color-bg').val() || '#ffffff') : '#ffffff';

      if (typeof window.QRCodeStyling !== 'undefined') {
        const qrConfig = {
          width: 220,
          height: 220,
          data: qrData,
          image: logoUrl,
          qrOptions: {
            errorCorrectionLevel: ecLevel
          },
          dotsOptions: {
            color: dotsColor,
            type: dotsShape
          },
          cornersSquareOptions: {
            color: cornersSquareColor,
            type: cornersSquareShape
          },
          cornersDotOptions: {
            color: cornersDotColor,
            type: cornersDotShape
          },
          backgroundOptions: {
            color: bgColor
          },
          imageOptions: {
            crossOrigin: 'anonymous',
            margin: logoMargin,
            imageSize: logoScale
          }
        };

        if (!self.qrStylingInstance) {
          $container.empty();
          self.qrStylingInstance = new window.QRCodeStyling(qrConfig);
          self.qrStylingInstance.append($container[0]);
        } else {
          self.qrStylingInstance.update(qrConfig);
        }
      } else {
        $container.html('<div style="color:#64748b; font-size:12px; padding:20px;">' + (whatauthAdminData.i18n.loadingEngine || 'Cargando motor de QR...') + '</div>');
      }
    },

    updatePreviewBubble: function (rawText) {
      const isEnabled = $('#wa-field-autoreply-enabled').is(':checked');
      const hasLine = Boolean($('#wa-field-line-id').val());
      const activeMedia = $('.wa-media-pill-btn.active').data('media') || 'none';
      const imgUrl = $('#wa-field-autoreply-img-url').val();
      const audioUrl = $('#wa-field-autoreply-audio-url').val();
      const docUrl = $('#wa-field-autoreply-doc-url').val();
      const docName = $('#wa-field-autoreply-doc-name').val() || 'documento.pdf';
      const ctaUrl = $('#wa-field-autoreply-url').val();

      if (!hasLine) {
        $('#wa-autoreply-preview-box').html('<em style="color:#64748b;">' + (whatauthAdminData.i18n.previewHostBotNotice || 'El Bot Anfitrión Oficial no emite Auto-Reply. Selecciona una línea propia para configurar respuestas automáticas.') + '</em>');
        return;
      }

      if (!isEnabled) {
        $('#wa-autoreply-preview-box').html('<em style="color:#64748b;">' + (whatauthAdminData.i18n.previewInactive || 'Respuesta automática inactiva. Marca la casilla "Activar Respuesta Automática" superior para habilitarla.') + '</em>');
        return;
      }

      let contentHtml = '';

      // 1. Adjunto de Imagen
      if (activeMedia === 'image' && imgUrl) {
        contentHtml += '<div class="wa-bubble-image-preview"><img src="' + this.escapeHtml(imgUrl) + '" alt="Imagen adjunta" onerror="this.style.display=\'none\'"></div>';
      }

      // 2. Adjunto de Audio (Simulador de nota de voz WhatsApp)
      if (activeMedia === 'audio' && audioUrl) {
        contentHtml += '<div class="wa-bubble-audio-preview">' +
          '<div class="wa-bubble-audio-btn">▶</div>' +
          '<div class="wa-bubble-audio-bar"></div>' +
          '<span style="font-size:11px; color:#475569; font-weight:600;">0:24</span>' +
          '<span style="display:inline-flex;align-items:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="#00a884"><path d="M12 14c1.66 0 2.99-1.34 2.99-3L15 5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm5.3-3c0 3-2.54 5.1-5.3 5.1S6.7 14 6.7 11H5c0 3.41 2.72 6.23 6 6.72V21h2v-3.28c3.28-.48 6-3.3 6-6.72h-1.7z"/></svg></span>' +
        '</div>';
      }

      // 3. Adjunto de Documento / PDF
      if (activeMedia === 'document' && docUrl) {
        contentHtml += '<div class="wa-bubble-doc-preview">' +
          '<div class="wa-bubble-doc-icon">PDF</div>' +
          '<div style="flex:1; overflow:hidden;">' +
            '<div style="font-weight:600; font-size:12px; white-space:nowrap; text-overflow:ellipsis; overflow:hidden;">' + this.escapeHtml(docName) + '</div>' +
            '<div style="font-size:10px; color:#64748b;">Documento adjunto</div>' +
          '</div>' +
        '</div>';
      }

      // 4. Texto principal con WhatsApp Markdown (*negrita*, _cursiva_, ~tachado~, ```mono```)
      const textToRender = rawText && rawText.trim() ? rawText : (whatauthAdminData.i18n.previewDefaultText || '¡Hola! Gracias por escanear nuestro código QR.');
      const formatted = this.escapeHtml(textToRender)
        .replace(/\*(.*?)\*/g, '<strong>$1</strong>')
        .replace(/_(.*?)_/g, '<em>$1</em>')
        .replace(/~(.*?)~/g, '<del>$1</del>')
        .replace(/```(.*?)```/g, '<code>$1</code>')
        .replace(/\n/g, '<br>');

      contentHtml += '<div>' + formatted + '</div>';

      // 5. Botón CTA interactivo si hay URL
      if (ctaUrl && ctaUrl.trim()) {
        contentHtml += '<a href="' + this.escapeHtml(ctaUrl) + '" target="_blank" rel="noopener noreferrer" class="wa-bubble-cta-btn"><svg style="display:inline-block;vertical-align:-2px;margin-right:4px;" width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>' + (whatauthAdminData.i18n.openLink || 'Abrir enlace') + '</a>';
      }

      // 6. Timestamp en la esquina inferior derecha
      contentHtml += '<div style="text-align:right; font-size:10px; color:#64748b; margin-top:4px;">12:00 <span style="color:#38bdf8;">✓✓</span></div>';

      $('#wa-autoreply-preview-box').html(contentHtml);
    },

    saveQr: function () {
      const self = this;
      const $btn = $('#btn-save-qr-modal');
      const title = $('#wa-field-title').val();

      if (!title) {
        self.showToast(whatauthAdminData.i18n.titleRequired || 'El título del código QR es obligatorio.', 'error');
        return;
      }

      $btn.prop('disabled', true).text(whatauthAdminData.i18n.saving);

      const postData = {
        action: 'whatauth_save_qr',
        nonce: whatauthAdminData.nonce,
        id: $('#wa-field-qr-id').val(),
        title: title,
        type: $('#wa-field-type').val(),
        lineId: $('#wa-field-line-id').val() || '',
        prefilledMessage: $('#wa-field-prefilled').val(),
        autoReplyEnabled: $('#wa-field-autoreply-enabled').is(':checked') ? 1 : 0,
        autoReplyMode: $('#wa-field-autoreply-mode').val() || 'TEXT',
        autoReplyContent: $('#wa-field-autoreply-text').val() || '',
        autoReplyImage: $('#wa-field-autoreply-img-url').val() || '',
        autoReplyAudio: $('#wa-field-autoreply-audio-url').val() || '',
        autoReplyDocument: $('#wa-field-autoreply-doc-url').val() || '',
        autoReplyDocumentName: $('#wa-field-autoreply-doc-name').val() || '',
        autoReplyUrl: $('#wa-field-autoreply-url').val() || '',
        webhookUrl: $('#wa-field-webhook-url').val() || '',
        webhookEnabled: $('#wa-field-webhook-enabled').is(':checked') ? 1 : 0,
        address: $('#wa-field-print-address').val() || '',
        city: $('#wa-field-print-city').val() || '',
        country: $('#wa-field-print-country').val() || '',
        logoUrl: $('#wa-field-qr-logo').val() || '',
        logoMargin: $('#wa-field-qr-logo-margin').val() || 6,
        logoSize: $('#wa-field-qr-logo-scale').val() || 0.22,
        dotsColor: $('#wa-field-qr-color-dots').val() || '#1a2517',
        dotsType: $('#wa-field-qr-shape-dots').val() || 'square',
        errorCorrectionLevel: $('#wa-field-qr-ec-level').val() || 'Q',
        cornersSquareColor: $('#wa-field-qr-color-corners').val() || '#1a2517',
        cornersSquareType: $('#wa-field-qr-shape-corners').val() || 'extra-rounded',
        cornersDotColor: $('#wa-field-qr-color-corner-dot').val() || '#6f8065',
        cornersDotType: $('#wa-field-qr-shape-corner-dot').val() || 'dot',
        bgColor: $('#wa-field-qr-color-bg').val() || '#ffffff'
      };

      $.ajax({
        url: whatauthAdminData.ajaxUrl,
        type: 'POST',
        dataType: 'json',
        data: postData,
        success: function (res) {
          $btn.prop('disabled', false).text(whatauthAdminData.i18n.saveQr || 'Guardar Código QR');
          if (res.success) {
            self.showToast(whatauthAdminData.i18n.saved, 'success');
            self.closeQrModal();
            self.loadQrs();
          } else {
            self.showToast(res.data?.message || (whatauthAdminData.i18n.errorSaving || 'Error guardando QR'), 'error');
          }
        },
        error: function () {
          $btn.prop('disabled', false).text(whatauthAdminData.i18n.saveQr || 'Guardar Código QR');
          self.showToast(whatauthAdminData.i18n.errorGeneric, 'error');
        }
      });
    },

    deleteQr: function (id) {
      const self = this;
      if (!confirm(whatauthAdminData.i18n.confirmDelete)) {
        return;
      }

      $.ajax({
        url: whatauthAdminData.ajaxUrl,
        type: 'POST',
        dataType: 'json',
        data: {
          action: 'whatauth_delete_qr',
          nonce: whatauthAdminData.nonce,
          id: id
        },
        success: function (res) {
          if (res.success) {
            self.showToast(whatauthAdminData.i18n.qrDeleted || 'Código QR eliminado correctamente.', 'success');
            self.loadQrs();
          } else {
            self.showToast(res.data?.message || (whatauthAdminData.i18n.errorDeleting || 'Error al eliminar'), 'error');
          }
        },
        error: function () {
          self.showToast(whatauthAdminData.i18n.errorGeneric, 'error');
        }
      });
    },

    applyPlanState: function (plan, lines, activeLineId) {
      const isPro = (plan === 'PRO' || plan === 'AGENCY');
      whatauthAdminData.plan = plan;
      whatauthAdminData.isProAgency = isPro;

      if (lines && Array.isArray(lines)) {
        whatauthAdminData.lines = lines;
      }
      if (activeLineId) {
        whatauthAdminData.defaultLineId = activeLineId;
      }

      // 1. Selector de líneas y auto-selección inteligente en Ajustes
      if ($('#whatauth-line-select').length) {
        if (lines && lines.length) {
          let options = '<option value="">' + (whatauthAdminData.i18n.officialHostBot || 'Bot Anfitrión Oficial (+34 631 490 990 - Starter)') + '</option>';
          let selectedId = activeLineId;
          if (!selectedId && isPro && lines.length) {
            selectedId = lines[0].id;
          }
          lines.forEach(function (line) {
            const isSelected = (line.id === selectedId) ? ' selected' : '';
            options += '<option value="' + line.id + '"' + isSelected + '>' + (line.name || line.phoneNumber) + ' (' + line.provider + ')</option>';
          });
          $('#whatauth-line-select').html(options);
          if (selectedId) {
            $('#whatauth-line-select').val(selectedId);
          }
        } else if (activeLineId) {
          $('#whatauth-line-select').val(activeLineId);
        }
      }

      // 2. Desbloquear o bloquear campos en la vista de Ajustes
      if (isPro) {
        $('.wa-plan-starter-view').hide();
        $('.wa-plan-pro-view').show();
        $('#whatauth-instruction-text').prop('readonly', false).removeAttr('style');
        $('#whatauth-btn-text').prop('readonly', false).removeAttr('style');
        $('#whatauth-btn-bg-color, #whatauth-btn-txt-color, #whatauth-custom-logo').prop('disabled', false);
      } else {
        $('.wa-plan-starter-view').show();
        $('.wa-plan-pro-view').hide();
        $('#whatauth-instruction-text').prop('readonly', true).css({ background: '#f3f4f6', color: '#6b7280', cursor: 'not-allowed' });
        $('#whatauth-btn-text').prop('readonly', true).css({ background: '#f3f4f6', color: '#6b7280', cursor: 'not-allowed' });
      }

      // 3. Desbloquear pestañas y controles en el Modal Studio de QRs
      if (isPro) {
        $('.wa-tab-lock').hide();
        $('.wa-studio-lock-banner').hide();
        $('.wa-studio-pro-controls').css('display', 'flex');
        $('#btn-upload-qr-logo').show();
        $('#wa-field-qr-logo').prop('readonly', false).removeAttr('style');
        $('#wa-field-qr-logo-margin, #wa-field-qr-logo-scale, #wa-field-qr-color-dots, #wa-field-qr-shape-dots, #wa-field-qr-ec-level, #wa-field-qr-color-corners, #wa-field-qr-shape-corners, #wa-field-qr-color-corner-dot, #wa-field-qr-shape-corner-dot, #wa-field-qr-color-bg').prop('disabled', false);
      } else {
        $('.wa-tab-lock').show();
        $('.wa-studio-lock-banner').show();
        $('.wa-studio-pro-controls').hide();
        $('#wa-field-qr-logo').prop('readonly', true).css({ background: '#f3f4f6' });
        $('#wa-field-qr-logo-margin, #wa-field-qr-logo-scale, #wa-field-qr-color-dots, #wa-field-qr-shape-dots, #wa-field-qr-ec-level, #wa-field-qr-color-corners, #wa-field-qr-shape-corners, #wa-field-qr-color-corner-dot, #wa-field-qr-shape-corner-dot, #wa-field-qr-color-bg').prop('disabled', true);
      }
    },

    testConnection: function () {
      const self = this;
      const $btn = $('#btn-test-token');
      const apiKey = $('#whatauth-api-key').val();
      const apiUrl = $('#whatauth-api-url').val();
      const $status = $('#whatauth-test-status');

      if (!apiKey) {
        self.showToast(whatauthAdminData.i18n.enterToken || 'Introduce tu Service Token de WhatAuth.', 'error');
        return;
      }

      $btn.prop('disabled', true);
      $status.html('<span style="color:#888;">' + whatauthAdminData.i18n.verifying + '</span>');

      $.ajax({
        url: whatauthAdminData.ajaxUrl,
        type: 'POST',
        dataType: 'json',
        data: {
          action: 'whatauth_verify_token',
          nonce: whatauthAdminData.nonce,
          api_key: apiKey,
          api_url: apiUrl
        },
        success: function (res) {
          $btn.prop('disabled', false);
          if (res.success && res.data) {
            const data = res.data.data || {};
            const plan = res.data.plan || data.plan || 'STARTER';
            const user = data.profile || {};
            const lines = res.data.lines || data.lines || [];
            const activeLineId = res.data.active_line_id || '';

            $status.html('<span style="color:#15803d; font-weight:600;">' + (whatauthAdminData.i18n.connectedPlan || '✓ Conectado: Plan') + ' ' + plan + ' (' + (user.name || user.phone || 'OK') + ')</span>');
            self.showToast(whatauthAdminData.i18n.verifiedSuccess, 'success');

            // Guardar o actualizar campo oculto de plan
            if (!$('#whatauth-plan-hidden').length) {
              $('<input>').attr({
                type: 'hidden',
                id: 'whatauth-plan-hidden',
                name: 'plan',
                value: plan
              }).appendTo('#whatauth-settings-form');
            } else {
              $('#whatauth-plan-hidden').val(plan);
            }

            // Aplicar reactivamente el estado y desbloqueo PRO
            self.applyPlanState(plan, lines, activeLineId);

            // Si el campo de admin_phone está vacío y la API devuelve el teléfono del usuario, rellenarlo automáticamente
            if (user.phone && $('#whatauth-admin-phone').length) {
              const currentAdminPhone = $('#whatauth-admin-phone').val().trim();
              if (!currentAdminPhone) {
                $('#whatauth-admin-phone').val(user.phone.replace(/[^0-9]/g, ''));
              }
            }
          } else {
            $status.html('<span style="color:#b91c1c; font-weight:600;">✗ ' + (res.data?.message || whatauthAdminData.i18n.verifiedFailed) + '</span>');
            self.showToast(res.data?.message || whatauthAdminData.i18n.verifiedFailed, 'error');
          }
        },
        error: function () {
          $btn.prop('disabled', false);
          $status.html('<span style="color:#b91c1c; font-weight:600;">✗ ' + (whatauthAdminData.i18n.apiConnectionError || 'Error de conexión con la API') + '</span>');
          self.showToast(whatauthAdminData.i18n.errorGeneric, 'error');
        }
      });
    },

    saveSettings: function () {
      const self = this;
      const $form = $('#whatauth-settings-form');
      const $btn = $form.find('button[type="submit"]');

      $btn.prop('disabled', true).text(whatauthAdminData.i18n.saving);

      const formData = $form.serializeArray();
      formData.push({ name: 'action', value: 'whatauth_save_settings' });
      formData.push({ name: 'nonce', value: whatauthAdminData.nonce });

      $.ajax({
        url: whatauthAdminData.ajaxUrl,
        type: 'POST',
        dataType: 'json',
        data: formData,
        success: function (res) {
          $btn.prop('disabled', false).text(whatauthAdminData.i18n.saveChanges || 'Guardar Cambios');
          if (res.success) {
            self.showToast(whatauthAdminData.i18n.saved, 'success');
            if (res.data && res.data.settings) {
              const savedPlan = res.data.settings.plan || 'STARTER';
              const savedLine = res.data.settings.line_id || '';
              self.applyPlanState(savedPlan, null, savedLine);
            }
          } else {
            self.showToast(res.data?.message || (whatauthAdminData.i18n.errorSaving || 'Error guardando ajustes'), 'error');
          }
        },
        error: function () {
          $btn.prop('disabled', false).text(whatauthAdminData.i18n.saveChanges || 'Guardar Cambios');
          self.showToast(whatauthAdminData.i18n.errorGeneric, 'error');
        }
      });
    },

    escapeHtml: function (string) {
      if (!string) return '';
      return String(string)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }
  };

  $(document).ready(function () {
    App.init();
  });

})(jQuery);

