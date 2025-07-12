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
    // Cek apakah tabel formularium sudah ada
    $stmt = $conn->query("SHOW TABLES LIKE 'formularium'");
    $tableExists = $stmt->rowCount() > 0;

    if (!$tableExists) {
        // Buat tabel formularium jika belum ada
        $sql = "CREATE TABLE `formularium` (
            `id_obat` char(36) NOT NULL,
            `nama_obat` varchar(255) NOT NULL,
            `nama_generik` varchar(255) DEFAULT NULL,
            `bentuk_sediaan` varchar(50) DEFAULT NULL,
            `dosis` varchar(100) DEFAULT NULL,
            `kategori` varchar(100) DEFAULT NULL,
            `catatan_obat` text DEFAULT NULL,
            `harga` int(11) NOT NULL,
            `status_aktif` tinyint(1) NOT NULL DEFAULT 1,
            `created_at` datetime DEFAULT current_timestamp(),
            `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id_obat`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $conn->exec($sql);
        echo "<div class='alert alert-success'>Tabel formularium berhasil dibuat!</div>";
    } else {
        echo "<div class='alert alert-info'>Tabel formularium sudah ada.</div>";
    }
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Tabel Formularium</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h4>Pembuatan Tabel Formularium</h4>
                    </div>
                    <div class="card-body">
                        <p>Tabel formularium digunakan untuk menyimpan data obat-obatan dan formularium klinik.</p>
                        <p>Struktur tabel:</p>
                        <ul>
                            <li><strong>id_obat</strong> - Primary key (UUID)</li>
                            <li><strong>nama_obat</strong> - Nama obat</li>
                            <li><strong>nama_generik</strong> - Nama generik obat</li>
                            <li><strong>bentuk_sediaan</strong> - Bentuk sediaan obat (tablet, kapsul, sirup, dll)</li>
                            <li><strong>dosis</strong> - Dosis obat</li>
                            <li><strong>kategori</strong> - Kategori obat</li>
                            <li><strong>catatan_obat</strong> - Catatan atau deskripsi obat</li>
                            <li><strong>harga</strong> - Harga obat</li>
                            <li><strong>status_aktif</strong> - Status aktif (1) atau nonaktif (0)</li>
                            <li><strong>created_at</strong> - Waktu pembuatan record</li>
                            <li><strong>updated_at</strong> - Waktu update terakhir</li>
                        </ul>
                        <div class="mt-4">
                            <a href="<?= $base_url ?>/modules/admin/formularium.php" class="btn btn-primary">Kembali ke Formularium</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>