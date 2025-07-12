<?php
class StatusGinekologi
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getStatusGinekologiByPasien($no_rkm_medis)
    {
        try {
            // Coba menggunakan tanggal_pemeriksaan
            $query = "SELECT * FROM status_ginekologi WHERE no_rkm_medis = ? ORDER BY created_at DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$no_rkm_medis]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting status ginekologi data: " . $e->getMessage());
            return [];
        }
    }

    public function getStatusGinekologiById($id)
    {
        try {
            error_log("Mencari status ginekologi dengan ID: " . $id);
            $query = "SELECT * FROM status_ginekologi WHERE id_status_ginekologi = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Hasil query: " . ($result ? json_encode($result) : "tidak ditemukan"));
            return $result;
        } catch (PDOException $e) {
            error_log("Error getting status ginekologi by ID: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    public function tambahStatusGinekologi($data)
    {
        try {
            // Generate UUID untuk id_status_ginekologi
            $uuid = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff)
            );

            $query = "INSERT INTO status_ginekologi (
                id_status_ginekologi, no_rkm_medis, Parturien, Abortus, 
                Hari_pertama_haid_terakhir, Kontrasepsi_terakhir, lama_menikah_th, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $uuid,
                $data['no_rkm_medis'],
                $data['parturien'],
                $data['abortus'],
                $data['hari_pertama_haid_terakhir'],
                $data['kontrasepsi_terakhir'],
                $data['lama_menikah_th']
            ]);

            return $result ? $uuid : false;
        } catch (PDOException $e) {
            error_log("Error adding status ginekologi: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    public function updateStatusGinekologi($id, $data)
    {
        try {
            $query = "UPDATE status_ginekologi SET 
                Parturien = ?,
                Abortus = ?,
                Hari_pertama_haid_terakhir = ?,
                Kontrasepsi_terakhir = ?,
                lama_menikah_th = ?,
                updated_at = NOW()
                WHERE id_status_ginekologi = ?";

            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                $data['parturien'],
                $data['abortus'],
                $data['hari_pertama_haid_terakhir'],
                $data['kontrasepsi_terakhir'],
                $data['lama_menikah_th'],
                $id
            ]);
        } catch (PDOException $e) {
            error_log("Error updating status ginekologi: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    public function hapusStatusGinekologi($id)
    {
        try {
            $query = "DELETE FROM status_ginekologi WHERE id_status_ginekologi = ?";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error deleting status ginekologi: " . $e->getMessage());
            return false;
        }
    }
}
