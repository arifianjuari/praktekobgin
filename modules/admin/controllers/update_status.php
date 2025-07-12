<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id_pendaftaran = $_POST['id_pendaftaran'];
        $status = $_POST['status'];

        $stmt = $conn->prepare("UPDATE pendaftaran SET Status_Pendaftaran = ? WHERE ID_Pendaftaran = ?");
        $stmt->execute([$status, $id_pendaftaran]);

        // Log aktivitas jika tabel activity_logs ada
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            $aktivitas = "Mengubah status pendaftaran $id_pendaftaran menjadi $status";

            try {
                $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$user_id, $aktivitas]);
            } catch (PDOException $e) {
                // Jika tabel activity_logs tidak ada, abaikan error
                error_log("Error logging activity: " . $e->getMessage());
            }
        }

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
