<?php
require_once 'config.php';

if (!isset($_GET['no_rawat'])) {
    echo '<div class="alert alert-danger">No. Rawat tidak ditemukan</div>';
    exit;
}

$no_rawat = $_GET['no_rawat'];

try {
    // Query untuk mengambil data pemeriksaan
    $query = "SELECT 
        DATE_FORMAT(p.tgl_perawatan, '%d/%m/%Y') as tanggal,
        TIME_FORMAT(p.jam_rawat, '%H:%i') as jam,
        p.suhu_tubuh,
        p.tensi,
        p.nadi,
        p.respirasi,
        p.spo2,
        p.gcs,
        p.keluhan,
        p.pemeriksaan,
        p.alergi,
        p.penilaian,
        p.rtl,
        p.instruksi,
        peg.nama as petugas
    FROM pemeriksaan_ranap p
    LEFT JOIN pegawai peg ON p.nip = peg.nik
    WHERE p.no_rawat = :no_rawat
    ORDER BY p.tgl_perawatan DESC, p.jam_rawat DESC";

    $stmt = $conn_db1->prepare($query);
    $stmt->bindParam(':no_rawat', $no_rawat, PDO::PARAM_STR);
    $stmt->execute();
    $pemeriksaan = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($pemeriksaan) > 0) {
        echo '<div class="accordion" id="accordionPemeriksaan">';
        foreach ($pemeriksaan as $index => $p) {
            $accordionId = "pemeriksaan" . $index;
?>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button <?php echo $index === 0 ? '' : 'collapsed'; ?>" type="button"
                        data-bs-toggle="collapse" data-bs-target="#<?php echo $accordionId; ?>">
                        Pemeriksaan <?php echo $p['tanggal']; ?> - <?php echo $p['jam']; ?>
                        oleh <?php echo htmlspecialchars($p['petugas']); ?>
                    </button>
                </h2>
                <div id="<?php echo $accordionId; ?>"
                    class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>"
                    data-bs-parent="#accordionPemeriksaan">
                    <div class="accordion-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <h6 class="fw-bold">Tanda Vital:</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td width="150">Suhu</td>
                                        <td>: <?php echo $p['suhu_tubuh']; ?> °C</td>
                                    </tr>
                                    <tr>
                                        <td>Tensi</td>
                                        <td>: <?php echo $p['tensi']; ?> mmHg</td>
                                    </tr>
                                    <tr>
                                        <td>Nadi</td>
                                        <td>: <?php echo $p['nadi']; ?> x/menit</td>
                                    </tr>
                                    <tr>
                                        <td>Respirasi</td>
                                        <td>: <?php echo $p['respirasi']; ?> x/menit</td>
                                    </tr>
                                    <tr>
                                        <td>SpO2</td>
                                        <td>: <?php echo $p['spo2']; ?> %</td>
                                    </tr>
                                    <tr>
                                        <td>GCS</td>
                                        <td>: <?php echo $p['gcs']; ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold">Keluhan:</h6>
                                <p><?php echo nl2br(htmlspecialchars($p['keluhan'])); ?></p>

                                <h6 class="fw-bold mt-3">Pemeriksaan:</h6>
                                <p><?php echo nl2br(htmlspecialchars($p['pemeriksaan'])); ?></p>

                                <?php if (!empty($p['alergi'])): ?>
                                    <h6 class="fw-bold mt-3">Alergi:</h6>
                                    <p><?php echo nl2br(htmlspecialchars($p['alergi'])); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="col-12">
                                <h6 class="fw-bold">Penilaian:</h6>
                                <p><?php echo nl2br(htmlspecialchars($p['penilaian'])); ?></p>

                                <h6 class="fw-bold mt-3">Rencana Tindak Lanjut:</h6>
                                <p><?php echo nl2br(htmlspecialchars($p['rtl'])); ?></p>

                                <?php if (!empty($p['instruksi'])): ?>
                                    <h6 class="fw-bold mt-3">Instruksi:</h6>
                                    <p><?php echo nl2br(htmlspecialchars($p['instruksi'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
<?php
        }
        echo '</div>';
    } else {
        echo '<div class="alert alert-info">Belum ada data pemeriksaan</div>';
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo '<div class="alert alert-danger">Terjadi kesalahan saat mengambil data pemeriksaan</div>';
}
?>