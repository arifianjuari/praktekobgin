-- Tambahkan kolom tanggal_pemeriksaan jika belum ada
ALTER TABLE status_ginekologi
ADD COLUMN IF NOT EXISTS tanggal_pemeriksaan DATE NULL AFTER no_rkm_medis;

-- Update nilai tanggal_pemeriksaan dari nilai created_at jika masih NULL
UPDATE status_ginekologi
SET tanggal_pemeriksaan = DATE(created_at)
WHERE tanggal_pemeriksaan IS NULL; 