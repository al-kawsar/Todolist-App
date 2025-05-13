<?php
// modules/tasks/task_detail.php - Lihat detail tugas
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/functions.php';
require_once '../../utils/auth.php';

// Redirect ke login jika belum login
if (!isLoggedIn()) {
    redirect('../../login.php');
}

// Dapatkan data pengguna saat ini
$currentUser = $_SESSION['user'];
$userId = $currentUser['user_id'];

// Dapatkan ID tugas dari URL
$taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$taskId) {
    redirect('view.php');
}

// Periksa apakah pengguna memiliki akses ke tugas ini
$sql = "SELECT t.*, l.title as list_title, l.color as list_color
        FROM tasks t
        LEFT JOIN lists l ON t.list_id = l.list_id
        WHERE t.task_id = ? AND (t.user_id = ? OR t.task_id IN 
        (SELECT entity_id FROM collaborators WHERE entity_type = 'task' AND user_id = ?))
        AND t.is_deleted = 0";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $taskId, $userId, $userId);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$task) {
    redirect('view.php');
}

// Dapatkan tag untuk tugas
$sql = "SELECT t.*
        FROM tags t
        JOIN task_tags tt ON t.tag_id = tt.tag_id
        WHERE tt.task_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $taskId);
$stmt->execute();
$tags = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Dapatkan sub-tugas
$sql = "SELECT *
        FROM subtasks
        WHERE task_id = ?
        ORDER BY created_at ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $taskId);
$stmt->execute();
$subtasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Dapatkan komentar dengan info pengguna
$sql = "SELECT c.*, u.full_name, u.username, u.profile_picture
               FROM comments c
        JOIN users u ON c.user_id = u.user_id
        WHERE c.task_id = ?
        ORDER BY c.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $taskId);
$stmt->execute();
$comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Dapatkan lampiran
$sql = "SELECT a.*, u.full_name, u.username
        FROM attachments a
        JOIN users u ON a.user_id = u.user_id
        WHERE a.task_id = ?
        ORDER BY a.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $taskId);
$stmt->execute();
$attachments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Dapatkan pemilik tugas
$sql = "SELECT user_id, username, full_name, email, profile_picture
        FROM users
        WHERE user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $task['user_id']);
$stmt->execute();
$taskOwner = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Dapatkan kolaborator tugas
$sql = "SELECT c.*, u.username, u.full_name, u.email, u.profile_picture
        FROM collaborators c
        JOIN users u ON c.user_id = u.user_id
        WHERE c.entity_type = 'task' AND c.entity_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $taskId);
