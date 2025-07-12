<?php
require_once __DIR__ . '/RekamMedisController.php';
$controller = new RekamMedisController();
$controller->handle();
// Legacy logic di bawah masih tersedia jika dibutuhkan

switch ($_REQUEST['action']) {
    case 'generate_pdf':
        if (isset($_GET['no_rkm_medis'])) {
            $no_rkm_medis = $_GET['no_rkm_medis'];

            // Query untuk mendapatkan data pasien
            $query_pasien = "SELECT * FROM pasien WHERE no_rkm_medis = '$no_rkm_medis'";
            $pasien = mysqli_fetch_assoc(mysqli_query($conn, $query_pasien));

            // Query untuk status obstetri
            $query_obstetri = "SELECT * FROM status_obstetri WHERE no_rkm_medis = '$no_rkm_medis' ORDER BY created_at DESC";
            $statusObstetri = mysqli_fetch_all(mysqli_query($conn, $query_obstetri), MYSQLI_ASSOC);

            // Query untuk riwayat pemeriksaan
            $query_pemeriksaan = "SELECT * FROM penilaian_medis_ralan_kandungan WHERE no_rawat = '$no_rkm_medis' ORDER BY tanggal DESC";
            $riwayatPemeriksaan = mysqli_fetch_all(mysqli_query($conn, $query_pemeriksaan), MYSQLI_ASSOC);

            // Generate PDF
            require_once('modules/rekam_medis/generate_resume_pdf.php');
        }
        break;

    case 'generate_status_obstetri_pdf':
        if (isset($_GET['no_rkm_medis'])) {
            $no_rkm_medis = $_GET['no_rkm_medis'];

            // Query untuk mendapatkan data pasien
            $query_pasien = "SELECT * FROM pasien WHERE no_rkm_medis = '$no_rkm_medis'";
            $pasien = mysqli_fetch_assoc(mysqli_query($conn, $query_pasien));

            // Query untuk status obstetri
            $query_obstetri = "SELECT * FROM status_obstetri WHERE no_rkm_medis = '$no_rkm_medis' ORDER BY created_at DESC LIMIT 1";
            $obstetri = mysqli_fetch_assoc(mysqli_query($conn, $query_obstetri));

            // Generate PDF
            require_once('modules/rekam_medis/generate_status_obstetri_pdf.php');
        }
        break;

    case 'generate_status_ginekologi_pdf':
        if (isset($_GET['no_rkm_medis'])) {
            $no_rkm_medis = $_GET['no_rkm_medis'];

            // Query untuk mendapatkan data pasien
            $query_pasien = "SELECT * FROM pasien WHERE no_rkm_medis = '$no_rkm_medis'";
            $pasien = mysqli_fetch_assoc(mysqli_query($conn, $query_pasien));

            // Query untuk status ginekologi
            $query_ginekologi = "SELECT * FROM status_ginekologi WHERE no_rkm_medis = '$no_rkm_medis' ORDER BY created_at DESC LIMIT 1";
            $statusGinekologi = mysqli_fetch_assoc(mysqli_query($conn, $query_ginekologi));

            // Query untuk data pemeriksaan dari penilaian_medis_ralan_kandungan
            // Cari berdasarkan no_rawat yang mengandung no_rkm_medis
            $query_pemeriksaan = "
                SELECT * FROM penilaian_medis_ralan_kandungan 
                WHERE no_rawat IN (
                    SELECT no_rawat FROM reg_periksa WHERE no_rkm_medis = '$no_rkm_medis'
                )
                ORDER BY tanggal DESC LIMIT 1
            ";
            $pemeriksaan = mysqli_fetch_assoc(mysqli_query($conn, $query_pemeriksaan));

            // Generate PDF
            require_once('modules/rekam_medis/generate_status_ginekologi_pdf.php');
        }
        break;

    case 'generate_edukasi_pdf':
        if (isset($_GET['no_rkm_medis'])) {
            $no_rkm_medis = $_GET['no_rkm_medis'];

            // Query untuk mendapatkan data pasien
            $query_pasien = "SELECT * FROM pasien WHERE no_rkm_medis = '$no_rkm_medis'";
            $pasien = mysqli_fetch_assoc(mysqli_query($conn, $query_pasien));

            // Query untuk data pemeriksaan dari penilaian_medis_ralan_kandungan
            // Cari berdasarkan no_rawat yang mengandung no_rkm_medis
            $query_pemeriksaan = "
                SELECT * FROM penilaian_medis_ralan_kandungan 
                WHERE no_rawat IN (
                    SELECT no_rawat FROM reg_periksa WHERE no_rkm_medis = '$no_rkm_medis'
                )
                ORDER BY tanggal DESC LIMIT 1
            ";
            $pemeriksaan = mysqli_fetch_assoc(mysqli_query($conn, $query_pemeriksaan));

            // Jika tidak ada data pemeriksaan, buat array kosong
            if (!$pemeriksaan) {
                $pemeriksaan = ['edukasi' => 'Tidak ada data edukasi'];
            }

            // Generate PDF
            require_once('modules/rekam_medis/generate_edukasi_pdf.php');
        }
        break;
}
