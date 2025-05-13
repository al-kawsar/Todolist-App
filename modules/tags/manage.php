<?php
// modules/tags/manage.php - Manage user tags
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

// Get all tags for the current user
$sql = "SELECT t.*, 
        (SELECT COUNT(*) FROM task_tags tt JOIN tasks tas ON tt.task_id = tas.task_id 
         WHERE tt.tag_id = t.tag_id AND tas.is_deleted = 0) as task_count
        FROM tags t 
        WHERE t.user_id = ? 
        ORDER BY t.name ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$tags = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Process tag creation
$errors = [];
$tagData = ['name' => '', 'color' => '#3498db'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $tagData['name'] = sanitize($_POST['name'] ?? '');
    $tagData['color'] = sanitize($_POST['color'] ?? '#3498db');
    
    // Validate input
    if (empty($tagData['name'])) {
        $errors['name'] = 'Tag name is required';
    } elseif (strlen($tagData['name']) > 50) {
        $errors['name'] = 'Tag name cannot exceed 50 characters';
    }
    
    // Check if tag name already exists
    $sql = "SELECT tag_id FROM tags WHERE user_id = ? AND name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $userId, $tagData['name']);
    $stmt->execute();
    $existingTag = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($existingTag) {
        $errors['name'] = 'A tag with this name already exists';
    }
    
    // If validation passes, create the tag
    if (empty($errors)) {
        $sql = "INSERT INTO tags (user_id, name, color) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $userId, $tagData['name'], $tagData['color']);
        $result = $stmt->execute();
        $tagId = $conn->insert_id;
        $stmt->close();
        
        if ($result) {
            // Log activity
            logActivity($userId, 'create', 'tag', $tagId);
            
            // Redirect to refresh the page
            redirect('manage.php?created=true');
        } else {
            $errors['general'] = 'Failed to create tag. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tags - <?= APP_NAME ?></title>
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
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div class="mb-8 flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-4 sm:space-y-0">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Manage Tags</h1>
                            <p class="mt-1 text-sm text-gray-500">Create and manage tags to organize your tasks</p>
                        </div>
                        <button id="create-tag-btn" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-plus mr-2"></i> New Tag
                        </button>
                    </div>
                    
                    <!-- Create Tag Form (initially hidden) -->
                    <div id="create-tag-form" class="mb-8 bg-white shadow-md rounded-lg overflow-hidden <?= (!empty($errors) || isset($_POST['action'])) ? '' : 'hidden' ?>">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Create New Tag</h3>
                        </div>
                        
                        <form action="manage.php" method="POST" class="px-6 py-4">
                            <input type="hidden" name="action" value="create">
                            
                            <?php if (!empty($errors['general'])): ?>
                                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                                    <span class="block sm:inline"><?= $errors['general'] ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Tag Name -->
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Tag Name *</label>
                                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($tagData['name']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                                    <?php if (!empty($errors['name'])): ?>
                                        <p class="mt-1 text-sm text-red-600"><?= $errors['name'] ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Tag Color -->
                                <div>
                                    <label for="color" class="block text-sm font-medium text-gray-700 mb-1">Tag Color</label>
                                    <div class="flex items-center">
                                        <div id="color-picker" class="w-8 h-8 rounded-full border border-gray-300 mr-2" style="background-color: <?= $tagData['color'] ?>;"></div>
                                        <input type="text" id="color" name="color" value="<?= $tagData['color'] ?>" class="flex-grow px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4 flex justify-end">
                                <button type="button" id="cancel-create" class="mr-2 inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Cancel
                                </button>
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fas fa-plus mr-2"></i> Create Tag
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Tags List -->
                    <?php if (!empty($tags)): ?>
                        <div class="bg-white shadow overflow-hidden sm:rounded-md">
                            <ul class="divide-y divide-gray-200">
                                <?php foreach ($tags as $tag): ?>
                                    <li id="tag-<?= $tag['tag_id'] ?>" class="tag-item">
                                        <div class="px-4 py-4 flex items-center justify-between sm:px-6 hover:bg-gray-50">
                                            <div class="flex items-center">
                                                <div class="w-3 h-3 rounded-full mr-3" style="background-color: <?= $tag['color'] ?>;"></div>
                                                <div>
                                                    <h3 class="text-sm font-medium text-gray-900"><?= htmlspecialchars($tag['name']) ?></h3>
                                                    <p class="text-xs text-gray-500 mt-1">
                                                        <?= $tag['task_count'] ?> task<?= $tag['task_count'] != 1 ? 's' : '' ?>
                                                    </p>
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-center">
                                                <a href="../tasks/view.php?tag_id=<?= $tag['tag_id'] ?>" class="text-gray-400 hover:text-gray-500 mr-4" title="View Tasks">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button type="button" class="edit-tag-btn text-gray-400 hover:text-gray-500 mr-4" data-tag-id="<?= $tag['tag_id'] ?>" data-tag-name="<?= htmlspecialchars($tag['name']) ?>" data-tag-color="<?= $tag['color'] ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="delete-tag-btn text-red-400 hover:text-red-500" data-tag-id="<?= $tag['tag_id'] ?>" data-tag-name="<?= htmlspecialchars($tag['name']) ?>" data-task-count="<?= $tag['task_count'] ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="bg-white shadow overflow-hidden sm:rounded-md">
                            <div class="px-4 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No tags</h3>
                                <p class="mt-1 text-sm text-gray-500">Get started by creating a new tag.</p>
                                <div class="mt-6">
                                    <button id="empty-create-tag-btn" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <i class="fas fa-plus mr-2"></i> New Tag
                                    </button>
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

    <!-- Edit Tag Modal (hidden by default) -->
    <div id="edit-tag-modal" class="hidden fixed inset-0 backdrop-blur-sm bg-opacity-50 transition-opacity">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Edit Tag</h3>
                </div>
                
                <form id="edit-tag-form" class="px-6 py-4">
                    <input type="hidden" id="edit-tag-id" name="tag_id">
                    
                    <!-- Tag Name -->
                    <div class="mb-4">
                        <label for="edit-name" class="block text-sm font-medium text-gray-700 mb-1">Tag Name *</label>
                        <input type="text" id="edit-name" name="name" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                        <p id="edit-name-error" class="hidden mt-1 text-sm text-red-600"></p>
                    </div>
                    
                    <!-- Tag Color -->
                    <div>
                        <label for="edit-color" class="block text-sm font-medium text-gray-700 mb-1">Tag Color</label>
                        <div class="flex items-center">
                            <div id="edit-color-picker" class="w-8 h-8 rounded-full border border-gray-300 mr-2"></div>
                            <input type="text" id="edit-color" name="color" class="flex-grow px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    
                    <div class="mt-4 flex justify-end">
                        <button type="button" id="close-edit-modal" class="mr-2 inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Cancel
                        </button>
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-save mr-2"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="../../node_modules/jquery/dist/jquery.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize color picker for create form
            const pickr = Pickr.create({
                el: '#color-picker',
                theme: 'classic',
                default: '<?= $tagData['color'] ?>',
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
            
            // Initialize color picker for edit form
            const editPickr = Pickr.create({
                el: '#edit-color-picker',
                theme: 'classic',
                default: '#3498db',
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
            
            // Update color input when color changes in edit form
            editPickr.on('save', (color) => {
                const hexColor = color.toHEXA().toString();
                $('#edit-color').val(hexColor);
                $('#edit-color-picker').css('background-color', hexColor);
                editPickr.hide();
            });
            
            // Show create tag form
            $('#create-tag-btn, #empty-create-tag-btn').click(function() {
                $('#create-tag-form').removeClass('hidden');
                $('html, body').animate({
                    scrollTop: $('#create-tag-form').offset().top - 100
                }, 500);
                $('#name').focus();
            });
            
            // Hide create tag form
            $('#cancel-create').click(function() {
                $('#create-tag-form').addClass('hidden');
            });
            
            // Delete Tag
            $('.delete-tag-btn').click(function() {
                const tagId = $(this).data('tag-id');
                const tagName = $(this).data('tag-name');
                const taskCount = $(this).data('task-count');
                
                let warningText = 'This will remove the tag from all tasks.';
                if (taskCount > 0) {
                    warningText = `This tag is used in ${taskCount} task${taskCount !== 1 ? 's' : ''}. ` + warningText;
                }
                
                Swal.fire({
                    title: `Delete "${tagName}"?`,
                    text: warningText,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#EF4444',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '../../api/tags.php',
                            type: 'POST',
                            data: {
                                action: 'delete_tag',
                                tag_id: tagId
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire(
                                        'Deleted!',
                                        'Your tag has been deleted.',
                                        'success'
                                    ).then(() => {
                                        $(`#tag-${tagId}`).fadeOut(300, function() {
                                            $(this).remove();
                                            
                                            // Reload if no tags left
                                            if ($('.tag-item').length === 0) {
                                                location.reload();
                                            }
                                        });
                                    });
                                } else {
                                    Swal.fire(
                                        'Error!',
                                        response.message || 'Failed to delete tag.',
                                        'error'
                                    );
                                }
                            },
                            error: function() {
                                Swal.fire(
                                    'Error!',
                                    'Failed to connect to server.',
                                    'error'
                                );
                            }
                        });
                    }
                });
            });
            
            // Show Edit Tag Modal
            $('.edit-tag-btn').click(function() {
                const tagId = $(this).data('tag-id');
                const tagName = $(this).data('tag-name');
                const tagColor = $(this).data('tag-color');
                
                $('#edit-tag-id').val(tagId);
                $('#edit-name').val(tagName);
                $('#edit-color').val(tagColor);
                $('#edit-color-picker').css('background-color', tagColor);
                editPickr.setColor(tagColor);
                
                $('#edit-tag-modal').removeClass('hidden');
            });
            
            // Hide Edit Tag Modal
            $('#close-edit-modal').click(function() {
                $('#edit-tag-modal').addClass('hidden');
                $('#edit-name-error').addClass('hidden');
            });
            
            // Close modal when clicking outside
            $('#edit-tag-modal').click(function(e) {
                if ($(e.target).closest('.bg-white').length === 0) {
                    $('#edit-tag-modal').addClass('hidden');
                    $('#edit-name-error').addClass('hidden');
                }
            });
            
            // Handle Edit Tag Form Submission
            $('#edit-tag-form').submit(function(e) {
                e.preventDefault();
                
                const tagId = $('#edit-tag-id').val();
                const name = $('#edit-name').val();
                const color = $('#edit-color').val();
                
                if (!name.trim()) {
                    $('#edit-name-error').text('Tag name is required').removeClass('hidden');
                    return;
                }
                
                $.ajax({
                    url: '../../api/tags.php',
                    type: 'POST',
                    data: {
                        action: 'update_tag',
                        tag_id: tagId,
                        name: name,
                        color: color
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Update the tag in the UI
                            const tagItem = $(`#tag-${tagId}`);
                            tagItem.find('h3').text(name);
                            tagItem.find('.w-3.h-3').css('background-color', color);
                            
                            // Update the data attributes for the edit button
                            tagItem.find('.edit-tag-btn')
                                .data('tag-name', name)
                                .attr('data-tag-name', name)
                                .data('tag-color', color)
                                .attr('data-tag-color', color);
                            
                            // Update the data attributes for the delete button
                            tagItem.find('.delete-tag-btn')
                                .data('tag-name', name)
                                .attr('data-tag-name', name);
                            
                            // Hide the modal
                            $('#edit-tag-modal').addClass('hidden');
                            
                            // Show success message
                            Toastify({
                                text: "Berhasil Update Label",
                                duration: 3000,
                                close: true,
                                gravity: "top",
                                position: "right",
                                backgroundColor: "#10B981",
                                stopOnFocus: true
                            }).showToast();
                        } else {
                            if (response.message === 'Tag name already exists') {
                                $('#edit-name-error').text(response.message).removeClass('hidden');
                            } else {
                                Swal.fire(
                                    'Error!',
                                    response.message || 'Failed to update tag.',
                                    'error'
                                );
                            }
                        }
                    },
                    error: function() {
                        Swal.fire(
                            'Error!',
                            'Failed to connect to server.',
                            'error'
                        );
                    }
                });
            });
            
            // Show success toast messages
            <?php if (isset($_GET['created'])): ?>
                Toastify({
                    text: "Gagal Update Label",
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