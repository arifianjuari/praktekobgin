<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <div class="row align-items-center gy-2">
                        <div class="col-lg-5 col-md-12">
                            <h3 class="card-title mb-0 text-primary"><i class="fas fa-users me-2"></i>Data Pasien</h3>
                        </div>
                        <div class="col-lg-7 col-md-12">
                            <div class="d-flex flex-wrap justify-content-lg-end gap-2">
                                <form action="index.php?module=rekam_medis&action=cari_pasien" method="POST" class="d-flex flex-grow-1 flex-lg-grow-0">
                                    <div class="input-group">
                                        <input type="text" name="keyword" class="form-control" placeholder="Cari No. RM / Nama..." value="<?= isset($_POST['keyword']) ? htmlspecialchars($_POST['keyword']) : '' ?>" aria-label="Cari Pasien">
                                        <button type="submit" class="btn btn-outline-primary" title="Cari Pasien">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </form>
                                <a href="index.php?module=rekam_medis&action=tambah_pasien" class="btn btn-success" data-bs-toggle="tooltip" title="Tambah Pasien Baru">
                                    <i class="fas fa-plus"></i> <span class="d-none d-sm-inline">Tambah</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body px-0 pt-0">

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show rounded-0 border-0 border-start border-4 border-success mb-0" role="alert">
                            <?= $_SESSION['success'] ?>
                            <button type="button" class="btn-close pb-2" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show rounded-0 border-0 border-start border-4 border-danger mb-0" role="alert">
                            <?= $_SESSION['error'] ?>
                            <button type="button" class="btn-close pb-2" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tablePasien">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center ps-3" width="5%">No</th>
                                    <th width="15%" class="d-none d-md-table-cell">No. RM</th>
                                    <th width="25%">Nama Pasien</th>
                                    <th width="10%">Usia</th>
                                    <th width="15%" class="d-none d-lg-table-cell">Kecamatan</th>
                                    <th width="15%" class="d-none d-md-table-cell">Pekerjaan</th>
                                    <th width="15%" class="d-none d-lg-table-cell">Nomor Telepon</th>
                                    <th width="10%" class="text-center">Berikutnya Gratis</th>
                                    <th class="text-center pe-3" width="10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($pasien) > 0): ?>
                                    <?php foreach ($pasien as $index => $p): ?>
                                        <?php
                                        // Mendapatkan nama kecamatan
                                        $nama_kecamatan = '-';
                                        if (!empty($p['kd_kec'])) {
                                            foreach ($kecamatan as $kec) {
                                                if ($kec['kd_kec'] == $p['kd_kec']) {
                                                    $nama_kecamatan = $kec['nm_kec'];
                                                    break;
                                                }
                                            }
                                        }
                                        ?>
                                        <tr>
                                            <td class="text-center ps-3"><?= $offset + $index + 1 ?></td>
                                            <td class="d-none d-md-table-cell"><span class="badge bg-secondary fw-normal"><?= htmlspecialchars($p['no_rkm_medis']) ?></span></td>
                                            <td class="fw-medium"><?= htmlspecialchars($p['nm_pasien']) ?></td>
                                            <td><span class="badge bg-light text-dark fw-normal"><?= htmlspecialchars($p['umur'] ?? '-') ?> th</span></td>
                                            <td class="d-none d-lg-table-cell"><?= htmlspecialchars($nama_kecamatan) ?></td>
                                            <td class="d-none d-md-table-cell"><?= htmlspecialchars($p['pekerjaan'] ?? '-') ?></td>
                                            <td class="d-none d-lg-table-cell"><?= htmlspecialchars($p['no_tlp'] ?? '-') ?></td>
                                            <td class="text-center">
                                                <div class="form-check form-switch d-flex justify-content-center position-relative">
                                                    <input class="form-check-input toggle-gratis" type="checkbox" role="switch"
                                                        data-no-rm="<?= $p['no_rkm_medis'] ?>"
                                                        data-bs-toggle="tooltip" data-bs-placement="top" title="Klik untuk mengubah status"
                                                        <?= !empty($p['berikutnya_gratis']) ? 'checked' : '' ?>>
                                                    <div class="toggle-spinner position-absolute top-0 start-50 translate-middle-x d-none">
                                                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                                                            <span class="visually-hidden">Loading...</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center pe-3">
                                                <div class="d-inline-flex gap-1">
                                                    <a href="index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=<?= $p['no_rkm_medis'] ?>&source=data_pasien" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" title="Lihat Rekam Medis">
                                                        <i class="fas fa-file-medical"></i>
                                                    </a>
                                                    <?php if (!empty($p['no_tlp'])): ?>
                                                        <a href="https://wa.me/62<?= preg_replace('/[^0-9]/', '', $p['no_tlp']) ?>?text=<?= urlencode("Salam sehat, ibu " . $p['nm_pasien'] . ", saya admin dari praktek dokter ... ingin menyampaikan ...") ?>" target="_blank" class="btn btn-success btn-sm" data-bs-toggle="tooltip" title="WhatsApp">
                                                            <i class="fab fa-whatsapp"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <button type="button" class="btn btn-outline-danger btn-sm hapus-pasien"
                                                        data-bs-toggle="modal" data-bs-target="#hapusPasienModal"
                                                        data-no-rm="<?= $p['no_rkm_medis'] ?>"
                                                        data-nama="<?= htmlspecialchars($p['nm_pasien']) ?>" data-bs-toggle="tooltip" title="Hapus Pasien">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-5">
                                            <div class="d-flex flex-column align-items-center">
                                                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                                <h5 class="text-muted">Tidak ada data pasien ditemukan</h5>
                                                <?php if (!empty($search)): ?>
                                                    <p class="text-muted small">Tidak ada pasien yang cocok dengan kata kunci "<?= htmlspecialchars($search) ?>".</p>
                                                    <a href="index.php?module=rekam_medis&action=data_pasien" class="btn btn-sm btn-outline-secondary mt-2">Tampilkan Semua Pasien</a>
                                                <?php else: ?>
                                                    <p class="text-muted small">Silahkan tambahkan data pasien baru.</p>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="mt-4 px-3">
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center flex-wrap">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="index.php?module=rekam_medis&action=data_pasien&page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" aria-label="Previous">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item disabled">
                                            <span class="page-link" aria-hidden="true"><i class="fas fa-chevron-left"></i></span>
                                        </li>
                                    <?php endif; ?>

                                    <?php
                                    // Show limited page numbers with ellipsis
                                    $range = 2; // Number of pages around the current page
                                    $start = max(1, $page - $range);
                                    $end = min($total_pages, $page + $range);

                                    if ($start > 1) {
                                        echo '<li class="page-item"><a class="page-link" href="index.php?module=rekam_medis&action=data_pasien&page=1' . (!empty($search) ? '&search=' . urlencode($search) : '') . '">1</a></li>';
                                        if ($start > 2) {
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                    }

                                    for ($i = $start; $i <= $end; $i++): ?>
                                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                            <a class="page-link" href="index.php?module=rekam_medis&action=data_pasien&page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor;

                                    if ($end < $total_pages) {
                                        if ($end < $total_pages - 1) {
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                        echo '<li class="page-item"><a class="page-link" href="index.php?module=rekam_medis&action=data_pasien&page=' . $total_pages . (!empty($search) ? '&search=' . urlencode($search) : '') . '">' . $total_pages . '</a></li>';
                                    }
                                    ?>

                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="index.php?module=rekam_medis&action=data_pasien&page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" aria-label="Next">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item disabled">
                                            <span class="page-link" aria-hidden="true"><i class="fas fa-chevron-right"></i></span>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Hapus Pasien -->
<div class="modal fade" id="hapusPasienModal" tabindex="-1" aria-labelledby="hapusPasienModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="hapusPasienModalLabel"><i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Hapus Pasien</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-4">
                    <i class="fas fa-user-times fa-4x text-danger mb-3"></i>
                    <p class="mb-1">Apakah Anda yakin ingin menghapus data pasien:</p>
                    <p class="fw-bold fs-5" id="namaPasienHapus"></p>
                    <p class="text-muted small">Tindakan ini tidak dapat dibatalkan dan akan menghapus semua data terkait pasien ini.</p>
                </div>
                <form id="formHapusPasien" action="index.php?module=rekam_medis&action=hapusPasien" method="POST">
                    <input type="hidden" name="no_rkm_medis" id="noRmHapus">
                </form>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>
                </button>
                <button type="submit" form="formHapusPasien" class="btn btn-danger">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .toggle-gratis:checked {
        background-color: #198754;
        border-color: #198754;
    }

    .toggle-gratis {
        cursor: pointer;
        width: 2.5rem;
        height: 1.25rem;
    }

    .toast {
        z-index: 9999;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    .form-check-input:focus {
        box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
    }

    .toggle-spinner {
        top: 50% !important;
        transform: translate(-50%, -50%) !important;
    }

    /* Buat efek pulse pada toggle saat terjadi perubahan */
    @keyframes toggle-pulse {
        0% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.1);
        }

        100% {
            transform: scale(1);
        }
    }

    .toggle-success {
        animation: toggle-pulse 0.5s;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Initialize DataTable for better table functionality
        if (typeof $.fn.DataTable !== 'undefined') {
            $('#tablePasien').DataTable({
                "paging": false,
                "ordering": true,
                "info": false,
                "searching": false,
                "responsive": true,
                "language": {
                    "emptyTable": "Tidak ada data pasien"
                }
            });
        }

        // Script untuk modal hapus pasien
        const hapusPasienModal = document.getElementById('hapusPasienModal');
        if (hapusPasienModal) {
            hapusPasienModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const noRm = button.getAttribute('data-no-rm');
                const nama = button.getAttribute('data-nama');

                document.getElementById('noRmHapus').value = noRm;
                document.getElementById('namaPasienHapus').textContent = `${nama} (${noRm})`;
            });

            // Tambahkan event listener untuk form submit
            const formHapusPasien = document.getElementById('formHapusPasien');
            formHapusPasien.addEventListener('submit', function(e) {
                e.preventDefault();

                // Submit form
                this.submit();
            });
        }

        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);

        // Add hover effect to table rows
        const tableRows = document.querySelectorAll('#tablePasien tbody tr');
        tableRows.forEach(row => {
            row.addEventListener('mouseover', function() {
                this.classList.add('bg-light');
            });
            row.addEventListener('mouseout', function() {
                this.classList.remove('bg-light');
            });
        });

        // Format nomor telepon untuk tautan WhatsApp
        document.querySelectorAll('a[href^="https://wa.me/"]').forEach(function(link) {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                // Periksa apakah nomor telepon valid (minimal 10 digit)
                const phoneMatch = href.match(/wa\.me\/(\d+)/);
                if (phoneMatch && phoneMatch[1].length < 10) {
                    e.preventDefault();
                    alert('Nomor telepon tidak valid atau tidak lengkap.');
                }
            });
        });

        // Handler untuk toggle berikutnya_gratis
        document.querySelectorAll('.toggle-gratis').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const noRm = this.getAttribute('data-no-rm');
                const isChecked = this.checked ? 1 : 0;
                const checkboxElement = this; // Simpan referensi ke checkbox
                const spinnerElement = this.parentNode.querySelector('.toggle-spinner');

                // Tampilkan loading state
                checkboxElement.disabled = true;
                spinnerElement.classList.remove('d-none');

                // Tambahkan log untuk debugging
                console.log(`Mengirim request: no_rkm_medis=${noRm}, berikutnya_gratis=${isChecked}`);

                // Kirim data ke server
                fetch('index.php?module=rekam_medis&action=toggleBerikutnyaGratis', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `no_rkm_medis=${noRm}&berikutnya_gratis=${isChecked}`
                    })
                    .then(response => {
                        console.log('Response status:', response.status);
                        return response.json();
                    })
                    .then(data => {
                        console.log('Response data:', data);
                        if (data.status === 'success') {
                            // Tampilkan notifikasi kecil
                            const toast = document.createElement('div');
                            toast.classList.add('toast', 'position-fixed', 'bottom-0', 'end-0', 'm-3');
                            toast.setAttribute('role', 'alert');
                            toast.setAttribute('aria-live', 'assertive');
                            toast.setAttribute('aria-atomic', 'true');
                            toast.innerHTML = `
                            <div class="toast-header ${isChecked ? 'bg-success' : 'bg-secondary'} text-white">
                                <strong class="me-auto">Status Pasien</strong>
                                <small>baru saja</small>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                            </div>
                            <div class="toast-body">
                                Status "Berikutnya Gratis" untuk pasien 
                                <strong>${noRm}</strong> telah diubah menjadi 
                                <span class="badge ${isChecked ? 'bg-success' : 'bg-secondary'}">${isChecked ? 'AKTIF' : 'TIDAK AKTIF'}</span>
                            </div>
                        `;
                            document.body.appendChild(toast);

                            // Inisialisasi dan tampilkan toast
                            const bsToast = new bootstrap.Toast(toast);
                            bsToast.show();

                            // Tambahkan efek pulse pada toggle
                            checkboxElement.classList.add('toggle-success');
                            setTimeout(() => {
                                checkboxElement.classList.remove('toggle-success');
                            }, 500);

                            // Hapus toast setelah ditutup
                            toast.addEventListener('hidden.bs.toast', function() {
                                toast.remove();
                            });
                        } else {
                            // Kembalikan checkbox ke status sebelumnya jika gagal
                            checkboxElement.checked = !isChecked;
                            alert('Gagal mengubah status: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Kembalikan checkbox ke status sebelumnya jika gagal
                        checkboxElement.checked = !isChecked;
                        alert('Terjadi kesalahan, silakan coba lagi');
                    })
                    .finally(() => {
                        // Kembalikan state normal
                        checkboxElement.disabled = false;
                        spinnerElement.classList.add('d-none');
                    });
            });
        });
    });
</script>