<?php
// Endpoint: Ambil jadwal berdasarkan layanan, tempat praktek, dan dokter dari jadwal_rutin
require_once __DIR__ . '/../../../config/koneksi.php';
$conn = getPDOConnection();

header('Content-Type: application/json');

$id_layanan = isset($_GET['layanan']) ? $_GET['layanan'] : '';
$id_tempat = isset($_GET['tempat']) ? $_GET['tempat'] : '';
$id_dokter = isset($_GET['dokter']) ? $_GET['dokter'] : '';

if (empty($id_layanan) || empty($id_tempat) || empty($id_dokter)) {
    echo json_encode(['error' => 'ID Layanan, Tempat, dan Dokter wajib diisi']);
    exit;
}

try {
    $query = "SELECT jr.ID_Jadwal_Rutin, jr.Hari, jr.Jam_Mulai, jr.Jam_Selesai
              FROM jadwal_rutin jr
              WHERE jr.ID_Layanan = :id_layanan AND jr.ID_Tempat_Praktek = :id_tempat AND jr.ID_Dokter = :id_dokter AND jr.status_aktif = 1
              ORDER BY FIELD(jr.Hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'), jr.Jam_Mulai ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute(['id_layanan' => $id_layanan, 'id_tempat' => $id_tempat, 'id_dokter' => $id_dokter]);
    $jadwal = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($jadwal);
} catch (PDOException $e) {
    error_log("Database Error in get_jadwal_by_layanan_tempat_dokter.php: " . $e->getMessage());
    echo json_encode(['error' => 'Terjadi kesalahan saat mengambil data jadwal']);
}
