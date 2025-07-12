<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= FormatHelper::cleanOutput($page_title) ?> - Sistem Antrian Pasien</title>
    
    <!-- Modern CSS Framework -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- Google Fonts - Modern Typography -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Modern CSS -->
    <style>
        :root {
            --primary-color: rgb(100, 130, 125); /* Teal yang lebih lembut dan modern */
            --primary-light: rgba(100, 130, 125, 0.1);
            --text-dark: #2d3748; /* Abu-abu gelap untuk teks */
            --text-light: #ffffff;
            --text-muted: #718096; /* Abu-abu untuk teks sekunder */
            --body-bg: #f8f9fa; /* Latar belakang netral yang sangat terang */
            --card-bg: #ffffff;
            --card-shadow: 0 5px 15px rgba(0,0,0,0.05);
            --card-hover-shadow: 0 8px 25px rgba(0,0,0,0.07);
            --border-radius: 12px; /* Radius border yang lebih halus */
            --border-color: #e9ecef;
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--body-bg);
            min-height: 100vh;
            line-height: 1.6;
            color: var(--text-dark);
        }

        /* Minimalist Header */
        .hero-section {
            background-color: var(--primary-light);
            /* background-image: linear-gradient(135deg, var(--primary-color) 0%, color-mix(in srgb, var(--primary-color) 70%, white) 100%); */
            padding: 3rem 0 5rem;
            text-align: center;
        }

        .hero-content {
            color: var(--text-dark);
        }

        .hero-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 2.8rem; /* Sedikit lebih kecil untuk kesan minimalis */
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: var(--primary-color);
        }

        .hero-subtitle {
            font-size: 1.1rem;
            font-weight: 400;
            opacity: 0.85;
            max-width: 600px;
            margin: 0 auto;
            color: var(--text-muted);
        }

        /* Minimalist Service Cards */
        .services-container {
            padding-top: 2rem; /* Add padding instead of negative margin */
            position: relative;
            z-index: 3;
        }

        .category-header {
            background: transparent; /* Hilangkan background, biarkan menyatu */
            border-radius: 0;
            padding: 1.5rem 0;
            margin-bottom: 1.5rem;
            box-shadow: none;
            border-bottom: 1px solid var(--border-color);
            /* border-left: 4px solid var(--primary-color); */
        }

        .category-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.5rem; /* Sedikit lebih kecil */
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        .category-title i {
            color: var(--primary-color);
        }

        .service-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            border: 1px solid var(--border-color);
            overflow: hidden;
            height: 100%;
            cursor: pointer;
            position: relative;
            /* Make cards more compact */
            margin-bottom: 1rem;
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background-color: var(--primary-color);
            transform: scaleX(0);
            transition: var(--transition);
            transform-origin: left;
        }

        .service-card:hover {
            transform: translateY(-5px); /* Efek hover lebih halus */
            box-shadow: var(--card-hover-shadow);
        }

        .service-card:hover::before {
            transform: scaleX(1);
        }

        .card-body {
            padding: 1rem; /* Reduced padding for more compact look */
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .service-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1rem; /* Smaller font size */
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem; /* Reduced margin */
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2; /* Limit to 2 lines */
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .service-description {
            font-size: 0.8rem; /* Smaller font size */
            line-height: 1.4;
            margin-bottom: 0.75rem; /* Reduced margin */
            color: var(--text-muted);
            flex-grow: 1;
            display: -webkit-box;
            -webkit-line-clamp: 2; /* Show fewer lines */
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .service-preparation {
            background-color: var(--primary-light);
            border-radius: 6px; /* Slightly smaller border radius */
            padding: 0.5rem 0.75rem; /* Reduced padding */
            margin-bottom: 0.75rem; /* Reduced margin */
            border-left: 2px solid var(--primary-color); /* Thinner border */
            font-size: 0.75rem; /* Smaller font size */
        }

        .service-preparation .prep-label {
            font-weight: 600;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.4rem;
            margin-bottom: 0.3rem;
        }
        .service-preparation .prep-label i {
            font-size: 0.9em;
        }

        .service-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 0.75rem; /* Reduced padding */
            border-top: 1px solid var(--border-color);
            margin-top: auto;
        }

        .service-duration {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 500;
        }

        .service-price {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.1rem; /* Slightly smaller price */
            font-weight: 700;
            color: var(--primary-color);
            white-space: nowrap; /* Prevent price from wrapping */
            margin-left: 0.5rem; /* Add some spacing */
        }

        /* Minimalist Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
            margin: 2rem 0;
        }

        .empty-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .empty-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .empty-message {
            color: var(--text-muted);
            font-size: 1rem;
            max-width: 400px;
            margin: 0 auto;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 1.8rem; /* Smaller title on mobile */
            }
            
            .hero-subtitle {
                font-size: 0.95rem; /* Slightly smaller subtitle */
            }
            
            .service-card {
                margin-bottom: 0.75rem; /* Reduced margin between cards */
            }
            
            .card-body {
                padding: 0.75rem; /* Even more compact on mobile */
            }
            
            .category-title {
                font-size: 1.2rem; /* Slightly smaller category title */
                margin-bottom: 0.5rem;
            }
            
            .service-title {
                font-size: 0.95rem; /* Slightly smaller title on mobile */
                margin-bottom: 0.4rem;
                -webkit-line-clamp: 2; /* Limit to 2 lines */
            }
            
            .service-description {
                display: none; /* Hide description on mobile to save space */
            }
            
            .service-preparation {
                display: none; /* Hide preparation on mobile */
            }
            
            .service-footer {
                padding-top: 0.5rem; /* Tighter footer */
                flex-wrap: wrap; /* Allow footer items to wrap */
            }
            
            .service-duration {
                font-size: 0.75rem; /* Smaller duration text */
                margin-right: 0.5rem;
            }
            
            .service-price {
                font-size: 1rem; /* Slightly smaller price */
            }
        }

        /* Loading Animation (optional for minimalist, can be removed or simplified) */
        .service-card {
            animation: fadeIn 0.5s ease-out forwards;
            opacity: 0;
        }

        @keyframes fadeIn {
            to {
                opacity: 1;
            }
        }

        /* Minimalist Alert */
        .modern-alert {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            border-left: 3px solid #dc3545; /* Standard danger color */
        }

        .modern-alert .alert-icon {
            font-size: 1.1rem;
            margin-right: 0.6rem;
            color: #dc3545;
        }
    </style>
