<?php
require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized']);
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $janitor_id = isset($data['janitor_id']) ? intval($data['janitor_id']) : 0;

    if ($janitor_id <= 0) {
        sendJSON(['success' => false, 'message' => 'Invalid janitor_id']);
    }

    $sql = "DELETE FROM janitors WHERE janitor_id = :janitor_id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':janitor_id' => $janitor_id]);

    sendJSON(['success' => true, 'message' => 'Janitor deleted successfully']);
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => $e->getMessage()]);
}
?>