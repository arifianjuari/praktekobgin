<?php
/**
 * Helper function untuk menangani redirect setelah operasi CRUD
 * Memastikan redirect kembali ke form_penilaian_medis_ralan_kandungan jika source berasal dari sana
 * dan no_rawat valid, atau ke halaman detail pasien jika tidak.
 * 
 * @param string $source Source parameter dari form
 * @param string $no_rawat Nomor rawat dari form
 * @param string $no_rkm_medis Nomor rekam medis pasien
 * @return void
 */
function handleRedirect($source, $no_rawat, $no_rkm_medis) {
    // Validasi parameter
    $source = isset($source) ? $source : '';
    $no_rawat = isset($no_rawat) && !empty($no_rawat) ? $no_rawat : '';
    $no_rkm_medis = isset($no_rkm_medis) ? $no_rkm_medis : '';
    
    // Log untuk debugging
    error_log("handleRedirect - source: " . $source);
    error_log("handleRedirect - no_rawat: " . $no_rawat);
    error_log("handleRedirect - no_rkm_medis: " . $no_rkm_medis);
    
    // Handle different source pages correctly
    if ($source == 'form_penilaian_medis_ralan_kandungan' && !empty($no_rawat)) {
        // Redirect kembali ke form penilaian medis ralan kandungan
        header("Location: index.php?module=rekam_medis&action=form_penilaian_medis_ralan_kandungan&no_rawat=" . $no_rawat);
    } elseif ($source == 'form_edit_pemeriksaan') {
        // Jika no_rawat tersedia, redirect ke form edit pemeriksaan.
        // Jika tidak, hindari error dengan kembali ke detail pasien.
        if (!empty($no_rawat)) {
            $redirect_url = "index.php?module=rekam_medis&action=form_edit_pemeriksaan&no_rkm_medis=" . $no_rkm_medis . "&no_rawat=" . $no_rawat;
            header("Location: " . $redirect_url);
        } else {
            // Fallback: langsung ke detail pasien agar tidak muncul error "Nomor rawat tidak valid"
            header("Location: index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=" . $no_rkm_medis . "#skrining");
        }
    } elseif ($source == 'detailPasien' || $source == 'detail_pasien') {
        // Redirect kembali ke halaman detail pasien dengan anchor ke tab status obstetri
        header("Location: index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=" . $no_rkm_medis . "#skrining");
    } else {
        // Default redirect ke halaman detail pasien
        header("Location: index.php?module=rekam_medis&action=detailPasien&no_rkm_medis=" . $no_rkm_medis);
    }
    exit;
}
