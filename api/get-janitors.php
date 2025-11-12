<?php
require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized']);
}

try {
    $filter = $_GET['filter'] ?? 'all';
    $filter = strtolower(trim($filter));

    $sql = "
        SELECT 
            j.janitor_id,
            j.first_name,
            j.last_name,
            j.email,
            j.phone,
            j.status,
            j.employee_id,
            COUNT(b.bin_id) AS assigned_bins
        FROM janitors j
        LEFT JOIN bins b ON j.janitor_id = b.assigned_to
        WHERE 1=1
    ";

    $params = [];
    if ($filter !== 'all') {
        $allowed = ['active', 'inactive'];
        if (in_array($filter, $allowed, true)) {
            $sql .= " AND j.status = ?";
            $params[] = $filter;
        }
    }

    $sql .= " GROUP BY j.janitor_id ORDER BY j.first_name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $janitors = $stmt->fetchAll();

    sendJSON(['success' => true, 'janitors' => $janitors]);
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => $e->getMessage()]);
}
?>