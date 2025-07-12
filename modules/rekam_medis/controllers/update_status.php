<?php
require_once '../../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_POST['id_pendaftaran']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$id_pendaftaran = $_POST['id_pendaftaran'];
$status = $_POST['status'];

try {
    $current_datetime = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("UPDATE pendaftaran SET Status_Pendaftaran = ?, updatedAt = ? WHERE ID_Pendaftaran = ?");
    $stmt->execute([$status, $current_datetime, $id_pendaftaran]);

    if ($stmt->rowCount() > 0) {
        // Jika ada parameter redirect, alihkan ke URL tersebut
        if (isset($_POST['redirect']) && !empty($_POST['redirect'])) {
            header('Location: ' . $_POST['redirect']);
            exit;
        } else {
            echo json_encode(['success' => true, 'message' => 'Status berhasil diupdate']);
        }
    } else {
        if (isset($_POST['redirect']) && !empty($_POST['redirect'])) {
            // Tambahkan parameter error ke URL redirect
            $redirect_url = $_POST['redirect'] . (strpos($_POST['redirect'], '?') !== false ? '&' : '?') . 'error=1&message=' . urlencode('Tidak ada data yang diupdate');
            header('Location: ' . $redirect_url);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Tidak ada data yang diupdate']);
        }
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan database']);
}
