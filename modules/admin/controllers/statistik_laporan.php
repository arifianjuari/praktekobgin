<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/logs/error.log');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'admin_praktek'])) {
    header('Location: ../login.php');
    exit();
}

// Get project root directory and set up base URL
$root_dir = dirname(__DIR__);
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../../config/config.php';
}

// Include required files
require_once __DIR__ . '/../../../config/database.php';

// Set page title and include templates
$page_title = "Statistik dan Laporan";
$is_admin = true; // Required for sidebar
include_once __DIR__ . '/../../../template/header.php';
?>

<!-- Add required CSS -->
<link rel="stylesheet" href="<?= $base_url ?>/assets/css/styles.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

<?php
// Include sidebar after CSS
include_once __DIR__ . '/../../../template/sidebar.php';

// Verify database connection
try {
    if (!isset($conn) || !($conn instanceof PDO)) {
        throw new PDOException("Database connection not available");
    }

    // Test connection
    $conn->query("SELECT 1");

    // Initialize variables for date filtering with validation
    $end_date = isset($_GET['end_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['end_date'])
        ? $_GET['end_date']
        : date('Y-m-d');

    $start_date = isset($_GET['start_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['start_date'])
        ? $_GET['start_date']
        : date('Y-m-d', strtotime('-30 days'));

    // Function to safely get statistics data
    function getStatistics($conn, $start_date, $end_date)
    {
        try {
            $stats = array();

            // Total patients with parameterized query
            $query = "SELECT COUNT(*) as total FROM pendaftaran WHERE tanggal_daftar BETWEEN :start_date AND :end_date";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['total_patients'] = $result['total'];

            // Patients by service type
            $query = "SELECT jenis_layanan, COUNT(*) as total 
                 FROM pendaftaran 
                 WHERE tanggal_daftar BETWEEN :start_date AND :end_date
                 GROUP BY jenis_layanan";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
            $stmt->execute();
            $stats['by_service'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Daily patient count
            $query = "SELECT DATE(tanggal_daftar) as date, COUNT(*) as total 
                 FROM pendaftaran 
                 WHERE tanggal_daftar BETWEEN :start_date AND :end_date
                 GROUP BY DATE(tanggal_daftar)
                 ORDER BY date";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
            $stmt->execute();
            $stats['daily_count'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Average daily patients
            $query = "SELECT COALESCE(AVG(daily_count), 0) as avg_daily 
                 FROM (
                    SELECT COUNT(*) as daily_count 
                    FROM pendaftaran 
                    WHERE tanggal_daftar BETWEEN :start_date AND :end_date
                    GROUP BY DATE(tanggal_daftar)
                 ) as daily_counts";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['avg_daily_patients'] = round($result['avg_daily'], 1);

            // Status distribution with error handling
            $query = "SELECT COALESCE(Status_Pendaftaran, 'Tidak Ada Status') as Status_Pendaftaran, 
                        COUNT(*) as total 
                 FROM pendaftaran 
                 WHERE tanggal_daftar BETWEEN :start_date AND :end_date
                 GROUP BY Status_Pendaftaran";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
            $stmt->execute();
            $stats['by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Busiest day of week
            $query = "SELECT 
                    DAYNAME(tanggal_daftar) as day_name,
                    COUNT(*) as total
                 FROM pendaftaran 
                 WHERE tanggal_daftar BETWEEN :start_date AND :end_date
                 GROUP BY DAYNAME(tanggal_daftar)
                 ORDER BY total DESC
                 LIMIT 1";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
            $stmt->execute();
            $busiest_day = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['busiest_day'] = $busiest_day ? $busiest_day['day_name'] : 'N/A';
            $stats['busiest_day_count'] = $busiest_day ? $busiest_day['total'] : 0;

            return $stats;
        } catch (PDOException $e) {
            error_log("Error in getStatistics: " . $e->getMessage());
            return [
                'error' => true,
                'message' => 'Terjadi kesalahan saat mengambil data statistik',
                'total_patients' => 0,
                'by_service' => [],
                'daily_count' => [],
                'avg_daily_patients' => 0,
                'by_status' => [],
                'busiest_day' => 'N/A',
                'busiest_day_count' => 0
            ];
        }
    }

    // Get statistics data with error handling
    try {
        $statistics = getStatistics($conn, $start_date, $end_date);
        if (isset($statistics['error'])) {
            // Log the error but continue with empty data
            error_log("Error retrieving statistics: " . $statistics['message']);
        }
    } catch (Exception $e) {
        error_log("Unexpected error in statistik_laporan.php: " . $e->getMessage());
        $statistics = [
            'total_patients' => 0,
            'by_service' => [],
            'daily_count' => [],
            'avg_daily_patients' => 0,
            'by_status' => [],
            'busiest_day' => 'N/A',
            'busiest_day_count' => 0
        ];
    }
?>

    <!-- Main content -->
    <div class="main-content">
        <div class="container-fluid px-4">
            <h1 class="mt-4">Statistik dan Laporan</h1>

            <!-- Date Filter -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-filter me-1"></i>
                    Filter Periode
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="start_date" class="form-label">Tanggal Mulai</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="end_date" class="form-label">Tanggal Selesai</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-sync-alt me-1"></i> Terapkan Filter
                                </button>
                                <button type="button" class="btn btn-success ms-2" onclick="exportToExcel()">
                                    <i class="fas fa-file-excel me-1"></i> Export Excel
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Statistics Overview -->
            <div class="row">
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-primary text-white mb-4">
                        <div class="card-body">
                            <h4><?php echo number_format($statistics['total_patients']); ?></h4>
                            <div>Total Pasien</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-success text-white mb-4">
                        <div class="card-body">
                            <h4><?php echo number_format($statistics['avg_daily_patients']); ?></h4>
                            <div>Rata-rata Pasien per Hari</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-warning mb-4">
                        <div class="card-body">
                            <h4><?php echo $statistics['busiest_day']; ?></h4>
                            <div>Hari Tersibuk (<?php echo number_format($statistics['busiest_day_count']); ?> pasien)</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-info text-white mb-4">
                        <div class="card-body">
                            <h4><?php echo count($statistics['by_service']); ?></h4>
                            <div>Jenis Layanan Aktif</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Service Type Distribution -->
                <div class="col-xl-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-chart-pie me-1"></i>
                            Distribusi Jenis Layanan
                        </div>
                        <div class="card-body">
                            <canvas id="serviceTypeChart" width="100%" height="40"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Status Distribution -->
                <div class="col-xl-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-chart-bar me-1"></i>
                            Distribusi Status Pendaftaran
                        </div>
                        <div class="card-body">
                            <canvas id="statusChart" width="100%" height="40"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daily Patient Trend -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-line me-1"></i>
                    Tren Kunjungan Harian
                </div>
                <div class="card-body">
                    <canvas id="dailyTrendChart" width="100%" height="40"></canvas>
                </div>
            </div>

            <!-- Detailed Data Table -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-table me-1"></i>
                    Detail Data Kunjungan
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jumlah Pasien</th>
                                    <th>Jenis Layanan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($statistics['daily_count'] as $day): ?>
                                    <tr>
                                        <td><?php echo date('d M Y', strtotime($day['date'])); ?></td>
                                        <td><?php echo number_format($day['total']); ?></td>
                                        <td>-</td>
                                        <td>-</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include required JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>

    <!-- Service Type Chart -->
    <script>
        const serviceTypeCtx = document.getElementById('serviceTypeChart');
        new Chart(serviceTypeCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($statistics['by_service'], 'jenis_layanan')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($statistics['by_service'], 'total')); ?>,
                    backgroundColor: [
                        'rgba(0, 123, 255, 0.8)',
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(220, 53, 69, 0.8)',
                        'rgba(111, 66, 193, 0.8)',
                        'rgba(23, 162, 184, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });

        // Status Distribution Chart
        const statusCtx = document.getElementById('statusChart');
        new Chart(statusCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($statistics['by_status'], 'Status_Pendaftaran')); ?>,
                datasets: [{
                    label: 'Jumlah Pasien',
                    data: <?php echo json_encode(array_column($statistics['by_status'], 'total')); ?>,
                    backgroundColor: 'rgba(0, 123, 255, 0.5)',
                    borderColor: 'rgba(0, 123, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Daily Trend Chart
        const dailyTrendCtx = document.getElementById('dailyTrendChart');
        new Chart(dailyTrendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($statistics['daily_count'], 'date')); ?>,
                datasets: [{
                    label: 'Jumlah Pasien',
                    data: <?php echo json_encode(array_column($statistics['daily_count'], 'total')); ?>,
                    fill: false,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Function to export data to Excel
        function exportToExcel() {
            const data = {
                'Daily Statistics': <?php echo json_encode($statistics['daily_count']); ?>,
                'Service Type Distribution': <?php echo json_encode($statistics['by_service']); ?>,
                'Status Distribution': <?php echo json_encode($statistics['by_status']); ?>
            };

            // Create workbook
            const wb = XLSX.utils.book_new();

            // Convert each dataset to worksheet
            for (let sheetName in data) {
                const ws = XLSX.utils.json_to_sheet(data[sheetName]);
                XLSX.utils.book_append_sheet(wb, ws, sheetName);
            }

            // Save file
            const fileName = `statistik_${<?php echo json_encode($start_date); ?>}_to_${<?php echo json_encode($end_date); ?>}.xlsx`;
            XLSX.writeFile(wb, fileName);
        }
    </script>

<?php
    include($root_dir . '/template/footer.php');
} catch (Exception $e) {
    error_log("Error in statistik_laporan.php: " . $e->getMessage());
    echo '<div class="alert alert-danger">Terjadi kesalahan saat memuat data. Silakan coba lagi nanti.</div>';
}
?>