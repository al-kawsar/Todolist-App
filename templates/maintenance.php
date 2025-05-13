<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Mode - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/output.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-auto p-6">
        <div class="bg-white rounded-lg shadow-xl p-8 text-center">
            <div class="mb-6">
                <i class="fas fa-tools text-6xl text-blue-500"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-4">Mode Pemeliharaan</h1>
            <p class="text-gray-600 mb-6"><?= getMaintenanceMessage() ?></p>
            <?php if (isLoggedIn()): ?>
                <a href="<?= BASE_URL ?>/logout.php" 
                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 