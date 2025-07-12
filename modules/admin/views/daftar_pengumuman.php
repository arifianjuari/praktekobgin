<?php if (!defined('APP_IN')) {
    define('APP_IN', true);
}
?>
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?= htmlspecialchars($page_title ?? 'Pengumuman'); ?></h5>
                        <a href="javascript:history.back()" class="btn btn-sm btn-light">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($pengumuman)): ?>
                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                            <?php foreach ($pengumuman as $item): ?>
                                <div class="col">
                                    <div class="card h-100 pengumuman-card">
                                        <div class="card-body">
                                            <h5 class="card-title"><?= htmlspecialchars($item['judul']); ?></h5>
                                            <p class="pengumuman-date mb-2">
                                                <i class="bi bi-calendar-event"></i>
                                                <?= date('d-m-Y', strtotime($item['tanggal_mulai'])); ?>
                                                <?php if (!empty($item['tanggal_berakhir'])): ?>
                                                    s/d <?= date('d-m-Y', strtotime($item['tanggal_berakhir'])); ?>
                                                <?php endif; ?>
                                            </p>
                                            <div class="pengumuman-content mb-3">
                                                <?= $item['isi_pengumuman']; ?>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-primary view-pengumuman"
                                                data-bs-toggle="modal" data-bs-target="#pengumumanModal"
                                                data-id="<?= $item['id_pengumuman']; ?>"
                                                data-judul="<?= htmlspecialchars($item['judul']); ?>"
                                                data-isi="<?= htmlspecialchars($item['isi_pengumuman']); ?>"
                                                data-mulai="<?= date('d-m-Y', strtotime($item['tanggal_mulai'])); ?>"
                                                data-berakhir="<?= !empty($item['tanggal_berakhir']) ? date('d-m-Y', strtotime($item['tanggal_berakhir'])) : '-'; ?>"
                                                data-penulis="<?= htmlspecialchars($item['username'] ?? 'Admin'); ?>">
                                                <i class="bi bi-eye"></i> Baca Selengkapnya
                                            </button>
                                        </div>
                                        <div class="card-footer text-muted">
                                            <small>Oleh: <?= htmlspecialchars($item['username'] ?? 'Admin'); ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Tidak ada pengumuman aktif saat ini.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Pengumuman -->
<div class="modal fade" id="pengumumanModal" tabindex="-1" aria-labelledby="pengumumanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="pengumumanModalLabel">Detail Pengumuman</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h4 id="modal-judul" class="mb-3 text-primary"></h4>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong><i class="bi bi-calendar-event"></i> Tanggal:</strong> <span id="modal-tanggal"></span></p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p><strong><i class="bi bi-person"></i> Oleh:</strong> <span id="modal-penulis"></span></p>
                    </div>
                </div>
                <div class="card border">
                    <div class="card-body">
                        <div id="modal-isi" class="pengumuman-content-full"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>