<?php
require_once 'config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/login.php');
    exit;
}

// Cek role jika diperlukan
if (isset($require_admin) && $require_admin && $_SESSION['role'] !== 'admin') {
    header('Location: ' . $base_url . '/login.php');
    exit;
}
