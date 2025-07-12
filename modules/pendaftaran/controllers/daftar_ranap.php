<?php
session_start();
require_once 'config.php';
$page_title = "Daftar Pasien Rawat Inap";

// Ambil parameter filter
$filter_bangsal = isset($_GET['bangsal']) ? $_GET['bangsal'] : '0';
$filter_dpjp = isset($_GET['dpjp']) ? $_GET['dpjp'] : '0'; // Ubah default menjadi '0'
$search = isset($_GET['search']) ? $_GET['search'] : '';

try {
    // Query untuk menghitung total pasien rawat inap
    $queryTotal = "SELECT COUNT(*) as total FROM kamar_inap WHERE stts_pulang = '-'";
    $stmtTotal = $conn_db1->prepare($queryTotal);
    $stmtTotal->execute();
    $totalPasien = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];

    $query = "SELECT DISTINCT 
            ran.no_rawat,
            pasien.nm_pasien as nama,
            TIMESTAMPDIFF(YEAR, pasien.tgl_lahir, CURDATE()) as usia,
            bngsal.nm_bangsal,
            DATE_FORMAT(ran.tgl_masuk, '%d/%m/%Y') as tgl_masuk,
            dokter.nm_dokter,
            ran.stts_pulang as status
            FROM kamar_inap as ran
            LEFT JOIN reg_periksa as reg ON ran.no_rawat = reg.no_rawat
            LEFT JOIN pasien ON reg.no_rkm_medis = pasien.no_rkm_medis
            LEFT JOIN kamar ON ran.kd_kamar = kamar.kd_kamar
            LEFT JOIN bangsal as bngsal ON kamar.kd_bangsal = bngsal.kd_bangsal
            LEFT JOIN dpjp_ranap as dpjp ON ran.no_rawat = dpjp.no_rawat
            LEFT JOIN dokter ON dpjp.kd_dokter = dokter.kd_dokter
            WHERE ran.stts_pulang = '-'";

    // Filter untuk bangsal Ken Dedes
    if ($filter_bangsal == '1') {
        $query .= " AND bngsal.nm_bangsal LIKE 'Ken Dedes%'";
    }

    // Filter untuk DPJP
    if ($filter_dpjp == '1') {
        $query .= " AND dokter.nm_dokter = 'dr. ARIFIAN JUARI, Sp.OG(K)'";
    }

    // Filter pencarian
    if ($search) {
        $query .= " AND (pasien.nm_pasien LIKE :search OR ran.no_rawat LIKE :search OR bngsal.nm_bangsal LIKE :search)";
    }

    // Urutkan berdasarkan bangsal dan tanggal masuk
    $query .= " ORDER BY bngsal.nm_bangsal ASC, ran.tgl_masuk DESC";

    $stmt = $conn_db1->prepare($query);

    if ($search) {
        $searchParam = "%$search%";
        $stmt->bindValue(':search', $searchParam, PDO::PARAM_STR);
    }

    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Kelompokkan pasien berdasarkan bangsal
    $groupedPatients = [];
    foreach ($patients as $patient) {
        // Gunakan nama bangsal lengkap sebagai key
        $bangsal = $patient['nm_bangsal'] ?? 'Lainnya';
        // Hapus nomor di akhir nama bangsal (misal: "Ken Dedes 1" menjadi "Ken Dedes")
        $bangsal = preg_replace('/\s+\d+$/', '', $bangsal);

        if (!isset($groupedPatients[$bangsal])) {
            $groupedPatients[$bangsal] = [];
        }
        $groupedPatients[$bangsal][] = $patient;
    }

    // Urutkan array bangsal
    ksort($groupedPatients);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo "Error: " . $e->getMessage();
    die();
}

