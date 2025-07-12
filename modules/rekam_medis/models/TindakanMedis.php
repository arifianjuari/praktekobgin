<?php
class TindakanMedis
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function generateId()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    public function getAllTindakanMedis()
    {
        $stmt = $this->pdo->prepare("
            SELECT tm.*, p.nm_pasien, d.Nama_Dokter
            FROM tindakan_medis tm
            JOIN pasien p ON tm.no_rkm_medis = p.no_rkm_medis
            JOIN dokter d ON tm.ID_Dokter = d.ID_Dokter
            ORDER BY tm.tgl_tindakan DESC, tm.jam_tindakan DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getTindakanMedisById($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT tm.*, p.nm_pasien, d.Nama_Dokter
            FROM tindakan_medis tm
            JOIN pasien p ON tm.no_rkm_medis = p.no_rkm_medis
            JOIN dokter d ON tm.ID_Dokter = d.ID_Dokter
            WHERE tm.id_tindakan = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getTindakanMedisByPasien($no_rkm_medis)
    {
        $stmt = $this->pdo->prepare("
            SELECT tm.*, p.nm_pasien, d.Nama_Dokter
            FROM tindakan_medis tm
            JOIN pasien p ON tm.no_rkm_medis = p.no_rkm_medis
            JOIN dokter d ON tm.ID_Dokter = d.ID_Dokter
            WHERE tm.no_rkm_medis = ?
            ORDER BY tm.tgl_tindakan DESC, tm.jam_tindakan DESC
        ");
        $stmt->execute([$no_rkm_medis]);
        return $stmt->fetchAll();
    }

    public function createTindakanMedis($data)
    {
        $id = $this->generateId();
        $stmt = $this->pdo->prepare("
            INSERT INTO tindakan_medis (
                id_tindakan, no_rkm_medis, ID_Dokter,
                tgl_tindakan, jam_tindakan, kode_tindakan, nama_tindakan,
                deskripsi_tindakan, hasil_tindakan, catatan
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $id,
            $data['no_rkm_medis'],
            $data['ID_Dokter'],
            $data['tgl_tindakan'],
            $data['jam_tindakan'],
            $data['kode_tindakan'] ?? null,
            $data['nama_tindakan'],
            $data['deskripsi_tindakan'] ?? null,
            $data['hasil_tindakan'] ?? null,
            $data['catatan'] ?? null
        ]);
        return $id;
    }

    public function updateTindakanMedis($id, $data)
    {
        $stmt = $this->pdo->prepare("
            UPDATE tindakan_medis SET
                no_rkm_medis = ?,
                ID_Dokter = ?,
                tgl_tindakan = ?,
                jam_tindakan = ?,
                kode_tindakan = ?,
                nama_tindakan = ?,
                deskripsi_tindakan = ?,
                hasil_tindakan = ?,
                catatan = ?
            WHERE id_tindakan = ?
        ");
        return $stmt->execute([
            $data['no_rkm_medis'],
            $data['ID_Dokter'],
            $data['tgl_tindakan'],
            $data['jam_tindakan'],
            $data['kode_tindakan'],
            $data['nama_tindakan'],
            $data['deskripsi_tindakan'],
            $data['hasil_tindakan'],
            $data['catatan'],
            $id
        ]);
    }

    public function deleteTindakanMedis($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM tindakan_medis WHERE id_tindakan = ?");
        return $stmt->execute([$id]);
    }
}
