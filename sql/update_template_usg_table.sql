-- Script untuk memperbarui struktur tabel template_usg
-- Jika ingin mengubah enum kategori_usg menjadi lebih banyak kategori, gunakan script berikut:

-- ALTER TABLE template_usg MODIFY COLUMN kategori_usg ENUM('obstetri', 'ginekologi', 'fetomaternal', 'ginekologi umum', 'onkogin', 'fertilitas', 'uroginekologi') NOT NULL;

-- Jika ingin menambahkan template baru, gunakan script berikut:

INSERT INTO template_usg (id_template_usg, nama_template_usg, isi_template_usg, kategori_usg, status) VALUES
('US000004', 'Obstetri TM3', 'Janin tunggal hidup intrauterin\nPresentasi kepala\nUkuran sesuai usia kehamilan\nDJJ (+) regular\nGerak janin (+)\nPlasenta di korpus posterior grade 2-3\nCairan ketuban cukup\nTBJ: ... gram', 'obstetri', 'active'),
('US000005', 'Ginekologi - Mioma', 'Uterus: membesar, kontur ireguler\nTampak massa hipoekoik di miometrium ... dengan ukuran ... x ... cm\nEndometrium: reguler, tebal ... mm\nOvarium kanan: ukuran normal, tidak tampak kista/massa\nOvarium kiri: ukuran normal, tidak tampak kista/massa\nCavum Douglas: tidak tampak cairan bebas', 'ginekologi', 'active'),
('US000006', 'Ginekologi - Kista Ovarium', 'Uterus: ukuran normal, kontur reguler, ekogenisitas homogen\nEndometrium: reguler, tebal ... mm\nMiometrium: tidak tampak kelainan fokal\nOvarium kanan: tampak kista anechoic dengan ukuran ... x ... cm, dinding tipis, tidak tampak komponen solid\nOvarium kiri: ukuran normal, tidak tampak kista/massa\nCavum Douglas: tidak tampak cairan bebas', 'ginekologi', 'active');

-- Jika perlu memperbaiki data yang sudah ada:

-- UPDATE template_usg SET kategori_usg = 'obstetri' WHERE nama_template_usg LIKE '%Obstetri%';
-- UPDATE template_usg SET kategori_usg = 'ginekologi' WHERE nama_template_usg LIKE '%Ginekologi%'; 