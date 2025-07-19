<?php
/**
 * Controller Edukasi - Mengelola tampilan daftar artikel edukasi
 * 
 * File ini mengikuti arsitektur MVC dengan memisahkan logika dan tampilan
 */

// Memulai session jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load dependencies
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';

// Set page title
$page_title = 'Edukasi Kesehatan';

// Ambil parameter kategori dari URL jika ada
$selected_kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';

// Ambil parameter pencarian dari URL jika ada
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Ambil parameter tag dari URL jika ada
$selected_tag = isset($_GET['tag']) ? $_GET['tag'] : '';

// Daftar kategori
$kategori_list = [
    'onkogin' => 'Onkogin',
    'endokrin' => 'Endokrin',
    'infertilitas' => 'Infertilitas',
    'fetomaternal' => 'Fetomaternal',
    'urogin' => 'Urogin',
    'tips_kesehatan' => 'Tips Kesehatan'
];

try {
    // Buat query dasar
    $query = "SELECT * FROM edukasi WHERE status_aktif = 1 AND ditampilkan_beranda = 1";
    $params = [];

    // Tambahkan filter kategori jika ada
    if (!empty($selected_kategori)) {
        $query .= " AND kategori = :kategori";
        $params[':kategori'] = $selected_kategori;
    }

    // Tambahkan filter tag jika ada
    if (!empty($selected_tag)) {
        $query .= " AND tag LIKE :tag";
        $params[':tag'] = "%$selected_tag%";
    }

    // Tambahkan pencarian jika ada
    if (!empty($search)) {
        $query .= " AND (judul LIKE :search OR isi_edukasi LIKE :search)";
        $params[':search'] = "%$search%";
    }

    // Tambahkan pengurutan
    $query .= " ORDER BY created_at DESC";

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $artikels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ambil semua tag unik dari database
    $tag_query = "SELECT DISTINCT tag FROM edukasi WHERE status_aktif = 1 AND tag IS NOT NULL AND tag != ''";
    $tag_stmt = $conn->prepare($tag_query);
    $tag_stmt->execute();
    $all_tags = [];

    while ($tag_row = $tag_stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($tag_row['tag'])) {
            $tags = explode(',', $tag_row['tag']);
            foreach ($tags as $tag) {
                $tag = trim($tag);
                if (!empty($tag) && !in_array($tag, $all_tags)) {
                    $all_tags[] = $tag;
                }
            }
        }
    }

    // Urutkan tag
    sort($all_tags);
} catch (PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
    $artikels = [];
}

// Persiapkan data untuk view
$view_data = [
    'page_title' => $page_title,
    'selected_kategori' => $selected_kategori,
    'search' => $search,
    'selected_tag' => $selected_tag,
    'kategori_list' => $kategori_list,
    'artikels' => $artikels,
    'all_tags' => $all_tags,
    'error_message' => isset($error_message) ? $error_message : ''
];

// Additional CSS untuk halaman edukasi
$additional_css = "
    /* Base Styles */
    body {
        overflow-x: hidden; /* Prevent horizontal scrollbar */
    }
    
    /* Main Content Layout */
    .main-content {
        margin-left: 240px;
        padding: 20px;
        transition: margin-left 0.3s ease, width 0.3s ease;
        width: calc(100% - 240px); /* Width minus sidebar width */
        box-sizing: border-box;
    }
    
    /* Adjust main content when sidebar is minimized */
    .sidebar.minimized ~ .main-content {
        margin-left: 60px;
        width: calc(100% - 60px); /* Width minus minimized sidebar width */
    }
    
    /* Mobile adjustments */
    @media (max-width: 991.98px) {
        .main-content {
            margin-left: 0;
            width: 100%;
        }
    }
    
    .article-card {
        height: 100%;
        transition: transform 0.2s;
        border: none;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        position: relative;
    }

    .article-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .article-category {
        position: absolute;
        bottom: 10px;
        right: 10px;
    }

    .article-summary {
        color: #6c757d;
        font-size: 0.9rem;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        margin-bottom: 2rem;
    }

    .article-meta {
        font-size: 0.85rem;
        color: #6c757d;
        margin-bottom: 2.5rem;
    }

    .category-filter {
        display: flex;
        flex-wrap: nowrap;
        overflow-x: auto;
        gap: 0.5rem;
        padding: 0.5rem 0;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .category-filter::-webkit-scrollbar {
        display: none;
    }

    .category-filter .btn {
        white-space: nowrap;
        flex-shrink: 0;
    }

    .search-form {
        position: relative;
        z-index: 1;
    }

    .search-form .input-group {
        width: 100%;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 2rem;
    }

    .page-title-section {
        flex: 1;
    }

    .search-section {
        width: 300px;
        margin-left: 1rem;
    }

    .tag-search-container {
        margin-bottom: 1.5rem;
        padding: 1rem;
        background-color: #f8f9fa;
        border-radius: 0.5rem;
    }

    .tag-search-heading {
        font-size: 1rem;
        margin-bottom: 0.75rem;
        color: #495057;
    }

    .tag-search-input {
        position: relative;
        margin-bottom: 0.5rem;
    }

    .tag-search-input input {
        padding-left: 2.5rem;
    }

    .tag-search-input i {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
    }

    .popular-tags {
        margin-top: 0.75rem;
    }

    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
        }

        .search-section {
            width: 100%;
            margin-left: 0;
            margin-top: 1rem;
        }

        .search-form {
            max-width: 100% !important;
        }
    }

    .tag-filter {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
    }

    .tag-badge {
        cursor: pointer;
        transition: all 0.2s;
    }

    .tag-badge:hover {
        opacity: 0.8;
    }

    .tag-badge.active {
        background-color: #0d6efd;
        color: white;
    }

    .tag-heading {
        font-size: 1rem;
        margin-bottom: 0.5rem;
        color: #495057;
    }
";

// Additional JavaScript untuk halaman edukasi
$additional_js = "
    // Script untuk pencarian tag
    document.addEventListener('DOMContentLoaded', function() {
        const tagSearchInput = document.getElementById('tagSearchInput');
        if (tagSearchInput) {
            tagSearchInput.addEventListener('change', function() {
                if (this.value) {
                    window.location.href = '" . $base_url . "/edukasi.php?tag=' + encodeURIComponent(this.value);
                }
            });
        }
    });
";

// Mulai output buffering untuk menangkap konten view
ob_start();

// Include file view
include __DIR__ . '/../views/index.php';

// Ambil konten yang sudah di-render
$content = ob_get_clean();

// Pastikan variabel yang dibutuhkan oleh sidebar.php tersedia
$current_page = basename($_SERVER['PHP_SELF']);
$current_path = $_SERVER['PHP_SELF'];

// Include template dengan path absolut dari root project
include_once __DIR__ . '/../../../template/layout.php';
?>
