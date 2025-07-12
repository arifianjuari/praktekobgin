<?php
// Script untuk mengupdate status_aktif pengumuman menjadi 0 jika sudah melewati tanggal_berakhir
// File ini dapat dijalankan melalui cron job setiap hari pada tengah malam

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

// Tanggal hari ini
$current_date = date('Y-m-d');

// Update status_aktif menjadi 0 untuk pengumuman yang sudah melewati tanggal_berakhir
$query = "UPDATE pengumuman 
          SET status_aktif = 0 
          WHERE status_aktif = 1 
          AND tanggal_berakhir IS NOT NULL 
          AND tanggal_berakhir < ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $current_date);
$stmt->execute();

// Hitung jumlah baris yang diupdate
$affected_rows = $stmt->affected_rows;

// Log hasil
$log_message = date('Y-m-d H:i:s') . " - " . $affected_rows . " pengumuman dinonaktifkan karena sudah melewati tanggal_berakhir.\n";
file_put_contents(__DIR__ . '/../logs/pengumuman_update.log', $log_message, FILE_APPEND);

// Output hasil jika dijalankan dari browser
echo $affected_rows . " pengumuman dinonaktifkan karena sudah melewati tanggal_berakhir.";

// Tutup koneksi
$stmt->close();
$conn->close();
