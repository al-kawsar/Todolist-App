<?php
// login.php - User login page
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'utils/auth.php';
require_once 'utils/validation.php';

// Redirect to dashboard if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$errors = [];
$username = '';

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate input
    $errors = validateLogin([
        'username' => $username,
        'password' => $password
    ]);

    // If validation passes, attempt to login
    if (empty($errors)) {
        $user = loginUser($username, $password);

        if ($user) {
            // Redirect to dashboard on successful login
            redirect('dashboard.php');
        } else {
            $errors['login'] = 'Nama pengguna/email atau kata sandi tidak valid';

        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="assets/css/output.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="node_modules/sweetalert2/dist/sweetalert2.min.js"></script>
    <link rel="stylesheet" href="node_modules/sweetalert2/dist/sweetalert2.min.css">
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <div class="flex justify-center">
                    <i class="fas fa-check-circle text-blue-500 text-5xl"></i>
                </div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Masuk ke akun Anda
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Atau
                    <a href="register.php" class="font-medium text-blue-600 hover:text-blue-500">
                        buat akun baru
                    </a>
                </p>

            </div>

            <?php if (!empty($errors['login'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?= $errors['login'] ?></span>
                </div>
            <?php endif; ?>

            <form class="mt-8 space-y-6" action="login.php" method="POST">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="username" class="sr-only">Username or Email</label>
                        <input id="username" name="username" type="text" autocomplete="username"
                            value="<?= htmlspecialchars($username) ?>" required
                            class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                            placeholder="Username or Email">
                        <?php if (!empty($errors['username'])): ?>
                            <p class="text-red-500 text-xs mt-1"><?= $errors['username'] ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="password" class="sr-only">Password</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" required
                            class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                            placeholder="Password">
                        <?php if (!empty($errors['password'])): ?>
                            <p class="text-red-500 text-xs mt-1"><?= $errors['password'] ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex items-center justify-between">

                </div>

                <div>
                    <button type="submit"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-lock"></i>
                        </span>
                        Masuk
                    </button>
                </div>

            </form>

            <div class="text-center mt-4">
                <a href="index.php" class="font-medium text-blue-600 hover:text-blue-500">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Home
                </a>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="node_modules/jquery/dist/jquery.min.js"></script>

    <script>
        $(document).ready(function () {
            // Sweet Alert for successful registration redirect
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('registered')) {
                Swal.fire({
                    title: 'Registration Successful!',
                    text: 'You can now login with your credentials',
                    icon: 'success',
                    confirmButtonText: 'Great!'
                });
            }
        });
    </script>
</body>

</html>