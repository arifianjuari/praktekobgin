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

// Proses tambah data
if (isset($_POST['tambah'])) {
    $id_perujuk = generateUUID();
    $nama_perujuk = $_POST['nama_perujuk'];
    $jenis_perujuk = $_POST['jenis_perujuk'];
    $no_telepon = $_POST['no_telepon'];
    $keterangan = $_POST['keterangan'];
    $persentase_fee = !empty($_POST['persentase_fee']) ? $_POST['persentase_fee'] : null;

    try {
        $stmt = $conn->prepare("INSERT INTO rujukan (id_perujuk, nama_perujuk, jenis_perujuk, no_telepon, keterangan, persentase_fee) 
                VALUES (:id_perujuk, :nama_perujuk, :jenis_perujuk, :no_telepon, :keterangan, :persentase_fee)");

        $stmt->bindParam(':id_perujuk', $id_perujuk);
        $stmt->bindParam(':nama_perujuk', $nama_perujuk);
        $stmt->bindParam(':jenis_perujuk', $jenis_perujuk);
        $stmt->bindParam(':no_telepon', $no_telepon);
        $stmt->bindParam(':keterangan', $keterangan);
        $stmt->bindParam(':persentase_fee', $persentase_fee);

        $stmt->execute();
        $success_message = "Data perujuk berhasil ditambahkan";
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Proses edit data
if (isset($_POST['edit'])) {
    $id_perujuk = $_POST['id_perujuk'];
    $nama_perujuk = $_POST['nama_perujuk'];
    $jenis_perujuk = $_POST['jenis_perujuk'];
    $no_telepon = $_POST['no_telepon'];
    $keterangan = $_POST['keterangan'];
    $persentase_fee = !empty($_POST['persentase_fee']) ? $_POST['persentase_fee'] : null;

    try {
        $stmt = $conn->prepare("UPDATE rujukan SET 
                nama_perujuk = :nama_perujuk, 
                jenis_perujuk = :jenis_perujuk, 
                no_telepon = :no_telepon, 
                keterangan = :keterangan, 
                persentase_fee = :persentase_fee 
                WHERE id_perujuk = :id_perujuk");

        $stmt->bindParam(':id_perujuk', $id_perujuk);
        $stmt->bindParam(':nama_perujuk', $nama_perujuk);
        $stmt->bindParam(':jenis_perujuk', $jenis_perujuk);
        $stmt->bindParam(':no_telepon', $no_telepon);
        $stmt->bindParam(':keterangan', $keterangan);
        $stmt->bindParam(':persentase_fee', $persentase_fee);

        $stmt->execute();
        $success_message = "Data perujuk berhasil diperbarui";
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Proses hapus data
if (isset($_GET['hapus'])) {
    $id_perujuk = $_GET['hapus'];

    try {
        $stmt = $conn->prepare("DELETE FROM rujukan WHERE id_perujuk = :id_perujuk");
        $stmt->bindParam(':id_perujuk', $id_perujuk);
        $stmt->execute();
        $success_message = "Data perujuk berhasil dihapus";
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Ambil data untuk ditampilkan
try {
    $stmt = $conn->query("SELECT * FROM rujukan ORDER BY nama_perujuk ASC");
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
    <title>Data Rujukan - Sistem Antrian Pasien</title>

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
                    <h2 class="page-title">Data Rujukan</h2>
                    <p class="text-muted">Manajemen data perujuk pasien</p>
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
                            <h5 class="mb-0">Daftar Perujuk</h5>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                                <i class="bi bi-plus-circle"></i> Tambah Perujuk
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="tabelRujukan" class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama Perujuk</th>
                                            <th>Jenis</th>
                                            <th>No. Telepon</th>
                                            <th>Persentase Fee</th>
                                            <th>Keterangan</th>
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
                                                <td><?= htmlspecialchars($row['nama_perujuk']) ?></td>
                                                <td><?= htmlspecialchars($row['jenis_perujuk']) ?></td>
                                                <td><?= htmlspecialchars($row['no_telepon']) ?></td>
                                                <td><?= $row['persentase_fee'] ? htmlspecialchars($row['persentase_fee']) . '%' : '-' ?></td>
                                                <td><?= htmlspecialchars($row['keterangan']) ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-info"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modalEdit"
                                                        data-id="<?= $row['id_perujuk'] ?>"
                                                        data-nama="<?= htmlspecialchars($row['nama_perujuk']) ?>"
                                                        data-jenis="<?= htmlspecialchars($row['jenis_perujuk']) ?>"
                                                        data-telepon="<?= htmlspecialchars($row['no_telepon']) ?>"
                                                        data-keterangan="<?= htmlspecialchars($row['keterangan']) ?>"
                                                        data-fee="<?= htmlspecialchars($row['persentase_fee']) ?>">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>
                                                    <a href="#" class="btn btn-sm btn-danger btn-hapus"
                                                        data-id="<?= $row['id_perujuk'] ?>"
                                                        data-nama="<?= htmlspecialchars($row['nama_perujuk']) ?>">
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
                        <h5 class="modal-title" id="modalTambahLabel">Tambah Data Perujuk</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nama_perujuk" class="form-label">Nama Perujuk <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_perujuk" name="nama_perujuk" required>
                        </div>
                        <div class="mb-3">
                            <label for="jenis_perujuk" class="form-label">Jenis Perujuk <span class="text-danger">*</span></label>
                            <select class="form-select" id="jenis_perujuk" name="jenis_perujuk" required>
                                <option value="">Pilih Jenis Perujuk</option>
                                <option value="Bidan">Bidan</option>
                                <option value="Puskesmas">Puskesmas</option>
                                <option value="Rumah Sakit">Rumah Sakit</option>
                                <option value="Klinik">Klinik</option>
                                <option value="Dokter Spesialis">Dokter Spesialis</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="no_telepon" class="form-label">No. Telepon</label>
                            <input type="text" class="form-control" id="no_telepon" name="no_telepon">
                        </div>
                        <div class="mb-3">
                            <label for="persentase_fee" class="form-label">Persentase Fee (%)</label>
                            <input type="number" class="form-control" id="persentase_fee" name="persentase_fee" step="0.01" min="0" max="100">
                            <small class="text-muted">Kosongkan jika tidak ada fee</small>
                        </div>
                        <div class="mb-3">
                            <label for="keterangan" class="form-label">Keterangan</label>
                            <textarea class="form-control" id="keterangan" name="keterangan" rows="3"></textarea>
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
                        <h5 class="modal-title" id="modalEditLabel">Edit Data Perujuk</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="edit_id_perujuk" name="id_perujuk">
                        <div class="mb-3">
                            <label for="edit_nama_perujuk" class="form-label">Nama Perujuk <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_nama_perujuk" name="nama_perujuk" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_jenis_perujuk" class="form-label">Jenis Perujuk <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_jenis_perujuk" name="jenis_perujuk" required>
                                <option value="">Pilih Jenis Perujuk</option>
                                <option value="Bidan">Bidan</option>
                                <option value="Puskesmas">Puskesmas</option>
                                <option value="Rumah Sakit">Rumah Sakit</option>
                                <option value="Klinik">Klinik</option>
                                <option value="Dokter Spesialis">Dokter Spesialis</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_no_telepon" class="form-label">No. Telepon</label>
                            <input type="text" class="form-control" id="edit_no_telepon" name="no_telepon">
                        </div>
                        <div class="mb-3">
                            <label for="edit_persentase_fee" class="form-label">Persentase Fee (%)</label>
                            <input type="number" class="form-control" id="edit_persentase_fee" name="persentase_fee" step="0.01" min="0" max="100">
                            <small class="text-muted">Kosongkan jika tidak ada fee</small>
                        </div>
                        <div class="mb-3">
                            <label for="edit_keterangan" class="form-label">Keterangan</label>
                            <textarea class="form-control" id="edit_keterangan" name="keterangan" rows="3"></textarea>
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

    <script>
        $(document).ready(function() {
            // Inisialisasi DataTables
            $('#tabelRujukan').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
                }
            });

            // Mengisi data ke modal edit
            $('#modalEdit').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var id = button.data('id');
                var nama = button.data('nama');
                var jenis = button.data('jenis');
                var telepon = button.data('telepon');
                var keterangan = button.data('keterangan');
                var fee = button.data('fee');

                var modal = $(this);
                modal.find('#edit_id_perujuk').val(id);
                modal.find('#edit_nama_perujuk').val(nama);
                modal.find('#edit_jenis_perujuk').val(jenis);
                modal.find('#edit_no_telepon').val(telepon);
                modal.find('#edit_keterangan').val(keterangan);
                modal.find('#edit_persentase_fee').val(fee);
            });

            // Konfirmasi hapus dengan SweetAlert2
            $('.btn-hapus').on('click', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                var nama = $(this).data('nama');

                Swal.fire({
                    title: 'Konfirmasi Hapus',
                    text: `Anda yakin ingin menghapus perujuk "${nama}"?`,
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