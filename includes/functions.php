<?php
/**
 * =====================================================
 * File    : functions.php
 * Fungsi  : Helper umum untuk format output, rupiah, order
 * Project : POS Coffee Shop (Project 2)
 * Author  : Muhammad Zaky Rahman
 * =====================================================
 */

/**
 * Escape output HTML
 * @param string|null $text Teks yang akan ditampilkan ke HTML
 * @return string Teks aman untuk HTML
 * Cara kerja: memakai htmlspecialchars untuk mencegah XSS
 */
function e($text)
{
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

/**
 * Format angka menjadi Rupiah
 * @param int|float|string $amount Nominal uang
 * @return string Contoh: "Rp 18.000"
 */
function formatRupiah($amount)
{
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
}

/**
 * Membuat nomor order unik berformat TRX-YYYYMMDD-XXXX
 * @param PDO $pdo Koneksi database aktif
 * @return string Contoh: "TRX-20261012-0007"
 * Cara kerja: hitung jumlah order dengan prefix tanggal hari ini, lalu +1
 */
function generateOrderNumber($pdo)
{
    // Format tanggal hari ini, contoh: 20261012
    $today = date('Ymd');

    // Prefix nomor order, contoh: TRX-20261012-
    $prefix = 'TRX-' . $today . '-';

    // Query: hitung jumlah order yang nomor ordernya diawali prefix hari ini
    // LIKE dipakai karena kita hanya menghitung urutan nomor order hari ini
    $sql = "SELECT COUNT(id) AS total
            FROM orders
            WHERE order_number LIKE :prefix";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'prefix' => $prefix . '%'
    ]);

    $row = $stmt->fetch();

    // Ambil jumlah order hari ini, lalu tambah 1 untuk nomor baru
    $nextNumber = ((int)$row['total']) + 1;

    // Gabungkan prefix dengan nomor urut 4 digit, contoh: 0001
    return $prefix . str_pad((string)$nextNumber, 4, '0', STR_PAD_LEFT);
}