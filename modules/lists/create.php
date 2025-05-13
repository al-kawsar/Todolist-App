<?php
// modules/lists/create.php - Create a new list
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/functions.php';
require_once '../../utils/auth.php';
require_once '../../utils/validation.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    redirect('../../login.php');
}

// Get current user
$currentUser = $_SESSION['user'];
$userId = $currentUser['user_id'];

// Tambahkan validasi user_id
$checkUserSql = "SELECT user_id FROM users WHERE user_id = ?";
$stmt = $conn->prepare($checkUserSql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // User tidak ditemukan di database
    session_destroy(); // Hapus session karena tidak valid
    redirect('../../login.php?error=invalid_user');
}
$stmt->close();

$errors = [];
$formData = [
    'title' => '',
    'description' => '',
    'color' => '#3498db',
    'icon' => 'list',
    'is_public' => false
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $formData = [
        'title' => sanitize($_POST['title'] ?? ''),
        'description' => sanitize($_POST['description'] ?? ''),
        'color' => sanitize($_POST['color'] ?? '#3498db'),
        'icon' => sanitize($_POST['icon'] ?? 'list'),
        'is_public' => isset($_POST['is_public'])
    ];
    
    // Validate input
    $errors = validateList($formData);
    
    // If validation passes, create the list
    if (empty($errors)) {
        $sql = "INSERT INTO lists (user_id, title, description, color, icon, is_public) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $isPublic = $formData['is_public'] ? 1 : 0;
        
        $stmt->bind_param(
            "issssi", 
            $userId, 
            $formData['title'], 
            $formData['description'], 
            $formData['color'], 
            $formData['icon'],
            $isPublic
        );
        
        $result = $stmt->execute();
        $listId = $conn->insert_id;
        $stmt->close();
        
        if ($result) {
            // Log activity
            logActivity($userId, 'create', 'list', $listId);
            
            // Redirect to list view page
            redirect('view.php?created=true');
        } else {
            $errors['general'] = 'Failed to create list. Please try again.';
        }
    }
}

