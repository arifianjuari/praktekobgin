<?php
session_start();
require_once '../config/database.php';

// Cek role admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die(json_encode(['success' => false, 'message' => 'Akses ditolak']));
}

// Cek apakah ada ID yang dikirim
if (!isset($_POST['id_pendaftaran'])) {
    die(json_encode(['success' => false, 'message' => 'ID Pendaftaran tidak ditemukan']));
}

$id_pendaftaran = $_POST['id_pendaftaran'];

try {
    // Mulai transaksi
    $conn->beginTransaction();

    // Hapus data pendaftaran
    $query = "DELETE FROM pendaftaran WHERE ID_Pendaftaran = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id_pendaftaran);
    $stmt->execute();

    // Commit transaksi
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Data pendaftaran berhasil dihapus']);
} catch (PDOException $e) {
    // Rollback jika terjadi error
    $conn->rollBack();
    error_log("Database Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Gagal menghapus data pendaftaran']);
}
