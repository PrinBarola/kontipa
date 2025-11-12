<?php
require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized']);
}

try {
    // Get total bins count
    $result = $conn->query("SELECT COUNT(*) as total_bins FROM bins");
    $totalBins = $result->fetch_assoc()['total_bins'];

    // Get full bins count
    $result = $conn->query("SELECT COUNT(*) as full_bins FROM bins WHERE status = 'full'");
    $fullBins = $result->fetch_assoc()['full_bins'];

    // Get active janitors count
    $result = $conn->query("SELECT COUNT(*) as active_janitors FROM users WHERE role = 'janitor' AND status = 'active'");
    $activeJanitors = $result->fetch_assoc()['active_janitors'];

    // Get today's collections
    $result = $conn->query("SELECT COUNT(*) as collections_today FROM collections WHERE DATE(collected_at) = CURDATE()");
    $collectionsToday = $result->fetch_assoc()['collections_today'];

    // Get bins overview
    $binsQuery = "SELECT b.bin_id, b.bin_code, b.location, b.status, b.capacity, 
                         CONCAT(u.first_name, ' ', u.last_name) as assigned_to,
                         MAX(c.collected_at) as last_emptied
                  FROM bins b
                  LEFT JOIN users u ON b.assigned_to = u.user_id
                  LEFT JOIN collections c ON b.bin_id = c.bin_id
                  GROUP BY b.bin_id
                  ORDER BY b.status DESC, b.capacity DESC
                  LIMIT 10";
    
    $binsResult = $conn->query($binsQuery);
    $bins = [];
    while ($row = $binsResult->fetch_assoc()) {
        $bins[] = $row;
    }

    sendJSON([
        'success' => true,
        'totalBins' => $totalBins,
        'fullBins' => $fullBins,
        'activeJanitors' => $activeJanitors,
        'collectionsToday' => $collectionsToday,
        'bins' => $bins
    ]);
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => $e->getMessage()]);
}
?>
