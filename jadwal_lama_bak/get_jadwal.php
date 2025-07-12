<?php
// File: get_jadwal.php
// Deskripsi: API untuk mendapatkan jadwal praktek dokter

// Matikan pelaporan error untuk output
error_reporting(0);
ini_set('display_errors', 0);

// Aktifkan log error
ini_set('log_errors', 1);
ini_set('error_log', 'error_log.txt');

// Pastikan tidak ada output sebelum header
if (ob_get_level()) ob_end_clean();
ob_start();

// Header dasar
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Access-Control-Allow-Origin: *');

// Fungsi untuk output JSON dan keluar
function output_json($data, $status = 200)
{
    // Bersihkan buffer output
    if (ob_get_level()) ob_end_clean();

    // Set status HTTP
    http_response_code($status);

    // Output JSON
    echo json_encode($data);
    exit;
}

// Log untuk debugging
error_log('Request to get_jadwal.php: ' . print_r($_GET, true));

// Cek koneksi database
try {
    // Cek file database
    if (!file_exists('config/database.php')) {
        error_log('Database config file not found');
        output_json(array('error' => 'Konfigurasi database tidak ditemukan'), 500);
    }

    // Load database config
    require_once 'config/database.php';

    // Cek koneksi
    if (!isset($conn) || !($conn instanceof PDO)) {
        error_log('Database connection not available');
        output_json(array('error' => 'Koneksi database tidak tersedia'), 500);
    }

    // Ambil parameter
    $id_tempat = isset($_GET['tempat']) ? trim($_GET['tempat']) : '';
    $id_dokter = isset($_GET['dokter']) ? trim($_GET['dokter']) : '';

    // Validasi parameter
    if (empty($id_tempat) || empty($id_dokter)) {
        error_log('Missing parameters: tempat=' . $id_tempat . ', dokter=' . $id_dokter);
        output_json(array('error' => 'Parameter tempat dan dokter diperlukan'), 400);
    }

    // Query sederhana
    $query = "
        SELECT 
            jr.ID_Jadwal_Rutin,
            jr.Hari,
            jr.Jam_Mulai,
            jr.Jam_Selesai,
            jr.Kuota_Pasien,
            jr.Jenis_Layanan,
            d.Nama_Dokter,
            tp.Nama_Tempat
        FROM 
            jadwal_rutin jr
        JOIN 
            dokter d ON jr.ID_Dokter = d.ID_Dokter
        JOIN 
            tempat_praktek tp ON jr.ID_Tempat_Praktek = tp.ID_Tempat_Praktek
        WHERE 
            jr.Status_Aktif = 1
            AND jr.ID_Tempat_Praktek = ?
            AND jr.ID_Dokter = ?
        ORDER BY 
            CASE jr.Hari
                WHEN 'Senin' THEN 1
                WHEN 'Selasa' THEN 2
                WHEN 'Rabu' THEN 3
                WHEN 'Kamis' THEN 4
                WHEN 'Jumat' THEN 5
                WHEN 'Sabtu' THEN 6
                WHEN 'Minggu' THEN 7
            END ASC,
            jr.Jam_Mulai ASC
    ";

    // Log query
    error_log('Query: ' . $query);
    error_log('Parameters: tempat=' . $id_tempat . ', dokter=' . $id_dokter);

    // Prepare dan execute
    $stmt = $conn->prepare($query);
    $stmt->execute(array($id_tempat, $id_dokter));

    // Ambil hasil
    $jadwal = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format jam
    $result = array();
    foreach ($jadwal as $j) {
        // Format jam ke format yang lebih sederhana
        $jam_mulai = substr($j['Jam_Mulai'], 0, 5);  // Ambil hanya HH:MM
        $jam_selesai = substr($j['Jam_Selesai'], 0, 5);  // Ambil hanya HH:MM

        $result[] = array(
            'ID_Jadwal_Rutin' => $j['ID_Jadwal_Rutin'],
            'Hari' => $j['Hari'],
            'Jam_Mulai' => $jam_mulai,
            'Jam_Selesai' => $jam_selesai,
            'Kuota_Pasien' => $j['Kuota_Pasien'],
            'Jenis_Layanan' => $j['Jenis_Layanan'],
            'Nama_Dokter' => $j['Nama_Dokter'],
            'Nama_Tempat' => $j['Nama_Tempat']
        );
    }

    // Log hasil
    error_log('Result count: ' . count($result));

    // Output hasil
    output_json($result);
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    output_json(array('error' => 'Error database: ' . $e->getMessage()), 500);
} catch (Exception $e) {
    error_log('General error: ' . $e->getMessage());
    output_json(array('error' => 'Error: ' . $e->getMessage()), 500);
}
