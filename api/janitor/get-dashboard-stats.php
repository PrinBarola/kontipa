<?php
require_once '../../includes/config.php';

if (!isJanitor()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized']);
}

$janitor_id = getCurrentUserId();

try {
    // Get assigned bins count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM bins WHERE assigned_to = ?");
    $stmt->execute([$janitor_id]);
    $assigned_bins = $stmt->fetch()['count'];

    // Get pending tasks count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tasks WHERE janitor_id = ? AND status IN ('pending', 'in_progress')");
    $stmt->execute([$janitor_id]);
    $pending_tasks = $stmt->fetch()['count'];

    // Get completed today
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tasks WHERE janitor_id = ? AND status = 'completed' AND DATE(completed_at) = ?");
    $stmt->execute([$janitor_id, $today]);
    $completed_today = $stmt->fetch()['count'];

    // Get recent alerts
    $stmt = $pdo->prepare("
        SELECT n.created_at, b.bin_code, b.location, n.notification_type, n.is_read
        FROM notifications n
        JOIN bins b ON n.bin_id = b.bin_id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$janitor_id]);
    $recent_alerts = $stmt->fetchAll();

    sendJSON([
        'success' => true,
        'assigned_bins_count' => $assigned_bins,
        'pending_tasks_count' => $pending_tasks,
        'completed_today_count' => $completed_today,
        'recent_alerts' => $recent_alerts
    ]);

} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => $e->getMessage()]);
}
?>
