<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/auth.php';

function isMaintenanceMode() {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT setting_value FROM app_settings WHERE setting_key = 'maintenance_mode'");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row && $row['setting_value'] === '1';
    } catch (Exception $e) {
        error_log("Error checking maintenance mode: " . $e->getMessage());
        return false;
    }
}

function getMaintenanceMessage() {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT setting_value FROM app_settings WHERE setting_key = 'maintenance_message'");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row ? $row['setting_value'] : 'Sistem sedang dalam pemeliharaan.';
    } catch (Exception $e) {
        error_log("Error getting maintenance message: " . $e->getMessage());
        return 'Sistem sedang dalam pemeliharaan.';
    }
}

function checkMaintenance() {
    if (!isMaintenanceMode()) {
        return;
    }

    // Jika user adalah admin, izinkan akses
    if (isset($_SESSION['user']) && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin') {
        return;
    }

    // Tampilkan halaman maintenance
    header('HTTP/1.1 503 Service Temporarily Unavailable');
    header('Status: 503 Service Temporarily Unavailable');
    header('Retry-After: 3600');
    
    echo '<!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Maintenance Mode - ' . APP_NAME . '</title>
        <link rel="stylesheet" href="' . BASE_URL . '/assets/css/output.css">
    </head>
    <body class="bg-gray-100 min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full mx-auto p-6">
            <div class="bg-white rounded-lg shadow-xl p-8 text-center">
                <div class="mb-6">
                    <i class="fas fa-tools text-6xl text-blue-500"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-4">Mode Pemeliharaan</h1>
                <p class="text-gray-600 mb-6">' . 
                getSettingValue('maintenance_message', 'Sistem sedang dalam pemeliharaan. Silakan coba beberapa saat lagi.') 
                . '</p>
            </div>
        </div>
    </body>
    </html>';
    exit;
} 