<?php
/**
 * =====================================================
 * File    : seed_users.php
 * Fungsi  : Membuat user default kasir dan dapur
 * Project : POS Coffee Shop (Project 2)
 * Author  : Muhammad Zaky Rahman
 * Depends : db_connect.php
 * Catatan : HAPUS file ini setelah user berhasil dibuat
 * =====================================================
 */

require_once __DIR__ . '/includes/db_connect.php';

// Daftar user default yang akan dibuat
$users = [
    [
        'username' => 'kasir',
        'password' => 'password123',
        'role'     => 'cashier',
        'name'     => 'Zaky Kasir',
    ],
    [
        'username' => 'dapur',
        'password' => 'password123',
        'role'     => 'kitchen',
        'name'     => 'Tim Dapur',
    ],
    [
        'username' => 'pemilik',
        'password' => 'owner123', 
        'role'     => 'kitchen', 
        'name'     => 'Tim Dapur', 
    ], 
];

echo '<pre>';
echo "Proses seed user default\n";
echo "========================\n\n";

foreach ($users as $user) {
    $username = $user['username'];
    $password = $user['password'];
    $role     = $user['role'];
    $name     = $user['name'];

    // Query: cek apakah username sudah ada
    // Jika sudah ada, kita skip agar tidak error unique
    $checkSql = "SELECT id
                 FROM users
                 WHERE username = :username
                 LIMIT 1";

    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([
        'username' => $username
    ]);

    if ($checkStmt->fetch()) {
        echo "- Username {$username} sudah ada, skip.\n";
        continue;
    }

    // Hash password dengan password_hash
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Query: insert user baru ke tabel users
    $insertSql = "INSERT INTO users (username, password, role, name)
                  VALUES (:username, :password, :role, :name)";

    $insertStmt = $pdo->prepare($insertSql);
    $insertStmt->execute([
        'username' => $username,
        'password' => $hashedPassword,
        'role'     => $role,
        'name'     => $name,
    ]);

    echo "+ User {$username} berhasil dibuat.\n";
    echo "  Password: {$password}\n";
    echo "  Role    : {$role}\n\n";
}

echo '</pre>';
echo '<p><strong>PENTING:</strong> Hapus file seed_users.php setelah user berhasil dibuat.</p>';