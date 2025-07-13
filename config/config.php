<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL Configuration
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domain = $_SERVER['HTTP_HOST'];
$script_path = dirname($_SERVER['SCRIPT_NAME']);

// Deteksi root path berdasarkan struktur folder
$root_path = $script_path;
$possible_paths = ['/config', '/modules/pendaftaran/controllers', '/modules/layanan/views'];
foreach ($possible_paths as $path) {
    $root_path = str_replace($path, '', $root_path);
}
// Generic removal of any modules path to get application root
$root_path = preg_replace('#/modules/.*$#', '', $root_path);

// Set base URL
$base_url = rtrim($protocol . $domain . $root_path, '/');

// Database RS (readonly) - db1
$db1_host = '103.76.149.29';
$db1_username = 'web_hasta';
$db1_password = '@Admin123/';
$db1_database = 'simsvbaru';

// Alias untuk kompatibilitas dengan kode lama
$host = $db1_host;
$username = $db1_username;
$password = $db1_password;
$database = $db1_database;

// Only create this connection if it doesn't already exist (to avoid conflicts with config/database.php)
if (!isset($conn) || !($conn instanceof PDO)) {
    $pdo_options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5, // Fail after 5 seconds if cannot connect
    ];
    error_log("[config.php] Attempting DB1 connection...");
    $start_time = microtime(true);
    try {
        $conn_db1 = new PDO(
            "mysql:host=$db1_host;dbname=$db1_database",
            $db1_username,
            $db1_password,
            $pdo_options
        );
        $elapsed = round((microtime(true) - $start_time) * 1000);
        error_log("[config.php] DB1 connection success in {$elapsed} ms");
    } catch (PDOException $e) {
        $elapsed = round((microtime(true) - $start_time) * 1000);
        error_log("[config.php] Connection to DB1 (RS) failed after {$elapsed} ms: " . $e->getMessage());
        // Don't die here, let the application continue if possible
    }
}
