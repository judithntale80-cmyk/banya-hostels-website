<?php
// ===== END THE ADMIN SESSION AND RETURN TO LOGIN =====
require '../config.php';
$_SESSION = [];

if (session_id() !== '') {
    session_destroy();
}

redirect('/admin/login.php');
