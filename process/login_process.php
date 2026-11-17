<?php
/**
 * =====================================================
 * File    : login_process.php
 * Fungsi  : Memproses login user
 * Project : POS Coffee Shop (Project 2)
 * Author  : Muhammad Zaky Rahman
 * Depends : auth_check.php, functions.php
 * =====================================================
 */

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

// Halaman ini hanya boleh diakses dengan method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/login.php');
    exit;
}

// Ambil input dari form login
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Jika input kosong, kembali ke login
if ($username === '' || $password === '') {
    header('Location: ' . BASE_URL . '/pages/login.php?error=1');
    exit;
}

// Query: ambil user berdasarkan username
// WHERE username = :username : hanya cari satu username spesifik
// LIMIT 1                    : cukup ambil satu hasil
$sql = "SELECT *
        FROM users
        WHERE username = :username
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'username' => $username
]);

$user = $stmt->fetch();

// Jika user tidak ditemukan, anggap password salah
if (!$user) {
    header('Location: ' . BASE_URL . '/pages/login.php?error=1');
    exit;
}

// Verifikasi password hash
if (!password_verify($password, $user['password'])) {
    header('Location: ' . BASE_URL . '/pages/login.php?error=1');
    exit;
}

// Regenerasi session ID untuk mencegah session fixation
session_regenerate_id(true);

// Simpan data user ke session
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['name'] = $user['name'];
$_SESSION['role'] = $user['role'];

// Redirect sesuai role
redirectByRole();