<?php
// View untuk menampilkan daftar atensi
$status_filter = isset($_GET['status_atensi']) ? $_GET['status_atensi'] : '';
?>

<div class="container-fluid">
    <!-- Notifikasi -->
    <?php if (isset($_GET['success']) && $_GET['success'] == 'update_success'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Berhasil!</strong> Status atensi berhasil diperbarui.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif (isset($_GET['success']) && $_GET['success'] == 'follow_up_updated'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Berhasil!</strong> Status follow up pasien berhasil diperbarui.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Gagal!</strong> <?= htmlspecialchars($_GET['error'] == 'no_rawat_invalid' ? 'No rawat tidak valid' : $_GET['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        Daftar Atensi Pasien
                    </h3>
                </div>
                <div class="card-body">
                    <!-- Filter Status Atensi -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <form method="GET" class="d-flex gap-2">
                                <input type="hidden" name="module" value="rekam_medis">
                                <input type="hidden" name="action" value="daftar_atensi">
                                <select name="status_atensi" class="form-select" style="width: 200px;">
                                    <option value="">Semua Status</option>
                                    <option value="1" <?= $status_filter === '1' ? 'selected' : '' ?>>Waspada</option>
                                    <option value="0" <?= $status_filter === '0' ? 'selected' : '' ?>>Jadwal Kontrol</option>
                                </select>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-filter"></i> Filter
                                </button>
                                <?php if (!empty($status_filter)): ?>
                                    <a href="index.php?module=rekam_medis&action=daftar_atensi" class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i> Reset
                                    </a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>

                    <style>
    /* Kecilkan font tabel atensi */
    #tabelAtensi, #tabelAtensi th, #tabelAtensi td {
        font-size: 13px;
    }
</style>
<div class="table-responsive">
                        <table class="table table-bordered table-hover" id="tabelAtensi">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Pasien</th>
                                    <th>Catatan Pasien</th>
                                    <th>Tanggal Periksa</th>
                                    <th>Tanggal Kontrol</th>
                                    <th>Status Atensi</th>
                                    <th>Diagnosis</th>
                                    <th>Tata Laksana</th>
                                    <th>Follow Up</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                foreach ($result as $row) :
                                    // Skip jika tidak sesuai filter
                                    if ($status_filter === '1' && $row['atensi'] != '1') continue;
                                    if ($status_filter === '0' && !($row['atensi'] == '0' || ($row['atensi'] === NULL && !empty($row['tanggal_kontrol']) && $row['tanggal_kontrol'] != '0000-00-00'))) continue;

                                    $tanggal_periksa = date('d-m-Y', strtotime($row['tanggal']));
                                    $tanggal_kontrol = $row['tanggal_kontrol'] ? date('d-m-Y', strtotime($row['tanggal_kontrol'])) : '-';
                                ?>
                                    <tr>
                                        <td><?= $no++ ?></td>

                                        <td><?= htmlspecialchars($row['nama_pasien']) ?></td>
                                        <td><?= htmlspecialchars($row['catatan_pasien'] ?? '-') ?></td>
                                        <td><?= $tanggal_periksa ?></td>
                                        <td><?= $tanggal_kontrol ?></td>
                                        <td>
                                            <?php if ($row['atensi'] == 1): ?>
                                                <span class="badge bg-danger">Waspada</span>
                                            <?php elseif ($row['atensi'] == 0): ?>
                                                <span class="badge bg-info">Jadwal Kontrol</span>
                                            <?php elseif ($row['atensi'] === NULL && !empty($row['tanggal_kontrol']) && $row['tanggal_kontrol'] != '0000-00-00'): ?>
                                                <span class="badge bg-info">Jadwal Kontrol</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Tidak Ada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($row['diagnosis'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($row['keterangan'] ?? '-') ?></td>
                                        <td class="text-center">
                                            <?php if (isset($row['sudah_follow_up']) && $row['sudah_follow_up'] == 1): ?>
                                                <a href="modules/rekam_medis/update_follow_up.php?no_rawat=<?= $row['no_rawat'] ?>&status=0" 
                                                   class="btn btn-success btn-sm" data-bs-toggle="tooltip" title="Sudah difollow up">
                                                    <i class="bi bi-check-lg"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="modules/rekam_medis/update_follow_up.php?no_rawat=<?= $row['no_rawat'] ?>&status=1" 
                                                   class="btn btn-outline-secondary btn-sm" data-bs-toggle="tooltip" title="Belum difollow up">
                                                    <i class="bi bi-dash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=<?= $row['no_rkm_medis'] ?>"
                                                    class="btn btn-primary btn-sm btn-icon" data-bs-toggle="tooltip"
                                                    title="Lihat Rekam Medis">
                                                    <i class="bi bi-journal-medical"></i>
                                                </a>
                                                <!-- Tombol selesai -->
                                                <a href="modules/rekam_medis/update_atensi.php?no_rawat=<?= $row['no_rawat'] ?>"
                                                    class="btn btn-warning btn-sm btn-icon"
                                                    data-bs-toggle="tooltip"
                                                    title="Selesai"
                                                    onclick="return confirm('Apakah Anda yakin ingin menandai data ini sebagai selesai?');">
                                                    <i class="bi bi-check-square"></i>
                                                </a>
                                                <!-- Tombol WhatsApp -->
                                                <?php if (!empty($row['no_tlp'])): ?>
                                                    <?php
                                                    // Format nomor telepon untuk WhatsApp
                                                    $no_tlp_clean = preg_replace('/[^0-9]/', '', $row['no_tlp']);
                                                    if (substr($no_tlp_clean, 0, 1) === '0') {
                                                        $no_tlp_clean = '62' . substr($no_tlp_clean, 1);
                                                    } elseif (substr($no_tlp_clean, 0, 2) !== '62') {
                                                        $no_tlp_clean = '62' . $no_tlp_clean;
                                                    }

                                                    // Buat pesan untuk WhatsApp
                                                    $pesan = "Salam sehat, Ibu " . $row['nama_pasien'] . ",\n\n";
                                                    if ($row['atensi'] == 1) {
                                                        $pesan .= "Kami dari praktek dokter ingin mengingatkan tentang...... .\n\n";
                                                    } elseif (!empty($row['tanggal_kontrol']) && $row['tanggal_kontrol'] != '0000-00-00') {
                                                        $tanggal_kontrol_format = date('d-m-Y', strtotime($row['tanggal_kontrol']));
                                                        $pesan .= "Kami dari praktek dokter ingin mengingatkan jadwal kontrol Anda pada tanggal " . $tanggal_kontrol_format . ".\n\n";
                                                    }
                                                    $pesan .= "Terima kasih.";
                                                    $pesan_encoded = urlencode($pesan);

                                                    // Buat URL WhatsApp
                                                    $whatsapp_url = "https://wa.me/" . $no_tlp_clean . "?text=" . $pesan_encoded;
                                                    ?>
                                                    <a href="<?= $whatsapp_url ?>" target="_blank"
                                                        class="btn btn-success btn-sm btn-icon"
                                                        data-bs-toggle="tooltip"
                                                        title="Hubungi via WhatsApp">
                                                        <i class="bi bi-whatsapp"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
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

<script>
    $(document).ready(function() {
        $('#tabelAtensi').DataTable({
            "responsive": true,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json"
            },
            "order": [
                [3, "desc"]
            ], // Urutkan berdasarkan tanggal periksa secara descending
            "pageLength": 25
        });

        // Inisialisasi tooltip
        $('[data-bs-toggle="tooltip"]').tooltip();
    });
</script>