<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../utils/auth.php';

if (!isLoggedIn()) {
    redirect('../../login.php');
}

$userId = $_SESSION['user']['user_id'];

// Ambil permintaan kolaborasi yang masuk (pending)
$incomingRequests = [];
$sql = "SELECT cr.*, l.title as list_title, u.username, u.full_name 
        FROM collaboration_requests cr
        JOIN lists l ON cr.list_id = l.list_id
        JOIN users u ON cr.sender_id = u.user_id
        WHERE cr.target_user_id = ? AND cr.status = 'pending'
        ORDER BY cr.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$incomingRequests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Ambil list yang dibagikan dengan saya
$sharedWithMe = [];
$sql = "SELECT lc.*, l.title, l.description, l.color, l.icon, u.username, u.full_name
        FROM list_collaborators lc
        JOIN lists l ON lc.list_id = l.list_id
        JOIN users u ON l.user_id = u.user_id
        WHERE lc.user_id = ?
        ORDER BY lc.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$sharedWithMe = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kolaborasi - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../../assets/css/output.css">
    <link rel="stylesheet" href="../../node_modules/@fortawesome/fontawesome-free/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <?php include_once '../../includes/header.php'; ?>
        
        <div class="flex-grow flex">
            <?php include_once '../../includes/sidebar.php'; ?>
            
            <div class="flex-1 overflow-auto">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <h1 class="text-2xl font-bold text-gray-900 mb-8">Kolaborasi</h1>

                    <!-- Permintaan Kolaborasi Masuk -->
                    <div class="mb-8">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Permintaan Kolaborasi Masuk</h2>
                        <?php if (empty($incomingRequests)): ?>
                            <p class="text-gray-500">Tidak ada permintaan kolaborasi yang menunggu.</p>
                        <?php else: ?>
                            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                                <ul class="divide-y divide-gray-200">
                                    <?php foreach ($incomingRequests as $request): ?>
                                        <li class="p-4">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <h3 class="text-sm font-medium text-gray-900">
                                                        <?= htmlspecialchars($request['list_title']) ?>
                                                    </h3>
                                                    <p class="text-sm text-gray-500">
                                                        Dari: <?= htmlspecialchars($request['full_name']) ?> (@<?= htmlspecialchars($request['username']) ?>)
                                                    </p>
                                                    <p class="text-xs text-gray-400">
                                                        <?= timeAgo($request['created_at']) ?>
                                                    </p>
                                                </div>
                                                <div class="flex space-x-2">
                                                    <button onclick="approveRequest(<?= $request['request_id'] ?>)" 
                                                            class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                                        Terima
                                                    </button>
                                                    <button onclick="rejectRequest(<?= $request['request_id'] ?>)"
                                                            class="inline-flex items-center px-3 py-1 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                                        Tolak
                                                    </button>
                                                </div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- List yang Dibagikan dengan Saya -->
                    <div>
                        <h2 class="text-lg font-medium text-gray-900 mb-4">List yang Dibagikan dengan Saya</h2>
                        <?php if (empty($sharedWithMe)): ?>
                            <p class="text-gray-500">Belum ada list yang dibagikan dengan Anda.</p>
                        <?php else: ?>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                <?php foreach ($sharedWithMe as $list): ?>
                                    <div class="bg-white overflow-hidden shadow rounded-lg">
                                        <div class="p-5">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10 rounded-full flex items-center justify-center" 
                                                     style="background-color: <?= $list['color'] ?>20;">
                                                    <i class="fas fa-<?= $list['icon'] ?>" style="color: <?= $list['color'] ?>;"></i>
                                                </div>
                                                <div class="ml-4">
                                                    <h3 class="text-lg font-medium text-gray-900">
                                                        <a href="../tasks/view.php?list_id=<?= $list['list_id'] ?>" class="hover:underline">
                                                            <?= htmlspecialchars($list['title']) ?>
                                                        </a>
                                                    </h3>
                                                    <p class="text-sm text-gray-500">
                                                        Dibagikan oleh: <?= htmlspecialchars($list['full_name']) ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <?php include_once '../../includes/footer.php'; ?>
    </div>

    <script src="../../node_modules/jquery/dist/jquery.min.js"></script>
    <script>
        function approveRequest(requestId) {
            if (confirm('Apakah Anda yakin ingin menerima permintaan kolaborasi ini?')) {
                $.ajax({
                    url: '../../api/collaboration_requests.php',
                    type: 'POST',
                    data: {
                        action: 'approve_request',
                        request_id: requestId
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.message);
                        }
                    }
                });
            }
        }

        function rejectRequest(requestId) {
            if (confirm('Apakah Anda yakin ingin menolak permintaan kolaborasi ini?')) {
                $.ajax({
                    url: '../../api/collaboration_requests.php',
                    type: 'POST',
                    data: {
                        action: 'reject_request',
                        request_id: requestId
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.message);
                        }
                    }
                });
            }
        }
    </script>
</body>
</html>