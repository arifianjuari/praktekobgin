<?php
require_once __DIR__ . '/config/config.php';

// Definisikan base_url untuk memastikan path CSS benar
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$base_url = $protocol . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

// Tambahkan CSS inline untuk memastikan styling diterapkan
$additional_css = "    
    /* Card styling */
    .card {
        border-radius: 15px;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 10px 20px rgba(0,0,0,0.05) !important;
        margin-bottom: 1.5rem;
        border: none;
    }
    
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important;
    }
    
    .card-header {
        background-color: #0d6efd;
        color: white;
        border-bottom: none;
        padding: 1.25rem 1.5rem;
    }
    
    /* Rounded corners */
    .rounded-4 {
        border-radius: 0.75rem !important;
    }
    
    .rounded-top-4 {
        border-top-left-radius: 0.75rem !important;
        border-top-right-radius: 0.75rem !important;
    }
    
    /* Table styling */
    .table-responsive {
        overflow-x: auto;
        white-space: nowrap;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        scrollbar-color: rgba(0,0,0,0.2) rgba(0,0,0,0.05);
    }
    
    .table-responsive::-webkit-scrollbar {
        height: 8px;
    }
    
    .table-responsive::-webkit-scrollbar-track {
        background: rgba(0,0,0,0.05);
        border-radius: 10px;
    }
    
    .table-responsive::-webkit-scrollbar-thumb {
        background-color: rgba(13,110,253,0.3);
        border-radius: 10px;
    }
    
    .table-responsive::-webkit-scrollbar-thumb:hover {
        background-color: rgba(13,110,253,0.5);
    }
    
    .table {
        margin-bottom: 0;
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .table thead th {
        border-top: none;
        font-weight: 600;
        color: #495057;
        background-color: #f8f9fa;
        position: sticky;
        top: 0;
        z-index: 1;
        padding: 0.75rem 1rem;
        font-size: 0.95rem;
    }
    
    .table tbody tr:hover {
        background-color: rgba(13,110,253,0.03);
    }
    
    .table td {
        vertical-align: middle;
        padding: 0.75rem 1rem;
        font-size: 0.95rem;
    }
    
    .table td.text-nowrap {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* Filter section styling */
    #filterSection .card {
        border: 1px solid rgba(0, 0, 0, 0.05);
        box-shadow: 0 5px 15px rgba(0,0,0,0.03) !important;
    }
    
    .form-select, .form-control {
        padding: 0.6rem 0.75rem;
        border-radius: 0.5rem;
        border: 1px solid #dee2e6;
        transition: all 0.2s;
        font-size: 0.95rem;
    }
    
    .form-select:focus, .form-control:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.25rem rgba(13,110,253,0.25);
    }
    
    .form-label {
        font-weight: 500;
        margin-bottom: 0.5rem;
        color: #495057;
    }
    
    /* Button styling */
    .btn {
        border-radius: 0.5rem;
        padding: 0.6rem 1.25rem;
        transition: all 0.3s;
        font-weight: 500;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .btn-primary {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
    
    .btn-outline-primary {
        color: #0d6efd;
        border-color: #0d6efd;
    }
    
    .btn-outline-primary:hover {
        background-color: #0d6efd;
        color: white;
    }
    
    .btn-sm {
        padding: 0.4rem 0.8rem;
        font-size: 0.85rem;
    }
    
    /* Alert styling */
    .alert {
        border-radius: 0.75rem;
        padding: 1rem 1.25rem;
        border: none;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    
    /* Modal styling */
    .modal-content {
        border-radius: 0.75rem;
        border: none;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    
    .modal-header {
        border-bottom: none;
        padding: 1.25rem 1.5rem;
    }
    
    .modal-body {
        padding: 1.5rem;
    }
    
    .modal-footer {
        border-top: none;
        padding: 1.25rem 1.5rem;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .container-fluid {
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }
        
        h2 {
            font-size: 1.5rem;
        }
        
        .card-header {
            padding: 1rem 1.25rem;
        }
        
        .card-body {
            padding: 1rem;
        }
        
        .table th, .table td {
            padding: 0.6rem 0.75rem;
            font-size: 0.85rem;
            vertical-align: middle;
        }
        
        .btn-sm {
            padding: 0.3rem 0.6rem;
            font-size: 0.8rem;
        }
        
        /* Improve mobile table display */
        .table-responsive {
            border-radius: 0.5rem;
            margin-left: -0.75rem;
            margin-right: -0.75rem;
            width: calc(100% + 1.5rem);
        }
        
        /* Make sure modals are properly sized on mobile */
        .modal-dialog {
            margin: 0.5rem;
            max-width: calc(100% - 1rem);
        }
        
        /* Better display of buttons on mobile */
        .btn-outline-info, .btn-outline-warning {
            border-width: 1px;
        }
    }
";

// Mulai output buffering untuk mengumpulkan konten yang akan dirender melalui layout
ob_start();

// Kredensial database - menggunakan kredensial yang sama seperti di pengumuman.php
require_once __DIR__ . '/config/database.php';
$db_host = $db2_host;
$db_user = $db2_username;
$db_pass = $db2_password;
$db_name = $db2_database;

// Koneksi database
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, 8889);
if ($conn->connect_error) {
    die('Koneksi database gagal: ' . $conn->connect_error);
}

// Filter berdasarkan hari dan jenis layanan
$hari = isset($_GET['hari']) ? $_GET['hari'] : '';
$jenis_layanan = isset($_GET['layanan']) ? $_GET['layanan'] : '';

// Query data jadwal_rutin dengan filter dan join menu_layanan
$sql = "SELECT jr.*, ml.nama_layanan, ml.kategori, ml.harga, ml.durasi_estimasi AS durasi, d.Nama_Dokter, d.Spesialisasi, tp.Nama_Tempat, tp.Alamat_Lengkap
        FROM jadwal_rutin jr
        JOIN menu_layanan ml ON jr.ID_Layanan = ml.id_layanan
        JOIN dokter d ON jr.ID_Dokter = d.ID_Dokter
        JOIN tempat_praktek tp ON jr.ID_Tempat_Praktek = tp.ID_Tempat_Praktek
        WHERE jr.Status_Aktif = 1";

// Tambahkan filter hari jika ada
if (!empty($hari)) {
    $sql .= " AND jr.Hari = '" . $conn->real_escape_string($hari) . "'";
}

// Tambahkan filter jenis layanan jika ada
if (!empty($jenis_layanan)) {
    $sql .= " AND ml.nama_layanan = '" . $conn->real_escape_string($jenis_layanan) . "'";
}

// Urutkan berdasarkan hari dan jam mulai
$sql .= " ORDER BY FIELD(jr.Hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'), jr.Jam_Mulai ASC";
$result = $conn->query($sql);

// Query untuk mendapatkan daftar unik nama_layanan
$sql_layanan = "SELECT DISTINCT ml.nama_layanan
                FROM jadwal_rutin jr
                JOIN menu_layanan ml ON jr.ID_Layanan = ml.id_layanan
                WHERE jr.Status_Aktif = 1 AND ml.nama_layanan IS NOT NULL
                ORDER BY ml.nama_layanan";
$result_layanan = $conn->query($sql_layanan);
$jenis_layanan_list = [];
while ($row = $result_layanan->fetch_assoc()) {
    if (!empty($row['nama_layanan'])) {
        $jenis_layanan_list[] = $row['nama_layanan'];
    }
}

// Kelompokkan jadwal berdasarkan hari
$jadwal_by_day = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if (!isset($jadwal_by_day[$row['Hari']])) {
            $jadwal_by_day[$row['Hari']] = [];
        }
        $jadwal_by_day[$row['Hari']][] = $row;
    }
}

// Urutan hari
$hari_order = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

// Urutkan array berdasarkan urutan hari
$jadwal_sorted = [];
foreach ($hari_order as $h) {
    if (isset($jadwal_by_day[$h])) {
        $jadwal_sorted[$h] = $jadwal_by_day[$h];
    }
}
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow border-0 rounded-4">
                <div class="card-header bg-white text-dark d-flex justify-content-between align-items-center rounded-top-4 border-bottom">
    <h5 class="mb-0"><i class="bi bi-calendar-week me-2 text-primary"></i>Jadwal Praktek</h5>
    
</div>

                <div id="filterSection" class="collapse show">
                    <div class="card-body py-2 px-3">
    <form id="filterForm" method="GET" class="row gx-2 align-items-center">
    <div class="col-4 col-md-3">
        <select class="form-select form-select-sm" id="hari" name="hari" aria-label="Filter Hari" onchange="this.form.submit()">
            <option value="">Hari (Semua)</option>
            <option value="Senin" <?= isset($_GET['hari']) && $_GET['hari'] == 'Senin' ? 'selected' : '' ?>>Senin</option>
            <option value="Selasa" <?= isset($_GET['hari']) && $_GET['hari'] == 'Selasa' ? 'selected' : '' ?>>Selasa</option>
            <option value="Rabu" <?= isset($_GET['hari']) && $_GET['hari'] == 'Rabu' ? 'selected' : '' ?>>Rabu</option>
            <option value="Kamis" <?= isset($_GET['hari']) && $_GET['hari'] == 'Kamis' ? 'selected' : '' ?>>Kamis</option>
            <option value="Jumat" <?= isset($_GET['hari']) && $_GET['hari'] == 'Jumat' ? 'selected' : '' ?>>Jumat</option>
            <option value="Sabtu" <?= isset($_GET['hari']) && $_GET['hari'] == 'Sabtu' ? 'selected' : '' ?>>Sabtu</option>
            <option value="Minggu" <?= isset($_GET['hari']) && $_GET['hari'] == 'Minggu' ? 'selected' : '' ?>>Minggu</option>
        </select>
    </div>
    <div class="col-8 col-md-7 d-flex align-items-center">
        <select class="form-select form-select-sm me-2" id="layanan" name="layanan" aria-label="Filter Jenis Layanan" onchange="this.form.submit()">
            <option value="">Layanan (Semua)</option>
            <?php foreach ($jenis_layanan_list as $jl): ?>
                <option value="<?= htmlspecialchars($jl) ?>" <?= isset($_GET['layanan']) && $_GET['layanan'] == $jl ? 'selected' : '' ?>>
                    <?= htmlspecialchars($jl) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <a href="jadwal.php" class="btn btn-outline-secondary btn-sm col-auto ms-1">
            <i class="bi bi-x-circle"></i>
        </a>
    </div>
</form>
</div>

                <div class="card-body">
                    <?php if (empty($jadwal_sorted)): ?>
                        <div class="alert alert-info rounded-4 shadow-sm mb-0">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-info-circle-fill fs-3 me-3"></i>
                                <div>
                                    <h5 class="alert-heading fw-bold">Tidak Ada Data</h5>
                                    <p class="mb-0">Tidak ada jadwal praktek yang ditemukan dengan filter yang dipilih.</p>
                                    <p class="mt-2 mb-0"><a href="jadwal.php" class="alert-link">Tampilkan semua jadwal</a></p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($jadwal_sorted as $hari => $jadwal_hari): ?>
                            <div class="card shadow mb-4 rounded-4 border-0">
                                <div class="card-header bg-white d-flex justify-content-center align-items-center rounded-top-4 border-bottom" style="padding:0.4rem 1rem;">
    <span style="font-size:1rem;font-weight:bold;font-family:'Segoe UI','Roboto','Arial',sans-serif;color:#d35400;letter-spacing:2px;text-transform:uppercase;text-align:center;width:100%;display:block;">
        <?= htmlspecialchars($hari) ?>
    </span>
</div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                <tr>
                                    <th scope="col" class="text-nowrap">Jam Praktek</th>
<th scope="col" class="text-nowrap">Dokter</th>
<th scope="col" class="text-nowrap">Tempat Praktek</th>
<th scope="col" class="text-nowrap">Jenis Layanan</th>
<th scope="col" class="text-nowrap">Kuota</th>
<th scope="col" class="text-nowrap">Harga</th>
<th scope="col" class="text-nowrap">Durasi</th>
<th scope="col" class="text-nowrap">Deskripsi</th>
<th scope="col" class="text-nowrap">Persiapan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($jadwal_hari as $j): ?>
                                    <tr class="align-middle">
    <td class="text-nowrap fw-medium">
        <span class="fw-bold"><?= htmlspecialchars(substr($j['Jam_Mulai'],0,5)) ?> - <?= htmlspecialchars(substr($j['Jam_Selesai'],0,5)) ?></span>
    </td>
    <td class="text-nowrap"><?= htmlspecialchars($j['Nama_Dokter'] ?? '-') ?></td>
    <td class="text-nowrap"><?= htmlspecialchars($j['Nama_Tempat'] ?? '-') ?></td>
    <td class="text-nowrap"><?= htmlspecialchars($j['Jenis_Layanan'] ?? '-') ?></td>
    <td class="text-center"><?= htmlspecialchars($j['Kuota_Pasien'] ?? '-') ?></td>
    <td class="text-nowrap"><?= !is_null($j['harga']) ? 'Rp ' . number_format($j['harga'],0,',','.') : '-' ?></td>
    <td class="text-nowrap"><?= !is_null($j['durasi']) ? htmlspecialchars($j['durasi']) . ' menit' : '-' ?></td>
    <td class="text-center">
        <?php if (!empty($j['deskripsi'])): ?>
            <button type="button" class="btn btn-sm btn-outline-info rounded-pill w-100" data-bs-toggle="modal" data-bs-target="#deskripsiModal<?= $j['ID_Jadwal_Rutin'] ?>">
                <i class="bi bi-info-circle me-md-1"></i><span class="d-none d-md-inline">Detail</span>
            </button>
        <?php else: ?>
            -
        <?php endif; ?>
    </td>
    <td class="text-center">
        <?php if (!empty($j['persiapan'])): ?>
            <button type="button" class="btn btn-sm btn-outline-warning rounded-pill w-100" data-bs-toggle="modal" data-bs-target="#persiapanModal<?= $j['ID_Jadwal_Rutin'] ?>">
                <i class="bi bi-list-check me-md-1"></i><span class="d-none d-md-inline">Lihat</span>
            </button>
        <?php else: ?>
            -
        <?php endif; ?>
    </td>
</tr>
                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Floating Action Button: Shortcut ke Form Pendaftaran -->
<a href="modules/pendaftaran/views/form_pendaftaran_pasien.php" class="btn btn-primary rounded-circle shadow-lg position-fixed" style="bottom:32px;right:32px;width:64px;height:64px;z-index:1050;display:flex;align-items:center;justify-content:center;font-size:2rem;box-shadow:0 4px 16px rgba(0,0,0,0.18);transition:background 0.2s;" title="Daftar Baru">
    <i class="bi bi-plus"></i>
</a>

<!-- Modal untuk deskripsi -->
<?php if ($result): ?>
    <?php $result->data_seek(0); // Reset pointer ?>
    <?php while ($row = $result->fetch_assoc()): ?>
        <?php if (!empty($row['deskripsi'])): ?>
            <div class="modal fade" id="deskripsiModal<?= $row['ID_Jadwal_Rutin'] ?>" tabindex="-1" aria-labelledby="deskripsiModalLabel<?= $row['ID_Jadwal_Rutin'] ?>" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-info text-white">
                            <h5 class="modal-title" id="deskripsiModalLabel<?= $row['ID_Jadwal_Rutin'] ?>"><i class="bi bi-info-circle me-2"></i>Deskripsi Layanan</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-0"><?= nl2br(htmlspecialchars($row['deskripsi'])) ?></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endwhile; ?>
<?php endif; ?>

<!-- Modal untuk persiapan -->
<?php if ($result): ?>
    <?php $result->data_seek(0); // Reset pointer ?>
    <?php while ($row = $result->fetch_assoc()): ?>
        <?php if (!empty($row['persiapan'])): ?>
            <div class="modal fade" id="persiapanModal<?= $row['ID_Jadwal_Rutin'] ?>" tabindex="-1" aria-labelledby="persiapanModalLabel<?= $row['ID_Jadwal_Rutin'] ?>" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-warning text-dark">
                            <h5 class="modal-title" id="persiapanModalLabel<?= $row['ID_Jadwal_Rutin'] ?>"><i class="bi bi-list-check me-2"></i>Persiapan Pasien</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-0"><?= nl2br(htmlspecialchars($row['persiapan'])) ?></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endwhile; ?>
<?php endif; ?>

<?php
$conn->close();

// Ambil konten yang sudah di-buffer dan bersihkan buffer
$content = ob_get_clean();

// Set page title
$page_title = 'Jadwal Praktek';

// Render layout dengan konten yang sudah dikumpulkan
require_once __DIR__ . '/template/layout.php';
?>
