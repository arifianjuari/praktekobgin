<?php
// router.php - Entry point utama aplikasi
session_start();

function not_found() {
    http_response_code(404);
    echo '<h1>404 Not Found</h1><p>Module tidak ditemukan.</p>';
    exit;
}

$module = $_GET['module'] ?? 'login';
$action = $_GET['action'] ?? '';

// Mapping module ke controller file dan class
$controllerMap = [
    'login' => ['file' => 'controllers/LoginController.php', 'class' => 'LoginController'],
    'dashboard' => ['file' => 'modules/admin/controllers/DashboardController.php', 'class' => 'DashboardController'],
    'rekam_medis' => ['file' => 'modules/rekam_medis/controllers/RekamMedisController.php', 'class' => 'RekamMedisController'],
    'pendaftaran' => ['file' => 'modules/pendaftaran/controllers/PendaftaranController.php', 'class' => 'PendaftaranController'],
    'jadwal' => ['file' => 'modules/pendaftaran/controllers/JadwalController.php', 'class' => 'JadwalController'],
    // Tambahkan mapping lain sesuai kebutuhan modul
];

if (!isset($controllerMap[$module])) {
    not_found();
}

$controllerFile = __DIR__ . '/' . $controllerMap[$module]['file'];
$controllerClass = $controllerMap[$module]['class'];

if (!file_exists($controllerFile)) {
    not_found();
}

require_once $controllerFile;

// Inisialisasi koneksi jika diperlukan oleh controller
$conn = null;
if (file_exists(__DIR__ . '/config/database.php')) {
    require_once __DIR__ . '/config/database.php';
    // $conn harus tersedia dari database.php
}

$controller = class_exists($controllerClass)
    ? new $controllerClass($conn)
    : null;
if (!$controller) {
    not_found();
}

// Routing ke method sesuai action
if (method_exists($controller, 'handle')) {
    $controller->handle();
} elseif ($action && method_exists($controller, $action)) {
    $controller->{$action}();
} elseif (method_exists($controller, 'index')) {
    $controller->index();
} else {
    not_found();
}
