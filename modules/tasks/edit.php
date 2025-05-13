<?php
// modules/tasks/edit.php - Edit existing task
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

// Get task ID from URL
$taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$taskId) {
    redirect('view.php');
}

// Check if the task exists and belongs to the user
$sql = "SELECT * FROM tasks WHERE task_id = ? AND user_id = ? AND is_deleted = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $taskId, $userId);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$task) {
    redirect('view.php');
}

// Initialize form data with task values
$formData = [
    'title' => $task['title'],
    'description' => $task['description'],
    'list_id' => $task['list_id'],
    'priority' => $task['priority'],
    'status' => $task['status'],
    'due_date' => $task['due_date'] ? date('Y-m-d', strtotime($task['due_date'])) : '',
    'reminder' => $task['reminder'] ? date('Y-m-d H:i', strtotime($task['reminder'])) : ''
];

// Get user's lists
$sql = "SELECT * FROM lists WHERE user_id = ? AND is_deleted = 0 ORDER BY title ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$lists = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get user's tags
$sql = "SELECT * FROM tags WHERE user_id = ? ORDER BY name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$tags = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get task's tags
$sql = "SELECT tag_id FROM task_tags WHERE task_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $taskId);
$stmt->execute();
$result = $stmt->get_result();
$selectedTags = [];
while ($row = $result->fetch_assoc()) {
    $selectedTags[] = $row['tag_id'];
}
$stmt->close();

