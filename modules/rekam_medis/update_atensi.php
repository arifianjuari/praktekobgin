<?php
// Aktifkan error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Deteksi apakah ini adalah request AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Set header untuk CORS dan JSON response jika ini adalah request AJAX
if ($isAjax) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Content-Type: application/json; charset=UTF-8');
}

// Log request untuk debugging
$log_file = __DIR__ . '/update_atensi_log.txt';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Request received\n", FILE_APPEND);
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Method: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Is AJAX: " . ($isAjax ? 'Yes' : 'No') . "\n", FILE_APPEND);

// Impor koneksi database
require_once __DIR__ . '/../../config/database.php';

// Terima parameter dari GET atau POST
$no_rawat = $_REQUEST['no_rawat'] ?? '';

file_put_contents($log_file, date('Y-m-d H:i:s') . " - No Rawat: $no_rawat\n", FILE_APPEND);

if (empty($no_rawat)) {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error: No rawat tidak valid\n", FILE_APPEND);

    if ($isAjax) {
        echo json_encode([
            'success' => false,
            'message' => 'No rawat tidak valid'
        ]);
    } else {
        // Redirect kembali ke halaman daftar atensi dengan pesan error
        header('Location: ../../index.php?module=rekam_medis&action=daftar_atensi&error=no_rawat_invalid');
    }
    exit;
}

try {
    // Jalankan query update
    $query = "UPDATE penilaian_medis_ralan_kandungan SET atensi = NULL, tanggal_kontrol = NULL WHERE no_rawat = :no_rawat";
    $stmt = $conn->prepare($query);
    $stmt->execute(['no_rawat' => $no_rawat]);

    // Log hasil query
    $rowCount = $stmt->rowCount();
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Query executed. Rows affected: $rowCount\n", FILE_APPEND);

    if ($isAjax) {
        echo json_encode([
            'success' => true,
            'message' => 'Status atensi berhasil diperbarui',
            'rows_affected' => $rowCount
        ]);
    } else {
        // Redirect kembali ke halaman daftar atensi dengan pesan sukses
        header('Location: ../../index.php?module=rekam_medis&action=daftar_atensi&success=update_success');
    }
} catch (Exception $e) {
    // Log error
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);

    if ($isAjax) {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal memperbarui status: ' . $e->getMessage()
        ]);
    } else {
        // Redirect kembali ke halaman daftar atensi dengan pesan error
        header('Location: ../../index.php?module=rekam_medis&action=daftar_atensi&error=' . urlencode($e->getMessage()));
    }
}
