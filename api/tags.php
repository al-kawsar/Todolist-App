<?php
// api/tags.php - Tags API endpoints
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
    
    // Delete tag
    if ($action === 'delete_tag') {
        $tagId = (int)($_POST['tag_id'] ?? 0);
        
        // Validate input
        if (!$tagId) {
            $response['message'] = 'Invalid input';
            sendResponse($response);
        }
        
        // Check if the user owns this tag
        $sql = "SELECT * FROM tags WHERE tag_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $tagId, $userId);
        $stmt->execute();
        $tag = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$tag) {
            $response['message'] = 'Tag not found or access denied';
            sendResponse($response);
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Delete associations in task_tags
            $sql = "DELETE FROM task_tags WHERE tag_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $tagId);
            $assocResult = $stmt->execute();
            $stmt->close();
            
            // Delete the tag
            $sql = "DELETE FROM tags WHERE tag_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $tagId);
            $tagResult = $stmt->execute();
            $stmt->close();
            
            // If both operations succeeded
            if ($assocResult && $tagResult) {
                $conn->commit();
                
                // Log activity
                logActivity($userId, 'delete', 'tag', $tagId, "Deleted tag '{$tag['name']}'");
                
                $response['success'] = true;
                $response['message'] = 'Tag deleted successfully';
            } else {
                $conn->rollback();
                $response['message'] = 'Failed to delete tag';
            }
        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = 'Error: ' . $e->getMessage();
        }
    }
    
    // Update tag
    elseif ($action === 'update_tag') {
        $tagId = (int)($_POST['tag_id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $color = sanitize($_POST['color'] ?? '#3498db');
        
        // Validate input
        if (!$tagId || empty($name)) {
            $response['message'] = 'Invalid input';
            sendResponse($response);
        }
        
        // Check if the user owns this tag
        $sql = "SELECT * FROM tags WHERE tag_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $tagId, $userId);
        $stmt->execute();
        $tag = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$tag) {
            $response['message'] = 'Tag not found or access denied';
            sendResponse($response);
        }
        
        // Check if tag name already exists (excluding current tag)
        $sql = "SELECT tag_id FROM tags WHERE user_id = ? AND name = ? AND tag_id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isi", $userId, $name, $tagId);
        $stmt->execute();
        $existingTag = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($existingTag) {
            $response['message'] = 'Tag name already exists';
            sendResponse($response);
        }
        
        // Update the tag
        $sql = "UPDATE tags SET name = ?, color = ? WHERE tag_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $name, $color, $tagId);
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            // Log activity
            logActivity($userId, 'update', 'tag', $tagId, "Updated tag from '{$tag['name']}' to '$name'");
            
            $response['success'] = true;
            $response['message'] = 'Tag updated successfully';
        } else {
            $response['message'] = 'Failed to update tag';
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
    
    // Get all tags
    if ($action === 'get_all_tags') {
        $sql = "SELECT * FROM tags WHERE user_id = ? ORDER BY name ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $tags = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        $response['success'] = true;
        $response['tags'] = $tags;
    }
    
    // Get tags for a specific task
    elseif ($action === 'get_task_tags') {
        $taskId = (int)($_GET['task_id'] ?? 0);
        
        // Validate input
        if (!$taskId) {
            $response['message'] = 'Invalid input';
            sendResponse($response);
        }
        
        // Check if the user has access to this task
        $sql = "SELECT * FROM tasks WHERE task_id = ? AND (user_id = ? OR task_id IN 
                (SELECT entity_id FROM collaborators WHERE entity_type = 'task' AND user_id = ?))";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $taskId, $userId, $userId);
        $stmt->execute();
        $task = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$task) {
            $response['message'] = 'Task not found or access denied';
            sendResponse($response);
        }
        
        // Get tags for the task
        $sql = "SELECT t.* FROM tags t 
                JOIN task_tags tt ON t.tag_id = tt.tag_id 
                WHERE tt.task_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $taskId);
        $stmt->execute();
        $tags = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        $response['success'] = true;
        $response['tags'] = $tags;
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