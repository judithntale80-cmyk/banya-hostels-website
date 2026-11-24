<?php
// ===== SHARED PAGE METADATA AND NAVIGATION =====
require_once __DIR__ . '/../config.php';
send_security_headers();

$pageTitle = (isset($title) ? $title . ' | ' : '') . setting('site_name');
$metaDesc = $desc ?? setting('tagline');
$currentPage = basename($_SERVER['PHP_SELF']);
$hostels = $pdo->query('SELECT slug, name FROM hostels ORDER BY id')->fetchAll();

$navigation = [
    'index.php' => 'Home',
    'about.php' => 'About Us',
    'gallery.php' => 'Gallery',
    'enquires.php' => 'Enquiries',
    'news.php' => 'News / Blog',
    'contact.php' => 'Contact Us',
];

function render_page_hero($title, $eyebrow = 'Banya Hostels · Fort Portal')
{
    echo '<section class="page-hero"><div class="container">'
        . '<p class="eyebrow text-uppercase">' . e($eyebrow) . '</p>'
        . '<h1 class="mb-0">' . e($title) . '</h1>'
        . '</div></section>';
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <!-- ===== DOCUMENT HEAD: SEO, FONTS, AND STYLESHEETS ===== -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="Content-Security-Policy" content="default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net data:; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; frame-src https://www.google.com https://maps.google.com; object-src 'none'; base-uri 'self';">
        <title><?= e($pageTitle) ?></title>
        <meta name="description" content="<?= e($metaDesc) ?>">
        <?= $extraHead ?? '' ?>
        <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/banya_images/favicon.png">
        <link rel="canonical" href="<?= e(SITE_URL . $_SERVER['REQUEST_URI']) ?>">
        <link href="https://fonts.googleapis.com/css2?family=Bitter:wght@600;700&amp;family=Public+Sans:wght@400;500;600&amp;display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
        <link
            href="<?= BASE_URL ?>/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>"
            rel="stylesheet"
        >
        <style>
            :root {
                --brand: #183D32;
            }
        </style>
    </head>
    <body>
        <!-- ===== PUBLIC SITE NAVIGATION AND PAGE CONTENT START ===== -->
        <a class="visually-hidden-focusable" href="#main">Skip to content</a>
        <nav class="navbar navbar-expand-lg sticky-top site-nav">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>/index.php">
                    <img class="brand-logo" src="<?= BASE_URL ?>/assets/banya_images/logo.png" alt="">
                    <span class="brand-name"><?= e(setting('site_name')) ?></span>
                </a>

                <button
                    class="navbar-toggler"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#nav"
                    aria-controls="nav"
                    aria-expanded="false"
                    aria-label="Toggle navigation"
                >
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div id="nav" class="collapse navbar-collapse">
                    <ul class="navbar-nav ms-auto align-items-lg-center">
                        <?php foreach ($navigation as $file => $label): ?>
                            <li class="nav-item">
                                <a
                                    class="nav-link <?= $currentPage === $file ? 'active' : '' ?>"
                                    href="<?= BASE_URL . '/' . $file ?>"
                                >
                                    <?= $label ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                        <li class="nav-item ms-lg-2">
                            <a class="btn btn-nav-book" href="<?= BASE_URL ?>/booking.php">BOOK NOW</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <main id="main">
