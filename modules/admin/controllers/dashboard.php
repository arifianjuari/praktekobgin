<?php
require_once __DIR__ . '/controllers/DashboardController.php';
$controller = new DashboardController();
$controller->handle();

// Query untuk menghitung jumlah pasien rawat inap
$query_ranap = "SELECT COUNT(DISTINCT ran.no_rawat) as total
                FROM kamar_inap as ran
                INNER JOIN dpjp_ranap as dpjp ON ran.no_rawat = dpjp.no_rawat
                INNER JOIN dokter ON dpjp.kd_dokter = dokter.kd_dokter
                WHERE ran.stts_pulang = '-'
                AND dokter.nm_dokter = 'dr. ARIFIAN JUARI, Sp.OG(K)'";

$stmt_ranap = $conn_db1->query($query_ranap);
$ranap_count = $stmt_ranap->fetch(PDO::FETCH_ASSOC)['total'];

// Query untuk menghitung jumlah pasien rawat jalan
$query_rajal = "SELECT COUNT(DISTINCT reg.no_rawat) as total
                FROM reg_periksa as reg
                WHERE reg.kd_dokter = (SELECT kd_dokter FROM dokter WHERE nm_dokter = 'dr. ARIFIAN JUARI, Sp.OG(K)')
                AND reg.tgl_registrasi = CURDATE()
                AND reg.stts != 'Batal'";

$stmt_rajal = $conn_db1->query($query_rajal);
$rajal_count = $stmt_rajal->fetch(PDO::FETCH_ASSOC)['total'];

// Start output buffering
ob_start();
?>

<div class="container-fluid py-4">
    <div class="row g-4">
        <!-- Card Rawat Inap -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Pasien Rawat Inap</h5>
                    <h6 class="text-muted mb-4">DPJP dr. ARIFIAN JUARI, Sp.OG(K)</h6>
                    <div class="d-flex align-items-center">
                        <h1 class="display-4 mb-0 text-primary"><?php echo $ranap_count; ?></h1>
                        <span class="ms-3">Pasien hari ini</span>
                    </div>
                    <a href="daftar_ranap.php" class="btn btn-outline-primary mt-4">
                        <i class="bi bi-eye"></i> Lihat Detail
                    </a>
                </div>
            </div>
        </div>

        <!-- Card Rawat Jalan -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Pasien Rawat Jalan</h5>
                    <h6 class="text-muted mb-4">Poli Obgin - dr. ARIFIAN JUARI, Sp.OG(K)</h6>
                    <div class="d-flex align-items-center">
                        <h1 class="display-4 mb-0 text-primary"><?php echo $rajal_count; ?></h1>
                        <span class="ms-3">Pasien hari ini</span>
                    </div>
                    <a href="daftar_rajal_rs.php" class="btn btn-outline-primary mt-4">
                        <i class="bi bi-eye"></i> Lihat Detail
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Konten dashboard utama -->
        <!-- Widget dummy telah dihapus -->
    </div>
    <div class="col-md-4">
        <!-- Widget Pengumuman -->
        <?php
        // Base URL untuk widget
        if (!isset($base_url)) {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'];
            $base_url = $protocol . $host;
        }

        // Include widget pengumuman
        include_once 'widgets/pengumuman_widget.php';
        ?>

        <!-- Widget Jadwal Hari Ini dummy telah dihapus -->
    </div>
</div>

<?php
$content = ob_get_clean();

// Additional CSS
$additional_css = "
    .card {
        border: none;
        border-radius: 10px;
    }
    .card-title {
        color: #2c3e50;
    }
    .display-4 {
        font-weight: 600;
    }
";

include 'template/layout.php';
?>