<?php
namespace XAutoPoster;

class Autoloader {
    public static function register() {
        spl_autoload_register(function ($class) {
            // XAutoPoster namespace kontrolü
            if (strpos($class, 'XAutoPoster\\') !== 0) {
                return;
            }

            // Namespace'i dosya yoluna çevir
            $class_file = str_replace('XAutoPoster\\', '', $class);
            $class_file = str_replace('\\', DIRECTORY_SEPARATOR, $class_file);
            $class_path = XAUTOPOSTER_PATH . 'src' . DIRECTORY_SEPARATOR . $class_file . '.php';

            // Sınıf dosyası var mı kontrol et
            if (file_exists($class_path)) {
                require_once $class_path;
                return true;
            }

            // Alternatif yolları dene
            $alternative_paths = [
                XAUTOPOSTER_PATH . 'src/Services/' . basename($class_file) . '.php',
                XAUTOPOSTER_PATH . 'src/Models/' . basename($class_file) . '.php',
                XAUTOPOSTER_PATH . 'src/Admin/' . basename($class_file) . '.php'
            ];

            foreach ($alternative_paths as $path) {
                if (file_exists($path)) {
                    require_once $path;
                    return true;
                }
            }

            return false;
        });
    }
}