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
                    <h3 class="card-title">Edit Template USG</h3>
                </div>
                <div class="card-body">
                    <form action="index.php?module=rekam_medis&action=update_template_usg" method="post" id="formEditTemplate">
                        <input type="hidden" name="id_template_usg" value="<?= $template['id_template_usg'] ?>">

                        <div class="mb-3">
                            <label for="nama_template_usg" class="form-label">Nama Template</label>
                            <input type="text" class="form-control" id="nama_template_usg" name="nama_template_usg" value="<?= htmlspecialchars($template['nama_template_usg']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="isi_template_usg" class="form-label">Isi Template</label>
                            <textarea class="form-control" id="isi_template_usg" name="isi_template_usg" rows="10" required><?= htmlspecialchars($template['isi_template_usg']) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="kategori_usg" class="form-label">Kategori</label>
                            <select class="form-select" id="kategori_usg" name="kategori_usg" required>
                                <option value="">Pilih Kategori</option>
                                <?php foreach ($kategori as $k) : ?>
                                    <option value="<?= $k['kategori_usg'] ?>" <?= $template['kategori_usg'] == $k['kategori_usg'] ? 'selected' : '' ?>><?= ucwords($k['kategori_usg']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="tags" class="form-label">Tags (opsional, pisahkan dengan koma)</label>
                            <input type="text" class="form-control" id="tags" name="tags" value="<?= htmlspecialchars($template['tags'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active" <?= $template['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $template['status'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="index.php?module=rekam_medis&action=template_usg" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Validasi form edit template
        const formEditTemplate = document.getElementById('formEditTemplate');
        if (formEditTemplate) {
            formEditTemplate.addEventListener('submit', function(event) {
                const kategori = document.getElementById('kategori_usg').value;
                if (!kategori) {
                    event.preventDefault();
                    alert('Silakan pilih kategori terlebih dahulu');
                    return false;
                }

                // Log untuk debugging
                console.log('Update Template:', {
                    id: document.querySelector('input[name="id_template_usg"]').value,
                    nama: document.getElementById('nama_template_usg').value,
                    isi: document.getElementById('isi_template_usg').value,
                    kategori: document.getElementById('kategori_usg').value,
                    status: document.getElementById('status').value,
                    tags: document.getElementById('tags').value
                });

                return true;
            });
        }
    });
</script>