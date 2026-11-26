<?php
/**
 * =====================================================
 * File    : dashboard.php
 * Fungsi  : Dashboard kasir: grid menu + tombol tambah ke keranjang
 * Project : POS Coffee Shop (Project 2)
 * Author  : Muhammad Zaky Rahman
 * Depends : auth_check.php, functions.php, header.php, footer.php
 * =====================================================
 */

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

// Baris WAJIB di semua halaman terproteksi (Bab 6.1)
requireRole('cashier');

// Query: ambil menu yang tersedia untuk ditampilkan di grid kasir
// JOIN categories  : butuh nama kategori untuk label badge di kartu menu
// WHERE is_available=1 : menu yang disembunyikan tidak boleh muncul
// ORDER BY name    : urutkan alfabetis agar kasir cepat mencari
$sql = "SELECT p.*, c.name AS category_name
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.is_available = 1
        ORDER BY p.name ASC";
$products = $pdo->query($sql)->fetchAll();

$pageTitle = 'Dashboard Kasir';
require_once __DIR__ . '/../../includes/header.php';
?>

<?php $flash = getFlash(); if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<h4 class="mb-3">Menu Tersedia</h4>

<?php if (!$products): ?>
    <div class="alert alert-info">
        Belum ada menu yang tampil. Tambahkan lewat halaman <strong>Menu</strong> di navbar.
    </div>
<?php endif; ?>

<div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
    <?php foreach ($products as $p): ?>
        <div class="col">
            <div class="card card-menu shadow-sm h-100">
                <img src="<?= e(BASE_URL) ?>/assets/uploads/products/<?= e($p['image']) ?>"
                     class="card-img-top" alt="<?= e($p['name']) ?>">
                <div class="card-body">
                    <h6 class="card-title mb-1"><?= e($p['name']) ?></h6>
                    <span class="badge text-bg-secondary mb-2"><?= e($p['category_name']) ?></span>
                    <div class="fw-bold"><?= e(formatRupiah($p['price'])) ?></div>

                    <?php if ((int)$p['stock'] > 0): ?>
                        <small class="text-muted">Stok: <?= (int)$p['stock'] ?></small>
                    <?php else: ?>
                        <small><span class="badge text-bg-danger">Stok habis</span></small>
                    <?php endif; ?>
                </div>

                <!-- Form tambah ke keranjang: POST murni, tanpa JS, agar andal -->
                <form method="post"
                      action="<?= e(BASE_URL) ?>/process/cart_process.php"
                      class="px-3 pb-3">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="redirect" value="dashboard">
                    <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                    <input type="hidden" name="qty" value="1">

                    <button type="submit"
                            class="btn btn-primary btn-sm w-100"
                            <?= ((int)$p['stock'] > 0) ? '' : 'disabled' ?>>
                        + Keranjang
                    </button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>