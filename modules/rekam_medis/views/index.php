<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Dapatkan root directory project
$root_dir = dirname(dirname(dirname(__DIR__)));

// Deklarasikan variabel global
global $conn;

// Include file konfigurasi
require_once $root_dir . '/config/database.php';
require_once $root_dir . '/config/config.php';

// Log status koneksi
error_log("Checking database connection in rekam_medis/views/index.php");

// Cek role admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . $base_url . '/login.php');
    exit;
}

// Cek koneksi database
if (!isset($conn) || !($conn instanceof PDO)) {
    error_log("Database connection not available in rekam_medis/views/index.php");
    die("Koneksi database tidak tersedia");
}

try {
    // Test koneksi
    $test = $conn->query("SELECT 1");
    if (!$test) {
        throw new PDOException("Koneksi database tidak dapat melakukan query");
    }
    error_log("Database connection test successful in rekam_medis/views/index.php");

    // Query untuk mengambil data pasien
    $query = "SELECT * FROM pasien ORDER BY no_rkm_medis DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $pasien = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Error in rekam_medis/views/index.php: " . $e->getMessage());
    echo "<div class='alert alert-danger'>Terjadi kesalahan saat mengambil data. Silakan coba lagi nanti.</div>";
    $pasien = [];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Rekam Medis</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>

<body>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Data Rekam Medis</h4>
                    </div>
                    <div class="card-body">
                        <!-- Tombol Tambah dan Pencarian -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <a href="<?php echo $base_url; ?>/index.php?module=rekam_medis&action=tambah_pasien" class="btn btn-success">
                                    <i class="bi bi-plus-circle"></i> Tambah Pasien
                                </a>
                            </div>
                            <div class="col-md-6">
                                <form action="<?php echo $base_url; ?>/index.php?module=rekam_medis&action=cari_pasien" method="POST" class="d-flex">
                                    <input type="text" name="keyword" class="form-control me-2" placeholder="Cari pasien...">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Tabel Data Pasien -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>No. RM</th>
                                        <th>Nama Pasien</th>
                                        <th>No. KTP</th>
                                        <th>Jenis Kelamin</th>
                                        <th>Tanggal Lahir</th>
                                        <th>Alamat</th>
                                        <th>No. Telepon</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($pasien)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center">Tidak ada data pasien</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($pasien as $p): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($p['no_rkm_medis']); ?></td>
                                                <td><?php echo htmlspecialchars($p['nm_pasien']); ?></td>
                                                <td><?php echo htmlspecialchars($p['no_ktp']); ?></td>
                                                <td><?php echo htmlspecialchars($p['jk']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($p['tgl_lahir'])); ?></td>
                                                <td><?php echo htmlspecialchars($p['alamat']); ?></td>
                                                <td><?php echo htmlspecialchars($p['no_tlp']); ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="<?php echo $base_url; ?>/index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=<?php echo $p['no_rkm_medis']; ?>"
                                                            class="btn btn-info btn-sm" title="Detail">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <a href="<?php echo $base_url; ?>/index.php?module=rekam_medis&action=edit_pasien&id=<?php echo $p['no_rkm_medis']; ?>"
                                                            class="btn btn-warning btn-sm" title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php
// Additional CSS
$additional_css = "
.card-tools {
    float: right;
}
.input-group {
    width: 250px;
}
.btn-group {
    display: flex;
    gap: 5px;
}
";
?>