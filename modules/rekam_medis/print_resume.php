<?php
// Pastikan tidak ada output sebelum header
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load TCPDF library
require_once('../../vendor/tecnickcom/tcpdf/tcpdf.php');

// Periksa apakah parameter tersedia
if (!isset($_GET['isi']) || trim($_GET['isi']) === '') {
    die("Tidak ada data resume yang diberikan.");
}

// Dapatkan data dari parameter
$isiResume = trim($_GET['isi']);
$noRawat = isset($_GET['no_rawat']) ? $_GET['no_rawat'] : 'N/A';
$namaPasien = isset($_GET['nama']) ? $_GET['nama'] : 'N/A';
$noRm = isset($_GET['no_rm']) ? $_GET['no_rm'] : 'N/A';

// Buat kelas turunan TCPDF untuk kustomisasi header dan footer
class MYPDF extends TCPDF
{
    // Hilangkan header default
    public function Header()
    {
        // Kosong (tidak ada header)
    }

    // Hilangkan footer default
    public function Footer()
    {
        // Kosong (tidak ada footer)
    }
}

// Tetapkan margin yang akan digunakan
$leftMargin = 1;
$topMargin = 1;
$rightMargin = 1;

// Tetapkan lebar konten
$contentWidth = 98; // 100mm - 8mm margin kiri - 8mm margin kanan

// Buat instance PDF sementara untuk menghitung tinggi konten
$tempPdf = new TCPDF('P', 'mm', array(100, 297), true, 'UTF-8', false);
$tempPdf->setPrintHeader(false);
$tempPdf->setPrintFooter(false);
$tempPdf->SetMargins($leftMargin, $topMargin, $rightMargin);
$tempPdf->SetAutoPageBreak(false); // Nonaktifkan page break otomatis untuk kalkulasi

$tempPdf->AddPage();

// ---- Mulai Tambahkan Konten ke PDF Sementara ----

// Posisi awal Y untuk melacak
$startY = $tempPdf->GetY();

$tempPdf->SetFont('helvetica', '', 7);
// Get page width and calculate position for right alignment
$pageWidth = $tempPdf->getPageWidth();
$dateText = 'Tgl. Cetak: ' . date('d-m-Y H:i');
$textWidth = $tempPdf->GetStringWidth($dateText);
// Position text at right margin
$tempPdf->SetX($pageWidth - $textWidth - $rightMargin);
$tempPdf->Cell($textWidth, 5, $dateText, 0, 1, 'R');

// Garis pemisah
$tempPdf->Ln(2);
$tempPdf->Line($leftMargin, $tempPdf->GetY(), 100 - $rightMargin, $tempPdf->GetY());
$tempPdf->Ln(4);

// Proses teks resume untuk menampilkan header dengan bold
$sections = [
    'STATUS OBSTETRI:' => true,
    'STATUS GINEKOLOGI:' => true,
    'PEMERIKSAAN FISIK:' => true,
    'PEMERIKSAAN USG:' => true,
    'DIAGNOSIS:' => true,
    'TATALAKSANA:' => true
];

$lines = explode("\n", $isiResume);
$formattedText = '';

// Flag to identify the first line (patient name)
$isFirstLine = true;

foreach ($lines as $line) {
    // Skip empty lines at the beginning
    if (empty(trim($line)) && $isFirstLine) {
        $formattedText .= "\n";
        continue;
    }
    
    // Check if this is the first non-empty line (patient name)
    if ($isFirstLine && !empty(trim($line))) {
        // Format the patient name with larger font size and bold
        $formattedText .= '<span style="font-size: 12pt; font-weight: bold;">' . $line . '</span>' . "\n";
        $isFirstLine = false;
    } else {
        // Check for section headers
        $isHeader = false;
        foreach ($sections as $header => $value) {
            if (strpos($line, $header) !== false) {
                $formattedText .= '<b>' . $line . '</b>' . "\n";
                $isHeader = true;
                break;
            }
        }

        if (!$isHeader) {
            $formattedText .= $line . "\n";
        }
    }
}

// Tampilkan hasil resume dengan format HTML
$tempPdf->writeHTML('<div style="line-height: 1.5; font-size: 10pt;">' . nl2br($formattedText) . '</div>', true, false, true, false, '');

// ---- Akhir Tambahkan Konten ke PDF Sementara ----

// Dapatkan posisi Y terakhir (tinggi konten)
$contentHeight = $tempPdf->GetY();

// Hitung tinggi halaman yang dibutuhkan
// Tambahkan margin bawah 10mm
$pageHeight = max(100, $contentHeight + 10);

// Hapus PDF sementara
unset($tempPdf);

// Buat instance PDF FINAL dengan tinggi yang dihitung
$pdf = new MYPDF('P', 'mm', array(100, $pageHeight), true, 'UTF-8', false);

// Nonaktifkan header bawaan
$pdf->setPrintHeader(false);
// Nonaktifkan footer kustom
$pdf->setPrintFooter(false);

// Set margin konsisten dengan kalkulasi sebelumnya
$pdf->SetMargins($leftMargin, $topMargin, $rightMargin);

// Nonaktifkan auto page break karena tinggi sudah dihitung
$pdf->SetAutoPageBreak(false);

// Tambahkan halaman
$pdf->AddPage();

// ---- Mulai Tambahkan Konten ke PDF FINAL (sama seperti di atas) ----

$pdf->SetFont('helvetica', '', 7);
// Get page width and calculate position for right alignment
$pageWidth = $pdf->getPageWidth();
$dateText = 'Tgl. Cetak: ' . date('d-m-Y H:i');
$textWidth = $pdf->GetStringWidth($dateText);
// Position text at right margin
$pdf->SetX($pageWidth - $textWidth - $rightMargin);
$pdf->Cell($textWidth, 5, $dateText, 0, 1, 'R');

// Tambahkan garis pemisah
$pdf->Ln(2);
$pdf->Line($leftMargin, $pdf->GetY(), 100 - $rightMargin, $pdf->GetY());
$pdf->Ln(4);

// Proses teks resume untuk menampilkan header dengan bold (gunakan hasil yang sudah diformat)
$pdf->writeHTML('<div style="line-height: 1.5; font-size: 10pt;">' . nl2br($formattedText) . '</div>', true, false, true, false, '');

// ---- Akhir Tambahkan Konten ke PDF FINAL ----

// Output PDF
$pdf->Output('resume_medis_' . $noRm . '.pdf', 'I'); // I untuk inline view, D untuk download 