<?php
// Impor konfigurasi database
require_once __DIR__ . '/../../../config/koneksi.php';
$conn = getPDOConnection();

// Set header untuk mengirim respons JSON
header('Content-Type: application/json');

// Ambil parameter tempat praktek dari request
$id_tempat_praktek = isset($_GET['tempat']) ? $_GET['tempat'] : '';

// Validasi input
if (empty($id_tempat_praktek)) {
    echo json_encode(['error' => 'ID Tempat Praktek tidak diberikan']);
    exit;
}

try {
    // Query untuk mendapatkan dokter berdasarkan tempat praktek
    $query = "SELECT DISTINCT d.* FROM dokter d 
              INNER JOIN jadwal_rutin jr ON d.ID_Dokter = jr.ID_Dokter 
              WHERE jr.ID_Tempat_Praktek = :id_tempat_praktek 
              AND d.Status_Aktif = 1 
              ORDER BY d.Nama_Dokter ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute(['id_tempat_praktek' => $id_tempat_praktek]);
    $dokter = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return data sebagai JSON
    echo json_encode($dokter);
    
} catch (PDOException $e) {
    // Log error dan return pesan error
    error_log("Database Error in get_dokter.php: " . $e->getMessage());
    echo json_encode(['error' => 'Terjadi kesalahan saat mengambil data dokter']);
}
?>
