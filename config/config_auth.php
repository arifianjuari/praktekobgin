<?php
// Database untuk aplikasi antrian pasien - db2
require_once __DIR__ . '/database.php';
// Semua variabel koneksi database diambil dari config/database.php (menggunakan env/local)

// Include konfigurasi base URL dari file config/config.php
require_once __DIR__ . '/config.php';

try {
    $conn_db2 = new PDO("mysql:host=$db2_host;dbname=$db2_database", $db2_username, $db2_password);
    $conn_db2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Tambahkan variabel $auth_conn yang merujuk ke koneksi yang sama
    $auth_conn = $conn_db2;
} catch (PDOException $e) {
    die("Connection to DB2 (Antrian) failed: " . $e->getMessage());
}
