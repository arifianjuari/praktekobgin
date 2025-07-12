<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/config/database.php';
$conn = new mysqli($db2_host, $db2_username, $db2_password, $db2_database, $db2_port);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
echo "Koneksi berhasil ke database: $db2_database di host: $db2_host";
