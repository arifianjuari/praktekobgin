<?php
session_start();
require_once 'config.php';

// Cek apakah user sudah login dan adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Redirect ke dashboard admin yang sebenarnya
header("Location: " . $base_url . "/admin/dashboard_admin.php");
exit;