$stmt->execute();
$collaborators = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Periksa apakah pengguna saat ini adalah pemilik
$isOwner = $task['user_id'] === $userId;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($task['title']) ?> - <?= APP_NAME ?></title>
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
        <!-- Navigasi Atas -->
        <?php include_once '../../includes/header.php'; ?>
        
        <div class="flex-grow flex">
            <!-- Sidebar -->
            <?php include_once '../../includes/sidebar.php'; ?>
            
            <!-- Konten Utama -->
            <div class="flex-1 overflow-auto">
                <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div class="mb-6 flex justify-between items-center">
                        <div class="flex items-center space-x-3">
                            <a href="view.php<?= $task['list_id'] ? "?list_id={$task['list_id']}" : '' ?>" class="inline-flex items-center text-gray-500 hover:text-gray-700">
                                <i class="fas fa-arrow-left mr-1"></i> Kembali
                            </a>
                            <h1 class="text-2xl font-bold text-gray-900">Detail Tugas</h1>
                        </div>
                        
                        <div class="flex space-x-3">
                            <a href="edit.php?id=<?= $taskId ?>" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-edit mr-2"></i> Edit
                            </a>
                            <?php if ($task['status'] !== 'completed'): ?>
                                <button id="mark-complete-btn" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    <i class="fas fa-check mr-2"></i> Tandai Selesai
                                </button>
                            <?php else: ?>
                                <button id="mark-incomplete-btn" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                                    <i class="fas fa-redo mr-2"></i> Tandai Belum Selesai
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                        <!-- Header Tugas -->
                        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                            <div class="flex flex-col md:flex-row md:justify-between md:items-start">
                                <div>
                                    <h2 class="text-xl font-semibold text-gray-900"><?= htmlspecialchars($task['title']) ?></h2>
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        <?php if ($task['list_title']): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: <?= $task['list_color'] ?>20; color: <?= $task['list_color'] ?>;">
                                                <a href="view.php?list_id=<?= $task['list_id'] ?>" class="hover:underline">
                                                    <?= htmlspecialchars($task['list_title']) ?>
                                                </a>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php foreach ($tags as $tag): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: <?= $tag['color'] ?>20; color: <?= $tag['color'] ?>;">
                                                <a href="view.php?tag_id=<?= $tag['tag_id'] ?>" class="hover:underline">
                                                    <?= htmlspecialchars($tag['name']) ?>
                                                </a>
                                            </span>
                                        <?php endforeach; ?>
                                        
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?php
                                            switch ($task['priority']) {
                                                case 'low':
                                                    echo 'bg-blue-100 text-blue-800';
                                                    break;
                                                case 'medium':
                                                    echo 'bg-yellow-100 text-yellow-800';
                                                    break;
                                                case 'high':
                                                    echo 'bg-orange-100 text-orange-800';
                                                    break;
                                                case 'urgent':
                                                    echo 'bg-red-100 text-red-800';
                                                    break;
                                            }
                                            ?>">
                                            <?php
                                            switch ($task['priority']) {
                                                case 'low':
                                                    echo 'Prioritas Rendah';
                                                    break;
                                                case 'medium':
                                                    echo 'Prioritas Sedang';
                                                    break;
                                                case 'high':
                                                    echo 'Prioritas Tinggi';
                                                    break;
                                                case 'urgent':
                                                    echo 'Prioritas Mendesak';
                                                    break;
                                            }
                                            ?>
                                        </span>
                                        
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?php
                                            switch ($task['status']) {
                                                case 'pending':
                                                    echo 'bg-gray-100 text-gray-800';
                                                    break;
                                                case 'in_progress':
                                                    echo 'bg-blue-100 text-blue-800';
                                                    break;
                                                case 'completed':
                                                    echo 'bg-green-100 text-green-800';
                                                    break;
                                                case 'cancelled':
                                                    echo 'bg-red-100 text-red-800';
                                                    break;
                                            }
                                            ?>">
                                            <?php
                                            switch ($task['status']) {
                                                case 'pending':
                                                    echo 'Menunggu';
                                                    break;
                                                case 'in_progress':
                                                    echo 'Sedang Dikerjakan';
                                                    break;
                                                case 'completed':
                                                    echo 'Selesai';
                                                    break;
                                                case 'cancelled':
                                                    echo 'Dibatalkan';
                                                    break;
                                            }
                                            ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="mt-4 md:mt-0 text-sm text-gray-500 md:text-right">
                                    <p>Dibuat: <?= formatDate($task['created_at']) ?></p>
                                    <?php if ($task['updated_at'] != $task['created_at']): ?>
                                        <p>Terakhir diperbarui: <?= formatDate($task['updated_at']) ?></p>
                                    <?php endif; ?>
                                    <?php if ($task['due_date']): ?>
                                        <?php
                                        $dueDate = new DateTime($task['due_date']);
                                        $today = new DateTime();
                                        $isDue = $dueDate < $today && $task['status'] !== 'completed';
                                        ?>
                                        <p class="<?= $isDue ? 'text-red-600 font-medium' : '' ?>">
                                            Tenggat waktu: <?= formatDate($task['due_date']) ?>
                                            <?= $isDue ? ' (Terlambat)' : '' ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Konten Tugas -->
                        <div class="border-b border-gray-200">
                            <dl>
                                <?php if (!empty($task['description'])): ?>
                                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                        <dt class="text-sm font-medium text-gray-500">Deskripsi</dt>
                                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 whitespace-pre-line"><?= nl2br(htmlspecialchars($task['description'])) ?></dd>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-500">Pemilik</dt>
                                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                        <div class="flex items-center">
                                            <img class="h-8 w-8 rounded-full mr-2" src="<?= $taskOwner['profile_picture'] ? BASE_URL . '/' . PROFILE_PIC_PATH . $taskOwner['profile_picture'] : '/assets/img/pp.png' ?>" alt="<?= htmlspecialchars($taskOwner['full_name']) ?>">
                                            <span><?= htmlspecialchars($taskOwner['full_name']) ?> (@<?= htmlspecialchars($taskOwner['username']) ?>)</span>
                                        </div>
                                    </dd>
                                </div>
                                
                                <?php if (!empty($collaborators)): ?>
                                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                        <dt class="text-sm font-medium text-gray-500">Kolaborator</dt>
                                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                            <ul class="divide-y divide-gray-200">
                                                <?php foreach ($collaborators as $collaborator): ?>
                                                    <li class="py-2 flex items-center justify-between">
                                                        <div class="flex items-center">
                                                            <img class="h-8 w-8 rounded-full mr-2" src="<?= $collaborator['profile_picture'] ? BASE_URL . '/' . PROFILE_PIC_PATH . $collaborator['profile_picture'] : '/assets/img/pp.png' ?>" alt="<?= htmlspecialchars($collaborator['full_name']) ?>">
                                                            <span><?= htmlspecialchars($collaborator['full_name']) ?> (@<?= htmlspecialchars($collaborator['username']) ?>)</span>
                                                        </div>
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                            <?php
                                                            switch ($collaborator['permission']) {
                                                                case 'read':
                                                                    echo 'Akses baca';
                                                                    break;
                                                                case 'write':
                                                                    echo 'Akses tulis';
                                                                    break;
                                                                case 'admin':
                                                                    echo 'Akses admin';
                                                                    break;
                                                            }
                                                            ?>
                                                        </span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </dd>
                                    </div>
                                <?php endif; ?>
                            </dl>
                        </div>
                        
                        <!-- Sub-tugas -->
                        <div class="border-b border-gray-200">
                            <div class="bg-white px-4 py-5 sm:px-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-medium text-gray-900">Sub-tugas <?= !empty($subtasks) ? '(' . count($subtasks) . ')' : '' ?></h3>
                                    <button type="button" id="add-subtask-btn" class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-plus mr-1"></i> Tambah Sub-tugas
                                    </button>
                                </div>
                                
                                <div id="add-subtask-form" class="mb-4 hidden">
                                    <div class="flex items-center">
                                        <input type="text" id="subtask-title" class="flex-grow px-3 py-2 border border-gray-300 rounded-l-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Masukkan judul sub-tugas...">
                                        <button type="submit" id="submit-subtask-btn" class="inline-flex items-center px-4 py-2 border border-transparent rounded-r-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            <i class="fas fa-plus mr-1"></i> Tambah
                                        </button>
                                    </div>
                                </div>
                                
                                <div id="subtasks-container">
                                    <?php if (!empty($subtasks)): ?>
                                        <ul class="divide-y divide-gray-200">
                                            <?php foreach ($subtasks as $subtask): ?>
                                                <li id="subtask-<?= $subtask['subtask_id'] ?>" class="py-3 flex items-center">
                                                    <input type="checkbox" class="subtask-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" data-subtask-id="<?= $subtask['subtask_id'] ?>" <?= $subtask['status'] === 'completed' ? 'checked' : '' ?>>
                                                    <span class="ml-3 text-sm <?= $subtask['status'] === 'completed' ? 'line-through text-gray-500' : 'text-gray-900' ?>">
                                                        <?= htmlspecialchars($subtask['title']) ?>
                                                    </span>
                                                    <button type="button" class="delete-subtask-btn ml-auto text-red-400 hover:text-red-500" data-subtask-id="<?= $subtask['subtask_id'] ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p id="no-subtasks-message" class="text-sm text-gray-500">Belum ada sub-tugas.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Lampiran -->
                        <div class="border-b border-gray-200">
                            <div class="bg-gray-50 px-4 py-5 sm:px-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-medium text-gray-900">Lampiran <?= !empty($attachments) ? '(' . count($attachments) . ')' : '' ?></h3>
                                    <button type="button" id="add-attachment-btn" class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-paperclip mr-1"></i> Tambah Lampiran
                                    </button>
                                </div>
                                
                                <div id="add-attachment-form" class="mb-4 hidden">
                                    <form id="upload-attachment-form" enctype="multipart/form-data">
                                        <input type="hidden" name="task_id" value="<?= $taskId ?>">
                                        <div class="flex items-center">
                                            <input type="file" name="attachment" id="attachment-file" class="hidden">
                                            <label for="attachment-file" class="flex-grow cursor-pointer px-3 py-2 border border-gray-300 rounded-l-md shadow-sm bg-white text-gray-500 truncate" id="file-name-display">
                                                Pilih file...
                                            </label>
                                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-r-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                <i class="fas fa-upload mr-1"></i> Unggah
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                
                                <div id="attachments-container">
                                    <?php if (!empty($attachments)): ?>
                                        <ul class="divide-y divide-gray-200">
                                            <?php foreach ($attachments as $attachment): ?>
                                                <li id="attachment-<?= $attachment['attachment_id'] ?>" class="py-3 flex items-center">
                                                    <div class="flex-shrink-0 mr-3">
                                                        <?php
                                                        // Icon berdasarkan tipe file
                                                        $fileIcon = 'fa-file';
                                                        $fileType = strtolower(pathinfo($attachment['file_name'], PATHINFO_EXTENSION));
                                                        
                                                        if (in_array($fileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                                                            $fileIcon = 'fa-file-image';
                                                        } elseif (in_array($fileType, ['pdf'])) {
                                                            $fileIcon = 'fa-file-pdf';
                                                        } elseif (in_array($fileType, ['doc', 'docx'])) {
                                                            $fileIcon = 'fa-file-word';
                                                        } elseif (in_array($fileType, ['xls', 'xlsx'])) {
                                                            $fileIcon = 'fa-file-excel';
                                                        } elseif (in_array($fileType, ['zip', 'rar'])) {
                                                            $fileIcon = 'fa-file-archive';
                                                        } elseif (in_array($fileType, ['txt'])) {
                                                            $fileIcon = 'fa-file-alt';
                                                        }
                                                        ?>
                                                        <i class="fas <?= $fileIcon ?> text-gray-400 text-xl"></i>
                                                    </div>
                                                    <div class="flex-grow min-w-0">
                                                        <a href="' + '<?= BASE_URL ?>/uploads/attachments/' + attachment.filename + '" target="_blank" class="text-sm font-medium text-blue-600 hover:text-blue-800 hover:underline truncate">
                                                            ' + escapeHtml(attachment.original_name) + '
                                                        </a>
                                                        <p class="text-xs text-gray-500 mt-1">
                                                            Diunggah oleh <?= htmlspecialchars($currentUser['full_name']) ?> baru saja
                                                        </p>
                                                    </div>
                                                    <?php if ($isOwner || $attachment['user_id'] === $userId): ?>
                                                        <button type="button" class="delete-attachment-btn ml-3 text-red-400 hover:text-red-500" data-attachment-id="<?= $attachment['attachment_id'] ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p id="no-attachments-message" class="text-sm text-gray-500">Belum ada lampiran.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Komentar -->
                        <div>
                            <div class="bg-white px-4 py-5 sm:px-6">
                                <div class="mb-4">
                                    <h3 class="text-lg font-medium text-gray-900">Komentar <?= !empty($comments) ? '(' . count($comments) . ')' : '' ?></h3>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="comment-content" class="block text-sm font-medium text-gray-700">Tambah komentar</label>
                                    <div class="mt-1">
                                        <textarea id="comment-content" rows="3" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border border-gray-300 rounded-md" placeholder="Tulis komentar..."></textarea>
                                    </div>
                                    <div class="mt-2 flex justify-end">
                                        <button type="button" id="submit-comment-btn" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            <i class="fas fa-paper-plane mr-1"></i> Kirim Komentar
                                        </button>
                                    </div>
                                </div>
                                
                                <div id="comments-container">
                                    <?php if (!empty($comments)): ?>
                                        <ul class="space-y-4">
                                            <?php foreach ($comments as $comment): ?>
                                                <li id="comment-<?= $comment['comment_id'] ?>" class="bg-gray-50 rounded-lg p-4">
                                                    <div class="flex">
                                                        <div class="flex-shrink-0 mr-3">
                                                            <?php 
                                                            $avatarUrl = $currentUser['profile_picture'] 
                                                                ? BASE_URL . '/' . PROFILE_PIC_PATH . $currentUser['profile_picture'] 
                                                                : '/assets/img/pp.png';
                                                            ?>
                                                            <img class="h-10 w-10 rounded-full" src="<?= addslashes($avatarUrl) ?>" alt="<?= htmlspecialchars($currentUser['full_name']) ?>">
                                                        </div>
                                                        <div class="flex-grow">
                                                            <div class="flex items-center justify-between">
                                                                <div>
                                                                    <h4 class="text-sm font-medium text-gray-900"><?= htmlspecialchars($comment['full_name']) ?></h4>
                                                                    <p class="text-xs text-gray-500"><?= formatDate($comment['created_at']) ?></p>
                                                                </div>
                                                                <?php if ($isOwner || $comment['user_id'] === $userId): ?>
                                                                    <button type="button" class="delete-comment-btn text-red-400 hover:text-red-500" data-comment-id="<?= $comment['comment_id'] ?>">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="mt-2 text-sm text-gray-700 whitespace-pre-line">
                                                                <?= nl2br(htmlspecialchars($comment['content'])) ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p id="no-comments-message" class="text-sm text-gray-500">Belum ada komentar.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
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
            // Tandai tugas sebagai selesai/belum selesai
            $('#mark-complete-btn, #mark-incomplete-btn').click(function() {
                const newStatus = $(this).attr('id') === 'mark-complete-btn' ? 'completed' : 'pending';
                
                $.ajax({
                    url: '../../api/tasks.php',
                    type: 'POST',
                    data: {
                        action: 'update_status',
                        task_id: <?= $taskId ?>,
                        status: newStatus
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Berhasil!',
                                text: `Tugas ditandai sebagai ${newStatus === 'completed' ? 'selesai' : 'belum selesai'}`,
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                // Muat ulang halaman untuk menampilkan perubahan
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error!', response.message || 'Gagal memperbarui status tugas', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error!', 'Gagal terhubung ke server', 'error');
                    }
                });
            });
            
            // Tampilkan/sembunyikan form tambah subtugas
            $('#add-subtask-btn').click(function() {
                $('#add-subtask-form').toggleClass('hidden');
                if (!$('#add-subtask-form').hasClass('hidden')) {
                    $('#subtask-title').focus();
                    
                    // Tambahkan event handler untuk keypress Enter
                    $('#subtask-title').keypress(function(e) {
                        if (e.which == 13) { // Enter key
                            e.preventDefault();
                            $('#submit-subtask-btn').click();
                        }
                    });
                }
            });
            
            // Tambah subtugas
            $('#submit-subtask-btn').click(function() {
                const title = $('#subtask-title').val().trim();
                
                if (!title) {
                    Toastify({
                        text: "Judul subtugas tidak boleh kosong",
                        duration: 3000,
                        close: true,
                        gravity: "top",
                        position: "right",
                        backgroundColor: "#EF4444",
                        stopOnFocus: true
                    }).showToast();
                    return;
                }
                
                $.ajax({
                    url: '../../api/tasks.php',
                    type: 'POST',
                    data: {
                        action: 'add_subtask',
                        task_id: <?= $taskId ?>,
                        title: title
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Bersihkan form
                            $('#subtask-title').val('');
                            
                            // Sembunyikan pesan tidak ada subtugas jika ada
                            $('#no-subtasks-message').hide();
                            
                            // Tambahkan subtugas baru ke UI
                            const subtask = response.subtask;
                            const subtaskHtml = `
                                <li id="subtask-${subtask.subtask_id}" class="py-3 flex items-center">
                                    <input type="checkbox" class="subtask-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" data-subtask-id="${subtask.subtask_id}">
                                    <span class="ml-3 text-sm text-gray-900">
                                        ${subtask.title}
                                    </span>
                                    <button type="button" class="delete-subtask-btn ml-auto text-red-400 hover:text-red-500" data-subtask-id="${subtask.subtask_id}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </li>
                            `;
                            
                            // Jika container subtugas kosong, buat ul baru
                            if ($('#subtasks-container ul').length === 0) {
                                $('#subtasks-container').html('<ul class="divide-y divide-gray-200"></ul>');
                            }
                            
                            $('#subtasks-container ul').append(subtaskHtml);
                            
                            // Tambahkan event handler untuk subtugas baru
                            attachSubtaskHandlers();
                            
                            // Tampilkan toast sukses
                            Toastify({
                                text: "Subtugas berhasil ditambahkan",
                                duration: 3000,
                                close: true,
                                gravity: "top",
                                position: "right",
                                backgroundColor: "#10B981",
                                stopOnFocus: true
                            }).showToast();
                        } else {
                            Toastify({
                                text: response.message || "Gagal menambahkan subtugas",
                                duration: 3000,
                                close: true,
                                gravity: "top",
                                position: "right",
                                backgroundColor: "#EF4444",
                                stopOnFocus: true
                            }).showToast();
                        }
                    },
                    error: function() {
                        Toastify({
                            text: "Gagal terhubung ke server",
                            duration: 3000,
                            close: true,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "#EF4444",
                            stopOnFocus: true
                        }).showToast();
                    }
                });
            });
            
            // Fungsi untuk menambahkan event handler ke subtugas
            function attachSubtaskHandlers() {
                // Perubahan checkbox subtugas
                $('.subtask-checkbox').off('change').on('change', function() {
                    const subtaskId = $(this).data('subtask-id');
                    const isChecked = $(this).prop('checked');
                    const status = isChecked ? 'completed' : 'pending';
                    
                    $.ajax({
                        url: '../../api/tasks.php',
                        type: 'POST',
                        data: {
                            action: 'update_subtask_status',
                            subtask_id: subtaskId,
                            status: status
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                // Perbarui UI
                                const textElement = $(`#subtask-${subtaskId}`).find('span');
                                
                                if (isChecked) {
                                    textElement.addClass('line-through text-gray-500').removeClass('text-gray-900');
                                } else {
                                    textElement.removeClass('line-through text-gray-500').addClass('text-gray-900');
                                }
                                
                                Toastify({
                                    text: `Subtugas ditandai sebagai ${status === 'completed' ? 'selesai' : 'belum selesai'}`,
                                    duration: 3000,
                                    close: true,
                                    gravity: "top",
                                    position: "right",
                                    backgroundColor: "#3B82F6",
                                    stopOnFocus: true
                                }).showToast();
                            } else {
                                // Tampilkan error dan kembalikan checkbox
                                $(this).prop('checked', !isChecked);
                                
                                Toastify({
                                    text: response.message || "Gagal memperbarui subtugas",
                                    duration: 3000,
                                    close: true,
                                    gravity: "top",
                                    position: "right",
                                    backgroundColor: "#EF4444",
                                    stopOnFocus: true
                                }).showToast();
                            }
                        },
                        error: function() {
                            // Tampilkan error dan kembalikan checkbox
                            $(this).prop('checked', !isChecked);
                            
                            Toastify({
                                text: "Gagal terhubung ke server",
                                duration: 3000,
                                close: true,
                                gravity: "top",
                                position: "right",
                                backgroundColor: "#EF4444",
                                stopOnFocus: true
                            }).showToast();
                        }
                    });
                });
                
                // Hapus subtugas
                $('.delete-subtask-btn').off('click').on('click', function() {
                    const subtaskId = $(this).data('subtask-id');
                    
                    Swal.fire({
                        title: 'Hapus Subtugas?',
                        text: "Tindakan ini tidak dapat dibatalkan.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#EF4444',
                        cancelButtonColor: '#6B7280',
                        confirmButtonText: 'Ya, hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: '../../api/tasks.php',
                                type: 'POST',
                                data: {
                                    action: 'delete_subtask',
                                    subtask_id: subtaskId
                                },
                                dataType: 'json',
                                success: function(response) {
                                    if (response.success) {
                                        // Hapus dari UI
                                        $(`#subtask-${subtaskId}`).fadeOut(300, function() {
                                            $(this).remove();
                                            
                                            // Jika tidak ada subtugas lagi, tampilkan pesan
                                            if ($('#subtasks-container ul li').length === 0) {
                                                $('#subtasks-container ul').remove();
                                                $('#subtasks-container').html('<p id="no-subtasks-message" class="text-sm text-gray-500">Belum ada subtugas.</p>');
                                            }
                                        });
                                        
                                        Toastify({
                                            text: "Subtugas berhasil dihapus",
                                            duration: 3000,
                                            close: true,
                                            gravity: "top",
                                            position: "right",
                                            backgroundColor: "#10B981",
                                            stopOnFocus: true
                                        }).showToast();
                                    } else {
                                        Swal.fire('Error!', response.message || 'Gagal menghapus subtugas', 'error');
                                    }
                                },
                                error: function() {
                                    Swal.fire('Error!', 'Gagal terhubung ke server', 'error');
                                }
                            });
                        }
                    });
                });
            }
            
            // Inisialisasi handler subtugas
            attachSubtaskHandlers();
            
            // Tampilkan/sembunyikan form tambah lampiran
            $('#add-attachment-btn').click(function() {
                $('#add-attachment-form').toggleClass('hidden');
            });
            
            // Tampilkan nama file yang dipilih
            $('#attachment-file').change(function() {
                const fileName = $(this).val().split('\\').pop();
                $('#file-name-display').text(fileName || 'Pilih file...');
            });
            
            // Unggah lampiran
            $('#upload-attachment-form').submit(function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('action', 'upload_attachment');
                formData.append('task_id', <?= $taskId ?>);
                
                if (!$('#attachment-file').val()) {
                    Toastify({
                        text: "Silakan pilih file untuk diunggah",
                        duration: 3000,
                        close: true,
                        gravity: "top",
                        position: "right",
                        backgroundColor: "#EF4444",
                        stopOnFocus: true
                    }).showToast();
                    return;
                }
                
                $.ajax({
                    url: '../../api/tasks.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Reset form
                            $('#upload-attachment-form')[0].reset();
                            $('#file-name-display').text('Pilih file...');
                            $('#add-attachment-form').addClass('hidden');
                            
                            // Sembunyikan pesan tidak ada lampiran jika ada
                            $('#no-attachments-message').hide();
                            
                            // Tambahkan lampiran baru ke UI
                            const attachment = response.attachment;
                            
                            // Tentukan ikon berdasarkan tipe file
                            let fileIcon = 'fa-file';
                            const fileType = attachment.original_name.split('.').pop().toLowerCase();
                            
                            if (['jpg', 'jpeg', 'png', 'gif'].includes(fileType)) {
                                fileIcon = 'fa-file-image';
                            } else if (['pdf'].includes(fileType)) {
                                fileIcon = 'fa-file-pdf';
                            } else if (['doc', 'docx'].includes(fileType)) {
                                fileIcon = 'fa-file-word';
                            } else if (['xls', 'xlsx'].includes(fileType)) {
                                fileIcon = 'fa-file-excel';
                            } else if (['zip', 'rar'].includes(fileType)) {
                                fileIcon = 'fa-file-archive';
                            } else if (['txt'].includes(fileType)) {
                                fileIcon = 'fa-file-alt';
                            }
                            
                            const attachmentHtml = '<li id="attachment-' + attachment.id + '" class="py-3 flex items-center">' +
                                '<div class="flex-shrink-0 mr-3">' +
                                '<i class="fas ' + fileIcon + ' text-gray-400 text-xl"></i>' +
                                '</div>' +
                                '<div class="flex-grow min-w-0">' +
                                '<a href="' + '<?= BASE_URL ?>/uploads/attachments/' + attachment.filename + '" target="_blank" class="text-sm font-medium text-blue-600 hover:text-blue-800 hover:underline truncate">' +
                                escapeHtml(attachment.original_name) +
                                '</a>' +
                                '<p class="text-xs text-gray-500 mt-1">' +
                                'Diunggah oleh <?= htmlspecialchars($currentUser['full_name']) ?> baru saja' +
                                '<span class="ml-2">' + formatFileSize(attachment.size) + '</span>' +
                                '</p>' +
                                '</div>' +
                                '<button type="button" class="delete-attachment-btn ml-3 text-red-400 hover:text-red-500" data-attachment-id="' + attachment.id + '">' +
                                '<i class="fas fa-trash"></i>' +
                                '</button>' +
                                '</li>';
                            
                            // Jika container lampiran kosong, buat ul baru
                            if ($('#attachments-container ul').length === 0) {
                                $('#attachments-container').html('<ul class="divide-y divide-gray-200"></ul>');
                            }
                            
                            $('#attachments-container ul').prepend(attachmentHtml);
                            
                            // Tambahkan event handler untuk lampiran baru
                            attachAttachmentHandlers();
                            
                            Toastify({
                                text: "Lampiran berhasil diunggah",
                                duration: 3000,
                                close: true,
                                gravity: "top",
                                position: "right",
                                backgroundColor: "#10B981",
                                stopOnFocus: true
                            }).showToast();
                        } else {
                            Toastify({
                                text: response.message || "Gagal mengunggah lampiran",
                                duration: 3000,
                                close: true,
                                gravity: "top",
                                position: "right",
                                backgroundColor: "#EF4444",
                                stopOnFocus: true
                            }).showToast();
                        }
                    },
                    error: function() {
                        Toastify({
                            text: "Gagal terhubung ke server",
                            duration: 3000,
                            close: true,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "#EF4444",
                            stopOnFocus: true
                        }).showToast();
                    }
                });
            });
            
            // Fungsi untuk menambahkan event handler ke lampiran
            function attachAttachmentHandlers() {
                // Hapus lampiran
                $('.delete-attachment-btn').off('click').on('click', function() {
                    const attachmentId = $(this).data('attachment-id');
                    
                    Swal.fire({
                        title: 'Hapus Lampiran?',
                        text: "Tindakan ini tidak dapat dibatalkan.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#EF4444',
                        cancelButtonColor: '#6B7280',
                        confirmButtonText: 'Ya, hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: '../../api/tasks.php',
                                type: 'POST',
                                data: {
                                    action: 'delete_attachment',
                                    attachment_id: attachmentId
                                },
                                dataType: 'json',
                                success: function(response) {
                                    if (response.success) {
                                        // Hapus dari UI
                                        $(`#attachment-${attachmentId}`).fadeOut(300, function() {
                                            $(this).remove();
                                            
                                            // Jika tidak ada lampiran lagi, tampilkan pesan
                                            if ($('#attachments-container ul li').length === 0) {
                                                $('#attachments-container ul').remove();
                                                $('#attachments-container').html('<p id="no-attachments-message" class="text-sm text-gray-500">Belum ada lampiran.</p>');
                                            }
                                        });
                                        
                                        Toastify({
                                            text: "Lampiran berhasil dihapus",
                                            duration: 3000,
                                            close: true,
                                            gravity: "top",
                                            position: "right",
                                            backgroundColor: "#10B981",
                                            stopOnFocus: true
                                        }).showToast();
                                    } else {
                                        Swal.fire('Error!', response.message || 'Gagal menghapus lampiran', 'error');
                                    }
                                },
                                error: function() {
                                    Swal.fire('Error!', 'Gagal terhubung ke server', 'error');
                                }
                            });
                        }
                    });
                });
            }
            
            // Inisialisasi handler lampiran
            attachAttachmentHandlers();
            
            // Tambah komentar
            $('#submit-comment-btn').click(function() {
                const content = $('#comment-content').val().trim();
                
                if (!content) {
                    Toastify({
                        text: "Komentar tidak boleh kosong",
                        duration: 3000,
                        close: true,
                        gravity: "top",
                        position: "right",
                        backgroundColor: "#EF4444",
                        stopOnFocus: true
                    }).showToast();
                    return;
                }
                
                $.ajax({
                    url: '../../api/tasks.php',
                    type: 'POST',
                    data: {
                        action: 'add_comment',
                        task_id: <?= $taskId ?>,
                        content: content
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Bersihkan form
                            $('#comment-content').val('');
                            
                            // Sembunyikan pesan tidak ada komentar jika ada
                            $('#no-comments-message').hide();
                            
                            // Tambahkan komentar baru ke UI
                            const comment = response.comment;
                            
                            // Escape karakter HTML khusus untuk mencegah XSS
                            function escapeHtml(unsafe) {
                                return unsafe
                                    .replace(/&/g, "&amp;")
                                    .replace(/</g, "&lt;")
                                    .replace(/>/g, "&gt;")
                                    .replace(/"/g, "&quot;")
                                    .replace(/'/g, "&#039;");
                            }
                            
                            // Dapatkan info pengguna dari variabel PHP, di luar template literal
                            <?php 
                            $avatarUrl = $currentUser['profile_picture'] 
                                ? BASE_URL . '/' . PROFILE_PIC_PATH . $currentUser['profile_picture'] 
                                : '/assets/img/pp.png';
                            ?>
                            const userImage = "<?= addslashes($avatarUrl) ?>";
                            const userName = "<?= htmlspecialchars($currentUser['full_name']) ?>";
                            
                            // Buat HTML dengan penggabungan string sederhana alih-alih template literals
                            let commentHtml = '<li id="comment-' + comment.comment_id + '" class="bg-gray-50 rounded-lg p-4">';
                            commentHtml += '<div class="flex">';
                            commentHtml += '<div class="flex-shrink-0 mr-3">';
                            commentHtml += '<img class="h-10 w-10 rounded-full" src="' + userImage + '" alt="' + userName + '">';
                            commentHtml += '</div>';
                            commentHtml += '<div class="flex-grow">';
                            commentHtml += '<div class="flex items-center justify-between">';
                            commentHtml += '<div>';
                            commentHtml += '<h4 class="text-sm font-medium text-gray-900">' + userName + '</h4>';
                            commentHtml += '<p class="text-xs text-gray-500">Baru saja</p>';
                            commentHtml += '</div>';
                            commentHtml += '<button type="button" class="delete-comment-btn text-red-400 hover:text-red-500" data-comment-id="' + comment.comment_id + '">';
                            commentHtml += '<i class="fas fa-trash"></i>';
                            commentHtml += '</button>';
                            commentHtml += '</div>';
                            commentHtml += '<div class="mt-2 text-sm text-gray-700 whitespace-pre-line">';
                            commentHtml += escapeHtml(comment.content).replace(/\n/g, '<br>');
                            commentHtml += '</div>';
                            commentHtml += '</div>';
                            commentHtml += '</div>';
                            commentHtml += '</li>';
                            
                            // Jika container komentar kosong, buat ul baru
                            if ($('#comments-container ul').length === 0) {
                                $('#comments-container').html('<ul class="space-y-4"></ul>');
                            }
                            
                            // Tambahkan ke DOM
                            $('#comments-container ul').prepend(commentHtml);
                            
                            // Tambahkan event handler untuk komentar baru
                            attachCommentHandlers();
                            
                            Toastify({
                                text: "Komentar berhasil ditambahkan",
                                duration: 3000,
                                close: true,
                                gravity: "top",
                                position: "right",
                                backgroundColor: "#10B981",
                                stopOnFocus: true
                            }).showToast();
                        } else {
                            Toastify({
                                text: response.message || "Gagal menambahkan komentar",
                                duration: 3000,
                                close: true,
                                gravity: "top",
                                position: "right",
                                backgroundColor: "#EF4444",
                                stopOnFocus: true
                            }).showToast();
                        }
                    },
                    error: function() {
                        Toastify({
                            text: "Gagal terhubung ke server",
                            duration: 3000,
                            close: true,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "#EF4444",
                            stopOnFocus: true
                        }).showToast();
                    }
                });
            });
            
            // Fungsi untuk menambahkan event handler ke komentar
            function attachCommentHandlers() {
                // Hapus komentar
                $('.delete-comment-btn').off('click').on('click', function() {
                    const commentId = $(this).data('comment-id');
                    
                    Swal.fire({
                        title: 'Hapus Komentar?',
                        text: "Tindakan ini tidak dapat dibatalkan.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#EF4444',
                        cancelButtonColor: '#6B7280',
                        confirmButtonText: 'Ya, hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: '../../api/tasks.php',
                                type: 'POST',
                                data: {
                                    action: 'delete_comment',
                                    comment_id: commentId
                                },
                                dataType: 'json',
                                success: function(response) {
                                    if (response.success) {
                                        // Hapus dari UI
                                        $(`#comment-${commentId}`).fadeOut(300, function() {
                                            $(this).remove();
                                            
                                            // Jika tidak ada komentar lagi, tampilkan pesan
                                            if ($('#comments-container ul li').length === 0) {
                                                $('#comments-container ul').remove();
                                                $('#comments-container').html('<p id="no-comments-message" class="text-sm text-gray-500">Belum ada komentar.</p>');
                                            }
                                        });
                                        
                                        Toastify({
                                            text: "Komentar berhasil dihapus",
                                            duration: 3000,
                                            close: true,
                                            gravity: "top",
                                            position: "right",
                                            backgroundColor: "#10B981",
                                            stopOnFocus: true
                                        }).showToast();
                                    } else {
                                        Swal.fire('Kesalahan!', response.message || 'Gagal menghapus komentar', 'error');
                                    }
                                },
                                error: function() {
                                    Swal.fire('Kesalahan!', 'Gagal terhubung ke server', 'error');
                                }
                            });
                        }
                    });
                });
            }
            
            // Inisialisasi penangan komentar
            attachCommentHandlers();
        });
        
        // Fungsi pembantu untuk memformat ukuran berkas
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Byte';
            
            const k = 1024;
            const sizes = ['Byte', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    </script>
</body>
</html>