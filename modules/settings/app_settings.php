<?php
// modules/settings/app_settings.php - Pengaturan aplikasi
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/functions.php';
require_once '../../utils/auth.php';

// Cek apakah user sudah login dan adalah admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../../login.php');
}

// Inisialisasi variabel
$errors = [];
$success = false;
$successMessage = '';

function getSetting($key) {
    global $conn;
    $stmt = $conn->prepare("SELECT setting_value FROM app_settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row ? $row['setting_value'] : null;
}

function updateSetting($key, $value, $userId) {
    global $conn;
    try {
        // Cek apakah setting sudah ada
        $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM app_settings WHERE setting_key = ?");
        $checkStmt->bind_param("s", $key);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $exists = $result->fetch_assoc()['count'] > 0;
        
        if ($exists) {
            // Update jika sudah ada
            $stmt = $conn->prepare("UPDATE app_settings SET setting_value = ?, updated_by = ?, updated_at = CURRENT_TIMESTAMP WHERE setting_key = ?");
            $stmt->bind_param("sis", $value, $userId, $key);
        } else {
            // Insert jika belum ada
            $stmt = $conn->prepare("INSERT INTO app_settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $key, $value, $userId);
        }
        
        $success = $stmt->execute();
        if ($success) {
            debug_log("Setting updated successfully: $key = $value");
        } else {
            debug_log("Failed to update setting: $key. Error: " . $stmt->error);
        }
        return $success;
    } catch (Exception $e) {
        debug_log("Error updating setting: " . $e->getMessage());
        return false;
    }
}

// Get current settings from database
$currentSettings = [
    'app_name' => getSetting('app_name'),
    'app_version' => getSetting('app_version'),
    'base_url' => getSetting('base_url'),
    'timezone' => getSetting('timezone'),
    'upload_max_size' => getSetting('upload_max_size'),
    'allowed_file_types' => getSetting('allowed_file_types'),
    'maintenance_mode' => getSetting('maintenance_mode'),
    'maintenance_message' => getSetting('maintenance_message')
];

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_settings'])) {
        $userId = $_SESSION['user']['user_id'];
        
        foreach ($_POST as $key => $value) {
            if (array_key_exists($key, $currentSettings)) {
                if ($key === 'maintenance_mode') {
                    $value = isset($_POST['maintenance_mode']) ? '1' : '0';
                    debug_log("Setting maintenance mode to: $value");
                }
                if (updateSetting($key, $value, $userId)) {
                    $currentSettings[$key] = $value;
                    $success = true;
                    $successMessage = 'Pengaturan berhasil diperbarui';
                    
                    // Log activity
                    logActivity($userId, 'update', 'settings', 0, "Updated setting: $key = $value");
                }
            }
        }
    }
}

