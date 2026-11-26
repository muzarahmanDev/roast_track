<?php
/**
 * =====================================================
 * File    : header.php
 * Fungsi  : Template bagian atas HTML dan navbar sesuai role
 * Project : POS Coffee Shop (Project 2)
 * Author  : Muhammad Zaky Rahman
 * Depends : db_connect.php, functions.php
 * =====================================================
 */

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/functions.php';

// Judul halaman bisa dikirim dari halaman pemanggil
$pageTitle = $pageTitle ?? 'RoastTrack';

// Role aktif untuk menentukan link navbar (Bab 5: navbar menyesuaikan role)
$currentRole = $_SESSION['role'] ?? '';

// Jumlah item keranjang untuk badge di navbar (khusus kasir)
$cartCount = cartCount();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | RoastTrack</title>

    <!-- Bootstrap 5.3 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- CSS lokal -->
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/style.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand navbar-dark bg-dark">
    <div class="container">
        <span class="navbar-brand fw-bold">☕ RoastTrack</span>

        <?php if (!empty($_SESSION['user_id'])): ?>
            <ul class="navbar-nav me-auto">
                <?php if ($currentRole === 'cashier'): ?>
                    <!-- Link khusus kasir -->
                    <li class="nav-item"><a class="nav-link" href="<?= e(BASE_URL) ?>/pages/cashier/dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(BASE_URL) ?>/pages/cashier/products.php">Menu</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(BASE_URL) ?>/pages/cashier/categories.php">Kategori</a></li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>/pages/cashier/cart.php">
                            Keranjang
                            <?php if ($cartCount > 0): ?>
                                <span class="badge text-bg-warning"><?= (int)$cartCount ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(BASE_URL) ?>/pages/cashier/active_orders.php">Pesanan Aktif</a></li>
                <?php elseif ($currentRole === 'kitchen'): ?>
                    <!-- Link khusus dapur -->
                    <li class="nav-item"><a class="nav-link" href="<?= e(BASE_URL) ?>/pages/kitchen/display.php">Display</a></li>
                <?php endif; ?>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <span class="text-light"><?= e($_SESSION['name'] ?? '') ?></span>
                <span class="badge text-bg-secondary"><?= e($currentRole) ?></span>
                <a class="btn btn-outline-light btn-sm" href="<?= e(BASE_URL) ?>/process/logout_process.php">Logout</a>
            </div>
        <?php endif; ?>
    </div>
</nav>

<main class="container py-4">