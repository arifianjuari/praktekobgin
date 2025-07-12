<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Pastikan parameter id ada
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "ID riwayat kehamilan tidak ditemukan";
    header("Location: index.php?module=rekam_medis&action=data_pasien");
    exit;
}

$id_riwayat_kehamilan = $_GET['id'];

try {
    // Dapatkan koneksi database dari global scope
    global $conn;

    if (!isset($conn) || !($conn instanceof PDO)) {
        // Jika koneksi tidak tersedia, coba load dari config
        require_once dirname(dirname(dirname(__DIR__))) . '/config/database.php';
    }

    // Test koneksi
    $test = $conn->query("SELECT 1");
    if (!$test) {
        throw new PDOException("Koneksi database tidak dapat melakukan query");
    }

    // Inisialisasi model
    require_once dirname(dirname(__DIR__)) . '/rekam_medis/models/RekamMedis.php';
    $rekamMedisModel = new RekamMedis($conn);

    // Ambil data riwayat kehamilan
    $riwayatKehamilan = $rekamMedisModel->getRiwayatKehamilanById($id_riwayat_kehamilan);

    if (!$riwayatKehamilan) {
        throw new PDOException("Data riwayat kehamilan tidak ditemukan");
    }

    $no_rkm_medis = $riwayatKehamilan['no_rkm_medis'];
} catch (PDOException $e) {
    error_log("Database Error in form_edit_riwayat_kehamilan.php: " . $e->getMessage());
    $_SESSION['error'] = "Terjadi kesalahan saat mengakses database. Silakan coba lagi nanti.";
    header("Location: index.php?module=rekam_medis&action=data_pasien");
    exit;
} catch (Exception $e) {
    error_log("Unexpected Error in form_edit_riwayat_kehamilan.php: " . $e->getMessage());
    $_SESSION['error'] = "Terjadi kesalahan yang tidak terduga. Silakan coba lagi nanti.";
    header("Location: index.php?module=rekam_medis&action=data_pasien");
    exit;
}

