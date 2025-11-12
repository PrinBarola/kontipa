<?php
require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized']);
}

try {
    $filter = $_GET['filter'] ?? 'all';
    $user_id = getCurrentUserId();
    
    $sql = "SELECT n.notification_id, n.title, n.message, n.notification_type, n.is_read, 
                   n.created_at, b.bin_code, b.location
            FROM notifications n
            LEFT JOIN bins b ON n.bin_id = b.bin_id
            WHERE n.user_id = $user_id";

    if ($filter !== 'all') {
        $sql .= " AND n.notification_type = '" . $conn->real_escape_string($filter) . "'";
    }

    $sql .= " ORDER BY n.created_at DESC LIMIT 100";

    $result = $conn->query($sql);
    $notifications = [];
    
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }

    // Get unread count
    $countResult = $conn->query("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = $user_id AND is_read = FALSE");
    $unreadCount = $countResult->fetch_assoc()['unread_count'];

    sendJSON(['success' => true, 'notifications' => $notifications, 'unread_count' => $unreadCount]);
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => $e->getMessage()]);
}
?>
