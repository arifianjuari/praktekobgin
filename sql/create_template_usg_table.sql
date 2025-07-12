-- Membuat tabel template_usg
CREATE TABLE IF NOT EXISTS `template_usg` (
    `id_template_usg` varchar(8) NOT NULL,
    `nama_template_usg` varchar(100) NOT NULL,
    `isi_template_usg` text NOT NULL,
    `kategori_usg` enum('fetomaternal', 'ginekologi umum', 'onkogin', 'fertilitas', 'uroginekologi') NOT NULL,
    `created_at` timestamp NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `status` enum('active','inactive') DEFAULT 'active',
    `tags` varchar(255) DEFAULT NULL,
    PRIMARY KEY (`id_template_usg`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Menambahkan beberapa data awal
INSERT INTO `template_usg` (`id_template_usg`, `nama_template_usg`, `isi_template_usg`, `kategori_usg`, `status`) VALUES
('US000001', 'USG Kehamilan Trimester 1', 'Janin tunggal hidup intrauterin\nUkuran sesuai usia kehamilan\nDJJ (+) regular\nGerak janin (+)\nPlasenta di korpus posterior grade 0\nCairan ketuban cukup\nTBJ: ... gram', 'fetomaternal', 'active'),
('US000002', 'USG Kehamilan Trimester 2', 'Janin tunggal hidup intrauterin\nPresentasi kepala\nUkuran sesuai usia kehamilan\nDJJ (+) regular\nGerak janin (+)\nPlasenta di korpus posterior grade 1\nCairan ketuban cukup\nTBJ: ... gram', 'fetomaternal', 'active'),
('US000003', 'USG Kehamilan Trimester 3', 'Janin tunggal hidup intrauterin\nPresentasi kepala\nUkuran sesuai usia kehamilan\nDJJ (+) regular\nGerak janin (+)\nPlasenta di korpus posterior grade 2-3\nCairan ketuban cukup\nTBJ: ... gram', 'fetomaternal', 'active'),
('US000004', 'USG Ginekologi - Normal', 'Uterus: ukuran normal, kontur reguler, ekogenisitas homogen\nEndometrium: reguler, tebal ... mm\nMiometrium: tidak tampak kelainan fokal\nOvarium kanan: ukuran normal, tidak tampak kista/massa\nOvarium kiri: ukuran normal, tidak tampak kista/massa\nCavum Douglas: tidak tampak cairan bebas', 'ginekologi umum', 'active'),
('US000005', 'USG Mioma Uteri', 'Uterus: membesar, kontur ireguler\nTampak massa hipoekoik di miometrium ... dengan ukuran ... x ... cm\nEndometrium: reguler, tebal ... mm\nOvarium kanan: ukuran normal, tidak tampak kista/massa\nOvarium kiri: ukuran normal, tidak tampak kista/massa\nCavum Douglas: tidak tampak cairan bebas', 'ginekologi umum', 'active'),
('US000006', 'USG Kista Ovarium', 'Uterus: ukuran normal, kontur reguler, ekogenisitas homogen\nEndometrium: reguler, tebal ... mm\nMiometrium: tidak tampak kelainan fokal\nOvarium kanan: tampak kista anechoic dengan ukuran ... x ... cm, dinding tipis, tidak tampak komponen solid\nOvarium kiri: ukuran normal, tidak tampak kista/massa\nCavum Douglas: tidak tampak cairan bebas', 'ginekologi umum', 'active'),
('US000007', 'USG Kehamilan Ektopik', 'Uterus: ukuran normal, kontur reguler\nEndometrium: tebal, tidak tampak kantung gestasi intrauterin\nAdneksa kanan: tampak massa kompleks dengan ukuran ... x ... cm\nAdneksa kiri: tidak tampak kelainan\nCavum Douglas: tampak cairan bebas', 'ginekologi umum', 'active'),
('US000008', 'USG Kanker Ovarium', 'Uterus: ukuran normal, kontur reguler\nEndometrium: reguler, tebal ... mm\nOvarium kanan: tampak massa kompleks dengan komponen solid dan kistik, ukuran ... x ... cm, batas ireguler\nOvarium kiri: ukuran normal, tidak tampak kista/massa\nCavum Douglas: tampak cairan bebas\nTampak pembesaran KGB para-aorta', 'onkogin', 'active'),
('US000009', 'USG Kanker Serviks', 'Uterus: ukuran normal, kontur ireguler\nServiks: tampak massa hipoekoik dengan ukuran ... x ... cm, batas ireguler\nParametrium: tampak infiltrasi ke parametrium kanan/kiri\nVesika urinaria: tidak tampak infiltrasi\nRektum: tidak tampak infiltrasi\nTampak pembesaran KGB pelvik', 'onkogin', 'active'),
('US000010', 'USG Infertilitas', 'Uterus: ukuran normal, kontur reguler, ekogenisitas homogen\nEndometrium: reguler, tebal ... mm\nMiometrium: tidak tampak kelainan fokal\nOvarium kanan: ukuran normal, tampak ... folikel antral\nOvarium kiri: ukuran normal, tampak ... folikel antral\nCavum Douglas: tidak tampak cairan bebas', 'fertilitas', 'active'); 