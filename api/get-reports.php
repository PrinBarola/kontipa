<?php
require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized']);
}

try {
    $sql = "SELECT r.report_id, r.report_name, r.report_type, r.created_at, r.status, r.format
            FROM reports r
            WHERE r.generated_by = " . getCurrentUserId() . "
            ORDER BY r.created_at DESC
            LIMIT 50";

    $result = $conn->query($sql);
    $reports = [];
    
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }

    sendJSON(['success' => true, 'reports' => $reports]);
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => $e->getMessage()]);
}
?>
