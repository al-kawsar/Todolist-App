<?php
// modules/lists/view.php - View user's lists
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/functions.php';
require_once '../../utils/auth.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    redirect('../../login.php');
}

// Get current user
$currentUser = $_SESSION['user'];
$userId = $currentUser['user_id'];

// Get list ID from URL if present
$listId = isset($_GET['list_id']) ? (int)$_GET['list_id'] : null;

// Get all lists for the current user
$sql = "SELECT l.*, 
        COALESCE((SELECT COUNT(*) FROM tasks WHERE list_id = l.list_id AND is_deleted = 0), 0) as task_count,
        COALESCE((SELECT COUNT(*) FROM tasks WHERE list_id = l.list_id AND status = 'completed' AND is_deleted = 0), 0) as completed_count
        FROM lists l 
        WHERE l.user_id = ? AND l.is_deleted = 0 
        ORDER BY l.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$lists = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$currentList = null;
if ($listId) {
    $sql = "SELECT * FROM lists WHERE list_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $listId, $userId);
    $stmt->execute();
    $currentList = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Lists - <?= APP_NAME ?></title>
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
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div class="mb-8 flex justify-between items-center">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">List Saya</h1>
                        </div>
                        <a href="create.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-plus mr-2"></i> Buat List
                        </a>
                    </div>
                    
                    <?php if (!empty($lists)): ?>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            <?php foreach ($lists as $list): ?>
                                <div class="bg-white  shadow rounded-lg hover:shadow-md transition-shadow duration-300">
                                    <div class="px-4 py-5 sm:p-6">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10 rounded-full flex items-center justify-center" style="background-color: <?= $list['color'] ?>20;">
                                                    <i class="fas fa-<?= $list['icon'] ?: 'list' ?> text-lg" style="color: <?= $list['color'] ?>;"></i>
                                                </div>
                                                <div class="ml-4">
                                                    <h3 class="text-lg font-medium text-gray-900">
                                                        <a href="../tasks/view.php?list_id=<?= $list['list_id'] ?>" class="hover:underline">
                                                            <?= htmlspecialchars($list['title']) ?>
                                                        </a>
                                                    </h3>
                                                    <?php if (!empty($list['description'])): ?>
                                                        <p class="text-sm text-gray-500 line-clamp-2"><?= htmlspecialchars($list['description']) ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="dropdown relative">
                                                <button class="text-gray-400 hover:text-gray-500 focus:outline-none cursor-pointer p-3">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <div class="dropdown-menu hidden absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 z-index ring-black ring-opacity-5 z-10">
                                                    <a href="../tasks/view.php?list_id=<?= $list['list_id'] ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Lihat Tugas</a>
                                                    <a href="edit.php?id=<?= $list['list_id'] ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Edit List</a>
                                                    <a href="#" class="share-list-btn block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" data-list-id="<?= $list['list_id'] ?>" data-list-title="<?= htmlspecialchars($list['title']) ?>">Bagikan List</a>
                                                    <button class="delete-list-btn block w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-gray-100" data-list-id="<?= $list['list_id'] ?>">Hapus List</button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-4">
                                            <div class="flex justify-between text-sm text-gray-500">
                                                <span><?= $list['task_count'] ?? 0 ?> task<?= ($list['task_count'] ?? 0) != 1 ? 's' : '' ?></span>
                                                <span><?= $list['completed_count'] ?? 0 ?> Selesai</span>
                                            </div>
                                            
                                            <?php if (($list['task_count'] ?? 0) > 0): ?>
                                                <div class="mt-2 relative pt-1">
                                                    <div class="overflow-hidden h-2 text-xs flex rounded bg-gray-200">
                                                        <?php 
                                                        $completionPercentage = ($list['task_count'] ?? 0) > 0 
                                                            ? round((($list['completed_count'] ?? 0) / ($list['task_count'] ?? 1)) * 100) 
                                                            : 0;
                                                        ?>
                                                        <div style="width: <?= $completionPercentage ?>%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-green-500"></div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="mt-4 flex justify-between">
                                            <div class="text-xs text-gray-500">
                                                <i class="far fa-calendar-alt mr-1"></i> Created: <?= formatDate($list['created_at'], 'd M Y') ?>
                                            </div>
                                            
                                            <a href="../tasks/create.php?list_id=<?= $list['list_id'] ?>" class="inline-flex items-center text-xs font-medium text-blue-600 hover:text-blue-800">
                                                <i class="fas fa-plus mr-1"></i> Tambah Tugas
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="bg-white shadow overflow-hidden sm:rounded-md">
                            <div class="px-4 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No lists</h3>
                                <p class="mt-1 text-sm text-gray-500">Get started by creating a new list.</p>
                                <div class="mt-6">
                                    <a href="create.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <i class="fas fa-plus mr-2"></i> Buat List
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
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
            // Dropdown toggle
            $('.dropdown button').click(function(e) {
                e.stopPropagation();
                const menu = $(this).next('.dropdown-menu');
                $('.dropdown-menu').not(menu).addClass('hidden');
                menu.toggleClass('hidden');
            });
            
            // Close dropdown when clicking elsewhere
            $(document).click(function() {
                $('.dropdown-menu').addClass('hidden');
            });
            
            // Delete List
            $('.delete-list-btn').click(function() {
                const listId = $(this).data('list-id');
                
                Swal.fire({
                    title: 'Yakin ingin menghapus?',
                    text: "Semua tugas di dalam daftar ini juga akan terhapus. Tindakan ini tidak bisa dibatalkan!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#EF4444',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '../../api/lists.php',
                            type: 'POST',
                            data: {
                                action: 'delete_list',
                                list_id: listId
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire(
                                        'Terhapus!',
                                        'List anda berhasil dihapus.',
                                        'success'
                                    ).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire(
                                        'Error!',
                                        response.message || 'Gagal menghapus list.',
                                        'error'
                                    );
                                }
                            },
                            error: function() {
                                Swal.fire(
                                    'Error!',
                                    'Gagal koneksi ke server.',
                                    'error'
                                );
                            }
                        });
                    }
                });
            });
            
            // Show success toast messages
            <?php if (isset($_GET['created'])): ?>
                Toastify({
                    text: "Berhasil membuat List baru",
                    duration: 3000,
                    close: true,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#10B981",
                    stopOnFocus: true
                }).showToast();
            <?php endif; ?>
            
            <?php if (isset($_GET['updated'])): ?>
                Toastify({
                    text: "Berhasil ubah List",
                    duration: 3000,
                    close: true,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#3B82F6",
                    stopOnFocus: true
                }).showToast();
            <?php endif; ?>
        });
    </script>
</body>
</html>