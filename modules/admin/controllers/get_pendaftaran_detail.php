<?php
session_start();
require_once '../config/database.php';

// Cek role admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo '<div class="alert alert-danger">Akses tidak diizinkan</div>';
    exit;
}

// Ambil ID pendaftaran dari parameter URL
$id_pendaftaran = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($id_pendaftaran)) {
    echo '<div class="alert alert-danger">ID Pendaftaran tidak valid</div>';
    exit;
}

try {
    // Query untuk mengambil detail pendaftaran
    $query = "
        SELECT 
            p.*,
            tp.Nama_Tempat,
            tp.Alamat_Lengkap as Alamat_Tempat,
            d.Nama_Dokter,
            d.Spesialisasi,
            jr.Hari,
            jr.Jam_Mulai,
            jr.Jam_Selesai,
            jr.Jenis_Layanan
        FROM 
            pendaftaran p
        LEFT JOIN 
            tempat_praktek tp ON p.ID_Tempat_Praktek = tp.ID_Tempat_Praktek
        LEFT JOIN 
            dokter d ON p.ID_Dokter = d.ID_Dokter
        LEFT JOIN 
            jadwal_rutin jr ON p.ID_Jadwal = jr.ID_Jadwal_Rutin
        WHERE 
            p.ID_Pendaftaran = :id_pendaftaran
    ";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id_pendaftaran', $id_pendaftaran);
    $stmt->execute();
    $pendaftaran = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pendaftaran) {
        echo '<div class="alert alert-warning">Data pendaftaran tidak ditemukan</div>';
        exit;
    }

    // Format tanggal lahir
    $tanggal_lahir = isset($pendaftaran['tgl_lahir']) ? date('d/m/Y', strtotime($pendaftaran['tgl_lahir'])) : '-';

    // Format waktu pendaftaran
    $waktu_pendaftaran = date('d/m/Y H:i', strtotime($pendaftaran['Waktu_Pendaftaran']));

    // Tampilkan detail pendaftaran
