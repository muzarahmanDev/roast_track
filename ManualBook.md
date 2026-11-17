# ☕ PROJECT 2: COFFEE SHOP POS + KITCHEN DISPLAY
## Software Design Document (SDD) & Project Blueprint

**Version:** 2.0.0  
**Name Project:** RoastTrack  
**Author:** Muhammad Zaky Rahman  
**Architecture:** Procedural PHP Native (Tanpa MVC)  
**Status:** Approved for Development  

---

## 📑 DAFTAR ISI
1. Project Overview & Roles
2. Technical Stack
3. Order Workflow (Alur Status Pesanan)
4. Database Design
5. File Structure
6. Spesifikasi Teknis Inti
7. **Standar Dokumentasi Kode (WAJIB)**
8. Panduan Simulasi 2 Device (LAN)
9. Security Guidelines
10. Development Roadmap
11. Testing Checklist

---

## 1. PROJECT OVERVIEW & ROLES

### 1.1 Deskripsi
Aplikasi Point of Sale (POS) untuk kedai kopi dengan **dua stasiun kerja**: meja kasir dan dapur (kitchen). Kasir mencatat pesanan & pembayaran; dapur melihat pesanan masuk secara live dan memperbarui status pengerjaan.

### 1.2 Matriks Role & Permission

| Fitur | Kasir (cashier) | Dapur (kitchen) |
|---|---|---|
| Login | ✅ | ✅ |
| CRUD Menu + Upload Foto | ✅ | ❌ |
| CRUD Kategori | ✅ | ❌ |
| Buat Pesanan / Keranjang | ✅ | ❌ |
| Checkout & Pembayaran | ✅ | ❌ |
| Batalkan Pesanan (hanya status pending) | ✅ | ❌ |
| Lihat Kitchen Display (pesanan masuk) | ❌ | ✅ |
| Ubah Status Pesanan (processing/completed) | ❌ | ✅ |
| Riwayat Transaksi | ✅ | ❌ |
| Laporan & Ekspor Spreadsheet | ✅ | ❌ |

**Aturan keras:** Setiap halaman WAJIB memeriksa role di baris pertama. Kitchen tidak boleh membuka halaman kasir, dan sebaliknya.

### 1.3 Fitur MVP
- [ ] Login multi-role
- [ ] CRUD menu dengan **upload foto, deskripsi, harga, stok**
- [ ] Keranjang belanja via session
- [ ] Checkout dengan DB transaction + pengurangan stok
- [ ] **Kitchen Display System (KDS)** dengan update live (AJAX polling)
- [ ] Status pesanan terlihat oleh kasir (pending / processing / completed)
- [ ] Riwayat transaksi & laporan penjualan
- [ ] **Ekspor laporan ke spreadsheet (CSV yang terbuka di Excel)**
- [ ] Cetak struk
- [ ] Aksesibel dari 2 device via LAN (HP sebagai kitchen)

---

## 2. TECHNICAL STACK
- **Backend:** PHP Native Prosedural (tanpa MVC, tanpa framework)
- **Database:** MySQL (driver PDO, wajib prepared statements)
- **Frontend:** HTML5, Bootstrap 5.3 (CDN), Vanilla JavaScript (fetch API untuk polling)
- **Server Lokal:** XAMPP/Laragon (harus bisa diakses via LAN — lihat Bab 8)
- **Ekspor:** CSV dengan UTF-8 BOM (kompatibel Excel tanpa library tambahan)

---

## 3. ORDER WORKFLOW (Alur Status Pesanan)

```
 KASIR buat pesanan
        │
        ▼
   [ pending ] ──── kasir batal (hanya dari sini) ───► [ cancelled ]
        │
        │ kitchen klik "Mulai Proses"
        ▼
   [ processing ]
        │
        │ kitchen klik "Selesai"
        ▼
   [ completed ]  ──► kasir lihat badge "Siap Disajikan"
```

**Aturan bisnis:**
1. Pesanan baru selalu berstatus `pending` dan langsung muncul di layar kitchen (maksimal 10 detik, via polling).
2. Kitchen hanya bisa mengubah `pending → processing` dan `processing → completed`.
3. Kasir hanya bisa membatalkan pesanan berstatus `pending` (sudah dimasak = tidak bisa dibatalkan).
4. Setiap perubahan status oleh kitchen langsung terlihat di halaman "Pesanan Aktif" milik kasir (live, tanpa refresh manual).