</head>

<body>
    <?php include_once __DIR__ . '/../../../template/sidebar.php'; ?>

    <div class="main-content">
        <!-- Hero Section (Only show if there's a title or subtitle) -->
        <?php if (!empty($page_title) || !empty($page_subtitle)): ?>
        <div class="hero-section">
            <div class="container">
                <div class="hero-content">
                    <?php if (!empty($page_title)): ?>
                    <h1 class="hero-title"><?= FormatHelper::cleanOutput($page_title) ?></h1>
                    <?php endif; ?>
                    <?php if (!empty($page_subtitle)): ?>
                    <p class="hero-subtitle"><?= FormatHelper::cleanOutput($page_subtitle) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Services Container -->
        <div class="container services-container">
            <!-- Error Message -->
            <?php if (isset($error_message) && $error_message): ?>
                <div class="modern-alert" role="alert">
                    <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
                    <?= FormatHelper::cleanOutput($error_message) ?>
                </div>
            <?php endif; ?>

            <!-- Services Content -->
            <?php if (!empty($layanan_by_kategori)): ?>
                <?php foreach ($layanan_by_kategori as $kategori => $items): ?>
                    <!-- Category Header -->
                    <div class="category-header">
                        <h2 class="category-title">
                            <i class="bi bi-heart-pulse-fill"></i>
                            <?= FormatHelper::cleanOutput($kategori) ?>
                        </h2>
                    </div>

                    <!-- Service Cards - More columns on mobile -->
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3 mb-4">
                        <?php foreach ($items as $index => $item): ?>
                        <div class="col-lg-4 col-md-6">
                                <div class="service-card" onclick="redirectToRegistration('<?= FormatHelper::cleanOutput($item['id_layanan']) ?>')" style="animation-delay: <?= $index * 0.1 ?>s">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h5 class="service-title mb-0"><?= FormatHelper::cleanOutput($item['nama_layanan']) ?></h5>
                                            <div class="service-price ms-2"><?= FormatHelper::formatRupiah($item['harga']) ?></div>
                                        </div>
                                        
                                        <?php if (!empty($item['deskripsi'])): ?>
                                            <p class="service-description d-none d-md-block">
                                                <?= FormatHelper::cleanOutput(FormatHelper::truncateText($item['deskripsi'], 100)) ?>
                                            </p>
                                        <?php endif; ?>

                                        <?php if (!empty($item['persiapan'])): ?>
                                            <div class="service-preparation d-none d-md-block">
                                                <div class="prep-label">
                                                    <i class="bi bi-info-circle-fill"></i>
                                                    <strong>Persiapan</strong>
                                                </div>
                                                <div>
                                                    <?= FormatHelper::cleanOutput(FormatHelper::truncateText($item['persiapan'], 80)) ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="service-footer">
    <div class="service-duration">
        <i class="bi bi-clock-history"></i>
        <?php if ($item['durasi_estimasi']): ?>
            <?= FormatHelper::formatDuration($item['durasi_estimasi']) ?>
        <?php else: ?>
            Estimasi variatif
        <?php endif; ?>
    </div>
</div>
                                    </div>
                                </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Modern Empty State -->
                <div class="empty-state">
                    <i class="bi bi-heart-pulse empty-icon"></i>
                    <h4 class="empty-title">Belum Ada Layanan Tersedia</h4>
                    <p class="empty-message">Saat ini belum ada layanan yang dapat ditampilkan. Silakan cek kembali di lain waktu.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modern JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function redirectToRegistration(layananId) {
        <?php if ($is_logged_in): ?>
            window.location.href = '<?= $base_url ?>/pendaftaran/form_pendaftaran_pasien.php?layanan=' + encodeURIComponent(layananId);
        <?php else: ?>
            window.location.href = '<?= $base_url ?>/login.php?redirect=<?= urlencode($_SERVER["REQUEST_URI"]) ?>';
        <?php endif; ?>
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        // Modern card interactions
        const cards = document.querySelectorAll('.service-card');
        
        cards.forEach((card, index) => {
            // Add modern hover effects
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
            
            // Add click feedback
            card.addEventListener('mousedown', function() {
                this.style.transform = 'translateY(-5px) scale(0.98)';
            });
            
            card.addEventListener('mouseup', function() {
                this.style.transform = 'translateY(-10px) scale(1.02)';
            });
            
            // Keyboard accessibility
            card.setAttribute('tabindex', '0');
            card.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                }
            });
        });
        
        // Smooth scroll for better UX
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const parallax = document.querySelector('.hero-section');
            if (parallax) {
                parallax.style.transform = `translateY(${scrolled * 0.5}px)`;
            }
        });
    });
    </script>
</body>
</html>
