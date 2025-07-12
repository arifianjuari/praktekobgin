<?php
// Pastikan tidak ada output sebelum header
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load TCPDF library
require_once('../../vendor/tecnickcom/tcpdf/tcpdf.php');

// Periksa apakah parameter tersedia
if (!isset($_GET['isi']) || trim($_GET['isi']) === '') {
    die("Tidak ada data resep yang diberikan.");
}

// Dapatkan data dari parameter
$isiResep = trim($_GET['isi']);
$noRawat = isset($_GET['no_rawat']) ? $_GET['no_rawat'] : 'N/A';
$namaPasien = isset($_GET['nama']) ? $_GET['nama'] : 'N/A';
$tglLahir = isset($_GET['tgl_lahir']) ? $_GET['tgl_lahir'] : 'N/A';
$noTelp = isset($_GET['no_tlp']) ? $_GET['no_tlp'] : 'N/A';
$noRm = isset($_GET['no_rm']) ? $_GET['no_rm'] : 'N/A';

// Jika data untuk tgl_lahir dan no_tlp belum ada di parameter URL,
// maka coba ambil dari database
if ($tglLahir === 'N/A' || $noTelp === 'N/A') {
    // Pastikan koneksi database tersedia
    require_once('../../config/database.php');

    if (!empty($noRm) && $noRm !== 'N/A' && isset($conn) && $conn instanceof PDO) {
        try {
            // Query untuk mengambil data pasien berdasarkan nomor rekam medis
            $query = "SELECT tgl_lahir, no_tlp FROM pasien WHERE no_rkm_medis = :no_rm";
            $stmt = $conn->prepare($query);

            if ($stmt) {
                $stmt->bindParam(':no_rm', $noRm, PDO::PARAM_STR);
                $stmt->execute();

                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($result) {
                    // Perbarui data jika tersedia dari database
                    if ($tglLahir === 'N/A' && !empty($result['tgl_lahir'])) {
                        // Format ulang tanggal dari database (format YYYY-MM-DD) ke format DD-MM-YYYY
                        $tglLahir = date('d-m-Y', strtotime($result['tgl_lahir']));
                    }

                    if ($noTelp === 'N/A' && !empty($result['no_tlp'])) {
                        $noTelp = $result['no_tlp'];
                    }
                }
            }
        } catch (PDOException $e) {
            // Log error tapi lanjutkan program
            error_log("Error saat mengambil data pasien: " . $e->getMessage());
        }
    }
}

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
$tempPdf->SetFont('helvetica', 'B', 9);
$tempPdf->Cell(0, 6, 'RESEP OBAT', 0, 1, 'C');
$tempPdf->SetFont('helvetica', '', 9);
$tempPdf->Cell(0, 4, 'dr.ARIFIAN JUARI,SpOG / No.SIP MR35792503010132', 0, 1, 'C');
$tempPdf->Cell($contentWidth - 25, 5, 'Kota Batu, ' . date('d-m-Y') . ' 20 ....', 0, 1, 'R');
$tempPdf->Ln(2);

// Simbol R/
$tempPdf->SetFont('helvetica', 'B', 12);
$tempPdf->Cell(10, 10, 'R/', 0, 1, 'L');

// Tambahkan ruang kosong untuk resep
$tempPdf->Ln(30);

// Tambahkan informasi pasien di bagian bawah
$tempPdf->SetFont('helvetica', '', 9);
$tempPdf->Cell(15, 5, 'Pro', 0, 0, 'L');
$tempPdf->Cell(3, 5, ':', 0, 0, 'C');
$tempPdf->Cell(52, 5, '', 0, 1, 'L');

$tempPdf->Cell(15, 5, 'Tanggal Lahir', 0, 0, 'L');
$tempPdf->Cell(3, 5, ':', 0, 0, 'C');
$tempPdf->Cell(52, 5, '', 0, 1, 'L');

$tempPdf->Cell(15, 5, 'No.Telp', 0, 0, 'L');
$tempPdf->Cell(3, 5, ':', 0, 0, 'C');
$tempPdf->Cell(52, 5, '', 0, 1, 'L');

$tempPdf->Ln(5);

// Catatan obat
$tempPdf->SetFont('helvetica', '', 8);
$tempPdf->Cell($contentWidth, 5, 'Obat tidak boleh diganti tanpa persetujuan Dokter', 1, 1, 'C');

// ---- Akhir Tambahkan Konten ke PDF Sementara ----

// Dapatkan posisi Y terakhir (tinggi konten)
$contentHeight = $tempPdf->GetY();

// Hitung tinggi halaman yang dibutuhkan
// Kurangi margin keamanan dari 20mm menjadi hanya 5mm
$pageHeight = max(150, $contentHeight + 5);

// Hapus PDF sementara
unset($tempPdf);

// Buat instance PDF FINAL dengan tinggi yang dihitung
$pdf = new MYPDF('P', 'mm', array(100, $pageHeight), true, 'UTF-8', false);

// Nonaktifkan header bawaan
$pdf->setPrintHeader(false);
// Nonaktifkan footer kustom (karena sudah dihilangkan isinya)
$pdf->setPrintFooter(false);

// Set margin konsisten dengan kalkulasi sebelumnya
$pdf->SetMargins($leftMargin, $topMargin, $rightMargin);

// Nonaktifkan auto page break karena tinggi sudah dihitung
$pdf->SetAutoPageBreak(false);

// Tambahkan halaman
$pdf->AddPage();

// ---- Mulai Tambahkan Konten ke PDF FINAL (sama seperti di atas) ----

// Judul utama
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, 'dr.ARIFIAN JUARI,SpOG', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 4, 'No.SIP MR35792503010132', 0, 1, 'C');

// Garis pembatas
$pdf->Line($leftMargin, $pdf->GetY() + 2, 100 - $rightMargin, $pdf->GetY() + 2);
$pdf->Ln(4);

// Tanggal
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell($contentWidth - 0, 5, 'Kota Batu, ' . date('d-m-Y'), 0, 1, 'R');
$pdf->Ln(2);

// Simbol R/
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(10, 10, 'R/', 0, 1, 'L');

// Tambahkan ruang untuk resep
$pdf->MultiCell($contentWidth, 30, $isiResep, 0, 'L', false, 1, '', '', true, 0, false, true, 0);

// Tambahkan jarak antara isi resep dengan informasi pasien
$pdf->Ln(10);

// Tambahkan informasi pasien di bagian bawah
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(15, 5, 'Pro', 0, 0, 'L');
$pdf->Cell(3, 5, ':', 0, 0, 'C');
$pdf->Cell(52, 5, $namaPasien, 0, 1, 'L');

$pdf->Cell(15, 5, 'Tgl.Lahir', 0, 0, 'L');
$pdf->Cell(3, 5, ':', 0, 0, 'C');
$pdf->Cell(52, 5, $tglLahir, 0, 1, 'L');

$pdf->Cell(15, 5, 'No.Telp', 0, 0, 'L');
$pdf->Cell(3, 5, ':', 0, 0, 'C');
$pdf->Cell(52, 5, $noTelp, 0, 1, 'L');

$pdf->Ln(5);

// Catatan obat
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell($contentWidth, 5, 'Obat tidak boleh diganti tanpa persetujuan Dokter', 1, 1, 'C');

// ---- Akhir Tambahkan Konten ke PDF FINAL ----

// Output PDF
$pdf->Output('resep_' . $noRm . '.pdf', 'I'); // I untuk inline view, D untuk download 