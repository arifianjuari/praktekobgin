<?php
/**
 * Controller Jadwal - Mengelola tampilan jadwal praktek
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
$page_title = 'Jadwal Praktek';

// Pastikan koneksi database tersedia
ensureDBConnection();

// Filter berdasarkan hari dan jenis layanan
$hari = isset($_GET['hari']) ? $_GET['hari'] : '';
$jenis_layanan_filter = isset($_GET['layanan']) ? $_GET['layanan'] : '';

// Base URL untuk reset filter
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$base_url = $protocol . $_SERVER['HTTP_HOST'];

// Ekstrak root path dengan menghapus bagian /modules/jadwal/controllers dari path
$script_path = dirname($_SERVER['SCRIPT_NAME']);
$modules_pos = strpos($script_path, '/modules');
$base_path = $modules_pos !== false ? substr($script_path, 0, $modules_pos) : $script_path;

// Query untuk mengambil data jadwal rutin
try {
    $query = "
        SELECT 
            jr.*
        FROM 
            jadwal_rutin jr
        WHERE 
            jr.Status_Aktif = 1
    ";
    
    $params = [];
    
    // Tambahkan filter hari jika ada
    if (!empty($hari)) {
        $query .= " AND jr.Hari = :hari";
        $params[':hari'] = $hari;
    }
    
    // Tambahkan filter jenis layanan jika ada
    if (!empty($jenis_layanan_filter)) {
        $query .= " AND jr.Jenis_Layanan = :jenis_layanan";
        $params[':jenis_layanan'] = $jenis_layanan_filter;
    }
    
    // Urutkan berdasarkan hari dan jam mulai
    $query .= " ORDER BY 
        CASE jr.Hari
            WHEN 'Senin' THEN 1
            WHEN 'Selasa' THEN 2
            WHEN 'Rabu' THEN 3
            WHEN 'Kamis' THEN 4
            WHEN 'Jumat' THEN 5
            WHEN 'Sabtu' THEN 6
            WHEN 'Minggu' THEN 7
        END, 
        jr.Jam_Mulai";
    
    $stmt = $conn->prepare($query);
    
    // Bind parameter jika ada
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $jadwal = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ambil daftar unik jenis layanan untuk filter
    $query_layanan = "SELECT DISTINCT Jenis_Layanan FROM jadwal_rutin WHERE Status_Aktif = 1 AND Jenis_Layanan IS NOT NULL ORDER BY Jenis_Layanan";
    $stmt_layanan = $conn->prepare($query_layanan);
    $stmt_layanan->execute();
    $jenis_layanan = [];
    
    while ($row = $stmt_layanan->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($row['Jenis_Layanan'])) {
            $jenis_layanan[] = $row['Jenis_Layanan'];
        }
    }
    
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $jadwal = [];
    $jenis_layanan = [];
}

// Additional CSS untuk styling halaman jadwal
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
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
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
    .table th, .table td {
        vertical-align: middle;
    }
    .table td {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-size: 0.95rem;
    }
    .table thead th {
        font-size: 0.95rem;
    }
    
    /* Responsive table */
    @media (max-width: 767.98px) {
        .table-responsive {
            overflow-x: auto;
        }
        .table {
            min-width: 800px;
        }
    }
";

// Render view dengan output buffering
ob_start();
include_once __DIR__ . '/../views/jadwal_view.php';
$content = ob_get_clean();

// Pastikan variabel yang dibutuhkan oleh layout.php tersedia
$current_page = 'jadwal.php';
$current_path = $base_path . '/jadwal.php';

// Definisikan variabel global untuk template
$GLOBALS['app_root'] = $base_path;

// Tambahkan script untuk memperbaiki navigasi setelah halaman dimuat
$additional_js = "\n// Script untuk memperbaiki navigasi\ndocument.addEventListener('DOMContentLoaded', function() {\n  // Perbaiki semua link di sidebar\n  const sidebarLinks = document.querySelectorAll('.sidebar a');\n  sidebarLinks.forEach(link => {\n    if (link.getAttribute('href') && !link.getAttribute('href').startsWith('http') && !link.getAttribute('href').startsWith('#')) {\n      // Pastikan path relatif dimulai dengan base path yang benar\n      if (!link.getAttribute('href').startsWith('$base_path')) {\n        link.setAttribute('href', '$base_path' + link.getAttribute('href'));\n      }\n    }\n  });\n});\n";

// Include template dengan path absolut dari root project
include_once __DIR__ . '/../../../template/layout.php';
