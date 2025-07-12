<?php
// [INFO] File view ini hanya berisi konten utama.
// Harus dirender melalui template/layout.php agar style dan struktur konsisten.
// Jika butuh style/script khusus, set variabel $additional_css/$additional_js di controller.
// Style khusus halaman ini:
// $additional_css = '
// .card { border-radius: 15px; overflow: hidden; transition: all 0.3s ease; }
// .rounded-4 { border-radius: 1.5rem!important; }
// .floating-whatsapp { position: fixed; bottom: 24px; right: 24px; z-index: 999; }
// .floating-whatsapp a { display: flex; align-items: center; background: #25d366; color: #fff; padding: 12px 18px; border-radius: 30px; text-decoration: none; font-weight: bold; box-shadow: 0 4px 16px rgba(0,0,0,0.18); }
// .floating-whatsapp a:hover { background: #128c7e; }
// ';
?>
<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow border-0 rounded-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center rounded-top-4">
                    <h5 class="mb-0"><i class="fas fa-list-ol me-2"></i>Daftar Praktekobgin</h5>
                    <button class="btn btn-sm btn-light rounded-pill" type="button" data-bs-toggle="collapse" data-bs-target="#filterSection">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                </div>
                <div class="collapse" id="filterSection">
                    <div class="card-body">
                        <form method="get" class="row g-3">
                            <div class="col-md-4">
                                <label for="tempat" class="form-label">Tempat Praktek</label>
                                <select name="tempat" id="tempat" class="form-select">
                                    <option value="">Semua</option>
                                    <?php foreach ($tempat_praktek as $tp): ?>
                                        <option value="<?= htmlspecialchars($tp['ID_Tempat_Praktek']) ?>" <?= (isset($_GET['tempat']) && $_GET['tempat'] == $tp['ID_Tempat_Praktek']) ? 'selected' : '' ?>><?= htmlspecialchars($tp['Nama_Tempat']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="dokter" class="form-label">Dokter</label>
                                <select name="dokter" id="dokter" class="form-select">
                                    <option value="">Semua</option>
                                    <?php foreach ($dokter as $d): ?>
                                        <option value="<?= htmlspecialchars($d['ID_Dokter']) ?>" <?= (isset($_GET['dokter']) && $_GET['dokter'] == $d['ID_Dokter']) ? 'selected' : '' ?>><?= htmlspecialchars($d['Nama_Dokter']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="hari" class="form-label">Hari</label>
                                <select name="hari" id="hari" class="form-select">
                                    <option value="">Semua</option>
                                    <?php foreach (["Senin","Selasa","Rabu","Kamis","Jumat","Sabtu","Minggu"] as $h): ?>
                                        <option value="<?= $h ?>" <?= (isset($_GET['hari']) && $_GET['hari'] == $h) ? 'selected' : '' ?>><?= $h ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger rounded-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-circle fa-2x me-3"></i>
                                <span><?= htmlspecialchars($error_message) ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (empty($antrian)): ?>
                        <div class="alert alert-info rounded-4">Belum ada antrian untuk filter yang dipilih.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Pasien</th>
                                        <th>Tempat</th>
                                        <th>Dokter</th>
                                        <th>Hari</th>
                                        <th>Jam</th>
                                        <th>Layanan</th>
                                        <th>Status</th>
                                        <th>Perkiraan</th>
                                        <th>Daftar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($antrian as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['nm_pasien']) ?></td>
                                            <td><?= htmlspecialchars($row['Nama_Tempat']) ?></td>
                                            <td><?= htmlspecialchars($row['Nama_Dokter']) ?></td>
                                            <td><?= htmlspecialchars($row['Hari']) ?></td>
                                            <td><?= htmlspecialchars($row['Jam_Mulai']) ?> - <?= htmlspecialchars($row['Jam_Selesai']) ?></td>
                                            <td><?= htmlspecialchars($row['Jenis_Layanan']) ?></td>
                                            <td><?= htmlspecialchars($row['Status_Pendaftaran']) ?></td>
                                            <td><?= htmlspecialchars($row['Waktu_Perkiraan']) ?></td>
                                            <td><?= htmlspecialchars($row['Waktu_Pendaftaran']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="floating-whatsapp">
    <a href="https://wa.me/6285190086842?text=Halo%20Admin%2C%20saya%20ingin%20bertanya%20tentang%20antrian%20pasien." target="_blank">
        <i class="fab fa-whatsapp me-2"></i> Hubungi Admin
    </a>
</div>
<script src="/assets/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/fontawesome.min.js"></script>
</body>
</html>
