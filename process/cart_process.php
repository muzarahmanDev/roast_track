<?php
/**
 * =====================================================
 * File    : cart_process.php
 * Fungsi  : Tambah / ubah / hapus / kosongkan item keranjang session
 * Project : POS Coffee Shop (Project 2)
 * Author  : Muhammad Zaky Rahman
 * Depends : db_connect.php, auth_check.php, functions.php
 * =====================================================
 */

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

// Baris WAJIB: hanya kasir yang punya keranjang
requireRole('cashier');

// Halaman proses hanya menerima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/cashier/cart.php');
    exit;
}

// Pastikan struktur keranjang selalu berupa array
// Bentuk keranjang: $_SESSION['cart'] = [product_id => qty]
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_POST['action'] ?? '';

// Tujuan kembali setelah proses: dari dashboard atau dari halaman keranjang
// Whitelist sederhana agar tidak ada redirect ke alamat asing
$redirect = (($_POST['redirect'] ?? 'cart') === 'dashboard')
    ? BASE_URL . '/pages/cashier/dashboard.php'
    : BASE_URL . '/pages/cashier/cart.php';

if ($action === 'add') {
    $id  = (int)($_POST['product_id'] ?? 0);
    $qty = max(1, (int)($_POST['qty'] ?? 1));

    // Query: ambil data menu yang ingin ditambah
    // is_available & stock dicek di server, tidak percaya pada tampilan
    $sql = "SELECT id, name, stock, is_available
            FROM products
            WHERE id = :id
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $product = $stmt->fetch();

    if (!$product || (int)$product['is_available'] !== 1) {
        setFlash('danger', 'Menu tidak tersedia untuk dipesan.');
        header('Location: ' . $redirect);
        exit;
    }

    $stock   = (int)$product['stock'];
    $current = $_SESSION['cart'][$id] ?? 0;
    $wanted  = $current + $qty;

    if ($stock < 1) {
        setFlash('danger', 'Stok "' . $product['name'] . '" habis.');
    } else {
        // Qty di keranjang tidak boleh melewati stok fisik
        $newQty = min($wanted, $stock);
        $_SESSION['cart'][$id] = $newQty;

        if ($wanted > $stock) {
            setFlash('warning', 'Jumlah "' . $product['name'] . '" disesuaikan stok tersedia (' . $stock . ').');
        } else {
            setFlash('success', '"' . $product['name'] . '" masuk keranjang.');
        }
    }
} elseif ($action === 'update') {
    $id = (int)($_POST['product_id'] ?? 0);

    if (isset($_SESSION['cart'][$id])) {
        $current = $_SESSION['cart'][$id];

        // Tombol +/- mengirim step; input angka mengirim qty langsung
        if (isset($_POST['step'])) {
            $qty = $current + (int)$_POST['step'];
        } else {
            $qty = (int)($_POST['qty'] ?? $current);
        }

        if ($qty < 1) {
            // Qty di bawah 1 dianggap menghapus item
            unset($_SESSION['cart'][$id]);
            setFlash('success', 'Item dihapus dari keranjang.');
        } else {
            // Query: baca stok terbaru agar qty tidak melewati stok
            $stmt = $pdo->prepare("SELECT stock, name FROM products WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            $product = $stmt->fetch();

            if ($product) {
                $qty = min($qty, (int)$product['stock']);
            }

            $_SESSION['cart'][$id] = max(1, $qty);
        }
    }
} elseif ($action === 'remove') {
    $id = (int)($_POST['product_id'] ?? 0);

    if (isset($_SESSION['cart'][$id])) {
        unset($_SESSION['cart'][$id]);
        setFlash('success', 'Item dihapus dari keranjang.');
    }
} elseif ($action === 'clear') {
    // Kosongkan seluruh keranjang sekaligus
    $_SESSION['cart'] = [];
    setFlash('success', 'Keranjang dikosongkan.');
}

// Pola POST-Redirect-GET
header('Location: ' . $redirect);
exit;