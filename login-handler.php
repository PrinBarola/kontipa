<?php
// NOTE: ensure this file is located in the project root (same as admin-login.php/user-login.php)
// and that includes/config.php is reachable from here.
require_once 'includes/config.php';

header('Content-Type: application/json');
$response = ['success' => false, 'errors' => []];

// Ensure it’s a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['errors']['general'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$role = trim($_POST['role'] ?? '');

// Accept alternative role naming (legacy)
if ($role === 'user') {
    $role = 'janitor';
}

// Validate inputs
if ($email === '') {
    $response['errors']['email'] = 'Email is required.';
}
if ($password === '') {
    $response['errors']['password'] = 'Password is required.';
}
if ($role === '' || !in_array($role, ['admin', 'janitor'], true)) {
    $response['errors']['general'] = 'Invalid login source.';
}

if (!empty($response['errors'])) {
    echo json_encode($response);
    exit;
}

// Map role -> table and id column, use explicit mapping (prevent arbitrary table injection)
if ($role === 'admin') {
    $table = 'admins';
    $idColumn = 'admin_id';
} else {
    $table = 'janitors';
    $idColumn = 'janitor_id';
}

// Fetch account - select explicit columns
try {
    $sql = "SELECT {$idColumn}, first_name, last_name, email, password, status FROM {$table} WHERE email = :email LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // In production, log $e->getMessage() to server logs
    $response['errors']['general'] = 'An error occurred while checking credentials.';
    echo json_encode($response);
    exit;
}

if (!$user) {
    $response['errors']['email'] = 'No account found with that email.';
    echo json_encode($response);
    exit;
}

// Ensure account active
if (!isset($user['status']) || $user['status'] !== 'active') {
    $response['errors']['general'] = 'Account is not active.';
    echo json_encode($response);
    exit;
}

// Verify password
$stored = $user['password'] ?? '';
$verified = false;

if ($stored !== '') {
    // Try secure verify first
    if (password_verify($password, $stored)) {
        $verified = true;
        // Optionally rehash
        if (password_needs_rehash($stored, PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE {$table} SET password = :h WHERE {$idColumn} = :id");
            $update->execute([':h' => $newHash, ':id' => $user[$idColumn]]);
        }
    } else {
        // Legacy MD5 compatibility (one-time migration)
        if (strlen($stored) === 32 && md5($password) === $stored) {
            $verified = true;
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE {$table} SET password = :h WHERE {$idColumn} = :id");
            $update->execute([':h' => $newHash, ':id' => $user[$idColumn]]);
        }
    }
}

if (!$verified) {
    $response['errors']['password'] = 'Incorrect password.';
    echo json_encode($response);
    exit;
}

// Success - make sure session is active (config already starts it)
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
session_regenerate_id(true);

if ($role === 'admin') {
    $_SESSION['admin_id'] = $user[$idColumn];
    $_SESSION['role'] = 'admin';
    $_SESSION['name'] = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
    $response['redirect'] = 'admin-dashboard.php';
    $response['message'] = 'Welcome back, Admin!';
} else {
    $_SESSION['janitor_id'] = $user[$idColumn];
    $_SESSION['role'] = 'janitor';
    $_SESSION['name'] = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
    $response['redirect'] = 'janitor-dashboard.php';
    $response['message'] = 'Welcome back!';
}

$response['success'] = true;
echo json_encode($response);
exit;
?>