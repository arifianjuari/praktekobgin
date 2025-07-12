<?php
// Pastikan yang mengakses adalah request AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Definisikan base path di sini agar tidak ada error
define('BASE_PATH', dirname(dirname(dirname(__DIR__))));

// Set header sebagai JSON
header('Content-Type: application/json');

// Fungsi untuk mengembalikan respons error
function returnError($message)
{
    echo json_encode([
        'status' => 'error',
        'message' => $message
    ]);
    exit;
}

// Validasi parameter no_rkm_medis
if (!isset($_GET['no_rkm_medis']) || empty($_GET['no_rkm_medis'])) {
    returnError('Parameter no_rkm_medis tidak ditemukan atau kosong');
}

$no_rkm_medis = $_GET['no_rkm_medis'];

// Koneksi ke database
try {
    require_once __DIR__ . '/../../../../config/database.php';
$conn = new mysqli($db2_host, $db2_username, $db2_password, $db2_database);

    if ($conn->connect_error) {
        returnError('Koneksi database gagal: ' . $conn->connect_error);
    }
} catch (Exception $e) {
    returnError('Error saat membuat koneksi: ' . $e->getMessage());
}

// Log untuk debugging
error_log("AJAX Request received for no_rkm_medis: $no_rkm_medis");

try {
    // Prepared statement untuk menghindari SQL injection
    $sql = "SELECT * FROM status_obstetri WHERE no_rkm_medis = ? ORDER BY updated_at DESC";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        returnError('Error saat menyiapkan query: ' . $conn->error);
    }

    $stmt->bind_param("s", $no_rkm_medis);
    $stmt->execute();

    $result = $stmt->get_result();
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $stmt->close();
    $conn->close();

    // Log untuk debugging
    error_log("Data found: " . count($data) . " rows for no_rkm_medis: $no_rkm_medis");

    // Kirim respons sukses
    echo json_encode([
        'status' => 'success',
        'message' => 'Data berhasil diambil',
        'data' => $data
    ]);
} catch (Exception $e) {
    returnError('Error saat mengambil data: ' . $e->getMessage());
}
