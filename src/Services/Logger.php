<?php
namespace XAutoPoster\Services;

class Logger {
    private $log_dir;
    private $log_file;

    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->log_dir = $upload_dir['basedir'] . '/xautoposter-logs';
        $this->log_file = $this->log_dir . '/xautoposter.log';

        if (!file_exists($this->log_dir)) {
            wp_mkdir_p($this->log_dir);
        }

        // Log dosyasını korumak için .htaccess ekle
        $htaccess = $this->log_dir . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Deny from all");
        }
    }

    public function log($level, $message, $context = []) {
        if (!is_string($message)) {
            $message = print_r($message, true);
        }

        $timestamp = current_time('mysql');
        $formatted = sprintf(
            "[%s] %s: %s %s\n",
            $timestamp,
            strtoupper($level),
            $message,
            !empty($context) ? json_encode($context) : ''
        );

        error_log($formatted, 3, $this->log_file);
    }

    public function info($message, $context = []) {
        $this->log('info', $message, $context);
    }

    public function error($message, $context = []) {
        $this->log('error', $message, $context);
    }

    public function warning($message, $context = []) {
        $this->log('warning', $message, $context);
    }

    public function debug($message, $context = []) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->log('debug', $message, $context);
        }
    }

    public function getLogFile() {
        return $this->log_file;
    }

    public function clearLogs() {
        if (file_exists($this->log_file)) {
            unlink($this->log_file);
        }
    }
}