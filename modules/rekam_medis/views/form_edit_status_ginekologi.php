<?php
// Pastikan variabel yang diperlukan tersedia
if (!isset($status_ginekologi)) {
    echo "Error: Data status ginekologi tidak tersedia.";
    exit;
}

// Add debugging
error_log("form_edit_status_ginekologi.php GET params: " . json_encode($_GET));
error_log("form_edit_status_ginekologi.php SESSION: " . json_encode($_SESSION));

// Ambil parameter source dengan prioritas
// 1. URL parameter (GET)
// 2. Session variable edit_source (specifically set for edit operations)
// 3. Session variable source_page (general)
$source = isset($_GET['source']) ? $_GET['source'] : '';
if (empty($source) && isset($_SESSION['edit_source'])) {
    $source = $_SESSION['edit_source'];
} elseif (empty($source) && isset($_SESSION['source_page'])) {
    $source = $_SESSION['source_page'];
}

// For debugging
error_log("Final source value used: " . $source);

// Get no_rawat parameter
$no_rawat = isset($_GET['no_rawat']) ? $_GET['no_rawat'] : '';
if (empty($no_rawat) && isset($_SESSION['no_rawat'])) {
    $no_rawat = $_SESSION['no_rawat'];
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Status Ginekologi</h3>
                    <div class="card-tools">
                        <?php if ($source == 'form_penilaian_medis_ralan_kandungan' && !empty($no_rawat)): ?>
                            <a href="index.php?module=rekam_medis&action=form_penilaian_medis_ralan_kandungan&no_rawat=<?= $no_rawat ?>&no_rkm_medis=<?= $status_ginekologi['no_rkm_medis'] ?>" class="btn btn-default btn-sm">
                                <i class="fas fa-arrow-left"></i> Kembali ke Form Penilaian
                            </a>
                        <?php else: ?>
                            <a href="index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=<?= $status_ginekologi['no_rkm_medis'] ?>" class="btn btn-default btn-sm">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        <?php endif; ?>
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
<form action="index.php?module=rekam_medis&action=update_status_ginekologi" method="post" autocomplete="off">
                        <input type="hidden" name="id_status_ginekologi" value="<?= $status_ginekologi['id_status_ginekologi'] ?>">
                        <input type="hidden" name="no_rkm_medis" value="<?= $status_ginekologi['no_rkm_medis'] ?>">
                        <?php if (!empty($source)): ?>
                        <input type="hidden" name="source" value="<?= $source ?>">
                        <?php endif; ?>
                        <?php if (!empty($no_rawat)): ?>
                        <input type="hidden" name="no_rawat" value="<?= $no_rawat ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Tanggal</label>
                            <?php
                            // Format tanggal dari database atau gunakan tanggal hari ini
                            $created_date = isset($status_ginekologi['created_at']) ? date('Y-m-d', strtotime($status_ginekologi['created_at'])) : date('Y-m-d');
                            ?>
                            <input type="date" name="tanggal" class="form-control" value="<?= $created_date ?>" readonly>
                            <small class="text-muted">Tanggal pembuatan data (tidak dapat diubah)</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Parturien</label>
                            <input type="number" name="parturien" class="form-control" value="<?= $status_ginekologi['Parturien'] ?? 0 ?>" min="0">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Abortus</label>
                            <input type="number" name="abortus" class="form-control" value="<?= $status_ginekologi['Abortus'] ?? 0 ?>" min="0">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Hari Pertama Haid Terakhir</label>
                            <input type="date" name="hpht" class="form-control" value="<?= $status_ginekologi['Hari_pertama_haid_terakhir'] ?? '' ?>">
                            <small class="text-muted">Opsional</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Kontrasepsi Terakhir</label>
                            <select name="kontrasepsi" class="form-control">
                                <option value="Tidak Ada" <?= $status_ginekologi['Kontrasepsi_terakhir'] == 'Tidak Ada' ? 'selected' : '' ?>>Tidak Ada</option>
                                <option value="Pil KB" <?= $status_ginekologi['Kontrasepsi_terakhir'] == 'Pil KB' ? 'selected' : '' ?>>Pil KB</option>
                                <option value="Suntik KB" <?= $status_ginekologi['Kontrasepsi_terakhir'] == 'Suntik KB' ? 'selected' : '' ?>>Suntik KB</option>
                                <option value="Spiral/IUD" <?= $status_ginekologi['Kontrasepsi_terakhir'] == 'Spiral/IUD' ? 'selected' : '' ?>>Spiral/IUD</option>
                                <option value="Implant" <?= $status_ginekologi['Kontrasepsi_terakhir'] == 'Implant' ? 'selected' : '' ?>>Implant</option>
                                <option value="MOW" <?= $status_ginekologi['Kontrasepsi_terakhir'] == 'MOW' ? 'selected' : '' ?>>MOW</option>
                                <option value="MOP" <?= $status_ginekologi['Kontrasepsi_terakhir'] == 'MOP' ? 'selected' : '' ?>>MOP</option>
                                <option value="Kondom" <?= $status_ginekologi['Kontrasepsi_terakhir'] == 'Kondom' ? 'selected' : '' ?>>Kondom</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Lama Menikah (Tahun)</label>
                            <input type="number" name="lama_menikah_th" class="form-control" step="0.1" min="0" value="<?= $status_ginekologi['lama_menikah_th'] ?? 0 ?>">
                            <small class="text-muted">Gunakan titik (.) untuk desimal, bukan koma</small>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Update</button>
                            <a href="index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=<?= $status_ginekologi['no_rkm_medis'] ?>" class="btn btn-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>