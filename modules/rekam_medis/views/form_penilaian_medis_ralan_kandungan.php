<?php
// Pastikan tidak ada akses langsung ke file ini
if (!defined('BASE_PATH')) {
    die('No direct script access allowed');
}

// Pastikan koneksi database $conn tersedia
if (!isset($conn) || !($conn instanceof PDO)) {
    require_once __DIR__ . '/../../../config/database.php';
    // Pastikan $conn diambil ulang dari $GLOBALS jika sudah tersedia
    if (!isset($conn) && isset($GLOBALS['conn'])) {
        $conn = $GLOBALS['conn'];
    }
    if (!isset($conn) || !($conn instanceof PDO)) {
        error_log('ERROR: Database connection not available in form_penilaian_medis_ralan_kandungan.php');
        die('Database connection not available.');
    }
}
error_log('DEBUG: Database connection IS available in form_penilaian_medis_ralan_kandungan.php');

// Pastikan session sudah dimulai
if (!isset($_SESSION)) {
    session_start();
}

// Set default source_page jika belum ada
if (!isset($_SESSION['source_page'])) {
    $_SESSION['source_page'] = 'form_penilaian_medis_ralan_kandungan';
}

// Ambil data TB dan BB terakhir
$tb_terakhir = '';
$bb_terakhir = '';
$diagnosis_terakhir = '';
$tatalaksana_terakhir = '';
$resep_terakhir = '';
$no_rkm_medis = $data['no_rkm_medis'];

global $conn;
// Database connection is now available as $conn for all queries below.

// Query untuk data terakhir
$sql = "SELECT tb, bb, diagnosis, tata, resep 
        FROM penilaian_medis_ralan_kandungan pmrk
        JOIN reg_periksa rp ON pmrk.no_rawat = rp.no_rawat
        WHERE rp.no_rkm_medis = ? 
        AND (pmrk.tb IS NOT NULL OR pmrk.bb IS NOT NULL OR pmrk.diagnosis IS NOT NULL 
             OR pmrk.tata IS NOT NULL OR pmrk.resep IS NOT NULL)
        AND (pmrk.tb != '' OR pmrk.bb != '' OR pmrk.diagnosis != '' 
             OR pmrk.tata != '' OR pmrk.resep != '')
        ORDER BY pmrk.tanggal DESC 
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute([$no_rkm_medis]);

if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $tb_terakhir = $row['tb'];
    $bb_terakhir = $row['bb'];
    $diagnosis_terakhir = $row['diagnosis'];
    $tatalaksana_terakhir = $row['tata'];
    $resep_terakhir = $row['resep'];
}

// Get status obstetri data from the model (already provided by the controller)
// $statusObstetri is already available from the controller
?>

<style>
    .form-control,
    .form-select {
        font-size: 0.875rem;
    }

    .card-title {
        font-size: 1rem;
    }

    label {
        font-size: 0.875rem;
    }

    .table {
        font-size: 0.875rem;
    }

    /* CSS untuk fitur template */
    .card .small {
        font-size: 0.6rem !important;
    }

    .modal-title {
        font-size: 0.8rem;
    }

    .modal .table {
        font-size: 0.7rem;
    }

    .modal label {
        font-size: 0.7rem;
    }

    .btn-sm {
        font-size: 0.6rem;
    }

    /* CSS untuk warna teks tombol info */
    .btn-info {
        color: #fff !important;
    }

    .btn-info:hover {
        color: #fff !important;
    }

    /* Style untuk tab */
    .tab-pane {
        transition: all 0.3s ease-in-out;
        overflow: hidden;
        font-size: 0.8rem;
        padding: 15px;
        border: 1px solid #dee2e6;
        border-top: none;
        border-radius: 0 0 0.25rem 0.25rem;
    }

    .tab-pane:not(.active),
    .tab-pane:not(.show) {
        display: none;
    }

    .nav-tabs {
        border-bottom: 1px solid #dee2e6;
        margin-bottom: 0;
    }

    .nav-tabs .nav-link {
        display: flex;
        align-items: center;
        font-size: 0.85rem;
        cursor: pointer;
        padding: 0.75rem 1rem;
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        margin-right: 0.25rem;
        border-radius: 0.25rem 0.25rem 0 0;
    }

    .nav-tabs .nav-link.active,
    .nav-tabs .nav-link:not(.collapsed) {
        background-color: #fff;
        border-bottom-color: #fff;
    }

    .nav-tabs .nav-link i {
        margin-left: 5px;
        transition: transform 0.3s;
    }

    .nav-tabs .nav-link.collapsed i {
        transform: rotate(-90deg);
    }

    .nav-tabs .nav-link:not(.collapsed) i {
        transform: rotate(0deg);
    }

    /* Style untuk tabel status obstetri */
    .table-responsive {
        margin-top: 1rem;
    }

    .table-sm {
        font-size: 0.85rem;
    }

    .btn-add {
        background-color: #28a745;
        color: white;
    }

    .btn-add:hover {
        background-color: #218838;
        color: white;
    }

    /* Fix tampilan tabel status obstetri */
    #skrining .table th,
    #skrining .table td {
        padding: 0.5rem;
        vertical-align: middle;
    }

    /* Loading spinner style */
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(255, 255, 255, 0.7);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    /* Debug box style */
    .alert-info {
        font-size: 0.8rem;
    }

    .alert-info h6 {
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }

    /* Fix tampilan tabel pada mode mobile */
    @media (max-width: 768px) {
        #skrining .table {
            font-size: 0.75rem;
        }

        #skrining .table th,
        #skrining .table td {
            padding: 0.3rem;
        }

        #skrining .btn-sm {
            padding: 0.15rem 0.3rem;
            font-size: 0.6rem;
        }
    }
</style>

<!-- Load Status Obstetri Helper -->
<script src="<?= BASE_URL ?>/assets/js/status_obstetri_helper.js"></script>

<!-- Initialize BASE_URL variable for JavaScript -->
<script>
    // Make BASE_URL available to JavaScript
    var BASE_URL = '<?= BASE_URL ?>';
    console.log('BASE_URL initialized as:', BASE_URL);
</script>

