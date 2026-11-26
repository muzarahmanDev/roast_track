<?php
/**
 * =====================================================
 * File    : cart.php
 * Fungsi  : Keranjang belanja + form checkout
 * Project : POS Coffee Shop (Project 2)
 * Author  : Muhammad Zaky Rahman
 * Depends : auth_check.php, functions.php, header.php, footer.php, cart.js
 * =====================================================
 */

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

// Baris WAJIB di semua halaman terproteksi (Bab 6.1)
requireRole('cashier');

$cart    = $_SESSION['cart'] ?? [];
$items   = [];
$cleaned = false;

if ($cart) {
    // Query: ambil data lengkap semua menu di keranjang sekaligus
    // IN (...)   : satu query untuk semua id, lebih efisien daripada loop
    // Placeholder ? dibuat sebanyak jumlah id agar tetap prepared statement
    $ids = array_keys($cart);
    $in  = implode(',', array_fill(0, count($ids), '?'));

    $sql = "SELECT p.*, c.name AS category_name
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE p.id IN ({$in})";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($ids);

    // Keyed by id agar mudah dicocokkan dengan isi keranjang
    foreach ($stmt->fetchAll() as $row) {
        $items[(int)$row['id']] = $row;
    }

    // Bersihkan keranjang dari menu yang sudah dihapus / disembunyikan / stok habis
    foreach ($cart as $id => $qty) {
        $exists = isset($items[$id]);
        if (!$exists
            || (int)$items[$id]['is_available'] !== 1
            || (int)$items[$id]['stock'] < 1) {
            unset($_SESSION['cart'][$id]);
            $cleaned = true;
        }
    }

    if ($cleaned) {
        setFlash('warning', 'Beberapa item dikeluarkan dari keranjang karena menu/stok berubah.');
        $cart  = $_SESSION['cart'];
        $flash = getFlash(); // ambil ulang agar warning tampil sekali di bawah
    }
}

// Hitung total dari harga ASLI di database, bukan dari session
$total = 0;
foreach ($cart as $id => $qty) {
    if (isset($items[$id])) {
        $total += (float)$items[$id]['price'] * $qty;
    }
}

$pageTitle   = 'Keranjang';
$pageScripts = [BASE_URL . '/assets/js/cart.js'];
require_once __DIR__ . '/../../includes/header.php';
?>

<?php $flash = getFlash(); if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<h4 class="mb-3">Keranjang Belanja</h4>

<?php if (!$cart): ?>
    <div class="alert alert-info">
        Keranjang masih kosong.
        <a href="<?= e(BASE_URL) ?>/pages/cashier/dashboard.php" class="alert-link">Pilih menu di dashboard</a>.
    </div>
<?php else: ?>
    <div class="row">
        <!-- Daftar item keranjang -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Menu</th>
                                    <th>Harga</th>
                                    <th style="width:180px;">Qty</th>
                                    <th>Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart as $id => $qty): ?>
                                    <?php $p = $items[$id]; ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="<?= e(BASE_URL) ?>/assets/uploads/products/<?= e($p['image']) ?>"
                                                     class="rounded" style="height:40px; width:40px; object-fit:cover;"
                                                     alt="<?= e($p['name']) ?>">
                                                <span><?= e($p['name']) ?></span>
                                            </div>
                                        </td>
                                        <td><?= e(formatRupiah($p['price'])) ?></td>
                                        <td>
                                            <!-- Satu form per baris: tombol +/- dan input qty -->
                                            <form method="post"
                                                  action="<?= e(BASE_URL) ?>/process/cart_process.php"
                                                  class="d-flex align-items-center gap-1">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="redirect" value="cart">
                                                <input type="hidden" name="product_id" value="<?= (int)$id ?>">

                                                <button type="submit" name="step" value="-1"
                                                        class="btn btn-sm btn-outline-secondary"
                                                        <?= ($qty <= 1) ? 'disabled' : '' ?>>-</button>

                                                <input type="number" name="qty" value="<?= (int)$qty ?>"
                                                       min="1" max="<?= (int)$p['stock'] ?>"
                                                       class="form-control form-control-sm text-center">

                                                <button type="submit" name="step" value="1"
                                                        class="btn btn-sm btn-outline-secondary"
                                                        <?= ($qty >= (int)$p['stock']) ? 'disabled' : '' ?>>+</button>
                                            </form>
                                        </td>
                                        <td><?= e(formatRupiah((float)$p['price'] * $qty)) ?></td>
                                        <td class="text-end">
                                            <form method="post" action="<?= e(BASE_URL) ?>/process/cart_process.php">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="redirect" value="cart">
                                                <input type="hidden" name="product_id" value="<?= (int)$id ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Kosongkan seluruh keranjang, dikonfirmasi oleh cart.js -->
                    <form method="post" action="<?= e(BASE_URL) ?>/process/cart_process.php" class="text-end">
                        <input type="hidden" name="action" value="clear">
                        <input type="hidden" name="redirect" value="cart">
                        <button type="submit" class="btn btn-outline-danger btn-sm"
                                data-confirm="Kosongkan seluruh keranjang?">
                            Kosongkan Keranjang
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Ringkasan + form checkout -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Ringkasan</h5>

                    <div class="d-flex justify-content-between mb-3">
                        <span>Total</span>
                        <strong><?= e(formatRupiah($total)) ?></strong>
                    </div>

                    <form method="post" action="<?= e(BASE_URL) ?>/process/checkout_process.php">
                        <div class="mb-3">
                            <label class="form-label">Metode Pembayaran</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method"
                                       id="pay_cash" value="cash" checked>
                                <label class="form-check-label" for="pay_cash">Tunai</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method"
                                       id="pay_qris" value="qris">
                                <label class="form-check-label" for="pay_qris">QRIS</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="note">Catatan untuk Dapur</label>
                            <textarea name="note" id="note" rows="2" class="form-control"
                                      placeholder="contoh: less ice, tanpa gula"></textarea>
                        </div>

                        <button type="submit" class="btn btn-success w-100">
                            Checkout & Simpan Pesanan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?> 