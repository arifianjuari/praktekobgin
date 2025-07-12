-- Tabel pengumuman
CREATE TABLE IF NOT EXISTS pengumuman (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(255) NOT NULL,
    isi TEXT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel pasien
CREATE TABLE IF NOT EXISTS pasien (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(255) NOT NULL,
    no_rm VARCHAR(50) UNIQUE,
    tanggal_lahir DATE,
    jenis_kelamin ENUM('L', 'P'),
    alamat TEXT,
    no_telp VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel antrian
CREATE TABLE IF NOT EXISTS antrian (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_antrian VARCHAR(10) NOT NULL,
    id_pasien INT NOT NULL,
    tanggal DATE NOT NULL,
    status ENUM('menunggu', 'dilayani', 'selesai', 'batal') DEFAULT 'menunggu',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pasien) REFERENCES pasien(id)
);

-- Index untuk optimasi query
CREATE INDEX idx_antrian_tanggal ON antrian(tanggal);
CREATE INDEX idx_antrian_status ON antrian(status);
CREATE INDEX idx_pengumuman_status ON pengumuman(status); 