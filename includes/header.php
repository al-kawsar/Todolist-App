<?php
// includes/header.php - Header component
// This file should be included in all pages that require the header

// Make sure config is loaded
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/functions.php';
}

// Get current user info
$currentUser = $_SESSION['user'] ?? null;
$userId = $currentUser['user_id'] ?? null;
$unreadNotificationsCount = $userId ? countUnreadNotifications($userId) : 0;

// Get profile picture URL
$profilePicUrl = BASE_URL . '/assets/img/pp.png'; // Default image
if ($currentUser && $currentUser['profile_picture']) {
    if (strpos($currentUser['profile_picture'], 'assets/') === 0) {
        $profilePicUrl = BASE_URL . '/' . $currentUser['profile_picture'];
    } else {
        $profilePicUrl = BASE_URL . '/uploads/profile_pictures/' . basename($currentUser['profile_picture']);
    }
}
?>
<script src="../../node_modules/jquery/dist/jquery.min.js"></script>
<nav class="bg-white shadow-sm">
    <div class="max-w-7xl mx-auto px-2 sm:px-4 lg:px-8">
        <div class="relative flex items-center justify-between h-16">
            <!-- Mobile menu button -->
            <div class="absolute inset-y-0 left-0 flex items-center sm:hidden">
                <button id="mobile-menu-button" type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500" aria-controls="mobile-menu" aria-expanded="false">
                    <span class="sr-only">Open main menu</span>
                    <i class="fas fa-bars block h-6 w-6"></i>
                </button>
            </div>

            <!-- Logo -->
            <div class="flex-1 flex items-center justify-center sm:justify-start">
                <div class="flex-shrink-0 flex items-center">
                    <i class="fas fa-check-circle text-blue-500 text-xl sm:text-2xl mr-2"></i>
                    <span class="text-lg sm:text-xl font-bold text-gray-800"><?= APP_NAME ?></span>
                </div>
            </div>
            
            <!-- Right side with profile and notifications -->
            <?php if ($currentUser): ?>
                <div class="absolute inset-y-0 right-0 flex items-center pr-2 sm:static sm:inset-auto sm:pr-0">
                    <!-- Notifications -->
                    <div class="relative mr-3">
                        <button id="notification-btn" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                            <i class="fas fa-bell text-lg sm:text-xl"></i>
                            <?php if ($unreadNotificationsCount > 0): ?>
                                <span class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-4 h-4 sm:w-5 sm:h-5 flex items-center justify-center text-xs">
                                    <?= $unreadNotificationsCount ?>
                                </span>
                            <?php endif; ?>
                        </button>
                    </div>
                    
                    <!-- Profile dropdown -->
                    <div class="relative ml-3">
                        <div>
                            <button id="user-menu-button" class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" aria-expanded="false" aria-haspopup="true">
                                <img class="h-8 w-8 rounded-full object-cover" 
                                     src="<?= $profilePicUrl ?>" 
                                     alt="<?= htmlspecialchars($currentUser['full_name']) ?>">
                                <span class="hidden sm:ml-2 sm:block text-gray-700"><?= htmlspecialchars($currentUser['full_name']) ?></span>
                                <i class="hidden sm:block fas fa-chevron-down ml-2 text-gray-400"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mobile menu -->
    <div id="mobile-menu" class="sm:hidden hidden">
        <div class="px-2 pt-2 pb-3 space-y-1">
            <a href="/modules/users/profile.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">Profile Anda</a>
            <a href="/modules/users/logout.php" class="block px-3 py-2 rounded-md text-base font-medium text-red-700 hover:text-red-900 hover:bg-gray-50">Keluar</a>
        </div>
    </div>
</nav>

<!-- User dropdown menu template -->
<div id="user-dropdown-template" class="hidden origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 z-10" role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" tabindex="-1">
    <a href="/modules/users/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Profil Anda</a>
    <!-- <a href="/modules/settings/app_settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Settings</a> -->
    <a href="/modules/users/logout.php" class="block px-4 py-2 text-sm text-red-700 hover:bg-gray-100" role="menuitem">Keluar</a>
