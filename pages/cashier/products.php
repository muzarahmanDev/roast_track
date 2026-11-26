<?php 
/**
 * =====================================================
 * File    : products.php
 * Fungsi  : Halaman CRUD menu + upload foto (khusus kasir)
 * Project : POS Coffee Shop (Project 2)
 * Author  : Muhammad Zaky Rahman
 * Depends : auth_check.php, functions.php, header.php, footer.php
 * =====================================================
 */

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php'; 

requireRole('cashier'); 

$sql = "SELECT p.*, c.name AS category_name
        FROM products p
        JOIN categories c ON p.category_id = c.id
        ORDER BY p.name ASC"; 

$products = $pdo->query($sql)->fetchAll(); 

$products = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll(); 

$edit = null; 
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => (int)$_GET['edit']]);
    $edit = $stmt->fetch();
}

$pageTitle = 'Menu'; 
require_once __DIR__ . '/../../includes/header.php'; 
?>

<?php $flash = getFlash(); if ($flash): ?> 
<div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?> 

<div class="row">
    <!-- Kolom form tambah / edit menu -->
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title"><?= $edit ? 'Edit Menu' : 'Tambah Menu' ?></h5>

                <!-- enctype wajib multipart/form-data agar file bisa dikirim -->
                <form method="post"
                      action="<?= e(BASE_URL) ?>/process/product_process.php"
                      enctype="multipart/form-data">

                    <input type="hidden" name="action" value="<?= $edit ? 'edit' : 'add' ?>">

                    <?php if ($edit): ?>
                        <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Nama Menu</label>
                        <input type="text" name="name" class="form-control"
                               maxlength="100" value="<?= e($edit['name'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">-- pilih kategori --</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= (int)$c['id'] ?>"
                                    <?= ($edit && (int)$edit['category_id'] === (int)$c['id']) ? 'selected' : '' ?>>
                                    <?= e($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Harga (Rp)</label>
                            <input type="number" name="price" class="form-control"
                                   min="0" step="0.01"
                                   value="<?= e($edit['price'] ?? '') ?>" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Stok</label>
                            <input type="number" name="stock" class="form-control"
                                   min="0" value="<?= e($edit['stock'] ?? 0) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="description" class="form-control"
                                  rows="2"><?= e($edit['description'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Foto Menu (jpg/jpeg/png/webp, maks 2 MB)</label>

                        <?php if ($edit): ?>
                            <div class="mb-2">
                                <img src="<?= e(BASE_URL) ?>/assets/uploads/products/<?= e($edit['image']) ?>"
                                     alt="foto lama" class="rounded" style="height:80px; object-fit:cover;">
                                <div class="form-text">Foto saat ini. Unggah file baru hanya jika ingin mengganti.</div>
                            </div>
                        <?php endif; ?>

                        <input type="file" name="image" class="form-control"
                               accept=".jpg,.jpeg,.png,.webp">
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="is_available"
                               name="is_available" value="1"
                               <?= ($edit === null || (int)($edit['is_available'] ?? 1) === 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_available">Tampilkan di menu kasir</label>
                    </div>

                    <button type="submit" class="btn btn-primary">Simpan</button>

                    <?php if ($edit): ?>
                        <a href="<?= e(BASE_URL) ?>/pages/cashier/products.php" class="btn btn-secondary">Batal</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- Kolom daftar menu -->
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Daftar Menu</h5>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>Nama</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$products): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Belum ada menu.</td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($products as $p): ?>
                                <tr>
                                    <td>
                                        <img src="<?= e(BASE_URL) ?>/assets/uploads/products/<?= e($p['image']) ?>"
                                             alt="<?= e($p['name']) ?>"
                                             class="rounded" style="height:48px; width:48px; object-fit:cover;">
                                    </td>
                                    <td><?= e($p['name']) ?></td>
                                    <td><span class="badge text-bg-secondary"><?= e($p['category_name']) ?></span></td>
                                    <td><?= e(formatRupiah($p['price'])) ?></td>
                                    <td><?= (int)$p['stock'] ?></td>
                                    <td>
                                        <?php if ((int)$p['is_available'] === 1): ?>
                                            <span class="badge text-bg-success">Tampil</span>
                                        <?php else: ?>
                                            <span class="badge text-bg-danger">Disembunyikan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= e(BASE_URL) ?>/pages/cashier/products.php?edit=<?= (int)$p['id'] ?>"
                                           class="btn btn-sm btn-outline-primary">Edit</a>

                                        <form method="post"
                                              action="<?= e(BASE_URL) ?>/process/product_process.php"
                                              class="d-inline"
                                              onsubmit="return confirm('Hapus menu <?= e($p['name']) ?>?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
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