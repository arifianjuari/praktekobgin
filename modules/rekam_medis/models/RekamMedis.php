<?php
class RekamMedis
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
            error_log("Database Error in RekamMedis constructor: " . $e->getMessage());
            throw new PDOException("Gagal menginisialisasi koneksi database: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("Error in RekamMedis constructor: " . $e->getMessage());
            throw new Exception("Gagal menginisialisasi model: " . $e->getMessage());
        }
    }

    public function getPasienById($no_rkm_medis)
    {
        try {
            // Tambahkan query parameter untuk mencegah cache
            $stmt = $this->pdo->prepare("SELECT * FROM pasien WHERE no_rkm_medis = ? LIMIT 1");
            $stmt->execute([$no_rkm_medis]);

            // Gunakan PDO::FETCH_ASSOC untuk memastikan hasil yang konsisten
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // Debug: Log hasil query
            // error_log("Data pasien: " . json_encode($result));

            return $result;
        } catch (PDOException $e) {
            error_log("Error getting patient data: " . $e->getMessage());
            return false;
        }
    }

    public function getRiwayatPemeriksaanRalan($no_rkm_medis)
    {
        $stmt = $this->pdo->prepare("
            SELECT tm.*, d.Nama_Dokter
            FROM tindakan_medis tm
            JOIN dokter d ON tm.ID_Dokter = d.ID_Dokter
            WHERE tm.no_rkm_medis = ?
            ORDER BY tm.tgl_tindakan DESC, tm.jam_tindakan DESC
        ");
        $stmt->execute([$no_rkm_medis]);
        return $stmt->fetchAll();
    }

    public function getRiwayatPemeriksaanObstetri($no_rkm_medis)
    {
        $stmt = $this->pdo->prepare("
            SELECT tm.*, d.Nama_Dokter
            FROM tindakan_medis tm
            JOIN dokter d ON tm.ID_Dokter = d.ID_Dokter
            WHERE tm.no_rkm_medis = ? AND tm.nama_tindakan LIKE '%Obstetri%'
            ORDER BY tm.tgl_tindakan DESC, tm.jam_tindakan DESC
        ");
        $stmt->execute([$no_rkm_medis]);
        return $stmt->fetchAll();
    }

    public function getRiwayatPemeriksaanGinekologi($no_rkm_medis)
    {
        $stmt = $this->pdo->prepare("
            SELECT tm.*, d.Nama_Dokter
            FROM tindakan_medis tm
            JOIN dokter d ON tm.ID_Dokter = d.ID_Dokter
            WHERE tm.no_rkm_medis = ? AND tm.nama_tindakan LIKE '%Ginekologi%'
            ORDER BY tm.tgl_tindakan DESC, tm.jam_tindakan DESC
        ");
        $stmt->execute([$no_rkm_medis]);
        return $stmt->fetchAll();
    }

    public function getRiwayatPenilaianMedis($no_rkm_medis)
    {
        $stmt = $this->pdo->prepare("
            SELECT tm.*, d.Nama_Dokter as nm_dokter
            FROM tindakan_medis tm
            JOIN dokter d ON tm.ID_Dokter = d.ID_Dokter
            WHERE tm.no_rkm_medis = ? AND tm.nama_tindakan LIKE '%Penilaian%'
            ORDER BY tm.tgl_tindakan DESC, tm.jam_tindakan DESC
        ");
        $stmt->execute([$no_rkm_medis]);
        return $stmt->fetchAll();
    }

    public function getRiwayatPemeriksaanUSG($no_rkm_medis)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT h.*, d.Nama_Dokter
                FROM hasil_pemeriksaan_usg h
                JOIN dokter d ON h.ID_Dokter = d.ID_Dokter
                WHERE h.no_rawat LIKE CONCAT(?,'%')
                ORDER BY h.tanggal DESC
            ");
            $stmt->execute([$no_rkm_medis]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getRiwayatPemeriksaanUSG: " . $e->getMessage());
            return [];
        }
    }

    public function getPenilaianMedisRalanKandungan($no_rkm_medis)
    {
    }

    /**
     * Update catatan_pasien pada tabel pasien
     * @param string $no_rkm_medis
     * @param string $catatan_pasien
     * @return bool
     */
    public function updateCatatanPasien($no_rkm_medis, $catatan_pasien)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE pasien SET catatan_pasien = ? WHERE no_rkm_medis = ?");
            return $stmt->execute([$catatan_pasien, $no_rkm_medis]);
        } catch (PDOException $e) {
            error_log("Error updating catatan_pasien: " . $e->getMessage());
            return false;
        }
    }

    public function tambahPenilaianMedisRalanKandungan($data)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO penilaian_medis_ralan_kandungan (
                    no_rawat, tanggal, kd_dokter,
                    anamnesis, hubungan, keluhan_utama, rps, rpd,
                    rpk, rpo, alergi, keadaan, kesadaran,
                    gcs, td, nadi, rr, suhu, spo, bb, tb,
                    kepala, mata, gigi, tht, thoraks,
                    abdomen, genital, ekstremitas, kulit, ket_fisik,
                    tfu, tbj, his, kontraksi, djj,
                    inspeksi, inspekulo, vt, rt,
                    ultra, kardio, lab,
                    diagnosis, tata, konsul, edukasi, resep
                ) VALUES (
                    :no_rawat, :tanggal, :kd_dokter,
                    :anamnesis, :hubungan, :keluhan_utama, :rps, :rpd,
                    :rpk, :rpo, :alergi, :keadaan, :kesadaran,
                    :gcs, :td, :nadi, :rr, :suhu, :spo, :bb, :tb,
                    :kepala, :mata, :gigi, :tht, :thoraks,
                    :abdomen, :genital, :ekstremitas, :kulit, :ket_fisik,
                    :tfu, :tbj, :his, :kontraksi, :djj,
                    :inspeksi, :inspekulo, :vt, :rt,
                    :ultra, :kardio, :lab,
                    :diagnosis, :tata, :konsul, :edukasi, :resep
                )
            ");

            $stmt->execute([
                ':no_rawat' => $data['no_rawat'],
                ':tanggal' => $data['tanggal'],
                ':kd_dokter' => $data['kd_dokter'],
                ':anamnesis' => $data['anamnesis'],
                ':hubungan' => $data['hubungan'],
                ':keluhan_utama' => $data['keluhan_utama'],
                ':rps' => $data['rps'],
                ':rpd' => $data['rpd'],
                ':rpk' => $data['rpk'],
                ':rpo' => $data['rpo'],
                ':alergi' => $data['alergi'],
                ':keadaan' => $data['keadaan'],
                ':kesadaran' => $data['kesadaran'],
                ':gcs' => $data['gcs'],
                ':td' => $data['td'],
                ':nadi' => $data['nadi'],
                ':rr' => $data['rr'],
                ':suhu' => $data['suhu'],
                ':spo' => $data['spo'],
                ':bb' => $data['bb'],
                ':tb' => $data['tb'],
                ':kepala' => $data['kepala'],
                ':mata' => $data['mata'],
                ':gigi' => $data['gigi'],
                ':tht' => $data['tht'],
                ':thoraks' => $data['thoraks'],
                ':abdomen' => $data['abdomen'],
                ':genital' => $data['genital'],
                ':ekstremitas' => $data['ekstremitas'],
                ':kulit' => $data['kulit'],
                ':ket_fisik' => $data['ket_fisik'],
                ':tfu' => $data['tfu'],
                ':tbj' => $data['tbj'],
                ':his' => $data['his'],
                ':kontraksi' => $data['kontraksi'],
                ':djj' => $data['djj'],
                ':inspeksi' => $data['inspeksi'],
                ':inspekulo' => $data['inspekulo'],
                ':vt' => $data['vt'],
                ':rt' => $data['rt'],
                ':ultra' => $data['ultra'],
                ':kardio' => $data['kardio'],
                ':lab' => $data['lab'],
                ':diagnosis' => $data['diagnosis'],
                ':tata' => $data['tata'],
                ':konsul' => $data['konsul'],
                ':edukasi' => $data['edukasi'],
                ':resep' => $data['resep']
            ]);

            return true;
        } catch (PDOException $e) {
            error_log("Error in tambahPenilaianMedisRalanKandungan: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    public function updatePenilaianMedisRalanKandungan($data)
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE penilaian_medis_ralan_kandungan SET
                    anamnesis = ?, hubungan = ?, keluhan_utama = ?, rps = ?, rpd = ?,
                    rpk = ?, rpo = ?, alergi = ?, keadaan = ?, kesadaran = ?,
                    td = ?, nadi = ?, suhu = ?, rr = ?, bb = ?,
                    tb = ?, lila = ?, tfu = ?, tbj = ?, his = ?,
                    kontraksi = ?, djj = ?, inspeksi = ?, inspekulo = ?, fluxus = ?,
                    fluor = ?, dalam = ?, pembukaan = ?, portio = ?, ketuban = ?,
                    presentasi = ?, penurunan = ?, denominator = ?, ukuran_panggul = ?, diagnosa = ?,
                    tindakan = ?, edukasi = ?, resep = ?
                WHERE no_rawat = ?
            ");

            $stmt->execute([
                $data['anamnesis'],
                $data['hubungan'],
                $data['keluhan_utama'],
                $data['rps'],
                $data['rpd'],
                $data['rpk'],
                $data['rpo'],
                $data['alergi'],
                $data['keadaan'],
                $data['kesadaran'],
                $data['td'],
                $data['nadi'],
                $data['suhu'],
                $data['rr'],
                $data['bb'],
                $data['tb'],
                $data['lila'],
                $data['tfu'],
                $data['tbj'],
                $data['his'],
                $data['kontraksi'],
                $data['djj'],
                $data['inspeksi'],
                $data['inspekulo'],
                $data['fluxus'],
                $data['fluor'],
                $data['dalam'],
                $data['pembukaan'],
                $data['portio'],
                $data['ketuban'],
                $data['presentasi'],
                $data['penurunan'],
                $data['denominator'],
                $data['ukuran_panggul'],
                $data['diagnosa'],
                $data['tindakan'],
                $data['edukasi'],
                $data['resep'],
                $data['no_rawat']
            ]);

            return true;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function getPenilaianMedisRalanKandunganByNoRawat($no_rawat)
    {
        try {
            error_log("Starting getPenilaianMedisRalanKandunganByNoRawat for no_rawat: " . $no_rawat);

            $query = "
                SELECT pmr.*, d.Nama_Dokter, rp.no_rkm_medis
                FROM penilaian_medis_ralan_kandungan pmr
                JOIN dokter d ON pmr.ID_Dokter = d.ID_Dokter
                JOIN reg_periksa rp ON pmr.no_rawat = rp.no_rawat
                WHERE pmr.no_rawat = ?
            ";

            error_log("Executing query: " . $query);

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$no_rawat]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                error_log("Found penilaian medis data: " . json_encode($result));
            } else {
                error_log("No penilaian medis found with no_rawat: " . $no_rawat);

                // Coba query alternatif untuk debugging
                $debugQuery = "
                    SELECT COUNT(*) as count 
                    FROM penilaian_medis_ralan_kandungan 
                    WHERE no_rawat = ?
                ";
                $debugStmt = $this->pdo->prepare($debugQuery);
                $debugStmt->execute([$no_rawat]);
                $count = $debugStmt->fetchColumn();
                error_log("Debug: Found {$count} records in penilaian_medis_ralan_kandungan");
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Error in getPenilaianMedisRalanKandunganByNoRawat: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    public function getGambarUSG($no_rawat)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM hasil_pemeriksaan_usg_gambar
                WHERE no_rawat = ?
            ");
            $stmt->execute([$no_rawat]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getGambarUSG: " . $e->getMessage());
            return [];
        }
    }

    public function getRiwayatPemeriksaanUSGGynecologi($no_rkm_medis)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT pug.*, d.Nama_Dokter
                FROM hasil_pemeriksaan_usg_gynecologi pug
                JOIN dokter d ON pug.kd_dokter = d.ID_Dokter
                WHERE pug.no_rawat LIKE CONCAT(?,'%')
                ORDER BY pug.tanggal DESC
            ");
            $stmt->execute([$no_rkm_medis]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getRiwayatPemeriksaanUSGGynecologi: " . $e->getMessage());
            return [];
        }
    }

    public function getGambarUSGGynecologi($no_rawat)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM hasil_pemeriksaan_usg_gynecologi_gambar
                WHERE no_rawat = ?
            ");
            $stmt->execute([$no_rawat]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getGambarUSGGynecologi: " . $e->getMessage());
            return [];
        }
    }

    public function getRiwayatKehamilan($no_rkm_medis)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM riwayat_kehamilan
            WHERE no_rkm_medis = ?
            ORDER BY no_urut_kehamilan ASC
        ");
        $stmt->execute([$no_rkm_medis]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSkriningKehamilan($no_rkm_medis)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT sk.*, rk.no_urut_kehamilan 
                FROM skrining_kehamilan sk
                JOIN riwayat_kehamilan rk ON sk.no_rawat = rk.no_rawat
                WHERE rk.no_rawat LIKE CONCAT(?,'%')
                ORDER BY sk.tanggal_pemeriksaan DESC
            ");
            $stmt->execute([$no_rkm_medis]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Jika tabel tidak ditemukan, log error dan kembalikan array kosong
            error_log("Error in getSkriningKehamilan: " . $e->getMessage());
            return [];
        }
    }

    // Fungsi untuk mendapatkan data status obstetri
    public function getStatusObstetri($no_rkm_medis)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM status_obstetri
                WHERE no_rkm_medis = ?
                ORDER BY created_at DESC
            ");
            $stmt->execute([$no_rkm_medis]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting status obstetri data: " . $e->getMessage());
            return [];
        }
    }

    // Fungsi untuk mendapatkan data status obstetri berdasarkan ID
    public function getStatusObstetriById($id_status_obstetri)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM status_obstetri
                WHERE id_status_obstetri = ?
                LIMIT 1
            ");
            $stmt->execute([$id_status_obstetri]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting status obstetri by ID: " . $e->getMessage());
            return false;
        }
    }

    // Fungsi untuk menambahkan data status obstetri
    public function tambahStatusObstetri($data)
    {
        try {
            // Debugging
            error_log("=== DEBUG TAMBAH STATUS OBSTETRI MODEL ===");
            error_log("Data yang diterima: " . print_r($data, true));

            // Generate UUID untuk id_status_obstetri
            $uuid = $this->generateUUID();

            $stmt = $this->pdo->prepare("
                INSERT INTO status_obstetri (
                    id_status_obstetri, no_rkm_medis, gravida, paritas, abortus, tb,
                    tanggal_hpht, tanggal_tp, tanggal_tp_penyesuaian, faktor_risiko_umum, faktor_risiko_obstetri,
                    faktor_risiko_preeklampsia, hasil_faktor_risiko, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP
                )
            ");

            $params = [
                $uuid,
                $data['no_rkm_medis'],
                isset($data['gravida']) && $data['gravida'] !== '' ? (int)$data['gravida'] : null,
                isset($data['paritas']) && $data['paritas'] !== '' ? (int)$data['paritas'] : null,
                isset($data['abortus']) && $data['abortus'] !== '' ? (int)$data['abortus'] : null,
                isset($data['tb']) && $data['tb'] !== '' ? (int)$data['tb'] : null,
                !empty($data['tanggal_hpht']) ? $data['tanggal_hpht'] : null,
                !empty($data['tanggal_tp']) ? $data['tanggal_tp'] : null,
                !empty($data['tanggal_tp_penyesuaian']) ? $data['tanggal_tp_penyesuaian'] : null,
                (isset($data['faktor_risiko_umum']) && is_array($data['faktor_risiko_umum']) && count($data['faktor_risiko_umum']) > 0) ? implode(',', $data['faktor_risiko_umum']) : null,
                (isset($data['faktor_risiko_obstetri']) && is_array($data['faktor_risiko_obstetri']) && count($data['faktor_risiko_obstetri']) > 0) ? implode(',', $data['faktor_risiko_obstetri']) : null,
                (isset($data['faktor_risiko_preeklampsia']) && is_array($data['faktor_risiko_preeklampsia']) && count($data['faktor_risiko_preeklampsia']) > 0) ? implode(',', $data['faktor_risiko_preeklampsia']) : null,
                isset($data['hasil_faktor_risiko']) && $data['hasil_faktor_risiko'] !== '' ? $data['hasil_faktor_risiko'] : null
            ];

            // Debugging
            error_log("Query parameters: " . print_r($params, true));

            $stmt->execute($params);

            return $uuid;
        } catch (PDOException $e) {
            error_log("Error adding status obstetri: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    // Fungsi untuk mengupdate data status obstetri
    public function updateStatusObstetri($data)
    {
        try {
            // Debugging
            error_log("=== DEBUG UPDATE STATUS OBSTETRI MODEL ===");
            error_log("Data yang diterima: " . print_r($data, true));

            $stmt = $this->pdo->prepare("
                UPDATE status_obstetri SET
                    gravida = ?,
                    paritas = ?,
                    abortus = ?,
                    tb = ?,
                    tanggal_hpht = ?,
                    tanggal_tp = ?,
                    tanggal_tp_penyesuaian = ?,
                    faktor_risiko_umum = ?,
                    faktor_risiko_obstetri = ?,
                    faktor_risiko_preeklampsia = ?,
                    hasil_faktor_risiko = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id_status_obstetri = ?
            ");

            $params = [
                $data['gravida'],
                $data['paritas'],
                $data['abortus'],
                $data['tb'],
                $data['tanggal_hpht'],
                $data['tanggal_tp'],
                $data['tanggal_tp_penyesuaian'],
                isset($data['faktor_risiko_umum']) ? implode(',', $data['faktor_risiko_umum']) : null,
                isset($data['faktor_risiko_obstetri']) ? implode(',', $data['faktor_risiko_obstetri']) : null,
                isset($data['faktor_risiko_preeklampsia']) ? implode(',', $data['faktor_risiko_preeklampsia']) : null,
                $data['hasil_faktor_risiko'],
                $data['id_status_obstetri']
            ];

            // Debugging
            error_log("Query parameters: " . print_r($params, true));

            $stmt->execute($params);

            return true;
        } catch (PDOException $e) {
            error_log("Error updating status obstetri: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    // Fungsi untuk menghapus data status obstetri
    public function hapusStatusObstetri($id_status_obstetri)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM status_obstetri WHERE id_status_obstetri = ?");
            $stmt->execute([$id_status_obstetri]);
            return true;
        } catch (PDOException $e) {
            error_log("Error deleting status obstetri: " . $e->getMessage());
            return false;
        }
    }

    // Fungsi untuk generate UUID
    private function generateUUID()
    {
        return sprintf(
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
    }

    public function getTindakanMedis($no_rkm_medis)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM tindakan_medis WHERE no_rkm_medis = ?
            ORDER BY tgl_tindakan DESC, jam_tindakan DESC
        ");
        $stmt->execute([$no_rkm_medis]);
        return $stmt->fetchAll();
    }

    public function updatePasien($no_rkm_medis, $data)
    {
        try {
            // Buat query update dinamis berdasarkan data yang diberikan
            $fields = [];
            $values = [];

            foreach ($data as $field => $value) {
                // Terima nilai kosong untuk beberapa field
                if (!empty($value) || $value === '0' || $value === 0 || $field === 'no_tlp' || $field === 'email' || $field === 'no_peserta') {
                    $fields[] = "$field = ?";
                    $values[] = $value;
                }
            }

            // Jika tidak ada data yang valid untuk diupdate
            if (empty($fields)) {
                error_log("No valid fields to update for patient: " . $no_rkm_medis);
                return false;
            }

            // Tambahkan no_rkm_medis ke array values untuk WHERE clause
            $values[] = $no_rkm_medis;

            $query = "UPDATE pasien SET " . implode(', ', $fields) . " WHERE no_rkm_medis = ?";

            // Debug: Log query dan parameter
            error_log("Update query: " . $query);
            error_log("Update parameters: " . json_encode($values));

            $stmt = $this->pdo->prepare($query);
            $result = $stmt->execute($values);

            // Debug: Log hasil eksekusi
            error_log("Update result: " . ($result ? "success" : "failed") . ", affected rows: " . $stmt->rowCount());

            return $result;
        } catch (PDOException $e) {
            error_log("Error updating pasien: " . $e->getMessage());
            throw new Exception("Gagal memperbarui data pasien: " . $e->getMessage());
        }
    }

    public function getRiwayatPemeriksaan($no_rkm_medis)
    {
        try {
            error_log("Starting getRiwayatPemeriksaan for no_rkm_medis: " . $no_rkm_medis);

            $query = "SELECT 
                    rp.no_rawat,
                    rp.tgl_registrasi,
                    rp.jam_reg,
                    rp.no_rkm_medis,
                    rp.status_bayar,
                    rp.rincian,
                    pmrk.tanggal as tgl_pemeriksaan,
                    COALESCE(pmrk.anamnesis, '') as anamnesis,
                    COALESCE(pmrk.hubungan, '') as hubungan,
                    COALESCE(pmrk.keluhan_utama, '') as keluhan_utama,
                    COALESCE(pmrk.rps, '') as rps,
                    COALESCE(pmrk.rpd, '') as rpd,
                    COALESCE(pmrk.rpk, '') as rpk,
                    COALESCE(pmrk.rpo, '') as rpo,
                    COALESCE(pmrk.alergi, '') as alergi,
                    COALESCE(pmrk.keadaan, '') as keadaan,
                    COALESCE(pmrk.kesadaran, '') as kesadaran,
                    COALESCE(pmrk.gcs, '') as gcs,
                    COALESCE(pmrk.td, '') as td,
                    COALESCE(pmrk.nadi, '') as nadi,
                    COALESCE(pmrk.rr, '') as rr,
                    COALESCE(pmrk.suhu, '') as suhu,
                    COALESCE(pmrk.spo, '') as spo,
                    COALESCE(pmrk.bb, '') as bb,
                    COALESCE(pmrk.tb, '') as tb,
                    COALESCE(pmrk.bmi, '') as bmi,
                    COALESCE(pmrk.interpretasi_bmi, '') as interpretasi_bmi,
                    COALESCE(pmrk.kepala, '') as kepala,
                    COALESCE(pmrk.mata, '') as mata,
                    COALESCE(pmrk.gigi, '') as gigi,
                    COALESCE(pmrk.tht, '') as tht,
                    COALESCE(pmrk.thoraks, '') as thoraks,
                    COALESCE(pmrk.abdomen, '') as abdomen,
                    COALESCE(pmrk.genital, '') as genital,
                    COALESCE(pmrk.ekstremitas, '') as ekstremitas,
                    COALESCE(pmrk.kulit, '') as kulit,
                    COALESCE(pmrk.ket_fisik, '') as ket_fisik,
                    COALESCE(pmrk.ultra, '') as ultra,
                    COALESCE(pmrk.lab, '') as lab,
                    COALESCE(pmrk.diagnosis, '') as diagnosis,
                    COALESCE(pmrk.tata, '') as tata,
                    COALESCE(pmrk.resep, '') as resep
                FROM reg_periksa rp
                LEFT JOIN penilaian_medis_ralan_kandungan pmrk ON rp.no_rawat = pmrk.no_rawat
                WHERE rp.no_rkm_medis = ?
                ORDER BY rp.tgl_registrasi DESC, rp.jam_reg DESC
            ";

            error_log("Executing query: " . $query);
            error_log("With parameter: " . $no_rkm_medis);

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$no_rkm_medis]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            error_log("Found " . count($result) . " records");
            if (count($result) > 0) {
                error_log("Sample record: " . json_encode($result[0]));
            }

            // Debug: Tampilkan data penilaian medis secara terpisah
            $debug_query = "SELECT * FROM penilaian_medis_ralan_kandungan WHERE no_rawat IN (
                SELECT no_rawat FROM reg_periksa WHERE no_rkm_medis = ?
            )";
            $debug_stmt = $this->pdo->prepare($debug_query);
            $debug_stmt->execute([$no_rkm_medis]);
            $debug_result = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Debug - Found " . count($debug_result) . " penilaian medis records");
            if (count($debug_result) > 0) {
                error_log("Debug - Sample penilaian medis: " . json_encode($debug_result[0]));
            }

            return $result;
        } catch (Exception $e) {
            error_log("Error in getRiwayatPemeriksaan: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [];
        }
    }

    public function updatePemeriksaan($data)
    {
        try {
            // Log untuk debugging
            error_log("Updating pemeriksaan for no_rawat: " . $data['no_rawat']);
            error_log("Data to update: " . json_encode($data));

            // Jika ada data ceklist, update ke tabel pasien
            if (isset($data['ceklist'])) {
                $no_rkm_medis = $this->getNoRkmMedisByNoRawat($data['no_rawat']);
                if ($no_rkm_medis) {
                    $updateCeklist = $this->pdo->prepare("UPDATE pasien SET ceklist = ? WHERE no_rkm_medis = ?");
                    $updateCeklist->execute([$data['ceklist'], $no_rkm_medis]);
                    error_log("Updated ceklist in pasien table for no_rkm_medis: " . $no_rkm_medis);
                }
            }

            // Periksa apakah data pemeriksaan sudah ada
            $check = $this->pdo->prepare("SELECT COUNT(*) FROM penilaian_medis_ralan_kandungan WHERE no_rawat = ?");
            $check->execute([$data['no_rawat']]);
            $exists = $check->fetchColumn();

            if (!$exists) {
                // Jika belum ada, lakukan INSERT
                $stmt = $this->pdo->prepare("
                    INSERT INTO penilaian_medis_ralan_kandungan (
                        no_rawat, tanggal, id_perujuk, keluhan_utama, rps, rpd, alergi,
                        gcs, td, nadi, rr, suhu, spo, bb, tb,
                        kepala, mata, gigi, tht, thoraks,
                        abdomen, genital, ekstremitas, kulit, ket_fisik,
                        ultra, lab, diagnosis, tata, edukasi,
                        tanggal_kontrol, atensi, resume, resep
                    ) VALUES (
                        :no_rawat, NOW(), :id_perujuk, :keluhan_utama, :rps, :rpd, :alergi,
                        :gcs, :td, :nadi, :rr, :suhu, :spo, :bb, :tb,
                        :kepala, :mata, :gigi, :tht, :thoraks,
                        :abdomen, :genital, :ekstremitas, :kulit, :ket_fisik,
                        :ultra, :lab, :diagnosis, :tata, :edukasi,
                        :tanggal_kontrol, :atensi, :resume, :resep
                    )
                ");
            } else {
                // Jika sudah ada, lakukan UPDATE
                $stmt = $this->pdo->prepare("
                    UPDATE penilaian_medis_ralan_kandungan SET
                        keluhan_utama = :keluhan_utama,
                        id_perujuk = :id_perujuk,
                        rps = :rps,
                        rpd = :rpd,
                        alergi = :alergi,
                        gcs = :gcs,
                        td = :td,
                        nadi = :nadi,
                        rr = :rr,
                        suhu = :suhu,
                        spo = :spo,
                        bb = :bb,
                        tb = :tb,
                        kepala = :kepala,
                        mata = :mata,
                        gigi = :gigi,
                        tht = :tht,
                        thoraks = :thoraks,
                        abdomen = :abdomen,
                        genital = :genital,
                        ekstremitas = :ekstremitas,
                        kulit = :kulit,
                        ket_fisik = :ket_fisik,
                        ultra = :ultra,
                        lab = :lab,
                        diagnosis = :diagnosis,
                        tata = :tata,
                        edukasi = :edukasi,
                        resume = :resume,
                        resep = :resep,
                        tanggal_kontrol = :tanggal_kontrol,
                        atensi = :atensi
                    WHERE no_rawat = :no_rawat
                ");
            }

            $params = [
                ':no_rawat' => $data['no_rawat'],
                ':id_perujuk' => $data['id_perujuk'] ?? null,
                ':keluhan_utama' => $data['keluhan_utama'],
                ':rps' => $data['rps'],
                ':rpd' => $data['rpd'],
                ':alergi' => $data['alergi'],
                ':gcs' => $data['gcs'],
                ':td' => $data['td'],
                ':nadi' => $data['nadi'],
                ':rr' => $data['rr'],
                ':suhu' => $data['suhu'],
                ':spo' => $data['spo'],
                ':bb' => $data['bb'],
                ':tb' => $data['tb'],
                ':kepala' => $data['kepala'],
                ':mata' => $data['mata'],
                ':gigi' => $data['gigi'],
                ':tht' => $data['tht'],
                ':thoraks' => $data['thoraks'],
                ':abdomen' => $data['abdomen'],
                ':genital' => $data['genital'],
                ':ekstremitas' => $data['ekstremitas'],
                ':kulit' => $data['kulit'],
                ':ket_fisik' => $data['ket_fisik'],
                ':ultra' => $data['ultra'],
                ':lab' => $data['lab'],
                ':diagnosis' => $data['diagnosis'],
                ':tata' => $data['tata'],
                ':edukasi' => $data['edukasi'],
                ':resume' => $data['resume'],
                ':resep' => $data['resep'],
                ':tanggal_kontrol' => $data['tanggal_kontrol'],
                ':atensi' => $data['atensi']
            ];

            $result = $stmt->execute($params);

            if (!$result) {
                error_log("PDO Error Info: " . print_r($stmt->errorInfo(), true));
            }
            error_log("Rows affected: " . $stmt->rowCount());

            return $result;
        } catch (PDOException $e) {
            error_log("Error updating pemeriksaan: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    public function getAllPoliklinik()
    {
        try {
            $stmt = $this->pdo->query("SELECT * FROM poliklinik ORDER BY nm_poli ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting poliklinik: " . $e->getMessage());
            return [];
        }
    }

    public function getAllDokter()
    {
        try {
            $stmt = $this->pdo->query("SELECT * FROM dokter ORDER BY Nama_Dokter ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting dokter: " . $e->getMessage());
            return [];
        }
    }

    public function generateNoRawat($tanggal)
    {
        try {
            error_log("Generating no_rawat for date: " . $tanggal);

            // Format: YYYYMMDD.nnn
            $prefix = date('Ymd', strtotime($tanggal));

            // Cari nomor urut terakhir untuk tanggal ini
            $stmt = $this->pdo->prepare("
                SELECT MAX(SUBSTRING_INDEX(no_rawat, '.', -1)) as last_num 
                FROM reg_periksa 
                WHERE no_rawat LIKE ?
            ");

            $pattern = $prefix . '.%';
            $stmt->execute([$pattern]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            error_log("Last number found: " . ($result['last_num'] ?? 'none'));

            // Jika belum ada nomor untuk hari ini, mulai dari 1
            // Jika sudah ada, increment nomor terakhir
            $next_num = $result['last_num'] ? ((int)$result['last_num'] + 1) : 1;

            // Format nomor dengan padding 3 digit
            $no_rawat = $prefix . '.' . str_pad($next_num, 3, '0', STR_PAD_LEFT);

            // Periksa apakah nomor rawat sudah ada
            $check_stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count
                FROM reg_periksa 
                WHERE no_rawat = ?
            ");
            $check_stmt->execute([$no_rawat]);
            $check_result = $check_stmt->fetch(PDO::FETCH_ASSOC);

            // Jika nomor rawat sudah ada, increment lagi sampai menemukan yang unik
            while ($check_result['count'] > 0) {
                $next_num++;
                $no_rawat = $prefix . '.' . str_pad($next_num, 3, '0', STR_PAD_LEFT);
                $check_stmt->execute([$no_rawat]);
                $check_result = $check_stmt->fetch(PDO::FETCH_ASSOC);
            }

            error_log("Generated no_rawat: " . $no_rawat);

            return $no_rawat;
        } catch (PDOException $e) {
            error_log("Error generating no_rawat: " . $e->getMessage());
            throw new Exception("Gagal generate nomor rawat");
        }
    }

    public function generateNoReg($tanggal)
    {
        try {
            error_log("Generating no_reg for date: " . $tanggal);

            // Cari nomor urut terakhir untuk tanggal ini
            $stmt = $this->pdo->prepare("
                SELECT MAX(CAST(no_reg AS UNSIGNED)) as last_num 
                FROM reg_periksa 
                WHERE tgl_registrasi = ?
            ");

            $stmt->execute([$tanggal]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            error_log("Last number found: " . ($result['last_num'] ?? 'none'));

            // Jika belum ada nomor untuk hari ini, mulai dari 1
            // Jika sudah ada, increment nomor terakhir
            $next_num = $result['last_num'] ? ((int)$result['last_num'] + 1) : 1;

            // Format nomor dengan padding 3 digit
            $no_reg = str_pad($next_num, 3, '0', STR_PAD_LEFT);
            error_log("Initial no_reg generated: " . $no_reg);

            // Periksa apakah nomor registrasi sudah ada
            $check_stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count
                FROM reg_periksa 
                WHERE tgl_registrasi = ? AND no_reg = ?
            ");
            $check_stmt->execute([$tanggal, $no_reg]);
            $check_result = $check_stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Check result for no_reg " . $no_reg . ": " . $check_result['count']);

            // Jika nomor registrasi sudah ada, increment lagi sampai menemukan yang unik
            $max_attempts = 100; // Batasi jumlah percobaan untuk menghindari infinite loop
            $attempt = 0;

            while ($check_result['count'] > 0 && $attempt < $max_attempts) {
                $attempt++;
                $next_num++;
                $no_reg = str_pad($next_num, 3, '0', STR_PAD_LEFT);
                error_log("Trying alternative no_reg (attempt " . $attempt . "): " . $no_reg);
                $check_stmt->execute([$tanggal, $no_reg]);
                $check_result = $check_stmt->fetch(PDO::FETCH_ASSOC);
                error_log("Check result for no_reg " . $no_reg . ": " . $check_result['count']);
            }

            if ($attempt >= $max_attempts) {
                error_log("WARNING: Reached maximum attempts to generate unique no_reg");
                // Tambahkan timestamp untuk memastikan keunikan
                $no_reg = $no_reg . date('His');
                error_log("Using timestamp-based no_reg: " . $no_reg);
            }

            error_log("Final generated no_reg: " . $no_reg);
            return $no_reg;
        } catch (PDOException $e) {
            error_log("Error generating no_reg: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw new Exception("Gagal generate nomor registrasi");
        }
    }

    public function tambahPemeriksaan($data)
    {
        try {
            error_log("=== MULAI PROSES TAMBAH PEMERIKSAAN ===");
            error_log("Data yang diterima: " . print_r($data, true));

            // Pastikan no_reg tidak null
            if (empty($data['no_reg'])) {
                // Generate no_reg baru jika null
                $no_reg = date('Ymd-His');
                error_log("No Registrasi kosong, generate baru: " . $no_reg);
            } else {
                $no_reg = $data['no_reg'];
                error_log("No Registrasi yang digunakan: " . $no_reg);
            }

            // Cek apakah kombinasi no_rawat sudah ada
            $check_stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count
                FROM reg_periksa 
                WHERE no_rawat = ?
            ");
            $check_stmt->execute([$data['no_rawat']]);
            $check_result = $check_stmt->fetch(PDO::FETCH_ASSOC);

            if ($check_result['count'] > 0) {
                error_log("No rawat sudah ada: " . $data['no_rawat']);
                throw new Exception("Nomor rawat sudah ada dalam database");
            }

            // Debug info
            error_log("No Reg sebelum insert: " . $no_reg);
            error_log("Tipe data no_reg: " . gettype($no_reg));
            error_log("Panjang no_reg: " . strlen($no_reg));

            // Insert data ke reg_periksa sesuai struktur tabel yang ada
            $stmt = $this->pdo->prepare("
                INSERT INTO reg_periksa (
                    no_reg,
                    no_rawat,
                    tgl_registrasi,
                    jam_reg,
                    no_rkm_medis,
                    status_bayar,
                    rincian
                ) VALUES (
                    :no_reg,
                    :no_rawat,
                    :tgl_registrasi,
                    :jam_reg,
                    :no_rkm_medis,
                    :status_bayar,
                    :rincian
                )
            ");

            $params = [
                ':no_reg' => $no_reg,
                ':no_rawat' => $data['no_rawat'],
                ':tgl_registrasi' => $data['tgl_registrasi'],
                ':jam_reg' => $data['jam_reg'],
                ':no_rkm_medis' => $data['no_rkm_medis'],
                ':status_bayar' => $data['status_bayar'],
                ':rincian' => $data['rincian'] ?? null
            ];

            error_log("Query parameters: " . print_r($params, true));

            $result = $stmt->execute($params);

            if (!$result) {
                $error_info = $stmt->errorInfo();
                error_log("Error executing query: " . print_r($error_info, true));
                throw new Exception("Gagal menyimpan data: " . $error_info[2]);
            }

            error_log("Pemeriksaan berhasil disimpan");
            return true;
        } catch (Exception $e) {
            error_log("Error adding pemeriksaan: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    public function getPdoStatus()
    {
        try {
            if (!$this->pdo) {
                return false;
            }

            // Coba jalankan query sederhana untuk memastikan koneksi masih aktif
            $this->pdo->query("SELECT 1");
            return true;
        } catch (PDOException $e) {
            error_log("PDO connection check failed: " . $e->getMessage());
            return false;
        }
    }

    // Fungsi untuk mendapatkan data riwayat kehamilan berdasarkan ID
    public function getRiwayatKehamilanById($id_riwayat_kehamilan)
    {
        try {
            // Pastikan koneksi database tersedia
            if (!isset($this->pdo) || !($this->pdo instanceof PDO)) {
                throw new PDOException("Koneksi database tidak tersedia di RekamMedis model");
            }

            $query = "SELECT * FROM riwayat_kehamilan WHERE id_riwayat_kehamilan = :id";
            $stmt = $this->pdo->prepare($query);

            if (!$stmt) {
                throw new PDOException("Error dalam mempersiapkan query");
            }

            $stmt->bindParam(':id', $id_riwayat_kehamilan, PDO::PARAM_STR);

            if (!$stmt->execute()) {
                throw new PDOException("Error dalam mengeksekusi query");
            }

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                throw new PDOException("Data riwayat kehamilan tidak ditemukan");
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Error in getRiwayatKehamilanById: " . $e->getMessage());
            throw $e;
        }
    }

    // Fungsi untuk menambah data riwayat kehamilan
    public function tambahRiwayatKehamilan($data)
    {
        try {
            // Generate UUID
            $uuid = $this->generateUUID();

            $query = "INSERT INTO riwayat_kehamilan (
                id_riwayat_kehamilan, 
                no_rkm_medis, 
                no_urut_kehamilan, 
                status_kehamilan, 
                jenis_persalinan, 
                tempat_persalinan, 
                penolong_persalinan, 
                tahun_persalinan, 
                jenis_kelamin_anak, 
                berat_badan_lahir, 
                kondisi_lahir, 
                komplikasi_kehamilan, 
                komplikasi_persalinan, 
                catatan
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )";

            $stmt = $this->pdo->prepare($query);

            $params = [
                $uuid,
                isset($data['no_rkm_medis']) && $data['no_rkm_medis'] !== '' ? $data['no_rkm_medis'] : null,
                isset($data['no_urut_kehamilan']) && $data['no_urut_kehamilan'] !== '' ? (int)$data['no_urut_kehamilan'] : null,
                isset($data['status_kehamilan']) && $data['status_kehamilan'] !== '' ? $data['status_kehamilan'] : null,
                isset($data['jenis_persalinan']) && $data['jenis_persalinan'] !== '' ? $data['jenis_persalinan'] : null,
                isset($data['tempat_persalinan']) && $data['tempat_persalinan'] !== '' ? $data['tempat_persalinan'] : null,
                isset($data['penolong_persalinan']) && $data['penolong_persalinan'] !== '' ? $data['penolong_persalinan'] : null,
                isset($data['tahun_persalinan']) && $data['tahun_persalinan'] !== '' ? (int)$data['tahun_persalinan'] : null,
                isset($data['jenis_kelamin_anak']) && $data['jenis_kelamin_anak'] !== '' ? $data['jenis_kelamin_anak'] : null,
                isset($data['berat_badan_lahir']) && $data['berat_badan_lahir'] !== '' ? (int)$data['berat_badan_lahir'] : null,
                isset($data['kondisi_lahir']) && $data['kondisi_lahir'] !== '' ? $data['kondisi_lahir'] : null,
                isset($data['komplikasi_kehamilan']) && $data['komplikasi_kehamilan'] !== '' ? $data['komplikasi_kehamilan'] : null,
                isset($data['komplikasi_persalinan']) && $data['komplikasi_persalinan'] !== '' ? $data['komplikasi_persalinan'] : null,
                isset($data['catatan']) && $data['catatan'] !== '' ? $data['catatan'] : null
            ];

            // Debugging
            error_log("Query parameters for tambahRiwayatKehamilan: " . print_r($params, true));

            $stmt->execute($params);

            return $uuid;
        } catch (PDOException $e) {
            error_log("Error adding riwayat kehamilan: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['error_message'] = 'DB ERROR: ' . $e->getMessage();
            return false;
        }
    }

    // Fungsi untuk mengupdate data riwayat kehamilan
    public function updateRiwayatKehamilan($data)
    {
        try {
            $query = "UPDATE riwayat_kehamilan SET 
                no_urut_kehamilan = ?, 
                status_kehamilan = ?, 
                jenis_persalinan = ?, 
                tempat_persalinan = ?, 
                penolong_persalinan = ?, 
                tahun_persalinan = ?, 
                jenis_kelamin_anak = ?, 
                berat_badan_lahir = ?, 
                kondisi_lahir = ?, 
                komplikasi_kehamilan = ?, 
                komplikasi_persalinan = ?, 
                catatan = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id_riwayat_kehamilan = ?";

            $stmt = $this->pdo->prepare($query);

            $params = [
                $data['no_urut_kehamilan'],
                $data['status_kehamilan'],
                $data['jenis_persalinan'] ?? null,
                $data['tempat_persalinan'] ?? null,
                $data['penolong_persalinan'] ?? null,
                $data['tahun_persalinan'] ?? null,
                $data['jenis_kelamin_anak'] ?? null,
                $data['berat_badan_lahir'] ?? null,
                $data['kondisi_lahir'] ?? null,
                $data['komplikasi_kehamilan'] ?? null,
                $data['komplikasi_persalinan'] ?? null,
                $data['catatan'] ?? null,
                $data['id_riwayat_kehamilan']
            ];

            // Debugging
            error_log("Query parameters for updateRiwayatKehamilan: " . print_r($params, true));

            $stmt->execute($params);

            return true;
        } catch (PDOException $e) {
            error_log("Error updating riwayat kehamilan: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    // Fungsi untuk menghapus data riwayat kehamilan
    public function hapusRiwayatKehamilan($id_riwayat_kehamilan)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM riwayat_kehamilan WHERE id_riwayat_kehamilan = ?");
            $stmt->execute([$id_riwayat_kehamilan]);
            return true;
        } catch (PDOException $e) {
            error_log("Error deleting riwayat kehamilan: " . $e->getMessage());
            return false;
        }
    }

    public function updateStatusBayar($no_rawat)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE reg_periksa SET status_bayar = 'Sudah Bayar' WHERE no_rawat = ?");
            return $stmt->execute([$no_rawat]);
        } catch (PDOException $e) {
            error_log("Error updating status bayar: " . $e->getMessage());
            return false;
        }
    }

    public function getNoRkmMedisByNoRawat($no_rawat)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT no_rkm_medis FROM reg_periksa WHERE no_rawat = ?");
            $stmt->execute([$no_rawat]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error in getNoRkmMedisByNoRawat: " . $e->getMessage());
            return false;
        }
    }
}
