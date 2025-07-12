<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $base_url . "/login.php");
    exit();
}

$page_title = "Data Tempat Praktek";

// Handle form submission untuk menambah/edit tempat praktek
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'tambah') {
                // Generate UUID untuk ID_Tempat_Praktek
                $uuid = sprintf(
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

                $query = "INSERT INTO tempat_praktek (
                            ID_Tempat_Praktek,
                            Nama_Tempat, 
                            Alamat_Lengkap, 
                            Kota, 
                            Provinsi,
                            Kode_Pos,
                            Nomor_Telepon,
                            Jenis_Fasilitas,
                            Status_Aktif,
                            createdAt
                        ) VALUES (
                            :id,
                            :nama,
                            :alamat,
                            :kota,
                            :provinsi,
                            :kode_pos,
                            :telepon,
                            :jenis,
                            :status,
                            NOW()
                        )";

                $stmt = $conn->prepare($query);
                $stmt->execute([
                    'id' => $uuid,
                    'nama' => $_POST['nama_tempat'],
                    'alamat' => $_POST['alamat'],
                    'kota' => $_POST['kota'],
                    'provinsi' => $_POST['provinsi'],
                    'kode_pos' => $_POST['kode_pos'],
                    'telepon' => $_POST['nomor_telepon'],
                    'jenis' => $_POST['jenis_fasilitas'],
                    'status' => isset($_POST['status_aktif']) ? 1 : 0
                ]);

                $_SESSION['success'] = "Data tempat praktek berhasil ditambahkan";
            } elseif ($_POST['action'] === 'edit') {
                $query = "UPDATE tempat_praktek SET 
                            Nama_Tempat = :nama,
                            Alamat_Lengkap = :alamat,
                            Kota = :kota,
                            Provinsi = :provinsi,
                            Kode_Pos = :kode_pos,
                            Nomor_Telepon = :telepon,
                            Jenis_Fasilitas = :jenis,
                            Status_Aktif = :status,
                            updatedAt = NOW()
                        WHERE ID_Tempat_Praktek = :id";

                $stmt = $conn->prepare($query);
                $stmt->execute([
                    'id' => $_POST['id_tempat'],
                    'nama' => $_POST['nama_tempat'],
                    'alamat' => $_POST['alamat'],
                    'kota' => $_POST['kota'],
                    'provinsi' => $_POST['provinsi'],
                    'kode_pos' => $_POST['kode_pos'],
                    'telepon' => $_POST['nomor_telepon'],
                    'jenis' => $_POST['jenis_fasilitas'],
                    'status' => isset($_POST['status_aktif']) ? 1 : 0
                ]);

                $_SESSION['success'] = "Data tempat praktek berhasil diperbarui";
            } elseif ($_POST['action'] === 'hapus') {
                // Cek apakah tempat praktek digunakan di jadwal praktek
                $check_query = "SELECT COUNT(*) FROM jadwal_praktek WHERE ID_Tempat_Praktek = :id";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->execute(['id' => $_POST['id_tempat']]);

                if ($check_stmt->fetchColumn() > 0) {
                    $_SESSION['error'] = "Tempat praktek tidak dapat dihapus karena masih digunakan dalam jadwal praktek";
                } else {
                    $query = "DELETE FROM tempat_praktek WHERE ID_Tempat_Praktek = :id";
                    $stmt = $conn->prepare($query);
                    $stmt->execute(['id' => $_POST['id_tempat']]);

                    $_SESSION['success'] = "Data tempat praktek berhasil dihapus";
                }
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }

    header("Location: tempat_praktek.php");
    exit();
}

