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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Manajemen Template Ceklist</h3>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahTemplate">
                        <i class="fas fa-plus"></i> Tambah Template
                    </button>
                </div>
                <div class="card-body">
                    <!-- Pesan Sukses/Error -->
                    <?php if (!empty($success_message)) : ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= $success_message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error_message)) : ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= $error_message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Filter Kategori -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <form action="index.php" method="get" id="formFilter">
                                <input type="hidden" name="module" value="rekam_medis">
                                <input type="hidden" name="action" value="template_ceklist">
                                <div class="input-group">
                                    <select id="filterKategori" name="kategori" class="form-select">
                                        <option value="">Semua Kategori</option>
                                        <?php foreach ($kategori as $k) : ?>
                                            <option value="<?= $k['kategori_ck'] ?>" <?= isset($filter_kategori) && $filter_kategori == $k['kategori_ck'] ? 'selected' : '' ?>><?= ucwords($k['kategori_ck']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-outline-secondary" type="submit">Filter</button>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-4 ms-auto">
                            <form action="index.php" method="get" id="formSearch">
                                <input type="hidden" name="module" value="rekam_medis">
                                <input type="hidden" name="action" value="template_ceklist">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Cari template..." id="searchInput" name="search" value="<?= isset($search_keyword) ? $search_keyword : '' ?>">
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <?php if (isset($search_keyword) || isset($filter_kategori)) : ?>
                                        <a href="index.php?module=rekam_medis&action=template_ceklist" class="btn btn-outline-secondary">
                                            <i class="fas fa-times"></i> Reset
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tabel Template -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="100">ID</th>
                                    <th width="200">Nama Template</th>
                                    <th>Isi Template</th>
                                    <th width="150">Kategori</th>
                                    <th width="100">Status</th>
                                    <th width="150">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($templates as $template) : ?>
                                    <tr>
                                        <td><?= $template['id_template_ceklist'] ?></td>
                                        <td><?= $template['nama_template_ck'] ?></td>
                                        <td>
                                            <div style="max-height: 100px; overflow-y: auto;">
                                                <?= nl2br(htmlspecialchars($template['isi_template_ck'])) ?>
                                            </div>
                                        </td>
                                        <td><?= ucwords($template['kategori_ck']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $template['status'] == 'active' ? 'success' : 'danger' ?>">
                                                <?= $template['status'] == 'active' ? 'Aktif' : 'Tidak Aktif' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="post" action="index.php?module=rekam_medis&action=edit_template_ceklist_form" style="display:inline;">
                                                <input type="hidden" name="id_template" value="<?= $template['id_template_ceklist'] ?>">
                                                <button type="submit" class="btn btn-sm btn-info mb-1 w-100">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-sm btn-danger w-100" onclick="confirmDelete('<?= $template['id_template_ceklist'] ?>')">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if (empty($templates)) : ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Tidak ada data template</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Template -->
<div class="modal fade" id="modalTambahTemplate" tabindex="-1" aria-labelledby="modalTambahTemplateLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTambahTemplateLabel">Tambah Template Ceklist</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="index.php?module=rekam_medis&action=simpan_template_ceklist" method="post" id="formTambahTemplate">
                    <div class="mb-3">
                        <label for="nama_template_ck" class="form-label">Nama Template <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama_template_ck" name="nama_template_ck" required>
                    </div>
                    <div class="mb-3">
                        <label for="kategori_ck" class="form-label">Kategori <span class="text-danger">*</span></label>
                        <select class="form-select" id="kategori_ck" name="kategori_ck" required>
                            <option value="">Pilih Kategori</option>
                            <?php foreach ($kategori as $k) : ?>
                                <option value="<?= $k['kategori_ck'] ?>"><?= ucwords($k['kategori_ck']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="isi_template_ck" class="form-label">Isi Template <span class="text-danger">*</span></label>
                        <p class="text-muted small">Masukkan setiap item ceklist pada baris terpisah</p>
                        <textarea class="form-control" id="isi_template_ck" name="isi_template_ck" rows="10" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="tags" class="form-label">Tags (opsional)</label>
                        <input type="text" class="form-control" id="tags" name="tags" placeholder="Pisahkan dengan koma, contoh: hamil, anemia, hipertensi">
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active">Aktif</option>
                            <option value="inactive">Tidak Aktif</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" form="formTambahTemplate">Simpan</button>
            </div>
        </div>
    </div>
</div>

<!-- Form Hapus Template (hidden) -->
<form id="formHapusTemplate" action="index.php?module=rekam_medis&action=hapus_template_ceklist" method="post" style="display: none;">
    <input type="hidden" name="id_template" id="hapusTemplateId">
</form>

<script>
    function confirmDelete(id) {
        if (confirm('Apakah Anda yakin ingin menghapus template ini?')) {
            document.getElementById('hapusTemplateId').value = id;
            document.getElementById('formHapusTemplate').submit();
        }
    }

    // Auto-resize textarea
    document.addEventListener('DOMContentLoaded', function() {
        const textareas = document.querySelectorAll('textarea');
        textareas.forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        });
    });
</script>
