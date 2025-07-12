<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $base_url . "/login.php");
    exit();
}

$page_title = "Data Dokter";

// Handle form submission untuk menambah/edit dokter
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'tambah') {
                // Generate UUID untuk ID_Dokter
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

                $query = "INSERT INTO dokter (
                            ID_Dokter,
                            Nama_Dokter, 
                            Spesialisasi, 
                            Nomor_SIP, 
                            Nomor_Telepon,
                            Email,
                            Status_Aktif,
                            createdAt
                        ) VALUES (
                            :id,
                            :nama,
                            :spesialisasi,
                            :sip,
                            :telepon,
                            :email,
                            :status,
                            NOW()
                        )";

                $stmt = $conn->prepare($query);
                $stmt->execute([
                    'id' => $uuid,
                    'nama' => $_POST['nama_dokter'],
                    'spesialisasi' => $_POST['spesialisasi'],
                    'sip' => $_POST['nomor_sip'],
                    'telepon' => $_POST['nomor_telepon'],
                    'email' => $_POST['email'],
                    'status' => isset($_POST['status_aktif']) ? 1 : 0
                ]);

                $_SESSION['success'] = "Data dokter berhasil ditambahkan";
            } elseif ($_POST['action'] === 'edit') {
                $query = "UPDATE dokter SET 
                            Nama_Dokter = :nama,
                            Spesialisasi = :spesialisasi,
                            Nomor_SIP = :sip,
                            Nomor_Telepon = :telepon,
                            Email = :email,
                            Status_Aktif = :status,
                            updatedAt = NOW()
                        WHERE ID_Dokter = :id";

                $stmt = $conn->prepare($query);
                $stmt->execute([
                    'id' => $_POST['id_dokter'],
                    'nama' => $_POST['nama_dokter'],
                    'spesialisasi' => $_POST['spesialisasi'],
                    'sip' => $_POST['nomor_sip'],
                    'telepon' => $_POST['nomor_telepon'],
                    'email' => $_POST['email'],
                    'status' => isset($_POST['status_aktif']) ? 1 : 0
                ]);

                $_SESSION['success'] = "Data dokter berhasil diperbarui";
            } elseif ($_POST['action'] === 'hapus') {
                // Cek apakah dokter digunakan di jadwal praktek
                $check_query = "SELECT COUNT(*) FROM jadwal_praktek WHERE ID_Dokter = :id";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->execute(['id' => $_POST['id_dokter']]);

                if ($check_stmt->fetchColumn() > 0) {
                    $_SESSION['error'] = "Dokter tidak dapat dihapus karena masih digunakan dalam jadwal praktek";
                } else {
                    $query = "DELETE FROM dokter WHERE ID_Dokter = :id";
                    $stmt = $conn->prepare($query);
                    $stmt->execute(['id' => $_POST['id_dokter']]);

                    $_SESSION['success'] = "Data dokter berhasil dihapus";
                }
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }

    header("Location: data_dokter.php");
    exit();
}

// Ambil data dokter
try {
    $query = "SELECT * FROM dokter ORDER BY Nama_Dokter";
    $stmt = $conn->query($query);
    $dokter = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    $dokter = [];
}

ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Data Dokter</h4>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahDokter">
            <i class="bi bi-plus-circle"></i> Tambah Dokter
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
                            <th>Nama Dokter</th>
                            <th>Spesialisasi</th>
                            <th>Nomor SIP</th>
                            <th>Telepon</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($dokter) > 0): ?>
                            <?php foreach ($dokter as $index => $d): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($d['Nama_Dokter']); ?></td>
                                    <td><?php echo htmlspecialchars($d['Spesialisasi']); ?></td>
                                    <td><?php echo htmlspecialchars($d['Nomor_SIP']); ?></td>
                                    <td><?php echo htmlspecialchars($d['Nomor_Telepon']); ?></td>
                                    <td><?php echo htmlspecialchars($d['Email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $d['Status_Aktif'] ? 'success' : 'danger'; ?>">
                                            <?php echo $d['Status_Aktif'] ? 'Aktif' : 'Tidak Aktif'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning edit-dokter"
                                            data-dokter='<?php echo htmlspecialchars(json_encode($d)); ?>'>
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger hapus-dokter"
                                            data-id="<?php echo $d['ID_Dokter']; ?>"
                                            data-nama="<?php echo htmlspecialchars($d['Nama_Dokter']); ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">Tidak ada data dokter</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Dokter -->
<div class="modal fade" id="modalTambahDokter" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Dokter</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="tambah">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Dokter</label>
                        <input type="text" name="nama_dokter" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Spesialisasi</label>
                        <input type="text" name="spesialisasi" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nomor SIP</label>
                        <input type="text" name="nomor_sip" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nomor Telepon</label>
                        <input type="text" name="nomor_telepon" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
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

<!-- Modal Edit Dokter -->
<div class="modal fade" id="modalEditDokter" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Dokter</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id_dokter" id="edit_id_dokter">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Dokter</label>
                        <input type="text" name="nama_dokter" id="edit_nama_dokter" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Spesialisasi</label>
                        <input type="text" name="spesialisasi" id="edit_spesialisasi" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nomor SIP</label>
                        <input type="text" name="nomor_sip" id="edit_nomor_sip" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nomor Telepon</label>
                        <input type="text" name="nomor_telepon" id="edit_nomor_telepon" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control">
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

<!-- Modal Hapus Dokter -->
<div class="modal fade" id="modalHapusDokter" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="hapus">
                <input type="hidden" name="id_dokter" id="hapus_id_dokter">
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus dokter <strong id="hapus_nama_dokter"></strong>?</p>
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
    // Edit dokter
    document.querySelectorAll('.edit-dokter').forEach(function(button) {
        button.addEventListener('click', function() {
            var dokter = JSON.parse(this.getAttribute('data-dokter'));
            document.getElementById('edit_id_dokter').value = dokter.ID_Dokter;
            document.getElementById('edit_nama_dokter').value = dokter.Nama_Dokter;
            document.getElementById('edit_spesialisasi').value = dokter.Spesialisasi;
            document.getElementById('edit_nomor_sip').value = dokter.Nomor_SIP;
            document.getElementById('edit_nomor_telepon').value = dokter.Nomor_Telepon;
            document.getElementById('edit_email').value = dokter.Email;
            document.getElementById('edit_status_aktif').checked = dokter.Status_Aktif == 1;
            
            new bootstrap.Modal(document.getElementById('modalEditDokter')).show();
        });
    });
    
    // Hapus dokter
    document.querySelectorAll('.hapus-dokter').forEach(function(button) {
        button.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var nama = this.getAttribute('data-nama');
            
            document.getElementById('hapus_id_dokter').value = id;
            document.getElementById('hapus_nama_dokter').textContent = nama;
            
            new bootstrap.Modal(document.getElementById('modalHapusDokter')).show();
        });
    });
});
";

include_once __DIR__ . '/../../../template/layout.php';
?>