<?php
// Widget Pengumuman
// File ini akan diinclude di halaman beranda atau halaman lain yang memerlukan widget pengumuman

// Gunakan koneksi PDO persistent dari config/koneksi.php
require_once __DIR__ . '/../config/koneksi.php';
$pdo = getPDOConnection();

// Update status pengumuman yang sudah melewati tanggal_berakhir
// Hanya jalankan update ini sekali per hari menggunakan session
$update_key = 'pengumuman_status_updated_' . date('Y-m-d');
if (!isset($_SESSION[$update_key])) {
    $current_date = date('Y-m-d');

    // Update status_aktif menjadi 0 untuk pengumuman yang sudah melewati tanggal_berakhir
    $update_query = "UPDATE pengumuman 
                    SET status_aktif = 0 
                    WHERE status_aktif = 1 
                    AND tanggal_berakhir IS NOT NULL 
                    AND tanggal_berakhir < ?";

    try {
        $update_stmt = $pdo->prepare($update_query);
        $update_stmt->execute([$current_date]);
        $affected_rows = $update_stmt->rowCount();

        if ($affected_rows > 0) {
            error_log(date('Y-m-d H:i:s') . " - " . $affected_rows . " pengumuman dinonaktifkan karena sudah melewati tanggal_berakhir.");
        }
    } catch (Exception $e) {
        error_log("Error mengupdate status pengumuman: " . $e->getMessage());
    }

    // Tandai bahwa update sudah dilakukan hari ini
    $_SESSION[$update_key] = true;
}

// Ambil pengumuman aktif (maksimal 2)
$pengumuman_widget = [];
$current_date = date('Y-m-d');

$query = "SELECT p.*, u.username 
          FROM pengumuman p 
          LEFT JOIN users u ON p.dibuat_oleh = u.id 
          WHERE p.status_aktif = 1 
          AND p.tanggal_mulai <= ? 
          AND (p.tanggal_berakhir IS NULL OR p.tanggal_berakhir >= ?)
          ORDER BY p.created_at DESC
          LIMIT 2";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute([$current_date, $current_date]);
    $result = $stmt->fetchAll();

    if ($result && count($result) > 0) {
        foreach ($result as $row) {
            $pengumuman_widget[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error mengambil data pengumuman: " . $e->getMessage());
}
// Tidak perlu tutup koneksi manual, sudah di-handle oleh shutdown function.
?>

<?php if (count($pengumuman_widget) > 0): ?>
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-megaphone"></i> Pengumuman Terbaru</h5>
            <a href="<?php echo $base_url; ?>/pengumuman.php" class="btn btn-sm btn-light">Lihat Semua</a>
        </div>
        <div class="card-body p-0">
            <?php if (count($pengumuman_widget) === 2): ?>
                <!-- Tampilan untuk 2 pengumuman -->
                <div class="row g-0">
                    <?php foreach ($pengumuman_widget as $item): ?>
                        <div class="col-md-6 border-end">
                            <div class="p-2 h-100 d-flex flex-column" style="padding: 0.5rem !important;">
                                <h6 class="card-title" style="color: #D35400;"><?php echo htmlspecialchars($item['judul']); ?></h6>
                                <div class="pengumuman-preview mb-2 flex-grow-1" style="overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; line-clamp: 2;">
                                    <?php
                                    // Strip HTML tags dan potong teks
                                    $preview = strip_tags($item['isi_pengumuman']);
                                    echo strlen($preview) > 80 ? substr($preview, 0, 80) . '...' : $preview;
                                    ?>
                                </div>
                                <div class="d-flex justify-content-end align-items-center mt-1">
                                    <p class="text-muted small mb-0">
                                        <i class="bi bi-calendar-event"></i>
                                        <?php echo date('d-m-Y', strtotime($item['tanggal_mulai'])); ?>
                                        <?php if (!empty($item['tanggal_berakhir'])): ?>
                                            s/d <?php echo date('d-m-Y', strtotime($item['tanggal_berakhir'])); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary view-pengumuman"
                                    data-bs-toggle="modal" data-bs-target="#pengumumanModal"
                                    data-id="<?php echo $item['id_pengumuman']; ?>"
                                    data-judul="<?php echo htmlspecialchars($item['judul']); ?>"
                                    data-isi="<?php echo htmlspecialchars($item['isi_pengumuman']); ?>"
                                    data-mulai="<?php echo date('d-m-Y', strtotime($item['tanggal_mulai'])); ?>"
                                    data-berakhir="<?php echo !empty($item['tanggal_berakhir']) ? date('d-m-Y', strtotime($item['tanggal_berakhir'])) : '-'; ?>"
                                    data-penulis="<?php echo htmlspecialchars($item['username'] ?? 'Admin'); ?>">
                                    <i class="bi bi-eye"></i> Baca Selengkapnya
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- Tampilan untuk 1 pengumuman -->
                <div class="list-group list-group-flush">
                    <?php foreach ($pengumuman_widget as $item): ?>
                        <div class="list-group-item list-group-item-action p-2">
                            <h6 class="mb-1" style="color: #D35400;"><?php echo htmlspecialchars($item['judul']); ?></h6>
                            <div class="pengumuman-preview mb-1" style="overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; line-clamp: 2;">
                                <?php
                                // Strip HTML tags dan potong teks
                                $preview = strip_tags($item['isi_pengumuman']);
                                echo strlen($preview) > 100 ? substr($preview, 0, 100) . '...' : $preview;
                                ?>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <small class="text-muted">
                                    <i class="bi bi-calendar-event"></i> <?php echo date('d-m-Y', strtotime($item['tanggal_mulai'])); ?>
                                </small>
                                <button type="button" class="btn btn-sm btn-outline-primary view-pengumuman"
                                data-bs-toggle="modal" data-bs-target="#pengumumanModal"
                                data-id="<?php echo $item['id_pengumuman']; ?>"
                                data-judul="<?php echo htmlspecialchars($item['judul']); ?>"
                                data-isi="<?php echo htmlspecialchars($item['isi_pengumuman']); ?>"
                                data-mulai="<?php echo date('d-m-Y', strtotime($item['tanggal_mulai'])); ?>"
                                data-berakhir="<?php echo !empty($item['tanggal_berakhir']) ? date('d-m-Y', strtotime($item['tanggal_berakhir'])) : '-'; ?>"
                                data-penulis="<?php echo htmlspecialchars($item['username'] ?? 'Admin'); ?>">
                                <i class="bi bi-eye"></i> Baca Selengkapnya
                            </button>
                                </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Detail Pengumuman -->
    <div class="modal fade" id="pengumumanModal" tabindex="-1" aria-labelledby="pengumumanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pengumumanModalLabel">Detail Pengumuman</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                </div>
                <div class="modal-body">
                    <h4 id="modal-judul" class="mb-3"></h4>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Tanggal:</strong> <span id="modal-tanggal"></span></p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p><strong>Oleh:</strong> <span id="modal-penulis"></span></p>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <div id="modal-isi"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Tampilkan detail pengumuman pada modal
            $('.view-pengumuman').click(function() {
                const judul = $(this).data('judul');
                const isi = $(this).data('isi');
                const mulai = $(this).data('mulai');
                const berakhir = $(this).data('berakhir');
                const penulis = $(this).data('penulis');

                let tanggalText = mulai;
                if (berakhir !== '-') {
                    tanggalText += ' s/d ' + berakhir;
                }

                $('#modal-judul').text(judul);
                $('#modal-isi').html(isi);
                $('#modal-tanggal').text(tanggalText);
                $('#modal-penulis').text(penulis);
            });
        });
    </script>
<?php endif; ?>