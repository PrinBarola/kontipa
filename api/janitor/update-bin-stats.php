<?php
require_once '../../includes/config.php';

if (!isJanitor()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized']);
}

$janitor_id = getCurrentUserId();
$input = json_decode(file_get_contents('php://input'), true);

$bin_id = $input['bin_id'] ?? null;
$status = $input['status'] ?? null;
$action_type = $input['action_type'] ?? null;
$notes = $input['notes'] ?? null;

if (!$bin_id || !$status) {
    sendJSON(['success' => false, 'message' => 'Missing required fields']);
}

try {
    $pdo->beginTransaction();

    // Verify bin is assigned to janitor
    $stmt = $pdo->prepare("SELECT bin_id FROM bins WHERE bin_id = ? AND assigned_to = ?");
    $stmt->execute([$bin_id, $janitor_id]);
    if (!$stmt->fetch()) {
        sendJSON(['success' => false, 'message' => 'Bin not assigned to you']);
    }

    // Update bin status
    $stmt = $pdo->prepare("UPDATE bins SET status = ?, updated_at = NOW() WHERE bin_id = ?");
    $stmt->execute([$status, $bin_id]);

    // Record collection
    $stmt = $pdo->prepare("
        INSERT INTO collections (bin_id, janitor_id, action_type, status, notes, collected_at, completed_at)
        VALUES (?, ?, ?, 'completed', ?, NOW(), NOW())
    ");
    $stmt->execute([$bin_id, $janitor_id, $action_type, $notes]);

    // Record task completion
    $stmt = $pdo->prepare("
        UPDATE tasks 
        SET status = 'completed', completed_at = NOW()
        WHERE bin_id = ? AND janitor_id = ? AND status != 'completed'
        LIMIT 1
    ");
    $stmt->execute([$bin_id, $janitor_id]);

    $pdo->commit();

    sendJSON(['success' => true, 'message' => 'Bin status updated successfully']);
} catch (Exception $e) {
    $pdo->rollBack();
    sendJSON(['success' => false, 'message' => $e->getMessage()]);
}
?>
