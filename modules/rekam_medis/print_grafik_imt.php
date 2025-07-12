<?php
// Pastikan tidak ada output sebelum header
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load TCPDF library
require_once('../../vendor/tecnickcom/tcpdf/tcpdf.php');

// Periksa apakah parameter tersedia
if (!isset($_POST['image_data']) || trim($_POST['image_data']) === '') {
    die("Tidak ada data grafik yang diberikan.");
}

// Dapatkan data dari parameter
$imageData = $_POST['image_data'];
$noRm = isset($_POST['no_rm']) ? $_POST['no_rm'] : 'N/A';
$namaPasien = isset($_POST['nama']) ? $_POST['nama'] : 'N/A';
$kategoriIMT = isset($_POST['kategori_imt']) ? $_POST['kategori_imt'] : 'N/A';
$rekomendasi = isset($_POST['rekomendasi']) ? $_POST['rekomendasi'] : 'N/A';

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

// Buat instance PDF
$pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Nonaktifkan header dan footer bawaan
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margin
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 15);

// Tambahkan halaman
$pdf->AddPage();

// Judul utama
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'GRAFIK PENINGKATAN BERAT BADAN', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'KATEGORI IMT PRA KEHAMILAN', 0, 1, 'C');

$pdf->Ln(5);

// Informasi pasien
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(40, 6, 'Nama Pasien', 0, 0, 'L');
$pdf->Cell(5, 6, ':', 0, 0, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, $namaPasien, 0, 1, 'L');

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(40, 6, 'No. RM', 0, 0, 'L');
$pdf->Cell(5, 6, ':', 0, 0, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, $noRm, 0, 1, 'L');

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(40, 6, 'Kategori IMT', 0, 0, 'L');
$pdf->Cell(5, 6, ':', 0, 0, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, $kategoriIMT, 0, 1, 'L');

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(40, 6, 'Rekomendasi', 0, 0, 'L');
$pdf->Cell(5, 6, ':', 0, 0, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, $rekomendasi, 0, 1, 'L');

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(40, 6, 'Tanggal Cetak', 0, 0, 'L');
$pdf->Cell(5, 6, ':', 0, 0, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, date('d-m-Y'), 0, 1, 'L');

$pdf->Ln(5);

// Proses image data URL
$imageData = str_replace('data:image/png;base64,', '', $imageData);
$imageData = str_replace(' ', '+', $imageData);
$imageData = base64_decode($imageData);

// Simpan gambar sementara
$tempFile = tempnam(sys_get_temp_dir(), 'img');
file_put_contents($tempFile, $imageData);

// Tambahkan gambar ke PDF
$pdf->Image($tempFile, 15, 70, 180, 90, 'PNG');

// Hapus file sementara
unlink($tempFile);

// Tambahkan keterangan di bawah grafik
$pdf->SetY(165);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, 'Keterangan:', 0, 1, 'L');

$pdf->SetFont('helvetica', '', 10);
$pdf->MultiCell(0, 6, 'Grafik ini menunjukkan peningkatan berat badan yang direkomendasikan selama kehamilan berdasarkan Indeks Massa Tubuh (IMT) pra-kehamilan. Garis putus-putus menunjukkan batas atas dan bawah yang direkomendasikan untuk kategori IMT pasien.', 0, 'L');

$pdf->Ln(3);
$pdf->MultiCell(0, 6, 'Rekomendasi peningkatan berat badan untuk kategori IMT ' . $kategoriIMT . ' adalah ' . $rekomendasi . '.', 0, 'L');

$pdf->Ln(3);
$pdf->MultiCell(0, 6, 'Catatan: Grafik ini diadaptasi dari Institute of Medicine (IOM) 2009.', 0, 'L');

// Output PDF
$pdf->Output('grafik_imt_' . $noRm . '.pdf', 'I'); // I untuk inline view, D untuk download
