<?php
class TemplateUsg
{
    private $pdo;

    public function __construct($conn = null)
    {
        try {
            if ($conn instanceof PDO) {
                $this->pdo = $conn;
            } else {
                // Jika koneksi tidak diberikan atau tidak valid, coba dapatkan dari global scope
                global $conn;

                if (!isset($conn) || !($conn instanceof PDO)) {
                    // Jika masih tidak tersedia, coba load dari config
                    if (!file_exists(dirname(dirname(dirname(__DIR__))) . '/config/database.php')) {
                        throw new Exception("File konfigurasi database tidak ditemukan");
                    }
                    require_once dirname(dirname(dirname(__DIR__))) . '/config/database.php';
                }

                if (!isset($conn) || !($conn instanceof PDO)) {
                    throw new PDOException("Koneksi database tidak tersedia");
                }

                $this->pdo = $conn;
            }

            // Test koneksi
            $test = $this->pdo->query("SELECT 1");
            if (!$test) {
                throw new PDOException("Koneksi database tidak dapat melakukan query");
            }
        } catch (PDOException $e) {
            error_log("Database Error in TemplateUsg constructor: " . $e->getMessage());
            throw new PDOException("Gagal menginisialisasi koneksi database: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("Error in TemplateUsg constructor: " . $e->getMessage());
            throw new Exception("Gagal menginisialisasi model: " . $e->getMessage());
        }
    }

    /**
     * Mendapatkan semua kategori template USG
     * 
     * @return array
     */
    public function getAllKategori()
    {
        try {
            // Menggunakan kategori enum yang sesuai dengan database
            $kategori = [
                ['kategori_usg' => 'obstetri'],
                ['kategori_usg' => 'ginekologi']
            ];

            return $kategori;
        } catch (PDOException $e) {
            error_log("Error in getAllKategori: " . $e->getMessage());
            throw new PDOException("Gagal mendapatkan kategori template: " . $e->getMessage());
        }
    }

    /**
     * Mendapatkan template USG berdasarkan kategori
     * 
     * @param string $kategori
     * @return array
     */
    public function getTemplateByKategori($kategori)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM template_usg WHERE kategori_usg = ? AND status = 'active' ORDER BY nama_template_usg ASC");
            $stmt->execute([$kategori]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getTemplateByKategori: " . $e->getMessage());
            throw new PDOException("Gagal mendapatkan template berdasarkan kategori: " . $e->getMessage());
        }
    }

