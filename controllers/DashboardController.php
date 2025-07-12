<?php
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../helpers/SessionHelper.php';

class DashboardController {
    public function handle() {
        if (function_exists('session_status')) {
            if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        } else {
            if (!headers_sent()) @session_start();
        }
        // Cek akses admin
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header("Location: home.php");
            exit;
        }
        global $conn_db1; // diasumsikan sudah diinit dari Database.php
        $page_title = "Dashboard Admin";
        // Query jumlah pasien rawat inap
        $ranap_count = 0;
        $rajal_count = 0;
        try {
            $query_ranap = "SELECT COUNT(DISTINCT ran.no_rawat) as total
                FROM kamar_inap as ran
                INNER JOIN dpjp_ranap as dpjp ON ran.no_rawat = dpjp.no_rawat
                INNER JOIN dokter ON dpjp.kd_dokter = dokter.kd_dokter
                WHERE ran.stts_pulang = '-' AND dokter.nm_dokter = 'dr. ARIFIAN JUARI, Sp.OG(K)'";
            $stmt_ranap = $conn_db1->query($query_ranap);
            $ranap_count = $stmt_ranap->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            $query_rajal = "SELECT COUNT(DISTINCT reg.no_rawat) as total
                FROM reg_periksa as reg
                WHERE reg.kd_dokter = (SELECT kd_dokter FROM dokter WHERE nm_dokter = 'dr. ARIFIAN JUARI, Sp.OG(K)')
                AND reg.tgl_registrasi = CURDATE() AND reg.stts != 'Batal'";
            $stmt_rajal = $conn_db1->query($query_rajal);
            $rajal_count = $stmt_rajal->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        } catch (Exception $e) {
            $ranap_count = 0;
            $rajal_count = 0;
        }
        require __DIR__ . '/../views/home.php';
    }
}
