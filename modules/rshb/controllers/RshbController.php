<?php
/**
 * RSH Bersalin (RSHB) Controller
 * 
 * Handles all operations related to RSHB data management
 */
class RshbController {
    private $conn;
    private $rshbModel;
    
    public function __construct($conn) {
        $this->conn = $conn;
        // Load models
        require_once __DIR__ . '/../models/RshbModel.php';
        $this->rshbModel = new RshbModel($conn);
    }
    
    /**
     * Display the Data Pasien page
     */
    public function dataPasien() {
        // Set page title
        $page_title = "Data Pasien RSHB";
        
        // Include view
        include __DIR__ . '/../views/data_pasien.php';
    }
    
    /**
     * Get all patient data
     * Used for AJAX requests
     */
    public function getAllPatients() {
        $data = $this->rshbModel->getAllPatients();
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'data' => $data
        ]);
        exit;
    }
    
    /**
     * Get a single patient by ID
     * Used for AJAX requests
     */
    public function getPatientById() {
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'ID pasien tidak ditemukan'
            ]);
            exit;
        }
        
        $id = $_GET['id'];
        $data = $this->rshbModel->getPatientById($id);
        
        if (!$data) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Data pasien tidak ditemukan'
            ]);
            exit;
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'data' => $data
        ]);
        exit;
    }
}