// Get task's subtasks
$sql = "SELECT * FROM subtasks WHERE task_id = ? ORDER BY created_at ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $taskId);
$stmt->execute();
$subtasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

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
    
    // Validate input
    $errors = validateTask($formData);
    
    // If validation passes, update the task
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
            
            // Jika reminder tidak kosong dan tidak memiliki bagian detik, tambahkan ":00"
            if (!empty($formData['reminder']) && !preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $formData['reminder'])) {
                $formData['reminder'] = $formData['reminder'] . ':00';
            }
            
            $reminder = !empty($formData['reminder']) ? $formData['reminder'] : null;
            
            // Update task in the database
            $sql = "UPDATE tasks SET 
                    list_id = ?, 
                    title = ?, 
                    description = ?, 
                    priority = ?, 
                    status = ?, 
                    due_date = ?, 
                    reminder = ?,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE task_id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "issssssi", 
                $listId, 
                $title, 
                $description, 
                $priority, 
                $status, 
                $dueDate, 
                $reminder,
                $taskId
            );
            
            $result = $stmt->execute();
            $stmt->close();
            
            // Delete existing tags for this task
            $sql = "DELETE FROM task_tags WHERE task_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $taskId);
            $stmt->execute();
            $stmt->close();
            
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
            
            // Handle subtasks
            if (isset($_POST['existing_subtasks']) && is_array($_POST['existing_subtasks'])) {
                // Update existing subtasks
                $updateSubtaskSql = "UPDATE subtasks SET title = ? WHERE subtask_id = ?";
                $updateSubtaskStmt = $conn->prepare($updateSubtaskSql);
                
                foreach ($_POST['existing_subtasks'] as $subtaskId => $subtaskTitle) {
                    if (!empty($subtaskTitle)) {
                        $updateSubtaskStmt->bind_param("si", $subtaskTitle, $subtaskId);
                        $updateSubtaskStmt->execute();
                    } else {
                        // Delete if empty
                        $deleteSubtaskSql = "DELETE FROM subtasks WHERE subtask_id = ?";
                        $deleteSubtaskStmt = $conn->prepare($deleteSubtaskSql);
                        $deleteSubtaskStmt->bind_param("i", $subtaskId);
                        $deleteSubtaskStmt->execute();
                        $deleteSubtaskStmt->close();
                    }
                }
                
                $updateSubtaskStmt->close();
            }
            
            // Add new subtasks if any
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
            logActivity($userId, 'update', 'task', $taskId);
            
            // Update reminder notification if set
            if (!empty($formData['reminder'])) {
                // Delete existing reminder notifications for this task
                $deleteSql = "DELETE FROM notifications WHERE user_id = ? AND entity_type = 'task' AND entity_id = ? AND type = 'reminder'";
                $deleteStmt = $conn->prepare($deleteSql);
                $deleteStmt->bind_param("ii", $userId, $taskId);
                $deleteStmt->execute();
                $deleteStmt->close();
                
                // Create new reminder
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
            redirect('view.php?updated=true');
            
        } catch (Exception $e) {
            // Rollback in case of error
            $conn->rollback();
            $errors['general'] = 'Error updating task: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Task - <?= APP_NAME ?></title>
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
                        <h1 class="text-2xl font-bold text-gray-900">Edit Tugas</h1>
                        <p class="mt-1 text-sm text-gray-500">Perbarui detail tugas</p>
                        </div>
                        <div class="flex space-x-3">
                        <a href="task_detail.php?id=<?= $taskId ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
        <i class="fas fa-eye mr-2"></i> Lihat Detail
    </a>
    <a href="view.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-500 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
        <i class="fas fa-arrow-left mr-2"></i> Kembali ke Tugas
    </a>
                        </div>
                    </div>
                    
                    <?php if (!empty($errors['general'])): ?>
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline"><?= $errors['general'] ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <form action="edit.php?id=<?= $taskId ?>" method="POST" class="bg-white shadow-md rounded-lg overflow-hidden">
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
                                <select id="list_id" name="list_id" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">-- Pilih List --</option>
                                    <?php foreach ($lists as $list): ?>
                                        <option value="<?= $list['list_id'] ?>" <?= ($formData['list_id'] == $list['list_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($list['title']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($lists)): ?>
                                    <p class="mt-1 text-sm text-gray-500">
                                        Tidak ada list ditemukan. <a href="../lists/create.php" class="text-blue-500 hover:text-blue-700">Create one</a>.
                                    </p>
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
                                        <option value="pending" <?= ($formData['status'] === 'pending') ? 'selected' : '' ?>>Menunggu</option>
                                        <option value="in_progress" <?= ($formData['status'] === 'in_progress') ? 'selected' : '' ?>>Sedang Dikerjakan</option>
                                        <option value="completed" <?= ($formData['status'] === 'completed') ? 'selected' : '' ?>>Selesai</option>
                                        <option value="cancelled" <?= ($formData['status'] === 'cancelled') ? 'selected' : '' ?>>Dibatalkan</option>
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
                                <label class="block text-sm font-medium text-gray-700 mb-1">Label</label>
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach ($tags as $tag): ?>
                                        <label class="inline-flex items-center p-2 rounded-md" style="background-color: <?= $tag['color'] ?>20;">
                                            <input type="checkbox" name="tags[]" value="<?= $tag['tag_id'] ?>" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" <?= in_array($tag['tag_id'], $selectedTags) ? 'checked' : '' ?>>
                                            <span class="ml-2 text-sm" style="color: <?= $tag['color'] ?>;"><?= htmlspecialchars($tag['name']) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <?php if (empty($tags)): ?>
                                    <p class="mt-1 text-sm text-gray-500">
                                        Tidak ada label. <a href="../tags/manage.php" class="text-blue-500 hover:text-blue-700">Buat baru</a>.
                                    </p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Existing Subtasks -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Subtugas yang ada</label>
                                <div id="existing-subtasks" class="space-y-2">
                                    <?php if (!empty($subtasks)): ?>
                                        <?php foreach ($subtasks as $subtask): ?>
                                            <div class="flex items-center">
                                                <input type="text" name="existing_subtasks[<?= $subtask['subtask_id'] ?>]" value="<?= htmlspecialchars($subtask['title']) ?>" class="flex-grow px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                                <button type="button" class="remove-subtask ml-2 text-red-500 hover:text-red-700">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-sm text-gray-500">Tidak ada subtugas yang ada</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- New Subtasks -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tambah Subtugas Baru</label>
                                <div id="subtasks-container" class="space-y-2">
                                    <div class="flex items-center">
                                        <input type="text" name="subtasks[]" class="flex-grow px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Masukkan subtugas...">
                                        <button type="button" class="remove-subtask ml-2 text-red-500 hover:text-red-700">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <button type="button" id="add-subtask" class="mt-2 inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fas fa-plus mr-1"></i> Tambah Subtask
                                </button>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="flex justify-end">
                                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fas fa-save mr-2"></i> Perbarui Tugas
                                </button>
                            </div>
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
    <!-- Flatpickr -->
    <script src="../../node_modules/flatpickr/dist/flatpickr.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize date picker
            $('.date-picker').flatpickr({
                dateFormat: "Y-m-d",
                allowInput: true
            });
            
            // Initialize datetime picker for reminders
            $('.datetime-picker').flatpickr({
                dateFormat: "Y-m-d H:i:S",
                enableTime: true,
                time_24hr: true,
                allowInput: true
            });
            
            // Add subtask
            $('#add-subtask').click(function() {
                const newSubtask = `
                    <div class="flex items-center">
                        <input type="text" name="subtasks[]" class="flex-grow px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Enter subtask...">
                        <button type="button" class="remove-subtask ml-2 text-red-500 hover:text-red-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                $('#subtasks-container').append(newSubtask);
            });
            
            // Remove subtask
            $(document).on('click', '.remove-subtask', function() {
                $(this).parent().remove();
            });
        });
    </script>
</body>
</html>