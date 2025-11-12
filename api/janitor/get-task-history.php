<?php
require_once '../../includes/config.php';

if (!isJanitor()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized']);
}

$janitor_id = getCurrentUserId();
$date_filter = $_GET['date'] ?? null;

try {
    $query = "
        SELECT 
            t.task_id,
            t.completed_at,
            b.bin_code,
            b.location,
            t.task_type,
            t.status,
            t.notes
        FROM tasks t
        JOIN bins b ON t.bin_id = b.bin_id
        WHERE t.janitor_id = ?
    ";
    $params = [$janitor_id];

    if ($date_filter) {
        $query .= " AND DATE(t.completed_at) = ?";
        $params[] = $date_filter;
    }

    $query .= " ORDER BY t.completed_at DESC LIMIT 100";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll();

    sendJSON(['success' => true, 'tasks' => $tasks]);
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => $e->getMessage()]);
}
?>
    