---

## 4. DATABASE DESIGN

### 4.1 ERD

```
┌──────────────┐     ┌──────────────┐
│    users     │     │  categories  │
├──────────────┤     ├──────────────┤
│ id (PK)      │     │ id (PK)      │
│ username     │     │ name         │
│ password     │     └──────┬───────┘
│ role (ENUM)  │            │ 1:N
│ name         │     ┌──────▼───────┐
└──────┬───────┘     │   products   │
       │ 1:N         ├──────────────┤
       │             │ id (PK)      │
       │             │ category_id  │
       │             │ name         │
       │             │ description  │
       │             │ price        │
       │             │ stock        │
       │             │ image        │
       │             │ is_available │
       │             └──────┬───────┘
       │                    │ 1:N
┌──────▼───────┐     ┌──────▼───────┐
│    orders    │     │ order_details│
├──────────────┤     ├──────────────┤
│ id (PK)      │◄────┤ order_id(FK) │
│ order_number │     │ product_id   │
│ cashier_id   │     │ product_name │ ← snapshot
│ total_amount │     │ price_at_time│ ← snapshot
│ payment_method│    │ quantity     │
│ status (ENUM)│     │ subtotal     │
│ note         │     └──────────────┘
│ created_at   │
│ updated_at   │
└──────────────┘
```

### 4.2 DDL (dengan komentar penjelasan)

