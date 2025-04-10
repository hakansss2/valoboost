<?php
// Hata raporlama
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Oturum başlat
session_start();

// Veritabanı bağlantı bilgileri
define('DB_HOST', 'localhost');
define('DB_NAME', 'kuvvet_boost');
define('DB_USER', 'kuvvet_boost');
define('DB_PASS', 'kuvvet_boost');

// Veritabanı bağlantısı
try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

// Zaman dilimi ayarı
date_default_timezone_set('Europe/Istanbul');

// Site ayarları
$site_title = 'EloBoost';
$site_description = 'Profesyonel EloBoost Hizmetleri';
$site_email = 'info@eloboost.com';
$site_phone = '+90 555 555 55 55';
$site_address = 'İstanbul, Türkiye';
$site_discord = 'https://discord.gg/eloboost';

// Site URL'si ve sabit dizinler
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
define('BASE_URL', $protocol . $host . '/');
define('ASSETS_URL', BASE_URL . 'assets/');
define('UPLOADS_URL', BASE_URL . 'uploads/');

// Yükleme dizinleri
define('UPLOADS_DIR', __DIR__ . '/../uploads/');
define('RANKS_DIR', UPLOADS_DIR . 'ranks/');
define('AGENTS_DIR', UPLOADS_DIR . 'agents/');
define('CHAMPIONS_DIR', UPLOADS_DIR . 'champions/');

// Yükleme dizinlerini oluştur
$directories = [UPLOADS_DIR, RANKS_DIR, AGENTS_DIR, CHAMPIONS_DIR];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}
?> 