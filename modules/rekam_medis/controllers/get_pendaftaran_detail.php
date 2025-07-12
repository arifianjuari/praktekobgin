<?php
require_once '../../../config/database.php';

header('Content-Type: text/html');

if (!isset($_GET['id'])) {
    echo '<div class="alert alert-danger">ID Pendaftaran tidak ditemukan</div>';
    exit;
}

$id_pendaftaran = $_GET['id'];

try {
    $query = "
        SELECT 
            p.ID_Pendaftaran,
            pas.no_rkm_medis,
            p.nm_pasien as Nama_Pasien,
            p.tgl_lahir,
            p.jk,
            p.alamat,
            p.no_tlp,
            p.Keluhan,
            p.Status_Pendaftaran,
            p.Waktu_Pendaftaran,
            p.Waktu_Perkiraan,
            p.voucher_code,
            jr.Hari,
            jr.Jam_Mulai,
            jr.Jam_Selesai,
            jr.Jenis_Layanan,
            tp.Nama_Tempat,
            tp.Alamat as Alamat_Tempat,
            d.Nama_Dokter,
            d.Spesialisasi
        FROM 
            pendaftaran p
        JOIN pasien pas ON p.no_ktp = pas.no_ktp
        JOIN jadwal_rutin jr ON p.ID_Jadwal = jr.ID_Jadwal_Rutin
        JOIN tempat_praktek tp ON p.ID_Tempat_Praktek = tp.ID_Tempat_Praktek
        JOIN dokter d ON p.ID_Dokter = d.ID_Dokter
        WHERE p.ID_Pendaftaran = :id
    ";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id_pendaftaran);
    $stmt->execute();

    $pendaftaran = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pendaftaran) {
        echo '<div class="alert alert-warning">Data pendaftaran tidak ditemukan</div>';
        exit;
    }

    // Format data untuk tampilan
    $jenis_kelamin = $pendaftaran['jk'] === 'L' ? 'Laki-laki' : 'Perempuan';
    $tgl_lahir = date('d-m-Y', strtotime($pendaftaran['tgl_lahir']));
    $waktu_daftar = date('d-m-Y H:i', strtotime($pendaftaran['Waktu_Pendaftaran']));
    $waktu_perkiraan = !empty($pendaftaran['Waktu_Perkiraan']) ?
        date('d-m-Y H:i', strtotime($pendaftaran['Waktu_Perkiraan'])) : '-';

    // Hitung umur dari tanggal lahir
    $birthDate = new DateTime($pendaftaran['tgl_lahir']);
    $today = new DateTime('today');
    $umur = $birthDate->diff($today)->y;

    // Tampilkan detail pendaftaran
