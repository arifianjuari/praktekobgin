<?php
// Memulai session jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/config.php';
require_once 'config/database.php';

// Ambil slug dari URL
$id_edukasi = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($id_edukasi)) {
    // Redirect ke halaman edukasi jika id kosong
    header("Location: {$base_url}/edukasi.php");
    exit;
}

try {
    // Query untuk mengambil artikel berdasarkan id
    $query = "SELECT * FROM edukasi WHERE id_edukasi = :id_edukasi AND status_aktif = 1";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id_edukasi' => $id_edukasi]);
    $artikel = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$artikel) {
        // Artikel tidak ditemukan
        $error_message = "Artikel tidak ditemukan";
    }
} catch (PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($artikel) ? htmlspecialchars($artikel['judul']) : 'Artikel Tidak Ditemukan' ?> - Sistem Antrian Pasien</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="<?= $base_url ?>/assets/css/styles.css" rel="stylesheet">

    <style>
        .article-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .article-header {
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 1rem;
        }

        .article-meta {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }

        .article-category {
            display: inline-block;
            margin-right: 1rem;
        }

        .article-content {
            line-height: 1.8;
            font-size: 1.1rem;
        }

        .article-content img {
            max-width: 100%;
            height: auto;
            margin: 1.5rem 0;
        }

        .article-source {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #dee2e6;
            font-size: 0.9rem;
            color: #6c757d;
        }

        .related-articles {
            margin-top: 3rem;
        }

        .back-button {
            margin-bottom: 1rem;
        }

        .article-featured-image {
            width: 100%;
            max-height: 500px;
            object-fit: contain;
            margin-bottom: 2rem;
            border-radius: 0;
            padding: 0;
        }

        .image-container {
            text-align: center;
            margin-bottom: 2rem;
            background-color: transparent;
            padding: 0;
            border-radius: 0;
        }
    </style>
</head>

<body>
    <?php include_once 'template/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <div class="back-button">
                <a href="<?= $base_url ?>/edukasi.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left"></i> Kembali ke Daftar Artikel
                </a>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?= $error_message ?>
                </div>
            <?php elseif (isset($artikel)): ?>
                <div class="article-container">
                    <div class="article-header">
                        <h1 class="mb-3"><?= htmlspecialchars($artikel['judul']) ?></h1>

                        <div class="article-meta">
                            <span class="article-category badge bg-primary">
                                <?= htmlspecialchars($artikel['kategori']) ?>
                            </span>
                            <span class="article-date">
                                <i class="bi bi-calendar3"></i>
                                <?= date('d F Y', strtotime($artikel['created_at'])) ?>
                            </span>
                        </div>
                    </div>

                    <?php if (!empty($artikel['link_gambar'])): ?>
                        <div class="image-container">
                            <img src="<?= $base_url ?>/uploads/edukasi/<?= htmlspecialchars($artikel['link_gambar']) ?>"
                                alt="<?= htmlspecialchars($artikel['judul']) ?>"
                                class="article-featured-image">
                        </div>
                    <?php endif; ?>

                    <div class="article-content">
                        <?= $artikel['isi_edukasi'] ?>
                    </div>

                    <?php if (!empty($artikel['sumber'])): ?>
                        <div class="article-source">
                            <strong>Sumber:</strong> <?= htmlspecialchars($artikel['sumber']) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($artikel['tag'])): ?>
                        <div class="article-tags mt-3">
                            <strong>Tag:</strong>
                            <?php
                            $tags = explode(',', $artikel['tag']);
                            foreach ($tags as $tag):
                                $tag = trim($tag);
                                if (!empty($tag)):
                            ?>
                                    <a href="<?= $base_url ?>/edukasi.php?tag=<?= urlencode($tag) ?>" class="badge bg-secondary text-decoration-none me-1">
                                        #<?= htmlspecialchars($tag) ?>
                                    </a>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Artikel Terkait -->
                <?php
                try {
                    // Ambil artikel terkait berdasarkan kategori yang sama
                    $query_related = "SELECT id_edukasi, judul, created_at FROM edukasi 
                                    WHERE kategori = :kategori 
                                    AND id_edukasi != :current_id 
                                    AND status_aktif = 1 
                                    ORDER BY created_at DESC LIMIT 3";
                    $stmt_related = $conn->prepare($query_related);
                    $stmt_related->execute([
                        ':kategori' => $artikel['kategori'],
                        ':current_id' => $artikel['id_edukasi']
                    ]);
                    $related_articles = $stmt_related->fetchAll(PDO::FETCH_ASSOC);

                    if (count($related_articles) > 0):
                ?>
                        <div class="related-articles">
                            <h3 class="mb-4">Artikel Terkait</h3>
                            <div class="row">
                                <?php foreach ($related_articles as $related): ?>
                                    <div class="col-md-4">
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <h5 class="card-title">
                                                    <a href="<?= $base_url ?>/edukasi-detail.php?id=<?= htmlspecialchars($related['id_edukasi']) ?>" class="text-decoration-none text-dark">
                                                        <?= htmlspecialchars($related['judul']) ?>
                                                    </a>
                                                </h5>
                                                <div class="small text-muted">
                                                    <i class="bi bi-calendar3"></i>
                                                    <?= date('d F Y', strtotime($related['created_at'])) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                <?php
                    endif;
                } catch (PDOException $e) {
                    // Jangan tampilkan error untuk artikel terkait
                }
                ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-journal-x display-1 text-muted"></i>
                    <h2 class="mt-3">Artikel Tidak Ditemukan</h2>
                    <p class="text-muted">Artikel yang Anda cari tidak tersedia atau telah dihapus.</p>
                    <a href="<?= $base_url ?>/edukasi.php" class="btn btn-primary mt-2">
                        Kembali ke Daftar Artikel
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>