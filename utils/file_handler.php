<?php
// utils/file_handler.php - File upload and management utilities

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';


/**
 * Upload a profile picture
 * 
 * @param array $file The file from $_FILES
 * @param int $userId The user ID
 * @return array Result with success status and message
 */
function uploadProfilePicture($file, $userId) {
    debug_log("=== Start Profile Picture Upload ===");
    debug_log("User ID: " . $userId);
    debug_log("File details: " . print_r($file, true));
    debug_log("Upload path: " . PROFILE_PIC_PATH);
    
    // Check if upload directory exists and is writable
    if (!file_exists(UPLOAD_PATH)) {
        debug_log("Creating base upload directory: " . UPLOAD_PATH);
        if (!mkdir(UPLOAD_PATH, 0755, true)) {
            debug_log("Failed to create base upload directory");
            return [
                'success' => false,
                'message' => 'Failed to create upload directory'
            ];
        }
    }
    
    if (!file_exists(PROFILE_PIC_PATH)) {
        debug_log("Creating profile pictures directory: " . PROFILE_PIC_PATH);
        if (!mkdir(PROFILE_PIC_PATH, 0755, true)) {
            debug_log("Failed to create profile pictures directory");
            return [
                'success' => false,
                'message' => 'Failed to create profile pictures directory'
            ];
        }
    }
    
    // Ensure directory is writable
    if (!is_writable(PROFILE_PIC_PATH)) {
        debug_log("Setting permissions for: " . PROFILE_PIC_PATH);
        chmod(PROFILE_PIC_PATH, 0755);
        if (!is_writable(PROFILE_PIC_PATH)) {
            debug_log("Directory still not writable after chmod");
            return [
                'success' => false,
                'message' => 'Upload directory is not writable'
            ];
        }
    }

    // Check if file is valid
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = getFileUploadErrorMessage($file['error']);
        debug_log("Upload error: " . $error);
        return [
            'success' => false,
            'message' => $error
        ];
    }
    
    // Check file size (max 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        return [
            'success' => false,
            'message' => 'File size exceeds the maximum limit of 2MB'
        ];
    }
    
    // Check file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $fileType = mime_content_type($file['tmp_name']);
    
    if (!in_array($fileType, $allowedTypes)) {
        return [
            'success' => false,
            'message' => 'Only JPEG, PNG, GIF, and WebP images are allowed'
        ];
    }

    // Validate image dimensions
    list($width, $height) = getimagesize($file['tmp_name']);
    if ($width < 100 || $height < 100) {
        return [
            'success' => false,
            'message' => 'Image dimensions must be at least 100x100 pixels'
        ];
    }

    // Generate filename with more uniqueness
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'user_' . $userId . '_' . time() . '_' . uniqid() . '.' . $extension;
    $filePath = rtrim(PROFILE_PIC_PATH, '/') . '/' . $filename;
    
    debug_log("Attempting to upload to: $filePath");
    
    // Try to move the uploaded file
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        debug_log("File successfully uploaded to: $filePath");
        chmod($filePath, 0644); // Set proper permissions
        
        return [
            'success' => true,
            'filename' => $filename,
            'message' => 'Profile picture uploaded successfully'
        ];
    } else {
        $uploadError = error_get_last();
        debug_log("Failed to move uploaded file. PHP Error: " . print_r($uploadError, true));
        return [
            'success' => false,
            'message' => 'Failed to upload the profile picture'
        ];
    }
}

/**
 * Process and optimize profile image
 * 
 * @param string $sourcePath Path to the source image
 * @param string $fileType MIME type of the image
 * @return resource|false Returns the processed image resource or false on failure
 */
function processProfileImage($sourcePath, $fileType) {
    // Load the image based on type
    $source = null;
    switch ($fileType) {
        case 'image/jpeg':
            $source = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $source = imagecreatefrompng($sourcePath);
            break;
        case 'image/gif':
            $source = imagecreatefromgif($sourcePath);
            break;
        case 'image/webp':
            $source = imagecreatefromwebp($sourcePath);
            break;
        default:
            return false;
    }

    if (!$source) {
        return false;
    }

    // Get original dimensions
    $width = imagesx($source);
    $height = imagesy($source);

    // Calculate new dimensions (max 500x500, maintaining aspect ratio)
    $maxDimension = 500;
    if ($width > $maxDimension || $height > $maxDimension) {
        if ($width > $height) {
            $newWidth = $maxDimension;
            $newHeight = intval($height * ($maxDimension / $width));
        } else {
            $newHeight = $maxDimension;
            $newWidth = intval($width * ($maxDimension / $height));
        }
    } else {
        $newWidth = $width;
        $newHeight = $height;
    }

    // Create new image with new dimensions
    $processed = imagecreatetruecolor($newWidth, $newHeight);

    // Preserve transparency for PNG images
    if ($fileType === 'image/png') {
        imagealphablending($processed, false);
        imagesavealpha($processed, true);
    }

    // Resize the image
    imagecopyresampled(
        $processed, $source,
        0, 0, 0, 0,
        $newWidth, $newHeight,
        $width, $height
    );

    // Free up memory
    imagedestroy($source);

    return $processed;
}


