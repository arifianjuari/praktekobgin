-- Membuat tabel template_tatalaksana
CREATE TABLE IF NOT EXISTS `template_tatalaksana` (
  `id_template_tx` varchar(8) NOT NULL,
  `nama_template_tx` varchar(100) NOT NULL,
  `isi_template_tx` text NOT NULL,
  `kategori_tx` enum('fetomaternal', 'ginekologi umum', 'onkogin', 'fertilitas', 'uroginekologi') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active',
  `tags` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_template_tx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Menambahkan beberapa data contoh
INSERT INTO `template_tatalaksana` (`id_template_tx`, `nama_template_tx`, `isi_template_tx`, `kategori_tx`, `status`) VALUES
('TX000001', 'Terapi Anemia Kehamilan', '1. Tablet Fe 1x1\n2. Asam Folat 1x1\n3. Vitamin C 1x1\n4. Edukasi diet tinggi zat besi', 'fetomaternal', 'active'),
('TX000002', 'Terapi Mual Muntah Kehamilan', '1. Vitamin B6 3x10mg\n2. Antihistamin (Dimenhydrinate) 3x50mg\n3. Edukasi makan porsi kecil tapi sering\n4. Hindari makanan berminyak dan berbau menyengat', 'fetomaternal', 'active'),
('TX000003', 'Terapi Hipertensi dalam Kehamilan', '1. Methyldopa 3x250mg\n2. Monitoring tekanan darah 2x/hari\n3. Diet rendah garam\n4. Istirahat cukup\n5. Kontrol 1 minggu', 'fetomaternal', 'active'),
('TX000004', 'Terapi Infeksi Saluran Kemih', '1. Amoxicillin 3x500mg selama 7 hari\n2. Banyak minum air putih (minimal 2L/hari)\n3. Edukasi kebersihan area genital\n4. Kontrol 1 minggu dengan hasil urinalisis', 'ginekologi umum', 'active'),
('TX000005', 'Terapi Vaginitis', '1. Metronidazole 2x500mg selama 7 hari\n2. Nystatin vaginal suppositoria 1x1 selama 7 hari\n3. Edukasi kebersihan area genital\n4. Hindari douching\n5. Kontrol 2 minggu', 'ginekologi umum', 'active'),
('TX000006', 'Terapi Nyeri Haid', '1. Asam Mefenamat 3x500mg\n2. Kompres hangat pada perut bagian bawah\n3. Istirahat cukup\n4. Edukasi olahraga teratur', 'ginekologi umum', 'active'),
('TX000007', 'Terapi Mioma Uteri', '1. Asam Tranexamat 3x500mg saat perdarahan\n2. Tablet Fe 1x1\n3. Kontrol 1 bulan dengan USG ulangan\n4. Edukasi tanda bahaya perdarahan berlebih', 'ginekologi umum', 'active'),
('TX000008', 'Terapi Kista Ovarium', '1. Pil KB kombinasi selama 3 bulan\n2. Analgesik bila nyeri\n3. Kontrol 3 bulan dengan USG ulangan\n4. Edukasi tanda kegawatan (nyeri hebat, demam)', 'ginekologi umum', 'active'),
('TX000009', 'Terapi Kontrasepsi', '1. Pil KB kombinasi 1x1 mulai hari ke-1 haid\n2. Edukasi cara minum dan efek samping\n3. Kontrol 3 bulan\n4. Pemeriksaan tekanan darah rutin', 'fertilitas', 'active'),
('TX000010', 'Terapi Pasca Pemasangan IUD', '1. Asam Mefenamat 3x500mg bila nyeri\n2. Hindari hubungan seksual selama 1 minggu\n3. Kontrol 1 bulan untuk evaluasi posisi IUD\n4. Edukasi tanda bahaya (perdarahan, nyeri hebat, demam)', 'fertilitas', 'active'),
('TX000011', 'Terapi Kanker Serviks Stadium Awal', '1. Rujuk ke spesialis onkologi ginekologi\n2. Edukasi mengenai prosedur operasi\n3. Pemeriksaan laboratorium pra-operasi\n4. Konsultasi anestesi', 'onkogin', 'active'),
('TX000012', 'Terapi Inkontinensia Urin', '1. Latihan otot dasar panggul (Kegel) 3x sehari\n2. Edukasi mengenai jadwal berkemih\n3. Hindari kafein dan alkohol\n4. Kontrol 1 bulan', 'uroginekologi', 'active'); 