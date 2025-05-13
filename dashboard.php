<?php
// dashboard.php - Dashboard pengguna
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'utils/auth.php';

// Redirect ke login jika belum masuk
if (!isLoggedIn()) {
    redirect('login.php');
}

// Dapatkan data pengguna saat ini
$currentUser = $_SESSION['user'];
$userId = $currentUser['user_id'];

// Dapatkan statistik pengguna
$stats = getUserStats($userId);
$monthlyStats = getMonthlyStats($userId);

// Dapatkan tugas terbaru
$sql = "SELECT t.*, l.title as list_title, l.color as list_color 
        FROM tasks t 
        LEFT JOIN lists l ON t.list_id = l.list_id 
        WHERE t.user_id = ? AND t.is_deleted = 0 
        ORDER BY t.created_at DESC 
        LIMIT 5";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$recentTasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Dapatkan tugas mendatang (jatuh tempo dalam 7 hari ke depan)
$today = date('Y-m-d');
$nextWeek = date('Y-m-d', strtotime('+7 days'));

$sql = "SELECT t.*, l.title as list_title, l.color as list_color 
        FROM tasks t 
        LEFT JOIN lists l ON t.list_id = l.list_id 
        WHERE t.user_id = ? 
        AND t.due_date BETWEEN ? AND ? 
        AND t.status != 'completed' 
        AND t.is_deleted = 0 
        ORDER BY t.due_date ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $userId, $today, $nextWeek);
