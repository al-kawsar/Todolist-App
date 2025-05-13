<?php
// api/notifications.php - Notifications API endpoints
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../utils/auth.php';

// Ensure the user is logged in
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = getCurrentUserId();
$response = ['success' => false];

// Handle GET requests (fetching notifications)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = sanitize($_GET['action'] ?? '');
    
    // Count unread notifications
    if ($action === 'count_unread') {
        $count = countUnreadNotifications($userId);
        
        $response['success'] = true;
        $response['count'] = $count;
    }
    // Default: Get recent notifications
    else {
        // Fetch the 10 most recent notifications
        $sql = "SELECT * FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 10";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        $response['success'] = true;
        $response = $notifications;
    }
}

// Handle POST requests (marking notifications as read)
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');
    
    // Mark a single notification as read
    if ($action === 'mark_read') {
        $notificationId = (int)($_POST['notification_id'] ?? 0);
        
        // Validate input
        if (!$notificationId) {
            $response['message'] = 'Invalid input';
            sendResponse($response);
        }
        
        // Check if the notification belongs to the user
        $sql = "SELECT * FROM notifications WHERE notification_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $notificationId, $userId);
        $stmt->execute();
        $notification = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$notification) {
            $response['message'] = 'Notification not found or access denied';
            sendResponse($response);
        }
        
        // Mark as read
        $sql = "UPDATE notifications SET is_read = 1 WHERE notification_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $notificationId);
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            $response['success'] = true;
            $response['message'] = 'Notification marked as read';
        } else {
            $response['message'] = 'Failed to update notification';
        }
    }
    
    // Mark all notifications as read
    elseif ($action === 'mark_all_read') {
        $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            $response['success'] = true;
            $response['message'] = 'All notifications marked as read';
        } else {
            $response['message'] = 'Failed to update notifications';
        }
    }
    
    // If action is not recognized
    else {
        $response['message'] = 'Invalid action';
    }
}

// Send the response
sendResponse($response);

// Helper function to send JSON response
function sendResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}