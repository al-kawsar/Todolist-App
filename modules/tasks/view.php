<?php
// modules/tasks/view.php - View tasks
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

// Get filter values
$listId = isset($_GET['list_id']) ? (int)$_GET['list_id'] : null;
$tagId = isset($_GET['tag_id']) ? (int)$_GET['tag_id'] : null;
$status = isset($_GET['status']) ? sanitize($_GET['status']) : null;
$priority = isset($_GET['priority']) ? sanitize($_GET['priority']) : null;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$filter = isset($_GET['filter']) ? sanitize($_GET['filter']) : '';

// Query untuk mengambil tasks dengan mempertimbangkan kolaborasi
$sql = "SELECT t.*, l.title as list_title, l.color as list_color, l.user_id as list_owner_id,
        CASE 
            WHEN l.user_id = ? THEN 'owner'
            ELSE lc.permission 
        END as list_permission
        FROM tasks t 
        JOIN lists l ON t.list_id = l.list_id 
        LEFT JOIN list_collaborators lc ON l.list_id = lc.list_id AND lc.user_id = ?
        WHERE (
            l.user_id = ? 
            OR 
            lc.user_id IS NOT NULL
        )
        AND t.is_deleted = 0";
$params = [$userId, $userId, $userId];
$types = "iii";

// Tambahkan filter list_id jika ada
if ($listId) {
    $sql .= " AND t.list_id = ? AND (l.user_id = ? OR lc.user_id IS NOT NULL)";
    $params[] = $listId;
    $params[] = $userId;
    $types .= "ii";
}

// Filter lainnya tetap sama
if ($tagId) {
    $sql .= " AND t.task_id IN (SELECT task_id FROM task_tags WHERE tag_id = ?)";
    $params[] = $tagId;
    $types .= "i";
}

if ($status) {
    $sql .= " AND t.status = ?";
    $params[] = $status;
    $types .= "s";
}

if ($priority) {
    $sql .= " AND t.priority = ?";
    $params[] = $priority;
    $types .= "s";
}

if (!empty($search)) {
    $sql .= " AND (t.title LIKE ? OR t.description LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ss";
}

// Filter spesial
if ($filter === 'today') {
    $sql .= " AND DATE(t.due_date) = CURDATE()";
} elseif ($filter === 'upcoming') {
    $sql .= " AND t.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
} elseif ($filter === 'overdue') {
    $sql .= " AND t.due_date < CURDATE() AND t.status != 'completed'";
}

// Pengurutan
$sql .= " ORDER BY 
    CASE 
        WHEN t.status = 'completed' THEN 1 
        ELSE 0 
    END,
    CASE 
        WHEN t.priority = 'urgent' THEN 1
        WHEN t.priority = 'high' THEN 2
        WHEN t.priority = 'medium' THEN 3
        WHEN t.priority = 'low' THEN 4
    END,
    t.due_date ASC,
    t.created_at DESC";

// Eksekusi query
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();


// Debug: Cek permission untuk list yang dipilih
if ($listId) {
    $debug_sql = "SELECT l.*, 
                  CASE 
                      WHEN l.user_id = ? THEN 'owner'
                      ELSE COALESCE(lc.permission, 'none')
                  END as permission
                  FROM lists l
                  LEFT JOIN list_collaborators lc ON l.list_id = lc.list_id AND lc.user_id = ?
                  WHERE l.list_id = ?";
    $debug_stmt = $conn->prepare($debug_sql);
    $debug_stmt->bind_param("iii", $userId, $userId, $listId);
    $debug_stmt->execute();
    $debug_list = $debug_stmt->get_result()->fetch_assoc();
    $debug_stmt->close();
    
    error_log("List Permission Debug:");
    error_log("List ID: " . $listId);
    error_log("Permission: " . ($debug_list['permission'] ?? 'none'));
    error_log("List Owner: " . $debug_list['user_id']);
}

