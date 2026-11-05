=== WhatAuth – Passwordless Login & Smart QR Hub ===
Contributors: fenixninja, fenixrios, whatauth
Donate link: https://whatauth.com
Tags: login, passwordless, qr code, woocommerce, authentication
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Passwordless WhatsApp QR login, verified PIN sessions, and smart QR campaign studio for WordPress and WooCommerce.

== Description ==

WhatAuth for WordPress seamlessly connects your website to the WhatAuth ecosystem, delivering 1-second passwordless authentication via WhatsApp and a multi-campaign QR code studio directly inside your WordPress admin dashboard. Identify real unique users, ensure end-to-end traceability, build trust, and drive genuine customer loyalty.

### Key Features:

* **Passwordless WhatsApp Login:** Visitors sign in or register instantly by scanning a dynamic QR code with their WhatsApp camera and verifying with an encrypted PIN or Emoji PIN.
* **Official wp-login.php Integration:** Automatically embeds the WhatAuth WhatsApp button into the standard WordPress login screen.
* **Native WooCommerce Support:** Injected directly into the My Account page and checkout flow for friction-free, passwordless purchases.
* **Multi-Campaign QR Code Studio:**
  * **LOGIN QR:** Instant web access and volatile passwordless authentication sessions.
  * **PIXEL QR:** WhatsApp lead generation and seamless marketing campaign redirects.
  * **PRINT QR:** High-resolution physical signage with native GeoTagging and coordinates.
* **WYSIWYG Chat Composer (Auto-Reply):** Configure automated post-scan instant responses with native formatting (*bold*, _italic_, ~strikethrough~, code) and rich media attachments (Images, Audio voice notes, PDF documents) with live chat preview.
* **Real-Time Outgoing Webhooks:** Dispatch automated webhook payloads to external systems (n8n, Make, Zapier, or custom backends) whenever a QR is scanned.
* **Dynamic Role-Based Access Control (RBAC):** Choose the default role for newly registered users (Subscriber, Customer) and assign QR management capabilities to custom WordPress team roles (Administrator, Shop Manager, Editor).
* **Gutenberg Block & Shortcode:** Insert the QR login modal anywhere with `[whatauth_login]` or the dedicated block.
* **100% Native i18n Multilingual:** Packaged with gettext translations in 10 languages: Spanish (es_ES, es_AR, es_MX), English (en_US), French (fr_FR), Italian (it_IT), Portuguese (pt_BR), German (de_DE), Russian (ru_RU), Chinese (zh_CN), Hindi (hi_IN), and Japanese (ja, ja_JP).

== Installation ==

1. Upload the `whatauth` folder to the `/wp-content/plugins/` directory, or install the ZIP file via **Plugins > Add New > Upload Plugin**.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Click on **WhatAuth** in the sidebar or launch the wizard under **WhatAuth > Wizard**.
4. Enter your **Service Token** (`wst_live_...`) generated in your WhatAuth dashboard at [whatauth.com](https://whatauth.com).
5. Choose your default WhatsApp line.
6. That's it! Passwordless login and QR campaigns are now active on your site.

== Frequently Asked Questions ==

= Do I need a WhatAuth account to use this plugin? =
Yes. You can create a free account at [whatauth.com](https://whatauth.com) and generate your Service Token under Settings > API Keys.

= Does it work with the Official Bot? =
Yes. You can use WhatAuth's shared official bot with zero setup to instantly deliver authentication requests.

= Is it compatible with WooCommerce? =
Yes, WhatAuth automatically adds login buttons to the WooCommerce My Account page and checkout forms.

= What happens if a user doesn't have an existing WordPress account? =
If auto-registration is enabled in settings, the plugin automatically creates a new WordPress account with the configured default role (e.g. Subscriber or Customer) linked to their verified WhatsApp number.

= Can I restrict who can create QR codes in my team? =
Yes, in the Settings tab you can grant QR management capabilities to specific roles such as Editors or Shop Managers, while reserving core settings to Administrators.

== Screenshots ==

1. Telemetry overview and connection health dashboard in WordPress admin.
2. Multi-campaign QR manager with Login, Pixel, and Print views.
3. Asynchronous Studio modal with WYSIWYG Auto-Reply chat composer and live preview.
4. One-click passwordless login button on the standard wp-login.php screen.
5. General settings, line routing, and role permission assignments.

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
* Initial official release of WhatAuth for WordPress.
* Passwordless WhatsApp login on wp-login.php and WooCommerce.
* Shortcode `[whatauth_login]` and Gutenberg block `whatauth/qr-login`.
* Multi-campaign QR studio for LOGIN, PIXEL, and PRINT with Auto-Reply and Webhooks.
* Setup wizard and service token integration.
* Fully bundled local assets and 10-language i18n support.

== License ==

This plugin is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details: https://www.gnu.org/licenses/gpl-2.0.html