// Start output buffering
ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-3">
            <h4>Daftar Pasien Rawat Inap</h4>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <form class="d-flex flex-wrap gap-3 mb-3" method="GET">
                <?php
                // Pertahankan nilai filter lain saat submit
                if ($search) echo '<input type="hidden" name="search" value="' . htmlspecialchars($search ?? '') . '">';
                ?>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="bangsal" value="1" id="bangsalFilter"
                        <?php echo $filter_bangsal == '1' ? 'checked' : ''; ?> onChange="this.form.submit()">
                    <label class="form-check-label" for="bangsalFilter">
                        Bangsal Ken Dedes
                    </label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="dpjp" value="1" id="dpjpFilter"
                        <?php echo $filter_dpjp == '1' ? 'checked' : ''; ?> onChange="this.form.submit()">
                    <label class="form-check-label" for="dpjpFilter">
                        DPJP dr. ARIFIAN JUARI, Sp.OG(K)
                    </label>
                </div>
                <div class="d-flex gap-2">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search ?? ''); ?>"
                        placeholder="Cari nama/no.rawat..." class="form-control" style="max-width: 200px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Cari
                    </button>
                    <?php if ($filter_bangsal == '1' || $search || $filter_dpjp == '1'): ?>
                        <a href="daftar_ranap.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Reset Filter
                        </a>
                    <?php endif; ?>
                </div>
                <button type="button" onclick="location.reload()" class="btn btn-secondary ms-auto">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </form>
        </div>
    </div>

    <?php foreach ($groupedPatients as $bangsal => $bangsalPatients): ?>
        <div class="table-responsive mb-4">
            <h5 class="bangsal-header bg-light p-2 rounded">
                <?php echo htmlspecialchars($bangsal ?? ''); ?>
                <span class="badge bg-primary"><?php echo count($bangsalPatients); ?> Pasien</span>
            </h5>
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>No</th>
                        <th>No. Rawat</th>
                        <th>Nama Pasien</th>
                        <th>Usia</th>
                        <th>Tgl Masuk</th>
                        <th>Dokter</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bangsalPatients as $index => $patient): ?>
                        <tr class="expandable-row" data-no-rawat="<?php echo htmlspecialchars($patient['no_rawat'] ?? ''); ?>">
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($patient['no_rawat'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($patient['nama'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($patient['usia'] ?? ''); ?> th</td>
                            <td><?php echo htmlspecialchars($patient['tgl_masuk'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($patient['nm_dokter'] ?? ''); ?></td>
                            <td>
                                <span class="badge bg-<?php
                                                        echo match ($patient['status']) {
                                                            '-' => 'info',
                                                            'Pindah Kamar' => 'warning',
                                                            'Pulang' => 'success',
                                                            'Meninggal' => 'danger',
                                                            default => 'secondary'
                                                        };
                                                        ?>">
                                    <?php echo $patient['status'] === '-' ? 'Dirawat' : htmlspecialchars($patient['status'] ?? ''); ?>
                                </span>
                            </td>
                        </tr>
                        <tr class="detail-row" style="display: none;">
                            <td colspan="7">
                                <div class="detail-content p-3">
                                    <div class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
</div>

<!-- File untuk mengambil detail pemeriksaan -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const rows = document.querySelectorAll('.expandable-row');

        rows.forEach(row => {
            row.addEventListener('click', function() {
                const noRawat = this.dataset.noRawat;
                const detailRow = this.nextElementSibling;
                const detailContent = detailRow.querySelector('.detail-content');

                // Toggle tampilan baris detail
                if (detailRow.style.display === 'none') {
                    detailRow.style.display = 'table-row';

                    // Ambil data pemeriksaan
                    fetch('get_pemeriksaan_ranap.php?no_rawat=' + noRawat)
                        .then(response => response.text())
                        .then(data => {
                            detailContent.innerHTML = data;
                        })
                        .catch(error => {
                            detailContent.innerHTML = '<div class="alert alert-danger">Error: Gagal memuat data pemeriksaan</div>';
                            console.error('Error:', error);
                        });
                } else {
                    detailRow.style.display = 'none';
                }
            });
        });
    });
</script>

<?php
$content = ob_get_clean();

// Additional CSS
$additional_css = "
    .table tbody td {
        vertical-align: middle;
        font-size: 0.9rem;
    }
    .badge {
        font-weight: 500;
    }
    .form-switch .form-check-input {
        width: 3em;
        cursor: pointer;
    }
    .form-check-label {
        cursor: pointer;
    }
    .expandable-row {
        cursor: pointer;
    }
    .expandable-row:hover {
        background-color: rgba(0,0,0,.075);
    }
    .detail-row {
        background-color: #f8f9fa;
    }
    .detail-content {
        padding: 1rem;
    }
    .bangsal-header {
        border-left: 4px solid #0d6efd;
        margin-bottom: 1rem;
    }
    .table-responsive {
        background: white;
        padding: 1rem;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0,0,0,.05);
    }
";

// Additional JavaScript if needed
$additional_js = "";

// Pastikan menggunakan path yang benar untuk template
$template_path = __DIR__ . '/template/layout.php';
if (file_exists($template_path)) {
    include $template_path;
} else {
    // Jika path pertama tidak ditemukan, coba path relatif
    include 'template/layout.php';
}
?>