// Get user's lists for the filter dropdown (termasuk list kolaborasi)
$sql = "SELECT DISTINCT l.*, 
        CASE 
            WHEN l.user_id = ? THEN 'owner'
            ELSE lc.permission 
        END as permission
        FROM lists l 
        LEFT JOIN list_collaborators lc ON l.list_id = lc.list_id AND lc.user_id = ?
        WHERE (l.user_id = ? OR lc.user_id IS NOT NULL)
        AND l.is_deleted = 0 
        ORDER BY 
            CASE WHEN l.user_id = ? THEN 0 ELSE 1 END,
            l.title ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $userId, $userId, $userId, $userId);
$stmt->execute();
$lists = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get user's tags for the filter dropdown
$sql = "SELECT * FROM tags WHERE user_id = ? ORDER BY name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$tags = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get current list details if list_id is set
$currentList = null;
if ($listId) {
    $sql = "SELECT l.*, 
            CASE 
                WHEN l.user_id = ? THEN 'owner'
                ELSE lc.permission 
            END as permission
            FROM lists l
            LEFT JOIN list_collaborators lc ON l.list_id = lc.list_id AND lc.user_id = ?
            WHERE l.list_id = ? AND (l.user_id = ? OR lc.user_id IS NOT NULL)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $userId, $userId, $listId, $userId);
    $stmt->execute();
    $currentList = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Get current tag details if tag_id is set
