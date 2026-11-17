<?php
/**
 * =====================================================
 * File    : dashboard.php
 * Fungsi  : Dashboard awal kasir
 * Project : POS Coffee Shop (Project 2)
 * Author  : Muhammad Zaky Rahman
 * Depends : auth_check.php, header.php, footer.php
 * =====================================================
 */

require_once __DIR__ . '/../../includes/auth_check.php';

// Halaman ini hanya boleh diakses oleh kasir
requireRole('cashier');

$pageTitle = 'Dashboard Kasir';

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="alert alert-success">
    Dashboard kasir berhasil diakses. Fase 1 login multi-role berjalan.
</div>

<div class="card">
    <div class="card-body">
        <h5 class="card-title">Menu Kasir</h5>
        <p class="text-muted mb-0">
            Halaman ini nanti akan menampilkan grid menu kopi untuk membuat pesanan.
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>