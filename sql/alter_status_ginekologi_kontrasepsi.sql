-- Ubah tipe data kolom Kontrasepsi_terakhir menjadi ENUM
ALTER TABLE status_ginekologi 
MODIFY COLUMN Kontrasepsi_terakhir ENUM(
    'Tidak Ada',
    'Pil KB',
    'Suntik KB',
    'Spiral/IUD',
    'Implant',
    'MOW',
    'MOP',
    'Kondom'
) DEFAULT 'Tidak Ada'; 