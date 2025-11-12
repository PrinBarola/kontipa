<?php
require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized']);
}

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!is_array($data)) {
        sendJSON(['success' => false, 'message' => 'Invalid input']);
    }

    $janitor_id = isset($data['janitor_id']) ? intval($data['janitor_id']) : 0;
    $first_name = trim($data['first_name'] ?? '');
    $last_name  = trim($data['last_name'] ?? '');
    $email      = trim($data['email'] ?? '');
    $phone      = trim($data['phone'] ?? '');
    $status     = trim($data['status'] ?? 'inactive');

    if ($janitor_id <= 0 || $first_name === '' || $last_name === '' || $email === '') {
        sendJSON(['success' => false, 'message' => 'Missing required fields']);
    }

    $sql = "UPDATE janitors
            SET first_name = :first_name,
                last_name  = :last_name,
                email      = :email,
                phone      = :phone,
                status     = :status,
                updated_at = NOW()
            WHERE janitor_id = :janitor_id
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':first_name'  => $first_name,
        ':last_name'   => $last_name,
        ':email'       => $email,
        ':phone'       => $phone,
        ':status'      => $status,
        ':janitor_id'  => $janitor_id
    ]);

    sendJSON(['success' => true, 'message' => 'Janitor updated successfully']);
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => $e->getMessage()]);
}
?>