<?php
// views/home.php
// Variabel: $page_title, $ranap_count, $rajal_count, $base_url
?>
<div class="container-fluid py-4">
    <div class="row g-4">
        <!-- Card Rawat Inap -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Pasien Rawat Inap</h5>
                    <h6 class="text-muted mb-4">DPJP dr. ARIFIAN JUARI, Sp.OG(K)</h6>
                    <div class="d-flex align-items-center">
                        <h1 class="display-4 mb-0 text-primary"><?= htmlspecialchars($ranap_count) ?></h1>
                        <span class="ms-3">Pasien hari ini</span>
                    </div>
                    <a href="daftar_ranap.php" class="btn btn-outline-primary mt-4">
                        <i class="bi bi-eye"></i> Lihat Detail
                    </a>
                </div>
            </div>
        </div>
        <!-- Card Rawat Jalan -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Pasien Rawat Jalan</h5>
                    <h6 class="text-muted mb-4">Poli Obgin - dr. ARIFIAN JUARI, Sp.OG(K)</h6>
                    <div class="d-flex align-items-center">
                        <h1 class="display-4 mb-0 text-primary"><?= htmlspecialchars($rajal_count) ?></h1>
                        <span class="ms-3">Pasien hari ini</span>
                    </div>
                    <a href="daftar_rajal_rs.php" class="btn btn-outline-primary mt-4">
                        <i class="bi bi-eye"></i> Lihat Detail
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-8">
        <!-- Konten dashboard utama -->
    </div>
    <div class="col-md-4">
        <!-- Widget Pengumuman -->
        <?php
        if (!isset($base_url)) {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'];
            $base_url = $protocol . $host;
        }
        include_once 'widgets/pengumuman_widget.php';
        ?>
    </div>
</div>
