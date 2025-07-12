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
file_put_contents($log_file, "PDF generation started at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

// Impor TCPDF
require_once(__DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php');

// Extend TCPDF
class StatusObstetriPDF extends TCPDF
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
if (!isset($pasien) || !isset($obstetri)) {
    file_put_contents($log_file, "Error: Data pasien atau obstetri tidak tersedia\n", FILE_APPEND);
    die("Data pasien atau obstetri tidak tersedia");
}

file_put_contents($log_file, "Data pasien tersedia: " . $pasien['no_rkm_medis'] . "\n", FILE_APPEND);
file_put_contents($log_file, "Data obstetri tersedia\n", FILE_APPEND);

try {
    // Buat instance PDF dengan ukuran khusus (100x60 mm)
    $pdf = new StatusObstetriPDF('L', 'mm', array(60, 100));
    file_put_contents($log_file, "PDF instance created\n", FILE_APPEND);

    // Set margin minimal
    $pdf->SetMargins(3, 2, 3);
    $pdf->SetAutoPageBreak(TRUE, 2);

    // Tambah halaman
    $pdf->AddPage();
    file_put_contents($log_file, "Page added\n", FILE_APPEND);

    // Nama dan Usia dan GPA
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 2, $pasien['nm_pasien'] . ' / ' . $pasien['umur'] . ' th' . ' / ' . 'G' . $obstetri['gravida'] . ' P' . $obstetri['paritas'] . ' A' . $obstetri['abortus'], 0, 1, 'L');
    $pdf->Ln(2); // Menambahkan space setelah cell ini (2 mm)


    // HPL/TP
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(20, 2, 'HPL/TP ', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 2, !empty($obstetri['tanggal_tp_penyesuaian']) ? date('d-m-Y', strtotime($obstetri['tanggal_tp_penyesuaian'])) : '-', 0, 1, 'L');

    // Faktor Risiko
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(20, 2, 'Faktor Risiko ', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $risiko = array();
    if (!empty($obstetri['faktor_risiko_umum'])) $risiko[] = 'Umum: ' . $obstetri['faktor_risiko_umum'];
    if (!empty($obstetri['faktor_risiko_obstetri'])) $risiko[] = 'Obstetri: ' . $obstetri['faktor_risiko_obstetri'];
    if (!empty($obstetri['faktor_risiko_preeklampsia'])) $risiko[] = 'Preeklampsia: ' . $obstetri['faktor_risiko_preeklampsia'];

    // Menggunakan PHP_EOL untuk line break alih-alih koma
    $pdf->MultiCell(0, 4, implode(PHP_EOL, $risiko), 0, 'L');

    // Catatan
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(20, 2, 'Catatan ', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->MultiCell(0, 2, !empty($obstetri['hasil_faktor_risiko']) ? $obstetri['hasil_faktor_risiko'] : '-', 0, 'L');

    file_put_contents($log_file, "All data added to PDF\n", FILE_APPEND);

    // Bersihkan output buffer sebelum mengirim header
    if (ob_get_length()) {
        file_put_contents($log_file, "Cleaning output buffer of length: " . ob_get_length() . "\n", FILE_APPEND);
        ob_clean();
    }

    // Set header untuk PDF
    file_put_contents($log_file, "Setting headers\n", FILE_APPEND);
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="status_obstetri_' . $pasien['no_rkm_medis'] . '.pdf"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Output PDF langsung ke browser
    file_put_contents($log_file, "Outputting PDF\n", FILE_APPEND);
    echo $pdf->Output('status_obstetri_' . $pasien['no_rkm_medis'] . '.pdf', 'S');
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