</div>

<!-- Notification dropdown template -->
<div id="notification-dropdown-template" class="hidden origin-top-right absolute right-0 mt-2 w-80 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10 divide-y divide-gray-100">
    <div class="px-4 py-3 flex justify-between items-center">
        <h3 class="text-sm font-medium text-gray-900">Pemberitahuan</h3>
        <a href="#" id="mark-all-read" class="text-xs text-blue-500 hover:text-blue-700"> Tandai semua sebagai sudah dibaca</a>
    </div>
    <div class="max-h-72 overflow-y-auto" id="notification-list">
        <!-- Pemberitahuan akan dimuat di sini melalui AJAX -->
        <div class="text-center py-4 text-sm text-gray-500">Memuat pemberitahuan...</div>
    </div>
    <div class="px-4 py-3 text-center">
        <a href="/modules/users/notifications.php" class="text-sm text-blue-500 hover:text-blue-700">Lihat semua pemberitahuan</a>
    </div>
</div>


<!-- Header JavaScript -->
<script>
$(document).ready(function() {
    // Mobile menu toggle
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');

    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', function() {
            const expanded = mobileMenuButton.getAttribute('aria-expanded') === 'true';
            mobileMenuButton.setAttribute('aria-expanded', !expanded);
            mobileMenu.classList.toggle('hidden');
        });
    }

    // User dropdown toggle
    const userMenuButton = document.getElementById('user-menu-button');
    const userDropdownTemplate = document.getElementById('user-dropdown-template');
    let userDropdown = null;

    if (userMenuButton && userDropdownTemplate) {
        userMenuButton.addEventListener('click', function() {
            if (userDropdown) {
                userDropdown.remove();
                userDropdown = null;
            } else {
                userDropdown = userDropdownTemplate.cloneNode(true);
                userDropdown.classList.remove('hidden');
                userDropdown.id = 'user-dropdown';
                userMenuButton.parentNode.appendChild(userDropdown);
            }
        });

        // Close user dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (userDropdown && !userMenuButton.contains(event.target) && !userDropdown.contains(event.target)) {
                userDropdown.remove();
                userDropdown = null;
            }
        });
    }

    // Notification dropdown toggle
    const notificationBtn = document.getElementById('notification-btn');
    const notificationDropdownTemplate = document.getElementById('notification-dropdown-template');
    let notificationDropdown = null;

    if (notificationBtn && notificationDropdownTemplate) {
        notificationBtn.addEventListener('click', function() {
            if (notificationDropdown) {
                notificationDropdown.remove();
                notificationDropdown = null;
            } else {
                notificationDropdown = notificationDropdownTemplate.cloneNode(true);
                notificationDropdown.classList.remove('hidden');
                notificationDropdown.id = 'notification-dropdown';
                notificationBtn.parentNode.appendChild(notificationDropdown);
                
                // Load notifications via AJAX
                loadNotifications();
                
                // Mark all as read button
                notificationDropdown.querySelector('#mark-all-read').addEventListener('click', function(e) {
                    e.preventDefault();
                    markAllNotificationsAsRead();
                });
            }
        });

        // Close notification dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (notificationDropdown && !notificationBtn.contains(event.target) && !notificationDropdown.contains(event.target)) {
                notificationDropdown.remove();
                notificationDropdown = null;
            }
        });

        // Load notifications function
        function loadNotifications() {
            $.ajax({
                url: '<?= BASE_URL ?>/api/notifications.php',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    const notificationList = document.getElementById('notification-list');
                    
                    if (!data.length || data.length === 0) {
                        notificationList.innerHTML = '<div class="text-center py-4 text-sm text-gray-500">No notifications</div>';
                        return;
                    }
                    
                    let html = '';
                    data.forEach(notification => {
                        const isRead = notification.is_read ? '' : 'bg-blue-50';
                        const notificationDate = new Date(notification.created_at);
                        const timeAgo = timeAgoFormatter(notificationDate);
                        
                        html += `
                            <a href="#" class="block px-4 py-3 hover:bg-gray-50 ${isRead}" data-id="${notification.notification_id}">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 pt-0.5">
                                        ${getNotificationIcon(notification.type)}
                                    </div>
                                    <div class="ml-3 w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-900">${notification.title}</p>
                                        <p class="text-sm text-gray-500">${notification.message}</p>
                                        <p class="mt-1 text-xs text-gray-400">${timeAgo}</p>
                                    </div>
                                </div>
                            </a>
                        `;
                    });
                    
                    notificationList.innerHTML = html;
                    
                    // Add click handlers to notification items
                    notificationList.querySelectorAll('a').forEach(item => {
                        item.addEventListener('click', function(e) {
                            e.preventDefault();
                            const id = this.getAttribute('data-id');
                            markNotificationAsRead(id);
                        });
                    });
                },
                error: function() {
                    const notificationList = document.getElementById('notification-list');
                    notificationList.innerHTML = '<div class="text-center py-4 text-sm text-gray-500">Error loading notifications</div>';
                }
            });
        }
        
        // Mark notification as read
        function markNotificationAsRead(id) {
            $.ajax({
                url: '<?= BASE_URL ?>/api/notifications.php',
                type: 'POST',
                data: { action: 'mark_read', notification_id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        loadNotifications();
                        updateNotificationCounter();
                    }
                }
            });
        }
        
        // Mark all notifications as read
        function markAllNotificationsAsRead() {
            $.ajax({
                url: '<?= BASE_URL ?>/api/notifications.php',
                type: 'POST',
                data: { action: 'mark_all_read' },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        loadNotifications();
                        updateNotificationCounter();
                    }
                }
            });
        }
        
        // Update notification counter
        function updateNotificationCounter() {
            $.ajax({
                url: '<?= BASE_URL ?>/api/notifications.php',
                type: 'GET',
                data: { action: 'count_unread' },
                dataType: 'json',
                success: function(response) {
                    const badge = notificationBtn.querySelector('span');
                    
                    if (response.count > 0) {
                        if (badge) {
                            badge.textContent = response.count;
                        } else {
                            const newBadge = document.createElement('span');
                            newBadge.className = 'absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-4 h-4 sm:w-5 sm:h-5 flex items-center justify-center text-xs';
                            newBadge.textContent = response.count;
                            notificationBtn.appendChild(newBadge);
                        }
                    } else if (badge) {
                        badge.remove();
                    }
                }
            });
        }
    }
    
    // Helper function for notification icons
    function getNotificationIcon(type) {
        switch (type) {
            case 'reminder':
                return '<i class="fas fa-bell text-yellow-500"></i>';
            case 'mention':
                return '<i class="fas fa-at text-blue-500"></i>';
            case 'share':
                return '<i class="fas fa-share-alt text-green-500"></i>';
            case 'comment':
                return '<i class="fas fa-comment text-purple-500"></i>';
            default:
                return '<i class="fas fa-info-circle text-gray-500"></i>';
        }
    }
    
    // Time ago formatter
    function timeAgoFormatter(date) {
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);
        
        if (diffInSeconds < 60) {
            return 'Just now';
        }
        
        const diffInMinutes = Math.floor(diffInSeconds / 60);
        if (diffInMinutes < 60) {
            return diffInMinutes + ' minute' + (diffInMinutes > 1 ? 's' : '') + ' ago';
        }
        
        const diffInHours = Math.floor(diffInMinutes / 60);
        if (diffInHours < 24) {
            return diffInHours + ' hour' + (diffInHours > 1 ? 's' : '') + ' ago';
        }
        
        const diffInDays = Math.floor(diffInHours / 24);
        if (diffInDays < 7) {
            return diffInDays + ' day' + (diffInDays > 1 ? 's' : '') + ' ago';
        }
        
        return date.toLocaleDateString();
    }
});
</script>