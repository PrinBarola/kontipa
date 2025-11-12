<?php
// includes/add-bin.php
// Adds a new bin. Accepts JSON POST body.
// Expects config.php to provide $conn (mysqli) or $pdo.

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

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) sendJSON(['success' => false, 'message' => 'Invalid JSON']);

$required = ['bin_code','location','type','capacity','status'];
foreach ($required as $r) {
    if (!isset($body[$r]) || $body[$r] === '') {
        sendJSON(['success' => false, 'message' => "Missing required field: $r"]);
    }
}

$bin_code = trim($body['bin_code']);
$location = trim($body['location']);
$type = trim($body['type']);
$capacity = (int)$body['capacity'];
$status = trim($body['status']);
$assigned_to = isset($body['assigned_to']) && $body['assigned_to'] !== '' ? $body['assigned_to'] : null;

try {
    if (isset($conn) && $conn instanceof mysqli) {
        $stmt = $conn->prepare("INSERT INTO bins (bin_code, location, type, capacity, status, assigned_to, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param('sssiss', $bin_code, $location, $type, $capacity, $status, $assigned_to);
        $ok = $stmt->execute();
        if (!$ok) {
            $err = $stmt->error;
            $stmt->close();
            sendJSON(['success' => false, 'message' => $err]);
        }
        $insertId = $stmt->insert_id;
        $stmt->close();
        sendJSON(['success' => true, 'bin_id' => $insertId]);
    } elseif (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->prepare("INSERT INTO bins (bin_code, location, type, capacity, status, assigned_to, created_at) VALUES (:code, :loc, :type, :cap, :status, :assigned, NOW())");
        $stmt->execute([
            ':code' => $bin_code,
            ':loc' => $location,
            ':type' => $type,
            ':cap' => $capacity,
            ':status' => $status,
            ':assigned' => $assigned_to,
        ]);
        sendJSON(['success' => true, 'bin_id' => (int)$pdo->lastInsertId()]);
    } else {
        sendJSON(['success' => false, 'message' => 'No DB connection']);
    }
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => $e->getMessage()]);
}