// Ambil daftar timezone
$timezones = DateTimeZone::listIdentifiers();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Aplikasi - <?= APP_NAME ?></title>
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="../../assets/css/output.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../../node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <!-- Toastify -->
    <link rel="stylesheet" href="../../node_modules/toastify-js/src/toastify.css">
    <script src="../../node_modules/toastify-js/src/toastify.js"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <?php include_once '../../includes/header.php'; ?>

        <div class="flex-grow flex">
            <!-- Sidebar -->
            <?php include_once '../../includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="flex-1 overflow-auto">
                <div class="container mx-auto px-4 py-8">
                    <div class="max-w-4xl mx-auto">
                        <div class="bg-white shadow rounded-lg overflow-hidden">
                            <div class="p-6">
                                <h2 class="text-2xl font-bold text-gray-900 mb-6">Pengaturan Aplikasi</h2>

                                <?php if ($success): ?>
                                    <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                                        <?= $successMessage ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($errors['general'])): ?>
                                    <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                                        <?= $errors['general'] ?>
                                    </div>
                                <?php endif; ?>

                                <form action="app_settings.php" method="POST">
                                    <!-- Nama Aplikasi -->
                                    <div class="mb-6">
                                        <label for="app_name" class="block text-sm font-medium text-gray-700 mb-1">
                                            Nama Aplikasi
                                        </label>
                                        <input type="text" id="app_name" name="app_name" 
                                            value="<?= htmlspecialchars($currentSettings['app_name']) ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                        <?php if (!empty($errors['app_name'])): ?>
                                            <p class="mt-1 text-sm text-red-600"><?= $errors['app_name'] ?></p>
                                        <?php endif; ?>
                                    </div>

                                    <!-- URL Dasar -->
                                    <!-- <div class="mb-6">
                                        <label for="base_url" class="block text-sm font-medium text-gray-700 mb-1">
                                            URL Dasar
                                        </label>
                                        <input type="url" id="base_url" name="base_url" 
                                            value="<?= htmlspecialchars($currentSettings['base_url']) ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                        <?php if (!empty($errors['base_url'])): ?>
                                            <p class="mt-1 text-sm text-red-600"><?= $errors['base_url'] ?></p>
                                        <?php endif; ?>
                                    </div> -->

                                    <!-- Zona Waktu -->
                                    <!-- <div class="mb-6">
                                        <label for="timezone" class="block text-sm font-medium text-gray-700 mb-1">
                                            Zona Waktu
                                        </label>
                                        <select id="timezone" name="timezone" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                            <?php foreach ($timezones as $tz): ?>
                                                <option value="<?= htmlspecialchars($tz) ?>" 
                                                    <?= $tz === $currentSettings['timezone'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($tz) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (!empty($errors['timezone'])): ?>
                                            <p class="mt-1 text-sm text-red-600"><?= $errors['timezone'] ?></p>
                                        <?php endif; ?>
                                    </div> -->

                                    <!-- Ukuran Maksimal Upload -->
                                    <!-- <div class="mb-6">
                                        <label for="upload_max_size" class="block text-sm font-medium text-gray-700 mb-1">
                                            Ukuran Maksimal Upload (MB)
                                        </label>
                                        <input type="number" id="upload_max_size" name="upload_max_size" 
                                            value="<?= htmlspecialchars($currentSettings['upload_max_size']) ?>"
                                            min="1" max="100"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                        <?php if (!empty($errors['upload_max_size'])): ?>
                                            <p class="mt-1 text-sm text-red-600"><?= $errors['upload_max_size'] ?></p>
                                        <?php endif; ?>
                                    </div> -->

                                    <!-- Tipe File yang Diizinkan -->
                                    <!-- <div class="mb-6">
                                        <label for="allowed_file_types" class="block text-sm font-medium text-gray-700 mb-1">
                                            Tipe File yang Diizinkan (pisahkan dengan koma)
                                        </label>
                                        <input type="text" id="allowed_file_types" name="allowed_file_types" 
                                            value="<?= htmlspecialchars($currentSettings['allowed_file_types']) ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                        <p class="mt-1 text-sm text-gray-500">Contoh: jpg,jpeg,png,gif,webp</p>
                                    </div> -->

                                    <!-- Mode Maintenance -->
                                    <div class="mb-6">
                                        <div class="flex items-center">
                                            <input type="checkbox" id="maintenance_mode" name="maintenance_mode" 
                                                <?= $currentSettings['maintenance_mode'] === '1' ? 'checked' : '' ?>
                                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                            <label for="maintenance_mode" class="ml-2 block text-sm text-gray-900">
                                                Mode Maintenance
                                            </label>
                                        </div>
                                        <p class="mt-1 text-sm text-gray-500">
                                            Jika diaktifkan, hanya admin yang dapat mengakses aplikasi
                                        </p>
                                    </div>

                                    <!-- Setelah form maintenance mode, tambahkan ini -->
                                    <div class="mt-2 text-sm">
                                        Status Maintenance Mode saat ini: 
                                        <span class="font-semibold <?= $currentSettings['maintenance_mode'] === '1' ? 'text-red-600' : 'text-green-600' ?>">
                                            <?= $currentSettings['maintenance_mode'] === '1' ? 'Aktif' : 'Nonaktif' ?>
                                        </span>
                                        
                                    </div>

                                    <!-- Setelah checkbox maintenance mode -->
                                    <div class="mt-4">
                                        <label for="maintenance_message" class="block text-sm font-medium text-gray-700 mb-1">
                                            Pesan Maintenance
                                        </label>
                                        <textarea id="maintenance_message" name="maintenance_message" rows="3"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        ><?= htmlspecialchars($currentSettings['maintenance_message'] ?? 'Sistem sedang dalam pemeliharaan. Silakan coba beberapa saat lagi.') ?></textarea>
                                    </div>

                                    <!-- Tambahkan sebelum tombol submit -->
                                    <!-- <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                                        <h3 class="text-sm font-medium text-gray-700 mb-2">Status Database:</h3>
                                        <?php
                                        $maintenanceStatus = getSetting('maintenance_mode');
                                        echo "<pre class='text-xs bg-white p-2 rounded'>";
                                        echo "maintenance_mode: " . var_export($maintenanceStatus, true) . "\n";
                                        echo "</pre>";
                                        ?>
                                    </div> -->

                                    <!-- Tombol Submit -->
                                    <div class="flex justify-end">
                                        <button type="submit" name="update_settings" 
                                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            <i class="fas fa-save mr-2"></i> Simpan Pengaturan
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <?php include_once '../../includes/footer.php'; ?>
    </div>

    <script>
        <?php if ($success): ?>
        Toastify({
            text: "<?= $successMessage ?>",
            duration: 3000,
            close: true,
            gravity: "top",
            position: "right",
            backgroundColor: "#10B981",
            stopOnFocus: true
        }).showToast();
        <?php endif; ?>
    </script>
</body>
</html>