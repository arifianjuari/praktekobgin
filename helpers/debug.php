<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../modules/rekam_medis/controllers/RekamMedisController.php';

// Inisialisasi controller
$rekamMedisController = new RekamMedisController($conn);

// Simulasi halaman data_pasien
echo "<h1>Debug: Data Pasien</h1>";

// Panggil fungsi dataPasien
ob_start();
$rekamMedisController->dataPasien();
$content = ob_get_clean();

// Tampilkan konten
echo $content;
