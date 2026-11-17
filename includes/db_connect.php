<?php
/**
 * =====================================================
 * File    : db_connect.php
 * Fungsi  : Koneksi database PDO dan konfigurasi global
 * Project : POS Coffee Shop (Project 2)
 * Author  : Muhammad Zaky Rahman
 * =====================================================
 */

// Mode development: tampilkan error agar mudah debugging.
// Untuk produksi, bagian ini sebaiknya dimatikan.
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Mulai session hanya jika belum aktif.
// Session dipakai untuk login, role, dan keranjang belanja.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// BASE_URL otomatis: menghitung posisi folder project terhadap document root Apache.
// Keuntungan: kode tetap benar walau diakses via localhost, IP LAN, atau folder diganti nama.
if (!defined('BASE_URL')) {
// BASE_URL: alamat dasar aplikasi.
// $host dibiarkan dinamis (localhost atau IP LAN) agar Fase 6 tetap jalan.
// Subfolder '/RoastTrack' ditulis eksplisit karena nama folder/junction di www memang itu.
if (!defined('BASE_URL')) {
    // Protokol http/https tergantung cara browser mengakses
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';

    // Host yang diketik browser: bisa localhost, bisa IP LAN
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Subfolder project di dalam web root Laragon
    define('BASE_URL', $protocol . $host . '/RoastTrack');
}
}

// Konfigurasi koneksi database Laragon
// Nama database mengikuti hasil setup kamu: roast_track
$db_host = '127.0.0.1';
$db_name = 'roast_track';
$db_user = 'root';
$db_pass = '';

try {
    // DSN PDO: driver mysql + host + nama database + charset
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";

    // Opsi PDO:
    // - ERRMODE_EXCEPTION      : error dilempar sebagai exception
    // - FETCH_ASSOC            : hasil query berupa array asosiatif
    // - EMULATE_PREPARES false : prepared statement asli, lebih aman
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    // Koneksi PDO disimpan di $pdo agar bisa dipakai file lain
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    // Hentikan aplikasi jika koneksi gagal
    die('Koneksi database gagal: ' . $e->getMessage());
}