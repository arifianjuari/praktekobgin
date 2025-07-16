<?php
// Pastikan tidak ada akses langsung ke file ini
if (!defined('BASE_PATH')) {
    // Definisikan BASE_PATH jika belum ada untuk kompatibilitas
    define('BASE_PATH', __DIR__);

    // Log untuk debugging
    error_log("FORM EDIT: BASE_PATH not defined, setting to: " . BASE_PATH);
}

// Enable error reporting untuk debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Tambahkan log error untuk debug
error_log("Form Edit Pemeriksaan: Loading started at " . date('Y-m-d H:i:s'));

// Pastikan session sudah dimulai
if (!isset($_SESSION)) {
    session_start();
}

// Set default source_page jika belum ada
if (!isset($_SESSION['source_page'])) {
    $_SESSION['source_page'] = 'form_edit_pemeriksaan';
}

try {
    // Deteksi apakah ini remote host atau localhost
    $is_remote = !($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1');
    error_log("Form Edit Pemeriksaan: Running on " . ($_SERVER['HTTP_HOST'] ?? 'unknown host') . ", remote: " . ($is_remote ? 'yes' : 'no'));
} catch (Exception $e) {
    error_log("Form Edit Pemeriksaan ERROR: " . $e->getMessage());
    echo "<div class='alert alert-danger'>Error koneksi database: " . $e->getMessage() . "</div>";
}

// Fungsi untuk mendapatkan koneksi database
function getConnection()
{
    global $conn;
    return $conn;
}

// Cek apakah ada data pemeriksaan
// PERUBAHAN: Hanya redirect jika $pemeriksaan tidak diset sama sekali
// Ini memungkinkan $pemeriksaan berisi array kosong (kasus ketika ada di reg_periksa tapi belum ada di penilaian_medis_ralan_kandungan)
if (!isset($pemeriksaan)) {
    $_SESSION['error'] = 'Data pemeriksaan tidak ditemukan';
    $redirect_url = isset($_SERVER['HTTP_HOST']) ?
        ($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/index.php?module=rekam_medis&action=data_pasien') :
        'index.php?module=rekam_medis&action=data_pasien';
    header('Location: ' . $redirect_url);
    exit;
}

// Log untuk debugging
error_log("Form Edit: Using pemeriksaan data: " . print_r($pemeriksaan, true));

// --- Ambil data Status Obstetri dari database (fix GPA, HPHT, TP, dsb.) ---
$obstetri_data = array(
    'gravida' => '0',
    'paritas' => '0',
    'abortus' => '0',
    'tanggal_hpht' => '-',
    'tanggal_tp' => '-',
    'tanggal_tp_penyesuaian' => '-',
    'tb' => '0',
    'faktor_risiko_umum' => '-',
    'faktor_risiko_obstetri' => '-',
    'faktor_risiko_preeklampsia' => '-',
    'hasil_faktor_risiko' => '-'
);
try {
    $conn = getConnection();
    $sql = "SELECT * FROM status_obstetri WHERE no_rkm_medis = :no_rkm_medis ORDER BY updated_at DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':no_rkm_medis', $pasien['no_rkm_medis'], PDO::PARAM_STR);
    $stmt->execute();
    if ($stmt && $stmt->rowCount() > 0) {
        $obstetri_data = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log('Error fetching status_obstetri: ' . $e->getMessage());
}
// --- Akhir ambil data Status Obstetri ---

// --- Ambil data Status Obstetri ---
$statusObstetri = [];
$conn = getConnection(); // Gunakan fungsi getConnection()

// Pastikan koneksi valid
if ($conn) {
    try {
        // Menggunakan PDO dari getConnection()
        $sql_status_obstetri = "SELECT * FROM status_obstetri WHERE no_rkm_medis = :no_rkm_medis ORDER BY updated_at DESC";
        $stmt_status_obstetri = $conn->prepare($sql_status_obstetri);
        $stmt_status_obstetri->bindParam(':no_rkm_medis', $pasien['no_rkm_medis'], PDO::PARAM_STR);
        $stmt_status_obstetri->execute();

        $statusObstetri = $stmt_status_obstetri->fetchAll(PDO::FETCH_ASSOC);
        error_log("Form Edit Pemeriksaan: Fetched " . count($statusObstetri) . " status obstetri records.");
    } catch (PDOException $e) {
        error_log("Form Edit Pemeriksaan Error fetching status obstetri: " . $e->getMessage());
        $_SESSION['error'] = 'Error memuat data status obstetri: ' . $e->getMessage();
    }
} else {
    error_log("Form Edit Pemeriksaan: Database connection not available for status obstetri query.");
    $_SESSION['error'] = 'Koneksi database tidak tersedia untuk memuat status obstetri.';
}
// --- Akhir Ambil data Status Obstetri ---


// --- Ambil data Riwayat Kehamilan ---
$riwayatKehamilan = [];
$conn = getConnection(); // Gunakan fungsi getConnection() lagi

// Pastikan koneksi valid
if ($conn) {
    try {
        // Menggunakan PDO dari getConnection()
        $sql_riwayat_kehamilan = "SELECT * FROM riwayat_kehamilan WHERE no_rkm_medis = :no_rkm_medis ORDER BY no_urut_kehamilan ASC";
        $stmt_riwayat_kehamilan = $conn->prepare($sql_riwayat_kehamilan);
        $stmt_riwayat_kehamilan->bindParam(':no_rkm_medis', $pasien['no_rkm_medis'], PDO::PARAM_STR);
        $stmt_riwayat_kehamilan->execute();

        $riwayatKehamilan = $stmt_riwayat_kehamilan->fetchAll(PDO::FETCH_ASSOC);
        error_log("Form Edit Pemeriksaan: Fetched " . count($riwayatKehamilan) . " riwayat kehamilan records.");
    } catch (PDOException $e) {
        error_log("Form Edit Pemeriksaan Error fetching riwayat kehamilan: " . $e->getMessage());
        $_SESSION['error'] = 'Error memuat data riwayat kehamilan: ' . $e->getMessage();
    }
} else {
    error_log("Form Edit Pemeriksaan: Database connection not available for riwayat kehamilan query.");
    $_SESSION['error'] = 'Koneksi database tidak tersedia untuk memuat riwayat kehamilan.';
}
// --- Akhir Ambil data Riwayat Kehamilan ---


// --- Ambil data Status Ginekologi ---
$statusGinekologi = [];
$conn = getConnection(); // Gunakan fungsi getConnection() lagi

// Pastikan koneksi valid
if ($conn) {
    try {
        // Menggunakan PDO dari getConnection()
        // Query langsung ke tabel status_ginekologi dengan no_rkm_medis
        // Perhatikan bahwa nama kolom menggunakan kapital di awal (seperti dalam model StatusGinekologi.php)
        $sql_status_ginekologi = "SELECT * FROM status_ginekologi WHERE no_rkm_medis = :no_rkm_medis ORDER BY created_at DESC";
        $stmt_status_ginekologi = $conn->prepare($sql_status_ginekologi);
        $stmt_status_ginekologi->bindParam(':no_rkm_medis', $pasien['no_rkm_medis'], PDO::PARAM_STR);
        $stmt_status_ginekologi->execute();

        $statusGinekologi = $stmt_status_ginekologi->fetchAll(PDO::FETCH_ASSOC);
        error_log("Form Edit Pemeriksaan: Fetched " . count($statusGinekologi) . " status ginekologi records.");
    } catch (PDOException $e) {
        error_log("Form Edit Pemeriksaan Error fetching status ginekologi: " . $e->getMessage());
        $_SESSION['error'] = 'Error memuat data status ginekologi: ' . $e->getMessage();
    }
} else {
    error_log("Form Edit Pemeriksaan: Database connection not available for status ginekologi query.");
    $_SESSION['error'] = 'Koneksi database tidak tersedia untuk memuat status ginekologi.';
}
// --- Akhir Ambil data Status Ginekologi ---
?>

<script>
    // --- FUNGSI TOMBOL RESUME (GLOBAL & AMAN) ---
    function hitungUmur(tanggalLahir) {
        var today = new Date();
        var birthDate = new Date(tanggalLahir);
        var age = today.getFullYear() - birthDate.getFullYear();
        var monthDiff = today.getMonth() - birthDate.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        return age;
    }

    function masukkanIdentitasPasien() {
        console.log('masukkanIdentitasPasien dipanggil');
        var namaPasien = "<?= isset($pasien['nm_pasien']) ? $pasien['nm_pasien'] : '' ?>";
        var tglLahir = "<?= isset($pasien['tgl_lahir']) ? date('d-m-Y', strtotime($pasien['tgl_lahir'])) : '' ?>";
        var umur = hitungUmur("<?= isset($pasien['tgl_lahir']) ? $pasien['tgl_lahir'] : '' ?>");
        var identitasPasien = namaPasien.toUpperCase() + "\n";
        identitasPasien += tglLahir + "/" + umur + " thn\n";
        var resumeField = document.getElementById('resume');
        if (!resumeField) {
            alert('Field resume tidak ditemukan!');
            console.error('resumeField tidak ditemukan');
            return;
        }
        resumeField.value = resumeField.value + (resumeField.value ? "\n" : "") + identitasPasien;
        resumeField.value += "\n";
        if (typeof autoResizeTextarea === 'function') {
            autoResizeTextarea(resumeField);
        }
        console.log('Identitas pasien dimasukkan ke resume');
    }

    function masukkanStatusUmum() {
        var td = document.querySelector('input[name="td"]') ? document.querySelector('input[name="td"]').value : '<?= isset($pemeriksaan["td"]) ? $pemeriksaan["td"] : "" ?>';
        var nadi = document.querySelector('input[name="nadi"]') ? document.querySelector('input[name="nadi"]').value : '<?= isset($pemeriksaan["nadi"]) ? $pemeriksaan["nadi"] : "" ?>';
        var bb = document.querySelector('input[name="bb"]') ? document.querySelector('input[name="bb"]').value : '<?= isset($pemeriksaan["bb"]) ? $pemeriksaan["bb"] : "" ?>';
        var statusUmumText = "Status Umum:\n";
        if (td) statusUmumText += "TD: " + td + " mmHg\n";
        if (nadi) statusUmumText += "N: " + nadi + " x/menit\n";
        if (bb) statusUmumText += "BB: " + bb + " kg\n";
        var resumeField = document.getElementById('resume');
        if (!resumeField) {
            alert('Field resume tidak ditemukan!');
            console.error('resumeField tidak ditemukan');
            return;
        }
        var currentContent = resumeField.value;
        if (currentContent.trim() === '') {
            resumeField.value = statusUmumText;
        } else {
            resumeField.value = currentContent + (currentContent.endsWith('\n') ? '' : '\n\n') + statusUmumText + "\n";
        }
        if (typeof autoResizeTextarea === 'function') {
            autoResizeTextarea(resumeField);
        }
        console.log('Status Umum ditambahkan dengan nilai TD:', td, 'Nadi:', nadi, 'BB:', bb);
    }

    function masukkanStatusObstetri() {
        var jenisKelamin = "<?= isset($pasien['jk']) ? $pasien['jk'] : '' ?>";
        if (jenisKelamin !== 'P') {
            alert('Status obstetri hanya berlaku untuk pasien perempuan');
            return;
        }
        var gravida = "<?= isset($obstetri_data['gravida']) ? $obstetri_data['gravida'] : '0' ?>";
        var paritas = "<?= isset($obstetri_data['paritas']) ? $obstetri_data['paritas'] : '0' ?>";
        var abortus = "<?= isset($obstetri_data['abortus']) ? $obstetri_data['abortus'] : '0' ?>";
        var tanggalHpht = "<?= isset($obstetri_data['tanggal_hpht']) && $obstetri_data['tanggal_hpht'] != '0000-00-00' ? date('d-m-Y', strtotime($obstetri_data['tanggal_hpht'])) : '-' ?>";
        var tanggalTpPenyesuaian = "<?= isset($obstetri_data['tanggal_tp_penyesuaian']) && $obstetri_data['tanggal_tp_penyesuaian'] != '0000-00-00' ? date('d-m-Y', strtotime($obstetri_data['tanggal_tp_penyesuaian'])) : '-' ?>";
        var faktorRisikoUmum = "<?= isset($obstetri_data['faktor_risiko_umum']) ? $obstetri_data['faktor_risiko_umum'] : '-' ?>";
        var faktorRisikoObstetri = "<?= isset($obstetri_data['faktor_risiko_obstetri']) ? $obstetri_data['faktor_risiko_obstetri'] : '-' ?>";
        var faktorRisikoPreeklampsia = "<?= isset($obstetri_data['faktor_risiko_preeklampsia']) ? $obstetri_data['faktor_risiko_preeklampsia'] : '-' ?>";
        var hasilFaktorRisiko = "<?= isset($obstetri_data['hasil_faktor_risiko']) ? $obstetri_data['hasil_faktor_risiko'] : '-' ?>";
        var statusObstetriText = "STATUS OBSTETRI:\n";
        statusObstetriText += "G" + gravida + "P" + paritas + "A" + abortus;
        if (tanggalHpht && tanggalHpht !== '-') statusObstetriText += "\nHPHT: " + tanggalHpht;
        if (tanggalTpPenyesuaian && tanggalTpPenyesuaian !== '-') statusObstetriText += "\nTP: " + tanggalTpPenyesuaian;
        var adaFaktorRisiko = false;
        var faktorRisikoText = "\nFaktor Risiko:";
        if (faktorRisikoUmum && faktorRisikoUmum !== '-') {
            faktorRisikoText += "\n" + faktorRisikoUmum;
            adaFaktorRisiko = true;
        }
        if (faktorRisikoObstetri && faktorRisikoObstetri !== '-') {
            faktorRisikoText += " + " + faktorRisikoObstetri;
            adaFaktorRisiko = true;
        }
        if (faktorRisikoPreeklampsia && faktorRisikoPreeklampsia !== '-') {
            faktorRisikoText += "\nFaktor Risiko PE: " + faktorRisikoPreeklampsia;
            adaFaktorRisiko = true;
        }
        if (adaFaktorRisiko) {
            statusObstetriText += faktorRisikoText;
            if (hasilFaktorRisiko && hasilFaktorRisiko !== '-') {
                statusObstetriText += "\nHasil Analisis Faktor Risiko: " + hasilFaktorRisiko;
            }
        }
        statusObstetriText += "\n";
        var resumeField = document.getElementById('resume');
        if (!resumeField) {
            alert('Field resume tidak ditemukan!');
            console.error('resumeField tidak ditemukan');
            return;
        }
        resumeField.value += statusObstetriText;
        if (typeof autoResizeTextarea === 'function') {
            autoResizeTextarea(resumeField);
        }
    }

    // Tambahan: Status Obstetri ke field Diagnosis
    function masukkanStatusObstetriDiagnosis() {
        // Cek jenis kelamin, hanya lanjutkan jika pasien perempuan
        var jenisKelamin = "<?= isset($pasien['jk']) ? $pasien['jk'] : '' ?>";
        if (jenisKelamin !== 'P') {
            alert('Status obstetri hanya berlaku untuk pasien perempuan');
            return;
        }
        var gravida = "<?= isset($obstetri_data['gravida']) ? $obstetri_data['gravida'] : '0' ?>";
        var paritas = "<?= isset($obstetri_data['paritas']) ? $obstetri_data['paritas'] : '0' ?>";
        var abortus = "<?= isset($obstetri_data['abortus']) ? $obstetri_data['abortus'] : '0' ?>";
        var tanggalHpht = "<?= isset($obstetri_data['tanggal_hpht']) && $obstetri_data['tanggal_hpht'] != '0000-00-00' ? date('d-m-Y', strtotime($obstetri_data['tanggal_hpht'])) : '-' ?>";
        var tanggalTp = "<?= isset($obstetri_data['tanggal_tp']) && $obstetri_data['tanggal_tp'] != '0000-00-00' ? date('d-m-Y', strtotime($obstetri_data['tanggal_tp'])) : '-' ?>";
        var tanggalTpPenyesuaian = "<?= isset($obstetri_data['tanggal_tp_penyesuaian']) && $obstetri_data['tanggal_tp_penyesuaian'] != '0000-00-00' ? date('d-m-Y', strtotime($obstetri_data['tanggal_tp_penyesuaian'])) : '-' ?>";
        var faktorRisikoUmum = "<?= isset($obstetri_data['faktor_risiko_umum']) ? $obstetri_data['faktor_risiko_umum'] : '-' ?>";
        var faktorRisikoObstetri = "<?= isset($obstetri_data['faktor_risiko_obstetri']) ? $obstetri_data['faktor_risiko_obstetri'] : '-' ?>";
        var faktorRisikoPreeklampsia = "<?= isset($obstetri_data['faktor_risiko_preeklampsia']) ? $obstetri_data['faktor_risiko_preeklampsia'] : '-' ?>";
        var hasilFaktorRisiko = "<?= isset($obstetri_data['hasil_faktor_risiko']) ? $obstetri_data['hasil_faktor_risiko'] : '-' ?>";

        // Format status obstetri untuk diagnosis
        var statusObstetriText = "G" + gravida + "P" + paritas + "A" + abortus;

        // Hitung usia kehamilan (UK) berdasarkan tanggal_tp_penyesuaian (EDD)
        if (tanggalTpPenyesuaian && tanggalTpPenyesuaian !== '-') {
            function hitungUKdariTP(tanggalTP_ddmmyyyy) {
                const parts = tanggalTP_ddmmyyyy.split('-');
                const tp = new Date(parts[2], parts[1] - 1, parts[0]); // yyyy, mm-1, dd
                const today = new Date();
                const tpClean = new Date(tp.getFullYear(), tp.getMonth(), tp.getDate());
                const todayClean = new Date(today.getFullYear(), today.getMonth(), today.getDate());
                const selisihHari = Math.floor((tpClean - todayClean) / (1000 * 60 * 60 * 24));
                const totalHariKehamilan = 280 - selisihHari;
                const minggu = Math.floor(totalHariKehamilan / 7);
                const hari = totalHariKehamilan % 7;
                let output = `UK ${minggu} minggu`;
                if (hari > 0) output += ` ${hari} hari`;
                if (selisihHari < 0) output += ` (Post Date)`;
                return output;
            }
            var hasilUK = hitungUKdariTP(tanggalTpPenyesuaian);
            statusObstetriText += " " + hasilUK + "\n";
        }

        // Tambahkan faktor risiko jika ada
        var adaFaktorRisiko = false;
        var faktorRisikoText = "+ ";
        if (faktorRisikoUmum && faktorRisikoUmum !== '-') {
            faktorRisikoText += faktorRisikoUmum;
            adaFaktorRisiko = true;
        }
        if (faktorRisikoObstetri && faktorRisikoObstetri !== '-') {
            faktorRisikoText += (adaFaktorRisiko ? " + " : "") + faktorRisikoObstetri;
            adaFaktorRisiko = true;
        }
        if (faktorRisikoPreeklampsia && faktorRisikoPreeklampsia !== '-') {
            faktorRisikoText += (adaFaktorRisiko ? "\n\n+ Risiko PE: " : "PE: ") + faktorRisikoPreeklampsia;
            adaFaktorRisiko = true;
        }
        if (adaFaktorRisiko) {
            statusObstetriText += faktorRisikoText;
            if (hasilFaktorRisiko && hasilFaktorRisiko !== '-') {
                statusObstetriText += " (" + hasilFaktorRisiko + ")";
            }
        }

        // Sisipkan ke field diagnosis
        var diagnosisField = document.getElementById('diagnosis');
        if (!diagnosisField) {
            alert('Field diagnosis tidak ditemukan!');
            console.error('diagnosisField tidak ditemukan');
            return;
        }
        var currentText = diagnosisField.value;
        if (currentText.trim() === '') {
            diagnosisField.value = statusObstetriText;
        } else {
            diagnosisField.value = currentText + "\n" + statusObstetriText;
        }
        if (typeof autoResizeTextarea === 'function') {
            autoResizeTextarea(diagnosisField);
        }
    }

    // TEMPLATE FUNGSI LAIN, SILAKAN LENGKAPI SESUAI KEBUTUHAN
    function masukkanStatusGinekologi() {
        var jenisKelamin = "<?= isset($pasien['jk']) ? $pasien['jk'] : '' ?>";
        if (jenisKelamin !== 'P') {
            alert('Status ginekologi hanya berlaku untuk pasien perempuan');
            return;
        }
        <?php
        // Ambil data status ginekologi terbaru dari hasil query di atas
        $ginekologi_data = [
            'Parturien' => '0',
            'Abortus' => '0',
            'Hari_pertama_haid_terakhir' => null,
            'Kontrasepsi_terakhir' => 'Tidak Ada',
            'lama_menikah_th' => '0'
        ];
        if (isset($statusGinekologi) && count($statusGinekologi) > 0) {
            $ginekologi_data = $statusGinekologi[0];
        }
        ?>
        var parturien = <?= isset($ginekologi_data['Parturien']) ? intval($ginekologi_data['Parturien']) : 0 ?>;
        var abortus = <?= isset($ginekologi_data['Abortus']) ? intval($ginekologi_data['Abortus']) : 0 ?>;
        var hariPertamaHaidTerakhir = "<?= isset($ginekologi_data['Hari_pertama_haid_terakhir']) && $ginekologi_data['Hari_pertama_haid_terakhir'] && $ginekologi_data['Hari_pertama_haid_terakhir'] != '0000-00-00' ? date('d-m-Y', strtotime($ginekologi_data['Hari_pertama_haid_terakhir'])) : '-' ?>";
        var kontrasepsiTerakhir = "<?= isset($ginekologi_data['Kontrasepsi_terakhir']) ? $ginekologi_data['Kontrasepsi_terakhir'] : 'Tidak Ada' ?>";
        var lamaMenikahTh = <?= isset($ginekologi_data['lama_menikah_th']) ? intval($ginekologi_data['lama_menikah_th']) : 0 ?>;

        var statusGinekologiText = "STATUS GINEKOLOGI:\n";
        statusGinekologiText += "P" + parturien;
        statusGinekologiText += "Ab" + abortus + "\n";
        statusGinekologiText += "HPHT: " + (hariPertamaHaidTerakhir !== '-' ? hariPertamaHaidTerakhir : "(tidak ada data)") + "\n";
        statusGinekologiText += "KB Terakhir: " + kontrasepsiTerakhir + "\n";
        statusGinekologiText += "Lama Menikah: " + lamaMenikahTh + " tahun\n";

        var resumeField = document.getElementById('resume');
        if (!resumeField) {
            alert('Field resume tidak ditemukan!');
            console.error('resumeField tidak ditemukan');
            return;
        }
        resumeField.value += (resumeField.value ? "\n" : "") + statusGinekologiText;
        if (typeof autoResizeTextarea === 'function') {
            autoResizeTextarea(resumeField);
        }
    }

    function masukkanPemeriksaanFisik() {
        // Ambil dari field ket_fisik (textarea temuan pemeriksaan fisik) jika ada
        var pemeriksaanFisik = '';
        var ketFisikField = document.getElementsByName('ket_fisik');
        if (ketFisikField && ketFisikField.length > 0) {
            pemeriksaanFisik = ketFisikField[0].value;
        } else if (document.getElementById('pemeriksaan_fisik')) {
            pemeriksaanFisik = document.getElementById('pemeriksaan_fisik').value;
        }
        var resumeField = document.getElementById('resume');
        if (!resumeField) {
            alert('Field resume tidak ditemukan!');
            console.error('resumeField tidak ditemukan');
            return;
        }
        if (pemeriksaanFisik && pemeriksaanFisik.trim() !== '') {
            resumeField.value += (resumeField.value ? "\n" : "") + "PEMERIKSAAN FISIK:\n" + pemeriksaanFisik + "\n";
            if (typeof autoResizeTextarea === 'function') {
                autoResizeTextarea(resumeField);
            }
        } else {
            alert('Isi data pemeriksaan fisik terlebih dahulu!');
        }
    }

    function masukkanHasilUSG() {
        // Ambil data dari field ultrasonografi
        var hasilUSG = document.getElementById('ultrasonografi') ? document.getElementById('ultrasonografi').value : '';
        var resumeField = document.getElementById('resume');
        if (!resumeField) {
            alert('Field resume tidak ditemukan!');
            console.error('resumeField tidak ditemukan');
            return;
        }
        if (hasilUSG) {
            resumeField.value += (resumeField.value ? "\n" : "") + "PEMERIKSAAN USG:\n" + hasilUSG + "\n";
            if (typeof autoResizeTextarea === 'function') {
                autoResizeTextarea(resumeField);
            }
        } else {
            alert('Isi data hasil USG terlebih dahulu!');
        }
    }

    function masukkanDiagnosis() {
        var diagnosis = document.getElementById('diagnosis') ? document.getElementById('diagnosis').value : '';
        var resumeField = document.getElementById('resume');
        if (!resumeField) {
            alert('Field resume tidak ditemukan!');
            console.error('resumeField tidak ditemukan');
            return;
        }
        if (diagnosis) {
            resumeField.value += (resumeField.value ? "\n" : "") + "DIAGNOSIS:\n" + diagnosis + "\n";
            if (typeof autoResizeTextarea === 'function') {
                autoResizeTextarea(resumeField);
            }
        } else {
            alert('Isi data diagnosis terlebih dahulu!');
        }
    }

    function masukkanTatalaksana() {
        var tatalaksana = document.getElementById('tatalaksana') ? document.getElementById('tatalaksana').value : '';
        var resumeField = document.getElementById('resume');
        if (!resumeField) {
            alert('Field resume tidak ditemukan!');
            console.error('resumeField tidak ditemukan');
            return;
        }
        if (tatalaksana) {
            resumeField.value += (resumeField.value ? "\n" : "") + "TATALAKSANA:\n" + tatalaksana + "\n";
            if (typeof autoResizeTextarea === 'function') {
                autoResizeTextarea(resumeField);
            }
        } else {
            alert('Isi data tatalaksana terlebih dahulu!');
        }
    }
    // --- END FUNGSI TOMBOL RESUME ---

    // Fungsi untuk menghitung umur berdasarkan tanggal lahir
    function hitungUmur(tanggalLahir) {
        var today = new Date();
        var birthDate = new Date(tanggalLahir);
        var age = today.getFullYear() - birthDate.getFullYear();
        var monthDiff = today.getMonth() - birthDate.getMonth();

        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }

        return age;
    }

    // Fungsi untuk memasukkan identitas pasien ke field resume
    function masukkanIdentitasPasien() {
        console.log('masukkanIdentitasPasien dipanggil');
        // Ambil data pasien
        var namaPasien = "<?= isset($pasien['nm_pasien']) ? $pasien['nm_pasien'] : '' ?>";
        var tglLahir = "<?= isset($pasien['tgl_lahir']) ? date('d-m-Y', strtotime($pasien['tgl_lahir'])) : '' ?>";
        var umur = hitungUmur("<?= isset($pasien['tgl_lahir']) ? $pasien['tgl_lahir'] : '' ?>");

        // Format identitas pasien
        var identitasPasien = namaPasien.toUpperCase() + "\n";
        identitasPasien += tglLahir + "/" + umur + " thn\n";

        // Sisipkan ke field resume
        var resumeField = document.getElementById('resume');
        if (!resumeField) {
            alert('Field resume tidak ditemukan!');
            console.error('resumeField tidak ditemukan');
            return;
        }
        resumeField.value = resumeField.value + (resumeField.value ? "\n" : "") + identitasPasien;
        resumeField.value += "\n";

        // Auto-resize textarea jika fungsi tersedia
        if (typeof autoResizeTextarea === 'function') {
            autoResizeTextarea(resumeField);
        }
        console.log('Identitas pasien dimasukkan ke resume');
    }

    // Menonaktifkan Service Worker untuk halaman ini
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.getRegistrations().then(function(registrations) {
            for (let registration of registrations) {
                registration.unregister();
                console.log('Service Worker dinonaktifkan untuk halaman Edit Pemeriksaan');
            }
        });

        // Menghindari caching
        if (window.caches) {
            caches.keys().then(function(cacheNames) {
                cacheNames.forEach(function(cacheName) {
                    caches.delete(cacheName);
                    console.log('Cache dihapus:', cacheName);
                });
            });
        }
    }
</script>

<style>
    /* CSS untuk mengatur ukuran font */
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

    .tab-pane:not(.active) {
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

    /* Responsive styles for mobile devices */
    @media (max-width: 767px) {
        .nav-tabs {
            flex-wrap: wrap;
            border-bottom: none;
            margin-bottom: 10px;
        }

        .nav-tabs .nav-item {
            width: 50%;
            margin-bottom: 5px;
            padding: 0 2px;
        }

        .nav-tabs .nav-link {
            font-size: 0.75rem;
            padding: 8px 5px;
            margin-right: 0;
            justify-content: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .nav-tabs .nav-link i.fas.fa-chevron-down {
            margin-left: 3px;
        }

        .nav-tabs .nav-link i:first-child {
            margin-right: 3px;
        }
    }

    .nav-tabs .nav-link.active {
        background-color: #fff;
        border-bottom-color: #fff;
    }

    .nav-tabs .nav-link i {
        margin-left: 5px;
        transition: transform 0.3s;
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

    /* Loading spinner style */
    .loading-overlay {
        position: fixed;
        /* Changed from absolute to fixed */
        top: 0;
        left: 0;
        width: 100vw;
        /* Changed from 100% to 100vw */
        height: 100vh;
        /* Changed from 100% to 100vh */
        background-color: rgba(255, 255, 255, 0.8);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        /* Increased z-index */
    }

    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 5px solid #f3f3f3;
        border-radius: 50%;
        border-top: 5px solid #3498db;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .modal-backdrop {
        opacity: 0.5;
        z-index: 1040;
    }

    .modal {
        z-index: 1050;
    }

    #globalLoadingOverlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.8);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }

    .loading-content {
        text-align: center;
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .spinner-border {
        width: 3rem;
        height: 3rem;
    }

    /* Toggle Gratis Styles */
    .form-check.header-toggle {
        min-width: 55px;
        margin-bottom: 0;
    }

    /* Toggle merah saat tidak aktif */
    .form-check-input.toggle-gratis:not(:checked) {
        background-color: rgb(255, 253, 253);
        border-color: #ff4d4f;
    }

    .form-check-input.toggle-gratis:not(:checked):focus {
        box-shadow: 0 0 0 0.25rem rgba(255, 77, 79, .25);
    }

    .form-check-input.toggle-gratis:not(:checked)::before {
        background-color: #ff4d4f;
    }

    .toggle-spinner {
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        z-index: 2;
    }

    .gratis-status {
        display: none;
        color: #198754;
        font-size: 0.8rem;
        font-weight: 500;
        align-items: center;
        opacity: 0;
        transition: opacity 0.3s;
    }

    .gratis-status.active {
        display: inline-flex;
        opacity: 1;

    }



    /* Toast notification */
    #toast-gratis {
        position: fixed;
        top: 80px;
        right: 30px;
        min-width: 220px;
        z-index: 99999;
        opacity: 0;
        transition: opacity 0.3s;
    }

    #toast-gratis.show {
        opacity: 1;
    }
</style>
<!-- Toast for Gratis Toggle -->
<div id="toast-gratis" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
        <div class="toast-body">
            Status gratis kunjungan berikutnya berhasil diubah.
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
</div>


<div id="globalLoadingOverlay" class="loading-overlay">
    <div class="loading-spinner"></div>
</div>

<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Edit Pemeriksaan Kandungan</h6>
            <a href="index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=<?= $pasien['no_rkm_medis'] ?>" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Lihat Rekam Medis
            </a>
        </div>
        <div class="card-body">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?= $_SESSION['error'] ?>
                    <?php unset($_SESSION['error']) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?= $_SESSION['success'] ?>
                    <?php unset($_SESSION['success']) ?>
                </div>
            <?php endif; ?>

            <!-- Tabs -->
            <ul class="nav nav-tabs mb-0" id="myTab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="identitas-tab" data-toggle="collapse" href="#identitas" role="tab" aria-expanded="true" aria-controls="identitas">
                        <i class="fas fa-user mr-1"></i> Data Praktekobgin <i class="fas fa-chevron-down"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link collapsed" id="skrining-tab" data-toggle="collapse" href="#skrining" role="tab" aria-expanded="false" aria-controls="skrining">
                        <i class="fas fa-clipboard-check mr-1"></i> Status Obstetri <i class="fas fa-chevron-down"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link collapsed" id="riwayat-kehamilan-tab" data-toggle="collapse" href="#riwayat-kehamilan" role="tab">
                        <i class="fas fa-baby mr-1"></i> Riwayat Kehamilan <i class="fas fa-chevron-down"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link collapsed" id="status-ginekologi-tab" data-toggle="collapse" href="#status-ginekologi" role="tab">
                        <i class="fas fa-venus mr-1"></i> Status Ginekologi <i class="fas fa-chevron-down"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link collapsed" id="grafik-imt-tab" data-toggle="collapse" href="#grafik-imt" role="tab">
                        <i class="fas fa-chart-line mr-1"></i> Grafik BB Kehamilan <i class="fas fa-chevron-down"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link collapsed" id="riwayat-tab" data-toggle="collapse" href="#riwayat" role="tab">
                        <i class="fas fa-history mr-1"></i> Riwayat <i class="fas fa-chevron-down"></i>
                    </a>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="myTabContent">
                <!-- Tab Data Praktekobgin -->
                <div class="tab-pane fade show active" id="identitas" role="tabpanel" aria-labelledby="identitas-tab">
                    <div class="py-3">
                        <div class="row">
                            <!-- Kolom Kiri - Data Praktekobgin -->
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="card-title mb-0" style="font-size: 0.9rem;">Informasi Pasien</h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <table class="table table-sm table-hover" style="font-size: 0.85rem;">
                                            <tr>
                                                <th width="140" class="text-muted px-3">No. RM</th>
                                                <td class="px-3"><?= $pasien['no_rkm_medis'] ?></td>
                                            </tr>
                                            <tr>
                                                <th class="text-muted px-3">Nama Pasien</th>
                                                <td class="px-3"><?= $pasien['nm_pasien'] ?></td>
                                            </tr>
                                            <tr>
                                                <th class="text-muted px-3">Tanggal Lahir</th>
                                                <td class="px-3"><?= date('d-m-Y', strtotime($pasien['tgl_lahir'])) ?></td>
                                            </tr>
                                            <tr>
                                                <th class="text-muted px-3">Tgl Periksa</th>
                                                <td class="px-3"><?= date('d-m-Y H:i', strtotime($pemeriksaan['tanggal'])) ?></td>
                                            </tr>
                                            <tr>
                                                <th class="text-muted px-3">Rujukan</th>
                                                <td class="px-3"><?= $pemeriksaan['rujukan'] ?? '-' ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Kolom Tengah - Data Tambahan -->
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="card-title mb-0" style="font-size: 0.9rem;">Informasi Tambahan</h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <table class="table table-sm table-hover" style="font-size: 0.85rem;">
                                            <tr>
                                                <th width="140" class="text-muted px-3">Alamat</th>
                                                <td class="px-3" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= $pasien['alamat'] ?? '-' ?></td>
                                            </tr>
                                            <tr>
                                                <th class="text-muted px-3">No. Telepon</th>
                                                <td class="px-3"><?= $pasien['no_tlp'] ?? '-' ?></td>
                                            </tr>
                                            <tr>
                                                <th class="text-muted px-3">Pekerjaan</th>
                                                <td class="px-3"><?= $pasien['pekerjaan'] ?? '-' ?></td>
                                            </tr>
                                            <tr>
                                                <th class="text-muted px-3">Status Nikah</th>
                                                <td class="px-3"><?= $pasien['stts_nikah'] ?? '-' ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                    

                                </div>
                            </div>

                            <!-- Kolom Ketiga (Ceklist) -->
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header bg-light position-relative">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="card-title mb-0" style="font-size: 0.9rem;">Ceklist</h6>
                                        </div>
                                        <div class="ceklist-buttons position-absolute" style="top: 8px; right: 10px;">
                                            <button type="button" class="btn btn-xs btn-outline-info me-1" style="padding: 0.2rem 0.4rem; font-size: 0.7rem;" data-bs-toggle="modal" data-bs-target="#modalDaftarTemplateCeklist">
                                                <i class="fas fa-list"></i>
                                            </button>
                                            <button type="button" id="saveCeklist" class="btn btn-xs btn-outline-success me-1" style="padding: 0.2rem 0.4rem; font-size: 0.7rem; display: none;">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button" onclick="printCeklist()" class="btn btn-xs btn-outline-success" style="padding: 0.2rem 0.4rem; font-size: 0.7rem;">
                                                <i class="fas fa-print"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="p-3" style="font-size: 0.75rem;">
                                            <div id="ceklistContent"
    contenteditable="true"
    style="white-space: pre-wrap; line-height: 1.3; min-height: 100px; outline: none; font-size: 0.7rem;"
    data-no-rkm-medis="<?= $pasien['no_rkm_medis'] ?>"><?= !empty($pemeriksaan['ceklist']) ? $pemeriksaan['ceklist'] : '-' ?></div>
<input type="hidden" name="ceklist" id="ceklistHidden" value="<?= !empty($pemeriksaan['ceklist']) ? htmlspecialchars($pemeriksaan['ceklist']) : '' ?>">
                                            <!-- Catatan Pasien (editable & AJAX save) -->
                                            <div class="mt-3">
                                                <label for="catatanPasienContent2" style="font-size:0.75rem; font-weight:bold;">Catatan Pasien</label>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div id="catatanPasienContent2"
                                                        contenteditable="true"
                                                        style="white-space: pre-wrap; line-height: 1.3; min-height: 60px; outline: none; font-size: 0.7rem; border:1px solid #e0e0e0; border-radius:4px; padding:8px; width:100%; background:#f9f9f9;"
                                                        data-no-rkm-medis="<?= $pasien['no_rkm_medis'] ?>"><?= htmlspecialchars($pasien['catatan_pasien'] ?? '-') ?></div>
                                                    <button type="button" id="saveCatatanPasien2" class="btn btn-xs btn-success" style="display:none; font-size:0.7rem; padding:0.2rem 0.5rem;">
                                                        <i class="fas fa-save"></i>
                                                    </button>
                                                </div>
                                                <input type="hidden" name="catatan_pasien" id="catatanPasienHidden2" value="<?= htmlspecialchars($pasien['catatan_pasien'] ?? '-') ?>">
                                            </div>
                                            <!-- Sinkronisasi contenteditable dengan input hidden & tombol save -->
                                            <script>
                                                document.addEventListener('DOMContentLoaded', function() {
                                                    // --- CATATAN PASIEN BAWAH CEKLIST ---
                                                    const catatanContent2 = document.getElementById('catatanPasienContent2');
                                                    const catatanHidden2 = document.getElementById('catatanPasienHidden2');
                                                    const saveCatatanBtn2 = document.getElementById('saveCatatanPasien2');
                                                    let catatanOriginal2 = catatanContent2.textContent;
                                                    const noRkmMedis2 = catatanContent2.getAttribute('data-no-rkm-medis');

                                                    catatanContent2.addEventListener('input', function() {
                                                        catatanHidden2.value = catatanContent2.textContent;
                                                        if (catatanContent2.textContent !== catatanOriginal2) {
                                                            saveCatatanBtn2.style.display = 'inline-block';
                                                            saveCatatanBtn2.innerHTML = '<i class="fas fa-save"></i>';
                                                            saveCatatanBtn2.classList.remove('btn-danger');
                                                            saveCatatanBtn2.classList.add('btn-success');
                                                        } else {
                                                            saveCatatanBtn2.style.display = 'none';
                                                        }
                                                    });

                                                    saveCatatanBtn2.addEventListener('click', function() {
                                                        const newCatatan2 = catatanContent2.textContent;
                                                        saveCatatanBtn2.disabled = true;
                                                        saveCatatanBtn2.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

                                                        fetch('index.php?module=rekam_medis&action=update_pemeriksaan', {
                                                                method: 'POST',
                                                                headers: {
                                                                    'Content-Type': 'application/x-www-form-urlencoded',
                                                                    'X-Requested-With': 'XMLHttpRequest'
                                                                },
                                                                body: `no_rkm_medis=${encodeURIComponent(noRkmMedis2)}&catatan_pasien=${encodeURIComponent(newCatatan2)}`
                                                            })
                                                            .then(response => response.json())
                                                            .then(data => {
                                                                if (data.success) {
                                                                    catatanOriginal2 = newCatatan2;
                                                                    saveCatatanBtn2.innerHTML = '<i class="fas fa-check"></i>';
                                                                    saveCatatanBtn2.classList.remove('btn-danger');
                                                                    saveCatatanBtn2.classList.add('btn-success');
                                                                    setTimeout(() => {
                                                                        saveCatatanBtn2.style.display = 'none';
                                                                        saveCatatanBtn2.innerHTML = '<i class="fas fa-save"></i>';
                                                                        saveCatatanBtn2.disabled = false;
                                                                    }, 2000);
                                                                } else {
                                                                    throw new Error(data.message || 'Gagal menyimpan perubahan');
                                                                }
                                                            })
                                                            .catch(error => {
                                                                console.error('Error:', error);
                                                                saveCatatanBtn2.innerHTML = '<i class="fas fa-times"></i>';
                                                                saveCatatanBtn2.classList.remove('btn-success');
                                                                saveCatatanBtn2.classList.add('btn-danger');
                                                                saveCatatanBtn2.disabled = false;
                                                                setTimeout(() => {
                                                                    saveCatatanBtn2.innerHTML = '<i class="fas fa-save"></i>';
                                                                    saveCatatanBtn2.classList.remove('btn-danger');
                                                                    saveCatatanBtn2.classList.add('btn-success');
                                                                }, 2000);
                                                            });
                                                    });

                                                    catatanContent2.addEventListener('keydown', function(e) {
                                                        if (e.key === 'Enter' && e.ctrlKey) {
                                                            e.preventDefault();
                                                            if (saveCatatanBtn2.style.display !== 'none') {
                                                                saveCatatanBtn2.click();
                                                            }
                                                        }
                                                    });
                                                    // --- END CATATAN PASIEN BAWAH CEKLIST ---

                                                    const ceklistContent = document.getElementById('ceklistContent');
                                                    const ceklistHidden = document.getElementById('ceklistHidden');
                                                    const saveButton = document.getElementById('saveCeklist');

                                                    if (ceklistContent && ceklistHidden && saveButton) {
                                                        let originalContent = ceklistContent.innerHTML;
                                                        const noRawat = '<?= $pemeriksaan['no_rawat'] ?? '' ?>';

                                                        // Sync hidden input with HTML content
                                                        ceklistContent.addEventListener('input', function() {
                                                            ceklistHidden.value = ceklistContent.innerHTML;
                                                            if (ceklistContent.innerHTML !== originalContent) {
                                                                saveButton.style.display = '';
                                                                saveButton.classList.remove('btn-success', 'btn-danger');
                                                                saveButton.classList.add('btn-success');
                                                            } else {
                                                                saveButton.style.display = 'none';
                                                            }
                                                        });

                                                        // Handle save button click
                                                        saveButton.addEventListener('click', function() {
                                                            const newContent = ceklistContent.innerHTML;
                                                            saveButton.disabled = true;
                                                            saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

                                                            fetch('index.php?module=rekam_medis&action=update_pemeriksaan', {
                                                                    method: 'POST',
                                                                    headers: {
                                                                        'Content-Type': 'application/x-www-form-urlencoded',
                                                                    },
                                                                    body: `no_rawat=${encodeURIComponent(noRawat)}&ceklist=${encodeURIComponent(newContent)}`
                                                                })
                                                                .then(response => response.json())
                                                                .then(data => {
                                                                    if (data.success) {
                                                                        originalContent = newContent;
                                                                        saveButton.innerHTML = '<i class="fas fa-check"></i>';
                                                                        saveButton.classList.remove('btn-secondary', 'btn-danger');
                                                                        saveButton.classList.add('btn-success');

                                                                        // Sembunyikan tombol setelah 2 detik
                                                                        setTimeout(() => {
                                                                            saveButton.style.display = 'none';
                                                                            saveButton.innerHTML = '<i class="fas fa-save"></i>';
                                                                            saveButton.disabled = false;
                                                                        }, 2000);
                                                                    } else {
                                                                        throw new Error(data.message || 'Gagal menyimpan perubahan');
                                                                    }
                                                                })
                                                                .catch(error => {
                                                                    console.error('Error:', error);
                                                                    saveButton.innerHTML = '<i class="fas fa-times"></i>';
                                                                    saveButton.classList.remove('btn-success');
                                                                    saveButton.classList.add('btn-danger');
                                                                    saveButton.disabled = false;

                                                                    // Kembalikan ke state semula setelah 2 detik
                                                                    setTimeout(() => {
                                                                        saveButton.innerHTML = '<i class="fas fa-save"></i>';
                                                                        saveButton.classList.remove('btn-danger');
                                                                        saveButton.classList.add('btn-success');
                                                                    }, 2000);
                                                                });
                                                        });

                                                        // Handle Ctrl+Enter untuk save
                                                        ceklistContent.addEventListener('keydown', function(e) {
                                                            if (e.key === 'Enter' && e.ctrlKey) {
                                                                e.preventDefault();
                                                                if (saveButton.style.display !== 'none') {
                                                                    saveButton.click();
                                                                }
                                                            }
                                                        });
                                                    }
                                                });
                                            </script>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Status Obstetri -->
                <div class="tab-pane fade collapse" id="skrining" role="tabpanel">
                    <div class="mb-3 d-flex justify-content-between">
                        <h6 class="font-weight-bold">Status Obstetri</h6>
                        <a href="index.php?module=rekam_medis&action=tambah_status_obstetri&no_rkm_medis=<?= $pasien['no_rkm_medis'] ?>&source=form_edit_pemeriksaan<?= isset($_GET['no_rawat']) ? '&no_rawat=' . $_GET['no_rawat'] : '' ?>" class="btn btn-primary btn-sm">
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
                                                    <a href="index.php?module=rekam_medis&action=edit_status_obstetri&id=<?= $so['id_status_obstetri'] ?>&source=form_edit_pemeriksaan<?= isset($_GET['no_rawat']) ? '&no_rawat=' . $_GET['no_rawat'] : '' ?>" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="index.php?module=rekam_medis&action=hapus_status_obstetri&id=<?= $so['id_status_obstetri'] ?>&source=form_edit_pemeriksaan<?= isset($_GET['no_rawat']) ? '&no_rawat=' . $_GET['no_rawat'] : '' ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">
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
                <div class="tab-pane fade collapse" id="riwayat-kehamilan" role="tabpanel">
                    <div class="mb-3 d-flex justify-content-between">
                        <h6 class="font-weight-bold">Riwayat Kehamilan</h6>
                        <a href="index.php?module=rekam_medis&action=tambah_riwayat_kehamilan&no_rkm_medis=<?= $pasien['no_rkm_medis'] ?>&source=form_edit_pemeriksaan<?= isset($_GET['no_rawat']) ? '&no_rawat=' . $_GET['no_rawat'] : '' ?>" class="btn btn-primary btn-sm">
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
                                        <th>Kondisi</th>
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
                                                    <a href="index.php?module=rekam_medis&action=edit_riwayat_kehamilan&id=<?= $rk['id_riwayat_kehamilan'] ?>&source=form_edit_pemeriksaan<?= isset($_GET['no_rawat']) ? '&no_rawat=' . $_GET['no_rawat'] : '' ?>" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="index.php?module=rekam_medis&action=hapus_riwayat_kehamilan&id=<?= $rk['id_riwayat_kehamilan'] ?>&source=form_edit_pemeriksaan<?= isset($_GET['no_rawat']) ? '&no_rawat=' . $_GET['no_rawat'] : '' ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">
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
                <div class="tab-pane fade collapse" id="status-ginekologi" role="tabpanel">
                    <div class="mb-3 d-flex justify-content-between">
                        <h6 class="font-weight-bold">Status Ginekologi</h6>
                        <a href="index.php?module=rekam_medis&action=tambah_status_ginekologi&no_rkm_medis=<?= $pasien['no_rkm_medis'] ?>&source=form_edit_pemeriksaan<?= isset($_GET['no_rawat']) ? '&no_rawat=' . $_GET['no_rawat'] : '' ?>" class="btn btn-primary btn-sm">
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
                                <tbody id="statusGinekologiTableBody">
                                    <?php if (isset($statusGinekologi) && count($statusGinekologi) > 0): ?>
                                        <?php foreach ($statusGinekologi as $sg): ?>
                                            <tr>
                                                <td><?= date('d-m-Y', strtotime($sg['created_at'])) ?></td>
                                                <td><?= htmlspecialchars($sg['Parturien'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars($sg['Abortus'] ?? '-') ?></td>
                                                <td><?= !empty($sg['Hari_pertama_haid_terakhir']) ? date('d-m-Y', strtotime($sg['Hari_pertama_haid_terakhir'])) : '-' ?></td>
                                                <td><?= htmlspecialchars($sg['Kontrasepsi_terakhir'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars($sg['lama_menikah_th'] ?? '-') ?></td>
                                                <td>
                                                    <a href="index.php?module=rekam_medis&action=edit_status_ginekologi&id=<?= $sg['id_status_ginekologi'] ?>&source=form_edit_pemeriksaan<?= isset($_GET['no_rawat']) ? '&no_rawat=' . $_GET['no_rawat'] : '' ?>" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="index.php?module=rekam_medis&action=hapus_status_ginekologi&id=<?= $sg['id_status_ginekologi'] ?>&source=form_edit_pemeriksaan<?= isset($_GET['no_rawat']) ? '&no_rawat=' . $_GET['no_rawat'] : '' ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">
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

                <!-- Tab Grafik Peningkatan Berat Badan -->
                <div class="tab-pane fade collapse" id="grafik-imt" role="tabpanel">
                    <div class="mb-3 d-flex justify-content-between">
                        <h6 class="font-weight-bold">Grafik Peningkatan Berat Badan Kehamilan</h6>
                        <button id="printGrafikIMT" type="button" class="btn btn-primary btn-sm">
                            <i class="fas fa-print"></i> Cetak Grafik
                        </button>
                    </div>

                    <!-- Form Input IMT dan BB -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="card-title mb-0" style="font-size: 0.9rem;">Data IMT dan Berat Badan</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="imt_pra_kehamilan" class="form-label">IMT Pra-Kehamilan</label>
                                            <div class="input-group">
                                                <input type="number" step="0.01" class="form-control form-control-sm" id="imt_pra_kehamilan" placeholder="Masukkan IMT">
                                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#modalHitungIMT">
                                                    <i class="fas fa-calculator"></i>
                                                </button>
                                            </div>
                                            <small class="text-muted">Masukkan IMT atau hitung dengan kalkulator</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="kategori_imt" class="form-label">Kategori IMT</label>
                                            <input type="text" class="form-control form-control-sm" id="kategori_imt" readonly>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="bb_pra_kehamilan" class="form-label">BB Pra-Kehamilan (kg)</label>
                                            <input type="number" step="0.1" class="form-control form-control-sm" id="bb_pra_kehamilan">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="tb_ibu" class="form-label">Tinggi Badan (cm)</label>
                                            <input type="number" step="0.1" class="form-control form-control-sm" id="tb_ibu">
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="minggu_kehamilan" class="form-label">Minggu Kehamilan Saat Ini</label>
                                            <input type="number" class="form-control form-control-sm" id="minggu_kehamilan">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="bb_sekarang" class="form-label">BB Saat Ini (kg)</label>
                                            <input type="number" step="0.1" class="form-control form-control-sm" id="bb_sekarang">
                                        </div>
                                    </div>

                                    <div class="d-grid">
                                        <button class="btn btn-primary btn-sm" id="updateGrafik" type="button">
                                            <i class="fas fa-sync-alt"></i> Update Grafik
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="card-title mb-0" style="font-size: 0.9rem;">Rekomendasi Peningkatan Berat Badan</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Kategori IMT Pra-kehamilan</th>
                                                <th>Rekomendasi Peningkatan BB</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>&lt; 18,5</td>
                                                <td>12,5 - 18 kg</td>
                                            </tr>
                                            <tr>
                                                <td>18,5 - 24,9</td>
                                                <td>11,5 - 16 kg</td>
                                            </tr>
                                            <tr>
                                                <td>25 - 29,9</td>
                                                <td>7 - 11,5 kg</td>
                                            </tr>
                                            <tr>
                                                <td>&gt; 30</td>
                                                <td>5 - 9 kg</td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <div class="alert alert-info mt-3" id="rekomendasiInfo">
                                        <strong>Rekomendasi untuk pasien:</strong>
                                        <span id="rekomendasiText">Silahkan masukkan IMT untuk melihat rekomendasi</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Grafik -->
                    <div class="card">
                        <div class="card-body">
                            <canvas id="grafikIMT" width="100%" height="400"></canvas>
                        </div>
                    </div>

                    <!-- Catatan -->
                    <div class="alert alert-secondary mt-3">
                        <small>
                            <i class="fas fa-info-circle"></i> Grafik ini diadaptasi dari Institute of Medicine (IOM) 2009.
                            Silahkan lihat edukasi di halaman 4-20 untuk informasi lebih lanjut.
                        </small>
                    </div>
                </div>

                <!-- Tab Riwayat -->
                <div class="tab-pane fade collapse" id="riwayat" role="tabpanel">
                    <!-- Riwayat Kunjungan & Pemeriksaan -->
                    <div class="py-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="font-weight-bold mb-0">Riwayat Kunjungan & Pemeriksaan</h6>
                            <a href="index.php?module=rekam_medis&action=tambah_pemeriksaan&no_rkm_medis=<?= $pasien['no_rkm_medis'] ?>&source=form_edit_pemeriksaan" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Tambah Kunjungan
                            </a>
                        </div>

                        <?php if (isset($riwayatPemeriksaan) && count($riwayatPemeriksaan) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered table-striped table-resizable">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="180">Waktu Pemeriksaan<div class="resizer"></div>
                                            </th>
                                            <th width="140">Keluhan Utama<div class="resizer"></div>
                                            </th>
                                            <th>Diagnosis<div class="resizer"></div>
                                            </th>
                                            <th>Tatalaksana<div class="resizer"></div>
                                            </th>
                                            <th>Resep<div class="resizer"></div>
                                            </th>
                                            <th width="80">Aksi<div class="resizer"></div>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($riwayatPemeriksaan as $rp): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= date('d-m-Y', strtotime($rp['tgl_registrasi'])) ?> <?= $rp['jam_reg'] ?></strong>
                                                    <?php if (!empty($rp['nm_dokter'])): ?>
                                                        <br><small>Dr. <?= $rp['nm_dokter'] ?></small>
                                                    <?php endif; ?>
                                                    <div class="small text-muted"><?= $rp['no_rawat'] ?></div>
                                                </td>
                                                <td><?= $rp['keluhan_utama'] ?: '-' ?></td>
                                                <td><?= $rp['diagnosis'] ?: '-' ?></td>
                                                <td><?= $rp['tata'] ?: '-' ?></td>
                                                <td><?= $rp['resep'] ?: '-' ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalDetail<?= str_replace('/', '', $rp['no_rawat']) ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <a href="index.php?module=rekam_medis&action=edit_pemeriksaan&id=<?= $rp['no_rawat'] ?>&source=form_edit_pemeriksaan" class="btn btn-warning btn-sm">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    </div>

                                                    <!-- Modal Detail Pemeriksaan -->
                                                    <div class="modal fade" id="modalDetail<?= str_replace('/', '', $rp['no_rawat']) ?>" tabindex="-1" aria-labelledby="modalDetailLabel<?= str_replace('/', '', $rp['no_rawat']) ?>" aria-hidden="true">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="modalDetailLabel<?= str_replace('/', '', $rp['no_rawat']) ?>">Detail Pemeriksaan</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <table class="table table-sm">
                                                                                <tr>
                                                                                    <th width="150">No. Rawat</th>
                                                                                    <td><?= $rp['no_rawat'] ?></td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <th>Tanggal/Jam</th>
                                                                                    <td><?= date('d-m-Y', strtotime($rp['tgl_registrasi'])) ?> <?= $rp['jam_reg'] ?></td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <th>Dokter</th>
                                                                                    <td><?= !empty($rp['nm_dokter']) ? 'Dr. ' . $rp['nm_dokter'] : '-' ?></td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <th>Status Bayar</th>
                                                                                    <td><?= $rp['status_bayar'] ?></td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <th>Rincian</th>
                                                                                    <td>
                                                                                        <?php
                                                                                        if (!empty($rp['rincian'])) {
                                                                                            echo nl2br(htmlspecialchars($rp['rincian']));
                                                                                        } else {
                                                                                            echo '-';
                                                                                        }
                                                                                        ?>
                                                                                    </td>
                                                                                </tr>
                                                                            </table>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <table class="table table-sm">
                                                                                <tr>
                                                                                    <th width="150">Keluhan Utama</th>
                                                                                    <td><?= $rp['keluhan_utama'] ?: '-' ?></td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <th>Riwayat Penyakit Sekarang</th>
                                                                                    <td><?= $rp['rps'] ?: '-' ?></td>
                                                                                </tr>
                                                                            </table>
                                                                        </div>
                                                                    </div>

                                                                    <h6 class="mb-2 mt-3">Hasil Pemeriksaan<?= $rp['tgl_pemeriksaan'] ? ': ' . date('d-m-Y H:i:s', strtotime($rp['tgl_pemeriksaan'])) : '' ?></h6>
                                                                    <div class="row">
                                                                        <div class="col-md-4">
                                                                            <table class="table table-sm">
                                                                                <tr>
                                                                                    <th width="150">BB/TB</th>
                                                                                    <td><?= ($rp['bb'] || $rp['tb']) ? ($rp['bb'] ?: '-') . ' kg / ' . ($rp['tb'] ?: '-') . ' cm' : '-' ?></td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <th>BMI</th>
                                                                                    <td><?= $rp['bmi'] ? $rp['bmi'] . ' kg/m² (' . $rp['interpretasi_bmi'] . ')' : '-' ?></td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <th>Tekanan Darah</th>
                                                                                    <td><?= $rp['td'] ? $rp['td'] . ' mmHg' : '-' ?></td>
                                                                                </tr>
                                                                            </table>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <table class="table table-sm">
                                                                                <tr>
                                                                                    <th width="150">Ultrasonografi</th>
                                                                                    <td><?= $rp['ultra'] ?: '-' ?></td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <th>Keterangan Fisik</th>
                                                                                    <td><?= $rp['ket_fisik'] ?: '-' ?></td>
                                                                                </tr>
                                                                            </table>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <table class="table table-sm">
                                                                                <tr>
                                                                                    <th width="150">Laboratorium</th>
                                                                                    <td><?= $rp['lab'] ?: '-' ?></td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <th>Diagnosis</th>
                                                                                    <td><?= $rp['diagnosis'] ?: '-' ?></td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <th>Tatalaksana</th>
                                                                                    <td><?= $rp['tata'] ?: '-' ?></td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <th>Resep</th>
                                                                                    <td><?= $rp['resep'] ?: '-' ?></td>
                                                                                </tr>
                                                                            </table>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                                    <?php if (empty($rp['keluhan_utama'])): ?>
                                                                        <a href="index.php?module=rekam_medis&action=form_penilaian_medis_ralan_kandungan&no_rawat=<?= $rp['no_rawat'] ?>&source=form_edit_pemeriksaan" class="btn btn-primary">
                                                                            <i class="fas fa-plus"></i> Tambah Pemeriksaan
                                                                        </a>
                                                                    <?php else: ?>
                                                                        <a href="index.php?module=rekam_medis&action=edit_pemeriksaan&id=<?= $rp['no_rawat'] ?>" class="btn btn-warning">
                                                                            <i class="fas fa-edit"></i> Edit Pemeriksaan
                                                                        </a>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                Belum ada riwayat kunjungan.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <form action="index.php?module=rekam_medis&action=update_pemeriksaan" method="post">
                <input type="hidden" name="no_rawat" value="<?= $pemeriksaan['no_rawat'] ?>">
                <input type="hidden" name="no_rkm_medis" value="<?= $pasien['no_rkm_medis'] ?>">

                <div class="row">
                    <!-- Blok Data Rujukan dipindahkan ke dalam form -->
                    <div class="col-md-12">
                        <div class="card mt-3">
                            <div class="card-header bg-light">

                            </div>
                            <div class="card-body p-3">
                                <div class="mb-2">
                                    <label for="nama_perujuk" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.2rem;">Nama Perujuk</label>
                                    <select class="form-select form-select-sm" id="id_perujuk" name="id_perujuk" style="font-size: 0.8rem;">
                                        <option value="">-- Pilih Perujuk --</option>
                                        <?php
                                        // Ambil data rujukan dari database
                                        try {
                                            $conn = getConnection();
                                            $sql = "SELECT id_perujuk, nama_perujuk, jenis_perujuk FROM rujukan ORDER BY nama_perujuk ASC";
                                            $stmt = $conn->prepare($sql);
                                            $stmt->execute();
                                            $rujukan = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                            
                                            foreach ($rujukan as $r) {
                                                $selected = (isset($pemeriksaan['id_perujuk']) && $pemeriksaan['id_perujuk'] == $r['id_perujuk']) ? 'selected' : '';
                                                echo "<option value='" . htmlspecialchars($r['id_perujuk']) . "' $selected>" . 
                                                     htmlspecialchars($r['nama_perujuk']) . ' (' . htmlspecialchars($r['jenis_perujuk']) . ")</option>";
                                            }
                                        } catch (PDOException $e) {
                                            error_log('Error fetching rujukan: ' . $e->getMessage());
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Kolom 1 -->
                    <div class="col-md-4">


                        <!-- Anamnesis -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">Anamnesis</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <label>Keluhan Utama</label>
                                    <textarea name="keluhan_utama" class="form-control form-control-sm" rows="2"><?= isset($pemeriksaan['keluhan_utama']) ? $pemeriksaan['keluhan_utama'] : '' ?></textarea>
                                </div>
                                <div class="mb-2">
                                    <label>Riwayat Sekarang</label>
                                    <div class="row">
                                        <div class="col-md-9">
                                            <!-- Modified textarea with auto-resize class and styling -->
                                            <textarea name="rps" id="riwayat_sekarang" class="form-control form-control-sm auto-resize" rows="6" style="min-height: 120px; overflow-y: hidden;"><?= isset($pemeriksaan['rps']) ? $pemeriksaan['rps'] : '' ?></textarea>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card border">

                                                <div class="card-body p-2">
                                                    <button type="button" class="btn btn-sm btn-info w-100" data-bs-toggle="modal" data-bs-target="#modalDaftarTemplateAnamnesis">
                                                        <i class="fas fa-list"></i> Lihat Template
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label>Riwayat Penyakit Dahulu</label>
                                    <textarea name="rpd" class="form-control form-control-sm" rows="2"><?= isset($pemeriksaan['rpd']) ? $pemeriksaan['rpd'] : '' ?></textarea>
                                </div>
                                <div class="mb-2">
                                    <label>Alergi</label>
                                    <textarea name="alergi" class="form-control form-control-sm" rows="2"><?= isset($pemeriksaan['alergi']) ? $pemeriksaan['alergi'] : '' ?></textarea>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Kolom 2 -->
                    <div class="col-md-4">
                        <!-- Pemeriksaan Fisik -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">Pemeriksaan Fisik</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-4">
                                        <div class="mb-2">
                                            <label>TD (mmHg)</label>
                                            <input type="text" name="td" class="form-control form-control-sm" value="<?= isset($pemeriksaan['td']) ? $pemeriksaan['td'] : '120/80' ?>">
                                        </div>
                                        <div class="mb-2">
                                            <label>Nadi (x/menit)</label>
                                            <input type="text" name="nadi" class="form-control form-control-sm" value="<?= isset($pemeriksaan['nadi']) ? $pemeriksaan['nadi'] : '90' ?>">
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="mb-2">
                                            <label>RR (x/menit)</label>
                                            <input type="text" name="rr" class="form-control form-control-sm" value="<?= isset($pemeriksaan['rr']) ? $pemeriksaan['rr'] : '16' ?>">
                                        </div>
                                        <div class="mb-2">
                                            <label>Suhu (°C)</label>
                                            <input type="text" name="suhu" class="form-control form-control-sm" value="<?= isset($pemeriksaan['suhu']) ? $pemeriksaan['suhu'] : '36.4' ?>">
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="mb-2">
                                            <label>BB (kg)</label>
                                            <input type="number" name="bb" class="form-control form-control-sm" value="<?= isset($pemeriksaan['bb']) ? $pemeriksaan['bb'] : '' ?>" step="0.1" min="0" max="500" placeholder=" " onkeypress="return (event.charCode >= 48 && event.charCode <= 57) || event.charCode == 46">

                                        </div>
                                        <div class="mb-2">
                                            <label>TB (cm)</label>
                                            <input type="number" name="tb" class="form-control form-control-sm" value="<?= (!empty($pemeriksaan['tb']) && $pemeriksaan['tb'] !== '0') ? htmlspecialchars($pemeriksaan['tb']) : '' ?>" step="0.1" min="0" max="300" onkeypress="return (event.charCode >= 48 && event.charCode <= 57) || event.charCode == 46">

                                        </div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label>Temuan Pemeriksaan Fisik</label>
                                    <textarea name="ket_fisik" class="form-control form-control-sm" rows="1"><?= isset($pemeriksaan['ket_fisik']) ? $pemeriksaan['ket_fisik'] : '' ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Pemeriksaan Penunjang -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">Pemeriksaan Penunjang</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label>Ultrasonografi</label>
                                    <div class="row">
                                        <div class="col-12">
                                            <textarea name="ultra" id="ultrasonografi" class="form-control" rows="10"><?= isset($pemeriksaan['ultra']) ? $pemeriksaan['ultra'] : '' ?></textarea>
                                            <div class="d-flex gap-2 mt-2">
                                                <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalDaftarTemplateUsg">
                                                    <i class="fas fa-list"></i> Lihat Template USG
                                                </button>
                                                <button type="button" onclick="printUsg()" class="btn btn-sm btn-success">
                                                    <i class="fas fa-print"></i> Cetak Hasil USG
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label>Laboratorium</label>
                                    <textarea name="lab" class="form-control" rows="2"><?= isset($pemeriksaan['lab']) ? $pemeriksaan['lab'] : '' ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Pilih Gambar Edukasi -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">Gambar Edukasi</h6>
                            </div>
                            <div class="card-body">
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalPilihGambarEdukasi">
                                    <i class="fas fa-images"></i> Pilih Gambar Edukasi
                                </button>
                            </div>
                        </div>

                        <!-- Gambar Edukasi Terpilih -->
                        <div class="card mb-3" id="cardGambarEdukasi" style="display: none;">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">Gambar Edukasi Terpilih</h6>
                                <button type="button" class="btn btn-sm btn-danger" onclick="hapusGambarTerpilih()">
                                    <i class="fas fa-trash"></i> Hapus
                                </button>
                            </div>
                            <div class="card-body">
                                <input type="hidden" name="gambar_edukasi" id="gambarEdukasiInput">
                                <input type="hidden" name="judul_gambar_edukasi" id="judulGambarEdukasiInput">
                                <div class="text-center">
                                    <a href="#" onclick="bukaGambarDiTabBaru(); return false;" style="cursor: pointer;" title="Klik untuk membuka gambar di tab baru">
                                        <img id="gambarEdukasiTerpilih" src="" class="img-fluid" style="max-height: 300px;" alt="Gambar Edukasi">
                                    </a>
                                    <p class="mt-2" id="judulGambarEdukasiTerpilih"></p>
                                    <small class="text-muted">(Klik gambar untuk membuka di tab baru)</small>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Kolom 3 -->
                    <div class="col-md-4">
                        <!-- Diagnosis & Tatalaksana -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">Diagnosis & Tatalaksana</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label>Diagnosis</label>
                                    <div class="row">
                                        <div class="col-md-8">
                                            <textarea name="diagnosis" id="diagnosis" class="form-control" rows="4"><?= isset($pemeriksaan['diagnosis']) ? $pemeriksaan['diagnosis'] : '' ?></textarea>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card border mb-2">
                                                <div class="card-body p-2">
                                                    <button type="button" class="btn btn-sm btn-info w-100" data-bs-toggle="modal" data-bs-target="#modalRiwayatDiagnosis">
                                                        <i class="fas fa-history"></i> Lihat Riwayat
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="card border">
                                                <div class="card-body p-2">
                                                    <button type="button" class="btn btn-sm btn-info w-100 mb-2" onclick="masukkanStatusObstetriDiagnosis()">
                                                        <i class="fas fa-female"></i> Status Obstetri
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
                                            <textarea name="tata" id="tatalaksana" class="form-control" rows="4"><?= isset($pemeriksaan['tata']) ? $pemeriksaan['tata'] : '' ?></textarea>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card border">

                                                <div class="card-body p-2">
                                                    <button type="button" class="btn btn-sm btn-info w-100" data-bs-toggle="modal" data-bs-target="#modalDaftarTemplate">
                                                        <i class="fas fa-list"></i> Lihat Template
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
                                            <textarea name="edukasi" id="edukasi" class="form-control" rows="5"><?= isset($pemeriksaan['edukasi']) ? $pemeriksaan['edukasi'] : '' ?></textarea>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card border">
                                                <div class="card-body p-2">
                                                    <button type="button" class="btn btn-sm btn-info w-100" data-bs-toggle="modal" data-bs-target="#modalDaftarEdukasi">
                                                        <i class="fas fa-list"></i> Lihat Template
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="card border mt-2">
                                                <div class="card-body p-2">
                                                    <a href="javascript:void(0)" onclick="printEdukasi()" class="btn btn-sm btn-success w-100">
                                                        <i class="fas fa-print"></i> Cetak Edukasi
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label>Resume</label>
                                    <div class="row">
                                        <div class="col-md-8">
                                            <textarea name="resume" id="resume" class="form-control" rows="17"><?= isset($pemeriksaan['resume']) ? $pemeriksaan['resume'] : '' ?></textarea>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card border">
                                                <div class="card-body p-2">
                                                    <button type="button" class="btn btn-sm btn-info w-100 mb-2" onclick="masukkanIdentitasPasien()">
                                                        <i class="fas fa-user-plus"></i> Identitas
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-info w-100 mb-2" onclick="masukkanStatusUmum()">
                                                        <i class="fas fa-heartbeat"></i> Status Umum
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-info w-100 mb-2" onclick="masukkanStatusObstetri()">
                                                        <i class="fas fa-female"></i> Status Obstetri
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-info w-100 mb-2" onclick="masukkanStatusGinekologi()">
                                                        <i class="fas fa-venus"></i> Status Ginekologi
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-info w-100 mb-2" onclick="masukkanPemeriksaanFisik()">
                                                        <i class="fas fa-stethoscope"></i> Periksa Fisik
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-info w-100 mb-2" onclick="masukkanHasilUSG()">
                                                        <i class="fas fa-heartbeat"></i> Hasil USG
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-info w-100 mb-2" onclick="masukkanDiagnosis()">
                                                        <i class="fas fa-tag"></i> Diagnosis
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-info w-100 mb-2" onclick="masukkanTatalaksana()">
                                                        <i class="fas fa-file-medical"></i> Tatalaksana
                                                    </button>
                                                    <a href="javascript:void(0)" onclick="printResume()" class="btn btn-sm btn-success w-100">
                                                        <i class="fas fa-print"></i> Cetak Resume
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label>Resep</label>
                                    <div class="row">
                                        <div class="col-md-8">
                                            <textarea name="resep" id="resep" class="form-control" rows="6"><?= isset($pemeriksaan['resep']) ? $pemeriksaan['resep'] : '' ?></textarea>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card border mb-2">
                                                <div class="card-body p-2">
                                                    <button type="button" class="btn btn-sm btn-info w-100" data-bs-toggle="modal" data-bs-target="#modalDaftarTemplateResep">
                                                        <i class="fas fa-list"></i> Lihat Daftar
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="card border">
                                                <div class="card-body p-2">
                                                    <a href="javascript:void(0)" onclick="printResep()" class="btn btn-sm btn-success w-100">
                                                        <i class="fas fa-print"></i> Cetak Hasil Resep
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label>Tanggal Kontrol</label>
                                    <?php
                                    // Workaround: Prevent SQL error if tanggal_kontrol is empty or invalid
                                    $tanggal_kontrol = '';
                                    if (isset($pemeriksaan['tanggal_kontrol']) && !empty($pemeriksaan['tanggal_kontrol']) && $pemeriksaan['tanggal_kontrol'] !== '0000-00-00') {
                                        // Only use if it's a valid date string (YYYY-MM-DD)
                                        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $pemeriksaan['tanggal_kontrol'])) {
                                            $tanggal_kontrol = $pemeriksaan['tanggal_kontrol'];
                                        }
                                    }
                                    ?>
                                    <input type="date" name="tanggal_kontrol" class="form-control" value="<?= $tanggal_kontrol ?>">
                                    <!-- If the value is empty or invalid, the input will be blank and no SQL error will occur -->
                                </div>
                                <div class="mb-3">
                                    <label>Atensi</label>
                                    <select name="atensi" class="form-select">
                                        <option value="0" <?= (isset($pemeriksaan['atensi']) && $pemeriksaan['atensi'] == '0') ? 'selected' : '' ?>>Tidak</option>
                                        <option value="1" <?= (isset($pemeriksaan['atensi']) && $pemeriksaan['atensi'] == '1') ? 'selected' : '' ?>>Ya</option>
                                    </select>
                                </div>
                                <!-- Toggle Digratiskan untuk Kunjungan Berikutnya -->
                                <div class="mb-3">
                                    <label class="form-label mb-1">Digratiskan untuk Kunjungan Berikutnya</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="checkbox" class="form-check-input toggle-gratis" id="toggleBerikutnyaGratis" data-no-rm="<?= $pasien['no_rkm_medis'] ?>" <?= !empty($pemeriksaan['berikutnya_gratis']) ? 'checked' : '' ?> />
                                        <span id="gratisStatusText" class="gratis-status <?= !empty($pemeriksaan['berikutnya_gratis']) ? 'active' : '' ?>">
                                            <i class="fas fa-check-circle me-1"></i>Gratis untuk Kunjungan Berikutnya
                                        </span>
                                        <div class="toggle-spinner position-relative d-none" id="toggleGratisSpinner" style="width:16px;height:16px;">
                                            <div class="spinner-border spinner-border-sm text-primary" role="status" style="width:16px;height:16px;">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group text-center">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                    <a href="index.php?module=rekam_medis&action=manajemen_antrian" class="btn btn-secondary btn-sm">
                        <i class="fas fa-times"></i> Batal
                    </a>
                </div>
                <!-- Hidden input untuk data pasien yang digunakan oleh fitur Grafik IMT -->
                <input type="hidden" id="hidden_no_rkm_medis" value="<?= $pasien['no_rkm_medis'] ?>">
                <input type="hidden" id="hidden_nm_pasien" value="<?= $pasien['nm_pasien'] ?>">
            </form>

            <!-- Style untuk toggle gratis -->
            <style>
                .toggle-gratis {
                    cursor: pointer;
                    width: 2rem;
                    height: 1rem;
                    accent-color: #198754;
                }

                .gratis-status {
                    font-size: 0.8rem;
                    background-color: #198754;
                    color: white;
                    padding: 2px 8px;
                    border-radius: 3px;
                    display: none;
                    margin-left: 5px;
                    transition: all 0.3s;
                }

                .gratis-status.active {
                    display: inline-block;
                    animation: toggle-pulse 0.5s;
                }

                @keyframes toggle-pulse {
                    0% {
                        transform: scale(1);
                    }

                    50% {
                        transform: scale(1.1);
                    }

                    100% {
                        transform: scale(1);
                    }
                }

                .toggle-spinner {
                    margin-left: 8px;
                }
            </style>

            <script>
                // Handler toggle gratis
                document.addEventListener('DOMContentLoaded', function() {
                    const toggle = document.getElementById('toggleBerikutnyaGratis');
                    const statusText = document.getElementById('gratisStatusText');
                    const spinner = document.getElementById('toggleGratisSpinner');

                    if (toggle) {
                        // Inisialisasi status awal
                        const updateUI = (isActive) => {
                            if (isActive) {
                                statusText.classList.add('active');
                            } else {
                                statusText.classList.remove('active');
                            }
                        };

                        // Set status awal
                        updateUI(toggle.checked);

                        toggle.addEventListener('change', function() {
                            const noRm = this.getAttribute('data-no-rm');
                            console.log('Toggle clicked for no_rkm_medis:', noRm);
                            const isChecked = this.checked ? 1 : 0;
                            const originalState = !isChecked; // Simpan state asli untuk rollback

                            // Update UI sementara
                            updateUI(isChecked);
                            spinner.classList.remove('d-none');
                            toggle.disabled = true;

                            // Kirim request ke server
                            const formData = new FormData();
                            formData.append('no_rkm_medis', noRm);
                            formData.append('berikutnya_gratis', isChecked);

                            // Gunakan URL yang benar ke controller
                            fetch('index.php?module=rekam_medis&action=toggleBerikutnyaGratis', {
                                    method: 'POST',
                                    body: formData,
                                    credentials: 'same-origin',
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest'
                                    }
                                })
                                .then(response => {
                                    if (!response.ok) {
                                        return response.text().then(text => {
                                            throw new Error(`HTTP error! status: ${response.status}, body: ${text}`);
                                        });
                                    }
                                    return response.json();
                                })
                                .then(result => {
                                    if (result && result.status === 'success') {
                                        // Update UI berdasarkan respons
                                        updateUI(!!result.berikutnya_gratis);
                                        toggle.checked = !!result.berikutnya_gratis;
                                        showNotification('Status berhasil diperbarui', 'success');
                                    } else {
                                        throw new Error(result?.message || 'Gagal memperbarui status');
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    // Kembalikan ke state semula
                                    toggle.checked = !isChecked;
                                    updateUI(!isChecked);
                                    showNotification(error.message || 'Terjadi kesalahan koneksi', 'error');
                                })
                                .finally(() => {
                                    spinner.classList.add('d-none');
                                    toggle.disabled = false;
                                });
                        });
                    }

                    // Fungsi untuk menampilkan notifikasi
                    function showNotification(message, type = 'info') {
                        // Hapus notifikasi sebelumnya jika ada
                        const existingNotif = document.querySelector('.custom-notification');
                        if (existingNotif) {
                            existingNotif.remove();
                        }

                        // Buat elemen notifikasi
                        const notification = document.createElement('div');
                        notification.className = `custom-notification alert alert-${type} fixed-top mx-auto mt-2`;
                        notification.style.maxWidth = '500px';
                        notification.style.zIndex = '9999';
                        notification.style.left = '50%';
                        notification.style.transform = 'translateX(-50%)';
                        notification.textContent = message;

                        // Tambahkan ke body
                        document.body.appendChild(notification);

                        // Hapus notifikasi setelah 3 detik
                        setTimeout(() => {
                            notification.remove();
                        }, 3000);
                    }
                });
            </script>

        </div>
    </div>
</div>

<!-- Modal Daftar Template Tatalaksana -->
<div class="modal" id="modalDaftarTemplate" tabindex="-1" aria-labelledby="modalDaftarTemplateLabel" aria-hidden="true">
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
                            // Koneksi ke database
                            // Use PDO connection from config.php
                            $conn = getConnection(); // Get the PDO connection

                            // Query untuk mendapatkan semua template
                            $sql = "SELECT * FROM template_tatalaksana WHERE status = 'active' ORDER BY kategori_tx ASC, nama_template_tx ASC";
                            $stmt = $conn->query($sql);

                            if ($stmt && $stmt->rowCount() > 0) {
                                $no = 1;
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<tr class='template-row' data-kategori='" . htmlspecialchars($row['kategori_tx']) . "'>";
                                    echo "<td>" . $no++ . "</td>";
                                    echo "<td>" . htmlspecialchars($row['nama_template_tx']) . "</td>";
                                    echo "<td><div style='max-height: 100px; overflow-y: auto;'>" . nl2br(htmlspecialchars($row['isi_template_tx'])) . "</div></td>";
                                    echo "<td>" . ucwords($row['kategori_tx']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['tags'] ?? '-') . "</td>";
                                    echo "<td><button type='button' class='btn btn-sm btn-primary w-100' onclick='gunakanTemplateTatalaksana(" . json_encode($row['isi_template_tx']) . ")'><i class='fas fa-check'></i> Gunakan</button></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center'>Tidak ada template tersedia</td></tr>";
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
<div class="modal" id="modalDaftarTemplateUsg" tabindex="-1" aria-labelledby="modalDaftarTemplateUsgLabel" aria-hidden="true">
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
                            <option value="obstetri">Obstetri</option>
                            <option value="ginekologi">Ginekologi</option>
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
                            // Use PDO connection from config.php
                            $conn = getConnection(); // Get the PDO connection

                            // Query untuk mendapatkan semua template
                            $sql = "SELECT * FROM template_usg WHERE status = 'active' ORDER BY kategori_usg ASC, nama_template_usg ASC";
                            $stmt = $conn->query($sql);

                            if ($stmt && $stmt->rowCount() > 0) {
                                $no = 1;
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
                            $no_rkm_medis = $pasien['no_rkm_medis'];

                            // Koneksi ke database
                            // Use PDO connection from config.php
                            $conn = getConnection(); // Get the PDO connection

                            // Query untuk mendapatkan riwayat diagnosis
                            $sql = "SELECT 
                                    pmrk.tanggal, 
                                    pmrk.diagnosis 
                                FROM penilaian_medis_ralan_kandungan pmrk
                                JOIN reg_periksa rp ON pmrk.no_rawat = rp.no_rawat
                                WHERE rp.no_rkm_medis = :no_rkm_medis 
                                AND pmrk.diagnosis IS NOT NULL 
                                AND pmrk.diagnosis != ''
                                ORDER BY pmrk.tanggal DESC";

                            $stmt = $conn->prepare($sql);
                            $stmt->bindParam(':no_rkm_medis', $no_rkm_medis, PDO::PARAM_STR);
                            $stmt->execute();

                            if ($stmt && $stmt->rowCount() > 0) {
                                $no = 1;
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<tr>";
                                    echo "<td>" . $no++ . "</td>";
                                    echo "<td>" . date('d-m-Y', strtotime($row['tanggal'])) . "</td>";
                                    echo "<td><div style='max-height: 100px; overflow-y: auto;'>" . nl2br(htmlspecialchars($row['diagnosis'])) . "</div></td>";
                                    echo "<td>
                                            <button type='button' class='btn btn-sm btn-primary w-100' onclick='gunakanTemplateDiagnosis(" . json_encode($row['diagnosis']) . ")'>
                                                <i class='fas fa-copy'></i> Gunakan
                                            </button>
                                          </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4' class='text-center'>Tidak ada riwayat diagnosis</td></tr>";
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
<!-- Modal Daftar Template Ceklist -->
<div class="modal fade" id="modalDaftarTemplateCeklist" tabindex="-1" aria-labelledby="modalDaftarTemplateCeklistLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDaftarTemplateCeklistLabel">Daftar Template Ceklist</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Filter dan Pencarian -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <select id="filter_kategori_ceklist" class="form-select me-2">
                            <option value="">Semua Kategori</option>
                            <option value="fetomaternal">Fetomaternal</option>
                            <option value="ginekologi umum">Ginekologi Umum</option>
                            <option value="onkogin">Onkogin</option>
                            <option value="fertilitas">Fertilitas</option>
                            <option value="uroginekologi">Uroginekologi</option>
                            <option value="obstetri">Obstetri</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <div class="input-group">
                            <input type="text" id="search_template_ceklist" class="form-control" placeholder="Cari template..." aria-label="Cari template">
                            <button class="btn btn-outline-secondary" type="button" id="clear_search_template_ceklist">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabel Template -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="tabelTemplateCeklist">
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
                            // Data template ceklist harus sudah dikirim dari controller sebagai $templateCeklist
                            if (isset($templateCeklist) && count($templateCeklist) > 0) {
                                $no = 1;
                                foreach ($templateCeklist as $row) {
                                    echo "<tr class='template-row' data-kategori='" . htmlspecialchars($row['kategori_ck']) . "'>";
                                    echo "<td>" . $no++ . "</td>";
                                    echo "<td>" . htmlspecialchars($row['nama_template_ck']) . "</td>";
                                    echo "<td><div style='max-height: 100px; overflow-y: auto;'>" . nl2br(htmlspecialchars($row['isi_template_ck'])) . "</div></td>";
                                    echo "<td>" . htmlspecialchars($row['kategori_ck']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['tags'] ?? '-') . "</td>";
                                    echo "<td>
                                            <button type='button' class='btn btn-sm btn-primary mb-1 w-100' onclick='gunakanTemplateCeklist(" . json_encode($row['isi_template_ck']) . ")'>
                                                <i class='fas fa-copy'></i> Gunakan
                                            </button>
                                          </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center'>Tidak ada data template ceklist</td></tr>";
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

<div class="modal" id="modalDaftarTemplateResep" tabindex="-1" aria-labelledby="modalDaftarTemplateResepLabel" aria-hidden="true">
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
                        <input type="text" id="search_generik" class="form-control" placeholder="Cari...">
                    </div>
                    <div class="col-md-4 text-end">
                        <button type="button" class="btn btn-primary btn-sm" onclick="tambahkanObatTerpilih()">Tambahkan Obat Terpilih</button>
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
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
                                <th width="15%">Nama Obat</th>
                                <th width="10%">Bentuk Sediaan</th>
                                <th width="10%">Dosis</th>
                                <th width="10%">Harga</th>
                                <th width="15%">Farmasi</th>
                                <th width="15%">Catatan</th>
                                <th width="10%">ED</th>
                                <th width="10%">Kategori</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Koneksi ke database
                            // Use PDO connection from config.php
                            $conn = getConnection(); // Get the PDO connection

                            // Query untuk mendapatkan semua data formularium, diurutkan berdasarkan nama obat
                            $sql = "SELECT * FROM formularium WHERE status_aktif = 1 ORDER BY nama_obat ASC";
                            $stmt = $conn->prepare($sql);
                            $stmt->execute();

                            if ($stmt && $stmt->rowCount() > 0) {
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    $bentuk_dosis = $row['bentuk_sediaan'] . ' ' . $row['dosis'];
                                    echo "<tr class='obat-row' data-kategori='" . htmlspecialchars($row['kategori'] ?? '') . "'>";
                                    echo "<td><input type='checkbox' class='form-check-input obat-checkbox' data-nama='" . htmlspecialchars($row['nama_obat'] ?? '') . "' data-bentuk-sediaan='" . htmlspecialchars($row['bentuk_sediaan'] ?? '') . "' data-dosis='" . htmlspecialchars($row['dosis'] ?? '') . "' data-catatan='" . htmlspecialchars($row['catatan_obat'] ?? '') . "'></td>";
                                    echo "<td>" . htmlspecialchars($row['nama_obat'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['bentuk_sediaan'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['dosis'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['harga'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['farmasi'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['catatan_obat'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['ed'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['kategori'] ?? '') . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='9' class='text-center'>Tidak ada data obat</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Modal footer dihapus karena tombol sudah dipindahkan ke atas -->

        </div>
    </div>
</div>

<!-- Modal Daftar Template Edukasi -->
<div class="modal" id="modalDaftarEdukasi" tabindex="-1" aria-labelledby="modalDaftarEdukasiLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDaftarEdukasiLabel">Daftar Template Edukasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Filter Kategori dan Pencarian -->
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
                            // Koneksi ke database
                            // Use PDO connection from config.php
                            $conn = getConnection(); // Get the PDO connection

                            // Query untuk mendapatkan semua template edukasi
                            $sql = "SELECT * FROM edukasi WHERE status_aktif = 1 ORDER BY kategori ASC, judul ASC";
                            $stmt = $conn->query($sql);

                            if ($stmt && $stmt->rowCount() > 0) {
                                $no = 1;
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<tr class='template-row' data-kategori='" . htmlspecialchars($row['kategori']) . "' data-judul='" . htmlspecialchars($row['judul']) . "'>";
                                    echo "<td>" . $no++ . "</td>";
                                    echo "<td>" . htmlspecialchars($row['judul']) . "</td>";
                                    echo "<td><div style='max-height: 100px; overflow-y: auto;'>" . $row['isi_edukasi'] . "</div></td>";
                                    echo "<td>" . ucwords($row['kategori']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['tag'] ?? '-') . "</td>";
                                    echo "<td><button type='button' class='btn btn-sm btn-primary w-100' onclick='gunakanTemplateEdukasi(" . json_encode($row['isi_edukasi']) . ")'><i class='fas fa-check'></i> Gunakan</button></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center'>Tidak ada template edukasi tersedia</td></tr>";
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

<!-- Modal Daftar Template Resume -->
<div class="modal" id="modalDaftarTemplateResume" tabindex="-1" aria-labelledby="modalDaftarTemplateResumeLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDaftarTemplateResumeLabel">Daftar Template Resume</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Filter Kategori dan Pencarian -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <select id="filter_kategori_resume" class="form-select me-2">
                            <option value="">Semua Kategori</option>
                            <option value="fetomaternal">Fetomaternal</option>
                            <option value="ginekologi umum">Ginekologi Umum</option>
                            <option value="onkogin">Onkogin</option>
                            <option value="fertilitas">Fertilitas</option>
                            <option value="uroginekologi">Uroginekologi</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" id="search_resume" class="form-control" placeholder="Cari resume...">
                    </div>
                </div>

                <!-- Tabel Template -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="tabelTemplateResume">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">No</th>
                                <th width="20%">Judul</th>
                                <th width="40%">Isi Resume</th>
                                <th width="15%">Kategori</th>
                                <th width="10%">Tags</th>
                                <th width="10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Koneksi ke database
                            // Use PDO connection from config.php
                            $conn = getConnection(); // Get the PDO connection

                            // Query untuk mendapatkan semua template resume (jika tabel sudah ada)
                            $sql = "SELECT * FROM template_resume WHERE status_aktif = 1 ORDER BY kategori ASC, judul ASC";
                            try {
                                $stmt = $conn->query($sql);

                                if ($stmt && $stmt->rowCount() > 0) {
                                    $no = 1;
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo "<tr class='template-row' data-kategori='" . htmlspecialchars($row['kategori']) . "' data-judul='" . htmlspecialchars($row['judul']) . "'>";
                                        echo "<td>" . $no++ . "</td>";
                                        echo "<td>" . htmlspecialchars($row['judul']) . "</td>";
                                        echo "<td><div style='max-height: 100px; overflow-y: auto;'>" . $row['isi_resume'] . "</div></td>";
                                        echo "<td>" . ucwords($row['kategori']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['tag'] ?? '-') . "</td>";
                                        echo "<td><button type='button' class='btn btn-sm btn-primary w-100' onclick='gunakanTemplateResume(" . json_encode($row['isi_resume']) . ")'><i class='fas fa-check'></i> Gunakan</button></td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='text-center'>Tidak ada template resume tersedia</td></tr>";
                                }
                            } catch (PDOException $e) {
                                echo "<tr><td colspan='6' class='text-center'>Fitur template resume belum tersedia</td></tr>";
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

<!-- Modal Pilih Gambar Edukasi -->
<!-- Modal Daftar Template Anamnesis -->
<div class="modal" id="modalDaftarTemplateAnamnesis" tabindex="-1" aria-labelledby="modalDaftarTemplateAnamnesisLabel" aria-hidden="true">
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
                            // Gunakan koneksi yang sudah ada dari controller
                            global $conn;

                            // Query untuk mengambil semua data template anamnesis
                            $sql = "SELECT * FROM template_anamnesis WHERE status = 'active' ORDER BY kategori_anamnesis ASC, nama_template_anamnesis ASC";
                            $result = $conn->query($sql);

                            if ($result->rowCount() > 0) {
                                $no = 1;
                                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<tr class='template-row' data-kategori='" . htmlspecialchars($row['kategori_anamnesis']) . "'>";
                                    echo "<td>" . $no++ . "</td>";
                                    echo "<td>" . htmlspecialchars($row['nama_template_anamnesis']) . "</td>";
                                    echo "<td><div style='max-height: 100px; overflow-y: auto;'>" . nl2br(htmlspecialchars($row['isi_template_anamnesis'])) . "</div></td>";
                                    echo "<td>" . htmlspecialchars($row['kategori_anamnesis']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['tags'] ?? '-') . "</td>";
                                    echo "<td>
                                            <button type='button' class='btn btn-sm btn-primary mb-1 w-100' onclick='gunakanTemplateRiwayatSekarang(" . json_encode($row['isi_template_anamnesis']) . ")'>
                                                <i class='fas fa-copy'></i> Gunakan
                                            </button>
                                          </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center'>Tidak ada data template anamnesis</td></tr>";
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

<div class="modal fade" id="modalPilihGambarEdukasi" tabindex="-1" aria-labelledby="modalPilihGambarEdukasiLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPilihGambarEdukasiLabel">Pilih Gambar Edukasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <!-- Filter Kategori dan Pencarian -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <select id="filter_kategori_gambar" class="form-select me-2">
                            <option value="">Semua Kategori</option>
                            <option value="fetomaternal">Fetomaternal</option>
                            <option value="ginekologi umum">Ginekologi Umum</option>
                            <option value="onkogin">Onkogin</option>
                            <option value="fertilitas">Fertilitas</option>
                            <option value="uroginekologi">Uroginekologi</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" id="search_gambar" class="form-control" placeholder="Cari judul gambar...">
                    </div>
                </div>

                <!-- Grid Gambar -->
                <div class="row" id="gridGambarEdukasi">
                    <?php
                    // Koneksi ke database
                    // Use PDO connection from config.php
                    $conn = getConnection(); // Get the PDO connection

                    // Query untuk mendapatkan semua gambar edukasi
                    $sql = "SELECT * FROM edukasi WHERE status_aktif = 1 AND link_gambar IS NOT NULL ORDER BY kategori ASC, judul ASC";
                    $stmt = $conn->query($sql);

                    if ($stmt && $stmt->rowCount() > 0) {
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo '<div class="col-md-4 mb-3 gambar-item" data-kategori="' . htmlspecialchars($row['kategori']) . '" data-judul="' . htmlspecialchars($row['judul']) . '">';
                            echo '<div class="card h-100">';
                            echo '<img src="uploads/edukasi/' . htmlspecialchars($row['link_gambar']) . '" class="card-img-top" alt="' . htmlspecialchars($row['judul']) . '" style="height: 200px; object-fit: contain;">';
                            echo '<div class="card-body">';
                            echo '<h6 class="card-title">' . htmlspecialchars($row['judul']) . '</h6>';
                            echo '<p class="card-text small">' . htmlspecialchars($row['kategori']) . '</p>';
                            echo '<button type="button" class="btn btn-primary btn-sm w-100" onclick="gunakanTemplateEdukasi(\'' . htmlspecialchars($row['judul']) . '\')"><i class="fas fa-check"></i> Gunakan</button>';
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                        }
                    } else {

                        echo '<div class="col-12 text-center">Tidak ada gambar edukasi tersedia</div>';
                    }
                    ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Pastikan jQuery dan Bootstrap JS dimuat -->
<script>
    // Fungsi untuk menggunakan template ceklist
    function gunakanTemplateCeklist(isi) {
        try {
            const ceklistContent = document.getElementById('ceklistContent');
            if (!ceklistContent) {
                console.error('Element ceklistContent not found');
                return;
            }

            const currentValue = ceklistContent.textContent.trim();

            // Jika sudah ada konten, tambahkan baris baru
            if (currentValue && currentValue !== '-') {
                ceklistContent.textContent = currentValue + '\n' + isi;
            } else {
                ceklistContent.textContent = isi;
            }

            // Tampilkan tombol simpan
            const saveButton = document.getElementById('saveCeklist');
            if (saveButton) {
                saveButton.style.display = 'inline-block';
            }

            // Update hidden input
            document.getElementById('ceklistHidden').value = ceklistContent.textContent;

            // Close modal
            const modalElement = document.getElementById('modalDaftarTemplateCeklist');
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modalElement && modal) {
                modal.hide();
            }
        } catch (error) {
            console.error('Error in gunakanTemplateCeklist:', error);
            alert('Terjadi kesalahan saat menggunakan template. Silakan coba lagi.');
        }
    }

    // Periksa jika jQuery belum dimuat
    if (typeof jQuery === 'undefined') {
        console.error('jQuery tidak ditemukan. Memuat dari CDN...');
        const jqueryScript = document.createElement('script');
        jqueryScript.src = 'https://code.jquery.com/jquery-3.6.0.min.js';
        jqueryScript.integrity = 'sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=';
        jqueryScript.crossOrigin = 'anonymous';
        document.head.appendChild(jqueryScript);
    }

    // Periksa jika Bootstrap JS belum dimuat
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap JS tidak ditemukan. Memuat dari CDN...');
        const bootstrapScript = document.createElement('script');
        bootstrapScript.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js';
        bootstrapScript.integrity = 'sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4';
        bootstrapScript.crossOrigin = 'anonymous';
        bootstrapScript.onload = function() {
            console.log('Bootstrap JS berhasil dimuat. Menginisialisasi tab...');
            // Inisialisasi tab secara langsung setelah Bootstrap dimuat
            initializeTabs();
        };
        document.head.appendChild(bootstrapScript);
    }

    // Fungsi untuk menginisialisasi tab
    function initializeTabs() {
        if (typeof bootstrap !== 'undefined') {
            // Inisialisasi semua tab
            const tabElements = document.querySelectorAll('#myTab a');
            tabElements.forEach(tabElement => {
                try {
                    // Coba instansiasi Tab
                    const tabInstance = new bootstrap.Tab(tabElement);
                    console.log('Tab berhasil dibuat untuk:', tabElement.id);

                    // Tambahkan event listener
                    tabElement.addEventListener('click', function(e) {
                        e.preventDefault();
                        tabInstance.show();
                    });
                } catch (error) {
                    console.error('Gagal menginisialisasi tab untuk element', tabElement.id, error);
                }
            });

            // Setup event listener untuk tab yang ditampilkan
            const myTabs = document.getElementById('myTab');
            if (myTabs) {
                myTabs.addEventListener('shown.bs.tab', function(event) {
                    const activeTab = event.target;
                    const tabId = activeTab.getAttribute('href').substring(1);
                    console.log('Tab aktif:', tabId);

                    // Load data berdasarkan tab yang aktif
                    if (tabId === 'skrining') {
                        refreshStatusObstetriData();
                    } else if (tabId === 'riwayat-kehamilan') {

                    } else if (tabId === 'status-ginekologi') {
                        refreshStatusGinekologiData();
                    }
                });
            }

            console.log('Semua tab berhasil diinisialisasi');
        } else {
            console.error('Bootstrap JS belum tersedia. Tab tidak dapat diinisialisasi');
        }
    }

    // Inisialisasi tab jika bootstrap sudah tersedia
    if (typeof bootstrap !== 'undefined') {
        document.addEventListener('DOMContentLoaded', initializeTabs);
    }
    // Helper seragam untuk isi field dan tutup modal dengan cleanup robust
    function isiFieldDanTutupModal(fieldId, modalId, isi) {
        let errorMsg = '';
        try {
            const textarea = document.getElementById(fieldId);
            if (textarea) {
                textarea.value = isi;
                textarea.dispatchEvent(new Event('input'));
            }
            // Pastikan modal selalu ditutup dengan Bootstrap API
            const modal = document.getElementById(modalId);
            if (modal && typeof bootstrap !== 'undefined' && bootstrap.Modal && modal) {
                let modalInstance = bootstrap.Modal.getOrCreateInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                    console.debug('Bootstrap modal hide dipanggil untuk', modalId);
                } else {
                    console.warn('Bootstrap modal instance tidak ditemukan untuk', modalId);
                }
                // Fallback: paksa modal hilang jika masih terbuka setelah 100ms
                setTimeout(() => {
                    if (modal.classList.contains('show')) {
                        modal.classList.remove('show');
                        modal.style.display = 'none';
                        modal.setAttribute('aria-hidden', 'true');
                        document.body.classList.remove('modal-open');
                        // Hapus backdrop
                        document.querySelectorAll('.modal-backdrop').forEach(el => el.parentNode.removeChild(el));
                        console.warn('Modal', modalId, 'dipaksa ditutup secara manual (fallback)');
                    }
                }, 100);
            } else {
                console.warn('Bootstrap atau modal tidak ditemukan untuk', modalId);
            }
        } catch (e) {
            errorMsg = e.message || e;
        } finally {
            setTimeout(() => {
                try {
                    document.querySelectorAll('.modal-backdrop').forEach(el => el.parentNode.removeChild(el));
                } catch (e) {
                    console.warn('Backdrop cleanup error:', e);
                }
                try {
                    document.body.classList.remove('modal-open');
                } catch (e) {
                    console.warn('modal-open cleanup error:', e);
                }
                try {
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                    document.body.style.pointerEvents = '';
                } catch (e) {
                    console.warn('body style cleanup error:', e);
                }
                try {
                    document.body.removeAttribute('style');
                } catch (e) {}
            }, 0);
            if (errorMsg) {
                alert('Gagal menutup modal dengan sempurna: ' + errorMsg);
            }
        }
    }

    // Fungsi untuk masing-masing template
    function gunakanTemplateRiwayatSekarang(isi) {
        isiFieldDanTutupModal('riwayat_sekarang', 'modalDaftarTemplateAnamnesis', isi);
    }

    function gunakanTemplateDiagnosis(isi) {
        isiFieldDanTutupModal('diagnosis', 'modalRiwayatDiagnosis', isi);
    }

    function gunakanTemplateTatalaksana(isi) {
        isiFieldDanTutupModal('tatalaksana', 'modalDaftarTemplate', isi);
    }

    function gunakanTemplateEdukasi(isi) {
        isiFieldDanTutupModal('edukasi', 'modalDaftarEdukasi', isi);
    }

    function gunakanTemplateResume(isi) {
        isiFieldDanTutupModal('resume', 'modalDaftarTemplateResume', isi);
    }

    function gunakanTemplateUsg(isi) {
        isiFieldDanTutupModal('ultrasonografi', 'modalDaftarTemplateUsg', isi);
    }

    function gunakanTemplateCeklist(isi) {
        isiFieldDanTutupModal('ceklistContent', 'modalDaftarTemplateCeklist', isi);
        // Ceklist bisa berupa div, jika ya, gunakan innerText
        try {
            const ceklistDiv = document.getElementById('ceklistContent');
            if (ceklistDiv && ceklistDiv.tagName === 'DIV') {
                ceklistDiv.innerText = isi;
            }
        } catch (e) {}
    }

    function gunakanTemplateResep(isi) {
        isiFieldDanTutupModal('resep', 'modalDaftarTemplateResep', isi);
    }
</script>

<?php
// Debug marker akhir file
echo "<!-- END FORM EDIT -->\n";
?>
<script>
    // Always define printResume at the end to avoid override issues
    function printResume() {
        console.log('printResume called'); // Debug marker
        // Ambil isi dari textarea resume
        const isiResume = document.getElementById('resume').value.trim();

        // Validasi isi resume
        if (!isiResume) {
            alert('Mohon isi data resume terlebih dahulu sebelum mencetak');
            return;
        }

        const noRawat = '<?= $pemeriksaan['no_rawat'] ?>';
        const namaPasien = '<?= $pasien['nm_pasien'] ?>';
        const noRm = '<?= $pasien['no_rkm_medis'] ?>';

        // Redirect ke halaman print dengan parameter
        const url = 'modules/rekam_medis/print_resume.php?isi=' + encodeURIComponent(isiResume) +
            '&no_rawat=' + encodeURIComponent(noRawat) +
            '&nama=' + encodeURIComponent(namaPasien) +
            '&no_rm=' + encodeURIComponent(noRm);

        // Buka di tab baru
        window.open(url, '_blank');
    }
</script>



<!-- Script utama aplikasi -->
<script>
    // Function to auto-resize textareas based on content
    function autoResizeTextarea(textarea) {
        // Reset height to auto to get the correct scrollHeight
        textarea.style.height = 'auto';
        // Set the height to match the content (scrollHeight)
        textarea.style.height = (textarea.scrollHeight) + 'px';
    }

    // Function to use template anamnesis content
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

    // Initialize auto-resize for textareas when the DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-resize for Riwayat Sekarang
        const riwayatSekarangTextarea = document.getElementById('riwayat_sekarang');
        if (riwayatSekarangTextarea) {
            // Initial resize (if there's content)
            autoResizeTextarea(riwayatSekarangTextarea);

            // Add input event listener to resize as user types
            riwayatSekarangTextarea.addEventListener('input', function() {
                autoResizeTextarea(this);
            });
        }

        // Auto-resize for Resume
        const resumeTextarea = document.getElementById('resume');
        if (resumeTextarea) {
            // Initial resize (if there's content)
            autoResizeTextarea(resumeTextarea);

            // Add input event listener to resize as user types
            resumeTextarea.addEventListener('input', function() {
                autoResizeTextarea(this);
            });
        }

        // Auto-resize for Resep
        const resepTextarea = document.getElementById('resep');
        if (resepTextarea) {
            // Initial resize (if there's content)
            autoResizeTextarea(resepTextarea);

            // Add input event listener to resize as user types
            resepTextarea.addEventListener('input', function() {
                autoResizeTextarea(this);
            });
        }

        // Auto-resize for Diagnosis
        const diagnosisTextarea = document.getElementById('diagnosis');
        if (diagnosisTextarea) {
            // Initial resize (if there's content)
            autoResizeTextarea(diagnosisTextarea);

            // Add input event listener to resize as user types
            diagnosisTextarea.addEventListener('input', function() {
                autoResizeTextarea(this);
            });
        }

        // Auto-resize for Tatalaksana
        const tatalaksanaTextarea = document.getElementById('tatalaksana');
        if (tatalaksanaTextarea) {
            // Initial resize (if there's content)
            autoResizeTextarea(tatalaksanaTextarea);

            // Add input event listener to resize as user types
            tatalaksanaTextarea.addEventListener('input', function() {
                autoResizeTextarea(this);
            });
        }

        // Auto-resize for Edukasi
        const edukasiTextarea = document.getElementById('edukasi');
        if (edukasiTextarea) {
            // Initial resize (if there's content)
            autoResizeTextarea(edukasiTextarea);

            // Add input event listener to resize as user types
            edukasiTextarea.addEventListener('input', function() {
                autoResizeTextarea(this);
            });
        }

        // Add event listener for template anamnesis category filter
        const filterKategoriAnamnesis = document.getElementById('filter_kategori_anamnesis');
        if (filterKategoriAnamnesis) {
            filterKategoriAnamnesis.addEventListener('change', function() {
                const kategori = this.value;
                const rows = document.querySelectorAll('#tabelTemplateAnamnesis tbody tr.template-row');

                rows.forEach(function(row) {
                    const rowKategori = row.getAttribute('data-kategori');
                    if (kategori === '' || rowKategori === kategori) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
    });

    const loadingManager = {
        overlay: null,
        timeoutId: null,

        init() {
            this.overlay = document.getElementById('globalLoadingOverlay');
        },

        show() {
            if (this.overlay) {
                clearTimeout(this.timeoutId);
                this.overlay.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        },

        hide() {
            if (this.overlay) {
                // Tambah delay kecil untuk memastikan transisi modal selesai
                this.timeoutId = setTimeout(() => {
                    this.overlay.style.display = 'none';
                    document.body.style.overflow = '';
                }, 300);
            }
        }
    };

    // Fungsi untuk mengelola tab collapse/expand
    function setupTabCollapse() {
        const tabLinks = document.querySelectorAll('.nav-link[data-toggle="collapse"]');

        tabLinks.forEach(link => {
            // Set status awal tab
            const targetId = link.getAttribute('href');
            const targetPane = document.querySelector(targetId);

            // Set event listener untuk toggle tab
            link.addEventListener('click', function(e) {
                e.preventDefault();

                // Toggle class active pada tab
                const isActive = this.classList.contains('active');

                // Tutup semua tab terlebih dahulu
                tabLinks.forEach(tab => {
                    if (tab !== this) {
                        tab.classList.add('collapsed');
                        const otherTargetId = tab.getAttribute('href');
                        const otherPane = document.querySelector(otherTargetId);
                        otherPane.classList.remove('show');
                        otherPane.classList.remove('active');
                        tab.classList.remove('active');
                        // Update ikon panah
                        const icon = tab.querySelector('.fa-chevron-down');
                        if (icon) {
                            icon.classList.remove('fa-chevron-up');
                            icon.classList.add('fa-chevron-down');
                        }
                    }
                });

                // Toggle tab yang diklik
                if (isActive) {
                    this.classList.remove('active');
                    targetPane.classList.remove('show');
                    targetPane.classList.remove('active');
                    this.classList.add('collapsed');
                } else {
                    this.classList.add('active');
                    targetPane.classList.add('show');
                    targetPane.classList.add('active');
                    this.classList.remove('collapsed');
                }

                // Update ikon panah
                const icon = this.querySelector('.fa-chevron-down, .fa-chevron-up');
                if (icon) {
                    icon.classList.toggle('fa-chevron-down');
                    icon.classList.toggle('fa-chevron-up');
                }
            });

            // Inisialisasi status awal tab
            if (link.classList.contains('active')) {
                targetPane.classList.add('show', 'active');
                link.classList.remove('collapsed');
                const icon = link.querySelector('.fa-chevron-down');
                if (icon) {
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-up');
                }
            }
        });
    }

    // Inisialisasi saat dokumen dimuat
    document.addEventListener('DOMContentLoaded', () => {
        // Setup tab collapse functionality
        setupTabCollapse();

        // Buka tab Data Praktekobgin secara otomatis
        setTimeout(function() {
            const identitasTab = document.getElementById('identitas-tab');
            if (identitasTab) {
                identitasTab.click(); // Trigger click untuk memastikan tab terbuka
            }
        }, 100);

        loadingManager.init();

        // Event listener untuk modal
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.addEventListener('show.bs.modal', () => {
                loadingManager.hide(); // Pastikan loading hilang saat modal muncul
            });

            modal.addEventListener('hidden.bs.modal', () => {
                loadingManager.hide(); // Pastikan loading hilang saat modal tertutup
            });
        });

        // Inisialisasi event listeners untuk ceklist
        initCeklistEventListeners();
        // Inisialisasi event listeners untuk catatan pasien
        initCatatanPasienEventListeners();
    });

    // Fungsi untuk menginisialisasi event listeners ceklist
    function initCeklistEventListeners() {}

    // Fungsi untuk menginisialisasi event listeners catatan pasien
    function initCatatanPasienEventListeners() {
        const catatanContent = document.getElementById('catatanPasienContent');
        const saveButton = document.getElementById('saveCatatanPasien');
        const hiddenInput = document.getElementById('catatanPasienHidden');

        if (catatanContent && saveButton && hiddenInput) {
            let originalContent = catatanContent.textContent;

            // Tampilkan tombol save ketika konten berubah
            catatanContent.addEventListener('input', function() {
                if (originalContent !== this.textContent) {
                    saveButton.style.display = 'block';
                    hiddenInput.value = this.textContent;
                } else {
                    saveButton.style.display = 'none';
                }
            });

            // Handle klik tombol save
            saveButton.addEventListener('click', function() {
                const noRkmMedis = catatanContent.dataset.noRkmMedis;
                const newContent = catatanContent.textContent;
                loadingManager.show();
                fetch('index.php?module=rekam_medis&action=updatePasien', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `no_rkm_medis=${encodeURIComponent(noRkmMedis)}&catatan_pasien=${encodeURIComponent(newContent)}`
                    })
                    .then(response => response.text())
                    .then(data => {
                        loadingManager.hide();
                        saveButton.style.display = 'none';
                        originalContent = newContent;
                        saveButton.innerHTML = '<i class="fas fa-check"></i>';
                        saveButton.classList.remove('btn-outline-success');
                        saveButton.classList.add('btn-success');
                        setTimeout(() => {
                            saveButton.innerHTML = '<i class="fas fa-check"></i>';
                            saveButton.classList.remove('btn-success');
                            saveButton.classList.add('btn-outline-success');
                        }, 2000);
                    })
                    .catch(error => {
                        loadingManager.hide();
                        console.error('Error:', error);
                        saveButton.innerHTML = '<i class="fas fa-times"></i>';
                        saveButton.classList.remove('btn-outline-success');
                        saveButton.classList.add('btn-danger');
                        setTimeout(() => {
                            saveButton.innerHTML = '<i class="fas fa-check"></i>';
                            saveButton.classList.remove('btn-danger');
                            saveButton.classList.add('btn-outline-success');
                        }, 2000);
                    });
            });

            // Handle Ctrl+Enter untuk menyimpan
            catatanContent.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && e.ctrlKey) {
                    e.preventDefault();
                    if (saveButton.style.display === 'block') {
                        saveButton.click();
                    }
                }
            });
        } else {
            console.warn('Some catatan pasien elements not found:', {
                content: !!catatanContent,
                saveButton: !!saveButton,
                hiddenInput: !!hiddenInput
            });
        }
    }
    // Dapatkan elemen-elemen ceklist
    const ceklistContent = document.getElementById('ceklistContent');
    const saveButton = document.getElementById('saveCeklist');
    const ceklistHidden = document.getElementById('ceklistHidden');

    if (ceklistContent && saveButton && ceklistHidden) {
        let originalContent = ceklistContent.textContent;

        // Tampilkan tombol save ketika konten berubah
        ceklistContent.addEventListener('input', function() {
            if (originalContent !== this.textContent) {
                saveButton.style.display = 'block';
                // Update hidden input untuk form submission
                ceklistHidden.value = this.textContent;
            } else {
                saveButton.style.display = 'none';
            }
        });

        // Handle klik tombol save
        saveButton.addEventListener('click', function() {
            const noRkmMedis = ceklistContent.dataset.noRkmMedis;
            const newContent = ceklistContent.textContent;

            // Tampilkan indikator loading
            loadingManager.show();

            fetch('index.php?module=rekam_medis&action=updatePasien', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `no_rkm_medis=${encodeURIComponent(noRkmMedis)}&ceklist=${encodeURIComponent(newContent)}`
                })
                .then(response => response.text())
                .then(data => {
                    loadingManager.hide();
                    saveButton.style.display = 'none';
                    originalContent = newContent;
                    // Tampilkan indikator sukses
                    saveButton.innerHTML = '<i class="fas fa-check"></i>';
                    saveButton.classList.remove('btn-outline-success');
                    saveButton.classList.add('btn-success');

                    // Kembalikan ke ikon asli setelah beberapa saat
                    setTimeout(() => {
                        saveButton.innerHTML = '<i class="fas fa-check"></i>';
                        saveButton.classList.remove('btn-success');
                        saveButton.classList.add('btn-outline-success');
                    }, 2000);
                })
                .catch(error => {
                    loadingManager.hide();
                    console.error('Error:', error);
                    saveButton.innerHTML = '<i class="fas fa-times"></i>';
                    saveButton.classList.remove('btn-outline-success');
                    saveButton.classList.add('btn-danger');

                    // Kembalikan ke ikon asli setelah beberapa saat
                    setTimeout(() => {
                        saveButton.innerHTML = '<i class="fas fa-check"></i>';
                        saveButton.classList.remove('btn-danger');
                        saveButton.classList.add('btn-outline-success');
                    }, 2000);
                });
        });

        // Handle Ctrl+Enter untuk menyimpan
        ceklistContent.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.ctrlKey) {
                e.preventDefault();
                if (saveButton.style.display === 'block') {
                    saveButton.click();
                }
            }
        });

        console.log('Ceklist event listeners initialized');
    } else {
        console.warn('Some ceklist elements not found:', {
            content: !!ceklistContent,
            saveButton: !!saveButton,
            hiddenInput: !!ceklistHidden
        });
    }
    }



    function printUsg() {
        // Ambil isi dari textarea ultrasonografi
        const isiUsg = document.getElementById('ultrasonografi').value.trim();

        // Validasi isi USG
        if (!isiUsg) {
            alert('Mohon isi data hasil USG terlebih dahulu sebelum mencetak');
            return;
        }

        const noRawat = '<?= $pemeriksaan['no_rawat'] ?>';
        const namaPasien = '<?= $pasien['nm_pasien'] ?>';
        const noRm = '<?= $pasien['no_rkm_medis'] ?>';

        // Redirect ke halaman print dengan parameter
        const url = 'modules/rekam_medis/print_usg.php?isi=' + encodeURIComponent(isiUsg) +
            '&no_rawat=' + encodeURIComponent(noRawat) +
            '&nama=' + encodeURIComponent(namaPasien) +
            '&no_rm=' + encodeURIComponent(noRm);

        // Buka di tab baru
        window.open(url, '_blank');
    }





    function gunakanDiagnosis(isi) {
        document.getElementById('diagnosis').value = isi;
        // Auto-resize after adding content
        autoResizeTextarea(document.getElementById('diagnosis'));
        $('#modalRiwayatDiagnosis').modal('hide');
    }



    function gunakanTemplateResume(isi) {
        try {
            loadingManager.show();
            const currentValue = document.getElementById('resume').value;

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
                document.getElementById('resume').value = currentValue + '\n\n' + cleanedContent;
            } else {
                document.getElementById('resume').value = cleanedContent;
            }
            // Auto-resize after adding content
            autoResizeTextarea(document.getElementById('resume'));
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalDaftarTemplateResume'));
            if (modal) {
                modal.hide();
            }
        } finally {
            loadingManager.hide();
        }
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
                var bentukSediaan = checkbox.getAttribute('data-bentuk-sediaan');
                var dosis = checkbox.getAttribute('data-dosis');
                // Menghilangkan pengambilan data-catatan

                // Format: [nama_obat]     No.
                //          [dosis]
                var textObat = namaObat + '     No.X';
                textObat += '\n         ' + dosis;

                // Menghilangkan penambahan catatan ke teks obat

                obatTerpilih.push(textObat);
            }
        }

        if (obatTerpilih.length > 0) {
            var currentValue = resepField.value;
            var newValue = obatTerpilih.join('\n\n');

            if (currentValue && currentValue.trim() !== '') {
                resepField.value = currentValue + '\n\n' + newValue;
            } else {
                resepField.value = newValue;
            }
            // Auto-resize after adding content
            autoResizeTextarea(resepField);
        }

        // Tutup modal menggunakan Bootstrap 5 API
        const modalElement = document.getElementById('modalDaftarTemplateResep');
        const modalInstance = bootstrap.Modal.getInstance(modalElement);
        if (modalInstance) {
            modalInstance.hide();
        } else {
            // Fallback ke jQuery jika instance tidak ditemukan
            $('#modalDaftarTemplateResep').modal('hide');
        }
    }

    function hitungUmur(tanggalLahir) {
        var today = new Date();
        var birthDate = new Date(tanggalLahir);
        var age = today.getFullYear() - birthDate.getFullYear();
        var monthDiff = today.getMonth() - birthDate.getMonth();

        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }

        return age;
    }

    // Fungsi untuk update dan menjaga format data di resume
    function updateResumeFormat() {
        var resumeField = document.getElementById('resume');
        var resumeText = resumeField.value.trim();

        // Jika kosong, tidak perlu diproses
        if (resumeText === '') {
            return;
        }

        // Data yang akan diambil dari resume
        var identitasData = '';
        var statusObstetriData = '';
        var statusGinekologiData = '';
        var pemeriksaanFisikData = '';
        var hasilUSGData = '';
        var diagnosisData = '';
        var tatalaksanaData = '';
        var otherData = '';

        // Ekstrak data identitas (jika ada)
        if (resumeText.includes("Nama:") && resumeText.includes("Tanggal Lahir:") && resumeText.includes("Umur:")) {
            var identitasMatch = resumeText.match(/Nama:.*\nTanggal Lahir:.*\nUmur:.*tahun\n\n/s);
            if (identitasMatch) {
                identitasData = identitasMatch[0];
                resumeText = resumeText.replace(identitasData, '');
            }
        }

        // Ekstrak data status obstetri (jika ada)
        if (resumeText.includes("STATUS OBSTETRI:")) {
            var obstetriMatch = resumeText.match(/STATUS OBSTETRI:\n(?:.*\n)+?\n+/s);
            if (obstetriMatch) {
                statusObstetriData = obstetriMatch[0];
                resumeText = resumeText.replace(statusObstetriData, '');
            }
        }

        // Ekstrak data status ginekologi (jika ada)
        if (resumeText.includes("STATUS GINEKOLOGI:")) {
            var ginekologiMatch = resumeText.match(/STATUS GINEKOLOGI:\n(?:.*\n)+?\n+/s);
            if (ginekologiMatch) {
                statusGinekologiData = ginekologiMatch[0];
                resumeText = resumeText.replace(statusGinekologiData, '');
            }
        }

        // Ekstrak data pemeriksaan fisik (jika ada)
        if (resumeText.includes("PEMERIKSAAN FISIK:")) {
            var fisikMatch = resumeText.match(/PEMERIKSAAN FISIK:\n(?:.*\n)+?\n+/s);
            if (fisikMatch) {
                pemeriksaanFisikData = fisikMatch[0];
                resumeText = resumeText.replace(pemeriksaanFisikData, '');
            }
        }

        // Ekstrak data hasil USG (jika ada)
        if (resumeText.includes("PEMERIKSAAN USG:")) {
            var usgMatch = resumeText.match(/PEMERIKSAAN USG:\n(?:.*\n)+?\n+/s);
            if (usgMatch) {
                hasilUSGData = usgMatch[0];
                resumeText = resumeText.replace(hasilUSGData, '');
            }
        }

        // Ekstrak data diagnosis (jika ada)
        if (resumeText.includes("DIAGNOSIS:")) {
            var diagnosisMatch = resumeText.match(/DIAGNOSIS:\n(?:.*\n)+?\n+/s);
            if (diagnosisMatch) {
                diagnosisData = diagnosisMatch[0];
                resumeText = resumeText.replace(diagnosisData, '');
            }
        }

        // Ekstrak data tatalaksana (jika ada)
        if (resumeText.includes("TATALAKSANA:")) {
            var tatalaksanaMatch = resumeText.match(/TATALAKSANA:\n(?:.*\n)+?\n+/s);
            if (tatalaksanaMatch) {
                tatalaksanaData = tatalaksanaMatch[0];
                resumeText = resumeText.replace(tatalaksanaData, '');
            }
        }

        // Sisa data lainnya
        otherData = resumeText.trim();

        // Susun ulang data sesuai urutan yang diinginkan
        var newResumeText = '';
        if (identitasData) newResumeText += identitasData;
        if (statusObstetriData) newResumeText += statusObstetriData;
        if (statusGinekologiData) newResumeText += statusGinekologiData;
        if (pemeriksaanFisikData) newResumeText += pemeriksaanFisikData;
        if (hasilUSGData) newResumeText += hasilUSGData;
        if (diagnosisData) newResumeText += diagnosisData;
        if (tatalaksanaData) newResumeText += tatalaksanaData;
        if (otherData) newResumeText += otherData + '\n\n';

        // Update field resume dengan tambahan satu baris kosong
        resumeField.value = newResumeText.trim() + '\n';

        // Auto-resize setelah mengubah konten
        autoResizeTextarea(resumeField);
    }

    function masukkanIdentitasPasien() {
        // Debug: pastikan fungsi terpanggil
        console.log('masukkanIdentitasPasien dipanggil');
        // Ambil data pasien dari tabel
        var namaPasien = "<?= $pasien['nm_pasien'] ?>";
        var tglLahir = "<?= date('d-m-Y', strtotime($pasien['tgl_lahir'])) ?>";
        var umur = hitungUmur("<?= $pasien['tgl_lahir'] ?>");

        // Format identitas pasien dengan nama pasien lebih besar dan bold
        var identitasPasien = namaPasien.toUpperCase() + "\n";
        identitasPasien += tglLahir + "/" + umur + " thn\n";

        // Sisipkan ke field resume
        var resumeField = document.getElementById('resume');
        if (!resumeField) {
            alert('Field resume tidak ditemukan!');
            console.error('resumeField tidak ditemukan');
            return;
        }
        resumeField.value = resumeField.value + (resumeField.value ? "\n" : "") + identitasPasien;
        resumeField.value += "\n";
        // Auto-resize setelah mengubah konten
        if (typeof autoResizeTextarea === 'function') {
            autoResizeTextarea(resumeField);
        }
        // Debug: pastikan selesai
        console.log('Identitas pasien dimasukkan ke resume');
    }

    function masukkanStatusObstetri() {
        // Cek jenis kelamin, hanya lanjutkan jika pasien perempuan
        var jenisKelamin = "<?= isset($pasien['jk']) ? $pasien['jk'] : '' ?>";

        if (jenisKelamin !== 'P') {
            alert('Status obstetri hanya berlaku untuk pasien perempuan');
            return;
        }

        <?php
        // Ambil data obstetri dari database menggunakan query yang disarankan
        $no_rawat = $pemeriksaan['no_rawat'];
        $obstetri_data = array(
            'gravida' => '0',
            'paritas' => '0',
            'abortus' => '0',
            'tanggal_hpht' => '-',
            'tanggal_tp' => '-',
            'tanggal_tp_penyesuaian' => '-',
            'tb' => '0',
            'faktor_risiko_umum' => '-',
            'faktor_risiko_obstetri' => '-',
            'faktor_risiko_preeklampsia' => '-',
            'hasil_faktor_risiko' => '-'
        );

        try {
            $conn = getConnection();
            $sql = "SELECT s.*
                    FROM reg_periksa r 
                    JOIN status_obstetri s ON r.no_rkm_medis = s.no_rkm_medis
                    WHERE r.no_rawat = :no_rawat";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':no_rawat', $no_rawat, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt && $stmt->rowCount() > 0) {
                $obstetri_data = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            // Handle error jika terjadi kesalahan pada query
            error_log("Error fetching obstetri data: " . $e->getMessage());
        }
        ?>

        // Ambil data obstetri dari data yang telah diambil dari database
        var gravida = "<?= isset($obstetri_data['gravida']) ? $obstetri_data['gravida'] : '0' ?>";
        var paritas = "<?= isset($obstetri_data['paritas']) ? $obstetri_data['paritas'] : '0' ?>";
        var abortus = "<?= isset($obstetri_data['abortus']) ? $obstetri_data['abortus'] : '0' ?>";
        var tanggalHpht = "<?= isset($obstetri_data['tanggal_hpht']) && $obstetri_data['tanggal_hpht'] != '0000-00-00' ? date('d-m-Y', strtotime($obstetri_data['tanggal_hpht'])) : '-' ?>";
        var tanggalTp = "<?= isset($obstetri_data['tanggal_tp']) && $obstetri_data['tanggal_tp'] != '0000-00-00' ? date('d-m-Y', strtotime($obstetri_data['tanggal_tp'])) : '-' ?>";
        var tanggalTpPenyesuaian = "<?= isset($obstetri_data['tanggal_tp_penyesuaian']) && $obstetri_data['tanggal_tp_penyesuaian'] != '0000-00-00' ? date('d-m-Y', strtotime($obstetri_data['tanggal_tp_penyesuaian'])) : '-' ?>";
        var tb = "<?= isset($obstetri_data['tb']) ? $obstetri_data['tb'] : '0' ?>";
        var faktorRisikoUmum = "<?= isset($obstetri_data['faktor_risiko_umum']) ? $obstetri_data['faktor_risiko_umum'] : '-' ?>";
        var faktorRisikoObstetri = "<?= isset($obstetri_data['faktor_risiko_obstetri']) ? $obstetri_data['faktor_risiko_obstetri'] : '-' ?>";
        var faktorRisikoPreeklampsia = "<?= isset($obstetri_data['faktor_risiko_preeklampsia']) ? $obstetri_data['faktor_risiko_preeklampsia'] : '-' ?>";
        var hasilFaktorRisiko = "<?= isset($obstetri_data['hasil_faktor_risiko']) ? $obstetri_data['hasil_faktor_risiko'] : '-' ?>";

        // Format status obstetri
        var statusObstetriText = "STATUS OBSTETRI:\n";
        statusObstetriText += "G" + gravida + "P" + paritas + "A" + abortus;

        if (tanggalHpht && tanggalHpht !== '-') {
            statusObstetriText += "\nHPHT: " + tanggalHpht;
        }

        if (tanggalTpPenyesuaian && tanggalTpPenyesuaian !== '-') {
            statusObstetriText += "\nTP: " + tanggalTpPenyesuaian;
        }



        // Tambahkan faktor risiko jika ada
        var adaFaktorRisiko = false;
        var faktorRisikoText = "\nFaktor Risiko:";

        if (faktorRisikoUmum && faktorRisikoUmum !== '-') {
            faktorRisikoText += "\n" + faktorRisikoUmum;
            adaFaktorRisiko = true;
        }

        if (faktorRisikoObstetri && faktorRisikoObstetri !== '-') {
            faktorRisikoText += " + " + faktorRisikoObstetri;
            adaFaktorRisiko = true;
        }

        if (faktorRisikoPreeklampsia && faktorRisikoPreeklampsia !== '-') {
            faktorRisikoText += "\nFaktor Risiko PE: " + faktorRisikoPreeklampsia;
            adaFaktorRisiko = true;
        }

        if (adaFaktorRisiko) {
            statusObstetriText += faktorRisikoText;

            if (hasilFaktorRisiko && hasilFaktorRisiko !== '-') {
                statusObstetriText += "\nHasil Analisis Faktor Risiko: " + hasilFaktorRisiko;
            }
        }

        statusObstetriText += "\n";

        // Sisipkan ke field resume
        var resumeField = document.getElementById('resume');
        resumeField.value += statusObstetriText;
        autoResizeTextarea(resumeField);

        // Update format data
        // updateResumeFormat();
    }

    function masukkanStatusUmum() {
        // Ambil data TD, N, dan BB dari input fields di form
        var td = document.querySelector('input[name="td"]') ? document.querySelector('input[name="td"]').value : '<?= isset($pemeriksaan["td"]) ? $pemeriksaan["td"] : "" ?>';
        var nadi = document.querySelector('input[name="nadi"]') ? document.querySelector('input[name="nadi"]').value : '<?= isset($pemeriksaan["nadi"]) ? $pemeriksaan["nadi"] : "" ?>';
        var bb = document.querySelector('input[name="bb"]') ? document.querySelector('input[name="bb"]').value : '<?= isset($pemeriksaan["bb"]) ? $pemeriksaan["bb"] : "" ?>';

        // Format teks yang akan dimasukkan
        var statusUmumText = "Status Umum:\n";
        if (td) statusUmumText += "TD: " + td + " mmHg\n";
        if (nadi) statusUmumText += "N: " + nadi + " x/menit\n";
        if (bb) statusUmumText += "BB: " + bb + " kg\n";

        // Masukkan ke textarea resume
        var resumeField = document.getElementById('resume');
        var currentContent = resumeField.value;

        // Tambahkan status umum di akhir konten yang sudah ada
        if (currentContent.trim() === '') {
            // Jika field kosong, langsung tambahkan
            resumeField.value = statusUmumText;
        } else {
            // Jika sudah ada konten, tambahkan baris baru dan status umum di akhir
            resumeField.value = currentContent + (currentContent.endsWith('\n') ? '' : '\n\n') + statusUmumText + "\n";
        }

        // Auto resize textarea
        autoResizeTextarea(resumeField);

        // Log untuk debugging
        console.log('Status Umum ditambahkan dengan nilai TD:', td, 'Nadi:', nadi, 'BB:', bb);
    }

    function masukkanStatusObstetriDiagnosis() {
        // Cek jenis kelamin, hanya lanjutkan jika pasien perempuan
        var jenisKelamin = "<?= isset($pasien['jk']) ? $pasien['jk'] : '' ?>";

        if (jenisKelamin !== 'P') {
            alert('Status obstetri hanya berlaku untuk pasien perempuan');
            return;
        }

        <?php
        // Ambil data obstetri dari database menggunakan query yang disarankan
        $no_rawat = $pemeriksaan['no_rawat'];
        $obstetri_data = array(
            'gravida' => '0',
            'paritas' => '0',
            'abortus' => '0',
            'tanggal_hpht' => '-',
            'tanggal_tp' => '-',
            'tanggal_tp_penyesuaian' => '-',
            'tb' => '0',
            'faktor_risiko_umum' => '-',
            'faktor_risiko_obstetri' => '-',
            'faktor_risiko_preeklampsia' => '-',
            'hasil_faktor_risiko' => '-'
        );

        try {
            $conn = getConnection();
            $sql = "SELECT s.*
                    FROM reg_periksa r 
                    JOIN status_obstetri s ON r.no_rkm_medis = s.no_rkm_medis
                    WHERE r.no_rawat = :no_rawat";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':no_rawat', $no_rawat, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt && $stmt->rowCount() > 0) {
                $obstetri_data = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            // Handle error jika terjadi kesalahan pada query
            error_log("Error fetching obstetri data: " . $e->getMessage());
        }
        ?>

        // Ambil data obstetri dari data yang telah diambil dari database
        var gravida = "<?= isset($obstetri_data['gravida']) ? $obstetri_data['gravida'] : '0' ?>";
        var paritas = "<?= isset($obstetri_data['paritas']) ? $obstetri_data['paritas'] : '0' ?>";
        var abortus = "<?= isset($obstetri_data['abortus']) ? $obstetri_data['abortus'] : '0' ?>";
        var tanggalHpht = "<?= isset($obstetri_data['tanggal_hpht']) && $obstetri_data['tanggal_hpht'] != '0000-00-00' ? date('d-m-Y', strtotime($obstetri_data['tanggal_hpht'])) : '-' ?>";
        var tanggalTp = "<?= isset($obstetri_data['tanggal_tp']) && $obstetri_data['tanggal_tp'] != '0000-00-00' ? date('d-m-Y', strtotime($obstetri_data['tanggal_tp'])) : '-' ?>";
        var tanggalTpPenyesuaian = "<?= isset($obstetri_data['tanggal_tp_penyesuaian']) && $obstetri_data['tanggal_tp_penyesuaian'] != '0000-00-00' ? date('d-m-Y', strtotime($obstetri_data['tanggal_tp_penyesuaian'])) : '-' ?>";
        var faktorRisikoUmum = "<?= isset($obstetri_data['faktor_risiko_umum']) ? $obstetri_data['faktor_risiko_umum'] : '-' ?>";
        var faktorRisikoObstetri = "<?= isset($obstetri_data['faktor_risiko_obstetri']) ? $obstetri_data['faktor_risiko_obstetri'] : '-' ?>";
        var faktorRisikoPreeklampsia = "<?= isset($obstetri_data['faktor_risiko_preeklampsia']) ? $obstetri_data['faktor_risiko_preeklampsia'] : '-' ?>";
        var hasilFaktorRisiko = "<?= isset($obstetri_data['hasil_faktor_risiko']) ? $obstetri_data['hasil_faktor_risiko'] : '-' ?>";

        // Format status obstetri untuk diagnosis
        var statusObstetriText = "G" + gravida + "P" + paritas + "A" + abortus;

        // Hitung usia kehamilan (UK) berdasarkan tanggal_tp_penyesuaian (EDD)
        if (tanggalTpPenyesuaian && tanggalTpPenyesuaian !== '-') {
            // Fungsi untuk menghitung usia kehamilan berdasarkan tanggal TP
            function hitungUKdariTP(tanggalTP_ddmmyyyy) {
                const parts = tanggalTP_ddmmyyyy.split('-');
                const tp = new Date(parts[2], parts[1] - 1, parts[0]); // yyyy, mm-1, dd
                const today = new Date();

                const tpClean = new Date(tp.getFullYear(), tp.getMonth(), tp.getDate());
                const todayClean = new Date(today.getFullYear(), today.getMonth(), today.getDate());

                const selisihHari = Math.floor((tpClean - todayClean) / (1000 * 60 * 60 * 24));
                const totalHariKehamilan = 280 - selisihHari;
                const minggu = Math.floor(totalHariKehamilan / 7);
                const hari = totalHariKehamilan % 7;

                let output = `UK ${minggu} minggu`;
                if (hari > 0) output += ` ${hari} hari`;
                if (selisihHari < 0) output += ` (Post Date)`;

                return output;
            }

            // Hitung usia kehamilan
            var hasilUK = hitungUKdariTP(tanggalTpPenyesuaian);

            // Tambahkan hasil ke status obstetri
            statusObstetriText += " " + hasilUK + "\n";
        }

        // Tambahkan faktor risiko jika ada
        var adaFaktorRisiko = false;
        var faktorRisikoText = "+ ";

        if (faktorRisikoUmum && faktorRisikoUmum !== '-') {
            faktorRisikoText += faktorRisikoUmum;
            adaFaktorRisiko = true;
        }

        if (faktorRisikoObstetri && faktorRisikoObstetri !== '-') {
            faktorRisikoText += (adaFaktorRisiko ? " + " : "") + faktorRisikoObstetri;
            adaFaktorRisiko = true;
        }

        if (faktorRisikoPreeklampsia && faktorRisikoPreeklampsia !== '-') {
            faktorRisikoText += (adaFaktorRisiko ? "\n" + "\n" + "+ Risiko PE: " : "PE: ") + faktorRisikoPreeklampsia;
            adaFaktorRisiko = true;
        }

        if (adaFaktorRisiko) {
            statusObstetriText += faktorRisikoText;

            if (hasilFaktorRisiko && hasilFaktorRisiko !== '-') {
                statusObstetriText += " (" + hasilFaktorRisiko + ")";
            }
        }

        // Sisipkan ke field diagnosis
        var diagnosisField = document.getElementById('diagnosis');
        var currentText = diagnosisField.value;

        // Tambahkan status obstetri ke awal diagnosis jika kosong, atau tambahkan di akhir dengan baris baru
        if (currentText.trim() === '') {
            diagnosisField.value = statusObstetriText;
        } else {
            diagnosisField.value = currentText + "\n" + statusObstetriText;
        }

        autoResizeTextarea(diagnosisField);
    }

    function masukkanStatusGinekologi() {
        // Cek jenis kelamin, hanya lanjutkan jika pasien perempuan
        var jenisKelamin = "<?= isset($pasien['jk']) ? $pasien['jk'] : '' ?>";

        if (jenisKelamin !== 'P') {
            alert('Status ginekologi hanya berlaku untuk pasien perempuan');
            return;
        }

        <?php
        // Ambil data ginekologi dari database menggunakan query langsung
        $no_rkm_medis = $pasien['no_rkm_medis']; // Ambil no_rkm_medis dari data pasien

        // Inisialisasi array data default
        $ginekologi_data = array(
            'Parturien' => '0',
            'Abortus' => '0',
            'Hari_pertama_haid_terakhir' => null,
            'Kontrasepsi_terakhir' => 'Tidak Ada',
            'lama_menikah_th' => '0'
        );

        try {
            // Get PDO connection
            $conn = getConnection();

            // Query langsung ke tabel status_ginekologi dengan no_rkm_medis
            // Perhatikan bahwa nama kolom menggunakan kapital di awal (seperti dalam model StatusGinekologi.php)
            $sql = "SELECT * FROM status_ginekologi WHERE no_rkm_medis = :no_rkm_medis ORDER BY created_at DESC LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':no_rkm_medis', $no_rkm_medis, PDO::PARAM_STR);
            $stmt->execute();

            // Debug output
            error_log("Query status_ginekologi untuk no_rkm_medis: " . $no_rkm_medis);

            if ($stmt && $stmt->rowCount() > 0) {
                $ginekologi_data = $stmt->fetch(PDO::FETCH_ASSOC);
                error_log("Data ginekologi ditemukan: " . json_encode($ginekologi_data));
            } else {
                error_log("Tidak ada data ginekologi untuk no_rkm_medis: " . $no_rkm_medis);
            }
        } catch (PDOException $e) {
            // Handle error jika terjadi kesalahan pada query
            error_log("Error fetching ginekologi data: " . $e->getMessage());
        }
        ?>

        // Format status ginekologi
        var statusGinekologiText = "STATUS GINEKOLOGI:\n";

        // Ambil data dengan konversi tipe yang benar dan memperhatikan nama kolom
        var parturien = <?= isset($ginekologi_data['Parturien']) ? intval($ginekologi_data['Parturien']) : 0 ?>;
        var abortus = <?= isset($ginekologi_data['Abortus']) ? intval($ginekologi_data['Abortus']) : 0 ?>;
        var hariPertamaHaidTerakhir = "<?= isset($ginekologi_data['Hari_pertama_haid_terakhir']) && $ginekologi_data['Hari_pertama_haid_terakhir'] && $ginekologi_data['Hari_pertama_haid_terakhir'] != '0000-00-00' ? date('d-m-Y', strtotime($ginekologi_data['Hari_pertama_haid_terakhir'])) : '-' ?>";
        var kontrasepsiTerakhir = "<?= isset($ginekologi_data['Kontrasepsi_terakhir']) ? $ginekologi_data['Kontrasepsi_terakhir'] : 'Tidak Ada' ?>";
        var lamaMenikahTh = <?= isset($ginekologi_data['lama_menikah_th']) ? intval($ginekologi_data['lama_menikah_th']) : 0 ?>;

        console.log("Data ginekologi yang diambil:");
        console.log("Parturien:", parturien);
        console.log("Abortus:", abortus);
        console.log("HPHT:", hariPertamaHaidTerakhir);
        console.log("Kontrasepsi:", kontrasepsiTerakhir);
        console.log("Lama Menikah:", lamaMenikahTh);

        // Selalu tampilkan data parturien (jumlah persalinan)
        statusGinekologiText += "P" + parturien;

        // Selalu tampilkan data abortus (jumlah keguguran)
        statusGinekologiText += "Ab" + abortus + "\n";

        // Tampilkan Hari Pertama Haid Terakhir
        statusGinekologiText += "HPHT: " + (hariPertamaHaidTerakhir !== '-' ? hariPertamaHaidTerakhir : "(tidak ada data)") + "\n";

        // Tampilkan Kontrasepsi Terakhir
        statusGinekologiText += "KB Terakhir: " + kontrasepsiTerakhir + "\n";

        // Selalu tampilkan Lama Menikah
        statusGinekologiText += "Lama Menikah: " + lamaMenikahTh + " tahun";

        statusGinekologiText += "\n";

        // Sisipkan ke field resume
        var resumeField = document.getElementById('resume');
        resumeField.value += statusGinekologiText;
        autoResizeTextarea(resumeField);

        // Update format data
        // updateResumeFormat();
    }

    function masukkanPemeriksaanFisik() {
        // Hanya ambil data keterangan pemeriksaan fisik
        var ket_fisik = document.getElementsByName('ket_fisik')[0].value;

        if (ket_fisik.trim() === '') {
            alert('Keterangan pemeriksaan fisik kosong. Silakan isi terlebih dahulu.');
            return;
        }

        // Format pemeriksaan fisik
        var pemeriksaanFisik = "PEMERIKSAAN FISIK:\n" + ket_fisik + "\n";

        // Sisipkan ke field resume
        var resumeField = document.getElementById('resume');
        resumeField.value += (resumeField.value ? "\n" : "") + pemeriksaanFisik;

        // Update format data
        // updateResumeFormat();
    }

    function masukkanHasilUSG() {
        // Ambil isi dari textarea ultrasonografi
        const isiUsg = document.getElementById('ultrasonografi').value.trim();

        // Validasi isi USG
        if (!isiUsg) {
            alert('Mohon isi data hasil USG terlebih dahulu sebelum menambahkan ke resume');
            return;
        }

        // Format pemeriksaan USG
        var pemeriksaanUSG = "PEMERIKSAAN USG:\n" + isiUsg + "\n";

        // Sisipkan ke field resume
        var resumeField = document.getElementById('resume');
        resumeField.value += (resumeField.value ? "\n" : "") + pemeriksaanUSG;

        // Update format data
        // updateResumeFormat();
    }

    function masukkanDiagnosis() {
        // Ambil isi dari textarea diagnosis
        const isiDiagnosis = document.getElementById('diagnosis').value.trim();

        // Validasi isi diagnosis
        if (!isiDiagnosis) {
            alert('Mohon isi data diagnosis terlebih dahulu sebelum menambahkan ke resume');
            return;
        }

        // Format diagnosis
        var diagnosisText = "DIAGNOSIS:\n" + isiDiagnosis + "\n";

        // Sisipkan ke field resume
        var resumeField = document.getElementById('resume');
        resumeField.value += (resumeField.value ? "\n" : "") + diagnosisText;

        // Auto-resize setelah mengubah konten
        autoResizeTextarea(resumeField);

        // Update format data
        // updateResumeFormat();
    }

    function masukkanTatalaksana() {
        // Ambil isi dari textarea tatalaksana
        const isiTatalaksana = document.getElementById('tatalaksana').value.trim();

        // Validasi isi tatalaksana
        if (!isiTatalaksana) {
            alert('Mohon isi data tatalaksana terlebih dahulu sebelum menambahkan ke resume');
            return;
        }

        // Format tatalaksana
        var tatalaksanaText = "TATALAKSANA:\n" + isiTatalaksana + "\n";

        // Sisipkan ke field resume
        var resumeField = document.getElementById('resume');
        resumeField.value += (resumeField.value ? "\n" : "") + tatalaksanaText;

        // Auto-resize setelah mengubah konten
        autoResizeTextarea(resumeField);

        // Update format data
        // updateResumeFormat();
    }

    function printResep() {
        // Ambil isi dari textarea resep
        const isiResep = document.getElementById('resep').value.trim();

        // Validasi isi resep
        if (!isiResep) {
            alert('Mohon isi data resep terlebih dahulu sebelum mencetak');
            return;
        }

        const noRawat = '<?= $pemeriksaan['no_rawat'] ?>';
        const namaPasien = '<?= $pasien['nm_pasien'] ?>';
        const noRm = '<?= $pasien['no_rkm_medis'] ?>';

        // Redirect ke halaman print dengan parameter
        const url = 'modules/rekam_medis/print_resep.php?isi=' + encodeURIComponent(isiResep) +
            '&no_rawat=' + encodeURIComponent(noRawat) +
            '&nama=' + encodeURIComponent(namaPasien) +
            '&no_rm=' + encodeURIComponent(noRm);

        // Buka di tab baru
        window.open(url, '_blank');
    }

    function printEdukasi() {
        // Ambil isi dari textarea edukasi
        const isiEdukasi = document.getElementById('edukasi').value.trim();

        // Validasi isi edukasi
        if (!isiEdukasi) {
            alert('Mohon isi data edukasi terlebih dahulu sebelum mencetak');
            return;
        }

        const noRawat = '<?= $pemeriksaan['no_rawat'] ?>';
        const namaPasien = '<?= $pasien['nm_pasien'] ?>';
        const noRm = '<?= $pasien['no_rkm_medis'] ?>';

        // Redirect ke halaman print dengan parameter
        const url = 'modules/rekam_medis/print_edukasi.php?isi=' + encodeURIComponent(isiEdukasi) +
            '&no_rawat=' + encodeURIComponent(noRawat) +
            '&nama=' + encodeURIComponent(namaPasien) +
            '&no_rm=' + encodeURIComponent(noRm);

        // Buka halaman print di tab baru
        window.open(url, '_blank');
    }

    function printCeklist() {
        // Ambil isi dari ceklist content
        const isiCeklist = document.getElementById('ceklistContent').textContent.trim();

        // Validasi isi ceklist
        if (!isiCeklist || isiCeklist === '-') {
            alert('Mohon isi data ceklist terlebih dahulu sebelum mencetak');
            return;
        }

        const noRawat = '<?= $pemeriksaan['no_rawat'] ?>';
        const namaPasien = '<?= $pasien['nm_pasien'] ?>';
        const noRm = '<?= $pasien['no_rkm_medis'] ?>';

        // Redirect ke halaman print dengan parameter
        const url = 'modules/rekam_medis/print_ceklist.php?isi=' + encodeURIComponent(isiCeklist) +
            '&no_rawat=' + encodeURIComponent(noRawat) +
            '&nama=' + encodeURIComponent(namaPasien) +
            '&no_rm=' + encodeURIComponent(noRm);

        // Buka halaman print di tab baru
        window.open(url, '_blank');
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Filter untuk template USG
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
            var tbody = document.querySelector('#tabelTemplateUsg tbody');
            var noDataRow = document.querySelector('#tabelTemplateUsg tbody tr:not(.template-row)');

            if (visibleRows.length === 0) {
                if (!noDataRow) {
                    var tr = document.createElement('tr');
                    tr.className = 'no-data-row';
                    tr.innerHTML = '<td colspan="6" class="text-center">Tidak ada template tersedia untuk kategori ini</td>';
                    tbody.appendChild(tr);
                } else {
                    noDataRow.style.display = '';
                }
            } else {
                if (noDataRow) {
                    noDataRow.style.display = 'none';
                }
            }
        });

        // Filter untuk template tatalaksana
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
            } else {
                if (noDataRow) {
                    noDataRow.style.display = 'none';
                }
            }
        });

        // Filter untuk template edukasi
        function filterTemplateEdukasi() {
            var kategori = document.getElementById('filter_kategori_edukasi').value;
            var searchText = document.getElementById('search_edukasi').value.toLowerCase();
            var rows = document.querySelectorAll('#tabelTemplateEdukasi tbody tr.template-row');
            var hasVisibleRows = false;

            rows.forEach(function(row) {
                var rowKategori = row.getAttribute('data-kategori');
                var rowJudul = row.getAttribute('data-judul').toLowerCase();
                var rowIsi = row.cells[2].textContent.toLowerCase();
                var rowTags = row.cells[4].textContent.toLowerCase();

                var matchesKategori = kategori === '' || rowKategori === kategori;
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

        // Filter untuk template resume
        function filterTemplateResume() {
            var kategori = document.getElementById('filter_kategori_resume').value;
            var searchText = document.getElementById('search_resume').value.toLowerCase();
            var rows = document.querySelectorAll('#tabelTemplateResume tbody tr.template-row');
            var hasVisibleRows = false;

            rows.forEach(function(row) {
                var rowKategori = row.getAttribute('data-kategori');
                var rowJudul = row.getAttribute('data-judul').toLowerCase();
                var rowIsi = row.cells[2].textContent.toLowerCase();
                var rowTags = row.cells[4].textContent.toLowerCase();

                var matchesKategori = kategori === '' || rowKategori === kategori;
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
            var visibleRows = document.querySelectorAll('#tabelTemplateResume tbody tr.template-row:not([style*="display: none"])');
            visibleRows.forEach(function(row, index) {
                row.cells[0].textContent = index + 1;
            });

            // Tampilkan pesan jika tidak ada data
            var tbody = document.querySelector('#tabelTemplateResume tbody');
            var noDataRow = document.querySelector('#tabelTemplateResume tbody tr.no-data-row');

            if (!hasVisibleRows) {
                if (!noDataRow) {
                    var tr = document.createElement('tr');
                    tr.className = 'no-data-row';
                    tr.innerHTML = '<td colspan="6" class="text-center">Tidak ada template resume yang sesuai dengan kriteria pencarian</td>';
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

        document.getElementById('filter_kategori_resume').addEventListener('change', filterTemplateResume);
        document.getElementById('search_resume').addEventListener('input', filterTemplateResume);
    });

    // Fungsi untuk debugging Status Obstetri
    function debugStatusObstetri(message) {
        console.log('DEBUG StatusObstetri: ' + message);
    }

    // Fungsi untuk refresh data status obstetri telah dihapus karena data sekarang dirender langsung menggunakan PHP

    // Fungsi untuk refresh data riwayat kehamilan
    function refreshRiwayatKehamilanData() {
        const noRkmMedis = '<?= $pasien['no_rkm_medis'] ?>';
        const riwayatKehamilanContent = document.getElementById('riwayatKehamilanContent');

        if (!riwayatKehamilanContent) {
            console.error('Element riwayatKehamilanContent tidak ditemukan');
            return;
        }

        // Buat element untuk loading overlay
        const loadingOverlay = document.createElement('div');
        loadingOverlay.className = 'loading-overlay';
        loadingOverlay.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
        loadingOverlay.style.position = 'absolute';
        loadingOverlay.style.top = '0';
        loadingOverlay.style.left = '0';
        loadingOverlay.style.width = '100%';
        loadingOverlay.style.height = '100%';
        loadingOverlay.style.backgroundColor = 'rgba(255, 255, 255, 0.7)';
        loadingOverlay.style.display = 'flex';
        loadingOverlay.style.justifyContent = 'center';
        loadingOverlay.style.alignItems = 'center';
        loadingOverlay.style.zIndex = '1000';

        // Tambahkan loading overlay ke content
        riwayatKehamilanContent.style.position = 'relative';
        riwayatKehamilanContent.appendChild(loadingOverlay);

        console.log('Refreshing Riwayat Kehamilan data for: ' + noRkmMedis);

        // AJAX request untuk riwayat kehamilan
        const xhr = new XMLHttpRequest();
        xhr.open('GET', 'index.php?module=rekam_medis&action=get_riwayat_kehamilan_ajax&no_rkm_medis=' + encodeURIComponent(noRkmMedis), true);

        xhr.onload = function() {
            if (this.status === 200) {
                try {
                    const rawResponse = this.responseText;

                    // Coba deteksi jika ini adalah HTML bukan JSON
                    if (rawResponse.trim().startsWith('<')) {
                        console.error('ERROR: Respons berisi HTML, bukan JSON. Endpoint mungkin tidak benar.');

                        // Tampilkan pesan kesalahan tentang endpoint
                    } else {
                        const response = JSON.parse(this.responseText);
                        console.log('Riwayat kehamilan data received:', response);

                        const tableBody = document.getElementById('riwayatKehamilanTableBody');
                        if (tableBody) {
                            if (response.status === 'success' && response.data && response.data.length > 0) {
                                let tableHtml = '';

                                response.data.forEach(function(rk) {
                                    const tanggalCreated = new Date(rk.created_at).toLocaleDateString('id-ID', {
                                        day: '2-digit',
                                        month: '2-digit',
                                        year: 'numeric'
                                    });

                                    const tanggalLahir = rk.tanggal_lahir ? new Date(rk.tanggal_lahir).toLocaleDateString('id-ID', {
                                        day: '2-digit',
                                        month: '2-digit',
                                        year: 'numeric'
                                    }) : '-';

                                    const tanggalPersalinan = rk.tanggal_persalinan ? new Date(rk.tanggal_persalinan).toLocaleDateString('id-ID', {
                                        day: '2-digit',
                                        month: '2-digit',
                                        year: 'numeric'
                                    }) : '-';

                                    tableHtml += `
                                        <tr>
                                            <td>${tanggalCreated}</td>
                                            <td>${rk.jenis_kelamin || '-'}</td>
                                            <td>${tanggalLahir}</td>
                                            <td>${rk.berat_badan_lahir || '-'}</td>
                                            <td>${tanggalPersalinan}</td>
                                            <td>
                                                <a href="index.php?module=rekam_medis&action=edit_riwayat_kehamilan&id=${rk.id_riwayat_kehamilan}&source=<?= $_SESSION['source_page'] ?? 'form_edit_pemeriksaan' ?><?= isset($_GET['no_rawat']) ? '&no_rawat=' . $_GET['no_rawat'] : '' ?>" class="btn btn-warning btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="index.php?module=rekam_medis&action=hapus_riwayat_kehamilan&id=${rk.id_riwayat_kehamilan}&source=<?= $_SESSION['source_page'] ?? 'form_edit_pemeriksaan' ?><?= isset($_GET['no_rawat']) ? '&no_rawat=' . $_GET['no_rawat'] : '' ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    `;
                                });

                                tableBody.innerHTML = tableHtml;
                            } else {
                                tableBody.innerHTML = '<tr><td colspan="6" class="text-center">Tidak ada data riwayat kehamilan</td></tr>';
                            }
                        }
                    }
                } catch (error) {
                    console.error('Error parsing JSON:', error);
                    document.getElementById('riwayatKehamilanTableBody').innerHTML =
                        '<tr><td colspan="6" class="text-center text-danger">Error: Gagal memuat data riwayat kehamilan</td></tr>';
                }
            } else {
                console.error('HTTP Error:', this.status);
                document.getElementById('riwayatKehamilanTableBody').innerHTML =
                    '<tr><td colspan="6" class="text-center text-danger">Error: Gagal memuat data riwayat kehamilan</td></tr>';
            }

            // Hapus loading overlay
            if (riwayatKehamilanContent.contains(loadingOverlay)) {
                riwayatKehamilanContent.removeChild(loadingOverlay);
            }
        };

        xhr.onerror = function() {
            console.error('Request Failed');
            document.getElementById('riwayatKehamilanTableBody').innerHTML =
                '<tr><td colspan="6" class="text-center text-danger">Error: Gagal terhubung ke server</td></tr>';

            // Hapus loading overlay
            if (riwayatKehamilanContent.contains(loadingOverlay)) {
                riwayatKehamilanContent.removeChild(loadingOverlay);
            }
        };

        xhr.send();
    }

    // Fungsi untuk refresh data status ginekologi telah dihapus karena data sekarang dirender langsung menggunakan PHP

    // Memuat data saat halaman dimuat
    document.addEventListener('DOMContentLoaded', () => {
        // Referensi ke tab dan konten tab
        const identitasTab = document.getElementById('identitas-tab');
        const skriningTab = document.getElementById('skrining-tab');
        const riwayatKehamilanTab = document.getElementById('riwayat-kehamilan-tab');
        const statusGinekologiTab = document.getElementById('status-ginekologi-tab');
        const grafikImtTab = document.getElementById('grafik-imt-tab');
        const riwayatTab = document.getElementById('riwayat-tab');

        const identitasContent = document.getElementById('identitas');
        const skriningContent = document.getElementById('skrining');
        const riwayatKehamilanContent = document.getElementById('riwayat-kehamilan');
        const statusGinekologiContent = document.getElementById('status-ginekologi');
        const grafikImtContent = document.getElementById('grafik-imt');
        const riwayatContent = document.getElementById('riwayat');

        // Initialize icon state
        identitasTab.querySelector('i.fas.fa-chevron-down').style.transform = 'rotate(0deg)';
        skriningTab.querySelector('i.fas.fa-chevron-down').style.transform = 'rotate(-90deg)';
        riwayatKehamilanTab.querySelector('i.fas.fa-chevron-down').style.transform = 'rotate(-90deg)';
        statusGinekologiTab.querySelector('i.fas.fa-chevron-down').style.transform = 'rotate(-90deg)';
        grafikImtTab.querySelector('i.fas.fa-chevron-down').style.transform = 'rotate(-90deg)';
        riwayatTab.querySelector('i.fas.fa-chevron-down').style.transform = 'rotate(-90deg)';

        // Fungsi untuk menutup semua tab kecuali yang aktif
        function closeAllTabsExcept(activeTabContent) {
            const allTabContents = [identitasContent, skriningContent, riwayatKehamilanContent, statusGinekologiContent, grafikImtContent, riwayatContent];
            const allTabs = [identitasTab, skriningTab, riwayatKehamilanTab, statusGinekologiTab, grafikImtTab, riwayatTab];

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
                    tab.querySelector('i.fas.fa-chevron-down').style.transform = 'rotate(-90deg)';
                }
            });
        }

        // Add click handlers
        identitasTab.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Identitas tab clicked');

            if (identitasContent.style.display === 'block') {
                identitasContent.style.display = 'none';
                identitasContent.classList.remove('show');
                this.classList.add('collapsed');
                this.classList.remove('active');
                this.querySelector('i.fas.fa-chevron-down').style.transform = 'rotate(-90deg)';
            } else {
                identitasContent.style.display = 'block';
                identitasContent.classList.add('show');
                closeAllTabsExcept(identitasContent);

                this.classList.remove('collapsed');
                this.classList.add('active');
                this.querySelector('i.fas.fa-chevron-down').style.transform = 'rotate(0deg)';
            }
        });

        skriningTab.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Status Obstetri tab clicked');

            if (skriningContent.style.display === 'block') {
                skriningContent.style.display = 'none';
                skriningContent.classList.remove('show');
                this.classList.add('collapsed');
                this.classList.remove('active');
                this.querySelector('i.fas.fa-chevron-down').style.transform = 'rotate(-90deg)';
            } else {
                skriningContent.style.display = 'block';
                skriningContent.classList.add('show');
                closeAllTabsExcept(skriningContent);

                this.classList.remove('collapsed');
                this.classList.add('active');
                this.querySelector('i.fas.fa-chevron-down').style.transform = 'rotate(0deg)';
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
                this.querySelector('i.fas.fa-chevron-down').style.transform = 'rotate(-90deg)';
            } else {
                riwayatKehamilanContent.style.display = 'block';
                riwayatKehamilanContent.classList.add('show');
                closeAllTabsExcept(riwayatKehamilanContent);

                this.classList.remove('collapsed');
                this.classList.add('active');
                this.querySelector('i.fas.fa-chevron-down').style.transform = 'rotate(0deg)';
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
                this.querySelector('i.fas.fa-chevron-down').style.transform = 'rotate(-90deg)';
            } else {
                statusGinekologiContent.style.display = 'block';
                statusGinekologiContent.classList.add('show');
                closeAllTabsExcept(statusGinekologiContent);

                this.classList.remove('collapsed');
                this.classList.add('active');
                this.querySelector('i.fas.fa-chevron-down').style.transform = 'rotate(0deg)';
            }
        });

        // Handler untuk tab Grafik IMT
        grafikImtTab.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Grafik IMT tab clicked');

            if (grafikImtContent.style.display === 'block') {
                grafikImtContent.style.display = 'none';
                grafikImtContent.classList.remove('show');
                this.classList.add('collapsed');
                this.classList.remove('active');
                this.querySelector('i.fas.fa-chevron-down').style.transform = 'rotate(-90deg)';
            } else {
                grafikImtContent.style.display = 'block';
                grafikImtContent.classList.add('show');
                closeAllTabsExcept(grafikImtContent);

                this.classList.remove('collapsed');
                this.classList.add('active');
                this.querySelector('i.fas.fa-chevron-down').style.transform = 'rotate(0deg)';
            }
        });

        // Handler untuk tab Riwayat
        riwayatTab.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Riwayat tab clicked');

            if (riwayatContent.style.display === 'block') {
                riwayatContent.style.display = 'none';
                riwayatContent.classList.remove('show');
                this.classList.add('collapsed');
                this.classList.remove('active');
                this.querySelector('i.fas.fa-chevron-down').style.transform = 'rotate(-90deg)';
            } else {
                riwayatContent.style.display = 'block';
                riwayatContent.classList.add('show');
                closeAllTabsExcept(riwayatContent);

                this.classList.remove('collapsed');
                this.classList.add('active');
                this.querySelector('i.fas.fa-chevron-down').style.transform = 'rotate(0deg)';
            }
        });

        // Inisialisasi tampilan - pastikan tab identitas terbuka di awal
        identitasContent.style.display = 'block';
        identitasContent.classList.add('show');
        identitasTab.classList.remove('collapsed');

        // === Toggle Gratis Logic ===
        document.querySelectorAll('.toggle-gratis').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const noRm = this.getAttribute('data-no-rm');
                const isChecked = this.checked ? 1 : 0;
                const checkboxElement = this;
                const spinnerElement = this.parentNode.querySelector('.toggle-spinner');
                const statusTextElement = document.querySelector('.gratis-status');

                if (isChecked) {
                    statusTextElement.classList.add('active');
                } else {
                    statusTextElement.classList.remove('active');
                }

                checkboxElement.disabled = true;
                spinnerElement.classList.remove('d-none');

                fetch('router.php?module=rekam_medis&action=toggleBerikutnyaGratis', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `no_rkm_medis=${noRm}&berikutnya_gratis=${isChecked}`
                    })
                    .then(response => response.json())
                    .then(result => {
                        // Always treat as success due to db update
                        // Tidak perlu toast sukses. Tetap update UI.
                        checkboxElement.classList.add('pulse');
                        setTimeout(() => checkboxElement.classList.remove('pulse'), 600);
                    })
                    .catch(error => {
                        // Rollback toggle
                        checkboxElement.checked = !isChecked;
                        if (isChecked) {
                            statusTextElement.classList.remove('active');
                        } else {
                            statusTextElement.classList.add('active');
                        }
                        // Tidak perlu alert. Hanya log error di konsol.
                        console.error('Error:', error);
                    })
                    .finally(() => {
                        checkboxElement.disabled = false;
                        spinnerElement.classList.add('d-none');
                    });
            });
        });

        // Toast logic
        window.showGratisToast = function() {
            const toast = document.getElementById('toast-gratis');
            if (!toast) return;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 2200);
        };
        // === END Toggle Gratis Logic ===

        identitasTab.classList.add('active');
        skriningContent.style.display = 'none';
        riwayatKehamilanContent.style.display = 'none';
        statusGinekologiContent.style.display = 'none';
        grafikImtContent.style.display = 'none';
        riwayatContent.style.display = 'none';

        // Buat IntersectionObserver untuk setiap konten tab sebagai fallback
        const setupObserver = (elementId, callbackFn) => {
            const element = document.getElementById(elementId);
            if (element) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            console.log(`Tab ${elementId} terlihat, memanggil callback via IntersectionObserver`);
                            callbackFn();
                            observer.disconnect();
                        }
                    });
                });
                observer.observe(element);
            }
        };

        // Set up observers untuk setiap tab content
        // Kode untuk setup observer ke tab Status Obstetri dan Status Ginekologi telah dihapus
        // karena data sekarang dirender langsung menggunakan PHP
    });

    // Tambahkan di bagian awal script, setelah definisi loadingManager
    const modalCleanup = {
        cleanup() {
            // Hapus semua overlay yang mungkin tertinggal
            const overlays = document.querySelectorAll('.loading-overlay');
            overlays.forEach(overlay => overlay.remove());

            // Reset scroll
            document.body.style.overflow = '';

            // Hapus semua modal backdrop yang mungkin tertinggal
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => backdrop.remove());

            // Tutup semua modal yang masih terbuka
            const openModals = document.querySelectorAll('.modal.show');
            openModals.forEach(modal => {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
                modal.classList.remove('show');
                modal.style.display = 'none';
            });

            // Reset loading manager
            if (loadingManager && loadingManager.overlay) {
                loadingManager.hide();
            }
        }
    };

    // Tambahkan event listener untuk tombol close modal
    document.querySelectorAll('.modal .btn-close, .modal .close').forEach(button => {
        button.addEventListener('click', () => {
            setTimeout(modalCleanup.cleanup, 300);
        });
    });

    // Tambahkan event listener untuk klik di luar modal
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) {
            setTimeout(modalCleanup.cleanup, 300);
        }
    });

    // Override fungsi hide loading untuk selalu membersihkan modal
    const originalHide = loadingManager.hide;
    loadingManager.hide = function() {
        originalHide.call(this);
        modalCleanup.cleanup();
    };

    // Tambahkan di bagian script, setelah DOMContentLoaded event listener yang ada
    document.addEventListener('keydown', function(e) {
        // Jika tombol Escape ditekan
        if (e.key === 'Escape') {
            loadingManager.hide();

            // Cari semua modal yang terbuka
            const openModals = document.querySelectorAll('.modal.show');
            openModals.forEach(modal => {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            });
        }
    });

    // Tambahkan event handler untuk semua modal
    const allModals = document.querySelectorAll('.modal');
    allModals.forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function() {
            // Pastikan loading overlay hilang ketika modal ditutup
            loadingManager.hide();
            document.body.style.overflow = '';
        });

        // Tambahkan error handler untuk modal
        modal.addEventListener('show.bs.modal', function(event) {
            try {
                // Reset modal state
                const modalBody = this.querySelector('.modal-body');
                if (modalBody) {
                    const loadingOverlays = modalBody.querySelectorAll('.loading-overlay');
                    loadingOverlays.forEach(overlay => overlay.remove());
                }
            } catch (error) {
                console.error('Error saat membuka modal:', error);
                loadingManager.hide();
            }
        });
    });

    // Perbaiki fungsi gunakanTemplate untuk menangani error dengan lebih baik
    function handleTemplateError(error, modalId) {
        console.error('Error saat menggunakan template:', error);
        loadingManager.hide();

        // Tutup modal jika masih terbuka
        const modal = document.getElementById(modalId);
        if (modal) {
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) {
                modalInstance.hide();
            }
        }

        // Reset scroll
        document.body.style.overflow = '';

        // Tampilkan pesan error ke user
        alert('Terjadi kesalahan saat menggunakan template. Silakan coba lagi.');
    }

    // Update semua fungsi template untuk menggunakan error handler