?>
    <div class="row">
        <div class="col-md-6">
            <h5 class="border-bottom pb-2 mb-3">Informasi Pendaftaran</h5>
            <table class="table table-borderless">
                <tr>
                    <td width="40%"><strong>ID Pendaftaran</strong></td>
                    <td>: <?= htmlspecialchars($pendaftaran['ID_Pendaftaran']) ?></td>
                </tr>
                <tr>
                    <td><strong>Waktu Pendaftaran</strong></td>
                    <td>: <?= $waktu_pendaftaran ?></td>
                </tr>
                <tr>
                    <td><strong>Status</strong></td>
                    <td>: <span class="badge <?= getStatusBadgeClass($pendaftaran['Status_Pendaftaran']) ?>"><?= htmlspecialchars($pendaftaran['Status_Pendaftaran']) ?></span></td>
                </tr>
            </table>

            <h5 class="border-bottom pb-2 mb-3 mt-4">Informasi Jadwal</h5>
            <table class="table table-borderless">
                <tr>
                    <td width="40%"><strong>Hari Praktek</strong></td>
                    <td>: <?= htmlspecialchars($pendaftaran['Hari']) ?></td>
                </tr>
                <tr>
                    <td><strong>Jam Praktek</strong></td>
                    <td>: <?= htmlspecialchars($pendaftaran['Jam_Mulai']) ?> - <?= htmlspecialchars($pendaftaran['Jam_Selesai']) ?></td>
                </tr>
                <tr>
                    <td><strong>Jenis Layanan</strong></td>
                    <td>: <?= htmlspecialchars($pendaftaran['Jenis_Layanan']) ?></td>
                </tr>
                <tr>
                    <td><strong>Tempat Praktek</strong></td>
                    <td>: <?= htmlspecialchars($pendaftaran['Nama_Tempat']) ?></td>
                </tr>
                <tr>
                    <td><strong>Alamat Tempat</strong></td>
                    <td>: <?= htmlspecialchars($pendaftaran['Alamat_Tempat']) ?></td>
                </tr>
                <tr>
                    <td><strong>Dokter</strong></td>
                    <td>: <?= htmlspecialchars($pendaftaran['Nama_Dokter']) ?> (<?= htmlspecialchars($pendaftaran['Spesialisasi']) ?>)</td>
                </tr>
            </table>
        </div>
        <div class="col-md-6">
            <h5 class="border-bottom pb-2 mb-3">Informasi Pasien</h5>
            <table class="table table-borderless">
                <tr>
                    <td width="40%"><strong>Nama Pasien</strong></td>
                    <td>: <?= htmlspecialchars($pendaftaran['nm_pasien']) ?></td>
                </tr>
                <?php if (isset($pendaftaran['tgl_lahir'])): ?>
                    <tr>
                        <td><strong>Tanggal Lahir</strong></td>
                        <td>: <?= $tanggal_lahir ?></td>
                    </tr>
                <?php endif; ?>
                <?php if (isset($pendaftaran['jk'])): ?>
                    <tr>
                        <td><strong>Jenis Kelamin</strong></td>
                        <td>: <?= htmlspecialchars($pendaftaran['jk']) ?></td>
                    </tr>
                <?php endif; ?>
                <?php if (isset($pendaftaran['no_tlp'])): ?>
                    <tr>
                        <td><strong>Nomor Telepon</strong></td>
                        <td>: <?= htmlspecialchars($pendaftaran['no_tlp']) ?></td>
                    </tr>
                <?php endif; ?>
                <?php if (isset($pendaftaran['alamat'])): ?>
                    <tr>
                        <td><strong>Alamat</strong></td>
                        <td>: <?= htmlspecialchars($pendaftaran['alamat']) ?></td>
                    </tr>
                <?php endif; ?>
            </table>

            <?php if (isset($pendaftaran['Keluhan']) && !empty($pendaftaran['Keluhan'])): ?>
                <h5 class="border-bottom pb-2 mb-3 mt-4">Keluhan</h5>
                <div class="p-3 bg-light rounded">
                    <?= nl2br(htmlspecialchars($pendaftaran['Keluhan'])) ?>
                </div>
            <?php endif; ?>

            <div class="mt-4">
                <h5 class="border-bottom pb-2 mb-3">Update Status</h5>
                <div class="d-flex justify-content-between">
                    <div class="btn-group" role="group">
                        <?php if ($pendaftaran['Status_Pendaftaran'] !== 'Menunggu Konfirmasi'): ?>
                            <button type="button" class="btn btn-outline-warning"
                                onclick="updateStatusDirect('<?= $pendaftaran['ID_Pendaftaran'] ?>', 'Menunggu Konfirmasi')"
                                data-bs-toggle="tooltip" title="Ubah ke Menunggu Konfirmasi">
                                <i class="bi bi-hourglass me-1"></i> Menunggu
                            </button>
                        <?php endif; ?>

                        <?php if ($pendaftaran['Status_Pendaftaran'] !== 'Dikonfirmasi'): ?>
                            <button type="button" class="btn btn-outline-success"
                                onclick="updateStatusDirect('<?= $pendaftaran['ID_Pendaftaran'] ?>', 'Dikonfirmasi')"
                                data-bs-toggle="tooltip" title="Konfirmasi Pendaftaran">
                                <i class="bi bi-check-circle me-1"></i> Konfirmasi
                            </button>
                        <?php endif; ?>

                        <?php if ($pendaftaran['Status_Pendaftaran'] !== 'Selesai'): ?>
                            <button type="button" class="btn btn-outline-info"
                                onclick="updateStatusDirect('<?= $pendaftaran['ID_Pendaftaran'] ?>', 'Selesai')"
                                data-bs-toggle="tooltip" title="Tandai Selesai">
                                <i class="bi bi-flag me-1"></i> Selesai
                            </button>
                        <?php endif; ?>

                        <?php if ($pendaftaran['Status_Pendaftaran'] !== 'Dibatalkan'): ?>
                            <button type="button" class="btn btn-outline-danger"
                                onclick="updateStatusDirect('<?= $pendaftaran['ID_Pendaftaran'] ?>', 'Dibatalkan')"
                                data-bs-toggle="tooltip" title="Batalkan Pendaftaran">
                                <i class="bi bi-x-circle me-1"></i> Batalkan
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Terjadi kesalahan: ' . $e->getMessage() . '</div>';
}

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
?>

<script>
    // Inisialisasi tooltip
    document.addEventListener('DOMContentLoaded', function() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>