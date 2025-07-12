<!-- View: Jadwal - Index -->
<div class="container py-4">
    <h2 class="mb-4">Jadwal Praktek Dokter</h2>
    <!-- Tambahkan filter dan tabel jadwal di sini (lihat controller untuk data: $jadwal_rutin, $tempat_praktek, $dokter) -->
    <form method="get" class="mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label for="tempat" class="form-label">Tempat Praktek</label>
                <select name="tempat" id="tempat" class="form-select">
                    <option value="">Semua Tempat</option>
                    <?php foreach ($tempat_praktek as $tp): ?>
                        <option value="<?= htmlspecialchars($tp['ID_Tempat_Praktek']) ?>" <?= (isset($_GET['tempat']) && $_GET['tempat'] == $tp['ID_Tempat_Praktek']) ? 'selected' : '' ?>><?= htmlspecialchars($tp['Nama_Tempat']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="dokter" class="form-label">Dokter</label>
                <select name="dokter" id="dokter" class="form-select">
                    <option value="">Semua Dokter</option>
                    <?php foreach ($dokter as $d): ?>
                        <option value="<?= htmlspecialchars($d['ID_Dokter']) ?>" <?= (isset($_GET['dokter']) && $_GET['dokter'] == $d['ID_Dokter']) ? 'selected' : '' ?>><?= htmlspecialchars($d['Nama_Dokter']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="hari" class="form-label">Hari</label>
                <select name="hari" id="hari" class="form-select">
                    <option value="">Semua Hari</option>
                    <option value="Senin" <?= (isset($_GET['hari']) && $_GET['hari'] == 'Senin') ? 'selected' : '' ?>>Senin</option>
                    <option value="Selasa" <?= (isset($_GET['hari']) && $_GET['hari'] == 'Selasa') ? 'selected' : '' ?>>Selasa</option>
                    <option value="Rabu" <?= (isset($_GET['hari']) && $_GET['hari'] == 'Rabu') ? 'selected' : '' ?>>Rabu</option>
                    <option value="Kamis" <?= (isset($_GET['hari']) && $_GET['hari'] == 'Kamis') ? 'selected' : '' ?>>Kamis</option>
                    <option value="Jumat" <?= (isset($_GET['hari']) && $_GET['hari'] == 'Jumat') ? 'selected' : '' ?>>Jumat</option>
                    <option value="Sabtu" <?= (isset($_GET['hari']) && $_GET['hari'] == 'Sabtu') ? 'selected' : '' ?>>Sabtu</option>
                    <option value="Minggu" <?= (isset($_GET['hari']) && $_GET['hari'] == 'Minggu') ? 'selected' : '' ?>>Minggu</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </div>
    </form>
    <div class="table-responsive mt-4">
        <table class="table table-bordered table-striped">
            <thead class="table-light">
                <tr>
                    <th>Tempat Praktek</th>
                    <th>Dokter</th>
                    <th>Hari</th>
                    <th>Jam Mulai</th>
                    <th>Jam Selesai</th>
                    <th>Jenis Layanan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($jadwal_rutin)): ?>
                    <?php foreach ($jadwal_rutin as $j): ?>
                        <tr>
                            <td><?= htmlspecialchars($j['Nama_Tempat']) ?></td>
                            <td><?= htmlspecialchars($j['Nama_Dokter']) ?></td>
                            <td><?= htmlspecialchars($j['Hari']) ?></td>
                            <td><?= htmlspecialchars($j['Jam_Mulai']) ?></td>
                            <td><?= htmlspecialchars($j['Jam_Selesai']) ?></td>
                            <td><?= htmlspecialchars($j['Jenis_Layanan']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center">Tidak ada data jadwal ditemukan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
