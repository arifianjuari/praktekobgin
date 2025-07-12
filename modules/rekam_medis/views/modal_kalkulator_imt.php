<!-- Modal Kalkulator IMT -->
<div class="modal fade" id="modalHitungIMT" tabindex="-1" aria-labelledby="modalHitungIMTLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalHitungIMTLabel">Kalkulator IMT</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="kalkulator_bb" class="form-label">Berat Badan (kg)</label>
                    <input type="number" step="0.1" class="form-control" id="kalkulator_bb">
                </div>
                <div class="mb-3">
                    <label for="kalkulator_tb" class="form-label">Tinggi Badan (cm)</label>
                    <input type="number" step="0.1" class="form-control" id="kalkulator_tb">
                </div>
                <div class="mb-3">
                    <label for="hasil_imt" class="form-label">Hasil IMT</label>
                    <input type="text" class="form-control" id="hasil_imt" readonly>
                </div>
                <div class="mb-3">
                    <label for="kategori_hasil_imt" class="form-label">Kategori</label>
                    <input type="text" class="form-control" id="kategori_hasil_imt" readonly>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="hitungIMT">Hitung</button>
                <button type="button" class="btn btn-success" id="gunakanIMT">Gunakan</button>
            </div>
        </div>
    </div>
</div>
