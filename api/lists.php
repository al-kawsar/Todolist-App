<?php
// api/lists.php - Lists API endpoints
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

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');
    
    // Delete list (soft delete)
    if ($action === 'delete_list') {
        $listId = (int)($_POST['list_id'] ?? 0);
        
        // Validate input
        if (!$listId) {
            $response['message'] = 'Invalid input';
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
            $response['message'] = 'List not found or access denied';
            sendResponse($response);
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Soft delete the list
            $sql = "UPDATE lists SET is_deleted = 1, updated_at = CURRENT_TIMESTAMP WHERE list_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $listId);
            $listResult = $stmt->execute();
            $stmt->close();
            
            // Soft delete all tasks in the list
            $sql = "UPDATE tasks SET is_deleted = 1, updated_at = CURRENT_TIMESTAMP WHERE list_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $listId);
            $tasksResult = $stmt->execute();
            $stmt->close();
            
            // If both operations succeeded
            if ($listResult && $tasksResult) {
                $conn->commit();
                
                // Log activity
                logActivity($userId, 'delete', 'list', $listId, "Deleted list '{$list['title']}' and its tasks");
                
                $response['success'] = true;
                $response['message'] = 'List and its tasks deleted successfully';
            } else {
                $conn->rollback();
                $response['message'] = 'Failed to delete list';
            }
        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = 'Error: ' . $e->getMessage();
        }
    }
    
    // Share list with another user
    elseif ($action === 'share_list') {
        $listId = (int)($_POST['list_id'] ?? 0);
        $email = sanitize($_POST['email'] ?? '');
        $permission = sanitize($_POST['permission'] ?? 'view');
        
        // Validate input
        if (!$listId || empty($email) || !in_array($permission, ['view', 'edit', 'admin'])) {
            $response['message'] = 'Invalid input';
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
            $response['message'] = 'List not found or access denied';
            sendResponse($response);
        }
        
        // Find the user to share with
        $sql = "SELECT user_id, full_name, email FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $sharedUser = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$sharedUser) {
            $response['message'] = 'User not found';
            sendResponse($response);
        }
        
        // Don't share with yourself
        if ($sharedUser['user_id'] === $userId) {
            $response['message'] = 'You cannot share a list with yourself';
            sendResponse($response);
        }
        
        // Check if already shared
        $sql = "SELECT * FROM collaborators WHERE entity_type = 'list' AND entity_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $listId, $sharedUser['user_id']);
        $stmt->execute();
        $existingShare = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($existingShare) {
            // Update existing share
            $sql = "UPDATE collaborators SET permission = ? WHERE collaboration_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $permission, $existingShare['collaboration_id']);
            $result = $stmt->execute();
            $stmt->close();
            
            if ($result) {
                // Log activity
                logActivity($userId, 'update', 'collaborator', $existingShare['collaboration_id'], "Updated sharing permission for list '{$list['title']}' with {$sharedUser['email']}");
                
                // Create notification for the shared user
                createNotification(
                    $sharedUser['user_id'],
                    'List Sharing Updated',
                    "{$currentUser['full_name']} updated sharing permissions for list: {$list['title']}",
                    'share',
                    'list',
                    $listId
                );
                
                $response['success'] = true;
                $response['message'] = 'Sharing permissions updated successfully';
            } else {
                $response['message'] = 'Failed to update sharing permissions';
            }
        } else {
            // Create new share
            $sql = "INSERT INTO collaborators (entity_type, entity_id, user_id, permission) VALUES ('list', ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iis", $listId, $sharedUser['user_id'], $permission);
            $result = $stmt->execute();
            $collaborationId = $conn->insert_id;
            $stmt->close();
            
            if ($result) {
                // Log activity
                logActivity($userId, 'share', 'list', $listId, "Shared list '{$list['title']}' with {$sharedUser['email']}");
                
                // Create notification for the shared user
                createNotification(
                    $sharedUser['user_id'],
                    'List Shared With You',
                    "{$currentUser['full_name']} shared a list with you: {$list['title']}",
                    'share',
                    'list',
                    $listId
                );
                
                $response['success'] = true;
                $response['message'] = 'List shared successfully';
                $response['collaboration'] = [
                    'id' => $collaborationId,
                    'user' => [
                        'user_id' => $sharedUser['user_id'],
                        'full_name' => $sharedUser['full_name'],
                        'email' => $sharedUser['email']
                    ],
                    'permission' => $permission
                ];
            } else {
                $response['message'] = 'Failed to share list';
            }
        }
    }
    
    // Remove list sharing
    elseif ($action === 'remove_share') {
        $collaborationId = (int)($_POST['collaboration_id'] ?? 0);
        
        // Validate input
        if (!$collaborationId) {
            $response['message'] = 'Invalid input';
            sendResponse($response);
        }
        
        // Get collaboration details
        $sql = "SELECT c.*, l.title, l.user_id as list_owner, u.full_name, u.email 
                FROM collaborators c 
                JOIN lists l ON c.entity_id = l.list_id AND c.entity_type = 'list' 
                JOIN users u ON c.user_id = u.user_id
                WHERE c.collaboration_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $collaborationId);
        $stmt->execute();
        $collaboration = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$collaboration) {
            $response['message'] = 'Collaboration not found';
            sendResponse($response);
        }
        
        // Check if the user is authorized to remove this collaboration
        // Only the list owner or the shared user can remove the collaboration
        if ($collaboration['list_owner'] !== $userId && $collaboration['user_id'] !== $userId) {
            $response['message'] = 'Not authorized to remove this share';
            sendResponse($response);
        }
        
        // Delete the collaboration
        $sql = "DELETE FROM collaborators WHERE collaboration_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $collaborationId);
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            // Log activity
            logActivity($userId, 'delete', 'collaborator', $collaborationId, 
                ($userId === $collaboration['list_owner']) 
                    ? "Removed sharing of list '{$collaboration['title']}' with {$collaboration['email']}" 
                    : "Left shared list '{$collaboration['title']}'"
            );
            
            // Create notification
            if ($userId === $collaboration['list_owner']) {
                // Notify the user that their access was removed
                createNotification(
                    $collaboration['user_id'],
                    'List Access Removed',
                    "Your access to the list '{$collaboration['title']}' has been removed",
                    'share',
                    'list',
                    $collaboration['entity_id']
                );
            } else {
                // Notify the list owner that the user left
                createNotification(
                    $collaboration['list_owner'],
                    'User Left Shared List',
                    "{$currentUser['full_name']} has left your shared list: {$collaboration['title']}",
                    'share',
                    'list',
                    $collaboration['entity_id']
                );
            }
            
            $response['success'] = true;
            $response['message'] = 'Sharing removed successfully';
        } else {
            $response['message'] = 'Failed to remove sharing';
        }
    }
    
    // If action is not recognized
    else {
        $response['message'] = 'Invalid action';
    }
}

