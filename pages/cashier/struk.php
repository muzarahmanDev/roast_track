<?php
/**
 * =====================================================
 * File    : struk.php
 * Fungsi  : Tampilan struk transaksi siap cetak
 * Project : POS Coffee Shop (Project 2)
 * Author  : Muhammad Zaky Rahman
 * Depends : auth_check.php, functions.php
 * =====================================================
 */

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

// Baris WAJIB: hanya kasir yang boleh melihat struk
requireRole('cashier');

$id = (int)($_GET['order_id'] ?? 0);

// Query: ambil header transaksi beserta nama kasirnya
// JOIN users : struk menampilkan siapa kasir yang melayani
$sql = "SELECT o.*, u.name AS cashier_name
        FROM orders o
        JOIN users u ON u.id = o.cashier_id
        WHERE o.id = :id
        LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $id]);
$order = $stmt->fetch();

// Order tidak ditemukan: kembalikan ke dashboard
if (!$order) {
    setFlash('danger', 'Transaksi tidak ditemukan.');
    header('Location: ' . BASE_URL . '/pages/cashier/dashboard.php');
    exit;
}

// Query: ambil rincian item transaksi ini
// ORDER BY id : urutan item sama seperti saat dimasukkan
$detailSql = "SELECT * FROM order_details WHERE order_id = :order_id ORDER BY id ASC";
$detailStmt = $pdo->prepare($detailSql);
$detailStmt->execute(['order_id' => $id]);
$details = $detailStmt->fetchAll();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk <?= e($order['order_number']) ?> | RoastTrack</title>

    <!-- Bootstrap 5.3 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* Struk dibuat sempit meniru kertas thermal */
        .struk {
            max-width: 340px;
            margin: 24px auto;
            background: #fff;
            padding: 20px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            box-shadow: 0 2px 8px rgba(0,0,0,.15);
        }
        .struk hr { border-style: dashed; }

        /* Saat dicetak, tombol dan latar tidak ikut tercetak */
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .struk { box-shadow: none; margin: 0; max-width: 100%; }
        }
    </style>
</head>
<body class="bg-light">

<div class="no-print text-center mt-3">
    <button onclick="window.print()" class="btn btn-primary btn-sm">🖨 Cetak Struk</button>
    <a href="<?= e(BASE_URL) ?>/pages/cashier/dashboard.php" class="btn btn-secondary btn-sm">Kembali ke Dashboard</a>
</div>

<div class="struk">
    <div class="text-center">
        <strong>☕ RoastTrack</strong><br>
        Coffee Shop POS<br>
        <?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?>
    </div>
    <hr>

    <div>No   : <?= e($order['order_number']) ?></div>
    <div>Kasir: <?= e($order['cashier_name']) ?></div>
    <div>Bayar: <?= e(strtoupper($order['payment_method'])) ?></div>
    <?php if ($order['note'] !== null && $order['note'] !== ''): ?>
        <div>Catat: <?= e($order['note']) ?></div>
    <?php endif; ?>
    <hr>

    <?php foreach ($details as $d): ?>
        <div><?= e($d['product_name']) ?></div>
        <div class="d-flex justify-content-between">
            <span><?= (int)$d['quantity'] ?> x <?= e(formatRupiah($d['price_at_time'])) ?></span>
            <span><?= e(formatRupiah($d['subtotal'])) ?></span>
        </div>
    <?php endforeach; ?>

    <hr>
    <div class="d-flex justify-content-between">
        <strong>TOTAL</strong>
        <strong><?= e(formatRupiah($order['total_amount'])) ?></strong>
    </div>
    <hr>

    <div class="text-center">Terima kasih!<br>Status: <?= e(strtoupper($order['status'])) ?></div>
</div>

</body>
</html>