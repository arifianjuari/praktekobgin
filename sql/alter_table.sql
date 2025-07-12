-- Menambahkan kolom no_rkm_medis
ALTER TABLE penilaian_medis_ralan_kandungan 
ADD COLUMN no_rkm_medis VARCHAR(15) AFTER no_rawat,
ADD INDEX idx_no_rkm_medis (no_rkm_medis);

-- Menambahkan kolom resep
ALTER TABLE penilaian_medis_ralan_kandungan
ADD COLUMN resep TEXT AFTER tata;

-- Mengupdate data no_rkm_medis dari tabel reg_periksa
UPDATE penilaian_medis_ralan_kandungan pm
JOIN reg_periksa rp ON pm.no_rawat = rp.no_rawat
SET pm.no_rkm_medis = rp.no_rkm_medis;

-- Menambahkan foreign key ke tabel pasien (opsional, tapi disarankan)
ALTER TABLE penilaian_medis_ralan_kandungan
ADD CONSTRAINT fk_penilaian_medis_pasien
FOREIGN KEY (no_rkm_medis) REFERENCES pasien(no_rkm_medis)
ON DELETE RESTRICT ON UPDATE CASCADE; 