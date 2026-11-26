<?php
/**
 * =====================================================
 * File    : login.php
 * Fungsi  : Halaman login multi-role
 * Project : POS Coffee Shop (Project 2)
 * Author  : Muhammad Zaky Rahman
 * Depends : auth_check.php, functions.php
 * =====================================================
 */

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

// Jika user sudah login, langsung arahkan ke halaman sesuai role
if (isLoggedIn()) {
    redirectByRole();
}

$pageTitle = 'Login';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | RoastTrack</title>

    <!-- Bootstrap 5.3 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: #f3f4f6;
        }
        .login-card {
            max-width: 420px;
            margin: auto;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="login-card">
        <div class="card shadow">
            <div class="card-body p-4">
                <h3 class="text-center mb-1">☕ RoastTrack</h3>
                <p class="text-center text-muted mb-4">Kyla Coffee Shop</p>

                <?php if (isset($_GET['error']) && $_GET['error'] == 1): ?>
                    <div class="alert alert-danger">
                        Username atau password salah.
                    </div>
                <?php endif; ?>

                <form action="<?= e(BASE_URL) ?>/process/login_process.php" method="post">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text"
                               name="username"
                               id="username"
                               class="form-control"
                               required
                               autofocus>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <input type="password"
                               name="password"
                               id="password"
                               class="form-control"
                               required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        Login
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>