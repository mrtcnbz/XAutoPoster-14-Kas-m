<?php
/**
 * Plugin Name: XAutoPoster
 * Description: WordPress içeriklerinizi otomatik olarak X (Twitter) hesabınızda paylaşın
 * Version: 1.0.0
 * Author: Murat Canbaz
 * Text Domain: xautoposter
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('XAUTOPOSTER_VERSION', '1.0.0');
define('XAUTOPOSTER_FILE', __FILE__);
define('XAUTOPOSTER_PATH', plugin_dir_path(__FILE__));
define('XAUTOPOSTER_URL', plugin_dir_url(__FILE__));
define('XAUTOPOSTER_BASENAME', plugin_basename(__FILE__));

// Composer autoloader'ı kontrol et ve yükle
$autoloader = XAUTOPOSTER_PATH . 'vendor/autoload.php';
if (!file_exists($autoloader)) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p>' . 
             esc_html__('XAutoPoster: Composer bağımlılıkları eksik. Lütfen plugin dizininde "composer install" komutunu çalıştırın.', 'xautoposter') . 
             '</p></div>';
    });
    return;
}
require_once $autoloader;

// Plugin autoloader'ı yükle
require_once XAUTOPOSTER_PATH . 'src/autoload.php';
XAutoPoster\Autoloader::register();

// Initialize the plugin
function xautoposter_init() {
    try {
        return \XAutoPoster\Plugin::getInstance();
    } catch (Exception $e) {
        add_action('admin_notices', function() use ($e) {
            printf(
                '<div class="error"><p>%s</p></div>',
                esc_html(sprintf('XAutoPoster Hata: %s', $e->getMessage()))
            );
        });
        return null;
    }
}

// Register hooks
add_action('plugins_loaded', 'xautoposter_init');