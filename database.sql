-- =====================================================
-- File    : database.sql
-- Fungsi  : Membuat database dan struktur tabel RoastTrack
-- Project : POS Coffee Shop (Project 2)
-- Author  : Muhammad Zaky Rahman
-- Cara pakai: source D:/development/RoastTrack/database.sql
-- =====================================================

-- Buang database lama agar re-run selalu bersih dari tabel setengah jadi
DROP DATABASE IF EXISTS roast_track;

-- Buat database baru dengan charset utf8mb4 agar mendukung semua karakter
CREATE DATABASE roast_track
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE roast_track;

-- =====================================================
-- TABEL: categories
-- Fungsi: menyimpan kategori menu (Kopi, Non-Kopi, Snack)
-- =====================================================
CREATE TABLE categories (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABEL: users
-- Fungsi: akun kasir dan dapur; role membatasi akses halaman
-- =====================================================
CREATE TABLE users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('cashier','kitchen') NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABEL: products
-- Fungsi: master data menu; image hanya menyimpan nama file
-- =====================================================
CREATE TABLE products (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    category_id INT(11) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT(11) NOT NULL DEFAULT 0,
    image VARCHAR(255),
    is_available TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABEL: orders (header transaksi)
-- status: mesin alur pesanan pending/processing/completed/cancelled
-- =====================================================
CREATE TABLE orders (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(30) NOT NULL UNIQUE,
    cashier_id INT(11) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash','qris') NOT NULL,
    status ENUM('pending','processing','completed','cancelled') DEFAULT 'pending',
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cashier_id) REFERENCES users(id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABEL: order_details (rincian transaksi)
-- product_name & price_at_time = snapshot agar riwayat tidak berubah
-- =====================================================
CREATE TABLE order_details (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    order_id INT(11) NOT NULL,
    product_id INT(11) NOT NULL,
    product_name VARCHAR(100) NOT NULL,
    price_at_time DECIMAL(10,2) NOT NULL,
    quantity INT(11) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Seed kategori awal
-- =====================================================
INSERT INTO categories (id, name) VALUES
(1, 'Kopi'),
(2, 'Non-Kopi'),
(3, 'Snack');