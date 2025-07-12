<?php
// Pastikan tidak ada akses langsung ke file ini
if (!defined('BASE_PATH')) {
    die('No direct script access allowed');
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Template Tatalaksana</h3>
                </div>
                <div class="card-body">
                    <form action="index.php?module=rekam_medis&action=update_template_tatalaksana" method="post" id="formEditTemplate">
                        <input type="hidden" name="id_template" value="<?= $template['id_template_tx'] ?>">

                        <div class="mb-3">
                            <label for="nama_template_tx" class="form-label">Nama Template</label>
                            <input type="text" class="form-control" id="nama_template_tx" name="nama_template_tx" value="<?= htmlspecialchars($template['nama_template_tx']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="kategori_tx" class="form-label">Kategori</label>
                            <select class="form-select" id="kategori_tx" name="kategori_tx" required>
                                <?php foreach ($kategori as $k) : ?>
                                    <option value="<?= $k['kategori_tx'] ?>" <?= $template['kategori_tx'] == $k['kategori_tx'] ? 'selected' : '' ?>><?= ucwords($k['kategori_tx']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="isi_template_tx" class="form-label">Isi Template</label>
                            <textarea class="form-control" id="isi_template_tx" name="isi_template_tx" rows="10" required><?= htmlspecialchars($template['isi_template_tx']) ?></textarea>
                            <small class="text-muted">Gunakan format baris baru untuk memisahkan item.</small>
                        </div>

                        <div class="mb-3">
                            <label for="tags" class="form-label">Tags (opsional)</label>
                            <input type="text" class="form-control" id="tags" name="tags" value="<?= htmlspecialchars($template['tags'] ?? '') ?>">
                            <small class="text-muted">Pisahkan dengan koma (contoh: obat, terapi, kontrol)</small>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" <?= $template['status'] == 'active' ? 'selected' : '' ?>>Aktif</option>
                                <option value="inactive" <?= $template['status'] == 'inactive' ? 'selected' : '' ?>>Tidak Aktif</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                            <a href="index.php?module=rekam_medis&action=template_tatalaksana" class="btn btn-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Validasi form
    document.getElementById('formEditTemplate').addEventListener('submit', function(e) {
        var nama = document.getElementById('nama_template_tx').value.trim();
        var isi = document.getElementById('isi_template_tx').value.trim();
        var kategori = document.getElementById('kategori_tx').value;

        if (!nama) {
            e.preventDefault();
            alert('Nama template harus diisi');
            return false;
        }

        if (!isi) {
            e.preventDefault();
            alert('Isi template harus diisi');
            return false;
        }

        if (!kategori) {
            e.preventDefault();
            alert('Kategori harus dipilih');
            return false;
        }

        return true;
    });
</script>