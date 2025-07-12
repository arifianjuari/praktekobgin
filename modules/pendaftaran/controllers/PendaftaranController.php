<?php
class PendaftaranController
{
    public function handle()
    {
        // Untuk demo, bisa langsung require view form pendaftaran pasien jika ada
        $view = __DIR__ . '/../views/form_pendaftaran_pasien.php';
        if (file_exists($view)) {
            require $view;
        } else {
            echo '<h2>Halaman Pendaftaran</h2><p>View form_pendaftaran_pasien.php tidak ditemukan.</p>';
        }
    }
}