$stmt->execute();
$upcomingTasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Dapatkan daftar pengguna
$sql = "SELECT * FROM lists WHERE user_id = ? AND is_deleted = 0 ORDER BY title ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$lists = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= APP_NAME ?></title>
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="assets/css/output.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="node_modules/sweetalert2/dist/sweetalert2.min.js"></script>
    <link rel="stylesheet" href="node_modules/sweetalert2/dist/sweetalert2.min.css">
    <!-- Chart.js -->
    <script src="node_modules/chart.js/dist/chart.umd.js"></script>
    <!-- Toastify JS -->
    <link rel="stylesheet" href="node_modules/toastify-js/src/toastify.css">
    <script src="node_modules/toastify-js/src/toastify.js"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <?php include 'includes/header.php'; ?>

        <div class="flex-grow flex">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>
            
            <!-- Main Content -->
            <div class="flex-1 overflow-auto">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <!-- Welcome Section with Quick Stats -->
                    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                        <div class="flex items-center justify-between">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">Selamat datang, <?= htmlspecialchars($currentUser['full_name']) ?>!</h1>
                                <p class="mt-1 text-sm text-gray-500">Berikut ringkasan aktivitas Anda</p>
                            </div>
                            <div class="flex space-x-4">
                                <button onclick="window.location.href='modules/tasks/create.php'" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                                    <i class="fas fa-plus mr-2"></i>
                                    Tugas Baru
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                        <!-- Total Tasks Card -->
                        <div class="bg-white rounded-lg shadow-lg p-6 transform hover:scale-105 transition-transform duration-200">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-blue-500 bg-opacity-10">
                                    <i class="fas fa-tasks text-blue-500 text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <h2 class="text-sm font-medium text-gray-600">Total Tugas</h2>
                                    <p class="text-2xl font-semibold text-gray-900"><?= $stats['total_tasks'] ?? 0 ?></p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <div class="flex items-center text-sm text-gray-500">
                                    <span class="text-green-500 mr-2">
                                        <i class="fas fa-arrow-up"></i>
                                        <?= calculateGrowth($stats['total_tasks'], $stats['previous_total_tasks']) ?>%
                                    </span>
                                    <span>dari bulan lalu</span>
                                </div>
                            </div>
                        </div>

                        <!-- Tugas Selesai Card -->
                        <div class="bg-white rounded-lg shadow-lg p-6 transform hover:scale-105 transition-transform duration-200">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-green-500 bg-opacity-10">
                                    <i class="fas fa-check-circle text-green-500 text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <h2 class="text-sm font-medium text-gray-600">Tugas Selesai</h2>
                                    <p class="text-2xl font-semibold text-gray-900"><?= $stats['completed_tasks'] ?? 0 ?></p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <div class="flex items-center text-sm text-gray-500">
                                    <span class="text-green-500 mr-2">
                                        <i class="fas fa-arrow-up"></i>
                                        <?= calculateGrowth($stats['completed_tasks'], $stats['previous_completed_tasks']) ?>%
                                    </span>
                                    <span>dari bulan lalu</span>
                                </div>
                            </div>
                        </div>

                        <!-- Tugas Tertunda Card -->
                        <div class="bg-white rounded-lg shadow-lg p-6 transform hover:scale-105 transition-transform duration-200">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-yellow-500 bg-opacity-10">
                                    <i class="fas fa-clock text-yellow-500 text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <h2 class="text-sm font-medium text-gray-600">Tugas Tertunda</h2>
                                    <p class="text-2xl font-semibold text-gray-900"><?= $stats['pending_tasks'] ?? 0 ?></p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <div class="flex items-center text-sm text-gray-500">
                                    <span class="text-green-500 mr-2">
                                        <i class="fas fa-arrow-up"></i>
                                        <?= calculateGrowth($stats['pending_tasks'], $stats['previous_pending_tasks']) ?>%
                                    </span>
                                    <span>dari bulan lalu</span>
                                </div>
                            </div>
                        </div>

                        <!-- Jatuh Tempo Hari Ini Card -->
                        <div class="bg-white rounded-lg shadow-lg p-6 transform hover:scale-105 transition-transform duration-200">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-red-500 bg-opacity-10">
                                    <i class="fas fa-calendar-day text-red-500 text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <h2 class="text-sm font-medium text-gray-600">Jatuh Tempo Hari Ini</h2>
                                    <p class="text-2xl font-semibold text-gray-900"><?= $stats['tasks_due_today'] ?? 0 ?></p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <div class="flex items-center text-sm text-gray-500">
                                    <span class="text-green-500 mr-2">
                                        <i class="fas fa-arrow-up"></i>
                                        <?= calculateGrowth($stats['tasks_due_today'], $stats['previous_tasks_due_today'] ?? 0) ?>%
                                    </span>
                                    <span>dari bulan lalu</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Section -->
                    <div class="grid grid-cols-1 lg:grid-cols-1 gap-8 mb-8">
                        <!-- Productivity Chart -->
                        <div class="bg-white rounded-lg shadow-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Tren Produktivitas</h3>
                                <div class="flex items-center space-x-2">
                                    <select class="form-select text-sm" id="productivityTimeRange">
                                        <option value="week">Minggu Ini</option>
                                        <option value="month" selected>Bulan Ini</option>
                                        <option value="year">Tahun Ini</option>
                                    </select>
                                </div>
                            </div>
                            <div class="relative h-80">
                                <canvas id="productivityChart"></canvas>
                            </div>
                        </div>

                        <!-- Task Distribution Chart -->
                      
                    </div>

                    <!-- Tasks Overview Section -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Recent Tasks -->
                        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                            <div class="p-6 border-b border-gray-200">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-medium text-gray-900">Tugas Terbaru</h3>
                                    <a href="modules/tasks/view.php" class="text-sm text-blue-600 hover:text-blue-800">Lihat Semua</a>
                                </div>
                            </div>
                            <div class="divide-y divide-gray-200 max-h-96 overflow-y-auto">
                                <!-- Task items with enhanced styling -->
                                <?php foreach ($recentTasks as $task): ?>
                                <div class="p-4 hover:bg-gray-50 transition-colors duration-150">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <?php if ($task['status'] === 'completed'): ?>
                                                <i class="fas fa-check-circle text-green-500 mr-3"></i>
                                            <?php elseif ($task['status'] === 'in_progress'): ?>
                                                <i class="fas fa-spinner text-blue-500 mr-3"></i>
                                            <?php else: ?>
                                                <i class="far fa-circle text-gray-400 mr-3"></i>
                                            <?php endif; ?>
                                            
                                            <p class="text-sm font-medium text-gray-900 <?= $task['status'] === 'completed' ? 'line-through' : '' ?>">
                                                <?= htmlspecialchars($task['title']) ?>
                                            </p>
                                        </div>
                                        
                                        <div class="flex items-center">
                                            <?php if ($task['list_title']): ?>
                                                <span class="px-2 py-1 text-xs rounded-full" style="background-color: <?= $task['list_color'] ?>20; color: <?= $task['list_color'] ?>">
                                                    <?= htmlspecialchars($task['list_title']) ?>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <a href="modules/tasks/edit.php?id=<?= $task['task_id'] ?>" class="ml-2 text-gray-400 hover:text-gray-500">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <?php if ($task['due_date']): ?>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <i class="far fa-calendar-alt mr-1.5 text-gray-400"></i>
                                            <p>
                                                Jatuh Tempo: <?= formatDate($task['due_date'], 'd M Y') ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Upcoming Tasks -->
                        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                            <div class="p-6 border-b border-gray-200">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-medium text-gray-900">Tugas Mendatang</h3>
                                    <a href="modules/tasks/view.php?filter=upcoming" class="text-sm text-blue-600 hover:text-blue-800">Lihat Semua</a>
                                </div>
                            </div>
                            <div class="divide-y divide-gray-200 max-h-96 overflow-y-auto">
                                <!-- Task items with enhanced styling -->
                                <?php foreach ($upcomingTasks as $task): ?>
                                <div class="p-4 hover:bg-gray-50 transition-colors duration-150">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <?php if ($task['priority'] === 'high'): ?>
                                                <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                                            <?php elseif ($task['priority'] === 'medium'): ?>
                                                <i class="fas fa-arrow-alt-circle-up text-yellow-500 mr-3"></i>
                                            <?php else: ?>
                                                <i class="fas fa-arrow-alt-circle-down text-blue-500 mr-3"></i>
                                            <?php endif; ?>
                                            
                                            <p class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($task['title']) ?>
                                            </p>
                                        </div>
                                        
                                        <div>
                                            <?php 
                                            $dueDate = new DateTime($task['due_date']);
                                            $today = new DateTime();
                                            $interval = $today->diff($dueDate);
                                            $daysLeft = $interval->days;
                                            
                                            if ($dueDate < $today) {
                                                echo '<span class="px-2 py-1 text-xs font-semibold rounded-full text-red-800 bg-red-100">Terlambat</span>';
                                            } elseif ($dueDate->format('Y-m-d') === $today->format('Y-m-d')) {
                                                echo '<span class="px-2 py-1 text-xs font-semibold rounded-full text-orange-800 bg-orange-100">Hari Ini</span>';
                                            } elseif ($daysLeft <= 1) {
                                                echo '<span class="px-2 py-1 text-xs font-semibold rounded-full text-yellow-800 bg-yellow-100">Besok</span>';
                                            } else {
                                                echo '<span class="px-2 py-1 text-xs font-semibold rounded-full text-green-800 bg-green-100">Dalam ' . $daysLeft . ' hari</span>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-2 flex items-center justify-between text-sm text-gray-500">
                                        <div class="flex items-center">
                                            <i class="far fa-calendar-alt mr-1.5 text-gray-400"></i>
                                            <p>
                                                Jatuh Tempo: <?= formatDate($task['due_date'], 'd M Y') ?>
                                            </p>
                                        </div>
                                        
                                        <?php if ($task['list_title']): ?>
                                            <span class="px-2 py-1 text-xs rounded-full" style="background-color: <?= $task['list_color'] ?>20; color: <?= $task['list_color'] ?>">
                                                <?= htmlspecialchars($task['list_title']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Scripts -->
    <script>
        // Enhanced chart configurations
        const productivityChart = new Chart(
            document.getElementById('productivityChart').getContext('2d'),
            {
                type: 'line',
                data: {
                    labels: <?= json_encode(array_column($monthlyStats, 'month')) ?>,
                    datasets: [
                        {
                            label: 'Total Tugas',
                            data: <?= json_encode(array_column($monthlyStats, 'total')) ?>,
                            borderColor: '#3B82F6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 6,
                            pointBackgroundColor: '#FFFFFF',
                            pointBorderColor: '#3B82F6',
                            pointBorderWidth: 3,
                            pointHoverRadius: 8,
                            pointHoverBackgroundColor: '#3B82F6',
                            pointHoverBorderColor: '#FFFFFF',
                            pointHoverBorderWidth: 4
                        },
                        {
                            label: 'Tugas Selesai', 
                            data: <?= json_encode(array_column($monthlyStats, 'completed')) ?>,
                            borderColor: '#10B981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 6,
                            pointBackgroundColor: '#FFFFFF',
                            pointBorderColor: '#10B981',
                            pointBorderWidth: 3,
                            pointHoverRadius: 8,
                            pointHoverBackgroundColor: '#10B981',
                            pointHoverBorderColor: '#FFFFFF',
                            pointHoverBorderWidth: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                padding: 20,
                                font: {
                                    size: 14,
                                    weight: 'bold'
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y + ' tugas';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                drawBorder: false,
                                color: 'rgba(0, 0, 0, 0.1)',
                                lineWidth: 1
                            },
                            ticks: {
                                font: {
                                    size: 12
                                },
                                padding: 10
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 12
                                },
                                padding: 10
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeInOutQuart'
                    }
                }
            }
        );

        // Add event listener for productivity time range changes
        document.getElementById('productivityTimeRange').addEventListener('change', function(e) {
            const timeRange = e.target.value;
            
            // Make AJAX request to get new data based on selected time range
            fetch(`api/stats.php?range=${timeRange}&user_id=<?= $userId ?>`)
                .then(response => response.json())
                .then(data => {
                    // Update chart data
                    productivityChart.data.labels = data.labels;
                    productivityChart.data.datasets[0].data = data.total;
                    productivityChart.data.datasets[1].data = data.completed;
                    productivityChart.update();
                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                    Toastify({
                        text: "Gagal memuat data statistik",
                        duration: 3000,
                        close: true,
                        gravity: "top",
                        position: "right",
                        backgroundColor: "#EF4444",
                    }).showToast();
                });
        });

        // Add smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>