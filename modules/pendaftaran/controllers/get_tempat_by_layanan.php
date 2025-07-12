<?php
// Endpoint: Ambil tempat praktek berdasarkan layanan dari jadwal_rutin
require_once __DIR__ . '/../../../config/koneksi.php';
$conn = getPDOConnection();

header('Content-Type: application/json');

$id_layanan = isset($_GET['layanan']) ? $_GET['layanan'] : '';

if (empty($id_layanan)) {
    echo json_encode(['error' => 'ID Layanan tidak diberikan']);
    exit;
}

try {
    $query = "SELECT DISTINCT tp.ID_Tempat_Praktek, tp.Nama_Tempat FROM jadwal_rutin jr
              JOIN tempat_praktek tp ON jr.ID_Tempat_Praktek = tp.ID_Tempat_Praktek
              WHERE jr.ID_Layanan = :id_layanan AND tp.Status_Aktif = 1 AND jr.status_aktif = 1
              ORDER BY tp.Nama_Tempat ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute(['id_layanan' => $id_layanan]);
    $tempat = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($tempat);
} catch (PDOException $e) {
    error_log("Database Error in get_tempat_by_layanan.php: " . $e->getMessage());
    echo json_encode(['error' => 'Terjadi kesalahan saat mengambil data tempat praktek']);
}
