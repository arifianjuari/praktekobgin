-- Indexes to speed up queries on formularium table
-- Run this once in your MySQL/MariaDB server

-- Composite index for WHERE kategori + ORDER BY nama_obat
CREATE INDEX idx_formularium_kategori_nama ON formularium (kategori, nama_obat);

-- Index for ORDER BY nama_obat when no filter is applied
CREATE INDEX idx_formularium_nama_obat ON formularium (nama_obat);

-- Optional: index for filtering/analytics by status
CREATE INDEX idx_formularium_status_aktif ON formularium (status_aktif);
