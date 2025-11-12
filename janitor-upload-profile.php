<?php
session_start();
require_once 'includes/config.php';

// JANITOR PROFILE PICTURE UPLOAD - DEDICATED HANDLER
// This handler ONLY updates the janitors table
// No ambiguity, no role checking - direct to janitors

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get janitor ID from session
$janitorId = $_SESSION['janitor_id'] ?? null;

if (!$janitorId || !is_numeric($janitorId)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Invalid janitor session']);
    exit;
}

// Validate file upload
if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== 0) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

$fileTmp = $_FILES['profile_picture']['tmp_name'];
$fileName = basename($_FILES['profile_picture']['name']);
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$fileSize = $_FILES['profile_picture']['size'];

// Validate file type and size
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

// Create upload directory
$uploadDir = 'uploads/profile-pictures/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

// Generate unique filename
$newFileName = 'profile_janitor_' . $janitorId . '_' . time() . '.' . $fileExt;
$targetFile = $uploadDir . $newFileName;

// Move uploaded file
if (!move_uploaded_file($fileTmp, $targetFile)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
    exit;
}

// Update janitors table - DIRECT UPDATE
try {
    $success = false;
    $affected = 0;
    
    // Use mysqli
    if (isset($conn) && $conn instanceof mysqli) {
        $stmt = $conn->prepare("UPDATE janitors SET profile_picture = ?, updated_at = NOW() WHERE janitor_id = ?");
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        
        $intId = (int)$janitorId;
        $stmt->bind_param("si", $targetFile, $intId);
        
        if (!$stmt->execute()) {
            throw new Exception('Execute failed: ' . $stmt->error);
        }
        
        $affected = $conn->affected_rows;
        $stmt->close();
        $success = true;
    }
    // Use PDO
    elseif (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->prepare("UPDATE janitors SET profile_picture = ?, updated_at = NOW() WHERE janitor_id = ?");
        if (!$stmt) {
            throw new Exception('PDO prepare failed');
        }
        
        if (!$stmt->execute([$targetFile, $intId])) {
            throw new Exception('PDO execute failed');
        }
        
        $affected = $stmt->rowCount();
        $success = true;
    }
    else {
        throw new Exception('No database connection available');
    }
    
    if (!$success) {
        throw new Exception('Update operation failed');
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'path' => $targetFile,
        'message' => 'Profile picture updated successfully',
        'debug' => [
            'janitor_id' => $janitorId,
            'file' => $targetFile,
            'affected_rows' => $affected
        ]
    ]);
    
} catch (Exception $e) {
    @unlink($targetFile);
    error_log('[janitor-upload-profile.php] Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'debug' => ['janitor_id' => $janitorId]
    ]);
}

exit;
?>
