/**
 * =====================================================
 * File    : cart.js
 * Fungsi  : Logika tombol keranjang (auto-update qty & konfirmasi)
 * Project : POS Coffee Shop (Project 2)
 * Author  : Muhammad Zaky Rahman
 * Depends : pages/cashier/cart.php
 * =====================================================
 */

document.addEventListener('DOMContentLoaded', function () {

    // 1) Input qty diubah manual -> submit form barisnya otomatis
    //    sehingga kasir tidak perlu menekan tombol tambahan
    document.querySelectorAll('input[name="qty"]').forEach(function (input) {
        input.addEventListener('change', function () {
            this.form.submit();
        });
    });

    // 2) Tombol ber-atribut data-confirm meminta konfirmasi dulu
    //    dipakai oleh tombol "Kosongkan Keranjang" agar tidak kepencet sembarangan
    document.querySelectorAll('[data-confirm]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            if (!confirm(btn.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });
}); 