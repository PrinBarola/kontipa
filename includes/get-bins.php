<?php
// includes/get-bins.php
// Returns a list of bins. Optional ?status=full|empty|needs_attention etc.
// Expects includes/config.php to create $conn (mysqli) or $pdo (PDO).

require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

if (!function_exists('sendJSON')) {
    function sendJSON($payload) {
        echo json_encode($payload);
        exit;
    }
}

if (function_exists('isLoggedIn') && function_exists('isAdmin')) {
    if (!isLoggedIn() || !isAdmin()) {
        sendJSON(['success' => false, 'message' => 'Unauthorized']);
    }
}

$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';

// Build and return
try {
    $bins = [];
    if (isset($conn) && $conn instanceof mysqli) {
        if ($statusFilter) {
            $stmt = $conn->prepare("
                SELECT b.bin_id, b.bin_code, b.location, b.type, b.status, b.capacity,
                       b.assigned_to, CONCAT(j.first_name,' ',j.last_name) as assigned_to_name,
                       MAX(c.collected_at) as last_emptied
                FROM bins b
                LEFT JOIN janitors j ON b.assigned_to = j.janitor_id
                LEFT JOIN collections c ON b.bin_id = c.bin_id
                WHERE b.status = ?
                GROUP BY b.bin_id
                ORDER BY b.capacity DESC
            ");
            $stmt->bind_param('s', $statusFilter);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) $bins[] = $row;
            $stmt->close();
        } else {
            $query = "
                SELECT b.bin_id, b.bin_code, b.location, b.type, b.status, b.capacity,
                       b.assigned_to, CONCAT(j.first_name,' ',j.last_name) as assigned_to_name,
                       MAX(c.collected_at) as last_emptied
                FROM bins b
                LEFT JOIN janitors j ON b.assigned_to = j.janitor_id
                LEFT JOIN collections c ON b.bin_id = c.bin_id
                GROUP BY b.bin_id
                ORDER BY FIELD(b.status, 'full','needs_attention','in_progress','empty','out_of_service'), b.capacity DESC
                LIMIT 100
            ";
            $res = $conn->query($query);
            while ($row = $res->fetch_assoc()) $bins[] = $row;
            if ($res) $res->free();
        }
    } elseif (isset($pdo) && $pdo instanceof PDO) {
        if ($statusFilter) {
            $stmt = $pdo->prepare("
                SELECT b.bin_id, b.bin_code, b.location, b.type, b.status, b.capacity,
                       b.assigned_to, CONCAT(j.first_name,' ',j.last_name) as assigned_to_name,
                       MAX(c.collected_at) as last_emptied
                FROM bins b
                LEFT JOIN janitors j ON b.assigned_to = j.janitor_id
                LEFT JOIN collections c ON b.bin_id = c.bin_id
                WHERE b.status = :status
                GROUP BY b.bin_id
                ORDER BY b.capacity DESC
            ");
            $stmt->execute([':status' => $statusFilter]);
            $bins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->query("
                SELECT b.bin_id, b.bin_code, b.location, b.type, b.status, b.capacity,
                       b.assigned_to, CONCAT(j.first_name,' ',j.last_name) as assigned_to_name,
                       MAX(c.collected_at) as last_emptied
                FROM bins b
                LEFT JOIN janitors j ON b.assigned_to = j.janitor_id
                LEFT JOIN collections c ON b.bin_id = c.bin_id
                GROUP BY b.bin_id
                ORDER BY FIELD(b.status, 'full','needs_attention','in_progress','empty','out_of_service'), b.capacity DESC
                LIMIT 100
            ");
            $bins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } else {
        sendJSON(['success' => false, 'message' => 'No DB connection']);
    }

    sendJSON(['success' => true, 'data' => $bins]);
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => $e->getMessage()]);
}