?>
    <div class="container-fluid p-0">
        <div class="row mb-3">
            <div class="col-md-6">
                <h6 class="text-primary"><i class="bi bi-person-badge me-2"></i>Data Pasien</h6>
                <table class="table table-sm">
                    <tr>
                        <td width="140">No. Rekam Medis</td>
                        <td width="10">:</td>
                        <td><strong><?= htmlspecialchars($pendaftaran['no_rkm_medis']) ?></strong></td>
                    </tr>
                    <tr>
                        <td>Nama Pasien</td>
                        <td>:</td>
                        <td><strong><?= htmlspecialchars($pendaftaran['Nama_Pasien']) ?></strong></td>
                    </tr>
                    <tr>
                        <td>Tanggal Lahir</td>
                        <td>:</td>
                        <td><?= $tgl_lahir ?> (<?= $umur ?> tahun)</td>
                    </tr>
                    <tr>
                        <td>Jenis Kelamin</td>
                        <td>:</td>
                        <td><?= $jenis_kelamin ?></td>
                    </tr>
                    <tr>
                        <td>Alamat</td>
                        <td>:</td>
                        <td><?= nl2br(htmlspecialchars($pendaftaran['alamat'])) ?></td>
                    </tr>
                    <tr>
                        <td>No. Telepon</td>
                        <td>:</td>
                        <td><?= htmlspecialchars($pendaftaran['no_tlp']) ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="text-primary"><i class="bi bi-calendar-check me-2"></i>Informasi Kunjungan</h6>
                <table class="table table-sm">
                    <tr>
                        <td width="140">ID Pendaftaran</td>
                        <td width="10">:</td>
                        <td><strong><?= htmlspecialchars($pendaftaran['ID_Pendaftaran']) ?></strong></td>
                    </tr>
                    <tr>
                        <td>Dokter</td>
                        <td>:</td>
                        <td><?= htmlspecialchars($pendaftaran['Nama_Dokter']) ?> (<?= htmlspecialchars($pendaftaran['Spesialisasi']) ?>)</td>
                    </tr>
                    <tr>
                        <td>Tempat Praktek</td>
                        <td>:</td>
                        <td><?= htmlspecialchars($pendaftaran['Nama_Tempat']) ?></td>
                    </tr>
                    <tr>
                        <td>Alamat Praktek</td>
                        <td>:</td>
                        <td><?= htmlspecialchars($pendaftaran['Alamat_Tempat']) ?></td>
                    </tr>
                    <tr>
                        <td>Jadwal</td>
                        <td>:</td>
                        <td><?= htmlspecialchars($pendaftaran['Hari']) ?>, <?= htmlspecialchars($pendaftaran['Jam_Mulai']) ?> - <?= htmlspecialchars($pendaftaran['Jam_Selesai']) ?></td>
                    </tr>
                    <tr>
                        <td>Jenis Layanan</td>
                        <td>:</td>
                        <td><?= htmlspecialchars($pendaftaran['Jenis_Layanan']) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <h6 class="text-primary"><i class="bi bi-info-circle me-2"></i>Status Pendaftaran</h6>
                <table class="table table-sm">
                    <tr>
                        <td width="140">Status</td>
                        <td width="10">:</td>
                        <td>
                            <span class="badge <?= getStatusBadgeClass($pendaftaran['Status_Pendaftaran']) ?>">
                                <?= htmlspecialchars($pendaftaran['Status_Pendaftaran']) ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td>Waktu Daftar</td>
                        <td>:</td>
                        <td><?= $waktu_daftar ?></td>
                    </tr>
                    <tr>
                        <td>Perkiraan Waktu</td>
                        <td>:</td>
                        <td><?= $waktu_perkiraan ?></td>
                    </tr>
                    <?php if (!empty($pendaftaran['voucher_code'])): ?>
                        <tr>
                            <td>Kode Voucher</td>
                            <td>:</td>
                            <td><span class="badge bg-info"><?= htmlspecialchars($pendaftaran['voucher_code']) ?></span></td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="text-primary"><i class="bi bi-clipboard2-pulse me-2"></i>Keluhan</h6>
                <div class="card">
                    <div class="card-body p-3">
                        <?php if (!empty($pendaftaran['Keluhan'])): ?>
                            <?= nl2br(htmlspecialchars($pendaftaran['Keluhan'])) ?>
                        <?php else: ?>
                            <em class="text-muted">Tidak ada keluhan yang dicatat</em>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-3">
            <div class="row">
                <div class="col">
                    <button class="btn btn-outline-primary btn-sm"
                        onclick="printDetail('<?= $pendaftaran['ID_Pendaftaran'] ?>')">
                        <i class="bi bi-printer me-1"></i> Cetak Detail
                    </button>

                    <?php if (!empty($pendaftaran['no_tlp'])): ?>
                        <?php
                        // Bersihkan nomor telepon dari karakter non-numerik
                        $no_tlp_clean = preg_replace('/[^0-9]/', '', $pendaftaran['no_tlp']);

                        // Pastikan format nomor telepon benar (awali dengan 62)
                        if (substr($no_tlp_clean, 0, 1) == '0') {
                            $no_tlp_clean = '62' . substr($no_tlp_clean, 1);
                        } elseif (substr($no_tlp_clean, 0, 2) != '62') {
                            $no_tlp_clean = '62' . $no_tlp_clean;
                        }

                        // Buat pesan untuk WhatsApp
                        $pesan = "Halo " . $pendaftaran['Nama_Pasien'] . ", ";
                        $pesan .= "pendaftaran Anda dengan ID " . $pendaftaran['ID_Pendaftaran'] . " ";
                        $pesan .= "pada tanggal " . $waktu_daftar . " ";
                        $pesan .= "saat ini berstatus " . $pendaftaran['Status_Pendaftaran'] . ".";

                        if ($pendaftaran['Status_Pendaftaran'] === 'Dikonfirmasi') {
                            $pesan .= " Silakan datang pada " . $pendaftaran['Hari'] . ", ";
                            $pesan .= "pukul " . $pendaftaran['Jam_Mulai'] . " di " . $pendaftaran['Nama_Tempat'] . ".";
                        }

                        // Encode pesan untuk URL
                        $pesan_encoded = urlencode($pesan);

                        // Buat URL WhatsApp
                        $whatsapp_url = "https://wa.me/" . $no_tlp_clean . "?text=" . $pesan_encoded;
                        ?>
                        <a href="<?= $whatsapp_url ?>" target="_blank" class="btn btn-success btn-sm">
                            <i class="bi bi-whatsapp me-1"></i> Hubungi via WhatsApp
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function printDetail(id) {
            // Implementasi cetak detail bisa ditambahkan nanti
            alert('Fitur cetak detail untuk ID ' + id + ' akan segera tersedia');
        }
    </script>

<?php
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Terjadi kesalahan saat mengambil data: ' . $e->getMessage() . '</div>';
}

// Fungsi untuk menentukan class badge berdasarkan status
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