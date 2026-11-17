<?php
/**
 * =====================================================
 * File    : auth_check.php
 * Fungsi  : Proteksi halaman berdasarkan login dan role
 * Project : POS Coffee Shop (Project 2)
 * Author  : Muhammad Zaky Rahman
 * Depends : db_connect.php
 * =====================================================
 */

require_once __DIR__ . '/db_connect.php';

/**
 * Memastikan user sudah login dan memiliki role yang sesuai
 * @param string $allowedRole 'cashier' atau 'kitchen'
 * @return void
 * Cara kerja: cek session; jika tidak sesuai, redirect ke halaman yang benar
 */
function requireRole($allowedRole)
{
    // Jika user belum login, arahkan ke halaman login
    if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
        header('Location: ' . BASE_URL . '/pages/login.php');
        exit;
    }

    $currentRole = $_SESSION['role'];

    // Jika role tidak cocok, lempar user ke halaman sesuai role miliknya
    if ($currentRole !== $allowedRole) {
        if ($currentRole === 'cashier') {
            header('Location: ' . BASE_URL . '/pages/cashier/dashboard.php');
        } elseif ($currentRole === 'kitchen') {
            header('Location: ' . BASE_URL . '/pages/kitchen/display.php');
        } else {
            // Role tidak dikenal: buang session dan kembali ke login
            session_destroy();
            header('Location: ' . BASE_URL . '/pages/login.php');
        }
        exit;
    }
}

/**
 * Cek apakah user sedang login
 * @return bool true jika session user_id tersedia
 */
function isLoggedIn()
{
    return !empty($_SESSION['user_id']);
}

/**
 * Redirect user ke halaman sesuai role setelah login
 * @return void
 */
function redirectByRole()
{
    // Jika role tidak ada, kembali ke login
    if (empty($_SESSION['role'])) {
        header('Location: ' . BASE_URL . '/pages/login.php');
        exit;
    }

    // Kasir masuk ke dashboard kasir
    if ($_SESSION['role'] === 'cashier') {
        header('Location: ' . BASE_URL . '/pages/cashier/dashboard.php');
        exit;
    }

    // Kitchen masuk ke kitchen display
    header('Location: ' . BASE_URL . '/pages/kitchen/display.php');
    exit;
}