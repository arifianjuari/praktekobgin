<?php
require_once __DIR__ . '/../config/config.php';
$current_page = basename($_SERVER['PHP_SELF']);
$current_path = $_SERVER['PHP_SELF'];
$is_logged_in = isset($_SESSION['user_id']); // Akan digunakan nanti
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin'; // Akan digunakan nanti

// Fungsi untuk membersihkan base_url
function clean_url($url)
{
    return str_replace(' ', '%20', $url);
}

// Fungsi untuk memeriksa apakah halaman saat ini adalah halaman yang ditentukan
function is_current_page($page_path)
{
    return strpos($_SERVER['PHP_SELF'], $page_path) !== false;
}

// Fungsi untuk memeriksa apakah halaman saat ini adalah halaman dengan parameter GET tertentu
function is_current_module($module, $action = null)
{
    if (!isset($_GET['module']) || $_GET['module'] != $module) {
        return false;
    }

    if ($action !== null) {
        return isset($_GET['action']) && $_GET['action'] == $action;
    }

    return true;
}
?>

<!-- Add Bootstrap Icons CDN -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
    /* Variabel warna untuk konsistensi */
    :root {
        --primary-color: rgb(134, 158, 155);
        --primary-light: #ECF2F5;
        --primary-dark: #C4DADE;
        --accent: #E7D9CC;
        --background: #F9F0E7;
        --secondary-bg: #DDE8ED;
        --text-dark: #333;
        --text-muted: #666;
        --bg-light: var(--background);
        --border-light: rgba(0, 0, 0, 0.05);
        --hover-bg: var(--primary-light);

        /* Warna untuk tombol aksi */
        --add-color: #28a745;
        --add-hover: #218838;
        --edit-color: #ffc107;
        --edit-hover: #e0a800;
        --delete-color: #dc3545;
        --delete-hover: #c82333;
        --download-color: #0d6efd;
        --download-hover: #0a58ca;
    }


    body {
        overflow-x: hidden;

    }

    body.sidebar-open {
        overflow: hidden;
    }

    .sidebar {
        width: 240px;
        min-height: 100vh;
        position: fixed;
        left: 0;
        top: 0;

        z-index: 1050;
        background-color: var(--bg-light);
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
    }

    /* Base styles */
    .nav-link {
        display: flex;
        align-items: center;
        padding: 0.35rem 1rem;
        color: var(--text-dark);
        text-decoration: none;
        gap: 0.4rem;
        border-radius: 0;
        white-space: nowrap;

        margin: 0;
        font-weight: 400;
        border-left: 3px solid transparent;
        font-size: 0.875rem;
        line-height: 1.2;
        position: relative;
        /* Added for better positioning */
    }

    .nav-link i {
        font-size: 1rem;
        min-width: 1.5rem;
        text-align: center;

        color: var(--text-muted);
    }

    /* Adjust icon spacing in minimized submenu */
    .sidebar.minimized .submenu.show .nav-link i {
        min-width: 0.7rem;
        margin-right: 0.1rem;
        font-size: 0.75rem;
        text-align: left;
        padding-left: 0;
    }

    .nav-link:hover {
        background-color: var(--hover-bg);
        color: var(--text-dark);
        transform: none;
        border-left: 3px solid var(--primary-color);
    }

    .nav-link:hover i {
        color: var(--primary-color);
    }

    .nav-link.active {
        background-color: var(--primary-light);
        color: var(--primary-color) !important;
        box-shadow: none;
        border-left: 3px solid var(--primary-color);
        font-weight: 500;
    }

    .nav-link.active i {
        color: var(--primary-color);
    }

    /* Submenu styles */
    .submenu {
        padding-left: 1.5rem;
        list-style: none;
        margin: 0;
        overflow: hidden;
        max-height: 0;

        background-color: transparent;
    }

    /* Ensure proper alignment in minimized mode */
    .sidebar.minimized .submenu.show {
        padding-left: 0;
        width: auto;
    }

    .submenu.show {
        max-height: 1000px;
        /* Large enough to accommodate all items */
        padding-top: 0.25rem;
        padding-bottom: 0.25rem;
    }

    /* Tighter spacing for submenu in minimized mode */
    .sidebar.minimized .submenu.show {
        padding-top: 0.15rem;
        padding-bottom: 0.15rem;
    }

    .submenu .nav-link {
        padding: 0.25rem 1rem;
        font-size: 0.8125rem;
        margin: 0;
        color: var(--text-muted);
        line-height: 1.2;
        opacity: 0.9;

    }

    /* Smaller spacing for submenu items in minimized mode */
    .sidebar.minimized .submenu.show .nav-link {
        padding: 0.15rem 0.2rem;
        font-size: 0.775rem;
        line-height: 1.1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        width: 100%;
        margin-left: 0;
        padding-right: 0.5rem;
    }

    .submenu .nav-link:hover {
        opacity: 1;
        transform: translateX(2px);
    }

    .submenu .nav-link.active {
        background-color: var(--primary-light);
        color: var(--primary-color) !important;
        border-left: 3px solid var(--primary-color);
        font-weight: 500;
        opacity: 1;
    }

    .submenu .nav-link.active i {
        color: var(--primary-color);
    }

    /* Highlight parent menu when submenu is active */
    .has-submenu.open>.nav-link {
        color: var(--primary-color);
        font-weight: 500;
        background-color: rgba(240, 128, 128, 0.08);
    }

    .has-submenu.open>.nav-link i {
        color: var(--primary-color);
    }

    .has-submenu.open .submenu-arrow {
        transform: rotate(90deg);
        color: var(--primary-color);
    }

    .submenu-toggle {
        cursor: pointer;
        position: relative;
    }

    .submenu-arrow {

        font-size: 0.75rem;
        position: absolute;
        right: 0.75rem;
        color: var(--text-muted);
    }

    /* Minimized state */
    .sidebar.minimized {
        width: 60px;
    }

    .sidebar.minimized .menu-text,
    .sidebar.minimized .submenu-arrow,
    .sidebar.minimized hr {
        display: none;
    }

    /* Improved minimized state */
    .sidebar.minimized .nav-link {
        justify-content: center;
        padding: 0.5rem;
    }

    /* Ensure submenu icons are properly aligned in minimized mode */
    .sidebar.minimized .submenu.show .nav-link {
        justify-content: flex-start;
        margin-left: 0;
    }

    .sidebar.minimized .nav-link i {
        margin-right: 0;
        font-size: 1.25rem;
        min-width: auto;
    }

    /* Hide all submenus in minimized state by default */
    .sidebar.minimized .has-submenu .submenu {
        display: none;
    }

    /* Search box styling */
    .search-container {
        padding: 0.75rem 1rem;
        margin-bottom: 0.5rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .search-input {
        position: relative;
        width: 100%;
    }

    .search-input input {
        width: 100%;
        padding: 0.75rem 1rem 0.75rem 2.5rem;
        border-radius: 0.5rem;
        border: 1px solid rgba(0, 0, 0, 0.1);
        background-color: #fff;
        font-size: 0.875rem;

    }

    .search-input input:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(232, 62, 140, 0.1);
    }

    .search-input i {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        font-size: 1rem;
    }

    /* Styling untuk search box saat minimized */
    .sidebar.minimized .search-container {
        padding: 0.75rem 0.5rem;
    }

    .sidebar.minimized .search-input input {
        width: 40px;
        height: 40px;
        padding: 0.5rem;
        border-radius: 50%;
        text-indent: -9999px;
        cursor: pointer;
        background-color: #f0f0f5;
        border: 1px solid rgba(0, 0, 0, 0.05);

    }

    .sidebar.minimized .search-input i {
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 1rem;
        color: var(--text-muted);
    }

    /* Hover effect untuk search box */
    .sidebar.minimized .search-input:hover input {
        background-color: var(--primary-light);
        border-color: var(--primary-color);
    }

    .sidebar.minimized .search-input:hover i {
        color: var(--primary-color);
    }

    /* Styling untuk search box saat active/focus */
    .sidebar.minimized .search-input input:focus {
        width: 180px;
        border-radius: 20px;
        padding: 0.5rem 1rem 0.5rem 2.5rem;
        text-indent: 0;
        position: absolute;
        left: 60px;
        background-color: white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        z-index: 1070;
    }

    .sidebar.minimized .search-input input:focus+i {
        left: 70px;
        transform: translateY(-50%);
        color: var(--primary-color);
    }

    /* Submenu styling */
    .sidebar.minimized .has-submenu:hover .submenu {
        display: block;
        position: absolute;
        left: 60px;
        top: 0;
        width: 200px;
        padding: 0.5rem;
        background: #fff;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        border-radius: 0 0.5rem 0.5rem 0;
        z-index: 1060;
        margin-left: 0;
        border-left: 1px solid rgba(0, 0, 0, 0.05);
    }

    .sidebar.minimized .has-submenu {
        position: relative;
    }

    .sidebar.minimized .has-submenu:hover .submenu .nav-link {
        padding: 0.5rem 1rem;
        margin: 0.25rem 0;
        border-radius: 0.25rem;
    }

    .sidebar.minimized .has-submenu:hover .submenu .menu-text {
        display: inline;
    }

    /* Tambahkan panah kecil di sebelah kiri submenu */
    .sidebar.minimized .has-submenu:hover::after {
        content: '';
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        border-top: 6px solid transparent;
        border-bottom: 6px solid transparent;
        border-right: 6px solid #fff;
        z-index: 1061;
    }

    /* Styling untuk submenu item saat hover */
    .sidebar.minimized .has-submenu:hover .submenu .nav-link:hover {
        background-color: var(--primary-light);
        color: var(--primary-color);
        border-left: 3px solid var(--primary-color);
    }

    /* Styling untuk submenu item yang aktif */
    .sidebar.minimized .has-submenu:hover .submenu .nav-link.active {
        background-color: var(--primary-light);
        color: var(--primary-color) !important;
        border-left: 3px solid var(--primary-color);
    }

    /* Main content adjustment */
    .main-content {
        margin-left: 240px;

        padding: 1rem;
    }

    .sidebar.minimized+.main-content {
        margin-left: 60px;
    }

    /* Header styling */
    .sidebar .d-flex.justify-content-between {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    /* Category headers */
    .category-header {
        text-transform: uppercase;
        font-size: 0.7rem;
        font-weight: 600;
        color: var(--text-muted);
        padding: 0.5rem 1rem 0.25rem;
        letter-spacing: 0.5px;
        line-height: 1.2;
    }

    /* Mobile Responsive */
    @media (max-width: 991.98px) {
        body {
            padding-left: 0 !important;
        }

        .sidebar {
            width: 240px;
            height: 100vh;
            min-height: 100vh;
            position: fixed;
            margin-bottom: 0;
            transform: translateX(0);

            overflow-y: auto;
            max-height: 100vh;
            top: 0;
            left: 0;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .sidebar.mobile-collapsed {
            transform: translateX(-100%);
        }

        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1040;
            display: none;

            opacity: 0;
            backdrop-filter: blur(4px);
        }

        .sidebar-overlay.show {
            display: block;
            opacity: 1;
        }

        .main-content {
            margin-left: 0 !important;
            width: 100%;

        }

        /* Mobile toggle button that stays fixed */
        .mobile-toggle-container {
            position: fixed;
            bottom: 1rem;
            left: 1rem;
            z-index: 1030;
            display: none;

        }

        .mobile-toggle-container.show {
            display: block;
        }

        .mobile-toggle-container .btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            background-color: var(--primary-color);
            border: none;
            color: white;
        }

        .mobile-toggle-container .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }

        .nav-link {
            padding: 0.5rem 1rem;
            margin: 0;
        }

        .submenu {
            padding-left: 1.5rem;
            background: transparent;
            border-radius: 0;
            margin: 0;
        }

        .submenu .nav-link {
            padding: 0.4rem 1rem;
            margin: 0;
        }

        /* Hide minimize button on mobile */
        #toggleSidebar {
            display: none;
        }

        /* Adjust dropdown positioning */
        .dropdown-menu {
            position: static !important;
            float: none;
            width: auto;
            margin-top: 0.5rem;
            background-color: transparent;
            border: none;
            box-shadow: none;
        }

        .dropdown-item {
            padding: 0.5rem 1rem;
            color: var(--text-muted);

        }

        .dropdown-item:hover {
            background-color: var(--hover-bg);
            color: var(--primary-color);
        }
    }

    /* Small mobile devices */
    @media (max-width: 575.98px) {
        .nav-link {
            padding: 0.4rem 0.75rem;
        }

        .submenu {
            padding-left: 1.25rem;
        }
    }

    /* Fix submenu hover conflicts */
    .submenu.show {
        display: block;
    }

    .sidebar.minimized .submenu {
        display: none;
        background: var(--bg-light);
    }

    .sidebar.minimized .has-submenu:hover>.submenu {
        display: block;
    }

    /* Additional alignment fixes */
    .nav-item {
        margin: 0;
    }

    .dropdown-toggle::after {
        margin-left: auto;
    }

    .dropdown-menu {
        min-width: 200px;
    }

    /* Custom scrollbar */
    .sidebar::-webkit-scrollbar {
        width: 4px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: transparent;
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: var(--text-muted);
        border-radius: 2px;
    }

    .sidebar::-webkit-scrollbar-thumb:hover {
        background: var(--text-dark);
    }

    /* User profile section */
    .user-section {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        background-color: var(--bg-light);
        border-top: 1px solid var(--border-light);
        padding: 0.75rem 0;
        z-index: 1051;
    }

    .sidebar.minimized .user-section {
        padding: 0.5rem 0;
    }

    .sidebar.minimized .user-section .menu-text,
    .sidebar.minimized .user-section .dropdown-toggle::after {
        display: none;
    }

    .sidebar.minimized .user-section .dropdown-menu {
        position: absolute !important;
        left: 60px !important;
        bottom: 60px;
        width: 200px;
        background-color: white;
        border: 1px solid rgba(0, 0, 0, 0.1);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        border-radius: 0.5rem;
        padding: 0.5rem 0;
    }

    .user-section .dropdown {
        padding: 0;
    }

    .user-section .dropdown-toggle {
        padding: 0.5rem 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--text-dark);
        text-decoration: none;

    }

    .user-section .dropdown-toggle:hover {
        background-color: var(--hover-bg);
    }

    .user-section .dropdown-toggle i {
        font-size: 1.25rem;
        color: var(--text-muted);
    }

    .user-section .dropdown-toggle:hover i {
        color: var(--primary-color);
    }

    .user-section .dropdown-menu {
        margin-bottom: 0.5rem;
    }

    .user-section .dropdown-item {
        padding: 0.5rem 1rem;
        color: var(--text-muted);

    }

    .user-section .dropdown-item:hover {
        background-color: var(--hover-bg);
        color: var(--primary-color);
    }

    /* Adjust main content to not overlap with user section */
    .nav-pills {
        margin-bottom: 60px;
    }

    /* Mobile adjustments for user section */
    @media (max-width: 991.98px) {
        .user-section {
            position: relative;
            margin-top: auto;
        }

        .nav-pills {
            margin-bottom: 0;
        }
    }

    /* Change button color in login section */
    .btn-primary {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .btn-primary:hover {
        background-color: var(--primary-dark);
        border-color: var(--primary-dark);
    }

    /* Specific style for active menu with blue background */
    .nav-link.active-blue {
        background-color: var(--primary-color);
        color: white !important;
        border-left: 3px solid var(--primary-color);
    }

    .nav-link.active-blue i {
        color: white;
    }

    /* Override any bootstrap active classes */
    .nav-pills .nav-link.active,
    .nav-pills .show>.nav-link {
        background-color: var(--primary-light);
        color: var(--primary-color) !important;
        border-left: 3px solid var(--primary-color);
    }

    /* Override for any remaining blue colors */
    .text-primary,
    .text-info,
    .text-primary i,
    .text-info i {
        color: var(--primary-color) !important;
    }

    .bg-primary,
    .bg-info {
        background-color: var(--primary-color) !important;
    }

    .border-primary,
    .border-info {
        border-color: var(--primary-color) !important;
    }

    /* Ensure all buttons use the blue-green color */
    .btn-primary,
    .btn-info {
        background-color: #2a9d8f !important;
        border-color: #2a9d8f !important;
    }

    .btn-outline-primary,
    .btn-outline-info {
        color: var(--primary-color) !important;
        border-color: var(--primary-color) !important;
    }

    .btn-outline-primary:hover,
    .btn-outline-info:hover {
        background-color: var(--primary-color) !important;
        color: white !important;
    }

    /* Styling untuk tombol aksi */
    .btn-add {
        background-color: var(--add-color);
        border-color: var(--add-color);
        color: white;
    }

    .btn-add:hover {
        background-color: var(--add-hover);
        border-color: var(--add-hover);
        color: white;
    }

    .btn-edit {
        background-color: var(--edit-color);
        border-color: var(--edit-color);
        color: #000;
    }

    .btn-edit:hover {
        background-color: var(--edit-hover);
        border-color: var(--edit-hover);
        color: #000;
    }

    .btn-delete {
        background-color: var(--delete-color);
        border-color: var(--delete-color);
        color: white;
    }

    .btn-delete:hover {
        background-color: var(--delete-hover);
        border-color: var(--delete-hover);
        color: white;
    }

    .btn-download {
        background-color: var(--download-color);
        border-color: var(--download-color);
        color: white;
    }

    .btn-download:hover {
        background-color: var(--download-hover);
        border-color: var(--download-hover);
        color: white;
    }

    /* Outline versions */
    .btn-outline-add {
        color: var(--add-color);
        border-color: var(--add-color);
        background-color: transparent;
    }

    .btn-outline-add:hover {
        color: white;
        background-color: var(--add-color);
        border-color: var(--add-color);
    }

    .btn-outline-edit {
        color: var(--edit-color);
        border-color: var(--edit-color);
        background-color: transparent;
    }

    .btn-outline-edit:hover {
        color: #000;
        background-color: var(--edit-color);
        border-color: var(--edit-color);
    }

    .btn-outline-delete {
        color: var(--delete-color);
        border-color: var(--delete-color);
        background-color: transparent;
    }

    .btn-outline-delete:hover {
        color: white;
        background-color: var(--delete-color);
        border-color: var(--delete-color);
    }

    .btn-outline-download {
        color: var(--download-color);
        border-color: var(--download-color);
        background-color: transparent;
    }

    .btn-outline-download:hover {
        color: white;
        background-color: var(--download-color);
        border-color: var(--download-color);
    }

    /* Icon colors */
    .text-add {
        color: var(--add-color) !important;
    }

    .text-edit {
        color: var(--edit-color) !important;
    }

    .text-delete {
        color: var(--delete-color) !important;
    }

    .text-download {
        color: var(--download-color) !important;
    }

    /* Small size buttons */
    .btn-sm.btn-add,
    .btn-sm.btn-edit,
    .btn-sm.btn-delete,
    .btn-sm.btn-download {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        border-radius: 0.2rem;
    }

    /* Button groups */
    .btn-group .btn-add:not(:first-child),
    .btn-group .btn-edit:not(:first-child),
    .btn-group .btn-delete:not(:first-child),
    .btn-group .btn-download:not(:first-child) {
        margin-left: -1px;
    }

    /* Disabled state */
    .btn-add:disabled,
    .btn-edit:disabled,
    .btn-delete:disabled,
    .btn-download:disabled {
        opacity: 0.65;
        pointer-events: none;
    }

    /* Animation for submenu items */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-5px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeOut {
        from {
            opacity: 1;
            transform: translateY(0);
        }

        to {
            opacity: 0;
            transform: translateY(-5px);
        }
    }

    /* Improved submenu toggle appearance */
    .submenu-toggle .submenu-arrow {}

    .has-submenu.open .submenu-toggle .submenu-arrow {
        transform: rotate(90deg);
    }

    /* Hover effect for menu items */
    .nav-link:hover {
        transform: translateX(3px);
    }

    /* Improved transition for sidebar */
    .sidebar {}

    /* Fix for minimized sidebar icons */
    .sidebar.minimized .nav-item {
        display: flex;
        justify-content: center;
    }

    /* Clean up minimized sidebar appearance */
    .sidebar.minimized .nav-link.active {
        border-left: none;
        border-right: 3px solid var(--primary-color);
        background-color: rgba(240, 128, 128, 0.1);
    }

    .sidebar.minimized .nav-link:hover {
        border-left: none;
        transform: none;
        background-color: rgba(0, 0, 0, 0.05);
    }

    .sidebar-profile .dropdown-menu {
        width: 200px;
    }

    .sidebar-logo-mini {
        display: none;
    }

    .sidebar.minimized .sidebar-logo-full {
        display: none !important;
    }

    .sidebar.minimized .sidebar-logo-mini {
        display: inline !important;
    }
</style>

<div id="sidebar" class="sidebar">
    <div class="d-flex justify-content-between align-items-center py-3 px-3">
        <div class="d-flex align-items-center">
            <a href="<?php echo clean_url($base_url); ?>" class="d-flex align-items-center text-decoration-none me-2">
                <img src="/assets/pwa/logopraktekobgin.png" alt="Logo Praktek Obgin" class="sidebar-logo sidebar-logo-full me-2" style="height: 38px; width: auto; object-fit: contain;" />
                <img src="/assets/pwa/icons/praktekobgin_icon72x72.png" alt="Logo Mini" class="sidebar-logo sidebar-logo-mini me-2" style="height: 38px; width: auto; object-fit: contain; display: none;" />
            </a>
            <?php if (!$is_logged_in): ?>
                <a href="<?php echo clean_url($base_url); ?>/router.php?module=login" class="btn btn-sm btn-outline-primary ms-1" title="Login" style="font-size: 0.7rem; padding: 0.15rem 0.4rem;">
                    <i class="bi bi-box-arrow-in-right"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php if ($is_admin): ?>
            <button id="toggleSidebar" class="btn btn-sm btn-light border d-none d-lg-block">
                <i class="bi bi-chevron-left"></i>
            </button>
            <button id="toggleMobileSidebar" class="btn btn-sm btn-light border d-lg-none">
                <i class="bi bi-chevron-left"></i>
            </button>
        <?php endif; ?>
    </div>

    <div class="search-container">
        <div class="search-input">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Search..." class="form-control">
        </div>
    </div>

    <ul class="nav nav-pills flex-column">
        <?php if ($is_admin): ?>
            <!-- Menu untuk Admin -->
            <li class="nav-item">
                <a href="<?php echo clean_url($base_url); ?>/dashboard.php" class="nav-link <?php echo is_current_page('/dashboard.php') ? 'active' : ''; ?>" data-title="Dashboard">
                    <i class="bi bi-grid"></i>
                    <span class="menu-text">Dashboard</span>
                </a>
            </li>

            <li class="nav-item has-submenu">
                <a href="#" class="nav-link submenu-toggle" data-title="RSHB">
                    <i class="bi bi-hospital"></i>
                    <span class="menu-text">RSHB</span>
                    <i class="bi bi-chevron-right ms-auto submenu-arrow"></i>
                </a>
                <ul class="submenu collapse">
                    <li class="nav-item">
                        <a href="<?php echo clean_url($base_url); ?>/index.php?module=rshb&action=dataPasien" class="nav-link <?php echo is_current_module('rshb', 'dataPasien') ? 'active' : ''; ?>" data-title="Data Pasien">
                            <i class="bi bi-people"></i>
                            <span class="menu-text">Data Pasien</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $base_url; ?>/daftar_ranap.php" class="nav-link <?php echo is_current_page('/daftar_ranap.php') ? 'active' : ''; ?>" data-title="Daftar Pasien Ranap">
                            <i class="bi bi-list-ul"></i>
                            <span class="menu-text">Daftar Pasien Ranap</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $base_url; ?>/daftar_rajal_rs.php" class="nav-link <?php echo is_current_page('/daftar_rajal_rs.php') ? 'active' : ''; ?>" data-title="Daftar Pasien Rajal">
                            <i class="bi bi-list-check"></i>
                            <span class="menu-text">Daftar Pasien Rajal</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $base_url; ?>/modules/pendaftaran/views/form_pendaftaran_pasien_rshb.php" class="nav-link <?php echo is_current_page('/modules/pendaftaran/views/form_pendaftaran_pasien_rshb.php') ? 'active' : ''; ?>" data-title="Pendaftaran Pasien RSHB">
                            <i class="bi bi-clipboard-plus"></i>
                            <span class="menu-text">Pendaftaran Pasien RSHB</span>
                        </a>
                    </li>
                </ul>
            </li>


            <!-- Menu Rekam Medis -->
            <li class="nav-item has-submenu">
                <a href="#" class="nav-link submenu-toggle" data-title="Rekam Medis">
                    <i class="bi bi-journal-medical"></i>
                    <span class="menu-text">Rekam Medis</span>
                    <i class="bi bi-chevron-right ms-auto submenu-arrow"></i>
                </a>
                <ul class="submenu collapse">
                    <li class="nav-item">
                        <a href="<?php echo clean_url($base_url); ?>/index.php?module=rekam_medis&action=manajemen_antrian"
                            class="nav-link <?php echo is_current_module('rekam_medis', 'manajemen_antrian') ? 'active' : ''; ?>" data-title="Pasien Rawat Jalan">
                            <i class="bi bi-people"></i>
                            <span class="menu-text">Pasien Rawat Jalan</span>
                        </a>
                    </li>
                                        <li class="nav-item">
                        <a href="<?php echo clean_url($base_url); ?>/index.php?module=rekam_medis&action=daftar_atensi"
                            class="nav-link <?php echo is_current_module('rekam_medis', 'daftar_atensi') ? 'active' : ''; ?>" data-title="Daftar Atensi">
                            <i class="bi bi-exclamation-circle"></i>
                            <span class="menu-text">Daftar Atensi</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo clean_url($base_url); ?>/index.php?module=rekam_medis&action=template_ceklist"
                            class="nav-link <?php echo is_current_module('rekam_medis', 'template_ceklist') ? 'active' : ''; ?>" data-title="Template Ceklist">
                            <i class="bi bi-check2-square"></i>
                            <span class="menu-text">Template Ceklist</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo clean_url($base_url); ?>/index.php?module=rekam_medis&action=template_anamnesis"
                            class="nav-link <?php echo is_current_module('rekam_medis', 'template_anamnesis') ? 'active' : ''; ?>" data-title="Template Anamnesis">
                            <i class="bi bi-clipboard-pulse"></i>
                            <span class="menu-text">Template Anamnesis</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo clean_url($base_url); ?>/index.php?module=rekam_medis&action=template_tatalaksana"
                            class="nav-link <?php echo is_current_module('rekam_medis', 'template_tatalaksana') ? 'active' : ''; ?>" data-title="Template Tatalaksana">
                            <i class="bi bi-file-text"></i>
                            <span class="menu-text">Template Tatalaksana</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo clean_url($base_url); ?>/index.php?module=rekam_medis&action=template_usg"
                            class="nav-link <?php echo is_current_module('rekam_medis', 'template_usg') ? 'active' : ''; ?>" data-title="Template USG">
                            <i class="bi bi-image"></i>
                            <span class="menu-text">Template USG</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $base_url; ?>/modules/admin/controllers/formularium.php" class="nav-link <?php echo is_current_page('/modules/admin/controllers/formularium.php') ? 'active' : ''; ?>" data-title="Formularium">
                            <i class="bi bi-capsule"></i>
                            <span class="menu-text">Formularium</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $base_url; ?>/modules/admin/controllers/manajemen_edukasi.php" class="nav-link <?php echo is_current_page('/modules/admin/controllers/manajemen_edukasi.php') ? 'active' : ''; ?>" data-title="Manajemen Edukasi">
                            <i class="bi bi-journal-text"></i>
                            <span class="menu-text">Manajemen Edukasi</span>
                        </a>
                    </li>

                </ul>
            </li>

            <li class="nav-item has-submenu">
                <a href="#" class="nav-link submenu-toggle" data-title="Admin Praktek">
                    <i class="bi bi-gear"></i>
                    <span class="menu-text">Admin Praktek</span>
                    <i class="bi bi-chevron-right ms-auto submenu-arrow"></i>
                </a>
                <ul class="submenu collapse">
                    <li class="nav-item">
                        <a href="<?php echo $base_url; ?>/modules/admin/controllers/data_dokter.php" class="nav-link <?php echo is_current_page('/modules/admin/controllers/data_dokter.php') ? 'active' : ''; ?>" data-title="Data Dokter">
                            <i class="bi bi-person-vcard"></i>
                            <span class="menu-text">Data Dokter</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $base_url; ?>/modules/admin/controllers/tempat_praktek.php" class="nav-link <?php echo is_current_page('/modules/admin/controllers/tempat_praktek.php') ? 'active' : ''; ?>" data-title="Tempat Praktek">
                            <i class="bi bi-building"></i>
                            <span class="menu-text">Tempat Praktek</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $base_url; ?>/modules/admin/controllers/jadwal_rutin.php" class="nav-link <?php echo is_current_page('/modules/admin/controllers/jadwal_rutin.php') ? 'active' : ''; ?>" data-title="Jadwal Rutin">
                            <i class="bi bi-calendar-week"></i>
                            <span class="menu-text">Jadwal Rutin</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $base_url; ?>/modules/admin/controllers/manajemen_pengumuman.php" class="nav-link <?php echo is_current_page('/modules/admin/controllers/manajemen_pengumuman.php') ? 'active' : ''; ?>" data-title="Pesan / Pengumuman">
                            <i class="bi bi-megaphone"></i>
                            <span class="menu-text">Pesan / Pengumuman</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $base_url; ?>/modules/admin/controllers/manajemen_user.php" class="nav-link <?php echo is_current_page('/modules/admin/controllers/manajemen_user.php') ? 'active' : ''; ?>" data-title="Manajemen User">
                            <i class="bi bi-person-gear"></i>
                            <span class="menu-text">Manajemen User</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $base_url; ?>/modules/admin/controllers/manajemen_antrian.php" class="nav-link <?php echo is_current_page('/modules/admin/controllers/manajemen_antrian.php') ? 'active' : ''; ?>" data-title="Manajemen Antrian">
                            <i class="bi bi-list-check"></i>
                            <span>Manajemen Antrian</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $base_url; ?>/modules/admin/controllers/data_rujukan.php" class="nav-link <?php echo is_current_page('/modules/admin/controllers/data_rujukan.php') ? 'active' : ''; ?>" data-title="Data Rujukan">
                            <i class="bi bi-file-earmark-medical"></i>
                            <span class="menu-text">Data Rujukan</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $base_url; ?>/modules/admin/controllers/manajemen_layanan.php" class="nav-link <?php echo is_current_page('/modules/admin/controllers/manajemen_layanan.php') ? 'active' : ''; ?>" data-title="Manajemen Layanan">
                            <i class="bi bi-gear-wide-connected"></i>
                            <span class="menu-text">Manajemen Layanan</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $base_url; ?>/modules/admin/controllers/dashboard_antrian.php" class="nav-link <?php echo is_current_page('/modules/admin/controllers/dashboard_antrian.php') ? 'active' : ''; ?>" data-title="Dashboard Antrian">
                            <i class="bi bi-display"></i>
                            <span class="menu-text">Dashboard Antrian</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $base_url; ?>/modules/admin/controllers/voucher.php" class="nav-link <?php echo is_current_page('/modules/admin/controllers/voucher.php') ? 'active' : ''; ?>" data-title="Manajemen Voucher">
                            <i class="bi bi-ticket-perforated"></i>
                            <span class="menu-text">Manajemen Voucher</span>
                        </a>
                    </li>
                    <li class="nav-item">
    <a href="<?php echo $base_url; ?>/modules/admin/controllers/statistik_laporan.php" class="nav-link <?php echo is_current_page('/modules/admin/controllers/statistik_laporan.php') ? 'active' : ''; ?>" data-title="Statistik dan Laporan">
        <i class="bi bi-graph-up"></i>
        <span class="menu-text">Statistik dan Laporan</span>
    </a>
</li>
<li class="nav-item">
    <a href="<?php echo clean_url($base_url); ?>/index.php?module=rekam_medis&action=data_pasien"
        class="nav-link <?php echo is_current_module('rekam_medis', 'data_pasien') ? 'active' : ''; ?>" data-title="Data Pasien">
        <i class="bi bi-person-vcard"></i>
        <span class="menu-text">Data Pasien</span>
    </a>
</li>
                </ul>
            </li>
        <?php endif; ?>

        <!-- Menu Pendaftaran - Selalu Tampil -->
        <li class="nav-item">
            <a href="<?php echo clean_url($base_url); ?>/modules/pendaftaran/views/form_pendaftaran_pasien.php" class="nav-link <?php echo is_current_page('/modules/pendaftaran/views/form_pendaftaran_pasien.php') ? 'active' : ''; ?>" data-title="Form Pendaftaran">
                <i class="bi bi-file-earmark-text"></i>
                <span class="menu-text">Form Pendaftaran</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo clean_url($base_url); ?>/modules/pendaftaran/controllers/antrian.php" class="nav-link <?php echo is_current_page('/modules/pendaftaran/controllers/antrian.php') ? 'active' : ''; ?>" data-title="Daftar Antrian">
                <i class="bi bi-list-ol"></i>
                <span class="menu-text">Daftar Antrian</span>
            </a>
        </li>

        <!-- Menu Jadwal - Selalu Tampil -->
        <li class="nav-item">
            <a href="<?php echo rtrim($base_url, '/'); ?>/jadwal.php" class="nav-link <?php echo is_current_page('/jadwal.php') ? 'active' : ''; ?>" data-title="Jadwal">
                <i class="bi bi-calendar-week"></i>
                <span class="menu-text">Jadwal</span>
            </a>
        </li>
        <!-- Menu Pengumuman - Selalu Tampil -->
        <li class="nav-item">
            <a href="<?php echo $base_url; ?>/pengumuman.php" class="nav-link <?php echo is_current_page('/pengumuman.php') ? 'active' : ''; ?>" data-title="Pengumuman">
                <i class="bi bi-megaphone"></i>
                <span class="menu-text">Pengumuman</span>
            </a>
        </li>

        <!-- Menu Layanan - Selalu Tampil -->
        <li class="nav-item">
            <a href="<?php echo clean_url($base_url); ?>/layanan.php" class="nav-link <?php echo is_current_page('/layanan.php') ? 'active' : ''; ?>" data-title="Layanan">
                <i class="bi bi-heart-pulse"></i>
                <span class="menu-text">Layanan</span>
            </a>
        </li>

        <!-- Menu Edukasi - Selalu Tampil -->
        <li class="nav-item">
            <a href="<?php echo clean_url($base_url); ?>/edukasi.php" class="nav-link <?php echo is_current_page('/edukasi.php') ? 'active' : ''; ?>" data-title="Edukasi">
                <i class="bi bi-journal-text"></i>
                <span class="menu-text">Edukasi</span>
            </a>
        </li>
        <?php if ($is_logged_in): ?>
            <li class="nav-item d-flex justify-content-end">
                <div class="me-3">
                    <a href="<?php echo clean_url($base_url); ?>/modules/auth/controllers/logout.php" class="btn btn-icon btn-outline-danger p-1" title="Logout" style="font-size: 1rem; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                </div>
            </li>
        <?php endif; ?>
    </ul>

    <!-- User section at bottom -->
    <div class="user-section">
        <?php if ($is_logged_in): ?>
            <div class="dropdown">
                <a href="#" class="dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false" data-title="User Profile">
                    <i class="bi bi-person-circle"></i>
                    <span class="menu-text"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                </a>
                <ul class="dropdown-menu shadow" aria-labelledby="dropdownUser1">
                    <li><a class="dropdown-item" href="<?= $base_url ?>/profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                    <li><a class="dropdown-item" href="<?= $base_url ?>/settings.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="<?= $base_url ?>/modules/auth/controllers/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign out</a></li>
                </ul>
            </div>
        <?php else: ?>

        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('toggleSidebar');
        const toggleMobileBtn = document.getElementById('toggleMobileSidebar');
        const isMobile = window.innerWidth < 992;

        // Fungsi untuk menyimpan status menu di localStorage
        function saveMenuState() {
            const openMenus = [];
            document.querySelectorAll('.has-submenu.open').forEach(menu => {
                openMenus.push(menu.querySelector('.submenu-toggle').textContent.trim());
            });
            localStorage.setItem('openMenus', JSON.stringify(openMenus));
        }

        // Fungsi untuk memulihkan status menu dari localStorage dengan peningkatan
        function restoreMenuState() {
            try {
                // Pertama, buka submenu yang memiliki item aktif
                const activeMenuItems = document.querySelectorAll('.submenu .nav-link.active');

                // Jika ada item aktif, hanya buka submenu yang memiliki item aktif
                if (activeMenuItems.length > 0) {
                    activeMenuItems.forEach(activeItem => {
                        const parentSubmenu = activeItem.closest('.submenu');
                        if (parentSubmenu) {
                            const parentItem = parentSubmenu.closest('.has-submenu');
                            if (parentItem) {
                                parentItem.classList.add('open');
                                parentSubmenu.classList.add('show');
                            }
                        }
                    });
                } else {
                    // Jika tidak ada item aktif, pulihkan menu yang sebelumnya terbuka dari localStorage
                    const openMenus = JSON.parse(localStorage.getItem('openMenus')) || [];

                    // Jika dalam mode desktop dan tidak diminimalkan, buka menu pertama jika tidak ada yang disimpan
                    if (openMenus.length === 0 && !isMobile && !sidebar.classList.contains('minimized')) {
                        const firstSubmenu = document.querySelector('.has-submenu');
                        if (firstSubmenu) {
                            const submenu = firstSubmenu.querySelector('.submenu');
                            firstSubmenu.classList.add('open');
                            submenu.classList.add('show');
                        }
                    } else if (openMenus.length > 0) {
                        // Jika ada menu yang disimpan, buka menu tersebut
                        document.querySelectorAll('.submenu-toggle').forEach(toggle => {
                            const menuText = toggle.textContent.trim();
                            if (openMenus.includes(menuText)) {
                                const parent = toggle.closest('.has-submenu');
                                const submenu = parent.querySelector('.submenu');
                                parent.classList.add('open');
                                submenu.classList.add('show');
                            }
                        });
                    }
                }
            } catch (e) {
                console.error('Error restoring menu state:', e);
            }
        }

        // Fungsi untuk menambahkan class pada body saat sidebar terbuka
        function updateBodyClass() {
            if (sidebar.classList.contains('mobile-collapsed')) {
                document.body.classList.remove('sidebar-open');
            } else {
                document.body.classList.add('sidebar-open');
            }
        }

        // Create overlay for mobile
        const overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);

        // Create mobile toggle button container
        const mobileToggleContainer = document.createElement('div');
        mobileToggleContainer.className = 'mobile-toggle-container';
        const mobileToggleBtn = document.createElement('button');
        mobileToggleBtn.className = 'btn btn-primary';
        mobileToggleBtn.innerHTML = '<i class="bi bi-grid-fill"></i>';
        mobileToggleContainer.appendChild(mobileToggleBtn);
        document.body.appendChild(mobileToggleContainer);

        // Auto collapse on mobile
        if (isMobile) {
            sidebar.classList.add('mobile-collapsed');
            mobileToggleContainer.classList.add('show');
            updateBodyClass();
        }

        // Restore menu state
        restoreMenuState();

        // Toggle sidebar on desktop
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                if (!isMobile) {
                    sidebar.classList.toggle('minimized');
                    // Simpan status minimized di localStorage
                    localStorage.setItem('sidebarMinimized', sidebar.classList.contains('minimized'));

                    // ADDED: Close all open submenus when minimizing
                    if (sidebar.classList.contains('minimized')) {
                        document.querySelectorAll('.has-submenu.open').forEach(openMenu => {
                            closeSubmenu(openMenu); // Use existing helper function
                        });
                    }
                    // END ADDED CODE

                    // Jika sidebar BARU SAJA menjadi expanded, kita bisa memilih untuk memulihkan state
                    // Tapi sepertinya logika restoreMenuState() sudah menangani ini saat load.
                }
            });
        }

        // Restore minimized state
        if (!isMobile && localStorage.getItem('sidebarMinimized') === 'true') {
            sidebar.classList.add('minimized');
        }

        // Toggle sidebar on mobile
        if (toggleMobileBtn) {
            toggleMobileBtn.addEventListener('click', function() {
                if (isMobile) {
                    sidebar.classList.toggle('mobile-collapsed');
                    overlay.classList.toggle('show');
                    mobileToggleContainer.classList.toggle('show');
                    updateBodyClass();
                }
            });
        }

        // Mobile toggle button in fixed position
        mobileToggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('mobile-collapsed');
            overlay.classList.toggle('show');
            mobileToggleContainer.classList.toggle('show');
            updateBodyClass();
        });

        // Close sidebar when clicking overlay
        overlay.addEventListener('click', function() {
            // Pada mobile, kita perlu menutup sidebar saat overlay diklik
            if (isMobile) {
                sidebar.classList.add('mobile-collapsed');
                updateBodyClass();
            }
            overlay.classList.remove('show');
            mobileToggleContainer.classList.add('show');
        });

        // Jangan collapse sidebar saat menu item diklik di mobile
        if (isMobile) {
            const menuLinks = sidebar.querySelectorAll('a.nav-link:not(.submenu-toggle):not([href="#"])');
            menuLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Pada mobile, kita tidak perlu menutup sidebar saat menu diklik
                    // Ini memungkinkan pengguna untuk melihat menu yang aktif
                    // Cukup tutup overlay jika terbuka
                    if (overlay.classList.contains('show')) {
                        sidebar.classList.add('mobile-collapsed');
                        overlay.classList.remove('show');
                        mobileToggleContainer.classList.add('show');
                        updateBodyClass();
                    }
                });
            });
        }

        // Auto-hide submenu when a submenu item is clicked (global)
        document.querySelectorAll('.submenu .nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                // Jika dalam mode minimized dan item submenu diklik, tutup semua popup submenu
                if (sidebar.classList.contains('minimized') && !isMobile) {
                    closeAllMinimizedSubmenus();
                }
                // Jika tidak dalam mode minimized, dan menu ini bukan bagian dari dropdown user
                // Biarkan submenu terbuka (untuk melihat item aktif), 
                // kecuali jika ini link navigasi biasa yang mengubah halaman
                // Jika link ini mengarahkan ke halaman lain (bukan # atau javascript:void(0))
                // maka tidak perlu tindakan khusus di sini, biarkan browser menavigasi.
                // Jika ini link internal (misalnya #) atau hanya toggle, biarkan submenu terbuka

                // Hentikan propagasi agar tidak memicu penutupan submenu lain secara tidak sengaja
                // e.stopPropagation(); // <-- Mungkin tidak diperlukan lagi dengan logika baru
            });
        });

        // Handle submenu toggles (klik)
        document.querySelectorAll('.submenu-toggle').forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();

                // Abaikan jika dalam mode minimized (hover yang menangani)
                if (sidebar.classList.contains('minimized') && !isMobile) {
                    return;
                }

                const parent = this.closest('.has-submenu');
                const submenu = parent.querySelector('.submenu');
                const isOpen = parent.classList.contains('open');

                // Tutup semua menu lain yang terbuka (kecuali parent dari menu ini jika ada)
                if (!isOpen) {
                    document.querySelectorAll('.has-submenu.open').forEach(openMenu => {
                        // Cek apakah openMenu adalah parent dari parent saat ini (untuk nested submenu jika ada)
                        if (!parent.contains(openMenu) && !openMenu.contains(parent)) {
                            closeSubmenu(openMenu);
                        }
                    });
                }

                // Toggle menu saat ini
                if (isOpen) {
                    closeSubmenu(parent);
                } else {
                    openSubmenu(parent);
                }

                // Simpan status menu hanya jika tidak mobile
                if (!isMobile) {
                    saveMenuState();
                }
            });
        });

        // Fungsi helper untuk membuka submenu
        function openSubmenu(menuItem) {
            const submenu = menuItem.querySelector('.submenu');
            menuItem.classList.add('open');
            submenu.classList.add('show');
            // Apply animation (optional)
            // submenu.style.animation = 'fadeIn 0.3s ease forwards'; 
        }

        // Fungsi helper untuk menutup submenu
        function closeSubmenu(menuItem) {
            const submenu = menuItem.querySelector('.submenu');
            menuItem.classList.remove('open');
            submenu.classList.remove('show');
            // Apply animation (optional)
            // submenu.style.animation = 'fadeOut 0.2s ease forwards';
            // submenu.addEventListener('animationend', () => { submenu.style.animation = ''; }, { once: true });
        }


        // Buka submenu yang memiliki item aktif saat halaman dimuat
        document.querySelectorAll('.submenu .nav-link.active').forEach(activeLink => {
            const parentSubmenu = activeLink.closest('.submenu');
            if (parentSubmenu && !sidebar.classList.contains('minimized')) { // Jangan buka otomatis jika minimized
                const parentItem = parentSubmenu.closest('.has-submenu');
                if (parentItem) {
                    openSubmenu(parentItem);
                }
            }
        });

        // Fungsi untuk menutup semua submenu popup di mode minimized
        function closeAllMinimizedSubmenus() {
            document.querySelectorAll('.has-submenu.open').forEach(item => {
                if (item.querySelector('.submenu').style.position === 'fixed') { // Hanya tutup yang popup (fixed)
                    const submenu = item.querySelector('.submenu');
                    item.classList.remove('open');
                    submenu.classList.remove('show');
                    // Reset style popup
                    submenu.style.position = '';
                    submenu.style.left = '';
                    submenu.style.top = '';
                    submenu.style.width = '';
                    submenu.style.maxHeight = '';
                    submenu.style.overflowY = '';
                    submenu.style.zIndex = '';
                    submenu.style.boxShadow = '';
                    submenu.style.backgroundColor = '';
                    submenu.style.borderRadius = '';
                    submenu.style.paddingTop = '';
                    submenu.style.paddingBottom = '';
                }
            });
        }

        // Handle hover states for minimized mode - Revised Logic
        let hoverTimeout = null;

        document.querySelectorAll('.has-submenu').forEach(item => {
            const submenu = item.querySelector('.submenu');

            item.addEventListener('mouseenter', () => {
                if (!isMobile && sidebar.classList.contains('minimized')) {
                    clearTimeout(hoverTimeout); // Hapus timeout jika masuk lagi

                    // Tutup submenu lain yang mungkin terbuka karena hover sebelumnya
                    closeAllMinimizedSubmenus();

                    // Tampilkan submenu ini sebagai popup
                    item.classList.add('open'); // Tandai parent sebagai open (meskipun tidak expand visual)
                    submenu.classList.add('show'); // Tampilkan submenu

                    // Kalkulasi posisi popup
                    const itemRect = item.getBoundingClientRect();
                    const sidebarRect = sidebar.getBoundingClientRect();
                    const viewportHeight = window.innerHeight;

                    let topPosition = itemRect.top;
                    const submenuHeight = submenu.scrollHeight; // Gunakan scrollHeight untuk tinggi sebenarnya

                    // Penyesuaian posisi vertikal agar tidak keluar layar
                    if (topPosition + submenuHeight > viewportHeight - 10) { // Beri sedikit margin
                        topPosition = Math.max(10, viewportHeight - submenuHeight - 10);
                    }

                    // Set style untuk popup
                    Object.assign(submenu.style, {
                        position: 'fixed',
                        left: `${sidebarRect.right}px`,
                        top: `${topPosition}px`,
                        width: '200px', // Atau lebar yang diinginkan
                        maxHeight: `calc(${viewportHeight}px - 20px)`, // Batasi tinggi maks
                        overflowY: 'auto',
                        zIndex: '1060',
                        boxShadow: '0 0.5rem 1rem rgba(0, 0, 0, 0.15)',
                        backgroundColor: 'var(--bg-light)',
                        borderRadius: '0 0.25rem 0.25rem 0',
                        padding: '0.5rem 0' // Padding atas/bawah untuk submenu popup
                    });
                }
            });

            item.addEventListener('mouseleave', () => {
                if (!isMobile && sidebar.classList.contains('minimized')) {
                    // Gunakan timeout untuk memberi waktu mouse pindah ke submenu
                    hoverTimeout = setTimeout(() => {
                        // Periksa apakah mouse masih di atas item atau submenunya
                        if (!item.matches(':hover') && !submenu.matches(':hover')) {
                            closeAllMinimizedSubmenus(); // Tutup semua jika mouse keluar
                        }
                    }, 150); // Waktu tunggu singkat (misal 150ms)
                }
            });

            // Event listener untuk submenu itu sendiri (penting!)
            submenu.addEventListener('mouseleave', () => {
                if (!isMobile && sidebar.classList.contains('minimized')) {
                    hoverTimeout = setTimeout(() => {
                        if (!item.matches(':hover') && !submenu.matches(':hover')) {
                            closeAllMinimizedSubmenus();
                        }
                    }, 150);
                }
            });
            // Juga tutup saat item submenu diklik
            submenu.querySelectorAll('.nav-link').forEach(subLink => {
                subLink.addEventListener('click', () => {
                    if (!isMobile && sidebar.classList.contains('minimized')) {
                        closeAllMinimizedSubmenus();
                    }
                });
            });
        });


        // Tambahkan event listener untuk document click untuk menutup submenu popup (minimized mode)
        document.addEventListener('click', function(e) {
            if (!isMobile && sidebar.classList.contains('minimized')) {
                // Jika klik terjadi di luar sidebar ATAU di dalam sidebar tapi bukan di item submenu yg sedang popup
                if (!sidebar.contains(e.target)) {
                    closeAllMinimizedSubmenus();
                } else if (!e.target.closest('.submenu.show') && !e.target.closest('.has-submenu.open')) {
                    // Jika klik di dalam sidebar tapi bukan di submenu popup atau parentnya
                    closeAllMinimizedSubmenus();
                }
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            const newIsMobile = window.innerWidth < 992;

            // Hanya reload jika berubah dari mobile ke desktop atau sebaliknya
            if (newIsMobile !== isMobile) {
                location.reload();
            }

            // Jika dalam mode mobile dan sidebar terbuka, tutup sidebar
            if (newIsMobile && !sidebar.classList.contains('mobile-collapsed')) {
                sidebar.classList.add('mobile-collapsed');
                overlay.classList.remove('show');
                mobileToggleContainer.classList.add('show');
                updateBodyClass();
            }
        });

        // Fungsi pencarian menu
        const searchInput = document.querySelector('.search-input input');
        const menuItems = document.querySelectorAll('.nav-link');
        const menuParents = document.querySelectorAll('.has-submenu');

        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();

            // Reset semua menu dan submenu
            menuItems.forEach(item => {
                item.style.display = 'flex';
                const parent = item.closest('.nav-item');
                if (parent) {
                    parent.style.display = 'block';
                }
            });

            menuParents.forEach(parent => {
                parent.style.display = 'block';
                const submenu = parent.querySelector('.submenu');
                if (submenu) {
                    submenu.classList.remove('show');
                    parent.classList.remove('open');
                }
            });

            if (searchTerm !== '') {
                // Sembunyikan semua menu terlebih dahulu
                menuItems.forEach(item => {
                    const text = item.textContent.toLowerCase();
                    const isSubmenuToggle = item.classList.contains('submenu-toggle');
                    const parent = item.closest('.nav-item');

                    if (!text.includes(searchTerm)) {
                        if (!isSubmenuToggle) {
                            if (parent) {
                                parent.style.display = 'none';
                            }
                        }
                    } else {
                        // Jika menu item ditemukan, tampilkan parent dan buka submenu jika ada
                        if (parent) {
                            parent.style.display = 'block';
                            const parentSubmenu = parent.closest('.submenu');
                            if (parentSubmenu) {
                                parentSubmenu.classList.add('show');
                                const parentItem = parentSubmenu.closest('.has-submenu');
                                if (parentItem) {
                                    parentItem.classList.add('open');
                                    parentItem.style.display = 'block';
                                }
                            }
                        }
                    }
                });

                // Periksa submenu yang memiliki item yang cocok
                menuParents.forEach(parent => {
                    const submenu = parent.querySelector('.submenu');
                    if (submenu) {
                        const hasVisibleChild = Array.from(submenu.querySelectorAll('.nav-item')).some(
                            item => item.style.display !== 'none'
                        );

                        if (hasVisibleChild) {
                            parent.style.display = 'block';
                            submenu.classList.add('show');
                            parent.classList.add('open');
                        } else {
                            const toggleText = parent.querySelector('.submenu-toggle').textContent.toLowerCase();
                            if (!toggleText.includes(searchTerm)) {
                                parent.style.display = 'none';
                            }
                        }
                    }
                });
            }
        });

        // Reset pencarian saat input dikosongkan
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                this.value = '';
                this.dispatchEvent(new Event('input'));
            }
        });
    });
</script>