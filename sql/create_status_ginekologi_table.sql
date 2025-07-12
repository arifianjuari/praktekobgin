-- Buat tabel status_ginekologi jika belum ada
CREATE TABLE IF NOT EXISTS status_ginekologi (
    id_status_ginekologi VARCHAR(36) PRIMARY KEY,
    no_rkm_medis VARCHAR(15) NOT NULL,
    Parturien INT DEFAULT 0,
    Abortus INT DEFAULT 0,
    Hari_pertama_haid_terakhir DATE,
    Kontrasepsi_terakhir VARCHAR(100),
    lama_menikah_th INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (no_rkm_medis) REFERENCES pasien(no_rkm_medis) ON DELETE CASCADE
); 