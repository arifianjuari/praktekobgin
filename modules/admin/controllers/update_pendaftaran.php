<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';

// Set header untuk mengembalikan respons JSON
header('Content-Type: application/json');

// Cek role admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Akses ditolak. Anda tidak memiliki izin.'
    ]);
    exit;
}

// Cek apakah request method adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Metode request tidak valid.'
    ]);
    exit;
}

// Validasi data yang diterima
if (!isset($_POST['id_pendaftaran']) || empty($_POST['id_pendaftaran'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID Pendaftaran tidak valid.'
    ]);
    exit;
}

// Ambil data dari form
$id_pendaftaran = $_POST['id_pendaftaran'];
$nm_pasien = isset($_POST['nm_pasien']) ? trim($_POST['nm_pasien']) : '';
$status = isset($_POST['status']) ? $_POST['status'] : '';
$id_dokter = isset($_POST['id_dokter']) ? $_POST['id_dokter'] : '';
$id_jadwal = isset($_POST['id_jadwal']) ? $_POST['id_jadwal'] : '';
$id_tempat = isset($_POST['id_tempat']) ? $_POST['id_tempat'] : '';
$waktu_perkiraan = isset($_POST['waktu_perkiraan']) ? trim($_POST['waktu_perkiraan']) : '';

// Validasi data
if (empty($nm_pasien) || empty($status) || empty($id_dokter) || empty($id_jadwal) || empty($id_tempat)) {
    echo json_encode([
        'success' => false,
        'message' => 'Semua field harus diisi.'
    ]);
    exit;
}

// Validasi status
$valid_statuses = ['Menunggu Konfirmasi', 'Dikonfirmasi', 'Dibatalkan', 'Selesai'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode([
        'success' => false,
        'message' => 'Status pendaftaran tidak valid.'
    ]);
    exit;
}

try {
    // Mulai transaksi
    $conn->beginTransaction();

    // Update data pendaftaran
    $query = "
        UPDATE pendaftaran 
        SET 
            nm_pasien = :nm_pasien,
            Status_Pendaftaran = :status,
            ID_Dokter = :id_dokter,
            ID_Jadwal = :id_jadwal,
            ID_Tempat_Praktek = :id_tempat,
            Waktu_Perkiraan = :waktu_perkiraan
        WHERE 
            ID_Pendaftaran = :id_pendaftaran
    ";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':nm_pasien', $nm_pasien);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':id_dokter', $id_dokter);
    $stmt->bindParam(':id_jadwal', $id_jadwal);
    $stmt->bindParam(':id_tempat', $id_tempat);
    
    // Handle waktu_perkiraan (can be NULL if empty)
    if (empty($waktu_perkiraan)) {
        $stmt->bindValue(':waktu_perkiraan', null, PDO::PARAM_NULL);
    } else {
        // Format waktu_perkiraan to include date
        $waktu_perkiraan_full = date('Y-m-d ') . $waktu_perkiraan . ':00';
        $stmt->bindParam(':waktu_perkiraan', $waktu_perkiraan_full);
    }
    
    $stmt->bindParam(':id_pendaftaran', $id_pendaftaran);

    $stmt->execute();

    // Cek apakah ada baris yang terpengaruh
    if ($stmt->rowCount() === 0) {
        throw new Exception('Tidak ada perubahan data atau data tidak ditemukan.');
    }

    // Commit transaksi
    $conn->commit();

    // Kirim respons sukses
    echo json_encode([
        'success' => true,
        'message' => 'Data pendaftaran berhasil diperbarui.'
    ]);
} catch (Exception $e) {
    // Rollback transaksi jika terjadi error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    // Kirim respons error
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
}
