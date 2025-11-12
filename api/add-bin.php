<?php
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['bin_code']) || !isset($data['location']) || !isset($data['type']) || !isset($data['status'])) {
        sendJSON(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    $bin_code = $conn->real_escape_string($data['bin_code']);
    $location = $conn->real_escape_string($data['location']);
    $type = $conn->real_escape_string($data['type']);
    $capacity = intval($data['capacity']);
    $status = $conn->real_escape_string($data['status']);
    $assigned_to = isset($data['assigned_to']) && !empty($data['assigned_to']) ? intval($data['assigned_to']) : null;

    $assigned_to_sql = $assigned_to ? $assigned_to : 'NULL';
    $sql = "INSERT INTO bins (bin_code, location, type, capacity, status, assigned_to) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssii", $bin_code, $location, $type, $capacity, $status, $assigned_to);
    
    if ($stmt->execute()) {
        sendJSON(['success' => true, 'message' => 'Bin added successfully', 'bin_id' => $conn->insert_id]);
    } else {
        sendJSON(['success' => false, 'message' => $stmt->error]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => $e->getMessage()]);
}
?>
