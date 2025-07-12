<?php
class TemplateCeklist
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
            error_log("Database Error in TemplateCeklist constructor: " . $e->getMessage());
            throw new PDOException("Gagal menginisialisasi koneksi database: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("Error in TemplateCeklist constructor: " . $e->getMessage());
            throw new Exception("Gagal menginisialisasi model: " . $e->getMessage());
        }
    }

    /**
     * Mendapatkan semua kategori template ceklist
     * 
     * @return array
     */
    public function getAllKategori()
    {
        try {
            // Menggunakan kategori enum yang telah ditentukan
            $kategori = [
                ['kategori_ck' => 'obstetri'],
                ['kategori_ck' => 'ginekologi umum'],
                ['kategori_ck' => 'onkogin'],
                ['kategori_ck' => 'fertilitas'],
                ['kategori_ck' => 'uroginekologi']
            ];

            return $kategori;
        } catch (PDOException $e) {
            error_log("Error in getAllKategori: " . $e->getMessage());
            throw new PDOException("Gagal mendapatkan kategori template: " . $e->getMessage());
        }
    }

    /**
     * Mendapatkan template ceklist berdasarkan kategori
     * 
     * @param string $kategori
     * @return array
     */
    public function getTemplateByKategori($kategori)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM template_ceklist WHERE kategori_ck = ? AND status = 'active' ORDER BY nama_template_ck ASC");
            $stmt->execute([$kategori]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getTemplateByKategori: " . $e->getMessage());
            throw new PDOException("Gagal mendapatkan template berdasarkan kategori: " . $e->getMessage());
        }
    }

    /**
     * Mendapatkan semua template ceklist
     * 
     * @return array
     */
    public function getAllTemplate()
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM template_ceklist WHERE status = 'active' ORDER BY kategori_ck ASC, nama_template_ck ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getAllTemplate: " . $e->getMessage());
            throw new PDOException("Gagal mendapatkan semua template: " . $e->getMessage());
        }
    }

    /**
     * Mendapatkan template ceklist berdasarkan ID
     * 
     * @param string $id_template
     * @return array|false
     */
    public function getTemplateById($id_template)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM template_ceklist WHERE id_template_ceklist = ? AND status = 'active'");
            $stmt->execute([$id_template]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getTemplateById: " . $e->getMessage());
            throw new PDOException("Gagal mendapatkan template berdasarkan ID: " . $e->getMessage());
        }
    }

    /**
     * Mencari template ceklist berdasarkan keyword
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
            $stmt = $this->pdo->prepare("SELECT * FROM template_ceklist 
                WHERE (nama_template_ck LIKE ? OR isi_template_ck LIKE ? OR kategori_ck LIKE ? OR tags LIKE ?) 
                AND status = 'active' 
                ORDER BY kategori_ck ASC, nama_template_ck ASC");

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
     * Menyimpan template ceklist baru
     * 
     * @param array $data
     * @return bool
     */
    public function saveTemplate($data)
    {
        try {
            // Validasi data
            if (empty($data['nama_template_ck']) || empty($data['isi_template_ck']) || empty($data['kategori_ck'])) {
                throw new PDOException("Data template tidak lengkap");
            }

            // Generate ID template baru
            $stmt = $this->pdo->prepare("SELECT MAX(CAST(SUBSTRING(id_template_ceklist, 3) AS UNSIGNED)) as max_id FROM template_ceklist");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $next_id = 1;
            if ($result && $result['max_id']) {
                $next_id = $result['max_id'] + 1;
            }

            $id_template = 'CK' . str_pad($next_id, 6, '0', STR_PAD_LEFT);

            // Insert data
            $stmt = $this->pdo->prepare("INSERT INTO template_ceklist 
                (id_template_ceklist, nama_template_ck, isi_template_ck, kategori_ck, status, tags, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");

            $result = $stmt->execute([
                $id_template,
                $data['nama_template_ck'],
                $data['isi_template_ck'],
                $data['kategori_ck'],
                $data['status'],
                $data['tags'],
                $data['created_by'] ?? null
            ]);

            if (!$result) {
                throw new PDOException("Gagal menyimpan template");
            }

            return true;
        } catch (PDOException $e) {
            error_log("Error in saveTemplate: " . $e->getMessage());
            throw new PDOException("Gagal menyimpan template: " . $e->getMessage());
        }
    }

    /**
     * Mengupdate template ceklist
     * 
     * @param array $data
     * @return bool
     */
    public function updateTemplate($data)
    {
        try {
            // Validasi data
            if (empty($data['id_template_ceklist']) || empty($data['nama_template_ck']) || 
                empty($data['isi_template_ck']) || empty($data['kategori_ck'])) {
                throw new PDOException("Data template tidak lengkap");
            }

            // Update data
            $stmt = $this->pdo->prepare("UPDATE template_ceklist 
                SET nama_template_ck = ?, 
                    isi_template_ck = ?, 
                    kategori_ck = ?, 
                    status = ?,
                    tags = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id_template_ceklist = ?");

            $result = $stmt->execute([
                $data['nama_template_ck'],
                $data['isi_template_ck'],
                $data['kategori_ck'],
                $data['status'],
                $data['tags'],
                $data['id_template_ceklist']
            ]);

            if (!$result) {
                throw new PDOException("Gagal mengupdate template");
            }

            return true;
        } catch (PDOException $e) {
            error_log("Error in updateTemplate: " . $e->getMessage());
            throw new PDOException("Gagal mengupdate template: " . $e->getMessage());
        }
    }

    /**
     * Menghapus template ceklist (hard delete)
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

            // Delete data
            $stmt = $this->pdo->prepare("DELETE FROM template_ceklist WHERE id_template_ceklist = ?");
            $result = $stmt->execute([$id_template]);

            if (!$result) {
                throw new PDOException("Gagal menghapus template");
            }

            return true;
        } catch (PDOException $e) {
            error_log("Error in deleteTemplate: " . $e->getMessage());
            throw new PDOException("Gagal menghapus template: " . $e->getMessage());
        }
    }
}
