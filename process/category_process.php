<?php


/**
 * =====================================================
 * File    : category_process.php
 * Fungsi  : Memproses tambah / ubah / hapus kategori
 * Project : POS Coffee Shop (Project 2)
 * Author  : Muhammad Zaky Rahman
 * Depends : db_connect.php, auth_check.php, functions.php
 * =====================================================
 */

require_once __DIR__ . '/../includes/auth_check.php'; 
require_once __DIR__ . '/../includes/functions.php'; 

requireRole('cashier'); 

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header('Location: ' . BASE_URL . '/pages/cashier/categories.php'); 
    exit; 
}

$action = $_POST['action'] ?? ''; 
$backUrl = BASE_URL . '/pages/cashier/categories.php'; 

if ($action === 'add' || $action === 'edit'){
    $name = trim($_POST['name'] ?? ''); 

    if ($name === '' || mb_strlen($name) > 100){
        setFlash('danger', 'nama kategori wajib diisi(maksimal 100 karakter).'); 
        header('Location: ' . $backUrl); 
        exit; 
    }

    if ($action === 'add'){
        $sql = "INSERT INTO categories (name) VALUES (:name)"; 
        $stmt = $pdo->prepare($sql); 
        $stmt->execute(['name' => $name]); 
        
        setFlash('succes', "kategori \"{$name}\" berhasil ditambahkan");
    }else{
        $id = (int)($_POST['id'] ?? 0); 

        $sql = "UPDATE categories SET name = :name WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'name' => $name,
            'id'   => $id,
        ]);

        setFlash('success', 'Kategori berhasil diperbarui.');
    }
} elseif ($action === 'delete'){
    $id = (int)($_POST['id'] ?? 0); 

    $checkSql = "SELECT COUNT (*) AS total FROM products WHERE category_id = :id"; 
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute(['id' => $id]);
    $used = (int)$checkStmt->fetch()['total']; 

    if($used > 0){
        setFlash('danger', "kategori tidak bisa dihapus karena masih dipakai {$used} menu.");  
    }else{
        $sql = "DELETE FROM categories WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]); 

        setFlash('succes', "kategori berhasil dihapus");  
    }

    header('Location' . $backUrl); 
    exit; 
}






?>