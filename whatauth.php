<?php
/**
 * Plugin Name:       WhatAuth – Passwordless Login & Smart QR Hub
 * Plugin URI:        https://whatauth.com/wordpress
 * Description:       Passwordless authentication, real-time verified sessions, and comprehensive QR campaign studio (Login, Pixel, Print) with instant Auto-Reply.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            WhatAuth Team
 * Author URI:        https://whatauth.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       whatauth
 * Domain Path:       /languages
 */

/**
 * WhatAuth is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * WhatAuth is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WhatAuth. If not, see <https://www.gnu.org/licenses/gpl-2.0.html>.
 */

if (!defined('ABSPATH')) {
    exit; // Evitar acceso directo
}

define('WHATAUTH_VERSION', '1.0.0');
define('WHATAUTH_PLUGIN_FILE', __FILE__);
define('WHATAUTH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WHATAUTH_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WHATAUTH_DEFAULT_API_URL', 'https://api.whatauth.com');

// Cargar módulos del núcleo
require_once WHATAUTH_PLUGIN_DIR . 'includes/class-whatauth-api-client.php';
require_once WHATAUTH_PLUGIN_DIR . 'includes/class-whatauth-roles.php';
require_once WHATAUTH_PLUGIN_DIR . 'includes/class-whatauth-auth.php';
require_once WHATAUTH_PLUGIN_DIR . 'includes/class-whatauth-qr-manager.php';

if (is_admin()) {
    require_once WHATAUTH_PLUGIN_DIR . 'admin/class-whatauth-admin.php';
}

/**
 * Inicialización de i18n con resolución automática de idioma de WordPress
 */
add_action('plugins_loaded', 'whatauth_load_textdomain');
function whatauth_load_textdomain() {
    $lang_dir = dirname(plugin_basename(__FILE__)) . '/languages';
    // phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound -- Fallback for offline/custom installations
    $loaded   = load_plugin_textdomain('whatauth', false, $lang_dir);

    // Fallback robusto para dialectos regionales y entornos donde determine_locale difiera
    if (!$loaded) {
        $locale = function_exists('determine_locale') ? determine_locale() : (is_admin() && function_exists('get_user_locale') ? get_user_locale() : get_locale());
        $lang_prefix = substr($locale, 0, 2);

        $fallback_map = array(
            'es' => 'es_ES',
            'en' => 'en_US',
            'fr' => 'fr_FR',
            'it' => 'it_IT',
            'pt' => 'pt_BR',
            'de' => 'de_DE',
            'ru' => 'ru_RU',
            'zh' => 'zh_CN',
            'hi' => 'hi_IN',
            'ja' => 'ja',
        );

        $target_mo = isset($fallback_map[$lang_prefix]) ? $fallback_map[$lang_prefix] : 'en_US';
        $mo_file   = WHATAUTH_PLUGIN_DIR . 'languages/whatauth-' . $target_mo . '.mo';
        if (file_exists($mo_file)) {
            load_textdomain('whatauth', $mo_file);
        }
    }
}

/**
 * Activación del plugin
 */
register_activation_hook(__FILE__, 'whatauth_activate_plugin');
function whatauth_activate_plugin() {
    $default_settings = array(
        'api_key'                 => '',
        'api_url'                 => WHATAUTH_DEFAULT_API_URL,
        'domain'                  => wp_parse_url(home_url(), PHP_URL_HOST),
        'line_id'                 => '',
        'admin_phone'             => '',
        'allow_registration'      => 1,
        'default_role'            => 'subscriber',
        'hide_wp_credentials'     => 0,
        'desktop_show_walink'     => 1,
        'mobile_show_walink_only' => 1,
        'enable_wplogin'          => 1,
        'enable_woocommerce'      => 1,
        'qr_size'                 => 280,
        'button_text'             => __('Abrir WhatsApp directamente', 'whatauth'),
        'login_instruction_text'  => __('Escanea con tu WhatsApp para acceder al instante sin contraseñas.', 'whatauth'),
        'button_bg_color'         => '#25D366',
        'button_text_color'       => '#ffffff',
        'custom_logo_url'         => '',
        'plan'                    => 'STARTER',
        'setup_completed'         => 0,
    );

    $current_settings = get_option('whatauth_settings', array());
    $merged_settings  = wp_parse_args($current_settings, $default_settings);
    update_option('whatauth_settings', $merged_settings);

    // Conceder capacidades administrativas por defecto al rol administrator
    WhatAuth_Roles::assign_default_capabilities();
}

/**
 * Desactivación del plugin
 */
register_deactivation_hook(__FILE__, 'whatauth_deactivate_plugin');
function whatauth_deactivate_plugin() {
    // Preservar configuración para no perjudicar al usuario en actualizaciones
}

/**
 * Arranque principal
 */
add_action('plugins_loaded', 'whatauth_init_app');
function whatauth_init_app() {
    WhatAuth_Auth::init();
    WhatAuth_QR_Manager::init();

    if (is_admin()) {
        WhatAuth_Admin::init();
    }
}

