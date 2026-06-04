<?php
/**
 * Veritabanı Konfigürasyonu
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gezi_haritasi');

// API Anahtarları
define('GOOGLE_MAPS_API_KEY', 'YOUR_GOOGLE_MAPS_API_KEY');
define('OPENAI_API_KEY', 'YOUR_OPENAI_API_KEY');
define('OPENWEATHER_API_KEY', 'YOUR_OPENWEATHER_API_KEY');

// Uygulama Ayarları
define('APP_NAME', 'İnteraktif Gezi Haritası');
define('APP_URL', 'http://localhost/hd');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// Veritabanı Bağlantısı
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_PERSISTENT => false,
        ]
    );
} catch (PDOException $e) {
    die('Veritabanı Hatası: ' . $e->getMessage());
}

// CORS Ayarları
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');
?>
