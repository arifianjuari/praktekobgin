<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/auth.php';

// Cek apakah user adalah admin
if (!isAdmin()) {
    header('Location: ' . $base_url . '/login.php');
    exit;
}

try {
    // Cek apakah tabel rujukan sudah ada
    $stmt = $conn->query("SHOW TABLES LIKE 'rujukan'");
    $tableExists = $stmt->rowCount() > 0;

    if (!$tableExists) {
        // Buat tabel rujukan jika belum ada
        $sql = "CREATE TABLE `rujukan` (
            `id_perujuk` char(36) NOT NULL,
            `nama_perujuk` varchar(255) NOT NULL,
            `jenis_perujuk` enum('Bidan','Puskesmas','Rumah Sakit','Klinik','Dokter Spesialis','Lainnya') NOT NULL,
            `no_telepon` varchar(20) DEFAULT NULL,
            `keterangan` text DEFAULT NULL,
            `persentase_fee` decimal(5,2) DEFAULT NULL,
            `created_at` datetime DEFAULT current_timestamp(),
            `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id_perujuk`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $conn->exec($sql);
        echo "Tabel rujukan berhasil dibuat!";
    } else {
        echo "Tabel rujukan sudah ada.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
