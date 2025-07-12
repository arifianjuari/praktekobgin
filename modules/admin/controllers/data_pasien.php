<?php
session_start();
require_once '../config_auth.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $base_url . "/login.php");
    exit();
}

$page_title = "Data Pasien";

// Inisialisasi variabel pencarian
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Jumlah data per halaman
$offset = ($page - 1) * $limit;

// Ambil data pasien dengan pagination dan pencarian
try {
    // Query untuk menghitung total data
    $count_query = "SELECT COUNT(*) FROM pasien";
    if (!empty($search)) {
        $count_query .= " WHERE no_rkm_medis LIKE :search OR nm_pasien LIKE :search OR no_ktp LIKE :search OR no_tlp LIKE :search";
    }

    $count_stmt = $conn_db2->prepare($count_query);
    if (!empty($search)) {
        $count_stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    }
    $count_stmt->execute();
    $total_records = $count_stmt->fetchColumn();

    // Hitung total halaman
    $total_pages = ceil($total_records / $limit);

    // Query untuk mengambil data pasien
    $query = "SELECT * FROM pasien";
    if (!empty($search)) {
        $query .= " WHERE no_rkm_medis LIKE :search OR nm_pasien LIKE :search OR no_ktp LIKE :search OR no_tlp LIKE :search";
    }
    $query .= " ORDER BY nm_pasien ASC LIMIT :limit OFFSET :offset";

    $stmt = $conn_db2->prepare($query);
    if (!empty($search)) {
        $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $pasien = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    $pasien = [];
    $total_pages = 0;
}

// Handle form submission untuk mengedit pasien
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'edit') {
                // Update data pasien
                $query = "UPDATE pasien SET 
                            nm_pasien = :nama,
                            jk = :jk,
                            tgl_lahir = :tgl_lahir,
                            alamat = :alamat,
                            pekerjaan = :pekerjaan,
                            no_tlp = :no_tlp,
                            umur = :umur,
                            kd_kec = :kd_kec,
                            nm_ibu = :nm_ibu,
                            namakeluarga = :namakeluarga,
                            kd_pj = :kd_pj,
                            kd_kel = :kd_kel,
                            kd_kab = :kd_kab,
                            tgl_daftar = :tgl_daftar
                        WHERE no_rkm_medis = :no_rkm_medis";

                // Hitung umur berdasarkan tanggal lahir
                $umur = date_diff(date_create($_POST['tgl_lahir']), date_create('today'))->y;

                $stmt = $conn_db2->prepare($query);
                $stmt->execute([
                    'no_rkm_medis' => $_POST['no_rkm_medis'],
                    'nama' => $_POST['nama_pasien'],
                    'jk' => $_POST['jenis_kelamin'],
                    'tgl_lahir' => $_POST['tgl_lahir'],
                    'alamat' => $_POST['alamat'],
                    'pekerjaan' => $_POST['pekerjaan'],
                    'no_tlp' => $_POST['no_tlp'],
                    'umur' => $umur,
                    'kd_kec' => $_POST['kd_kec'],
                    'nm_ibu' => $_POST['nm_ibu'],
                    'namakeluarga' => $_POST['namakeluarga'],
                    'kd_pj' => $_POST['kd_pj'],
                    'kd_kel' => $_POST['kd_kel'],
                    'kd_kab' => $_POST['kd_kab'],
                    'tgl_daftar' => $_POST['tgl_daftar']
                ]);

                $_SESSION['success'] = "Data pasien berhasil diperbarui";
            } elseif ($_POST['action'] === 'add') {
                // Generate nomor rekam medis baru
                $query_last_rm = "SELECT MAX(CAST(no_rkm_medis AS UNSIGNED)) as last_rm FROM pasien";
                $stmt_last_rm = $conn_db2->prepare($query_last_rm);
                $stmt_last_rm->execute();
                $last_rm = $stmt_last_rm->fetch(PDO::FETCH_ASSOC);
                $new_rm = $last_rm['last_rm'] + 1;
                $no_rkm_medis = str_pad($new_rm, 6, '0', STR_PAD_LEFT);

                // Hitung umur berdasarkan tanggal lahir
                $umur = date_diff(date_create($_POST['tgl_lahir']), date_create('today'))->y;

                // Tambah data pasien baru
                $query = "INSERT INTO pasien (
                            no_rkm_medis, nm_pasien, no_ktp, jk, tgl_lahir, 
                            alamat, pekerjaan, no_tlp, umur, kd_kec, 
                            nm_ibu, namakeluarga, kd_pj, kd_kel, kd_kab, tgl_daftar
                        ) VALUES (
                            :no_rkm_medis, :nama, :no_ktp, :jk, :tgl_lahir, 
                            :alamat, :pekerjaan, :no_tlp, :umur, :kd_kec, 
                            :nm_ibu, :namakeluarga, :kd_pj, :kd_kel, :kd_kab, :tgl_daftar
                        )";

                $stmt = $conn_db2->prepare($query);
                $stmt->execute([
                    'no_rkm_medis' => $no_rkm_medis,
                    'nama' => $_POST['nama_pasien'],
                    'no_ktp' => $_POST['no_ktp'],
                    'jk' => $_POST['jenis_kelamin'],
                    'tgl_lahir' => $_POST['tgl_lahir'],
                    'alamat' => $_POST['alamat'],
                    'pekerjaan' => $_POST['pekerjaan'],
                    'no_tlp' => $_POST['no_tlp'],
                    'umur' => $umur,
                    'kd_kec' => $_POST['kd_kec'],
                    'nm_ibu' => $_POST['nm_ibu'],
                    'namakeluarga' => $_POST['namakeluarga'],
                    'kd_pj' => $_POST['kd_pj'],
                    'kd_kel' => $_POST['kd_kel'],
                    'kd_kab' => $_POST['kd_kab'],
                    'tgl_daftar' => $_POST['tgl_daftar'] ?: date('Y-m-d')
                ]);

                $_SESSION['success'] = "Data pasien berhasil ditambahkan dengan No. RM: $no_rkm_medis";
            } elseif ($_POST['action'] === 'delete') {
                // Hapus data pasien
                $query = "DELETE FROM pasien WHERE no_rkm_medis = :no_rkm_medis";
                $stmt = $conn_db2->prepare($query);
                $stmt->execute([
                    'no_rkm_medis' => $_POST['no_rkm_medis']
                ]);

                $_SESSION['success'] = "Data pasien berhasil dihapus";
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }

    header("Location: data_pasien.php" . (!empty($search) ? "?search=$search" : ""));
    exit();
}

