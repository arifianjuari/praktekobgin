<?php
/**
 * Controller Antrian - Mengelola tampilan daftar antrian pasien
 * 
 * File ini mengikuti arsitektur MVC dengan memisahkan logika dan tampilan
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load dependencies
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';

// Set page title
$page_title = 'Daftar Antrian Pasien';

// Cek apakah user sudah login (opsional)
$is_logged_in = isset($_SESSION['user_id']);

// Filter tanggal
$id_tempat_praktek = isset($_GET['tempat']) ? $_GET['tempat'] : '';
$id_dokter = isset($_GET['dokter']) ? $_GET['dokter'] : '';
$hari = isset($_GET['hari']) ? $_GET['hari'] : '';

// Pastikan koneksi database tersedia
ensureDBConnection();

// Ambil data tempat praktek
try {
    $query_tempat = "SELECT ID_Tempat_Praktek, Nama_Tempat FROM tempat_praktek WHERE Status_Aktif = 1";
    $stmt_tempat = $conn->prepare($query_tempat);
    $stmt_tempat->execute();
    $tempat_praktek = $stmt_tempat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $tempat_praktek = [];
}

// Ambil data dokter
try {
    $query_dokter = "SELECT ID_Dokter, Nama_Dokter FROM dokter WHERE Status_Aktif = 1";
    $stmt_dokter = $conn->prepare($query_dokter);
    $stmt_dokter->execute();
    $dokter = $stmt_dokter->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $dokter = [];
}

// Ambil data antrian
$antrian = [];
$error_message = '';
$antrian_by_day_place = [];


try {
    $query = "
        SELECT 
            p.ID_Pendaftaran,
            p.nm_pasien,
            p.Status_Pendaftaran,
            p.Waktu_Perkiraan,
            jr.Hari,
            jr.Jam_Mulai,
            jr.Jam_Selesai,
            ml.nama_layanan AS nama_layanan,
            tp.Nama_Tempat,
            d.Nama_Dokter,
            p.Waktu_Pendaftaran,
            p.updatedAt
        FROM 
            pendaftaran p
        JOIN 
            jadwal_rutin jr ON p.ID_Jadwal = jr.ID_Jadwal_Rutin
        JOIN 
            menu_layanan ml ON jr.ID_Layanan = ml.id_layanan
        JOIN 
            tempat_praktek tp ON p.ID_Tempat_Praktek = tp.ID_Tempat_Praktek
        JOIN 
            dokter d ON p.ID_Dokter = d.ID_Dokter
        WHERE (p.Status_Pendaftaran NOT IN ('Dibatalkan', 'Selesai')
              OR (p.Status_Pendaftaran = 'Selesai' AND DATE(p.updatedAt) = CURRENT_DATE()))
    ";

    $params = [];

    if (!empty($_GET['hari'])) {
        $query .= " AND jr.Hari = :hari";
        $params[':hari'] = $_GET['hari'];
    }

    if (!empty($_GET['tempat'])) {
        $query .= " AND p.ID_Tempat_Praktek = :tempat";
        $params[':tempat'] = $_GET['tempat'];
    }

    if (!empty($_GET['dokter'])) {
        $query .= " AND p.ID_Dokter = :dokter";
        $params[':dokter'] = $_GET['dokter'];
    }

    $query .= " ORDER BY 
        CASE jr.Hari
            WHEN 'Senin' THEN 1
            WHEN 'Selasa' THEN 2
            WHEN 'Rabu' THEN 3
            WHEN 'Kamis' THEN 4
            WHEN 'Jumat' THEN 5
            WHEN 'Sabtu' THEN 6
            WHEN 'Minggu' THEN 7
        END ASC,
        jr.Jam_Mulai ASC,
        p.Waktu_Perkiraan ASC";  // Changed from Waktu_Pendaftaran to Waktu_Perkiraan

    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $antrian = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format waktu dan kelompokkan antrian berdasarkan hari, tempat, dan dokter
    $antrian_by_day_place = [];
    foreach ($antrian as &$a) {
        $a['Jam_Mulai_Format'] = date('H:i', strtotime($a['Jam_Mulai']));
        $a['Jam_Selesai_Format'] = date('H:i', strtotime($a['Jam_Selesai']));
        $a['Waktu_Daftar_Format'] = date('d/m/Y H:i', strtotime($a['Waktu_Pendaftaran']));
        $a['Waktu_Perkiraan_Format'] = !empty($a['Waktu_Perkiraan']) ? date('H:i', strtotime($a['Waktu_Perkiraan'])) : '-';

        $key = $a['Hari'] . '_' . $a['Nama_Tempat'] . '_' . $a['Nama_Dokter'] . '_' . $a['Jam_Mulai_Format'] . '-' . $a['Jam_Selesai_Format'];
        if (!isset($antrian_by_day_place[$key])) {
            $antrian_by_day_place[$key] = [
                'hari' => $a['Hari'],
                'tempat' => $a['Nama_Tempat'],
                'dokter' => $a['Nama_Dokter'],
                'jam_mulai' => $a['Jam_Mulai_Format'],
                'jam_selesai' => $a['Jam_Selesai_Format'],
                'antrian' => []
            ];
        }
        $antrian_by_day_place[$key]['antrian'][] = $a;
    }
    
    // Sort each group's antrian by Waktu_Perkiraan
    foreach ($antrian_by_day_place as &$group) {
        // Sort the antrian array by Waktu_Perkiraan
        usort($group['antrian'], function($a, $b) {
            if (empty($a['Waktu_Perkiraan']) && empty($b['Waktu_Perkiraan'])) {
                return 0;
            } elseif (empty($a['Waktu_Perkiraan'])) {
                return 1;
            } elseif (empty($b['Waktu_Perkiraan'])) {
                return -1;
            }
            return strtotime($a['Waktu_Perkiraan']) - strtotime($b['Waktu_Perkiraan']);
        });
    }
    unset($group); // Unset the reference to avoid issues
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $antrian = [];
}

// Persiapkan data untuk view
$view_data = [
    'page_title' => $page_title,
    'is_logged_in' => $is_logged_in,
    'tempat_praktek' => $tempat_praktek,
    'dokter' => $dokter,
    'antrian' => $antrian,
    'antrian_by_day_place' => $antrian_by_day_place,
    'error_message' => $error_message,
    'id_tempat_praktek' => $id_tempat_praktek,
    'id_dokter' => $id_dokter,
    'hari' => $hari
];

// Mulai output buffering untuk menangkap konten view
ob_start();

// Include file view
include_once __DIR__ . '/../views/daftar_antrian.php';

// Ambil konten yang sudah di-render
$content = ob_get_clean();

// Additional CSS
$additional_css = "
    .card {
        border-radius: 15px;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
    .card-header {
        background-color: #0d6efd;
    }
    .border-bottom {
        border-bottom: 2px solid #dee2e6 !important;
    }
    .border-primary {
        border-color: #0d6efd !important;
    }
    .rounded-4 {
        border-radius: 0.75rem !important;
    }
    .rounded-top-4 {
        border-top-left-radius: 0.75rem !important;
        border-top-right-radius: 0.75rem !important;
    }
    .table > :not(caption) > * > * {
        padding: 0.5rem 0.75rem;
    }
    .badge {
        font-weight: 500;
        padding: 0.35em 0.65em;
        font-size: 0.85em;
    }
    .rounded-pill {
        border-radius: 50rem !important;
    }

    /* Pengaturan Tabel */
    .table-responsive {
        overflow-x: auto;
        white-space: nowrap;
    }
    .table th, .table td {
        vertical-align: middle;
    }
    /* Lebar kolom spesifik */
    .table th.no-column {
        width: 80px;
    }
    .table th.pasien-column {
        width: 200px;
    }
    .table th.waktu-perkiraan-column {
        width: 150px;
    }
    .table th.waktu-column {
        width: 180px;
    }
    .table th.status-column {
        width: 150px;
    }
    /* Mencegah wrapping teks */
    .table td {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-size: 0.95rem;
    }
    .table td > div {
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .table td .small {
        font-size: 0.85rem;
    }
    .table thead th {
        font-size: 0.95rem;
    }
    .table td .fw-bold {
        font-size: 0.95rem;
    }
    .table td .fs-5 {
        font-size: 1rem !important;
    }

    /* Floating WhatsApp Icon */
    .floating-whatsapp {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 1000;
    }
    .floating-whatsapp a {
        display: block;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background-color: #25D366;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 30px;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    .floating-whatsapp a:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
    }
    .floating-whatsapp .tooltip-text {
        position: absolute;
        right: 70px;
        background-color: #333;
        color: white;
        padding: 5px 10px;
        border-radius: 5px;
        font-size: 14px;
        visibility: hidden;
        opacity: 0;
        transition: all 0.3s ease;
        white-space: nowrap;
    }
    .floating-whatsapp:hover .tooltip-text {
        visibility: visible;
        opacity: 1;
    }

    /* Warna Teal untuk header */
    .text-teal {
        color: #20B2AA !important;
    }
    .bg-teal {
        background-color: #20B2AA !important;
        color: white;
    }
    .text-pink {
        color: #FF69B4 !important;
    }
    .text-muted {
        color: #6c757d !important;
    }
";

// Pastikan variabel yang dibutuhkan oleh sidebar.php tersedia
$current_page = basename($_SERVER['PHP_SELF']);
$current_path = $_SERVER['PHP_SELF'];

// Include template dengan path absolut dari root project
include_once __DIR__ . '/../../../template/layout.php';
?>