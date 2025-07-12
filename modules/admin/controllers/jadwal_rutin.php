<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $base_url . "/login.php");
    exit();
}

$page_title = "Jadwal Praktek Rutin";

// Mulai output buffering untuk konten utama
ob_start();

// Fungsi untuk mendapatkan daftar tempat praktek
function getDaftarTempatPraktek()
{
    global $conn;
    $query = "SELECT ID_Tempat_Praktek, Nama_Tempat FROM tempat_praktek WHERE Status_Aktif = 1 ORDER BY Nama_Tempat";
    $stmt = $conn->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fungsi untuk mendapatkan daftar dokter
function getDaftarDokter()
{
    global $conn;
    $query = "SELECT ID_Dokter, Nama_Dokter FROM dokter WHERE Status_Aktif = 1 ORDER BY Nama_Dokter";
    $stmt = $conn->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fungsi untuk mendapatkan daftar layanan
function getDaftarLayanan()
{
    global $conn;
    $query = "SELECT id_layanan, nama_layanan FROM menu_layanan WHERE status_aktif = 1 ORDER BY nama_layanan";
    $stmt = $conn->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle form submission untuk menambah/edit jadwal rutin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'tambah') {
                // Generate UUID untuk ID_Jadwal_Rutin
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

                $query = "INSERT INTO jadwal_rutin (
                            ID_Jadwal_Rutin,
                            ID_Tempat_Praktek, 
                            ID_Dokter, 
                            Hari, 
                            Jam_Mulai,
                            Jam_Selesai,
                            Status_Aktif,
                            Kuota_Pasien,
                            ID_Layanan,
                            createdAt
                        ) VALUES (
                            :id,
                            :tempat,
                            :dokter,
                            :hari,
                            :jam_mulai,
                            :jam_selesai,
                            :status,
                            :kuota,
                            :id_layanan,
                            NOW()
                        )";

                $stmt = $conn->prepare($query);
                $stmt->execute([
                    'id' => $uuid,
                    'tempat' => $_POST['tempat_praktek'],
                    'dokter' => $_POST['dokter'],
                    'hari' => $_POST['hari'],
                    'jam_mulai' => $_POST['jam_mulai'],
                    'jam_selesai' => $_POST['jam_selesai'],
                    'status' => isset($_POST['status_aktif']) ? 1 : 0,
                    'kuota' => $_POST['kuota_pasien'],
                    'id_layanan' => $_POST['id_layanan']
                ]);

                $_SESSION['success'] = "Jadwal rutin berhasil ditambahkan";
            } elseif ($_POST['action'] === 'edit') {
                $query = "UPDATE jadwal_rutin SET 
                            ID_Tempat_Praktek = :tempat,
                            ID_Dokter = :dokter,
                            Hari = :hari,
                            Jam_Mulai = :jam_mulai,
                            Jam_Selesai = :jam_selesai,
                            Status_Aktif = :status,
                            Kuota_Pasien = :kuota,
                            ID_Layanan = :id_layanan,
                            updatedAt = NOW()
                        WHERE ID_Jadwal_Rutin = :id";

                $stmt = $conn->prepare($query);
                $stmt->execute([
                    'id' => $_POST['id_jadwal'],
                    'tempat' => $_POST['tempat_praktek'],
                    'dokter' => $_POST['dokter'],
                    'hari' => $_POST['hari'],
                    'jam_mulai' => $_POST['jam_mulai'],
                    'jam_selesai' => $_POST['jam_selesai'],
                    'status' => isset($_POST['status_aktif']) ? 1 : 0,
                    'kuota' => $_POST['kuota_pasien'],
                    'id_layanan' => $_POST['id_layanan']
                ]);

                $_SESSION['success'] = "Jadwal rutin berhasil diperbarui";
            } elseif ($_POST['action'] === 'hapus') {
                // Hapus langsung tanpa pengecekan karena kolom ID_Jadwal_Rutin tidak ada di tabel jadwal_praktek
                $query = "DELETE FROM jadwal_rutin WHERE ID_Jadwal_Rutin = :id";
                $stmt = $conn->prepare($query);
                $stmt->execute(['id' => $_POST['id_jadwal']]);

                $_SESSION['success'] = "Jadwal rutin berhasil dihapus";
            } elseif ($_POST['action'] === 'toggle_status') {
                $query = "UPDATE jadwal_rutin SET 
                            Status_Aktif = :status,
                            updatedAt = NOW()
                        WHERE ID_Jadwal_Rutin = :id";

                $stmt = $conn->prepare($query);
                $stmt->execute([
                    'id' => $_POST['id_jadwal'],
                    'status' => $_POST['status'] == 1 ? 0 : 1
                ]);

                $_SESSION['success'] = "Status jadwal rutin berhasil diubah";
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }

    header("Location: jadwal_rutin.php");
    exit();
}