// Ambil data kecamatan
try {
    // Menggunakan array statis untuk wilayah
    $kecamatan = [
        ['kd_kec' => '1', 'nm_kec' => 'Batu'],
        ['kd_kec' => '2', 'nm_kec' => 'Bumiaji'],
        ['kd_kec' => '3', 'nm_kec' => 'Junrejo'],
        ['kd_kec' => '4', 'nm_kec' => 'Pujon'],
        ['kd_kec' => '5', 'nm_kec' => 'Ngantang'],
        ['kd_kec' => '6', 'nm_kec' => 'Lainnya']
    ];
} catch (PDOException $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    $kecamatan = [];
}

// Ambil data kelurahan
try {
    // Menggunakan array statis untuk kelurahan
    $kelurahan = [
        ['kd_kel' => '1', 'nm_kel' => 'Sisir'],
        ['kd_kel' => '2', 'nm_kel' => 'Temas'],
        ['kd_kel' => '3', 'nm_kel' => 'Ngaglik'],
        ['kd_kel' => '4', 'nm_kel' => 'Songgokerto'],
        ['kd_kel' => '5', 'nm_kel' => 'Lainnya']
    ];
} catch (PDOException $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    $kelurahan = [];
}

// Ambil data kabupaten
try {
    // Menggunakan array statis untuk kabupaten
    $kabupaten = [
        ['kd_kab' => '1', 'nm_kab' => 'Kota Batu'],
        ['kd_kab' => '2', 'nm_kab' => 'Kota Malang'],
        ['kd_kab' => '3', 'nm_kab' => 'Kabupaten Malang'],
        ['kd_kab' => '4', 'nm_kab' => 'Lainnya']
    ];
} catch (PDOException $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    $kabupaten = [];
}

