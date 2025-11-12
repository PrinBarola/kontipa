<?php
require_once '../includes/config.php';

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized']);
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = getCurrentUserId();
    
    $first_name = $conn->real_escape_string($data['first_name']);
    $last_name = $conn->real_escape_string($data['last_name']);
    $email = $conn->real_escape_string($data['email']);
    $phone = $conn->real_escape_string($data['phone']);

    $sql = "UPDATE users SET first_name='$first_name', last_name='$last_name', 
            email='$email', phone='$phone' WHERE user_id = $user_id";

    if ($conn->query($sql)) {
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name'] = $last_name;
        sendJSON(['success' => true, 'message' => 'Profile updated successfully']);
    } else {
        sendJSON(['success' => false, 'message' => $conn->error]);
    }
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => $e->getMessage()]);
}
?>
