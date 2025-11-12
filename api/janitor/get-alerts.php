<?php
require_once '../../includes/config.php';

if (!isJanitor()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized']);
}

$janitor_id = getCurrentUserId();
$filter = $_GET['filter'] ?? 'all';

try {
    $query = "
        SELECT 
            n.notification_id,
            n.created_at,
            b.bin_code,
            b.location,
            n.notification_type,
            n.is_read
        FROM notifications n
        JOIN bins b ON n.bin_id = b.bin_id
        WHERE n.user_id = ?
    ";
    $params = [$janitor_id];

    if ($filter !== 'all') {
        $query .= " AND n.notification_type = ?";
        $params[] = $filter;
    }

    $query .= " ORDER BY n.is_read ASC, n.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $alerts = $stmt->fetchAll();

    sendJSON(['success' => true, 'alerts' => $alerts]);
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => $e->getMessage()]);
}
?>
