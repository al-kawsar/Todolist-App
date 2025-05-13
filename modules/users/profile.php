<?php
// modules/users/profile.php - Manajemen profil pengguna
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/functions.php';
require_once '../../utils/auth.php';
require_once '../../utils/file_handler.php';

// Redirect ke halaman login jika belum login
if (!isLoggedIn()) {
    redirect('../../login.php');
}

// Ambil data pengguna dari sesi
$currentUser = $_SESSION['user'];
$userId = $currentUser['user_id'];

// Ambil data lengkap pengguna dari database
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$userResult = $stmt->get_result();
if ($userResult->num_rows > 0) {
    // Gabungkan data dari database dengan data sesi
    $userFromDb = $userResult->fetch_assoc();
    $currentUser = array_merge($currentUser, $userFromDb);
}
$stmt->close();


// Inisialisasi variabel
$errors = [];
$success = false;
$formData = [
    'username' => $currentUser['username'],
    'email' => $currentUser['email'],
    'full_name' => $currentUser['full_name'],
];

// Proses pengiriman formulir
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Tangani pembaruan profil
    
    if (isset($_POST['update_profile'])) {
        // Ambil data formulir
        $formData = [
            'username' => sanitize($_POST['username'] ?? ''),
            'email' => sanitize($_POST['email'] ?? ''),
            'full_name' => sanitize($_POST['full_name'] ?? ''),
        ];
        
        // Validasi input
        if (empty($formData['username'])) {
            $errors['username'] = 'Nama pengguna wajib diisi';
        } elseif (strlen($formData['username']) < 3 || strlen($formData['username']) > 50) {
            $errors['username'] = 'Nama pengguna harus antara 3 dan 50 karakter';
        } elseif ($formData['username'] !== $currentUser['username']) {
            // Periksa apakah username sudah ada
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
            $stmt->bind_param("si", $formData['username'], $userId);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors['username'] = 'Nama pengguna sudah digunakan';
            }
            $stmt->close();
        }
        
        if (empty($formData['email'])) {
            $errors['email'] = 'Email wajib diisi';
        } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format email tidak valid';
        } elseif ($formData['email'] !== $currentUser['email']) {
            // Periksa apakah email sudah ada
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $stmt->bind_param("si", $formData['email'], $userId);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors['email'] = 'Email sudah digunakan';
            }
            $stmt->close();
        }
        
        if (empty($formData['full_name'])) {
            $errors['full_name'] = 'Nama lengkap wajib diisi';
        } elseif (strlen($formData['full_name']) > 100) {
            $errors['full_name'] = 'Nama lengkap tidak boleh lebih dari 100 karakter';
        }
        
        // Tangani unggah foto profil
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['size'] > 0) {
            $uploadResult = uploadProfilePicture($_FILES['profile_picture'], $userId);
            
            if (!$uploadResult['success']) {
                $errors['profile_picture'] = $uploadResult['message'];
            } else {
                // Simpan nama file lengkap
                $profilePicture = $uploadResult['filename'];
                debug_log("New profile picture filename: " . $profilePicture);
                
                // Update database
                $sql = "UPDATE users SET profile_picture = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $profilePicture, $userId);
                
                if ($stmt->execute()) {
                    // Update session dengan nama file baru
                    $_SESSION['user']['profile_picture'] = $profilePicture;
                    $currentUser['profile_picture'] = $profilePicture;
                    $success = true;
                    $successMessage = 'Foto profil berhasil diperbarui';
                    debug_log("Profile picture updated successfully in database");
                } else {
                    $errors['profile_picture'] = 'Gagal memperbarui foto profil dalam database';
                    debug_log("Failed to update profile picture in database: " . $conn->error);
                }
                $stmt->close();
            }
        }
        
        // Jika validasi berhasil, perbarui data pengguna
        if (empty($errors)) {
            $sql = "UPDATE users SET username = ?, email = ?, full_name = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $formData['username'], $formData['email'], $formData['full_name'], $userId);

            if ($stmt->execute()) {
                // Perbarui data sesi
                $_SESSION['user']['username'] = $formData['username'];
                $_SESSION['user']['email'] = $formData['email'];
                $_SESSION['user']['full_name'] = $formData['full_name'];
                
                // Perbarui data pengguna saat ini
                $currentUser['username'] = $formData['username'];
                $currentUser['email'] = $formData['email'];
                $currentUser['full_name'] = $formData['full_name'];
                
                // Catat aktivitas
                logActivity($userId, 'update', 'user', $userId, 'Memperbarui informasi profil');
                
                $success = true;
                $successMessage = 'Profil berhasil diperbarui';
            } else {
                $errors['general'] = 'Gagal memperbarui profil. Silakan coba lagi.';
            }
            
            $stmt->close();
        }
    }
    
    // Tangani perubahan kata sandi
    elseif (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validasi input
        if (empty($currentPassword)) {
            $errors['current_password'] = 'Kata sandi saat ini wajib diisi';
        } else {
            // Verifikasi kata sandi saat ini
            $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            if (!password_verify($currentPassword, $user['password'])) {
                $errors['current_password'] = 'Kata sandi saat ini salah';
            }
        }
        
        if (empty($newPassword)) {
            $errors['new_password'] = 'Kata sandi baru wajib diisi';
        } elseif (strlen($newPassword) < 6) {
            $errors['new_password'] = 'Kata sandi baru minimal 6 karakter';
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'Konfirmasi kata sandi tidak cocok';
        }
        
        // Jika validasi berhasil, perbarui kata sandi
        if (empty($errors)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $sql = "UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $hashedPassword, $userId);
            
            if ($stmt->execute()) {
                // Catat aktivitas
                logActivity($userId, 'update', 'user', $userId, 'Mengubah kata sandi');
                
                $success = true;
                $successMessage = 'Kata sandi berhasil diubah';
            } else {
                $errors['general'] = 'Gagal mengubah kata sandi. Silakan coba lagi.';
            }
            
            $stmt->close();
        }
    }
}

