<?php
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../helpers/SessionHelper.php';

class AntrianController {
    public function handle() {
        if (function_exists('session_status')) {
            if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        } else {
            if (!headers_sent()) @session_start();
        }
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method === 'POST') {
            $this->processPost();
        } else {
            $this->showAntrian();
        }
    }

    private function showAntrian() {
        $conn = getPDOConnection(); // Get PDO connection from koneksi.php
        $page_title = 'Daftar Praktekobgin';
        $is_logged_in = isset($_SESSION['user_id']);
        $id_tempat_praktek = $_GET['tempat'] ?? '';
        $id_dokter = $_GET['dokter'] ?? '';
        $hari = $_GET['hari'] ?? '';
        $error_message = '';

        // Data tempat praktek
        try {
            $query_tempat = "SELECT ID_Tempat_Praktek, Nama_Tempat FROM tempat_praktek WHERE Status_Aktif = 1";
            $stmt_tempat = $conn->prepare($query_tempat);
            $stmt_tempat->execute();
            $tempat_praktek = $stmt_tempat->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error: " . $e->getMessage());
            $tempat_praktek = [];
        }
        // Data dokter
        try {
            $query_dokter = "SELECT ID_Dokter, Nama_Dokter FROM dokter WHERE Status_Aktif = 1";
            $stmt_dokter = $conn->prepare($query_dokter);
            $stmt_dokter->execute();
            $dokter = $stmt_dokter->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error: " . $e->getMessage());
            $dokter = [];
        }
        // Data antrian
        $antrian = [];
        try {
            $query = "
                SELECT 
                    p.ID_Pendaftaran,
                    p.nm_pasien,
                    p.Status_Pendaftaran,
                    p.Waktu_Perkiraan,
                    jr.Hari,
                    jr.Jam_Mulai,
                    jr.Jam_Selesai,
                    jr.Jenis_Layanan,
                    tp.Nama_Tempat,
                    d.Nama_Dokter,
                    p.Waktu_Pendaftaran,
                    p.updatedAt
                FROM 
                    pendaftaran p
                JOIN 
                    jadwal_rutin jr ON p.ID_Jadwal = jr.ID_Jadwal_Rutin
                JOIN 
                    tempat_praktek tp ON p.ID_Tempat_Praktek = tp.ID_Tempat_Praktek
                JOIN 
                    dokter d ON p.ID_Dokter = d.ID_Dokter
                WHERE (p.Status_Pendaftaran NOT IN ('Dibatalkan', 'Selesai')
                      OR (p.Status_Pendaftaran = 'Selesai' AND DATE(p.updatedAt) = CURRENT_DATE()))
            ";
            $params = [];
            if (!empty($hari)) {
                $query .= " AND jr.Hari = :hari";
                $params[':hari'] = $hari;
            }
            if (!empty($id_tempat_praktek)) {
                $query .= " AND p.ID_Tempat_Praktek = :tempat";
                $params[':tempat'] = $id_tempat_praktek;
            }
            if (!empty($id_dokter)) {
                $query .= " AND p.ID_Dokter = :dokter";
                $params[':dokter'] = $id_dokter;
            }
            $query .= " ORDER BY 
                CASE jr.Hari
                    WHEN 'Senin' THEN 1
                    WHEN 'Selasa' THEN 2
                    WHEN 'Rabu' THEN 3
                    WHEN 'Kamis' THEN 4
                    WHEN 'Jumat' THEN 5
                    WHEN 'Sabtu' THEN 6
                    WHEN 'Minggu' THEN 7
                END ASC,
                jr.Jam_Mulai ASC,
                p.Waktu_Perkiraan ASC";
            $stmt = $conn->prepare($query);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->execute();
            $antrian = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error: " . $e->getMessage());
            $antrian = [];
            $error_message = 'Gagal mengambil data antrian.';
        }
        require __DIR__ . '/../../views/pendaftaran/daftar_antrian.php';
    }

    private function processPost() {
        try {
            // Get database connection
            $conn = getPDOConnection();
            
            // Add your POST logic here if needed
            // For now, just show the antrian
            $this->showAntrian();
        } catch (PDOException $e) {
            error_log("Database Error in processPost: " . $e->getMessage());
            $_SESSION['error'] = 'Gagal memproses permintaan. Silakan coba lagi.';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }
}