```sql
-- =====================================================
-- TABEL: users
-- Fungsi: menyimpan akun kasir dan dapur
-- role: membatasi akses halaman (lihat Bab 1.2)
-- =====================================================
CREATE TABLE users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,   -- dipakai untuk login, harus unik
    password VARCHAR(255) NOT NULL,         -- WAJIB di-hash (password_hash)
    role ENUM('cashier','kitchen') NOT NULL,-- penentu hak akses
    name VARCHAR(100) NOT NULL,             -- nama tampilan di navbar
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABEL: products
-- Fungsi: master data menu kopi
-- image: hanya menyimpan NAMA FILE, file fisiknya di assets/uploads/
-- =====================================================
CREATE TABLE products (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    category_id INT(11) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,                        -- deskripsi menu untuk tampilan
    price DECIMAL(10,2) NOT NULL,            -- DECIMAL agar tidak ada error pembulatan uang
    stock INT(11) NOT NULL DEFAULT 0,
    image VARCHAR(255),                      -- contoh: 'latte_66f3a1b2c9.jpg'
    is_available TINYINT(1) DEFAULT 1,       -- 1 = tampil di kasir, 0 = disembunyikan
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABEL: orders (header transaksi)
-- status: mesin alur pesanan (lihat Bab 3)
-- =====================================================
CREATE TABLE orders (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(30) NOT NULL UNIQUE, -- contoh: TRX-20261012-0001
    cashier_id INT(11) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash','qris') NOT NULL,
    status ENUM('pending','processing','completed','cancelled') DEFAULT 'pending',
    note TEXT,                                -- catatan utk dapur, misal: "less ice"
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cashier_id) REFERENCES users(id),
    INDEX idx_status (status)                 -- index agar polling kitchen cepat
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABEL: order_details (rincian transaksi)
-- product_name & price_at_time = SNAPSHOT:
--   disimpan agar riwayat TIDAK berubah walau harga/menu diedit nanti
-- =====================================================
CREATE TABLE order_details (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    order_id INT(11) NOT NULL,
    product_id INT(11) NOT NULL,
    product_name VARCHAR(100) NOT NULL,      -- snapshot nama saat transaksi
    price_at_time DECIMAL(10,2) NOT NULL,    -- snapshot harga saat transaksi
    quantity INT(11) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 4.3 Seed Data Awal
```sql
-- Password untuk keduanya: "password123" (sudah di-hash bcrypt)
INSERT INTO users (username, password, role, name) VALUES
('kasir', '$2y$10$...hash...', 'cashier', 'Zaky Kasir'),
('dapur', '$2y$10$...hash...', 'kitchen', 'Tim Dapur');
-- (Hash asli di-generate dengan password_hash('password123', PASSWORD_DEFAULT) saat setup)
```

---

## 5. FILE STRUCTURE (Prosedural)

```
pos-coffee/
├── assets/
│   ├── css/style.css
│   ├── js/
│   │   ├── cart.js            # logika tombol keranjang
│   │   └── kitchen_polling.js # AJAX polling utk kitchen & kasir
│   └── uploads/products/      # FOTO MENU DISIMPAN DI SINI
├── includes/
│   ├── db_connect.php         # koneksi PDO
│   ├── functions.php          # formatRupiah, generateOrderNumber, dll
│   ├── auth_check.php         # fungsi requireRole()
│   ├── header.php             # navbar menyesuaikan role
│   └── footer.php
├── pages/
│   ├── login.php
│   ├── cashier/
│   │   ├── dashboard.php      # grid menu + tombol tambah ke cart
│   │   ├── cart.php           # keranjang & checkout
│   │   ├── active_orders.php  # pantau status pesanan (live)
│   │   ├── history.php        # riwayat transaksi
│   │   ├── report.php         # laporan + tombol ekspor
│   │   ├── products.php       # CRUD menu + upload foto
│   │   └── categories.php
│   └── kitchen/
│       └── display.php        # KITCHEN DISPLAY SYSTEM (KDS)
├── process/
│   ├── login_process.php
│   ├── logout_process.php
│   ├── product_process.php    # CRUD menu + handle upload
│   ├── cart_process.php       # tambah/ubah/hapus item session
│   ├── checkout_process.php   # DB transaction inti
│   ├── cancel_process.php     # batal pesanan (pending saja)
│   ├── status_process.php     # kitchen ubah status
│   ├── kitchen_ajax.php       # endpoint JSON utk polling kitchen
│   ├── orders_ajax.php        # endpoint JSON utk polling kasir
│   └── export_report.php      # unduh CSV
├── index.php                  # redirect berdasar role
├── database.sql
└── README.md
```

---

## 6. SPESIFIKASI TEKNIS INTI

### 6.1 Kontrol Akses per Role
Setiap halaman wajib diawali:
```php
<?php
// =====================================================
// Baris WAJIB di semua halaman terproteksi
// requireRole() mengecek session; jika role tidak cocok,
// user dilempar kembali ke halaman miliknya sendiri
// =====================================================
require_once '../includes/auth_check.php';
requireRole('cashier');   // atau 'kitchen' untuk halaman dapur
```

### 6.2 Upload Foto Menu
**Aturan upload (wajib dipatuhi):**
1. Whitelist ekstensi: `jpg, jpeg, png, webp` — dicek dari **konten file**, bukan hanya dari nama.
2. Ukuran maksimal: 2 MB.
3. File di-rename acak: `namamenu_uniqid().ext` (mencegah timpa file & menyembunyikan nama asli).
4. Disimpan ke `assets/uploads/products/`, nama file saja yang masuk database.
5. Jika upload gagal, menu tetap tersimpan dengan foto default `default.jpg`.

### 6.3 Checkout dengan DB Transaction
Pola `beginTransaction → commit → rollBack` sama seperti blueprint sebelumnya, ditambah satu aturan baru: **cek stok ulang di dalam transaksi** (baca stok terbaru sebelum mengurangi) agar tidak minus.

### 6.4 Kitchen Display System (KDS) — Live Update
Karena PHP prosedural tidak punya WebSocket, kita pakai **AJAX polling**: browser kitchen bertanya ke server setiap 5–10 detik.

**Endpoint `process/kitchen_ajax.php`** mengembalikan JSON:
```php
<?php
/**
 * File   : kitchen_ajax.php
 * Fungsi : Endpoint JSON untuk polling Kitchen Display
 * Output : JSON daftar pesanan berstatus pending & processing
 */
require_once '../includes/auth_check.php';
requireRole('kitchen');                 // hanya dapur yang boleh memanggil

header('Content-Type: application/json'); // beri tahu browser: ini JSON, bukan HTML

