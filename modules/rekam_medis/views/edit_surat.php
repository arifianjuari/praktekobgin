<?php
// Expecting $surat (array) and $pasien (array) from controller
if (!isset($surat)) {
    echo "<div class='alert alert-danger'>Data surat tidak tersedia.</div>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Surat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Edit Surat</h4>
        <div>
            <a href="index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=<?= htmlspecialchars($surat['no_rkm_medis']) ?>#surat" class="btn btn-secondary btn-sm">
                &laquo; Kembali
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="post" action="index.php?module=rekam_medis&action=updateSurat">
                <input type="hidden" name="id_surat" value="<?= htmlspecialchars($surat['id_surat']) ?>">
                <input type="hidden" name="no_rkm_medis" value="<?= htmlspecialchars($surat['no_rkm_medis']) ?>">

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Surat</label>
                        <input type="date" name="tanggal_surat" class="form-control" value="<?= htmlspecialchars($surat['tanggal_surat']) ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Jenis Surat</label>
                        <select name="jenis_surat" id="jenisSuratEdit" class="form-select" required>
                            <option value="">-- Pilih Jenis Surat --</option>
                            <option value="skd" <?= $surat['jenis_surat']==='skd'?'selected':'' ?>>Surat Keterangan Dokter</option>
                            <option value="sakit" <?= $surat['jenis_surat']==='sakit'?'selected':'' ?>>Surat Sakit</option>
                            <option value="rujukan" <?= $surat['jenis_surat']==='rujukan'?'selected':'' ?>>Surat Rujukan</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Dokter Pemeriksa</label>
                        <input type="text" name="dokter_pemeriksa" class="form-control" value="<?= htmlspecialchars($surat['dokter_pemeriksa'] ?? '') ?>" required>
                    </div>
                </div>

                <div id="fieldSuratSakitEdit" class="mt-3" style="display:none;">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Mulai Sakit</label>
                            <input type="date" name="mulai_sakit" class="form-control" value="<?= htmlspecialchars($surat['mulai_sakit'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Selesai Sakit</label>
                            <input type="date" name="selesai_sakit" class="form-control" value="<?= htmlspecialchars($surat['selesai_sakit'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div id="fieldSuratDokterEdit" class="mt-3" style="display:none;">
                    <div class="mb-3">
                        <label class="form-label">Keperluan</label>
                        <input type="text" name="keperluan" class="form-control" placeholder="Keperluan dibuatnya surat" value="<?= htmlspecialchars($surat['keperluan'] ?? '') ?>">
                    </div>
                </div>

                <div class="mb-3 mt-3">
                    <label class="form-label">Diagnosa</label>
                    <input type="text" name="diagnosa" class="form-control" placeholder="Diagnosa pasien (opsional)" value="<?= htmlspecialchars($surat['diagnosa'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Catatan</label>
                    <textarea name="catatan" class="form-control" rows="3" placeholder="Catatan tambahan di surat"><?= htmlspecialchars($surat['catatan'] ?? '') ?></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=<?= htmlspecialchars($surat['no_rkm_medis']) ?>#surat" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
(function() {
    function toggleJenisSuratFields(value) {
        const sakit = document.getElementById('fieldSuratSakitEdit');
        const skd = document.getElementById('fieldSuratDokterEdit');
        sakit.style.display = 'none';
        skd.style.display = 'none';
        if (value === 'sakit') sakit.style.display = 'block';
        if (value === 'skd') skd.style.display = 'block';
    }
    const select = document.getElementById('jenisSuratEdit');
    toggleJenisSuratFields(select.value);
    select.addEventListener('change', function() { toggleJenisSuratFields(this.value); });
})();
</script>
</body>
</html>
