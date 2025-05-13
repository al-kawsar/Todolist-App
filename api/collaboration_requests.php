<?php
// api/collaboration_requests.php - Handle collaboration requests
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../utils/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$response = ['success' => false, 'message' => 'Invalid request'];
$userId = $_SESSION['user']['user_id'];
$currentUser = $_SESSION['user'];

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');
    
    // Send collaboration request
    if ($action === 'send_request') {
        $listId = (int)($_POST['list_id'] ?? 0);
        $username = sanitize($_POST['username'] ?? '');
        $permission = sanitize($_POST['permission'] ?? 'view');
        
        // Validate input
        if (!$listId || empty($username) || !in_array($permission, ['view', 'edit', 'admin'])) {
            $response['message'] = 'Data tidak valid';
            sendResponse($response);
        }
        
        // Check if the user owns this list
        $sql = "SELECT * FROM lists WHERE list_id = ? AND user_id = ? AND is_deleted = 0";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $listId, $userId);
        $stmt->execute();
        $list = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$list) {
            $response['message'] = 'List tidak ditemukan atau akses ditolak';
            sendResponse($response);
        }
        
        // Find the user to share with
        $sql = "SELECT user_id, full_name, username FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $targetUser = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$targetUser) {
            $response['message'] = 'Pengguna tidak ditemukan';
            sendResponse($response);
        }
        
        // Don't share with yourself
        if ($targetUser['user_id'] === $userId) {
            $response['message'] = 'Anda tidak dapat berbagi list dengan diri sendiri';
            sendResponse($response);
        }
        
        // Check if already shared
        $sql = "SELECT * FROM list_collaborators WHERE list_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $listId, $targetUser['user_id']);
        $stmt->execute();
        $existingShare = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($existingShare) {
            $response['message'] = 'List sudah dibagikan dengan pengguna ini';
            sendResponse($response);
        }
        
        // Check for existing request
        $sql = "SELECT * FROM collaboration_requests WHERE list_id = ? AND target_user_id = ? AND status = 'pending'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $listId, $targetUser['user_id']);
        $stmt->execute();
        $existingRequest = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($existingRequest) {
            $response['message'] = 'Permintaan kolaborasi sudah ada untuk pengguna ini';
            sendResponse($response);
        }
        
        // Create collaboration request
        $sql = "INSERT INTO collaboration_requests (list_id, sender_id, target_user_id, permission, status) 
                VALUES (?, ?, ?, ?, 'pending')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiis", $listId, $userId, $targetUser['user_id'], $permission);
        $result = $stmt->execute();
        $requestId = $conn->insert_id;
        $stmt->close();
        
        if ($result) {
            // Create notification for target user
            createNotification(
                $targetUser['user_id'],
                'Permintaan Kolaborasi Baru',
                $currentUser['username'] . ' mengundang Anda untuk berkolaborasi pada daftar "' . $list['title'] . '"',
                'collaboration_request',
                'list',
                $listId
            );
            
            $response['success'] = true;
            $response['message'] = 'Permintaan kolaborasi berhasil dikirim';
        } else {
            $response['message'] = 'Gagal mengirim permintaan kolaborasi';
        }
    }
    
    // Approve collaboration request
    elseif ($action === 'approve_request') {
        $requestId = (int)($_POST['request_id'] ?? 0);
        
        // Validate input
        if (!$requestId) {
            $response['message'] = 'Request ID tidak valid';
            sendResponse($response);
        }
        
        // Get request details
        $sql = "SELECT cr.*, l.title as list_title, u.username as sender_username 
                FROM collaboration_requests cr
                JOIN lists l ON cr.list_id = l.list_id 
                JOIN users u ON cr.sender_id = u.user_id
                WHERE cr.request_id = ? AND cr.target_user_id = ? AND cr.status = 'pending'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $requestId, $userId);
        $stmt->execute();
        $request = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$request) {
            $response['message'] = 'Permintaan tidak ditemukan atau sudah diproses';
            sendResponse($response);
        }
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Update request status
            $sql = "UPDATE collaboration_requests SET status = 'approved', updated_at = CURRENT_TIMESTAMP WHERE request_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $requestId);
            $stmt->execute();
            
            // Create collaboration record
            $sql = "INSERT INTO list_collaborators (list_id, user_id, permission) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iis", $request['list_id'], $userId, $request['permission']);
            $stmt->execute();
            
            // Create notification for request sender
            createNotification(
                $request['sender_id'],
                'Permintaan Kolaborasi Diterima',
                $currentUser['username'] . ' menerima permintaan kolaborasi Anda untuk daftar "' . $request['list_title'] . '"',
                'collaboration_accepted',
                'list',
                $request['list_id']
            );
            $conn->commit();
            
            $response['success'] = true;
            $response['message'] = 'Permintaan kolaborasi diterima';
        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = 'Error: ' . $e->getMessage();
        }
    }
    
    // Reject collaboration request
    elseif ($action === 'reject_request') {
        $requestId = (int)($_POST['request_id'] ?? 0);
        
        if (!$requestId) {
            $response['message'] = 'Request ID tidak valid';
            sendResponse($response);
        }
        
        // Get request details first
        $sql = "SELECT cr.*, l.title as list_title, u.username as sender_username 
                FROM collaboration_requests cr
                JOIN lists l ON cr.list_id = l.list_id 
                JOIN users u ON cr.sender_id = u.user_id
                WHERE cr.request_id = ? AND cr.target_user_id = ? AND cr.status = 'pending'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $requestId, $userId);
        $stmt->execute();
        $request = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($request) {
            $sql = "UPDATE collaboration_requests SET status = 'rejected', updated_at = CURRENT_TIMESTAMP 
                    WHERE request_id = ? AND target_user_id = ? AND status = 'pending'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $requestId, $userId);
            $result = $stmt->execute();
            
            if ($result) {
                // Create notification for request sender
                createNotification(
                    $request['sender_id'],
                    'Permintaan Kolaborasi Ditolak',
                    $currentUser['username'] . ' menolak permintaan kolaborasi Anda untuk daftar "' . $request['list_title'] . '"',
                    'collaboration_rejected',
                    'list',
                    $request['list_id']
                );
                $response['success'] = true;
                $response['message'] = 'Permintaan kolaborasi ditolak';
            } else {
                $response['message'] = 'Gagal menolak permintaan kolaborasi';
            }
        } else {
            $response['message'] = 'Permintaan tidak ditemukan atau sudah diproses';
        }
    }
}

// Return response
function sendResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Return response
sendResponse($response);