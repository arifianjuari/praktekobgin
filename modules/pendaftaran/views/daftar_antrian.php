<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Daftar Antrian</h2>
        <div>
            <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#filterSection">
                <i class="bi bi-funnel"></i> Filter
            </button>
        </div>
    </div>

    <div class="collapse" id="filterSection">
        <div class="card mb-4">
            <div class="card-body">
                <form id="filterForm" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="hari" class="form-label">Hari</label>
                        <select class="form-select" id="hari" name="hari">
                            <option value="">Semua Hari</option>
                            <option value="Senin">Senin</option>
                            <option value="Selasa">Selasa</option>
                            <option value="Rabu">Rabu</option>
                            <option value="Kamis">Kamis</option>
                            <option value="Jumat">Jumat</option>
                            <option value="Sabtu">Sabtu</option>
                            <option value="Minggu">Minggu</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="tempat" class="form-label">Tempat Praktek</label>
                        <select class="form-select" id="tempat" name="tempat">
                            <option value="">Semua Tempat</option>
                            <?php foreach ($tempat_praktek as $tp): ?>
                                <option value="<?= htmlspecialchars($tp['ID_Tempat_Praktek']) ?>">
                                    <?= htmlspecialchars($tp['Nama_Tempat']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="dokter" class="form-label">Dokter</label>
                        <select class="form-select" id="dokter" name="dokter">
                            <option value="">Semua Dokter</option>
                            <?php foreach ($dokter as $d): ?>
                                <option value="<?= htmlspecialchars($d['ID_Dokter']) ?>">
                                    <?= htmlspecialchars($d['Nama_Dokter']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger rounded-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-circle fa-2x me-3"></i>
                <div>
                    <h5 class="alert-heading">Error!</h5>
                    <p class="mb-0"><?= htmlspecialchars($error_message) ?></p>
                </div>
            </div>
        </div>
    <?php elseif (empty($antrian_by_day_place)): ?>
        <div class="alert alert-info rounded-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-info-circle fa-2x me-3"></i>
                <div>
                    <h5 class="alert-heading">Tidak Ada Data</h5>
                    <p class="mb-0">Tidak ada antrian yang ditemukan dengan filter yang dipilih.</p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Tampilan Antrian Minimalis -->
        <div class="row">
            <?php foreach ($antrian_by_day_place as $group): ?>
                <div class="col-md-12 mb-4">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center py-3">
                            <h6 class="mb-0 fw-bold text-teal">
                                <i class="fas fa-calendar-day me-2 text-pink"></i><span class="text-teal"><?= htmlspecialchars($group['hari']) ?></span>
                                <span class="mx-2 text-muted">|</span>
                                <i class="fas fa-hospital me-2 text-primary"></i><span class="text-primary"><?= htmlspecialchars($group['tempat']) ?></span>
                                <span class="mx-2 text-muted">|</span>
                                <i class="fas fa-user-md me-2 text-success"></i><span class="text-success"><?= htmlspecialchars($group['dokter']) ?></span>
                                <span class="mx-2 text-muted">|</span>
                                <i class="fas fa-clock me-2 text-warning"></i><span class="text-warning"><?= htmlspecialchars($group['jam_mulai']) ?> - <?= htmlspecialchars($group['jam_selesai']) ?></span>
                            </h6>
                            <span class="badge bg-primary rounded-pill"><?= count($group['antrian']) ?> Pasien</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" class="text-center" style="width: 60px;">No</th>
                                            <th scope="col">Pasien</th>
                                            <th scope="col" class="text-center">Status</th>
                                            <th scope="col" class="text-center">Waktu Perkiraan</th>
                                            <th scope="col" class="text-center">Waktu Daftar</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $i = 1;
                                        foreach ($group['antrian'] as $row): ?>
                                            <tr>
                                                <td class="text-center"><?= $i++ ?></td>
                                                <td>
                                                    <div class="fw-medium"><?= htmlspecialchars($row['nm_pasien']) ?></div>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($row['Status_Pendaftaran'] == 'Dikonfirmasi'): ?>
                                                        <span class="badge bg-success">Dikonfirmasi</span>
                                                    <?php elseif ($row['Status_Pendaftaran'] == 'Menunggu'): ?>
                                                        <span class="badge bg-warning">Menunggu</span>
                                                    <?php elseif ($row['Status_Pendaftaran'] == 'Batal'): ?>
                                                        <span class="badge bg-danger">Batal</span>
                                                    <?php elseif ($row['Status_Pendaftaran'] == 'Selesai'): ?>
                                                        <span class="badge bg-info">Selesai</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary"><?= htmlspecialchars($row['Status_Pendaftaran']) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <i class="far fa-clock text-muted me-1"></i>
                                                    <?= !empty($row['Waktu_Perkiraan']) ? htmlspecialchars(substr($row['Waktu_Perkiraan'], 0, 5)) : '-' ?>
                                                </td>
                                                <td class="text-center">
                                                    <i class="far fa-calendar-alt text-muted me-1"></i>
                                                    <?= date('d/m/Y H:i', strtotime($row['Waktu_Pendaftaran'])) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Tambahkan floating WhatsApp button -->
<div class="floating-whatsapp">
    <a href="https://wa.me/6285190086842?text=Halo%20Admin%2C%20saya%20ingin%20bertanya%20tentang%20antrian%20pasien." target="_blank">
        <i class="fab fa-whatsapp"></i>
    </a>
    <span class="tooltip-text">Hubungi Admin via WhatsApp</span>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Set nilai filter dari URL jika ada
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('hari')) {
            document.getElementById('hari').value = urlParams.get('hari');
        }
        if (urlParams.has('tempat')) {
            document.getElementById('tempat').value = urlParams.get('tempat');
        }
        if (urlParams.has('dokter')) {
            document.getElementById('dokter').value = urlParams.get('dokter');
        }

        // Animasi untuk card saat hover
        const cards = document.querySelectorAll('.card');
        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = '0 10px 20px rgba(0,0,0,0.1)';
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.05)';
            });
        });

        // Tambahkan efek ripple pada tombol
        const buttons = document.querySelectorAll('.btn');
        buttons.forEach(button => {
            button.addEventListener('click', function(e) {
                const x = e.clientX - e.target.getBoundingClientRect().left;
                const y = e.clientY - e.target.getBoundingClientRect().top;

                const ripple = document.createElement('span');
                ripple.classList.add('ripple-effect');
                ripple.style.left = `${x}px`;
                ripple.style.top = `${y}px`;

                this.appendChild(ripple);

                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });

        // Fungsi untuk menampilkan notifikasi refresh
        function showRefreshNotification() {
            const notification = document.createElement('div');
            notification.className = 'position-fixed bottom-0 end-0 p-3';
            notification.style.zIndex = '5';
            notification.innerHTML = `
                <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header bg-info text-white">
                        <i class="fas fa-sync-alt me-2"></i>
                        <strong class="me-auto">Informasi</strong>
                        <small>Baru saja</small>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        Halaman diperbarui secara otomatis.
                    </div>
                </div>
            `;
            document.body.appendChild(notification);

            // Hapus notifikasi setelah 3 detik
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // Set interval untuk refresh otomatis dengan interval 2 menit
        const refreshInterval = 120000; // 2 menit dalam milidetik
        setInterval(function() {
            // Simpan posisi scroll saat ini
            const scrollPosition = window.scrollY;

            // Simpan posisi scroll di sessionStorage
            sessionStorage.setItem('scrollPosition', scrollPosition);

            // Refresh halaman
            location.reload();
        }, refreshInterval);

        // Kembalikan posisi scroll setelah refresh
        const savedScrollPosition = sessionStorage.getItem('scrollPosition');
        if (savedScrollPosition) {
            window.scrollTo(0, parseInt(savedScrollPosition));
            showRefreshNotification();
        }
    });
</script>

<?php
// Query untuk mengambil data antrian
$query = "
    SELECT 
        p.ID_Pendaftaran,
        p.nm_pasien,
        jr.Hari,
        jr.Jam_Mulai,
        jr.Jam_Selesai,
        tp.Nama_Tempat,
        d.Nama_Dokter,
        p.Status_Pendaftaran
    FROM 
        pendaftaran p
    JOIN 
        jadwal_rutin jr ON p.ID_Jadwal = jr.ID_Jadwal_Rutin
    JOIN 
        tempat_praktek tp ON p.ID_Tempat_Praktek = tp.ID_Tempat_Praktek
    JOIN 
        dokter d ON p.ID_Dokter = d.ID_Dokter
    WHERE 1=1
";

// Tambahkan filter jika ada
$params = [];

if (!empty($_GET['hari'])) {
    $query .= " AND jr.Hari = :hari";
    $params[':hari'] = $_GET['hari'];
}

if (!empty($_GET['tempat'])) {
    $query .= " AND p.ID_Tempat_Praktek = :tempat";
    $params[':tempat'] = $_GET['tempat'];
}

if (!empty($_GET['dokter'])) {
    $query .= " AND p.ID_Dokter = :dokter";
    $params[':dokter'] = $_GET['dokter'];
}

$query .= " ORDER BY jr.Hari ASC, jr.Jam_Mulai ASC";

$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$antrian = $stmt->fetchAll(PDO::FETCH_ASSOC);
