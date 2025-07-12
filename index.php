<?php
// Define base URL and root path
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$basePath = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
define('BASE_URL', $protocol . $host . $basePath);
define('ROOT_PATH', __DIR__);

// Debug points - Jangan hapus komentar ini
// echo "<!-- Debug Point 1: Awal Eksekusi -->";

// Aktifkan log error ke file
ini_set('log_errors', 1);

// Jika akses root (tidak ada module), langsung tampilkan form pendaftaran pasien
if (!isset($_GET['module'])) {
    require_once __DIR__ . '/modules/pendaftaran/views/form_pendaftaran_pasien.php';
    exit;
}

ini_set('error_log', __DIR__ . '/error_log.txt');
error_log("=== Mulai eksekusi index.php pada " . date('Y-m-d H:i:s') . " ===\nURI: " . ($_SERVER['REQUEST_URI'] ?? 'unknown'));

// Deteksi versi PHP dan sesuaikan error reporting
if (PHP_VERSION_ID >= 80000) {
    error_log("Detected PHP version 8.x: " . PHP_VERSION);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
} else if (PHP_VERSION_ID >= 70000) {
    error_log("Detected PHP version 7.x: " . PHP_VERSION);
    error_reporting(E_ALL & ~E_NOTICE);
} else {
    error_log("Detected PHP version: " . PHP_VERSION);
    error_reporting(E_ALL);
}

// Force display errors (dapat dioverride oleh setting server)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Debug mode - set true untuk menampilkan error detail di browser
define('DEBUG', true);

// Include error handler
try {
    if (file_exists(__DIR__ . '/error_handler.php')) {
        require_once __DIR__ . '/error_handler.php';
        error_log("Error handler loaded successfully");
    } else {
        error_log("WARNING: error_handler.php tidak ditemukan!");
    }
} catch (Exception $e) {
    error_log("ERROR loading error_handler.php: " . $e->getMessage());
}

// Define base path
define('BASE_PATH', __DIR__);

// Start session if not already started - kompatibel dengan berbagai versi PHP
if (function_exists('session_status')) {
    // PHP 5.4.0 atau lebih baru
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
} else {
    // PHP versi lama
    if (!headers_sent()) {
        @session_start();
    }
}

// Include configuration files in correct order
require_once 'config/config.php';
require_once 'config/database.php';

// Include root config.php for backward compatibility
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

// Log request information
error_log("Request URI: " . $_SERVER['REQUEST_URI']);
error_log("Query String: " . ($_SERVER['QUERY_STRING'] ?? ''));

// Cek koneksi database
if (!isset($conn) || !($conn instanceof PDO)) {
    error_log("Database connection not available in index.php");
    die("Koneksi database tidak tersedia. Silakan hubungi administrator.");
}

// Load controller
require_once 'modules/rekam_medis/controllers/RekamMedisController.php';

// echo "<!-- Debug Point 2: Sebelum inisialisasi controller -->";

