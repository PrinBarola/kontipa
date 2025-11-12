<?php
// includes/add-janitor.php
// Adds a new janitor. Accepts JSON POST body.

require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

if (!function_exists('sendJSON')) {
    function sendJSON($payload) {
        echo json_encode($payload);
        exit;
    }
}

if (function_exists('isLoggedIn') && function_exists('isAdmin')) {
    if (!isLoggedIn() || !isAdmin()) {
        sendJSON(['success' => false, 'message' => 'Unauthorized']);
    }
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) sendJSON(['success' => false, 'message' => 'Invalid JSON']);

$required = ['first_name','last_name','email','phone','status'];
foreach ($required as $r) {
    if (!isset($body[$r]) || $body[$r] === '') {
        sendJSON(['success' => false, 'message' => "Missing required field: $r"]);
    }
}

$first_name = trim($body['first_name']);
$last_name = trim($body['last_name']);
$email = trim($body['email']);
$phone = trim($body['phone']);
$status = trim($body['status']);

try {
    if (isset($conn) && $conn instanceof mysqli) {
        // simple uniqueness check by email
        $check = $conn->prepare("SELECT janitor_id FROM janitors WHERE email = ?");
        $check->bind_param('s', $email);
        $check->execute();
        $res = $check->get_result();
        if ($res && $res->num_rows > 0) {
            $check->close();
            sendJSON(['success' => false, 'message' => 'Email already exists']);
        }
        $check->close();

        $stmt = $conn->prepare("INSERT INTO janitors (first_name, last_name, email, phone, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param('sssss', $first_name, $last_name, $email, $phone, $status);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            sendJSON(['success' => false, 'message' => $err]);
        }
        $insertId = $stmt->insert_id;
        $stmt->close();
        sendJSON(['success' => true, 'janitor_id' => $insertId]);
    } elseif (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->prepare("SELECT janitor_id FROM janitors WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) sendJSON(['success' => false, 'message' => 'Email already exists']);

        $stmt = $pdo->prepare("INSERT INTO janitors (first_name, last_name, email, phone, status, created_at) VALUES (:fn, :ln, :email, :phone, :status, NOW())");
        $stmt->execute([
            ':fn' => $first_name,
            ':ln' => $last_name,
            ':email' => $email,
            ':phone' => $phone,
            ':status' => $status
        ]);
        sendJSON(['success' => true, 'janitor_id' => (int)$pdo->lastInsertId()]);
    } else {
        sendJSON(['success' => false, 'message' => 'No DB connection']);
    }
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => $e->getMessage()]);
}