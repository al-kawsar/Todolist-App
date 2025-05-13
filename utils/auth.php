<?php
// utils/auth.php - Authentication and authorization utilities

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

// Register a new user
function registerUser($username, $email, $password, $fullName) {
    global $conn;
    
    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Default values
    $defaultProfilePic = 'pp.png';
    $created_at = date('Y-m-d H:i:s');
    
    // Insert the user with default profile picture
    $sql = "INSERT INTO users (username, email, password, full_name, profile_picture, created_at) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $username, $email, $hashedPassword, $fullName, $defaultProfilePic, $created_at);
    
    $result = $stmt->execute();
    $userId = $conn->insert_id;
    $stmt->close();
    
    if ($result) {
        // Log activity
        logActivity($userId, 'create', 'user', $userId);
        
        // Set session with basic user info and defaults
        $_SESSION['user_id'] = $userId;
        $_SESSION['user'] = [
            'user_id' => $userId,
            'username' => $username,
            'email' => $email,
            'full_name' => $fullName,
            'role' => 'regular',
            'profile_picture' => $defaultProfilePic,
            'created_at' => $created_at,
            'last_login' => $created_at,
            'updated_at' => $created_at
        ];
        
        // Fetch full user data from database to get all fields
        $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $userResult = $stmt->get_result();
        if ($userResult->num_rows > 0) {
            // Merge database data with session data
            $userFromDb = $userResult->fetch_assoc();
            $_SESSION['user'] = array_merge($_SESSION['user'], $userFromDb);
        }
        
        return $userId;
    }
    
    return false;
}

// Authenticate a user
function loginUser($username, $password) {
    global $conn;
    
    $sql = "SELECT * FROM users WHERE (username = ? OR email = ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // Update last login time
            $updateSql = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("i", $user['user_id']);
            $updateStmt->execute();
            $updateStmt->close();
            
            // Log the login activity
            logActivity($user['user_id'], 'login', 'user', $user['user_id']);
            
            // Store user data in session (except password)
            unset($user['password']);
            $_SESSION['user'] = $user;
            $_SESSION['user_id'] = $user['user_id'];
            
            // Fetch full user data from database to get all fields
            $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user['user_id']);
            $stmt->execute();
            $userResult = $stmt->get_result();
            if ($userResult->num_rows > 0) {
                // Merge database data with session data
                $userFromDb = $userResult->fetch_assoc();
                $_SESSION['user'] = array_merge($_SESSION['user'], $userFromDb);
            }
            
            return $user;
        }
    }
        
    return false;
}

// Logout the current user
function logoutUser() {
    // Unset all of the session variables
    $_SESSION = [];
    
    // If it's desired to kill the session, also delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Finally, destroy the session
    session_destroy();
}

// Check if user has permission to access a resource
function hasPermission($entityType, $entityId, $requiredPermission = 'view') {
    global $conn;
    $userId = getCurrentUserId();
    
    if (!$userId) {
        return false;
    }
    
    // If user is the owner, they have all permissions
    if ($entityType === 'list') {
        $sql = "SELECT user_id FROM lists WHERE list_id = ?";
    } elseif ($entityType === 'task') {
        $sql = "SELECT user_id FROM tasks WHERE task_id = ?";
    } else {
        return false;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $entityId);
    $stmt->execute();
    $result = $stmt->get_result();
    $entity = $result->fetch_assoc();
    $stmt->close();
    
    if ($entity && $entity['user_id'] === $userId) {
        return true;
    }
    
    // Check if user is a collaborator
    $sql = "SELECT permission FROM collaborators 
            WHERE entity_type = ? AND entity_id = ? AND user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $entityType, $entityId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $collaboration = $result->fetch_assoc();
    $stmt->close();
    
    if ($collaboration) {
        $permissions = ['view' => 1, 'edit' => 2, 'admin' => 3];
        $userPermLevel = $permissions[$collaboration['permission']];
        $requiredPermLevel = $permissions[$requiredPermission];
        
        return $userPermLevel >= $requiredPermLevel;
    }
    
    return false;
}

/**
 * Check if the current logged in user is an admin
 * 
 * @return bool True if user is admin, false otherwise
 */
function isAdmin() {
    // Pastikan user sudah login
    if (!isLoggedIn()) {
        return false;
    }
    
    // Cek role user dari session
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
}

// Uncomment and keep these default fallbacks
$currentUser['profile_picture'] = $currentUser['profile_picture'] ?? 'assets/img/pp.png';
$currentUser['created_at'] = $currentUser['created_at'] ?? date('Y-m-d H:i:s');