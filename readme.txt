=== WhatAuth – Passwordless WhatsApp Login & Smart QR Hub ===
Contributors: fenixrios, whatauth
Donate link: https://whatauth.com
Tags: whatsapp, login, passwordless, qr code, woocommerce, authentication, security
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Usuarios únicos, trazabilidad en tiempo real, fidelización y confianza: conecta con tu comunidad y las operaciones de tu negocio con autenticación passwordless por WhatsApp y códigos QR de la manera más humana.

== Description ==

WhatAuth para WordPress conecta tu sitio con el ecosistema WhatAuth para ofrecer inicio de sesión sin contraseñas a través de WhatsApp y un centro de gestión de campañas de códigos QR directamente en tu panel de administración. Identifica usuarios únicos reales, asegura la máxima trazabilidad en cada interacción, genera confianza y fortalece la fidelización de tu comunidad de la forma más natural y humana.

### Características Principales:

* **Login Passwordless WhatsApp:** Tus visitantes pueden iniciar sesión o registrarse en 1 segundo escaneando un código QR con su cámara de WhatsApp y enviando un mensaje verificado con PIN o Emoji PIN.
* **Integración Oficial con wp-login.php:** Añade automáticamente el botón de WhatsApp a tu pantalla de inicio de sesión estándar de WordPress.
* **Soporte Nativo para WooCommerce:** Integración en la página de Mi Cuenta y Checkout para agilizar las compras sin fricción de contraseñas.
* **Gestor de Códigos QR Multicampaña:**
  * **LOGIN QR:** Accesos web rápidos y sesiones temporales.
  * **PIXEL QR:** Captura de leads de WhatsApp y redirecciones a landing pages.
  * **PRINT QR:** Cartelería física de alta resolución con GeoTagging nativo y ubicación.
* **Editor de Auto-Reply WYSIWYG:** Configura mensajes automáticos instantáneos con formato de WhatsApp (*negrita*, _cursiva_, ~tachado~, código) y previsualizador en vivo estilo chat de WhatsApp.
* **Webhooks Salientes en Tiempo Real:** Envía eventos automáticos a tus endpoints o herramientas no-code (Make, n8n, Zapier) cada vez que un usuario escanee un QR.
* **Gestión Dinámica de Roles de WordPress:** Define el rol asignado a nuevos registros (Suscriptor, Cliente) y decide qué roles de tu equipo (Administrador, Shop Manager, Editor) pueden gestionar campañas de QR.
* **Bloque Gutenberg y Shortcode:** Inserta el botón o el código QR en cualquier página o maquetador visual (Elementor, Divi) con `[whatauth_login]`.
* **Internacionalización Nativa (i18n):** Adaptación automática al idioma del panel de WordPress (Español e Inglés 100% integrados) con soporte gettext en PHP y JS, y resolución inteligente de dialectos regionales.

== Installation ==

1. Sube la carpeta `whatauth-for-wordpress` al directorio `/wp-content/plugins/` de tu instalación de WordPress, o instala el archivo ZIP desde **Plugins > Añadir nuevo > Subir plugin**.
2. Activa el plugin a través del menú **Plugins** en WordPress.
3. Haz clic en **WhatAuth** en el menú lateral o accede al asistente de configuración en **WhatAuth > Asistente**.
4. Introduce tu **Service Token** (`wst_live_...`) obtenido de tu panel de WhatAuth en [whatauth.com](https://whatauth.com).
5. Selecciona si deseas operar con el **Bot Anfitrión Oficial de WhatAuth** (Starter) o con tu **Línea Dedicada** (PRO / Agency).
6. ¡Listo! El login con WhatsApp y el gestor de QRs estarán activos en tu sitio.

== Frequently Asked Questions ==

= ¿Necesito una cuenta en WhatAuth para usar este plugin? =
Sí. Puedes crear una cuenta gratuita en [whatauth.com](https://whatauth.com) y generar tu Service Token en la sección de Ajustes > API Keys.

= ¿Funciona con el Bot Oficial o necesito mi propia línea de WhatsApp? =
Funciona con ambos. Puedes utilizar el Bot Anfitrión oficial compartido de WhatAuth sin coste de telefonía, o vincular tu propia línea dedicada de WhatsApp en planes PRO o Agency (NATIVE o META_API).

= ¿Es compatible con WooCommerce? =
Sí, WhatAuth inyecta automáticamente el botón de acceso por WhatsApp en la página de Mi Cuenta y en el formulario de inicio de sesión de WooCommerce.

= ¿Qué ocurre si un usuario no tiene cuenta previa en WordPress? =
Si tienes habilitada la opción de auto-registro en los ajustes de WhatAuth, el plugin creará automáticamente una cuenta de usuario asignándole el rol configurado (por ejemplo, Suscriptor o Cliente) vinculando su número de teléfono verificado.

= ¿Puedo restringir quién crea códigos QR en mi equipo? =
Sí, en la pestaña de Ajustes puedes conceder permisos de gestión de QRs a roles específicos de WordPress como Editores o Gestores de la tienda (Shop Managers), reservando la configuración técnica a los Administradores.

== Screenshots ==

1. Resumen de telemetría y estado de conexión en el panel de WordPress.
2. Gestor de códigos QR con pestañas de Login, Pixel y Print.
3. Modal asíncrono con editor WYSIWYG de Auto-Reply y previsualización WhatsApp.
4. Botón de inicio de sesión por WhatsApp en la pantalla oficial wp-login.php.
5. Ajustes generales, selección de telefonía y asignación dinámica de roles.

== Third-Party Services ==

This plugin interfaces with the WhatAuth Cloud SaaS service to verify WhatsApp authentication sessions, generate dynamic QR codes, provide real-time socket updates, and dispatch auto-reply messages.

* Service Provider: WhatAuth (https://whatauth.com)
* API Endpoints: https://api.whatauth.com
* Terms of Service: https://whatauth.com/terms
* Privacy Policy: https://whatauth.com/privacy
* Data Transmitted:
  - Authorized website domain and API Service Token for authentication.
  - Temporary session identifiers and QR tokens.
  - Verified WhatsApp phone numbers during login verification to resolve or create the corresponding WordPress user account.
  - No private WordPress database contents, passwords, or customer metadata are shared or stored externally.

== Disclaimer & Trademarks ==

WhatsApp is a registered trademark of Meta Platforms, Inc. WhatAuth is an independent integration platform and SaaS service. This plugin and WhatAuth are not affiliated with, associated with, authorized by, endorsed by, or in any way officially connected with Meta Platforms, Inc., WhatsApp Inc., or any of their subsidiaries or affiliates.

== Changelog ==

= 1.0.0 =
* Versión inicial oficial de WhatAuth para WordPress.
* Inicio de sesión Passwordless con WhatsApp en wp-login.php y WooCommerce.
* Shortcode `[whatauth_login]` y bloque Gutenberg `whatauth/qr-login`.
* Gestor completo de QRs de tipo LOGIN, PIXEL y PRINT con Auto-Reply y Webhooks.
* Asistente de bienvenida e integración con Service Tokens.
* Diccionarios i18n empaquetados en 12 idiomas.

== License ==

This plugin is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details: https://www.gnu.org/licenses/gpl-2.0.html


