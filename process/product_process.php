<?php
/**
 * =====================================================
 * File    : product_process.php
 * Fungsi  : Memproses tambah / ubah / hapus menu + upload foto
 * Project : POS Coffee Shop (Project 2)
 * Author  : Muhammad Zaky Rahman
 * Depends : db_connect.php, auth_check.php, functions.php
 * =====================================================
 */

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

// Baris WAJIB: hanya kasir yang boleh mengelola menu
requireRole('cashier');

// Halaman proses hanya menerima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/cashier/products.php');
    exit;
}

// Folder fisik penyimpanan foto menu (Bab 5 SDD)
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/products/');

/**
 * Validasi dan simpan file foto menu sesuai Bab 6.2 SDD
 * @param array $file Elemen $_FILES['image']
 * @param string $menuName Nama menu, dipakai sebagai awalan nama file
 * @return array ['ok' => bool, 'filename' => string|null, 'error' => string]
 * Cara kerja: cek error upload → cek ukuran → cek MIME dari ISI file →
 *             rename acak → move_uploaded_file
 */
function handleProductUpload($file, $menuName)
{
    // Tidak ada file dikirim: bukan error, cukup berarti "tanpa foto baru"
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE || (int)$file['size'] === 0) {
        return ['ok' => true, 'filename' => null, 'error' => ''];
    }

    // Error level PHP (misal melebihi upload_max_filesize di php.ini)
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'filename' => null, 'error' => 'Upload gagal (error code ' . $file['error'] . ').'];
    }

    // Aturan Bab 6.2: ukuran maksimal 2 MB
    if ($file['size'] > 2 * 1024 * 1024) {
        return ['ok' => false, 'filename' => null, 'error' => 'Ukuran foto maksimal 2 MB.'];
    }

    // Aturan Bab 6.2: whitelist dicek dari ISI file, bukan dari nama
    // finfo membaca magic bytes file sehingga file .php yang diganti nama
    // menjadi .jpg tetap ketahuan bukan gambar
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);

    // Pemetaan MIME yang diizinkan ke ekstensi tujuan
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime])) {
        return ['ok' => false, 'filename' => null, 'error' => 'Format foto harus jpg, jpeg, png, atau webp.'];
    }

    // Pastikan benar-benar gambar yang bisa dibaca mesin
    if (@getimagesize($file['tmp_name']) === false) {
        return ['ok' => false, 'filename' => null, 'error' => 'File bukan gambar yang valid.'];
    }

    // Aturan Bab 6.2: rename file menjadi namamenu_uniqid().ext
    // Nama menu dibersihkan: karakter aneh diganti underscore, huruf kecil semua
    $base = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $menuName), '_'));
    if ($base === '') {
        $base = 'menu';
    }
    $filename = $base . '_' . uniqid() . '.' . $allowed[$mime];

    // Pindahkan file dari temp PHP ke folder uploads products
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $filename)) {
        return ['ok' => false, 'filename' => null, 'error' => 'Gagal menyimpan file ke server.'];
    }

    return ['ok' => true, 'filename' => $filename, 'error' => ''];
}

/**
 * Hapus file foto lama dari folder uploads
 * @param string|null $filename Nama file yang akan dihapus
 * @return void
 * Cara kerja: default.jpg tidak boleh pernah dihapus karena foto fallback
 */
function deleteOldImage($filename)
{
    if ($filename && $filename !== 'default.jpg' && file_exists(UPLOAD_DIR . $filename)) {
        unlink(UPLOAD_DIR . $filename);
    }
}

$action  = $_POST['action'] ?? '';
$backUrl = BASE_URL . '/pages/cashier/products.php';

