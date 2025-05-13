<?php
// api/stats.php - Endpoint API untuk mengambil statistik produktivitas
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../utils/auth.php';

// Set header response JSON
header('Content-Type: application/json');

// Periksa apakah pengguna sudah login
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Tidak diizinkan']);
    exit;
}

// Ambil parameter
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : getCurrentUserId();
$range = isset($_GET['range']) ? $_GET['range'] : 'month';

// Validasi akses pengguna
if ($userId !== getCurrentUserId()) {
    http_response_code(403);
    echo json_encode(['error' => 'Dilarang']);
    exit;
}

try {
    $stats = [];
    $labels = [];
    $totalData = [];
    $completedData = [];

    switch ($range) {
        case 'week':
            // Ambil data 7 hari terakhir
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $labels[] = date('D', strtotime("-$i days")); // Nama hari

                $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                FROM tasks 
                WHERE user_id = ? 
                AND DATE(created_at) = ?
                AND is_deleted = 0";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("is", $userId, $date);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                
                $totalData[] = (int)$result['total'];
                $completedData[] = (int)$result['completed'];
                $stmt->close();
            }
            break;

        case 'month':
            // Ambil data 30 hari terakhir dikelompokkan per minggu
            for ($i = 4; $i >= 0; $i--) {
                $weekStart = date('Y-m-d', strtotime("-$i week"));
                $weekEnd = date('Y-m-d', strtotime("-" . ($i-1) . " week -1 day"));
                $labels[] = 'Minggu ' . ($i + 1);

                $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                FROM tasks 
                WHERE user_id = ? 
                AND DATE(created_at) BETWEEN ? AND ?
                AND is_deleted = 0";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iss", $userId, $weekStart, $weekEnd);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                
                $totalData[] = (int)$result['total'];
                $completedData[] = (int)$result['completed'];
                $stmt->close();
            }
            break;

        case 'year':
            // Ambil data 12 bulan terakhir
            for ($i = 11; $i >= 0; $i--) {
                $monthStart = date('Y-m-01', strtotime("-$i months"));
                $monthEnd = date('Y-m-t', strtotime("-$i months"));
                $labels[] = date('M Y', strtotime("-$i months")); // Nama bulan dan tahun

                $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                FROM tasks 
                WHERE user_id = ? 
                AND DATE(created_at) BETWEEN ? AND ?
                AND is_deleted = 0";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iss", $userId, $monthStart, $monthEnd);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                
                $totalData[] = (int)$result['total'];
                $completedData[] = (int)$result['completed'];
                $stmt->close();
            }
            break;

        default:
            throw new Exception('Parameter rentang waktu tidak valid');
    }

    // Kembalikan data
    echo json_encode([
        'labels' => $labels,
        'total' => $totalData,
        'completed' => $completedData
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>