<?php
// includes/get-janitors.php
// Returns janitors. Optional ?status=active|inactive
// Includes assigned_bins count (calculated)

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

try {
    $janitors = [];
    if (isset($conn) && $conn instanceof mysqli) {
        $sql = "
            SELECT j.janitor_id, j.first_name, j.last_name, j.email, j.phone, j.status,
                   COUNT(b.bin_id) AS assigned_bins
            FROM janitors j
            LEFT JOIN bins b ON b.assigned_to = j.janitor_id
        ";
        if ($statusFilter) {
            $sql .= " WHERE j.status = ? ";
        }
        $sql .= " GROUP BY j.janitor_id ORDER BY j.first_name, j.last_name";

        if ($statusFilter) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $statusFilter);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) $janitors[] = $row;
            $stmt->close();
        } else {
            $res = $conn->query($sql);
            while ($row = $res->fetch_assoc()) $janitors[] = $row;
            if ($res) $res->free();
        }
    } elseif (isset($pdo) && $pdo instanceof PDO) {
        if ($statusFilter) {
            $stmt = $pdo->prepare("
                SELECT j.janitor_id, j.first_name, j.last_name, j.email, j.phone, j.status,
                       COUNT(b.bin_id) AS assigned_bins
                FROM janitors j
                LEFT JOIN bins b ON b.assigned_to = j.janitor_id
                WHERE j.status = :s
                GROUP BY j.janitor_id ORDER BY j.first_name, j.last_name
            ");
            $stmt->execute([':s' => $statusFilter]);
        } else {
            $stmt = $pdo->query("
                SELECT j.janitor_id, j.first_name, j.last_name, j.email, j.phone, j.status,
                       COUNT(b.bin_id) AS assigned_bins
                FROM janitors j
                LEFT JOIN bins b ON b.assigned_to = j.janitor_id
                GROUP BY j.janitor_id ORDER BY j.first_name, j.last_name
            ");
        }
        $janitors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        sendJSON(['success' => false, 'message' => 'No DB connection']);
    }

    sendJSON(['success' => true, 'data' => $janitors]);
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => $e->getMessage()]);
}