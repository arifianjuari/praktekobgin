<?php
// Disable display errors to prevent HTML error messages in JSON response
error_reporting(0);
ini_set('display_errors', 0);

// Buffer output to catch any unexpected output
ob_start();

try {
    require_once '../config/config.php';
    require_once '../config/database.php';
    require_once '../config/auth.php';

    // Cek apakah user adalah admin
    if (!isAdmin()) {
        throw new Exception('Unauthorized access');
    }

// Fungsi untuk format harga
function formatRupiah($angka)
{
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Fungsi untuk format tanggal
function formatDate($date)
{
    if (empty($date)) return '-';
    return date('d-m-Y', strtotime($date));
}

    // Pastikan request adalah POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Pastikan semua parameter yang diperlukan ada
    if (!isset($_POST['id']) || !isset($_POST['field']) || !isset($_POST['value'])) {
        throw new Exception('Missing required parameters');
    }

$id = $_POST['id'];
$field = $_POST['field'];
$value = $_POST['value'];

// Daftar field yang diperbolehkan untuk diupdate
$allowed_fields = [
    'nama_obat',
    'nama_generik',
    'bentuk_sediaan',
    'dosis',
    'kategori',
    'catatan_obat',
    'harga',
    'farmasi',
    'ed',
    'status_aktif'
];

    // Cek apakah field yang akan diupdate diperbolehkan
    if (!in_array($field, $allowed_fields)) {
        throw new Exception('Invalid field');
    }

// Log the request for debugging
$log_data = [
    'id' => $id,
    'field' => $field,
    'value' => $value
];
file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . ' - Request: ' . json_encode($log_data) . PHP_EOL, FILE_APPEND);

    // Validate connection
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    // Sanitize the field name to prevent SQL injection
    if (!preg_match('/^[a-zA-Z_]+$/', $field)) {
        throw new Exception('Invalid field name format');
    }
    
    // Khusus untuk field tanggal, jika kosong set NULL
    if ($field === 'ed' && empty($value)) {
        $stmt = $conn->prepare("UPDATE formularium SET `$field` = NULL WHERE id_obat = :id");
    } else {
        $stmt = $conn->prepare("UPDATE formularium SET `$field` = :value WHERE id_obat = :id");
        $stmt->bindParam(':value', $value);
    }
    
    $stmt->bindParam(':id', $id);
    $result = $stmt->execute();
    
    if (!$result) {
        throw new Exception('Query execution failed: ' . implode(' ', $stmt->errorInfo()));
    }
    
    // Format nilai untuk ditampilkan kembali ke client
    $formatted = $value;
    if ($field === 'harga') {
        $formatted = formatRupiah($value);
    } elseif ($field === 'ed') {
        $formatted = !empty($value) ? formatDate($value) : '-';
    }
    
    $response = [
        'success' => true, 
        'message' => 'Data berhasil diperbarui',
        'formatted' => $formatted,
        'field' => $field,
        'value' => $value
    ];
    
    // Log the response
    file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . ' - Response: ' . json_encode($response) . PHP_EOL, FILE_APPEND);
    
    // Clear any buffered output
    ob_clean();
    
    // Set JSON header
    header('Content-Type: application/json');
    
    // Output clean JSON
    echo json_encode($response);
    exit;
    
} catch (PDOException $e) {
    $error_msg = 'Database error: ' . $e->getMessage();
    file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . ' - Error: ' . $error_msg . PHP_EOL, FILE_APPEND);
    
    // Clear any buffered output
    ob_clean();
    
    // Set JSON header
    header('Content-Type: application/json');
    
    // Output error as JSON
    echo json_encode(['success' => false, 'message' => $error_msg]);
    exit;
} catch (Exception $e) {
    $error_msg = 'Error: ' . $e->getMessage();
    file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . ' - Error: ' . $error_msg . PHP_EOL, FILE_APPEND);
    
    // Clear any buffered output
    ob_clean();
    
    // Set JSON header
    header('Content-Type: application/json');
    
    // Output error as JSON
    echo json_encode(['success' => false, 'message' => $error_msg]);
    exit;
}
