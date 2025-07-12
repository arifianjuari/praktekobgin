<?php
// Aktifkan error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set header untuk CORS dan JSON response
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=UTF-8');

// Log request untuk debugging
file_put_contents(__DIR__ . '/debug_log.txt', date('Y-m-d H:i:s') . " - Request received\n", FILE_APPEND);

require_once __DIR__ . '/../../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Log data POST
        file_put_contents(__DIR__ . '/debug_log.txt', date('Y-m-d H:i:s') . " - POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);

        $no_rawat = $_POST['no_rawat'] ?? '';

        if (empty($no_rawat)) {
            throw new Exception('No rawat tidak valid');
        }

        // Log query yang akan dijalankan
        file_put_contents(__DIR__ . '/debug_log.txt', date('Y-m-d H:i:s') . " - Running query for no_rawat: $no_rawat\n", FILE_APPEND);

        $query = "UPDATE penilaian_medis_ralan_kandungan SET atensi = NULL WHERE no_rawat = :no_rawat";
        $stmt = $conn->prepare($query);
        $stmt->execute(['no_rawat' => $no_rawat]);

        // Log hasil query
        $rowCount = $stmt->rowCount();
        file_put_contents(__DIR__ . '/debug_log.txt', date('Y-m-d H:i:s') . " - Query executed. Rows affected: $rowCount\n", FILE_APPEND);

        echo json_encode([
            'success' => true,
            'message' => 'Status atensi berhasil diperbarui',
            'rows_affected' => $rowCount
        ]);
        exit;
    } catch (Exception $e) {
        // Log error
        file_put_contents(__DIR__ . '/debug_log.txt', date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);

        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Gagal memperbarui status: ' . $e->getMessage()
        ]);
        exit;
    }
} else {
    // Log method yang tidak diizinkan
    file_put_contents(__DIR__ . '/debug_log.txt', date('Y-m-d H:i:s') . " - Invalid method: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);

    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method tidak diizinkan'
    ]);
    exit;
}
