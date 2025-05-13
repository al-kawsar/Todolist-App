<?php
// modules/users/notifications.php - User notifications
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

// Mark notifications as read if requested
if (isset($_GET['mark_read']) && $_GET['mark_read'] == 'all') {
    $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
    
    // Redirect back to notifications page
    redirect('notifications.php?marked_read=true');
}

// Mark a single notification as read
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $notificationId = (int)$_GET['mark_read'];
    
    $sql = "UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $notificationId, $userId);
    $stmt->execute();
    $stmt->close();
    
    // Redirect to the notification's entity if available
    if (isset($_GET['entity_type']) && isset($_GET['entity_id'])) {
        $entityType = $_GET['entity_type'];
        $entityId = (int)$_GET['entity_id'];
        
        if ($entityType === 'task') {
            redirect("../tasks/task_detail.php?id=$entityId&notification=true");
        } elseif ($entityType === 'list') {
            redirect("../tasks/view.php?list_id=$entityId&notification=true");
        } else {
            redirect('notifications.php?marked_read=true');
        }
    } else {
        redirect('notifications.php?marked_read=true');
    }
}

// Delete a notification
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $notificationId = (int)$_GET['delete'];
    
    $sql = "DELETE FROM notifications WHERE notification_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $notificationId, $userId);
    $stmt->execute();
    $stmt->close();
    
    redirect('notifications.php?deleted=true');
}

// Get user's notifications
$sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Count unread notifications
$unreadCount = 0;
foreach ($notifications as $notification) {
    if (!$notification['is_read']) {
        $unreadCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - <?= APP_NAME ?></title>
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
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Notifications</h1>
                            <p class="mt-1 text-sm text-gray-500">
                                <?= count($notifications) ?> notifications (<?= $unreadCount ?> unread)
                            </p>
                        </div>
                        
                        <?php if ($unreadCount > 0): ?>
                            <a href="notifications.php?mark_read=all" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-check-double mr-2"></i> Mark All Read
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (isset($_GET['marked_read'])): ?>
                        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline">Notification(s) marked as read.</span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['deleted'])): ?>
                        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline">Notification deleted successfully.</span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                        <?php if (!empty($notifications)): ?>
                            <ul class="divide-y divide-gray-200">
                                <?php foreach ($notifications as $notification): ?>
                                    <li class="px-4 py-4 sm:px-6 <?= $notification['is_read'] ? 'bg-white' : 'bg-blue-50' ?>">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 mt-0.5">
                                                <?php
                                                // Icon based on notification type
                                                $icon = 'fa-bell';
                                                $iconColor = 'text-gray-400';
                                                
                                                switch ($notification['type']) {
                                                    case 'reminder':
                                                        $icon = 'fa-clock';
                                                        $iconColor = 'text-purple-500';
                                                        break;
                                                    case 'mention':
                                                        $icon = 'fa-at';
                                                        $iconColor = 'text-blue-500';
                                                        break;
                                                    case 'share':
                                                        $icon = 'fa-share-alt';
                                                        $iconColor = 'text-green-500';
                                                        break;
                                                    case 'comment':
                                                        $icon = 'fa-comment';
                                                        $iconColor = 'text-yellow-500';
                                                        break;
                                                    case 'system':
                                                        $icon = 'fa-cog';
                                                        $iconColor = 'text-gray-500';
                                                        break;
                                                }
                                                ?>
                                                <i class="fas <?= $icon ?> <?= $iconColor ?> text-lg"></i>
                                            </div>
                                            <div class="ml-3 flex-1">
                                                <div class="flex justify-between">
                                                    <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($notification['title']) ?></p>
                                                    <div class="flex space-x-2">
                                                        <span class="text-xs text-gray-500"><?= formatDate($notification['created_at']) ?></span>
                                                        
                                                        <?php if (!$notification['is_read']): ?>
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                                New
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <p class="mt-1 text-sm text-gray-700"><?= htmlspecialchars($notification['message']) ?></p>
                                                <div class="mt-2 flex space-x-2">
                                                    <?php if (!$notification['is_read']): ?>
                                                        <a href="notifications.php?mark_read=<?= $notification['notification_id'] ?><?= $notification['entity_type'] != 'system' && $notification['entity_id'] ? '&entity_type=' . $notification['entity_type'] . '&entity_id=' . $notification['entity_id'] : '' ?>" class="inline-flex items-center text-xs font-medium text-blue-600 hover:text-blue-800">
                                                            <i class="fas fa-check mr-1"></i> Mark as Read
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($notification['entity_type'] != 'system' && $notification['entity_id']): ?>
                                                        <a href="<?= $notification['entity_type'] === 'task' ? '../tasks/task_detail.php?id=' . $notification['entity_id'] : ($notification['entity_type'] === 'list' ? '../tasks/view.php?list_id=' . $notification['entity_id'] : '#') ?>" class="inline-flex items-center text-xs font-medium text-green-600 hover:text-green-800">
                                                            <i class="fas fa-eye mr-1"></i> View
                                                            <?= $notification['entity_type'] === 'task' ? 'Task' : ($notification['entity_type'] === 'list' ? 'List' : $notification['entity_type']) ?>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <a href="notifications.php?delete=<?= $notification['notification_id'] ?>" class="inline-flex items-center text-xs font-medium text-red-600 hover:text-red-800">
                                                        <i class="fas fa-trash mr-1"></i> Delete
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="px-4 py-12 text-center">
                                <i class="fas fa-bell-slash text-gray-400 text-5xl mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-900">No notifications</h3>
                                <p class="mt-1 text-sm text-gray-500">You don't have any notifications right now.</p>
                            </div>
                        <?php endif; ?>
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
            // Confirmation before marking all as read
            $('a[href="notifications.php?mark_read=all"]').click(function(e) {
                e.preventDefault();
                const url = $(this).attr('href');
                
                Swal.fire({
                    title: 'Mark all as read?',
                    text: "Semua notifikasi telah ditandai selesai.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, mark all'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    }
                });
            });
            
            // Confirmation before deleting
            $('a[href^="notifications.php?delete="]').click(function(e) {
                e.preventDefault();
                const url = $(this).attr('href');
                
                Swal.fire({
                    title: 'Hapus Notifikasi?',
                    text: "Aksi ini tidak dapat dibatalkan.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, hapus'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    }
                });
            });
            
            <?php if (isset($_GET['marked_read']) || isset($_GET['deleted'])): ?>
                Toastify({
                    text: "<?= isset($_GET['marked_read']) ? 'Notification(s) marked as read' : 'Notification deleted' ?>",
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
</body>
</html>