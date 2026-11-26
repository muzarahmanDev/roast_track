<?php
/**
 * =====================================================
 * File    : display.php
 * Fungsi  : Kitchen Display System (KDS) dengan polling live
 * Project : POS Coffee Shop (Project 2)
 * Author  : Muhammad Zaky Rahman
 * Depends : auth_check.php, functions.php, header.php, footer.php, kitchen_polling.js
 * =====================================================
 */

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

// Baris WAJIB: hanya dapur yang boleh melihat KDS
requireRole('kitchen');

$pageTitle   = 'Kitchen Display';
$pageScripts = [BASE_URL . '/assets/js/kitchen_polling.js'];
require_once __DIR__ . '/../../includes/header.php';
?>

<?php $flash = getFlash(); if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<h4 class="mb-1">Pesanan Masuk</h4>
<p class="text-muted small">Layar memperbarui diri otomatis setiap 5 detik — tidak perlu refresh manual.</p>

<!-- data-ajax & data-status-url dibaca oleh kitchen_polling.js -->
<div id="kds-board" class="row g-3"
     data-ajax="<?= e(BASE_URL) ?>/process/kitchen_ajax.php"
     data-status-url="<?= e(BASE_URL) ?>/process/status_process.php">
    <div class="col-12 text-muted">Memuat pesanan…</div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?> 