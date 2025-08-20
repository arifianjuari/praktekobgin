<?php
// Pastikan tidak ada output sebelum header
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load TCPDF library
require_once('../../vendor/tecnickcom/tcpdf/tcpdf.php');

// Load markdown helper
require_once('helpers/markdown_helper.php');

// Periksa apakah parameter tersedia
if (!isset($_GET['isi']) || trim($_GET['isi']) === '') {
    die("Tidak ada data edukasi yang diberikan.");
}

// Dapatkan data dari parameter
$isiEdukasi = trim($_GET['isi']);
// Jika parameter html=1, maka konten sudah berupa HTML lengkap
$isHtml = isset($_GET['html']) && $_GET['html'] == '1';
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
// Hilangkan padding/margin cell agar tidak ada spasi ekstra
$tempPdf->setCellPaddings(0, 0, 0, 0);
$tempPdf->setCellMargins(0, 0, 0, 0);
$tempPdf->setCellHeightRatio(1.0);

// ---- Mulai Tambahkan Konten ke PDF Sementara ----

// Posisi awal Y untuk melacak
$startY = $tempPdf->GetY();

// Tampilkan hasil edukasi
$tempPdf->SetFont('helvetica', '', 9);
// Samakan proses render dengan final agar perhitungan tinggi akurat
if ($isHtml) {
    $renderedContent = $isiEdukasi;
} else {
    $renderedContent = markdownToHtml($isiEdukasi);
}
$zeroMarginCss = '<style>
  div, p, h1, h2, h3, h4, h5, h6, ul, ol, li { margin: 0; padding: 0; }
  ul, ol { list-style-position: inside; padding-left: 0; }
  li { margin: 0; padding: 0; }
</style>';
$tempPdf->writeHTML($zeroMarginCss . '<div style="margin:0; padding:0; text-align: left;">' . $renderedContent . '</div>', false, false, true, false, '');

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
// Hilangkan padding/margin cell agar tidak ada spasi ekstra
$pdf->setCellPaddings(0, 0, 0, 0);
$pdf->setCellMargins(0, 0, 0, 0);
$pdf->setCellHeightRatio(1.0);

// ---- Mulai Tambahkan Konten ke PDF FINAL (sama seperti di atas) ----





// Tampilkan hasil edukasi
$pdf->SetFont('helvetica', '', 9);
// Parse markdown content if it's not already HTML
if ($isHtml) {
    $renderedContent = $isiEdukasi;
} else {
    $renderedContent = markdownToHtml($isiEdukasi);
}
$pdf->writeHTML($zeroMarginCss . '<div style="margin:0; padding:0; text-align: left;">' . $renderedContent . '</div>', false, false, true, false, '');

// ---- Akhir Tambahkan Konten ke PDF FINAL ----

// Output PDF
$pdf->Output('edukasi_pasien_' . $noRm . '.pdf', 'I'); // I untuk inline view, D untuk download 