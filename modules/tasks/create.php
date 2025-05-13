<?php
// modules/tasks/create.php - Create new task
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
$errors = [];
$formData = [
    'title' => '',
    'description' => '',
    'list_id' => isset($_GET['list_id']) ? $_GET['list_id'] : '',
    'priority' => 'medium',
    'due_date' => '',
    'reminder' => ''
];

// Inisialisasi lists sebagai array kosong
$lists = [];

// Cek apakah ini dari undangan kolaborasi (melalui parameter GET)
$invitedListId = isset($_GET['list_id']) ? (int)$_GET['list_id'] : null;
$isInvitedCollaborator = false;
$invitedList = null;

if ($invitedListId) {
    // Cek apakah user adalah kolaborator yang diundang
    $sql = "SELECT l.*, lc.permission 
            FROM lists l 
            JOIN list_collaborators lc ON l.list_id = lc.list_id 
            WHERE l.list_id = ? AND lc.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $invitedListId, $userId);
    $stmt->execute();
    $invitedList = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($invitedList) {
        $isInvitedCollaborator = true;
        $formData['list_id'] = $invitedListId;
        // Tambahkan list yang diundang ke array lists
        $lists[] = $invitedList;
    }
} else {
    // Jika bukan dari undangan, ambil semua list user
    $sql = "SELECT DISTINCT l.*, 
            CASE 
                WHEN l.user_id = ? THEN 'owner'
                ELSE lc.permission 
            END as permission
            FROM lists l 
            LEFT JOIN list_collaborators lc ON l.list_id = lc.list_id 
            WHERE (l.user_id = ? OR lc.user_id = ?)
            AND l.is_deleted = 0 
            ORDER BY l.title ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $userId, $userId, $userId);
    $stmt->execute();
    $lists = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Debug: Tampilkan jumlah list yang ditemukan (sekarang aman karena $lists selalu array)
error_log("Found " . count($lists) . " lists for user $userId");

