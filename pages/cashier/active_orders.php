<?php
/**
 * =====================================================
 * File    : active_orders.php
 * Fungsi  : Pantau status pesanan hari ini secara live + batal pending
 * Project : POS Coffee Shop (Project 2)
 * Author  : Muhammad Zaky Rahman
 * Depends : auth_check.php, functions.php, header.php, footer.php, kitchen_polling.js
 * =====================================================
 */

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

// Baris WAJIB: hanya kasir yang boleh melihat halaman ini
requireRole('cashier');

$pageTitle   = 'Pesanan Aktif';
$pageScripts = [BASE_URL . '/assets/js/kitchen_polling.js'];
require_once __DIR__ . '/../../includes/header.php';
?>

<?php $flash = getFlash(); if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<h4 class="mb-1">Pesanan Aktif Hari Ini</h4>
<p class="text-muted small">Status diperbarui otomatis setiap 5 detik mengikuti tindakan dapur.</p>

<!-- data-ajax & data-cancel-url dibaca oleh kitchen_polling.js -->
<div id="active-orders"
     data-ajax="<?= e(BASE_URL) ?>/process/orders_ajax.php"
     data-cancel-url="<?= e(BASE_URL) ?>/process/cancel_process.php">
    <p class="text-muted">Memuat pesanan…</p>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>