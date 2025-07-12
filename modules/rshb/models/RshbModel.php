<?php
/**
 * RSH Bersalin (RSHB) Model
 * 
 * Handles database operations for RSHB module
 * Connects to the specified database (simsvbaru)
 */
class RshbModel {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get all patients
     * 
     * @return array Array of patients
     */
    public function getAllPatients() {
        try {
            // Query from the pasien table in the simsvbaru database
            $query = "SELECT no_rkm_medis, nama, tgl_lahir, alamat, no_telp, jk, umur, pekerjaan, pnd, stts_nikah 
                     FROM pasien 
                     ORDER BY nama ASC 
                     LIMIT 1000"; // Limit to prevent performance issues
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getAllPatients: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get patient by ID
     * 
     * @param string $id Patient ID (no_rkm_medis)
     * @return array|false Patient data or false if not found
     */
    public function getPatientById($id) {
        try {
            // Query specific fields from the pasien table in the simsvbaru database
            $query = "SELECT no_rkm_medis, nama, tgl_lahir, alamat, no_telp, jk, umur, pekerjaan, pnd, stts_nikah,
                            nm_ibu, agama, stts_daftar, tgl_daftar
                     FROM pasien 
                     WHERE no_rkm_medis = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getPatientById: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get patient's obstetric status data
     * 
     * @param string $id Patient ID (no_rkm_medis)
     * @return array|false Obstetric status data or false if not found
     */
    public function getObstetricStatus($id) {
        try {
            $query = "SELECT * FROM status_obstetri WHERE no_rkm_medis = :id ORDER BY created_at DESC LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getObstetricStatus: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get patient's gynecological status data
     * 
     * @param string $id Patient ID (no_rkm_medis)
     * @return array|false Gynecological status data or false if not found
     */
    public function getGynecologicalStatus($id) {
        try {
            $query = "SELECT * FROM status_ginekologi WHERE no_rkm_medis = :id ORDER BY created_at DESC LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getGynecologicalStatus: " . $e->getMessage());
            return false;
        }
    }
}
