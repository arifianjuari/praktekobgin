<?php
session_start();
require_once '../../../config/database.php';

// Periksa apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$page_title = "Manajemen Voucher";

// Proses form tambah/edit voucher
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'add') {
                // Validasi input
                $voucher_code = trim($_POST['voucher_code']);
                $nama_voucher = trim($_POST['nama_voucher']);
                $deskripsi = trim($_POST['deskripsi']);
                $tipe_voucher = $_POST['tipe_voucher'];
                $nilai_voucher = floatval($_POST['nilai_voucher']);
                $valid_awal = $_POST['valid_awal'];
                $valid_akhir = $_POST['valid_akhir'];
                $status = 'aktif';

                // Cek apakah kode voucher sudah ada
                $stmt = $conn->prepare("SELECT COUNT(*) FROM voucher WHERE voucher_code = ?");
                $stmt->execute([$voucher_code]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception("Kode voucher sudah digunakan");
                }

                // Ambil kuota dari input
                $kuota = isset($_POST['kuota']) ? intval($_POST['kuota']) : 1;
                $terpakai = 0;

                // Insert voucher baru
                $stmt = $conn->prepare("INSERT INTO voucher (
                    voucher_code, nama_voucher, deskripsi, tipe_voucher, 
                    nilai_voucher, valid_awal, valid_akhir, status, kuota, terpakai
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $stmt->execute([
                    $voucher_code,
                    $nama_voucher,
                    $deskripsi,
                    $tipe_voucher,
                    $nilai_voucher,
                    $valid_awal,
                    $valid_akhir,
                    $status,
                    $kuota,
                    $terpakai
                ]);

                $_SESSION['success'] = "Voucher berhasil ditambahkan";
            } elseif ($_POST['action'] === 'edit') {
                $voucher_id = $_POST['voucher_id'];
                $nama_voucher = trim($_POST['nama_voucher']);
                $deskripsi = trim($_POST['deskripsi']);
                $tipe_voucher = $_POST['tipe_voucher'];
                $nilai_voucher = floatval($_POST['nilai_voucher']);
                $valid_awal = $_POST['valid_awal'];
                $valid_akhir = $_POST['valid_akhir'];
                $status = $_POST['status'];

                // Ambil kuota dari input
                $kuota = isset($_POST['kuota']) ? intval($_POST['kuota']) : 1;
                
                // Ambil jumlah terpakai saat ini dari database
                $stmt = $conn->prepare("SELECT terpakai FROM voucher WHERE voucher_id = ?");
                $stmt->execute([$voucher_id]);
                $row = $stmt->fetch();
                $terpakai = $row ? intval($row['terpakai']) : 0;
                
                // Jika terpakai >= kuota, set status otomatis ke 'terpakai'
                if ($terpakai >= $kuota) {
                    $status = 'terpakai';
                }
                
                // Log untuk debugging
                error_log("Updating voucher ID: $voucher_id");
                error_log("Kuota: $kuota, Terpakai: $terpakai, Status: $status");

                // Update voucher
                $stmt = $conn->prepare("UPDATE voucher SET 
                    nama_voucher = ?,
                    deskripsi = ?,
                    tipe_voucher = ?,
                    nilai_voucher = ?,
                    valid_awal = ?,
                    valid_akhir = ?,
                    status = ?,
                    kuota = ?,
                    terpakai = ?,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE voucher_id = ?");

                $stmt->execute([
                    $nama_voucher,
                    $deskripsi,
                    $tipe_voucher,
                    $nilai_voucher,
                    $valid_awal,
                    $valid_akhir,
                    $status,
                    $kuota,
                    $terpakai,
                    $voucher_id
                ]);

                $_SESSION['success'] = "Voucher berhasil diperbarui";
            } elseif ($_POST['action'] === 'delete') {
                $voucher_id = $_POST['voucher_id'];

                // Hapus voucher
                $stmt = $conn->prepare("DELETE FROM voucher WHERE voucher_id = ?");
                $stmt->execute([$voucher_id]);

                $_SESSION['success'] = "Voucher berhasil dihapus";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }

        // Redirect untuk menghindari resubmission
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Ambil data voucher untuk ditampilkan
try {
    $stmt = $conn->query("SELECT * FROM voucher ORDER BY created_at DESC");
    $vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error: Gagal mengambil data voucher";
    $vouchers = [];
}

// Start output buffering
ob_start();
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Daftar Voucher</h5>
                    <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addVoucherModal">
                        <i class="bi bi-plus"></i> Tambah Voucher
                    </button>
                </div>
                <div class="card-body">
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

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="voucherTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Kode Voucher</th>
                                    <th>Nama</th>
                                    <th>Tipe</th>
                                    <th>Nilai</th>
                                    <th>Kuota</th>
                                    <th>Terpakai</th>
                                    <th>Berlaku Dari</th>
                                    <th>Berlaku Sampai</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vouchers as $voucher): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($voucher['voucher_code']) ?></td>
                                        <td><?= htmlspecialchars($voucher['nama_voucher']) ?></td>
                                        <td><?= htmlspecialchars($voucher['tipe_voucher']) ?></td>
                                        <td>
                                            <?php
                                            if ($voucher['tipe_voucher'] === 'persentase') {
                                                echo number_format($voucher['nilai_voucher'], 0) . '%';
                                            } elseif ($voucher['tipe_voucher'] === 'nominal') {
                                                echo 'Rp ' . number_format($voucher['nilai_voucher'], 0, ',', '.');
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td><?= htmlspecialchars($voucher['kuota']) ?></td>
                                        <td><?= htmlspecialchars($voucher['terpakai']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($voucher['valid_awal'])) ?></td>
                                        <td><?= date('d/m/Y', strtotime($voucher['valid_akhir'])) ?></td>
                                        <td>
                                            <span class="badge <?= getBadgeClass($voucher['status']) ?>">
                                                <?= htmlspecialchars($voucher['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info"
                                                onclick="editVoucher(<?= htmlspecialchars(json_encode($voucher)) ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger"
                                                onclick="deleteVoucher(<?= $voucher['voucher_id'] ?>, '<?= htmlspecialchars($voucher['voucher_code']) ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
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

<!-- Modal Tambah Voucher -->
<div class="modal fade" id="addVoucherModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Voucher Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">

                    <div class="mb-3">
                        <label for="voucher_code" class="form-label">Kode Voucher</label>
                        <input type="text" class="form-control" id="voucher_code" name="voucher_code" required>
                    </div>

                    <div class="mb-3">
                        <label for="nama_voucher" class="form-label">Nama Voucher</label>
                        <input type="text" class="form-control" id="nama_voucher" name="nama_voucher" required>
                    </div>

                    <div class="mb-3">
                        <label for="deskripsi" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="tipe_voucher" class="form-label">Tipe Voucher</label>
                        <select class="form-select" id="tipe_voucher" name="tipe_voucher" required>
                            <option value="persentase">Persentase</option>
                            <option value="nominal">Nominal</option>
                            <option value="produk_gratis">Produk Gratis</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="nilai_voucher" class="form-label">Nilai Voucher</label>
                        <input type="number" class="form-control" id="nilai_voucher" name="nilai_voucher" required>
                        <div id="nilai_help" class="form-text">Untuk tipe persentase, masukkan nilai 1-100</div>
                    </div>

                    <div class="mb-3">
                        <label for="kuota" class="form-label">Kuota Voucher</label>
                        <input type="number" class="form-control" id="kuota" name="kuota" min="1" value="1" required>
                        <div id="kuota_help" class="form-text">Jumlah maksimum voucher bisa digunakan</div>
                    </div>

                    <div class="mb-3">
                        <label for="valid_awal" class="form-label">Berlaku Dari</label>
                        <input type="date" class="form-control" id="valid_awal" name="valid_awal" required>
                    </div>

                    <div class="mb-3">
                        <label for="valid_akhir" class="form-label">Berlaku Sampai</label>
                        <input type="date" class="form-control" id="valid_akhir" name="valid_akhir" required>
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

<!-- Modal Edit Voucher -->
<div class="modal fade" id="editVoucherModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Voucher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="voucher_id" id="edit_voucher_id">

                    <div class="mb-3">
                        <label class="form-label">Kode Voucher</label>
                        <input type="text" class="form-control" id="edit_voucher_code" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="edit_nama_voucher" class="form-label">Nama Voucher</label>
                        <input type="text" class="form-control" id="edit_nama_voucher" name="nama_voucher" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_deskripsi" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="edit_deskripsi" name="deskripsi" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="edit_tipe_voucher" class="form-label">Tipe Voucher</label>
                        <select class="form-select" id="edit_tipe_voucher" name="tipe_voucher" required>
                            <option value="persentase">Persentase</option>
                            <option value="nominal">Nominal</option>
                            <option value="produk_gratis">Produk Gratis</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_nilai_voucher" class="form-label">Nilai Voucher</label>
                        <input type="number" step="0.01" class="form-control" id="edit_nilai_voucher" name="nilai_voucher" required>
                        <div id="nilai_help" class="form-text">Untuk tipe persentase, masukkan nilai 1-100</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_kuota" class="form-label">Kuota Voucher</label>
                                <input type="number" class="form-control" id="edit_kuota" name="kuota" min="1" value="1" required>
                                <div id="kuota_help" class="form-text">Jumlah maksimum voucher bisa digunakan</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_terpakai" class="form-label">Terpakai</label>
                                <input type="number" class="form-control" id="edit_terpakai" name="terpakai" min="0" value="0" readonly>
                                <div class="form-text">Jumlah voucher yang sudah digunakan</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_valid_awal" class="form-label">Berlaku Dari</label>
                        <input type="date" class="form-control" id="edit_valid_awal" name="valid_awal" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_valid_akhir" class="form-label">Berlaku Sampai</label>
                        <input type="date" class="form-control" id="edit_valid_akhir" name="valid_akhir" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <select class="form-select" id="edit_status" name="status" required>
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                            <option value="kadaluarsa">Kadaluarsa</option>
                            <option value="terpakai">Terpakai</option>
                        </select>
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

<!-- Modal Hapus Voucher -->
<div class="modal fade" id="deleteVoucherModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hapus Voucher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="voucher_id" id="delete_voucher_id">
                    <p>Anda yakin ingin menghapus voucher dengan kode: <strong id="delete_voucher_code"></strong>?</p>
                    <p class="text-danger">Tindakan ini tidak dapat dibatalkan!</p>
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
function getBadgeClass($status)
{
    switch ($status) {
        case 'aktif':
            return 'bg-success';
        case 'nonaktif':
            return 'bg-secondary';
        case 'kadaluarsa':
            return 'bg-warning text-dark';
        case 'terpakai':
            return 'bg-info';
        default:
            return 'bg-secondary';
    }
}

$content = ob_get_clean();

// Additional CSS
$additional_css = "
    .table th {
        white-space: nowrap;
    }
";

// Additional JavaScript
$additional_scripts = "
<script>
$(document).ready(function() {
    // Inisialisasi DataTable
    $('#voucherTable').DataTable({
        order: [[4, 'desc']], // Urutkan berdasarkan tanggal berlaku
        language: {
            url: '../assets/js/dataTables.indonesian.json'
        }
    });

    // Validasi form
    function validateForm(form) {
        const tipeVoucher = form.find('[name=tipe_voucher]').val();
        const nilaiVoucher = parseFloat(form.find('[name=nilai_voucher]').val());
        
        if (tipeVoucher === 'persentase' && (nilaiVoucher < 0 || nilaiVoucher > 100)) {
            alert('Nilai voucher persentase harus antara 0-100');
            return false;
        }
        
        const validAwal = new Date(form.find('[name=valid_awal]').val());
        const validAkhir = new Date(form.find('[name=valid_akhir]').val());
        
        if (validAkhir < validAwal) {
            alert('Tanggal berakhir tidak boleh lebih awal dari tanggal mulai');
            return false;
        }
        
        return true;
    }

    // Tambahkan validasi ke form
    $('form').submit(function(e) {
        if (!validateForm($(this))) {
            e.preventDefault();
        }
    });
});

// Fungsi untuk mengedit voucher
function editVoucher(voucher) {
    console.log('Edit voucher data:', voucher); // Debug log
    
    // Isi form edit dengan data voucher
    $('#edit_voucher_id').val(voucher.voucher_id);
    $('#edit_voucher_code').val(voucher.voucher_code);
    $('#edit_nama_voucher').val(voucher.nama_voucher);
    $('#edit_deskripsi').val(voucher.deskripsi || '');
    $('#edit_tipe_voucher').val(voucher.tipe_voucher);
    $('#edit_nilai_voucher').val(voucher.nilai_voucher);
    
    // Set nilai kuota dan terpakai
    $('#edit_kuota').val(voucher.kuota !== undefined ? voucher.kuota : 1);
    $('#edit_terpakai').val(voucher.terpakai !== undefined ? voucher.terpakai : 0);
    
    // Format tanggal
    var validAwal = voucher.valid_awal.split(' ')[0];  // Ambil hanya tanggal tanpa waktu
    var validAkhir = voucher.valid_akhir.split(' ')[0];
    
    $('#edit_valid_awal').val(validAwal);
    $('#edit_valid_akhir').val(validAkhir);
    $('#edit_status').val(voucher.status);
    
    // Tampilkan modal
    $('#editVoucherModal').modal('show');
}

// Fungsi untuk menghapus voucher
function deleteVoucher(voucherId, voucherCode) {
    // Isi form hapus dengan data voucher
    $('#delete_voucher_id').val(voucherId);
    $('#delete_voucher_code').text(voucherCode);
    
    // Tampilkan modal konfirmasi
    $('#deleteVoucherModal').modal('show');
}
</script>
";

// Include template
include_once '../../../template/layout.php';
?>