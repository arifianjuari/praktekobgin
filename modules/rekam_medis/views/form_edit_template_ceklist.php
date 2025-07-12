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
                    <h3 class="card-title">Edit Template Ceklist</h3>
                </div>
                <div class="card-body">
                    <!-- Pesan Error -->
                    <?php if (!empty($error_message)) : ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= $error_message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="index.php?module=rekam_medis&action=update_template_ceklist" method="post">
                        <input type="hidden" name="id_template_ceklist" value="<?= $template['id_template_ceklist'] ?>">
                        
                        <div class="mb-3">
                            <label for="nama_template_ck" class="form-label">Nama Template <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_template_ck" name="nama_template_ck" value="<?= htmlspecialchars($template['nama_template_ck']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="kategori_ck" class="form-label">Kategori <span class="text-danger">*</span></label>
                            <select class="form-select" id="kategori_ck" name="kategori_ck" required>
                                <option value="">Pilih Kategori</option>
                                <?php foreach ($kategori as $k) : ?>
                                    <option value="<?= $k['kategori_ck'] ?>" <?= $template['kategori_ck'] == $k['kategori_ck'] ? 'selected' : '' ?>><?= ucwords($k['kategori_ck']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="isi_template_ck" class="form-label">Isi Template <span class="text-danger">*</span></label>
                            <p class="text-muted small">Masukkan setiap item ceklist pada baris terpisah</p>
                            <textarea class="form-control" id="isi_template_ck" name="isi_template_ck" rows="10" required><?= htmlspecialchars($template['isi_template_ck']) ?></textarea>
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
                            <a href="index.php?module=rekam_medis&action=template_ceklist" class="btn btn-secondary">
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
        const textarea = document.getElementById('isi_template_ck');
        
        function autoResize() {
            textarea.style.height = 'auto';
            textarea.style.height = (textarea.scrollHeight) + 'px';
        }
        
        // Initial resize
        autoResize();
        
        // Resize on input
        textarea.addEventListener('input', autoResize);
    });
</script>
