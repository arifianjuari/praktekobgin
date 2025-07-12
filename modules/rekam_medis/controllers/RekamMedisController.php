<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'modules/rekam_medis/models/RekamMedis.php';
require_once 'modules/rekam_medis/models/TindakanMedis.php';
require_once 'modules/rekam_medis/models/StatusGinekologi.php';
require_once 'modules/rekam_medis/models/TemplateTatalaksana.php';
require_once 'modules/rekam_medis/models/TemplateUsg.php';
require_once 'modules/rekam_medis/models/Surat.php';
// Atensi model is not available yet, so we're removing the include
// require_once 'modules/rekam_medis/models/Atensi.php';
require_once 'modules/rekam_medis/models/TemplateAnamnesis.php';
require_once 'modules/rekam_medis/includes/redirect_helper.php';

class RekamMedisController
{
    private $rekamMedisModel;
    private $tindakanMedisModel;
    private $templateTatalaksanaModel;
    private $templateUsgModel;
    // Atensi model is not available yet, so we're removing the property
    // private $atensiModel;
    private $templateAnamnesisModel;
    private $templateCeklistModel; // Property for Template Ceklist model
    private $pdo;
    private $conn;

    public function __construct($conn)
    {
        global $conn;
        $this->conn = $conn;
        try {
            // Jika koneksi tidak valid, load dari config dan gunakan $conn global
            if (!isset($conn) || !($conn instanceof PDO)) {
                require_once __DIR__ . '/../../../config/database.php';
                // $conn sudah otomatis terisi dari config/database.php
            }

            // Test koneksi
            $test = $conn->query("SELECT 1");
            if (!$test) {
                throw new PDOException("Koneksi database tidak dapat melakukan query");
            }

            $this->pdo = $conn;
            $this->rekamMedisModel = new RekamMedis($conn);
            $this->tindakanMedisModel = new TindakanMedis($conn);
            $this->templateTatalaksanaModel = new TemplateTatalaksana($conn);

            // Inisialisasi model template anamnesis dan ceklist
            require_once 'modules/rekam_medis/models/TemplateAnamnesis.php';
            $this->templateAnamnesisModel = new TemplateAnamnesis($conn);

            // Inisialisasi model template ceklist jika file ada
            $templateCeklistPath = 'modules/rekam_medis/models/TemplateCeklist.php';
            if (file_exists($templateCeklistPath)) {
                require_once $templateCeklistPath;
                $this->templateCeklistModel = new TemplateCeklist($conn);
            }
            $this->templateUsgModel = new TemplateUsg($conn);
            // Atensi model is not available yet, so we're removing the initialization
            // $this->atensiModel = new Atensi($conn);
            $this->templateAnamnesisModel = new TemplateAnamnesis($conn);
        } catch (PDOException $e) {
            error_log("Database Error in RekamMedisController constructor: " . $e->getMessage());
            throw new Exception("Koneksi database bermasalah: " . $e->getMessage());
        }
    }

    public function index()
    {
        // Alihkan ke halaman data pasien sebagai halaman utama
        header('Location: index.php?module=rekam_medis&action=data_pasien');
        exit;
    }

