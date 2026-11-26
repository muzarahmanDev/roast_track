<?php
/**
 * =====================================================
 * File    : display.php
 * Fungsi  : Kitchen Display System awal
 * Project : POS Coffee Shop (Project 2)
 * Author  : Muhammad Zaky Rahman
 * Depends : auth_check.php, header.php, footer.php
 * =====================================================
 */

require_once __DIR__ . '/../../includes/auth_check.php';

// Halaman ini hanya boleh diakses oleh kitchen
requireRole('kitchen');

$pageTitle = 'Kitchen Display';

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="alert alert-success" onclick="this.style.display='none'" style="cursor: pointer;" title="Klik untuk menutup">
    Dashboard kasir berhasil diakses. Fase 1 login multi-role berjalan.
</div>


<div class="card">
    <div class="card-body">
        <h5 class="card-title">Kitchen Display</h5>
        <p class="text-muted mb-0">
            Halaman ini nanti akan menampilkan pesanan masuk secara live.
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>