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
    // Cek apakah tabel menu_layanan sudah ada
    $stmt = $conn->query("SHOW TABLES LIKE 'menu_layanan'");
    $tableExists = $stmt->rowCount() > 0;

    if (!$tableExists) {
        // Buat tabel menu_layanan jika belum ada
        $sql = "CREATE TABLE `menu_layanan` (
            `id_layanan` char(36) NOT NULL,
            `nama_layanan` varchar(255) NOT NULL,
            `kategori` enum('Konsultasi','Tindakan','Pemeriksaan','Paket','Lainnya') NOT NULL,
            `harga` int(11) NOT NULL,
            `deskripsi` text DEFAULT NULL,
            `persiapan` text DEFAULT NULL,
            `durasi_estimasi` int(11) DEFAULT NULL,
            `status_aktif` tinyint(1) NOT NULL DEFAULT 1,
            `dapat_dibooking` tinyint(1) NOT NULL DEFAULT 0,
            `created_at` datetime DEFAULT current_timestamp(),
            `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id_layanan`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $conn->exec($sql);
        echo "Tabel menu_layanan berhasil dibuat!";
    } else {
        echo "Tabel menu_layanan sudah ada.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
