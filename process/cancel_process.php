<?php
/**
 * =====================================================
 * File    : cancel_process.php
 * Fungsi  : Kasir membatalkan pesanan berstatus pending + kembalikan stok
 * Project : POS Coffee Shop (Project 2)
 * Author  : Muhammad Zaky Rahman
 * Depends : db_connect.php, auth_check.php, functions.php
 * =====================================================
 */

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

// Baris WAJIB: hanya kasir yang boleh membatalkan pesanan
requireRole('cashier');

// Halaman proses hanya menerima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/cashier/active_orders.php');
    exit;
}

$backUrl = BASE_URL . '/pages/cashier/active_orders.php';
$id      = (int)($_POST['order_id'] ?? 0);

try {
    // Transaksi dipakai karena pembatalan menyentuh dua tabel:
    // orders (ubah status) dan products (kembalikan stok)
    $pdo->beginTransaction();

    // Query: kunci baris pesanan agar tidak dibatalkan dua kali bersamaan
    $sql = "SELECT id, order_number, status
            FROM orders
            WHERE id = :id
            FOR UPDATE";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $order = $stmt->fetch();

    if (!$order) {
        throw new Exception('Pesanan tidak ditemukan.');
    }

    // Aturan bisnis Bab 3: hanya pending yang boleh dibatalkan kasir.
    // Sudah dimasak (processing/completed) = tidak bisa dibatalkan
    if ($order['status'] !== 'pending') {
        throw new Exception('Hanya pesanan berstatus pending yang bisa dibatalkan.');
    }

    // Query: tandai pesanan sebagai dibatalkan
    $sql = "UPDATE orders SET status = 'cancelled' WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);

    // Query: kembalikan stok semua item pesanan ke tabel products
    // JOIN order_details : qty yang dikembalikan dibaca dari rincian transaksi
    // stock = stock + od.quantity : pembalikan dari pengurangan saat checkout
    $sql = "UPDATE products p
            JOIN order_details od ON od.product_id = p.id
            SET p.stock = p.stock + od.quantity
            WHERE od.order_id = :order_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['order_id' => $id]);

    $pdo->commit();

    setFlash('success', 'Pesanan ' . $order['order_number'] . ' dibatalkan dan stok dikembalikan.');
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    setFlash('danger', 'Pembatalan gagal: ' . $e->getMessage());
}

// Pola POST-Redirect-GET
header('Location: ' . $backUrl);
exit;