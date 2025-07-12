<?php
session_start();
require_once 'config/database.php';
require_once 'config/config.php';

$page_title = "Form Pendaftaran Pasien";
// Start output buffering
ob_start();

// Tampilkan pesan sukses jika ada
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            ' . htmlspecialchars($_SESSION['success_message']) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
    unset($_SESSION['success_message']);
}

// Ambil parameter dari URL jika ada
$id_tempat_praktek = isset($_GET['tempat']) ? $_GET['tempat'] : '';
$id_dokter = isset($_GET['dokter']) ? $_GET['dokter'] : '';
$id_jadwal = isset($_GET['jadwal']) ? $_GET['jadwal'] : '';

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

// Konversi koneksi PDO ke MySQLi untuk widget pengumuman
$pdo_conn = $conn; // Simpan koneksi PDO
$conn_mysqli = new mysqli($db2_host, $db2_username, $db2_password, $db2_database);
if ($conn_mysqli->connect_error) {
    error_log("MySQLi Connection Error: " . $conn_mysqli->connect_error);
}

// Proses form jika disubmit
$errors = [];
$success = false;
$id_pendaftaran = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi input
    $nama_pasien = trim($_POST['nama_pasien'] ?? '');
    $tanggal_lahir = trim($_POST['tanggal_lahir'] ?? '');
    $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');
    $nomor_telepon = trim($_POST['nomor_telepon'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $keluhan = trim($_POST['keluhan'] ?? '');
    $id_tempat_praktek = trim($_POST['id_tempat_praktek'] ?? '');
    $id_dokter = trim($_POST['id_dokter'] ?? '');
    $id_jadwal = trim($_POST['id_jadwal'] ?? '');

    // Validasi data
    if (empty($nama_pasien)) {
        $errors[] = "Nama pasien harus diisi";
    }
    if (empty($tanggal_lahir)) {
        $errors[] = "Tanggal lahir harus diisi";
    }
    if (empty($jenis_kelamin)) {
        $errors[] = "Jenis kelamin harus dipilih";
    }
    if (empty($nomor_telepon)) {
        $errors[] = "Nomor telepon harus diisi";
    }
    if (empty($id_tempat_praktek)) {
        $errors[] = "Tempat praktek harus dipilih";
    }
    if (empty($id_dokter)) {
        $errors[] = "Dokter harus dipilih";
    }
    if (empty($id_jadwal)) {
        $errors[] = "Jadwal harus dipilih";
    }

    // Jika tidak ada error, simpan data
    if (empty($errors)) {
        try {
            // Buat ID pendaftaran
            $tanggal_format = date('Ymd');

            // Cek nomor urut terakhir untuk tanggal ini
            $query = "SELECT MAX(SUBSTRING(ID_Pendaftaran, 13)) as last_number 
                      FROM pendaftaran 
                      WHERE ID_Pendaftaran LIKE :prefix";
            $stmt = $conn->prepare($query);
            $prefix = "REG-" . $tanggal_format . "-%";
            $stmt->bindParam(':prefix', $prefix);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $last_number = $result['last_number'] ? intval($result['last_number']) : 0;
            $new_number = $last_number + 1;
            $id_pendaftaran = "REG-" . $tanggal_format . "-" . str_pad($new_number, 4, "0", STR_PAD_LEFT);

            // Simpan data pendaftaran
            $query = "INSERT INTO pendaftaran (
                        ID_Pendaftaran, 
                        nm_pasien, 
                        tgl_lahir, 
                        Jenis_Kelamin, 
                        no_tlp, 
                        alamat, 
                        Keluhan, 
                        ID_Tempat_Praktek, 
                        ID_Dokter, 
                        ID_Jadwal, 
                        Status_Pendaftaran, 
                        Waktu_Pendaftaran
                    ) VALUES (
                        :id_pendaftaran,
                        :nama_pasien,
                        :tanggal_lahir,
                        :jenis_kelamin,
                        :nomor_telepon,
                        :alamat,
                        :keluhan,
                        :id_tempat_praktek,
                        :id_dokter,
                        :id_jadwal,
                        'Menunggu Konfirmasi',
                        :waktu_pendaftaran
                    )";

            // Buat timestamp dengan zona waktu Asia/Jakarta
            $waktu_pendaftaran = date('Y-m-d H:i:s');

            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id_pendaftaran', $id_pendaftaran);
            $stmt->bindParam(':nama_pasien', $nama_pasien);
            $stmt->bindParam(':tanggal_lahir', $tanggal_lahir);
            $stmt->bindParam(':jenis_kelamin', $jenis_kelamin);
            $stmt->bindParam(':nomor_telepon', $nomor_telepon);
            $stmt->bindParam(':alamat', $alamat);
            $stmt->bindParam(':keluhan', $keluhan);
            $stmt->bindParam(':id_tempat_praktek', $id_tempat_praktek);
            $stmt->bindParam(':id_dokter', $id_dokter);
            $stmt->bindParam(':id_jadwal', $id_jadwal);
            $stmt->bindParam(':waktu_pendaftaran', $waktu_pendaftaran);
            $stmt->execute();

            // Set pesan sukses
            $success = true;
            $_SESSION['success_message'] = "Pendaftaran berhasil dilakukan dengan ID: " . $id_pendaftaran;

            // Redirect ke halaman sukses
            header("Location: pendaftaran/pendaftaran_sukses.php?id=" . $id_pendaftaran);
            exit;
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            $errors[] = "Terjadi kesalahan saat menyimpan data. Silakan coba lagi.";
        }
    }
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <!-- Konten utama -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Form Pendaftaran Pasien</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" id="formPendaftaran" class="needs-validation" novalidate>
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h5 class="border-bottom pb-2">Data Pasien</h5>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nama_pasien" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nama_pasien" name="nama_pasien" required>
                                    <div class="invalid-feedback">Nama lengkap harus diisi</div>
                                </div>
                                <div class="mb-3">
                                    <label for="tanggal_lahir" class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" required>
                                    <div class="invalid-feedback">Tanggal lahir harus diisi</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="jenis_kelamin" id="gender_male" value="Laki-laki" required>
                                            <label class="form-check-label" for="gender_male">Laki-laki</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="jenis_kelamin" id="gender_female" value="Perempuan" required>
                                            <label class="form-check-label" for="gender_female">Perempuan</label>
                                        </div>
                                        <div class="invalid-feedback">Jenis kelamin harus dipilih</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nomor_telepon" class="form-label">Nomor Telepon <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="nomor_telepon" name="nomor_telepon" required>
                                    <div class="invalid-feedback">Nomor telepon harus diisi</div>
                                </div>
                                <div class="mb-3">
                                    <label for="alamat" class="form-label">Alamat</label>
                                    <textarea class="form-control" id="alamat" name="alamat" rows="3"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h5 class="border-bottom pb-2">Informasi Kunjungan</h5>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="id_tempat_praktek" class="form-label">Tempat Praktek <span class="text-danger">*</span></label>
                                    <select class="form-select" id="id_tempat_praktek" name="id_tempat_praktek" required>
                                        <option value="">Pilih Tempat Praktek</option>
                                        <?php foreach ($tempat_praktek as $tp): ?>
                                            <option value="<?php echo htmlspecialchars($tp['ID_Tempat_Praktek']); ?>" <?php echo $id_tempat_praktek == $tp['ID_Tempat_Praktek'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($tp['Nama_Tempat']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">Tempat praktek harus dipilih</div>
                                </div>
                                <div class="mb-3">
                                    <label for="id_dokter" class="form-label">Dokter <span class="text-danger">*</span></label>
                                    <select class="form-select" id="id_dokter" name="id_dokter" required>
                                        <option value="">Pilih Dokter</option>
                                        <?php foreach ($dokter as $d): ?>
                                            <option value="<?php echo htmlspecialchars($d['ID_Dokter']); ?>" <?php echo $id_dokter == $d['ID_Dokter'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($d['Nama_Dokter']); ?> (<?php echo htmlspecialchars($d['Spesialisasi']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">Dokter harus dipilih</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="id_jadwal" class="form-label">Jadwal <span class="text-danger">*</span></label>
                                    <select class="form-select" id="id_jadwal" name="id_jadwal" required>
                                        <option value="">Pilih Tempat dan Dokter terlebih dahulu</option>
                                    </select>
                                    <div class="invalid-feedback">Jadwal harus dipilih</div>
                                </div>
                                <div class="mb-3">
                                    <label for="keluhan" class="form-label">Keluhan</label>
                                    <textarea class="form-control" id="keluhan" name="keluhan" rows="3"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Daftar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <!-- Sidebar dengan widget -->
            <?php
            // Base URL untuk widget
            if (!isset($base_url)) {
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                $host = $_SERVER['HTTP_HOST'];
                $base_url = $protocol . $host;
            }

            // Include widget pengumuman
            include_once 'widgets/pengumuman_widget.php';
            ?>

            <!-- Widget Jadwal Praktek -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-calendar-check"></i> Jadwal Praktek</h5>
                </div>
                <div class="card-body">
                    <p>Lihat jadwal praktek dokter terbaru.</p>
                    <a href="<?php echo $base_url ?>/pendaftaran/jadwal.php" class="btn btn-outline-success w-100">
                        <i class="bi bi-calendar2-week"></i> Lihat Jadwal
                    </a>
                </div>
            </div>

            <!-- Widget Bantuan -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-question-circle"></i> Bantuan</h5>
                </div>
                <div class="card-body">
                    <p>Butuh bantuan untuk pendaftaran?</p>
                    <p>Hubungi kami di:</p>
                    <ul class="list-unstyled">
                        <li><i class="bi bi-telephone"></i> (021) 123-4567</li>
                        <li><i class="bi bi-envelope"></i> info@klinik.com</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Form validation
    (function() {
        'use strict';
        var forms = document.querySelectorAll('.needs-validation');
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();

    // Dynamic jadwal loading
    document.getElementById('id_tempat_praktek').addEventListener('change', loadJadwal);
    document.getElementById('id_dokter').addEventListener('change', loadJadwal);

    function loadJadwal() {
        const tempatPraktek = document.getElementById('id_tempat_praktek').value;
        const dokter = document.getElementById('id_dokter').value;
        const jadwalSelect = document.getElementById('id_jadwal');

        // Reset jadwal dropdown
        jadwalSelect.innerHTML = '<option value="">Pilih Tempat dan Dokter terlebih dahulu</option>';
        jadwalSelect.disabled = true;

        if (tempatPraktek && dokter) {
            fetch(`pendaftaran/get_jadwal.php?tempat=${tempatPraktek}&dokter=${dokter}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        data.forEach(jadwal => {
                            const option = document.createElement('option');
                            option.value = jadwal.ID_Jadwal_Rutin;
                            option.textContent = `${jadwal.Hari} - ${jadwal.Jam_Mulai} s/d ${jadwal.Jam_Selesai}`;
                            jadwalSelect.appendChild(option);
                        });
                        jadwalSelect.disabled = false;
                    } else {
                        jadwalSelect.innerHTML = '<option value="">Tidak ada jadwal tersedia</option>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    jadwalSelect.innerHTML = '<option value="">Error loading jadwal</option>';
                });
        }
    }
</script>

<?php
$content = ob_get_clean();

// Additional CSS
$additional_css = "
    .card {
        border: none;
        border-radius: 10px;
    }
    .card-title {
        color: #2c3e50;
    }
    .alert-info {
        background-color: #e1f5fe;
        border-color: #b3e5fc;
        color: #0288d1;
    }
";

// Additional JavaScript if needed
$additional_js = "";

include 'template/layout.php';
?>