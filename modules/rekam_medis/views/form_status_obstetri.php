<?php
// Cek apakah ini form edit atau tambah
$isEdit = isset($statusObstetri);
$pageTitle = $isEdit ? "Edit Status Obstetri" : "Tambah Status Obstetri";

// Siapkan data untuk form
$formAction = $isEdit
    ? "index.php?module=rekam_medis&action=update_status_obstetri"
    : "index.php?module=rekam_medis&action=simpan_status_obstetri";

// Siapkan nilai default untuk form
$id_status_obstetri = $isEdit ? $statusObstetri['id_status_obstetri'] : '';
$gravida = $isEdit ? $statusObstetri['gravida'] : '';
$paritas = $isEdit ? $statusObstetri['paritas'] : '';
$abortus = $isEdit ? $statusObstetri['abortus'] : '';
$tb = $isEdit ? $statusObstetri['tb'] : '';
$tanggal_hpht = $isEdit ? $statusObstetri['tanggal_hpht'] : '';
$tanggal_tp = $isEdit ? $statusObstetri['tanggal_tp'] : '';
$tanggal_tp_penyesuaian = $isEdit ? $statusObstetri['tanggal_tp_penyesuaian'] : '';
$hasil_faktor_risiko = $isEdit ? $statusObstetri['hasil_faktor_risiko'] : '';

// Siapkan array untuk checkbox
$faktor_risiko_umum = $isEdit && !empty($statusObstetri['faktor_risiko_umum'])
    ? explode(',', $statusObstetri['faktor_risiko_umum'])
    : [];
$faktor_risiko_obstetri = $isEdit && !empty($statusObstetri['faktor_risiko_obstetri'])
    ? explode(',', $statusObstetri['faktor_risiko_obstetri'])
    : [];