// (Auto-fill jenis perujuk removed as no longer needed)
    document.addEventListener('DOMContentLoaded', function() {
        const perujukSelect = document.getElementById('nama_perujuk');
        
        // Set nilai awal jika ada data yang dipilih

        
        // Tambahkan event listener untuk perubahan select

    });

    // Fungsi untuk filter gambar
    function filterGambar() {
        var kategori = document.getElementById('filter_kategori_gambar').value;
        var searchText = document.getElementById('search_gambar').value.toLowerCase();
        var items = document.querySelectorAll('.gambar-item');
        var hasVisibleItems = false;

        items.forEach(function(item) {
            var itemKategori = item.getAttribute('data-kategori');
            var itemJudul = item.getAttribute('data-judul').toLowerCase();

            var matchesKategori = kategori === '' || itemKategori === kategori;
            var matchesSearch = searchText === '' || itemJudul.includes(searchText);

            if (matchesKategori && matchesSearch) {
                item.style.display = '';
                hasVisibleItems = true;
            } else {
                item.style.display = 'none';
            }
        });

        // Tampilkan pesan jika tidak ada item yang sesuai
        var noDataMessage = document.querySelector('.no-data-message');
        if (!hasVisibleItems) {
            if (!noDataMessage) {
                noDataMessage = document.createElement('div');
                noDataMessage.className = 'col-12 text-center no-data-message';
                noDataMessage.innerHTML = 'Tidak ada gambar yang sesuai dengan kriteria pencarian';
                document.getElementById('gridGambarEdukasi').appendChild(noDataMessage);
            }
            noDataMessage.style.display = '';
        } else if (noDataMessage) {
            noDataMessage.style.display = 'none';
        }
    }

    // Event listener untuk filter
    document.getElementById('filter_kategori_gambar').addEventListener('change', filterGambar);
    document.getElementById('search_gambar').addEventListener('input', filterGambar);

    // Event listener untuk filter formularium (resep)
    document.getElementById('search_generik').addEventListener('input', filterFormularium);
    document.getElementById('filter_kategori_obat').addEventListener('change', filterFormularium);

    function filterFormularium() {
        // Ambil nilai filter kategori dan kata kunci pencarian
        var kategori = document.getElementById('filter_kategori_obat').value.toLowerCase();
        var searchText = document.getElementById('search_generik').value.toLowerCase();
        var rows = document.querySelectorAll('#tabelFormularium tbody tr.obat-row');
        var hasVisible = false;

        // Iterasi setiap baris dalam tabel
        rows.forEach(function(row) {
            var rowKategori = row.getAttribute('data-kategori').toLowerCase();

            // Perbaikan: Ambil semua teks dari semua kolom (kecuali kolom aksi di index 0)
            // untuk memungkinkan pencarian di semua kolom formularium
            var text = Array.from(row.cells).map(function(cell, index) {
                // Skip kolom aksi (biasanya kolom pertama dengan tombol)
                if (index === 0) return '';
                return cell.textContent.toLowerCase();
            }).join(' ');

            // Cek apakah kategori dan kata kunci cocok
            var matchesKategori = kategori === '' || rowKategori === kategori;
            var matchesSearch = searchText === '' || text.includes(searchText);

            // Tampilkan atau sembunyikan baris berdasarkan hasil filter
            if (matchesKategori && matchesSearch) {
                row.style.display = '';
                hasVisible = true;
            } else {
                row.style.display = 'none';
            }
        });

        // Tampilkan pesan jika tidak ada data yang cocok
        var tbody = document.querySelector('#tabelFormularium tbody');
        var noData = document.querySelector('#tabelFormularium tbody tr.no-data-row');
        if (!hasVisible) {
            if (!noData) {
                var tr = document.createElement('tr');
                tr.className = 'no-data-row';
                tr.innerHTML = '<td colspan="7" class="text-center">Tidak ada data obat yang sesuai dengan kriteria pencarian</td>';
                tbody.appendChild(tr);
            } else {
                noData.style.display = '';
            }
        } else if (noData) {
            noData.style.display = 'none';
        }
    }

    // Fungsi untuk memilih gambar
    function pilihGambar(namaFile, judul) {
        // Simpan data gambar ke input hidden
        document.getElementById('gambarEdukasiInput').value = namaFile;
        document.getElementById('judulGambarEdukasiInput').value = judul;

        // Tampilkan gambar dengan path yang benar
        const gambarPath = 'uploads/edukasi/' + namaFile;
        document.getElementById('gambarEdukasiTerpilih').src = gambarPath;
        document.getElementById('judulGambarEdukasiTerpilih').textContent = judul;

        // Tampilkan card gambar
        document.getElementById('cardGambarEdukasi').style.display = 'block';

        // Tutup modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalPilihGambarEdukasi'));
        if (modal) {
            modal.hide();
        }
    }

    // Fungsi untuk menghapus gambar terpilih
    function hapusGambarTerpilih() {
        // Reset input hidden
        document.getElementById('gambarEdukasiInput').value = '';
        document.getElementById('judulGambarEdukasiInput').value = '';

        // Reset gambar
        document.getElementById('gambarEdukasiTerpilih').src = '';
        document.getElementById('judulGambarEdukasiTerpilih').textContent = '';

        // Sembunyikan card
        document.getElementById('cardGambarEdukasi').style.display = 'none';
    }

    function bukaGambarDiTabBaru() {
        const gambarSrc = document.getElementById('gambarEdukasiTerpilih').src;
        if (gambarSrc) {
            window.open(gambarSrc, '_blank');
        }
    }
