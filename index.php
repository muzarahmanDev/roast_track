<?php
/**
 * =====================================================
 * File    : index.php
 * Fungsi  : Entry point, redirect sesuai kondisi login
 * Project : POS Coffee Shop (Project 2)
 * Author  : Muhammad Zaky Rahman
 * Depends : auth_check.php
 * =====================================================
 */

require_once __DIR__ . '/includes/auth_check.php';

// Jika sudah login, redirect ke halaman sesuai role
if (isLoggedIn()) {
    redirectByRole();
}

// Jika belum login, arahkan ke halaman login
header('Location: ' . BASE_URL . '/pages/login.php');
exit;