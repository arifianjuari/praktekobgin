<?php
// bootstrap.php: Inisialisasi environment, config, koneksi DB, dsb.

// 1. Load environment variables dari config.env
function loadEnv($envFile = __DIR__ . '/config.env') {
    if (!file_exists($envFile)) return;
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = array_map('trim', explode('=', $line, 2));
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}
loadEnv();

// 2. Definisikan BASE_URL dan DEBUG dari env
if (isset($_ENV['BASE_URL'])) define('BASE_URL', rtrim($_ENV['BASE_URL'], '/'));
if (isset($_ENV['DEBUG'])) define('DEBUG', $_ENV['DEBUG'] === 'true');

// 3. Koneksi database PDO reusable
function getPDOConnectionFromEnv() {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_NAME'] ?? '';
    $username = $_ENV['DB_USER'] ?? '';
    $password = $_ENV['DB_PASS'] ?? '';
    try {
        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        if (defined('DEBUG') && DEBUG) {
            die("Koneksi database gagal: " . $e->getMessage());
        } else {
            die("Koneksi database gagal. Silakan hubungi administrator.");
        }
    }
}

// 4. Start session jika belum
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// 5. Logging error ke file jika di development
if (defined('DEBUG') && DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
}