// Siapkan konten untuk layout
ob_start();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Edit Riwayat Kehamilan</h5>
                    <div class="card-tools">
                        <a href="index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=<?= $no_rkm_medis ?>" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?= $_SESSION['error'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <?php unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>

                    <form action="index.php?module=rekam_medis&action=update_riwayat_kehamilan" method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="id_riwayat_kehamilan" value="<?= $id_riwayat_kehamilan ?>">
                        <input type="hidden" name="no_rkm_medis" value="<?= $no_rkm_medis ?>">
                        <!-- Tambahkan parameter source untuk redirect -->
                        <input type="hidden" name="source" value="<?= isset($_GET['source']) ? $_GET['source'] : '' ?>">
                        <!-- Tambahkan parameter no_rawat jika ada -->
                        <input type="hidden" name="no_rawat" value="<?= isset($_GET['no_rawat']) ? $_GET['no_rawat'] : '' ?>">

                        <div class="mb-3">
                            <label for="no_urut_kehamilan" class="form-label required-field">No. Urut Kehamilan</label>
                            <input type="number" class="form-control" id="no_urut_kehamilan" name="no_urut_kehamilan" value="<?= $riwayatKehamilan['no_urut_kehamilan'] ?>" required>
                            <div class="invalid-feedback">
                                Silakan masukkan urutan kehamilan.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="status_kehamilan" class="form-label required-field">Status Kehamilan</label>
                            <select class="form-select" id="status_kehamilan" name="status_kehamilan" required>
                                <option value="" disabled>Pilih Status Kehamilan</option>
                                <option value="Sedang Hamil" <?= $riwayatKehamilan['status_kehamilan'] == 'Sedang Hamil' ? 'selected' : '' ?>>Sedang Hamil</option>
                                <option value="Lahir Hidup" <?= $riwayatKehamilan['status_kehamilan'] == 'Lahir Hidup' ? 'selected' : '' ?>>Lahir Hidup</option>
                                <option value="Lahir Mati" <?= $riwayatKehamilan['status_kehamilan'] == 'Lahir Mati' ? 'selected' : '' ?>>Lahir Mati</option>
                                <option value="Abortus" <?= $riwayatKehamilan['status_kehamilan'] == 'Abortus' ? 'selected' : '' ?>>Abortus</option>
                                <option value="Ektopik" <?= $riwayatKehamilan['status_kehamilan'] == 'Ektopik' ? 'selected' : '' ?>>Ektopik</option>
                            </select>
                            <div class="invalid-feedback">
                                Silakan pilih status kehamilan.
                            </div>
                        </div>

                        <!-- Fields for all status except "Sedang Hamil" -->
                        <div id="persalinanFields" style="display: <?= $riwayatKehamilan['status_kehamilan'] != 'Sedang Hamil' ? 'block' : 'none' ?>;">
                            <div class="mb-3">
                                <label for="tahun_persalinan" class="form-label">Tahun Persalinan</label>
                                <input type="number" class="form-control" id="tahun_persalinan" name="tahun_persalinan" min="1900" max="<?= date('Y') ?>" value="<?= $riwayatKehamilan['tahun_persalinan'] ?>" placeholder="Masukkan tahun persalinan">
                            </div>

                            <div class="mb-3">
                                <label for="jenis_persalinan" class="form-label">Jenis Persalinan</label>
                                <select class="form-select" id="jenis_persalinan" name="jenis_persalinan">
                                    <option value="" disabled>Pilih Jenis Persalinan</option>
                                    <option value="Spontan" <?= $riwayatKehamilan['jenis_persalinan'] == 'Spontan' ? 'selected' : '' ?>>Spontan</option>
                                    <option value="Sectio Caesaria" <?= $riwayatKehamilan['jenis_persalinan'] == 'Sectio Caesaria' ? 'selected' : '' ?>>Sectio Caesaria</option>
                                    <option value="Vakum" <?= $riwayatKehamilan['jenis_persalinan'] == 'Vakum' ? 'selected' : '' ?>>Vakum</option>
                                    <option value="Forceps" <?= $riwayatKehamilan['jenis_persalinan'] == 'Forceps' ? 'selected' : '' ?>>Forceps</option>
                                    <option value="Tidak Relevan" <?= $riwayatKehamilan['jenis_persalinan'] == 'Tidak Relevan' ? 'selected' : '' ?>>Tidak Relevan</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="tempat_persalinan" class="form-label">Tempat Persalinan</label>
                                <select class="form-select" id="tempat_persalinan" name="tempat_persalinan">
                                    <option value="" disabled>Pilih Tempat Persalinan</option>
                                    <option value="Rumah Sakit" <?= $riwayatKehamilan['tempat_persalinan'] == 'Rumah Sakit' ? 'selected' : '' ?>>Rumah Sakit</option>
                                    <option value="Puskesmas" <?= $riwayatKehamilan['tempat_persalinan'] == 'Puskesmas' ? 'selected' : '' ?>>Puskesmas</option>
                                    <option value="Klinik" <?= $riwayatKehamilan['tempat_persalinan'] == 'Klinik' ? 'selected' : '' ?>>Klinik</option>
                                    <option value="Rumah" <?= $riwayatKehamilan['tempat_persalinan'] == 'Rumah' ? 'selected' : '' ?>>Rumah</option>
                                    <option value="Tidak Relevan" <?= $riwayatKehamilan['tempat_persalinan'] == 'Tidak Relevan' ? 'selected' : '' ?>>Tidak Relevan</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="penolong_persalinan" class="form-label">Penolong Persalinan</label>
                                <select class="form-select" id="penolong_persalinan" name="penolong_persalinan">
                                    <option value="" disabled>Pilih Penolong Persalinan</option>
                                    <option value="Dokter SpOG" <?= $riwayatKehamilan['penolong_persalinan'] == 'Dokter SpOG' ? 'selected' : '' ?>>Dokter SpOG</option>
                                    <option value="Dokter Umum" <?= $riwayatKehamilan['penolong_persalinan'] == 'Dokter Umum' ? 'selected' : '' ?>>Dokter Umum</option>
                                    <option value="Bidan" <?= $riwayatKehamilan['penolong_persalinan'] == 'Bidan' ? 'selected' : '' ?>>Bidan</option>
                                    <option value="Dukun Beranak" <?= $riwayatKehamilan['penolong_persalinan'] == 'Dukun Beranak' ? 'selected' : '' ?>>Dukun Beranak</option>
                                    <option value="Tidak Relevan" <?= $riwayatKehamilan['penolong_persalinan'] == 'Tidak Relevan' ? 'selected' : '' ?>>Tidak Relevan</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="jenis_kelamin_anak" class="form-label">Jenis Kelamin Anak</label>
                                <select class="form-select" id="jenis_kelamin_anak" name="jenis_kelamin_anak">
                                    <option value="" disabled>Pilih Jenis Kelamin</option>
                                    <option value="Laki-laki" <?= $riwayatKehamilan['jenis_kelamin_anak'] == 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                                    <option value="Perempuan" <?= $riwayatKehamilan['jenis_kelamin_anak'] == 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                                    <option value="Tidak Relevan" <?= $riwayatKehamilan['jenis_kelamin_anak'] == 'Tidak Relevan' ? 'selected' : '' ?>>Tidak Relevan</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="berat_badan_lahir" class="form-label">Berat Badan Lahir (gram)</label>
                                <input type="number" class="form-control" id="berat_badan_lahir" name="berat_badan_lahir" step="1" min="0" value="<?= $riwayatKehamilan['berat_badan_lahir'] ?>" placeholder="Masukkan berat badan lahir dalam gram">
                            </div>

                            <div class="mb-3">
                                <label for="kondisi_lahir" class="form-label">Kondisi Lahir</label>
                                <select class="form-select" id="kondisi_lahir" name="kondisi_lahir">
                                    <option value="" disabled>Pilih Kondisi Lahir</option>
                                    <option value="Sehat" <?= $riwayatKehamilan['kondisi_lahir'] == 'Sehat' ? 'selected' : '' ?>>Sehat</option>
                                    <option value="Asfiksia" <?= $riwayatKehamilan['kondisi_lahir'] == 'Asfiksia' ? 'selected' : '' ?>>Asfiksia</option>
                                    <option value="BBLR" <?= $riwayatKehamilan['kondisi_lahir'] == 'BBLR' ? 'selected' : '' ?>>BBLR</option>
                                    <option value="Cacat" <?= $riwayatKehamilan['kondisi_lahir'] == 'Cacat' ? 'selected' : '' ?>>Cacat</option>
                                    <option value="Tidak Relevan" <?= $riwayatKehamilan['kondisi_lahir'] == 'Tidak Relevan' ? 'selected' : '' ?>>Tidak Relevan</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="komplikasi_kehamilan" class="form-label">Komplikasi Kehamilan</label>
                            <textarea class="form-control" id="komplikasi_kehamilan" name="komplikasi_kehamilan" rows="3"><?= $riwayatKehamilan['komplikasi_kehamilan'] ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="komplikasi_persalinan" class="form-label">Komplikasi Persalinan</label>
                            <textarea class="form-control" id="komplikasi_persalinan" name="komplikasi_persalinan" rows="3"><?= $riwayatKehamilan['komplikasi_persalinan'] ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="catatan" class="form-label">Catatan</label>
                            <textarea class="form-control" id="catatan" name="catatan" rows="3"><?= $riwayatKehamilan['catatan'] ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=<?= $no_rkm_medis ?>" class="btn btn-secondary">Kembali</a>
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Tambahkan CSS khusus
$additional_css = "
    .required-field::after {
        content: ' *';
        color: red;
    }
";

// Tambahkan JavaScript khusus
$additional_js = "
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

    // Toggle fields based on status kehamilan
    document.getElementById('status_kehamilan').addEventListener('change', function() {
        var status = this.value;
        var persalinanFields = document.getElementById('persalinanFields');
        
        if (status === 'Sedang Hamil') {
            persalinanFields.style.display = 'none';
        } else {
            persalinanFields.style.display = 'block';
        }
    });
";

// Include layout
require_once __DIR__ . '/../../../template/layout.php';
?>