<!-- Modal untuk menampilkan gambar edukasi -->
<div class="modal fade" id="gambarEdukasiModal" tabindex="-1" aria-labelledby="gambarEdukasiModalLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="gambarEdukasiModalLabel">Gambar Edukasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center" id="gambarEdukasiContent">
                <!-- Gambar akan dimuat di sini -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Form Penilaian Medis Rawat Jalan Kandungan</h3>
                </div>
                <div class="card-body">

                    <!-- Tab Identitas dan Status Obstetri -->
                    <ul class="nav nav-tabs mb-0" id="myTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="identitas-tab" data-toggle="collapse" href="#identitas" role="tab">
                                Identitas <i class="fas fa-chevron-down"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link collapsed" id="skrining-tab" data-toggle="collapse" href="#skrining" role="tab">
                                Status Obstetri <i class="fas fa-chevron-down"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link collapsed" id="riwayat-kehamilan-tab" data-toggle="collapse" href="#riwayat-kehamilan" role="tab">
                                Riwayat Kehamilan <i class="fas fa-chevron-down"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link collapsed" id="status-ginekologi-tab" data-toggle="collapse" href="#status-ginekologi" role="tab">
                                Status Ginekologi <i class="fas fa-chevron-down"></i>
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade collapse show" id="identitas" role="tabpanel">
                            <div class="row">
                                <!-- Kolom Kiri -->
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h6 class="card-title mb-0" style="font-size: 0.9rem;">Data Pribadi</h6>
                                        </div>
                                        <div class="card-body p-0">
                                            <table class="table table-sm table-hover" style="font-size: 0.85rem;">
                                                <tr>
                                                    <th class="text-muted px-3">Nama Pasien</th>
                                                    <td class="px-3"><?= $data['nm_pasien'] ?></td>
                                                </tr>
                                                <tr>
                                                    <th class="text-muted px-3">Jenis Kelamin</th>
                                                    <td class="px-3"><?= $data['jk'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                                                </tr>
                                                <tr>
                                                    <th class="text-muted px-3">Tanggal Lahir</th>
                                                    <td class="px-3"><?= date('d-m-Y', strtotime($data['tgl_lahir'])) ?></td>
                                                </tr>
                                                <tr>
                                                    <th class="text-muted px-3">Umur</th>
                                                    <td class="px-3"><?= $data['umur'] ?? '-' ?> tahun</td>
                                                </tr>

                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Kolom Kanan -->
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h6 class="card-title mb-0" style="font-size: 0.9rem;">Informasi Tambahan</h6>
                                        </div>
                                        <div class="card-body p-0">
                                            <table class="table table-sm table-hover" style="font-size: 0.85rem;">
                                                <tr>
                                                    <th width="140" class="text-muted px-3">Alamat</th>
                                                    <td class="px-3"><?= $data['alamat'] ?? '-' ?></td>
                                                </tr>
                                                <tr>
                                                    <th class="text-muted px-3">No. Telepon</th>
                                                    <td class="px-3"><?= $data['no_tlp'] ?? '-' ?></td>
                                                </tr>
                                                <tr>
                                                    <th class="text-muted px-3">Pekerjaan</th>
                                                    <td class="px-3"><?= $data['pekerjaan'] ?? '-' ?></td>
                                                </tr>
                                                <tr>
                                                    <th class="text-muted px-3">Status Nikah</th>
                                                    <td class="px-3"><?= $data['stts_nikah'] ?? '-' ?></td>
                                                </tr>

                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Status Obstetri -->
                        <div class="tab-pane fade" id="skrining" role="tabpanel">
                            <div class="mb-3 d-flex justify-content-between">
                                <h6 class="font-weight-bold">Status Obstetri</h6>
                                <a href="index.php?module=rekam_medis&action=tambah_status_obstetri&no_rkm_medis=<?= $data['no_rkm_medis'] ?>&source=form_penilaian_medis_ralan_kandungan&no_rawat=<?= $data['no_rawat'] ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Tambah Data
                                </a>
                            </div>
                            <div id="statusObstetriContent">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered table-striped">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Tanggal</th>
                                                <th>G-P-A</th>
                                                <th>HPHT</th>
                                                <th>TP</th>
                                                <th>TP Penyesuaian</th>
                                                <th>Faktor Risiko</th>
                                                <th width="100">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="statusObstetriTableBody">
                                            <?php if (isset($statusObstetri) && count($statusObstetri) > 0): ?>
                                                <?php foreach ($statusObstetri as $so): ?>
                                                    <tr>
                                                        <td><?= date('d-m-Y', strtotime($so['updated_at'])) ?></td>
                                                        <td><?= $so['gravida'] . '-' . $so['paritas'] . '-' . $so['abortus'] ?></td>
                                                        <td><?= !empty($so['tanggal_hpht']) ? date('d-m-Y', strtotime($so['tanggal_hpht'])) : '-' ?></td>
                                                        <td><?= !empty($so['tanggal_tp']) ? date('d-m-Y', strtotime($so['tanggal_tp'])) : '-' ?></td>
                                                        <td><?= !empty($so['tanggal_tp_penyesuaian']) ? date('d-m-Y', strtotime($so['tanggal_tp_penyesuaian'])) : '-' ?></td>
                                                        <td>
                                                            <?php
                                                            $faktor_risiko = [];
                                                            if (!empty($so['faktor_risiko_umum'])) {
                                                                $faktor_risiko[] = 'Umum: ' . str_replace(',', ', ', $so['faktor_risiko_umum']);
                                                            }
                                                            if (!empty($so['faktor_risiko_obstetri'])) {
                                                                $faktor_risiko[] = 'Obstetri: ' . str_replace(',', ', ', $so['faktor_risiko_obstetri']);
                                                            }
                                                            if (!empty($so['faktor_risiko_preeklampsia'])) {
                                                                $faktor_risiko[] = 'Preeklampsia: ' . str_replace(',', ', ', $so['faktor_risiko_preeklampsia']);
                                                            }
                                                            echo !empty($faktor_risiko) ? implode('<br>', $faktor_risiko) : '-';
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <a href="index.php?module=rekam_medis&action=edit_status_obstetri&id=<?= $so['id_status_obstetri'] ?>&source=form_penilaian_medis_ralan_kandungan&no_rawat=<?= $data['no_rawat'] ?>" class="btn btn-warning btn-sm">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="index.php?module=rekam_medis&action=hapus_status_obstetri&id=<?= $so['id_status_obstetri'] ?>&source=form_penilaian_medis_ralan_kandungan&no_rawat=<?= $data['no_rawat'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="7" class="text-center">Tidak ada data status obstetri</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Riwayat Kehamilan -->
                        <div class="tab-pane fade" id="riwayat-kehamilan" role="tabpanel">
                            <div class="mb-3 d-flex justify-content-between">
                                <h6 class="font-weight-bold">Riwayat Kehamilan</h6>
                                <a href="index.php?module=rekam_medis&action=tambah_riwayat_kehamilan&no_rkm_medis=<?= $pasien['no_rkm_medis'] ?>&source=form_penilaian_medis_ralan_kandungan&no_rawat=<?= $data['no_rawat'] ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Tambah Data
                                </a>
                            </div>
                            <div id="riwayatKehamilanContent">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Status</th>
                                                <th>Jenis</th>
                                                <th>Tempat</th>
                                                <th>Penolong</th>
                                                <th>Tahun</th>
                                                <th>Jenis Kelamin</th>
                                                <th>BB</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (isset($riwayatKehamilan) && count($riwayatKehamilan) > 0): ?>
                                                <?php foreach ($riwayatKehamilan as $rk): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($rk['no_urut_kehamilan'] ?? '-') ?></td>
                                                        <td><?= htmlspecialchars($rk['status_kehamilan'] ?? '-') ?></td>
                                                        <td><?= htmlspecialchars($rk['jenis_persalinan'] ?? '-') ?></td>
                                                        <td><?= htmlspecialchars($rk['tempat_persalinan'] ?? '-') ?></td>
                                                        <td><?= htmlspecialchars($rk['penolong_persalinan'] ?? '-') ?></td>
                                                        <td><?= htmlspecialchars($rk['tahun_persalinan'] ?? '-') ?></td>
                                                        <td><?= htmlspecialchars($rk['jenis_kelamin_anak'] ?? '-') ?></td>
                                                        <td><?= htmlspecialchars($rk['berat_badan_lahir'] ?? '-') ?></td>
                                                        <td><?= htmlspecialchars($rk['kondisi_lahir'] ?? '-') ?></td>
                                                        <td>
                                                            <a href="index.php?module=rekam_medis&action=edit_riwayat_kehamilan&id=<?= $rk['id_riwayat_kehamilan'] ?>&source=form_penilaian_medis_ralan_kandungan&no_rawat=<?= $data['no_rawat'] ?>" class="btn btn-warning btn-sm">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="index.php?module=rekam_medis&action=hapus_riwayat_kehamilan&id=<?= $rk['id_riwayat_kehamilan'] ?>&source=form_penilaian_medis_ralan_kandungan&no_rawat=<?= $data['no_rawat'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="10" class="text-center">Tidak ada data riwayat kehamilan</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Status Ginekologi -->
                        <div class="tab-pane fade" id="status-ginekologi" role="tabpanel">
                            <div class="mb-3 d-flex justify-content-between">
                                <h6 class="font-weight-bold">Status Ginekologi</h6>
                                <a href="index.php?module=rekam_medis&action=tambah_status_ginekologi&no_rkm_medis=<?= $data['no_rkm_medis'] ?>&source=form_penilaian_medis_ralan_kandungan&no_rawat=<?= $data['no_rawat'] ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Tambah Data
                                </a>
                            </div>
                            <div id="statusGinekologiContent">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Tanggal</th>
                                                <th>Parturien</th>
                                                <th>Abortus</th>
                                                <th>Hari Pertama Haid Terakhir</th>
                                                <th>Kontrasepsi Terakhir</th>
                                                <th>Lama Menikah (Tahun)</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (isset($statusGinekologi) && count($statusGinekologi) > 0): ?>
                                                <?php foreach ($statusGinekologi as $sg): ?>
                                                    <tr>
                                                        <td><?= isset($sg['created_at']) ? date('d-m-Y', strtotime($sg['created_at'])) : '-' ?></td>
                                                        <td><?= htmlspecialchars($sg['Parturien'] ?? '-') ?></td>
                                                        <td><?= htmlspecialchars($sg['Abortus'] ?? '-') ?></td>
                                                        <td><?= !empty($sg['Hari_pertama_haid_terakhir']) ? date('d-m-Y', strtotime($sg['Hari_pertama_haid_terakhir'])) : '-' ?></td>
                                                        <td><?= htmlspecialchars($sg['Kontrasepsi_terakhir'] ?? '-') ?></td>
                                                        <td><?= htmlspecialchars($sg['lama_menikah_th'] ?? '-') ?></td>
                                                        <td>
                                                            <a href="index.php?module=rekam_medis&action=edit_status_ginekologi&id=<?= $sg['id_status_ginekologi'] ?>&source=form_penilaian_medis_ralan_kandungan&no_rawat=<?= $data['no_rawat'] ?>" class="btn btn-warning btn-sm">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="index.php?module=rekam_medis&action=hapus_status_ginekologi&id=<?= $sg['id_status_ginekologi'] ?>&source=form_penilaian_medis_ralan_kandungan&no_rawat=<?= $data['no_rawat'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="7" class="text-center">Tidak ada data status ginekologi</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form action="index.php?module=rekam_medis&action=simpan_penilaian_medis_ralan_kandungan" method="POST">
                        <input type="hidden" name="no_rawat" value="<?= $data['no_rawat'] ?>">
                        <input type="hidden" name="tanggal" value="<?= date('Y-m-d H:i:s') ?>">
                        <input type="hidden" name="anamnesis" value="Autoanamnesis">
                        <input type="hidden" name="hubungan" value="-">
                        <input type="hidden" name="keadaan" value="Sehat">
                        <input type="hidden" name="kesadaran" value="Compos Mentis">
                        <input type="hidden" name="kepala" value="Normal">
                        <input type="hidden" name="mata" value="Normal">
                        <input type="hidden" name="gigi" value="Normal">
                        <input type="hidden" name="tht" value="Normal">
                        <input type="hidden" name="thoraks" value="Normal">
                        <input type="hidden" name="abdomen" value="Normal">
                        <input type="hidden" name="genital" value="Normal">
                        <input type="hidden" name="ekstremitas" value="Normal">
                        <input type="hidden" name="kulit" value="Normal">

                        <div class="row">
                            <!-- Kolom 1 -->
                            <div class="col-md-4">
                                <!-- Anamnesis -->
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Anamnesis</h5>
                                    </div>
                                    <div class="card-body">

                                        <?php
// Ambil daftar perujuk dari tabel rujukan
$rujukanList = [];
try {
    $stmtRujuk = $conn->query("SELECT id_perujuk, nama_perujuk, jenis_perujuk FROM rujukan ORDER BY nama_perujuk ASC");
    $rujukanList = $stmtRujuk->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Gagal mengambil data perujuk: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
// Ambil id_perujuk yang sudah tersimpan jika mode edit
$id_perujuk_selected = isset($data['id_perujuk']) ? $data['id_perujuk'] : '';
?>
<div class="mb-2">
    <label>Nama Perujuk</label>
    <select name="id_perujuk" class="form-select form-select-sm">
        <option value="">-- Pilih Perujuk --</option>
        <?php foreach ($rujukanList as $r): ?>
            <option value="<?= htmlspecialchars($r['id_perujuk']) ?>" <?= $id_perujuk_selected == $r['id_perujuk'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($r['nama_perujuk']) ?> (<?= htmlspecialchars($r['jenis_perujuk']) ?>)
            </option>
        <?php endforeach; ?>
    </select>
</div>
<div class="mb-2">
    <label>Keluhan Utama</label>
    <textarea name="keluhan_utama" class="form-control form-control-sm" rows="2" required></textarea>
</div>
                                        <div class="mb-2">
                                            <label>Riwayat Sekarang</label>
                                            <div class="row">
                                                <div class="col-md-9">
                                                    <!-- Modified textarea with auto-resize class and data attribute -->
                                                    <textarea name="rps" id="riwayat_sekarang" class="form-control form-control-sm auto-resize" rows="6" style="min-height: 120px; overflow-y: hidden;"></textarea>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="card border">

                                                        <div class="card-body p-2">
                                                            <button type="button" class="btn btn-sm btn-info w-100" data-bs-toggle="modal" data-bs-target="#modalDaftarTemplateAnamnesis">
                                                                <i class="fas fa-list"></i> Template Anamnesis
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <label>Riwayat Penyakit Dahulu</label>
                                            <textarea name="rpd" class="form-control form-control-sm" rows="4"></textarea>
                                        </div>
                                        <div class="mb-2">
                                            <label>Alergi</label>
                                            <textarea name="alergi" class="form-control form-control-sm" rows="2"></textarea>
                                        </div>

                                    </div>
                                </div>

                                <!-- Pemeriksaan Fisik -->
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Pemeriksaan Fisik</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-2">
                                            <div class="col-4">
                                                <div class="mb-2">
                                                    <label>GCS</label>
                                                    <input type="text" name="gcs" class="form-control form-control-sm" required value="456">
                                                </div>
                                                <div class="mb-2">
                                                    <label>TD (mmHg)</label>
                                                    <input type="text" name="td" class="form-control form-control-sm" required value="120/80">
                                                </div>
                                                <div class="mb-2">
                                                    <label>Nadi (x/menit)</label>
                                                    <input type="text" name="nadi" class="form-control form-control-sm" required value="90">
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="mb-2">
                                                    <label>RR (x/menit)</label>
                                                    <input type="text" name="rr" class="form-control form-control-sm" required value="16">
                                                </div>
                                                <div class="mb-2">
                                                    <label>Suhu (°C)</label>
                                                    <input type="text" name="suhu" class="form-control form-control-sm" required value="36.4">
                                                </div>
                                                <div class="mb-2">
                                                    <label>SpO2 (%)</label>
                                                    <input type="text" name="spo" class="form-control form-control-sm" value="99">
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="mb-3">
                                                    <label>BB (kg)</label>
                                                    <input type="number" name="bb" class="form-control form-control-sm" value="<?= htmlspecialchars($bb_terakhir) ?>" step="0.01" min="0" max="500" placeholder=" " onkeypress="return (event.charCode >= 48 && event.charCode <= 57) || event.charCode == 46">

                                                </div>
                                                <div class="mb-3">
                                                    <label>TB (cm)</label>
                                                    <input type="number" name="tb" class="form-control form-control-sm" value="<?= htmlspecialchars($tb_terakhir) ?>" step="0.1" min="0" max="300" placeholder=" " onkeypress="return (event.charCode >= 48 && event.charCode <= 57) || event.charCode == 46">

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Kolom 2 -->
                            <div class="col-md-4">
                                <!-- Pemeriksaan Organ -->
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Pemeriksaan Organ</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-2">
                                            <div class="col-4">
                                                <div class="mb-2">
                                                    <label>Kepala</label>
                                                    <select name="kepala" class="form-select form-select-sm" required>
                                                        <option value="Normal">Normal</option>
                                                        <option value="Abnormal">Abnormal</option>
                                                        <option value="Tidak Diperiksa">Tidak Diperiksa</option>
                                                    </select>
                                                </div>
                                                <div class="mb-2">
                                                    <label>Mata</label>
                                                    <select name="mata" class="form-select form-select-sm" required>
                                                        <option value="Normal">Normal</option>
                                                        <option value="Abnormal">Abnormal</option>
                                                        <option value="Tidak Diperiksa">Tidak Diperiksa</option>
                                                    </select>
                                                </div>
                                                <div class="mb-2">
                                                    <label>Gigi</label>
                                                    <select name="gigi" class="form-select form-select-sm" required>
                                                        <option value="Normal">Normal</option>
                                                        <option value="Abnormal">Abnormal</option>
                                                        <option value="Tidak Diperiksa">Tidak Diperiksa</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="mb-2">
                                                    <label>THT</label>
                                                    <select name="tht" class="form-select form-select-sm" required>
                                                        <option value="Normal">Normal</option>
                                                        <option value="Abnormal">Abnormal</option>
                                                        <option value="Tidak Diperiksa">Tidak Diperiksa</option>
                                                    </select>
                                                </div>
                                                <div class="mb-2">
                                                    <label>Thoraks</label>
                                                    <select name="thoraks" class="form-select form-select-sm" required>
                                                        <option value="Normal">Normal</option>
                                                        <option value="Abnormal">Abnormal</option>
                                                        <option value="Tidak Diperiksa">Tidak Diperiksa</option>
                                                    </select>
                                                </div>
                                                <div class="mb-2">
                                                    <label>Abdomen</label>
                                                    <select name="abdomen" class="form-select form-select-sm" required>
                                                        <option value="Normal">Normal</option>
                                                        <option value="Abnormal">Abnormal</option>
                                                        <option value="Tidak Diperiksa">Tidak Diperiksa</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="mb-2">
                                                    <label>Genital</label>
                                                    <select name="genital" class="form-select form-select-sm" required>
                                                        <option value="Normal">Normal</option>
                                                        <option value="Abnormal">Abnormal</option>
                                                        <option value="Tidak Diperiksa">Tidak Diperiksa</option>
                                                    </select>
                                                </div>
                                                <div class="mb-2">
                                                    <label>Ekstremitas</label>
                                                    <select name="ekstremitas" class="form-select form-select-sm" required>
                                                        <option value="Normal">Normal</option>
                                                        <option value="Abnormal">Abnormal</option>
                                                        <option value="Tidak Diperiksa">Tidak Diperiksa</option>
                                                    </select>
                                                </div>
                                                <div class="mb-2">
                                                    <label>Kulit</label>
                                                    <select name="kulit" class="form-select form-select-sm" required>
                                                        <option value="Normal">Normal</option>
                                                        <option value="Abnormal">Abnormal</option>
                                                        <option value="Tidak Diperiksa">Tidak Diperiksa</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <label>Keterangan Pemeriksaan Fisik</label>
                                            <textarea name="ket_fisik" class="form-control form-control-sm" rows="1"></textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pemeriksaan Penunjang -->
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Pemeriksaan Penunjang</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label>Ultrasonografi</label>
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <textarea name="ultra" id="ultrasonografi" class="form-control" rows="10"></textarea>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="card border">

                                                        <div class="card-body p-2">
                                                            <button type="button" class="btn btn-sm btn-info w-100" data-bs-toggle="modal" data-bs-target="#modalDaftarTemplateUsg">
                                                                <i class="fas fa-list"></i> Template USG
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label>Laboratorium</label>
                                            <textarea name="lab" class="form-control" rows="2"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Kolom 3 -->
                            <div class="col-md-4">
                                <!-- Diagnosis & Tatalaksana -->
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Diagnosis & Tatalaksana</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label>Diagnosis</label>
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <textarea name="diagnosis" id="diagnosis" class="form-control" rows="4"><?= htmlspecialchars($diagnosis_terakhir) ?></textarea>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="card border">

                                                        <div class="card-body p-2">
                                                            <button type="button" class="btn btn-sm btn-info w-100" data-bs-toggle="modal" data-bs-target="#modalRiwayatDiagnosis">
                                                                <i class="fas fa-history"></i> Riwayat Diagnosis
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label>Tatalaksana</label>
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <textarea name="tata" id="tatalaksana" class="form-control" rows="4"><?= htmlspecialchars($tatalaksana_terakhir) ?></textarea>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="card border">

                                                        <div class="card-body p-2">
                                                            <button type="button" class="btn btn-sm btn-info w-100" data-bs-toggle="modal" data-bs-target="#modalDaftarTemplate">
                                                                <i class="fas fa-list"></i> Daftar Tatalaksana
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label>Edukasi</label>
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <textarea name="edukasi" id="edukasi" class="form-control" rows="4"></textarea>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="card border">

                                                        <div class="card-body p-2">
                                                            <button type="button" class="btn btn-sm btn-info w-100" data-bs-toggle="modal" data-bs-target="#modalDaftarEdukasi">
                                                                <i class="fas fa-list"></i> Daftar Edukasi
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label>Resep</label>
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <textarea name="resep" id="resep" class="form-control" rows="4"><?= htmlspecialchars($resep_terakhir) ?></textarea>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="card border">

                                                        <div class="card-body p-2">
                                                            <button type="button" class="btn btn-sm btn-info w-100" data-bs-toggle="modal" data-bs-target="#modalDaftarTemplateResep">
                                                                <i class="fas fa-list"></i> Formularium
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label>Tanggal Kontrol</label>
                                            <input type="date" name="tanggal_kontrol" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label>Atensi</label>
                                            <select name="atensi" class="form-select">
                                                <option value="0">Tidak</option>
                                                <option value="1">Ya</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group text-center">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-save"></i> Simpan
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="history.back()">
                                <i class="fas fa-times"></i> Batal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Daftar Template Tatalaksana -->
<div class="modal fade" id="modalDaftarTemplate" tabindex="-1" aria-labelledby="modalDaftarTemplateLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDaftarTemplateLabel">Daftar Template Tatalaksana</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Filter Kategori -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <select id="filter_kategori_tatalaksana" class="form-select me-2">
                            <option value="">Semua Kategori</option>
                            <option value="fetomaternal">Fetomaternal</option>
                            <option value="ginekologi umum">Ginekologi Umum</option>
                            <option value="onkogin">Onkogin</option>
                            <option value="fertilitas">Fertilitas</option>
                            <option value="uroginekologi">Uroginekologi</option>
                        </select>
                    </div>
                </div>

                <!-- Tabel Template -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="tabelTemplateTatalaksana">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">No</th>
                                <th width="20%">Nama Template</th>
                                <th width="40%">Isi Template</th>
                                <th width="15%">Kategori</th>
                                <th width="10%">Tags</th>
                                <th width="10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                // Query untuk mengambil semua data template
                                $stmt = $conn->query("SELECT * FROM template_tatalaksana WHERE status = 'active' ORDER BY kategori_tx ASC, nama_template_tx ASC");
                                $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                if (count($templates) > 0) {
                                    $no = 1;
                                    foreach ($templates as $row) {
                                        echo "<tr class='template-row' data-kategori='" . htmlspecialchars($row['kategori_tx']) . "'>";
                                        echo "<td>" . $no++ . "</td>";
                                        echo "<td>" . htmlspecialchars($row['nama_template_tx']) . "</td>";
                                        echo "<td><div style='max-height: 100px; overflow-y: auto;'>" . nl2br(htmlspecialchars($row['isi_template_tx'])) . "</div></td>";
                                        echo "<td>" . htmlspecialchars($row['kategori_tx']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['tags'] ?? '-') . "</td>";
                                        echo "<td>
                                            <button type='button' class='btn btn-sm btn-primary mb-1 w-100' onclick='gunakanTemplate(" . json_encode($row['isi_template_tx']) . ")'>
                                                <i class='fas fa-copy'></i> Gunakan
                                            </button>
                                          </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='text-center'>Tidak ada data template</td></tr>";
                                }
                            } catch (PDOException $e) {
                                echo "<tr><td colspan='6' class='text-center text-danger'>Error: " . htmlspecialchars(
                                    $e->getMessage()
                                ) . "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Daftar Template USG -->
<div class="modal fade" id="modalDaftarTemplateUsg" tabindex="-1" aria-labelledby="modalDaftarTemplateUsgLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDaftarTemplateUsgLabel">Daftar Template USG</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Filter Kategori -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <select id="filter_kategori_usg" class="form-select me-2">
                            <option value="">Semua Kategori</option>
                            <option value="obstetri" <?= isset($_GET['kategori_usg']) && $_GET['kategori_usg'] == 'obstetri' ? 'selected' : '' ?>>Obstetri</option>
                            <option value="ginekologi" <?= isset($_GET['kategori_usg']) && $_GET['kategori_usg'] == 'ginekologi' ? 'selected' : '' ?>>Ginekologi</option>
                        </select>
                    </div>
                </div>

                <!-- Tabel Template -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="tabelTemplateUsg">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">No</th>
                                <th width="20%">Nama Template</th>
                                <th width="40%">Isi Template</th>
                                <th width="15%">Kategori</th>
                                <th width="10%">Tags</th>
                                <th width="10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Koneksi ke database
                            // Database config already included at the top of the file.
                            try {
                                // Query untuk mendapatkan semua template USG
                                $stmt = $conn->query("SELECT * FROM template_usg WHERE status = 'active' ORDER BY kategori_usg ASC, nama_template_usg ASC");
                                $usgTemplates = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                if (count($usgTemplates) > 0) {
                                    $no = 1;
                                    foreach ($usgTemplates as $row) {
                                        echo "<tr class='template-row' data-kategori='" . htmlspecialchars($row['kategori_usg']) . "'>";
                                        echo "<td>" . $no++ . "</td>";
                                        echo "<td>" . htmlspecialchars($row['nama_template_usg']) . "</td>";
                                        echo "<td><div style='max-height: 100px; overflow-y: auto;'>" . nl2br(htmlspecialchars($row['isi_template_usg'])) . "</div></td>";
                                        echo "<td>" . ucwords($row['kategori_usg']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['tags'] ?? '-') . "</td>";
                                        echo "<td><button type='button' class='btn btn-sm btn-success w-100' onclick='gunakanTemplateUsg(" . json_encode($row['isi_template_usg']) . ")'><i class='fas fa-check'></i> Gunakan</button></td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='text-center'>Tidak ada template tersedia</td></tr>";
                                }
                            } catch (PDOException $e) {
                                echo "<tr><td colspan='6' class='text-center text-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Riwayat Diagnosis -->
<div class="modal fade" id="modalRiwayatDiagnosis" tabindex="-1" aria-labelledby="modalRiwayatDiagnosisLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRiwayatDiagnosisLabel">Riwayat Diagnosis Pasien</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">No</th>
                                <th width="15%">Tanggal</th>
                                <th width="65%">Diagnosis</th>
                                <th width="15%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Ambil no_rkm_medis dari data pasien
                            $no_rkm_medis = $data['no_rkm_medis'];

                            try {
                                // Query untuk mendapatkan riwayat diagnosis
                                $sql = "SELECT 
                                        pmrk.tanggal, 
                                        pmrk.diagnosis 
                                    FROM penilaian_medis_ralan_kandungan pmrk
                                    JOIN reg_periksa rp ON pmrk.no_rawat = rp.no_rawat
                                    WHERE rp.no_rkm_medis = ? 
                                    AND pmrk.diagnosis IS NOT NULL 
                                    AND pmrk.diagnosis != ''
                                    ORDER BY pmrk.tanggal DESC";

                                $stmt = $conn->prepare($sql);
                                $stmt->execute([$no_rkm_medis]);
                                $diagnosisHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                if (count($diagnosisHistory) > 0) {
                                    $no = 1;
                                    foreach ($diagnosisHistory as $row) {
                                        echo "<tr>";
                                        echo "<td>" . $no++ . "</td>";
                                        echo "<td>" . date('d-m-Y H:i:s', strtotime($row['tanggal'])) . "</td>";
                                        echo "<td>" . nl2br(htmlspecialchars($row['diagnosis'])) . "</td>";
                                        echo "<td><button type='button' class='btn btn-sm btn-success w-100' onclick='gunakanDiagnosis(" . json_encode($row['diagnosis']) . ")'><i class='fas fa-check'></i> Gunakan</button></td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4' class='text-center'>Tidak ada riwayat diagnosis</td></tr>";
                                }
                            } catch (PDOException $e) {
                                echo "<tr><td colspan='4' class='text-center text-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Daftar Template Resep -->
<div class="modal fade" id="modalDaftarTemplateResep" tabindex="-1" aria-labelledby="modalDaftarTemplateResepLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDaftarTemplateResepLabel">Daftar Formularium</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Filter Kategori -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <select id="filter_kategori_obat" class="form-select me-2">
                            <option value="">Semua Kategori</option>
                            <option value="Analgesik" <?= isset($_GET['kategori_obat']) && $_GET['kategori_obat'] == 'Analgesik' ? 'selected' : '' ?>>Analgesik</option>
                            <option value="Antibiotik" <?= isset($_GET['kategori_obat']) && $_GET['kategori_obat'] == 'Antibiotik' ? 'selected' : '' ?>>Antibiotik</option>
                            <option value="Antiinflamasi" <?= isset($_GET['kategori_obat']) && $_GET['kategori_obat'] == 'Antiinflamasi' ? 'selected' : '' ?>>Antiinflamasi</option>
                            <option value="Antihipertensi" <?= isset($_GET['kategori_obat']) && $_GET['kategori_obat'] == 'Antihipertensi' ? 'selected' : '' ?>>Antihipertensi</option>
                            <option value="Antidiabetes" <?= isset($_GET['kategori_obat']) && $_GET['kategori_obat'] == 'Antidiabetes' ? 'selected' : '' ?>>Antidiabetes</option>
                            <option value="Vitamin dan Suplemen" <?= isset($_GET['kategori_obat']) && $_GET['kategori_obat'] == 'Vitamin dan Suplemen' ? 'selected' : '' ?>>Vitamin dan Suplemen</option>
                            <option value="Hormon" <?= isset($_GET['kategori_obat']) && $_GET['kategori_obat'] == 'Hormon' ? 'selected' : '' ?>>Hormon</option>
                            <option value="Obat Kulit" <?= isset($_GET['kategori_obat']) && $_GET['kategori_obat'] == 'Obat Kulit' ? 'selected' : '' ?>>Obat Kulit</option>
                            <option value="Obat Mata" <?= isset($_GET['kategori_obat']) && $_GET['kategori_obat'] == 'Obat Mata' ? 'selected' : '' ?>>Obat Mata</option>
                            <option value="Obat Saluran Pencernaan" <?= isset($_GET['kategori_obat']) && $_GET['kategori_obat'] == 'Obat Saluran Pencernaan' ? 'selected' : '' ?>>Obat Saluran Pencernaan</option>
                            <option value="Obat Saluran Pernapasan" <?= isset($_GET['kategori_obat']) && $_GET['kategori_obat'] == 'Obat Saluran Pernapasan' ? 'selected' : '' ?>>Obat Saluran Pernapasan</option>
                            <option value="Lainnya" <?= isset($_GET['kategori_obat']) && $_GET['kategori_obat'] == 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" id="search_generik" class="form-control" placeholder="Cari nama generik...">
                    </div>
                </div>

                <!-- Tabel Formularium -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="tabelFormularium">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">
                                    <input type="checkbox" id="checkAll" class="form-check-input">
                                </th>
                                <th width="20%">Nama Obat</th>
                                <th width="15%">Nama Generik</th>
                                <th width="15%">Bentuk & Dosis</th>
                                <th width="15%">Kategori</th>
                                <th width="15%">Catatan</th>
                                <th width="15%">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                // Query untuk mendapatkan semua data formularium
                                $stmt = $conn->query("SELECT * FROM formularium WHERE status_aktif = 1 ORDER BY nama_obat ASC");
                                $formularium = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                if (count($formularium) > 0) {
                                    foreach ($formularium as $row) {
                                        $bentuk_dosis = $row['bentuk_sediaan'] . ' ' . $row['dosis'];
                                        echo "<tr class='obat-row' data-kategori='" . htmlspecialchars($row['kategori']) . "'>";
                                        echo "<td><input type='checkbox' class='form-check-input obat-checkbox' data-nama='" . htmlspecialchars($row['nama_obat'] ?? '') . "' data-bentuk-dosis='" . htmlspecialchars($bentuk_dosis ?? '') . "' data-catatan='" . htmlspecialchars($row['catatan_obat'] ?? '') . "' data-generik='" . htmlspecialchars($row['nama_generik'] ?? '') . "'></td>";
                                        echo "<td>" . htmlspecialchars($row['nama_obat']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['nama_generik'] ?? '') . "</td>";
                                        echo "<td>" . htmlspecialchars($bentuk_dosis) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['kategori']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['catatan_obat']) . "</td>";
                                        echo "<td><span class='badge bg-success'>Aktif</span></td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='7' class='text-center'>Tidak ada data obat</td></tr>";
                                }
                            } catch (PDOException $e) {
                                echo "<tr><td colspan='7'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" onclick="tambahkanObatTerpilih()">Tambahkan Obat Terpilih</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Daftar Template Anamnesis -->
<div class="modal fade" id="modalDaftarTemplateAnamnesis" tabindex="-1" aria-labelledby="modalDaftarTemplateAnamnesisLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDaftarTemplateAnamnesisLabel">Daftar Template Anamnesis</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Filter Kategori -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <select id="filter_kategori_anamnesis" class="form-select me-2">
                            <option value="">Semua Kategori</option>
                            <option value="fetomaternal">Fetomaternal</option>
                            <option value="ginekologi umum">Ginekologi Umum</option>
                            <option value="onkogin">Onkogin</option>
                            <option value="fertilitas">Fertilitas</option>
                            <option value="uroginekologi">Uroginekologi</option>
                        </select>
                    </div>
                </div>

                <!-- Tabel Template -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="tabelTemplateAnamnesis">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">No</th>
                                <th width="20%">Nama Template</th>
                                <th width="40%">Isi Template</th>
                                <th width="15%">Kategori</th>
                                <th width="10%">Tags</th>
                                <th width="10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                // Query untuk mengambil semua data template anamnesis
                                $stmt = $conn->query("SELECT * FROM template_anamnesis WHERE status = 'active' ORDER BY kategori_anamnesis ASC, nama_template_anamnesis ASC");
                                $anamnesisTemplates = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                if (count($anamnesisTemplates) > 0) {
                                    $no = 1;
                                    foreach ($anamnesisTemplates as $row) {
                                        echo "<tr class='template-row' data-kategori='" . htmlspecialchars($row['kategori_anamnesis']) . "'>";
                                        echo "<td>" . $no++ . "</td>";
                                        echo "<td>" . htmlspecialchars($row['nama_template_anamnesis']) . "</td>";
                                        echo "<td><div style='max-height: 100px; overflow-y: auto;'>" . nl2br(htmlspecialchars($row['isi_template_anamnesis'])) . "</div></td>";
                                        echo "<td>" . htmlspecialchars($row['kategori_anamnesis']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['tags'] ?? '-') . "</td>";
                                        echo "<td>
                                            <button type='button' class='btn btn-sm btn-primary mb-1 w-100' onclick='gunakanTemplateAnamnesis(" . json_encode($row['isi_template_anamnesis']) . ")'>
                                                <i class='fas fa-copy'></i> Gunakan
                                            </button>
                                          </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='text-center'>Tidak ada data template anamnesis</td></tr>";
                                }
                            } catch (PDOException $e) {
                                echo "<tr><td colspan='6' class='text-center text-danger'>Error: " . htmlspecialchars(
                                    $e->getMessage()
                                ) . "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Daftar Template Edukasi -->
<div class="modal fade" id="modalDaftarEdukasi" tabindex="-1" aria-labelledby="modalDaftarEdukasiLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDaftarEdukasiLabel">Daftar Template Edukasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Filter Kategori -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <select id="filter_kategori_edukasi" class="form-select me-2">
                            <option value="">Semua Kategori</option>
                            <option value="fetomaternal">Fetomaternal</option>
                            <option value="ginekologi umum">Ginekologi Umum</option>
                            <option value="onkogin">Onkogin</option>
                            <option value="fertilitas">Fertilitas</option>
                            <option value="uroginekologi">Uroginekologi</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" id="search_edukasi" class="form-control" placeholder="Cari judul atau isi edukasi...">
                    </div>
                </div>

                <!-- Tabel Template -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="tabelTemplateEdukasi">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">No</th>
                                <th width="20%">Judul</th>
                                <th width="40%">Isi Edukasi</th>
                                <th width="15%">Kategori</th>
                                <th width="10%">Tags</th>
                                <th width="10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                // Query untuk mendapatkan semua template edukasi
                                $sql = "SELECT id_edukasi, judul, isi_edukasi, kategori, tag, link_gambar, status_aktif FROM edukasi WHERE status_aktif = 1 ORDER BY kategori ASC, judul ASC";
                                $stmt = $conn->query($sql);
                                $edukasiTemplates = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                if (count($edukasiTemplates) > 0) {
                                    $no = 1;
                                    foreach ($edukasiTemplates as $row) {
                                        echo "<tr class='template-row' data-kategori='" . htmlspecialchars($row['kategori']) . "' data-judul='" . htmlspecialchars($row['judul']) . "'>";
                                        echo "<td>" . $no++ . "</td>";
                                        echo "<td>" . htmlspecialchars($row['judul']) . "</td>";
                                        echo "<td><div style='max-height: 100px; overflow-y: auto;'>" . $row['isi_edukasi'] . "</div></td>";
                                        echo "<td>" . ucwords($row['kategori']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['tag'] ?? '-') . "</td>";
                                        echo "<td>";
                                        echo "<button type='button' class='btn btn-sm btn-primary mb-1 w-100' onclick='gunakanTemplateEdukasi(" . json_encode($row['isi_edukasi']) . ")'>";
                                        echo "<i class='fas fa-check'></i> Gunakan";
                                        echo "</button>";
                                        // Debugging to check if link_gambar exists and has values
                                        echo "<button type='button' class='btn btn-sm btn-info w-100 mt-1' onclick='lihatGambarEdukasi(\"" . (isset($row['link_gambar']) ? htmlspecialchars($row['link_gambar']) : "https://via.placeholder.com/400x300?text=No+Image") . "\", \"" . htmlspecialchars($row['judul']) . "\")'>";
                                        echo "<i class='fas fa-image'></i> Lihat Gambar";
                                        echo "</button>";
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='text-center'>Tidak ada template edukasi tersedia</td></tr>";
                                }
                            } catch (PDOException $e) {
                                echo "<tr><td colspan='6' class='text-center text-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                            }

                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Function to auto-resize textareas based on content
    function autoResizeTextarea(textarea) {
        // Reset height to auto to get the correct scrollHeight
        textarea.style.height = 'auto';
        // Set the height to match the content (scrollHeight)
        textarea.style.height = (textarea.scrollHeight) + 'px';
    }

    // Tab functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize auto-resize for the Riwayat Sekarang textarea
        const riwayatSekarangTextarea = document.getElementById('riwayat_sekarang');
        if (riwayatSekarangTextarea) {
            // Initial resize (if there's content)
            autoResizeTextarea(riwayatSekarangTextarea);

            // Add input event listener to resize as user types
            riwayatSekarangTextarea.addEventListener('input', function() {
                autoResizeTextarea(this);
            });
        }
        // Identitas tab
        const identitasTab = document.getElementById('identitas-tab');
        const identitasContent = document.getElementById('identitas');

        // Status Obstetri tab
        const skriningTab = document.getElementById('skrining-tab');
        const skriningContent = document.getElementById('skrining');

        // Riwayat Kehamilan tab
        const riwayatKehamilanTab = document.getElementById('riwayat-kehamilan-tab');
        const riwayatKehamilanContent = document.getElementById('riwayat-kehamilan');

        // Status Ginekologi tab
        const statusGinekologiTab = document.getElementById('status-ginekologi-tab');
        const statusGinekologiContent = document.getElementById('status-ginekologi');

        // Setup default state
        identitasContent.style.display = 'block';
        identitasContent.classList.add('show');
        skriningContent.style.display = 'none';
        riwayatKehamilanContent.style.display = 'none';
        statusGinekologiContent.style.display = 'none';

        // Pastikan tab akif memiliki class active
        identitasTab.classList.add('active');
        identitasTab.classList.remove('collapsed');
        skriningTab.classList.add('collapsed');
        skriningTab.classList.remove('active');
        riwayatKehamilanTab.classList.add('collapsed');
        riwayatKehamilanTab.classList.remove('active');
        statusGinekologiTab.classList.add('collapsed');
        statusGinekologiTab.classList.remove('active');

        // Log untuk debugging
        debugStatusObstetri('Tab system initialized');
        debugStatusObstetri('identitasTab: ' + (identitasTab ? 'found' : 'not found'));
        debugStatusObstetri('identitasContent: ' + (identitasContent ? 'found' : 'not found'));
        debugStatusObstetri('skriningTab: ' + (skriningTab ? 'found' : 'not found'));
        debugStatusObstetri('skriningContent: ' + (skriningContent ? 'found' : 'not found'));

        // Initialize icon state
        identitasTab.querySelector('i').style.transform = 'rotate(0deg)';
        skriningTab.querySelector('i').style.transform = 'rotate(-90deg)';
        riwayatKehamilanTab.querySelector('i').style.transform = 'rotate(-90deg)';
        statusGinekologiTab.querySelector('i').style.transform = 'rotate(-90deg)';

        // Fungsi untuk menutup semua tab kecuali yang aktif
        function closeAllTabsExcept(activeTabContent) {
            const allTabContents = [identitasContent, skriningContent, riwayatKehamilanContent, statusGinekologiContent];
            const allTabs = [identitasTab, skriningTab, riwayatKehamilanTab, statusGinekologiTab];

            allTabContents.forEach(content => {
                if (content !== activeTabContent) {
                    content.style.display = 'none';
                    content.classList.remove('show');
                }
            });

            allTabs.forEach(tab => {
                if (tab.getAttribute('href') !== '#' + activeTabContent.id) {
                    tab.classList.add('collapsed');
                    tab.classList.remove('active');
                    tab.querySelector('i').style.transform = 'rotate(-90deg)';
                }
            });
        }

        // Add click handlers
        identitasTab.addEventListener('click', function(e) {
            e.preventDefault();
            debugStatusObstetri('Identitas tab clicked');

            if (identitasContent.style.display === 'block') {
                identitasContent.style.display = 'none';
                identitasContent.classList.remove('show');
                this.classList.add('collapsed');
                this.classList.remove('active');
                this.querySelector('i').style.transform = 'rotate(-90deg)';
            } else {
                identitasContent.style.display = 'block';
                identitasContent.classList.add('show');
                closeAllTabsExcept(identitasContent);

                this.classList.remove('collapsed');
                this.classList.add('active');
                this.querySelector('i').style.transform = 'rotate(0deg)';
            }
        });

        skriningTab.addEventListener('click', function(e) {
            e.preventDefault();
            debugStatusObstetri('Status Obstetri tab clicked');

            if (skriningContent.style.display === 'block') {
                skriningContent.style.display = 'none';
                skriningContent.classList.remove('show');
                this.classList.add('collapsed');
                this.classList.remove('active');
                this.querySelector('i').style.transform = 'rotate(-90deg)';
            } else {
                skriningContent.style.display = 'block';
                skriningContent.classList.add('show');
                closeAllTabsExcept(skriningContent);

                this.classList.remove('collapsed');
                this.classList.add('active');
                this.querySelector('i').style.transform = 'rotate(0deg)';

                // Tab status obstetri sudah menggunakan data dari PHP
            }
        });

        // Handler untuk tab Riwayat Kehamilan
        riwayatKehamilanTab.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Riwayat Kehamilan tab clicked');

            if (riwayatKehamilanContent.style.display === 'block') {
                riwayatKehamilanContent.style.display = 'none';
                riwayatKehamilanContent.classList.remove('show');
                this.classList.add('collapsed');
                this.classList.remove('active');
                this.querySelector('i').style.transform = 'rotate(-90deg)';
            } else {
                riwayatKehamilanContent.style.display = 'block';
                riwayatKehamilanContent.classList.add('show');
                closeAllTabsExcept(riwayatKehamilanContent);

                this.classList.remove('collapsed');
                this.classList.add('active');
                this.querySelector('i').style.transform = 'rotate(0deg)';

                // Muat data riwayat kehamilan saat tab dibuka

            }
        });

        // Handler untuk tab Status Ginekologi
        statusGinekologiTab.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Status Ginekologi tab clicked');

            if (statusGinekologiContent.style.display === 'block') {
                statusGinekologiContent.style.display = 'none';
                statusGinekologiContent.classList.remove('show');
                this.classList.add('collapsed');
                this.classList.remove('active');
                this.querySelector('i').style.transform = 'rotate(-90deg)';
            } else {
                statusGinekologiContent.style.display = 'block';
                statusGinekologiContent.classList.add('show');
                closeAllTabsExcept(statusGinekologiContent);

                this.classList.remove('collapsed');
                this.classList.add('active');
                this.querySelector('i').style.transform = 'rotate(0deg)';

                // Muat data status ginekologi saat tab dibuka
                refreshStatusGinekologiData();
                console.log('Calling refreshStatusGinekologiData when tab is opened');
            }
        });
    });

    function gunakanTemplate(isi) {
        const currentValue = document.getElementById('tatalaksana').value;
        if (currentValue && currentValue.trim() !== '') {
            document.getElementById('tatalaksana').value = currentValue + '\n\n' + isi;
        } else {
            document.getElementById('tatalaksana').value = isi;
        }
        $('#modalDaftarTemplate').modal('hide');
    }

    function gunakanTemplateAnamnesis(isi) {
        const textarea = document.getElementById('riwayat_sekarang');
        const currentValue = textarea.value;
        if (currentValue && currentValue.trim() !== '') {
            textarea.value = currentValue + '\n\n' + isi;
        } else {
            textarea.value = isi;
        }
        // Auto-resize the textarea after content is added
        autoResizeTextarea(textarea);
        $('#modalDaftarTemplateAnamnesis').modal('hide');
    }

    function gunakanTemplateUsg(isi) {
        const currentValue = document.getElementById('ultrasonografi').value;
        if (currentValue && currentValue.trim() !== '') {
            document.getElementById('ultrasonografi').value = currentValue + '\n\n' + isi;
        } else {
            document.getElementById('ultrasonografi').value = isi;
        }
        $('#modalDaftarTemplateUsg').modal('hide');
    }

    function gunakanDiagnosis(isi) {
        document.getElementById('diagnosis').value = isi;
        $('#modalRiwayatDiagnosis').modal('hide');
    }

    function gunakanTemplateEdukasi(isi) {
        const currentValue = document.getElementById('edukasi').value;

        // Hapus escape karakter yang mungkin ada
        const cleanedIsi = isi.replace(/\\n/g, '\n').replace(/\\"/g, '"').replace(/\\'/g, "'");

        // Konversi HTML ke teks biasa
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = cleanedIsi;
        const textContent = tempDiv.textContent || tempDiv.innerText || '';

        // Bersihkan spasi dan baris kosong berlebihan
        const cleanedContent = textContent
            .replace(/^\s+|\s+$/g, '') // Hapus whitespace di awal dan akhir
            .replace(/\n\s*\n\s*\n/g, '\n\n'); // Ubah 3 atau lebih baris kosong menjadi 2

        if (currentValue && currentValue.trim() !== '') {
            document.getElementById('edukasi').value = currentValue + '\n\n' + cleanedContent;
        } else {
            document.getElementById('edukasi').value = cleanedContent;
        }
        $('#modalDaftarEdukasi').modal('hide');
    }

    // Fungsi untuk menangani checkbox "Pilih Semua"
    document.getElementById('checkAll').addEventListener('change', function() {
        var checkboxes = document.getElementsByClassName('obat-checkbox');
        for (var checkbox of checkboxes) {
            checkbox.checked = this.checked;
        }
    });

    // Fungsi untuk menambahkan obat yang dipilih ke field resep
    function tambahkanObatTerpilih() {
        var checkboxes = document.getElementsByClassName('obat-checkbox');
        var resepField = document.getElementById('resep');
        var obatTerpilih = [];

        for (var checkbox of checkboxes) {
            if (checkbox.checked) {
                var namaObat = checkbox.getAttribute('data-nama');
                var bentukDosis = checkbox.getAttribute('data-bentuk-dosis');
                var catatan = checkbox.getAttribute('data-catatan');

                var textObat = namaObat + ' - ' + bentukDosis;

                if (catatan) {
                    textObat += '\nCatatan: ' + catatan;
                }
                obatTerpilih.push(textObat);
            }
        }

        if (obatTerpilih.length > 0) {
            var currentValue = resepField.value;
            var newValue = obatTerpilih.join('\n');

            if (currentValue && currentValue.trim() !== '') {
                resepField.value = currentValue + '\n' + newValue;
            } else {
                resepField.value = newValue;
            }
        }

        $('#modalDaftarTemplateResep').modal('hide');
    }

    // Filter untuk template USG
    document.addEventListener('DOMContentLoaded', function() {
        // Tambahkan event listener untuk filter kategori Anamnesis
        document.getElementById('filter_kategori_anamnesis').addEventListener('change', function() {
            var kategori = this.value;
            var rows = document.querySelectorAll('#tabelTemplateAnamnesis tbody tr.template-row');

            rows.forEach(function(row) {
                var rowKategori = row.getAttribute('data-kategori');
                if (kategori === '' || rowKategori === kategori) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Tambahkan event listener untuk filter kategori USG
        document.getElementById('filter_kategori_usg').addEventListener('change', function() {
            var kategori = this.value;
            var rows = document.querySelectorAll('#tabelTemplateUsg tbody tr.template-row');

            rows.forEach(function(row) {
                if (kategori === '' || row.getAttribute('data-kategori') === kategori) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });

            // Perbarui nomor urut yang ditampilkan
            var visibleRows = document.querySelectorAll('#tabelTemplateUsg tbody tr.template-row:not([style*="display: none"])');
            visibleRows.forEach(function(row, index) {
                row.cells[0].textContent = index + 1;
            });

            // Tampilkan pesan jika tidak ada data
            var noDataRow = document.querySelector('#tabelTemplateUsg tbody tr:not(.template-row)');
            if (noDataRow) {
                noDataRow.style.display = visibleRows.length === 0 ? '' : 'none';
            }
        });

        // Tambahkan event listener untuk filter kategori obat dan pencarian nama generik
        function filterTable() {
            var kategori = document.getElementById('filter_kategori_obat').value;
            var searchTerm = document.getElementById('search_generik').value.toLowerCase();
            var rows = document.querySelectorAll('#tabelFormularium tbody tr.obat-row');

            rows.forEach(function(row) {
                var rowKategori = row.getAttribute('data-kategori');
                var namaGenerik = row.cells[2].textContent.toLowerCase(); // Kolom nama generik
                var showByKategori = kategori === '' || rowKategori === kategori;
                var showBySearch = searchTerm === '' || namaGenerik.includes(searchTerm);

                if (showByKategori && showBySearch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });

            // Tampilkan pesan jika tidak ada data
            var visibleRows = document.querySelectorAll('#tabelFormularium tbody tr.obat-row:not([style*="display: none"])');
            if (visibleRows.length === 0) {
                var tbody = document.querySelector('#tabelFormularium tbody');
                var noDataRow = document.querySelector('#tabelFormularium tbody tr.no-data-row');

                if (!noDataRow) {
                    var tr = document.createElement('tr');
                    tr.className = 'no-data-row';
                    tr.innerHTML = '<td colspan="7" class="text-center">Tidak ada data obat yang sesuai dengan kriteria pencarian</td>';
                    tbody.appendChild(tr);
                } else {
                    noDataRow.style.display = '';
                }
            } else {
                var noDataRows = document.querySelectorAll('#tabelFormularium tbody tr.no-data-row');
                noDataRows.forEach(function(row) {
                    row.style.display = 'none';
                });
            }

            // Uncheck "Pilih Semua" checkbox saat filter berubah
            document.getElementById('checkAll').checked = false;
        }

        // Event listener untuk filter kategori
        document.getElementById('filter_kategori_obat').addEventListener('change', filterTable);

        // Event listener untuk pencarian nama generik
        document.getElementById('search_generik').addEventListener('input', filterTable);

        // Inisialisasi DataTables untuk tabel formularium
        $(document).ready(function() {
            var table = $('#tabelFormularium').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
                },
                pageLength: 10,
                order: [
                    [1, 'asc']
                ]
            });

            // Hapus event handler lama untuk filter kategori
            $('select[name="kategori_obat"]').off('change');
        });

        // Tambahkan event listener untuk filter kategori tatalaksana
        document.getElementById('filter_kategori_tatalaksana').addEventListener('change', function() {
            var kategori = this.value;
            var rows = document.querySelectorAll('#tabelTemplateTatalaksana tbody tr.template-row');

            rows.forEach(function(row) {
                if (kategori === '' || row.getAttribute('data-kategori') === kategori) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });

            // Perbarui nomor urut yang ditampilkan
            var visibleRows = document.querySelectorAll('#tabelTemplateTatalaksana tbody tr.template-row:not([style*="display: none"])');
            visibleRows.forEach(function(row, index) {
                row.cells[0].textContent = index + 1;
            });

            // Tampilkan pesan jika tidak ada data
            var tbody = document.querySelector('#tabelTemplateTatalaksana tbody');
            var noDataRow = document.querySelector('#tabelTemplateTatalaksana tbody tr:not(.template-row)');

            if (visibleRows.length === 0) {
                if (!noDataRow) {
                    var tr = document.createElement('tr');
                    tr.className = 'no-data-row';
                    tr.innerHTML = '<td colspan="6" class="text-center">Tidak ada template tersedia untuk kategori ini</td>';
                    tbody.appendChild(tr);
                } else {
                    noDataRow.style.display = '';
                }
            }
        });

        // Filter untuk template edukasi
        function filterTemplateEdukasi() {
            var kategori = document.getElementById('filter_kategori_edukasi').value;
            var searchText = document.getElementById('search_edukasi').value.toLowerCase();
            var rows = document.querySelectorAll('#tabelTemplateEdukasi tbody tr.template-row');

            var hasVisibleRows = false; // Flag untuk mengecek apakah ada baris yang terlihat

            rows.forEach(function(row) {
                var rowJudul = row.cells[1].textContent.toLowerCase();
                var rowIsi = row.cells[2].textContent.toLowerCase();
                var rowTags = row.cells[4].textContent.toLowerCase();

                var matchesKategori = kategori === '' || row.getAttribute('data-kategori') === kategori;
                var matchesSearch = searchText === '' ||
                    rowJudul.includes(searchText) ||
                    rowIsi.includes(searchText) ||
                    rowTags.includes(searchText);

                if (matchesKategori && matchesSearch) {
                    row.style.display = '';
                    hasVisibleRows = true;
                } else {
                    row.style.display = 'none';
                }
            });

            // Perbarui nomor urut yang ditampilkan
            var visibleRows = document.querySelectorAll('#tabelTemplateEdukasi tbody tr.template-row:not([style*="display: none"])');
            visibleRows.forEach(function(row, index) {
                row.cells[0].textContent = index + 1;
            });

            // Tampilkan pesan jika tidak ada data
            var tbody = document.querySelector('#tabelTemplateEdukasi tbody');
            var noDataRow = document.querySelector('#tabelTemplateEdukasi tbody tr.no-data-row');

            if (!hasVisibleRows) {
                if (!noDataRow) {
                    var tr = document.createElement('tr');
                    tr.className = 'no-data-row';
                    tr.innerHTML = '<td colspan="6" class="text-center">Tidak ada template edukasi yang sesuai dengan kriteria pencarian</td>';
                    tbody.appendChild(tr);
                } else {
                    noDataRow.style.display = '';
                }
            } else {
                if (noDataRow) {
                    noDataRow.style.display = 'none';
                }
            }
        }

        document.getElementById('filter_kategori_edukasi').addEventListener('change', filterTemplateEdukasi);
        document.getElementById('search_edukasi').addEventListener('input', filterTemplateEdukasi);
    });

    // Fungsi status obstetri sekarang menggunakan PHP, tidak menggunakan AJAX

    // Memuat Status Obstetri Pertama Kali
    document.addEventListener('DOMContentLoaded', () => {
        // Buat observer untuk tab riwayat kehamilan
        const observerRiwayatKehamilan = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    console.log('Tab content Riwayat Kehamilan terlihat, memanggil refresh data (via IntersectionObserver)');
                    refreshRiwayatKehamilanData();
                    observerRiwayatKehamilan.disconnect();
                }
            });
        });

        if (riwayatKehamilanPane) {
            observerRiwayatKehamilan.observe(riwayatKehamilanPane);
        }

        // Buat observer untuk tab status ginekologi
        const observerStatusGinekologi = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    console.log('Tab content Status Ginekologi terlihat, memanggil refresh data (via IntersectionObserver)');
                    refreshStatusGinekologiData();
                    observerStatusGinekologi.disconnect();
                }
            });
        });

        if (statusGinekologiPane) {
            observerStatusGinekologi.observe(statusGinekologiPane);
        }
    });

    // Fungsi untuk melihat gambar edukasi
    function lihatGambarEdukasi(url, judul) {
        // Set judul modal
        document.getElementById('gambarEdukasiModalLabel').textContent = 'Gambar: ' + judul;

        // Log untuk debugging
        console.log('Original image URL:', url);

        // Set base path untuk gambar
        const basePath = BASE_URL ? BASE_URL + '/uploads/edukasi/' : 'https://srv1151-files.hstgr.io/37b1269c3c524999/files/public_html/uploads/edukasi/';

        // Periksa dan format URL gambar
        if (url && url.trim() !== '') {
            // Jika URL tidak dimulai dengan http/https dan bukan placeholder
            if (!url.startsWith('http') && !url.startsWith('https://') && url !== "https://via.placeholder.com/400x300?text=No+Image") {
                url = basePath + url;
            }
        } else {
            // Jika URL kosong, gunakan placeholder
            url = 'https://via.placeholder.com/400x300?text=Gambar+Tidak+Tersedia';
        }

        console.log('Final image URL:', url);

        // Set gambar ke dalam modal
        const imgHtml = `
            <div class="text-center">
                <img src="${url}" 
                     class="img-fluid" 
                     alt="Gambar Edukasi" 
                     onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300?text=Gambar+Tidak+Ditemukan'; console.log('Failed to load image: ${url}');">
            </div>`;

        document.getElementById('gambarEdukasiContent').innerHTML = imgHtml;

        // Tampilkan modal
        var modal = new bootstrap.Modal(document.getElementById('gambarEdukasiModal'));
        modal.show();
    }



    // Memuat Status Obstetri Pertama Kali
    document.addEventListener('DOMContentLoaded', () => {
        debugStatusObstetri('DOMContentLoaded fired');

        // --- Pendekatan #1: Event click collapse/tab ---
        const skriningTab = document.getElementById('skrining-tab');

        if (skriningTab) {
            debugStatusObstetri('Tab Status Obstetri ditemukan, menambahkan click listener');
            skriningTab.addEventListener('click', function(e) {
                debugStatusObstetri('Tab Status Obstetri diklik');

                // Periksa apakah tab akan terbuka - collapsed class akan dihapus
                // Karena ini kompleks dengan timing Bootstrap, kita akan coba pendekatan lain

                // Set timer untuk loading data setelah tab dibuka (200ms delay)
                // Status obstetri sekarang menggunakan data PHP, tidak perlu AJAX
            });
        }

        // Observer untuk Status Obstetri tidak diperlukan lagi karena menggunakan PHP

        // Buat observer untuk tab riwayat kehamilan
        const observerRiwayatKehamilan = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    console.log('Tab content Riwayat Kehamilan terlihat, memanggil refresh data (via IntersectionObserver)');
                    refreshRiwayatKehamilanData();
                    observerRiwayatKehamilan.disconnect();
                }
            });
        });

        if (riwayatKehamilanPane) {
            observerRiwayatKehamilan.observe(riwayatKehamilanPane);
        }

        // Buat observer untuk tab status ginekologi
        const observerStatusGinekologi = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    console.log('Tab content Status Ginekologi terlihat, memanggil refresh data (via IntersectionObserver)');
                    refreshStatusGinekologiData();
                    observerStatusGinekologi.disconnect();
                }
            });
        });

        if (statusGinekologiPane) {
            observerStatusGinekologi.observe(statusGinekologiPane);
        }
    });
</script>