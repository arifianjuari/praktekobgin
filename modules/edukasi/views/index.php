<?php
/**
 * View untuk halaman Edukasi
 * 
 * Menampilkan daftar artikel edukasi dengan filter kategori dan tag
 */
?>

<!-- Header dengan Search -->
<div class="page-header">
    <div class="page-title-section">
        <h2 class="page-title">Edukasi Kesehatan</h2>
        <p class="text-muted">Temukan berbagai artikel informatif seputar kesehatan</p>
    </div>
    <div class="search-section">
        <form action="" method="GET" class="search-form">
            <div class="input-group">
                <input type="text" class="form-control" name="search"
                    placeholder="Cari artikel..." value="<?= htmlspecialchars($search) ?>">
                <?php if (!empty($selected_kategori)): ?>
                    <input type="hidden" name="kategori" value="<?= htmlspecialchars($selected_kategori) ?>">
                <?php endif; ?>
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Kategori Filter -->
<div class="category-filter mb-4">
    <a href="<?= $base_url ?>/edukasi.php<?= !empty($selected_tag) ? '?tag=' . urlencode($selected_tag) : '' ?>"
        class="btn <?= empty($selected_kategori) ? 'btn-primary' : 'btn-outline-primary' ?>">
        Semua
    </a>
    <?php foreach ($kategori_list as $key => $nama_kategori): ?>
        <a href="<?= $base_url ?>/edukasi.php?kategori=<?= urlencode($key) ?><?= !empty($selected_tag) ? '&tag=' . urlencode($selected_tag) : '' ?>"
            class="btn <?= $selected_kategori === $key ? 'btn-primary' : 'btn-outline-primary' ?>">
            <?= $nama_kategori ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- Tag Filter -->
<?php if (!empty($all_tags)): ?>
    <div class="mb-4">
        <div class="tag-search-container">
            <h6 class="tag-search-heading">Cari berdasarkan tag:</h6>
            <div class="tag-search-input">
                <i class="bi bi-hash"></i>
                <input type="text" class="form-control" id="tagSearchInput" placeholder="Ketik untuk mencari tag..." list="tagSuggestions">
            </div>
            <div class="popular-tags">
                <small class="text-muted">Tag populer:</small>
                <?php
                // Ambil 5 tag populer atau acak jika jumlah tag lebih dari 5
                $popular_tags = count($all_tags) > 5 ? array_slice($all_tags, 0, 5) : $all_tags;
                foreach ($popular_tags as $tag):
                ?>
                    <a href="<?= $base_url ?>/edukasi.php?tag=<?= urlencode($tag) ?>" class="badge bg-secondary me-1 mt-1 tag-badge">
                        #<?= htmlspecialchars($tag ?? '') ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger" role="alert">
        <?= $error_message ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <?php foreach ($artikels as $artikel): ?>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card article-card">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <a href="<?= $base_url ?>/edukasi/<?= htmlspecialchars($artikel['slug'] ?? '') ?>"
                            class="text-decoration-none text-dark">
                            <?= htmlspecialchars($artikel['judul'] ?? '') ?>
                        </a>
                    </h5>

                    <?php if (!empty($artikel['isi_edukasi'])): ?>
                        <p class="article-summary mb-3">
                            <?= htmlspecialchars(substr(strip_tags($artikel['isi_edukasi'] ?? ''), 0, 150)) . '...' ?>
                        </p>
                    <?php endif; ?>

                    <div class="article-meta">
                        <i class="bi bi-calendar3"></i>
                        <?= !empty($artikel['created_at']) ? date('d F Y', strtotime($artikel['created_at'])) : 'Tanggal tidak tersedia' ?>
                    </div>

                    <?php if (!empty($artikel['tag'])): ?>
                        <div class="mt-2 mb-3">
                            <?php
                            $tags = explode(',', $artikel['tag']);
                            foreach ($tags as $tag):
                                $tag = trim($tag);
                                if (!empty($tag)):
                            ?>
                                    <a href="<?= $base_url ?>/edukasi.php?tag=<?= urlencode($tag) ?>" class="badge bg-secondary text-decoration-none me-1">
                                        #<?= htmlspecialchars($tag ?? '') ?>
                                    </a>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </div>
                    <?php endif; ?>

                    <span class="badge bg-primary article-category">
                        <?= htmlspecialchars($kategori_list[$artikel['kategori']] ?? 'Umum') ?>
                    </span>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if (empty($artikels)): ?>
    <div class="alert alert-info text-center py-5">
        <i class="bi bi-info-circle fs-1 d-block mb-3"></i>
        <h4>Tidak ada artikel ditemukan</h4>
        <p class="mb-0">Coba ubah filter pencarian atau kategori untuk menemukan artikel lain.</p>
    </div>
<?php endif; ?>

<!-- Datalist untuk saran tag -->
<datalist id="tagSuggestions">
    <?php foreach ($all_tags as $tag): ?>
        <option value="<?= htmlspecialchars($tag) ?>">
    <?php endforeach; ?>
</datalist>