// Ambil data cara bayar
try {
    // Menggunakan array statis untuk cara bayar
    $cara_bayar = [
        ['kd_pj' => 'UMU', 'nm_pj' => 'Umum'],
        ['kd_pj' => 'BPJ', 'nm_pj' => 'BPJS'],
        ['kd_pj' => 'ASR', 'nm_pj' => 'Asuransi'],
        ['kd_pj' => 'KOR', 'nm_pj' => 'Korporasi']
    ];
} catch (PDOException $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    $cara_bayar = [];
}

ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Data Pasien</h4>
        <div class="d-flex">
            <form action="" method="GET" class="d-flex me-2">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Cari pasien..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAddPasien">
                <i class="bi bi-plus-circle"></i> Tambah Pasien
            </button>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th>No. RM</th>
                            <th>Nama Pasien</th>
                            <th>NIK</th>
                            <th>Jenis Kelamin</th>
                            <th>Tanggal Lahir</th>
                            <th>Umur</th>
                            <th>Alamat</th>
                            <th>Telepon</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($pasien) > 0): ?>
                            <?php foreach ($pasien as $index => $p): ?>
                                <tr>
                                    <td><?php echo $offset + $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($p['no_rkm_medis']); ?></td>
                                    <td><?php echo htmlspecialchars($p['nm_pasien']); ?></td>
                                    <td><?php echo htmlspecialchars($p['no_ktp']); ?></td>
                                    <td><?php echo htmlspecialchars($p['jk']); ?></td>
                                    <td><?php echo date('d-m-Y', strtotime($p['tgl_lahir'])); ?></td>
                                    <td><?php echo htmlspecialchars($p['umur']); ?> th</td>
                                    <td><?php echo htmlspecialchars($p['alamat']); ?></td>
                                    <td><?php echo htmlspecialchars($p['no_tlp']); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-warning edit-pasien"
                                                data-pasien='<?php echo htmlspecialchars(json_encode($p)); ?>'>
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger delete-pasien"
                                                data-no-rm="<?php echo htmlspecialchars($p['no_rkm_medis']); ?>"
                                                data-nama="<?php echo htmlspecialchars($p['nm_pasien']); ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center">Tidak ada data pasien</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Previous</a>
                        </li>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Edit Pasien -->
<div class="modal fade" id="modalEditPasien" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Data Pasien</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="no_rkm_medis" id="edit_no_rkm_medis">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nama Pasien</label>
                                <input type="text" name="nama_pasien" id="edit_nama_pasien" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">NIK</label>
                                <input type="text" name="no_ktp" id="edit_no_ktp" class="form-control" readonly>
                                <small class="text-muted">NIK tidak dapat diubah</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Jenis Kelamin</label>
                                <select name="jenis_kelamin" id="edit_jenis_kelamin" class="form-select" required>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tanggal Lahir</label>
                                <input type="date" name="tgl_lahir" id="edit_tgl_lahir" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nama Ibu</label>
                                <input type="text" name="nm_ibu" id="edit_nm_ibu" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nama Keluarga</label>
                                <input type="text" name="namakeluarga" id="edit_namakeluarga" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tanggal Daftar</label>
                                <input type="date" name="tgl_daftar" id="edit_tgl_daftar" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Alamat</label>
                                <textarea name="alamat" id="edit_alamat" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kecamatan</label>
                                <select name="kd_kec" id="edit_kd_kec" class="form-select">
                                    <option value="">Pilih Kecamatan</option>
                                    <?php foreach ($kecamatan as $k): ?>
                                        <option value="<?php echo $k['kd_kec']; ?>"><?php echo $k['nm_kec']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kelurahan</label>
                                <select name="kd_kel" id="edit_kd_kel" class="form-select">
                                    <option value="">Pilih Kelurahan</option>
                                    <?php foreach ($kelurahan as $k): ?>
                                        <option value="<?php echo $k['kd_kel']; ?>"><?php echo $k['nm_kel']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kabupaten</label>
                                <select name="kd_kab" id="edit_kd_kab" class="form-select">
                                    <option value="">Pilih Kabupaten</option>
                                    <?php foreach ($kabupaten as $k): ?>
                                        <option value="<?php echo $k['kd_kab']; ?>"><?php echo $k['nm_kab']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Cara Bayar</label>
                                <select name="kd_pj" id="edit_kd_pj" class="form-select">
                                    <option value="">Pilih Cara Bayar</option>
                                    <?php foreach ($cara_bayar as $cb): ?>
                                        <option value="<?php echo $cb['kd_pj']; ?>"><?php echo $cb['nm_pj']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Pekerjaan</label>
                                <input type="text" name="pekerjaan" id="edit_pekerjaan" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nomor Telepon</label>
                                <input type="text" name="no_tlp" id="edit_no_tlp" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tambah Pasien -->
