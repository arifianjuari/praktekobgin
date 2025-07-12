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
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --card-shadow: 0 10px 40px rgba(0,0,0,0.1);
            --card-hover-shadow: 0 20px 60px rgba(0,0,0,0.15);
            --border-radius: 20px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            line-height: 1.6;
        }

        /* Modern Header */
        .hero-section {
            background: var(--primary-gradient);
            padding: 4rem 0 6rem;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="white" opacity="0.1"><polygon points="0,100 1000,0 1000,100"/></svg>');
            background-size: cover;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            color: white;
        }

        .hero-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }

        .hero-subtitle {
            font-size: 1.25rem;
            font-weight: 300;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Modern Service Cards */
        .services-container {
            margin-top: -3rem;
            position: relative;
            z-index: 3;
        }

        .category-header {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
            border-left: 5px solid #667eea;
        }

        .category-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.75rem;
            font-weight: 600;
            color: #2d3748;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .service-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            border: none;
            overflow: hidden;
            height: 100%;
            cursor: pointer;
            position: relative;
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
            transform: scaleX(0);
            transition: var(--transition);
        }

        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--card-hover-shadow);
        }

        .service-card:hover::before {
            transform: scaleX(1);
        }

        .card-body {
            padding: 2rem;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .service-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.4rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 1rem;
            line-height: 1.3;
        }

        .service-description {
            color: #718096;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
            flex-grow: 1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .service-preparation {
            background: linear-gradient(135deg, #e6f3ff 0%, #f0f8ff 100%);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid #4299e1;
            font-size: 0.85rem;
        }

        .service-preparation .prep-label {
            font-weight: 600;
            color: #2b6cb0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .service-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
            margin-top: auto;
        }

        .service-duration {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #718096;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .service-price {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Modern Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            margin: 2rem 0;
        }

        .empty-icon {
            font-size: 4rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1.5rem;
        }

        .empty-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.75rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.75rem;
        }

        .empty-message {
            color: #718096;
            font-size: 1.1rem;
            max-width: 400px;
            margin: 0 auto;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.1rem;
            }
            
            .service-card {
                margin-bottom: 1.5rem;
            }
            
            .card-body {
                padding: 1.5rem;
            }
        }

        /* Loading Animation */
        .service-card {
            animation: slideUp 0.6s ease-out forwards;
            opacity: 0;
            transform: translateY(30px);
        }

        @keyframes slideUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Modern Alert */
        .modern-alert {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            border: none;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 4px solid #f56565;
        }

        .modern-alert .alert-icon {
            font-size: 1.25rem;
            margin-right: 0.75rem;
        }
    </style>
</head>

<body>
    <?php include_once __DIR__ . '/../../../template/sidebar.php'; ?>

    <div class="main-content">
        <!-- Hero Section -->
        <div class="hero-section">
            <div class="container">
                <div class="hero-content">
                    <h1 class="hero-title"><?= FormatHelper::cleanOutput($page_title) ?></h1>
                    <p class="hero-subtitle"><?= FormatHelper::cleanOutput($page_subtitle) ?></p>
                </div>
            </div>
        </div>

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

                    <!-- Service Cards -->
                    <div class="row g-4 mb-5">
                        <?php foreach ($items as $index => $item): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="service-card" onclick="redirectToRegistration('<?= FormatHelper::cleanOutput($item['id_layanan']) ?>')" style="animation-delay: <?= $index * 0.1 ?>s">
                                <div class="card-body">
                                    <h5 class="service-title"><?= FormatHelper::cleanOutput($item['nama_layanan']) ?></h5>
                                    
                                    <?php if (!empty($item['deskripsi'])): ?>
                                        <p class="service-description">
                                            <?= FormatHelper::cleanOutput(FormatHelper::truncateText($item['deskripsi'], 120)) ?>
                                        </p>
                                    <?php endif; ?>

                                    <?php if (!empty($item['persiapan'])): ?>
                                        <div class="service-preparation">
                                            <div class="prep-label">
                                                <i class="bi bi-info-circle-fill"></i>
                                                <strong>Persiapan</strong>
                                            </div>
                                            <div>
                                                <?= FormatHelper::cleanOutput(FormatHelper::truncateText($item['persiapan'], 100)) ?>
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
                                        <div class="service-price"><?= FormatHelper::formatRupiah($item['harga']) ?></div>
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
