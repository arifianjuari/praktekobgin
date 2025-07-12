<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Pastikan $data berisi informasi pasien
if (!isset($data) || !isset($data['pasien'])) {
    $_SESSION['error'] = "Data pasien tidak tersedia";
    header('Location: index.php?module=rekam_medis');
    exit;
}

$page_title = "Form Tambah Riwayat Kehamilan";
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?= $page_title ?></h3>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?= $_SESSION['error_message'] ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?= $_SESSION['success_message'] ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>

                    <!-- Form Riwayat Kehamilan -->
                    <form action="index.php?module=rekam_medis&action=simpan_riwayat_kehamilan" method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="no_rkm_medis" value="<?= htmlspecialchars($data['no_rkm_medis']) ?>">
                        <!-- Tambahkan parameter source untuk redirect -->
                        <input type="hidden" name="source" value="<?= isset($_GET['source']) ? $_GET['source'] : '' ?>">
                        <!-- Tambahkan parameter no_rawat jika ada -->
                        <input type="hidden" name="no_rawat" value="<?= isset($_GET['no_rawat']) ? $_GET['no_rawat'] : '' ?>">

                        <!-- Informasi Pasien -->
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">No. Rekam Medis:</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control-plaintext" value="<?= htmlspecialchars($data['no_rkm_medis']) ?>" readonly>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Nama Pasien:</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control-plaintext" value="<?= htmlspecialchars($data['pasien']['nm_pasien']) ?>" readonly>
                            </div>
                        </div>

                        <!-- Form Input Riwayat Kehamilan -->
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label required-field">No. Urut Kehamilan</label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" name="no_urut_kehamilan" required min="1">
                                <div class="invalid-feedback">Silakan masukkan nomor urut kehamilan</div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label required-field">Status Kehamilan</label>
                            <div class="col-sm-9">
                                <select class="form-control" name="status_kehamilan" required>
                                    <option value="">Pilih Status Kehamilan</option>
                                    <option value="Sedang Hamil">Sedang Hamil</option>
                                    <option value="Abortus">Abortus</option>
                                    <option value="Lahir Hidup">Lahir Hidup</option>
                                    <option value="Lahir Mati">Lahir Mati</option>
                                    <option value="Ektopik">Ektopik</option>
                                </select>
                                <div class="invalid-feedback">Silakan pilih status kehamilan</div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Jenis Persalinan</label>
                            <div class="col-sm-9">
                                <select class="form-control" name="jenis_persalinan">
                                    <option value="">Pilih Jenis Persalinan</option>
                                    <option value="Spontan">Spontan</option>
                                    <option value="Forceps">Forceps</option>
                                    <option value="Vakum">Vakum</option>
                                    <option value="Sectio Caesaria">Sectio Caesaria</option>
                                    <option value="Tidak Relevan">Tidak Relevan</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Tempat Persalinan</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" name="tempat_persalinan">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Penolong Persalinan</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" name="penolong_persalinan">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Tahun Persalinan</label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" name="tahun_persalinan" min="1900" max="<?= date('Y') ?>">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Jenis Kelamin Anak</label>
                            <div class="col-sm-9">
                                <select class="form-control" name="jenis_kelamin_anak">
                                    <option value="">Pilih Jenis Kelamin</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Berat Badan Lahir (gram)</label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" name="berat_badan_lahir" min="0" step="1">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Kondisi Lahir</label>
                            <div class="col-sm-9">
                                <select class="form-control" name="kondisi_lahir">
                                    <option value="">Pilih Kondisi</option>
                                    <option value="Hidup">Hidup</option>
                                    <option value="Meninggal">Meninggal</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Komplikasi Kehamilan</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" name="komplikasi_kehamilan" rows="2"></textarea>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Komplikasi Persalinan</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" name="komplikasi_persalinan" rows="2"></textarea>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Catatan</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" name="catatan" rows="2"></textarea>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-sm-9 offset-sm-3">
                                <button type="submit" class="btn btn-primary">Simpan</button>
                                <a href="index.php?module=rekam_medis&action=detail_pasien&no_rkm_medis=<?= htmlspecialchars($data['no_rkm_medis']) ?>" class="btn btn-secondary">Kembali</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Form validation
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            var forms = document.getElementsByClassName('needs-validation');
            var validation = Array.prototype.filter.call(forms, function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();

    // Dynamically show/hide fields based on status kehamilan
    document.querySelector('select[name="status_kehamilan"]').addEventListener('change', function() {
        const abortusFields = document.querySelectorAll(
            'select[name="jenis_persalinan"],' +
            'input[name="tempat_persalinan"],' +
            'input[name="penolong_persalinan"],' +
            'input[name="berat_badan_lahir"],' +
            'select[name="jenis_kelamin_anak"],' +
            'select[name="kondisi_lahir"]'
        ).forEach(field => {
            field.closest('.form-group').style.display = this.value === 'Abortus' ? 'none' : 'flex';
            if (this.value === 'Abortus') {
                field.value = '';
            }
        });
    });
</script>