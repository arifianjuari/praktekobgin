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
    // Koneksi ke database RSHB
    $db1_host = '103.76.149.29';
    $db1_username = 'web_hasta';
    $db1_password = '@Admin123/';
    $db1_database = 'simsvbaru';

    try {
        $conn_rshb = new PDO(
            "mysql:host=$db1_host;dbname=$db1_database;charset=utf8",
            $db1_username,
            $db1_password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
    } catch (PDOException $e) {
        error_log("RSHB Database Connection Error: " . $e->getMessage());
        throw new Exception("Tidak dapat terhubung ke database RSHB.");
    }

    $nik = isset($_GET['nik']) ? trim($_GET['nik']) : '';
    $response = ['found' => false];

    if (strlen($nik) === 16) {
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
