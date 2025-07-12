<!-- View: Jadwal - Form Tambah/Edit Jadwal -->
<div class="container py-4">
    <h2 class="mb-4">Form Jadwal Praktek Dokter</h2>
    <form method="post" action="?action=simpan">
        <div class="row mb-3">
            <div class="col-md-4">
                <label for="tempat" class="form-label">Tempat Praktek</label>
                <select name="tempat" id="tempat" class="form-select" required>
                    <option value="">Pilih Tempat</option>
                    <?php foreach ($tempat_praktek as $tp): ?>
                        <option value="<?= htmlspecialchars($tp['ID_Tempat_Praktek']) ?>"><?= htmlspecialchars($tp['Nama_Tempat']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="dokter" class="form-label">Dokter</label>
                <select name="dokter" id="dokter" class="form-select" required>
                    <option value="">Pilih Dokter</option>
                    <?php foreach ($dokter as $d): ?>
                        <option value="<?= htmlspecialchars($d['ID_Dokter']) ?>"><?= htmlspecialchars($d['Nama_Dokter']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="hari" class="form-label">Hari</label>
                <select name="hari" id="hari" class="form-select" required>
                    <option value="">Pilih Hari</option>
                    <option value="Senin">Senin</option>
                    <option value="Selasa">Selasa</option>
                    <option value="Rabu">Rabu</option>
                    <option value="Kamis">Kamis</option>
                    <option value="Jumat">Jumat</option>
                    <option value="Sabtu">Sabtu</option>
                    <option value="Minggu">Minggu</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="jam_mulai" class="form-label">Jam Mulai</label>
                <input type="time" name="jam_mulai" id="jam_mulai" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label for="jam_selesai" class="form-label">Jam Selesai</label>
                <input type="time" name="jam_selesai" id="jam_selesai" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label for="jenis_layanan" class="form-label">Jenis Layanan</label>
                <input type="text" name="jenis_layanan" id="jenis_layanan" class="form-control" required>
            </div>
        </div>
        <button type="submit" class="btn btn-success">Simpan Jadwal</button>
    </form>
</div>
