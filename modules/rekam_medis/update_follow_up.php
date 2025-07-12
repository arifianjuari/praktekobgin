<?php
// File untuk mengupdate status follow up pasien
require_once '../../config/koneksi.php';

// Validasi parameter
if (!isset($_GET['no_rawat']) || empty($_GET['no_rawat'])) {
    header('Location: ../../index.php?module=rekam_medis&action=daftar_atensi&error=no_rawat_invalid');
    exit;
}

$no_rawat = $_GET['no_rawat'];
$status = isset($_GET['status']) ? (int)$_GET['status'] : 1; // Default ke 1 (sudah follow up)

$pdo = getPDOConnection();
$query = "UPDATE penilaian_medis_ralan_kandungan SET sudah_follow_up = ? WHERE no_rawat = ?";
$stmt = $pdo->prepare($query);

if ($stmt->execute([$status, $no_rawat])) {
    header('Location: ../../index.php?module=rekam_medis&action=daftar_atensi&success=follow_up_updated');
} else {
    $errorInfo = $stmt->errorInfo();
    header('Location: ../../index.php?module=rekam_medis&action=daftar_atensi&error=' . urlencode($errorInfo[2]));
}

// Tidak perlu close PDO, koneksi otomatis ditutup di akhir script

?>
