<?php
/**
 * =====================================================
 * File    : orders_ajax.php
 * Fungsi  : Endpoint JSON untuk polling halaman Pesanan Aktif kasir
 * Project : POS Coffee Shop (Project 2)
 * Author  : Muhammad Zaky Rahman
 * Depends : db_connect.php, auth_check.php
 * =====================================================
 */

require_once __DIR__ . '/../includes/auth_check.php';

// Aturan keras Bab 9: endpoint AJAX pun wajib cek role
requireRole('cashier');

// Beri tahu browser: respons ini JSON, bukan HTML
header('Content-Type: application/json');

// Query: ambil semua transaksi HARI INI yang tidak dibatalkan
// DATE(created_at)=CURDATE() : layar "aktif" cukup menampilkan hari berjalan
// status <> 'cancelled'      : pesanan batal tidak perlu dipantau lagi
// ORDER BY created_at DESC   : transaksi terbaru paling atas
$sql = "SELECT o.id, o.order_number, o.status, o.total_amount, o.created_at,
               od.product_name, od.quantity
        FROM orders o
        JOIN order_details od ON od.order_id = o.id
        WHERE DATE(o.created_at) = CURDATE()
          AND o.status <> 'cancelled'
        ORDER BY o.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// GROUPING yang sama seperti kitchen_ajax: satu objek per pesanan
$orders = [];
foreach ($rows as $row) {
    $oid = (int)$row['id'];

    if (!isset($orders[$oid])) {
        $orders[$oid] = [
            'id'           => $oid,
            'order_number' => $row['order_number'],
            'status'       => $row['status'],
            'total'        => (float)$row['total_amount'],
            'created_at'   => $row['created_at'],
            'items'        => [],
        ];
    }

    $orders[$oid]['items'][] = [
        'product_name' => $row['product_name'],
        'quantity'     => (int)$row['quantity'],
    ];
}

echo json_encode(array_values($orders)); 