<?php
/**
 * =====================================================
 * File    : header.php
 * Fungsi  : Template bagian atas HTML dan navbar
 * Project : POS Coffee Shop (Project 2)
 * Author  : Muhammad Zaky Rahman
 * Depends : db_connect.php, functions.php
 * =====================================================
 */

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/functions.php';

// Judul halaman bisa dikirim dari halaman pemanggil
$pageTitle = $pageTitle ?? 'RoastTrack';
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

<nav class="navbar navbar-dark bg-dark">
    <div class="container">
        <span class="navbar-brand fw-bold">☕ RoastTrack</span>

        <div class="d-flex align-items-center gap-3">
            <?php if (!empty($_SESSION['name'])): ?>
                <span class="text-light"><?= e($_SESSION['name']) ?></span>
                <span class="badge text-bg-secondary"><?= e($_SESSION['role']) ?></span>
            <?php endif; ?>

            <?php if (!empty($_SESSION['user_id'])): ?>
                <a class="btn btn-outline-light btn-sm" href="<?= e(BASE_URL) ?>/process/logout_process.php">
                    Logout
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="container py-4"></main>