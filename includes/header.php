<?php
/**
 * Application Header — Sidebar Layout
 * Determines current section for active nav highlighting
 */
$_currentScript = basename($_SERVER['SCRIPT_NAME']);
$_currentDir = basename(dirname($_SERVER['SCRIPT_NAME']));
$_userName = sanitize($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User');
$_userInitial = strtoupper(substr($_userName, 0, 1));
$_userRole = ucfirst($_SESSION['role'] ?? 'staff');
$_pageTitle = $pageTitle ?? APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= $_pageTitle ?> — <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="app-layout">

    <!-- Sidebar Overlay (mobile) -->
    <div class="sidebar-overlay"></div>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="<?= ASSETS_URL ?>/images/logo.jpg" alt="Logo" class="sidebar-logo">
            <div class="sidebar-brand">
                <h1>Nyumbalink</h1>
                <small>Rental Management</small>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">Main</div>
            <ul>
                <li class="nav-item">
                    <a href="<?= url('modules/dashboard/index.php') ?>" class="nav-link <?= ($_currentDir === 'dashboard' && $_currentScript === 'index.php') ? 'active' : '' ?>">
                        <i class="bi bi-grid-1x2-fill"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
            </ul>

            <div class="nav-section">Management</div>
            <ul>
                <li class="nav-item">
                    <a href="<?= url('modules/houses/index.php') ?>" class="nav-link <?= $_currentDir === 'houses' ? 'active' : '' ?>">
                        <i class="bi bi-house-door-fill"></i>
                        <span>Houses</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= url('modules/tenants/index.php') ?>" class="nav-link <?= $_currentDir === 'tenants' && $_currentScript !== 'agreements.php' ? 'active' : '' ?>">
                        <i class="bi bi-people-fill"></i>
                        <span>Tenants</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= url('modules/tenants/agreements.php') ?>" class="nav-link <?= $_currentScript === 'agreements.php' ? 'active' : '' ?>">
                        <i class="bi bi-file-earmark-text-fill"></i>
                        <span>Agreements</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= url('modules/payments/index.php') ?>" class="nav-link <?= $_currentDir === 'payments' ? 'active' : '' ?>">
                        <i class="bi bi-wallet2"></i>
                        <span>Payments</span>
                    </a>
                </li>
            </ul>

            <div class="nav-section">Analytics</div>
            <ul>
                <li class="nav-item">
                    <a href="<?= url('modules/reports/index.php') ?>" class="nav-link <?= $_currentDir === 'reports' ? 'active' : '' ?>">
                        <i class="bi bi-bar-chart-line-fill"></i>
                        <span>Reports</span>
                    </a>
                </li>
            </ul>

            <?php if (isAdmin()): ?>
            <div class="nav-section">Admin</div>
            <ul>
                <li class="nav-item">
                    <a href="<?= url('modules/auth/register.php') ?>" class="nav-link <?= $_currentScript === 'register.php' ? 'active' : '' ?>">
                        <i class="bi bi-person-plus-fill"></i>
                        <span>Add Staff</span>
                    </a>
                </li>
            </ul>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-user-avatar"><?= $_userInitial ?></div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name"><?= $_userName ?></div>
                    <div class="sidebar-user-role"><?= $_userRole ?></div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="topbar-toggle" aria-label="Toggle menu">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="topbar-title"><?= $_pageTitle ?></h1>
            </div>
            <div class="topbar-right dropdown">
                <div class="topbar-user" role="button" data-bs-toggle="dropdown" aria-expanded="false" id="userDropdownBtn">
                    <div class="topbar-user-avatar"><?= $_userInitial ?></div>
                    <span class="topbar-user-name"><?= $_userName ?></span>
                    <i class="bi bi-chevron-down" style="font-size:0.7rem;color:var(--text-muted)"></i>
                </div>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdownBtn">
                    <li><span class="dropdown-item" style="cursor:default;opacity:0.6"><i class="bi bi-shield-lock"></i> <?= $_userRole ?></span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= url('modules/auth/logout.php') ?>"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </header>

        <!-- Page Content -->
        <div class="page-content fade-in">
            <?php
            $flash = getFlash();
            if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?> alert-dismissible" role="alert">
                    <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle-fill' : ($flash['type'] === 'danger' ? 'exclamation-triangle-fill' : 'info-circle-fill') ?>"></i>
                    <?= $flash['message'] ?>
                    <button type="button" class="btn-close" aria-label="Close">&times;</button>
                </div>
            <?php endif; ?>
