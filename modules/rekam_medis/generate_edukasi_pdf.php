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
file_put_contents($log_file, "PDF Edukasi generation started at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

// Impor TCPDF
require_once(__DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php');

// Extend TCPDF
class EdukasiPDF extends TCPDF
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
if (!isset($pasien) || !isset($pemeriksaan)) {
    file_put_contents($log_file, "Error: Data pasien atau pemeriksaan tidak tersedia\n", FILE_APPEND);
    die("Data pasien atau pemeriksaan tidak tersedia");
}

file_put_contents($log_file, "Data pasien tersedia: " . $pasien['no_rkm_medis'] . "\n", FILE_APPEND);
file_put_contents($log_file, "Data pemeriksaan tersedia\n", FILE_APPEND);

// Log data pemeriksaan untuk debugging
if (isset($pemeriksaan)) {
    file_put_contents($log_file, "Data pemeriksaan: " . json_encode($pemeriksaan) . "\n", FILE_APPEND);
    file_put_contents($log_file, "Edukasi: " . ($pemeriksaan['edukasi'] ?? 'tidak ada') . "\n", FILE_APPEND);
} else {
    file_put_contents($log_file, "Data pemeriksaan tidak tersedia\n", FILE_APPEND);
}

try {
    // Buat instance PDF dengan ukuran khusus (100x60 mm)
    $pdf = new EdukasiPDF('L', 'mm', array(60, 100));
    file_put_contents($log_file, "PDF instance created\n", FILE_APPEND);

    // Set margin minimal
    $pdf->SetMargins(3, 3, 3);
    $pdf->SetAutoPageBreak(TRUE, 3);

    // Tambah halaman
    $pdf->AddPage();
    file_put_contents($log_file, "Page added\n", FILE_APPEND);

    // Judul
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 4, 'Pesan Edukasi', 0, 1, 'R');

    // Tambahkan baris kosong untuk jarak
    $pdf->Cell(0, 1, '', 0, 1);

    // Isi Edukasi
    $pdf->SetFont('helvetica', '', 8);
    $edukasi = !empty($pemeriksaan['edukasi']) ? $pemeriksaan['edukasi'] : '-';
    $pdf->MultiCell(0, 4, $edukasi, 0, 'L');

    // Tambahkan tanggal pemeriksaan
    $tanggal_periksa = !empty($pemeriksaan['tanggal']) ? date('d-m-Y', strtotime($pemeriksaan['tanggal'])) : date('d-m-Y');
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 4, $tanggal_periksa, 0, 1, 'R');

    file_put_contents($log_file, "All data added to PDF\n", FILE_APPEND);

    // Bersihkan output buffer sebelum mengirim header
    if (ob_get_length()) {
        file_put_contents($log_file, "Cleaning output buffer of length: " . ob_get_length() . "\n", FILE_APPEND);
        ob_clean();
    }

    // Set header untuk PDF
    file_put_contents($log_file, "Setting headers\n", FILE_APPEND);
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="edukasi_' . $pasien['no_rkm_medis'] . '.pdf"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Output PDF langsung ke browser
    file_put_contents($log_file, "Outputting PDF\n", FILE_APPEND);
    echo $pdf->Output('edukasi_' . $pasien['no_rkm_medis'] . '.pdf', 'S');
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
