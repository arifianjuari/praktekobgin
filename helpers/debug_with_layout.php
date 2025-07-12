<?php
session_start();
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../modules/rekam_medis/controllers/RekamMedisController.php';

// Simulasi login
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

// Inisialisasi controller
$rekamMedisController = new RekamMedisController($conn);

// Set page title
$page_title = "Debug: Data Pasien";

// Panggil fungsi dataPasien
ob_start();
$rekamMedisController->dataPasien();
$content = ob_get_clean();

// Include the layout template
include __DIR__ . '/../template/layout.php';
