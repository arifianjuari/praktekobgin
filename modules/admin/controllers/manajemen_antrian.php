<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/config.php';

$page_title = "Manajemen Antrian Pasien";
// Start output buffering
ob_start();

// Cek role admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Filter dan pengurutan
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'waktu_desc'; // Default: waktu terbaru
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Filter default: tampilkan semua kecuali yang dibatalkan dan selesai
$default_filter = true; // Flag untuk menandai apakah menggunakan filter default
if (isset($_GET['clear_filter']) && $_GET['clear_filter'] == '1') {
    $default_filter = false;
}

// Fungsi untuk mengurutkan hari mulai dari Senin
function getDayOrder($day)
{
    $days = [
        'Senin' => 1,
        'Selasa' => 2,
        'Rabu' => 3,
        'Kamis' => 4,
        'Jumat' => 5,
        'Sabtu' => 6,
        'Minggu' => 7
    ];

    return isset($days[$day]) ? $days[$day] : 8; // Jika hari tidak dikenal, letakkan di akhir
}

// Pastikan koneksi database tersedia
ensureDBConnection();

// Query untuk mengambil data pendaftaran
try {
    $query = "
        SELECT 
            p.ID_Pendaftaran,
            p.nm_pasien as Nama_Pasien,
            p.Status_Pendaftaran,
            p.Waktu_Pendaftaran,
            p.Waktu_Perkiraan,
            jr.Hari,
            jr.Jam_Mulai,
            jr.Jam_Selesai,
            ml.nama_layanan AS nama_layanan,
            tp.Nama_Tempat,
            tp.ID_Tempat_Praktek,
            d.Nama_Dokter
        FROM 
            pendaftaran p
        JOIN jadwal_rutin jr ON p.ID_Jadwal = jr.ID_Jadwal_Rutin
        JOIN menu_layanan ml ON jr.ID_Layanan = ml.id_layanan
        JOIN tempat_praktek tp ON p.ID_Tempat_Praktek = tp.ID_Tempat_Praktek
        JOIN dokter d ON p.ID_Dokter = d.ID_Dokter
        WHERE 1=1
    ";

    // Tambahkan filter status jika dipilih
    if (!empty($status_filter)) {
        $query .= " AND p.Status_Pendaftaran = :status";
    }
    // Jika menggunakan filter default, kecualikan status Dibatalkan dan Selesai
    else if ($default_filter) {
        $query .= " AND p.Status_Pendaftaran NOT IN ('Dibatalkan', 'Selesai')";
    }

    // Tambahkan pencarian jika ada
    if (!empty($search)) {
        $query .= " AND (p.nm_pasien LIKE :search OR p.ID_Pendaftaran LIKE :search)";
    }

    // Tambahkan pengurutan berdasarkan hari dan tempat praktek
    $query .= " ORDER BY CASE jr.Hari 
                WHEN 'Senin' THEN 1 
                WHEN 'Selasa' THEN 2 
                WHEN 'Rabu' THEN 3 
                WHEN 'Kamis' THEN 4 
                WHEN 'Jumat' THEN 5 
                WHEN 'Sabtu' THEN 6 
                WHEN 'Minggu' THEN 7 
                ELSE 8 END, tp.Nama_Tempat, jr.Jam_Mulai ASC";

    $stmt = $conn->prepare($query);

    // Bind parameter filter jika ada
    if (!empty($status_filter)) {
        $stmt->bindParam(':status', $status_filter);
    }

    // Bind parameter pencarian jika ada
    if (!empty($search)) {
        $search_param = "%$search%";
        $stmt->bindParam(':search', $search_param);
    }

    $stmt->execute();
    $antrian = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Kelompokkan antrian berdasarkan hari dan tempat
    $antrian_grouped = [];
    foreach ($antrian as $a) {
        $hari = $a['Hari'];
        $tempat = $a['Nama_Tempat'];
        if (!isset($antrian_grouped[$hari])) {
            $antrian_grouped[$hari] = [];
        }
        if (!isset($antrian_grouped[$hari][$tempat])) {
            $antrian_grouped[$hari][$tempat] = [];
        }
        $antrian_grouped[$hari][$tempat][] = $a;
    }
    
    // Sort each group by Waktu_Perkiraan
    foreach ($antrian_grouped as $hari => &$tempat_groups) {
        foreach ($tempat_groups as $tempat => &$antrian_list) {
            usort($antrian_list, function($a, $b) {
                if (empty($a['Waktu_Perkiraan']) && empty($b['Waktu_Perkiraan'])) {
                    return 0;
                } elseif (empty($a['Waktu_Perkiraan'])) {
                    return 1;
                } elseif (empty($b['Waktu_Perkiraan'])) {
                    return -1;
                }
                return strtotime($a['Waktu_Perkiraan']) - strtotime($b['Waktu_Perkiraan']);
            });
        }
    }
    unset($tempat_groups);
    unset($antrian_list);

    // Debug
    if (empty($antrian)) {
        error_log("Query tidak mengembalikan data: " . $query);
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $antrian = [];
    $antrian_grouped = [];
}

// Hitung jumlah antrian berdasarkan status
try {
    $query_count = "
        SELECT 
            Status_Pendaftaran, 
            COUNT(*) as jumlah 
        FROM 
            pendaftaran 
        GROUP BY 
            Status_Pendaftaran
    ";
    $stmt_count = $conn->query($query_count);
    $status_counts = [];

    while ($row = $stmt_count->fetch(PDO::FETCH_ASSOC)) {
        $status_counts[$row['Status_Pendaftaran']] = $row['jumlah'];
    }

    // Hitung total antrian (kecuali Dibatalkan dan Selesai)
    $total_antrian = 0;
    foreach ($status_counts as $status => $count) {
        if ($status !== 'Dibatalkan' && $status !== 'Selesai') {
            $total_antrian += $count;
        }
    }

    // Hitung total keseluruhan
    $total_keseluruhan = array_sum($status_counts);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $status_counts = [];
    $total_antrian = 0;
    $total_keseluruhan = 0;
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Manajemen Antrian Pasien</h4>
                </div>
                <div class="card-body">
                    <!-- Statistik Antrian -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card shadow-sm">
                                <div class="card-body p-3">
                                    <div class="row">
                                        <div class="col-md-2 col-6 text-center mb-2 mb-md-0">
                                            <div class="bg-light rounded p-2 stat-box">
                                                <h6 class="mb-0">Total Aktif</h6>
                                                <h3 class="mb-0"><?= $total_antrian ?></h3>
                                                <small>Pendaftaran Aktif</small>
                                            </div>
                                        </div>
                                        <div class="col-md-2 col-6 text-center mb-2 mb-md-0">
                                            <div class="bg-warning bg-opacity-25 rounded p-2 stat-box">
                                                <h6 class="mb-0">Menunggu</h6>
                                                <h3 class="mb-0"><?= isset($status_counts['Menunggu Konfirmasi']) ? $status_counts['Menunggu Konfirmasi'] : 0 ?></h3>
                                                <small>Konfirmasi</small>
                                            </div>
                                        </div>
                                        <div class="col-md-2 col-6 text-center mb-2 mb-md-0">
                                            <div class="bg-success bg-opacity-25 rounded p-2 stat-box">
                                                <h6 class="mb-0">Dikonfirmasi</h6>
                                                <h3 class="mb-0"><?= isset($status_counts['Dikonfirmasi']) ? $status_counts['Dikonfirmasi'] : 0 ?></h3>
                                                <small>Pendaftaran</small>
                                            </div>
                                        </div>
                                        <div class="col-md-2 col-6 text-center mb-2 mb-md-0">
                                            <div class="bg-info bg-opacity-25 rounded p-2 stat-box">
                                                <h6 class="mb-0">Selesai</h6>
                                                <h3 class="mb-0"><?= isset($status_counts['Selesai']) ? $status_counts['Selesai'] : 0 ?></h3>
                                                <small>Pendaftaran</small>
                                            </div>
                                        </div>
                                        <div class="col-md-2 col-6 text-center mb-2 mb-md-0">
                                            <div class="bg-secondary bg-opacity-25 rounded p-2 stat-box">
                                                <h6 class="mb-0">Total Semua</h6>
                                                <h3 class="mb-0"><?= $total_keseluruhan ?></h3>
                                                <small>Semua Pendaftaran</small>
                                            </div>
                                        </div>
                                        <div class="col-md-2 col-6 text-center mb-2 mb-md-0">
                                            <div class="bg-danger bg-opacity-25 rounded p-2 stat-box">
                                                <h6 class="mb-0">Dibatalkan</h6>
                                                <h3 class="mb-0"><?= isset($status_counts['Dibatalkan']) ? $status_counts['Dibatalkan'] : 0 ?></h3>
                                                <small>Pendaftaran</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter dan Pengurutan -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <form method="GET" class="d-flex gap-2 filter-form">
                                <div class="search-container">
                                    <input type="text" name="search" class="form-control form-control-lg" placeholder="Cari nama pasien atau ID pendaftaran..." value="<?= htmlspecialchars($search) ?>">
                                </div>
                                <div class="filter-container">
                                    <select name="status" class="form-select form-select-lg">
                                        <option value="" <?= $status_filter === '' ? 'selected' : '' ?>>Semua Status</option>
                                        <option value="Menunggu Konfirmasi" <?= $status_filter === 'Menunggu Konfirmasi' ? 'selected' : '' ?>>Menunggu Konfirmasi</option>
                                        <option value="Dikonfirmasi" <?= $status_filter === 'Dikonfirmasi' ? 'selected' : '' ?>>Dikonfirmasi</option>
                                        <option value="Dibatalkan" <?= $status_filter === 'Dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                                        <option value="Selesai" <?= $status_filter === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                    </select>
                                </div>
                                <div class="filter-container">
                                    <select name="sort" class="form-select form-select-lg">
                                        <option value="waktu_desc" <?= $sort_by === 'waktu_desc' ? 'selected' : '' ?>>Waktu Terbaru</option>
                                        <option value="waktu_asc" <?= $sort_by === 'waktu_asc' ? 'selected' : '' ?>>Waktu Terlama</option>
                                        <option value="nama_asc" <?= $sort_by === 'nama_asc' ? 'selected' : '' ?>>Nama (A-Z)</option>
                                        <option value="nama_desc" <?= $sort_by === 'nama_desc' ? 'selected' : '' ?>>Nama (Z-A)</option>
                                        <option value="status_asc" <?= $sort_by === 'status_asc' ? 'selected' : '' ?>>Status (A-Z)</option>
                                        <option value="status_desc" <?= $sort_by === 'status_desc' ? 'selected' : '' ?>>Status (Z-A)</option>
                                        <option value="hari_asc" <?= $sort_by === 'hari_asc' ? 'selected' : '' ?>>Hari (Senin-Minggu)</option>
                                    </select>
                                </div>
                                <div class="button-container">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-search"></i> Cari
                                    </button>
                                </div>
                                <?php if (!empty($search) || !empty($status_filter) || $sort_by !== 'waktu_desc'): ?>
                                    <div class="button-container">
                                        <a href="manajemen_antrian.php" class="btn btn-secondary btn-lg">
                                            <i class="bi bi-x-circle"></i> Reset
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <?php if ($default_filter): ?>
                                    <div class="button-container">
                                        <a href="manajemen_antrian.php?clear_filter=1" class="btn btn-info btn-lg">
                                            <i class="bi bi-eye"></i> Tampilkan Semua
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="button-container">
                                        <a href="manajemen_antrian.php" class="btn btn-warning btn-lg">
                                            <i class="bi bi-filter"></i> Sembunyikan
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </form>
                        </div>
                        <div class="col-md-4 text-end">
                            <button type="button" class="btn btn-success btn-lg" onclick="refreshPage()">
                                <i class="bi bi-arrow-clockwise"></i> Refresh Data
                            </button>
                        </div>
                    </div>

                    <?php if (empty($antrian_grouped)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Tidak ada data antrian saat ini.
                        </div>
                    <?php else: ?>
                        <?php foreach ($antrian_grouped as $hari => $tempat_list): ?>
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="bi bi-calendar-day me-2"></i>
                                        <?= htmlspecialchars($hari) ?>
                                    </h5>
                                </div>
                                <?php foreach ($tempat_list as $tempat => $antrian_list): ?>
                                    <div class="card-body border-bottom">
                                        <h6 class="text-muted mb-3">
                                            <i class="bi bi-hospital me-2"></i>
                                            <?= htmlspecialchars($tempat) ?>
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>No. Antrian</th>
                                                        <th>Waktu Daftar</th>
                                                        <th>Nama Pasien</th>
                                                        <th>Jam Praktek</th>
                                                        <th>Dokter</th>
                                                        <th>Status</th>
                                                        <th>Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($antrian_list as $index => $a): ?>
                                                        <tr>
                                                            <td><?= $index + 1 ?></td>
                                                            <td>
                                                                <?= date('d/m/Y H:i', strtotime($a['Waktu_Pendaftaran'])) ?>
                                                                <?php if (!empty($a['Waktu_Perkiraan'])): ?>
                                                                <br><small class="text-muted">Perkiraan: <?= date('H:i', strtotime($a['Waktu_Perkiraan'])) ?></small>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?= htmlspecialchars($a['Nama_Pasien']) ?></td>
                                                            <td><?= htmlspecialchars($a['Jam_Mulai']) ?> - <?= htmlspecialchars($a['Jam_Selesai']) ?></td>
                                                            <td><?= htmlspecialchars($a['Nama_Dokter']) ?></td>
                                                            <td>
                                                                <span class="badge <?= getStatusBadgeClass($a['Status_Pendaftaran']) ?>">
                                                                    <?= htmlspecialchars($a['Status_Pendaftaran']) ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <div class="btn-group" role="group">
                                                                    <?php if ($a['Status_Pendaftaran'] !== 'Menunggu Konfirmasi'): ?>
                                                                        <button type="button" class="btn btn-sm btn-outline-warning"
                                                                            onclick="updateStatusDirect('<?= $a['ID_Pendaftaran'] ?>', 'Menunggu Konfirmasi')"
                                                                            data-bs-toggle="tooltip" title="Ubah ke Menunggu Konfirmasi">
                                                                            <i class="bi bi-hourglass"></i>
                                                                        </button>
                                                                    <?php endif; ?>

                                                                    <?php if ($a['Status_Pendaftaran'] !== 'Dikonfirmasi'): ?>
                                                                        <button type="button" class="btn btn-sm btn-outline-success"
                                                                            onclick="updateStatusDirect('<?= $a['ID_Pendaftaran'] ?>', 'Dikonfirmasi')"
                                                                            data-bs-toggle="tooltip" title="Konfirmasi Pendaftaran">
                                                                            <i class="bi bi-check-circle"></i>
                                                                        </button>
                                                                    <?php endif; ?>

                                                                    <?php if ($a['Status_Pendaftaran'] !== 'Selesai'): ?>
                                                                        <button type="button" class="btn btn-sm btn-outline-info"
                                                                            onclick="updateStatusDirect('<?= $a['ID_Pendaftaran'] ?>', 'Selesai')"
                                                                            data-bs-toggle="tooltip" title="Tandai Selesai">
                                                                            <i class="bi bi-flag"></i>
                                                                        </button>
                                                                    <?php endif; ?>

                                                                    <?php if ($a['Status_Pendaftaran'] !== 'Dibatalkan'): ?>
                                                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                                            onclick="updateStatusDirect('<?= $a['ID_Pendaftaran'] ?>', 'Dibatalkan')"
                                                                            data-bs-toggle="tooltip" title="Batalkan Pendaftaran">
                                                                            <i class="bi bi-x-circle"></i>
                                                                        </button>
                                                                    <?php endif; ?>

                                                                    <button type="button" class="btn btn-sm btn-info"
                                                                        onclick="viewDetail('<?= $a['ID_Pendaftaran'] ?>')"
                                                                        data-bs-toggle="tooltip" title="Lihat Detail">
                                                                        <i class="bi bi-eye"></i>
                                                                    </button>

                                                                    <button type="button" class="btn btn-sm btn-primary"
                                                                        onclick="editPendaftaran('<?= $a['ID_Pendaftaran'] ?>')"
                                                                        data-bs-toggle="tooltip" title="Edit Pendaftaran">
                                                                        <i class="bi bi-pencil"></i>
                                                                    </button>

                                                                    <button type="button" class="btn btn-sm btn-danger"
                                                                        onclick="deletePendaftaran('<?= $a['ID_Pendaftaran'] ?>')"
                                                                        data-bs-toggle="tooltip" title="Hapus Pendaftaran">
                                                                        <i class="bi bi-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Update Status -->
<div class="modal fade" id="updateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Update Status Pendaftaran</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="updateForm">
                <div class="modal-body">
                    <input type="hidden" name="id_pendaftaran" id="id_pendaftaran">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="Menunggu Konfirmasi">Menunggu Konfirmasi</option>
                            <option value="Dikonfirmasi">Dikonfirmasi</option>
                            <option value="Dibatalkan">Dibatalkan</option>
                            <option value="Selesai">Selesai</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Batal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail Pendaftaran -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Detail Pendaftaran</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p>Memuat data...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Pendaftaran -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Edit Pendaftaran</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="editContent">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p>Memuat form edit...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Batal
                </button>
                <button type="button" class="btn btn-primary" id="btnSaveEdit">
                    <i class="bi bi-save me-1"></i>Simpan Perubahan
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function updateStatus(id, currentStatus) {
        document.getElementById('id_pendaftaran').value = id;
        document.getElementById('status').value = currentStatus;
        new bootstrap.Modal(document.getElementById('updateModal')).show();
    }

    function updateStatusDirect(id, newStatus) {
        if (confirm(`Apakah Anda yakin ingin mengubah status menjadi "${newStatus}"?`)) {
            const formData = new FormData();
            formData.append('id_pendaftaran', id);
            formData.append('status', newStatus);

            fetch('update_status.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Gagal mengupdate status: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat mengupdate status');
                });
        }
    }

    function viewDetail(id) {
        const detailModal = new bootstrap.Modal(document.getElementById('detailModal'));
        detailModal.show();

        // Ambil detail pendaftaran
        fetch(`get_pendaftaran_detail.php?id=${id}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById('detailContent').innerHTML = data;
            })
            .catch(error => {
                document.getElementById('detailContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Terjadi kesalahan saat memuat data: ${error.message}
                    </div>
                `;
            });
    }

    function editPendaftaran(id) {
        const editModal = new bootstrap.Modal(document.getElementById('editModal'));
        editModal.show();

        // Ambil data pendaftaran untuk diedit
        fetch(`get_pendaftaran_edit.php?id=${id}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById('editContent').innerHTML = data;

                // Tambahkan event listener untuk tombol simpan
                document.getElementById('btnSaveEdit').addEventListener('click', function() {
                    const editForm = document.getElementById('formEditPendaftaran');
                    if (editForm) {
                        const formData = new FormData(editForm);

                        fetch('update_pendaftaran.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(result => {
                                if (result.success) {
                                    alert('Data pendaftaran berhasil diperbarui');
                                    location.reload();
                                } else {
                                    alert('Gagal memperbarui data: ' + result.message);
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('Terjadi kesalahan saat memperbarui data');
                            });
                    }
                });
            })
            .catch(error => {
                document.getElementById('editContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Terjadi kesalahan saat memuat form edit: ${error.message}
                    </div>
                `;
            });
    }

    function deletePendaftaran(id) {
        if (confirm('Apakah Anda yakin ingin menghapus data pendaftaran ini? Tindakan ini tidak dapat dibatalkan.')) {
            const formData = new FormData();
            formData.append('id_pendaftaran', id);

            fetch('delete_pendaftaran.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Data pendaftaran berhasil dihapus');
                        location.reload();
                    } else {
                        alert('Gagal menghapus data: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat menghapus data');
                });
        }
    }

    // Handle form submission
    document.getElementById('updateForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch('update_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Gagal mengupdate status: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat mengupdate status');
            });
    });

    function refreshPage() {
        location.reload();
    }

    // Inisialisasi tooltip
    document.addEventListener('DOMContentLoaded', function() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>

<?php
function getStatusBadgeClass($status)
{
    switch ($status) {
        case 'Menunggu Konfirmasi':
            return 'bg-warning text-dark';
        case 'Dikonfirmasi':
            return 'bg-success';
        case 'Dibatalkan':
            return 'bg-danger';
        case 'Selesai':
            return 'bg-info';
        default:
            return 'bg-secondary';
    }
}

$content = ob_get_clean();

// Additional CSS
$additional_css = "
    .card {
        border: none;
        border-radius: 10px;
    }
    .table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }
    .badge {
        font-size: 0.875rem;
        padding: 0.5em 0.75em;
    }
    .btn-sm {
        padding: 0.25rem 0.5rem;
        margin-right: 0.25rem;
    }
    .modal-header {
        border-bottom: 0;
    }
    .modal-footer {
        border-top: 0;
    }
    .form-control-lg, .form-select-lg, .btn-lg {
        height: 50px;
        font-size: 1rem;
        padding-left: 15px;
        padding-right: 15px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .form-select-lg {
        padding-top: 0;
        padding-bottom: 0;
    }
    .btn-lg {
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 120px;
    }
    .btn-lg i {
        margin-right: 5px;
    }
    .filter-form {
        flex-wrap: wrap;
        align-items: center;
    }
    .search-container {
        width: 300px;
    }
    .filter-container {
        width: 200px;
    }
    .button-container {
        display: flex;
    }
    .stat-box {
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .stat-box h6 {
        font-size: 0.9rem;
        font-weight: 600;
    }
    .stat-box h3 {
        font-size: 1.8rem;
        font-weight: 700;
    }
    .stat-box small {
        font-size: 0.75rem;
    }
    @media (max-width: 1200px) {
        .search-container {
            width: 250px;
        }
        .filter-container {
            width: 180px;
        }
    }
    @media (max-width: 992px) {
        .search-container {
            width: 100%;
            margin-bottom: 10px;
        }
        .filter-container {
            width: 48%;
            margin-bottom: 10px;
        }
        .button-container {
            margin-bottom: 10px;
        }
    }
    @media (max-width: 768px) {
        .form-control-lg, .form-select-lg, .btn-lg {
            font-size: 0.9rem;
            height: 45px;
            padding-left: 10px;
            padding-right: 10px;
        }
        .btn-lg {
            min-width: 100px;
        }
        .filter-container {
            width: 100%;
        }
        .stat-box {
            margin-bottom: 10px;
        }
    }
";

// Additional JavaScript if needed
$additional_js = "";

include_once __DIR__ . '/../../../template/layout.php';
?>