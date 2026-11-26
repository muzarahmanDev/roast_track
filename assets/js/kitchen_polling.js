/**
 * =====================================================
 * File    : kitchen_polling.js
 * Fungsi  : AJAX polling untuk Kitchen Display & Pesanan Aktif kasir
 * Project : POS Coffee Shop (Project 2)
 * Author  : Muhammad Zaky Rahman
 * Depends : pages/kitchen/display.php, pages/cashier/active_orders.php
 * =====================================================
 */
(function () {
    'use strict';

    /**
     * Escape teks sebelum disisipkan ke HTML (anti-XSS sisi klien)
     * @param {string|null} s Teks mentah dari server
     * @return {string} Teks aman untuk innerHTML
     * Cara kerja: menitipkan teks ke textContent elemen sementara,
     *             lalu membaca kembali versi yang sudah di-escape
     */
    function esc(s) {
        var d = document.createElement('div');
        d.textContent = (s === null || s === undefined) ? '' : String(s);
        return d.innerHTML;
    }

    /**
     * Format timestamp MySQL "YYYY-MM-DD HH:MM:SS" menjadi "HH:MM"
     * @param {string} mysqlDate String tanggal dari database
     * @return {string} Jam dan menit saja agar kartu tidak penuh
     */
    function timeHM(mysqlDate) {
        // Spasi diganti T agar parser Date mengerti format ISO
        var d = new Date(String(mysqlDate).replace(' ', 'T'));
        var h = String(d.getHours()).padStart(2, '0');
        var m = String(d.getMinutes()).padStart(2, '0');
        return h + ':' + m;
    }

    /**
     * Format angka menjadi Rupiah sederhana untuk tampilan tabel kasir
     * @param {number} n Nominal uang
     * @return {string} Contoh: "Rp 36.000"
     */
    function rupiah(n) {
        return 'Rp ' + Number(n).toLocaleString('id-ID');
    }

    /**
     * Render kartu pesanan untuk layar kitchen (KDS)
     * @param {HTMLElement} board Kontainer #kds-board
     * @param {Array} orders Daftar pesanan dari kitchen_ajax.php
     * @param {string} statusUrl Alamat process/status_process.php
     * @return void
     * Cara kerja: bangun string HTML kartu; tombol menyesuaikan status
     */
    function renderKitchen(board, orders, statusUrl) {
        // Tidak ada pesanan aktif: tampilkan pesan santai, bukan layar kosong
        if (!orders.length) {
            board.innerHTML =
                '<div class="col-12"><div class="alert alert-success mb-0">' +
                'Tidak ada pesanan aktif. Dapur santai dulu ☕</div></div>';
            return;
        }

        var html = '';
        orders.forEach(function (o) {
            // Badge warna membedakan pesanan baru vs yang sedang dimasak
            var badge = (o.status === 'pending')
                ? '<span class="badge text-bg-warning">BARU</span>'
                : '<span class="badge text-bg-info">DIMASAK</span>';

            // Tombol besar mengikuti aturan transisi Bab 3
            var btn = (o.status === 'pending')
                ? '<button name="action" value="start" class="btn btn-warning btn-lg w-100">🍳 Mulai Proses</button>'
                : '<button name="action" value="complete" class="btn btn-success btn-lg w-100">✅ Selesai</button>';

            // Daftar item pesanan: nama + jumlah
            var items = o.items.map(function (it) {
                return '<li class="list-group-item d-flex justify-content-between">' +
                       '<span>' + esc(it.product_name) + '</span>' +
                       '<span class="badge text-bg-secondary">' + esc(it.quantity) + 'x</span>' +
                       '</li>';
            }).join('');

            // Catatan dapur hanya tampil jika ada isinya
            var note = o.note
                ? '<div class="alert alert-light border small mt-2 mb-0">📝 ' + esc(o.note) + '</div>'
                : '';

            // Kolom responsif: 1 kartu per baris di HP, 3 per baris di layar lebar
            html +=
                '<div class="col-12 col-md-6 col-xl-4">' +
                    '<div class="card shadow-sm h-100">' +
                        '<div class="card-header d-flex justify-content-between align-items-center">' +
                            '<strong>' + esc(o.order_number) + '</strong>' + badge +
                        '</div>' +
                        '<div class="card-body">' +
                            '<div class="small text-muted mb-2">Masuk pukul ' + timeHM(o.created_at) + '</div>' +
                            '<ul class="list-group list-group-flush">' + items + '</ul>' +
                            note +
                        '</div>' +
                        '<div class="card-body pt-0">' +
                            '<form method="post" action="' + statusUrl + '">' +
                                '<input type="hidden" name="order_id" value="' + esc(o.id) + '">' +
                                btn +
                            '</form>' +
                        '</div>' +
                    '</div>' +
                '</div>';
        });

        board.innerHTML = html;
    }

    /**
     * Render tabel pesanan aktif untuk layar kasir
     * @param {HTMLElement} box Kontainer #active-orders
     * @param {Array} orders Daftar pesanan dari orders_ajax.php
     * @param {string} cancelUrl Alamat process/cancel_process.php
     * @return void
     * Cara kerja: bangun baris tabel; tombol batal hanya untuk status pending
     */
    function renderCashier(box, orders, cancelUrl) {
        if (!orders.length) {
            box.innerHTML = '<p class="text-muted">Belum ada transaksi hari ini.</p>';
            return;
        }

        var rows = orders.map(function (o) {
            // Badge status: completed ditampilkan sebagai "Siap Disajikan"
            var badge = {
                pending:    '<span class="badge text-bg-warning">Menunggu</span>',
                processing: '<span class="badge text-bg-info">Sedang Dimasak</span>',
                completed:  '<span class="badge text-bg-success">Siap Disajikan</span>'
            }[o.status] || esc(o.status);

            // Aturan Bab 3: pembatalan hanya tersedia saat pending
            var act = (o.status === 'pending')
                ? '<form method="post" action="' + cancelUrl + '"' +
                  ' onsubmit="return confirm(\'Batalkan pesanan ' + esc(o.order_number) + '?\');">' +
                  '<input type="hidden" name="order_id" value="' + esc(o.id) + '">' +
                  '<button class="btn btn-sm btn-outline-danger">Batalkan</button></form>'
                : '<span class="text-muted small">—</span>';

            // Ringkasan item: "2x Es Kopi Susu, 1x Croissant"
            var items = o.items.map(function (it) {
                return esc(it.quantity) + 'x ' + esc(it.product_name);
            }).join(', ');

            return '<tr>' +
                   '<td>' + esc(o.order_number) + '</td>' +
                   '<td>' + timeHM(o.created_at) + '</td>' +
                   '<td>' + items + '</td>' +
                   '<td>' + rupiah(o.total) + '</td>' +
                   '<td>' + badge + '</td>' +
                   '<td class="text-end">' + act + '</td>' +
                   '</tr>';
        }).join('');

        box.innerHTML =
            '<div class="table-responsive"><table class="table table-hover align-middle">' +
            '<thead><tr><th>No. Order</th><th>Jam</th><th>Item</th><th>Total</th>' +
            '<th>Status</th><th class="text-end">Aksi</th></tr></thead>' +
            '<tbody>' + rows + '</tbody></table></div>';
    }

    document.addEventListener('DOMContentLoaded', function () {
        // ---------- Layar kitchen ----------
        var board = document.getElementById('kds-board');
        if (board) {
            var pollKitchen = function () {
                // fetch = pertanyaan berkala ke server (Bab 6.4)
                fetch(board.dataset.ajax)
                    .then(function (r) { return r.json(); })
                    .then(function (orders) { renderKitchen(board, orders, board.dataset.statusUrl); })
                    .catch(function () {
                        // Jika server sesaat error, konten lama dipertahankan;
                        // percobaan berikutnya terjadi 5 detik kemudian
                    });
            };
            pollKitchen();                  // panggil langsung agar tidak menunggu 5 detik pertama
            setInterval(pollKitchen, 5000); // lalu ulang setiap 5 detik
        }

        // ---------- Layar kasir ----------
        var active = document.getElementById('active-orders');
        if (active) {
            var pollCashier = function () {
                fetch(active.dataset.ajax)
                    .then(function (r) { return r.json(); })
                    .then(function (orders) { renderCashier(active, orders, active.dataset.cancelUrl); })
                    .catch(function () {});
            };
            pollCashier();
            setInterval(pollCashier, 5000);
        }
    });
})(); 