<?php
namespace XAutoPoster\Security;

class SecurityManager {
    public static function verifyNonce($nonce, $action) {
        if (!wp_verify_nonce($nonce, $action)) {
            wp_die(__('Güvenlik doğrulaması başarısız oldu.', 'xautoposter'));
        }
    }

    public static function checkPermissions() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Bu işlem için yetkiniz bulunmuyor.', 'xautoposter'));
        }
    }

    public static function sanitizeInput($input, $type = 'text') {
        switch ($type) {
            case 'text':
                return sanitize_text_field($input);
            case 'textarea':
                return sanitize_textarea_field($input);
            case 'html':
                return wp_kses_post($input);
            case 'url':
                return esc_url_raw($input);
            case 'email':
                return sanitize_email($input);
            case 'int':
                return intval($input);
            case 'array':
                return array_map('sanitize_text_field', (array)$input);
            default:
                return sanitize_text_field($input);
        }
    }

    public static function validateApiCredentials($credentials) {
        foreach (['api_key', 'api_secret', 'access_token', 'access_token_secret'] as $field) {
            if (empty($credentials[$field])) {
                throw new \Exception(__('Tüm API bilgileri zorunludur.', 'xautoposter'));
            }
        }
    }

    public static function secureHeaders() {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
}