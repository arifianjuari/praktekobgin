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
$log_file = __DIR__ . '/../../pdf_debug.log';
file_put_contents($log_file, "PDF Status Ginekologi generation started at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

// Impor TCPDF
require_once(__DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php');

// Extend TCPDF
class StatusGinekologiPDF extends TCPDF
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

// Pastikan variabel yang diperlukan tersedia
if (!isset($pasien) || !isset($statusGinekologi)) {
    file_put_contents($log_file, "Error: Data pasien atau status ginekologi tidak tersedia\n", FILE_APPEND);
    die("Data pasien atau status ginekologi tidak tersedia");
}

file_put_contents($log_file, "Data pasien tersedia: " . $pasien['no_rkm_medis'] . "\n", FILE_APPEND);
file_put_contents($log_file, "Data status ginekologi tersedia\n", FILE_APPEND);

try {
    // Buat instance PDF dengan ukuran khusus (100x60 mm)
    $pdf = new StatusGinekologiPDF('L', 'mm', array(60, 100));
    file_put_contents($log_file, "PDF instance created\n", FILE_APPEND);

    // Set margin minimal
    $pdf->SetMargins(3, 3, 3);
    $pdf->SetAutoPageBreak(TRUE, 3);

    // Tambah halaman
    $pdf->AddPage();
    file_put_contents($log_file, "Page added\n", FILE_APPEND);

    // Nama, Usia, dan P/A
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 4, $pasien['nm_pasien'] . ' / ' . $pasien['umur'] . ' th / P' . $statusGinekologi['Parturien'] . ' A' . $statusGinekologi['Abortus'], 0, 1, 'L');

    // HPHT dan KB
    $pdf->SetFont('helvetica', '', 8);
    $hpht = !empty($statusGinekologi['Hari_pertama_haid_terakhir']) ? date('d-m-Y', strtotime($statusGinekologi['Hari_pertama_haid_terakhir'])) : '-';
    $pdf->Cell(0, 4, 'HPHT ' . $hpht . ' / KB ' . $statusGinekologi['Kontrasepsi_terakhir'], 0, 1, 'L');

    // Tanggal Periksa
    $tanggal_periksa = !empty($pemeriksaan['tanggal']) ? date('d-m-Y', strtotime($pemeriksaan['tanggal'])) : '-';
    $pdf->Cell(25, 4, 'Tanggal Periksa', 0, 0, 'L');
    $pdf->Cell(0, 4, ': ' . $tanggal_periksa, 0, 1, 'L');

    // Pemeriksaan Fisik
    $td = !empty($pemeriksaan['td']) ? $pemeriksaan['td'] : '-';
    $ket_fisik = !empty($pemeriksaan['ket_fisik']) ? $pemeriksaan['ket_fisik'] : '-';
    $pdf->Cell(25, 4, 'Pemeriksaan fisik', 0, 0, 'L');
    $pdf->Cell(0, 4, ': TD ' . $td . ', ' . $ket_fisik, 0, 1, 'L');

    // Hasil USG
    $pdf->Cell(25, 4, 'Hasil USG', 0, 0, 'L');
    $pdf->Cell(0, 4, ':', 0, 1, 'L');

    // USG Content
    $ultra = !empty($pemeriksaan['ultra']) ? $pemeriksaan['ultra'] : '-';
    $pdf->SetFont('helvetica', '', 7);
    // Menggunakan MultiCell dengan lebar penuh dan tinggi baris 3mm
    $pdf->MultiCell(0, 3, $ultra, 0, 'L');

    // Diagnosis
    $pdf->SetFont('helvetica', '', 8);
    $diagnosis = !empty($pemeriksaan['diagnosis']) ? $pemeriksaan['diagnosis'] : '-';
    $pdf->Cell(25, 4, 'Diagnosis', 0, 0, 'L');
    $pdf->Cell(0, 4, ': ' . $diagnosis, 0, 1, 'L');

    // Rencana & Saran
    $tata = !empty($pemeriksaan['tata']) ? $pemeriksaan['tata'] : '-';
    $resep = !empty($pemeriksaan['resep']) ? $pemeriksaan['resep'] : '-';

    $pdf->Cell(25, 4, 'Rencana & Saran', 0, 0, 'L');

    // Gabungkan semua informasi rencana & saran
    $rencana_saran = $tata;
    if (!empty($resep) && $resep != '-') {
        $rencana_saran .= $resep;
    }

    // Gunakan MultiCell untuk rencana & saran agar bisa wrap text
    $pdf->Cell(2, 4, ':', 0, 0, 'L');
    $current_x = $pdf->GetX();
    $current_y = $pdf->GetY();
    $pdf->MultiCell(69, 3, $rencana_saran, 0, 'L');

    file_put_contents($log_file, "All data added to PDF\n", FILE_APPEND);

    // Bersihkan output buffer sebelum mengirim header
    if (ob_get_length()) {
        file_put_contents($log_file, "Cleaning output buffer of length: " . ob_get_length() . "\n", FILE_APPEND);
        ob_clean();
    }

    // Set header untuk PDF
    file_put_contents($log_file, "Setting headers\n", FILE_APPEND);
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="status_ginekologi_' . $pasien['no_rkm_medis'] . '.pdf"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Output PDF langsung ke browser
    file_put_contents($log_file, "Outputting PDF\n", FILE_APPEND);
    echo $pdf->Output('status_ginekologi_' . $pasien['no_rkm_medis'] . '.pdf', 'S');
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
