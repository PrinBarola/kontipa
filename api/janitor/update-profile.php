<?php
require_once '../../includes/config.php';

if (!isJanitor()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized']);
}

$user_id = getCurrentUserId();
$input = json_decode(file_get_contents('php://input'), true);

$first_name = $input['first_name'] ?? null;
$last_name = $input['last_name'] ?? null;
$email = $input['email'] ?? null;
$phone = $input['phone'] ?? null;

if (!$first_name || !$last_name || !$email) {
    sendJSON(['success' => false, 'message' => 'Missing required fields']);
}

try {
    // Check if email already exists (for other users)
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        sendJSON(['success' => false, 'message' => 'Email already exists']);
    }

    $stmt = $pdo->prepare("
        UPDATE users 
        SET first_name = ?, last_name = ?, email = ?, phone = ?, updated_at = NOW()
        WHERE user_id = ?
    ");
    $stmt->execute([$first_name, $last_name, $email, $phone, $user_id]);

    // Update session
    $_SESSION['name'] = "$first_name $last_name";
    $_SESSION['email'] = $email;

    sendJSON(['success' => true, 'message' => 'Profile updated successfully']);
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => $e->getMessage()]);
}
?>
