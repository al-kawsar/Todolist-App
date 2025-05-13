<?php
// Tambahkan di awal file setelah mendapatkan user ID
function getUserListPermission($conn, $userId, $listId) {
    // Cek apakah user adalah pemilik list
    $sql = "SELECT 'owner' as permission FROM lists WHERE list_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $listId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        return 'owner';
    }
    
    // Cek permission kolaborator
    $sql = "SELECT permission FROM list_collaborators WHERE list_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $listId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['permission'];
    }
    
    return null;
}

// Dapatkan permission jika ada list_id
$userPermission = $listId ? getUserListPermission($conn, $userId, $listId) : null;