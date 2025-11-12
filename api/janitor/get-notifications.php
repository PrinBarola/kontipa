<?php
require_once '../../includes/config.php';

if (!isJanitor()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized']);
}

$janitor_id = getCurrentUserId();

try {
    $stmt = $pdo->prepare("
        SELECT 
            n.notification_id,
            n.created_at,
            b.bin_code,
            n.notification_type,
            n.title,
            n.message,
            n.is_read
        FROM notifications n
        LEFT JOIN bins b ON n.bin_id = b.bin_id
        WHERE n.user_id = ?
        ORDER BY n.is_read ASC, n.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$janitor_id]);
    $notifications = $stmt->fetchAll();

    $unread_count = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $unread_count->execute([$janitor_id]);
    $unread = $unread_count->fetch()['count'];

    sendJSON(['success' => true, 'notifications' => $notifications, 'unread_count' => $unread]);
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => $e->getMessage()]);
}
?>