</script>

<!-- Tambahkan Chart.js dan script grafik IMT -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
<script src="../../modules/rekam_medis/js/grafik_imt.js"></script>

<!-- Modal Kalkulator IMT -->
<div class="modal fade" id="modalHitungIMT" tabindex="-1" aria-labelledby="modalHitungIMTLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalHitungIMTLabel">Kalkulator IMT</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="kalkulator_bb" class="form-label">Berat Badan (kg)</label>
                    <input type="number" step="0.1" class="form-control" id="kalkulator_bb">
                </div>
                <div class="mb-3">
                    <label for="kalkulator_tb" class="form-label">Tinggi Badan (cm)</label>
                    <input type="number" step="0.1" class="form-control" id="kalkulator_tb">
                </div>
                <div class="mb-3">
                    <label for="hasil_imt" class="form-label">Hasil IMT</label>
                    <input type="text" class="form-control" id="hasil_imt" readonly>
                </div>
                <div class="mb-3">
                    <label for="kategori_hasil_imt" class="form-label">Kategori</label>
                    <input type="text" class="form-control" id="kategori_hasil_imt" readonly>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="hitungIMT">Hitung</button>
                <button type="button" class="btn btn-success" id="gunakanIMT">Gunakan</button>
            </div>
        </div>
    </div>
</div>