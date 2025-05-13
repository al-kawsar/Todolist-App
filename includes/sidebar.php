<?php
// includes/sidebar.php - Komponen sidebar untuk navigasi
// File ini harus disertakan di semua halaman yang membutuhkan sidebar

// Pastikan config sudah dimuat
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/functions.php';
}

// Dapatkan pengguna saat ini
$currentUser = $_SESSION['user'] ?? null;
$userId = $currentUser['user_id'] ?? null;
$isAdmin = $currentUser['role'] === 'admin';

// Dapatkan semua daftar untuk pengguna saat ini
$lists = [];
if ($userId) {
    $sql = "SELECT * FROM lists WHERE user_id = ? AND is_deleted = 0 ORDER BY title ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $lists = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<!-- Desktop Sidebar -->
<div class="hidden md:flex md:flex-shrink-0">
    <div class="flex flex-col w-64 border-r border-gray-200 bg-white/80 backdrop-blur-lg">
        <div class="h-0 flex-1 flex flex-col pt-5 pb-4 overflow-y-auto">
            <div class="px-4 space-y-4">
                <!-- Navigation Links -->
                <nav class="space-y-2">
                    <a href="<?= BASE_URL ?>/dashboard.php" class="flex items-center px-2 py-2 text-sm font-medium rounded-lg transition-all duration-200 <?= strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false ? 'bg-blue-50/80 text-blue-600' : 'text-gray-600 hover:bg-gray-50/80 hover:text-gray-900' ?>">
                        <i class="fas fa-home mr-3 <?= strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false ? 'text-blue-500' : 'text-gray-400' ?>"></i>
                        Dasbor
                    </a>
                    <a href="<?= BASE_URL ?>/modules/tasks/view.php" class="flex items-center px-2 py-2 text-sm font-medium rounded-lg transition-all duration-200 <?= strpos($_SERVER['REQUEST_URI'], 'tasks') !== false ? 'bg-blue-50/80 text-blue-600' : 'text-gray-600 hover:bg-gray-50/80 hover:text-gray-900' ?>">
                        <i class="fas fa-tasks mr-3 <?= strpos($_SERVER['REQUEST_URI'], 'tasks') !== false ? 'text-blue-500' : 'text-gray-400' ?>"></i>
                        Tugas Saya
                    </a>
                    <a href="<?= BASE_URL ?>/modules/lists/view.php" class="flex items-center px-2 py-2 text-sm font-medium rounded-lg transition-all duration-200 <?= strpos($_SERVER['REQUEST_URI'], 'lists') !== false ? 'bg-blue-50/80 text-blue-600' : 'text-gray-600 hover:bg-gray-50/80 hover:text-gray-900' ?>">
                        <i class="fas fa-list-alt mr-3 <?= strpos($_SERVER['REQUEST_URI'], 'lists') !== false ? 'text-blue-500' : 'text-gray-400' ?>"></i>
                        List Saya
                    </a>
                    <a href="<?= BASE_URL ?>/modules/tags/manage.php" class="flex items-center px-2 py-2 text-sm font-medium rounded-lg transition-all duration-200 <?= strpos($_SERVER['REQUEST_URI'], 'tags') !== false ? 'bg-blue-50/80 text-blue-600' : 'text-gray-600 hover:bg-gray-50/80 hover:text-gray-900' ?>">
                        <i class="fas fa-tags mr-3 <?= strpos($_SERVER['REQUEST_URI'], 'tags') !== false ? 'text-blue-500' : 'text-gray-400' ?>"></i>
                        Kelola Label
                    </a>
                    <a href="<?= BASE_URL ?>/modules/statistics/stats.php" class="flex items-center px-2 py-2 text-sm font-medium rounded-lg transition-all duration-200 <?= strpos($_SERVER['REQUEST_URI'], 'statistics') !== false ? 'bg-blue-50/80 text-blue-600' : 'text-gray-600 hover:bg-gray-50/80 hover:text-gray-900' ?>">
                        <i class="fas fa-chart-line mr-3 <?= strpos($_SERVER['REQUEST_URI'], 'statistics') !== false ? 'text-blue-500' : 'text-gray-400' ?>"></i>
                        Statistik
                    </a>
                    <?php if ($isAdmin): ?>
                    <a href="<?= BASE_URL ?>/modules/settings/app_settings.php" class="flex items-center px-2 py-2 text-sm font-medium rounded-lg transition-all duration-200 <?= strpos($_SERVER['REQUEST_URI'], 'settings') !== false ? 'bg-blue-50/80 text-blue-600' : 'text-gray-600 hover:bg-gray-50/80 hover:text-gray-900' ?>">
                        <i class="fas fa-cog mr-3 <?= strpos($_SERVER['REQUEST_URI'], 'settings') !== false ? 'text-blue-500' : 'text-gray-400' ?>"></i>
                        Pengaturan
                    </a>
                    <?php endif; ?>
                    <!-- <a href="/modules/users/collaborations.php" class="flex items-center px-2 py-2 text-sm font-medium rounded-lg transition-all duration-200 <?= strpos($_SERVER['REQUEST_URI'], '/modules/users/collaborations.php') !== false ? 'bg-blue-50/80 text-blue-600' : 'text-gray-600 hover:bg-gray-50/80 hover:text-gray-900' ?>">
                        <i class="fas fa-users mr-3 <?= strpos($_SERVER['REQUEST_URI'], '/modules/users/collaborations.php') !== false ? 'text-blue-500' : 'text-gray-400' ?>"></i>
                        Kolaborasi
                        <?php
                        // Tampilkan badge jika ada permintaan kolaborasi yang pending
                        $pendingQuery = "SELECT COUNT(*) as count FROM collaboration_requests WHERE target_user_id = ? AND status = 'pending'";
                        $stmt = $conn->prepare($pendingQuery);
                        $stmt->bind_param("i", $_SESSION['user']['user_id']);
                        $stmt->execute();
                        $pendingCount = $stmt->get_result()->fetch_assoc()['count'];
                        if ($pendingCount > 0): 
                        ?>
                            <span class="ml-auto inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full"><?= $pendingCount ?></span>
                        <?php endif; ?>
                    </a> -->
                </nav>
            </div>

            <!-- Lists Section -->
            <div class="mt-6 px-4">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">List Saya</h2>
                    <a href="<?= BASE_URL ?>/modules/lists/create.php" class="text-xs text-blue-500 hover:text-blue-700 transition-colors">
                        <i class="fas fa-plus"></i> Baru
                    </a>
                </div>
                <div class="space-y-1">
                    <?php foreach ($lists as $list): ?>
                    <a href="<?= BASE_URL ?>/modules/tasks/view.php?list_id=<?= $list['list_id'] ?>" 
                       class="group flex items-center px-2 py-2 text-sm font-medium rounded-lg transition-all duration-200 text-gray-600 hover:bg-gray-50/80 hover:text-gray-900">
                        <span class="w-2 h-2 mr-3 rounded-full" style="background-color: <?= htmlspecialchars($list['color']) ?>"></span>
                        <span class="truncate"><?= htmlspecialchars($list['title']) ?></span>
                    </a>
                    <?php endforeach; ?>
                    
                    <?php if (empty($lists)): ?>
                    <p class="text-xs text-gray-500 px-2 py-2">Belum ada daftar yang dibuat</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Filters -->
            <div class="mt-6 px-4">
                <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Filter Cepat</h2>
                <div class="space-y-1">
                    <a href="<?= BASE_URL ?>/modules/tasks/view.php?filter=today" 
                       class="group flex items-center px-2 py-2 text-sm font-medium rounded-lg transition-all duration-200 text-gray-600 hover:bg-gray-50/80 hover:text-gray-900">
                        <i class="fas fa-calendar-day text-yellow-500 mr-3"></i>
                        <span class="truncate">Jatuh Tempo Hari Ini</span>
                    </a>
                    <a href="<?= BASE_URL ?>/modules/tasks/view.php?filter=upcoming" 
                       class="group flex items-center px-2 py-2 text-sm font-medium rounded-lg transition-all duration-200 text-gray-600 hover:bg-gray-50/80 hover:text-gray-900">
                        <i class="fas fa-calendar-week text-blue-500 mr-3"></i>
                        <span class="truncate">Akan Datang</span>
                    </a>
                    <a href="<?= BASE_URL ?>/modules/tasks/view.php?filter=overdue" 
                       class="group flex items-center px-2 py-2 text-sm font-medium rounded-lg transition-all duration-200 text-gray-600 hover:bg-gray-50/80 hover:text-gray-900">
                        <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                        <span class="truncate">Terlambat</span>
                    </a>
                    <a href="<?= BASE_URL ?>/modules/tasks/view.php?status=completed" 
                       class="group flex items-center px-2 py-2 text-sm font-medium rounded-lg transition-all duration-200 text-gray-600 hover:bg-gray-50/80 hover:text-gray-900">
                        <i class="fas fa-check-circle text-green-500 mr-3"></i>
                        <span class="truncate">Selesai</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Logout Button -->
        <div class="flex-shrink-0 flex border-t border-gray-200 p-4">
            <a href="<?= BASE_URL ?>/modules/users/logout.php" 
               class="flex items-center w-full px-2 py-2 text-sm font-medium text-red-500 hover:text-red-700 hover:bg-red-50/80 rounded-lg transition-all duration-200">
                <i class="fas fa-sign-out-alt mr-3"></i>
                <span>Keluar</span>
            </a>
        </div>
    </div>
</div>

<!-- Mobile Sidebar -->
<div class="md:hidden">
    <button id="mobile-menu-button" 
            class="fixed bottom-4 right-4 z-50 p-3 rounded-full bg-blue-500 text-white shadow-lg hover:bg-blue-600 transition-all duration-200 focus:outline-none">
        <i class="fas fa-bars"></i>
    </button>

    <div id="mobile-sidebar" 
         class="fixed inset-0 z-40 hidden">
        <div class="absolute inset-0 bg-gray-600/75 backdrop-blur-sm"></div>
        
        <div class="fixed inset-y-0 right-0 max-w-[85%] w-full bg-white/90 backdrop-blur-lg shadow-xl transform transition-all duration-300 ease-in-out translate-x-full">
            <div class="flex items-center justify-between px-4 h-16 border-b border-gray-200">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-blue-500 text-2xl mr-2"></i>
                    <span class="text-xl font-bold text-gray-800"><?= APP_NAME ?></span>
                </div>
                <button id="close-mobile-menu" class="p-2 rounded-lg text-gray-500 hover:text-gray-900 hover:bg-gray-100/80 transition-all duration-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto py-4">
                <!-- Mobile Navigation -->
                <div class="px-4 space-y-4">
                    <nav class="space-y-2">
                        <a href="<?= BASE_URL ?>/dashboard.php" class="flex items-center px-2 py-2 text-sm font-medium rounded-lg transition-all duration-200 <?= strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false ? 'bg-blue-50/80 text-blue-600' : 'text-gray-600 hover:bg-gray-50/80 hover:text-gray-900' ?>">
                            <i class="fas fa-home mr-3 <?= strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false ? 'text-blue-500' : 'text-gray-400' ?>"></i>
                            Dasbor
                        </a>
                        <a href="<?= BASE_URL ?>/modules/tasks/view.php" class="flex items-center px-2 py-2 text-sm font-medium rounded-lg transition-all duration-200 <?= strpos($_SERVER['REQUEST_URI'], 'tasks') !== false ? 'bg-blue-50/80 text-blue-600' : 'text-gray-600 hover:bg-gray-50/80 hover:text-gray-900' ?>">
                            <i class="fas fa-tasks mr-3 <?= strpos($_SERVER['REQUEST_URI'], 'tasks') !== false ? 'text-blue-500' : 'text-gray-400' ?>"></i>
                            Tugas Saya
                        </a>
                        <a href="<?= BASE_URL ?>/modules/lists/view.php" class="flex items-center px-2 py-2 text-sm font-medium rounded-lg transition-all duration-200 <?= strpos($_SERVER['REQUEST_URI'], 'lists') !== false ? 'bg-blue-50/80 text-blue-600' : 'text-gray-600 hover:bg-gray-50/80 hover:text-gray-900' ?>">
                            <i class="fas fa-list-alt mr-3 <?= strpos($_SERVER['REQUEST_URI'], 'lists') !== false ? 'text-blue-500' : 'text-gray-400' ?>"></i>
                            List Saya
                        </a>
                        <a href="<?= BASE_URL ?>/modules/tags/manage.php" class="flex items-center px-2 py-2 text-sm font-medium rounded-lg transition-all duration-200 <?= strpos($_SERVER['REQUEST_URI'], 'tags') !== false ? 'bg-blue-50/80 text-blue-600' : 'text-gray-600 hover:bg-gray-50/80 hover:text-gray-900' ?>">
                            <i class="fas fa-tags mr-3 <?= strpos($_SERVER['REQUEST_URI'], 'tags') !== false ? 'text-blue-500' : 'text-gray-400' ?>"></i>
                            Kelola Label
                        </a>
                        <a href="<?= BASE_URL ?>/modules/statistics/stats.php" class="flex items-center px-2 py-2 text-sm font-medium rounded-lg transition-all duration-200 <?= strpos($_SERVER['REQUEST_URI'], 'statistics') !== false ? 'bg-blue-50/80 text-blue-600' : 'text-gray-600 hover:bg-gray-50/80 hover:text-gray-900' ?>">
                            <i class="fas fa-chart-line mr-3 <?= strpos($_SERVER['REQUEST_URI'], 'statistics') !== false ? 'text-blue-500' : 'text-gray-400' ?>"></i>
                            Statistik
                        </a>
                        <?php if ($isAdmin): ?>
                        <a href="<?= BASE_URL ?>/modules/settings/app_settings.php" class="flex items-center px-2 py-2 text-sm font-medium rounded-lg transition-all duration-200 <?= strpos($_SERVER['REQUEST_URI'], 'settings') !== false ? 'bg-blue-50/80 text-blue-600' : 'text-gray-600 hover:bg-gray-50/80 hover:text-gray-900' ?>">
                            <i class="fas fa-cog mr-3 <?= strpos($_SERVER['REQUEST_URI'], 'settings') !== false ? 'text-blue-500' : 'text-gray-400' ?>"></i>
                            Pengaturan
                        </a>
                        <?php endif; ?>
                        <a href="/modules/users/collaborations.php" class="flex items-center px-2 py-2 text-sm font-medium rounded-lg transition-all duration-200 <?= strpos($_SERVER['REQUEST_URI'], '/modules/users/collaborations.php') !== false ? 'bg-blue-50/80 text-blue-600' : 'text-gray-600 hover:bg-gray-50/80 hover:text-gray-900' ?>">
                            <i class="fas fa-users mr-3 <?= strpos($_SERVER['REQUEST_URI'], '/modules/users/collaborations.php') !== false ? 'text-blue-500' : 'text-gray-400' ?>"></i>
                            Kolaborasi
                            <?php if ($pendingCount > 0): ?>
                                <span class="ml-auto inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full"><?= $pendingCount ?></span>
                            <?php endif; ?>
                        </a>
                    </nav>
                </div>

                <!-- Mobile Lists Section -->
                <div class="mt-6 px-4">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">List Saya</h2>
                        <a href="<?= BASE_URL ?>/modules/lists/create.php" class="text-xs text-blue-500 hover:text-blue-700 transition-colors">
                            <i class="fas fa-plus"></i> Baru
                        </a>
                    </div>
                    <div class="space-y-1">
                        <?php foreach ($lists as $list): ?>
                        <a href="<?= BASE_URL ?>/modules/tasks/view.php?list_id=<?= $list['list_id'] ?>" 
                           class="group flex items-center px-2 py-2 text-sm font-medium rounded-lg transition-all duration-200 text-gray-600 hover:bg-gray-50/80 hover:text-gray-900">
                            <span class="w-2 h-2 mr-3 rounded-full" style="background-color: <?= htmlspecialchars($list['color']) ?>"></span>
                            <span class="truncate"><?= htmlspecialchars($list['title']) ?></span>
                        </a>
                        <?php endforeach; ?>
                        
                        <?php if (empty($lists)): ?>
                        <p class="text-xs text-gray-500 px-2 py-2">Belum ada daftar yang dibuat</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Mobile Quick Filters -->
                <div class="mt-6 px-4">
                    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Filter Cepat</h2>
                    <div class="space-y-1">
                        <a href="<?= BASE_URL ?>/modules/tasks/view.php?filter=today" 
                           class="group flex items-center px-2 py-2 text-sm font-medium rounded-lg transition-all duration-200 text-gray-600 hover:bg-gray-50/80 hover:text-gray-900">
                            <i class="fas fa-calendar-day text-yellow-500 mr-3"></i>
                            <span class="truncate">Jatuh Tempo Hari Ini</span>
                        </a>
                        <a href="<?= BASE_URL ?>/modules/tasks/view.php?filter=upcoming" 
                           class="group flex items-center px-2 py-2 text-sm font-medium rounded-lg transition-all duration-200 text-gray-600 hover:bg-gray-50/80 hover:text-gray-900">
                            <i class="fas fa-calendar-week text-blue-500 mr-3"></i>
                            <span class="truncate">Akan Datang</span>
                        </a>
                        <a href="<?= BASE_URL ?>/modules/tasks/view.php?filter=overdue" 
                           class="group flex items-center px-2 py-2 text-sm font-medium rounded-lg transition-all duration-200 text-gray-600 hover:bg-gray-50/80 hover:text-gray-900">
                            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                            <span class="truncate">Terlambat</span>
                        </a>
                        <a href="<?= BASE_URL ?>/modules/tasks/view.php?status=completed" 
                           class="group flex items-center px-2 py-2 text-sm font-medium rounded-lg transition-all duration-200 text-gray-600 hover:bg-gray-50/80 hover:text-gray-900">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <span class="truncate">Selesai</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const closeMobileMenu = document.getElementById('close-mobile-menu');
    const mobileSidebar = document.getElementById('mobile-sidebar');
    const sidebarContent = mobileSidebar?.querySelector('.fixed');
    
    function openSidebar() {
        mobileSidebar.classList.remove('hidden');
        setTimeout(() => {
            sidebarContent.classList.remove('translate-x-full');
        }, 10);
        document.body.classList.add('overflow-hidden');
    }
    
    function closeSidebar() {
        sidebarContent.classList.add('translate-x-full');
        setTimeout(() => {
            mobileSidebar.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }, 300);
    }
    
    mobileMenuButton?.addEventListener('click', openSidebar);
    closeMobileMenu?.addEventListener('click', closeSidebar);
    
    mobileSidebar?.addEventListener('click', (e) => {
        if (e.target === mobileSidebar) {
            closeSidebar();
        }
    });
});
</script>