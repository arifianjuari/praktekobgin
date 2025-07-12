<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i class="bi bi-calendar-week text-primary me-2"></i>Jadwal Praktek</h2>
        <div>
            <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#filterSection">
                <i class="bi bi-funnel"></i> Filter
            </button>
        </div>
    </div>

    <div class="collapse show" id="filterSection">
        <div class="card shadow-sm mb-4 rounded-4">
            <div class="card-body">
                <form id="filterForm" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="hari" class="form-label">Hari</label>
                        <select class="form-select" id="hari" name="hari">
                            <option value="">Semua Hari</option>
                            <option value="Senin" <?= isset($_GET['hari']) && $_GET['hari'] == 'Senin' ? 'selected' : '' ?>>Senin</option>
                            <option value="Selasa" <?= isset($_GET['hari']) && $_GET['hari'] == 'Selasa' ? 'selected' : '' ?>>Selasa</option>
                            <option value="Rabu" <?= isset($_GET['hari']) && $_GET['hari'] == 'Rabu' ? 'selected' : '' ?>>Rabu</option>
                            <option value="Kamis" <?= isset($_GET['hari']) && $_GET['hari'] == 'Kamis' ? 'selected' : '' ?>>Kamis</option>
                            <option value="Jumat" <?= isset($_GET['hari']) && $_GET['hari'] == 'Jumat' ? 'selected' : '' ?>>Jumat</option>
                            <option value="Sabtu" <?= isset($_GET['hari']) && $_GET['hari'] == 'Sabtu' ? 'selected' : '' ?>>Sabtu</option>
                            <option value="Minggu" <?= isset($_GET['hari']) && $_GET['hari'] == 'Minggu' ? 'selected' : '' ?>>Minggu</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="layanan" class="form-label">Jenis Layanan</label>
                        <select class="form-select" id="layanan" name="layanan">
                            <option value="">Semua Layanan</option>
                            <?php if (!empty($jenis_layanan)): ?>
                                <?php foreach ($jenis_layanan as $layanan): ?>
                                    <option value="<?= htmlspecialchars($layanan) ?>" <?= isset($_GET['layanan']) && $_GET['layanan'] == $layanan ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($layanan) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="w-100 text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search me-1"></i> Terapkan Filter
                            </button>
                            <a href="<?= $base_path ?>/jadwal.php" class="btn btn-outline-secondary ms-2">
                                <i class="bi bi-x-circle me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if (empty($jadwal)): ?>
        <div class="alert alert-info rounded-4 shadow-sm">
            <div class="d-flex align-items-center">
                <i class="bi bi-info-circle-fill fs-3 me-3"></i>
                <div>
                    <h5 class="alert-heading">Tidak Ada Data</h5>
                    <p class="mb-0">Tidak ada jadwal praktek yang ditemukan dengan filter yang dipilih.</p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php
        // Kelompokkan jadwal berdasarkan hari
        $jadwal_by_day = [];
        foreach ($jadwal as $j) {
            if (!isset($jadwal_by_day[$j['Hari']])) {
                $jadwal_by_day[$j['Hari']] = [];
            }
            $jadwal_by_day[$j['Hari']][] = $j;
        }
        
        // Urutan hari
        $hari_order = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
        
        // Urutkan array berdasarkan urutan hari
        $jadwal_sorted = [];
        foreach ($hari_order as $hari) {
            if (isset($jadwal_by_day[$hari])) {
                $jadwal_sorted[$hari] = $jadwal_by_day[$hari];
            }
        }
        ?>

        <?php foreach ($jadwal_sorted as $hari => $jadwal_hari): ?>
            <div class="card shadow-sm mb-4 rounded-4 border-0">
                <div class="card-header bg-light py-3 rounded-top-4">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-calendar-day text-primary me-2"></i>
                        <span class="text-primary"><?= htmlspecialchars($hari) ?></span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Jam Praktek</th>
                                    <th>Jenis Layanan</th>
                                    <th>Kuota</th>
                                    <th>Harga</th>
                                    <th>Durasi</th>
                                    <th>Deskripsi</th>
                                    <th>Persiapan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($jadwal_hari as $j): ?>
                                    <tr>
                                        <td>
                                            <span class="fw-bold"><?= htmlspecialchars(substr($j['Jam_Mulai'],0,5)) ?> - <?= htmlspecialchars(substr($j['Jam_Selesai'],0,5)) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($j['Jenis_Layanan'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($j['Kuota_Pasien'] ?? '-') ?></td>
                                        <td><?= !is_null($j['harga']) ? 'Rp ' . number_format($j['harga'],0,',','.') : '-' ?></td>
                                        <td><?= !is_null($j['durasi']) ? htmlspecialchars($j['durasi']) . ' menit' : '-' ?></td>
                                        <td>
                                            <?php if (!empty($j['deskripsi'])): ?>
                                                <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#deskripsiModal<?= $j['ID_Jadwal_Rutin'] ?>">
                                                    <i class="bi bi-info-circle"></i> Detail
                                                </button>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($j['persiapan'])): ?>
                                                <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#persiapanModal<?= $j['ID_Jadwal_Rutin'] ?>">
                                                    <i class="bi bi-list-check"></i> Lihat
                                                </button>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Modal untuk deskripsi -->
        <?php foreach ($jadwal as $j): ?>
            <?php if (!empty($j['deskripsi'])): ?>
                <div class="modal fade" id="deskripsiModal<?= $j['ID_Jadwal_Rutin'] ?>" tabindex="-1" aria-labelledby="deskripsiModalLabel<?= $j['ID_Jadwal_Rutin'] ?>" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-info text-white">
                                <h5 class="modal-title" id="deskripsiModalLabel<?= $j['ID_Jadwal_Rutin'] ?>">Deskripsi Layanan</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="mb-0"><?= nl2br(htmlspecialchars($j['deskripsi'])) ?></p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <!-- Modal untuk persiapan -->
        <?php foreach ($jadwal as $j): ?>
            <?php if (!empty($j['persiapan'])): ?>
                <div class="modal fade" id="persiapanModal<?= $j['ID_Jadwal_Rutin'] ?>" tabindex="-1" aria-labelledby="persiapanModalLabel<?= $j['ID_Jadwal_Rutin'] ?>" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-warning text-dark">
                                <h5 class="modal-title" id="persiapanModalLabel<?= $j['ID_Jadwal_Rutin'] ?>">Persiapan Pasien</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="mb-0"><?= nl2br(htmlspecialchars($j['persiapan'])) ?></p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
});
</script>