    public function dataPasien()
    {
        // Debugging
        echo "<!-- Debug: dataPasien() function called -->";

        // Inisialisasi variabel pencarian
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10; // Jumlah data per halaman
        $offset = ($page - 1) * $limit;

        try {
            // Query untuk menghitung total data
            $count_query = "SELECT COUNT(*) FROM pasien";
            if (!empty($search)) {
                $count_query .= " WHERE no_rkm_medis LIKE :search1 OR nm_pasien LIKE :search2 OR no_ktp LIKE :search3";
            }

            $count_stmt = $this->pdo->prepare($count_query);
            if (!empty($search)) {
                $searchTerm = "%$search%";
                $count_stmt->bindValue(':search1', $searchTerm, PDO::PARAM_STR);
                $count_stmt->bindValue(':search2', $searchTerm, PDO::PARAM_STR);
                $count_stmt->bindValue(':search3', $searchTerm, PDO::PARAM_STR);
            }
            $count_stmt->execute();
            $total_records = $count_stmt->fetchColumn();

            // Hitung total halaman
            $total_pages = ceil($total_records / $limit);

            // Query untuk mengambil data pasien
            $query = "SELECT * FROM pasien";
            if (!empty($search)) {
                $query .= " WHERE no_rkm_medis LIKE :search1 OR nm_pasien LIKE :search2 OR no_ktp LIKE :search3";
            }
            $query .= " ORDER BY nm_pasien ASC LIMIT :limit OFFSET :offset";

            $stmt = $this->pdo->prepare($query);
            if (!empty($search)) {
                $searchTerm = "%$search%";
                $stmt->bindValue(':search1', $searchTerm, PDO::PARAM_STR);
                $stmt->bindValue(':search2', $searchTerm, PDO::PARAM_STR);
                $stmt->bindValue(':search3', $searchTerm, PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $pasien = $stmt->fetchAll();

            // Debugging
            echo "<!-- Debug: Total pasien: " . count($pasien) . " -->";
        } catch (PDOException $e) {
            // Debugging
            echo "<!-- Debug: Error: " . $e->getMessage() . " -->";

            $_SESSION['error'] = "Error: " . $e->getMessage();
            $pasien = [];
            $total_pages = 0;
        }

        // Data wilayah statis
        $kecamatan = [
            ['kd_kec' => '1', 'nm_kec' => 'Batu'],
            ['kd_kec' => '2', 'nm_kec' => 'Bumiaji'],
            ['kd_kec' => '3', 'nm_kec' => 'Junrejo'],
            ['kd_kec' => '4', 'nm_kec' => 'Pujon'],
            ['kd_kec' => '5', 'nm_kec' => 'Ngantang'],
            ['kd_kec' => '6', 'nm_kec' => 'Lainnya']
        ];

        $kelurahan = [
            ['kd_kel' => '1', 'nm_kel' => 'Sisir'],
            ['kd_kel' => '2', 'nm_kel' => 'Temas'],
            ['kd_kel' => '3', 'nm_kel' => 'Ngaglik'],
            ['kd_kel' => '4', 'nm_kel' => 'Songgokerto'],
            ['kd_kel' => '5', 'nm_kel' => 'Lainnya']
        ];

        $kabupaten = [
            ['kd_kab' => '1', 'nm_kab' => 'Kota Batu'],
            ['kd_kab' => '2', 'nm_kab' => 'Kota Malang'],
            ['kd_kab' => '3', 'nm_kab' => 'Kabupaten Malang'],
            ['kd_kab' => '4', 'nm_kab' => 'Lainnya']
        ];

        $cara_bayar = [
            ['kd_pj' => 'UMU', 'nm_pj' => 'Umum'],
            ['kd_pj' => 'BPJ', 'nm_pj' => 'BPJS'],
            ['kd_pj' => 'ASR', 'nm_pj' => 'Asuransi'],
            ['kd_pj' => 'KOR', 'nm_pj' => 'Korporasi']
        ];

        include 'modules/rekam_medis/views/data_pasien.php';
    }

    public function cariPasien()
    {
        $keyword = $_POST['keyword'] ?? '';

        if (empty($keyword)) {
            header('Location: index.php?module=rekam_medis&action=data_pasien');
            exit;
        }

        // Redirect ke halaman data pasien dengan parameter pencarian
        header('Location: index.php?module=rekam_medis&action=data_pasien&search=' . urlencode($keyword));
        exit;
    }

    public function hapusPasien()
    {
        error_log("=== Mulai proses hapusPasien ===");

        $no_rkm_medis = $_POST['no_rkm_medis'] ?? '';
        error_log("No RM yang akan dihapus: " . $no_rkm_medis);

        if (empty($no_rkm_medis)) {
            $_SESSION['error'] = 'Parameter ID pasien tidak ditemukan';
            error_log("Error: Parameter ID pasien kosong");
            header('Location: index.php?module=rekam_medis&action=data_pasien');
            exit;
        }

        try {
            // Buat koneksi langsung ke database
            require_once __DIR__ . '/../../../config/database.php';



            $pdo = new PDO(
                "mysql:host=$db2_host;dbname=$db2_database;charset=utf8mb4",
                $db2_username,
                $db2_password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );

            error_log("Koneksi database berhasil dibuat");

            // Cek apakah data pasien ada
            $check_exist_query = "SELECT COUNT(*) FROM pasien WHERE no_rkm_medis = :no_rkm_medis";
            $check_exist_stmt = $pdo->prepare($check_exist_query);
            $check_exist_stmt->bindParam(':no_rkm_medis', $no_rkm_medis, PDO::PARAM_STR);
            $check_exist_stmt->execute();
            $exists = $check_exist_stmt->fetchColumn();

            if ($exists == 0) {
                error_log("Data pasien tidak ditemukan di database");
                $_SESSION['error'] = 'Data pasien tidak ditemukan';
                header('Location: index.php?module=rekam_medis&action=data_pasien');
                exit;
            }

            error_log("Data pasien ditemukan, melanjutkan proses");

            // Hapus data pasien
            $delete_query = "DELETE FROM pasien WHERE no_rkm_medis = :no_rkm_medis";
            error_log("Query hapus: " . $delete_query);
            $delete_stmt = $pdo->prepare($delete_query);
            $delete_stmt->bindParam(':no_rkm_medis', $no_rkm_medis, PDO::PARAM_STR);
            $result = $delete_stmt->execute();
            error_log("Hasil eksekusi query hapus: " . ($result ? "Berhasil" : "Gagal"));

            if ($result) {
                $rows_affected = $delete_stmt->rowCount();
                error_log("Jumlah baris yang dihapus: " . $rows_affected);

                if ($rows_affected > 0) {
                    error_log("Data berhasil dihapus");
                    $_SESSION['success'] = 'Data pasien berhasil dihapus';
                } else {
                    error_log("Tidak ada baris yang dihapus");
                    $_SESSION['error'] = 'Tidak ada data pasien yang dihapus';
                }
            } else {
                $errorInfo = $delete_stmt->errorInfo();
                error_log("Query gagal: " . print_r($errorInfo, true));
                $_SESSION['error'] = 'Gagal menghapus data pasien. Error: ' . $errorInfo[2];
            }
        } catch (PDOException $e) {
            error_log("Database Error in hapusPasien: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $_SESSION['error'] = 'Gagal menghapus data pasien: ' . $e->getMessage();
        }

        error_log("=== Selesai proses hapusPasien ===");
        error_log("Redirect ke: index.php?module=rekam_medis&action=data_pasien");
        header('Location: index.php?module=rekam_medis&action=data_pasien');
        exit;
    }

    public function tambahPasien()
    {
        // Data wilayah statis
        $kecamatan = [
            ['kd_kec' => '1', 'nm_kec' => 'Batu'],
            ['kd_kec' => '2', 'nm_kec' => 'Bumiaji'],
            ['kd_kec' => '3', 'nm_kec' => 'Junrejo'],
            ['kd_kec' => '4', 'nm_kec' => 'Pujon'],
            ['kd_kec' => '5', 'nm_kec' => 'Ngantang'],
            ['kd_kec' => '6', 'nm_kec' => 'Lainnya']
        ];

        $kelurahan = [
            ['kd_kel' => '1', 'nm_kel' => 'Sisir'],
            ['kd_kel' => '2', 'nm_kel' => 'Temas'],
            ['kd_kel' => '3', 'nm_kel' => 'Ngaglik'],
            ['kd_kel' => '4', 'nm_kel' => 'Songgokerto'],
            ['kd_kel' => '5', 'nm_kel' => 'Lainnya']
        ];

        $kabupaten = [
            ['kd_kab' => '1', 'nm_kab' => 'Kota Batu'],
            ['kd_kab' => '2', 'nm_kab' => 'Kota Malang'],
            ['kd_kab' => '3', 'nm_kab' => 'Kabupaten Malang'],
            ['kd_kab' => '4', 'nm_kab' => 'Lainnya']
        ];

        $cara_bayar = [
            ['kd_pj' => 'UMU', 'nm_pj' => 'Umum'],
            ['kd_pj' => 'BPJ', 'nm_pj' => 'BPJS'],
            ['kd_pj' => 'ASR', 'nm_pj' => 'Asuransi'],
            ['kd_pj' => 'KOR', 'nm_pj' => 'Korporasi']
        ];

        include 'modules/rekam_medis/views/form_tambah_pasien.php';
    }

    public function simpanPasien()
    {
        // Ambil data dari form
        $nm_pasien = $_POST['nm_pasien'] ?? '';
        $jk = $_POST['jk'] ?? '';
        $tgl_lahir = $_POST['tgl_lahir'] ?? '';
        $alamat = $_POST['alamat'] ?? '';
        $pekerjaan = $_POST['pekerjaan'] ?? '';
        $no_tlp = $_POST['no_tlp'] ?? '';
        $no_ktp = $_POST['no_ktp'] ?? '';
        $kd_kec = $_POST['kd_kec'] ?? '';
        $umur = $_POST['umur'] ?? '';
        $tgl_daftar = $_POST['tgl_daftar'] ?? date('Y-m-d H:i:s');
        $catatan_pasien = $_POST['catatan_pasien'] ?? '';

        // Validasi data
        if (empty($nm_pasien) || empty($jk) || empty($tgl_lahir)) {
            $_SESSION['error'] = 'Data pasien tidak lengkap';
            header('Location: index.php?module=rekam_medis&action=tambah_pasien');
            exit;
        }

        // Cek apakah NIK sudah ada
        if (!empty($no_ktp)) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM pasien WHERE no_ktp = ?");
            $stmt->execute([$no_ktp]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $_SESSION['error'] = 'NIK sudah terdaftar dalam database';
                header('Location: index.php?module=rekam_medis&action=tambah_pasien');
                exit;
            }
        }

        try {
            // Generate nomor rekam medis dengan format RM-YYYYMMDD-XXX
            $tanggal_sekarang = date('Ymd');

            // Cari nomor urut terakhir dari semua nomor rekam medis
            $stmt = $this->pdo->prepare("SELECT MAX(CAST(SUBSTRING_INDEX(no_rkm_medis, '-', -1) AS UNSIGNED)) as max_id FROM pasien");
            $stmt->execute();
            $result = $stmt->fetch();

            // Jika belum ada nomor rekam medis, mulai dari 001
            // Jika sudah ada, increment nomor terakhir
            $next_id = (empty($result['max_id']) || $result['max_id'] === null) ? 1 : (int)$result['max_id'] + 1;
            $no_rkm_medis = "RM-" . $tanggal_sekarang . "-" . $next_id;

            // Simpan data pasien
            $stmt = $this->pdo->prepare("
                INSERT INTO pasien (
                    no_rkm_medis, nm_pasien, jk, tgl_lahir, alamat, pekerjaan, 
                    no_tlp, umur, kd_kec, tgl_daftar, no_ktp, catatan_pasien
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
            ");

            $stmt->execute([
                $no_rkm_medis,
                $nm_pasien,
                $jk,
                $tgl_lahir,
                $alamat,
                $pekerjaan,
                $no_tlp,
                $umur,
                $kd_kec,
                $tgl_daftar,
                $no_ktp,
                $catatan_pasien
            ]);

            $_SESSION['success'] = 'Data pasien berhasil ditambahkan';
            header('Location: index.php?module=rekam_medis&action=data_pasien');
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Gagal menambahkan data pasien: ' . $e->getMessage();
            header('Location: index.php?module=rekam_medis&action=tambah_pasien');
        }
        exit;
    }

    public function cekNikPasien()
    {
        // Pastikan request adalah AJAX
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
            http_response_code(403);
            echo json_encode(['error' => 'Akses ditolak']);
            exit;
        }

        // Ambil NIK dari request
        $nik = $_POST['nik'] ?? '';

        if (empty($nik)) {
            echo json_encode(['status' => 'empty']);
            exit;
        }

        try {
            // Cek apakah NIK sudah ada di database
            $stmt = $this->pdo->prepare("SELECT no_rkm_medis, nm_pasien FROM pasien WHERE no_ktp = ?");
            $stmt->execute([$nik]);
            $result = $stmt->fetch();

            if ($result) {
                // NIK sudah ada
                echo json_encode([
                    'status' => 'exists',
                    'message' => 'NIK sudah terdaftar dengan nomor RM: ' . $result['no_rkm_medis'] . ' atas nama ' . $result['nm_pasien']
                ]);
            } else {
                // NIK belum ada
                echo json_encode(['status' => 'not_exists']);
            }
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
        exit;
    }

    public function updatePasien()
    {
        // Validasi data
        $no_rkm_medis = $_POST['no_rkm_medis'] ?? '';

        if (empty($no_rkm_medis)) {
            $_SESSION['error'] = 'Parameter ID pasien tidak ditemukan';
            header('Location: index.php?module=rekam_medis');
            exit;
        }

        // Hitung umur berdasarkan tanggal lahir
        $tgl_lahir = $_POST['tgl_lahir'] ?? '';
        $umur = $_POST['umur'] ?? '';
        if (!empty($tgl_lahir) && empty($umur)) {
            $birthDate = new DateTime($tgl_lahir);
            $today = new DateTime();
            $diff = $today->diff($birthDate);
            $umur = $diff->y . " Th";
        }

        // Cek apakah ini update ceklist saja, catatan_pasien saja, atau update data pasien lengkap
        $isCeklistUpdateOnly = isset($_POST['ceklist']) && count($_POST) <= 2; // Hanya no_rkm_medis dan ceklist
        $isCatatanOnly = isset($_POST['catatan_pasien']) && count($_POST) <= 2 && !isset($_POST['ceklist']); // Hanya no_rkm_medis dan catatan_pasien

        if ($isCeklistUpdateOnly) {
            // Jika hanya update ceklist, ambil data pasien yang ada dan update hanya field ceklist
            $existingPasien = $this->rekamMedisModel->getPasienById($no_rkm_medis);
            if (!$existingPasien) {
                echo json_encode(['status' => 'error', 'message' => 'Data pasien tidak ditemukan']);
                exit;
            }

            $data = [
                'ceklist' => $_POST['ceklist'] ?? ''
            ];

            error_log("Update ceklist only for patient ID: $no_rkm_medis");
            try {
                $result = $this->rekamMedisModel->updatePasien($no_rkm_medis, $data);
                if ($result) {
                    echo json_encode(['status' => 'success', 'message' => 'Ceklist berhasil diperbarui']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui ceklist']);
                }
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
            }
            exit;
        } elseif ($isCatatanOnly) {
            // Jika hanya update catatan_pasien, ambil data pasien yang ada dan update hanya field catatan_pasien
            $existingPasien = $this->rekamMedisModel->getPasienById($no_rkm_medis);
            if (!$existingPasien) {
                echo json_encode(['status' => 'error', 'message' => 'Data pasien tidak ditemukan']);
                exit;
            }
            $data = [
                'catatan_pasien' => $_POST['catatan_pasien'] ?? ''
            ];
            error_log("Update catatan_pasien only for patient ID: $no_rkm_medis");
            try {
                $result = $this->rekamMedisModel->updatePasien($no_rkm_medis, $data);
                if ($result) {
                    echo json_encode(['status' => 'success', 'message' => 'Catatan pasien berhasil diperbarui']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui catatan pasien']);
                }
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
            }
            exit;
        } else {
            // Update data pasien lengkap
            $data = [
                'nm_pasien' => $_POST['nm_pasien'] ?? '',
                'jk' => $_POST['jk'] ?? '',
                'tgl_lahir' => $tgl_lahir,
                'umur' => $umur,
                'alamat' => $_POST['alamat'] ?? '',
                'kd_kec' => $_POST['kd_kec'] ?? '',
                'no_tlp' => $_POST['no_tlp'] ?? '',
                'pekerjaan' => $_POST['pekerjaan'] ?? '',
                'no_ktp' => $_POST['no_ktp'] ?? '',
                'stts_nikah' => $_POST['stts_nikah'] ?? '',
                'catatan_pasien' => $_POST['catatan_pasien'] ?? '',
                'ceklist' => $_POST['ceklist'] ?? ''
            ];

            error_log("Update full patient data for ID: $no_rkm_medis");
        }

        try {
            // Update data pasien
            $result = $this->rekamMedisModel->updatePasien($no_rkm_medis, $data);

            if ($result) {
                $_SESSION['success'] = 'Data pasien berhasil diperbarui';
            } else {
                $_SESSION['error'] = 'Gagal memperbarui data pasien';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Terjadi kesalahan: ' . $e->getMessage();
        }

        // Redirect ke halaman detail pasien dengan parameter refresh dan waktu untuk memastikan cache browser tidak digunakan
        header('Location: index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=' . $no_rkm_medis . '&refresh=1&t=' . time());
        exit;
    }

    public function detailPasien($no_rkm_medis)
    {
        try {
            // Log untuk debugging
            error_log("detailPasien called for no_rkm_medis: " . $no_rkm_medis);

            // Pastikan koneksi database menggunakan kredensial yang benar
            global $db2_host, $db2_username, $db2_password, $db2_database;
            error_log("Using database: $db2_host, $db2_database");

            // Cek koneksi database yang digunakan model
            error_log("Model PDO connection: " . ($this->rekamMedisModel->getPdoStatus() ? "Connected" : "Not connected"));

            $pasien = $this->rekamMedisModel->getPasienById($no_rkm_medis);
            if (!$pasien) {
                error_log("Pasien tidak ditemukan: " . $no_rkm_medis);
                echo "<div class='alert alert-danger'>Data pasien tidak ditemukan</div>";
                return;
            }

            error_log("Pasien ditemukan: " . json_encode($pasien));

            // Data wilayah statis
            $kecamatan = [
                ['kd_kec' => '1', 'nm_kec' => 'Batu'],
                ['kd_kec' => '2', 'nm_kec' => 'Bumiaji'],
                ['kd_kec' => '3', 'nm_kec' => 'Junrejo'],
                ['kd_kec' => '4', 'nm_kec' => 'Pujon'],
                ['kd_kec' => '5', 'nm_kec' => 'Ngantang'],
                ['kd_kec' => '6', 'nm_kec' => 'Lainnya']
            ];

            $kabupaten = [
                ['kd_kab' => '1', 'nm_kab' => 'Kota Batu'],
                ['kd_kab' => '2', 'nm_kab' => 'Kota Malang'],
                ['kd_kab' => '3', 'nm_kab' => 'Kabupaten Malang'],
                ['kd_kab' => '4', 'nm_kab' => 'Lainnya']
            ];

            // Ambil riwayat pemeriksaan
            $riwayatPemeriksaan = $this->rekamMedisModel->getRiwayatPemeriksaan($no_rkm_medis);
            error_log("Riwayat pemeriksaan count in controller: " . count($riwayatPemeriksaan));

            // Jika tidak ada riwayat pemeriksaan, coba ambil langsung dari database
            if (empty($riwayatPemeriksaan)) {
                error_log("No records found via model, trying direct database query");
                try {
                    // Buat koneksi langsung ke database
                    $directPdo = new PDO(
                        "mysql:host=$db2_host;dbname=$db2_database;charset=utf8mb4",
                        $db2_username,
                        $db2_password,
                        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                    );

                    // Query langsung ke tabel reg_periksa
                    $stmt = $directPdo->prepare("
                        SELECT * FROM reg_periksa 
                        WHERE no_rkm_medis = ? 
                        ORDER BY tgl_registrasi DESC, jam_reg DESC
                    ");
                    $stmt->execute([$no_rkm_medis]);
                    $riwayatPemeriksaan = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    error_log("Direct query found " . count($riwayatPemeriksaan) . " records");
                } catch (PDOException $e) {
                    error_log("Error in direct database query: " . $e->getMessage());
                }
            }

            // Data lainnya tetap sama
            $skriningKehamilan = $this->rekamMedisModel->getSkriningKehamilan($no_rkm_medis);
            $riwayatKehamilan = $this->rekamMedisModel->getRiwayatKehamilan($no_rkm_medis);
            $statusObstetri = $this->rekamMedisModel->getStatusObstetri($no_rkm_medis);

            // Memuat data status ginekologi
            $statusGinekologiModel = new StatusGinekologi($this->pdo);
            $statusGinekologi = $statusGinekologiModel->getStatusGinekologiByPasien($no_rkm_medis);

            $riwayatPenilaianRalan = $this->rekamMedisModel->getRiwayatPenilaianMedis($no_rkm_medis);
            $riwayatPemeriksaanObstetri = $this->rekamMedisModel->getRiwayatPemeriksaanObstetri($no_rkm_medis);
            $riwayatPemeriksaanGinekologi = $this->rekamMedisModel->getRiwayatPemeriksaanGinekologi($no_rkm_medis);

            // Tampilkan view dengan path yang benar
            error_log("Mencoba menampilkan view detail_pasien.php");
            include 'modules/rekam_medis/views/detail_pasien.php';
            error_log("View detail_pasien.php berhasil ditampilkan");
        } catch (Exception $e) {
            error_log("Error di detailPasien: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
            echo "<a href='index.php?module=rekam_medis' class='btn btn-primary'>Kembali</a>";
        }
    }

    public function tambahTindakanMedis()
    {
        // Ambil daftar dokter
        $stmt = $this->pdo->prepare("SELECT * FROM dokter WHERE Status_Aktif = 1");
        $stmt->execute();
        $dokter = $stmt->fetchAll();

        include 'modules/rekam_medis/views/form_tindakan_medis.php';
    }

    public function simpanTindakanMedis()
    {
        $no_rkm_medis = $_POST['no_rkm_medis'] ?? '';
        $ID_Dokter = $_POST['ID_Dokter'] ?? '';
        $tgl_tindakan = $_POST['tgl_tindakan'] ?? date('Y-m-d');
        $jam_tindakan = $_POST['jam_tindakan'] ?? date('H:i:s');
        $kode_tindakan = $_POST['kode_tindakan'] ?? '';
        $nama_tindakan = $_POST['nama_tindakan'] ?? '';
        $deskripsi_tindakan = $_POST['deskripsi_tindakan'] ?? '';
        $hasil_tindakan = $_POST['hasil_tindakan'] ?? '';
        $catatan = $_POST['catatan'] ?? '';

        // Validasi
        if (empty($no_rkm_medis) || empty($ID_Dokter) || empty($nama_tindakan)) {
            $_SESSION['error'] = 'Data tidak lengkap';
            header('Location: index.php?module=rekam_medis&action=tambah_tindakan_medis');
            exit;
        }

        // Simpan tindakan medis
        $data = [
            'no_rkm_medis' => $no_rkm_medis,
            'ID_Dokter' => $ID_Dokter,
            'tgl_tindakan' => $tgl_tindakan,
            'jam_tindakan' => $jam_tindakan,
            'kode_tindakan' => $kode_tindakan,
            'nama_tindakan' => $nama_tindakan,
            'deskripsi_tindakan' => $deskripsi_tindakan,
            'hasil_tindakan' => $hasil_tindakan,
            'catatan' => $catatan
        ];

        $id_tindakan = $this->tindakanMedisModel->createTindakanMedis($data);

        if ($id_tindakan) {
            $_SESSION['success'] = 'Tindakan medis berhasil ditambahkan';
            header('Location: index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=' . $no_rkm_medis);
        } else {
            $_SESSION['error'] = 'Gagal menambahkan tindakan medis';
            header('Location: index.php?module=rekam_medis&action=tambah_tindakan_medis');
        }
        exit;
    }

    public function editTindakanMedis($id)
    {
        // Ambil data tindakan medis
        $tindakan = $this->tindakanMedisModel->getTindakanMedisById($id);

        if (!$tindakan) {
            $_SESSION['error'] = 'Tindakan medis tidak ditemukan';
            header('Location: index.php?module=rekam_medis');
            exit;
        }

        // Ambil daftar dokter
        $stmt = $this->pdo->prepare("SELECT * FROM dokter WHERE Status_Aktif = 1");
        $stmt->execute();
        $dokter = $stmt->fetchAll();

        include 'modules/rekam_medis/views/form_tindakan_medis_edit.php';
    }

    public function updateTindakanMedis()
    {
        $id_tindakan = $_POST['id_tindakan'] ?? '';
        $no_rkm_medis = $_POST['no_rkm_medis'] ?? '';
        $ID_Dokter = $_POST['ID_Dokter'] ?? '';
        $tgl_tindakan = $_POST['tgl_tindakan'] ?? '';
        $jam_tindakan = $_POST['jam_tindakan'] ?? '';
        $kode_tindakan = $_POST['kode_tindakan'] ?? '';
        $nama_tindakan = $_POST['nama_tindakan'] ?? '';
        $deskripsi_tindakan = $_POST['deskripsi_tindakan'] ?? '';
        $hasil_tindakan = $_POST['hasil_tindakan'] ?? '';
        $catatan = $_POST['catatan'] ?? '';

        // Validasi
        if (empty($id_tindakan) || empty($no_rkm_medis) || empty($ID_Dokter) || empty($nama_tindakan)) {
            $_SESSION['error'] = 'Data tidak lengkap';
            header('Location: index.php?module=rekam_medis&action=edit_tindakan_medis&id=' . $id_tindakan);
            exit;
        }

        // Update tindakan medis
        $data = [
            'no_rkm_medis' => $no_rkm_medis,
            'ID_Dokter' => $ID_Dokter,
            'tgl_tindakan' => $tgl_tindakan,
            'jam_tindakan' => $jam_tindakan,
            'kode_tindakan' => $kode_tindakan,
            'nama_tindakan' => $nama_tindakan,
            'deskripsi_tindakan' => $deskripsi_tindakan,
            'hasil_tindakan' => $hasil_tindakan,
            'catatan' => $catatan
        ];

        $result = $this->tindakanMedisModel->updateTindakanMedis($id_tindakan, $data);

        if ($result) {
            $_SESSION['success'] = 'Tindakan medis berhasil diperbarui';
            header('Location: index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=' . $no_rkm_medis);
        } else {
            $_SESSION['error'] = 'Gagal memperbarui tindakan medis';
            header('Location: index.php?module=rekam_medis&action=edit_tindakan_medis&id=' . $id_tindakan);
        }
        exit;
    }

    public function hapusTindakanMedis($id)
    {
        // Ambil data tindakan medis
        $tindakan = $this->tindakanMedisModel->getTindakanMedisById($id);

        if (!$tindakan) {
            $_SESSION['error'] = 'Tindakan medis tidak ditemukan';
            header('Location: index.php?module=rekam_medis');
            exit;
        }

        // Hapus tindakan medis
        $result = $this->tindakanMedisModel->deleteTindakanMedis($id);

        if ($result) {
            $_SESSION['success'] = 'Tindakan medis berhasil dihapus';
        } else {
            $_SESSION['error'] = 'Gagal menghapus tindakan medis';
        }

        header('Location: index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=' . $tindakan['no_rkm_medis']);
        exit;
    }

    public function detailTindakanMedis($id)
    {
        // Ambil data tindakan medis
        $tindakan = $this->tindakanMedisModel->getTindakanMedisById($id);

        if (!$tindakan) {
            $_SESSION['error'] = 'Tindakan medis tidak ditemukan';
            header('Location: index.php?module=rekam_medis');
            exit;
        }

        include 'modules/rekam_medis/views/detail_tindakan_medis.php';
    }

    public function tambahPenilaianMedis()
    {
        // Ambil no_rkm_medis dari parameter URL
        $no_rkm_medis = $_GET['id'] ?? '';

        if (empty($no_rkm_medis)) {
            $_SESSION['error'] = 'Parameter ID pasien tidak ditemukan';
            header('Location: index.php?module=rekam_medis');
            exit;
        }

        // Ambil data pasien
        $stmt = $this->pdo->prepare("SELECT * FROM pasien WHERE no_rkm_medis = ?");
        $stmt->execute([$no_rkm_medis]);
        $pasien = $stmt->fetch();

        if (!$pasien) {
            $_SESSION['error'] = 'Data pasien tidak ditemukan';
            header('Location: index.php?module=rekam_medis');
            exit;
        }

        // Ambil daftar dokter
        $stmt = $this->pdo->prepare("SELECT * FROM dokter WHERE Status_Aktif = 1");
        $stmt->execute();
        $dokter = $stmt->fetchAll();

        include 'modules/rekam_medis/views/form_penilaian_medis.php';
    }

    public function simpanPenilaianMedis()
    {
        error_log("POST Data: " . print_r($_POST, true));
        error_log("GET Data: " . print_r($_GET, true));

        // Validasi field yang diperlukan
        if (empty($_POST['no_rkm_medis']) || empty($_POST['kd_dokter']) || empty($_POST['keluhan_utama'])) {
            error_log("Validasi gagal: Ada field yang kosong");
            $_SESSION['error'] = "Data pasien, dokter, dan keluhan utama harus diisi";
            header('Location: index.php?module=rekam_medis&action=tambah_penilaian_medis&id=' . $_POST['no_rkm_medis']);
            exit;
        }

        try {
            // Dapatkan nomor registrasi terakhir untuk hari ini
            $stmt = $this->pdo->prepare("
                SELECT MAX(CAST(no_reg AS UNSIGNED)) as max_reg 
                FROM reg_periksa 
                WHERE DATE(tgl_registrasi) = CURDATE()
            ");
            $stmt->execute();
            $result = $stmt->fetch();
            $reg_number = ((int)$result['max_reg'] ?? 0) + 1;

            // Format no_reg dengan padding 3 digit
            $no_reg = str_pad($reg_number, 3, '0', STR_PAD_LEFT);

            // Format no_rawat: no_rkm_medis-YYYYMMDD-[nomor urut]
            $no_rawat = sprintf(
                "%s-%s-%d",
                $_POST['no_rkm_medis'],
                date('Ymd'),
                $reg_number
            );

            // Periksa apakah no_reg sudah ada
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM reg_periksa WHERE no_reg = ? AND DATE(tgl_registrasi) = CURDATE()");
            $stmt->execute([$no_reg]);
            if ($stmt->fetchColumn() > 0) {
                // Jika sudah ada, tambahkan timestamp untuk memastikan keunikan
                $no_reg = $no_reg . date('His');
            }

            // 1. Buat record reg_periksa
            $stmt = $this->pdo->prepare("
                INSERT INTO reg_periksa (
                    no_reg,
                    no_rawat,
                    tgl_registrasi,
                    jam_reg,
                    kd_dokter,
                    no_rkm_medis,
                    status_lanjut,
                    stts,
                    stts_daftar
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $no_reg,
                $no_rawat,
                date('Y-m-d'),
                date('H:i:s'),
                $_POST['kd_dokter'],
                $_POST['no_rkm_medis'],
                'Ralan',
                'Belum',
                'Baru'
            ]);

            // 2. Simpan ke tabel tindakan_medis
            $stmt = $this->pdo->prepare("
                INSERT INTO tindakan_medis (
                    no_rawat,
                    no_rkm_medis,
                    ID_Dokter,
                    tgl_tindakan,
                    jam_tindakan,
                    nama_tindakan,
                    deskripsi_tindakan,
                    hasil_tindakan,
                    catatan
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $no_rawat,
                $_POST['no_rkm_medis'],
                $_POST['kd_dokter'],
                date('Y-m-d'),
                date('H:i:s'),
                'Pemeriksaan Medis',
                $_POST['keluhan_utama'],
                $_POST['diagnosis'] ?? '',
                $_POST['tata'] ?? ''
            ]);

            // 3. Simpan ke tabel penilaian_medis_ralan_kandungan
            $stmt = $this->pdo->prepare("
                INSERT INTO penilaian_medis_ralan_kandungan (
                    no_rawat,
                    tanggal,
                    kd_dokter,
                    anamnesis,
                    hubungan,
                    keluhan_utama,
                    rps,
                    rpd,
                    rpk,
                    rpo,
                    alergi,
                    keadaan,
                    kesadaran,
                    td,
                    nadi,
                    suhu,
                    rr,
                    bb,
                    tb,
                    tfu,
                    tbj,
                    his,
                    kontraksi,
                    djj,
                    inspeksi,
                    inspekulo,
                    diagnosis,
                    tata,
                    tanggal_kontrol,
                    atensi,
                    resep
                    atensi
                    tata
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $no_rawat,
                date('Y-m-d H:i:s'),
                $_POST['kd_dokter'],
                $_POST['anamnesis'] ?? 'Autoanamnesis',
                $_POST['hubungan'] ?? '-',
                $_POST['keluhan_utama'],
                $_POST['rps'] ?? '',
                $_POST['rpd'] ?? '',
                $_POST['rpk'] ?? '',
                $_POST['rpo'] ?? '',
                $_POST['alergi'] ?? '',
                $_POST['keadaan'] ?? 'Sehat',
                $_POST['kesadaran'] ?? 'Compos Mentis',
                $_POST['td'] ?? '',
                $_POST['nadi'] ?? '',
                $_POST['suhu'] ?? '',
                $_POST['rr'] ?? '',
                $_POST['bb'] ?? '',
                $_POST['tb'] ?? '',
                $_POST['tfu'] ?? '',
                $_POST['tbj'] ?? '',
                $_POST['his'] ?? '',
                $_POST['kontraksi'] ?? 'Tidak',
                $_POST['djj'] ?? '',
                $_POST['inspeksi'] ?? '',
                $_POST['inspekulo'] ?? '',
                $_POST['diagnosis'] ?? '',
                $_POST['tata'] ?? '',
                !empty($_POST['tanggal_kontrol']) ? $_POST['tanggal_kontrol'] : null,
                $_POST['atensi'] ?? '0'
            ]);

            $_SESSION['success'] = 'Penilaian medis berhasil disimpan';
            header('Location: index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=' . $_POST['no_rkm_medis']);
            exit;
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
            header('Location: index.php?module=rekam_medis&action=tambah_penilaian_medis&id=' . $_POST['no_rkm_medis']);
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
            header('Location: index.php?module=rekam_medis&action=tambah_penilaian_medis&id=' . $_POST['no_rkm_medis']);
            exit;
        }
    }

    public function manajemenAntrian()
    {
        // Tampilkan halaman manajemen antrian
        // View akan menggunakan koneksi database global
        include 'modules/rekam_medis/views/manajemen_antrian.php';
    }

    public function tambahPenilaianRalan()
    {
        $no_rkm_medis = $_GET['id'];

        // Ambil data pasien
        $pasien = $this->rekamMedisModel->getPasienById($no_rkm_medis);
        if (!$pasien) {
            $_SESSION['error'] = 'Pasien tidak ditemukan';
            header('Location: index.php?module=rekam_medis');
            exit;
        }

        // Generate no_rawat
        $tanggal = date('Y-m-d');
        $no_rawat = $no_rkm_medis . '/' . date('Ymd');

        include 'modules/rekam_medis/views/form_penilaian_ralan.php';
    }

    public function simpanPenilaianRalan()
    {
        $data = [
            'no_rawat' => $_POST['no_rkm_medis'] . '/' . date('Ymd'),
            'no_rkm_medis' => $_POST['no_rkm_medis'],
            'tanggal' => date('Y-m-d'),
            'jam' => date('H:i:s'),
            'kd_dokter' => $_SESSION['user_id'], // Sesuaikan dengan ID dokter yang login
            'anamnesis' => $_POST['anamnesis'],
            'hubungan' => $_POST['hubungan'],
            'keluhan_utama' => $_POST['keluhan_utama'],
            'rps' => $_POST['rps'],
            'rpd' => $_POST['rpd'],
            'rpk' => $_POST['rpk'],
            'rpo' => $_POST['rpo'],
            'alergi' => $_POST['alergi'],
            'keadaan' => $_POST['keadaan'],
            'kesadaran' => $_POST['kesadaran'],
            'td' => $_POST['td'],
            'nadi' => $_POST['nadi'],
            'suhu' => $_POST['suhu'],
            'rr' => $_POST['rr'],
            'bb' => (isset($_POST['bb']) && $_POST['bb'] !== '' ? $_POST['bb'] : null),
            'tb' => (isset($_POST['tb']) && $_POST['tb'] !== '' ? $_POST['tb'] : null),
            'lila' => $_POST['lila'],
            'tfu' => $_POST['tfu'],
            'tbj' => $_POST['tbj'],
            'his' => $_POST['his'],
            'kontraksi' => $_POST['kontraksi'],
            'djj' => $_POST['djj'],
            'inspeksi' => $_POST['inspeksi'],
            'inspekulo' => $_POST['inspekulo'],
            'diagnosa' => $_POST['diagnosa'],
            'tindakan' => $_POST['tindakan'],
            'edukasi' => $_POST['edukasi']
        ];

        if ($this->rekamMedisModel->tambahPenilaianMedisRalanKandungan($data)) {
            $_SESSION['success'] = 'Data penilaian medis berhasil disimpan';
        } else {
            $_SESSION['error'] = 'Gagal menyimpan data penilaian medis';
        }

        header('Location: index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=' . $_POST['no_rkm_medis']);
        exit;
    }

    public function editPenilaianRalan()
    {
        $no_rawat = $_GET['id'];

        // Ambil data penilaian medis
        $penilaian_medis = $this->rekamMedisModel->getPenilaianMedisRalanKandunganByNoRawat($no_rawat);
        if (!$penilaian_medis) {
            $_SESSION['error'] = 'Data penilaian medis tidak ditemukan';
            header('Location: index.php?module=rekam_medis');
            exit;
        }

        $no_rkm_medis = $penilaian_medis['no_rkm_medis'];
        include 'modules/rekam_medis/views/form_penilaian_ralan.php';
    }

    public function updatePenilaianRalan()
    {
        $data = [
            'no_rawat' => $_POST['no_rawat'],
            'id_perujuk' => isset($_POST['id_perujuk']) && $_POST['id_perujuk'] !== '' ? $_POST['id_perujuk'] : null,
            'anamnesis' => $_POST['anamnesis'],
            'hubungan' => $_POST['hubungan'],
            'keluhan_utama' => $_POST['keluhan_utama'],
            'rps' => $_POST['rps'],
            'rpd' => $_POST['rpd'],
            'rpk' => $_POST['rpk'],
            'rpo' => $_POST['rpo'],
            'alergi' => $_POST['alergi'],
            'keadaan' => $_POST['keadaan'],
            'kesadaran' => $_POST['kesadaran'],
            'td' => $_POST['td'],
            'nadi' => $_POST['nadi'],
            'suhu' => $_POST['suhu'],
            'rr' => $_POST['rr'],
            'bb' => (isset($_POST['bb']) && $_POST['bb'] !== '' ? $_POST['bb'] : null),
            'tb' => (isset($_POST['tb']) && $_POST['tb'] !== '' ? $_POST['tb'] : null),
            'lila' => $_POST['lila'],
            'tfu' => $_POST['tfu'],
            'tbj' => $_POST['tbj'],
            'his' => $_POST['his'],
            'kontraksi' => $_POST['kontraksi'],
            'djj' => $_POST['djj'],
            'inspeksi' => $_POST['inspeksi'],
            'inspekulo' => $_POST['inspekulo'],
            'diagnosa' => $_POST['diagnosa'],
            'tindakan' => $_POST['tindakan'],
            'edukasi' => $_POST['edukasi']
        ];

        if ($this->rekamMedisModel->updatePenilaianMedisRalanKandungan($data)) {
            $_SESSION['success'] = 'Data penilaian medis berhasil diperbarui';
        } else {
            $_SESSION['error'] = 'Gagal memperbarui data penilaian medis';
        }

        header('Location: index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=' . $_POST['no_rkm_medis']);
        exit;
    }

    public function detailPenilaianRalan()
    {
        $no_rawat = $_GET['id'];

        // Ambil data penilaian medis
        $penilaian_medis = $this->rekamMedisModel->getPenilaianMedisRalanKandunganByNoRawat($no_rawat);
        if (!$penilaian_medis) {
            $_SESSION['error'] = 'Data penilaian medis tidak ditemukan';
            header('Location: index.php?module=rekam_medis');
            exit;
        }

        include 'modules/rekam_medis/views/detail_penilaian_ralan.php';
    }

    public function tambah_penilaian_medis_ralan_kandungan()
    {
        $no_rkm_medis = $_GET['no_rkm_medis'];

        // Ambil data pasien
        $pasien = $this->rekamMedisModel->getPasienById($no_rkm_medis);
        if (!$pasien) {
            $_SESSION['error'] = 'Pasien tidak ditemukan';
            header('Location: index.php?module=rekam_medis');
            exit;
        }

        // Generate no_rawat
        $tanggal = date('Y-m-d');
        $no_rawat = $no_rkm_medis . '/' . date('Ymd');

        include 'modules/rekam_medis/views/form_penilaian_medis_ralan_kandungan.php';
    }

    public function simpan_penilaian_medis_ralan_kandungan()
    {
        try {
            error_log("=== MULAI PROSES SIMPAN PENILAIAN MEDIS RALAN KANDUNGAN ===");
            error_log("Raw POST Data: " . file_get_contents('php://input'));
            error_log("POST Array: " . print_r($_POST, true));
            error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
            error_log("Content Type: " . $_SERVER['CONTENT_TYPE']);

            // Validasi data yang diperlukan
            if (empty($_POST['no_rawat'])) {
                error_log("no_rawat kosong");
                throw new Exception('No rawat tidak boleh kosong');
            }

            if (empty($_POST['keluhan_utama'])) {
                error_log("keluhan_utama kosong");
                throw new Exception('Keluhan utama tidak boleh kosong');
            }

            // Siapkan data sesuai struktur tabel aktual di database
            $data = [
                'no_rawat' => $_POST['no_rawat'],
                'tanggal' => $_POST['tanggal'] ?? date('Y-m-d H:i:s'),
                'id_perujuk' => isset($_POST['id_perujuk']) && $_POST['id_perujuk'] !== '' ? $_POST['id_perujuk'] : null,
                'anamnesis' => $_POST['anamnesis'] ?? 'Autoanamnesis',
                'hubungan' => $_POST['hubungan'] ?? '-',
                'keluhan_utama' => $_POST['keluhan_utama'],
                'rps' => $_POST['rps'] ?? '',
                'rpd' => $_POST['rpd'] ?? '',
                'rpk' => $_POST['rpk'] ?? '',
                'rpo' => $_POST['rpo'] ?? '',
                'alergi' => $_POST['alergi'] ?? '',
                'keadaan' => $_POST['keadaan'] ?? 'Sehat',
                'gcs' => $_POST['gcs'] ?? '15',
                'kesadaran' => $_POST['kesadaran'] ?? 'Compos Mentis',
                'td' => $_POST['td'] ?? '',
                'nadi' => $_POST['nadi'] ?? '',
                'rr' => $_POST['rr'] ?? '',
                'suhu' => $_POST['suhu'] ?? '',
                'spo' => $_POST['spo'] ?? '',
                'bb' => (isset($_POST['bb']) && $_POST['bb'] !== '' ? $_POST['bb'] : null),
                'tb' => (isset($_POST['tb']) && $_POST['tb'] !== '' ? $_POST['tb'] : null),
                'kepala' => $_POST['kepala'] ?? 'Normal',
                'mata' => $_POST['mata'] ?? 'Normal',
                'gigi' => $_POST['gigi'] ?? 'Normal',
                'tht' => $_POST['tht'] ?? 'Normal',
                'thoraks' => $_POST['thoraks'] ?? 'Normal',
                'abdomen' => $_POST['abdomen'] ?? 'Normal',
                'genital' => $_POST['genital'] ?? 'Normal',
                'ekstremitas' => $_POST['ekstremitas'] ?? 'Normal',
                'kulit' => $_POST['kulit'] ?? 'Normal',
                'ket_fisik' => $_POST['ket_fisik'] ?? '',
                'ultra' => $_POST['ultra'] ?? '',
                'lab' => $_POST['lab'] ?? '',
                'diagnosis' => $_POST['diagnosis'] ?? '',
                'tata' => $_POST['tata'] ?? '',
                'edukasi' => $_POST['edukasi'] ?? '',
                'resep' => $_POST['resep'] ?? '',
                'atensi' => $_POST['atensi'] ?? '0',
                'tanggal_kontrol' => !empty($_POST['tanggal_kontrol']) ? $_POST['tanggal_kontrol'] : null
            ];

            error_log("Data yang akan disimpan: " . print_r($data, true));

            // Query untuk menyimpan data
            $columns = implode(', ', array_keys($data));
            $values = implode(', ', array_fill(0, count($data), '?'));
            $sql = "INSERT INTO penilaian_medis_ralan_kandungan ($columns) VALUES ($values)";

            error_log("SQL Query: " . $sql);
            error_log("SQL Values: " . print_r(array_values($data), true));

            $stmt = $this->pdo->prepare($sql);

            if (!$stmt) {
                error_log("Error preparing statement: " . print_r($this->pdo->errorInfo(), true));
                throw new Exception('Gagal mempersiapkan query');
            }

            if ($stmt->execute(array_values($data))) {
                error_log("Data berhasil disimpan");
                $_SESSION['success'] = 'Data penilaian medis berhasil disimpan';

                // Ambil no_rkm_medis dari reg_periksa
                $stmt = $this->pdo->prepare("SELECT no_rkm_medis FROM reg_periksa WHERE no_rawat = ?");
                $stmt->execute([$_POST['no_rawat']]);
                $no_rkm_medis = $stmt->fetchColumn();

                error_log("Redirect ke detail pasien dengan no_rkm_medis: " . $no_rkm_medis);
                header('Location: index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=' . $no_rkm_medis);
                exit;
            } else {
                error_log("Error executing statement: " . print_r($stmt->errorInfo(), true));
                throw new Exception('Gagal menyimpan data penilaian medis: ' . implode(", ", $stmt->errorInfo()));
            }
        } catch (Exception $e) {
            error_log("Error in simpan_penilaian_medis_ralan_kandungan: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $_SESSION['error'] = $e->getMessage();
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }

    public function simpan_pemeriksaan()
    {
        try {
            error_log("Starting simpan_pemeriksaan");
            error_log("POST data: " . print_r($_POST, true));

            // Validasi input
            if (!isset($_POST['no_rkm_medis']) || !isset($_POST['status_bayar'])) {
                throw new Exception("Data yang diperlukan tidak lengkap");
            }

            // Pastikan no_reg tidak kosong
            if (empty($_POST['no_reg'])) {
                $_POST['no_reg'] = date('Ymd-His');
                error_log("No_reg kosong, generate baru: " . $_POST['no_reg']);
            }

            // Siapkan data untuk disimpan
            $data = [
                'no_rawat' => $_POST['no_rawat'],
                'no_rkm_medis' => $_POST['no_rkm_medis'],
                'tgl_registrasi' => $_POST['tgl_registrasi'],
                'jam_reg' => $_POST['jam_reg'],
                'no_reg' => $_POST['no_reg'],
                'status_bayar' => $_POST['status_bayar'],
                'rincian' => $_POST['rincian'] ?? null
            ];

            error_log("Attempting to save pemeriksaan with data: " . json_encode($data));

            // Simpan ke database
            $result = $this->rekamMedisModel->tambahPemeriksaan($data);

            if ($result) {
                // Buat data pemeriksaan awal di tabel penilaian_medis_ralan_kandungan
                $dataPemeriksaan = [
                    'no_rawat' => $_POST['no_rawat'],
                    'tanggal' => date('Y-m-d'),
                    'anamnesis' => 'Autoanamnesis',
                    'hubungan' => '',
                    'keluhan_utama' => '-',
                    'rps' => '',
                    'rpd' => '',
                    'rpk' => '',
                    'rpo' => '',
                    'alergi' => '',
                    'keadaan' => 'Sehat',
                    'gcs' => '',
                    'kesadaran' => 'Compos Mentis',
                    'td' => '',
                    'nadi' => '',
                    'rr' => '',
                    'suhu' => '',
                    'spo' => '',
                    'bb' => null,
                    'tb' => null,
                    'kepala' => 'Normal',
                    'mata' => 'Normal',
                    'gigi' => 'Normal',
                    'tht' => 'Normal',
                    'thoraks' => 'Normal',
                    'abdomen' => 'Normal',
                    'genital' => 'Normal',
                    'ekstremitas' => 'Normal',
                    'kulit' => 'Normal',
                    'ket_fisik' => '',
                    'ultra' => '',
                    'lab' => '',
                    'diagnosis' => '',
                    'tata' => '',
                    'edukasi' => '',
                    'resume' => '',
                    'resep' => '',
                    'tanggal_kontrol' => null,
                    'atensi' => null
                ];

                // Simpan data pemeriksaan awal
                $stmt = $this->pdo->prepare("
                    INSERT INTO penilaian_medis_ralan_kandungan (
                        no_rawat, tanggal, anamnesis, hubungan, keluhan_utama, rps, rpd, rpk, rpo, alergi, keadaan, gcs, kesadaran, td, nadi, rr, suhu, spo, bb, tb, kepala, mata, gigi, tht, thoraks, abdomen, genital, ekstremitas, kulit, ket_fisik, ultra, lab, diagnosis, tata, edukasi, resume, resep, tanggal_kontrol, atensi
                    ) VALUES (
                        :no_rawat, :tanggal, :anamnesis, :hubungan, :keluhan_utama, :rps, :rpd, :rpk, :rpo, :alergi, :keadaan, :gcs, :kesadaran, :td, :nadi, :rr, :suhu, :spo, :bb, :tb, :kepala, :mata, :gigi, :tht, :thoraks, :abdomen, :genital, :ekstremitas, :kulit, :ket_fisik, :ultra, :lab, :diagnosis, :tata, :edukasi, :resume, :resep, :tanggal_kontrol, :atensi
                    )
                ");

                $resultPemeriksaan = $stmt->execute($dataPemeriksaan);

                if ($resultPemeriksaan) {
                    $_SESSION['success'] = "Kunjungan baru dan data pemeriksaan awal berhasil ditambahkan";
                } else {
                    $_SESSION['success'] = "Kunjungan berhasil ditambahkan, namun gagal membuat data pemeriksaan awal";
                    error_log("Gagal menyimpan data pemeriksaan awal: " . print_r($stmt->errorInfo(), true));
                }
                
                header("Location: index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=" . $_POST['no_rkm_medis']);
                exit;
            } else {
                throw new Exception("Gagal menyimpan data kunjungan. Silakan coba lagi.");
            }
        } catch (Exception $e) {
            error_log("Error in simpan_pemeriksaan: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $_SESSION['error'] = $e->getMessage();
            header("Location: index.php?module=rekam_medis&action=tambah_pemeriksaan&no_rkm_medis=" . $_POST['no_rkm_medis']);
            exit;
        }
    }

    public function editPasien()
    {
        // Set header untuk mencegah caching
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");

        $no_rkm_medis = $_GET['id'] ?? '';

        if (empty($no_rkm_medis)) {
            $_SESSION['error'] = 'Parameter ID pasien tidak ditemukan';
            header('Location: index.php?module=rekam_medis');
            exit;
        }

        // Ambil data pasien dengan parameter waktu untuk mencegah cache
        $pasien = $this->rekamMedisModel->getPasienById($no_rkm_medis);

        // Debug: Log data pasien yang diambil
        error_log("Data pasien untuk form edit: " . json_encode($pasien));

        if (!$pasien) {
            $_SESSION['error'] = 'Data pasien tidak ditemukan';
            header('Location: index.php?module=rekam_medis');
            exit;
        }

        // Data wilayah statis
        $kecamatan = [
            ['kd_kec' => '1', 'nm_kec' => 'Batu'],
            ['kd_kec' => '2', 'nm_kec' => 'Bumiaji'],
            ['kd_kec' => '3', 'nm_kec' => 'Junrejo'],
            ['kd_kec' => '4', 'nm_kec' => 'Pujon'],
            ['kd_kec' => '5', 'nm_kec' => 'Ngantang'],
            ['kd_kec' => '6', 'nm_kec' => 'Lainnya']
        ];

        $kelurahan = [
            ['kd_kel' => '1', 'nm_kel' => 'Sisir'],
            ['kd_kel' => '2', 'nm_kel' => 'Temas'],
            ['kd_kel' => '3', 'nm_kel' => 'Ngaglik'],
            ['kd_kel' => '4', 'nm_kel' => 'Songgokerto'],
            ['kd_kel' => '5', 'nm_kel' => 'Lainnya']
        ];

        $kabupaten = [
            ['kd_kab' => '1', 'nm_kab' => 'Kota Batu'],
            ['kd_kab' => '2', 'nm_kab' => 'Kota Malang'],
            ['kd_kab' => '3', 'nm_kab' => 'Kabupaten Malang'],
            ['kd_kab' => '4', 'nm_kab' => 'Lainnya']
        ];

        $cara_bayar = [
            ['kd_pj' => 'UMU', 'nm_pj' => 'Umum'],
            ['kd_pj' => 'BPJ', 'nm_pj' => 'BPJS'],
            ['kd_pj' => 'ASR', 'nm_pj' => 'Asuransi'],
            ['kd_pj' => 'KOR', 'nm_pj' => 'Korporasi']
        ];

        include 'modules/rekam_medis/views/form_edit_pasien.php';
    }

    public function detail_pemeriksaan()
    {
        $no_rawat = $_GET['id'];
        error_log("detail_pemeriksaan called for no_rawat: " . $no_rawat);

        // Ambil data pemeriksaan
        $pemeriksaan = $this->rekamMedisModel->getPenilaianMedisRalanKandunganByNoRawat($no_rawat);
        if (!$pemeriksaan) {
            $_SESSION['error'] = 'Data pemeriksaan tidak ditemukan';
            header('Location: index.php?module=rekam_medis');
            exit;
        }

        // Ambil data pasien
        $pasien = $this->rekamMedisModel->getPasienById($pemeriksaan['no_rkm_medis']);
        if (!$pasien) {
            $_SESSION['error'] = 'Data pasien tidak ditemukan';
            header('Location: index.php?module=rekam_medis');
            exit;
        }

        // Tampilkan view
        include 'modules/rekam_medis/views/detail_pemeriksaan.php';
    }

    /**
     * Function edit_pemeriksaan - Redirects to form_edit_pemeriksaan for consistency
     * This method exists to maintain backward compatibility with existing links
     * that use the edit_pemeriksaan action instead of form_edit_pemeriksaan
     */
    public function edit_pemeriksaan()
    {
        error_log("==== DEBUGGING edit_pemeriksaan START ====");
        error_log("REQUEST_URI: " . $_SERVER['REQUEST_URI']);
        error_log("QUERY STRING: " . $_SERVER['QUERY_STRING']);
        error_log("id param: " . ($_GET['id'] ?? 'not set'));

        // Redirect ke formEditPemeriksaan untuk konsistensi antara local dan online
        if (isset($_GET['id']) && !empty($_GET['id'])) {
            $no_rawat = $_GET['id'];
            $source = $_GET['source'] ?? '';
            error_log("Redirecting from edit_pemeriksaan to form_edit_pemeriksaan with no_rawat: " . $no_rawat);
            header("Location: index.php?module=rekam_medis&action=form_edit_pemeriksaan&no_rawat=" . $no_rawat . "&source=" . $source);
            exit;
        } else {
            error_log("No ID provided in edit_pemeriksaan");
            $_SESSION['error'] = 'ID pemeriksaan tidak valid';
            header('Location: ' . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/index.php?module=rekam_medis&action=data_pasien');
            exit;
        }
    }

    /**
     * Menampilkan form edit pemeriksaan berdasarkan no_rawat
     */
    public function edit_pemeriksaan_data()
    {
        // Pastikan user login
        if (!isset($_SESSION['username'])) {
            header('Location: index.php?module=auth&action=login');
            exit;
        }

        $no_rawat = isset($_GET['no_rawat']) ? htmlspecialchars($_GET['no_rawat']) : '';
        $source = isset($_GET['source']) ? htmlspecialchars($_GET['source']) : '';

        if (empty($no_rawat)) {
            $_SESSION['error'] = 'No rawat tidak valid!';
            header('Location: index.php?module=rekam_medis&action=data_pasien');
            exit;
        }

        // Ambil data pasien dan pemeriksaan
        $stmt = $this->conn->prepare("SELECT p.*, pr.no_rawat, pr.tgl_registrasi, pr.jam_reg
            FROM pasien p
            JOIN reg_periksa pr ON p.no_rkm_medis = pr.no_rkm_medis
            WHERE pr.no_rawat = ?");
        $stmt->execute([$no_rawat]);
        $pasien = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pasien) {
            $_SESSION['error'] = 'Data pasien tidak ditemukan!';
            header('Location: index.php?module=rekam_medis&action=data_pasien');
            exit;
        }

        // Mengambil data dari penilaian_medis_ralan_kandungan
        $stmt = $this->conn->prepare("SELECT * FROM penilaian_medis_ralan_kandungan WHERE no_rawat = ?");
        $stmt->execute([$no_rawat]);
        $pemeriksaan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Inisialisasi variabel pemeriksaan_ralan dengan nilai default
        $pemeriksaan_ralan = [];

        // Riwayat pemeriksaan
        $stmt = $this->conn->prepare("SELECT pr.no_rawat, pr.tgl_registrasi, pr.jam_reg, 
            pm.keluhan_utama, pm.diagnosis as pemeriksaan
            FROM reg_periksa pr
            LEFT JOIN penilaian_medis_ralan_kandungan pm ON pr.no_rawat = pm.no_rawat
            WHERE pr.no_rkm_medis = ?
            ORDER BY pr.tgl_registrasi DESC, pr.jam_reg DESC");
        $stmt->execute([$pasien['no_rkm_medis']]);
        $riwayatPemeriksaan = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $riwayatPemeriksaan[] = $row;
        }

        // Sediakan variabel untuk view
        include 'modules/rekam_medis/views/form_edit_pemeriksaan.php';
    }

    /**
     * Update data pemeriksaan
     */
    public function update_pemeriksaan()
    {
        error_log("Starting update_pemeriksaan");
        error_log("POST data: " . print_r($_POST, true));
        
        // Cek jika ini request AJAX untuk update ceklist saja
        $isAjaxRequest = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                         strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        
        $isCeklistUpdateOnly = isset($_POST['ceklist']) && count($_POST) <= 2; // Hanya no_rawat dan ceklist
        $isCatatanPasienUpdateOnly = isset($_POST['catatan_pasien']) && isset($_POST['no_rkm_medis']) && count($_POST) <= 2;
        // Handler AJAX untuk catatan_pasien
        if ($isAjaxRequest && $isCatatanPasienUpdateOnly) {
            try {
                $no_rkm_medis = $_POST['no_rkm_medis'];
                $catatan_pasien = $_POST['catatan_pasien'];
                $result = $this->rekamMedisModel->updateCatatanPasien($no_rkm_medis, $catatan_pasien);
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Catatan pasien berhasil diperbarui']);
                } else {
                    throw new Exception('Gagal memperbarui catatan pasien');
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }
        
        if ($isAjaxRequest && $isCeklistUpdateOnly) {
            try {
                if (!isset($_POST['no_rawat']) || empty($_POST['no_rawat'])) {
                    throw new Exception('Nomor rawat tidak valid');
                }
                
                $data = [
                    'no_rawat' => $_POST['no_rawat'],
                    'ceklist' => $_POST['ceklist']
                ];
                
                $result = $this->rekamMedisModel->updatePemeriksaan($data);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Ceklist berhasil diperbarui']);
                } else {
                    throw new Exception('Tidak ada perubahan data atau terjadi kesalahan');
                }
                
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }

        // Validasi data
        if (!isset($_POST['no_rawat']) || empty($_POST['no_rawat'])) {
            $_SESSION['error'] = 'Nomor rawat tidak boleh kosong';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        // Cek apakah no_rawat ada di reg_periksa
        $check_reg = $this->pdo->prepare("SELECT no_rawat FROM reg_periksa WHERE no_rawat = ?");
        $check_reg->execute([$_POST['no_rawat']]);
        
        if ($check_reg->rowCount() == 0) {
            // Jika tidak ada, buat registrasi baru
            $tgl_registrasi = date('Y-m-d');
            $no_rawat = substr($_POST['no_rawat'], 0, 17); // Pastikan tidak melebihi 17 karakter
            $no_rkm_medis = $_POST['no_rkm_medis'] ?? null;
            
            if ($no_rkm_medis) {
                try {
                    // Insert ke reg_periksa dengan no_rawat yang sudah ada
                    $stmt = $this->pdo->prepare("INSERT INTO reg_periksa (no_rawat, tgl_registrasi, no_rkm_medis) 
                                             VALUES (?, ?, ?)");
                    $stmt->execute([$no_rawat, $tgl_registrasi, $no_rkm_medis]);
                    
                    error_log("Registrasi baru berhasil dibuat: " . $no_rawat);
                    $_SESSION['info'] = 'Registrasi baru berhasil dibuat';
                } catch (PDOException $e) {
                    error_log("Gagal membuat registrasi baru: " . $e->getMessage());
                    $_SESSION['error'] = 'Gagal membuat registrasi baru. ' . $e->getMessage();
                    header('Location: ' . $_SERVER['HTTP_REFERER']);
                    exit;
                }
            } else {
                $_SESSION['error'] = 'Data pasien tidak valid, no_rkm_medis kosong.';
                header('Location: ' . $_SERVER['HTTP_REFERER']);
                exit;
            }
        }

        try {
            // Log jika input terlalu panjang
            if (isset($_POST['no_rawat']) && strlen($_POST['no_rawat']) > 17) {
                error_log("WARNING: no_rawat terlalu panjang: " . $_POST['no_rawat']);
            }
            // Siapkan data untuk update
            $data = [
                'no_rawat' => isset($_POST['no_rawat']) ? substr($_POST['no_rawat'], 0, 17) : null,
                'id_perujuk' => $_POST['id_perujuk'] ?? null,
                'keluhan_utama' => $_POST['keluhan_utama'] ?? null,
                'rps' => $_POST['rps'] ?? null,
                'rpd' => $_POST['rpd'] ?? null,
                'alergi' => $_POST['alergi'] ?? null,
                'gcs' => $_POST['gcs'] ?? null,
                'td' => $_POST['td'] ?? null,
                'nadi' => $_POST['nadi'] ?? null,
                'rr' => $_POST['rr'] ?? null,
                'suhu' => $_POST['suhu'] ?? null,
                'spo' => $_POST['spo'] ?? null,
                'bb' => (isset($_POST['bb']) && $_POST['bb'] !== '' ? $_POST['bb'] : null),
                'tb' => $_POST['tb'] ?? null,
                'kepala' => $_POST['kepala'] ?? null,
                'mata' => $_POST['mata'] ?? null,
                'gigi' => $_POST['gigi'] ?? null,
                'tht' => $_POST['tht'] ?? null,
                'thoraks' => $_POST['thoraks'] ?? null,
                'abdomen' => $_POST['abdomen'] ?? null,
                'genital' => $_POST['genital'] ?? null,
                'ekstremitas' => $_POST['ekstremitas'] ?? null,
                'kulit' => $_POST['kulit'] ?? null,
                'ket_fisik' => $_POST['ket_fisik'] ?? null,
                'ultra' => $_POST['ultra'] ?? null,
                'lab' => $_POST['lab'] ?? null,
                'diagnosis' => $_POST['diagnosis'] ?? null,
                'tata' => $_POST['tata'] ?? null,
                'edukasi' => $_POST['edukasi'] ?? null,
                'resume' => $_POST['resume'] ?? null,
                'ceklist' => $_POST['ceklist'] ?? null, // Tambahan agar field ceklist ikut tersimpan
                'resep' => $_POST['resep'] ?? null,
                'tanggal_kontrol' => !empty($_POST['tanggal_kontrol']) ? $_POST['tanggal_kontrol'] : null,
                'atensi' => $_POST['atensi'] ?? '0'
            ];

            // Log data yang akan diupdate
            error_log("Data to update: " . json_encode($data));

            // Update menggunakan model
            $result = $this->rekamMedisModel->updatePemeriksaan($data);

            if ($result) {
                error_log("Update successful");
                $_SESSION['success'] = 'Data pemeriksaan berhasil diperbarui';
            } else {
                error_log("Update failed or no changes made");
                $_SESSION['warning'] = 'Tidak ada perubahan data';
            }

            // Ambil no_rkm_medis untuk redirect
            $stmt = $this->pdo->prepare("SELECT no_rkm_medis FROM reg_periksa WHERE no_rawat = ?");
            $stmt->execute([$_POST['no_rawat']]);
            $no_rkm_medis = $stmt->fetchColumn();
            // Sanitasi agar tidak ada newline
            $no_rkm_medis = str_replace(["\r", "\n"], '', $no_rkm_medis);
            header('Location: index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=' . $no_rkm_medis);
            exit;
        } catch (Exception $e) {
            error_log("Error in update_pemeriksaan: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $_SESSION['error'] = 'Terjadi kesalahan saat memperbarui data: ' . $e->getMessage();
            // Tetap di halaman edit agar user bisa memperbaiki
            if (!headers_sent()) {
                $no_rawat_sanitized = isset($_POST['no_rawat']) ? str_replace(["\r", "\n"], '', $_POST['no_rawat']) : '';
                header('Location: index.php?module=rekam_medis&action=form_edit_pemeriksaan&no_rawat=' . $no_rawat_sanitized);
            } else {
                echo "<div class='alert alert-danger'>Terjadi kesalahan: ".$e->getMessage()."</div>";
            }
            exit;
        }
    }

    /**
     * Fungsi untuk memeriksa pasien - dengan alur:
     * 1. Cek apakah sudah ada kunjungan di reg_periksa
     *    - Jika belum ada, arahkan ke halaman tambah kunjungan baru
     * 2. Jika sudah ada kunjungan, cek apakah sudah ada data di penilaian_medis_ralan_kandungan
     *    - Jika sudah ada, tampilkan form edit pemeriksaan dengan no_rawat yang ditemukan
     *    - Jika belum ada, arahkan ke halaman form_penilaian_medis_ralan_kandungan.php untuk buat data baru
     */
    public function periksa_pasien()
    {
        error_log("==== DEBUGGING periksa_pasien START ====");

        try {
            // Validasi parameter no_rkm_medis
            if (!isset($_GET['no_rkm_medis']) || empty($_GET['no_rkm_medis'])) {
                throw new Exception('No RM tidak ditemukan');
            }

            $no_rkm_medis = $_GET['no_rkm_medis'];
            $source = $_GET['source'] ?? '';

            error_log("Checking reg_periksa for no_rkm_medis: " . $no_rkm_medis);

            // Cek apakah ada data di tabel reg_periksa untuk pasien ini
            $stmt_reg = $this->pdo->prepare("
                SELECT no_rawat FROM reg_periksa 
                WHERE no_rkm_medis = ? 
                ORDER BY tgl_registrasi DESC, jam_reg DESC 
                LIMIT 1
            ");
            $stmt_reg->execute([$no_rkm_medis]);
            $result_reg = $stmt_reg->fetch(PDO::FETCH_ASSOC);

            if ($result_reg && isset($result_reg['no_rawat'])) {
                $no_rawat = $result_reg['no_rawat'];
                error_log("Found existing reg_periksa record with no_rawat: " . $no_rawat);

                // Cek apakah sudah ada data di tabel penilaian_medis_ralan_kandungan
                $stmt_penilaian = $this->pdo->prepare("
                    SELECT no_rawat FROM penilaian_medis_ralan_kandungan 
                    WHERE no_rawat = ? 
                    LIMIT 1
                ");
                $stmt_penilaian->execute([$no_rawat]);
                $result_penilaian = $stmt_penilaian->fetch(PDO::FETCH_ASSOC);

                if ($result_penilaian) {
                    // Jika sudah ada data penilaian, tampilkan form edit pemeriksaan
                    // PERUBAHAN: Menggunakan format URL yang berfungsi di online (form_edit_pemeriksaan dengan parameter no_rawat)
                    error_log("Found existing penilaian_medis_ralan_kandungan record, redirecting to form_edit_pemeriksaan");
                    header("Location: index.php?module=rekam_medis&action=form_edit_pemeriksaan&no_rawat=" . $no_rawat . "&source=" . $source);
                    exit;
                } else {
                    // Jika belum ada data penilaian, arahkan ke form_penilaian_medis_ralan_kandungan
                    error_log("No penilaian_medis_ralan_kandungan record found, redirecting to form_penilaian_medis_ralan_kandungan");
                    header("Location: index.php?module=rekam_medis&action=form_penilaian_medis_ralan_kandungan&no_rawat=" . $no_rawat . "&source=" . $source);
                    exit;
                }
            } else {
                // Jika tidak ditemukan di reg_periksa, arahkan ke halaman tambah kunjungan baru
                error_log("No existing reg_periksa record found, redirecting to tambah_pemeriksaan");
                header("Location: index.php?module=rekam_medis&action=tambah_pemeriksaan&no_rkm_medis=" . $no_rkm_medis . "&source=" . $source);
                exit;
            }
        } catch (Exception $e) {
            error_log("ERROR in periksa_pasien: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header('Location: index.php?module=rekam_medis&action=data_pasien');
            exit;
        }
    }

    public function tambah_pemeriksaan()
    {
        try {
            if (!isset($_GET['no_rkm_medis'])) {
                throw new Exception('No RM tidak ditemukan');
            }

            $no_rkm_medis = $_GET['no_rkm_medis'];
            $pasien = $this->rekamMedisModel->getPasienById($no_rkm_medis);

            if (!$pasien) {
                throw new Exception('Data pasien tidak ditemukan');
            }

            // Generate nomor rawat dan nomor registrasi
            $tgl_registrasi = date('Y-m-d');
            $no_rawat = $this->rekamMedisModel->generateNoRawat($tgl_registrasi);

            include 'modules/rekam_medis/views/form_tambah_pemeriksaan.php';
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: index.php?module=rekam_medis');
            exit;
        }
    }

    public function formPenilaianMedisRalanKandungan()
    {
        $no_rawat = $_GET['no_rawat'] ?? '';

        if (empty($no_rawat)) {
            $_SESSION['error'] = "Nomor rawat tidak valid";
            header("Location: index.php?module=rekam_medis");
            exit;
        }

        // Ambil data pasien berdasarkan no_rawat
        $stmt = $this->pdo->prepare("
            SELECT p.*, rp.no_rawat, rp.tgl_registrasi, rp.jam_reg
            FROM reg_periksa rp
            JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
            WHERE rp.no_rawat = ?
        ");
        $stmt->execute([$no_rawat]);
        $data = $stmt->fetch();

        if (!$data) {
            $_SESSION['error'] = "Data pasien tidak ditemukan";
            header("Location: index.php?module=rekam_medis");
            exit;
        }

        // Ambil data status ginekologi
        $statusGinekologiModel = new StatusGinekologi($this->pdo);
        $statusGinekologi = $statusGinekologiModel->getStatusGinekologiByPasien($data['no_rkm_medis']);

        // Ambil data riwayat kehamilan
        $riwayatKehamilan = $this->rekamMedisModel->getRiwayatKehamilan($data['no_rkm_medis']);

        // Ambil data status obstetri
        $statusObstetri = $this->rekamMedisModel->getStatusObstetri($data['no_rkm_medis']);

        include 'modules/rekam_medis/views/form_penilaian_medis_ralan_kandungan.php';
    }

    public function edit_kunjungan()
    {
        try {
            error_log('=== Debug Edit Kunjungan ===');
            error_log('Timestamp: ' . date('Y-m-d H:i:s'));

            // Ambil no_rawat dari parameter
            $no_rawat = isset($_GET['no_rawat']) ? $_GET['no_rawat'] : null;
            error_log('no_rawat: ' . ($no_rawat ?? 'null'));

            if (!$no_rawat) {
                throw new Exception('Parameter no_rawat tidak ditemukan');
            }

            // Query untuk mengambil data kunjungan
            $stmt = $this->pdo->prepare("
                SELECT rp.*, p.nm_pasien 
                FROM reg_periksa rp 
                JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis 
                WHERE rp.no_rawat = ?
            ");
            $stmt->execute([$no_rawat]);
            $kunjungan = $stmt->fetch(PDO::FETCH_ASSOC);

            error_log('Data kunjungan: ' . json_encode($kunjungan));

            if (!$kunjungan) {
                throw new Exception('Data kunjungan tidak ditemukan');
            }

            // Tampilkan form edit
            include 'modules/rekam_medis/views/form_edit_kunjungan.php';
        } catch (Exception $e) {
            error_log('Error in edit_kunjungan: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            $_SESSION['error'] = $e->getMessage();

            // Jika kita memiliki no_rkm_medis, arahkan ke halaman detail pasien
            if (isset($kunjungan) && isset($kunjungan['no_rkm_medis'])) {
                header('Location: index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=' . $kunjungan['no_rkm_medis']);
            } else {
                header('Location: index.php?module=rekam_medis');
            }
            exit;
        }
    }

    public function update_kunjungan()
    {
        try {
            error_log('=== Debug Update Kunjungan ===');
            error_log('Timestamp: ' . date('Y-m-d H:i:s'));
            error_log('POST data: ' . json_encode($_POST));

            // Validasi input
            $required = ['no_rawat', 'tgl_registrasi', 'jam_reg', 'no_rkm_medis'];
            foreach ($required as $field) {
                if (!isset($_POST[$field]) || empty($_POST[$field])) {
                    throw new Exception("Field $field harus diisi");
                }
            }

            // Ambil data lama untuk logging
            $stmt = $this->pdo->prepare("SELECT * FROM reg_periksa WHERE no_rawat = ?");
            $stmt->execute([$_POST['no_rawat']]);
            $old_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$old_data) {
                throw new Exception("Data kunjungan dengan no_rawat " . $_POST['no_rawat'] . " tidak ditemukan");
            }

            error_log('Data lama: ' . json_encode($old_data));

            // Update data kunjungan - hanya kolom yang ada di tabel reg_periksa
            $query = "UPDATE reg_periksa SET 
                     tgl_registrasi = ?,
                     jam_reg = ?,
                     status_bayar = ?,
                     rincian = ?
                     WHERE no_rawat = ?";
            error_log('Query: ' . $query);

            $stmt = $this->pdo->prepare($query);
            if (!$stmt) {
                error_log('PDO prepare error: ' . json_encode($this->pdo->errorInfo()));
                throw new Exception('Gagal mempersiapkan query');
            }

            $params = [
                $_POST['tgl_registrasi'],
                $_POST['jam_reg'],
                $_POST['status_bayar'] ?? 'Belum Bayar',
                $_POST['rincian'] ?? null,
                $_POST['no_rawat']
            ];
            error_log('Execute params: ' . json_encode($params));

            $result = $stmt->execute($params);
            if (!$result) {
                error_log('PDO execute error: ' . json_encode($stmt->errorInfo()));
                throw new Exception('Gagal mengupdate data kunjungan');
            }

            // Ambil data baru untuk logging
            $stmt = $this->pdo->prepare("SELECT * FROM reg_periksa WHERE no_rawat = ?");
            $stmt->execute([$_POST['no_rawat']]);
            $new_data = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log('Data baru: ' . json_encode($new_data));

            error_log('Update successful');
            $_SESSION['success'] = 'Data kunjungan berhasil diupdate';
            header('Location: index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=' . $_POST['no_rkm_medis']);
            exit;
        } catch (Exception $e) {
            error_log('Error in update_kunjungan: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            $_SESSION['error'] = $e->getMessage();

            if (isset($_POST['no_rawat']) && isset($_POST['no_rkm_medis'])) {
                header('Location: index.php?module=rekam_medis&action=edit_kunjungan&no_rawat=' . $_POST['no_rawat']);
            } else {
                header('Location: index.php?module=rekam_medis');
            }
            exit;
        }
    }

    public function update_status()
    {
        // Fungsi ini tidak lagi diperlukan karena kolom 'stts' sudah dihapus
        $_SESSION['error'] = 'Fungsi ini tidak lagi tersedia karena perubahan struktur database';
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    public function hapus_kunjungan()
    {
        try {
            error_log("=== MULAI PROSES HAPUS KUNJUNGAN ===");

            // Pastikan no_rawat ada
            if (!isset($_GET['no_rawat']) || empty($_GET['no_rawat'])) {
                throw new Exception('Parameter no_rawat tidak ditemukan');
            }

            $no_rawat = $_GET['no_rawat'];
            error_log("No Rawat yang akan dihapus: " . $no_rawat);

            // Cek apakah data ada dan ambil no_rkm_medis
            error_log("Memeriksa keberadaan data...");
            $check_stmt = $this->pdo->prepare("SELECT no_rkm_medis FROM reg_periksa WHERE no_rawat = ?");
            $check_stmt->execute([$no_rawat]);
            $data = $check_stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) {
                error_log("Data tidak ditemukan untuk no_rawat: " . $no_rawat);
                throw new Exception("Data kunjungan tidak ditemukan");
            }

            $no_rkm_medis = $data['no_rkm_medis'];
            error_log("No RM ditemukan: " . $no_rkm_medis);

            // Mulai transaksi
            $this->pdo->beginTransaction();

            try {
                // Hapus dari tabel penilaian_medis_ralan_kandungan jika ada
                $stmt1 = $this->pdo->prepare("DELETE FROM penilaian_medis_ralan_kandungan WHERE no_rawat = ?");
                $stmt1->execute([$no_rawat]);
                error_log("Menghapus dari penilaian_medis_ralan_kandungan: " . $stmt1->rowCount() . " baris");

                // Hapus dari tabel tindakan_medis jika ada
                $stmt2 = $this->pdo->prepare("DELETE FROM tindakan_medis WHERE no_rawat = ?");
                $stmt2->execute([$no_rawat]);
                error_log("Menghapus dari tindakan_medis: " . $stmt2->rowCount() . " baris");

                // Hapus dari tabel reg_periksa
                $stmt3 = $this->pdo->prepare("DELETE FROM reg_periksa WHERE no_rawat = ?");
                $stmt3->execute([$no_rawat]);
                error_log("Menghapus dari reg_periksa: " . $stmt3->rowCount() . " baris");

                if ($stmt3->rowCount() === 0) {
                    throw new Exception("Gagal menghapus data kunjungan");
                }

                // Commit transaksi
                $this->pdo->commit();
                error_log("Transaksi berhasil di-commit");

                $_SESSION['success'] = "Kunjungan berhasil dihapus";
                header("Location: index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=" . $no_rkm_medis);
            } catch (Exception $e) {
                // Rollback jika terjadi error
                $this->pdo->rollBack();
                error_log("Rollback transaksi: " . $e->getMessage());
                $_SESSION['error'] = "Error: " . $e->getMessage();
                header("Location: index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=" . $no_rkm_medis);
            }
        } catch (Exception $e) {
            error_log("ERROR: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $_SESSION['error'] = "Error: " . $e->getMessage();

            // Jika kita memiliki no_rkm_medis, arahkan ke halaman detail pasien
            if (isset($no_rkm_medis)) {
                header("Location: index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=" . $no_rkm_medis);
            } else {
                // Jika tidak ada no_rkm_medis, baru arahkan ke daftar rekam medis
                header("Location: index.php?module=rekam_medis");
            }
        }
        exit;
    }

    public function update_status_bayar()
    {
        try {
            if (!isset($_GET['no_rawat'])) {
                throw new Exception('No rawat tidak ditemukan');
            }

            $no_rawat = $_GET['no_rawat'];

            if ($this->rekamMedisModel->updateStatusBayar($no_rawat)) {
                $_SESSION['success'] = 'Status pembayaran berhasil diubah menjadi Sudah Bayar';
            } else {
                $_SESSION['error'] = 'Gagal mengubah status pembayaran';
            }

            // Redirect kembali ke halaman detail pasien
            $no_rkm_medis = $this->rekamMedisModel->getNoRkmMedisByNoRawat($no_rawat);

            if ($no_rkm_medis) {
                header('Location: index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=' . $no_rkm_medis);
            } else {
                header('Location: index.php?module=rekam_medis');
            }
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: index.php?module=rekam_medis');
            exit;
        }
    }

    public function getPdoStatus()
    {
        return $this->rekamMedisModel->getPdoStatus();
    }

    // Fungsi untuk menampilkan form tambah status obstetri
    public function tambah_status_obstetri()
    {
        // Pastikan parameter no_rkm_medis tersedia
        if (!isset($_GET['no_rkm_medis'])) {
            $_SESSION['error'] = "Parameter no_rkm_medis tidak ditemukan";
            header("Location: index.php?module=rekam_medis");
            exit;
        }

        $no_rkm_medis = $_GET['no_rkm_medis'];
        $pasien = $this->rekamMedisModel->getPasienById($no_rkm_medis);

        if (!$pasien) {
            $_SESSION['error'] = "Data pasien tidak ditemukan";
            header("Location: index.php?module=rekam_medis");
            exit;
        }

        // Tampilkan form tambah status obstetri
        include 'modules/rekam_medis/views/form_status_obstetri.php';
    }

    // Fungsi untuk menyimpan data status obstetri
    public function simpan_status_obstetri()
    {
        // Debugging
        error_log("=== DEBUG SIMPAN STATUS OBSTETRI ===");
        error_log("POST data: " . print_r($_POST, true));

        // Validasi data yang dikirimkan
        if (!isset($_POST['no_rkm_medis']) || empty($_POST['no_rkm_medis'])) {
            $_SESSION['error'] = "No. Rekam Medis tidak boleh kosong";
            header("Location: index.php?module=rekam_medis");
            exit;
        }

        // Siapkan data untuk disimpan
        $data = [
            'no_rkm_medis' => $_POST['no_rkm_medis'],
            'gravida' => isset($_POST['gravida']) ? $_POST['gravida'] : null,
            'paritas' => isset($_POST['paritas']) ? $_POST['paritas'] : null,
            'abortus' => isset($_POST['abortus']) ? $_POST['abortus'] : null,
            'tb' => (isset($_POST['tb']) && $_POST['tb'] !== '' ? $_POST['tb'] : null),
            'tanggal_hpht' => isset($_POST['tanggal_hpht']) ? $_POST['tanggal_hpht'] : null,
            'tanggal_tp' => isset($_POST['tanggal_tp']) ? $_POST['tanggal_tp'] : null,
            'tanggal_tp_penyesuaian' => isset($_POST['tanggal_tp_penyesuaian']) ? $_POST['tanggal_tp_penyesuaian'] : null,
            'faktor_risiko_umum' => isset($_POST['faktor_risiko_umum']) ? $_POST['faktor_risiko_umum'] : [],
            'faktor_risiko_obstetri' => isset($_POST['faktor_risiko_obstetri']) ? $_POST['faktor_risiko_obstetri'] : [],
            'faktor_risiko_preeklampsia' => isset($_POST['faktor_risiko_preeklampsia']) ? $_POST['faktor_risiko_preeklampsia'] : [],
            'hasil_faktor_risiko' => isset($_POST['hasil_faktor_risiko']) ? $_POST['hasil_faktor_risiko'] : null
        ];

        // Simpan data status obstetri
        $result = $this->rekamMedisModel->tambahStatusObstetri($data);

        if ($result) {
            $_SESSION['success'] = "Data status obstetri berhasil disimpan";
        } else {
            $_SESSION['error'] = "Gagal menyimpan data status obstetri";
        }

        // Cek source parameter untuk redirect
        $source = isset($_POST['source']) ? $_POST['source'] : '';
        $no_rawat = isset($_POST['no_rawat']) ? $_POST['no_rawat'] : '';
        
        // Gunakan helper function untuk redirect
        handleRedirect($source, $no_rawat, $_POST['no_rkm_medis']);
        exit;
    }

    // Fungsi untuk menampilkan form edit status obstetri
    public function edit_status_obstetri()
    {
        // Pastikan parameter id tersedia
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            $_SESSION['error'] = "Parameter ID tidak ditemukan";
            header("Location: index.php?module=rekam_medis");
            exit;
        }

        $id_status_obstetri = $_GET['id'];
        $statusObstetri = $this->rekamMedisModel->getStatusObstetriById($id_status_obstetri);

        if (!$statusObstetri) {
            $_SESSION['error'] = "Data status obstetri tidak ditemukan";
            header("Location: index.php?module=rekam_medis");
            exit;
        }

        $pasien = $this->rekamMedisModel->getPasienById($statusObstetri['no_rkm_medis']);

        // Tampilkan form edit status obstetri
        include 'modules/rekam_medis/views/form_status_obstetri.php';
    }

    // Fungsi untuk mengupdate data status obstetri
    public function update_status_obstetri()
    {
        // Debugging
        error_log("=== DEBUG UPDATE STATUS OBSTETRI ===");
        error_log("POST data: " . print_r($_POST, true));

        // Validasi data yang dikirimkan
        if (!isset($_POST['id_status_obstetri']) || empty($_POST['id_status_obstetri'])) {
            $_SESSION['error'] = "ID Status Obstetri tidak boleh kosong";
            header("Location: index.php?module=rekam_medis");
            exit;
        }

        // Siapkan data untuk diupdate
        $data = [
            'id_status_obstetri' => $_POST['id_status_obstetri'],
            'gravida' => isset($_POST['gravida']) ? $_POST['gravida'] : null,
            'paritas' => isset($_POST['paritas']) ? $_POST['paritas'] : null,
            'abortus' => isset($_POST['abortus']) ? $_POST['abortus'] : null,
            'tb' => (isset($_POST['tb']) && $_POST['tb'] !== '' ? $_POST['tb'] : null),
            'tanggal_hpht' => isset($_POST['tanggal_hpht']) ? $_POST['tanggal_hpht'] : null,
            'tanggal_tp' => isset($_POST['tanggal_tp']) ? $_POST['tanggal_tp'] : null,
            'tanggal_tp_penyesuaian' => isset($_POST['tanggal_tp_penyesuaian']) ? $_POST['tanggal_tp_penyesuaian'] : null,
            'faktor_risiko_umum' => isset($_POST['faktor_risiko_umum']) ? $_POST['faktor_risiko_umum'] : [],
            'faktor_risiko_obstetri' => isset($_POST['faktor_risiko_obstetri']) ? $_POST['faktor_risiko_obstetri'] : [],
            'faktor_risiko_preeklampsia' => isset($_POST['faktor_risiko_preeklampsia']) ? $_POST['faktor_risiko_preeklampsia'] : [],
            'hasil_faktor_risiko' => isset($_POST['hasil_faktor_risiko']) ? $_POST['hasil_faktor_risiko'] : null
        ];

        // Update data status obstetri
        $result = $this->rekamMedisModel->updateStatusObstetri($data);

        if ($result) {
            $_SESSION['success'] = "Data status obstetri berhasil diupdate";
        } else {
            $_SESSION['error'] = "Gagal mengupdate data status obstetri";
        }

        // Cek source parameter untuk redirect
        $source = isset($_POST['source']) ? $_POST['source'] : '';
        $no_rawat = isset($_POST['no_rawat']) ? $_POST['no_rawat'] : '';
        
        // Gunakan helper function untuk redirect
        handleRedirect($source, $no_rawat, $_POST['no_rkm_medis']);
        exit;
    }

    // Fungsi untuk menghapus data status obstetri
    public function hapus_status_obstetri()
    {
        // Pastikan parameter id tersedia
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            $_SESSION['error'] = "Parameter ID tidak ditemukan";
            header("Location: index.php?module=rekam_medis");
            exit;
        }

        $id_status_obstetri = $_GET['id'];
        $statusObstetri = $this->rekamMedisModel->getStatusObstetriById($id_status_obstetri);

        if (!$statusObstetri) {
            $_SESSION['error'] = "Data status obstetri tidak ditemukan";
            header("Location: index.php?module=rekam_medis");
            exit;
        }

        // Simpan no_rkm_medis untuk redirect
        $no_rkm_medis = $statusObstetri['no_rkm_medis'];

        // Hapus data status obstetri
        $result = $this->rekamMedisModel->hapusStatusObstetri($id_status_obstetri);

        if ($result) {
            $_SESSION['success'] = "Data status obstetri berhasil dihapus";
        } else {
            $_SESSION['error'] = "Gagal menghapus data status obstetri";
        }

        // Cek source parameter untuk redirect
        $source = isset($_GET['source']) ? $_GET['source'] : '';
        $no_rawat = isset($_GET['no_rawat']) ? $_GET['no_rawat'] : '';

        // Gunakan helper function untuk redirect
        handleRedirect($source, $no_rawat, $no_rkm_medis);
        exit;
    }

    // Fungsi untuk menampilkan form tambah riwayat kehamilan
    public function tambah_riwayat_kehamilan()
    {
        try {
            // Pastikan parameter no_rkm_medis ada
            if (!isset($_GET['no_rkm_medis'])) {
                throw new Exception('Parameter ID pasien tidak ditemukan');
            }

            $no_rkm_medis = $_GET['no_rkm_medis'];

            // Query untuk mendapatkan data pasien
            $query = "SELECT * FROM pasien WHERE no_rkm_medis = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$no_rkm_medis]);
            $pasien = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$pasien) {
                throw new Exception('Data pasien tidak ditemukan');
            }

            // Set source page untuk redirect setelah simpan
            $_SESSION['source_page'] = $_GET['source'] ?? 'detailPasien';

            // Siapkan data untuk view
            $data = [
                'no_rkm_medis' => $no_rkm_medis,
                'pasien' => $pasien
            ];

            // Include view form tambah riwayat kehamilan
            include 'modules/rekam_medis/views/form_tambah_riwayat_kehamilan.php';
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: index.php?module=rekam_medis&action=data_pasien');
            exit;
        }
    }

    // Fungsi untuk menyimpan data riwayat kehamilan
    public function simpan_riwayat_kehamilan()
    {
        // Debugging
        error_log("=== DEBUG SIMPAN RIWAYAT KEHAMILAN ===");
        error_log("POST data: " . print_r($_POST, true));

        // Validasi data yang dikirimkan
        if (!isset($_POST['no_rkm_medis']) || empty($_POST['no_rkm_medis'])) {
            $_SESSION['error_message'] = "Nomor rekam medis tidak boleh kosong";
            header("Location: index.php?module=rekam_medis");
            exit;
        }

        if (!isset($_POST['no_urut_kehamilan']) || empty($_POST['no_urut_kehamilan'])) {
            $_SESSION['error_message'] = "Urutan kehamilan tidak boleh kosong";
            header("Location: index.php?module=rekam_medis&action=tambah_riwayat_kehamilan&no_rkm_medis=" . $_POST['no_rkm_medis']);
            exit;
        }

        if (!isset($_POST['status_kehamilan']) || empty($_POST['status_kehamilan'])) {
            $_SESSION['error_message'] = "Status kehamilan tidak boleh kosong";
            header("Location: index.php?module=rekam_medis&action=tambah_riwayat_kehamilan&no_rkm_medis=" . $_POST['no_rkm_medis']);
            exit;
        }

        // Siapkan data untuk disimpan
        $data = [
            'no_rkm_medis' => $_POST['no_rkm_medis'],
            'no_urut_kehamilan' => $_POST['no_urut_kehamilan'],
            'status_kehamilan' => $_POST['status_kehamilan'],
            'jenis_persalinan' => isset($_POST['jenis_persalinan']) ? $_POST['jenis_persalinan'] : null,
            'tempat_persalinan' => isset($_POST['tempat_persalinan']) ? $_POST['tempat_persalinan'] : null,
            'penolong_persalinan' => isset($_POST['penolong_persalinan']) ? $_POST['penolong_persalinan'] : null,
            'tahun_persalinan' => isset($_POST['tahun_persalinan']) && !empty($_POST['tahun_persalinan']) ? $_POST['tahun_persalinan'] : null,
            'jenis_kelamin_anak' => isset($_POST['jenis_kelamin_anak']) ? $_POST['jenis_kelamin_anak'] : null,
            'berat_badan_lahir' => isset($_POST['berat_badan_lahir']) && !empty($_POST['berat_badan_lahir']) ? $_POST['berat_badan_lahir'] : null,
            'kondisi_lahir' => isset($_POST['kondisi_lahir']) ? $_POST['kondisi_lahir'] : null,
            'komplikasi_kehamilan' => isset($_POST['komplikasi_kehamilan']) ? $_POST['komplikasi_kehamilan'] : null,
            'komplikasi_persalinan' => isset($_POST['komplikasi_persalinan']) ? $_POST['komplikasi_persalinan'] : null,
            'catatan' => isset($_POST['catatan']) ? $_POST['catatan'] : null
        ];

        // Simpan data riwayat kehamilan
        $result = $this->rekamMedisModel->tambahRiwayatKehamilan($data);

        if ($result) {
            $_SESSION['success_message'] = "Data riwayat kehamilan berhasil disimpan";
        } else {
            if (!empty($_SESSION['error_message'])) {
                // Biarkan pesan error_message dari PDO tetap
            } else {
                $_SESSION['error_message'] = "Gagal menyimpan data riwayat kehamilan";
            }
        }

        // Redirect ke halaman detail pasien
        $source = isset($_POST['source']) ? $_POST['source'] : '';
        $no_rawat = isset($_POST['no_rawat']) ? $_POST['no_rawat'] : '';
        handleRedirect($source, $no_rawat, $_POST['no_rkm_medis']);
        exit;
    }

    // Fungsi untuk menampilkan form edit riwayat kehamilan
    public function edit_riwayat_kehamilan()
    {
        // Pastikan parameter id ada
        if (!isset($_GET['id'])) {
            $_SESSION['error_message'] = "ID riwayat kehamilan tidak ditemukan";
            header("Location: index.php?module=rekam_medis&action=data_pasien");
            exit;
        }

        // Tampilkan form edit riwayat kehamilan
        include 'modules/rekam_medis/views/form_edit_riwayat_kehamilan.php';
    }

    // Fungsi untuk mengupdate data riwayat kehamilan
    public function update_riwayat_kehamilan()
    {
        // Debugging
        error_log("=== DEBUG UPDATE RIWAYAT KEHAMILAN ===");
        error_log("POST data: " . print_r($_POST, true));

        // Validasi data yang dikirimkan
        if (!isset($_POST['id_riwayat_kehamilan']) || empty($_POST['id_riwayat_kehamilan'])) {
            $_SESSION['error_message'] = "ID Riwayat Kehamilan tidak boleh kosong";
            header("Location: index.php?module=rekam_medis");
            exit;
        }

        // Siapkan data untuk diupdate
        $data = [
            'id_riwayat_kehamilan' => $_POST['id_riwayat_kehamilan'],
            'no_urut_kehamilan' => $_POST['no_urut_kehamilan'],
            'status_kehamilan' => $_POST['status_kehamilan'],
            'jenis_persalinan' => isset($_POST['jenis_persalinan']) ? $_POST['jenis_persalinan'] : null,
            'tempat_persalinan' => isset($_POST['tempat_persalinan']) ? $_POST['tempat_persalinan'] : null,
            'penolong_persalinan' => isset($_POST['penolong_persalinan']) ? $_POST['penolong_persalinan'] : null,
            'tahun_persalinan' => isset($_POST['tahun_persalinan']) && !empty($_POST['tahun_persalinan']) ? $_POST['tahun_persalinan'] : null,
            'jenis_kelamin_anak' => isset($_POST['jenis_kelamin_anak']) ? $_POST['jenis_kelamin_anak'] : null,
            'berat_badan_lahir' => isset($_POST['berat_badan_lahir']) && !empty($_POST['berat_badan_lahir']) ? $_POST['berat_badan_lahir'] : null,
            'kondisi_lahir' => isset($_POST['kondisi_lahir']) ? $_POST['kondisi_lahir'] : null,
            'komplikasi_kehamilan' => isset($_POST['komplikasi_kehamilan']) ? $_POST['komplikasi_kehamilan'] : null,
            'komplikasi_persalinan' => isset($_POST['komplikasi_persalinan']) ? $_POST['komplikasi_persalinan'] : null,
            'catatan' => isset($_POST['catatan']) ? $_POST['catatan'] : null
        ];

        // Update data riwayat kehamilan
        $result = $this->rekamMedisModel->updateRiwayatKehamilan($data);

        if ($result) {
            $_SESSION['success_message'] = "Data riwayat kehamilan berhasil diupdate";
        } else {
            $_SESSION['error_message'] = "Gagal mengupdate data riwayat kehamilan";
        }

        // Redirect ke halaman detail pasien
        $source = isset($_POST['source']) ? $_POST['source'] : '';
        $no_rawat = isset($_POST['no_rawat']) ? $_POST['no_rawat'] : '';
        handleRedirect($source, $no_rawat, $_POST['no_rkm_medis']);
        exit;
    }

    // Fungsi untuk menghapus data riwayat kehamilan
    public function hapus_riwayat_kehamilan()
    {
        // Pastikan parameter id ada
        if (!isset($_GET['id'])) {
            $_SESSION['error_message'] = "ID riwayat kehamilan tidak ditemukan";
            header("Location: index.php?module=rekam_medis&action=data_pasien");
            exit;
        }

        // Ambil data riwayat kehamilan untuk mendapatkan no_rkm_medis
        $riwayatKehamilan = $this->rekamMedisModel->getRiwayatKehamilanById($_GET['id']);
        if (!$riwayatKehamilan) {
            $_SESSION['error_message'] = "Data riwayat kehamilan tidak ditemukan";
            header("Location: index.php?module=rekam_medis&action=data_pasien");
            exit;
        }

        // Hapus data riwayat kehamilan
        $result = $this->rekamMedisModel->hapusRiwayatKehamilan($_GET['id']);

        if ($result) {
            $_SESSION['success_message'] = "Data riwayat kehamilan berhasil dihapus";
        } else {
            $_SESSION['error_message'] = "Gagal menghapus data riwayat kehamilan";
        }

        // Redirect ke halaman detail pasien
        header("Location: index.php?module=rekam_medis&action=detail_pasien&no_rkm_medis=" . $riwayatKehamilan['no_rkm_medis']);
        exit;
    }

    public function tambah_status_ginekologi()
    {
        // Pastikan no_rkm_medis tersedia
        if (!isset($_GET['no_rkm_medis'])) {
            $_SESSION['error'] = 'Nomor rekam medis tidak ditemukan';
            header('Location: index.php?module=rekam_medis');
            exit;
        }

        $no_rkm_medis = $_GET['no_rkm_medis'];

        // Tampilkan form
        require_once('modules/rekam_medis/views/form_status_ginekologi.php');
    }

    public function simpan_status_ginekologi()
    {
        // Debugging
        error_log("=== DEBUG SIMPAN STATUS GINEKOLOGI ===");
        error_log("POST data: " . print_r($_POST, true));

        try {
            // Validasi data yang dikirimkan
            if (!isset($_POST['no_rkm_medis']) || empty($_POST['no_rkm_medis'])) {
                throw new Exception("No. Rekam Medis tidak boleh kosong");
            }

            // Inisialisasi model StatusGinekologi
            require_once __DIR__ . '/../models/StatusGinekologi.php';
            $statusGinekologiModel = new StatusGinekologi($this->pdo);

            // Siapkan data untuk disimpan
            $data = [
                'no_rkm_medis' => $_POST['no_rkm_medis'],
                'parturien' => isset($_POST['parturien']) && $_POST['parturien'] !== '' ? (int)$_POST['parturien'] : null,
                'abortus' => isset($_POST['abortus']) && $_POST['abortus'] !== '' ? (int)$_POST['abortus'] : null,
                'hari_pertama_haid_terakhir' => isset($_POST['hpht']) && !empty($_POST['hpht']) ? $_POST['hpht'] : null,
                'kontrasepsi_terakhir' => isset($_POST['kontrasepsi']) ? $_POST['kontrasepsi'] : 'Tidak Ada',
                'lama_menikah_th' => isset($_POST['lama_menikah_th']) && $_POST['lama_menikah_th'] !== '' ? (float)$_POST['lama_menikah_th'] : null
            ];

            // Validasi enum kontrasepsi
            $enum_kontrasepsi = ['Tidak Ada', 'Pil KB', 'Suntik KB', 'Spiral/IUD', 'Implant', 'MOW', 'MOP', 'Kondom'];
            if (!in_array($data['kontrasepsi_terakhir'], $enum_kontrasepsi)) {
                $data['kontrasepsi_terakhir'] = 'Tidak Ada';
            }

            // Simpan data status ginekologi
            $result = $statusGinekologiModel->tambahStatusGinekologi($data);

            if ($result) {
                $_SESSION['success'] = "Data status ginekologi berhasil disimpan";
            } else {
                throw new Exception("Gagal menyimpan data status ginekologi");
            }

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            error_log("Error in simpan_status_ginekologi: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
        }

        // Cek source parameter untuk redirect
        $source = isset($_POST['source']) ? $_POST['source'] : '';
        $no_rawat = isset($_POST['no_rawat']) ? $_POST['no_rawat'] : '';
        
        // Gunakan helper function untuk redirect
        if (function_exists('handleRedirect')) {
            handleRedirect($source, $no_rawat, $_POST['no_rkm_medis']);
        } else {
            // Fallback jika handleRedirect tidak ada
            if (!empty($source) && $source == 'form_penilaian_medis_ralan_kandungan' && !empty($no_rawat)) {
                header("Location: index.php?module=rekam_medis&action=form_penilaian_medis_ralan_kandungan&no_rawat=" . $no_rawat);
            } else {
                header("Location: index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=" . $_POST['no_rkm_medis']);
            }
            exit;
        }
    }

    public function edit_status_ginekologi()
    {
        // Debugging
        error_log("=== Mulai proses edit_status_ginekologi ===");
        error_log("GET parameters: " . json_encode($_GET));
        error_log("SESSION: " . json_encode($_SESSION));

        // Pastikan parameter id tersedia
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            $_SESSION['error'] = "Parameter ID tidak ditemukan";
            header("Location: index.php?module=rekam_medis");
            exit;
        }

        $id_status_ginekologi = $_GET['id'];
        $source = isset($_GET['source']) ? $_GET['source'] : '';
        $no_rawat = isset($_GET['no_rawat']) ? $_GET['no_rawat'] : '';

        // Debugging source parameter
        error_log("Source from URL: " . $source);

        // Store source in session explicitly
        if (!empty($source)) {
            $_SESSION['edit_source'] = $source;
            error_log("Stored source in session: " . $source);
        }

        // Simpan no_rawat dalam session jika tersedia
        if (!empty($no_rawat)) {
            $_SESSION['no_rawat'] = $no_rawat;
        }

        error_log("ID status ginekologi yang akan diedit: " . $id_status_ginekologi);

        // Gunakan model StatusGinekologi untuk mendapatkan data
        $statusGinekologiModel = new StatusGinekologi($this->pdo);
        $status_ginekologi = $statusGinekologiModel->getStatusGinekologiById($id_status_ginekologi);

        if (!$status_ginekologi) {
            $_SESSION['error'] = "Data status ginekologi tidak ditemukan";
            header("Location: index.php?module=rekam_medis");
            exit;
        }

        $pasien = $this->rekamMedisModel->getPasienById($status_ginekologi['no_rkm_medis']);

        // Debug untuk memeriksa isi data
        error_log("Data status ginekologi ditemukan: " . json_encode($status_ginekologi));

        // Tampilkan form edit status ginekologi
        include 'modules/rekam_medis/views/form_edit_status_ginekologi.php';
    }

    public function update_status_ginekologi()
{
    // Debugging
    error_log("=== DEBUG UPDATE STATUS GINEKOLOGI (BARU) ===");
    error_log("POST data: " . print_r($_POST, true));

    try {
        // Validasi input
        if (!isset($_POST['id_status_ginekologi']) || empty($_POST['id_status_ginekologi'])) {
            $_SESSION['error'] = "ID status ginekologi tidak boleh kosong";
            header("Location: index.php?module=rekam_medis");
            exit;
        }
        if (!isset($_POST['no_rkm_medis']) || empty($_POST['no_rkm_medis'])) {
            $_SESSION['error'] = "Nomor rekam medis tidak boleh kosong";
            header("Location: index.php?module=rekam_medis");
            exit;
        }

        $id_status_ginekologi = $_POST['id_status_ginekologi'];
        $data = [
            'parturien' => isset($_POST['parturien']) ? (int)$_POST['parturien'] : 0,
            'abortus' => isset($_POST['abortus']) ? (int)$_POST['abortus'] : 0,
            'hari_pertama_haid_terakhir' => !empty($_POST['hpht']) ? $_POST['hpht'] : null,
            'kontrasepsi_terakhir' => !empty($_POST['kontrasepsi']) ? $_POST['kontrasepsi'] : 'Tidak Ada',
            'lama_menikah_th' => isset($_POST['lama_menikah_th']) ? (float)$_POST['lama_menikah_th'] : 0
        ];

        // Gunakan model StatusGinekologi (PDO)
        $statusGinekologiModel = new StatusGinekologi($this->conn);
        $result = $statusGinekologiModel->updateStatusGinekologi($id_status_ginekologi, $data);

        if ($result) {
            $_SESSION['success'] = "Data status ginekologi berhasil diupdate";
        } else {
            $_SESSION['error'] = "Gagal mengupdate data status ginekologi";
        }

        // Ambil parameter untuk redirect
        $source = isset($_POST['source']) ? $_POST['source'] : '';
        $no_rawat = isset($_POST['no_rawat']) ? $_POST['no_rawat'] : '';

        // Gunakan helper function untuk redirect
        handleRedirect($source, $no_rawat, $_POST['no_rkm_medis']);
        exit;
    } catch (Exception $e) {
        error_log("Error in update_status_ginekologi: " . $e->getMessage());
        $_SESSION['error'] = $e->getMessage();
        if (isset($_POST['id_status_ginekologi'])) {
            header("Location: index.php?module=rekam_medis&action=edit_status_ginekologi&id=" . $_POST['id_status_ginekologi']);
        } else {
            header("Location: index.php?module=rekam_medis");
        }
        exit;
    }
}
 
    public function hapus_status_ginekologi()
    {
        // Debugging
        error_log("=== Mulai proses hapus_status_ginekologi ===");

        try {
            // Validasi input
            if (!isset($_GET['id']) || empty($_GET['id'])) {
                throw new Exception("ID status ginekologi tidak valid");
            }

            $id_status_ginekologi = $_GET['id'];
            error_log("ID status ginekologi yang akan dihapus: " . $id_status_ginekologi);

            // Gunakan model StatusGinekologi untuk mendapatkan data
            $statusGinekologiModel = new StatusGinekologi($this->pdo);
            $statusGinekologi = $statusGinekologiModel->getStatusGinekologiById($id_status_ginekologi);

            if (!$statusGinekologi) {
                throw new Exception("Data status ginekologi tidak ditemukan");
            }

            $no_rkm_medis = $statusGinekologi['no_rkm_medis'];
            error_log("No RM Medis dari status ginekologi: " . $no_rkm_medis);

            // Gunakan model StatusGinekologi untuk menghapus data
            $result = $statusGinekologiModel->hapusStatusGinekologi($id_status_ginekologi);

            if ($result) {
                $_SESSION['success'] = 'Data status ginekologi berhasil dihapus';
                error_log("Data status ginekologi berhasil dihapus untuk ID: $id_status_ginekologi");
            } else {
                throw new Exception("Gagal menghapus data status ginekologi");
            }

            // Cek source parameter untuk redirect
            $source = isset($_GET['source']) ? $_GET['source'] : '';
            
            // Get no_rawat from GET or session
            $no_rawat = '';
            if (isset($_GET['no_rawat']) && !empty($_GET['no_rawat'])) {
                $no_rawat = $_GET['no_rawat'];
            } elseif (isset($_SESSION['no_rawat']) && !empty($_SESSION['no_rawat'])) {
                $no_rawat = $_SESSION['no_rawat'];
            }
            
            // Use helper function for consistent redirect handling
            handleRedirect($source, $no_rawat, $no_rkm_medis);
            exit;
        } catch (Exception $e) {
            error_log("Error in hapus_status_ginekologi: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();

            // Redirect ke halaman sebelumnya atau ke daftar pasien jika terjadi error
            if (isset($_SERVER['HTTP_REFERER'])) {
                header("Location: " . $_SERVER['HTTP_REFERER']);
            } else {
                header("Location: index.php?module=rekam_medis&action=data_pasien");
            }
            exit;
        }
    }

    public function generate_pdf()
    {
        if (!isset($_GET['no_rkm_medis'])) {
            die("Nomor rekam medis tidak ditemukan");
        }

        $no_rkm_medis = $_GET['no_rkm_medis'];

        try {
            // Query untuk mendapatkan data pasien
            $query_pasien = "SELECT * FROM pasien WHERE no_rkm_medis = :no_rkm_medis";
            $stmt_pasien = $this->pdo->prepare($query_pasien);
            $stmt_pasien->execute([':no_rkm_medis' => $no_rkm_medis]);
            $pasien = $stmt_pasien->fetch(PDO::FETCH_ASSOC);

            if (!$pasien) {
                die("Data pasien tidak ditemukan");
            }

            // Query untuk status obstetri
            $query_obstetri = "SELECT * FROM status_obstetri WHERE no_rkm_medis = :no_rkm_medis ORDER BY tanggal_tp_penyesuaian DESC LIMIT 1";
            $stmt_obstetri = $this->pdo->prepare($query_obstetri);
            $stmt_obstetri->execute([':no_rkm_medis' => $no_rkm_medis]);
            $obstetri = $stmt_obstetri->fetch(PDO::FETCH_ASSOC);

            if (!$obstetri) {
                die("Data status obstetri tidak ditemukan");
            }

            // Generate PDF
            require_once('modules/rekam_medis/generate_resume_pdf.php');
        } catch (PDOException $e) {
            error_log("Database error in generate_pdf: " . $e->getMessage());
            die("Error: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("General error in generate_pdf: " . $e->getMessage());
            die("Error: " . $e->getMessage());
        }
    }

    public function generate_status_obstetri_pdf()
    {
        if (!isset($_GET['no_rkm_medis'])) {
            die("Nomor rekam medis tidak ditemukan");
        }

        $no_rkm_medis = $_GET['no_rkm_medis'];

        try {
            // Query untuk mendapatkan data pasien
            $query_pasien = "SELECT * FROM pasien WHERE no_rkm_medis = :no_rkm_medis";
            $stmt_pasien = $this->pdo->prepare($query_pasien);
            $stmt_pasien->execute([':no_rkm_medis' => $no_rkm_medis]);
            $pasien = $stmt_pasien->fetch(PDO::FETCH_ASSOC);

            if (!$pasien) {
                die("Data pasien tidak ditemukan");
            }

            // Query untuk status obstetri
            $query_obstetri = "SELECT * FROM status_obstetri WHERE no_rkm_medis = :no_rkm_medis ORDER BY tanggal_tp_penyesuaian DESC LIMIT 1";
            $stmt_obstetri = $this->pdo->prepare($query_obstetri);
            $stmt_obstetri->execute([':no_rkm_medis' => $no_rkm_medis]);
            $obstetri = $stmt_obstetri->fetch(PDO::FETCH_ASSOC);

            if (!$obstetri) {
                die("Data status obstetri tidak ditemukan");
            }

            // Generate PDF
            require_once('modules/rekam_medis/generate_status_obstetri_pdf.php');
        } catch (PDOException $e) {
            error_log("Database error in generate_status_obstetri_pdf: " . $e->getMessage());
            die("Error: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("General error in generate_status_obstetri_pdf: " . $e->getMessage());
            die("Error: " . $e->getMessage());
        }
    }

    public function generate_status_ginekologi_pdf()
    {
        if (!isset($_GET['no_rkm_medis'])) {
            die("Nomor rekam medis tidak ditemukan");
        }

        $no_rkm_medis = $_GET['no_rkm_medis'];

        try {
            // Query untuk mendapatkan data pasien
            $query_pasien = "SELECT * FROM pasien WHERE no_rkm_medis = :no_rkm_medis";
            $stmt_pasien = $this->pdo->prepare($query_pasien);
            $stmt_pasien->execute([':no_rkm_medis' => $no_rkm_medis]);
            $pasien = $stmt_pasien->fetch(PDO::FETCH_ASSOC);

            if (!$pasien) {
                die("Data pasien tidak ditemukan");
            }

            // Query untuk status ginekologi
            $query_ginekologi = "SELECT * FROM status_ginekologi WHERE no_rkm_medis = :no_rkm_medis ORDER BY created_at DESC LIMIT 1";
            $stmt_ginekologi = $this->pdo->prepare($query_ginekologi);
            $stmt_ginekologi->execute([':no_rkm_medis' => $no_rkm_medis]);
            $statusGinekologi = $stmt_ginekologi->fetch(PDO::FETCH_ASSOC);

            if (!$statusGinekologi) {
                die("Data status ginekologi tidak ditemukan");
            }

            // Query untuk data pemeriksaan dari penilaian_medis_ralan_kandungan
            // Cari berdasarkan no_rawat yang mengandung no_rkm_medis
            $query_pemeriksaan = "
                SELECT * FROM penilaian_medis_ralan_kandungan 
                WHERE no_rawat IN (
                    SELECT no_rawat FROM reg_periksa WHERE no_rkm_medis = :no_rkm_medis
                )
                ORDER BY tanggal DESC LIMIT 1
            ";
            $stmt_pemeriksaan = $this->pdo->prepare($query_pemeriksaan);
            $stmt_pemeriksaan->execute([':no_rkm_medis' => $no_rkm_medis]);
            $pemeriksaan = $stmt_pemeriksaan->fetch(PDO::FETCH_ASSOC);

            if (!$pemeriksaan || empty($pemeriksaan['edukasi'])) {
                die("Data edukasi tidak ditemukan");
            }

            // Generate PDF
            require_once('modules/rekam_medis/generate_status_ginekologi_pdf.php');
        } catch (PDOException $e) {
            error_log("Database error in generate_status_ginekologi_pdf: " . $e->getMessage());
            die("Error: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("General error in generate_status_ginekologi_pdf: " . $e->getMessage());
            die("Error: " . $e->getMessage());
        }
    }

    public function generate_edukasi_pdf()
    {
        if (!isset($_GET['no_rkm_medis'])) {
            die("Nomor rekam medis tidak ditemukan");
        }

        $no_rkm_medis = $_GET['no_rkm_medis'];

        try {
            // Query untuk mendapatkan data pasien
            $query_pasien = "SELECT * FROM pasien WHERE no_rkm_medis = :no_rkm_medis";
            $stmt_pasien = $this->pdo->prepare($query_pasien);
            $stmt_pasien->execute([':no_rkm_medis' => $no_rkm_medis]);
            $pasien = $stmt_pasien->fetch(PDO::FETCH_ASSOC);

            if (!$pasien) {
                die("Data pasien tidak ditemukan");
            }

            // Query untuk data pemeriksaan dari penilaian_medis_ralan_kandungan
            // Cari berdasarkan no_rawat yang mengandung no_rkm_medis
            $query_pemeriksaan = "
                SELECT * FROM penilaian_medis_ralan_kandungan 
                WHERE no_rawat IN (
                    SELECT no_rawat FROM reg_periksa WHERE no_rkm_medis = :no_rkm_medis
                )
                ORDER BY tanggal DESC LIMIT 1
            ";
            $stmt_pemeriksaan = $this->pdo->prepare($query_pemeriksaan);
            $stmt_pemeriksaan->execute([':no_rkm_medis' => $no_rkm_medis]);
            $pemeriksaan = $stmt_pemeriksaan->fetch(PDO::FETCH_ASSOC);

            // Jika tidak ada data pemeriksaan, buat array kosong
            if (!$pemeriksaan) {
                $pemeriksaan = ['edukasi' => 'Tidak ada data edukasi'];
            }

            // Generate PDF
            require_once('modules/rekam_medis/generate_edukasi_pdf.php');
        } catch (PDOException $e) {
            error_log("Database error in generate_edukasi_pdf: " . $e->getMessage());
            die("Error: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("General error in generate_edukasi_pdf: " . $e->getMessage());
            die("Error: " . $e->getMessage());
        }
    }

    public function daftarAtensi()
    {
        try {
            // Pastikan koneksi database tersedia
            if (!$this->pdo) {
                error_log("Database connection not available in daftarAtensi");
                throw new Exception("Koneksi database tidak tersedia");
            }
            
            // Gunakan pendekatan yang lebih aman dengan PHP untuk memfilter data
            // Ambil semua data terlebih dahulu
            $query = "SELECT 
                pmrk.no_rawat,
                pmrk.tanggal,
                pmrk.tanggal_kontrol,
                pmrk.atensi,
                pmrk.diagnosis,
                pmrk.tata as keterangan,
                pmrk.sudah_follow_up,
                p.nm_pasien as nama_pasien,
                p.no_tlp,
                p.catatan_pasien,
                rp.no_rkm_medis
            FROM penilaian_medis_ralan_kandungan pmrk
            JOIN reg_periksa rp ON pmrk.no_rawat = rp.no_rawat
            JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
            WHERE pmrk.atensi = '1' OR pmrk.tanggal_kontrol IS NOT NULL";

            // Log query untuk debugging
            error_log("Executing query in daftarAtensi: " . $query);

            $stmt = $this->pdo->prepare($query);

            if (!$stmt) {
                error_log("Failed to prepare statement: " . print_r($this->pdo->errorInfo(), true));
                throw new Exception("Gagal mempersiapkan query");
            }

            $stmt->execute();

            if ($stmt->errorCode() !== '00000') {
                error_log("Error executing statement: " . print_r($stmt->errorInfo(), true));
                throw new Exception("Gagal menjalankan query");
            }

            $allResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($allResults === false) {
                error_log("Error fetching results: " . print_r($stmt->errorInfo(), true));
                throw new Exception("Gagal mengambil data hasil query");
            }
            
            // Filter hasil dengan PHP untuk menghindari masalah tanggal di SQL
            $result = [];
            foreach ($allResults as $row) {
                // Validasi tanggal_kontrol
                if ($row['atensi'] == '1' || 
                    ($row['tanggal_kontrol'] && 
                     $row['tanggal_kontrol'] != '0000-00-00' && 
                     $row['tanggal_kontrol'] != '')) {
                    
                    // Pastikan tanggal_kontrol valid atau null
                    if ($row['tanggal_kontrol'] == '' || $row['tanggal_kontrol'] == '0000-00-00') {
                        $row['tanggal_kontrol'] = null;
                    }
                    
                    $result[] = $row;
                }
            }
            
            // Urutkan hasil berdasarkan tanggal_kontrol
            usort($result, function($a, $b) {
                // Tanggal null atau kosong selalu di akhir
                if (empty($a['tanggal_kontrol']) && empty($b['tanggal_kontrol'])) return 0;
                if (empty($a['tanggal_kontrol'])) return 1;
                if (empty($b['tanggal_kontrol'])) return -1;
                
                // Bandingkan tanggal
                return strtotime($a['tanggal_kontrol']) - strtotime($b['tanggal_kontrol']);
            });

            // Set page title
            $page_title = "Daftar Atensi Pasien";

            // Include the view
            include __DIR__ . '/../views/daftar_atensi.php';
        } catch (PDOException $e) {
            error_log("Database error in daftarAtensi: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw new Exception("Terjadi kesalahan pada database: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("General error in daftarAtensi: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw new Exception("Terjadi kesalahan: " . $e->getMessage());
        }
    }

    /**
     * Menangani permintaan untuk template tatalaksana
     */
    public function get_template_tatalaksana()
    {
        // Default response
        $response = [
            'status' => 'error',
            'message' => 'Permintaan tidak valid',
            'data' => null
        ];

        // Cek jenis request
        if (isset($_GET['action'])) {
            $action = $_GET['action'];

            try {
                switch ($action) {
                    case 'get_kategori':
                        // Ambil semua kategori
                        $kategori = $this->templateTatalaksanaModel->getAllKategori();
                        $response = [
                            'status' => 'success',
                            'message' => 'Berhasil mengambil data kategori',
                            'data' => $kategori
                        ];
                        break;

                    case 'get_template_by_kategori':
                        // Validasi parameter
                        if (!isset($_GET['kategori']) || empty($_GET['kategori'])) {
                            $response['message'] = 'Parameter kategori diperlukan';
                            break;
                        }

                        // Ambil template berdasarkan kategori
                        $templates = $this->templateTatalaksanaModel->getTemplateByKategori($_GET['kategori']);
                        $response = [
                            'status' => 'success',
                            'message' => 'Berhasil mengambil data template',
                            'data' => $templates
                        ];
                        break;

                    case 'get_template_by_id':
                        // Validasi parameter
                        if (!isset($_GET['id']) || empty($_GET['id'])) {
                            $response['message'] = 'Parameter ID diperlukan';
                            break;
                        }

                        // Ambil template berdasarkan ID
                        $template = $this->templateTatalaksanaModel->getTemplateById($_GET['id']);
                        if ($template) {
                            $response = [
                                'status' => 'success',
                                'message' => 'Berhasil mengambil data template',
                                'data' => $template
                            ];
                        } else {
                            $response['message'] = 'Template tidak ditemukan';
                        }
                        break;

                    case 'get_all_template':
                        // Ambil semua template
                        $templates = $this->templateTatalaksanaModel->getAllTemplate();
                        $response = [
                            'status' => 'success',
                            'message' => 'Berhasil mengambil semua data template',
                            'data' => $templates
                        ];
                        break;

                    default:
                        $response['message'] = 'Action tidak dikenali';
                        break;
                }
            } catch (Exception $e) {
                $response['message'] = 'Terjadi kesalahan: ' . $e->getMessage();
            }
        }

        // Kirim response dalam format JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    /**
     * Menangani permintaan untuk template anamnesis
     */
    public function get_template_anamnesis()
    {
        // Default response
        $response = [
            'status' => 'error',
            'message' => 'Permintaan tidak valid',
            'data' => null
        ];

        // Cek jenis request
        if (isset($_GET['action'])) {
            $action = $_GET['action'];

            try {
                switch ($action) {
                    case 'get_kategori':
                        // Ambil semua kategori
                        $kategori = $this->templateAnamnesisModel->getAllKategori();
                        $response = [
                            'status' => 'success',
                            'message' => 'Berhasil mengambil data kategori',
                            'data' => $kategori
                        ];
                        break;

                    case 'get_template_by_kategori':
                        // Validasi parameter
                        if (!isset($_GET['kategori']) || empty($_GET['kategori'])) {
                            $response['message'] = 'Parameter kategori diperlukan';
                            break;
                        }

                        // Ambil template berdasarkan kategori
                        $templates = $this->templateAnamnesisModel->getTemplateByKategori($_GET['kategori']);
                        $response = [
                            'status' => 'success',
                            'message' => 'Berhasil mengambil data template',
                            'data' => $templates
                        ];
                        break;

                    case 'get_template_by_id':
                        // Validasi parameter
                        if (!isset($_GET['id']) || empty($_GET['id'])) {
                            $response['message'] = 'Parameter ID diperlukan';
                            break;
                        }

                        // Ambil template berdasarkan ID
                        $template = $this->templateAnamnesisModel->getTemplateById($_GET['id']);
                        if ($template) {
                            $response = [
                                'status' => 'success',
                                'message' => 'Berhasil mengambil data template',
                                'data' => $template
                            ];
                        } else {
                            $response['message'] = 'Template tidak ditemukan';
                        }
                        break;

                    case 'get_all_template':
                        // Ambil semua template
                        $templates = $this->templateAnamnesisModel->getAllTemplate();
                        $response = [
                            'status' => 'success',
                            'message' => 'Berhasil mengambil semua data template',
                            'data' => $templates
                        ];
                        break;

                    default:
                        $response['message'] = 'Action tidak dikenali';
                        break;
                }
            } catch (Exception $e) {
                $response['message'] = 'Terjadi kesalahan: ' . $e->getMessage();
            }
        }

        // Kirim response dalam format JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    /**
     * Menampilkan halaman manajemen template anamnesis
     */
    public function template_anamnesis()
    {
        try {
            // Ambil semua kategori
            $kategori = $this->templateAnamnesisModel->getAllKategori();

            // Filter berdasarkan kategori jika ada
            if (isset($_GET['kategori']) && !empty($_GET['kategori'])) {
                $templates = $this->templateAnamnesisModel->getTemplateByKategori($_GET['kategori']);
                $filter_kategori = $_GET['kategori']; // Untuk menandai kategori yang dipilih di dropdown
            }
            // Filter berdasarkan pencarian jika ada
            else if (isset($_GET['search']) && !empty($_GET['search'])) {
                $templates = $this->templateAnamnesisModel->searchTemplate($_GET['search']);
                $search_keyword = $_GET['search']; // Untuk menampilkan keyword di input search
            }
            // Jika tidak ada filter, ambil semua template
            else {
                $templates = $this->templateAnamnesisModel->getAllTemplate();
            }

            // Pesan sukses atau error
            $success_message = '';
            $error_message = '';

            if (isset($_GET['success'])) {
                switch ($_GET['success']) {
                    case '1':
                        $success_message = 'Template berhasil ditambahkan';
                        break;
                    case '2':
                        $success_message = 'Template berhasil diperbarui';
                        break;
                    case '3':
                        $success_message = 'Template berhasil dihapus';
                        break;
                }
            }

            if (isset($_GET['error'])) {
                $error_message = urldecode($_GET['error']);
            }

            // Tampilkan view
            include 'modules/rekam_medis/views/template_anamnesis.php';
        } catch (Exception $e) {
            echo "Terjadi kesalahan: " . $e->getMessage();
        }
    }

    /**
     * Menyimpan template anamnesis baru
     */
    public function simpan_template_anamnesis()
    {
        try {
            // Validasi input
            if (!isset($_POST['nama_template_anamnesis']) || empty($_POST['nama_template_anamnesis'])) {
                throw new Exception("Nama template harus diisi");
            }

            if (!isset($_POST['isi_template_anamnesis']) || empty($_POST['isi_template_anamnesis'])) {
                throw new Exception("Isi template harus diisi");
            }

            if (!isset($_POST['kategori_anamnesis']) || empty($_POST['kategori_anamnesis'])) {
                throw new Exception("Kategori harus dipilih");
            }

            // Siapkan data
            $data = [
                'nama_template_anamnesis' => $_POST['nama_template_anamnesis'],
                'isi_template_anamnesis' => $_POST['isi_template_anamnesis'],
                'kategori_anamnesis' => $_POST['kategori_anamnesis'],
                'status' => $_POST['status'] ?? 'active',
                'tags' => $_POST['tags'] ?? null
            ];

            // Simpan template
            $result = $this->templateAnamnesisModel->saveTemplate($data);

            if ($result) {
                // Redirect dengan pesan sukses
                header("Location: index.php?module=rekam_medis&action=template_anamnesis&success=1");
                exit;
            } else {
                throw new Exception("Gagal menyimpan template");
            }
        } catch (Exception $e) {
            // Redirect dengan pesan error
            header("Location: index.php?module=rekam_medis&action=template_anamnesis&error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Mengupdate template anamnesis
     */
    public function update_template_anamnesis()
    {
        try {
            // Validasi input
            if (!isset($_POST['id_template_anamnesis']) || empty($_POST['id_template_anamnesis'])) {
                throw new Exception("ID template tidak valid");
            }

            if (!isset($_POST['nama_template_anamnesis']) || empty($_POST['nama_template_anamnesis'])) {
                throw new Exception("Nama template harus diisi");
            }

            if (!isset($_POST['isi_template_anamnesis']) || empty($_POST['isi_template_anamnesis'])) {
                throw new Exception("Isi template harus diisi");
            }

            if (!isset($_POST['kategori_anamnesis']) || empty($_POST['kategori_anamnesis'])) {
                throw new Exception("Kategori harus dipilih");
            }

            // Siapkan data
            $data = [
                'id_template_anamnesis' => $_POST['id_template_anamnesis'],
                'nama_template_anamnesis' => $_POST['nama_template_anamnesis'],
                'isi_template_anamnesis' => $_POST['isi_template_anamnesis'],
                'kategori_anamnesis' => $_POST['kategori_anamnesis'],
                'status' => $_POST['status'] ?? 'active',
                'tags' => $_POST['tags'] ?? null
            ];

            // Update template
            $result = $this->templateAnamnesisModel->updateTemplate($data);

            if ($result) {
                // Redirect dengan pesan sukses
                header("Location: index.php?module=rekam_medis&action=template_anamnesis&success=2");
                exit;
            } else {
                throw new Exception("Gagal mengupdate template");
            }
        } catch (Exception $e) {
            // Redirect dengan pesan error
            header("Location: index.php?module=rekam_medis&action=template_anamnesis&error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Menghapus template anamnesis
     */
    public function hapus_template_anamnesis()
    {
        try {
            // Validasi input
            if (!isset($_POST['id_template']) || empty($_POST['id_template'])) {
                throw new Exception("ID template tidak valid");
            }

            // Hapus template
            $result = $this->templateAnamnesisModel->deleteTemplate($_POST['id_template']);

            if ($result) {
                // Redirect dengan pesan sukses
                header("Location: index.php?module=rekam_medis&action=template_anamnesis&success=3");
                exit;
            } else {
                throw new Exception("Gagal menghapus template");
            }
        } catch (Exception $e) {
            // Redirect dengan pesan error
            header("Location: index.php?module=rekam_medis&action=template_anamnesis&error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Menampilkan halaman template ceklist
     */
    public function template_ceklist()
    {
        try {
            // Inisialisasi model jika belum ada
            if (!isset($this->templateCeklistModel)) {
                require_once 'modules/rekam_medis/models/TemplateCeklist.php';
                $this->templateCeklistModel = new TemplateCeklist();
            }

            // Ambil semua kategori
            $kategori = $this->templateCeklistModel->getAllKategori();

            // Filter berdasarkan kategori jika ada
            if (isset($_GET['kategori']) && !empty($_GET['kategori'])) {
                $templates = $this->templateCeklistModel->getTemplateByKategori($_GET['kategori']);
                $filter_kategori = $_GET['kategori']; // Untuk menandai kategori yang dipilih di dropdown
            }
            // Filter berdasarkan pencarian jika ada
            else if (isset($_GET['search']) && !empty($_GET['search'])) {
                $templates = $this->templateCeklistModel->searchTemplate($_GET['search']);
                $search_keyword = $_GET['search']; // Untuk menampilkan keyword di input search
            }
            // Jika tidak ada filter, ambil semua template
            else {
                $templates = $this->templateCeklistModel->getAllTemplate();
            }

            // Pesan sukses atau error
            $success_message = '';
            $error_message = '';

            if (isset($_GET['success'])) {
                switch ($_GET['success']) {
                    case '1':
                        $success_message = 'Template berhasil ditambahkan';
                        break;
                    case '2':
                        $success_message = 'Template berhasil diperbarui';
                        break;
                    case '3':
                        $success_message = 'Template berhasil dihapus';
                        break;
                }
            }

            if (isset($_GET['error'])) {
                $error_message = urldecode($_GET['error']);
            }

            // Tampilkan view
            include 'modules/rekam_medis/views/template_ceklist.php';
        } catch (Exception $e) {
            echo "Terjadi kesalahan: " . $e->getMessage();
        }
    }

    /**
     * Menyimpan template ceklist baru
     */
    public function simpan_template_ceklist()
    {
        try {
            // Inisialisasi model jika belum ada
            if (!isset($this->templateCeklistModel)) {
                require_once 'modules/rekam_medis/models/TemplateCeklist.php';
                $this->templateCeklistModel = new TemplateCeklist();
            }

            // Validasi input
            if (!isset($_POST['nama_template_ck']) || empty($_POST['nama_template_ck'])) {
                throw new Exception("Nama template harus diisi");
            }

            if (!isset($_POST['isi_template_ck']) || empty($_POST['isi_template_ck'])) {
                throw new Exception("Isi template harus diisi");
            }

            if (!isset($_POST['kategori_ck']) || empty($_POST['kategori_ck'])) {
                throw new Exception("Kategori harus dipilih");
            }

            // Siapkan data
            $data = [
                'nama_template_ck' => $_POST['nama_template_ck'],
                'isi_template_ck' => $_POST['isi_template_ck'],
                'kategori_ck' => $_POST['kategori_ck'],
                'status' => $_POST['status'] ?? 'active',
                'tags' => $_POST['tags'] ?? null,
                'created_by' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null
            ];

            // Simpan template
            $result = $this->templateCeklistModel->saveTemplate($data);

            if ($result) {
                // Redirect dengan pesan sukses
                header("Location: index.php?module=rekam_medis&action=template_ceklist&success=1");
                exit;
            } else {
                throw new Exception("Gagal menyimpan template");
            }
        } catch (Exception $e) {
            // Redirect dengan pesan error
            header("Location: index.php?module=rekam_medis&action=template_ceklist&error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Menampilkan form edit template ceklist
     */
    public function edit_template_ceklist_form()
    {
        try {
            // Inisialisasi model jika belum ada
            if (!isset($this->templateCeklistModel)) {
                require_once 'modules/rekam_medis/models/TemplateCeklist.php';
                $this->templateCeklistModel = new TemplateCeklist();
            }

            // Validasi input
            if (!isset($_POST['id_template']) || empty($_POST['id_template'])) {
                throw new Exception("ID template tidak valid");
            }

            // Ambil data template berdasarkan ID
            $template = $this->templateCeklistModel->getTemplateById($_POST['id_template']);

            if (!$template) {
                throw new Exception("Template tidak ditemukan");
            }

            // Ambil semua kategori
            $kategori = $this->templateCeklistModel->getAllKategori();

            // Tampilkan view
            include 'modules/rekam_medis/views/form_edit_template_ceklist.php';
        } catch (Exception $e) {
            // Redirect dengan pesan error
            header("Location: index.php?module=rekam_medis&action=template_ceklist&error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Mengupdate template ceklist
     */
    public function update_template_ceklist()
    {
        try {
            // Inisialisasi model jika belum ada
            if (!isset($this->templateCeklistModel)) {
                require_once 'modules/rekam_medis/models/TemplateCeklist.php';
                $this->templateCeklistModel = new TemplateCeklist();
            }

            // Validasi input
            if (!isset($_POST['id_template_ceklist']) || empty($_POST['id_template_ceklist'])) {
                throw new Exception("ID template tidak valid");
            }

            if (!isset($_POST['nama_template_ck']) || empty($_POST['nama_template_ck'])) {
                throw new Exception("Nama template harus diisi");
            }

            if (!isset($_POST['isi_template_ck']) || empty($_POST['isi_template_ck'])) {
                throw new Exception("Isi template harus diisi");
            }

            if (!isset($_POST['kategori_ck']) || empty($_POST['kategori_ck'])) {
                throw new Exception("Kategori harus dipilih");
            }

            // Siapkan data
            $data = [
                'id_template_ceklist' => $_POST['id_template_ceklist'],
                'nama_template_ck' => $_POST['nama_template_ck'],
                'isi_template_ck' => $_POST['isi_template_ck'],
                'kategori_ck' => $_POST['kategori_ck'],
                'status' => $_POST['status'] ?? 'active',
                'tags' => $_POST['tags'] ?? null
            ];

            // Update template
            $result = $this->templateCeklistModel->updateTemplate($data);

            if ($result) {
                // Redirect dengan pesan sukses
                header("Location: index.php?module=rekam_medis&action=template_ceklist&success=2");
                exit;
            } else {
                throw new Exception("Gagal mengupdate template");
            }
        } catch (Exception $e) {
            // Redirect dengan pesan error
            header("Location: index.php?module=rekam_medis&action=template_ceklist&error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Menghapus template ceklist
     */
    public function hapus_template_ceklist()
    {
        try {
            // Inisialisasi model jika belum ada
            if (!isset($this->templateCeklistModel)) {
                require_once 'modules/rekam_medis/models/TemplateCeklist.php';
                $this->templateCeklistModel = new TemplateCeklist();
            }

            // Validasi input
            if (!isset($_POST['id_template']) || empty($_POST['id_template'])) {
                throw new Exception("ID template tidak valid");
            }

            // Hapus template
            $result = $this->templateCeklistModel->deleteTemplate($_POST['id_template']);

            if ($result) {
                // Redirect dengan pesan sukses
                header("Location: index.php?module=rekam_medis&action=template_ceklist&success=3");
                exit;
            } else {
                throw new Exception("Gagal menghapus template");
            }
        } catch (Exception $e) {
            // Redirect dengan pesan error
            header("Location: index.php?module=rekam_medis&action=template_ceklist&error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Menampilkan form edit template anamnesis
     */
    public function edit_template_anamnesis_form()
    {
        try {
            // Validasi input
            if (!isset($_POST['id_template']) || empty($_POST['id_template'])) {
                throw new Exception("ID template tidak valid");
            }

            // Ambil data template
            $template = $this->templateAnamnesisModel->getTemplateById($_POST['id_template']);

            if (!$template) {
                throw new Exception("Template tidak ditemukan");
            }

            // Ambil semua kategori
            $kategori = $this->templateAnamnesisModel->getAllKategori();

            // Tampilkan view
            include 'modules/rekam_medis/views/form_edit_template_anamnesis.php';
        } catch (Exception $e) {
            // Redirect dengan pesan error
            header("Location: index.php?module=rekam_medis&action=template_anamnesis&error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Menampilkan halaman manajemen template tatalaksana
     */
    public function template_tatalaksana()
    {
        try {
            // Ambil semua kategori
            $kategori = $this->templateTatalaksanaModel->getAllKategori();

            // Filter berdasarkan kategori jika ada
            if (isset($_GET['kategori']) && !empty($_GET['kategori'])) {
                $templates = $this->templateTatalaksanaModel->getTemplateByKategori($_GET['kategori']);
                $filter_kategori = $_GET['kategori']; // Untuk menandai kategori yang dipilih di dropdown
            }
            // Filter berdasarkan pencarian jika ada
            else if (isset($_GET['search']) && !empty($_GET['search'])) {
                $templates = $this->templateTatalaksanaModel->searchTemplate($_GET['search']);
                $search_keyword = $_GET['search']; // Untuk menampilkan keyword di input search
            }
            // Jika tidak ada filter, ambil semua template
            else {
                $templates = $this->templateTatalaksanaModel->getAllTemplate();
            }

            // Pesan sukses atau error
            $success_message = '';
            $error_message = '';

            if (isset($_GET['success'])) {
                switch ($_GET['success']) {
                    case '1':
                        $success_message = 'Template berhasil ditambahkan';
                        break;
                    case '2':
                        $success_message = 'Template berhasil diperbarui';
                        break;
                    case '3':
                        $success_message = 'Template berhasil dihapus';
                        break;
                }
            }

            if (isset($_GET['error'])) {
                $error_message = urldecode($_GET['error']);
            }

            // Tampilkan view
            include 'modules/rekam_medis/views/template_tatalaksana.php';
        } catch (Exception $e) {
            echo "Terjadi kesalahan: " . $e->getMessage();
        }
    }

    /**
     * Menyimpan template tatalaksana baru
     */
    public function simpan_template_tatalaksana()
    {
        try {
            // Validasi input
            if (!isset($_POST['nama_template_tx']) || empty($_POST['nama_template_tx'])) {
                throw new Exception("Nama template harus diisi");
            }

            if (!isset($_POST['isi_template_tx']) || empty($_POST['isi_template_tx'])) {
                throw new Exception("Isi template harus diisi");
            }

            if (!isset($_POST['kategori_tx']) || empty($_POST['kategori_tx'])) {
                throw new Exception("Kategori harus dipilih");
            }

            // Siapkan data
            $data = [
                'nama_template_tx' => $_POST['nama_template_tx'],
                'isi_template_tx' => $_POST['isi_template_tx'],
                'kategori_tx' => $_POST['kategori_tx'],
                'status' => $_POST['status'],
                'tags' => isset($_POST['tags']) ? $_POST['tags'] : null
            ];

            // Simpan template
            $result = $this->templateTatalaksanaModel->saveTemplate($data);

            if ($result) {
                // Redirect ke halaman template dengan pesan sukses
                header("Location: index.php?module=rekam_medis&action=template_tatalaksana&success=1");
                exit;
            } else {
                throw new Exception("Gagal menyimpan template");
            }
        } catch (Exception $e) {
            // Redirect dengan pesan error
            header("Location: index.php?module=rekam_medis&action=template_tatalaksana&error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Mengupdate template tatalaksana
     */
    public function update_template_tatalaksana()
    {
        try {
            // Validasi input
            if (!isset($_POST['id_template']) || empty($_POST['id_template'])) {
                throw new Exception("ID template tidak valid");
            }

            if (!isset($_POST['nama_template_tx']) || empty($_POST['nama_template_tx'])) {
                throw new Exception("Nama template harus diisi");
            }

            if (!isset($_POST['isi_template_tx']) || empty($_POST['isi_template_tx'])) {
                throw new Exception("Isi template harus diisi");
            }

            if (!isset($_POST['kategori_tx']) || empty($_POST['kategori_tx'])) {
                throw new Exception("Kategori harus dipilih");
            }

            // Siapkan data
            $data = [
                'id_template_tx' => $_POST['id_template'],
                'nama_template_tx' => $_POST['nama_template_tx'],
                'isi_template_tx' => $_POST['isi_template_tx'],
                'kategori_tx' => $_POST['kategori_tx'],
                'status' => $_POST['status'],
                'tags' => isset($_POST['tags']) ? $_POST['tags'] : null
            ];

            // Update template
            $result = $this->templateTatalaksanaModel->updateTemplate($data);

            if ($result) {
                // Redirect ke halaman template dengan pesan sukses
                header("Location: index.php?module=rekam_medis&action=template_tatalaksana&success=2");
                exit;
            } else {
                throw new Exception("Gagal mengupdate template");
            }
        } catch (Exception $e) {
            // Redirect dengan pesan error
            header("Location: index.php?module=rekam_medis&action=template_tatalaksana&error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Menghapus template tatalaksana
     */
    public function hapus_template_tatalaksana()
    {
        try {
            // Validasi input
            if (!isset($_POST['id_template']) || empty($_POST['id_template'])) {
                throw new Exception("ID template tidak valid");
            }

            // Hapus template
            $result = $this->templateTatalaksanaModel->deleteTemplate($_POST['id_template']);

            if ($result) {
                // Redirect ke halaman template dengan pesan sukses
                header("Location: index.php?module=rekam_medis&action=template_tatalaksana&success=3");
                exit;
            } else {
                throw new Exception("Gagal menghapus template");
            }
        } catch (Exception $e) {
            // Redirect dengan pesan error
            header("Location: index.php?module=rekam_medis&action=template_tatalaksana&error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Menampilkan form edit template tatalaksana
     */
    public function edit_template_form()
    {
        try {
            // Validasi input
            if (!isset($_POST['id_template']) || empty($_POST['id_template'])) {
                throw new Exception("ID template tidak valid");
            }

            // Ambil data template berdasarkan ID
            $template = $this->templateTatalaksanaModel->getTemplateById($_POST['id_template']);

            if (!$template) {
                throw new Exception("Template tidak ditemukan");
            }

            // Ambil semua kategori
            $kategori = $this->templateTatalaksanaModel->getAllKategori();

            // Tampilkan view form edit
            include 'modules/rekam_medis/views/form_edit_template.php';
        } catch (Exception $e) {
            // Redirect dengan pesan error
            header("Location: index.php?module=rekam_medis&action=template_tatalaksana&error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Menangani permintaan untuk template USG
     */
    public function get_template_usg()
    {
        try {
            // Validasi request
            if (!isset($_GET['action']) || $_GET['action'] !== 'get_template_usg') {
                throw new Exception("Invalid request");
            }

            // Set header untuk JSON response
            header('Content-Type: application/json');

            // Jika ada parameter kategori, ambil template berdasarkan kategori
            if (isset($_GET['kategori']) && !empty($_GET['kategori'])) {
                $kategori = $_GET['kategori'];
                $templates = $this->templateUsgModel->getTemplateByKategori($kategori);
                echo json_encode(['status' => 'success', 'data' => $templates]);
                return;
            }

            // Jika ada parameter id, ambil template berdasarkan id
            if (isset($_GET['id']) && !empty($_GET['id'])) {
                $id = $_GET['id'];
                $template = $this->templateUsgModel->getTemplateById($id);
                if (!$template) {
                    echo json_encode(['status' => 'error', 'message' => 'Template tidak ditemukan']);
                    return;
                }
                echo json_encode(['status' => 'success', 'data' => $template]);
                return;
            }

            // Default: ambil semua template
            $templates = $this->templateUsgModel->getAllTemplate();
            echo json_encode(['status' => 'success', 'data' => $templates]);
        } catch (Exception $e) {
            error_log("Error in get_template_usg: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Menampilkan halaman manajemen template USG
     */
    public function template_usg()
    {
        try {
            // Ambil semua kategori
            $kategori = $this->templateUsgModel->getAllKategori();

            // Filter berdasarkan kategori jika ada
            if (isset($_GET['kategori']) && !empty($_GET['kategori'])) {
                $templates = $this->templateUsgModel->getTemplateByKategori($_GET['kategori']);
            }
            // Pencarian jika ada
            elseif (isset($_GET['search']) && !empty($_GET['search'])) {
                $templates = $this->templateUsgModel->searchTemplate($_GET['search']);
            }
            // Default: ambil semua template
            else {
                $templates = $this->templateUsgModel->getAllTemplate();
            }

            // Tampilkan view
            include 'modules/rekam_medis/views/template_usg.php';
        } catch (Exception $e) {
            error_log("Error in template_usg: " . $e->getMessage());
            echo "Error: " . $e->getMessage();
        }
    }

    /**
     * Menyimpan template USG baru
     */
    public function simpan_template_usg()
    {
        try {
            // Validasi request
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Invalid request method");
            }

            // Validasi data
            if (
                empty($_POST['nama_template_usg']) ||
                empty($_POST['isi_template_usg']) ||
                empty($_POST['kategori_usg'])
            ) {
                throw new Exception("Data template tidak lengkap");
            }

            // Siapkan data
            $data = [
                'nama_template_usg' => $_POST['nama_template_usg'],
                'isi_template_usg' => $_POST['isi_template_usg'],
                'kategori_usg' => $_POST['kategori_usg'],
                'status' => $_POST['status'] ?? 'active',
                'tags' => $_POST['tags'] ?? null
            ];

            // Simpan template
            $result = $this->templateUsgModel->saveTemplate($data);

            if ($result) {
                // Redirect ke halaman template dengan pesan sukses
                header("Location: index.php?module=rekam_medis&action=template_usg&success=1");
                exit;
            } else {
                throw new Exception("Gagal menyimpan template");
            }
        } catch (Exception $e) {
            error_log("Error in simpan_template_usg: " . $e->getMessage());
            header("Location: index.php?module=rekam_medis&action=template_usg&error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Mengupdate template USG
     */
    public function update_template_usg()
    {
        try {
            // Validasi request
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Invalid request method");
            }

            // Validasi data
            if (
                empty($_POST['id_template_usg']) ||
                empty($_POST['nama_template_usg']) ||
                empty($_POST['isi_template_usg']) ||
                empty($_POST['kategori_usg'])
            ) {
                throw new Exception("Data template tidak lengkap");
            }

            // Siapkan data
            $data = [
                'id_template_usg' => $_POST['id_template_usg'],
                'nama_template_usg' => $_POST['nama_template_usg'],
                'isi_template_usg' => $_POST['isi_template_usg'],
                'kategori_usg' => $_POST['kategori_usg'],
                'status' => $_POST['status'] ?? 'active',
                'tags' => $_POST['tags'] ?? null
            ];

            // Update template
            $result = $this->templateUsgModel->updateTemplate($data);

            if ($result) {
                // Redirect ke halaman template dengan pesan sukses
                header("Location: index.php?module=rekam_medis&action=template_usg&success=2");
                exit;
            } else {
                throw new Exception("Gagal mengupdate template");
            }
        } catch (Exception $e) {
            error_log("Error in update_template_usg: " . $e->getMessage());
            header("Location: index.php?module=rekam_medis&action=template_usg&error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Menghapus template USG
     */
    public function hapus_template_usg()
    {
        try {
            // Validasi request
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id_template'])) {
                throw new Exception("Invalid request");
            }

            // Hapus template
            $result = $this->templateUsgModel->deleteTemplate($_POST['id_template']);

            if ($result) {
                // Redirect ke halaman template dengan pesan sukses
                header("Location: index.php?module=rekam_medis&action=template_usg&success=3");
                exit;
            } else {
                throw new Exception("Gagal menghapus template");
            }
        } catch (Exception $e) {
            error_log("Error in hapus_template_usg: " . $e->getMessage());
            header("Location: index.php?module=rekam_medis&action=template_usg&error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Menampilkan form edit template USG
     */
    public function edit_template_usg_form()
    {
        try {
            // Validasi request
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id_template'])) {
                throw new Exception("Invalid request");
            }

            // Ambil data template
            $template = $this->templateUsgModel->getTemplateById($_POST['id_template']);
            if (!$template) {
                throw new Exception("Template tidak ditemukan");
            }

            // Ambil kategori
            $kategori = $this->templateUsgModel->getAllKategori();

            // Tampilkan form edit
            include 'modules/rekam_medis/views/form_edit_template_usg.php';
        } catch (Exception $e) {
            error_log("Error in edit_template_usg_form: " . $e->getMessage());
            header("Location: index.php?module=rekam_medis&action=template_usg&error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Toggle status berikutnya_gratis untuk pasien
     */
    public function toggleBerikutnyaGratis()
    {
        // Set header untuk response JSON
        header('Content-Type: application/json');
        
        try {
            error_log("toggleBerikutnyaGratis dipanggil");
            error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
            error_log("POST data: " . print_r($_POST, true));

            // Pastikan request method adalah POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed', 405);
            }

            // Validasi input
            if (!isset($_POST['no_rkm_medis']) || empty(trim($_POST['no_rkm_medis']))) {
                throw new Exception('No RM pasien tidak valid', 400);
            }

            $no_rkm_medis = trim($_POST['no_rkm_medis']);
            $berikutnya_gratis = isset($_POST['berikutnya_gratis']) ? (int)$_POST['berikutnya_gratis'] : 0;

            // Pastikan koneksi database tersedia
            if (!$this->pdo) {
                throw new Exception('Koneksi database tidak tersedia', 500);
            }

            // Periksa apakah kolom berikutnya_gratis sudah ada di tabel pasien
            try {
                $checkColumn = $this->pdo->query("SHOW COLUMNS FROM pasien LIKE 'berikutnya_gratis'");
                $columnExists = $checkColumn && $checkColumn->rowCount() > 0;

                // Jika kolom belum ada, tambahkan kolom baru
                if (!$columnExists) {
                    error_log("Kolom berikutnya_gratis belum ada, membuat kolom baru");
                    $this->pdo->exec("ALTER TABLE pasien ADD COLUMN berikutnya_gratis TINYINT(1) NOT NULL DEFAULT 0");
                    error_log("Kolom berikutnya_gratis berhasil dibuat");
                }
            } catch (Exception $ex) {
                error_log("Gagal memeriksa/membuat kolom berikutnya_gratis: " . $ex->getMessage());
                throw new Exception('Gagal memeriksa/membuat kolom database', 500);
            }

            error_log("Data yang akan diupdate: no_rkm_medis = $no_rkm_medis, berikutnya_gratis = $berikutnya_gratis");

            // Update status berikutnya_gratis
            $query = "UPDATE pasien SET berikutnya_gratis = :berikutnya_gratis WHERE no_rkm_medis = :no_rkm_medis";
            $stmt = $this->pdo->prepare($query);
            
            if (!$stmt) {
                $error = $this->pdo->errorInfo();
                throw new Exception('Gagal menyiapkan query: ' . ($error[2] ?? 'Unknown error'), 500);
            }
            
            $stmt->bindParam(':berikutnya_gratis', $berikutnya_gratis, PDO::PARAM_INT);
            $stmt->bindParam(':no_rkm_medis', $no_rkm_medis, PDO::PARAM_STR);
            
            $result = $stmt->execute();
            $affectedRows = $stmt->rowCount();
            
            error_log("Query result: " . ($result ? 'success' : 'failed') . ", affected rows: $affectedRows");

            if ($result) {
                if ($affectedRows > 0) {
                    $response = [
                        'status' => 'success',
                        'message' => 'Status berhasil diperbarui',
                        'berikutnya_gratis' => $berikutnya_gratis
                    ];
                    http_response_code(200);
                } else {
                    // Tidak ada baris yang diupdate, mungkin no_rkm_medis tidak ditemukan
                    $response = [
                        'status' => 'error',
                        'message' => 'Data pasien tidak ditemukan',
                        'berikutnya_gratis' => $berikutnya_gratis
                    ];
                    http_response_code(404);
                }
                echo json_encode($response);
            } else {
                $error = $stmt->errorInfo();
                error_log("Database error: " . print_r($error, true));
                throw new Exception('Gagal memperbarui status: ' . ($error[2] ?? 'Unknown error'), 500);
            }
        } catch (Exception $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            http_response_code($statusCode);
            
            $response = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
            
            // Tambahkan detail error jika dalam mode debug
            if (defined('DEBUG') && DEBUG) {
                $response['debug'] = [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ];
            }
            
            echo json_encode($response);
            error_log("Error in toggleBerikutnyaGratis: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
        }
        exit;
    }

    // Metode untuk mendapatkan data surat via AJAX
    public function get_surat_ajax()
    {
        // Pastikan request adalah AJAX atau memiliki parameter yang diperlukan
        if (!isset($_GET['no_rkm_medis'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Parameter tidak lengkap'
            ]);
            exit;
        }

        $no_rkm_medis = $_GET['no_rkm_medis'];

        try {
            // Gunakan model Surat untuk mendapatkan data
            $suratModel = new Surat($this->pdo);
            $dataList = $suratModel->getSuratByPasien($no_rkm_medis);

            echo json_encode([
                'status' => 'success',
                'data' => $dataList
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    // Metode untuk menambah data surat
    public function tambahSurat()
    {
        // Debug: Log the request and post data
        error_log("tambahSurat method called");
        error_log("POST data: " . json_encode($_POST));

        // Pastikan request adalah AJAX atau POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("Request method is not POST: " . $_SERVER['REQUEST_METHOD']);
            echo json_encode([
                'status' => 'error',
                'message' => 'Metode request tidak valid'
            ]);
            exit;
        }

        // Validasi data yang diperlukan
        if (empty($_POST['no_rkm_medis']) || empty($_POST['jenis_surat']) || empty($_POST['tanggal_surat'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Data tidak lengkap'
            ]);
            exit;
        }

        // Data surat yang akan disimpan
        $data = [
            'no_rkm_medis' => $_POST['no_rkm_medis'],
            'jenis_surat' => $_POST['jenis_surat'],
            'tanggal_surat' => $_POST['tanggal_surat'],
            'mulai_sakit' => !empty($_POST['mulai_sakit']) ? $_POST['mulai_sakit'] : null,
            'selesai_sakit' => !empty($_POST['selesai_sakit']) ? $_POST['selesai_sakit'] : null,
            'keperluan' => $_POST['keperluan'] ?? null,
            'diagnosa' => $_POST['diagnosa'] ?? null,
            'catatan' => $_POST['catatan'] ?? null,
            'dokter_pemeriksa' => $_POST['dokter_pemeriksa'],
            'created_by' => $_SESSION['username'] ?? 'system'
        ];

        try {
            // Debug data array before saving
            error_log("Data yang akan disimpan: " . json_encode($data));

            // Gunakan model Surat untuk menyimpan data
            $suratModel = new Surat($this->pdo);
            $result = $suratModel->tambahSurat($data);

            error_log("Result of tambahSurat: " . var_export($result, true));

            if ($result) {
                error_log("Success saving surat with ID: " . $result);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Data surat berhasil disimpan',
                    'id_surat' => $result
                ]);
            } else {
                error_log("Failed to save surat");
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Gagal menyimpan data surat'
                ]);
            }
        } catch (Exception $e) {
            error_log("Exception in tambahSurat: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            echo json_encode([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    // Metode untuk mengedit data surat
    public function edit_surat()
    {
        // Pastikan parameter ID tersedia
        if (!isset($_GET['id'])) {
            $_SESSION['error'] = 'Parameter ID surat tidak ditemukan';
            header('Location: index.php?module=rekam_medis');
            exit;
        }

        $id_surat = $_GET['id'];

        try {
            // Gunakan model Surat untuk mendapatkan data
            $suratModel = new Surat($this->pdo);
            $surat = $suratModel->getSuratById($id_surat);

            if (!$surat) {
                $_SESSION['error'] = 'Data surat tidak ditemukan';
                header('Location: index.php?module=rekam_medis');
                exit;
            }

            // Dapatkan data pasien
            $pasien = $this->rekamMedisModel->getPasienById($surat['no_rkm_medis']);

            // Tampilkan form edit
            include 'modules/rekam_medis/views/edit_surat.php';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Terjadi kesalahan: ' . $e->getMessage();
            header('Location: index.php?module=rekam_medis');
            exit;
        }
    }

    // Metode untuk menyimpan hasil edit surat
    public function updateSurat()
    {
        // Pastikan request adalah POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Metode request tidak valid';
            header('Location: index.php?module=rekam_medis');
            exit;
        }

        // Validasi data yang diperlukan
        if (empty($_POST['id_surat']) || empty($_POST['jenis_surat']) || empty($_POST['tanggal_surat'])) {
            $_SESSION['error'] = 'Data tidak lengkap';
            header('Location: index.php?module=rekam_medis');
            exit;
        }

        $id_surat = $_POST['id_surat'];
        $no_rkm_medis = $_POST['no_rkm_medis'];

        // Data surat yang akan diupdate
        $data = [
            'jenis_surat' => $_POST['jenis_surat'],
            'tanggal_surat' => $_POST['tanggal_surat'],
            'mulai_sakit' => !empty($_POST['mulai_sakit']) ? $_POST['mulai_sakit'] : null,
            'selesai_sakit' => !empty($_POST['selesai_sakit']) ? $_POST['selesai_sakit'] : null,
            'keperluan' => $_POST['keperluan'] ?? null,
            'diagnosa' => $_POST['diagnosa'] ?? null,
            'catatan' => $_POST['catatan'] ?? null,
            'dokter_pemeriksa' => $_POST['dokter_pemeriksa']
        ];

        try {
            // Gunakan model Surat untuk update data
            $suratModel = new Surat($this->pdo);
            $result = $suratModel->updateSurat($id_surat, $data);

            if ($result) {
                $_SESSION['success'] = 'Data surat berhasil diperbarui';
            } else {
                $_SESSION['error'] = 'Gagal memperbarui data surat';
            }

            // Redirect ke halaman detail pasien
            header('Location: index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=' . $no_rkm_medis . '#surat');
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = 'Terjadi kesalahan: ' . $e->getMessage();
            header('Location: index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=' . $no_rkm_medis);
            exit;
        }
    }

    // Metode untuk menghapus data surat
    public function hapus_surat()
    {
        // Pastikan parameter ID tersedia
        if (!isset($_GET['id'])) {
            $_SESSION['error'] = 'Parameter ID surat tidak ditemukan';
            header('Location: index.php?module=rekam_medis');
            exit;
        }

        $id_surat = $_GET['id'];

        try {
            // Gunakan model Surat untuk mendapatkan data
            $suratModel = new Surat($this->pdo);
            $surat = $suratModel->getSuratById($id_surat);

            if (!$surat) {
                $_SESSION['error'] = 'Data surat tidak ditemukan';
                header('Location: index.php?module=rekam_medis');
                exit;
            }

            $no_rkm_medis = $surat['no_rkm_medis'];

            // Gunakan model Surat untuk menghapus data
            $result = $suratModel->hapusSurat($id_surat);

            if ($result) {
                $_SESSION['success'] = 'Data surat berhasil dihapus';
            } else {
                $_SESSION['error'] = 'Gagal menghapus data surat';
            }

            // Redirect ke halaman detail pasien
            header('Location: index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=' . $no_rkm_medis . '#surat');
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = 'Terjadi kesalahan: ' . $e->getMessage();
            header('Location: index.php?module=rekam_medis');
            exit;
        }
    }

    // Metode untuk mencetak surat
    public function cetak_surat()
    {
        // Pastikan parameter ID tersedia
        if (!isset($_GET['id'])) {
            $_SESSION['error'] = 'Parameter ID surat tidak ditemukan';
            header('Location: index.php?module=rekam_medis');
            exit;
        }

        $id_surat = $_GET['id'];

        try {
            // Gunakan model Surat untuk mendapatkan data
            $suratModel = new Surat($this->pdo);
            $surat = $suratModel->getSuratById($id_surat);

            if (!$surat) {
                $_SESSION['error'] = 'Data surat tidak ditemukan';
                header('Location: index.php?module=rekam_medis');
                exit;
            }

            // Dapatkan data pasien
            $pasien = $this->rekamMedisModel->getPasienById($surat['no_rkm_medis']);

            // Tampilkan halaman cetak surat sesuai jenis surat
            switch ($surat['jenis_surat']) {
                case 'skd':
                    include 'modules/rekam_medis/views/cetak_surat_dokter.php';
                    break;
                case 'sakit':
                    include 'modules/rekam_medis/views/cetak_surat_sakit.php';
                    break;
                case 'rujukan':
                    include 'modules/rekam_medis/views/cetak_surat_rujukan.php';
                    break;
                default:
                    $_SESSION['error'] = 'Jenis surat tidak valid';
                    header('Location: index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=' . $surat['no_rkm_medis']);
                    exit;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Terjadi kesalahan: ' . $e->getMessage();
            header('Location: index.php?module=rekam_medis');
            exit;
        }
    }

    // [Fungsi dataKunjungan telah dihapus - tidak lagi digunakan]

    public function formEditPemeriksaan()
    {
        // Set header untuk mencegah caching
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Tanggal di masa lalu

        error_log("==== DEBUGGING formEditPemeriksaan START ====");
        error_log("REQUEST_URI: " . $_SERVER['REQUEST_URI']);
        error_log("QUERY STRING: " . $_SERVER['QUERY_STRING']);
        error_log("no_rawat param: " . ($_GET['no_rawat'] ?? 'not set'));

        // Gunakan koneksi global yang sudah ada (tidak perlu koneksi baru)
        $no_rawat = $_GET['no_rawat'] ?? '';

        if (empty($no_rawat)) {
            error_log("CRITICAL formEditPemeriksaan: Nomor rawat tidak valid atau kosong.");
            $_SESSION['error'] = "Nomor rawat tidak valid";
            if (!headers_sent()) {
                header("Location: " . $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . "/index.php?module=rekam_medis&action=data_pasien");
            } else {
                echo "Error: Nomor rawat tidak valid. Silakan cek log server.";
            }
            exit;
        }

        try {
            // 1. Ambil data pemeriksaan dan pasien berdasarkan no_rawat
            $stmt = $this->pdo->prepare("
                SELECT pmrk.*, p.*, rp.tgl_registrasi, rp.jam_reg 
                FROM penilaian_medis_ralan_kandungan pmrk
                JOIN reg_periksa rp ON pmrk.no_rawat = rp.no_rawat
                JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
                WHERE pmrk.no_rawat = ?
            ");
            $stmt->execute([$no_rawat]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) {
                // Jika tidak ada data pemeriksaan, cek reg_periksa dan pasien
                $stmt_reg = $this->pdo->prepare("
                    SELECT rp.*, p.*
                    FROM reg_periksa rp
                    JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
                    WHERE rp.no_rawat = ?
                ");
                $stmt_reg->execute([$no_rawat]);
                $data_reg = $stmt_reg->fetch(PDO::FETCH_ASSOC);

                if (!$data_reg) {
                    error_log("CRITICAL formEditPemeriksaan: Data reg_periksa tidak ditemukan untuk no_rawat: " . $no_rawat);
                    $_SESSION['error'] = "Data pasien/registrasi tidak ditemukan";
                    if (!headers_sent()) {
                        header("Location: " . $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . "/index.php?module=rekam_medis&action=data_pasien");
                    } else {
                        echo "Error: Data pasien/registrasi tidak ditemukan. Silakan cek log server.";
                    }
                    exit;
                }
                // Siapkan data pasien dan reg_periksa, pemeriksaan kosong
                $data = $data_reg;
                $pemeriksaan = [];
                $pasien = $data_reg;
            } else {
                // Data pemeriksaan ditemukan
                $pemeriksaan = $data;
                $pasien = $data;
            }

            // Semua proses utama dalam satu blok try
            try {
                // Pisahkan blok view agar tidak nested di else
                // Path ke file view - gunakan deteksi fleksibel berdasarkan lingkungan
                $app_dir = '';
                if (strpos($_SERVER['DOCUMENT_ROOT'], 'public_html') !== false) {
                    $app_dir = '';
                } else {
                    $app_dir = '/';
                }
                $view_file_path = $_SERVER['DOCUMENT_ROOT'] . $app_dir . '/modules/rekam_medis/views/form_edit_pemeriksaan.php';
                error_log("DEBUG formEditPemeriksaan: Attempting to include view file: " . $view_file_path);

                // Ambil data riwayat kehamilan
                $riwayatKehamilan = $this->rekamMedisModel->getRiwayatKehamilan($data['no_rkm_medis']);
                error_log("DEBUG formEditPemeriksaan: Fetched riwayat kehamilan data");
                // Ambil data riwayat pemeriksaan untuk ditampilkan di bagian bawah form
                $riwayatPemeriksaan = $this->rekamMedisModel->getRiwayatPemeriksaan($data['no_rkm_medis']);
                error_log("DEBUG formEditPemeriksaan: Fetched riwayat pemeriksaan data: " . count($riwayatPemeriksaan) . " records");

                // Ambil data template ceklist (pakai model jika ada, jika tidak query manual)
                if (isset($this->templateCeklistModel) && method_exists($this->templateCeklistModel, 'getAllActive')) {
                    $templateCeklist = $this->templateCeklistModel->getAllActive();
                } else {
                    // Query manual jika tidak ada model
                    $stmtCk = $this->pdo->prepare("SELECT * FROM template_ceklist WHERE status = 'active' ORDER BY kategori_ck ASC, nama_template_ck ASC");
                    $stmtCk->execute();
                    $templateCeklist = $stmtCk->fetchAll(PDO::FETCH_ASSOC);
                }

                if (file_exists($view_file_path) && is_readable($view_file_path)) {
                    error_log("DEBUG formEditPemeriksaan: View file found and readable. Including...");
                    include $view_file_path;
                    error_log("DEBUG formEditPemeriksaan: View file included successfully.");
                } else {
                    $error_message = "CRITICAL formEditPemeriksaan: View file not found or not readable. Path: " . $view_file_path;
                    if (!file_exists($view_file_path)) {
                        $error_message .= " (File does not exist)";
                    }
                    if (!is_readable($view_file_path)) {
                        $error_message .= " (File is not readable - check permissions)";
                    }
                    error_log($error_message);
                    $_SESSION['error'] = 'Kesalahan internal: Gagal memuat tampilan form edit. (ERR_VIEW_LOAD)';
                    echo "<h3>Error Kritis</h3><p>Tidak dapat memuat komponen halaman. Silakan hubungi administrator. (Kode: ERR_VIEW_LOAD)</p><p>Detail: " . htmlspecialchars($error_message) . "</p>";
                    if (!headers_sent()) {
                        header('Location: ' . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/index.php?module=rekam_medis&action=data_pasien');
                        exit;
                    }
                }
                return;
            } catch (PDOException $e) {
                error_log("CRITICAL formEditPemeriksaan: PDOException during data fetch or view include: " . $e->getMessage());
                $_SESSION['error'] = 'Terjadi kesalahan database saat memuat form edit pemeriksaan: ' . $e->getMessage();
                if (!headers_sent()) {
                    header('Location: ' . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/index.php?module=rekam_medis&action=data_pasien');
                } else {
                    echo "Error: Terjadi kesalahan database. Silakan cek log server.";
                }
                exit;
            } catch (Exception $e) {
                error_log("CRITICAL formEditPemeriksaan: Generic Exception during data fetch or view include: " . $e->getMessage());
                $_SESSION['error'] = 'Terjadi kesalahan umum saat memuat form edit pemeriksaan.';
                if (!headers_sent()) {
                    header('Location: ' . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/index.php?module=rekam_medis&action=data_pasien');
                } else {
                    echo "Error: Terjadi kesalahan umum. Silakan cek log server.";
                }
                exit;
            } finally {
                // Kembalikan koneksi PDO asli jika diubah, jika perlu
                // if (isset($original_pdo)) {
                //     $this->pdo = $original_pdo;
                //     error_log("DEBUG formEditPemeriksaan: Restored original PDO connection.");
                // }
                error_log("==== DEBUGGING formEditPemeriksaan END ====");
            }


            exit;
        } catch (Exception $e) {
            error_log("CRITICAL formEditPemeriksaan: Generic Exception during data fetch or view include: " . $e->getMessage());
            $_SESSION['error'] = 'Terjadi kesalahan umum saat memuat form edit pemeriksaan.';
            if (!headers_sent()) {
                header('Location: ' . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/index.php?module=rekam_medis&action=data_pasien');
            } else {
                echo "Error: Terjadi kesalahan umum. Silakan cek log server.";
            }
            exit;
        } finally {
            // Kembalikan koneksi PDO asli jika diubah, jika perlu
            // if (isset($original_pdo)) {
            //     $this->pdo = $original_pdo;
            //     error_log("DEBUG formEditPemeriksaan: Restored original PDO connection.");
            // }
            error_log("==== DEBUGGING formEditPemeriksaan END ====");
        }
    }
}
