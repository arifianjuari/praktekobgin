-- Hapus kolom yang tidak digunakan
ALTER TABLE status_ginekologi
DROP COLUMN IF EXISTS tanggal_pemeriksaan,
DROP COLUMN IF EXISTS menarche,
DROP COLUMN IF EXISTS siklus_haid,
DROP COLUMN IF EXISTS lama_haid,
DROP COLUMN IF EXISTS jumlah_pembalut,
DROP COLUMN IF EXISTS nyeri_haid,
DROP COLUMN IF EXISTS keputihan,
DROP COLUMN IF EXISTS kontrasepsi,
DROP COLUMN IF EXISTS riwayat_kb;

-- Tambah kolom baru jika belum ada
ALTER TABLE status_ginekologi
ADD COLUMN IF NOT EXISTS Parturien INT DEFAULT 0 AFTER no_rkm_medis,
ADD COLUMN IF NOT EXISTS Abortus INT DEFAULT 0 AFTER Parturien,
ADD COLUMN IF NOT EXISTS Hari_pertama_haid_terakhir DATE AFTER Abortus,
ADD COLUMN IF NOT EXISTS Kontrasepsi_terakhir VARCHAR(100) AFTER Hari_pertama_haid_terakhir,
ADD COLUMN IF NOT EXISTS lama_menikah_th INT DEFAULT 0 AFTER Kontrasepsi_terakhir; 