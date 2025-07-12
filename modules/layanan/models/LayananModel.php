<?php
/**
 * Layanan Model
 * Handles database operations for services
 * 
 * @author Your Name
 * @version 1.0
 */

class LayananModel 
{
    private $db;
    
    public function __construct($database) 
    {
        $this->db = $database;
    }
    
    /**
     * Get all active services
     */
    public function getActiveServices() 
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM menu_layanan WHERE status_aktif = 1 ORDER BY kategori, nama_layanan ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error fetching services: " . $e->getMessage());
        }
    }
    
    /**
     * Get active services grouped by category
     */
    public function getActiveServicesGroupedByCategory() 
    {
        $services = $this->getActiveServices();
        $grouped = [];
        
        foreach ($services as $service) {
            $grouped[$service['kategori']][] = $service;
        }
        
        return $grouped;
    }
    
    /**
     * Get service by ID
     */
    public function getServiceById($id) 
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM menu_layanan WHERE id_layanan = ? AND status_aktif = 1");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error fetching service by ID: " . $e->getMessage());
        }
    }
    
    /**
     * Get services by category
     */
    public function getServicesByCategory($category) 
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM menu_layanan WHERE kategori = ? AND status_aktif = 1 ORDER BY nama_layanan ASC");
            $stmt->execute([$category]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error fetching services by category: " . $e->getMessage());
        }
    }
    
    /**
     * Get all categories
     */
    public function getCategories() 
    {
        try {
            $stmt = $this->db->prepare("SELECT DISTINCT kategori FROM menu_layanan WHERE status_aktif = 1 ORDER BY kategori ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            throw new Exception("Error fetching categories: " . $e->getMessage());
        }
    }
    
    /**
     * Search services
     */
    public function searchServices($keyword) 
    {
        try {
            $keyword = '%' . $keyword . '%';
            $stmt = $this->db->prepare("
                SELECT * FROM menu_layanan 
                WHERE (nama_layanan LIKE ? OR deskripsi LIKE ? OR kategori LIKE ?) 
                AND status_aktif = 1 
                ORDER BY kategori, nama_layanan ASC
            ");
            $stmt->execute([$keyword, $keyword, $keyword]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error searching services: " . $e->getMessage());
        }
    }
    
    /**
     * Get service statistics
     */
    public function getServiceStats() 
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_services,
                    COUNT(DISTINCT kategori) as total_categories,
                    AVG(harga) as avg_price,
                    MIN(harga) as min_price,
                    MAX(harga) as max_price
                FROM menu_layanan 
                WHERE status_aktif = 1
            ");
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error fetching service statistics: " . $e->getMessage());
        }
    }
}
