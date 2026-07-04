<?php
/**
 * Konfigurasi koneksi database.
 * Sesuaikan kredensial berikut dengan server MySQL Anda.
 *
 * Disarankan memakai user 'backend_app' yang sudah dibuat pada
 * database/04_rbac_security.sql (role_app_backend) agar aplikasi
 * hanya bisa INSERT jadwal lewat stored procedure (concurrency-safe)
 * dan tidak bisa mengubah tabel jadwal secara langsung.
 *
 * Untuk kemudahan pengembangan/testing, contoh di bawah memakai
 * 'root'. GANTI sebelum digunakan di server produksi.
 */

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'db_jadwal_kuliah');
define('DB_USER', 'root');       // contoh: 'backend_app'
define('DB_PASS', '');           // contoh: 'GantiPasswordKuat!4'
define('DB_CHARSET', 'utf8mb4');

function getKoneksi(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $opsi = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opsi);
        } catch (PDOException $e) {
            http_response_code(500);
            die('Koneksi database gagal. Periksa konfigurasi di includes/config.php. (' . $e->getMessage() . ')');
        }
    }

    return $pdo;
}

// Zona waktu default
date_default_timezone_set('Asia/Jakarta');
