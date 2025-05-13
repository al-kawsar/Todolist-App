<?php
// api/tasks.php - Task API endpoints
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
    
    // Update task status
    if ($action === 'update_status') {
        $taskId = (int)($_POST['task_id'] ?? 0);
        $status = sanitize($_POST['status'] ?? '');
        
        // Validate input
        if (!$taskId || !in_array($status, ['pending', 'in_progress', 'completed', 'cancelled'])) {
            $response['message'] = 'Invalid input';
            sendResponse($response);
        }
        
        // Check if the user owns this task
        $sql = "SELECT * FROM tasks WHERE task_id = ? AND user_id = ? AND is_deleted = 0";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $taskId, $userId);
        $stmt->execute();
        $task = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$task) {
            $response['message'] = 'Task not found or access denied';
            sendResponse($response);
        }
        
        // Update the task status
        $sql = "UPDATE tasks SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE task_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $taskId);
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            // Log the activity
            $actionType = $status === 'completed' ? 'complete' : 'update';
            logActivity($userId, $actionType, 'task', $taskId, "Changed task status to $status");
            
            $response['success'] = true;
            $response['message'] = "Task status updated to $status";
        } else {
            $response['message'] = 'Failed to update task status';
        }
    }
    
    // Delete task (soft delete)
    elseif ($action === 'delete_task') {
        $taskId = (int)($_POST['task_id'] ?? 0);
        
        // Validate input
        if (!$taskId) {
            $response['message'] = 'Invalid input';
            sendResponse($response);
        }
        
        // Check if the user owns this task
        $sql = "SELECT * FROM tasks WHERE task_id = ? AND user_id = ? AND is_deleted = 0";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $taskId, $userId);
        $stmt->execute();
        $task = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$task) {
            $response['message'] = 'Task not found or access denied';
            sendResponse($response);
        }
        
        // Soft delete the task
        $sql = "UPDATE tasks SET is_deleted = 1, updated_at = CURRENT_TIMESTAMP WHERE task_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $taskId);
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            // Log the activity
            logActivity($userId, 'delete', 'task', $taskId, "Deleted task");
            
            $response['success'] = true;
            $response['message'] = 'Task deleted successfully';
        } else {
            $response['message'] = 'Failed to delete task';
        }
    }
    
    // Add comment to task
    elseif ($action === 'add_comment') {
        $taskId = (int)($_POST['task_id'] ?? 0);
        $content = sanitize($_POST['content'] ?? '');
        
        // Validate input
        if (!$taskId || empty($content)) {
            $response['message'] = 'Invalid input';
            sendResponse($response);
        }
        
        // Check if the user has access to this task
        $sql = "SELECT * FROM tasks WHERE task_id = ? AND (user_id = ? OR task_id IN 
                (SELECT entity_id FROM collaborators WHERE entity_type = 'task' AND user_id = ?)) AND is_deleted = 0";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $taskId, $userId, $userId);
        $stmt->execute();
        $task = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$task) {
            $response['message'] = 'Task not found or access denied';
            sendResponse($response);
        }
        
        // Add the comment
        $sql = "INSERT INTO comments (task_id, user_id, content) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $taskId, $userId, $content);
        $result = $stmt->execute();
        $commentId = $conn->insert_id;
        $stmt->close();
        
        if ($result) {
            // Log the activity
            logActivity($userId, 'create', 'comment', $commentId, "Added comment to task #$taskId");
            
            // Notify task owner if different from current user
            if ($task['user_id'] !== $userId) {
                createNotification(
                    $task['user_id'],
                    'New Comment',
                    "New comment on your task: " . $task['title'],
                    'comment',
                    'task',
                    $taskId
                );
            }
            
            // Get user data for the response
            $userData = getUserById($userId);
            
            $response['success'] = true;
            $response['message'] = 'Comment added successfully';
            $response['comment'] = [
                'comment_id' => $commentId,
                'content' => $content,
                'created_at' => date('Y-m-d H:i:s'),
                'user_name' => $userData['full_name'],
                'user_image' => $userData['profile_picture'] ? PROFILE_PIC_PATH . $userData['profile_picture'] : null
            ];
        } else {
            $response['message'] = 'Failed to add comment';
        }
    }
    
    // Add subtask
    elseif ($action === 'add_subtask') {
        $taskId = (int)($_POST['task_id'] ?? 0);
        $title = sanitize($_POST['title'] ?? '');
        
        // Validate input
        if (!$taskId || empty($title)) {
            $response['message'] = 'Invalid input';
            sendResponse($response);
        }
        
        // Check if the user owns this task
        $sql = "SELECT * FROM tasks WHERE task_id = ? AND user_id = ? AND is_deleted = 0";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $taskId, $userId);
        $stmt->execute();
        $task = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$task) {
            $response['message'] = 'Task not found or access denied';
            sendResponse($response);
        }
        
        // Add the subtask
        $sql = "INSERT INTO subtasks (task_id, title) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $taskId, $title);
        $result = $stmt->execute();
        $subtaskId = $conn->insert_id;
        $stmt->close();
        
        if ($result) {
            // Log the activity
            logActivity($userId, 'create', 'subtask', $subtaskId, "Added subtask to task #$taskId");
            
            $response['success'] = true;
            $response['message'] = 'Subtask added successfully';
            $response['subtask'] = [
                'subtask_id' => $subtaskId,
                'title' => $title,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ];
        } else {
            $response['message'] = 'Failed to add subtask';
        }
    }
    
    // Update subtask status
    elseif ($action === 'update_subtask_status') {
        $subtaskId = (int)($_POST['subtask_id'] ?? 0);
        $status = sanitize($_POST['status'] ?? '');
        
        // Validate input
        if (!$subtaskId || !in_array($status, ['pending', 'completed'])) {
            $response['message'] = 'Invalid input';
            sendResponse($response);
        }
        
        // Check if the user owns the parent task
        $sql = "SELECT t.* FROM tasks t 
                JOIN subtasks s ON t.task_id = s.task_id 
                WHERE s.subtask_id = ? AND t.user_id = ? AND t.is_deleted = 0";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $subtaskId, $userId);
        $stmt->execute();
        $task = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$task) {
            $response['message'] = 'Subtask not found or access denied';
            sendResponse($response);
        }
        
        // Update the subtask status
        $sql = "UPDATE subtasks SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE subtask_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $subtaskId);
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            // Log the activity
            $actionType = $status === 'completed' ? 'complete' : 'update';
            logActivity($userId, $actionType, 'subtask', $subtaskId, "Changed subtask status to $status");
            
            $response['success'] = true;
            $response['message'] = "Subtask status updated to $status";
        } else {
            $response['message'] = 'Failed to update subtask status';
        }
    }
    
    // Delete subtask
    elseif ($action === 'delete_subtask') {
        $subtaskId = (int)($_POST['subtask_id'] ?? 0);
        
        // Validate input
        if (!$subtaskId) {
            $response['message'] = 'Invalid input';
            sendResponse($response);
        }
        
        // Check if the user owns the parent task
        $sql = "SELECT t.* FROM tasks t 
                JOIN subtasks s ON t.task_id = s.task_id 
                WHERE s.subtask_id = ? AND t.user_id = ? AND t.is_deleted = 0";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $subtaskId, $userId);
        $stmt->execute();
        $task = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$task) {
            $response['message'] = 'Subtask not found or access denied';
            sendResponse($response);
        }
        
        // Delete the subtask
        $sql = "DELETE FROM subtasks WHERE subtask_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $subtaskId);
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            // Log the activity
            logActivity($userId, 'delete', 'subtask', $subtaskId, "Deleted subtask from task #{$task['task_id']}");
            
            $response['success'] = true;
            $response['message'] = 'Subtask deleted successfully';
        } else {
            $response['message'] = 'Failed to delete subtask';
        }
    }
    
    // Delete comment
    elseif ($action === 'delete_comment') {
        $commentId = (int)($_POST['comment_id'] ?? 0);
        
        // Validate input
        if (!$commentId) {
            $response['message'] = 'Invalid input';
            sendResponse($response);
        }
        
        // Check if the user owns the comment or the parent task
        $sql = "SELECT c.*, t.user_id as task_owner_id 
                FROM comments c 
                JOIN tasks t ON c.task_id = t.task_id 
                WHERE c.comment_id = ? AND (c.user_id = ? OR t.user_id = ?) AND t.is_deleted = 0";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $commentId, $userId, $userId);
        $stmt->execute();
        $comment = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$comment) {
            $response['message'] = 'Comment not found or access denied';
            sendResponse($response);
        }
        
        // Delete the comment
        $sql = "DELETE FROM comments WHERE comment_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $commentId);
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            // Log the activity
            logActivity($userId, 'delete', 'comment', $commentId, "Deleted comment from task #{$comment['task_id']}");
            
            $response['success'] = true;
            $response['message'] = 'Comment deleted successfully';
        } else {
            $response['message'] = 'Failed to delete comment';
        }
    }
    
    // Delete attachment
    elseif ($action === 'delete_attachment') {
        $attachmentId = (int)($_POST['attachment_id'] ?? 0);
        
        // Validate input
        if (!$attachmentId) {
            $response['message'] = 'Invalid input';
            sendResponse($response);
        }
        
        // Check if the user owns the attachment or the parent task
        $sql = "SELECT a.*, t.user_id as task_owner_id, a.file_path
                FROM attachments a
                JOIN tasks t ON a.task_id = t.task_id 
                WHERE a.attachment_id = ? AND (a.user_id = ? OR t.user_id = ?) AND t.is_deleted = 0";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $attachmentId, $userId, $userId);
        $stmt->execute();
        $attachment = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$attachment) {
            $response['message'] = 'Attachment not found or access denied';
            sendResponse($response);
        }
        
        // Delete the attachment from the database
        $sql = "DELETE FROM attachments WHERE attachment_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $attachmentId);
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            // Delete the file from the server
            $filePath = ATTACHMENTS_PATH . $attachment['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // Log the activity
            logActivity($userId, 'delete', 'attachment', $attachmentId, "Deleted attachment from task #{$attachment['task_id']}");
            
            $response['success'] = true;
            $response['message'] = 'Attachment deleted successfully';
        } else {
            $response['message'] = 'Failed to delete attachment';
        }
    }
    
    // Upload attachment
    elseif ($action === 'upload_attachment') {
        require_once '../utils/file_handler.php';
        
        $taskId = (int)($_POST['task_id'] ?? 0);
        
        // Validate input
        if (!$taskId || !isset($_FILES['attachment'])) {
            $response['message'] = 'Invalid input';
            sendResponse($response);
        }
        
        // Check if the user has access to this task
        $sql = "SELECT * FROM tasks WHERE task_id = ? AND (user_id = ? OR task_id IN 
                (SELECT entity_id FROM collaborators WHERE entity_type = 'task' AND user_id = ?)) AND is_deleted = 0";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $taskId, $userId, $userId);
        $stmt->execute();
        $task = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$task) {
            $response['message'] = 'Task not found or access denied';
            sendResponse($response);
        }
        
        // Upload the attachment
        $uploadResult = uploadAttachment($_FILES['attachment'], $taskId, $userId);
        
        if ($uploadResult['success']) {
            $response['success'] = true;
            $response['message'] = 'Attachment uploaded successfully';
            $response['attachment'] = $uploadResult;
        } else {
            $response['message'] = $uploadResult['message'] ?? 'Failed to upload attachment';
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
    
    // Get task details
    if ($action === 'get_task') {
        $taskId = (int)($_GET['task_id'] ?? 0);
        
        // Validate input
        if (!$taskId) {
            $response['message'] = 'Invalid input';
            sendResponse($response);
        }
        
        // Check if the user has access to this task
        $sql = "SELECT t.*, l.title as list_title, l.color as list_color 
                FROM tasks t 
                LEFT JOIN lists l ON t.list_id = l.list_id 
                WHERE t.task_id = ? AND (t.user_id = ? OR t.task_id IN 
                (SELECT entity_id FROM collaborators WHERE entity_type = 'task' AND user_id = ?)) 
                AND t.is_deleted = 0";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $taskId, $userId, $userId);
        $stmt->execute();
        $task = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$task) {
            $response['message'] = 'Task not found or access denied';
            sendResponse($response);
        }
        
        // Get task tags
        $sql = "SELECT t.* FROM tags t 
                JOIN task_tags tt ON t.tag_id = tt.tag_id 
                WHERE tt.task_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $taskId);
        $stmt->execute();
        $tags = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Get subtasks
        $sql = "SELECT * FROM subtasks WHERE task_id = ? ORDER BY created_at ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $taskId);
        $stmt->execute();
        $subtasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Get comments
        $sql = "SELECT c.*, u.full_name, u.profile_picture 
                FROM comments c 
                JOIN users u ON c.user_id = u.user_id 
                WHERE c.task_id = ? 
                ORDER BY c.created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $taskId);
        $stmt->execute();
        $comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Get attachments
        $sql = "SELECT * FROM attachments WHERE task_id = ? ORDER BY created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $taskId);
        $stmt->execute();
        $attachments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Build response
        $response['success'] = true;
        $response['task'] = $task;
        $response['tags'] = $tags;
        $response['subtasks'] = $subtasks;
        $response['comments'] = $comments;
        $response['attachments'] = $attachments;
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

function canModifyTask($conn, $userId, $listId) {
    $sql = "SELECT 'allowed' as allowed FROM (
        SELECT user_id, 'owner' as permission FROM lists WHERE list_id = ?
        UNION
        SELECT user_id, permission FROM list_collaborators WHERE list_id = ? AND permission IN ('edit', 'admin')
    ) permissions WHERE user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $listId, $listId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}