// Query: ambil pesanan aktif beserta rincian itemnya
// WHERE status IN (...): hanya pesanan yang masih dikerjakan dapur
$sql = "SELECT o.id, o.order_number, o.status, o.note, o.created_at,
               od.product_name, od.quantity
        FROM orders o
        JOIN order_details od ON o.order_id = o.id   -- gabungkan header & rincian
        WHERE o.status IN ('pending','processing')
        ORDER BY o.created_at ASC";                    -- pesanan terlama paling atas
$stmt = $pdo->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($rows);   // kirim hasil ke JavaScript kitchen
```

**Sisi JavaScript (`kitchen_polling.js`):** `setInterval` tiap 5 detik → `fetch('kitchen_ajax.php')` → render ulang kartu pesanan → tombol "Mulai Proses"/"Selesai" mengirim POST ke `status_process.php`. Halaman kasir `active_orders.php` memakai polling yang sama ke `orders_ajax.php` agar badge status ikut live.

### 6.5 Ekspor Spreadsheet (CSV untuk Excel)
Kita pakai **CSV dengan BOM UTF-8** — terbuka sempurna di Excel tanpa library apa pun:
```php
<?php
/**
 * File   : export_report.php
 * Fungsi : Mengunduh laporan transaksi sebagai file CSV (kompatibel Excel)
 */
requireRole('cashier');

// Header HTTP: memaksa browser mengunduh file, bukan menampilkannya
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="laporan_penjualan.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");  // BOM UTF-8: agar Excel baca karakter & format dengan benar

// Baris judul kolom di Excel
fputcsv($out, ['No. Order', 'Tanggal', 'Kasir', 'Menu', 'Qty', 'Harga', 'Subtotal', 'Total', 'Metode', 'Status']);

// Query laporan: join 3 tabel untuk data lengkap per baris item
$sql = "SELECT o.order_number, o.created_at, u.name AS kasir,
               od.product_name, od.quantity, od.price_at_time, od.subtotal,
               o.total_amount, o.payment_method, o.status
        FROM orders o
        JOIN order_details od ON od.order_id = o.id
        JOIN users u ON u.id = o.cashier_id
        WHERE DATE(o.created_at) BETWEEN :start AND :end
        ORDER BY o.created_at";
// ... execute dengan bind param tanggal dari filter form ...
while ($row = $stmt->fetch()) { fputcsv($out, $row); }
fclose($out);
exit;
```
*Upgrade path (opsional nanti): jika butuh `.xlsx` asli, pakai library PhpSpreadsheet via Composer — tapi untuk portofolio level ini, CSV sudah tepat dan menunjukkan pemahaman format data.*

---

## 7. ⭐ STANDAR DOKUMENTASI KODE (WAJIB DIPATUHI)

Ini ketentuan barumu, dan mulai sekarang menjadi **hukum** di semua chat coding proyek ini. Setiap kode yang diberikan/ditulis wajib memiliki 3 lapis komentar:

**Lapis 1 — Header File** (di atas setiap file PHP/JS):
```php
<?php
/**
 * =====================================================
 * File    : checkout_process.php
 * Fungsi  : Memproses pembayaran & menyimpan pesanan
 * Project : POS Coffee Shop (Project 2)
 * Author  : Muhammad Zaky Rahman
 * Depends : db_connect.php, auth_check.php
 * =====================================================
 */
```

**Lapis 2 — Docblock Fungsi** (di atas setiap fungsi):
```php
/**
 * Membuat nomor order unik berformat TRX-YYYYMMDD-XXXX
 * @param PDO $pdo Koneksi database aktif
 * @return string Contoh: "TRX-20261012-0007"
 * Cara kerja: hitung jumlah order hari ini, lalu +1 dengan padding nol
 */
function generateOrderNumber($pdo) { ... }
```

**Lapis 3 — Komentar Inline per Query & Logika** (menjelaskan *mengapa*, bukan hanya *apa*):
```php
// Query: ambil menu yang tersedia untuk ditampilkan di grid kasir
// JOIN categories  : butuh nama kategori untuk label badge di kartu menu
// WHERE is_available=1 : menu yang disembunyikan tidak boleh muncul
// ORDER BY name    : urutkan alfabetis agar kasir cepat mencari
$sql = "SELECT p.*, c.name AS category_name
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.is_available = 1
        ORDER BY p.name ASC";
