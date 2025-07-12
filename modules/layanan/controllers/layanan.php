<?php
/**
 * Layanan Controller
 * Handles service-related operations and data processing
 * 
 * @author Your Name
 * @version 1.0
 */

// Memulai session jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../models/LayananModel.php';
require_once __DIR__ . '/../../../helpers/FormatHelper.php';

class LayananController 
{
    private $db;
    private $layananModel;
    
    public function __construct($database) 
    {
        $this->db = $database;
        $this->layananModel = new LayananModel($this->db);
    }
    
    /**
     * Display services page
     */
    public function index() 
    {
        try {
            // Check login status
            $is_logged_in = $this->isUserLoggedIn();
            
            // Get active services grouped by category
            $layanan_by_kategori = $this->layananModel->getActiveServicesGroupedByCategory();
            
            // Prepare data for view
            $data = [
                'is_logged_in' => $is_logged_in,
                'layanan_by_kategori' => $layanan_by_kategori,
                'base_url' => $GLOBALS['base_url'] ?? '',
                'page_title' => '',
                'page_subtitle' => '',
                'error_message' => null
            ];
            
            // Load view
            $this->loadView('index', $data);
            
        } catch (Exception $e) {
            // Log error
            error_log("Layanan Controller Error: " . $e->getMessage());
            
            // Prepare error data
            $data = [
                'is_logged_in' => $this->isUserLoggedIn(),
                'layanan_by_kategori' => [],
                'base_url' => $GLOBALS['base_url'] ?? '',
                'page_title' => '',
                'page_subtitle' => '',
                'error_message' => 'Terjadi kesalahan saat memuat data layanan. Silakan coba lagi nanti.'
            ];
            
            $this->loadView('index', $data);
        }
    }
    
