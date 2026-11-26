<?php
/**
 * =====================================================
 * File    : footer.php
 * Fungsi  : Penutup HTML + muat script tambahan per halaman
 * Project : POS Coffee Shop (Project 2)
 * Author  : Muhammad Zaky Rahman
 * =====================================================
 */
?>
</main>

<footer class="text-center small text-muted py-3">
    RoastTrack - Kyla Coffee Shop
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php
// Script tambahan khusus halaman tertentu, dikirim lewat $pageScripts
// Contoh: halaman keranjang mengirim cart.js agar tidak dimuat di halaman lain
foreach (($pageScripts ?? []) as $script): ?>
    <script src="<?= e($script) ?>"></script>
<?php endforeach; ?>

</body>
</html>