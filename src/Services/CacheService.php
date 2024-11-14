<?php
namespace XAutoPoster\Services;

class CacheService {
    private $prefix = 'xautoposter_';
    private $default_expiry = 3600; // 1 saat

    public function set($key, $value, $expiry = null) {
        $expiry = $expiry ?? $this->default_expiry;
        set_transient($this->prefix . $key, $value, $expiry);
    }

    public function get($key) {
        return get_transient($this->prefix . $key);
    }

    public function delete($key) {
        delete_transient($this->prefix . $key);
    }

    public function flush() {
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
                $wpdb->esc_like($this->prefix) . '%'
            )
        );
    }
}