if ($action === 'add' || $action === 'edit') {
    // ---------- VALIDASI INPUT FORM ----------
    $name        = trim($_POST['name'] ?? '');
    $categoryId  = (int)($_POST['category_id'] ?? 0);
    $price       = (float)str_replace(',', '.', $_POST['price'] ?? 0);
    $stock       = (int)($_POST['stock'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    // Checkbox tidak terkirim saat tidak dicentang, jadi absen = 0
    $isAvailable = isset($_POST['is_available']) ? 1 : 0;

    if ($name === '' || mb_strlen($name) > 100) {
        setFlash('danger', 'Nama menu wajib diisi (maksimal 100 karakter).');
        header('Location: ' . $backUrl);
        exit;
    }

    if ($price <= 0) {
        setFlash('danger', 'Harga harus lebih besar dari 0.');
        header('Location: ' . $backUrl);
        exit;
    }

    if ($stock < 0) {
        setFlash('danger', 'Stok tidak boleh negatif.');
        header('Location: ' . $backUrl);
        exit;
    }

    // Query: pastikan kategori yang dipilih benar-benar ada
    // Agar pesan error ramah, tidak menunggu error foreign key
    $catSql = "SELECT id FROM categories WHERE id = :id LIMIT 1";
    $catStmt = $pdo->prepare($catSql);
    $catStmt->execute(['id' => $categoryId]);
    if (!$catStmt->fetch()) {
        setFlash('danger', 'Kategori tidak valid.');
        header('Location: ' . $backUrl);
        exit;
    }

    // ---------- URUSAN UPLOAD FOTO ----------
    $upload = handleProductUpload($_FILES['image'] ?? null, $name);

    if ($action === 'add') {
        // Aturan Bab 6.2: upload gagal pun menu TETAP tersimpan,
        // fotonya memakai default.jpg
        $image = 'default.jpg';
        if ($upload['ok'] && $upload['filename']) {
            $image = $upload['filename'];
        }

        // Query: simpan menu baru
        // image hanya menyimpan NAMA FILE, file fisik ada di assets/uploads/products/
        $sql = "INSERT INTO products
                    (category_id, name, description, price, stock, image, is_available)
                VALUES
                    (:category_id, :name, :description, :price, :stock, :image, :is_available)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'category_id'  => $categoryId,
            'name'         => $name,
            'description'  => $description,
            'price'        => $price,
            'stock'        => $stock,
            'image'        => $image,
            'is_available' => $isAvailable,
        ]);

        if (!$upload['ok']) {
            setFlash('warning', 'Menu tersimpan, tetapi foto ditolak: ' . $upload['error'] . ' Dipakai foto default.');
        } else {
            setFlash('success', "Menu \"{$name}\" berhasil ditambahkan.");
        }
    } else {
        $id = (int)($_POST['id'] ?? 0);

        // Query: ambil data lama untuk tahu nama file foto sebelumnya
        $oldSql = "SELECT image FROM products WHERE id = :id LIMIT 1";
        $oldStmt = $pdo->prepare($oldSql);
        $oldStmt->execute(['id' => $id]);
        $old = $oldStmt->fetch();

        if (!$old) {
            setFlash('danger', 'Menu tidak ditemukan.');
            header('Location: ' . $backUrl);
            exit;
        }

        // Default: pertahankan foto lama jika tidak ada foto baru yang valid
        $image = $old['image'];

        if ($upload['ok'] && $upload['filename']) {
            // Ada foto baru yang lolos validasi: ganti dan hapus file lama
            $image = $upload['filename'];
            deleteOldImage($old['image']);
        } elseif (!$upload['ok']) {
            // Upload baru ditolak: menu tetap diperbarui, foto lama dipertahankan
            setFlash('warning', 'Data menu diperbarui, tetapi foto baru ditolak: ' . $upload['error']);
        }

        // Query: perbarui data menu berdasarkan id
        $sql = "UPDATE products
                SET category_id  = :category_id,
                    name         = :name,
                    description  = :description,
                    price        = :price,
                    stock        = :stock,
                    image        = :image,
                    is_available = :is_available
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'category_id'  => $categoryId,
            'name'         => $name,
            'description'  => $description,
            'price'        => $price,
            'stock'        => $stock,
            'image'        => $image,
            'is_available' => $isAvailable,
            'id'           => $id,
        ]);

        if (!isset($_SESSION['flash'])) {
            setFlash('success', "Menu \"{$name}\" berhasil diperbarui.");
        }
    }
} elseif ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);

    // Query: ambil nama file foto sebelum barisnya dihapus
    $imgSql = "SELECT image FROM products WHERE id = :id LIMIT 1";
    $imgStmt = $pdo->prepare($imgSql);
    $imgStmt->execute(['id' => $id]);
    $row = $imgStmt->fetch();

    if ($row) {
        // Query: hapus menu dari database
        $sql = "DELETE FROM products WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        // Bersihkan file fotonya agar folder uploads tidak menumpuk sampah
        deleteOldImage($row['image']);

        setFlash('success', 'Menu berhasil dihapus.');
    } else {
        setFlash('danger', 'Menu tidak ditemukan.');
    }
}

// Pola POST-Redirect-GET
header('Location: ' . $backUrl);
exit;