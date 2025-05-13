<?php
// config/functions.php - Common utility functions

require_once 'config.php';
require_once 'database.php';

// Sanitize user input
function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8'));
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect to a URL
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Format date to a readable format
function formatDate($date, $format = 'd M Y, H:i') {
    return date($format, strtotime($date));
}

// Generate random string (for tokens, file names, etc.)
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $randomString;
}

// Log activity
function logActivity($userId, $actionType, $entityType, $entityId, $details = null) {
    global $conn;
    
    $sql = "INSERT INTO activity_logs (user_id, action_type, entity_type, entity_id, details) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $userId, $actionType, $entityType, $entityId, $details);
    $stmt->execute();
    $stmt->close();
}

// Create notification
function createNotification($userId, $title, $message, $type, $entityType, $entityId = null) {
    global $conn;
    
    $sql = "INSERT INTO notifications (user_id, title, message, type, entity_type, entity_id) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssi", $userId, $title, $message, $type, $entityType, $entityId);
    $stmt->execute();
    $stmt->close();
}

// Count unread notifications for a user
function countUnreadNotifications($userId) {
    global $conn;
    
    $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'];
}

// Get user by ID
function getUserById($userId) {
    global $conn;
    
    $sql = "SELECT * FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    return $user;
}

/**
 * Calculate growth percentage between two numbers
 * @param int|float $current Current value
 * @param int|float $previous Previous value
 * @return float Growth percentage
 */
function calculateGrowth($current, $previous) {
    if ($previous == 0) {
        return $current > 0 ? 100 : 0;
    }
    return round((($current - $previous) / $previous) * 100, 1);
}

// Modify the getUserStats function to include previous month's data
function getUserStats($userId) {
    global $conn;
    
    // Current month stats
    $currentMonth = date('Y-m');
    $previousMonth = date('Y-m', strtotime('-1 month'));
    
    // Total tasks for current month
    $sql = "SELECT 
                COUNT(*) as total_tasks,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_tasks
            FROM tasks 
            WHERE user_id = ? 
            AND is_deleted = 0 
            AND DATE_FORMAT(created_at, '%Y-%m') = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $userId, $currentMonth);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentStats = $result->fetch_assoc();
    $stmt->close();
    
    // Previous month stats
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $userId, $previousMonth);
    $stmt->execute();
    $result = $stmt->get_result();
    $previousStats = $result->fetch_assoc();
    $stmt->close();
    
    // Tasks due today
    $today = date('Y-m-d');
    $sql = "SELECT COUNT(*) as tasks_due_today 
            FROM tasks 
            WHERE user_id = ? 
            AND DATE(due_date) = ? 
            AND status != 'completed' 
            AND is_deleted = 0";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $userId, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $dueTodayStats = $result->fetch_assoc();
    $stmt->close();
    
    // Combine all stats and add previous month data
    return array_merge($currentStats, $dueTodayStats, [
        'previous_total_tasks' => $previousStats['total_tasks'],
        'previous_completed_tasks' => $previousStats['completed_tasks'],
        'previous_pending_tasks' => $previousStats['pending_tasks'],
        'previous_in_progress_tasks' => $previousStats['in_progress_tasks']
    ]);
}

// Get monthly task completion statistics for charts
function getMonthlyStats($userId) {
    global $conn;
    
    $stats = [];
    for ($i = 0; $i < 6; $i++) {
        $month = date('Y-m', strtotime("-$i months"));
        $monthName = date('M Y', strtotime("-$i months"));
        
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                FROM tasks 
                WHERE user_id = ? 
                AND DATE_FORMAT(created_at, '%Y-%m') = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $userId, $month);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        $stats[] = [
            'month' => $monthName,
            'total' => (int)$row['total'],
            'completed' => (int)$row['completed']
        ];
    }
    
    return array_reverse($stats);
}

/**
 * Konversi timestamp menjadi format "waktu yang lalu"
 * @param string $timestamp
 * @return string
 */
function timeAgo($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    
    $minutes = round($seconds / 60);    // value 60 is seconds
    $hours   = round($seconds / 3600);  // value 3600 is 60 minutes * 60 seconds
    $days    = round($seconds / 86400); // value 86400 is 24 hours * 60 minutes * 60 seconds
    $weeks   = round($seconds / 604800);// value 604800 is 7 days * 24 hours * 60 minutes * 60 seconds
    $months  = round($seconds / 2629440);// value 2629440 is ((365+365+365+365+366)/5/12) days * 24 hours * 60 minutes * 60 seconds
    $years   = round($seconds / 31553280);// value 31553280 is ((365+365+365+365+366)/5) days * 24 hours * 60 minutes * 60 seconds
    
    if ($seconds <= 60) {
        return "Baru saja";
    } else if ($minutes <= 60) {
        if ($minutes == 1) {
            return "1 menit yang lalu";
        } else {
            return "$minutes menit yang lalu";
        }
    } else if ($hours <= 24) {
        if ($hours == 1) {
            return "1 jam yang lalu";
        } else {
            return "$hours jam yang lalu";
        }
    } else if ($days <= 7) {
        if ($days == 1) {
            return "Kemarin";
        } else {
            return "$days hari yang lalu";
        }
    } else if ($weeks <= 4.3) {
        if ($weeks == 1) {
            return "1 minggu yang lalu";
        } else {
            return "$weeks minggu yang lalu";
        }
    } else if ($months <= 12) {
        if ($months == 1) {
            return "1 bulan yang lalu";
        } else {
            return "$months bulan yang lalu";
        }
    } else {
        if ($years == 1) {
            return "1 tahun yang lalu";
        } else {
            return "$years tahun yang lalu";
        }
    }
}

/**
 * Set flash message yang akan ditampilkan di halaman berikutnya
 * @param string $type Tipe pesan (success, error, warning, info)
 * @param string $message Isi pesan
 */
function setFlashMessage($type, $message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Ambil dan hapus flash messages
 * @return array
 */
function getFlashMessages() {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Tampilkan flash messages
 */
function displayFlashMessages() {
    $messages = getFlashMessages();
    foreach ($messages as $message) {
        $type = $message['type'];
        $text = $message['message'];
        
        // Tentukan warna berdasarkan tipe pesan
        $bgColor = 'bg-gray-100';
        $textColor = 'text-gray-800';
        $borderColor = 'border-gray-400';
        
        switch ($type) {
            case 'success':
                $bgColor = 'bg-green-100';
                $textColor = 'text-green-800';
                $borderColor = 'border-green-400';
                break;
            case 'error':
                $bgColor = 'bg-red-100';
                $textColor = 'text-red-800';
                $borderColor = 'border-red-400';
                break;
            case 'warning':
                $bgColor = 'bg-yellow-100';
                $textColor = 'text-yellow-800';
                $borderColor = 'border-yellow-400';
                break;
            case 'info':
                $bgColor = 'bg-blue-100';
                $textColor = 'text-blue-800';
                $borderColor = 'border-blue-400';
                break;
        }
        
        echo "<div class='mb-4 border-l-4 p-4 {$bgColor} {$textColor} border {$borderColor}' role='alert'>
                <p class='font-medium'>{$text}</p>
              </div>";
    }
}