    /**
     * Mendapatkan semua template USG
     * 
     * @return array
     */
    public function getAllTemplate()
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM template_usg WHERE status = 'active' ORDER BY kategori_usg ASC, nama_template_usg ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getAllTemplate: " . $e->getMessage());
            throw new PDOException("Gagal mendapatkan semua template: " . $e->getMessage());
        }
    }

    /**
     * Mendapatkan template USG berdasarkan ID
     * 
     * @param string $id_template
     * @return array|false
     */
    public function getTemplateById($id_template)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM template_usg WHERE id_template_usg = ? AND status = 'active'");
            $stmt->execute([$id_template]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getTemplateById: " . $e->getMessage());
            throw new PDOException("Gagal mendapatkan template berdasarkan ID: " . $e->getMessage());
        }
    }

    /**
     * Mencari template USG berdasarkan keyword
     * 
     * @param string $keyword
     * @return array
     */
    public function searchTemplate($keyword)
    {
        try {
            // Validasi keyword
            if (empty($keyword)) {
                return $this->getAllTemplate();
            }

            $keyword = '%' . trim($keyword) . '%';

            // Cari di nama, isi, kategori, dan tags
            $stmt = $this->pdo->prepare("SELECT * FROM template_usg 
                WHERE (nama_template_usg LIKE ? OR isi_template_usg LIKE ? OR kategori_usg LIKE ? OR tags LIKE ?) 
                AND status = 'active' 
                ORDER BY kategori_usg ASC, nama_template_usg ASC");

            $stmt->execute([$keyword, $keyword, $keyword, $keyword]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Jika tidak ada hasil, kembalikan array kosong
            if (!$results) {
                return [];
            }

            return $results;
        } catch (PDOException $e) {
            error_log("Error in searchTemplate: " . $e->getMessage());
            throw new PDOException("Gagal mencari template: " . $e->getMessage());
        }
    }

    /**
     * Menyimpan template USG baru
     * 
     * @param array $data
     * @return bool
     */
    public function saveTemplate($data)
    {
        try {
            // Validasi data
            if (empty($data['nama_template_usg']) || empty($data['isi_template_usg']) || empty($data['kategori_usg'])) {
                throw new PDOException("Data template tidak lengkap");
            }

            // Generate ID template baru
            $stmt = $this->pdo->prepare("SELECT MAX(CAST(SUBSTRING(id_template_usg, 3) AS UNSIGNED)) as max_id FROM template_usg");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $next_id = 1;
            if ($result && $result['max_id']) {
                $next_id = $result['max_id'] + 1;
            }

            $id_template = 'US' . str_pad($next_id, 6, '0', STR_PAD_LEFT);

            // Insert data
            $stmt = $this->pdo->prepare("INSERT INTO template_usg 
                (id_template_usg, nama_template_usg, isi_template_usg, kategori_usg, status, tags) 
                VALUES (?, ?, ?, ?, ?, ?)");

            $result = $stmt->execute([
                $id_template,
                $data['nama_template_usg'],
                $data['isi_template_usg'],
                $data['kategori_usg'],
                $data['status'],
                $data['tags']
            ]);

            if (!$result) {
                throw new PDOException("Gagal menyimpan template");
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Error in saveTemplate: " . $e->getMessage());
            throw new PDOException("Gagal menyimpan template: " . $e->getMessage());
        }
    }

    /**
     * Mengupdate template USG
     * 
     * @param array $data
     * @return bool
     */
    public function updateTemplate($data)
    {
        try {
            // Validasi data
            if (
                empty($data['id_template_usg']) || empty($data['nama_template_usg']) ||
                empty($data['isi_template_usg']) || empty($data['kategori_usg'])
            ) {
                throw new PDOException("Data template tidak lengkap");
            }

            // Cek apakah template dengan ID tersebut ada
            $check = $this->getTemplateById($data['id_template_usg']);
            if (!$check) {
                throw new PDOException("Template dengan ID tersebut tidak ditemukan");
            }

            // Update data
            $stmt = $this->pdo->prepare("UPDATE template_usg 
                SET nama_template_usg = ?, isi_template_usg = ?, kategori_usg = ?, status = ?, tags = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id_template_usg = ?");

            $result = $stmt->execute([
                $data['nama_template_usg'],
                $data['isi_template_usg'],
                $data['kategori_usg'],
                $data['status'],
                $data['tags'],
                $data['id_template_usg']
            ]);

            if (!$result) {
                throw new PDOException("Gagal mengupdate template");
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Error in updateTemplate: " . $e->getMessage());
            throw new PDOException("Gagal mengupdate template: " . $e->getMessage());
        }
    }

    /**
     * Menghapus template USG (hard delete)
     * 
     * @param string $id_template
     * @return bool
     */
    public function deleteTemplate($id_template)
    {
        try {
            // Validasi ID
            if (empty($id_template)) {
                throw new PDOException("ID template tidak valid");
            }

            // Cek apakah template dengan ID tersebut ada
            $check = $this->getTemplateById($id_template);
            if (!$check) {
                throw new PDOException("Template dengan ID tersebut tidak ditemukan");
            }

            // Hapus data
            $stmt = $this->pdo->prepare("DELETE FROM template_usg WHERE id_template_usg = ?");
            $result = $stmt->execute([$id_template]);

            if (!$result) {
                throw new PDOException("Gagal menghapus template");
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Error in deleteTemplate: " . $e->getMessage());
            throw new PDOException("Gagal menghapus template: " . $e->getMessage());
        }
    }
}
