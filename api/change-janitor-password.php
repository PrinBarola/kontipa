<?php
require_once __DIR__ . '/../includes/config.php';

// This endpoint specifically handles janitor password changes and ONLY touches the janitors table.
// It accepts application/json or form-encoded POST (serialized form).

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Get janitor ID from session - try multiple possible keys
$user_id = null;
if (isset($_SESSION['janitor_id'])) {
    $user_id = intval($_SESSION['janitor_id']);
} elseif (isset($_SESSION['user_id'])) {
    $user_id = intval($_SESSION['user_id']);
} elseif (isset($_SESSION['id'])) {
    $user_id = intval($_SESSION['id']);
}

// Verify they are a janitor (based on session key, not table check)
$is_janitor = isset($_SESSION['janitor_id']);

if (!$is_janitor || $user_id <= 0) {
    sendJSON(['success' => false, 'message' => 'Unauthorized or session expired']);
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) $data = $_POST;

$current_password = isset($data['current_password']) ? trim($data['current_password']) : '';
$new_password = isset($data['new_password']) ? trim($data['new_password']) : '';

if ($current_password === '' || $new_password === '') {
    sendJSON(['success' => false, 'message' => 'Missing required fields']);
}

try {
    // Fetch stored hash from janitors table
    $dbHash = null;
    $first = '';
    $last = '';
    
    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->prepare('SELECT password, first_name, last_name FROM janitors WHERE janitor_id = ? LIMIT 1');
        $stmt->execute([$user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            error_log('[change-janitor-password] janitor_id=' . $user_id . ' not found in janitors table');
            sendJSON(['success' => false, 'message' => 'Janitor profile not found']);
        }
        $dbHash = $row['password'] ?? null;
        $first = $row['first_name'] ?? '';
        $last = $row['last_name'] ?? '';
    } else {
        if (!isset($conn)) throw new Exception('No DB connection');
        $stmt = $conn->prepare('SELECT password, first_name, last_name FROM janitors WHERE janitor_id = ? LIMIT 1');
        if (!$stmt) throw new Exception($conn->error ?: 'DB prepare failed');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        if (!$row) {
            error_log('[change-janitor-password] janitor_id=' . $user_id . ' not found in janitors table');
            sendJSON(['success' => false, 'message' => 'Janitor profile not found']);
        }
        $dbHash = $row['password'] ?? null;
        $first = $row['first_name'] ?? '';
        $last = $row['last_name'] ?? '';
    }

    if (empty($dbHash) || !password_verify($current_password, $dbHash)) {
        sendJSON(['success' => false, 'message' => 'Current password is incorrect']);
    }

    // Basic validation for new password (adjust policy as needed)
    if (strlen($new_password) < 6) {
        sendJSON(['success' => false, 'message' => 'New password must be at least 6 characters']);
    }

    $hashed = password_hash($new_password, PASSWORD_DEFAULT);

    // Update janitors table
    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->prepare('UPDATE janitors SET password = ?, updated_at = NOW() WHERE janitor_id = ?');
        $stmt->execute([$hashed, $user_id]);
    } else {
        $stmt = $conn->prepare('UPDATE janitors SET password = ?, updated_at = NOW() WHERE janitor_id = ?');
        if (!$stmt) throw new Exception($conn->error ?: 'DB prepare failed');
        $stmt->bind_param('si', $hashed, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    // Optional: create a notification (best-effort)
    try {
        $title = 'Changed password: ' . trim($first . ' ' . $last);
        $message = trim(($first . ' ' . $last)) . ' changed their password.';
        if (isset($pdo) && $pdo instanceof PDO) {
            $stmtN = $pdo->prepare('INSERT INTO notifications (admin_id, janitor_id, bin_id, notification_type, title, message, created_at) VALUES (:admin_id, :janitor_id, :bin_id, :type, :title, :message, NOW())');
            $stmtN->execute([':admin_id' => null, ':janitor_id' => $user_id, ':bin_id' => null, ':type' => 'security', ':title' => $title, ':message' => $message]);
        } elseif (isset($conn) && $conn->query("SHOW TABLES LIKE 'notifications'")->num_rows > 0) {
            $stmtN = $conn->prepare('INSERT INTO notifications (admin_id, janitor_id, bin_id, notification_type, title, message, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
            if ($stmtN) {
                $adminParam = null;
                $janitorParam = (int)$user_id;
                $binParam = null;
                $type = 'security';
                $stmtN->bind_param('iiisss', $adminParam, $janitorParam, $binParam, $type, $title, $message);
                $stmtN->execute();
                $stmtN->close();
            }
        }
    } catch (Exception $e) {
        // swallow notification errors
        error_log('[api/change-janitor-password.php] notification failed: ' . $e->getMessage());
    }

    sendJSON(['success' => true, 'message' => 'Password changed successfully']);

} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => $e->getMessage()]);
}

?>
