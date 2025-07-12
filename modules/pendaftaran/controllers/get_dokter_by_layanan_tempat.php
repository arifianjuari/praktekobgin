<?php
// Endpoint: Ambil dokter berdasarkan layanan & tempat praktek dari jadwal_rutin
require_once __DIR__ . '/../../../config/koneksi.php';
$conn = getPDOConnection();

header('Content-Type: application/json');

$id_layanan = isset($_GET['layanan']) ? $_GET['layanan'] : '';
$id_tempat = isset($_GET['tempat']) ? $_GET['tempat'] : '';

if (empty($id_layanan) || empty($id_tempat)) {
    echo json_encode(['error' => 'ID Layanan dan ID Tempat Praktek wajib diisi']);
    exit;
}

try {
    $query = "SELECT DISTINCT d.ID_Dokter, d.Nama_Dokter, d.Spesialisasi
              FROM jadwal_rutin jr
              JOIN dokter d ON jr.ID_Dokter = d.ID_Dokter
              WHERE jr.ID_Layanan = :id_layanan AND jr.ID_Tempat_Praktek = :id_tempat AND d.Status_Aktif = 1 AND jr.status_aktif = 1
              ORDER BY d.Nama_Dokter ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute(['id_layanan' => $id_layanan, 'id_tempat' => $id_tempat]);
    $dokter = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($dokter);
} catch (PDOException $e) {
    error_log("Database Error in get_dokter_by_layanan_tempat.php: " . $e->getMessage());
    echo json_encode(['error' => 'Terjadi kesalahan saat mengambil data dokter']);
}
