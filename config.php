<?php
// ===== CONFIGURATION: edit for local (WAMP) vs live (cPanel) =====
// Update these connection and URL values when moving the site between environments.
define('DB_HOST', 'localhost');
define('DB_NAME', 'banya_hostels');
define('DB_USER', 'root');
define('DB_PASS', ''); // Live: cPanel database user/password.
define('BASE_URL', '/banya'); // Live (domain root): ''.
define('SITE_URL', 'http://localhost/banya'); // Live: 'https://yourdomain.com'.

define(
    'IS_HTTPS',
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (!empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => BASE_URL ?: '/',
        'domain' => '',
        'secure' => IS_HTTPS,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
// ===== DATABASE CONNECTION =====
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed. Check config.php and import schema.sql.');
}

// ===== SHARED OUTPUT, SETTINGS, AND SECURITY HELPERS =====
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function setting($key)
{
    global $pdo;
    static $cache = null;

    if ($cache === null) {
        $cache = $pdo->query('SELECT k, v FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    return $cache[$key] ?? '';
}

function brand_color()
{
    $color = setting('brand_color');

    return preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : '#1b4965';
}

function send_security_headers()
{
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net data:; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; connect-src 'self'; frame-src https://www.google.com https://maps.google.com; object-src 'none'; base-uri 'self'; frame-ancestors 'none';");
}

function redirect($url, $status = 303)
{
    $location = $url;

    if (!preg_match('/^(?:[a-z]+:)?\/\//i', $url)) {
        if (strpos($url, '/') === 0) {
            $location = (BASE_URL !== '' && BASE_URL !== '/' && strpos($url, BASE_URL) !== 0)
                ? BASE_URL . $url
                : $url;
        } else {
            $location = (BASE_URL !== '' && BASE_URL !== '/' ? BASE_URL . '/' : '/') . $url;
        }
    }

    header('Location: ' . $location, true, $status);
    exit;
}

// ===== FORM TOKENS, MESSAGES, AND ADMIN ACCESS =====
function csrf()
{
    if (empty($_SESSION['t'])) {
        $_SESSION['t'] = bin2hex(random_bytes(16));
    }

    return '<input type="hidden" name="t" value="' . $_SESSION['t'] . '">';
}

function csrf_ok()
{
    return isset($_POST['t'], $_SESSION['t'])
        && is_string($_POST['t'])
        && hash_equals($_SESSION['t'], $_POST['t']);
}

function flash($message = null, $type = 'success')
{
    if ($message !== null) {
        $_SESSION['f'] = [$message, $type];
        return;
    }

    if (isset($_SESSION['f'])) {
        [$message, $type] = $_SESSION['f'];
        unset($_SESSION['f']);

        return "<div class='alert alert-$type'>" . e($message) . '</div>';
    }

    return '';
}

function require_admin()
{
    if (empty($_SESSION['admin'])) {
        redirect('/admin/login.php');
    }

    global $pdo;
    $query = $pdo->prepare('SELECT expires_at FROM users WHERE username = ?');
    $query->execute([$_SESSION['admin']]);
    $admin = $query->fetch();

    if (!$admin || ($admin['expires_at'] !== null && strtotime($admin['expires_at']) <= time())) {
        unset($_SESSION['admin']);
        redirect('/admin/login.php');
    }
}

function slugify($value)
{
    return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($value)), '-');
}

function gallery_categories()
{
    return [
        'Single Rooms',
        'Double Rooms',
        'Bathrooms',
        'Kitchen',
        'Exterior',
        'Surroundings',
        'Common Areas',
    ];
}

function gallery_image_url($image)
{
    $image = (string) $image;
    $directory = strpos($image, 'assets/banya_images/') === 0
        ? '/assets/banya_images/'
        : '/uploads/';
    $filename = basename(str_replace('\\', '/', $image));

    return BASE_URL . $directory . rawurlencode($filename);
}

// ===== IMAGE UPLOAD HELPER =====
function upload_image($field)
{
    if (empty($_FILES[$field]['tmp_name']) || $_FILES[$field]['error'] !== 0) {
        return null;
    }

    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES[$field]['tmp_name']);
    $size = (int) ($_FILES[$field]['size'] ?? 0);

    if (!isset($allowedTypes[$mime]) || $size > 2 * 1024 * 1024) {
        return null;
    }

    $filename = bin2hex(random_bytes(8)) . '.' . $allowedTypes[$mime];

    if (!move_uploaded_file($_FILES[$field]['tmp_name'], __DIR__ . '/uploads/' . $filename)) {
        return null;
    }

    return $filename;
}
