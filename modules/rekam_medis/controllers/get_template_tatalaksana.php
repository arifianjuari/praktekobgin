<?php
// Pastikan tidak ada akses langsung ke file ini
if (!defined('BASE_PATH')) {
    // Jika diakses langsung melalui AJAX, set header dan lanjutkan
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        // Define BASE_PATH untuk AJAX request
        define('BASE_PATH', realpath(dirname(dirname(dirname(__DIR__)))));

        // Load database connection
        require_once BASE_PATH . '/config/database.php';
    } else {
        // Jika bukan AJAX request, tolak akses
        header('HTTP/1.0 403 Forbidden');
        exit('Akses langsung tidak diizinkan');
    }
}

// Load model
require_once dirname(dirname(__FILE__)) . '/models/TemplateTatalaksana.php';

// Inisialisasi model
$templateModel = new TemplateTatalaksana($conn);

// Default response
$response = [
    'status' => 'error',
    'message' => 'Permintaan tidak valid',
    'data' => null
];

// Cek jenis request
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    try {
        switch ($action) {
            case 'get_kategori':
                // Ambil semua kategori
                $kategori = $templateModel->getAllKategori();
                $response = [
                    'status' => 'success',
                    'message' => 'Berhasil mengambil data kategori',
                    'data' => $kategori
                ];
                break;

            case 'get_template_by_kategori':
                // Validasi parameter
                if (!isset($_GET['kategori']) || empty($_GET['kategori'])) {
                    $response['message'] = 'Parameter kategori diperlukan';
                    break;
                }

                // Ambil template berdasarkan kategori
                $templates = $templateModel->getTemplateByKategori($_GET['kategori']);
                $response = [
                    'status' => 'success',
                    'message' => 'Berhasil mengambil data template',
                    'data' => $templates
                ];
                break;

            case 'get_template_by_id':
                // Validasi parameter
                if (!isset($_GET['id']) || empty($_GET['id'])) {
                    $response['message'] = 'Parameter ID diperlukan';
                    break;
                }

                // Ambil template berdasarkan ID
                $template = $templateModel->getTemplateById($_GET['id']);
                if ($template) {
                    $response = [
                        'status' => 'success',
                        'message' => 'Berhasil mengambil data template',
                        'data' => $template
                    ];
                } else {
                    $response['message'] = 'Template tidak ditemukan';
                }
                break;

            case 'get_all_template':
                // Ambil semua template
                $templates = $templateModel->getAllTemplate();
                $response = [
                    'status' => 'success',
                    'message' => 'Berhasil mengambil semua data template',
                    'data' => $templates
                ];
                break;

            case 'search_template':
                // Validasi parameter
                if (!isset($_GET['keyword'])) {
                    $response['message'] = 'Parameter keyword diperlukan';
                    break;
                }

                // Cari template berdasarkan keyword
                $templates = $templateModel->searchTemplate($_GET['keyword']);
                $response = [
                    'status' => 'success',
                    'message' => 'Berhasil mencari data template',
                    'data' => $templates
                ];
                break;

            default:
                $response['message'] = 'Action tidak dikenali';
                break;
        }
    } catch (Exception $e) {
        $response['message'] = 'Terjadi kesalahan: ' . $e->getMessage();
    }
}

// Kirim response dalam format JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;