<div class="modal fade" id="modalAddPasien" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Data Pasien Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nama Pasien</label>
                                <input type="text" name="nama_pasien" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">NIK</label>
                                <input type="text" name="no_ktp" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Jenis Kelamin</label>
                                <select name="jenis_kelamin" class="form-select" required>
                                    <option value="">Pilih Jenis Kelamin</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tanggal Lahir</label>
                                <input type="date" name="tgl_lahir" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nama Ibu</label>
                                <input type="text" name="nm_ibu" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nama Keluarga</label>
                                <input type="text" name="namakeluarga" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Alamat</label>
                                <textarea name="alamat" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kecamatan</label>
                                <select name="kd_kec" class="form-select">
                                    <option value="">Pilih Kecamatan</option>
                                    <?php foreach ($kecamatan as $k): ?>
                                        <option value="<?php echo $k['kd_kec']; ?>"><?php echo $k['nm_kec']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kelurahan</label>
                                <select name="kd_kel" class="form-select">
                                    <option value="">Pilih Kelurahan</option>
                                    <?php foreach ($kelurahan as $k): ?>
                                        <option value="<?php echo $k['kd_kel']; ?>"><?php echo $k['nm_kel']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kabupaten</label>
                                <select name="kd_kab" class="form-select">
                                    <option value="">Pilih Kabupaten</option>
                                    <?php foreach ($kabupaten as $k): ?>
                                        <option value="<?php echo $k['kd_kab']; ?>"><?php echo $k['nm_kab']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Cara Bayar</label>
                                <select name="kd_pj" class="form-select">
                                    <option value="">Pilih Cara Bayar</option>
                                    <?php foreach ($cara_bayar as $cb): ?>
                                        <option value="<?php echo $cb['kd_pj']; ?>"><?php echo $cb['nm_pj']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Pekerjaan</label>
                                <input type="text" name="pekerjaan" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nomor Telepon</label>
                                <input type="text" name="no_tlp" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="modalDeletePasien" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus data pasien <strong id="delete-nama-pasien"></strong>?</p>
                <p class="text-danger">Tindakan ini tidak dapat dibatalkan!</p>
            </div>
            <div class="modal-footer">
                <form action="" method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="no_rkm_medis" id="delete_no_rkm_medis">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Hapus Data</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Additional CSS
$additional_css = "
    .table tbody td {
        vertical-align: middle;
        font-size: 0.9rem;
    }
    .badge {
        font-weight: 500;
    }
";

// Additional JavaScript
$additional_js = "
document.addEventListener('DOMContentLoaded', function() {
    // Edit pasien
    document.querySelectorAll('.edit-pasien').forEach(function(button) {
        button.addEventListener('click', function() {
            var pasien = JSON.parse(this.getAttribute('data-pasien'));
            document.getElementById('edit_no_rkm_medis').value = pasien.no_rkm_medis;
            document.getElementById('edit_nama_pasien').value = pasien.nm_pasien;
            document.getElementById('edit_no_ktp').value = pasien.no_ktp;
            document.getElementById('edit_jenis_kelamin').value = pasien.jk;
            document.getElementById('edit_tgl_lahir').value = pasien.tgl_lahir;
            document.getElementById('edit_alamat').value = pasien.alamat;
            document.getElementById('edit_pekerjaan').value = pasien.pekerjaan;
            document.getElementById('edit_no_tlp').value = pasien.no_tlp;
            document.getElementById('edit_kd_kec').value = pasien.kd_kec;
            document.getElementById('edit_nm_ibu').value = pasien.nm_ibu;
            document.getElementById('edit_namakeluarga').value = pasien.namakeluarga;
            document.getElementById('edit_kd_pj').value = pasien.kd_pj;
            document.getElementById('edit_kd_kel').value = pasien.kd_kel;
            document.getElementById('edit_kd_kab').value = pasien.kd_kab;
            document.getElementById('edit_tgl_daftar').value = pasien.tgl_daftar;
            
            new bootstrap.Modal(document.getElementById('modalEditPasien')).show();
        });
    });
    
    // Hapus pasien
    document.querySelectorAll('.delete-pasien').forEach(function(button) {
        button.addEventListener('click', function() {
            var noRm = this.getAttribute('data-no-rm');
            var nama = this.getAttribute('data-nama');
            
            document.getElementById('delete_no_rkm_medis').value = noRm;
            document.getElementById('delete-nama-pasien').textContent = nama;
            
            new bootstrap.Modal(document.getElementById('modalDeletePasien')).show();
        });
    });
});
";

include '../template/layout.php';
?>