```

**Instruksi tambahan saat kamu bawa SDD ini ke chat coding:** tambahkan kalimat ini di prompt pertamamu — *"Setiap kode yang kamu berikan WAJIB mengikuti Standar Dokumentasi Kode Bab 7 pada SDD: header file, docblock fungsi, dan komentar inline yang menjelaskan setiap query SQL baris per baris."*

---

## 8. PANDUAN SIMULASI 2 DEVICE (LAN) — KITCHEN LEWAT HP

Ini cara membuat PC-mu menjadi "server mini" satu rumah:

1. **Satu WiFi:** PC (XAMPP) dan HP terhubung ke WiFi yang sama.
2. **Cek Apache mendengarkan semua interface:** buka `xampp/apache/conf/httpd.conf`, pastikan ada baris `Listen 80` (bukan `Listen 127.0.0.1:80`).
3. **Izinkan di Firewall Windows:** Control Panel → Windows Defender Firewall → Allow an app → centang **Apache HTTP Server** untuk jaringan *Private*.
4. **Cari IP PC-mu:** buka CMD → ketik `ipconfig` → catat **IPv4 Address** (misal `192.168.1.10`).
5. **Akses dari HP:** buka browser HP → ketik `http://192.168.1.10/pos-coffee/pages/login.php`.
6. **Login berbeda role:** PC login sebagai `kasir`, HP login sebagai `dapur`.
7. **Uji live:** buat pesanan di PC → dalam ≤10 detik kartu pesanan muncul di HP tanpa refresh manual (berkat polling Bab 6.4).
8. **Responsif:** karena memakai Bootstrap grid, tampilan KDS otomatis menyesuaikan layar HP. Gunakan kartu besar & tombol besar agar mudah dipencet juru masak.

*Catatan: session disimpan di server, jadi dua device dengan dua login berbeda berjalan independen tanpa konflik.*

---

## 9. SECURITY GUIDELINES
- Prepared statements di **semua** query tanpa kecuali.
- `password_hash()` / `password_verify()` untuk login.
- `requireRole()` di baris pertama setiap halaman & setiap endpoint AJAX.
- Validasi upload sesuai Bab 6.2 (cek konten file, bukan cuma ekstensi teks).
- `htmlspecialchars()` pada semua output (nama menu, catatan dapur) untuk cegah XSS.
- Folder `assets/uploads/products/` tidak boleh bisa mengeksekusi PHP (default XAMPP sudah aman, tapi verifikasi).

---

## 10. DEVELOPMENT ROADMAP

| Fase | Minggu | Target | Deliverable |
|---|---|---|---|
| 1 | 1 | Setup DB, login multi-role, template header/footer per role | Login kasir & dapur berfungsi |
| 2 | 2 | CRUD kategori + CRUD menu dengan upload foto | Menu tampil dengan gambar |
| 3 | 3 | Keranjang session + checkout DB transaction + struk | Transaksi tersimpan, stok berkurang |
| 4 | 4 | KDS kitchen + polling AJAX + ubah status + halaman active_orders kasir | Live update 2 tab/2 device |
| 5 | 5 | History, laporan, ekspor CSV, cetak struk rapi | File CSV terbuka di Excel |
| 6 | 6 | Simulasi LAN 2 device, testing penuh, README + screenshot, push GitHub | Repo publik rapi & ter-pin |

---

## 11. TESTING CHECKLIST
- [ ] Kitchen tidak bisa membuka URL halaman kasir (dan sebaliknya)
- [ ] Upload file `.php` atau `.exe` ditolak; file >2MB ditolak
- [ ] Pesanan baru muncul di HP (kitchen) ≤10 detik tanpa refresh
- [ ] Tombol "Selesai" di kitchen mengubah badge di layar kasir secara live
- [ ] Pesanan berstatus `processing` tidak bisa dibatalkan kasir
- [ ] Stok tidak pernah minus meski checkout cepat beruntun
- [ ] Riwayat transaksi tidak berubah meski harga menu diedit sesudahnya (snapshot bekerja)
- [ ] File CSV terbuka di Excel: kolom rapi, tanggal & nominal tidak berantakan
- [ ] Tampilan KDS nyaman dipakai di layar HP (tombol tidak terlalu kecil)

---