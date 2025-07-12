<?php
require_once __DIR__ . '/../../config/config.php';

// Query untuk mengambil data atensi
$query = "SELECT 
            pmrk.*,
            p.nama_pasien,
            p.no_rekam_medis,
            p.catatan_pasien
          FROM penilaian_medis_ralan_kandungan pmrk
          JOIN pasien p ON pmrk.no_rekam_medis = p.no_rekam_medis
          WHERE pmrk.atensi = 'Ya' 
          OR pmrk.tanggal_kontrol IS NOT NULL
          ORDER BY 
            CASE 
              WHEN pmrk.tanggal_kontrol IS NOT NULL THEN pmrk.tanggal_kontrol
              ELSE pmrk.created_at 
            END DESC";

$result = mysqli_query($conn, $query);

// Handle jika query error
if (!$result) {
    die("Error: " . mysqli_error($conn));
}

$page_title = "Daftar Atensi Pasien";
require_once __DIR__ . '/../../template/header.php';
?>

<div class="container-fluid">
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
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="tabelAtensi">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>No. Rekam Medis</th>
                                    <th>Nama Pasien</th>
                                    <th>Tanggal Periksa</th>
                                    <th>Tanggal Kontrol</th>
                                    <th>Status Atensi</th>
                                    <th>Keterangan</th>
<th>Catatan Pasien</th>
<th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                while ($row = mysqli_fetch_assoc($result)) :
                                    $tanggal_periksa = date('d-m-Y', strtotime($row['created_at']));
                                    $tanggal_kontrol = $row['tanggal_kontrol'] ? date('d-m-Y', strtotime($row['tanggal_kontrol'])) : '-';
                                ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($row['no_rekam_medis']) ?></td>
                                        <td><?= htmlspecialchars($row['nama_pasien']) ?></td>
                                        <td><?= $tanggal_periksa ?></td>
                                        <td><?= $tanggal_kontrol ?></td>
                                        <td>
                                            <?php if ($row['atensi'] == 'Ya'): ?>
                                                <span class="badge bg-danger">Perlu Atensi</span>
                                            <?php endif; ?>
                                            <?php if ($row['tanggal_kontrol']): ?>
                                                <span class="badge bg-info">Jadwal Kontrol</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($row['keterangan'] ?? '-') ?></td>
<td><?= htmlspecialchars($row['catatan_pasien'] ?? '-') ?></td>
<td>
                                            <a href="<?= $base_url ?>/index.php?module=rekam_medis&action=detail_pasien&no_rekam_medis=<?= $row['no_rekam_medis'] ?>"
                                                class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i> Detail
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
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
    });
</script>

<?php
require_once __DIR__ . '/../../template/footer.php';
?>