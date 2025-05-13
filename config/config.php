<?php
// config/config.php - General application configuration

// Buat direktori logs jika belum ada
$logDir = __DIR__ . '/../logs';
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}

// Set custom error log
ini_set('log_errors', 1);
ini_set('error_log', $logDir . '/debug.log');

// Fungsi untuk logging yang lebih mudah dibaca
function debug_log($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . "\n", 3, __DIR__ . '/../logs/debug.log');
}

// Load database connection first
require_once __DIR__ . '/database.php';

function getSettingValue($key, $default = null) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT setting_value FROM app_settings WHERE setting_key = ?");
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row ? $row['setting_value'] : $default;
    } catch (Exception $e) {
        error_log("Error getting setting $key: " . $e->getMessage());
        return $default;
    }
}

// Application settings dengan fallback ke nilai default
define('APP_NAME', getSettingValue('app_name', 'UKK TODOLIST'));
define('APP_VERSION', getSettingValue('app_version', '1.0.0'));
define('BASE_URL', getSettingValue('base_url', 'http://localhost:8002'));
define('DEFAULT_TIMEZONE', getSettingValue('timezone', 'Asia/Jakarta'));

// Set timezone
date_default_timezone_set(DEFAULT_TIMEZONE);

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS'])); // Set to 1 if using HTTPS

// Error reporting (turn off in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Definisikan path absolut untuk upload
define('UPLOAD_PATH', realpath(__DIR__ . '/../uploads'));
define('PROFILE_PIC_PATH', UPLOAD_PATH . '/profile_pictures');
define('ATTACHMENTS_PATH', UPLOAD_PATH . '/attachments');

// Buat direktori dengan permission yang benar
foreach ([UPLOAD_PATH, PROFILE_PIC_PATH, ATTACHMENTS_PATH] as $dir) {
    if (!file_exists($dir)) {
        if (!mkdir($dir, 0755, true)) {
            error_log("Failed to create directory: $dir");
        } else {
            error_log("Successfully created directory: $dir");
        }
    }
}

// Pastikan direktori dapat ditulis
foreach ([UPLOAD_PATH, PROFILE_PIC_PATH, ATTACHMENTS_PATH] as $dir) {
    if (!is_writable($dir)) {
        error_log("Directory not writable: $dir");
        chmod($dir, 0755);
    }
}

// Then load maintenance check
require_once __DIR__ . '/../utils/maintenance.php';

// Check maintenance mode last
checkMaintenance();