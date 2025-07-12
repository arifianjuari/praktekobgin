<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek role admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Dapatkan root directory project
$root_dir = dirname(dirname(dirname(__DIR__)));

// Include database configuration
require_once $root_dir . '/config/database.php';

// Validasi input
if (!isset($_POST['id_pendaftaran']) || !isset($_POST['waktu_perkiraan'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$id_pendaftaran = $_POST['id_pendaftaran'];
$waktu_perkiraan = $_POST['waktu_perkiraan'];

// Validasi format waktu
if (!empty($waktu_perkiraan) && !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $waktu_perkiraan)) {
    echo json_encode(['success' => false, 'message' => 'Invalid time format']);
    exit;
}

try {
    // Update waktu perkiraan
    $query = "UPDATE pendaftaran SET Waktu_Perkiraan = :waktu_perkiraan WHERE ID_Pendaftaran = :id_pendaftaran";
    $stmt = $conn->prepare($query);

    // Jika waktu kosong, set NULL
    if (empty($waktu_perkiraan)) {
        $stmt->bindValue(':waktu_perkiraan', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':waktu_perkiraan', $waktu_perkiraan);
    }

    $stmt->bindValue(':id_pendaftaran', $id_pendaftaran);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update time']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