// Ambil data tempat praktek
try {
    $query = "SELECT * FROM tempat_praktek ORDER BY Nama_Tempat";
    $stmt = $conn->query($query);
    $tempat_praktek = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    $tempat_praktek = [];
}

ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Data Tempat Praktek</h4>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahTempat">
            <i class="bi bi-plus-circle"></i> Tambah Tempat Praktek
        </button>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th>Nama Tempat</th>
                            <th>Alamat</th>
                            <th>Kota</th>
                            <th>Telepon</th>
                            <th>Jenis Fasilitas</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($tempat_praktek) > 0): ?>
                            <?php foreach ($tempat_praktek as $index => $tp): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($tp['Nama_Tempat']); ?></td>
                                    <td><?php echo htmlspecialchars($tp['Alamat_Lengkap']); ?></td>
                                    <td><?php echo htmlspecialchars($tp['Kota']); ?></td>
                                    <td><?php echo htmlspecialchars($tp['Nomor_Telepon']); ?></td>
                                    <td><?php echo htmlspecialchars($tp['Jenis_Fasilitas']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $tp['Status_Aktif'] ? 'success' : 'danger'; ?>">
                                            <?php echo $tp['Status_Aktif'] ? 'Aktif' : 'Tidak Aktif'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning edit-tempat"
                                            data-tempat='<?php echo htmlspecialchars(json_encode($tp)); ?>'>
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger hapus-tempat"
                                            data-id="<?php echo $tp['ID_Tempat_Praktek']; ?>"
                                            data-nama="<?php echo htmlspecialchars($tp['Nama_Tempat']); ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">Tidak ada data tempat praktek</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Tempat Praktek -->
<div class="modal fade" id="modalTambahTempat" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Tempat Praktek</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="tambah">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Tempat</label>
                        <input type="text" name="nama_tempat" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat Lengkap</label>
                        <textarea name="alamat" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label class="form-label">Kota</label>
                            <input type="text" name="kota" class="form-control" required>
                        </div>
                        <div class="col">
                            <label class="form-label">Provinsi</label>
                            <input type="text" name="provinsi" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kode Pos</label>
                        <input type="text" name="kode_pos" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nomor Telepon</label>
                        <input type="text" name="nomor_telepon" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jenis Fasilitas</label>
                        <select name="jenis_fasilitas" class="form-select" required>
                            <option value="">Pilih Jenis Fasilitas</option>
                            <option value="Rumah Sakit">Rumah Sakit</option>
                            <option value="Klinik">Klinik</option>
                            <option value="Puskesmas">Puskesmas</option>
                            <option value="Praktek Pribadi">Praktek Pribadi</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="status_aktif" class="form-check-input" id="statusAktif" checked>
                        <label class="form-check-label" for="statusAktif">Status Aktif</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Tempat Praktek -->
<div class="modal fade" id="modalEditTempat" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Tempat Praktek</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id_tempat" id="edit_id_tempat">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Tempat</label>
                        <input type="text" name="nama_tempat" id="edit_nama_tempat" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat Lengkap</label>
                        <textarea name="alamat" id="edit_alamat" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label class="form-label">Kota</label>
                            <input type="text" name="kota" id="edit_kota" class="form-control" required>
                        </div>
                        <div class="col">
                            <label class="form-label">Provinsi</label>
                            <input type="text" name="provinsi" id="edit_provinsi" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kode Pos</label>
                        <input type="text" name="kode_pos" id="edit_kode_pos" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nomor Telepon</label>
                        <input type="text" name="nomor_telepon" id="edit_nomor_telepon" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jenis Fasilitas</label>
                        <select name="jenis_fasilitas" id="edit_jenis_fasilitas" class="form-select" required>
                            <option value="">Pilih Jenis Fasilitas</option>
                            <option value="Rumah Sakit">Rumah Sakit</option>
                            <option value="Klinik">Klinik</option>
                            <option value="Puskesmas">Puskesmas</option>
                            <option value="Praktek Pribadi">Praktek Pribadi</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="status_aktif" class="form-check-input" id="edit_status_aktif">
                        <label class="form-check-label" for="edit_status_aktif">Status Aktif</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Hapus Tempat Praktek -->
<div class="modal fade" id="modalHapusTempat" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="hapus">
                <input type="hidden" name="id_tempat" id="hapus_id_tempat">
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus tempat praktek <strong id="hapus_nama_tempat"></strong>?</p>
                    <p class="text-danger">Perhatian: Data yang sudah dihapus tidak dapat dikembalikan!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </div>
            </form>
        </div>
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
    .badge {
        font-weight: 500;
    }
";

// Additional JavaScript
$additional_js = "
document.addEventListener('DOMContentLoaded', function() {
    // Edit tempat praktek
    document.querySelectorAll('.edit-tempat').forEach(function(button) {
        button.addEventListener('click', function() {
            var tempat = JSON.parse(this.getAttribute('data-tempat'));
            document.getElementById('edit_id_tempat').value = tempat.ID_Tempat_Praktek;
            document.getElementById('edit_nama_tempat').value = tempat.Nama_Tempat;
            document.getElementById('edit_alamat').value = tempat.Alamat_Lengkap;
            document.getElementById('edit_kota').value = tempat.Kota;
            document.getElementById('edit_provinsi').value = tempat.Provinsi;
            document.getElementById('edit_kode_pos').value = tempat.Kode_Pos;
            document.getElementById('edit_nomor_telepon').value = tempat.Nomor_Telepon;
            document.getElementById('edit_jenis_fasilitas').value = tempat.Jenis_Fasilitas;
            document.getElementById('edit_status_aktif').checked = tempat.Status_Aktif == 1;
            
            new bootstrap.Modal(document.getElementById('modalEditTempat')).show();
        });
    });
    
    // Hapus tempat praktek
    document.querySelectorAll('.hapus-tempat').forEach(function(button) {
        button.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var nama = this.getAttribute('data-nama');
            
            document.getElementById('hapus_id_tempat').value = id;
            document.getElementById('hapus_nama_tempat').textContent = nama;
            
            new bootstrap.Modal(document.getElementById('modalHapusTempat')).show();
        });
    });
});
";

include_once __DIR__ . '/../../../template/layout.php';
?>