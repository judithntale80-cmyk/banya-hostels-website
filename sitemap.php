<?php
// ===== XML SITEMAP RESPONSE =====
require 'config.php';
send_security_headers();
header('Content-Type: application/xml; charset=utf-8');

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

$pages = [
    'index.php',
    'about.php',
    'gallery.php',
    'booking.php',
    'news.php',
    'enquires.php',
    'contact.php',
    'track.php',
];

foreach ($pages as $page) {
    echo '<url><loc>' . e(SITE_URL . '/' . $page) . '</loc></url>';
}

foreach ($pdo->query('SELECT slug FROM hostels') as $hostel) {
    echo '<url><loc>' . e(SITE_URL . '/hostel/' . $hostel['slug']) . '</loc></url>';
}

echo '</urlset>';