// Ambil log aktivitas pengguna
$sql = "SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$activityLogs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Ambil statistik pengguna
$userStats = getUserStats($userId);

// Fungsi untuk menghasilkan URL avatar
function generateAvatarUrl($name, $backgroundColor = '0D8ABC', $textColor = 'fff') {
    // Buat avatar berdasarkan inisial alih-alih menggunakan CDN eksternal
    // Ini menyimpan huruf pertama dari setiap kata dalam nama dan menggabungkannya
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $word) {
        $initials .= strtoupper(substr($word, 0, 1));
    }
    
    // Jika hanya ada satu inisial, gunakan dua huruf pertama
    if (strlen($initials) === 1 && strlen($words[0]) > 1) {
        $initials .= strtoupper(substr($words[0], 1, 1));
    }
    
    return $initials;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - <?= APP_NAME ?></title>
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="../../assets/css/output.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../../node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="../../node_modules/sweetalert2/dist/sweetalert2.min.js"></script>
    <link rel="stylesheet" href="../../node_modules/sweetalert2/dist/sweetalert2.min.css">
    <!-- Toastify JS -->
    <link rel="stylesheet" href="../../node_modules/toastify-js/src/toastify.css">
    <script src="../../node_modules/toastify-js/src/toastify.js"></script>
    <style>
        .avatar-circle {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 6rem;
            width: 6rem;
            border-radius: 9999px;
            background-color: #0D8ABC;
            color: white;
            font-weight: bold;
            font-size: 2rem;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <!-- Navigasi Atas -->
        <?php include_once '../../includes/header.php'; ?>
        
        <div class="flex-grow flex">
            <!-- Sidebar -->
            <?php include_once '../../includes/sidebar.php'; ?>
            
            <!-- Konten Utama -->
            <div class="flex-1 overflow-auto">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <h1 class="text-2xl font-bold text-gray-900 mb-6">Profil Saya</h1>
                    
                    <?php if ($success): ?>
                        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline"><?= $successMessage ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Informasi Profil Pengguna -->
                        <div class="lg:col-span-2">
                            <div class="bg-white shadow rounded-lg overflow-hidden">
                                <div class="p-6">
                                    <div class="flex flex-col md:flex-row items-center md:items-start mb-6">
                                        <div class="mb-4 md:mb-0 md:mr-6">
                                            <div class="relative">
                                                <?php if (!empty($currentUser['profile_picture'])): ?>
                                                    <?php
                                                    $profilePicFilename = basename($currentUser['profile_picture']);
                                                    debug_log("Profile picture filename: " . $profilePicFilename);
                                                    
                                                    $imageUrl = BASE_URL . '/uploads/profile_pictures/' . $profilePicFilename;
                                                    debug_log("Generated image URL: " . $imageUrl);
                                                    ?>
                                                    <img class="h-24 w-24 rounded-full object-cover" 
                                                        src="<?= htmlspecialchars($imageUrl) ?>" 
                                                        alt="<?= htmlspecialchars($currentUser['full_name']) ?>"
                                                        alt="<?= htmlspecialchars($currentUser['full_name']) ?>">
                                                <?php else: ?>
                                                    <div class="avatar-circle">
                                                        <?= generateAvatarUrl($currentUser['full_name']) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <button type="button" id="change-profile-pic-btn" class="absolute bottom-0 right-0 bg-blue-600 text-white rounded-full p-1 shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                    <i class="fas fa-camera"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="text-center md:text-left">
                                            <h2 class="text-xl font-semibold text-gray-900"><?= htmlspecialchars($currentUser['full_name']) ?></h2>
                                            <p class="text-gray-500">@<?= htmlspecialchars($currentUser['username']) ?></p>
                                            <p class="text-gray-500"><?= htmlspecialchars($currentUser['email']) ?></p>
                                            <p class="text-sm text-gray-500 mt-1">
                                                Akun <?= ucfirst($currentUser['role'] ?? 'Reguler') ?>
                                                <span class="mx-1">•</span>
                                                Bergabung sejak <?= date('M Y', strtotime($currentUser['created_at'])) ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-6">
                                        <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Profil</h3>
                                        
                                        <?php if (!empty($errors['general'])): ?>
                                            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                                                <span class="block sm:inline"><?= $errors['general'] ?></span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <form action="profile.php" method="POST" enctype="multipart/form-data">
                                            <!-- Input file tersembunyi untuk foto profil -->
                                            <input type="file" id="profile_picture" name="profile_picture" class="hidden" accept="image/*">
                                            
                                            <!-- Nama Pengguna -->
                                            <div class="mb-4">
                                                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Nama Pengguna</label>
                                                <input type="text" id="username" name="username" value="<?= htmlspecialchars($formData['username']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                                                <?php if (!empty($errors['username'])): ?>
                                                    <p class="mt-1 text-sm text-red-600"><?= $errors['username'] ?></p>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Email -->
                                            <div class="mb-4">
                                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                                <input type="email" id="email" name="email" value="<?= htmlspecialchars($formData['email']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                                                <?php if (!empty($errors['email'])): ?>
                                                    <p class="mt-1 text-sm text-red-600"><?= $errors['email'] ?></p>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Nama Lengkap -->
                                            <div class="mb-4">
                                                <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                                                <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($formData['full_name']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                                                <?php if (!empty($errors['full_name'])): ?>
                                                    <p class="mt-1 text-sm text-red-600"><?= $errors['full_name'] ?></p>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="flex justify-end">
                                                <button type="submit" name="update_profile" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                    <i class="fas fa-save mr-2"></i> Simpan Perubahan
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Bagian Ubah Kata Sandi -->
                            <div class="bg-white shadow rounded-lg overflow-hidden mt-6">
                                <div class="p-6">
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Ubah Kata Sandi</h3>
                                    
                                    <form action="profile.php" method="POST">
                                        <!-- Kata Sandi Saat Ini -->
                                        <div class="mb-4">
                                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Kata Sandi Saat Ini</label>
                                            <input type="password" id="current_password" name="current_password" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                                            <?php if (!empty($errors['current_password'])): ?>
                                                <p class="mt-1 text-sm text-red-600"><?= $errors['current_password'] ?></p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Kata Sandi Baru -->
                                        <div class="mb-4">
                                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">Kata Sandi Baru</label>
                                            <input type="password" id="new_password" name="new_password" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                                            <?php if (!empty($errors['new_password'])): ?>
                                                <p class="mt-1 text-sm text-red-600"><?= $errors['new_password'] ?></p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Konfirmasi Kata Sandi Baru -->
                                        <div class="mb-4">
                                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Kata Sandi Baru</label>
                                            <input type="password" id="confirm_password" name="confirm_password" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                                            <?php if (!empty($errors['confirm_password'])): ?>
                                                <p class="mt-1 text-sm text-red-600"><?= $errors['confirm_password'] ?></p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="flex justify-end">
                                            <button type="submit" name="change_password" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                <i class="fas fa-key mr-2"></i> Ubah Kata Sandi
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sidebar Statistik & Aktivitas -->
                        <div>
                            <!-- Statistik Pengguna -->
                            <div class="bg-white shadow rounded-lg overflow-hidden">
                                <div class="px-4 py-5 sm:p-6">
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Statistik Anda</h3>
                                    
                                    <div class="space-y-4">
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-500">Total Tugas</span>
                                            <span class="text-sm font-medium"><?= $userStats['total_tasks'] ?? 0 ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-500">Tugas Selesai</span>
                                            <span class="text-sm font-medium"><?= $userStats['completed_tasks'] ?? 0 ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-500">Tugas Tertunda</span>
                                            <span class="text-sm font-medium"><?= $userStats['pending_tasks'] ?? 0 ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-500">Tugas Dalam Proses</span>
                                            <span class="text-sm font-medium"><?= $userStats['in_progress_tasks'] ?? 0 ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-500">Total Daftar</span>
                                            <span class="text-sm font-medium"><?= $userStats['total_lists'] ?? 0 ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-500">Tugas Jatuh Tempo Hari Ini</span>
                                            <span class="text-sm font-medium"><?= $userStats['tasks_due_today'] ?? 0 ?></span>
                                        </div>
                                    </div>
                                    
                                    <?php
                                    // Hitung tingkat penyelesaian
                                    $completionRate = 0;
                                    if (($userStats['total_tasks'] ?? 0) > 0) {
                                        $completionRate = round((($userStats['completed_tasks'] ?? 0) / ($userStats['total_tasks'] ?? 1)) * 100);
                                    }
                                    ?>
                                    
                                    <div class="mt-4">
                                        <p class="text-sm text-gray-500 mb-1">Tingkat Penyelesaian</p>
                                        <div class="relative pt-1">
                                            <div class="overflow-hidden h-2 text-xs flex rounded bg-gray-200">
                                                <div style="width: <?= $completionRate ?>%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-green-500"></div>
                                            </div>
                                            <div class="mt-1 text-xs text-right text-gray-500"><?= $completionRate ?>%</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Aktivitas Terbaru -->
                            <div class="bg-white shadow rounded-lg overflow-hidden mt-6">
                                <div class="px-4 py-5 sm:p-6">
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Aktivitas Terbaru</h3>
                                    
                                    <?php if (!empty($activityLogs)): ?>
                                        <ul class="space-y-3">
                                            <?php foreach ($activityLogs as $log): ?>
                                                <li class="text-sm">
                                                    <div class="flex items-start">
                                                        <?php
                                                        // Ikon berdasarkan jenis tindakan
                                                        $icon = 'fa-circle';
                                                        $color = 'text-gray-400';
                                                        
                                                        switch ($log['action_type']) {
                                                            case 'create':
                                                                $icon = 'fa-plus-circle';
                                                                $color = 'text-green-500';
                                                                break;
                                                            case 'update':
                                                                $icon = 'fa-edit';
                                                                $color = 'text-blue-500';
                                                                break;
                                                            case 'delete':
                                                                $icon = 'fa-trash';
                                                                $color = 'text-red-500';
                                                                break;
                                                            case 'complete':
                                                                $icon = 'fa-check-circle';
                                                                $color = 'text-green-500';
                                                                break;
                                                            case 'login':
                                                                $icon = 'fa-sign-in-alt';
                                                                $color = 'text-purple-500';
                                                                break;
                                                        }
                                                        ?>
                                                        <i class="fas <?= $icon ?> <?= $color ?> mr-2 mt-1"></i>
                                                        <div>
                                                            <div>
                                                                <?php
                                                                // Format pesan aktivitas
                                                                $action = ucfirst($log['action_type']);
                                                                $entity = $log['entity_type'];
                                                                
                                                                echo "$action $entity";
                                                                
                                                                if (!empty($log['details'])) {
                                                                    echo ": " . htmlspecialchars($log['details']);
                                                                }
                                                                ?>
                                                            </div>
                                                            <div class="text-xs text-gray-500 mt-1">
                                                                <?= formatDate($log['created_at']) ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p class="text-sm text-gray-500">Tidak ada aktivitas terbaru</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <?php include_once '../../includes/footer.php'; ?>
    </div>

    <!-- jQuery -->
    <script src="../../node_modules/jquery/dist/jquery.min.js"></script>
    
    <script>
        $(document).ready(function() {
            const previewModal = $('#preview-modal');
            const previewImage = $('#preview-image');
            const fileInfo = $('#file-info');
            const profileForm = $('form[enctype="multipart/form-data"]');
            let selectedFile = null;

            // Fungsi untuk memformat ukuran file
            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }

            // Memicu input file saat tombol ubah foto profil diklik
            $('#change-profile-pic-btn').click(function() {
                $('#profile_picture').click();
            });

            // Handler untuk input file
            function handleFileSelect(file) {
                if (file) {
                    // Validasi tipe file
                    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    if (!allowedTypes.includes(file.type)) {
                        Swal.fire({
                            title: 'Tipe File Tidak Didukung',
                            text: 'Hanya file JPEG, PNG, GIF, dan WebP yang diizinkan',
                            icon: 'error'
                        });
                        return false;
                    }

                    // Validasi ukuran file (max 2MB)
                    if (file.size > 2 * 1024 * 1024) {
                        Swal.fire({
                            title: 'File Terlalu Besar',
                            text: 'Ukuran file tidak boleh lebih dari 2MB',
                            icon: 'error'
                        });
                        return false;
                    }

                    // Baca dan tampilkan preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImage.attr('src', e.target.result);
                        fileInfo.html(`
                            <p><strong>Nama File:</strong> ${file.name}</p>
                            <p><strong>Tipe:</strong> ${file.type}</p>
                            <p><strong>Ukuran:</strong> ${formatFileSize(file.size)}</p>
                        `);
                        previewModal.removeClass('hidden');
                    };
                    reader.readAsDataURL(file);
                    return true;
                }
                return false;
            }

            // Event handler untuk input file
            $('#profile_picture').change(function(e) {
                selectedFile = e.target.files[0];
                if (!handleFileSelect(selectedFile)) {
                    this.value = ''; // Reset input file jika validasi gagal
                    selectedFile = null;
                }
            });

            // Handler untuk tombol ubah foto
            $('#change-photo').click(function() {
                $('#profile_picture').click();
            });

            // Handler untuk tombol unggah
            $('#confirm-upload').click(function() {
                if (selectedFile) {
                    Swal.fire({
                        title: 'Konfirmasi Unggah',
                        text: 'Apakah Anda yakin ingin mengunggah foto profil ini?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Ya, Unggah!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Buat FormData baru
                            const formData = new FormData();
                            formData.append('profile_picture', selectedFile);
                            formData.append('update_profile', '1');
                            
                            // Tampilkan loading
                            Swal.fire({
                                title: 'Mengunggah...',
                                text: 'Mohon tunggu sebentar',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });

                            // Kirim dengan AJAX
                            $.ajax({
                                url: 'profile.php',
                                type: 'POST',
                                data: formData,
                                processData: false,
                                contentType: false,
                                success: function(response) {
                                    Swal.fire({
                                        title: 'Berhasil!',
                                        text: 'Foto profil berhasil diperbarui',
                                        icon: 'success'
                                    }).then(() => {
                                        location.reload();
                                    });
                                },
                                error: function() {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: 'Gagal mengunggah foto profil',
                                        icon: 'error'
                                    });
                                }
                            });
                        }
                    });
                }
            });

            // Handler untuk tombol batal
            $('#cancel-upload').click(function() {
                $('#profile_picture').val('');
                selectedFile = null;
                previewModal.addClass('hidden');
            });

            // Tutup modal jika mengklik overlay
            previewModal.click(function(e) {
                if (e.target === this) {
                    $('#profile_picture').val('');
                    selectedFile = null;
                    previewModal.addClass('hidden');
                }
            });

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
        });
    </script>

    <!-- Modal Preview Foto Profil -->
    <div id="preview-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 backdrop-blur-sm bg-opacity-50 transition-opacity" aria-hidden="true"></div>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
                                Preview Foto Profil
                            </h3>
                            <div class="mt-2">
                                <!-- Preview Container -->
                                <div class="flex flex-col items-center">
                                    <div class="relative w-40 h-40 rounded-full overflow-hidden mb-4">
                                        <img id="preview-image" class="w-full h-full object-cover" src="" alt="Preview">
                                    </div>
                                    <!-- File Info -->
                                    <div class="text-sm text-gray-500 mb-4" id="file-info"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" id="confirm-upload" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        <i class="fas fa-upload mr-2"></i> Unggah Foto
                    </button>
                    <button type="button" id="change-photo" class="mt-3 sm:mt-0 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        <i class="fas fa-exchange-alt mr-2"></i> Ubah Foto
                    </button>
                    <button type="button" id="cancel-upload" class="mt-3 sm:mt-0 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto sm:text-sm">
                        <i class="fas fa-times mr-2"></i> Batal
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>