// Handle GET requests
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = sanitize($_GET['action'] ?? '');
    
    // Get list collaborators
    if ($action === 'get_collaborators') {
        $listId = (int)($_GET['list_id'] ?? 0);
        
        // Validate input
        if (!$listId) {
            $response['message'] = 'Invalid input';
            sendResponse($response);
        }
        
        // Check if the user has access to this list
        $sql = "SELECT * FROM lists WHERE list_id = ? AND (user_id = ? OR list_id IN 
                (SELECT entity_id FROM collaborators WHERE entity_type = 'list' AND user_id = ?))";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $listId, $userId, $userId);
        $stmt->execute();
        $list = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$list) {
            $response['message'] = 'List not found or access denied';
            sendResponse($response);
        }
        
        // Get list owner
        $sql = "SELECT user_id, username, full_name, email, profile_picture FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $list['user_id']);
        $stmt->execute();
        $owner = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        // Get collaborators
        $sql = "SELECT c.*, u.username, u.full_name, u.email, u.profile_picture 
                FROM collaborators c 
                JOIN users u ON c.user_id = u.user_id 
                WHERE c.entity_type = 'list' AND c.entity_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $listId);
        $stmt->execute();
        $collaborators = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Build response
        $response['success'] = true;
        $response['owner'] = $owner;
        $response['collaborators'] = $collaborators;
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