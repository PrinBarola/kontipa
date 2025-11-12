<?php
require_once __DIR__ . '/../includes/config.php'; // adjust path if needed

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$first_name = trim($data['first_name'] ?? '');
$last_name  = trim($data['last_name'] ?? '');
$email      = trim($data['email'] ?? '');
$phone      = trim($data['phone'] ?? '');
$status     = $data['status'] ?? 'active';

if (empty($first_name) || empty($last_name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    // Use PDO (from includes/config.php)
    $stmt = $pdo->prepare("
        INSERT INTO janitors (first_name, last_name, email, phone, status, created_at)
        VALUES (:first_name, :last_name, :email, :phone, :status, NOW())
    ");
    $stmt->execute([
        ':first_name' => $first_name,
        ':last_name'  => $last_name,
        ':email'      => $email,
        ':phone'      => $phone,
        ':status'     => $status
    ]);
    $new_janitor_id = (int)$pdo->lastInsertId();

    // Insert notification (use your notifications schema)
    try {
        $creatorAdminId = getCurrentUserId() ?: null;
        $notificationType = 'success';
        $title = "New Janitor Account";
        $message = "New janitor '{$first_name} {$last_name}' has been created.";
        $stmtN = $pdo->prepare("
            INSERT INTO notifications (admin_id, janitor_id, bin_id, notification_type, title, message, created_at)
            VALUES (:admin_id, :janitor_id, NULL, :type, :title, :message, NOW())
        ");
        $stmtN->execute([
            ':admin_id' => $creatorAdminId,
            ':janitor_id' => $new_janitor_id,
            ':type' => $notificationType,
            ':title' => $title,
            ':message' => $message
        ]);
    } catch (Exception $e) {
        error_log("[add-janitor] notification insert failed: " . $e->getMessage());
        // don't fail the janitor creation if notification insert fails
    }

    echo json_encode(['success' => true, 'janitor_id' => $new_janitor_id]);
    exit;

} catch (Exception $e) {
    error_log("[add-janitor] " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}