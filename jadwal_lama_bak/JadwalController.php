<?php
// JadwalController.php
// Controller untuk manajemen jadwal praktek dokter

require_once __DIR__ . '/../../config/database.php';
// Tambahkan require model/helper jika ada

class JadwalController {
    private $conn;
    public function __construct($conn = null) {
        if ($conn) {
            $this->conn = $conn;
        } else {
            global $conn;
            $this->conn = $conn;
        }
    }

    public function handle() {
        $action = $_REQUEST['action'] ?? 'index';
        switch ($action) {
            case 'form':
                $this->showForm();
                break;
            default:
                $this->index();
        }
    }

    public function index() {
        // Ambil data tempat praktek
        $tempat_praktek = [];
        try {
            $query = "SELECT * FROM tempat_praktek WHERE Status_Aktif = 1 ORDER BY Nama_Tempat ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $tempat_praktek = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
        }

        // Ambil data dokter
        $dokter = [];
        try {
            $query = "SELECT * FROM dokter WHERE Status_Aktif = 1 ORDER BY Nama_Dokter ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $dokter = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
        }

        // Ambil jadwal rutin (filter sesuai request)
        $id_tempat_praktek = $_GET['tempat'] ?? '';
        $id_dokter = $_GET['dokter'] ?? '';
        $hari = $_GET['hari'] ?? '';

        $hari_names = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
        ];
        if (in_array($hari, array_keys($hari_names))) {
            $hari = $hari_names[$hari];
        }

        $jadwal_rutin = [];
        try {
            $query = "SELECT jr.*, tp.Nama_Tempat, tp.Alamat_Lengkap, tp.Kota, tp.Jenis_Fasilitas, d.Nama_Dokter, d.Spesialisasi, d.Nomor_SIP
                      FROM jadwal_rutin jr
                      LEFT JOIN tempat_praktek tp ON jr.ID_Tempat_Praktek = tp.ID_Tempat_Praktek
                      LEFT JOIN dokter d ON jr.ID_Dokter = d.ID_Dokter
                      WHERE jr.Status_Aktif = 1";
            $params = [];
            if (!empty($id_tempat_praktek)) {
                $query .= " AND jr.ID_Tempat_Praktek = :id_tempat_praktek";
                $params[':id_tempat_praktek'] = $id_tempat_praktek;
            }
            if (!empty($id_dokter)) {
                $query .= " AND jr.ID_Dokter = :id_dokter";
                $params[':id_dokter'] = $id_dokter;
            }
            if (!empty($hari)) {
                $query .= " AND jr.Hari = :hari";
                $params[':hari'] = $hari;
            }
            $query .= " ORDER BY tp.Nama_Tempat, d.Nama_Dokter, FIELD(jr.Hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'), jr.Jam_Mulai ASC";
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->execute();
            $jadwal_rutin = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
        }

        // Render view jadwal/index.php
        include __DIR__ . '/../views/jadwal/index.php';
    }

    public function showForm() {
        // Data untuk form tambah/edit jadwal
        $tempat_praktek = [];
        $dokter = [];
        try {
            $stmt = $this->conn->prepare("SELECT * FROM tempat_praktek WHERE Status_Aktif = 1 ORDER BY Nama_Tempat ASC");
            $stmt->execute();
            $tempat_praktek = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt = $this->conn->prepare("SELECT * FROM dokter WHERE Status_Aktif = 1 ORDER BY Nama_Dokter ASC");
            $stmt->execute();
            $dokter = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
        }
        // Render view jadwal/form.php
        include __DIR__ . '/../views/jadwal/form.php';
    }
}
