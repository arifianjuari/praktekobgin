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
                    <h3 class="card-title">Edit Template Anamnesis</h3>
                </div>
                <div class="card-body">
                    <!-- Pesan Sukses/Error -->
                    <?php if (!empty($error_message)) : ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= $error_message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="index.php?module=rekam_medis&action=update_template_anamnesis" method="post">
                        <input type="hidden" name="id_template_anamnesis" value="<?= $template['id_template_anamnesis'] ?>">
                        
                        <div class="mb-3">
                            <label for="nama_template_anamnesis" class="form-label">Nama Template <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_template_anamnesis" name="nama_template_anamnesis" value="<?= htmlspecialchars($template['nama_template_anamnesis']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="kategori_anamnesis" class="form-label">Kategori <span class="text-danger">*</span></label>
                            <select class="form-select" id="kategori_anamnesis" name="kategori_anamnesis" required>
                                <option value="">Pilih Kategori</option>
                                <?php foreach ($kategori as $k) : ?>
                                    <option value="<?= $k['kategori_anamnesis'] ?>" <?= $template['kategori_anamnesis'] == $k['kategori_anamnesis'] ? 'selected' : '' ?>><?= ucwords($k['kategori_anamnesis']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="isi_template_anamnesis" class="form-label">Isi Template <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="isi_template_anamnesis" name="isi_template_anamnesis" rows="10" required><?= htmlspecialchars($template['isi_template_anamnesis']) ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tags" class="form-label">Tags (opsional)</label>
                            <input type="text" class="form-control" id="tags" name="tags" value="<?= htmlspecialchars($template['tags'] ?? '') ?>" placeholder="Pisahkan dengan koma, contoh: hamil, anemia, hipertensi">
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" <?= $template['status'] == 'active' ? 'selected' : '' ?>>Aktif</option>
                                <option value="inactive" <?= $template['status'] == 'inactive' ? 'selected' : '' ?>>Tidak Aktif</option>
                            </select>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="index.php?module=rekam_medis&action=template_anamnesis" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Auto-resize textarea
    document.addEventListener('DOMContentLoaded', function() {
        const textarea = document.getElementById('isi_template_anamnesis');
        textarea.style.height = 'auto';
        textarea.style.height = (textarea.scrollHeight) + 'px';
        
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });
</script>
