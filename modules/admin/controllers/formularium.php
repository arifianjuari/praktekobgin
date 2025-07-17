<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/auth.php';

// Cek apakah user adalah admin
if (!isAdmin()) {
    header('Location: ' . $base_url . '/login.php');
    exit;
}

// Fungsi untuk membuat UUID v4
function generateUUID()
{
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
}

// Fungsi untuk format harga
function formatRupiah($angka)
{
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Daftar kategori obat
$kategori_list = [
    'Analgesik',
    'Antibiotik',
    'Antiinflamasi',
    'Antihipertensi',
    'Antidiabetes',
    'Vitamin dan Suplemen',
    'Hormon',
    'Obat Kulit',
    'Obat Mata',
    'Obat Saluran Pencernaan',
    'Obat Saluran Pernapasan',
    'Lainnya'
];

// Daftar bentuk sediaan
$bentuk_sediaan_list = [
    'Tablet',
    'Kapsul',
    'Sirup',
    'Suspensi',
    'Injeksi',
    'Salep',
    'Krim',
    'Tetes',
    'Suppositoria',
    'Inhaler',
    'Lainnya'
];

// Proses tambah data
if (isset($_POST['tambah'])) {
    $id_obat = generateUUID();
    $nama_obat = $_POST['nama_obat'];
    $nama_generik = $_POST['nama_generik'] ?? '';
    $bentuk_sediaan = $_POST['bentuk_sediaan'] ?? '';
    $dosis = $_POST['dosis'] ?? '';
    $kategori = $_POST['kategori'] ?? '';
    $catatan_obat = $_POST['catatan_obat'] ?? '';
    $harga = $_POST['harga'];
    $farmasi = $_POST['farmasi'] ?? '';
    $ed = !empty($_POST['ed']) ? $_POST['ed'] : null;
    $status_aktif = isset($_POST['status_aktif']) ? 1 : 0;

    try {
        $stmt = $conn->prepare("INSERT INTO formularium (id_obat, nama_obat, nama_generik, bentuk_sediaan, dosis, kategori, catatan_obat, harga, farmasi, ed, status_aktif) 
            VALUES (:id_obat, :nama_obat, :nama_generik, :bentuk_sediaan, :dosis, :kategori, :catatan_obat, :harga, :farmasi, :ed, :status_aktif)");
        $stmt->bindParam(':id_obat', $id_obat);
        $stmt->bindParam(':nama_obat', $nama_obat);
        $stmt->bindParam(':nama_generik', $nama_generik);
        $stmt->bindParam(':bentuk_sediaan', $bentuk_sediaan);
        $stmt->bindParam(':dosis', $dosis);
        $stmt->bindParam(':kategori', $kategori);
        $stmt->bindParam(':catatan_obat', $catatan_obat);
        $stmt->bindParam(':harga', $harga);
        $stmt->bindParam(':farmasi', $farmasi);
        if ($ed === null) {
            $stmt->bindValue(':ed', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':ed', $ed);
        }
        $stmt->bindParam(':status_aktif', $status_aktif);
        $stmt->execute();
        $success_message = "Data obat berhasil ditambahkan";
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Proses edit data
if (isset($_POST['edit'])) {
    $id_obat = $_POST['id_obat'];
    $nama_obat = $_POST['nama_obat'];
    $nama_generik = $_POST['nama_generik'] ?? '';
    $bentuk_sediaan = $_POST['bentuk_sediaan'] ?? '';
    $dosis = $_POST['dosis'] ?? '';
    $kategori = $_POST['kategori'] ?? '';
    $catatan_obat = $_POST['catatan_obat'] ?? '';
    $harga = $_POST['harga'];
    $farmasi = $_POST['farmasi'] ?? '';
    // Determine ED: null if empty to allow proper NULL binding
    $ed = !empty($_POST['ed']) ? $_POST['ed'] : null;
    $status_aktif = isset($_POST['status_aktif']) ? 1 : 0;

    try {
        $stmt = $conn->prepare("UPDATE formularium SET 
                nama_obat = :nama_obat,
                nama_generik = :nama_generik,
                bentuk_sediaan = :bentuk_sediaan,
                dosis = :dosis,
                kategori = :kategori,
                catatan_obat = :catatan_obat,
                harga = :harga,
                farmasi = :farmasi,
                ed = :ed,
                status_aktif = :status_aktif
                WHERE id_obat = :id_obat");

        $stmt->bindParam(':id_obat', $id_obat);
        $stmt->bindParam(':nama_obat', $nama_obat);
        $stmt->bindParam(':nama_generik', $nama_generik);
        $stmt->bindParam(':bentuk_sediaan', $bentuk_sediaan);
        $stmt->bindParam(':dosis', $dosis);
        $stmt->bindParam(':kategori', $kategori);
        $stmt->bindParam(':catatan_obat', $catatan_obat);
        $stmt->bindParam(':harga', $harga);
        $stmt->bindParam(':farmasi', $farmasi);
        // Bind ED: set as NULL if not provided
        if ($ed === null) {
            $stmt->bindValue(':ed', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':ed', $ed);
        }
        $stmt->bindParam(':status_aktif', $status_aktif);

        $stmt->execute();
        $success_message = "Data obat berhasil diperbarui";
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Proses hapus data
if (isset($_GET['hapus'])) {
    $id_obat = $_GET['hapus'];

    try {
        $stmt = $conn->prepare("DELETE FROM formularium WHERE id_obat = :id_obat");
        $stmt->bindParam(':id_obat', $id_obat);
        $stmt->execute();
        $success_message = "Data obat berhasil dihapus";
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Ambil data untuk ditampilkan
try {
    // Filter berdasarkan kategori jika ada
    $kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : '';

    if (!empty($kategori_filter)) {
        $stmt = $conn->prepare("SELECT * FROM formularium WHERE kategori = :kategori ORDER BY nama_obat ASC");
        $stmt->bindParam(':kategori', $kategori_filter);
        $stmt->execute();
    } else {
        $stmt = $conn->query("SELECT * FROM formularium ORDER BY nama_obat ASC");
    }

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
    $result = [];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formularium - Admin</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="<?= $base_url ?>/assets/css/styles.css" rel="stylesheet">

    <style>
        /* Base Styles */
        body {
            overflow-x: hidden; /* Prevent horizontal scrollbar */
        }
        
        /* Main Content Layout */
        .main-content {
            margin-left: 240px;
            padding: 20px;
            transition: margin-left 0.3s ease, width 0.3s ease;
            width: calc(100% - 240px); /* Width minus sidebar width */
            box-sizing: border-box;
        }
        
        /* Adjust main content when sidebar is minimized */
        .sidebar.minimized ~ .main-content {
            margin-left: 60px;
            width: calc(100% - 60px); /* Width minus minimized sidebar width */
        }
        
        /* Mobile adjustments */
        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }
        
        .filter-container {
            margin-bottom: 20px;
        }

        .filter-container .btn {
            margin-right: 5px;
            margin-bottom: 5px;
        }
        /* Perkecil font tabel formularium */
        #tabelFormularium th, #tabelFormularium td {
            font-size: 0.85rem;
        }
    </style>
</head>

<body>
    <?php include_once __DIR__ . '/../../../template/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $success_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $error_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Daftar Obat</h5>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                                <i class="bi bi-plus-circle"></i> Tambah Obat
                            </button>
                        </div>
                        <div class="card-body">
                            <!-- Filter Kategori -->
                            <div class="filter-container">
                                <div class="d-flex flex-wrap align-items-center">
                                    <label class="me-2"><strong>Filter Kategori:</strong></label>
                                    <a href="<?= $base_url ?>/modules/admin/formularium.php" class="btn <?= empty($kategori_filter) ? 'btn-primary' : 'btn-outline-primary' ?>">
                                        Semua
                                    </a>
                                    <?php foreach ($kategori_list as $kat): ?>
                                        <a href="<?= $base_url ?>/modules/admin/formularium.php?kategori=<?= urlencode($kat) ?>"
                                            class="btn <?= $kategori_filter === $kat ? 'btn-primary' : 'btn-outline-primary' ?>">
                                            <?= $kat ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table id="tabelFormularium" class="table table-striped table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama Obat</th>
                                            <th>Nama Generik</th>
                                            <th>Bentuk Sediaan</th>
                                            <th>Dosis</th>
                                            <th>Harga</th>
                                            <th>Farmasi</th>
                                            <th>Catatan</th>
                                            <th>ED</th>
                                            <th>Kategori</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1;
                                        foreach ($result as $row):
                                            $raw_harga = $row['harga'];
                                            $ed_formatted = !empty($row['ed']) ? date('d-m-Y', strtotime($row['ed'])) : '';
                                            $ed_raw = !empty($row['ed']) ? $row['ed'] : '';
                                        ?>
                                            <tr data-id="<?= $row['id_obat'] ?>">
                                                <td><?= $no++ ?></td>
                                                <td class="editable" data-field="nama_obat"><?= htmlspecialchars($row['nama_obat']) ?></td>
                                                <td class="editable" data-field="nama_generik"><?= htmlspecialchars($row['nama_generik'] ?? '') ?></td>
                                                <td class="editable" data-field="bentuk_sediaan"><?= htmlspecialchars($row['bentuk_sediaan']) ?></td>
                                                <td class="editable" data-field="dosis"><?= htmlspecialchars($row['dosis'] ?? '') ?></td>
                                                <td class="editable" data-field="harga" data-value="<?= $raw_harga ?>"><?= formatRupiah($row['harga']) ?></td>
                                                <td class="editable" data-field="farmasi"><?= htmlspecialchars($row['farmasi'] ?? '') ?></td>
                                                <td class="editable" data-field="catatan_obat"><?= htmlspecialchars($row['catatan_obat'] ?? '') ?></td>
                                                <td class="editable date-field" data-field="ed" data-value="<?= $ed_raw ?>">
                                                    <span class="editable-text"><?= $ed_formatted ?: '-' ?></span>
                                                    <input type="date" class="form-control form-control-sm editable-date" style="display:none;" value="<?= $ed_raw ?>">
                                                </td>
                                                <td class="editable" data-field="kategori">
                                                    <select class="form-select form-select-sm editable-select" data-field="kategori" style="display:none;">
                                                        <?php foreach ($kategori_list as $kategori): ?>
                                                            <option value="<?= $kategori ?>" <?= $kategori == $row['kategori'] ? 'selected' : '' ?>>
                                                                <?= $kategori ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <span class="editable-text"><?= htmlspecialchars($row['kategori'] ?? '-') ?></span>
                                                </td>
                                                <td class="editable" data-field="status_aktif" data-value="<?= $row['status_aktif'] ?>">
                                                    <select class="form-select form-select-sm editable-select" data-field="status_aktif" style="display:none;">
                                                        <option value="1" <?= $row['status_aktif'] == 1 ? 'selected' : '' ?>>Aktif</option>
                                                        <option value="0" <?= $row['status_aktif'] == 0 ? 'selected' : '' ?>>Nonaktif</option>
                                                    </select>
                                                    <span class="badge <?= $row['status_aktif'] ? 'bg-success' : 'bg-danger' ?>">
                                                        <?= $row['status_aktif'] ? 'Aktif' : 'Nonaktif' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-info btn-edit-modal"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modalEdit"
                                                        data-id="<?= $row['id_obat'] ?>"
                                                        data-nama="<?= htmlspecialchars($row['nama_obat']) ?>"
                                                        data-generik="<?= htmlspecialchars($row['nama_generik'] ?? '') ?>"
                                                        data-bentuk="<?= htmlspecialchars($row['bentuk_sediaan']) ?>"
                                                        data-dosis="<?= htmlspecialchars($row['dosis'] ?? '') ?>"
                                                        data-kategori="<?= htmlspecialchars($row['kategori']) ?>"
                                                        data-catatan="<?= htmlspecialchars($row['catatan_obat']) ?>"
                                                        data-harga="<?= $row['harga'] ?>"
                                                        data-farmasi="<?= htmlspecialchars($row['farmasi'] ?? '') ?>"
                                                        data-ed="<?= (isset($row['ed']) && $row['ed'] != '0000-00-00' ? htmlspecialchars($row['ed']) : '') ?>"
                                                        data-status="<?= $row['status_aktif'] ?>">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>
                                                    <a href="#" class="btn btn-sm btn-danger btn-hapus"
                                                        data-id="<?= $row['id_obat'] ?>"
                                                        data-nama="<?= htmlspecialchars($row['nama_obat']) ?>">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah -->
    <div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTambahLabel">Tambah Obat</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nama_obat" class="form-label">Nama Obat <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_obat" name="nama_obat" required>
                        </div>
                        <div class="mb-3">
                            <label for="nama_generik" class="form-label">Nama Generik</label>
                            <input type="text" class="form-control" id="nama_generik" name="nama_generik">
                        </div>
                        <div class="mb-3">
                            <label for="bentuk_sediaan" class="form-label">Bentuk Sediaan</label>
                            <input type="text" class="form-control" id="bentuk_sediaan" name="bentuk_sediaan" placeholder="Masukkan Bentuk Sediaan">
                        </div>
                        <div class="mb-3">
                            <label for="dosis" class="form-label">Dosis</label>
                            <input type="text" class="form-control" id="dosis" name="dosis">
                        </div>
                        <div class="mb-3">
                            <label for="kategori" class="form-label">Kategori</label>
                            <select class="form-select" id="kategori" name="kategori">
                                <option value="">Pilih Kategori</option>
                                <?php foreach ($kategori_list as $kategori): ?>
                                    <option value="<?= $kategori ?>"><?= $kategori ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="harga" class="form-label">Harga <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" id="harga" name="harga" min="0" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="farmasi" class="form-label">Farmasi</label>
                            <input type="text" class="form-control" id="farmasi" name="farmasi">
                        </div>
                        <div class="mb-3">
                            <label for="ed" class="form-label">ED</label>
                            <input type="date" class="form-control" id="ed" name="ed">
                        </div>
                        <div class="mb-3">
                            <label for="catatan_obat" class="form-label">Catatan</label>
                            <textarea class="form-control" id="catatan_obat" name="catatan_obat" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="status_aktif" name="status_aktif" checked>
                                <label class="form-check-label" for="status_aktif">Status Aktif</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit -->
    <div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalEditLabel">Edit Obat</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="edit_id_obat" name="id_obat">
                        <div class="mb-3">
                            <label for="edit_nama_obat" class="form-label">Nama Obat <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_nama_obat" name="nama_obat" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_nama_generik" class="form-label">Nama Generik</label>
                            <input type="text" class="form-control" id="edit_nama_generik" name="nama_generik">
                        </div>
                        <div class="mb-3">
                            <label for="edit_bentuk_sediaan" class="form-label">Bentuk Sediaan</label>
                            <input type="text" class="form-control" id="edit_bentuk_sediaan" name="bentuk_sediaan" placeholder="Masukkan Bentuk Sediaan">
                        </div>
                        <div class="mb-3">
                            <label for="edit_dosis" class="form-label">Dosis</label>
                            <input type="text" class="form-control" id="edit_dosis" name="dosis">
                        </div>
                        <div class="mb-3">
                            <label for="edit_kategori" class="form-label">Kategori</label>
                            <select class="form-select" id="edit_kategori" name="kategori">
                                <option value="">Pilih Kategori</option>
                                <?php foreach ($kategori_list as $kategori): ?>
                                    <option value="<?= $kategori ?>"><?= $kategori ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_harga" class="form-label">Harga <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" id="edit_harga" name="harga" min="0" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_farmasi" class="form-label">Farmasi</label>
                            <input type="text" class="form-control" id="edit_farmasi" name="farmasi">
                        </div>
                        <div class="mb-3">
                            <label for="edit_ed" class="form-label">ED</label>
                            <input type="date" class="form-control" id="edit_ed" name="ed">
                        </div>
                        <div class="mb-3">
                            <label for="edit_catatan_obat" class="form-label">Catatan</label>
                            <textarea class="form-control" id="edit_catatan_obat" name="catatan_obat" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="edit_status_aktif" name="status_aktif">
                                <label class="form-check-label" for="edit_status_aktif">Status Aktif</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .editable {
            position: relative;
            cursor: pointer;
        }
        .editable:hover {
            background-color: #f8f9fa;
        }
        .editable input, .editable select {
            width: 100%;
            box-sizing: border-box;
        }
        .editable-active {
            background-color: #e9ecef;
        }
        .save-indicator {
            position: absolute;
            top: 0;
            right: 0;
            font-size: 12px;
            color: green;
            display: none;
        }
    </style>

    <script>
        $(document).ready(function() {
            // Inisialisasi DataTables tanpa pagination
            var table = $('#tabelFormularium').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
                },
                paging: false,
                info: false // Menghilangkan informasi "Showing X of Y entries"
            });

            // Mengisi data ke modal edit
            $('#modalEdit').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var id = button.data('id');
                var nama = button.data('nama');
                var generik = button.data('generik');
                var bentuk = button.data('bentuk');
                var dosis = button.data('dosis');
                var kategori = button.data('kategori');
                var catatan = button.data('catatan');
                var harga = button.data('harga');
                var farmasi = button.data('farmasi');
                // Use attribute to preserve empty string
                var ed = button.attr('data-ed');
                var status = button.data('status');

                var modal = $(this);
                modal.find('#edit_id_obat').val(id);
                modal.find('#edit_nama_obat').val(nama);
                modal.find('#edit_nama_generik').val(generik);
                modal.find('#edit_bentuk_sediaan').val(bentuk);
                modal.find('#edit_dosis').val(dosis);
                modal.find('#edit_kategori').val(kategori);
                modal.find('#edit_catatan_obat').val(catatan);
                modal.find('#edit_harga').val(harga);
                modal.find('#edit_farmasi').val(farmasi);
                // Set ED value or clear if empty
                modal.find('#edit_ed').val(ed ? ed : '');
                modal.find('#edit_status_aktif').prop('checked', status == 1);
            });

            // Konfirmasi hapus dengan SweetAlert2
            $('.btn-hapus').on('click', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                var nama = $(this).data('nama');

                Swal.fire({
                    title: 'Konfirmasi Hapus',
                    text: `Anda yakin ingin menghapus obat "${nama}"?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `?hapus=${id}`;
                    }
                });
            });

            // Auto-hide alert setelah 5 detik
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);

            // Inline editing functionality
            $('.editable').on('click', function(e) {
                // Don't trigger if clicking on a select or input
                if ($(e.target).is('select, input')) {
                    return;
                }
                
                var $cell = $(this);
                var field = $cell.data('field');
                var currentValue = $cell.data('value') || $cell.text().trim();
                
                // Handle different field types
                if (field === 'kategori' || field === 'status_aktif') {
                    // For select fields
                    $cell.addClass('editable-active');
                    $cell.find('.editable-text').hide();
                    $cell.find('.editable-select').show().focus();
                } else if (field === 'ed') {
                    // For date fields
                    $cell.addClass('editable-active');
                    $cell.find('.editable-text').hide();
                    $cell.find('.editable-date').show().focus();
                } else {
                    // For text fields
                    var inputType = field === 'harga' ? 'number' : 'text';
                    var inputValue = field === 'harga' ? currentValue : $cell.text().trim();
                    
                    $cell.addClass('editable-active');
                    $cell.html(`<input type="${inputType}" class="form-control form-control-sm" value="${inputValue}">
                                <span class="save-indicator"><i class="bi bi-check-circle"></i></span>`);
                    $cell.find('input').focus().select();
                }
            });

            // Handle blur event for text inputs
            $(document).on('blur', '.editable-active input[type="text"], .editable-active input[type="number"]', function() {
                var $input = $(this);
                var $cell = $input.closest('.editable');
                var newValue = $input.val().trim();
                var field = $cell.data('field');
                var rowId = $cell.closest('tr').data('id');
                
                // Save the value
                saveInlineEdit(rowId, field, newValue, $cell);
            });

            // Handle change event for selects
            $(document).on('change', '.editable-select', function() {
                var $select = $(this);
                var $cell = $select.closest('.editable');
                var newValue = $select.val();
                var field = $cell.data('field');
                var rowId = $cell.closest('tr').data('id');
                
                // Save the value
                saveInlineEdit(rowId, field, newValue, $cell);
            });

            // Handle change event for date inputs
            $(document).on('change', '.editable-date', function() {
                var $input = $(this);
                var $cell = $input.closest('.editable');
                var newValue = $input.val();
                var field = $cell.data('field');
                var rowId = $cell.closest('tr').data('id');
                
                // Save the value
                saveInlineEdit(rowId, field, newValue, $cell);
            });

            // Handle Enter key for text inputs
            $(document).on('keydown', '.editable-active input', function(e) {
                if (e.keyCode === 13) { // Enter key
                    $(this).blur();
                }
            });

            // Function to save inline edits
            function saveInlineEdit(id, field, value, $cell) {
                // Store original value for rollback if needed
                var originalValue = $cell.data('original-value') || $cell.text().trim();
                $cell.data('original-value', originalValue);
                
                // Show loading indicator
                var $indicator = $('<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div>');
                $cell.append($indicator);
                
                $.ajax({
                    url: 'update_formularium.php',
                    type: 'POST',
                    data: {
                        id: id,
                        field: field,
                        value: value
                    },
                    success: function(response) {
                        // Remove loading indicator
                        $indicator.remove();
                        
                        var result;
                        
                        // Try to parse the response as JSON
                        try {
                            // If response is already an object, use it directly
                            if (typeof response === 'object') {
                                result = response;
                            } else {
                                // Try to parse string response as JSON
                                result = JSON.parse(response);
                            }
                            
                            if (result.success) {
                                // Update the cell display
                                updateCellDisplay($cell, field, value, result.formatted);
                                
                                // Show success indicator briefly
                                $cell.find('.save-indicator').show().delay(1000).fadeOut();
                            } else {
                                // Show error
                                Swal.fire({
                                    title: 'Error',
                                    text: result.message || 'Gagal menyimpan perubahan',
                                    icon: 'error'
                                });
                                // Revert to original value
                                updateCellDisplay($cell, field, originalValue, null);
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                            console.log('Raw response:', response);
                            
                            // Even though there was an error, the update might have succeeded
                            // So we'll update the display but show a warning
                            updateCellDisplay($cell, field, value, null);
                            
                            // Show a warning toast instead of an error dialog
                            const Toast = Swal.mixin({
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true
                            });
                            
                            Toast.fire({
                                icon: 'warning',
                                title: 'Peringatan',
                                text: 'Respons server tidak valid, tetapi data mungkin sudah tersimpan'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        // Remove loading indicator
                        $indicator.remove();
                        
                        console.error('AJAX Error:', status, error);
                        
                        // Even though there was an error, the update might have succeeded
                        // So we'll update the display but show a warning
                        updateCellDisplay($cell, field, value, null);
                        
                        // Show a warning toast instead of an error dialog
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                        
                        Toast.fire({
                            icon: 'warning',
                            title: 'Peringatan',
                            text: 'Koneksi server bermasalah, tetapi data mungkin sudah tersimpan'
                        });
                    }
                });
            }

            // Function to update cell display after edit
            function updateCellDisplay($cell, field, value, formatted) {
                $cell.removeClass('editable-active');
                
                if (field === 'kategori') {
                    // For select fields
                    $cell.find('.editable-select').hide();
                    $cell.find('.editable-text').text(value).show();
                } else if (field === 'status_aktif') {
                    // For status field
                    $cell.find('.editable-select').hide();
                    var badgeClass = value == 1 ? 'bg-success' : 'bg-danger';
                    var statusText = value == 1 ? 'Aktif' : 'Nonaktif';
                    $cell.find('.badge').removeClass('bg-success bg-danger').addClass(badgeClass).text(statusText);
                } else if (field === 'ed') {
                    // For date fields
                    $cell.find('.editable-date').hide();
                    $cell.data('value', value);
                    var displayDate = value ? formatted || formatDate(value) : '-';
                    $cell.find('.editable-text').text(displayDate).show();
                } else if (field === 'harga') {
                    // For price fields
                    $cell.data('value', value);
                    $cell.html(formatted || formatRupiah(value) + '<span class="save-indicator"><i class="bi bi-check-circle"></i></span>');
                } else {
                    // For text fields
                    $cell.html(value + '<span class="save-indicator"><i class="bi bi-check-circle"></i></span>');
                }
            }

            // Helper function to format date
            function formatDate(dateString) {
                if (!dateString) return '-';
                var date = new Date(dateString);
                return date.toLocaleDateString('id-ID', {day: '2-digit', month: '2-digit', year: 'numeric'}).replace(/\//g, '-');
            }

            // Helper function to format currency
            function formatRupiah(angka) {
                return 'Rp ' + parseFloat(angka).toLocaleString('id-ID');
            }
        });
    </script>
</body>

</html>