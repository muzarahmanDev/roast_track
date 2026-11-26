<?php
/**
 * =====================================================
 * File    : categories.php
 * Fungsi  : Halaman CRUD kategori (khusus kasir)
 * Project : POS Coffee Shop (Project 2)
 * Author  : Muhammad Zaky Rahman
 * Depends : auth_check.php, functions.php, header.php, footer.php
 * =====================================================
 */

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

// Baris WAJIB di semua halaman terproteksi (Bab 6.1)
requireRole('cashier');

// Query: ambil semua kategori beserta jumlah menu di dalamnya
// Subquery COUNT : kasir bisa melihat kategori masih dipakai atau tidak
// ORDER BY name  : urutkan alfabetis agar mudah dicari
$sql = "SELECT c.*,
               (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count
        FROM categories c
        ORDER BY c.name ASC";
$categories = $pdo->query($sql)->fetchAll();

// Mode edit: jika URL mengandung ?edit=ID, muat data kategori tersebut ke form
$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => (int)$_GET['edit']]);
    $edit = $stmt->fetch();
}

$pageTitle = 'Kategori';
require_once __DIR__ . '/../../includes/header.php';
?>

<?php $flash = getFlash(); if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Kolom form tambah / edit -->
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title">
                    <?= $edit ? 'Edit Kategori' : 'Tambah Kategori' ?>
                </h5>

                <form method="post" action="<?= e(BASE_URL) ?>/process/category_process.php">
                    <!-- action memberi tahu proses mana yang dijalankan: add atau edit -->
                    <input type="hidden" name="action" value="<?= $edit ? 'edit' : 'add' ?>">

                    <?php if ($edit): ?>
                        <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Nama Kategori</label>
                        <input type="text"
                               name="name"
                               class="form-control"
                               maxlength="100"
                               value="<?= e($edit['name'] ?? '') ?>"
                               required>
                    </div>

                    <button type="submit" class="btn btn-primary">Simpan</button>

                    <?php if ($edit): ?>
                        <a href="<?= e(BASE_URL) ?>/pages/cashier/categories.php" class="btn btn-secondary">Batal</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- Kolom daftar kategori -->
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Daftar Kategori</h5>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama</th>
                                <th>Jumlah Menu</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$categories): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Belum ada kategori.</td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($categories as $i => $c): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= e($c['name']) ?></td>
                                    <td>
                                        <span class="badge text-bg-secondary"><?= (int)$c['product_count'] ?> menu</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= e(BASE_URL) ?>/pages/cashier/categories.php?edit=<?= (int)$c['id'] ?>"
                                           class="btn btn-sm btn-outline-primary">Edit</a>

                                        <form method="post"
                                              action="<?= e(BASE_URL) ?>/process/category_process.php"
                                              class="d-inline"
                                              onsubmit="return confirm('Hapus kategori <?= e($c['name']) ?>?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>