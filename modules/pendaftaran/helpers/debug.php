<?php
// File: debug.php
// Deskripsi: File untuk mendiagnosis masalah di server

// Pastikan output sebagai JSON
header('Content-Type: application/json; charset=utf-8');

// Informasi server
$server_info = [
    'php_version' => phpversion(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'request_time' => date('Y-m-d H:i:s'),
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
    'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
    'http_user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
    'http_accept' => $_SERVER['HTTP_ACCEPT'] ?? 'Unknown',
    'http_host' => $_SERVER['HTTP_HOST'] ?? 'Unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
    'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'Unknown',
];

// Cek file konfigurasi
$config_files = [
    '../config/database.php' => file_exists('../config/database.php'),
    '../config/timezone.php' => file_exists('../config/timezone.php'),
    '../config/config.php' => file_exists('../config/config.php'),
    'get_jadwal.php' => file_exists('get_jadwal.php'),
];

// Cek koneksi database
$db_connection = false;
$db_error = '';

try {
    // Impor konfigurasi database
    require_once '../config/database.php';

    // Cek koneksi
    if (isset($conn) && $conn instanceof PDO) {
        // Test koneksi dengan query sederhana
        $stmt = $conn->query("SELECT 1 as test");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $db_connection = ($result['test'] == 1);
    }
} catch (Exception $e) {
    $db_error = $e->getMessage();
}

// Cek tabel jadwal_rutin
$jadwal_table = [];
$jadwal_error = '';

if ($db_connection) {
    try {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM jadwal_rutin");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $jadwal_table['count'] = $result['count'];

        // Ambil beberapa baris untuk contoh
        $stmt = $conn->query("SELECT * FROM jadwal_rutin LIMIT 3");
        $jadwal_table['sample'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $jadwal_error = $e->getMessage();
    }
}

// Hasil
$result = [
    'server_info' => $server_info,
    'config_files' => $config_files,
    'database' => [
        'connection' => $db_connection,
        'error' => $db_error
    ],
    'jadwal_table' => [
        'data' => $jadwal_table,
        'error' => $jadwal_error
    ]
];

// Output hasil
echo json_encode($result, JSON_PRETTY_PRINT);
