<?php
require 'config.php';

$slug = isset($_GET['slug']) && is_string($_GET['slug']) ? $_GET['slug'] : '';
header('Location: ' . BASE_URL . '/news.php?slug=' . rawurlencode($slug), true, 301);
exit;