// Ambil data jadwal rutin
try {
    $query = "SELECT jr.*, tp.Nama_Tempat, d.Nama_Dokter, ml.nama_layanan 
              FROM jadwal_rutin jr
              JOIN tempat_praktek tp ON jr.ID_Tempat_Praktek = tp.ID_Tempat_Praktek
              JOIN dokter d ON jr.ID_Dokter = d.ID_Dokter
              LEFT JOIN menu_layanan ml ON jr.ID_Layanan = ml.id_layanan
              ORDER BY FIELD(jr.Hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), jr.Jam_Mulai";
    $stmt = $conn->query($query);
    $jadwal_rutin = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    $jadwal_rutin = [];
}

ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Jadwal Praktek Rutin</h4>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahJadwal">
            <i class="bi bi-plus-circle"></i> Tambah Jadwal Rutin
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
                            <th>Hari</th>
                            <th>Jam</th>
                            <th>Dokter</th>
                            <th>Tempat Praktek</th>
                            <th>Kuota</th>
                            <th>Layanan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($jadwal_rutin) > 0): ?>
                            <?php foreach ($jadwal_rutin as $index => $jr): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($jr['Hari']); ?></td>
                                    <td>
                                        <?php
                                        $jam_mulai = date("H:i", strtotime($jr['Jam_Mulai']));
                                        $jam_selesai = date("H:i", strtotime($jr['Jam_Selesai']));
                                        echo $jam_mulai . ' - ' . $jam_selesai;
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($jr['Nama_Dokter']); ?></td>
                                    <td><?php echo htmlspecialchars($jr['Nama_Tempat']); ?></td>
                                    <td><?php echo htmlspecialchars($jr['Kuota_Pasien']); ?></td>
                                    <td><?php echo htmlspecialchars($jr['nama_layanan'] ?? ''); ?></td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input toggle-status" type="checkbox"
                                                data-id="<?php echo $jr['ID_Jadwal_Rutin']; ?>"
                                                data-status="<?php echo $jr['Status_Aktif']; ?>"
                                                <?php echo $jr['Status_Aktif'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label">
                                                <span class="badge bg-<?php echo $jr['Status_Aktif'] ? 'success' : 'danger'; ?>">
                                                    <?php echo $jr['Status_Aktif'] ? 'Aktif' : 'Tidak Aktif'; ?>
                                                </span>
                                            </label>
                                        </div>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning edit-jadwal"
                                            data-jadwal='<?php echo htmlspecialchars(json_encode($jr)); ?>'>
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger hapus-jadwal"
                                            data-id="<?php echo $jr['ID_Jadwal_Rutin']; ?>"
                                            data-info="<?php echo htmlspecialchars($jr['Hari'] . ' ' . $jam_mulai . '-' . $jam_selesai . ' - ' . $jr['Nama_Dokter']); ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">Tidak ada data jadwal rutin</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Jadwal Rutin -->
<div class="modal fade" id="modalTambahJadwal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Jadwal Rutin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="tambah">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tempat Praktek</label>
                        <select name="tempat_praktek" class="form-select" required>
                            <option value="">Pilih Tempat Praktek</option>
                            <?php foreach (getDaftarTempatPraktek() as $tp): ?>
                                <option value="<?php echo $tp['ID_Tempat_Praktek']; ?>">
                                    <?php echo htmlspecialchars($tp['Nama_Tempat']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dokter</label>
                        <select name="dokter" class="form-select" required>
                            <option value="">Pilih Dokter</option>
                            <?php foreach (getDaftarDokter() as $d): ?>
                                <option value="<?php echo $d['ID_Dokter']; ?>">
                                    <?php echo htmlspecialchars($d['Nama_Dokter']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hari</label>
                        <select name="hari" class="form-select" required>
                            <option value="">Pilih Hari</option>
                            <option value="Senin">Senin</option>
                            <option value="Selasa">Selasa</option>
                            <option value="Rabu">Rabu</option>
                            <option value="Kamis">Kamis</option>
                            <option value="Jumat">Jumat</option>
                            <option value="Sabtu">Sabtu</option>
                            <option value="Minggu">Minggu</option>
                        </select>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label class="form-label">Jam Mulai</label>
                            <input type="time" name="jam_mulai" class="form-control" required>
                        </div>
                        <div class="col">
                            <label class="form-label">Jam Selesai</label>
                            <input type="time" name="jam_selesai" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kuota Pasien</label>
                        <input type="number" name="kuota_pasien" class="form-control" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Layanan</label>
                        <select name="id_layanan" class="form-select" required>
                            <option value="">Pilih Layanan</option>
                            <?php foreach (getDaftarLayanan() as $layanan): ?>
                                <option value="<?php echo htmlspecialchars($layanan['id_layanan']); ?>">
                                    <?php echo htmlspecialchars($layanan['nama_layanan']); ?>
                                </option>
                            <?php endforeach; ?>
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

<!-- Modal Edit Jadwal Rutin -->
<div class="modal fade" id="modalEditJadwal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Jadwal Rutin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id_jadwal" id="edit_id_jadwal">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tempat Praktek</label>
                        <select name="tempat_praktek" id="edit_tempat_praktek" class="form-select" required>
                            <option value="">Pilih Tempat Praktek</option>
                            <?php foreach (getDaftarTempatPraktek() as $tp): ?>
                                <option value="<?php echo $tp['ID_Tempat_Praktek']; ?>">
                                    <?php echo htmlspecialchars($tp['Nama_Tempat']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dokter</label>
                        <select name="dokter" id="edit_dokter" class="form-select" required>
                            <option value="">Pilih Dokter</option>
                            <?php foreach (getDaftarDokter() as $d): ?>
                                <option value="<?php echo $d['ID_Dokter']; ?>">
                                    <?php echo htmlspecialchars($d['Nama_Dokter']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hari</label>
                        <select name="hari" id="edit_hari" class="form-select" required>
                            <option value="">Pilih Hari</option>
                            <option value="Senin">Senin</option>
                            <option value="Selasa">Selasa</option>
                            <option value="Rabu">Rabu</option>
                            <option value="Kamis">Kamis</option>
                            <option value="Jumat">Jumat</option>
                            <option value="Sabtu">Sabtu</option>
                            <option value="Minggu">Minggu</option>
                        </select>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label class="form-label">Jam Mulai</label>
                            <input type="time" name="jam_mulai" id="edit_jam_mulai" class="form-control" required>
                        </div>
                        <div class="col">
                            <label class="form-label">Jam Selesai</label>
                            <input type="time" name="jam_selesai" id="edit_jam_selesai" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kuota Pasien</label>
                        <input type="number" name="kuota_pasien" id="edit_kuota_pasien" class="form-control" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Layanan</label>
                        <select name="id_layanan" id="edit_id_layanan" class="form-select" required>
                            <option value="">Pilih Layanan</option>
                            <?php foreach (getDaftarLayanan() as $layanan): ?>
                                <option value="<?php echo htmlspecialchars($layanan['id_layanan']); ?>">
                                    <?php echo htmlspecialchars($layanan['nama_layanan']); ?>
                                </option>
                            <?php endforeach; ?>
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

<!-- Modal Hapus Jadwal Rutin -->
<div class="modal fade" id="modalHapusJadwal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="hapus">
                <input type="hidden" name="id_jadwal" id="hapus_id_jadwal">
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus jadwal rutin <strong id="hapus_info_jadwal"></strong>?</p>
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

<!-- Form untuk toggle status -->
<form id="formToggleStatus" action="" method="POST" style="display: none;">
    <input type="hidden" name="action" value="toggle_status">
    <input type="hidden" name="id_jadwal" id="toggle_id_jadwal">
    <input type="hidden" name="status" id="toggle_status">
</form>

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
    .form-check-input {
        cursor: pointer;
    }
";

// Additional JavaScript
$additional_js = "
document.addEventListener('DOMContentLoaded', function() {
    // Edit jadwal rutin
    document.querySelectorAll('.edit-jadwal').forEach(function(button) {
        button.addEventListener('click', function() {
            var jadwal = JSON.parse(this.getAttribute('data-jadwal'));
            document.getElementById('edit_id_jadwal').value = jadwal.ID_Jadwal_Rutin;
            document.getElementById('edit_tempat_praktek').value = jadwal.ID_Tempat_Praktek;
            document.getElementById('edit_dokter').value = jadwal.ID_Dokter;
            document.getElementById('edit_hari').value = jadwal.Hari;
            document.getElementById('edit_jam_mulai').value = jadwal.Jam_Mulai.substring(0, 5);
            document.getElementById('edit_jam_selesai').value = jadwal.Jam_Selesai.substring(0, 5);
            document.getElementById('edit_kuota_pasien').value = jadwal.Kuota_Pasien;
            document.getElementById('edit_id_layanan').value = jadwal.ID_Layanan;
            document.getElementById('edit_status_aktif').checked = jadwal.Status_Aktif == 1;
            
            new bootstrap.Modal(document.getElementById('modalEditJadwal')).show();
        });
    });
    
    // Hapus jadwal rutin
    document.querySelectorAll('.hapus-jadwal').forEach(function(button) {
        button.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var info = this.getAttribute('data-info');
            
            document.getElementById('hapus_id_jadwal').value = id;
            document.getElementById('hapus_info_jadwal').textContent = info;
            
            new bootstrap.Modal(document.getElementById('modalHapusJadwal')).show();
        });
    });
    
    // Toggle status jadwal rutin
    document.querySelectorAll('.toggle-status').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            var id = this.getAttribute('data-id');
            var status = this.getAttribute('data-status');
            
            document.getElementById('toggle_id_jadwal').value = id;
            document.getElementById('toggle_status').value = status;
            
            document.getElementById('formToggleStatus').submit();
        });
    });
});
";

include_once __DIR__ . '/../../../template/layout.php';
?>