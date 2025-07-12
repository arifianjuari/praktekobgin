<?php
// Pastikan tidak ada output sebelum ini
ob_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cek apakah session sudah aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log untuk debugging
$log_file = __DIR__ . '/../../logs/pdf_debug.log';
file_put_contents($log_file, "PDF generation started at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

// Konfigurasi database
require_once __DIR__ . '/../../../../config/database.php';
$db_host = $db2_host;
$db_username = $db2_username;
$db_password = $db2_password;
$db_database = $db2_database;

// Koneksi database
try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_database;charset=utf8",
        $db_username,
        $db_password,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        )
    );
    file_put_contents($log_file, "Database connection established\n", FILE_APPEND);
} catch (PDOException $e) {
    file_put_contents($log_file, "Database connection failed: " . $e->getMessage() . "\n", FILE_APPEND);
    die("Koneksi database gagal: " . $e->getMessage());
}

// Impor TCPDF
require_once(__DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php');

// Extend TCPDF
class ResumePDF extends TCPDF
{
    public function Header()
    {
        // Kosongkan header
    }

    public function Footer()
    {
        // Kosongkan footer
    }
}

// Log untuk debugging
file_put_contents($log_file, "TCPDF class defined\n", FILE_APPEND);

// Periksa apakah variabel $pasien tersedia, jika tidak, coba ambil dari parameter URL
if (!isset($pasien)) {
    file_put_contents($log_file, "Variabel pasien tidak tersedia, mencoba ambil dari parameter URL\n", FILE_APPEND);

    if (isset($_GET['no_rkm_medis'])) {
        $no_rkm_medis = $_GET['no_rkm_medis'];

        // Query untuk mendapatkan data pasien
        $query_pasien = "SELECT * FROM pasien WHERE no_rkm_medis = :no_rkm_medis";
        $stmt_pasien = $pdo->prepare($query_pasien);
        $stmt_pasien->execute([':no_rkm_medis' => $no_rkm_medis]);
        $pasien = $stmt_pasien->fetch(PDO::FETCH_ASSOC);

        if ($pasien) {
            file_put_contents($log_file, "Data pasien berhasil diambil dari database: " . $pasien['no_rkm_medis'] . "\n", FILE_APPEND);
        } else {
            file_put_contents($log_file, "Data pasien tidak ditemukan di database\n", FILE_APPEND);
            die("Data pasien tidak ditemukan");
        }
    } else {
        file_put_contents($log_file, "Parameter no_rkm_medis tidak ditemukan di URL\n", FILE_APPEND);
        die("Parameter no_rkm_medis tidak ditemukan");
    }
}

file_put_contents($log_file, "Data pasien tersedia: " . $pasien['no_rkm_medis'] . "\n", FILE_APPEND);

try {
    // Debug: Tampilkan struktur tabel yang lebih detail
    $debug_query1 = "SELECT COLUMN_NAME, DATA_TYPE, COLUMN_COMMENT 
                    FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = '$db_database' 
                    AND TABLE_NAME = 'penilaian_medis_ralan_kandungan'";
    $debug_stmt1 = $pdo->query($debug_query1);
    $kolom_tabel1 = $debug_stmt1->fetchAll(PDO::FETCH_ASSOC);
    file_put_contents($log_file, "Detail kolom penilaian_medis_ralan_kandungan:\n" . print_r($kolom_tabel1, true) . "\n", FILE_APPEND);

    $debug_query2 = "SELECT COLUMN_NAME, DATA_TYPE, COLUMN_COMMENT 
                    FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = '$db_database' 
                    AND TABLE_NAME = 'status_obstetri'";
    $debug_stmt2 = $pdo->query($debug_query2);
    $kolom_tabel2 = $debug_stmt2->fetchAll(PDO::FETCH_ASSOC);
    file_put_contents($log_file, "Detail kolom status_obstetri:\n" . print_r($kolom_tabel2, true) . "\n", FILE_APPEND);

    // Debug: Tampilkan contoh data dari tabel
    $debug_query3 = "SELECT * FROM penilaian_medis_ralan_kandungan LIMIT 1";
    $debug_stmt3 = $pdo->query($debug_query3);
    $contoh_data1 = $debug_stmt3->fetch(PDO::FETCH_ASSOC);
    file_put_contents($log_file, "Contoh data penilaian_medis_ralan_kandungan:\n" . print_r($contoh_data1, true) . "\n", FILE_APPEND);

    // Debug: Tampilkan nilai no_rkm_medis yang digunakan
    file_put_contents($log_file, "Nilai no_rkm_medis yang digunakan: " . $pasien['no_rkm_medis'] . "\n", FILE_APPEND);

    // Debug: Tampilkan struktur tabel status_obstetri
    $debug_query4 = "DESCRIBE status_obstetri";
    $debug_stmt4 = $pdo->query($debug_query4);
    $struktur_obstetri = $debug_stmt4->fetchAll(PDO::FETCH_ASSOC);
    file_put_contents($log_file, "Struktur tabel status_obstetri:\n" . print_r($struktur_obstetri, true) . "\n", FILE_APPEND);

    // Buat instance PDF dengan ukuran khusus (100x60 mm)
    $pdf = new ResumePDF('L', 'mm', array(60, 100));
    file_put_contents($log_file, "PDF instance created\n", FILE_APPEND);

    // Set margin minimal
    $pdf->SetMargins(3, 2, 3);
    $pdf->SetAutoPageBreak(TRUE, 2);

    // Tambah halaman
    $pdf->AddPage();
    file_put_contents($log_file, "Page added\n", FILE_APPEND);

    // Ambil data pemeriksaan terbaru dari tabel penilaian_medis_ralan_kandungan
    // Periksa struktur tabel untuk menentukan query yang benar
    $debug_query5 = "DESCRIBE penilaian_medis_ralan_kandungan";
    $debug_stmt5 = $pdo->query($debug_query5);
    $struktur_pemeriksaan = $debug_stmt5->fetchAll(PDO::FETCH_ASSOC);
    file_put_contents($log_file, "Struktur tabel penilaian_medis_ralan_kandungan:\n" . print_r($struktur_pemeriksaan, true) . "\n", FILE_APPEND);

    // Coba query dengan no_rawat
    $query_pemeriksaan = "SELECT pmrk.tanggal as tanggal, pmrk.ket_fisik, pmrk.lab, pmrk.ultra, pmrk.diagnosis, pmrk.tata,
                         pmrk.td, pmrk.bb, pmrk.bmi, pmrk.interpretasi_bmi 
                         FROM penilaian_medis_ralan_kandungan pmrk
                         JOIN reg_periksa rp ON pmrk.no_rawat = rp.no_rawat
                         WHERE rp.no_rkm_medis = :no_rkm_medis 
                         ORDER BY pmrk.tanggal DESC LIMIT 1";
    $stmt_pemeriksaan = $pdo->prepare($query_pemeriksaan);
    $stmt_pemeriksaan->execute([':no_rkm_medis' => $pasien['no_rkm_medis']]);
    $pemeriksaan = $stmt_pemeriksaan->fetch(PDO::FETCH_ASSOC);

    // Jika tidak ada hasil, coba query alternatif
    if (!$pemeriksaan) {
        file_put_contents($log_file, "Query pemeriksaan pertama tidak menghasilkan data, mencoba query alternatif\n", FILE_APPEND);
        $query_pemeriksaan_alt = "SELECT * FROM penilaian_medis_ralan_kandungan 
                                 WHERE no_rawat = :no_rkm_medis 
                                 ORDER BY tanggal DESC LIMIT 1";
        $stmt_pemeriksaan_alt = $pdo->prepare($query_pemeriksaan_alt);
        $stmt_pemeriksaan_alt->execute([':no_rkm_medis' => $pasien['no_rkm_medis']]);
        $pemeriksaan = $stmt_pemeriksaan_alt->fetch(PDO::FETCH_ASSOC);
    }

    // Debug: Tampilkan hasil query pemeriksaan
    file_put_contents($log_file, "Hasil query pemeriksaan:\n" . print_r($pemeriksaan, true) . "\n", FILE_APPEND);

    // Ambil data obstetri terbaru dari tabel status_obstetri
    $query_obstetri = "SELECT tanggal_tp_penyesuaian, faktor_risiko_umum, faktor_risiko_obstetri, faktor_risiko_preeklampsia 
                      FROM status_obstetri 
                      WHERE no_rkm_medis = :no_rkm_medis 
                      ORDER BY tanggal_tp_penyesuaian DESC LIMIT 1";
    $stmt_obstetri = $pdo->prepare($query_obstetri);
    $stmt_obstetri->execute([':no_rkm_medis' => $pasien['no_rkm_medis']]);
    $obstetri = $stmt_obstetri->fetch(PDO::FETCH_ASSOC);

    // Debug: Tampilkan hasil query obstetri
    file_put_contents($log_file, "Hasil query obstetri:\n" . print_r($obstetri, true) . "\n", FILE_APPEND);

    // Reset ke font 6pt untuk label dan 8pt untuk isi
    $pdf->SetFont('helvetica', '', 6);
    $pdf->Cell(20, 2, 'Tanggal Periksa ', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(0, 2, !empty($pemeriksaan['tanggal']) ? date('d-m-Y', strtotime($pemeriksaan['tanggal'])) : '-', 0, 1, 'L');

    // Pemeriksaan fisik
    $pdf->SetFont('helvetica', '', 6);
    $pdf->Cell(20, 2, 'Pemeriksaan fisik ', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 8);

    // Gabungkan data TD, BMI, dan ket_fisik
    $pemeriksaan_fisik = '';
    if (!empty($pemeriksaan['td'])) {
        $pemeriksaan_fisik .= 'TD ' . $pemeriksaan['td'] . ', ';
    }
    if (!empty($pemeriksaan['bmi'])) {
        $pemeriksaan_fisik .= 'BMI ' . $pemeriksaan['bmi'];
        if (!empty($pemeriksaan['interpretasi_bmi'])) {
            $pemeriksaan_fisik .= ' (' . $pemeriksaan['interpretasi_bmi'] . '), ';
        } else {
            $pemeriksaan_fisik .= ', ';
        }
    }
    if (!empty($pemeriksaan['ket_fisik'])) {
        $pemeriksaan_fisik .= $pemeriksaan['ket_fisik'];
    }

    // Jika tidak ada data, tampilkan tanda strip
    if (empty($pemeriksaan_fisik)) {
        $pemeriksaan_fisik = '-';
    }

    $pdf->MultiCell(0, 2, $pemeriksaan_fisik, 0, 'L');

    // Hasil USG - Memastikan label sejajar dengan label lainnya
    $pdf->SetFont('helvetica', '', 6);
    $pdf->Cell(20, 2, 'Hasil USG ', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(2, 2, '', 0, 0, 'L');
    $pdf->MultiCell(0, 2, !empty($pemeriksaan['ultra']) ? $pemeriksaan['ultra'] : '-', 0, 'L');

    // Tambahkan spasi 0,5 baris
    $pdf->Ln(1);

    // Diagnosis
    $pdf->SetFont('helvetica', '', 6);
    $pdf->Cell(20, 2, 'Diagnosis ', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 8);
    $pdf->MultiCell(0, 2, !empty($pemeriksaan['diagnosis']) ? $pemeriksaan['diagnosis'] : '-', 0, 'L');

    // Rencana dan Saran
    $pdf->SetFont('helvetica', '', 6);
    $pdf->Cell(20, 2, 'Rencana & Saran ', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 8);
    $pdf->MultiCell(0, 2, !empty($pemeriksaan['tata']) ? $pemeriksaan['tata'] : '-', 0, 'L');

    file_put_contents($log_file, "All data added to PDF\n", FILE_APPEND);

    // Bersihkan output buffer sebelum mengirim header
    if (ob_get_length()) {
        file_put_contents($log_file, "Cleaning output buffer of length: " . ob_get_length() . "\n", FILE_APPEND);
        ob_clean();
    }

    // Set header untuk PDF
    file_put_contents($log_file, "Setting headers\n", FILE_APPEND);
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="resume_medis_' . $pasien['no_rkm_medis'] . '.pdf"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Output PDF langsung ke browser
    file_put_contents($log_file, "Outputting PDF\n", FILE_APPEND);
    echo $pdf->Output('resume_medis_' . $pasien['no_rkm_medis'] . '.pdf', 'S');
    file_put_contents($log_file, "PDF output complete\n", FILE_APPEND);
    exit;
} catch (Exception $e) {
    // Log error
    $error_message = "Error generating PDF: " . $e->getMessage();
    file_put_contents($log_file, $error_message . "\n", FILE_APPEND);
    error_log($error_message);

    // Clean output buffer
    if (ob_get_length()) {
        ob_clean();
    }

    // Set error header
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: text/plain');

    // Output error message
    die($error_message);
}
