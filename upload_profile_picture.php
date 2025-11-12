<?php
session_start();
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Use helpers from includes/config.php to find current user id/type
$userid = getCurrentUserId();
// allow caller to explicitly set scope (janitor/admin) to avoid accidental cross-table writes
$requestedScope = null;
if (isset($_POST['scope'])) $requestedScope = trim($_POST['scope']);
elseif (isset($_REQUEST['scope'])) $requestedScope = trim($_REQUEST['scope']);

$role = null;
if ($requestedScope === 'admin') {
    $role = 'admin';
} elseif ($requestedScope === 'janitor') {
    $role = 'janitor';
} else {
    $role = getCurrentUserType() ?? (isset($_SESSION['admin_id']) ? 'admin' : 'janitor');
}

if (!$userid) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized: No user ID in session']);
    exit;
}

if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== 0) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

$fileTmp = $_FILES['profile_picture']['tmp_name'];
$fileName = basename($_FILES['profile_picture']['name']);
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$fileSize = $_FILES['profile_picture']['size'];

$allowed = ['jpg', 'jpeg', 'png', 'gif'];
$maxSize = 5 * 1024 * 1024; // 5MB

if (!in_array($fileExt, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: JPG, PNG, GIF']);
    exit;
}

if ($fileSize > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'File too large. Max 5MB']);
    exit;
}

$uploadDir = 'uploads/profile-pictures/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate unique filename
$newFileName = 'profile_' . $userid . '_' . time() . '.' . $fileExt;
$targetFile = $uploadDir . $newFileName;

// Move uploaded file
if (!move_uploaded_file($fileTmp, $targetFile)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
    exit;
}

// Determine table and id field based on role
if ($role === 'admin') {
    $table = 'admins';
    $idField = 'admin_id';
} else {
    $table = 'janitors';
    $idField = 'janitor_id';
}

// Insert/Update file path into database
try {
    $affected = null;
    if (isset($pdo) && $pdo instanceof PDO) {
        // PDO version
        $stmt = $pdo->prepare("UPDATE {$table} SET profile_picture = :path, updated_at = NOW() WHERE {$idField} = :id");
        $stmt->execute([':path' => $targetFile, ':id' => $userid]);
        $affected = $stmt->rowCount();
    } else {
        // mysqli version - id is integer
        $stmt = $conn->prepare("UPDATE {$table} SET profile_picture = ?, updated_at = NOW() WHERE {$idField} = ?");
        if (!$stmt) {
            throw new Exception('Database prepare failed: ' . $conn->error);
        }
        // bind param types: string (path), integer (id)
        $intId = (int)$userid;
        $stmt->bind_param("si", $targetFile, $intId);
        $ok = $stmt->execute();
        $affected = $conn->affected_rows;
        if (!$ok || $stmt->errno) {
            throw new Exception($stmt->error ?? 'Database execute failed');
        }
        $stmt->close();
    }

    // --- Create admin notification when a janitor updates their profile picture ---
    try {
        if ($role !== 'admin' && $affected > 0) {
            $notificationType = 'photo_update';
            $title = 'Janitor updated profile photo';
            $message = 'A janitor has uploaded a new profile picture.';

            if (isset($pdo) && $pdo instanceof PDO) {
                $stmtN = $pdo->prepare(
                    "INSERT INTO notifications (admin_id, janitor_id, bin_id, notification_type, title, message, created_at) VALUES (:admin_id, :janitor_id, :bin_id, :type, :title, :message, NOW())"
                );
                $stmtN->execute([
                    ':admin_id' => null,
                    ':janitor_id' => $userid,
                    ':bin_id' => null,
                    ':type' => $notificationType,
                    ':title' => $title,
                    ':message' => $message
                ]);
            } else {
                if ($conn->query("SHOW TABLES LIKE 'notifications'")->num_rows > 0) {
                    $stmtN = $conn->prepare(
                        "INSERT INTO notifications (admin_id, janitor_id, bin_id, notification_type, title, message, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())"
                    );
                    if ($stmtN) {
                        $adminParam = null;
                        $janitorParam = (int)$userid;
                        $binParam = null;
                        $stmtN->bind_param("iiisss", $adminParam, $janitorParam, $binParam, $notificationType, $title, $message);
                        $stmtN->execute();
                        $stmtN->close();
                    }
                }
            }
        }
    } catch (Exception $e) {
        // Non-fatal: log and continue
        error_log('[upload_profile_picture.php] notification insert failed: ' . $e->getMessage());
    }

    $resp = [
        'success' => true,
        'path' => $targetFile,
        'message' => 'Profile picture updated successfully'
    ];
    // Add debug info when DEV_MODE is enabled (helpful for debugging why DB didn't update)
    if (defined('DEV_MODE') && DEV_MODE) {
        $resp['debug'] = [
            'role' => $role,
            'userid' => $userid,
            'table' => $table,
            'idField' => $idField,
            'affected_rows' => $affected
        ];
    }
    echo json_encode($resp);
} catch (Exception $e) {
    @unlink($targetFile);
    error_log('[upload_profile_picture.php] Error: ' . $e->getMessage());
    $out = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    if (defined('DEV_MODE') && DEV_MODE) $out['debug'] = ['role'=>$role,'userid'=>$userid,'table'=>$table,'idField'=>$idField];
    echo json_encode($out);
}
exit;
?>
