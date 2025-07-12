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
    // Cek apakah tabel edukasi sudah ada
    $stmt = $conn->query("SHOW TABLES LIKE 'edukasi'");
    $tableExists = $stmt->rowCount() > 0;

    if (!$tableExists) {
        // Buat tabel edukasi jika belum ada
        $sql = "CREATE TABLE `edukasi` (
            `id_edukasi` char(36) NOT NULL,
            `judul` varchar(255) NOT NULL,
            `kategori` enum('fetomaternal','ginekologi umum','onkogin','fertilitas','uroginekologi') NOT NULL,
            `isi_edukasi` text NOT NULL,
            `link_gambar` varchar(255) DEFAULT NULL,
            `link_video` varchar(255) DEFAULT NULL,
            `sumber` varchar(255) DEFAULT NULL,
            `tag` varchar(255) DEFAULT NULL,
            `status_aktif` tinyint(1) NOT NULL DEFAULT 1,
            `ditampilkan_beranda` tinyint(1) NOT NULL DEFAULT 0,
            `urutan_tampil` int(11) DEFAULT NULL,
            `dibuat_oleh` char(36) DEFAULT NULL,
            `created_at` datetime DEFAULT current_timestamp(),
            `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id_edukasi`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $conn->exec($sql);
        echo "Tabel edukasi berhasil dibuat!";
    } else {
        echo "Tabel edukasi sudah ada.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
