<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/koneksi.php';

// Cek apakah user sudah login dan memiliki role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    // Mendapatkan hari dalam bahasa Indonesia
    $hari_ini = date('w');
    $nama_hari = '';
    switch ($hari_ini) {
        case 0:
            $nama_hari = 'Minggu';
            break;
        case 1:
            $nama_hari = 'Senin';
            break;
        case 2:
            $nama_hari = 'Selasa';
            break;
        case 3:
            $nama_hari = 'Rabu';
            break;
        case 4:
            $nama_hari = 'Kamis';
            break;
        case 5:
            $nama_hari = 'Jumat';
            break;
        case 6:
            $nama_hari = 'Sabtu';
            break;
    }

    $response = [];

    // Query untuk statistik
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_antrian,
            SUM(CASE WHEN p.Status_Pendaftaran = 'Selesai' THEN 1 ELSE 0 END) as sudah_dilayani,
            SUM(CASE WHEN p.Status_Pendaftaran IN ('Menunggu Konfirmasi', 'Dikonfirmasi') THEN 1 ELSE 0 END) as sedang_menunggu
        FROM pendaftaran p
        JOIN jadwal_rutin jr ON p.ID_Jadwal = jr.ID_Jadwal_Rutin
        WHERE jr.Hari = ?
    ");
    $stmt->execute([$nama_hari]);
    $response['statistik'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // Query untuk antrian yang sedang dilayani
    $stmt = $pdo->prepare("
        SELECT p.*, p.nm_pasien as nama 
        FROM pendaftaran p 
        JOIN jadwal_rutin jr ON p.ID_Jadwal = jr.ID_Jadwal_Rutin
        WHERE p.Status_Pendaftaran = 'Dikonfirmasi' 
        AND jr.Hari = ?
        ORDER BY p.Waktu_Perkiraan DESC 
        LIMIT 1
    ");
    $stmt->execute([$nama_hari]);
    $response['antrian_sekarang'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // Query untuk antrian berikutnya
    $stmt = $pdo->prepare("
        SELECT 
            p.ID_Pendaftaran as id,
            p.ID_Pendaftaran as Nomor_Urut,
            p.nm_pasien,
            p.Status_Pendaftaran,
            p.Waktu_Perkiraan
        FROM 
            pendaftaran p
        JOIN 
            jadwal_rutin jr ON p.ID_Jadwal = jr.ID_Jadwal_Rutin
        WHERE 
            p.Status_Pendaftaran IN ('Menunggu Konfirmasi', 'Dikonfirmasi')
            AND jr.Hari = ?
        ORDER BY 
            jr.Jam_Mulai ASC, p.Waktu_Pendaftaran ASC
        LIMIT 5
    ");
    $stmt->execute([$nama_hari]);
    $response['antrian_berikutnya'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Query untuk pengumuman terkini
    $stmt = $pdo->query("
        SELECT * FROM pengumuman 
        WHERE status_aktif = 1 
        AND (tanggal_berakhir IS NULL OR tanggal_berakhir >= CURDATE())
        AND tanggal_mulai <= CURDATE()
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $response['pengumuman'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Set header JSON
    header('Content-Type: application/json');
    echo json_encode($response);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