// Get user's tags
$sql = "SELECT * FROM tags WHERE user_id = ? ORDER BY name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$tags = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Tambahkan setelah require statements
function getUserListPermission($conn, $userId, $listId) {
    // Cek apakah user adalah pemilik list
    $sql = "SELECT user_id FROM lists WHERE list_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $listId);
    $stmt->execute();
    $result = $stmt->get_result();
    $list = $result->fetch_assoc();
    $stmt->close();

    if ($list && $list['user_id'] == $userId) {
        return 'owner';
    }

    // Cek permission dari list_collaborators
    $sql = "SELECT permission FROM list_collaborators WHERE list_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $listId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $collab = $result->fetch_assoc();
    $stmt->close();

    return $collab ? $collab['permission'] : null;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $formData = [
        'title' => sanitize($_POST['title'] ?? ''),
        'description' => sanitize($_POST['description'] ?? ''),
        'list_id' => (int)($_POST['list_id'] ?? 0),
        'priority' => sanitize($_POST['priority'] ?? 'medium'),
        'status' => sanitize($_POST['status'] ?? 'pending'),
        'due_date' => sanitize($_POST['due_date'] ?? ''),
        'reminder' => sanitize($_POST['reminder'] ?? '')
    ];

    // Validasi list_id
    if (empty($formData['list_id'])) {
        $errors['list_id'] = 'Please select a list';
    } else {
        // Jika ini kolaborator yang diundang, pastikan mereka menggunakan list yang benar
        if ($isInvitedCollaborator && $formData['list_id'] !== $invitedListId) {
            $errors['permission'] = 'Invalid list selected';
        } else {
            // Cek permission untuk list yang dipilih
            $permission = getUserListPermission($conn, $userId, $formData['list_id']);
            if (!$permission || !in_array($permission, ['owner', 'edit', 'admin'])) {
                $errors['permission'] = 'You do not have permission to create tasks in this list';
            }
        }
    }

    // Lanjutkan dengan validasi lainnya jika tidak ada error
    if (empty($errors)) {
        $errors = validateTask($formData);
    }

    // Proses pembuatan task jika tidak ada error
    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();
        try {
            // Prepare date values properly for binding
            $listId = $formData['list_id'];
            $title = $formData['title'];
            $description = $formData['description'];
            $priority = $formData['priority'];
            $status = $formData['status'];
            $dueDate = !empty($formData['due_date']) ? $formData['due_date'] : null;
            $reminder = !empty($formData['reminder']) ? $formData['reminder'] : null;
            
            // Insert into tasks table
            $sql = "INSERT INTO tasks (list_id, user_id, title, description, priority, status, due_date, reminder) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "iissssss", 
                $listId, 
                $userId, 
                $title, 
                $description, 
                $priority, 
                $status, 
                $dueDate, 
                $reminder
            );
            
            $result = $stmt->execute();
            $taskId = $conn->insert_id;
            $stmt->close();
            
            // Get list information
            $sql = "SELECT l.*, u.username as owner_username 
                    FROM lists l 
                    JOIN users u ON l.user_id = u.user_id 
                    WHERE l.list_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $listId);
            $stmt->execute();
            $list = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            // Notifikasi untuk pemilik list jika yang membuat task bukan pemilik
            if ($userId != $list['user_id']) {
                createNotification(
                    $list['user_id'],
                    'New Task Created',
                    'A new task "' . $title . '" has been created in your list "' . $list['title'] . '" by ' . $currentUser['username'],
                    'task_created',
                    'task',
                    $taskId
                );
            }
            
            // Notifikasi untuk semua kolaborator list kecuali yang membuat task
            $sql = "SELECT lc.user_id, u.username 
                    FROM list_collaborators lc
                    JOIN users u ON lc.user_id = u.user_id
                    WHERE lc.list_id = ? AND lc.user_id !=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $listId, $userId);
            $stmt->execute();
            $collaborators = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            foreach ($collaborators as $collaborator) {
                createNotification(
                    $collaborator['user_id'],
                    'New Task Added',
                    'A new task "' . $title . '" has been added to list "' . $list['title'] . '" by ' . $currentUser['username'],
                    'task_created',
                    'task',
                    $taskId
                );
            }
            
            // Add tags if any
            if (isset($_POST['tags']) && is_array($_POST['tags'])) {
                $tagSql = "INSERT INTO task_tags (task_id, tag_id) VALUES (?, ?)";
                $tagStmt = $conn->prepare($tagSql);
                
                foreach ($_POST['tags'] as $tagId) {
                    $tagStmt->bind_param("ii", $taskId, $tagId);
                    $tagStmt->execute();
                }
                
                $tagStmt->close();
            }
            
            // Add subtasks if any
            if (isset($_POST['subtasks']) && is_array($_POST['subtasks'])) {
                $subtaskSql = "INSERT INTO subtasks (task_id, title) VALUES (?, ?)";
                $subtaskStmt = $conn->prepare($subtaskSql);
                
                foreach ($_POST['subtasks'] as $subtaskTitle) {
                    if (!empty($subtaskTitle)) {
                        $subtaskStmt->bind_param("is", $taskId, $subtaskTitle);
                        $subtaskStmt->execute();
                    }
                }
                
                $subtaskStmt->close();
            }
            
            // Log activity
            logActivity($userId, 'create', 'task', $taskId);
            
            // Add reminder notification if set
            if (!empty($formData['reminder'])) {
                createNotification(
                    $userId, 
                    'Task Reminder', 
                    'Reminder for task: ' . $formData['title'], 
                    'reminder', 
                    'task', 
                    $taskId
                );
            }
            
            // Commit transaction
            $conn->commit();
            
            // Redirect to the task view page with success message
            redirect('view.php?created=true');
            
        } catch (Exception $e) {
            // Rollback in case of error
            $conn->rollback();
            $errors['general'] = 'Error creating task: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Task - <?= APP_NAME ?></title>
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="../../assets/css/output.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../../node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="../../node_modules/sweetalert2/dist/sweetalert2.min.js"></script>
    <link rel="stylesheet" href="../../node_modules/sweetalert2/dist/sweetalert2.min.css">
    <!-- Flatpickr for date inputs -->
    <link rel="stylesheet" href="../../node_modules/flatpickr/dist/flatpickr.min.css">
    <script src="../../node_modules/flatpickr/dist/flatpickr.js"></script>
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
                        <h1 class="text-2xl font-bold text-gray-900">Buat Tugas</h1>
<p class="mt-1 text-sm text-gray-500">Tambahkan tugas baru ke dalam daftar Anda</p>

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
                    
                    <?php if (!empty($errors['permission'])): ?>
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline"><?= $errors['permission'] ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <form action="create.php" method="POST" class="bg-white shadow-md rounded-lg overflow-hidden">
                        <div class="px-6 py-6">
                            <!-- Task Title -->
                            <div class="mb-6">
                                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Judul Tugas *</label>
                                <input type="text" id="title" name="title" value="<?= htmlspecialchars($formData['title']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                                <?php if (!empty($errors['title'])): ?>
                                    <p class="mt-1 text-sm text-red-600"><?= $errors['title'] ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Task Description -->
                            <div class="mb-6">
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                                <textarea id="description" name="description" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?= htmlspecialchars($formData['description']) ?></textarea>
                            </div>
                            
                            <!-- List Selection -->
                            <div class="mb-6">
                                <label for="list_id" class="block text-sm font-medium text-gray-700 mb-1">List</label>
                                <?php if ($isInvitedCollaborator && $invitedList): ?>
                                    <input type="hidden" name="list_id" value="<?= $invitedListId ?>">
                                    <div class="px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
                                        <?= htmlspecialchars($invitedList['title']) ?>
                                        (<?= ucfirst($invitedList['permission']) ?>)
                                    </div>
                                <?php else: ?>
                                    <select id="list_id" name="list_id" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" >
                                        <option value="">-- Pilih List --</option>
                                        <?php foreach ($lists as $list): ?>
                                            <option value="<?= $list['list_id'] ?>" 
                                                    <?= ($formData['list_id'] == $list['list_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($list['title']) ?>
                                                <?php if (isset($list['permission']) && $list['permission'] !== 'owner'): ?>
                                                    (<?= ucfirst($list['permission']) ?>)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    
                                    <?php if (empty($lists)): ?>
                                        <p class="mt-1 text-sm text-gray-500">
                                            Tidak ada list tersedia. 
                                            <?php if ($currentUser['role'] !== 'guest'): ?>
                                                <a href="../lists/create.php" class="text-blue-500 hover:text-blue-700">Buat</a>
                                            <?php endif; ?>
                                        </p>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if (!empty($errors['list_id'])): ?>
                                    <p class="mt-1 text-sm text-red-600"><?= $errors['list_id'] ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                <!-- Priority -->
                                <div>
                                    <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">Prioritas</label>
                                    <select id="priority" name="priority" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                        <option value="low" <?= ($formData['priority'] === 'low') ? 'selected' : '' ?>>Rendah</option>
                                        <option value="medium" <?= ($formData['priority'] === 'medium') ? 'selected' : '' ?>>Sedang</option>
                                        <option value="high" <?= ($formData['priority'] === 'high') ? 'selected' : '' ?>>Tinggi</option>
                                        <option value="urgent" <?= ($formData['priority'] === 'urgent') ? 'selected' : '' ?>>Mendesak</option>
                                    </select>
                                </div>
                                
                                <!-- Status -->
                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    <option value="pending" selected>Menunggu</option>
                                    <option value="in_progress">Sedang Dikerjakan</option>
                                    <option value="completed">Selesai</option>

                                    </select>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                <!-- Due Date -->
                                <div>
                                    <label for="due_date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Jatuh Tempo</label>
                                    <input type="text" id="due_date" name="due_date" value="<?= htmlspecialchars($formData['due_date']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 date-picker" placeholder="YYYY-MM-DD">
                                    <?php if (!empty($errors['due_date'])): ?>
                                        <p class="mt-1 text-sm text-red-600"><?= $errors['due_date'] ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Reminder -->
                                <div>
                                    <label for="reminder" class="block text-sm font-medium text-gray-700 mb-1">Pengingat</label>
                                    <input type="text" id="reminder" name="reminder" value="<?= htmlspecialchars($formData['reminder']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 datetime-picker" placeholder="YYYY-MM-DD HH:MM">
                                    <?php if (!empty($errors['reminder'])): ?>
                                        <p class="mt-1 text-sm text-red-600"><?= $errors['reminder'] ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Tags -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach ($tags as $tag): ?>
                                        <label class="inline-flex items-center py-1 px-3 rounded-full text-sm" style="background-color: <?= $tag['color'] ?>20; color: <?= $tag['color'] ?>; border: 1px solid <?= $tag['color'] ?>;">
                                            <input type="checkbox" name="tags[]" value="<?= $tag['tag_id'] ?>" class="mr-1">
                                            <?= htmlspecialchars($tag['name']) ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <?php if (empty($tags)): ?>
                                    <p class="mt-1 text-sm text-gray-500">
                                        No tags found. <a href="../tags/manage.php" class="text-blue-500 hover:text-blue-700">Create one</a>.
                                    </p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Subtasks -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Subtasks</label>
                                <div id="subtasks-container">
                                    <div class="subtask-item flex items-center mb-2">
                                        <input type="text" name="subtasks[]" class="flex-grow px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Subtask">
                                        <button type="button" class="remove-subtask ml-2 text-red-500 hover:text-red-700">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <button type="button" id="add-subtask" class="mt-2 flex items-center text-sm text-blue-500 hover:text-blue-700">
                                    <i class="fas fa-plus mr-1"></i> Add Subtask
                                </button>
                            </div>
                        </div>
                        
                        <div class="px-6 py-4 bg-gray-50 text-right">
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-plus mr-2"></i> Create Task
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="../../node_modules/jquery/dist/jquery.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Date/Time pickers
            flatpickr(".date-picker", {
                dateFormat: "Y-m-d",
                allowInput: true
            });
            
            flatpickr(".datetime-picker", {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                time_24hr: true,
                allowInput: true
            });

            flatpickr(".datetime-picker", {
                enableTime: true,
                dateFormat: "Y-m-d H:i:S", // format jam lengkap dengan detik
                time_24hr: true,
                allowInput: true
            });

            
            // Add Subtask
            $('#add-subtask').click(function() {
                const newSubtask = `
                    <div class="subtask-item flex items-center mb-2">
                        <input type="text" name="subtasks[]" class="flex-grow px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Subtask">
                        <button type="button" class="remove-subtask ml-2 text-red-500 hover:text-red-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                $('#subtasks-container').append(newSubtask);
            });
            
            // Remove Subtask
            $(document).on('click', '.remove-subtask', function() {
                $(this).closest('.subtask-item').remove();
            });
        });
    </script>
</body>
</html>