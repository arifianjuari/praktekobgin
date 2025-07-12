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
                    <h3 class="card-title">Manajemen Template USG</h3>
                </div>
                <div class="card-body">
                    <!-- Alert Success -->
                    <?php if (isset($_GET['success'])) : ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php
                            $success_msg = '';
                            switch ($_GET['success']) {
                                case '1':
                                    $success_msg = 'Template berhasil disimpan';
                                    break;
                                case '2':
                                    $success_msg = 'Template berhasil diupdate';
                                    break;
                                case '3':
                                    $success_msg = 'Template berhasil dihapus';
                                    break;
                                default:
                                    $success_msg = 'Operasi berhasil';
                            }
                            echo $success_msg;
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Alert Error -->
                    <?php if (isset($_GET['error'])) : ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= urldecode($_GET['error']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Filter dan Pencarian -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <form method="get" class="d-flex">
                                <input type="hidden" name="module" value="rekam_medis">
                                <input type="hidden" name="action" value="template_usg">
                                <select name="kategori" class="form-select me-2" onchange="this.form.submit()">
                                    <option value="">Semua Kategori</option>
                                    <?php foreach ($kategori as $k) : ?>
                                        <option value="<?= $k['kategori_usg'] ?>" <?= isset($_GET['kategori']) && $_GET['kategori'] == $k['kategori_usg'] ? 'selected' : '' ?>>
                                            <?= ucwords($k['kategori_usg']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </div>
                        <div class="col-md-4">
                            <form method="get" class="d-flex">
                                <input type="hidden" name="module" value="rekam_medis">
                                <input type="hidden" name="action" value="template_usg">
                                <input type="text" name="search" class="form-control me-2" placeholder="Cari template..." value="<?= isset($_GET['search']) ? $_GET['search'] : '' ?>">
                                <button type="submit" class="btn btn-primary">Cari</button>
                            </form>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="index.php?module=rekam_medis&action=template_usg" class="btn btn-outline-secondary">
                                <i class="fas fa-sync-alt"></i> Reset
                            </a>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahTemplate">
                                <i class="fas fa-plus"></i> Tambah Template
                            </button>
                        </div>
                    </div>

                    <!-- Tabel Template -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="20%">Nama Template</th>
                                    <th width="40%">Isi Template</th>
                                    <th width="15%">Kategori</th>
                                    <th width="10%">Tags</th>
                                    <th width="10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($templates)) : ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Tidak ada data template</td>
                                    </tr>
                                <?php else : ?>
                                    <?php $no = 1; ?>
                                    <?php foreach ($templates as $template) : ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= htmlspecialchars($template['nama_template_usg']) ?></td>
                                            <td>
                                                <div style="max-height: 100px; overflow-y: auto;">
                                                    <?= nl2br(htmlspecialchars($template['isi_template_usg'])) ?>
                                                </div>
                                            </td>
                                            <td><?= ucwords($template['kategori_usg']) ?></td>
                                            <td><?= htmlspecialchars($template['tags'] ?? '-') ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalEditTemplate"
                                                    data-id="<?= $template['id_template_usg'] ?>"
                                                    data-nama="<?= htmlspecialchars($template['nama_template_usg']) ?>"
                                                    data-isi="<?= htmlspecialchars($template['isi_template_usg']) ?>"
                                                    data-kategori="<?= $template['kategori_usg'] ?>"
                                                    data-status="<?= $template['status'] ?>"
                                                    data-tags="<?= htmlspecialchars($template['tags'] ?? '') ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="post" action="index.php?module=rekam_medis&action=hapus_template_usg" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus template \'<?= addslashes(htmlspecialchars($template['nama_template_usg'])) ?>\'?');">
                                                    <input type="hidden" name="id_template" value="<?= $template['id_template_usg'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
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
                <h5 class="modal-title" id="modalTambahTemplateLabel">Tambah Template USG</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formTambahTemplate" action="index.php?module=rekam_medis&action=simpan_template_usg" method="post">
                    <div class="mb-3">
                        <label for="nama_template_usg" class="form-label">Nama Template</label>
                        <input type="text" class="form-control" id="nama_template_usg" name="nama_template_usg" required>
                    </div>
                    <div class="mb-3">
                        <label for="isi_template_usg" class="form-label">Isi Template</label>
                        <textarea class="form-control" id="isi_template_usg" name="isi_template_usg" rows="10" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="kategori_usg" class="form-label">Kategori</label>
                        <select class="form-select" id="kategori_usg" name="kategori_usg" required>
                            <option value="">Pilih Kategori</option>
                            <?php foreach ($kategori as $k) : ?>
                                <option value="<?= $k['kategori_usg'] ?>"><?= ucwords($k['kategori_usg']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="tags" class="form-label">Tags (opsional, pisahkan dengan koma)</label>
                        <input type="text" class="form-control" id="tags" name="tags">
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" form="formTambahTemplate" class="btn btn-primary">Simpan</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Template -->
<div class="modal fade" id="modalEditTemplate" tabindex="-1" aria-labelledby="modalEditTemplateLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditTemplateLabel">Edit Template USG</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formEditTemplate" action="index.php?module=rekam_medis&action=update_template_usg" method="post">
                    <input type="hidden" id="edit_id_template_usg" name="id_template_usg">
                    <div class="mb-3">
                        <label for="edit_nama_template_usg" class="form-label">Nama Template</label>
                        <input type="text" class="form-control" id="edit_nama_template_usg" name="nama_template_usg" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_isi_template_usg" class="form-label">Isi Template</label>
                        <textarea class="form-control" id="edit_isi_template_usg" name="isi_template_usg" rows="10" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_kategori_usg" class="form-label">Kategori</label>
                        <select class="form-select" id="edit_kategori_usg" name="kategori_usg" required>
                            <option value="">Pilih Kategori</option>
                            <?php foreach ($kategori as $k) : ?>
                                <option value="<?= $k['kategori_usg'] ?>"><?= ucwords($k['kategori_usg']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_tags" class="form-label">Tags (opsional, pisahkan dengan koma)</label>
                        <input type="text" class="form-control" id="edit_tags" name="tags">
                    </div>
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <select class="form-select" id="edit_status" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" form="formEditTemplate" class="btn btn-primary">Update</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Hapus Template -->
<div class="modal fade" id="modalHapusTemplate" tabindex="-1" aria-labelledby="modalHapusTemplateLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalHapusTemplateLabel">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus template ini?</p>
                <form id="formHapusTemplate" action="index.php?module=rekam_medis&action=hapus_template_usg" method="post">
                    <input type="hidden" id="hapus_id_template" name="id_template">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" form="formHapusTemplate" class="btn btn-danger">Hapus</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Validasi form tambah template
        const formTambahTemplate = document.getElementById('formTambahTemplate');
        if (formTambahTemplate) {
            formTambahTemplate.addEventListener('submit', function(event) {
                const kategori = document.getElementById('kategori_usg').value;
                if (!kategori) {
                    event.preventDefault();
                    alert('Silakan pilih kategori terlebih dahulu');
                    return false;
                }

                // Log untuk debugging
                console.log('Tambah Template:', {
                    nama: document.getElementById('nama_template_usg').value,
                    isi: document.getElementById('isi_template_usg').value,
                    kategori: document.getElementById('kategori_usg').value,
                    status: document.getElementById('status').value,
                    tags: document.getElementById('tags').value
                });

                return true;
            });
        }

        // Inisialisasi modal edit
        const modalEditTemplate = document.getElementById('modalEditTemplate');
        if (modalEditTemplate) {
            modalEditTemplate.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const nama = button.getAttribute('data-nama');
                const isi = button.getAttribute('data-isi');
                const kategori = button.getAttribute('data-kategori');
                const status = button.getAttribute('data-status');
                const tags = button.getAttribute('data-tags');

                document.getElementById('edit_id_template_usg').value = id;
                document.getElementById('edit_nama_template_usg').value = nama;
                document.getElementById('edit_isi_template_usg').value = isi;

                // Pastikan dropdown kategori diatur dengan benar
                const kategoriDropdown = document.getElementById('edit_kategori_usg');
                for (let i = 0; i < kategoriDropdown.options.length; i++) {
                    if (kategoriDropdown.options[i].value === kategori) {
                        kategoriDropdown.selectedIndex = i;
                        break;
                    }
                }

                document.getElementById('edit_status').value = status;
                document.getElementById('edit_tags').value = tags;

                // Log untuk debugging
                console.log('Edit Template:', {
                    id,
                    nama,
                    isi,
                    kategori,
                    status,
                    tags,
                    'selected kategori': kategoriDropdown.value
                });
            });

            // Validasi form edit template
            const formEditTemplate = document.getElementById('formEditTemplate');
            if (formEditTemplate) {
                formEditTemplate.addEventListener('submit', function(event) {
                    const kategori = document.getElementById('edit_kategori_usg').value;
                    if (!kategori) {
                        event.preventDefault();
                        alert('Silakan pilih kategori terlebih dahulu');
                        return false;
                    }

                    // Log untuk debugging
                    console.log('Update Template:', {
                        id: document.getElementById('edit_id_template_usg').value,
                        nama: document.getElementById('edit_nama_template_usg').value,
                        isi: document.getElementById('edit_isi_template_usg').value,
                        kategori: document.getElementById('edit_kategori_usg').value,
                        status: document.getElementById('edit_status').value,
                        tags: document.getElementById('edit_tags').value
                    });

                    return true;
                });
            }
        }

        // Inisialisasi modal hapus
        const modalHapusTemplate = document.getElementById('modalHapusTemplate');
        if (modalHapusTemplate) {
            modalHapusTemplate.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                document.getElementById('hapus_id_template').value = id;
            });
        }
    });
</script>