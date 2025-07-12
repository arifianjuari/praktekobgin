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

// Fungsi untuk memformat harga
function formatRupiah($angka)
{
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Proses tambah data
if (isset($_POST['tambah'])) {
    $id_layanan = generateUUID();
    $nama_layanan = $_POST['nama_layanan'];
    $kategori = $_POST['kategori'];
    $harga = str_replace(['Rp ', '.'], '', $_POST['harga']);
    $deskripsi = $_POST['deskripsi'];
    $persiapan = $_POST['persiapan'];
    $durasi_estimasi = !empty($_POST['durasi_estimasi']) ? $_POST['durasi_estimasi'] : null;
    $status_aktif = isset($_POST['status_aktif']) ? 1 : 0;
    $dapat_dibooking = isset($_POST['dapat_dibooking']) ? 1 : 0;

    try {
        $stmt = $conn->prepare("INSERT INTO menu_layanan (id_layanan, nama_layanan, kategori, harga, deskripsi, persiapan, durasi_estimasi, status_aktif, dapat_dibooking) 
                VALUES (:id_layanan, :nama_layanan, :kategori, :harga, :deskripsi, :persiapan, :durasi_estimasi, :status_aktif, :dapat_dibooking)");

        $stmt->bindParam(':id_layanan', $id_layanan);
        $stmt->bindParam(':nama_layanan', $nama_layanan);
        $stmt->bindParam(':kategori', $kategori);
        $stmt->bindParam(':harga', $harga);
        $stmt->bindParam(':deskripsi', $deskripsi);
        $stmt->bindParam(':persiapan', $persiapan);
        $stmt->bindParam(':durasi_estimasi', $durasi_estimasi);
        $stmt->bindParam(':status_aktif', $status_aktif);
        $stmt->bindParam(':dapat_dibooking', $dapat_dibooking);

        $stmt->execute();
        $success_message = "Data layanan berhasil ditambahkan";
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Proses edit data
if (isset($_POST['edit'])) {
    $id_layanan = $_POST['id_layanan'];
    $nama_layanan = $_POST['nama_layanan'];
    $kategori = $_POST['kategori'];
    $harga = str_replace(['Rp ', '.'], '', $_POST['harga']);
    $deskripsi = $_POST['deskripsi'];
    $persiapan = $_POST['persiapan'];
    $durasi_estimasi = !empty($_POST['durasi_estimasi']) ? $_POST['durasi_estimasi'] : null;
    $status_aktif = isset($_POST['status_aktif']) ? 1 : 0;
    $dapat_dibooking = isset($_POST['dapat_dibooking']) ? 1 : 0;

    try {
        $stmt = $conn->prepare("UPDATE menu_layanan SET 
                nama_layanan = :nama_layanan,
                kategori = :kategori,
                harga = :harga,
                deskripsi = :deskripsi,
                persiapan = :persiapan,
                durasi_estimasi = :durasi_estimasi,
                status_aktif = :status_aktif,
                dapat_dibooking = :dapat_dibooking
                WHERE id_layanan = :id_layanan");

        $stmt->bindParam(':id_layanan', $id_layanan);
        $stmt->bindParam(':nama_layanan', $nama_layanan);
        $stmt->bindParam(':kategori', $kategori);
        $stmt->bindParam(':harga', $harga);
        $stmt->bindParam(':deskripsi', $deskripsi);
        $stmt->bindParam(':persiapan', $persiapan);
        $stmt->bindParam(':durasi_estimasi', $durasi_estimasi);
        $stmt->bindParam(':status_aktif', $status_aktif);
        $stmt->bindParam(':dapat_dibooking', $dapat_dibooking);

        $stmt->execute();
        $success_message = "Data layanan berhasil diperbarui";
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Proses hapus data
if (isset($_GET['hapus'])) {
    $id_layanan = $_GET['hapus'];

    try {
        $stmt = $conn->prepare("DELETE FROM menu_layanan WHERE id_layanan = :id_layanan");
        $stmt->bindParam(':id_layanan', $id_layanan);
        $stmt->execute();
        $success_message = "Data layanan berhasil dihapus";
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Ambil data untuk ditampilkan
try {
    $stmt = $conn->query("SELECT * FROM menu_layanan ORDER BY kategori, nama_layanan ASC");
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
    <title>Manajemen Layanan - Sistem Antrian Pasien</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">

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
    </style>
</head>

<body>
    <?php include_once __DIR__ . '/../../../template/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <div class="row mb-3">
                <div class="col-12">
                    <h2 class="page-title">Manajemen Layanan</h2>
                    <p class="text-muted">Kelola daftar layanan yang tersedia</p>
                </div>
            </div>

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
                            <h5 class="mb-0">Daftar Layanan</h5>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                                <i class="bi bi-plus-circle"></i> Tambah Layanan
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="tabelLayanan" class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama Layanan</th>
                                            <th>Kategori</th>
                                            <th>Harga</th>
                                            <th>Durasi</th>
                                            <th>Status</th>
                                            <th>Booking</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1;
                                        foreach ($result as $row):
                                        ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td><?= htmlspecialchars($row['nama_layanan']) ?></td>
                                                <td><?= htmlspecialchars($row['kategori']) ?></td>
                                                <td><?= formatRupiah($row['harga']) ?></td>
                                                <td><?= $row['durasi_estimasi'] ? $row['durasi_estimasi'] . ' menit' : '-' ?></td>
                                                <td>
                                                    <span class="badge <?= $row['status_aktif'] ? 'bg-success' : 'bg-danger' ?>">
                                                        <?= $row['status_aktif'] ? 'Aktif' : 'Nonaktif' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $row['dapat_dibooking'] ? 'bg-primary' : 'bg-secondary' ?>">
                                                        <?= $row['dapat_dibooking'] ? 'Ya' : 'Tidak' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-info"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modalEdit"
                                                        data-id="<?= $row['id_layanan'] ?>"
                                                        data-nama="<?= htmlspecialchars($row['nama_layanan']) ?>"
                                                        data-kategori="<?= htmlspecialchars($row['kategori']) ?>"
                                                        data-harga="<?= $row['harga'] ?>"
                                                        data-deskripsi="<?= htmlspecialchars($row['deskripsi']) ?>"
                                                        data-persiapan="<?= htmlspecialchars($row['persiapan']) ?>"
                                                        data-durasi="<?= $row['durasi_estimasi'] ?>"
                                                        data-status="<?= $row['status_aktif'] ?>"
                                                        data-booking="<?= $row['dapat_dibooking'] ?>">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>
                                                    <a href="#" class="btn btn-sm btn-danger btn-hapus"
                                                        data-id="<?= $row['id_layanan'] ?>"
                                                        data-nama="<?= htmlspecialchars($row['nama_layanan']) ?>">
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
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTambahLabel">Tambah Layanan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nama_layanan" class="form-label">Nama Layanan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nama_layanan" name="nama_layanan" required>
                                </div>
                                <div class="mb-3">
                                    <label for="kategori" class="form-label">Kategori <span class="text-danger">*</span></label>
                                    <select class="form-select" id="kategori" name="kategori" required>
                                        <option value="">Pilih Kategori</option>
                                        <option value="Konsultasi">Konsultasi</option>
                                        <option value="Tindakan">Tindakan</option>
                                        <option value="Pemeriksaan">Pemeriksaan</option>
                                        <option value="Paket">Paket</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="harga" class="form-label">Harga <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="harga" name="harga" required>
                                </div>
                                <div class="mb-3">
                                    <label for="durasi_estimasi" class="form-label">Durasi Estimasi (menit)</label>
                                    <input type="number" class="form-control" id="durasi_estimasi" name="durasi_estimasi" min="1">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="deskripsi" class="form-label">Deskripsi</label>
                                    <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="persiapan" class="form-label">Persiapan</label>
                                    <textarea class="form-control" id="persiapan" name="persiapan" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="status_aktif" name="status_aktif" checked>
                                        <label class="form-check-label" for="status_aktif">Status Aktif</label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="dapat_dibooking" name="dapat_dibooking">
                                        <label class="form-check-label" for="dapat_dibooking">Dapat Dibooking</label>
                                    </div>
                                </div>
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
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalEditLabel">Edit Layanan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="edit_id_layanan" name="id_layanan">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_nama_layanan" class="form-label">Nama Layanan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="edit_nama_layanan" name="nama_layanan" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_kategori" class="form-label">Kategori <span class="text-danger">*</span></label>
                                    <select class="form-select" id="edit_kategori" name="kategori" required>
                                        <option value="">Pilih Kategori</option>
                                        <option value="Konsultasi">Konsultasi</option>
                                        <option value="Tindakan">Tindakan</option>
                                        <option value="Pemeriksaan">Pemeriksaan</option>
                                        <option value="Paket">Paket</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_harga" class="form-label">Harga <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="edit_harga" name="harga" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_durasi_estimasi" class="form-label">Durasi Estimasi (menit)</label>
                                    <input type="number" class="form-control" id="edit_durasi_estimasi" name="durasi_estimasi" min="1">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_deskripsi" class="form-label">Deskripsi</label>
                                    <textarea class="form-control" id="edit_deskripsi" name="deskripsi" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_persiapan" class="form-label">Persiapan</label>
                                    <textarea class="form-control" id="edit_persiapan" name="persiapan" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="edit_status_aktif" name="status_aktif">
                                        <label class="form-check-label" for="edit_status_aktif">Status Aktif</label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="edit_dapat_dibooking" name="dapat_dibooking">
                                        <label class="form-check-label" for="edit_dapat_dibooking">Dapat Dibooking</label>
                                    </div>
                                </div>
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

    <!-- Input Mask -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.8/jquery.inputmask.min.js"></script>

    <script>
        $(document).ready(function() {
            // Inisialisasi DataTables
            $('#tabelLayanan').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
                }
            });

            // Input mask untuk harga
            $('#harga, #edit_harga').inputmask({
                alias: 'currency',
                prefix: 'Rp ',
                groupSeparator: '.',
                radixPoint: ',',
                digits: 0,
                digitsOptional: false,
                rightAlign: false,
                autoUnmask: true,
                removeMaskOnSubmit: true
            });

            // Mengisi data ke modal edit
            $('#modalEdit').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var id = button.data('id');
                var nama = button.data('nama');
                var kategori = button.data('kategori');
                var harga = button.data('harga');
                var deskripsi = button.data('deskripsi');
                var persiapan = button.data('persiapan');
                var durasi = button.data('durasi');
                var status = button.data('status');
                var booking = button.data('booking');

                var modal = $(this);
                modal.find('#edit_id_layanan').val(id);
                modal.find('#edit_nama_layanan').val(nama);
                modal.find('#edit_kategori').val(kategori);
                modal.find('#edit_harga').val(harga);
                modal.find('#edit_deskripsi').val(deskripsi);
                modal.find('#edit_persiapan').val(persiapan);
                modal.find('#edit_durasi_estimasi').val(durasi);
                modal.find('#edit_status_aktif').prop('checked', status == 1);
                modal.find('#edit_dapat_dibooking').prop('checked', booking == 1);
            });

            // Konfirmasi hapus dengan SweetAlert2
            $('.btn-hapus').on('click', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                var nama = $(this).data('nama');

                Swal.fire({
                    title: 'Konfirmasi Hapus',
                    text: `Anda yakin ingin menghapus layanan "${nama}"?`,
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
        });
    </script>
</body>

</html>