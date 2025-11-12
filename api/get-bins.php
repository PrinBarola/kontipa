<?php
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized']);
}

try {
    $status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
    
    $sql = "SELECT b.bin_id, b.bin_code, b.location, b.type, b.capacity, b.status, 
            CONCAT(u.first_name, ' ', u.last_name) as assigned_to_name
            FROM bins b
            LEFT JOIN users u ON b.assigned_to = u.user_id";
    
    if ($status_filter !== 'all') {
        $status_filter = $conn->real_escape_string($status_filter);
        $sql .= " WHERE b.status = '$status_filter'";
    }
    
    $sql .= " ORDER BY b.bin_code";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        sendJSON(['success' => false, 'message' => $conn->error]);
    }
    
    $bins = [];
    while ($row = $result->fetch_assoc()) {
        $bins[] = $row;
    }
    
    sendJSON(['success' => true, 'data' => $bins]);
    
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => $e->getMessage()]);
}
?>
