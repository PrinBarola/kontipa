<?php
require_once '../../includes/config.php';

if (!isJanitor()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized']);
}

$user_id = getCurrentUserId();
$input = json_decode(file_get_contents('php://input'), true);
$current_password = $input['current_password'] ?? null;

if (!$current_password) {
    sendJSON(['success' => false, 'message' => 'Missing current password']);
}

try {
    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->prepare("SELECT password FROM janitors WHERE janitor_id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) sendJSON(['success' => false, 'message' => 'User not found']);
        $dbHash = $row['password'] ?? null;
    } else {
        $stmt = $conn->prepare("SELECT password FROM janitors WHERE janitor_id = ? LIMIT 1");
        if (!$stmt) throw new Exception($conn->error);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        if (!$row) sendJSON(['success' => false, 'message' => 'User not found']);
        $dbHash = $row['password'] ?? null;
    }

    if (empty($dbHash)) {
        sendJSON(['success' => false, 'valid' => false, 'message' => 'No password set for user']);
    }

    if (password_verify($current_password, $dbHash)) {
        sendJSON(['success' => true, 'valid' => true, 'message' => 'Current password is correct']);
    } else {
        sendJSON(['success' => true, 'valid' => false, 'message' => 'Current password is incorrect']);
    }
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => $e->getMessage()]);
}

?>
