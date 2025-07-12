<?php
require_once __DIR__ . '/JadwalController.php';
$controller = new JadwalController();
$controller->handle();
// Legacy logic di bawah masih tersedia jika dibutuhkan

session_start();
require_once '../config/database.php';
$page_title = "Jadwal Praktek Dokter";

// Filter
$id_tempat_praktek = isset($_GET['tempat']) ? $_GET['tempat'] : '';
$id_dokter = isset($_GET['dokter']) ? $_GET['dokter'] : '';
$hari = isset($_GET['hari']) ? $_GET['hari'] : '';

// Convert English day name to Indonesian
$hari_names = [
    'Sunday' => 'Minggu',
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu'
];

// Jika hari dalam bahasa Inggris, konversi ke Indonesia
if (in_array($hari, array_keys($hari_names))) {
    $hari = $hari_names[$hari];
}

// Ambil data tempat praktek
try {
    $query = "SELECT * FROM tempat_praktek WHERE Status_Aktif = 1 ORDER BY Nama_Tempat ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $tempat_praktek = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $tempat_praktek = [];
}

// Ambil data dokter
try {
    $query = "SELECT * FROM dokter WHERE Status_Aktif = 1 ORDER BY Nama_Dokter ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $dokter = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $dokter = [];
}

// Ambil jadwal rutin
$jadwal_rutin = [];
try {
    $query = "
        SELECT 
            jr.*,
            tp.Nama_Tempat,
            tp.Alamat_Lengkap,
            tp.Kota,
            tp.Jenis_Fasilitas,
            d.Nama_Dokter,
            d.Spesialisasi,
            d.Nomor_SIP
        FROM 
            jadwal_rutin jr
        LEFT JOIN 
            tempat_praktek tp ON jr.ID_Tempat_Praktek = tp.ID_Tempat_Praktek
        LEFT JOIN 
            dokter d ON jr.ID_Dokter = d.ID_Dokter
        WHERE 
            jr.Status_Aktif = 1
    ";

    $params = [];

    if (!empty($id_tempat_praktek)) {
        $query .= " AND jr.ID_Tempat_Praktek = :id_tempat_praktek";
        $params[':id_tempat_praktek'] = $id_tempat_praktek;
    }

    if (!empty($id_dokter)) {
        $query .= " AND jr.ID_Dokter = :id_dokter";
        $params[':id_dokter'] = $id_dokter;
    }

    if (!empty($hari)) {
        $query .= " AND jr.Hari = :hari";
        $params[':hari'] = $hari;
    }

    $query .= " ORDER BY jr.Hari, jr.Jam_Mulai ASC";

    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $jadwal_rutin = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Set status kuota dan format waktu
    foreach ($jadwal_rutin as &$jr) {
        $jr['Status_Kuota'] = $jr['Status_Aktif'] ? 'Tersedia' : 'Tidak Tersedia';
        $jr['Jenis_Jadwal'] = 'Rutin';

        // Format jam praktek untuk tampilan yang lebih baik
        $jr['Jam_Mulai_Format'] = date('H:i', strtotime($jr['Jam_Mulai']));
        $jr['Jam_Selesai_Format'] = date('H:i', strtotime($jr['Jam_Selesai']));
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $jadwal_rutin = [];
}

// Kelompokkan jadwal berdasarkan tempat praktek
$jadwal_by_tempat = [];
foreach ($jadwal_rutin as $jadwal) {
    $id_tempat = $jadwal['ID_Tempat_Praktek'];
    if (!isset($jadwal_by_tempat[$id_tempat])) {
        $jadwal_by_tempat[$id_tempat] = [
            'info_tempat' => [
                'ID_Tempat_Praktek' => $id_tempat,
                'Nama_Tempat' => $jadwal['Nama_Tempat'],
                'Alamat_Lengkap' => $jadwal['Alamat_Lengkap'],
                'Kota' => $jadwal['Kota'],
                'Jenis_Fasilitas' => $jadwal['Jenis_Fasilitas']
            ],
            'jadwal' => []
        ];
    }
    $jadwal_by_tempat[$id_tempat]['jadwal'][] = $jadwal;
}

// Kelompokkan jadwal berdasarkan hari untuk tampilan kalender
$jadwal_by_hari = [
    'Senin' => [],
    'Selasa' => [],
    'Rabu' => [],
    'Kamis' => [],
    'Jumat' => [],
    'Sabtu' => [],
    'Minggu' => []
];

foreach ($jadwal_rutin as $jadwal) {
    $jadwal_by_hari[$jadwal['Hari']][] = $jadwal;
}

// Start output buffering
ob_start();
?>

<div class="container py-4">
    <!-- Header dan Filter -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow border-0 rounded-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center rounded-top-4">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Jadwal Praktek Dokter</h5>
                    <div class="d-flex align-items-center">
                        <?php if (!empty($hari)): ?>
                            <span class="badge bg-light text-primary fs-6 rounded-pill px-3 py-2">
                                <i class="far fa-calendar-check me-1"></i><?php echo $hari; ?>
                            </span>
                        <?php else: ?>
                            <span class="badge bg-light text-primary fs-6 rounded-pill px-3 py-2">
                                <i class="far fa-calendar-alt me-1"></i>Semua Hari
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" action="" class="row g-3" id="filterForm">
                        <div class="col-md-4">
                            <label for="tempat" class="form-label fw-bold"><i class="fas fa-hospital me-1"></i>Tempat Praktek</label>
                            <select class="form-select rounded-pill" id="tempat" name="tempat">
                                <option value="">Semua Tempat</option>
                                <?php foreach ($tempat_praktek as $tp): ?>
                                    <option value="<?php echo htmlspecialchars($tp['ID_Tempat_Praktek']); ?>" <?php echo $id_tempat_praktek == $tp['ID_Tempat_Praktek'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tp['Nama_Tempat']); ?>
                                        <?php if (!empty($tp['Jenis_Fasilitas'])): ?>
                                            (<?php echo htmlspecialchars($tp['Jenis_Fasilitas']); ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="dokter" class="form-label fw-bold"><i class="fas fa-user-md me-1"></i>Dokter</label>
                            <select class="form-select rounded-pill" id="dokter" name="dokter">
                                <option value="">Semua Dokter</option>
                                <?php foreach ($dokter as $d): ?>
                                    <option value="<?php echo htmlspecialchars($d['ID_Dokter']); ?>" <?php echo $id_dokter == $d['ID_Dokter'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($d['Nama_Dokter']); ?>
                                        <?php if (!empty($d['Spesialisasi'])): ?>
                                            (<?php echo htmlspecialchars($d['Spesialisasi']); ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="hari" class="form-label fw-bold"><i class="fas fa-calendar-day me-1"></i>Hari</label>
                            <select class="form-select rounded-pill" id="hari" name="hari">
                                <option value="">Semua Hari</option>
                                <option value="Senin" <?php echo $hari == 'Senin' ? 'selected' : ''; ?>>Senin</option>
                                <option value="Selasa" <?php echo $hari == 'Selasa' ? 'selected' : ''; ?>>Selasa</option>
                                <option value="Rabu" <?php echo $hari == 'Rabu' ? 'selected' : ''; ?>>Rabu</option>
                                <option value="Kamis" <?php echo $hari == 'Kamis' ? 'selected' : ''; ?>>Kamis</option>
                                <option value="Jumat" <?php echo $hari == 'Jumat' ? 'selected' : ''; ?>>Jumat</option>
                                <option value="Sabtu" <?php echo $hari == 'Sabtu' ? 'selected' : ''; ?>>Sabtu</option>
                                <option value="Minggu" <?php echo $hari == 'Minggu' ? 'selected' : ''; ?>>Minggu</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Tampilan Kalender Mingguan (hanya tampil jika tidak ada filter dokter dan tempat) -->
    <?php if (empty($id_tempat_praktek) && empty($id_dokter)): ?>
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow border-0 rounded-4">
                    <div class="card-header bg-primary text-white rounded-top-4">
                        <h5 class="mb-0"><i class="fas fa-calendar-week me-2"></i>Jadwal Mingguan</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <?php foreach (array_keys($jadwal_by_hari) as $hari_name): ?>
                                            <th class="text-center">
                                                <?php echo $hari_name; ?>
                                            </th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <?php foreach ($jadwal_by_hari as $hari_name => $jadwal_list): ?>
                                            <td class="align-top">
                                                <?php if (empty($jadwal_list)): ?>
                                                    <div class="text-center text-muted py-3">
                                                        <i class="fas fa-calendar-times fa-2x mb-2"></i>
                                                        <p class="mb-0">Tidak ada jadwal</p>
                                                    </div>
                                                <?php else: ?>
                                                    <?php foreach ($jadwal_list as $jadwal): ?>
                                                        <div class="jadwal-item mb-2 p-2 border-start border-4 
                                                    <?php echo $jadwal['Jenis_Layanan'] == 'Obgin (BPJS)' ? 'border-success' : 'border-primary'; ?> 
                                                    rounded shadow-sm">
                                                            <div class="fw-bold"><?php echo htmlspecialchars($jadwal['Nama_Dokter']); ?></div>
                                                            <div class="small text-muted"><?php echo htmlspecialchars($jadwal['Spesialisasi']); ?></div>
                                                            <div class="mt-1">
                                                                <span class="badge bg-light text-dark">
                                                                    <i class="far fa-clock"></i>
                                                                    <?php echo $jadwal['Jam_Mulai_Format'] . ' - ' . $jadwal['Jam_Selesai_Format']; ?>
                                                                </span>
                                                            </div>
                                                            <div class="small mt-1">
                                                                <i class="fas fa-hospital-alt"></i>
                                                                <?php echo htmlspecialchars($jadwal['Nama_Tempat']); ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tampilan Jadwal Detail -->
    <?php if (empty($jadwal_by_tempat)): ?>
        <div class="alert alert-info rounded-4 shadow-sm">
            <div class="d-flex align-items-center">
                <i class="fas fa-info-circle fa-2x me-3"></i>
                <div>
                    <h5 class="mb-1">Tidak ada jadwal praktek</h5>
                    <p class="mb-0">Tidak ada jadwal praktek yang tersedia untuk kriteria yang dipilih.</p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-md-12">
                <div class="accordion" id="accordionJadwal">
                    <?php foreach ($jadwal_by_tempat as $id_tempat => $data): ?>
                        <div class="accordion-item mb-3 shadow-sm border-0 rounded-4 overflow-hidden">
                            <h2 class="accordion-header">
                                <button class="accordion-button <?php echo $id_tempat_praktek && $id_tempat_praktek != $id_tempat ? 'collapsed' : ''; ?> fw-bold"
                                    type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapse<?php echo $id_tempat; ?>">
                                    <i class="fas fa-hospital-alt me-2"></i>
                                    <?php echo htmlspecialchars($data['info_tempat']['Nama_Tempat']); ?>
                                    <span class="badge bg-primary ms-2 rounded-pill"><?php echo count($data['jadwal']); ?> Jadwal</span>
                                    <?php if (!empty($data['info_tempat']['Jenis_Fasilitas'])): ?>
                                        <span class="badge bg-secondary ms-2 rounded-pill"><?php echo htmlspecialchars($data['info_tempat']['Jenis_Fasilitas']); ?></span>
                                    <?php endif; ?>
                                </button>
                            </h2>
                            <div id="collapse<?php echo $id_tempat; ?>" class="accordion-collapse collapse <?php echo $id_tempat_praktek == $id_tempat || empty($id_tempat_praktek) ? 'show' : ''; ?>">
                                <div class="accordion-body">
                                    <?php if (!empty($data['info_tempat']['Alamat_Lengkap'])): ?>
                                        <div class="mb-3 p-3 bg-light rounded-3">
                                            <div class="d-flex align-items-start">
                                                <i class="fas fa-map-marker-alt text-danger mt-1 me-2"></i>
                                                <div>
                                                    <h6 class="mb-1">Alamat:</h6>
                                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($data['info_tempat']['Alamat_Lengkap'])); ?></p>
                                                    <?php if (!empty($data['info_tempat']['Kota'])): ?>
                                                        <p class="mb-0"><?php echo htmlspecialchars($data['info_tempat']['Kota']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="table-responsive">
                                        <table class="table table-hover table-striped">
                                            <thead class="table-light">
                                                <tr>
                                                    <th><i class="fas fa-user-md me-1"></i>Dokter</th>
                                                    <th><i class="fas fa-calendar-day me-1"></i>Hari</th>
                                                    <th><i class="far fa-clock me-1"></i>Jam Praktek</th>
                                                    <th><i class="fas fa-stethoscope me-1"></i>Jenis Layanan</th>
                                                    <th><i class="fas fa-users me-1"></i>Kuota</th>
                                                    <th><i class="fas fa-info-circle me-1"></i>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($data['jadwal'] as $jadwal): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="fw-bold"><?php echo htmlspecialchars($jadwal['Nama_Dokter']); ?></div>
                                                            <div class="small text-muted"><?php echo htmlspecialchars($jadwal['Spesialisasi']); ?></div>
                                                            <?php if (!empty($jadwal['Nomor_SIP'])): ?>
                                                                <div class="small text-muted">SIP: <?php echo htmlspecialchars($jadwal['Nomor_SIP']); ?></div>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-light text-dark rounded-pill px-3 py-2">
                                                                <?php echo htmlspecialchars($jadwal['Hari']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <i class="far fa-clock text-primary me-1"></i>
                                                                <span><?php echo $jadwal['Jam_Mulai_Format'] . ' - ' . $jadwal['Jam_Selesai_Format']; ?></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge <?php echo strpos($jadwal['Jenis_Layanan'], 'BPJS') !== false ? 'bg-success' : 'bg-primary'; ?> rounded-pill px-3 py-2">
                                                                <?php echo htmlspecialchars($jadwal['Jenis_Layanan']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="me-2">
                                                                    <span class="badge bg-light text-dark rounded-pill px-3 py-2">
                                                                        <i class="fas fa-users me-1"></i>
                                                                        <?php echo htmlspecialchars($jadwal['Kuota_Pasien']); ?>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge <?php echo $jadwal['Status_Kuota'] == 'Tersedia' ? 'bg-success' : 'bg-danger'; ?> rounded-pill px-3 py-2">
                                                                <i class="<?php echo $jadwal['Status_Kuota'] == 'Tersedia' ? 'fas fa-check-circle' : 'fas fa-times-circle'; ?> me-1"></i>
                                                                <?php echo htmlspecialchars($jadwal['Status_Kuota']); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-submit form when filters change
        const form = document.querySelector('#filterForm');
        const filters = form.querySelectorAll('select');

        filters.forEach(filter => {
            filter.addEventListener('change', () => {
                form.submit();
            });
        });

        // Highlight hari ini
        const hariIni = '<?php echo $hari_names[date('l')]; ?>';
        const hariCells = document.querySelectorAll('th');

        hariCells.forEach(cell => {
            if (cell.textContent.trim().startsWith(hariIni)) {
                cell.classList.add('bg-light-primary');
            }
        });
    });
</script>

<?php
$content = ob_get_clean();

// Additional CSS
$additional_css = "
    .card {
        border-radius: 15px;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
    .accordion-item {
        border-radius: 15px;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    .accordion-button:not(.collapsed) {
        background-color: #e7f1ff;
        color: #0d6efd;
    }
    .accordion-button:focus {
        box-shadow: none;
        border-color: rgba(0,0,0,.125);
    }
    .table > :not(caption) > * > * {
        padding: 1rem 0.75rem;
    }
    .badge {
        font-weight: 500;
    }
    .jadwal-item {
        transition: all 0.3s ease;
    }
    .jadwal-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1) !important;
    }
    .bg-light-primary {
        background-color: rgba(13, 110, 253, 0.1);
    }
    .rounded-pill {
        border-radius: 50rem !important;
    }
    .rounded-4 {
        border-radius: 0.75rem !important;
    }
    .rounded-top-4 {
        border-top-left-radius: 0.75rem !important;
        border-top-right-radius: 0.75rem !important;
    }
";

// Include template
include_once '../template/layout.php';
?>