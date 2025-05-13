<?php
// register.php - User registration
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'utils/auth.php';
require_once 'utils/validation.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$errors = [];
$formData = [
    'username' => '',
    'email' => '',
    'full_name' => '',
    'password' => '',
    'confirm_password' => ''
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $formData = [
        'username' => sanitize($_POST['username'] ?? ''),
        'email' => sanitize($_POST['email'] ?? ''),
        'full_name' => sanitize($_POST['full_name'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? ''
    ];
    
    // Validate registration data
    $errors = validateRegistration($formData);
    
    // Check if username already exists
    if (empty($errors['username'])) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->bind_param("s", $formData['username']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors['username'] = 'Username sudah digunakan';
        }
        $stmt->close();
    }
    
    // Check if email already exists
    if (empty($errors['email'])) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $formData['email']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors['email'] = 'Email sudah digunakan';
        }
        $stmt->close();
    }
    
    // If validation passes, register user
    if (empty($errors)) {
        // Use the registerUser function from utils/auth.php instead of direct DB operations
        $userId = registerUser(
            $formData['username'], 
            $formData['email'], 
            $formData['password'], 
            $formData['full_name']
        );
        
        if ($userId) {
            // Create a default list for the user
            $defaultListTitle = "My Tasks";
            $defaultListSql = "INSERT INTO lists (user_id, title, description, color, icon) VALUES (?, ?, 'Default task list', '#3498db', 'tasks')";
            $listStmt = $conn->prepare($defaultListSql);
            $listStmt->bind_param("is", $userId, $defaultListTitle);
            $listStmt->execute();
            $listStmt->close();
            
            // Buat notifikasi selamat datang
            $notificationSql = "INSERT INTO notifications (user_id, title, message, type, entity_type, entity_id) 
                               VALUES (?, 'Selamat datang di TodoList!', 'Terima kasih telah mendaftar. Mulailah dengan membuat tugas pertama Anda.', 'system', 'system', NULL)";
            $notificationStmt = $conn->prepare($notificationSql);
            $notificationStmt->bind_param("i", $userId);
            $notificationStmt->execute();
            $notificationStmt->close();
            
            // Log the activity
            logActivity($userId, 'login', 'user', $userId, 'User registered and logged in');
            
            // Redirect to dashboard
            redirect('dashboard.php?welcome=true');
        } else {
            $errors['general'] = 'Registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - <?= APP_NAME ?></title>
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="assets/css/output.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="node_modules/sweetalert2/dist/sweetalert2.min.js"></script>
    <link rel="stylesheet" href="node_modules/sweetalert2/dist/sweetalert2.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-8">
            <div class="text-center mb-6">
                <h1 class="text-3xl font-bold text-gray-900"><?= APP_NAME ?></h1>
                <p class="mt-2 text-sm text-gray-600">Buat akun Anda</p>
            </div>
            
            <?php if (!empty($errors['general'])): ?>
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?= $errors['general'] ?></span>
                </div>
            <?php endif; ?>
            
            <form action="register.php" method="POST" class="space-y-6">
                <!-- Username -->
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Nama Pengguna</label>
                    <div class="mt-1">
                        <input id="username" name="username" type="text" value="<?= htmlspecialchars($formData['username']) ?>" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                    </div>
                    <?php if (!empty($errors['username'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= $errors['username'] ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <div class="mt-1">
                        <input id="email" name="email" type="email" value="<?= htmlspecialchars($formData['email']) ?>" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                    </div>
                    <?php if (!empty($errors['email'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= $errors['email'] ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Full Name -->
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                    <div class="mt-1">
                        <input id="full_name" name="full_name" type="text" value="<?= htmlspecialchars($formData['full_name']) ?>" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                    </div>
                    <?php if (!empty($errors['full_name'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= $errors['full_name'] ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Kata Sandi</label>
                    <div class="mt-1">
                        <input id="password" name="password" type="password" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                    </div>
                    <?php if (!empty($errors['password'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= $errors['password'] ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Confirm Password -->
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">Konfirmasi Kata Sandi</label>
                    <div class="mt-1">
                        <input id="confirm_password" name="confirm_password" type="password" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                    </div>
                    <?php if (!empty($errors['confirm_password'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= $errors['confirm_password'] ?></p>
                    <?php endif; ?>
                </div>
                
                <div>
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Buat Akun
                    </button>
                </div>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Sudah punya akun? <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">Masuk</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
