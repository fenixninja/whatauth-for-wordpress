<?php
if (!defined('ABSPATH')) {
    exit;
}

class WhatAuth_Roles {

    const CAP_MANAGE_SETTINGS = 'manage_whatauth_settings';
    const CAP_MANAGE_QRS      = 'manage_whatauth_qrs';

    /**
     * Asigna las capacidades por defecto al rol administrador
     */
    public static function assign_default_capabilities() {
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap(self::CAP_MANAGE_SETTINGS);
            $admin_role->add_cap(self::CAP_MANAGE_QRS);
        }
    }

    /**
     * Obtiene todos los roles registrados dinámicamente en WordPress
     */
    public static function get_available_roles() {
        $wp_roles = wp_roles();
        $roles = array();

        if (!empty($wp_roles->roles) && is_array($wp_roles->roles)) {
            foreach ($wp_roles->roles as $role_key => $role_data) {
                $roles[$role_key] = translate_user_role($role_data['name']);
            }
        }

        return $roles;
    }

    /**
     * Obtiene el rol configurado para nuevos registros garantizando privilegios mínimos
     */
    public static function get_default_registration_role() {
        $settings = get_option('whatauth_settings', array());
        $role = !empty($settings['default_role']) ? $settings['default_role'] : 'subscriber';

        // Prohibir explícitamente roles privilegiados en registros automáticos públicos
        $disallowed_roles = array('administrator', 'editor', 'author');
        if (in_array(strtolower($role), $disallowed_roles, true)) {
            return 'subscriber';
        }

        $available = self::get_available_roles();
        if (!isset($available[$role])) {
            return 'subscriber';
        }

        return $role;
    }

    /**
     * Comprueba si el usuario actual puede gestionar la configuración de WhatAuth
     */
    public static function can_manage_settings($user_id = null) {
        if ($user_id) {
            return user_can($user_id, self::CAP_MANAGE_SETTINGS) || user_can($user_id, 'manage_options');
        }
        return current_user_can(self::CAP_MANAGE_SETTINGS) || current_user_can('manage_options');
    }

    /**
     * Comprueba si el usuario actual puede gestionar códigos QR
     */
    public static function can_manage_qrs($user_id = null) {
        if ($user_id) {
            return user_can($user_id, self::CAP_MANAGE_QRS) || user_can($user_id, 'manage_options');
        }
        return current_user_can(self::CAP_MANAGE_QRS) || current_user_can('manage_options');
    }

    /**
     * Actualiza qué roles tienen permiso para gestionar QRs
     */
    public static function update_role_permissions($roles_with_qr_access = array()) {
        $wp_roles = wp_roles();
        if (empty($wp_roles->roles)) {
            return;
        }

        foreach ($wp_roles->roles as $role_key => $role_data) {
            $role_obj = get_role($role_key);
            if (!$role_obj) continue;

            // El administrador siempre conserva el control total
            if ($role_key === 'administrator') {
                $role_obj->add_cap(self::CAP_MANAGE_SETTINGS);
                $role_obj->add_cap(self::CAP_MANAGE_QRS);
                continue;
            }

            if (in_array($role_key, (array)$roles_with_qr_access, true)) {
                $role_obj->add_cap(self::CAP_MANAGE_QRS);
            } else {
                $role_obj->remove_cap(self::CAP_MANAGE_QRS);
            }
        }
    }
}

