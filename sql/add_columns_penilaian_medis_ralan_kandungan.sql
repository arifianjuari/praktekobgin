-- Menambahkan kolom tanggal_kontrol dan atensi ke tabel penilaian_medis_ralan_kandungan
ALTER TABLE penilaian_medis_ralan_kandungan 
ADD COLUMN tanggal_kontrol DATE NULL AFTER tata,
ADD COLUMN atensi BOOLEAN DEFAULT 0 AFTER tanggal_kontrol;

-- Keterangan:
-- tanggal_kontrol: Menyimpan tanggal kontrol pasien (bisa NULL jika tidak ada jadwal kontrol)
-- atensi: Menyimpan status atensi (1 = Ya, 0 = Tidak) 