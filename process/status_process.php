<?php
/**
 * =====================================================
 * File    : status_process.php
 * Fungsi  : Kitchen mengubah status pesanan (pending→processing→completed)
 * Project : POS Coffee Shop (Project 2)
 * Author  : Muhammad Zaky Rahman
 * Depends : db_connect.php, auth_check.php, functions.php
 * =====================================================
 */

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

// Baris WAJIB: hanya dapur yang boleh mengubah status pengerjaan
requireRole('kitchen');

// Halaman proses hanya menerima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/kitchen/display.php');
    exit;
}

$backUrl = BASE_URL . '/pages/kitchen/display.php';
$action  = $_POST['action'] ?? '';
$id      = (int)($_POST['order_id'] ?? 0);

// Peta transisi yang DIIZINKAN sesuai Bab 3 (Order Workflow):
// start    : pending    -> processing
// complete : processing -> completed
// Transisi lain (misal pending langsung completed) otomatis ditolak
$transitions = [
    'start'    => ['from' => 'pending',    'to' => 'processing', 'label' => 'Mulai Proses'],
    'complete' => ['from' => 'processing', 'to' => 'completed',  'label' => 'Selesai'],
];

if (!isset($transitions[$action])) {
    setFlash('danger', 'Aksi status tidak dikenal.');
    header('Location: ' . $backUrl);
    exit;
}

$t = $transitions[$action];

// Query: baca status pesanan saat ini sebelum mengubah
// Cek di PHP memberi pesan error yang ramah; cek kedua di WHERE UPDATE
// memberi keamanan atomik bila dua device menekan tombol bersamaan
$stmt = $pdo->prepare("SELECT id, order_number, status FROM orders WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $id]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('danger', 'Pesanan tidak ditemukan.');
    header('Location: ' . $backUrl);
    exit;
}

if ($order['status'] !== $t['from']) {
    // Contoh: tombol "Mulai Proses" dipencet dua kali; klik kedua ditolak di sini
    setFlash('danger', 'Transisi ditolak: status pesanan ' . $order['order_number'] . ' saat ini "' . $order['status'] . '", bukan "' . $t['from'] . '".');
    header('Location: ' . $backUrl);
    exit;
}

// Query: ubah status dengan penjagaan ganda
// WHERE status = :from : jika status sudah berubah sejak dibaca,
//                        UPDATE tidak mengenai baris apa pun (rowCount 0)
$sql = "UPDATE orders
        SET status = :to
        WHERE id = :id AND status = :from";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    'to'   => $t['to'],
    'id'   => $id,
    'from' => $t['from'],
]);

if ($stmt->rowCount() === 1) {
    setFlash('success', 'Pesanan ' . $order['order_number'] . ' → ' . $t['label'] . '.');
} else {
    setFlash('danger', 'Status sudah diubah oleh device lain. Layar akan menyegarkan diri.');
}

// Pola POST-Redirect-GET
header('Location: ' . $backUrl);
exit; 