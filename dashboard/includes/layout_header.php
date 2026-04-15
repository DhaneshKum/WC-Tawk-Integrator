<?php
$currentUser = Auth::user();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Dashboard' ?> — <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="fas fa-chart-line"></i></div>
        <span class="brand-text"><?= APP_NAME ?></span>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Main</div>
        <a href="<?= APP_URL ?>/index.php" class="nav-item <?= $currentPage === 'index' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
        <a href="<?= APP_URL ?>/pages/orders.php" class="nav-item <?= $currentPage === 'orders' ? 'active' : '' ?>">
            <i class="fas fa-shopping-bag"></i>
            <span>Orders</span>
            <span class="nav-badge" id="pendingOrdersBadge"></span>
        </a>
        <a href="<?= APP_URL ?>/pages/chats.php" class="nav-item <?= $currentPage === 'chats' ? 'active' : '' ?>">
            <i class="fas fa-comments"></i>
            <span>Live Chats</span>
            <span class="nav-badge" id="openChatsBadge"></span>
        </a>
        <a href="<?= APP_URL ?>/pages/tickets.php" class="nav-item <?= $currentPage === 'tickets' ? 'active' : '' ?>">
            <i class="fas fa-ticket-alt"></i>
            <span>Tickets</span>
        </a>

        <?php if (in_array($currentUser['role'], ['admin', 'super_admin'])): ?>
        <div class="nav-section-title">Management</div>
        <a href="<?= APP_URL ?>/pages/users.php" class="nav-item <?= $currentPage === 'users' ? 'active' : '' ?>">
            <i class="fas fa-users"></i>
            <span>Users</span>
        </a>
        <a href="<?= APP_URL ?>/pages/settings.php" class="nav-item <?= $currentPage === 'settings' ? 'active' : '' ?>">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?= getInitials($currentUser['full_name']) ?></div>
            <div class="user-details">
                <div class="user-name"><?= sanitize($currentUser['full_name']) ?></div>
                <div class="user-role"><?= ucfirst($currentUser['role']) ?></div>
            </div>
            <a href="<?= APP_URL ?>/logout.php" class="logout-btn" title="Logout">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</aside>

<!-- Main Content -->
<div class="main-layout">
    <!-- Top Navbar -->
    <header class="topbar">
        <div class="topbar-left">
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <div class="topbar-breadcrumb">
                <span class="breadcrumb-title"><?= $pageTitle ?? 'Dashboard' ?></span>
            </div>
        </div>
        <div class="topbar-right">
            <div class="topbar-time" id="topbarTime"></div>
            <div class="dropdown">
                <button class="topbar-btn" data-bs-toggle="dropdown">
                    <i class="fas fa-bell"></i>
                    <span class="notification-dot" id="notifDot" style="display:none;"></span>
                </button>
                <div class="dropdown-menu dropdown-menu-end notification-dropdown">
                    <div class="dropdown-header">Notifications</div>
                    <div id="notificationList">
                        <div class="text-center py-3 text-muted small">No new notifications</div>
                    </div>
                </div>
            </div>
            <div class="topbar-user">
                <div class="topbar-avatar"><?= getInitials($currentUser['full_name']) ?></div>
                <span class="d-none d-md-inline"><?= sanitize($currentUser['full_name']) ?></span>
            </div>
        </div>
    </header>

    <!-- Flash Message -->
    <?php if ($flash): ?>
    <div class="alert-container">
        <div class="alert-custom alert-<?= $flash['type'] ?>" id="flashAlert">
            <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
            <?= sanitize($flash['message']) ?>
            <button onclick="this.parentElement.remove()" class="alert-close"><i class="fas fa-times"></i></button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Page Content -->
    <main class="page-content">
