/**
 * WhatAuth Frontend JavaScript SDK Wrapper para WordPress
 */

(function ($) {
  'use strict';

  const WhatAuthFrontend = {
    socket: null,
    currentSessionId: null,
    targetRedirect: null,
    pollTimer: null,

    init: function () {
      this.bindEvents();

      // Si existe el contenedor inline en wp-login.php, iniciar flujo inmediatamente
      if ($('#whatauth-inline-login').length) {
        this.targetRedirect = window.location.href;
        this.startInlineAuthFlow();
      }
    },

    bindEvents: function () {
      const self = this;

      $(document).on('click', '.whatauth-trigger-btn:not(#wa-inline-walink-btn)', function (e) {
        // Si no es el botón directo de WaLink, abre modal o maneja click
        if ($(this).attr('id') === 'wa-inline-walink-btn' || $(this).attr('href')) {
          return; // Dejar que el enlace nativo abra WhatsApp
        }
        e.preventDefault();
        self.targetRedirect = $(this).data('redirect') || window.location.href;
        self.openModal();
      });

      $(document).on('click', '.whatauth-front-modal-close, .whatauth-front-modal-backdrop', function (e) {
        if (e.target === this) {
          self.closeModal();
        }
      });
    },

    startInlineAuthFlow: function () {
      const self = this;
      $('#wa-inline-qr-loading').show().text(whatauthData.i18n.generatingQr || 'Generando QR WhatAuth...');
      $('#wa-inline-qr-img').hide();
      $('#wa-inline-pin-container').hide();
      $('#wa-inline-status').text(whatauthData.i18n.waitingScan);

      this.ensureSocketIO(function () {
        self.connectSocket();
      });
    },

    ensureModal: function () {
      let $backdrop = $('#whatauth-front-modal-backdrop');
      if ($backdrop.length) return $backdrop;

      const i18n = whatauthData.i18n;

      const modalHtml = `
        <div id="whatauth-front-modal-backdrop" class="whatauth-front-modal-backdrop">
          <div class="whatauth-front-modal">
            <button type="button" class="whatauth-front-modal-close">&times;</button>
            <div style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 8px;">
              <img src="${whatauthData.logoUrl || ''}" alt="WhatAuth" style="width: 28px; height: 28px; object-fit: contain;">
              <h3 style="margin: 0; font-size: 18px; color: #111827; font-weight: 700;">${i18n.modalTitle}</h3>
            </div>
            <p style="font-size: 13px; color: #6b7280; margin: 0 0 14px 0;">${i18n.scanInstruction}</p>

            <div class="whatauth-qr-container">
              <div id="wa-qr-loading" style="width: 200px; height: 200px; display: flex; align-items: center; justify-content: center; font-size: 13px; color: #9ca3af;">
                ${i18n.loadingQr || 'Cargando código QR...'}
              </div>
              <img id="wa-qr-img" class="whatauth-qr-image" style="display: none; width: 200px; height: 200px;" alt="QR Code">
            </div>

            <div id="wa-pin-container" style="display: none;">
              <div class="whatauth-pin-box">
                PIN: <span id="wa-pin-value" style="color: #25D366; font-weight: 700;"></span>
              </div>
            </div>

            <a id="wa-mobile-btn" href="#" target="_blank" rel="noopener noreferrer" class="whatauth-mobile-direct-btn">
              <img src="${whatauthData.logoUrl || ''}" alt="WhatAuth" style="width: 20px; height: 20px; object-fit: contain; margin-right: 6px;">
              <span>${i18n.openWhatsApp}</span>
            </a>

            <div id="wa-status-text" class="whatauth-status-text">${i18n.waitingScan}</div>

            ${(whatauthData.plan === 'STARTER' || !whatauthData.lineId) ? `
              <div class="whatauth-modal-branding-footer" style="margin-top: 14px; padding-top: 10px; border-top: 1px dashed #e5e7eb;">
                <a href="https://whatauth.com?utm_source=wp_plugin&utm_medium=modal_footer&utm_campaign=powered_by" target="_blank" rel="noopener noreferrer" style="font-size: 11px; color: #16a34a; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-weight: 600;">
                  <img src="${whatauthData.logoUrl || ''}" alt="WhatAuth" style="width: 14px; height: 14px; object-fit: contain;">
                  <span>${i18n.brandingBadge || 'Crea tu login con QR con Whatauth.com'}</span>
                </a>
              </div>
            ` : ''}
          </div>
        </div>
      `;

      $backdrop = $(modalHtml).appendTo('body');
      return $backdrop;
    },

    openModal: function () {
      const $backdrop = this.ensureModal();
      $backdrop.addClass('is-open');
      this.startAuthFlow();
    },

    closeModal: function () {
      $('#whatauth-front-modal-backdrop').removeClass('is-open');
      if (this.socket) {
        this.socket.disconnect();
        this.socket = null;
      }
      if (this.pollTimer) {
        clearInterval(this.pollTimer);
        this.pollTimer = null;
      }
    },

    startAuthFlow: function () {
      const self = this;
      $('#wa-qr-loading').show().text('Generando sesión WhatAuth...');
      $('#wa-qr-img').hide();
      $('#wa-pin-container').hide();
      $('#wa-status-text').text(whatauthData.i18n.waitingScan);

      // Cargar Socket.IO si aún no está en la página
      this.ensureSocketIO(function () {
        self.connectSocket();
      });
    },

    ensureSocketIO: function (callback) {
      if (typeof window.io === 'function') {
        return callback();
      }
      const script = document.createElement('script');
      script.src = 'https://cdn.socket.io/4.8.1/socket.io.min.js';
      script.async = true;
      script.onload = callback;
      script.onerror = function () {
        console.error('[WhatAuth] No se pudo cargar Socket.IO');
      };
      document.head.appendChild(script);
    },

    connectSocket: function () {
      const self = this;
      const apiUrl = whatauthData.apiUrl || 'https://api.whatauth.com';
      const apiKey = whatauthData.apiKey || '';
      const domain = whatauthData.domain || (typeof window !== 'undefined' ? window.location.hostname : 'localhost');

      try {
        this.socket = window.io(apiUrl, {
          auth: {
            key: apiKey,
            domain: domain,
            origin: (typeof window !== 'undefined' && window.location.origin) ? window.location.origin : ''
          },
          query: {
            domain: domain
          },
          extraHeaders: {
            'x-api-key': apiKey,
            'x-whatauth-domain': domain
          },
          transports: ['websocket', 'polling'],
          reconnection: true,
          reconnectionAttempts: 5,
          timeout: 10000
        });

        this.socket.on('connect', function () {
          console.log('[WhatAuth] Conectado vía Socket. Solicitando QR...');
          // Emitir solicitud de QR Outside oficial
          self.socket.emit('req_outside_qr_auth', {
            key: apiKey,
            domain: domain
          });
          // Fallback de solicitud nativa
          self.socket.emit('req_qr_auth', {
            apiKey: apiKey,
            domain: domain
          });
        });

        // 1. Recepción de QR generado
        this.socket.on('res_outside_qr_auth', function (data) {
          console.log('[WhatAuth] QR recibido (res_outside_qr_auth):', data);
          self.renderQr(data);
        });

        this.socket.on('res_qr_auth', function (data) {
          console.log('[WhatAuth] QR recibido (res_qr_auth):', data);
          self.renderQr(data);
        });

        this.socket.on('qr', function (data) {
          self.renderQr(data);
        });

        // 2. Éxito de verificación tras enviar el mensaje por WhatsApp
        this.socket.on('outside_auth_success', function (data) {
          console.log('[WhatAuth] Autenticado con éxito (outside_auth_success):', data);
          self.handleAuthenticated(data);
        });

        this.socket.on('qr_verified', function (data) {
          self.handleAuthenticated(data);
        });

        this.socket.on('authenticated', function (data) {
          self.handleAuthenticated(data);
        });

        this.socket.on('login_success', function (data) {
          self.handleAuthenticated(data);
        });

        // 3. Manejo de errores
        this.socket.on('outside_auth_error', function (err) {
          console.error('[WhatAuth] Error outside auth:', err);
          $('#wa-qr-loading').html('<span style="color:#b91c1c; font-size:12px;">' + (err.error || 'Error generando código QR. Comprueba la clave o el dominio.') + '</span>');
        });

        this.socket.on('error', function (err) {
          console.error('[WhatAuth] Error socket:', err);
          $('#wa-qr-loading').html('<span style="color:#b91c1c; font-size:12px;">' + (err.message || 'Error en la conexión con el servidor.') + '</span>');
        });

        this.socket.on('disconnect', function (reason) {
          console.log('[WhatAuth] Conexión cerrada:', reason);
        });

        this.socket.on('connect_error', function (err) {
          console.warn('[WhatAuth] Error connect_error:', err);
          $('#wa-qr-loading').text('Error conectando con WhatAuth. Comprueba la configuración.');
        });
      } catch (e) {
        console.error('[WhatAuth] Excepción iniciando Socket:', e);
      }
    },

    renderQr: function (data) {
      const qrDataUrl = data.qrBase64 || data.qrDataUrl || data.qrUrlData || data.qr || data.image;
      const pin = data.pin || data.code;
      const waLink = data.walink || data.link || data.waLink || data.url;

      // Render modal (si está abierto)
      if (qrDataUrl) {
        $('#wa-qr-loading').hide();
        $('#wa-qr-img').attr('src', qrDataUrl).show();

        // Render inline
        $('#wa-inline-qr-loading').hide();
        $('#wa-inline-qr-img').attr('src', qrDataUrl).show();
      }

      if (pin) {
        $('#wa-pin-value').text(pin);
        $('#wa-pin-container').show();

        // Render inline PIN
        $('#wa-inline-pin-value').text(pin);
        $('#wa-inline-pin-container').show();
      }

      if (waLink) {
        $('#wa-mobile-btn').attr('href', waLink);
        $('#wa-inline-walink-btn').attr('href', waLink);
        $('#wa-mobile-card-btn').attr('href', waLink);
      }

      if (whatauthData.buttonBgColor) {
        $('#wa-inline-walink-btn, #wa-mobile-card-btn, #wa-mobile-btn').css('background-color', whatauthData.buttonBgColor);
      }
      if (whatauthData.buttonTextColor) {
        $('#wa-inline-walink-btn, #wa-mobile-card-btn, #wa-mobile-btn').css('color', whatauthData.buttonTextColor);
      }

      $('#wa-status-text').text(whatauthData.i18n.waitingScan);
      $('#wa-inline-status').text(whatauthData.i18n.waitingScan);
      $('#wa-mobile-status').text(whatauthData.i18n.waitingScan);
    },

    handleAuthenticated: function (data) {
      const self = this;
      const ticket = data.token || data.accessToken || data.ticket;
      const phone = data.phone || data.user?.phone || '';
      const name = data.name || data.user?.name || '';

      $('#wa-status-text').html('<span style="color:#15803d; font-weight:600;">' + whatauthData.i18n.successLogin + '</span>');
      $('#wa-inline-status').html('<span style="color:#15803d; font-weight:600;">' + whatauthData.i18n.successLogin + '</span>');
      $('#wa-mobile-status').html('<span style="color:#15803d; font-weight:600;">' + whatauthData.i18n.successLogin + '</span>');

      // Enviar sesión a WordPress REST endpoint
      $.ajax({
        url: whatauthData.restUrl,
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
          token: ticket,
          phone: phone,
          name: name,
          redirectUrl: self.targetRedirect
        }),
        success: function (res) {
          if (res.success) {
            const redirect = res.redirectUrl || self.targetRedirect || window.location.href;
            setTimeout(function () {
              window.location.href = redirect;
            }, 500);
          } else {
            const errMsg = res.error || whatauthData.i18n.errorLogin;
            $('#wa-status-text').html('<span style="color:#b91c1c;">' + errMsg + '</span>');
            $('#wa-inline-status').html('<span style="color:#b91c1c;">' + errMsg + '</span>');
            $('#wa-mobile-status').html('<span style="color:#b91c1c;">' + errMsg + '</span>');
          }
        },
        error: function (xhr) {
          const err = xhr.responseJSON?.error || whatauthData.i18n.errorLogin;
          $('#wa-status-text').html('<span style="color:#b91c1c;">' + err + '</span>');
          $('#wa-inline-status').html('<span style="color:#b91c1c;">' + err + '</span>');
          $('#wa-mobile-status').html('<span style="color:#b91c1c;">' + err + '</span>');
        }
      });
    }
  };

  $(document).ready(function () {
    WhatAuthFrontend.init();
  });

})(jQuery);