// Available icons for the list
$availableIcons = [
    'list', 'clipboard', 'tasks', 'check-square', 'calendar', 'calendar-alt', 'briefcase', 
    'home', 'shopping-cart', 'heart', 'star', 'graduation-cap', 'book', 'code', 'money-bill',
    'plane', 'car', 'utensils', 'film', 'music', 'gamepad', 'gift', 'running', 'bicycle'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create List - <?= APP_NAME ?></title>
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="../../assets/css/output.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../../node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="../../node_modules/sweetalert2/dist/sweetalert2.min.js"></script>
    <link rel="stylesheet" href="../../node_modules/sweetalert2/dist/sweetalert2.min.css">
    <!-- Color Picker -->
    <link rel="stylesheet" href="../../node_modules/@simonwep/pickr/dist/themes/classic.min.css">
    <script src="../../node_modules/@simonwep/pickr/dist/pickr.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <!-- Top Navigation -->
        <?php include_once '../../includes/header.php'; ?>
        
        <div class="flex-grow flex">
            <!-- Sidebar -->
            <?php include_once '../../includes/sidebar.php'; ?>
            
            <!-- Main Content -->
            <div class="flex-1 overflow-auto">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div class="mb-8 flex justify-between items-center">
                        <div>
                        <h1 class="text-2xl font-bold text-gray-900">Buat List</h1>
<p class="mt-1 text-sm text-gray-500">Buat List baru untuk mengatur tugas-tugasmu</p>

                        </div>
                        <a href="view.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-500 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            <i class="fas fa-arrow-left mr-2"></i> Kembali
                        </a>
                    </div>
                    
                    <?php if (!empty($errors['general'])): ?>
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline"><?= $errors['general'] ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <form action="create.php" method="POST" class="bg-white shadow-md rounded-lg overflow-hidden">
                        <div class="px-6 py-6">
                            <!-- List Title -->
                            <div class="mb-6">
                                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Judul List *</label>
                                <input type="text" id="title" name="title" value="<?= htmlspecialchars($formData['title']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                                <?php if (!empty($errors['title'])): ?>
                                    <p class="mt-1 text-sm text-red-600"><?= $errors['title'] ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- List Description -->
                            <div class="mb-6">
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                                <textarea id="description" name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?= htmlspecialchars($formData['description']) ?></textarea>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <!-- List Color -->
                                <div>
                                    <label for="color" class="block text-sm font-medium text-gray-700 mb-1">Warna List</label>
                                    <div class="flex items-center">
                                        <div id="color-picker" class="w-8 h-8 rounded-full border border-gray-300 mr-2" style="background-color: <?= $formData['color'] ?>;"></div>
                                        <input type="text" id="color" name="color" value="<?= $formData['color'] ?>" class="flex-grow px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                </div>
                                
                                <!-- List Icon -->
                                <div>
                                    <label for="icon" class="block text-sm font-medium text-gray-700 mb-1">Icon List</label>
                                    <div class="flex">
                                        <div class="relative flex-grow">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i id="selected-icon" class="fas fa-<?= $formData['icon'] ?>"></i>
                                            </div>
                                            <input type="text" id="icon" name="icon" value="<?= $formData['icon'] ?>" class="w-full pl-10 pr-10 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" readonly>
                                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                                <button type="button" id="show-icons-btn" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                                                    <i class="fas fa-chevron-down"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Icon selector (hidden by default) -->
                                    <div id="icon-selector" class="hidden mt-2 p-2 bg-white border border-gray-300 rounded-md shadow-sm grid grid-cols-6 gap-2 max-h-48 overflow-y-auto">
                                        <?php foreach ($availableIcons as $icon): ?>
                                            <div class="icon-option text-center p-2 rounded-md hover:bg-gray-100 cursor-pointer <?= $icon === $formData['icon'] ? 'bg-blue-100' : '' ?>" data-icon="<?= $icon ?>">
                                                <i class="fas fa-<?= $icon ?> text-lg"></i>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Visibility Option -->
                            <div class="mb-6">
                                <div class="flex items-center">
                                    <input type="checkbox" id="is_public" name="is_public" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" <?= $formData['is_public'] ? 'checked' : '' ?>>
                                    <label for="is_public" class="ml-2 block text-sm text-gray-700">
                                    Jadikan daftar ini publik (dapat dibagikan ke orang lain)
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="px-6 py-4 bg-gray-50 text-right">
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-plus mr-2"></i> Create List
                            </button>
                        </div>
                    </form>
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
            // Initialize color picker
            const pickr = Pickr.create({
                el: '#color-picker',
                theme: 'classic',
                default: '<?= $formData['color'] ?>',
                components: {
                    preview: true,
                    opacity: true,
                    hue: true,
                    interaction: {
                        hex: true,
                        input: true,
                        clear: true,
                        save: true
                    }
                }
            });
            
            // Update color input when color changes
            pickr.on('save', (color) => {
                const hexColor = color.toHEXA().toString();
                $('#color').val(hexColor);
                $('#color-picker').css('background-color', hexColor);
                pickr.hide();
            });
            
            // Show/hide icon selector
            $('#show-icons-btn').click(function() {
                $('#icon-selector').toggle();
            });
            
            // Select icon
            $('.icon-option').click(function() {
                const icon = $(this).data('icon');
                $('#icon').val(icon);
                $('#selected-icon').removeClass().addClass('fas fa-' + icon);
                $('.icon-option').removeClass('bg-blue-100');
                $(this).addClass('bg-blue-100');
                $('#icon-selector').hide();
            });
            
            // Hide icon selector when clicking outside
            $(document).click(function(e) {
                if (!$(e.target).closest('#show-icons-btn, #icon-selector').length) {
                    $('#icon-selector').hide();
                }
            });
        });
    </script>
</body>
</html>