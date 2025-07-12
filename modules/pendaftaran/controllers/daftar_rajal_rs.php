<?php
session_start();
require_once 'config.php';  // Koneksi ke database RS
$page_title = "Daftar Pasien Rawat Jalan RS";

// Ambil status filter dari parameter GET dengan default '0' (tidak aktif)
$filter_dokter = isset($_GET['filter_dokter']) ? $_GET['filter_dokter'] : '0';
$filter_poli = isset($_GET['filter_poli']) ? $_GET['filter_poli'] : '0';
$search = isset($_GET['search']) ? $_GET['search'] : '';

try {
    $query = "SELECT DISTINCT 
            reg.no_rawat,
            pasien.nm_pasien as nama,
            TIMESTAMPDIFF(YEAR, pasien.tgl_lahir, CURDATE()) as usia,
            reg.no_reg,
            poli.nm_poli,
            dokter.nm_dokter,
            reg.stts as status,
            pasien.no_tlp as no_telepon
            FROM reg_periksa as reg
            INNER JOIN pasien ON reg.no_rkm_medis = pasien.no_rkm_medis
            INNER JOIN poliklinik as poli ON reg.kd_poli = poli.kd_poli
            INNER JOIN dokter ON reg.kd_dokter = dokter.kd_dokter
            WHERE reg.tgl_registrasi = CURDATE()
            AND reg.status_lanjut = 'Ralan'
            AND reg.stts != 'Batal'";

    // Tambahkan filter dokter jika diaktifkan
    if ($filter_dokter == '1') {
        $query .= " AND dokter.nm_dokter = 'dr. ARIFIAN JUARI, Sp.OG(K)'";
    }

    // Tambahkan filter poli jika diaktifkan
    if ($filter_poli == '1') {
        $query .= " AND poli.nm_poli LIKE '%OBG%'";
    }

    if ($search) {
        $query .= " AND (pasien.nm_pasien LIKE :search OR reg.no_rawat LIKE :search)";
    }

    $query .= " ORDER BY reg.no_reg ASC";

    $stmt = $conn_db1->prepare($query);

    if ($search) {
        $searchParam = "%$search%";
        $stmt->bindValue(':search', $searchParam, PDO::PARAM_STR);
    }

    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            <h4>Daftar Pasien Rawat Jalan RS</h4>
            <form class="d-flex gap-3" method="GET" action="">
                <?php if (isset($search) && $search !== ''): ?>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                <?php endif; ?>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="filter_dokter" value="1" id="filterDokter" <?php echo $filter_dokter == '1' ? 'checked' : ''; ?> onChange="this.form.submit()">
                    <label class="form-check-label" for="filterDokter">DPJP dr. ARIFIAN JUARI, Sp.OG(K)</label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="filter_poli" value="1" id="filterPoli" <?php echo $filter_poli == '1' ? 'checked' : ''; ?> onChange="this.form.submit()">
                    <label class="form-check-label" for="filterPoli">Poli OBGYN</label>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex gap-2">
                <form class="d-flex gap-2" method="GET" action="">
                    <?php if ($filter_dokter == '1'): ?>
                        <input type="hidden" name="filter_dokter" value="1">
                    <?php endif; ?>
                    <?php if ($filter_poli == '1'): ?>
                        <input type="hidden" name="filter_poli" value="1">
                    <?php endif; ?>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search ?? ''); ?>" placeholder="Cari nama/no.rawat..." class="form-control" style="max-width: 200px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Cari
                    </button>
                    <?php if (isset($search) || $filter_dokter == '1' || $filter_poli == '1'): ?>
                        <a href="daftar_rajal_rs.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Reset Filter
                        </a>
                    <?php endif; ?>
                </form>
                <button onclick="location.reload()" class="btn btn-secondary">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-dark">
                <tr>
                    <th>No</th>
                    <th>No. Rawat</th>
                    <th>No. Antrian</th>
                    <th>Nama Pasien</th>
                    <th>Usia</th>
                    <th>No. Telepon</th>
                    <th>Poliklinik</th>
                    <th>Dokter</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($patients) > 0): ?>
                    <?php foreach ($patients as $index => $patient): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($patient['no_rawat']); ?></td>
                            <td><?php echo htmlspecialchars($patient['no_reg']); ?></td>
                            <td><?php echo htmlspecialchars($patient['nama']); ?></td>
                            <td><?php echo htmlspecialchars($patient['usia']); ?> th</td>
                            <td>
                                <?php if (!empty($patient['no_telepon'])): ?>
                                    <?php
                                    // Bersihkan nomor telepon dari karakter non-numerik
                                    $clean_number = preg_replace('/[^0-9]/', '', $patient['no_telepon']);

                                    // Pastikan format nomor telepon benar untuk WhatsApp
                                    if (substr($clean_number, 0, 1) == '0') {
                                        $clean_number = '62' . substr($clean_number, 1);
                                    } elseif (substr($clean_number, 0, 2) != '62') {
                                        $clean_number = '62' . $clean_number;
                                    }

                                    $whatsapp_url = "https://wa.me/{$clean_number}";
                                    ?>
                                    <div class="d-flex align-items-center">
                                        <span class="me-2"><?php echo htmlspecialchars($patient['no_telepon']); ?></span>
                                        <a href="#" onclick="return openWhatsApp('<?php echo $clean_number; ?>')" class="btn btn-whatsapp btn-sm" title="Chat WhatsApp">
                                            <i class="bi bi-whatsapp"></i>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($patient['nm_poli']); ?></td>
                            <td><?php echo htmlspecialchars($patient['nm_dokter']); ?></td>
                            <td>
                                <span class="badge bg-<?php
                                                        echo match ($patient['status']) {
                                                            'Belum' => 'warning',
                                                            'Sudah' => 'success',
                                                            'Batal' => 'danger',
                                                            default => 'secondary'
                                                        };
                                                        ?>">
                                    <?php echo htmlspecialchars($patient['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center">Tidak ada data pasien</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();

// Additional CSS
$additional_css = "
    .table tbody td {
        vertical-align: middle;
        font-size: 0.9rem;
    }
    .form-switch .form-check-input {
        width: 3em;
        cursor: pointer;
    }
    .form-check-label {
        cursor: pointer;
    }
    .btn-whatsapp {
        background-color: #25D366;
        border-color: #25D366;
        color: white;
    }
    .btn-whatsapp:hover {
        background-color: #128C7E;
        border-color: #128C7E;
        color: white;
    }
";

// Additional JavaScript if needed
$additional_js = "
    // Fungsi untuk membuka WhatsApp dengan pesan default
    function openWhatsApp(number) {
        const defaultMessage = 'Halo, ini dari RS. Kami ingin menginformasikan mengenai jadwal kunjungan Anda.';
        const encodedMessage = encodeURIComponent(defaultMessage);
        window.open('https://wa.me/' + number + '?text=' + encodedMessage, '_blank');
        return false;
    }
";

// Pastikan menggunakan path yang benar untuk template
$template_path = __DIR__ . '/template/layout.php';
if (file_exists($template_path)) {
    include $template_path;
} else {
    // Jika path pertama tidak ditemukan, coba path relatif
    include 'template/layout.php';
}
?>