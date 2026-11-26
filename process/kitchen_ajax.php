<?php
/**
 * =====================================================
 * File    : kitchen_ajax.php
 * Fungsi  : Endpoint JSON untuk polling Kitchen Display
 * Project : POS Coffee Shop (Project 2)
 * Author  : Muhammad Zaky Rahman
 * Depends : db_connect.php, auth_check.php
 * =====================================================
 */

require_once __DIR__ . '/../includes/auth_check.php';

// Aturan keras Bab 9: endpoint AJAX pun wajib cek role di baris pertama
requireRole('kitchen');

// Beri tahu browser: respons ini JSON, bukan HTML
header('Content-Type: application/json');

// Query: ambil pesanan aktif beserta rincian itemnya
// WHERE status IN (...): hanya pesanan yang masih dikerjakan dapur
// ORDER BY created_at ASC: pesanan terlama paling atas (FIFO)
$sql = "SELECT o.id, o.order_number, o.status, o.note, o.created_at,
               od.product_name, od.quantity
        FROM orders o
        JOIN order_details od ON od.order_id = o.id
        WHERE o.status IN ('pending','processing')
        ORDER BY o.created_at ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// GROUPING: hasil query berbentuk flat (satu baris per item).
// Kita susun ulang menjadi satu objek per pesanan dengan array items,
// agar JavaScript tinggal merender kartu tanpa memikirkan pengelompokan.
$orders = [];
foreach ($rows as $row) {
    $oid = (int)$row['id'];

    if (!isset($orders[$oid])) {
        $orders[$oid] = [
            'id'           => $oid,
            'order_number' => $row['order_number'],
            'status'       => $row['status'],
            'note'         => $row['note'],
            'created_at'   => $row['created_at'],
            'items'        => [],
        ];
    }

    $orders[$oid]['items'][] = [
        'product_name' => $row['product_name'],
        'quantity'     => (int)$row['quantity'],
    ];
}

// array_values agar JSON berupa list [ ... ], bukan object keyed by id
echo json_encode(array_values($orders)); 