<?php
/**
 * QRMed Card - Konfigurasi Koneksi Database
 * Sesuaikan DB_USER / DB_PASS jika pengaturan MySQL Laragon Anda berbeda.
 * Default Laragon: user root tanpa password.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'qrmed_card');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Nama folder project di dalam www/ Laragon. Ubah HANYA jika Anda mengganti
// nama foldernya dari 'qrmed-card' menjadi nama lain.
define('APP_SUBPATH', '/qrmed-card');

// Base URL aplikasi dideteksi OTOMATIS dari alamat yang sedang dipakai untuk
// mengakses situs ini (localhost, IP WiFi rumah, IP hotspot, dst). Ini artinya
// QR Code akan selalu mengarah ke alamat yang benar sesuai jaringan yang aktif
// saat halaman "Kartu Digital" dibuka — tidak perlu edit manual saat pindah jaringan.
$_scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('BASE_URL', $_scheme . '://' . $_host . APP_SUBPATH);

function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die('Koneksi database gagal. Pastikan MySQL di Laragon sudah aktif dan database "qrmed_card" sudah diimport. Detail: ' . htmlspecialchars($e->getMessage()));
        }
    }

    return $pdo;
}