    /**
     * Get service details via AJAX
     */
    public function getServiceDetails($id) 
    {
        header('Content-Type: application/json');
        
        try {
            // Validate input
            if (empty($id) || !is_numeric($id)) {
                throw new InvalidArgumentException('ID layanan tidak valid');
            }
            
            $service = $this->layananModel->getServiceById($id);
            
            if ($service) {
                // Format data for response
                $service['harga_formatted'] = FormatHelper::formatRupiah($service['harga']);
                $service['durasi_formatted'] = $service['durasi_estimasi'] 
                    ? FormatHelper::formatDuration($service['durasi_estimasi'])
                    : 'Estimasi variatif';
                
                echo json_encode([
                    'success' => true,
                    'data' => $service
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Layanan tidak ditemukan'
                ]);
            }
        } catch (Exception $e) {
            error_log("Get Service Details Error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memuat detail layanan'
            ]);
        }
    }
    
    /**
     * Handle service registration redirect
     */
    public function redirectToRegistration($layananId) 
    {
        try {
            // Validate input
            if (empty($layananId)) {
                throw new InvalidArgumentException('ID layanan diperlukan');
            }
            
            // Verify service exists
            $service = $this->layananModel->getServiceById($layananId);
            if (!$service) {
                throw new Exception('Layanan tidak ditemukan');
            }
            
            if ($this->isUserLoggedIn()) {
                $redirect_url = $GLOBALS['base_url'] . '/pendaftaran/form_pendaftaran_pasien.php?layanan=' . urlencode($layananId);
            } else {
                $current_url = urlencode($_SERVER["REQUEST_URI"]);
                $redirect_url = $GLOBALS['base_url'] . '/login.php?redirect=' . $current_url;
            }
            
            header("Location: " . $redirect_url);
            exit();
            
        } catch (Exception $e) {
            error_log("Registration Redirect Error: " . $e->getMessage());
            header("Location: " . $GLOBALS['base_url'] . '/layanan?error=' . urlencode('Terjadi kesalahan. Silakan coba lagi.'));
            exit();
        }
    }
    
    /**
     * Search services
     */
    public function search() 
    {
        header('Content-Type: application/json');
        
        try {
            $keyword = $_GET['q'] ?? '';
            
            if (empty($keyword)) {
                throw new InvalidArgumentException('Kata kunci pencarian diperlukan');
            }
            
            $services = $this->layananModel->searchServices($keyword);
            
            // Format services for response
            $formatted_services = array_map(function($service) {
                return [
                    'id' => $service['id_layanan'],
                    'nama' => $service['nama_layanan'],
                    'kategori' => $service['kategori'],
                    'deskripsi' => FormatHelper::truncateText($service['deskripsi'], 100),
                    'harga' => FormatHelper::formatRupiah($service['harga']),
                    'durasi' => $service['durasi_estimasi'] 
                        ? FormatHelper::formatDuration($service['durasi_estimasi'])
                        : 'Estimasi variatif'
                ];
            }, $services);
            
            echo json_encode([
                'success' => true,
                'data' => $formatted_services,
                'total' => count($formatted_services)
            ]);
            
        } catch (Exception $e) {
            error_log("Search Services Error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Terjadi kesalahan saat melakukan pencarian'
            ]);
        }
    }
    
    /**
     * Get services by category
     */
    public function getByCategory($category) 
    {
        header('Content-Type: application/json');
        
        try {
            if (empty($category)) {
                throw new InvalidArgumentException('Kategori diperlukan');
            }
            
            $services = $this->layananModel->getServicesByCategory($category);
            
            echo json_encode([
                'success' => true,
                'data' => $services,
                'total' => count($services)
            ]);
            
        } catch (Exception $e) {
            error_log("Get Services by Category Error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memuat layanan berdasarkan kategori'
            ]);
        }
    }
    
    /**
     * Get service statistics
     */
    public function getStats() 
    {
        header('Content-Type: application/json');
        
        try {
            $stats = $this->layananModel->getServiceStats();
            
            // Format statistics
            $formatted_stats = [
                'total_services' => $stats['total_services'],
                'total_categories' => $stats['total_categories'],
                'avg_price' => FormatHelper::formatRupiah($stats['avg_price']),
                'min_price' => FormatHelper::formatRupiah($stats['min_price']),
                'max_price' => FormatHelper::formatRupiah($stats['max_price'])
            ];
            
            echo json_encode([
                'success' => true,
                'data' => $formatted_stats
            ]);
            
        } catch (Exception $e) {
            error_log("Get Service Stats Error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memuat statistik layanan'
            ]);
        }
    }
    
    /**
     * Check if user is logged in
     */
    private function isUserLoggedIn() 
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Load view file
     */
    private function loadView($view, $data = []) 
    {
        // Extract data to variables
        extract($data);
        
        // Include view file
        $view_file = __DIR__ . '/../views/' . $view . '.php';
        
        if (file_exists($view_file)) {
            include $view_file;
        } else {
            throw new Exception("View file not found: " . $view_file);
        }
    }
    
    /**
     * Validate input data
     */
    private function validateInput($data, $rules) 
    {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            if ($rule['required'] && empty($data[$field])) {
                $errors[$field] = $rule['message'] ?? "Field {$field} is required";
            }
            
            if (isset($rule['type']) && !empty($data[$field])) {
                switch ($rule['type']) {
                    case 'numeric':
                        if (!is_numeric($data[$field])) {
                            $errors[$field] = "Field {$field} must be numeric";
                        }
                        break;
                    case 'email':
                        if (!filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                            $errors[$field] = "Field {$field} must be a valid email";
                        }
                        break;
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Send JSON response
     */
    private function sendJsonResponse($data, $status_code = 200) 
    {
        http_response_code($status_code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
    
    /**
     * Handle errors gracefully
     */
    private function handleError($message, $status_code = 500) 
    {
        error_log("Layanan Controller Error: " . $message);
        
        if ($this->isAjaxRequest()) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $message
            ], $status_code);
        } else {
            // Redirect to error page or show error message
            header("Location: " . $GLOBALS['base_url'] . '/layanan?error=' . urlencode($message));
            exit();
        }
    }
    
    /**
     * Check if request is AJAX
     */
    private function isAjaxRequest() 
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
}

// Initialize controller if accessed directly
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    try {
        $controller = new LayananController($conn);
        
        // Handle different actions
        $action = $_GET['action'] ?? 'index';
        
        switch ($action) {
            case 'details':
                $id = $_GET['id'] ?? null;
                if ($id) {
                    $controller->getServiceDetails($id);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'ID layanan diperlukan']);
                }
                break;
                
            case 'register':
                $layananId = $_GET['layanan_id'] ?? null;
                if ($layananId) {
                    $controller->redirectToRegistration($layananId);
                } else {
                    http_response_code(400);
                    echo "ID layanan diperlukan";
                }
                break;
                
            case 'search':
                $controller->search();
                break;
                
            case 'category':
                $category = $_GET['cat'] ?? null;
                if ($category) {
                    $controller->getByCategory($category);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Kategori diperlukan']);
                }
                break;
                
            case 'stats':
                $controller->getStats();
                break;
                
            default:
                $controller->index();
                break;
        }
        
    } catch (Exception $e) {
        error_log("Layanan Controller Fatal Error: " . $e->getMessage());
        http_response_code(500);
        
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem. Silakan coba lagi nanti.'
            ]);
        } else {
            echo "Terjadi kesalahan sistem. Silakan coba lagi nanti.";
        }
    }
}