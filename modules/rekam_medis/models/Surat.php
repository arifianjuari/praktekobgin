<?php
class Surat
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getSuratByPasien($no_rkm_medis)
    {
        try {
            $query = "SELECT * FROM surat_keterangan WHERE no_rkm_medis = ? ORDER BY created_at DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$no_rkm_medis]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting surat data: " . $e->getMessage());
            return [];
        }
    }

    public function getSuratById($id)
    {
        try {
            error_log("Mencari surat dengan ID: " . $id);
            $query = "SELECT * FROM surat_keterangan WHERE id_surat = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Hasil query: " . ($result ? json_encode($result) : "tidak ditemukan"));
            return $result;
        } catch (PDOException $e) {
            error_log("Error getting surat by ID: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Generate a formatted letter number (nomor surat)
     * Format: SKD / [sequential number in current year] / [month in Roman numerals] / [year]
     * 
     * @param string $tanggal_surat Date in Y-m-d format
     * @return string Formatted letter number
     */
    public function generateNomorSurat($tanggal_surat)
    {
        try {
            // Parse the date
            $date = new DateTime($tanggal_surat);
            $year = $date->format('Y');
            $month = $date->format('n'); // 1-12
            
            // Convert month to Roman numerals
            $romanMonths = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
            $romanMonth = $romanMonths[$month - 1];
            
            // Get the highest sequence number for the current year
            $query = "SELECT MAX(SUBSTRING_INDEX(SUBSTRING_INDEX(nomor_surat, '/', 2), '/', -1)) as max_number 
                      FROM surat_keterangan 
                      WHERE YEAR(tanggal_surat) = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$year]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Next sequence number
            $nextNumber = 1; // Default to 1 if no records found
            if ($result && $result['max_number'] !== null) {
                $nextNumber = (int)$result['max_number'] + 1;
            }
            
            // Format the letter number
            $nomorSurat = "SKD/$nextNumber/$romanMonth/$year";
            error_log("Generated nomor surat: $nomorSurat");
            
            return $nomorSurat;
        } catch (Exception $e) {
            error_log("Error generating nomor surat: " . $e->getMessage());
            // Fallback format if there's an error
            return "SKD/ERR/" . date('m/Y');
        }
    }
    
    public function tambahSurat($data)
    {
        try {
            error_log("tambahSurat model method called with data: " . json_encode($data));
            
            // Generate nomor surat
            $nomor_surat = $this->generateNomorSurat($data['tanggal_surat']);
            
            $query = "INSERT INTO surat_keterangan (
                no_rkm_medis, jenis_surat, tanggal_surat, 
                mulai_sakit, selesai_sakit, keperluan, 
                diagnosa, catatan, dokter_pemeriksa, created_by,
                nomor_surat, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

            error_log("SQL Query: " . $query);
            
            // Check database connection
            if (!$this->db instanceof PDO) {
                error_log("Database connection is not a valid PDO instance");
                return false;
            }
            
            try {
                $stmt = $this->db->prepare($query);
                error_log("Query prepared successfully");
                
                // Log the parameter values that will be bound
                $params = [
                    $data['no_rkm_medis'],
                    $data['jenis_surat'],
                    $data['tanggal_surat'],
                    $data['mulai_sakit'] ?? null,
                    $data['selesai_sakit'] ?? null,
                    $data['keperluan'] ?? null,
                    $data['diagnosa'] ?? null,
                    $data['catatan'] ?? null,
                    $data['dokter_pemeriksa'],
                    $data['created_by'] ?? $_SESSION['username'] ?? 'system',
                    $nomor_surat // Add the generated nomor_surat
                ];
                error_log("Parameters to bind: " . json_encode($params));
                
                $result = $stmt->execute($params);
                error_log("Query execution result: " . ($result ? 'true' : 'false'));
                
                if ($result) {
                    $lastId = $this->db->lastInsertId();
                    error_log("Last Insert ID: " . $lastId);
                    return $lastId;
                } else {
                    error_log("Execute failed, error info: " . json_encode($stmt->errorInfo()));
                    return false;
                }
            } catch (PDOException $e) {
                error_log("Inner PDO Exception: " . $e->getMessage());
                error_log("Inner PDO Error Code: " . $e->getCode());
                throw $e; // Re-throw the exception for the outer catch block
            }
        } catch (PDOException $e) {
            error_log("Error adding surat: " . $e->getMessage());
            error_log("PDO Error Code: " . $e->getCode());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        } catch (Exception $e) {
            error_log("General Exception: " . $e->getMessage());
            error_log("General Exception Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    public function updateSurat($id, $data)
    {
        try {
            // Check if tanggal_surat has changed to a different year
            $currentSurat = $this->getSuratById($id);
            $originalYear = date('Y', strtotime($currentSurat['tanggal_surat'])); 
            $newYear = date('Y', strtotime($data['tanggal_surat']));
            
            // If year has changed, generate a new nomor_surat
            if ($originalYear != $newYear) {
                $nomor_surat = $this->generateNomorSurat($data['tanggal_surat']);
                
                $query = "UPDATE surat_keterangan SET 
                    jenis_surat = ?,
                    tanggal_surat = ?,
                    mulai_sakit = ?,
                    selesai_sakit = ?,
                    keperluan = ?,
                    diagnosa = ?,
                    catatan = ?,
                    dokter_pemeriksa = ?,
                    nomor_surat = ?,
                    updated_at = NOW()
                    WHERE id_surat = ?";

                $stmt = $this->db->prepare($query);
                return $stmt->execute([
                    $data['jenis_surat'],
                    $data['tanggal_surat'],
                    $data['mulai_sakit'] ?? null,
                    $data['selesai_sakit'] ?? null,
                    $data['keperluan'] ?? null,
                    $data['diagnosa'] ?? null,
                    $data['catatan'] ?? null,
                    $data['dokter_pemeriksa'],
                    $nomor_surat,
                    $id
                ]);
            } else {
                // If year hasn't changed, preserve the original nomor_surat
                $query = "UPDATE surat_keterangan SET 
                    jenis_surat = ?,
                    tanggal_surat = ?,
                    mulai_sakit = ?,
                    selesai_sakit = ?,
                    keperluan = ?,
                    diagnosa = ?,
                    catatan = ?,
                    dokter_pemeriksa = ?,
                    updated_at = NOW()
                    WHERE id_surat = ?";

                $stmt = $this->db->prepare($query);
                return $stmt->execute([
                    $data['jenis_surat'],
                    $data['tanggal_surat'],
                    $data['mulai_sakit'] ?? null,
                    $data['selesai_sakit'] ?? null,
                    $data['keperluan'] ?? null,
                    $data['diagnosa'] ?? null,
                    $data['catatan'] ?? null,
                    $data['dokter_pemeriksa'],
                    $id
                ]);
            }
        } catch (PDOException $e) {
            error_log("Error updating surat: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    public function hapusSurat($id)
    {
        try {
            $query = "DELETE FROM surat_keterangan WHERE id_surat = ?";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error deleting surat: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
}
