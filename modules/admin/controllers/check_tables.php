<?php
// Aktifkan pelaporan error
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Sertakan file konfigurasi database
require_once '../config_auth.php';

try {
    // Periksa struktur tabel tempat_praktek
    $query = "SHOW CREATE TABLE tempat_praktek";
    $stmt = $conn_db2->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<h3>Struktur Tabel tempat_praktek:</h3>";
    echo "<pre>" . $result['Create Table'] . "</pre>";

    // Periksa struktur tabel dokter
    $query = "SHOW CREATE TABLE dokter";
    $stmt = $conn_db2->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<h3>Struktur Tabel dokter:</h3>";
    echo "<pre>" . $result['Create Table'] . "</pre>";

    // Periksa struktur tabel jadwal_rutin
    $query = "SHOW CREATE TABLE jadwal_rutin";
    $stmt = $conn_db2->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<h3>Struktur Tabel jadwal_rutin:</h3>";
    echo "<pre>" . $result['Create Table'] . "</pre>";

    // Periksa struktur tabel jadwal_praktek
    $query = "SHOW CREATE TABLE jadwal_praktek";
    $stmt = $conn_db2->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<h3>Struktur Tabel jadwal_praktek:</h3>";
    echo "<pre>" . $result['Create Table'] . "</pre>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
