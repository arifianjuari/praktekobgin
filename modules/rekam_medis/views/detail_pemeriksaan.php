<?php
// Pastikan tidak ada output sebelum header
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah ada data pemeriksaan
if (!isset($pemeriksaan) || !$pemeriksaan) {
    $_SESSION['error'] = 'Data pemeriksaan tidak ditemukan';
    header('Location: index.php?module=rekam_medis');
    exit;
}

// Set URL untuk edit pemeriksaan menggunakan form_edit_pemeriksaan (dengan underscore) yang sudah terdaftar di router
$edit_url = "index.php?module=rekam_medis&action=form_edit_pemeriksaan&no_rawat=" . $pemeriksaan['no_rawat'];
error_log("Edit URL: " . $edit_url);
?>

<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Detail Pemeriksaan</h6>
            <div>
                <a href="<?= $edit_url ?>" class="btn btn-sm btn-warning">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <a href="index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=<?= $pemeriksaan['no_rkm_medis'] ?>" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?= $_SESSION['error'] ?>
                    <?php unset($_SESSION['error']) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?= $_SESSION['success'] ?>
                    <?php unset($_SESSION['success']) ?>
                </div>
            <?php endif; ?>

            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="mb-3">Data Pasien</h5>
                    <table class="table table-sm">
                        <tr>
                            <th width="150">No. Rekam Medis</th>
                            <td><?= $pasien['no_rkm_medis'] ?></td>
                        </tr>
                        <tr>
                            <th>Nama Pasien</th>
                            <td><?= $pasien['nm_pasien'] ?></td>
                        </tr>
                        <tr>
                            <th>Tanggal Lahir</th>
                            <td><?= date('d-m-Y', strtotime($pasien['tgl_lahir'])) ?></td>
                        </tr>
                        <tr>
                            <th>Alamat</th>
                            <td><?= $pasien['alamat'] ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h5 class="mb-3">Data Pemeriksaan</h5>
                    <table class="table table-sm">
                        <tr>
                            <th width="150">Tanggal Pemeriksaan</th>
                            <td><?= date('d-m-Y H:i', strtotime($pemeriksaan['tanggal'])) ?></td>
                        </tr>
                        <tr>
                            <th>Dokter</th>
                            <td><?= $pemeriksaan['Nama_Dokter'] ?></td>
                        </tr>
                        <tr>
                            <th>No. Rawat</th>
                            <td><?= $pemeriksaan['no_rawat'] ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <h5 class="mb-3">Hasil Pemeriksaan</h5>
                    <table class="table table-bordered">
                        <tr>
                            <th width="200">Keluhan Utama</th>
                            <td><?= nl2br($pemeriksaan['keluhan_utama']) ?></td>
                        </tr>
                        <tr>
                            <th>Pemeriksaan</th>
                            <td><?= nl2br($pemeriksaan['rps']) ?></td>
                        </tr>
                        <tr>
                            <th>BB/TB</th>
                            <td><?= ($pemeriksaan['bb'] || $pemeriksaan['tb']) ? ($pemeriksaan['bb'] ?: '-') . ' kg / ' . ($pemeriksaan['tb'] ?: '-') . ' cm' : '-' ?></td>
                        </tr>
                        <tr>
                            <th>BMI</th>
                            <td><?= $pemeriksaan['bmi'] ? $pemeriksaan['bmi'] . ' kg/m² (' . $pemeriksaan['interpretasi_bmi'] . ')' : '-' ?></td>
                        </tr>
                        <tr>
                            <th>Diagnosis</th>
                            <td><?= nl2br($pemeriksaan['diagnosis']) ?></td>
                        </tr>
                        <tr>
                            <th>Tindakan</th>
                            <td><?= nl2br($pemeriksaan['tata']) ?></td>
                        </tr>
                        <tr>
                            <th>Resep</th>
                            <td><?= nl2br($pemeriksaan['resep']) ?></td>
                        </tr>
                        <tr>
                            <th>Edukasi</th>
                            <td><?= nl2br($pemeriksaan['konsul']) ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="mt-4">
                <a href="index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=<?= $pemeriksaan['no_rkm_medis'] ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali ke Detail Pasien
                </a>
                <a href="<?= $edit_url ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit Pemeriksaan
                </a>
            </div>
        </div>
    </div>
</div>