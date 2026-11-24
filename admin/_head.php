<?php
// ===== ADMIN ACCESS, NAVIGATION, AND SHARED PAGE HEADER =====
require_once __DIR__ . '/../config.php';
send_security_headers();

if (!defined('BASE_URL')) {
    define('BASE_URL', '/banya');
}
require_admin();

$currentPage = basename($_SERVER['PHP_SELF']);
$adminQuery = $pdo->prepare('SELECT expires_at FROM users WHERE username = ?');
$adminQuery->execute([$_SESSION['admin']]);
$adminAccount = $adminQuery->fetch();
$isTemporaryAdmin = $adminAccount && $adminAccount['expires_at'] !== null;

$menuItems = [
    'index.php' => 'Dashboard',
    'hostels.php' => 'Hostels & prices',
    'rooms.php' => 'Rooms',
    'bookings.php' => 'Bookings',
    'enquiries.php' => 'Enquiries',
    'gallery.php' => 'Gallery',
    'posts.php' => 'Blog posts',
    'settings.php' => 'Settings',
];
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <!-- ===== ADMIN DOCUMENT HEAD AND STYLESHEETS ===== -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">
        <meta http-equiv="Content-Security-Policy" content="default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net data:; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; object-src 'none'; base-uri 'self'; frame-ancestors 'none';">
        <meta http-equiv="X-Frame-Options" content="DENY">
        <meta http-equiv="X-Content-Type-Options" content="nosniff">
        <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
        <title>Admin | <?= e(setting('site_name')) ?></title>
        <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/banya_images/favicon.png">
        <link href="https://fonts.googleapis.com/css2?family=Bitter:wght@600;700&amp;family=Public+Sans:wght@400;500;600&amp;display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
        <link href="<?= BASE_URL ?>/assets/css/admin.css" rel="stylesheet">
    </head>
    <body class="admin-body">
        <!-- ===== ADMIN NAVIGATION AND PAGE CONTENT START ===== -->
        <nav class="navbar navbar-expand-lg navbar-dark site-nav admin-navbar">
            <div class="container">
                <span class="navbar-brand"><?= $isTemporaryAdmin ? 'Temporary Admin' : 'Admin' ?></span>
                <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#a">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div id="a" class="collapse navbar-collapse">
                    <ul class="navbar-nav me-auto">
                        <?php foreach ($menuItems as $file => $label): ?>
                            <li class="nav-item">
                                <a
                                    class="nav-link <?= $currentPage === $file ? 'active' : '' ?>"
                                    href="<?= $file ?>"
                                >
                                    <?= $label ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <a
                        class="btn btn-sm btn-gold admin-view-site me-2"
                        href="<?= BASE_URL ?>/index.php"
                        target="_blank"
                        rel="noopener"
                    >View site</a>
                    <a class="btn btn-sm btn-danger" href="logout.php">Logout</a>
                </div>
            </div>
        </nav>
        <div class="container py-4"><?= flash() ?>
