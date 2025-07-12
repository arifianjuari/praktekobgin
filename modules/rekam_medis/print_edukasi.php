<?php
// Pastikan tidak ada output sebelum header
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load TCPDF library
require_once('../../vendor/tecnickcom/tcpdf/tcpdf.php');

// Periksa apakah parameter tersedia
if (!isset($_GET['isi']) || trim($_GET['isi']) === '') {
    die("Tidak ada data edukasi yang diberikan.");
}

// Dapatkan data dari parameter
$isiEdukasi = trim($_GET['isi']);
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

// Judul utama
$tempPdf->SetFont('helvetica', 'B', 12);
$tempPdf->Cell(0, 6, 'EDUKASI PASIEN', 0, 1, 'C');

$tempPdf->Ln(3);

// Informasi pasien
$tempPdf->SetFont('helvetica', 'B', 9);
$tempPdf->Cell(25, 5, 'Nama Pasien', 0, 0, 'L');
$tempPdf->Cell(3, 5, ':', 0, 0, 'C');
$tempPdf->SetFont('helvetica', '', 9);
$tempPdf->Cell(52, 5, $namaPasien, 0, 1, 'L');

$tempPdf->SetFont('helvetica', 'B', 9);
$tempPdf->Cell(25, 5, 'No. RM', 0, 0, 'L');
$tempPdf->Cell(3, 5, ':', 0, 0, 'C');
$tempPdf->SetFont('helvetica', '', 9);
$tempPdf->Cell(52, 5, $noRm, 0, 1, 'L');

$tempPdf->SetFont('helvetica', 'B', 9);
$tempPdf->Cell(25, 5, 'Tanggal', 0, 0, 'L');
$tempPdf->Cell(3, 5, ':', 0, 0, 'C');
$tempPdf->SetFont('helvetica', '', 9);
$tempPdf->Cell(52, 5, date('d-m-Y'), 0, 1, 'L');

// Garis pemisah
$tempPdf->Ln(2);
$tempPdf->Line($leftMargin, $tempPdf->GetY(), 100 - $rightMargin, $tempPdf->GetY());
$tempPdf->Ln(4);


// Tampilkan hasil edukasi
$tempPdf->SetFont('helvetica', '', 9);
$tempPdf->writeHTML('<div style="line-height: 1.5; text-align: justify;">' . nl2br($isiEdukasi) . '</div>', true, false, true, false, '');

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

// Judul utama
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 6, 'EDUKASI PASIEN', 0, 1, 'C');

$pdf->Ln(3);

// Informasi pasien
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(25, 5, 'Nama Pasien', 0, 0, 'L');
$pdf->Cell(3, 5, ':', 0, 0, 'C');
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(52, 5, $namaPasien, 0, 1, 'L');

$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(25, 5, 'No. RM', 0, 0, 'L');
$pdf->Cell(3, 5, ':', 0, 0, 'C');
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(52, 5, $noRm, 0, 1, 'L');

$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(25, 5, 'Tanggal', 0, 0, 'L');
$pdf->Cell(3, 5, ':', 0, 0, 'C');
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(52, 5, date('d-m-Y'), 0, 1, 'L');

// Garis pemisah
$pdf->Ln(2);
$pdf->Line($leftMargin, $pdf->GetY(), 100 - $rightMargin, $pdf->GetY());
$pdf->Ln(4);


// Tampilkan hasil edukasi
$pdf->SetFont('helvetica', '', 9);
$pdf->writeHTML('<div style="line-height: 1.5; text-align: justify;">' . nl2br($isiEdukasi) . '</div>', true, false, true, false, '');

// ---- Akhir Tambahkan Konten ke PDF FINAL ----

// Output PDF
$pdf->Output('edukasi_pasien_' . $noRm . '.pdf', 'I'); // I untuk inline view, D untuk download 