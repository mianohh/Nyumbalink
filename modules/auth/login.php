<?php
require_once __DIR__ . '/../../includes/core.php';

if (isAuthenticated()) {
    redirect(url('modules/dashboard/index.php'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Login — <?= APP_NAME ?></title>
    <link rel="icon" href="<?= ASSETS_URL ?>/favicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>/css/style.css" rel="stylesheet">
</head>
<body>
<div class="login-layout">

    <!-- Background -->
    <div class="login-bg"></div>

    <!-- Login Card -->
    <div class="login-card">
        <div class="login-card-header">
            <img src="<?= ASSETS_URL ?>/images/logo.jpg" alt="Logo" class="login-logo">
            <h3><?= APP_NAME ?></h3>
            <p>Sign in to manage your properties</p>
        </div>

        <?php if (isset($_GET['timeout'])): ?>
            <div class="login-alert alert-warning">
                <i class="bi bi-clock-history"></i>
                Session expired. Please login again.
            </div>
        <?php endif; ?>

        <?php $flash = getFlash(); if ($flash): ?>
            <div class="login-alert alert-<?= $flash['type'] ?>">
                <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle-fill' : ($flash['type'] === 'danger' ? 'exclamation-triangle-fill' : 'info-circle-fill') ?>"></i>
                <?= $flash['message'] ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login_process.php" class="login-form">
            <?= csrfField() ?>

            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control" id="email" name="username"
                           placeholder="Enter your email" required autofocus
                           value="<?= getInput('username') ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text" id="togglePassword" style="cursor:pointer;"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="Enter your password" required>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="bi bi-box-arrow-in-right"></i>&nbsp; Sign In
            </button>
        </form>

        <div class="login-footer">
            &copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var toggle = document.getElementById('togglePassword');
    var passwordInput = document.getElementById('password');
    if (toggle && passwordInput) {
        toggle.addEventListener('click', function() {
            var icon = toggle.querySelector('i');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.className = 'bi bi-unlock';
            } else {
                passwordInput.type = 'password';
                icon.className = 'bi bi-lock';
            }
        });
    }
});
</script>
</body>
</html>
