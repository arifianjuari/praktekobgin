<?php
// Kredensial database
require_once __DIR__ . '/../../../config/database.php';
$db_host = $db2_host;
$db_username = $db2_username;
$db_password = $db2_password;
$db_database = $db2_database;

// Buat koneksi
// Gunakan koneksi global PDO
require_once __DIR__ . '/../../../config/database.php';
global $conn; // $conn sudah merupakan instance PDO dari config/database.php

// Cek koneksi database
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Baca file SQL
$sql_file = file_get_contents(__DIR__ . '/create_table_pengumuman.sql');

// Jalankan query SQL
if ($conn->multi_query($sql_file)) {
    echo "Tabel pengumuman berhasil dibuat!";

    // Bersihkan hasil query
    while ($conn->more_results() && $conn->next_result()) {
        // Kosongkan buffer hasil
        if ($result = $conn->store_result()) {
            $result->free();
        }
    }
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
