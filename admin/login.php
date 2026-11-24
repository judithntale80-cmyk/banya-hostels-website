<?php
// ===== LOGIN FORM AND FAILED-ATTEMPT CHECK =====
require '../config.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    $ip = $_SERVER['REMOTE_ADDR'];

    $attemptsQuery = $pdo->prepare("
        SELECT COUNT(*)
        FROM login_attempts
        WHERE ip = ? AND at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
    ");
    $attemptsQuery->execute([$ip]);

    if ($attemptsQuery->fetchColumn() >= 5) {
        $error = 'Too many failed attempts. Try again in 15 minutes.';
    } else {
        $userQuery = $pdo->prepare('SELECT * FROM users WHERE username = ?');
        $userQuery->execute([trim($_POST['username'] ?? '')]);
        $user = $userQuery->fetch();

        $temporaryAccessExpired = $user
            && $user['expires_at'] !== null
            && strtotime($user['expires_at']) <= time();

        if ($user && !$temporaryAccessExpired && password_verify($_POST['password'] ?? '', $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin'] = $user['username'];
            $pdo->prepare('DELETE FROM login_attempts WHERE ip = ?')->execute([$ip]);
            redirect('/admin/index.php');
        }

        $pdo->prepare('INSERT INTO login_attempts(ip) VALUES(?)')->execute([$ip]);
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">
        <title>Admin login</title>
        <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/banya_images/favicon.png">
        <link href="https://fonts.googleapis.com/css2?family=Bitter:wght@600;700&amp;family=Public+Sans:wght@400;500;600&amp;display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
        <link href="<?= BASE_URL ?>/assets/css/admin.css" rel="stylesheet">
    </head>
    <body class="admin-body">
        <!-- ===== ADMIN LOGIN FORM ===== -->
        <div class="container admin-login">
            <div class="card p-4">
                <h1 class="h4 mb-3">Admin login</h1>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="post">
                    <?= csrf() ?>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input class="form-control" name="username" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <button class="btn btn-dark w-100">Log in</button>
                </form>
            </div>
        </div>
    </body>
</html>
