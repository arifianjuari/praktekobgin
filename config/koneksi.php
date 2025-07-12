<?php
// Fungsi untuk mendapatkan koneksi PDO
function getPDOConnection() {
    global $db2_host, $db2_database, $db2_username, $db2_password, $db2_port;
    // Cek apakah koneksi sudah ada di global scope
    global $pdo;
    
    // Jika koneksi sudah ada dan valid, gunakan kembali
    if (isset($pdo) && $pdo instanceof PDO) {
        try {
            // Test koneksi dengan query sederhana
            $pdo->query("SELECT 1");
            return $pdo;
        } catch (PDOException $e) {
            // Koneksi ada tapi bermasalah, buat koneksi baru
            error_log("Koneksi database bermasalah, membuat koneksi baru: " . $e->getMessage());
        }
    }
    
    // Buat koneksi baru
    try {
        require_once __DIR__ . '/../config/database.php';
        $host = $db2_host;
        $dbname = $db2_database;
        $username = $db2_username;
        $password = $db2_password;
        
        $pdo = new PDO(
            "mysql:host=$host;port=$db2_port;dbname=$dbname;charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true // Gunakan koneksi persisten
            ]
        );
        
        return $pdo;
    } catch (PDOException $e) {
        die("Koneksi database gagal: " . $e->getMessage());
    }
}

// Fungsi untuk menutup koneksi database
function closePDOConnection() {
    global $pdo;
    if (isset($pdo)) {
        $pdo = null;
        error_log("Koneksi PDO ditutup secara eksplisit");
    }
}

// Register shutdown function untuk menutup koneksi di akhir eksekusi script
register_shutdown_function('closePDOConnection');

// Dapatkan koneksi database
try {
    $pdo = getPDOConnection();
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Pastikan session sudah dimulai dengan cara yang kompatibel dengan berbagai versi PHP
if (function_exists('session_status')) {
    // PHP 5.4.0 atau lebih baru
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
} else {
    // PHP versi lama
    if (!headers_sent()) {
        @session_start();
    }
}
