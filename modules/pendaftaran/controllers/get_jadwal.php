<?php
// Impor konfigurasi database
require_once __DIR__ . '/../../../config/koneksi.php';
$conn = getPDOConnection();

// Set header untuk mengirim respons JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Tangkap parameter dari request
$id_tempat_praktek = isset($_GET['tempat']) ? $_GET['tempat'] : '';
$id_dokter = isset($_GET['dokter']) ? $_GET['dokter'] : '';

// Validasi input
if (empty($id_tempat_praktek) || empty($id_dokter)) {
    echo json_encode(['error' => 'Parameter tempat dan dokter harus diisi']);
    exit;
}

try {
    // Query untuk mendapatkan jadwal berdasarkan tempat praktek dan dokter
    $query = "SELECT jr.*, 
              DATE_FORMAT(jr.Jam_Mulai, '%H:%i') as jam_mulai_format, 
              DATE_FORMAT(jr.Jam_Selesai, '%H:%i') as jam_selesai_format,
              CASE 
                WHEN jr.Hari = 1 THEN 'Senin'
                WHEN jr.Hari = 2 THEN 'Selasa'
                WHEN jr.Hari = 3 THEN 'Rabu'
                WHEN jr.Hari = 4 THEN 'Kamis'
                WHEN jr.Hari = 5 THEN 'Jumat'
                WHEN jr.Hari = 6 THEN 'Sabtu'
                WHEN jr.Hari = 7 THEN 'Minggu'
              END as nama_hari
              FROM jadwal_rutin jr
              WHERE jr.ID_Tempat_Praktek = :id_tempat_praktek 
              AND jr.ID_Dokter = :id_dokter
              AND jr.Status_Aktif = 1
              ORDER BY jr.Hari ASC, jr.Jam_Mulai ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        'id_tempat_praktek' => $id_tempat_praktek,
        'id_dokter' => $id_dokter
    ]);
    
    $jadwal = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format jadwal untuk tampilan dropdown
    $formatted_jadwal = [];
    foreach ($jadwal as $j) {
        $j['label'] = $j['nama_hari'] . ', ' . $j['jam_mulai_format'] . ' - ' . $j['jam_selesai_format'];
        $formatted_jadwal[] = $j;
    }
    
    // Return data sebagai JSON
    echo json_encode($formatted_jadwal);
    
} catch (PDOException $e) {
    // Log error dan return pesan error
    error_log("Database Error in get_jadwal.php: " . $e->getMessage());
    echo json_encode(['error' => 'Terjadi kesalahan saat mengambil data jadwal']);
}
?>
