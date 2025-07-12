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
                    <h3 class="card-title">Manajemen Template Tatalaksana</h3>
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
                                <input type="hidden" name="action" value="template_tatalaksana">
                                <div class="input-group">
                                    <select id="filterKategori" name="kategori" class="form-select">
                                        <option value="">Semua Kategori</option>
                                        <?php foreach ($kategori as $k) : ?>
                                            <option value="<?= $k['kategori_tx'] ?>" <?= isset($filter_kategori) && $filter_kategori == $k['kategori_tx'] ? 'selected' : '' ?>><?= ucwords($k['kategori_tx']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-outline-secondary" type="submit">Filter</button>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-4 ms-auto">
                            <form action="index.php" method="get" id="formSearch">
                                <input type="hidden" name="module" value="rekam_medis">
                                <input type="hidden" name="action" value="template_tatalaksana">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Cari template..." id="searchInput" name="search" value="<?= isset($search_keyword) ? $search_keyword : '' ?>">
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <?php if (isset($search_keyword) || isset($filter_kategori)) : ?>
                                        <a href="index.php?module=rekam_medis&action=template_tatalaksana" class="btn btn-outline-secondary">
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
                                        <td><?= $template['id_template_tx'] ?></td>
                                        <td><?= $template['nama_template_tx'] ?></td>
                                        <td>
                                            <div style="max-height: 100px; overflow-y: auto;">
                                                <?= nl2br(htmlspecialchars($template['isi_template_tx'])) ?>
                                            </div>
                                        </td>
                                        <td><?= ucwords($template['kategori_tx']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $template['status'] == 'active' ? 'success' : 'danger' ?>">
                                                <?= $template['status'] == 'active' ? 'Aktif' : 'Tidak Aktif' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="post" action="index.php?module=rekam_medis&action=edit_template_form" style="display:inline;">
                                                <input type="hidden" name="id_template" value="<?= $template['id_template_tx'] ?>">
                                                <button type="submit" class="btn btn-warning btn-sm">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                            </form>
                                            <form method="post" action="index.php?module=rekam_medis&action=hapus_template_tatalaksana" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus template \'<?= addslashes(htmlspecialchars($template['nama_template_tx'])) ?>\'?');">
                                                <input type="hidden" name="id_template" value="<?= $template['id_template_tx'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
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
                <h5 class="modal-title" id="modalTambahTemplateLabel">Tambah Template Tatalaksana</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formTambahTemplate" action="index.php?module=rekam_medis&action=simpan_template_tatalaksana" method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nama_template_tx" class="form-label">Nama Template</label>
                        <input type="text" class="form-control" id="nama_template_tx" name="nama_template_tx" required>
                    </div>
                    <div class="mb-3">
                        <label for="kategori_tx" class="form-label">Kategori</label>
                        <select class="form-select" id="kategori_tx" name="kategori_tx" required>
                            <?php foreach ($kategori as $k) : ?>
                                <option value="<?= $k['kategori_tx'] ?>"><?= ucwords($k['kategori_tx']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="isi_template_tx" class="form-label">Isi Template</label>
                        <textarea class="form-control" id="isi_template_tx" name="isi_template_tx" rows="5" required></textarea>
                        <small class="text-muted">Gunakan format baris baru untuk memisahkan item.</small>
                    </div>
                    <div class="mb-3">
                        <label for="tags" class="form-label">Tags (opsional)</label>
                        <input type="text" class="form-control" id="tags" name="tags">
                        <small class="text-muted">Pisahkan dengan koma (contoh: obat, terapi, kontrol)</small>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active">Aktif</option>
                            <option value="inactive">Tidak Aktif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Template -->
<div class="modal fade" id="modalEditTemplate" tabindex="-1" aria-labelledby="modalEditTemplateLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditTemplateLabel">Edit Template Tatalaksana</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditTemplate" action="index.php?module=rekam_medis&action=update_template_tatalaksana" method="post">
                <div class="modal-body">
                    <input type="hidden" id="edit_id_template" name="id_template">
                    <div class="mb-3">
                        <label for="edit_nama_template_tx" class="form-label">Nama Template</label>
                        <input type="text" class="form-control" id="edit_nama_template_tx" name="nama_template_tx" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_kategori_tx" class="form-label">Kategori</label>
                        <select class="form-select" id="edit_kategori_tx" name="kategori_tx" required>
                            <?php foreach ($kategori as $k) : ?>
                                <option value="<?= $k['kategori_tx'] ?>"><?= ucwords($k['kategori_tx']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_isi_template_tx" class="form-label">Isi Template</label>
                        <textarea class="form-control" id="edit_isi_template_tx" name="isi_template_tx" rows="5" required></textarea>
                        <small class="text-muted">Gunakan format baris baru untuk memisahkan item.</small>
                    </div>
                    <div class="mb-3">
                        <label for="edit_tags" class="form-label">Tags (opsional)</label>
                        <input type="text" class="form-control" id="edit_tags" name="tags">
                        <small class="text-muted">Pisahkan dengan koma (contoh: obat, terapi, kontrol)</small>
                    </div>
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <select class="form-select" id="edit_status" name="status">
                            <option value="active">Aktif</option>
                            <option value="inactive">Tidak Aktif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="modalKonfirmasiHapus" tabindex="-1" aria-labelledby="modalKonfirmasiHapusLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalKonfirmasiHapusLabel">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus template "<span id="hapus_nama_template"></span>"?</p>
                <p class="text-danger">Tindakan ini akan menghapus template secara permanen dan tidak dapat dibatalkan.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form id="formHapusTemplate" action="index.php?module=rekam_medis&action=hapus_template_tatalaksana" method="post">
                    <input type="hidden" id="hapus_id_template" name="id_template">
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Fungsi untuk menampilkan modal edit template
    function editTemplate(id, nama, isi, kategori, status, tags) {
        // Set nilai pada form edit
        document.getElementById('edit_id_template').value = id;
        document.getElementById('edit_nama_template_tx').value = nama;
        document.getElementById('edit_isi_template_tx').value = isi;
        document.getElementById('edit_kategori_tx').value = kategori;
        document.getElementById('edit_status').value = status;
        document.getElementById('edit_tags').value = tags;

        // Tampilkan modal
        var myModal = new bootstrap.Modal(document.getElementById('modalEditTemplate'));
        myModal.show();
    }

    // Fungsi untuk menampilkan modal konfirmasi hapus
    function hapusTemplate(id, nama) {
        // Set nilai pada form hapus
        document.getElementById('hapus_id_template').value = id;
        document.getElementById('hapus_nama_template').textContent = nama;

        // Tampilkan modal
        var myModal = new bootstrap.Modal(document.getElementById('modalKonfirmasiHapus'));
        myModal.show();
    }

    // Validasi form tambah template
    document.getElementById('formTambahTemplate').addEventListener('submit', function(e) {
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

    // Validasi form edit template
    document.getElementById('formEditTemplate').addEventListener('submit', function(e) {
        var nama = document.getElementById('edit_nama_template_tx').value.trim();
        var isi = document.getElementById('edit_isi_template_tx').value.trim();
        var kategori = document.getElementById('edit_kategori_tx').value;

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