try {
    // Verifikasi koneksi database
    if (!isset($conn) || !($conn instanceof PDO)) {
        error_log("CRITICAL: Database connection not available or invalid in index.php");
        throw new Exception("Koneksi database tidak tersedia. Silakan hubungi administrator.");
    }
    error_log("Database connection verified");
    
    // Inisialisasi controller dengan koneksi database
    if (!class_exists('RekamMedisController')) {
        error_log("CRITICAL: RekamMedisController class does not exist");
        throw new Exception("Class controller tidak ditemukan");
    }
    
    $rekamMedisController = new RekamMedisController($conn);
    error_log("Controller initialized successfully");

    // Ambil modul dari parameter GET
    $module = isset($_GET['module']) ? $_GET['module'] : '';
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    // Log routing information
    error_log("Module: " . $module);
    error_log("Action: " . $action);

    // Cek apakah user sudah login
    if (!isset($_SESSION['user_id'])) {
        // Jika tidak ada modul yang ditentukan atau modul bukan pendaftaran, arahkan ke form pendaftaran pasien
        if (empty($module) || ($module != 'pendaftaran' && $module != 'auth')) {
            header("Location: " . BASE_URL . "/index.php?module=pendaftaran&action=form_pendaftaran_pasien");
            exit;
        }
    }

    // Jika tidak ada action yang ditentukan untuk modul rekam_medis, arahkan ke data_pasien
    if ($module == 'rekam_medis' && empty($action)) {
        header('Location: ' . BASE_URL . '/index.php?module=rekam_medis&action=data_pasien');
        exit;
    }

    // Start output buffering
    ob_start();

    // Routing untuk modul rekam medis
    if ($module == 'rekam_medis') {
        // Set page title
        $page_title = "Rekam Medis";

        // Routing berdasarkan action
        switch ($action) {
            case 'manajemen_antrian':
                $rekamMedisController->manajemenAntrian();
                break;
            case 'data_pasien':
                $rekamMedisController->dataPasien();
                break;
            case 'daftar_atensi':
                $rekamMedisController->daftarAtensi();
                break;
            case 'template_anamnesis':
                $rekamMedisController->template_anamnesis();
                break;
            case 'simpan_template_anamnesis':
                $rekamMedisController->simpan_template_anamnesis();
                break;
            case 'update_template_anamnesis':
                $rekamMedisController->update_template_anamnesis();
                break;
            case 'hapus_template_anamnesis':
                $rekamMedisController->hapus_template_anamnesis();
                break;
            case 'edit_template_anamnesis_form':
                $rekamMedisController->edit_template_anamnesis_form();
                break;
            case 'get_template_anamnesis':
                $rekamMedisController->get_template_anamnesis();
                break;
                
            // Template Ceklist routes
            case 'template_ceklist':
                $rekamMedisController->template_ceklist();
                break;
                
            case 'simpan_template_ceklist':
                $rekamMedisController->simpan_template_ceklist();
                break;
                
            case 'update_template_ceklist':
                $rekamMedisController->update_template_ceklist();
                break;
                
            case 'hapus_template_ceklist':
                $rekamMedisController->hapus_template_ceklist();
                break;
                
            case 'edit_template_ceklist_form':
                $rekamMedisController->edit_template_ceklist_form();
                break;
            case 'template_tatalaksana':
                $rekamMedisController->template_tatalaksana();
                break;
            case 'simpan_template_tatalaksana':
                $rekamMedisController->simpan_template_tatalaksana();
                break;
            case 'edit_template_form':
                $rekamMedisController->edit_template_form();
                break;
            case 'update_template_tatalaksana':
                $rekamMedisController->update_template_tatalaksana();
                break;
            case 'hapus_template_tatalaksana':
                $rekamMedisController->hapus_template_tatalaksana();
                break;
            case 'get_template_tatalaksana':
                $rekamMedisController->get_template_tatalaksana();
                break;
            case 'template_usg':
                $rekamMedisController->template_usg();
                break;
            case 'simpan_template_usg':
                $rekamMedisController->simpan_template_usg();
                break;
            case 'edit_template_usg_form':
                $rekamMedisController->edit_template_usg_form();
                break;
            case 'update_template_usg':
                $rekamMedisController->update_template_usg();
                break;
            case 'hapus_template_usg':
                $rekamMedisController->hapus_template_usg();
                break;
            case 'get_template_usg':
                $rekamMedisController->get_template_usg();
                break;
            case 'cari_pasien':
                $rekamMedisController->cariPasien();
                break;
            case 'tambah_pasien':
                $rekamMedisController->tambahPasien();
                break;
            case 'simpan_pasien':
                $rekamMedisController->simpanPasien();
                break;
            case 'cek_nik_pasien':
                $rekamMedisController->cekNikPasien();
                break;
            case 'detailPasien':
                $rekamMedisController->detailPasien($_GET['no_rkm_medis']);
                break;
            case 'detail_pasien':
                $rekamMedisController->detailPasien($_GET['no_rkm_medis']);
                break;
            case 'editPasien':
                $rekamMedisController->editPasien();
                break;
            case 'updatePasien':
                $rekamMedisController->updatePasien();
                break;
            case 'hapusPasien':
                $rekamMedisController->hapusPasien();
                break;
            case 'periksa_pasien':
                error_log("Routing to periksa_pasien with no_rkm_medis: " . ($_GET['no_rkm_medis'] ?? 'not set'));
                $rekamMedisController->periksa_pasien();
                break;
            case 'tambah_pemeriksaan':
                error_log("Routing to tambah_pemeriksaan");
                $rekamMedisController->tambah_pemeriksaan();
                break;
            case 'simpan_pemeriksaan':
                error_log("Routing to simpan_pemeriksaan");
                $rekamMedisController->simpan_pemeriksaan();
                break;
            case 'edit_pemeriksaan':
                error_log("Routing to edit_pemeriksaan with id: " . ($_GET['id'] ?? 'no id'));
                $rekamMedisController->edit_pemeriksaan();
                break;
            case 'form_edit_pemeriksaan':
                error_log("Routing to form_edit_pemeriksaan with no_rawat: " . ($_GET['no_rawat'] ?? 'not set'));
                $rekamMedisController->formEditPemeriksaan();
                break;
            case 'update_pemeriksaan':
                $rekamMedisController->update_pemeriksaan();
                break;
            case 'tambah_penilaian_medis':
                $rekamMedisController->tambahPenilaianMedis();
                break;
            case 'simpan_penilaian_medis':
                $rekamMedisController->simpanPenilaianMedis();
                break;
            case 'simpan_penilaian_medis_ralan_kandungan':
                $rekamMedisController->simpan_penilaian_medis_ralan_kandungan();
                break;
            case 'tambah_tindakan_medis':
                $rekamMedisController->tambahTindakanMedis();
                break;
            case 'simpan_tindakan_medis':
                $rekamMedisController->simpanTindakanMedis();
                break;
            case 'edit_tindakan_medis':
                $rekamMedisController->editTindakanMedis($_GET['id']);
                break;
            case 'update_tindakan_medis':
                $rekamMedisController->updateTindakanMedis();
                break;
            case 'hapus_tindakan_medis':
                $rekamMedisController->hapusTindakanMedis($_GET['id']);
                break;
            case 'detail_tindakan_medis':
                $rekamMedisController->detailTindakanMedis($_GET['id']);
                break;
            case 'form_penilaian_medis_ralan_kandungan':
                $rekamMedisController->formPenilaianMedisRalanKandungan();
                break;
            case 'edit_kunjungan':
                $rekamMedisController->edit_kunjungan();
                break;
            case 'update_kunjungan':
                $rekamMedisController->update_kunjungan();
                break;
            case 'hapus_kunjungan':
                $rekamMedisController->hapus_kunjungan();
                break;
            case 'update_status':
                $rekamMedisController->update_status();
                break;
            case 'tambah_status_obstetri':
                $rekamMedisController->tambah_status_obstetri();
                break;
            case 'simpan_status_obstetri':
                $rekamMedisController->simpan_status_obstetri();
                break;
            case 'edit_status_obstetri':
                $rekamMedisController->edit_status_obstetri();
                break;
            case 'update_status_obstetri':
                $rekamMedisController->update_status_obstetri();
                break;
            case 'hapus_status_obstetri':
                $rekamMedisController->hapus_status_obstetri();
                break;
            case 'tambah_riwayat_kehamilan':
                $rekamMedisController->tambah_riwayat_kehamilan();
                break;
            case 'simpan_riwayat_kehamilan':
                $rekamMedisController->simpan_riwayat_kehamilan();
                break;
            case 'edit_riwayat_kehamilan':
                $rekamMedisController->edit_riwayat_kehamilan();
                break;
            case 'update_riwayat_kehamilan':
                $rekamMedisController->update_riwayat_kehamilan();
                break;
            case 'hapus_riwayat_kehamilan':
                $rekamMedisController->hapus_riwayat_kehamilan();
                break;
            case 'tambah_status_ginekologi':
                $rekamMedisController->tambah_status_ginekologi();
                break;
            case 'simpan_status_ginekologi':
                $rekamMedisController->simpan_status_ginekologi();
                break;
            case 'edit_status_ginekologi':
                $rekamMedisController->edit_status_ginekologi();
                break;
            case 'update_status_ginekologi':
                $rekamMedisController->update_status_ginekologi();
                break;
            case 'hapus_status_ginekologi':
                $rekamMedisController->hapus_status_ginekologi();
                break;
            case 'toggleBerikutnyaGratis':
                $rekamMedisController->toggleBerikutnyaGratis();
                break;
            case 'generate_pdf':
                error_log("Routing to generate_pdf");
                $rekamMedisController->generate_pdf();
                break;
            case 'generate_status_obstetri_pdf':
                error_log("Routing to generate_status_obstetri_pdf");
                $rekamMedisController->generate_status_obstetri_pdf();
                break;
            case 'generate_status_ginekologi_pdf':
                error_log("Routing to generate_status_ginekologi_pdf");
                $rekamMedisController->generate_status_ginekologi_pdf();
                break;
            case 'generate_edukasi_pdf':
                error_log("Routing to generate_edukasi_pdf");
                $rekamMedisController->generate_edukasi_pdf();
                break;
            case 'get_status_obstetri_ajax':
                // Mengambil data status obstetri via AJAX
                error_log("Routing to get_status_obstetri_ajax for no_rkm_medis: " . ($_GET['no_rkm_medis'] ?? 'not set'));
                // Tetapkan header untuk JSON
                header('Content-Type: application/json');

                // Langsung include file action untuk menghindari overhead controller
                include 'modules/rekam_medis/actions/get_status_obstetri_ajax.php';
                // Penting: exit setelah include untuk mencegah layout dimuat
                exit;
                break;
            case 'get_riwayat_kehamilan_ajax':
                // Mengambil data riwayat kehamilan via AJAX
                error_log("Routing to get_riwayat_kehamilan_ajax for no_rkm_medis: " . ($_GET['no_rkm_medis'] ?? 'not set'));
                // Tetapkan header untuk JSON
                header('Content-Type: application/json');

                // Langsung include file action untuk menghindari overhead controller
                include 'modules/rekam_medis/actions/get_riwayat_kehamilan_ajax.php';
                // Penting: exit setelah include untuk mencegah layout dimuat
                exit;
                break;
            case 'get_status_ginekologi_ajax':
                // Mengambil data status ginekologi via AJAX
                error_log("Routing to get_status_ginekologi_ajax for no_rkm_medis: " . ($_GET['no_rkm_medis'] ?? 'not set'));
                // Tetapkan header untuk JSON
                header('Content-Type: application/json');

                // Langsung include file action untuk menghindari overhead controller
                include 'modules/rekam_medis/actions/get_status_ginekologi_ajax.php';
                // Penting: exit setelah include untuk mencegah layout dimuat
                exit;
                break;
            case 'get_surat_ajax':
                // Mengambil data surat via AJAX
                error_log("Routing to get_surat_ajax for no_rkm_medis: " . ($_GET['no_rkm_medis'] ?? 'not set'));
                // Tetapkan header untuk JSON
                header('Content-Type: application/json');
                // Panggil method controller
                $rekamMedisController->get_surat_ajax();
                // Penting: exit setelah include untuk mencegah layout dimuat
                exit;
                break;
            case 'tambahSurat':
                // Menambah data surat via AJAX
                error_log("Routing to tambahSurat");
                // Tetapkan header untuk JSON
                header('Content-Type: application/json');
                // Panggil method controller
                $rekamMedisController->tambahSurat();
                // Penting: exit setelah include untuk mencegah layout dimuat
                exit;
                break;
            case 'hapus_surat':
                // Menghapus data surat
                error_log("Routing to hapus_surat");
                // Panggil method controller
                $rekamMedisController->hapus_surat();
                // Penting: exit setelah include untuk mencegah layout dimuat
                exit;
                break;
            case 'edit_surat':
                // Edit data surat
                error_log("Routing to edit_surat");
                // Panggil method controller
                $rekamMedisController->edit_surat();
                exit;
                break;
            case 'cetak_surat':
                // Cetak surat
                error_log("Routing to cetak_surat");
                // Panggil method controller
                $rekamMedisController->cetak_surat();
                exit;
                break;
            // case 'dataKunjungan':
            //     // Data Kunjungan dari tabel penilaian_medis_ralan_kandungan
            //     error_log("Routing to dataKunjungan");
            //     $rekamMedisController->dataKunjungan();
            //     break;
            case 'edit_pemeriksaan_data':
                $rekamMedisController->edit_pemeriksaan_data();
                break;
            case 'update_pemeriksaan':
                $rekamMedisController->update_pemeriksaan();
                break;
            default:
                if (empty($action)) {
                    $rekamMedisController->index();
                } else {
                    error_log("Invalid action requested: " . $action);
                    throw new Exception("Halaman tidak ditemukan");
                }
                break;
        }
    } elseif ($module == 'pendaftaran') {
        // Set page title
        $page_title = "Pendaftaran Pasien";
        
        // Routing berdasarkan action
        switch ($action) {
            case 'form_pendaftaran_pasien':
                include ROOT_PATH . '/modules/pendaftaran/views/form_pendaftaran_pasien.php';
                break;
            case 'pendaftaran_sukses':
                include ROOT_PATH . '/modules/pendaftaran/views/pendaftaran_sukses.php';
                break;
            default:
                // Default action for pendaftaran module
                include ROOT_PATH . '/modules/pendaftaran/controllers/antrian.php';
                break;
        }
    } elseif ($module == 'rshb') {
        // Set page title
        $page_title = "RSHB";
        
        // Load RSHB controller
        require_once 'modules/rshb/controllers/RshbController.php';
        $rshbController = new RshbController($conn);
        
        // Routing berdasarkan action
        switch ($action) {
            case 'dataPasien':
                $rshbController->dataPasien();
                break;
            case 'getAllPatients':
                $rshbController->getAllPatients();
                break;
            case 'getPatientById':
                $rshbController->getPatientById();
                break;
            default:
                // Jika action tidak ditemukan, redirect ke data_pasien
                header('Location: ' . BASE_URL . '/index.php?module=rshb&action=dataPasien');
                exit;
        }
    } else {
        // Jika modul tidak ditemukan, redirect ke form pendaftaran pasien
        header("Location: " . BASE_URL . "/index.php?module=pendaftaran&action=form_pendaftaran_pasien");
        exit;
    }
} catch (PDOException $e) {
    // Khusus untuk error database
    error_log("DATABASE ERROR in routing: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    
    if (defined('DEBUG') && DEBUG === true) {
        // Dalam mode debug, tampilkan error lengkap
        echo "<h1>Database Error</h1>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
        exit;
    } else {
        // Dalam mode produksi, redirect ke halaman error
        $_SESSION['error'] = "Terjadi kesalahan pada database. Silakan coba lagi atau hubungi administrator.";
        header('Location: ' . BASE_URL . '/index.php?module=rekam_medis&action=data_pasien');
        exit;
    }
} catch (Exception $e) {
    // Untuk error umum
    error_log("ERROR in routing: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    
    if (defined('DEBUG') && DEBUG === true) {
        // Dalam mode debug, tampilkan error lengkap
        echo "<h1>Application Error</h1>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
        exit;
    } else {
        // Dalam mode produksi, redirect ke halaman error
        $_SESSION['error'] = $e->getMessage();
        header('Location: ' . BASE_URL . '/index.php?module=rekam_medis&action=data_pasien');
        exit;
    }
}

echo "<!-- Debug Point 3: Sebelum ob_get_clean -->";

// Periksa buffer sebelum get_clean
$buffer_length = ob_get_length() ?: 0;
error_log("Content buffer length before ob_get_clean: " . $buffer_length);

// Alternatif 1: Gunakan ob_get_contents() dan ob_end_clean() terpisah
// untuk mendiagnosis masalah dgn ob_get_clean()
$content = ob_get_contents(); // Hanya mengambil konten tanpa membersihkan buffer
error_log("Buffer setelah ob_get_contents: " . (ob_get_length() ?: 0));
ob_end_clean(); // Membersihkan buffer

// Verifikasi konten yang diambil
if (empty($content)) {
    error_log("CRITICAL: Content buffer is empty! Original buffer length: " . $buffer_length);
    
    // Jika buffer asli tidak kosong tapi $content kosong, coba alternatif lain
    if ($buffer_length > 0) {
        error_log("Buffering issue detected - output exists but couldn't be captured");
    }
    
    // Fallback content
    $content = "<div class='alert alert-danger'>
        <h4>Error Rendering Content</h4>
        <p>Konten tidak dapat dimuat. Buffer asli: {$buffer_length} bytes.</p>
        <p>Silahkan coba <a href='" . $_SERVER['REQUEST_URI'] . "'>refresh halaman</a> atau hubungi administrator.</p>
      </div>";
} else {
    error_log("Content successfully captured, length: " . strlen($content) . " bytes");
    
    // Diagnostic: Log the first 200 chars of content
    $content_preview = substr($content, 0, 200);
    error_log("Content preview: " . $content_preview . (strlen($content) > 200 ? '...' : ''));
}

// Add diagnostic information at bottom of page for troubleshooting
if (isset($_GET['debug']) && $_GET['debug'] === 'content') {
    $content .= "<div style='background:#f8f9fa; padding:15px; margin:20px 0; border:1px solid #ddd;'>
        <h5>Content Debug Info</h5>
        <ul>
            <li>Original buffer length: {$buffer_length} bytes</li> 
            <li>Captured content length: " . strlen($content) . " bytes</li>
            <li>URI: {$_SERVER['REQUEST_URI']}</li>
            <li>PHP Version: " . PHP_VERSION . "</li>
            <li>Time: " . date('Y-m-d H:i:s') . "</li>
        </ul>
      </div>";
}

echo "<!-- Debug Point 4: Sebelum include layout -->";

// Periksa keberadaan layout template
$layout_path = __DIR__ . '/template/layout.php';
if (!file_exists($layout_path)) {
    error_log("CRITICAL: Layout template tidak ditemukan di: " . $layout_path);
    echo "<h1>Error</h1><p>Layout template tidak ditemukan. Path: {$layout_path}</p>";
    echo "<hr>Konten:<br>";
    echo $content; // Tampilkan konten tanpa layout jika layout tidak ditemukan
    exit;
}

// Include the layout template dengan path absolut
error_log("Including layout template from: " . $layout_path);
try {
    include $layout_path;
    echo "<!-- Debug Point 5: Setelah include layout -->";
} catch (Exception $e) {
    error_log("ERROR including layout: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    if (defined('DEBUG') && DEBUG === true) {
        echo "<h1>Error Including Layout</h1>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    } else {
        echo "<div class='alert alert-danger'>Terjadi kesalahan saat memuat layout. Silahkan coba lagi.</div>";
    }
}
