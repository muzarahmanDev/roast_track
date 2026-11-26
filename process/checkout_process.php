<?php
/**
 * =====================================================
 * File    : checkout_process.php
 * Fungsi  : Memproses pembayaran & menyimpan pesanan (DB transaction)
 * Project : POS Coffee Shop (Project 2)
 * Author  : Muhammad Zaky Rahman
 * Depends : db_connect.php, auth_check.php, functions.php
 * =====================================================
 */

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

// Baris WAJIB: hanya kasir yang boleh checkout
requireRole('cashier');

// Halaman proses hanya menerima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/cashier/cart.php');
    exit;
}

$cart = $_SESSION['cart'] ?? [];

// Keranjang kosong tidak boleh checkout
if (!$cart) {
    setFlash('danger', 'Keranjang kosong, tidak ada yang bisa di-checkout.');
    header('Location: ' . BASE_URL . '/pages/cashier/cart.php');
    exit;
}

// Validasi metode pembayaran: hanya nilai ENUM yang diizinkan database
$payment = $_POST['payment_method'] ?? '';
if (!in_array($payment, ['cash', 'qris'], true)) {
    setFlash('danger', 'Metode pembayaran tidak valid.');
    header('Location: ' . BASE_URL . '/pages/cashier/cart.php');
    exit;
}

// Catatan dapur opsional, dibersihkan dari spasi berlebih
$note = trim($_POST['note'] ?? '');

try {
    // ===== MULAI TRANSAKSI (Bab 6.3) =====
    // Semua langkah di bawah harus sukses bersama-sama.
    // Jika satu gagal, semuanya dibatalkan (rollBack).
    $pdo->beginTransaction();

    $items = [];
    $total = 0;

    foreach ($cart as $productId => $qty) {
        // Query: kunci baris menu selama transaksi berlangsung
        // FOR UPDATE : transaksi lain yang checkout menu sama harus menunggu,
        //              sehingga stok yang dibaca selalu stok terbaru
        //              dan stok tidak pernah minus
        $sql = "SELECT id, name, price, stock
                FROM products
                WHERE id = :id
                FOR UPDATE";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $productId]);
        $product = $stmt->fetch();

        // Menu hilang dari database di tengah proses
        if (!$product) {
            throw new Exception('Menu tidak ditemukan lagi di database.');
        }

        // Cek stok ulang DI DALAM transaksi (aturan Bab 6.3)
        if ((int)$product['stock'] < $qty) {
            throw new Exception(
                'Stok "' . $product['name'] . '" tidak mencukupi (sisa ' . (int)$product['stock'] . ').'
            );
        }

        // Harga dibaca dari database, bukan dari session (anti manipulasi)
        $subtotal = (float)$product['price'] * $qty;
        $total   += $subtotal;

        $items[] = [
            'product_id'   => (int)$product['id'],
            'product_name' => $product['name'],   // snapshot nama saat transaksi
            'price_at_time'=> (float)$product['price'], // snapshot harga saat transaksi
            'quantity'     => $qty,
            'subtotal'     => $subtotal,
        ];
    }

    // Buat nomor order unik format TRX-YYYYMMDD-XXXX
    $orderNumber = generateOrderNumber($pdo);

    // Query: simpan header transaksi
    // status diisi 'pending' agar langsung masuk antrean kitchen (Bab 3)
    $sql = "INSERT INTO orders
                (order_number, cashier_id, total_amount, payment_method, status, note)
            VALUES
                (:order_number, :cashier_id, :total_amount, :payment_method, 'pending', :note)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'order_number'   => $orderNumber,
        'cashier_id'     => $_SESSION['user_id'],
        'total_amount'   => $total,
        'payment_method' => $payment,
        'note'           => $note,
    ]);

    // id order yang baru dibuat, dipakai untuk struk & rincian
    $orderId = (int)$pdo->lastInsertId();

    foreach ($items as $item) {
        // Query: simpan rincian transaksi beserta snapshot nama & harga
        // Snapshot membuat riwayat tidak berubah walau menu diedit nanti
        $sql = "INSERT INTO order_details
                    (order_id, product_id, product_name, price_at_time, quantity, subtotal)
                VALUES
                    (:order_id, :product_id, :product_name, :price_at_time, :quantity, :subtotal)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'order_id'      => $orderId,
            'product_id'    => $item['product_id'],
            'product_name'  => $item['product_name'],
            'price_at_time' => $item['price_at_time'],
            'quantity'      => $item['quantity'],
            'subtotal'      => $item['subtotal'],
        ]);

        // Query: kurangi stok setelah semua validasi lolos
        // stock = stock - :qty : pengurangan dilakukan oleh MySQL, bukan PHP,
        //                        agar konsisten dengan baris yang terkunci
        $sql = "UPDATE products
                SET stock = stock - :qty
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'qty' => $item['quantity'],
            'id'  => $item['product_id'],
        ]);
    }

    // ===== SEMUA SUKSES: SIMPAN PERMANEN =====
    $pdo->commit();

    // Keranjang selesai dipakai, bersihkan session
    $_SESSION['cart'] = [];

    setFlash('success', 'Transaksi ' . $orderNumber . ' tersimpan. Pesanan masuk ke dapur.');
    header('Location: ' . BASE_URL . '/pages/cashier/struk.php?order_id=' . $orderId);
    exit;
} catch (Exception $e) {
    // ===== ADA YANG GAGAL: BATALKAN SEMUA =====
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    setFlash('danger', 'Checkout gagal: ' . $e->getMessage());
    header('Location: ' . BASE_URL . '/pages/cashier/cart.php');
    exit;
} 