$faktor_risiko_preeklampsia = $isEdit && !empty($statusObstetri['faktor_risiko_preeklampsia'])
    ? explode(',', $statusObstetri['faktor_risiko_preeklampsia'])
    : [];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?= $pageTitle ?></h3>
                    <div class="card-tools">
                        <a href="index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=<?= $pasien['no_rkm_medis'] ?>" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i> <?= $_SESSION['error'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <?php unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>

                    <form action="<?= $formAction ?>" method="post">
                        <?php if ($isEdit): ?>
                            <input type="hidden" name="id_status_obstetri" value="<?= $id_status_obstetri ?>">
                        <?php endif; ?>
                        <input type="hidden" name="no_rkm_medis" value="<?= $pasien['no_rkm_medis'] ?>">
                        <input type="hidden" name="updated_at" value="<?= date('Y-m-d H:i:s') ?>">
                        <!-- Tambahkan parameter source untuk redirect -->
                        <input type="hidden" name="source" value="<?= isset($_GET['source']) ? $_GET['source'] : '' ?>">
                        <!-- Tambahkan parameter no_rawat jika ada -->
                        <input type="hidden" name="no_rawat" value="<?= isset($_GET['no_rawat']) ? $_GET['no_rawat'] : '' ?>">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="card-title mb-0">Data Pasien</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-2">
                                            <label class="col-sm-4 col-form-label">No. Rekam Medis</label>
                                            <div class="col-sm-8">
                                                <input type="text" class="form-control" value="<?= $pasien['no_rkm_medis'] ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <label class="col-sm-4 col-form-label">Nama Pasien</label>
                                            <div class="col-sm-8">
                                                <input type="text" class="form-control" value="<?= $pasien['nm_pasien'] ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <label class="col-sm-4 col-form-label">Tanggal Lahir</label>
                                            <div class="col-sm-8">
                                                <input type="text" class="form-control" value="<?= date('d-m-Y', strtotime($pasien['tgl_lahir'])) ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <label class="col-sm-4 col-form-label">Umur</label>
                                            <div class="col-sm-8">
                                                <input type="text" class="form-control" value="<?= $pasien['umur'] ?> tahun" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="card-title mb-0">Data Obstetri</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-2">
                                            <label class="col-sm-4 col-form-label">Gravida</label>
                                            <div class="col-sm-8">
                                                <input type="number" class="form-control" name="gravida" value="<?= $gravida ?>" min="0">
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <label class="col-sm-4 col-form-label">Paritas</label>
                                            <div class="col-sm-8">
                                                <input type="number" class="form-control" name="paritas" value="<?= $paritas ?>" min="0">
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <label class="col-sm-4 col-form-label">Abortus</label>
                                            <div class="col-sm-8">
                                                <input type="number" class="form-control" name="abortus" value="<?= $abortus ?>" min="0">
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <label class="col-sm-4 col-form-label">Tinggi Badan (cm)</label>
                                            <div class="col-sm-8">
                                                <input type="number" class="form-control" name="tb" value="<?= $tb ?>" min="0" step="1">
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <label class="col-sm-4 col-form-label">Tanggal HPHT</label>
                                            <div class="col-sm-8">
                                                <input type="date" class="form-control" name="tanggal_hpht" value="<?= $tanggal_hpht ?>">
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <label class="col-sm-4 col-form-label">Tanggal TP</label>
                                            <div class="col-sm-8">
                                                <input type="date" class="form-control" name="tanggal_tp" value="<?= $tanggal_tp ?>">
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <label class="col-sm-4 col-form-label">Tanggal TP Penyesuaian</label>
                                            <div class="col-sm-8">
                                                <input type="date" class="form-control" name="tanggal_tp_penyesuaian" value="<?= $tanggal_tp_penyesuaian ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="card-title mb-0">Faktor Risiko</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <h6>Faktor Risiko Umum</h6>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="faktor_risiko_umum[]" value="Terlalu muda hamil kurang dari 16 th" <?= in_array('Terlalu muda hamil kurang dari 16 th', $faktor_risiko_umum) ? 'checked' : '' ?>>
                                                    <label class="form-check-label">Terlalu muda hamil kurang dari 16 th</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="faktor_risiko_umum[]" value="Terlalu lambat hamil 1 kawin lebih dari 4 th" <?= in_array('Terlalu lambat hamil 1 kawin lebih dari 4 th', $faktor_risiko_umum) ? 'checked' : '' ?>>
                                                    <label class="form-check-label">Terlalu lambat hamil 1 kawin lebih dari 4 th</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="faktor_risiko_umum[]" value="Terlalu tua hamil 1 lebih dari 35 th" <?= in_array('Terlalu tua hamil 1 lebih dari 35 th', $faktor_risiko_umum) ? 'checked' : '' ?>>
                                                    <label class="form-check-label">Terlalu tua hamil 1 lebih dari 35 th</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="faktor_risiko_umum[]" value="Terlalu cepat hamil lagi kurang dari 2 th" <?= in_array('Terlalu cepat hamil lagi kurang dari 2 th', $faktor_risiko_umum) ? 'checked' : '' ?>>
                                                    <label class="form-check-label">Terlalu cepat hamil lagi kurang dari 2 th</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="faktor_risiko_umum[]" value="Terlalu lama hamil lagi lebih dari 10 th" <?= in_array('Terlalu lama hamil lagi lebih dari 10 th', $faktor_risiko_umum) ? 'checked' : '' ?>>
                                                    <label class="form-check-label">Terlalu lama hamil lagi lebih dari 10 th</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="faktor_risiko_umum[]" value="Terlalu banyak anak lebih dari 3" <?= in_array('Terlalu banyak anak lebih dari 3', $faktor_risiko_umum) ? 'checked' : '' ?>>
                                                    <label class="form-check-label">Terlalu banyak anak lebih dari 3</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="faktor_risiko_umum[]" value="Terlalu tua umur lebih dari 35 th" <?= in_array('Terlalu tua umur lebih dari 35 th', $faktor_risiko_umum) ? 'checked' : '' ?>>
                                                    <label class="form-check-label">Terlalu tua umur lebih dari 35 th</label>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <h6>Faktor Risiko Obstetri</h6>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="faktor_risiko_obstetri[]" value="Terlalu pendek kurang dari 145 th" <?= in_array('Terlalu pendek kurang dari 145 th', $faktor_risiko_obstetri) ? 'checked' : '' ?>>
                                                    <label class="form-check-label">Terlalu pendek kurang dari 145 th</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="faktor_risiko_obstetri[]" value="Pernah melahirkan dengan vakum" <?= in_array('Pernah melahirkan dengan vakum', $faktor_risiko_obstetri) ? 'checked' : '' ?>>
                                                    <label class="form-check-label">Pernah melahirkan dengan vakum</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="faktor_risiko_obstetri[]" value="Pernah dilakukan plasenta manual" <?= in_array('Pernah dilakukan plasenta manual', $faktor_risiko_obstetri) ? 'checked' : '' ?>>
                                                    <label class="form-check-label">Pernah dilakukan plasenta manual</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="faktor_risiko_obstetri[]" value="Riwayat operasi sesar" <?= in_array('Riwayat operasi sesar', $faktor_risiko_obstetri) ? 'checked' : '' ?>>
                                                    <label class="form-check-label">Riwayat operasi sesar</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="faktor_risiko_obstetri[]" value="Kelainan letak sungsang atau lintang atau oblik" <?= in_array('Kelainan letak sungsang atau lintang atau oblik', $faktor_risiko_obstetri) ? 'checked' : '' ?>>
                                                    <label class="form-check-label">Kelainan letak sungsang atau lintang atau oblik</label>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <h6>Faktor Risiko Preeklampsia</h6>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="faktor_risiko_preeklampsia[]" value="Riwayat keluarga preeklampsia" <?= in_array('Riwayat keluarga preeklampsia', $faktor_risiko_preeklampsia) ? 'checked' : '' ?>>
                                                    <label class="form-check-label">Riwayat keluarga preeklampsia</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="faktor_risiko_preeklampsia[]" value="Anak pertama atau primigravida" <?= in_array('Anak pertama atau primigravida', $faktor_risiko_preeklampsia) ? 'checked' : '' ?>>
                                                    <label class="form-check-label">Anak pertama atau primigravida</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="faktor_risiko_preeklampsia[]" value="Kehamilan kembar" <?= in_array('Kehamilan kembar', $faktor_risiko_preeklampsia) ? 'checked' : '' ?>>
                                                    <label class="form-check-label">Kehamilan kembar</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="faktor_risiko_preeklampsia[]" value="Hamil ini setelah lebih dari 10 tahun" <?= in_array('Hamil ini setelah lebih dari 10 tahun', $faktor_risiko_preeklampsia) ? 'checked' : '' ?>>
                                                    <label class="form-check-label">Hamil ini setelah lebih dari 10 tahun</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="faktor_risiko_preeklampsia[]" value="Usia lebih dari 35 tahun" <?= in_array('Usia lebih dari 35 tahun', $faktor_risiko_preeklampsia) ? 'checked' : '' ?>>
                                                    <label class="form-check-label">Usia lebih dari 35 tahun</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="faktor_risiko_preeklampsia[]" value="Body Mass Index BMI lebih dari 30 atau obesitas" <?= in_array('Body Mass Index BMI lebih dari 30 atau obesitas', $faktor_risiko_preeklampsia) ? 'checked' : '' ?>>
                                                    <label class="form-check-label">Body Mass Index BMI lebih dari 30 atau obesitas</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="faktor_risiko_preeklampsia[]" value="Mean Arterial Pressure lebih dari 90" <?= in_array('Mean Arterial Pressure lebih dari 90', $faktor_risiko_preeklampsia) ? 'checked' : '' ?>>
                                                    <label class="form-check-label">Mean Arterial Pressure lebih dari 90</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="faktor_risiko_preeklampsia[]" value="Roll Over Test lebih dari 15 mmHg" <?= in_array('Roll Over Test lebih dari 15 mmHg', $faktor_risiko_preeklampsia) ? 'checked' : '' ?>>
                                                    <label class="form-check-label">Roll Over Test lebih dari 15 mmHg</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="faktor_risiko_preeklampsia[]" value="Riwayat Hipertensi kehamilan sebelumnya" <?= in_array('Riwayat Hipertensi kehamilan sebelumnya', $faktor_risiko_preeklampsia) ? 'checked' : '' ?>>
                                                    <label class="form-check-label">Riwayat Hipertensi kehamilan sebelumnya</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="faktor_risiko_preeklampsia[]" value="Kelainan ginjal" <?= in_array('Kelainan ginjal', $faktor_risiko_preeklampsia) ? 'checked' : '' ?>>
                                                    <label class="form-check-label">Kelainan ginjal</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="faktor_risiko_preeklampsia[]" value="Diabetes" <?= in_array('Diabetes', $faktor_risiko_preeklampsia) ? 'checked' : '' ?>>
                                                    <label class="form-check-label">Diabetes</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="faktor_risiko_preeklampsia[]" value="Penyakit autoimun" <?= in_array('Penyakit autoimun', $faktor_risiko_preeklampsia) ? 'checked' : '' ?>>
                                                    <label class="form-check-label">Penyakit autoimun</label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row mt-3">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="hasil_faktor_risiko">Hasil Faktor Risiko</label>
                                                    <textarea class="form-control" name="hasil_faktor_risiko" rows="3"><?= $hasil_faktor_risiko ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Simpan
                                </button>
                                <a href="index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=<?= $pasien['no_rkm_medis'] ?>" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Batal
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Fungsi untuk menghitung tanggal TP berdasarkan HPHT
    document.addEventListener('DOMContentLoaded', function() {
        const hphtInput = document.querySelector('input[name="tanggal_hpht"]');
        const tpInput = document.querySelector('input[name="tanggal_tp"]');
        const tpPenyesuaianInput = document.querySelector('input[name="tanggal_tp_penyesuaian"]');

        hphtInput.addEventListener('change', function() {
            if (this.value) {
                // Hitung TP = HPHT + 280 hari (40 minggu)
                const hphtDate = new Date(this.value);
                const tpDate = new Date(hphtDate);
                tpDate.setDate(hphtDate.getDate() + 280);

                // Format tanggal untuk input date (YYYY-MM-DD)
                const year = tpDate.getFullYear();
                const month = String(tpDate.getMonth() + 1).padStart(2, '0');
                const day = String(tpDate.getDate()).padStart(2, '0');
                const formattedDate = `${year}-${month}-${day}`;

                // Set nilai untuk TP dan TP Penyesuaian
                tpInput.value = formattedDate;
                tpPenyesuaianInput.value = formattedDate;
            }
        });
    });
</script>