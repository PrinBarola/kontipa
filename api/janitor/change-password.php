<?php
require_once '../../includes/config.php';

if (!isJanitor()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized']);
}

$user_id = getCurrentUserId();
$input = json_decode(file_get_contents('php://input'), true);

$current_password = $input['current_password'] ?? null;
$new_password = $input['new_password'] ?? null;

if (!$current_password || !$new_password) {
    sendJSON(['success' => false, 'message' => 'Missing required fields']);
}

try {
    // Fetch current password hash from janitors table
    $dbHash = null;
    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->prepare("SELECT password, first_name, last_name FROM janitors WHERE janitor_id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) sendJSON(['success' => false, 'message' => 'User not found']);
        $dbHash = $row['password'] ?? null;
        $first = $row['first_name'] ?? '';
        $last = $row['last_name'] ?? '';
    } else {
        $stmt = $conn->prepare("SELECT password, first_name, last_name FROM janitors WHERE janitor_id = ? LIMIT 1");
        if (!$stmt) throw new Exception($conn->error);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        if (!$row) sendJSON(['success' => false, 'message' => 'User not found']);
        $dbHash = $row['password'] ?? null;
        $first = $row['first_name'] ?? '';
        $last = $row['last_name'] ?? '';
    }

    // Verify current password
    if (empty($dbHash) || !password_verify($current_password, $dbHash)) {
        sendJSON(['success' => false, 'message' => 'Current password is incorrect']);
    }

    // Server-side validation for new password
    $valid = true;
    $errors = [];
    if (strlen($new_password) < 6) {
        $valid = false;
        $errors[] = 'Password must be at least 6 characters and include uppercase, lowercase, number, and special character.';
    }
    if (!preg_match('/[A-Z]/', $new_password)) { $valid = false; $errors[] = 'Password must contain at least one uppercase letter.'; }
    if (!preg_match('/[a-z]/', $new_password)) { $valid = false; $errors[] = 'Password must contain at least one lowercase letter.'; }
    if (!preg_match('/\d/', $new_password)) { $valid = false; $errors[] = 'Password must contain at least one number.'; }
    if (!preg_match('/[@$!%*?&]/', $new_password)) { $valid = false; $errors[] = 'Password must contain at least one special character (@$!%*?&).'; }

    if (!$valid) {
        sendJSON(['success' => false, 'message' => implode(' ', $errors)]);
    }

    // All good - hash and update janitors table
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->prepare("UPDATE janitors SET password = ?, updated_at = NOW() WHERE janitor_id = ?");
        $stmt->execute([$hashed_password, $user_id]);
    } else {
        $stmt = $conn->prepare("UPDATE janitors SET password = ?, updated_at = NOW() WHERE janitor_id = ?");
        if (!$stmt) throw new Exception($conn->error);
        $stmt->bind_param('si', $hashed_password, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    // Insert admin notification about password change (best-effort)
    try {
        $title = 'Changed password: ' . trim($first . ' ' . $last);
        $message = trim(($first . ' ' . $last)) . ' changed their password.';
        $notificationType = 'security';
        if (isset($pdo) && $pdo instanceof PDO) {
            $stmtN = $pdo->prepare("INSERT INTO notifications (admin_id, janitor_id, bin_id, notification_type, title, message, created_at) VALUES (:admin_id, :janitor_id, :bin_id, :type, :title, :message, NOW())");
            $stmtN->execute([':admin_id' => null, ':janitor_id' => $user_id, ':bin_id' => null, ':type' => $notificationType, ':title' => $title, ':message' => $message]);
        } else {
            if ($conn->query("SHOW TABLES LIKE 'notifications'")->num_rows > 0) {
                $stmtN = $conn->prepare("INSERT INTO notifications (admin_id, janitor_id, bin_id, notification_type, title, message, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                if ($stmtN) {
                    $adminParam = null;
                    $janitorParam = (int)$user_id;
                    $binParam = null;
                    $stmtN->bind_param('iiisss', $adminParam, $janitorParam, $binParam, $notificationType, $title, $message);
                    $stmtN->execute();
                    $stmtN->close();
                }
            }
        }
    } catch (Exception $e) {
        error_log('[api/janitor/change-password.php] notification insert failed: ' . $e->getMessage());
    }

    sendJSON(['success' => true, 'message' => 'Password changed successfully']);
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => $e->getMessage()]);
}
?>
