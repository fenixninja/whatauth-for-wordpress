# WhatAuth – Passwordless Login & Smart QR Hub

[![GPLv2+ License](https://img.shields.io/badge/License-GPLv2%2B-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759b.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4.svg)](https://php.net)
[![WhatAuth](https://img.shields.io/badge/WhatAuth-Cloud%20SaaS-25D366.svg)](https://whatauth.com)

Official WordPress plugin for **[WhatAuth](https://whatauth.com)**: passwordless authentication via WhatsApp QR codes, full-spectrum QR campaign management (Login, Pixel, Print), WYSIWYG Auto-Reply with rich media, and real-time outgoing webhooks directly inside your WordPress admin dashboard.

---

## ✨ Features

* 🔐 **Passwordless WhatsApp Login:** Instant 1-second sign-in and user registration via WhatsApp QR scanning and verified PIN / Emoji PIN.
* 🚀 **Native wp-login.php & WooCommerce Integration:** Seamless injection into the official WordPress login screen, WooCommerce My Account, and Checkout forms.
* 📱 **Multi-Campaign QR Studio:**
  * **LOGIN QR:** Instant web access and volatile passwordless sessions.
  * **PIXEL QR:** Lead generation, user capture, and web campaign redirects.
  * **PRINT QR:** High-resolution physical signage with native GeoTagging.
* 🎨 **Bespoke QR Styling:** Customize dots shapes (square, dots, rounded, classy), corner styles, custom logo margin & scaling, error correction levels (Q, H, M, L), and live Canvas rendering via `qr-code-styling`.
* 💬 **WhatsApp Chat Composer (Auto-Reply):** Built-in WYSIWYG editor supporting native WhatsApp formatting (`*bold*`, `_italic_`, `~strikethrough~`), rich media attachments (Images, Audio voice notes, PDF documents), interactive CTA links, and real-time chat bubble preview.
* 📞 **Flexible WhatsApp Line Routing:** Operate on WhatAuth's shared Official Host Bot or connect your own dedicated WhatsApp line (PRO / Agency) via Native Gateway or Meta Cloud API.
* 🌐 **100% Native i18n Multilingual:** Packaged with gettext translations in 12 languages (Spanish, English, French, Italian, Portuguese, German, Russian, Chinese, Hindi, Japanese).
* 🛡️ **Role-Based Access Control (RBAC):** Restrict QR management capabilities to specific WordPress roles (`manage_whatauth_qrs`).

---

## 📦 Installation

1. Download the latest release `.zip` from the [Releases](https://github.com/fenixninja/whatauth-for-wordpress/releases) page.
2. In your WordPress admin, navigate to **Plugins > Add New > Upload Plugin** and select the `.zip` file.
3. Click **Install Now** and **Activate**.
4. Go to **WhatAuth > Settings & Roles** (or launch the Setup Wizard) and paste your WhatAuth **Service Token** (`wst_live_...`).
5. Choose your default WhatsApp line and enjoy passwordless access!

---

## 🧩 Shortcodes & Gutenberg Blocks

* **Shortcode:**
  ```html
  [whatauth_login redirect="/my-account"]
  ```
* **Gutenberg Block:**
  Search for `WhatAuth QR Login` in the block inserter.

---

## ☁️ Third-Party Services

This plugin interfaces with the **WhatAuth Cloud SaaS** service to verify WhatsApp authentication sessions, generate dynamic QR codes, provide real-time socket updates, and dispatch auto-reply messages.

* **Service Provider:** [WhatAuth](https://whatauth.com)
* **API Endpoints:** `https://api.whatauth.com`
* **Terms of Service:** [https://whatauth.com/terms](https://whatauth.com/terms)
* **Privacy Policy:** [https://whatauth.com/privacy](https://whatauth.com/privacy)
* **Data Transmitted:**
  - Authorized website domain and API Service Token for authentication.
  - Temporary session identifiers and QR tokens.
  - Verified WhatsApp phone numbers during login verification to resolve or create the corresponding WordPress user account.
  - No private WordPress database contents, passwords, or customer metadata are shared or stored externally.

---

## ⚖️ Disclaimer & Trademarks

WhatsApp is a registered trademark of Meta Platforms, Inc. WhatAuth is an independent integration platform and SaaS service. This plugin and WhatAuth are not affiliated with, associated with, authorized by, endorsed by, or in any way officially connected with Meta Platforms, Inc., WhatsApp Inc., or any of their subsidiaries or affiliates.

---

## 📄 License

This project is licensed under the **GNU General Public License v2 or later** (GPL-2.0-or-later). See the [LICENSE.txt](LICENSE.txt) file for details.

