<?php
// modules/statistics/stats.php - Statistik dan analitik untuk tugas
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/functions.php';
require_once '../../utils/auth.php';

// Redirect ke login jika belum login
if (!isLoggedIn()) {
    redirect('../../login.php');
}

// Dapatkan pengguna saat ini
$currentUser = $_SESSION['user'];
$userId = $currentUser['user_id'];

// Dapatkan statistik keseluruhan
$stats = getUserStats($userId);

// Dapatkan statistik bulanan untuk grafik
$monthlyStats = getMonthlyStats($userId);

// Dapatkan penyelesaian tugas berdasarkan hari dalam seminggu
$sql = "SELECT 
            DAYOFWEEK(due_date) as day_num,
            COUNT(*) as total_tasks,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks
        FROM tasks
        WHERE user_id = ? AND due_date IS NOT NULL AND is_deleted = 0
        GROUP BY DAYOFWEEK(due_date)
        ORDER BY DAYOFWEEK(due_date)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$weekdayStats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Format data hari dalam seminggu untuk grafik
$daysOfWeek = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$weekdayData = [];
foreach ($daysOfWeek as $index => $day) {
    $dayNum = $index + 1; // MySQL DAYOFWEEK() dimulai dengan 1 = Minggu
    $found = false;
    
    foreach ($weekdayStats as $stat) {
        if ($stat['day_num'] == $dayNum) {
            $completionRate = $stat['total_tasks'] > 0 
                ? round(($stat['completed_tasks'] / $stat['total_tasks']) * 100) 
                : 0;
            
            $weekdayData[] = [
                'day' => $day,
                'total' => (int)$stat['total_tasks'],
                'completed' => (int)$stat['completed_tasks'],
                'completion_rate' => $completionRate
            ];
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        $weekdayData[] = [
            'day' => $day,
            'total' => 0,
            'completed' => 0,
            'completion_rate' => 0
        ];
    }
}

// Dapatkan statistik tugas berdasarkan prioritas
$sql = "SELECT 
            priority,
            COUNT(*) as total_tasks,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks
        FROM tasks
        WHERE user_id = ? AND is_deleted = 0
        GROUP BY priority
        ORDER BY FIELD(priority, 'urgent', 'high', 'medium', 'low')";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$priorityStats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Dapatkan statistik tugas berdasarkan daftar
$sql = "SELECT 
            l.list_id,
            l.title as list_name,
            l.color as list_color,
            COUNT(t.task_id) as total_tasks,
            SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks
        FROM lists l
        LEFT JOIN tasks t ON l.list_id = t.list_id AND t.is_deleted = 0
        WHERE l.user_id = ? AND l.is_deleted = 0
        GROUP BY l.list_id
        ORDER BY total_tasks DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$listStats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Dapatkan tugas yang baru selesai
$sql = "SELECT t.*, l.title as list_title, l.color as list_color 
        FROM tasks t
        LEFT JOIN lists l ON t.list_id = l.list_id
        WHERE t.user_id = ? AND t.status = 'completed' AND t.is_deleted = 0
        ORDER BY t.updated_at DESC
        LIMIT 5";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$recentCompletedTasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Dapatkan rentetan penyelesaian tugas
$sql = "SELECT DATE(updated_at) as completion_date
        FROM tasks
        WHERE user_id = ? AND status = 'completed' AND is_deleted = 0
        ORDER BY updated_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$completionDates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Hitung rentetan saat ini
$currentStreak = 0;
$longestStreak = 0;
$yesterday = date('Y-m-d', strtotime('-1 day'));
$today = date('Y-m-d');

// Periksa apakah ada tugas yang diselesaikan hari ini
$completedToday = false;
foreach ($completionDates as $date) {
    if ($date['completion_date'] === $today) {
        $completedToday = true;
        break;
    }
}

// Hitung rentetan
if (count($completionDates) > 0) {
    $uniqueDates = [];
    foreach ($completionDates as $date) {
        $uniqueDates[$date['completion_date']] = true;
    }
    
    // Rentetan saat ini
    if ($completedToday) {
        $currentStreak = 1;
        $checkDate = $yesterday;
        
        while (isset($uniqueDates[$checkDate])) {
            $currentStreak++;
            $checkDate = date('Y-m-d', strtotime($checkDate . ' -1 day'));
        }
    } elseif (isset($uniqueDates[$yesterday])) {
        $currentStreak = 1;
        $checkDate = date('Y-m-d', strtotime($yesterday . ' -1 day'));
        
        while (isset($uniqueDates[$checkDate])) {
            $currentStreak++;
            $checkDate = date('Y-m-d', strtotime($checkDate . ' -1 day'));
        }
    }
    
    // Rentetan terpanjang
    $dates = array_keys($uniqueDates);
    sort($dates);
    
    $tempStreak = 1;
    $longestStreak = 1;
    
    for ($i = 1; $i < count($dates); $i++) {
        $curr = new DateTime($dates[$i]);
        $prev = new DateTime($dates[$i-1]);
        $diff = $curr->diff($prev);
        
        if ($diff->days == 1) {
            $tempStreak++;
            $longestStreak = max($longestStreak, $tempStreak);
        } else {
            $tempStreak = 1;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistik - <?= APP_NAME ?></title>
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="../../assets/css/output.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../../node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <!-- Chart.js -->
    <script src="../../node_modules/chart.js/dist/chart.umd.js"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <!-- Navigasi Atas -->
        <?php include_once '../../includes/header.php'; ?>
        
        <div class="flex-grow flex">
            <!-- Sidebar -->
            <?php include_once '../../includes/sidebar.php'; ?>
            
            <!-- Konten Utama -->
            <div class="flex-1 overflow-auto">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div class="mb-8">
                        <h1 class="text-2xl font-bold text-gray-900">Statistik & Analitik</h1>
                        <p class="mt-1 text-sm text-gray-500">Pantau produktivitas dan pola penyelesaian tugas Anda</p>
                    </div>
                    
                    <!-- Kartu Statistik -->
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                        <!-- Total Tugas -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                        <i class="fas fa-tasks text-white text-xl"></i>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">
                                                Total Tugas
                                            </dt>
                                            <dd>
                                                <div class="text-lg font-medium text-gray-900">
                                                    <?= $stats['total_tasks'] ?? 0 ?>
                                                </div>
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tingkat Penyelesaian -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                        <i class="fas fa-chart-pie text-white text-xl"></i>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">
                                                Tingkat Penyelesaian
                                            </dt>
                                            <dd>
                                                <div class="text-lg font-medium text-gray-900">
                                                    <?php
                                                    $completionRate = $stats['total_tasks'] > 0 
                                                        ? round(($stats['completed_tasks'] / $stats['total_tasks']) * 100) 
                                                        : 0;
                                                    echo $completionRate . '%';
                                                    ?>
                                                </div>
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Rentetan Saat Ini -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                                        <i class="fas fa-fire text-white text-xl"></i>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">
                                                Rentetan Saat Ini
                                            </dt>
                                            <dd>
                                                <div class="text-lg font-medium text-gray-900">
                                                    <?= $currentStreak ?> hari
                                                </div>
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Rentetan Terpanjang -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                                        <i class="fas fa-trophy text-white text-xl"></i>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">
                                                Rentetan Terpanjang
                                            </dt>
                                            <dd>
                                                <div class="text-lg font-medium text-gray-900">
                                                    <?= $longestStreak ?> hari
                                                </div>
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Grafik -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                        <!-- Grafik Produktivitas Bulanan -->
                        <div class="bg-white p-6 rounded-lg shadow">
                            <h2 class="text-lg font-medium text-gray-900 mb-4">Produktivitas Bulanan</h2>
                            <div>
                                <canvas id="monthlyChart" height="250"></canvas>
                            </div>
                        </div>
                        
                        <!-- Kinerja per Hari -->
                        <div class="bg-white p-6 rounded-lg shadow">
                            <h2 class="text-lg font-medium text-gray-900 mb-4">Kinerja per Hari</h2>
                            <div>
                                <canvas id="weekdayChart" height="250"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                        <!-- Grafik Tugas berdasarkan Prioritas -->
                        <div class="bg-white p-6 rounded-lg shadow">
                            <h2 class="text-lg font-medium text-gray-900 mb-4">Tugas berdasarkan Prioritas</h2>
                            <div class="flex justify-center">
                                <div style="height: 250px; width: 250px;">
                                    <canvas id="priorityChart"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Grafik Tugas berdasarkan Status -->
                        <div class="bg-white p-6 rounded-lg shadow">
                            <h2 class="text-lg font-medium text-gray-900 mb-4">Tugas berdasarkan Status</h2>
                            <div class="flex justify-center">
                                <div style="height: 250px; width: 250px;">
                                    <canvas id="statusChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Statistik Tugas per Daftar -->
                    <div class="bg-white p-6 rounded-lg shadow mb-8">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Penyelesaian Tugas per Daftar</h2>
                        
                        <?php if (!empty($listStats)): ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Daftar
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Total Tugas
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Selesai
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Tingkat Penyelesaian
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Progres
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($listStats as $list): ?>
                                            <?php
                                            $completionRate = $list['total_tasks'] > 0 
                                                ? round(($list['completed_tasks'] / $list['total_tasks']) * 100) 
                                                : 0;
                                            ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        <div class="w-3 h-3 rounded-full mr-2" style="background-color: <?= $list['list_color'] ?>"></div>
                                                        <div class="text-sm font-medium text-gray-900">
                                                            <a href="../tasks/view.php?list_id=<?= $list['list_id'] ?>" class="hover:underline">
                                                                <?= htmlspecialchars($list['list_name']) ?>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?= $list['total_tasks'] ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?= $list['completed_tasks'] ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?= $completionRate ?>%
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                                                        <div class="bg-green-500 h-2.5 rounded-full" style="width: <?= $completionRate ?>%"></div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4 text-sm text-gray-500">
                                Tidak ada daftar atau tugas yang ditemukan.
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Tugas yang Baru Selesai -->
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Tugas yang Baru Selesai</h2>
                        
                        <?php if (!empty($recentCompletedTasks)): ?>
                            <div class="overflow-hidden">
                                <ul class="divide-y divide-gray-200">
                                    <?php foreach ($recentCompletedTasks as $task): ?>
                                        <li class="py-4">
                                            <div class="flex items-center space-x-4">
                                                <div class="flex-shrink-0">
                                                    <i class="fas fa-check-circle text-green-500 text-xl"></i>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 truncate">
                                                        <?= htmlspecialchars($task['title']) ?>
                                                    </p>
                                                    <div class="flex items-center mt-1">
                                                        <?php if ($task['list_title']): ?>
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: <?= $task['list_color'] ?>20; color: <?= $task['list_color'] ?>;">
                                                                <?= htmlspecialchars($task['list_title']) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <time datetime="<?= $task['updated_at'] ?>"><?= formatDate($task['updated_at']) ?></time>
                                                </div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4 text-sm text-gray-500">
                                Belum ada tugas yang selesai.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <?php include_once '../../includes/footer.php'; ?>
    </div>

    <!-- jQuery -->
    <script src="../../node_modules/jquery/dist/jquery.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Grafik Bulanan
            const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
            const monthlyData = <?= json_encode($monthlyStats) ?>;
            
            new Chart(monthlyCtx, {
                type: 'line',
                data: {
                    labels: monthlyData.map(item => item.month),
                    datasets: [
                        {
                            label: 'Total Tugas',
                            data: monthlyData.map(item => item.total),
                            borderColor: '#3B82F6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Tugas Selesai',
                            data: monthlyData.map(item => item.completed),
                            borderColor: '#10B981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
            
            // Grafik Mingguan
            const weekdayCtx = document.getElementById('weekdayChart').getContext('2d');
            const weekdayData = <?= json_encode($weekdayData) ?>;
            
            new Chart(weekdayCtx, {
                type: 'bar',
                data: {
                    labels: weekdayData.map(item => item.day),
                    datasets: [
                        {
                            label: 'Total Tugas',
                            data: weekdayData.map(item => item.total),
                            backgroundColor: 'rgba(59, 130, 246, 0.7)',
                            borderColor: '#3B82F6',
                            borderWidth: 1
                        },
                        {
                            label: 'Tugas Selesai',
                            data: weekdayData.map(item => item.completed),
                            backgroundColor: 'rgba(16, 185, 129, 0.7)',
                            borderColor: '#10B981',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
            
            // Grafik Prioritas
            const priorityCtx = document.getElementById('priorityChart').getContext('2d');
            const priorityData = <?= json_encode($priorityStats) ?>;
            
            // Transform data prioritas
            const priorityLabels = [];
            const priorityValues = [];
            const priorityColors = {
                'urgent': '#EF4444', // Merah
                'high': '#F59E0B',   // Kuning
                'medium': '#3B82F6', // Biru
                'low': '#10B981'     // Hijau
            };
            const priorityBgColors = [];
            
            priorityData.forEach(item => {
                priorityLabels.push(item.priority.charAt(0).toUpperCase() + item.priority.slice(1));
                priorityValues.push(item.total_tasks);
                priorityBgColors.push(priorityColors[item.priority]);
            });
            
            new Chart(priorityCtx, {
                type: 'doughnut',
                data: {
                    labels: priorityLabels,
                    datasets: [{
                        data: priorityValues,
                        backgroundColor: priorityBgColors,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    },
                    cutout: '65%'
                }
            });
            
            // Grafik Status
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            const statusData = {
                labels: ['Selesai', 'Sedang Dikerjakan', 'Menunggu', 'Dibatalkan'],
                datasets: [{
                    data: [
                        <?= $stats['completed_tasks'] ?? 0 ?>,
                        <?= $stats['in_progress_tasks'] ?? 0 ?>,
                        <?= $stats['pending_tasks'] ?? 0 ?>,
                        <?= $stats['cancelled_tasks'] ?? 0 ?>
                    ],
                    backgroundColor: [
                        '#10B981', // Hijau untuk selesai
                        '#3B82F6', // Biru untuk sedang dikerjakan
                        '#F59E0B', // Kuning untuk menunggu
                        '#6B7280'  // Abu-abu untuk dibatalkan
                    ],
                    hoverOffset: 4
                }]
            };
            
            new Chart(statusCtx, {
                type: 'doughnut',
                data: statusData,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    },
                    cutout: '65%'
                }
            });
        });
    </script>
</body>
</html>