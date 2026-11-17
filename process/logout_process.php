<?php
/**
 * =====================================================
 * File    : logout_process.php
 * Fungsi  : Menghapus session dan logout user
 * Project : POS Coffee Shop (Project 2)
 * Author  : Muhammad Zaky Rahman
 * Depends : db_connect.php
 * =====================================================
 */

require_once __DIR__ . '/../includes/db_connect.php';

// Kosongkan semua data session
$_SESSION = [];

// Hapus session di server
session_destroy();

// Kembali ke halaman login
header('Location: ' . BASE_URL . '/pages/login.php');
exit;