<?php
// Pastikan tidak ada output sebelum header
error_reporting(E_ALL);
ini_set('display_errors', 0); // Matikan display error agar tidak mengganggu output JSON

// Fungsi untuk mengembalikan respons JSON dan keluar
function sendJsonResponse($data)
{
    // Pastikan tidak ada output sebelumnya
    if (ob_get_length()) ob_clean();

    // Set header CORS
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST');
    header('Access-Control-Allow-Headers: Content-Type');

    // Set header Content-Type
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Tangkap semua output yang mungkin terjadi
ob_start();

// Log untuk debugging
error_log("check_patient_rshb.php called with NIK: " . (isset($_GET['nik']) ? $_GET['nik'] : 'not set'));

try {
    // Gunakan koneksi database dari config.php
    require_once __DIR__ . '/../../../config.php';
    if (!isset($conn_db1) || !($conn_db1 instanceof PDO)) {
        error_log("Koneksi database RSHB (conn_db1) tidak tersedia!");
        throw new Exception("Koneksi database RSHB tidak tersedia.");
    }
    $conn_rshb = $conn_db1;

    $nik = isset($_GET['nik']) ? trim($_GET['nik']) : '';
    $response = ['found' => false];

    if (strlen($nik) === 16) {
        error_log("Mencari pasien berdasarkan NIK: $nik");
        try {
            $query = "SELECT no_ktp, nm_pasien, tgl_lahir, jk, no_tlp, alamat, kd_kec, pekerjaan, pekerjaanpj 
                    FROM pasien 
                    WHERE no_ktp = ?";
            $stmt = $conn_rshb->prepare($query);
            $stmt->execute([$nik]);
            $patient = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($patient) {
                // Format tanggal lahir ke format Y-m-d untuk input date HTML
                if (isset($patient['tgl_lahir'])) {
                    $date = new DateTime($patient['tgl_lahir']);
                    $patient['tgl_lahir'] = $date->format('Y-m-d');
                }

                // Pastikan semua field yang dibutuhkan tersedia
                if (!isset($patient['pekerjaan'])) {
                    $patient['pekerjaan'] = '';
                }

                // Konversi kd_kec ke string jika perlu
                if (isset($patient['kd_kec'])) {
                    $patient['kd_kec'] = (string)$patient['kd_kec'];
                }

                $response = [
                    'found' => true,
                    'patient' => $patient
                ];
            } else {
                error_log("Pasien dengan NIK $nik tidak ditemukan di database RSHB.");
            }
        } catch (PDOException $e) {
            $response = [
                'found' => false,
                'error' => 'Terjadi kesalahan database: ' . $e->getMessage()
            ];
            error_log("Database Error in check_patient_rshb.php: " . $e->getMessage());
        }
    }

    // Bersihkan output buffer sebelum mengirim respons JSON
    ob_end_clean();
    sendJsonResponse($response);
} catch (Throwable $e) {
    // Tangkap semua error dan exception
    error_log("Fatal Error in check_patient_rshb.php: " . $e->getMessage());

    // Bersihkan output buffer sebelum mengirim respons JSON
    ob_end_clean();
    sendJsonResponse([
        'found' => false,
        'error' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
    ]);
}
