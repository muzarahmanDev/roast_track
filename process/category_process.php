<?php
/**
 * =====================================================
 * File    : category_process.php
 * Fungsi  : Memproses tambah / ubah / hapus kategori
 * Project : POS Coffee Shop (Project 2)
 * Author  : Muhammad Zaky Rahman
 * Depends : db_connect.php, auth_check.php, functions.php
 * =====================================================
 */

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

// Baris WAJIB: hanya kasir yang boleh memproses kategori
requireRole('cashier');

// Semua aksi proses harus lewat POST (Bab 9: halaman proses tidak boleh dibuka langsung)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/cashier/categories.php');
    exit;
}

$action  = $_POST['action'] ?? '';
$backUrl = BASE_URL . '/pages/cashier/categories.php';

if ($action === 'add' || $action === 'edit') {
    // Bersihkan input nama kategori
    $name = trim($_POST['name'] ?? '');

    // Validasi sederhana: tidak kosong dan maksimal 100 karakter sesuai DDL
    if ($name === '' || mb_strlen($name) > 100) {
        setFlash('danger', 'Nama kategori wajib diisi (maksimal 100 karakter).');
        header('Location: ' . $backUrl);
        exit;
    }

    if ($action === 'add') {
        // Query: tambah kategori baru
        // :name di-bind lewat prepared statement agar aman dari SQL injection
        $sql = "INSERT INTO categories (name) VALUES (:name)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['name' => $name]);

        setFlash('success', "Kategori \"{$name}\" berhasil ditambahkan.");
    } else {
        $id = (int)($_POST['id'] ?? 0);

        // Query: ubah nama kategori berdasarkan id
        // WHERE id = :id : hanya baris kategori yang dipilih yang diubah
        $sql = "UPDATE categories SET name = :name WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'name' => $name,
            'id'   => $id,
        ]);

        setFlash('success', 'Kategori berhasil diperbarui.');
    }
} elseif ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);

    // Query: hitung menu yang masih memakai kategori ini
    // Jika masih dipakai, hapus akan ditolak karena FK ON DELETE RESTRICT
    $checkSql = "SELECT COUNT(*) AS total
                 FROM products
                 WHERE category_id = :id";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute(['id' => $id]);
    $used = (int)$checkStmt->fetch()['total'];

    if ($used > 0) {
        // Tolak penghapusan dengan pesan ramah, bukan error database
        setFlash('danger', "Kategori tidak bisa dihapus karena masih dipakai {$used} menu.");
    } else {
        // Query: hapus kategori yang tidak dipakai menu apa pun
        $sql = "DELETE FROM categories WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        setFlash('success', 'Kategori berhasil dihapus.');
    }
}

// Pola POST-Redirect-GET: setelah proses, kembali ke halaman kategori
header('Location: ' . $backUrl);
exit;