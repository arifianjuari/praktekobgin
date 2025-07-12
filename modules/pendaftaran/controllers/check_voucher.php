<?php
require_once __DIR__ . '/../../../config/koneksi.php';
$conn = getPDOConnection();

// Set header untuk response JSON
header('Content-Type: application/json');

// Aktifkan error logging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Set timezone ke Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');

// Log untuk debugging
error_log("Check Voucher Script Started");
error_log("Current server time: " . date('Y-m-d H:i:s'));

// Periksa apakah ada kode voucher yang dikirim
if (!isset($_POST['voucher_code']) || empty($_POST['voucher_code'])) {
    error_log("Voucher code empty or not set");
    echo json_encode([
        'valid' => false,
        'message' => 'Kode voucher tidak boleh kosong'
    ]);
    exit;
}

try {
    // Ambil dan bersihkan kode voucher
    $voucher_code = trim($_POST['voucher_code']);
    error_log("Checking voucher code: " . $voucher_code);

    // Query untuk debugging - cek voucher tanpa kondisi waktu
    $debug_query = "SELECT * FROM voucher WHERE voucher_code = :voucher_code";
    $debug_stmt = $conn->prepare($debug_query);
    $debug_stmt->bindParam(':voucher_code', $voucher_code, PDO::PARAM_STR);
    $debug_stmt->execute();
    $debug_voucher = $debug_stmt->fetch(PDO::FETCH_ASSOC);

    error_log("Debug voucher data: " . print_r($debug_voucher, true));

    if ($debug_voucher) {
        error_log("Found voucher in database");
        error_log("Voucher status: " . $debug_voucher['status']);
        error_log("Valid from: " . $debug_voucher['valid_awal']);
        error_log("Valid until: " . $debug_voucher['valid_akhir']);

        $now = new DateTime();
        $valid_awal = new DateTime($debug_voucher['valid_awal']);
        $valid_akhir = new DateTime($debug_voucher['valid_akhir']);

        error_log("Current time: " . $now->format('Y-m-d H:i:s'));
        error_log("Valid from: " . $valid_awal->format('Y-m-d H:i:s'));
        error_log("Valid until: " . $valid_akhir->format('Y-m-d H:i:s'));

        // Cek status
        if ($debug_voucher['status'] !== 'aktif') {
            echo json_encode([
                'valid' => false,
                'message' => 'Voucher tidak dapat digunakan (status: ' . $debug_voucher['status'] . ')'
            ]);
            exit;
        }

        // Cek periode validitas
        if ($now < $valid_awal) {
            echo json_encode([
                'valid' => false,
                'message' => 'Voucher belum berlaku'
            ]);
            exit;
        }

        if ($now > $valid_akhir) {
            echo json_encode([
                'valid' => false,
                'message' => 'Voucher sudah kadaluarsa'
            ]);
            exit;
        }

        // Jika sampai di sini, voucher valid
        $message = '';
        switch ($debug_voucher['tipe_voucher']) {
            case 'persentase':
                $message = "Diskon " . number_format($debug_voucher['nilai_voucher'], 0) . "%";
                break;
            case 'nominal':
                $message = "Potongan Rp " . number_format($debug_voucher['nilai_voucher'], 0, ',', '.');
                break;
            case 'produk_gratis':
                $message = "Gratis produk/layanan tertentu";
                break;
            default:
                $message = $debug_voucher['nama_voucher'];
        }

        error_log("Voucher valid: " . $message);

        // Jika mode adalah 'use', update status voucher menjadi terpakai
        if (isset($_POST['mode']) && $_POST['mode'] === 'use') {
            // Cek quota dan terpakai
            $kuota = isset($debug_voucher['kuota']) ? intval($debug_voucher['kuota']) : 1;
            $terpakai = isset($debug_voucher['terpakai']) ? intval($debug_voucher['terpakai']) : 0;

            if ($terpakai >= $kuota) {
                echo json_encode([
                    'valid' => false,
                    'message' => 'Voucher sudah mencapai batas penggunaan (quota habis)'
                ]);
                exit;
            }

            try {
                $conn->beginTransaction();
                $terpakai_baru = $terpakai + 1;
                if ($terpakai_baru >= $kuota) {
                    // Update both terpakai and status
                    $update_query = "UPDATE voucher SET terpakai = :terpakai, status = 'terpakai' WHERE voucher_code = :voucher_code";
                } else {
                    // Only increment terpakai
                    $update_query = "UPDATE voucher SET terpakai = :terpakai WHERE voucher_code = :voucher_code";
                }
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bindParam(':terpakai', $terpakai_baru, PDO::PARAM_INT);
                $update_stmt->bindParam(':voucher_code', $voucher_code, PDO::PARAM_STR);
                $update_stmt->execute();

                $conn->commit();
                error_log("Voucher terpakai incremented to $terpakai_baru");
                if ($terpakai_baru >= $kuota) {
                    error_log("Voucher status updated to 'terpakai' (quota reached)");
                }
            } catch (PDOException $e) {
                $conn->rollBack();
                error_log("Error updating voucher usage/quota: " . $e->getMessage());
                throw $e;
            }
        }

        echo json_encode([
            'valid' => true,
            'nama_voucher' => $debug_voucher['nama_voucher'],
            'message' => $message,
            'tipe_voucher' => $debug_voucher['tipe_voucher'],
            'nilai_voucher' => $debug_voucher['nilai_voucher']
        ]);
    } else {
        error_log("Voucher not found in database");
        echo json_encode([
            'valid' => false,
            'message' => 'Kode voucher tidak ditemukan'
        ]);
    }
} catch (PDOException $e) {
    error_log("Database Error in check_voucher.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    echo json_encode([
        'valid' => false,
        'message' => 'Terjadi kesalahan saat memeriksa voucher: ' . $e->getMessage()
    ]);
}