$currentTag = null;
if ($tagId) {
    $sql = "SELECT * FROM tags WHERE tag_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $tagId, $userId);
    $stmt->execute();
    $currentTag = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Tambahkan di awal file setelah mendapatkan user ID
function getUserListPermission($conn, $userId, $listId) {
    // Debug: Log parameter yang diterima
    error_log("Checking permission for User ID: $userId, List ID: $listId");
    
    // Cek apakah user adalah pemilik list
    $sql = "SELECT user_id FROM lists WHERE list_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $listId);
    $stmt->execute();
    $result = $stmt->get_result();
    $list = $result->fetch_assoc();
    $stmt->close();
    
    if ($list && $list['user_id'] == $userId) {
        error_log("User is owner of the list");
        return 'owner';
    }
    
    // Cek permission kolaborator
    $sql = "SELECT permission FROM list_collaborators WHERE list_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $listId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $collab = $result->fetch_assoc();
    $stmt->close();
    
    if ($collab) {
        error_log("User has collaborator permission: " . $collab['permission']);
        return $collab['permission'];
    }
    
    error_log("User has no permission for this list");
    return null;
}

// Dapatkan permission jika ada list_id
$userPermission = $listId ? getUserListPermission($conn, $userId, $listId) : null;

// Debug untuk melihat query dan parameter
error_log("SQL Query: " . $sql);
error_log("User ID: " . $userId);

function removeQueryParam($param) {
    $params = $_GET;
    unset($params[$param]);
    
    if (empty($params)) {
        return 'view.php';
    }
    
    return 'view.php?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks - <?= APP_NAME ?></title>
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="../../assets/css/output.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../../node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="../../node_modules/sweetalert2/dist/sweetalert2.min.js"></script>
    <link rel="stylesheet" href="../../node_modules/sweetalert2/dist/sweetalert2.min.css">
    <!-- Toastify JS (perlu diinstal dengan npm install toastify-js) -->
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
                    <div class="mb-8 flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-4 sm:space-y-0">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">
                            <?php if ($currentList): ?>
                                Tugas dari daftar "<?= htmlspecialchars($currentList['title']) ?>"
                            <?php elseif ($currentTag): ?>
                                Tugas dengan tag "<?= htmlspecialchars($currentTag['name']) ?>"
                            <?php elseif ($filter === 'today'): ?>
                                Tugas Hari Ini
                            <?php elseif ($filter === 'upcoming'): ?>
                                Tugas Mendatang
                            <?php elseif ($filter === 'overdue'): ?>
                                Tugas yang Terlambat
                            <?php elseif ($status): ?>
                                Tugas dengan Status <?= ucfirst($status) ?>
                            <?php elseif ($priority): ?>
                                Tugas Prioritas <?= ucfirst($priority) ?>
                            <?php elseif (!empty($search)): ?>
                                Hasil Pencarian untuk "<?= htmlspecialchars($search) ?>"
                            <?php else: ?>
                                Semua Tugas
                            <?php endif; ?>
                            </h1>
                            <p class="mt-1 text-sm text-gray-500">
                                <?= count($tasks) ?> tasks found
                            </p>
                        </div>
                        <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-3">
                            <?php if (!$listId || $userPermission == 'owner' || $userPermission == 'edit' || $userPermission == 'admin'): ?>
                                <a href="create.php<?= $listId ? "?list_id=$listId" : '' ?>" class="inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fas fa-plus mr-2"></i> New Task
                                </a>
                            <?php endif; ?>
                            <button id="filter-button" class="inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-filter mr-2"></i> Filter
                            </button>
                        </div>
                    </div>
                    
                    <!-- Filters Panel (initially hidden) -->
                    <div id="filters-panel" class="hidden mb-6 bg-white shadow-md rounded-lg overflow-hidden">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Filter Tasks</h3>
                            <form action="view.php" method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                                <!-- Preserve the list_id if it's set -->
                                <?php if ($listId): ?>
                                    <input type="hidden" name="list_id" value="<?= $listId ?>">
                                <?php endif; ?>
                                
                                <!-- List Filter -->
                                <div>
                                    <label for="list_filter" class="block text-sm font-medium text-gray-700">List</label>
                                    <select id="list_filter" name="list_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                        <option value="">All Lists</option>
                                        <?php foreach ($lists as $list): ?>
                                            <option value="<?= $list['list_id'] ?>" <?= ($listId == $list['list_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($list['title']) ?> 
                                                <?php if ($list['permission'] !== 'owner'): ?>
                                                    (<?= ucfirst($list['permission']) ?>)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- Tag Filter -->
                                <div>
                                    <label for="tag_filter" class="block text-sm font-medium text-gray-700">Tag</label>
                                    <select id="tag_filter" name="tag_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                        <option value="">All Tags</option>
                                        <?php foreach ($tags as $tag): ?>
                                            <option value="<?= $tag['tag_id'] ?>" <?= ($tagId == $tag['tag_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($tag['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- Status Filter -->
                                <div>
                                    <label for="status_filter" class="block text-sm font-medium text-gray-700">Status</label>
                                    <select id="status_filter" name="status" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                        <option value="">All Statuses</option>
                                        <option value="pending" <?= ($status === 'pending') ? 'selected' : '' ?>>Pending</option>
                                        <option value="in_progress" <?= ($status === 'in_progress') ? 'selected' : '' ?>>In Progress</option>
                                        <option value="completed" <?= ($status === 'completed') ? 'selected' : '' ?>>Completed</option>
                                        <option value="cancelled" <?= ($status === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                </div>
                                
                                <!-- Priority Filter -->
                                <div>
                                    <label for="priority_filter" class="block text-sm font-medium text-gray-700">Priority</label>
                                    <select id="priority_filter" name="priority" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                        <option value="">All Priorities</option>
                                        <option value="low" <?= ($priority === 'low') ? 'selected' : '' ?>>Low</option>
                                        <option value="medium" <?= ($priority === 'medium') ? 'selected' : '' ?>>Medium</option>
                                        <option value="high" <?= ($priority === 'high') ? 'selected' : '' ?>>High</option>
                                        <option value="urgent" <?= ($priority === 'urgent') ? 'selected' : '' ?>>Urgent</option>
                                    </select>
                                </div>
                                
                                <!-- Special Filter -->
                                <div>
                                    <label for="special_filter" class="block text-sm font-medium text-gray-700">Special Filter</label>
                                    <select id="special_filter" name="filter" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                    <option value="">Tidak Ada</option>
<option value="today" <?= ($filter === 'today') ? 'selected' : '' ?>>Jatuh Tempo Hari Ini</option>
<option value="upcoming" <?= ($filter === 'upcoming') ? 'selected' : '' ?>>Mendatang (7 Hari Berikutnya)</option>
<option value="overdue" <?= ($filter === 'overdue') ? 'selected' : '' ?>>Terlambat</option>

                                    </select>
                                </div>
                                
                                <!-- Search -->
                                <div>
                                    <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                                    <input type="text" name="search" id="search" value="<?= htmlspecialchars($search) ?>" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="Search tasks...">
                                </div>
                                
                                <!-- Submit and Reset -->
                                <div class="md:col-span-2 lg:col-span-3 flex justify-end space-x-3 mt-4">
                                    <button type="reset" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        Reset
                                    </button>
                                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        Apply Filters
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Active Filters -->
                    <?php if ($listId || $tagId || $status || $priority || !empty($search) || $filter): ?>
                        <div class="mb-6 flex flex-wrap items-center">
                            <span class="text-sm font-medium text-gray-700 mr-2">Active Filters:</span>
                            
                            <?php if ($listId && $currentList): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 mr-2 mb-2">
                                    List: <?= htmlspecialchars($currentList['title']) ?>
                                    <?php if ($currentList['permission'] !== 'owner'): ?>
                                        <span class="ml-1 text-xs">(<?= ucfirst($currentList['permission']) ?>)</span>
                                    <?php endif; ?>
                                    <a href="<?= removeQueryParam('list_id') ?>" class="ml-1 text-blue-500 hover:text-blue-700">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($tagId && $currentTag): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium mr-2 mb-2" style="background-color: <?= $currentTag['color'] ?>20; color: <?= $currentTag['color'] ?>;">
                                    Tag: <?= htmlspecialchars($currentTag['name']) ?>
                                    <a href="<?= removeQueryParam('tag_id') ?>" class="ml-1" style="color: <?= $currentTag['color'] ?>;">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($status): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 mr-2 mb-2">
                                    Status: <?= ucfirst($status) ?>
                                    <a href="<?= removeQueryParam('status') ?>" class="ml-1 text-green-500 hover:text-green-700">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($priority): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 mr-2 mb-2">
                                    Priority: <?= ucfirst($priority) ?>
                                    <a href="<?= removeQueryParam('priority') ?>" class="ml-1 text-yellow-500 hover:text-yellow-700">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($search)): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800 mr-2 mb-2">
                                    Search: <?= htmlspecialchars($search) ?>
                                    <a href="<?= removeQueryParam('search') ?>" class="ml-1 text-purple-500 hover:text-purple-700">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($filter): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 mr-2 mb-2">
                                    <?php 
                                    $filterLabel = '';
                                    switch ($filter) {
                                        case 'today':
                                            $filterLabel = 'Jatuh Tempo Hari Ini';
                                        break;
                                        case 'upcoming':
                                            $filterLabel = 'Mendatang (7 Hari Berikutnya)';
                                            break;
                                        case 'overdue':
                                            $filterLabel = 'Terlambat';
                                            break;

                                    }
                                    echo $filterLabel;
                                    ?>
                                    <a href="<?= removeQueryParam('filter') ?>" class="ml-1 text-red-500 hover:text-red-700">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </span>
                            <?php endif; ?>
                            
                            <a href="view.php<?= $listId ? "?list_id=$listId" : '' ?>" class="text-sm text-blue-500 hover:text-blue-700 mb-2">
                                Clear All Filters
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Tasks List -->
                    <div class="bg-white shadow overflow-hidden sm:rounded-md">
                        <?php if (!empty($tasks)): ?>
                            <ul class="divide-y divide-gray-200">
                                <?php foreach ($tasks as $task): ?>
                                    <li id="task-<?= $task['task_id'] ?>" class="task-item">
                                        <div class="px-4 py-4 flex items-center sm:px-6 hover:bg-gray-50">
                                            <div class="min-w-0 flex-1 sm:flex sm:items-center sm:justify-between">
                                                <div class="flex items-center">
                                                    <!-- Task Status Checkbox -->
                                                    <div class="flex-shrink-0 mr-3">
                                                        <input type="checkbox" class="task-status-checkbox h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" data-task-id="<?= $task['task_id'] ?>" <?= ($task['status'] === 'completed') ? 'checked' : '' ?>>
                                                    </div>
                                                    
                                                    <div>
                                                        <!-- Task Title -->
                                                        <div class="flex items-center">
                                                            <h3 class="text-sm font-medium <?= ($task['status'] === 'completed') ? 'line-through text-gray-500' : 'text-gray-900' ?>">
                                                                <?= htmlspecialchars($task['title']) ?>
                                                            </h3>
                                                            
                                                            <!-- Priority Indicator -->
                                                            <?php if ($task['priority'] === 'high' || $task['priority'] === 'urgent'): ?>
                                                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= ($task['priority'] === 'urgent') ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                                                    <?= ucfirst($task['priority']) ?>
                                                                </span>
                                                            <?php endif; ?>
                                                            
                                                            <!-- Status Badge for In Progress -->
                                                            <?php if ($task['status'] === 'in_progress'): ?>
                                                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                                    In Progress
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        
                                                        <!-- List Badge -->
                                                        <?php if ($task['list_title'] && !$listId): ?>
                                                            <div class="mt-1">
                                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: <?= $task['list_color'] ?>20; color: <?= $task['list_color'] ?>;">
                                                                    <a href="view.php?list_id=<?= $task['list_id'] ?>" class="hover:underline">
                                                                        <?= htmlspecialchars($task['list_title']) ?>
                                                                    </a>
                                                                </span>
                                                            </div>
                                                        <?php endif; ?>
                                                        
                                                        <!-- Due Date -->
                                                        <?php if ($task['due_date']): ?>
                                                            <div class="mt-1 flex items-center text-sm text-gray-500">
                                                                <i class="far fa-calendar-alt mr-1.5 text-gray-400"></i>
                                                                <p>
                                                                    <?php
                                                                    $dueDate = new DateTime($task['due_date']);
                                                                    $today = new DateTime();
                                                                    $dueText = 'Jatuh Tempo: ' . $dueDate->format('d M, Y');
                                                                    
                                                                    if ($task['status'] !== 'completed') {
                                                                        if ($dueDate < $today) {
                                                                            echo "<span class='text-red-600 font-medium'>$dueText (Terlambat)</span>";
                                                                        } elseif ($dueDate->format('Y-m-d') === $today->format('Y-m-d')) {
                                                                            echo "<span class='text-orange-600 font-medium'>$dueText (Hari Ini)</span>";
                                                                        } else {
                                                                            echo $dueText;
                                                                        }
                                                                    } else {
                                                                        echo $dueText;
                                                                    }
                                                                    
                                                                    ?>
                                                                </p>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                
                                                <!-- Action Buttons -->
                                                <div class="mt-4 flex-shrink-0 sm:mt-0 sm:ml-5">
                                                    <div class="flex -space-x-1 overflow-hidden">
                                                        <!-- View Button -->
                                                        <a href="task_detail.php?id=<?= $task['task_id'] ?>" class="text-gray-400 hover:text-gray-500 ml-4">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        
                                                        <?php if ($task['user_id'] == $userId || $task['list_permission'] == 'admin' || $task['list_permission'] == 'edit'): ?>
                                                            <!-- Edit Button -->
                                                            <a href="edit.php?id=<?= $task['task_id'] ?>" class="text-gray-400 hover:text-gray-500 ml-4">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            
                                                            <!-- Delete Button -->
                                                            <button type="button" class="delete-task-btn text-red-400 hover:text-red-500 ml-4" data-task-id="<?= $task['task_id'] ?>">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="px-4 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No tasks found</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    <?php if (!empty($search) || $listId || $tagId || $status || $priority || $filter): ?>
                                        No tasks match your current filters.
                                    <?php else: ?>
                                        Get started by creating a new task.
                                    <?php endif; ?>
                                </p>
                                <div class="mt-6">
                                    <?php if (!$listId || $userPermission == 'owner' || $userPermission == 'edit' || $userPermission == 'admin'): ?>
                                        <a href="create.php<?= $listId ? "?list_id=$listId" : '' ?>" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            <i class="fas fa-plus mr-2"></i> New Task
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="../../node_modules/jquery/dist/jquery.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Filter panel toggle
            $('#filter-button').click(function() {
                $('#filters-panel').toggle();
            });
            
            // Task Status Checkbox
            $('.task-status-checkbox').change(function() {
                const taskId = $(this).data('task-id');
                const isChecked = $(this).prop('checked');
                const status = isChecked ? 'completed' : 'pending';
                
                // Update task status via AJAX
                $.ajax({
                    url: '../../api/tasks.php',
                    type: 'POST',
                    data: {
                        action: 'update_status',
                        task_id: taskId,
                        status: status
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Update UI
                            const taskTitle = $(`#task-${taskId}`).find('h3');
                            
                            if (isChecked) {
                                taskTitle.addClass('line-through text-gray-500');
                                
                                Toastify({
                                    text: "Tugas telah ditandai selesai",
                                    duration: 3000,
                                    close: true,
                                    gravity: "top",
                                    position: "right",
                                    backgroundColor: "#10B981",
                                    stopOnFocus: true
                                }).showToast();
                            } else {
                                taskTitle.removeClass('line-through text-gray-500');
                                
                                Toastify({
                                    text: "Tugas telah ditandai ditunda",
                                    duration: 3000,
                                    close: true,
                                    gravity: "top",
                                    position: "right",
                                    backgroundColor: "#3B82F6",
                                    stopOnFocus: true
                                }).showToast();
                            }
                        } else {
                            // Show error
                            Toastify({
                                text: "Error updating task status",
                                duration: 3000,
                                close: true,
                                gravity: "top"
                            }).showToast();
                        }
                    },
                    error: function() {
                        // Show error
                        Toastify({
                            text: "Error connecting to server",
                            duration: 3000,
                            close: true,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "#EF4444",
                            stopOnFocus: true
                        }).showToast();
                        
                        // Revert checkbox to previous state
                        $(this).prop('checked', !isChecked);
                    }
                });
            });
            
            // Delete Task
            $('.delete-task-btn').click(function() {
                const taskId = $(this).data('task-id');
                
                // Show confirmation dialog
                Swal.fire({
                    title: 'Apakah anda yakin?',
                    text: "Tindakan ini tidak dapat dibatalkan!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#EF4444',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: 'Ya, Hapus!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Delete task via AJAX
                        $.ajax({
                            url: '../../api/tasks.php',
                            type: 'POST',
                            data: {
                                action: 'delete_task',
                                task_id: taskId
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    // Remove task from DOM
                                    $(`#task-${taskId}`).fadeOut(300, function() {
                                        $(this).remove();
                                        
                                        // Show success message
                                        Swal.fire(
                                            'Terhapus!',
                                            'Tugas anda berhasil dihapus.',
                                            'success'
                                        );
                                        
                                        // Check if no tasks left
                                        if ($('.task-item').length === 0) {
                                            location.reload();
                                        }
                                    });
                                } else {
                                    // Show error
                                    Swal.fire(
                                        'Gagal!',
                                        'Gagal menghapus tugas.',
                                        'error'
                                    );
                                }
                            },
                            error: function() {
                                // Show error
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
            
            // Show toast for task creation success
            <?php if (isset($_GET['created'])): ?>
                Toastify({
                    text: "Tugas berhasil dibuat",
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