/**
 * Save processed image to file
 * 
 * @param GdImage $image The image to save
 * @param string $filePath Path where to save the image
 * @param string $fileType MIME type of the image
 * @return bool Success status
 */
function saveProcessedImage(GdImage $image, string $filePath, string $fileType): bool {
    // Validate image resource
    if (!$image || !is_resource($image)) {
        return false;
    }

    switch ($fileType) {
        case 'image/jpeg':
            return imagejpeg($image, $filePath, 85);
        case 'image/png':
            return imagepng($image, $filePath, 8);
        case 'image/gif':
            return imagegif($image, $filePath);
        case 'image/webp':
            if (!function_exists('imagewebp')) {
                debug_log("WebP support is not available");
                // Fall back to PNG if WebP is not supported
                return imagepng($image, $filePath, 8);
            }
            return imagewebp($image, $filePath, 85);
        default:
            debug_log("Unsupported image type: " . $fileType);
            return false;
    }
}

function uploadAttachment($file, $taskId, $userId) {
    // Check if file is valid
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [
            'success' => false,
            'message' => getFileUploadErrorMessage($file['error'])
        ];
    }
    
    // Check file size (max 10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        return [
            'success' => false,
            'message' => 'File size exceeds the maximum limit of 10MB'
        ];
    }
    
    // Create attachments directory if it doesn't exist
    $attachmentsDir = rtrim(ATTACHMENTS_PATH, '/');
    if (!file_exists($attachmentsDir)) {
        mkdir($attachmentsDir, 0755, true);
    }
    
    // Generate filename
    $originalName = $file['name'];
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $filename = 'task_' . $taskId . '_' . time() . '_' . generateRandomString(8) . '.' . $extension;
    $filePath = $attachmentsDir . '/' . $filename;
    
    // Get file type
    $fileType = mime_content_type($file['tmp_name']);
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        // Add attachment to database
        global $conn;
        $sql = "INSERT INTO attachments (task_id, user_id, file_name, file_path, file_type, file_size) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisssi", $taskId, $userId, $originalName, $filename, $fileType, $file['size']);
        $stmt->execute();
        $attachmentId = $conn->insert_id;
        $stmt->close();
        
        // Log activity
        logActivity($userId, 'create', 'attachment', $attachmentId, "Added attachment to task #$taskId");
        
        return [
            'success' => true,
            'id' => $attachmentId,
            'filename' => $filename,
            'original_name' => $originalName,
            'type' => $fileType,
            'size' => $file['size'],
            'message' => 'Attachment uploaded successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to upload the attachment'
        ];
    }
}

/**
 * Remove old profile picture for a user
 * 
 * @param int $userId The user ID
 * @return bool Success status
 */
function removeOldProfilePicture($userId) {
    global $conn;
    
    // Get current profile picture
    $sql = "SELECT profile_picture FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // If user has a profile picture, delete it
    if ($user && $user['profile_picture']) {
        $filePath = rtrim(PROFILE_PIC_PATH, '/') . '/' . $user['profile_picture'];
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
    }
    
    return true;
}

/**
 * Get error message for file upload errors
 * 
 * @param int $errorCode The error code from file upload
 * @return string The error message
 */
function getFileUploadErrorMessage($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
            return 'File yang diunggah melebihi batas upload_max_filesize di php.ini';
        case UPLOAD_ERR_FORM_SIZE:
            return 'File yang diunggah melebihi batas MAX_FILE_SIZE yang ditentukan dalam form HTML';
        case UPLOAD_ERR_PARTIAL:
            return 'File hanya terunggah sebagian';
        case UPLOAD_ERR_NO_FILE:
            return 'Tidak ada file yang diunggah';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Folder temporary tidak ditemukan';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Gagal menyimpan file ke disk';
        case UPLOAD_ERR_EXTENSION:
            return 'Ekstensi PHP menghentikan proses unggah file';
        default:
            return 'Terjadi kesalahan yang tidak diketahui';
    }
}