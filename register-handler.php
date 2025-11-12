<?php
// register-handler.php
// Modified to insert into janitors table (instead of users) and log to activity_logs.janitor_id

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once 'includes/config.php';

error_log("[v0] Registration handler called");
error_log("[v0] POST data: " . print_r($_POST, true));

$response = [
    'success' => false,
    'message' => '',
    'errors' => [],
    'redirect' => ''
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['errors']['general'] = 'Invalid request method';
    error_log("[v0] Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode($response);
    exit;
}

// Get form data (names match registration.js)
$firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : '';
$lastName = isset($_POST['lastName']) ? trim($_POST['lastName']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$confirmPassword = isset($_POST['confirmPassword']) ? $_POST['confirmPassword'] : '';

error_log("[v0] Form data received - Email: $email, Phone: $phone");

// Validation (same keys used previously)
if (empty($firstName)) {
    $response['errors']['firstName'] = 'First name is required';
} elseif (!preg_match('/^[a-zA-Z\s]+$/', $firstName)) {
    $response['errors']['firstName'] = 'First name can only contain letters';
}

if (empty($lastName)) {
    $response['errors']['lastName'] = 'Last name is required';
} elseif (!preg_match('/^[a-zA-Z\s]+$/', $lastName)) {
    $response['errors']['lastName'] = 'Last name can only contain letters';
}

if (empty($email)) {
    $response['errors']['email'] = 'Email is required';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['errors']['email'] = 'Please enter a valid email address';
}

$cleanPhone = preg_replace('/\D/', '', $phone);
if (empty($phone)) {
    $response['errors']['phone'] = 'Phone number is required';
} elseif (strlen($cleanPhone) !== 11) {
    $response['errors']['phone'] = 'Phone number must be exactly 11 digits';
}

if (empty($password)) {
    $response['errors']['password'] = 'Password is required';
} else {
    if (strlen($password) < 6) {
        $response['errors']['password'] = 'Password must be at least 6 characters';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $response['errors']['password'] = 'Password must contain an uppercase letter';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $response['errors']['password'] = 'Password must contain a lowercase letter';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $response['errors']['password'] = 'Password must contain a number';
    } elseif (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        $response['errors']['password'] = 'Password must contain a special character';
    }
}

if (empty($confirmPassword)) {
    $response['errors']['confirmPassword'] = 'Please confirm your password';
} elseif ($confirmPassword !== $password) {
    $response['errors']['confirmPassword'] = 'Passwords do not match';
}

if (!empty($response['errors'])) {
    error_log("[v0] Validation errors: " . print_r($response['errors'], true));
    echo json_encode($response);
    exit;
}

try {
    if (!isset($pdo) || $pdo === null) {
        error_log("[v0] Database PDO connection is null");
        throw new Exception('Database connection failed. Please check your database configuration.');
    }

    error_log("[v0] Database connection successful (PDO)");

    // Check if email already exists in janitors table
    $checkStmt = $pdo->prepare("SELECT janitor_id FROM janitors WHERE email = ? LIMIT 1");
    $checkStmt->execute([$email]);
    if ($checkStmt->rowCount() > 0) {
        $response['errors']['email'] = 'This email is already registered';
        error_log("[v0] Email already exists in janitors: $email");
        echo json_encode($response);
        exit;
    }

    // Check if phone already exists (store phone as digits-only in DB)
    $checkPhone = $pdo->prepare("SELECT janitor_id FROM janitors WHERE phone = ? LIMIT 1");
    $checkPhone->execute([$cleanPhone]);
    if ($checkPhone->rowCount() > 0) {
        $response['errors']['phone'] = 'This phone number is already registered';
        error_log("[v0] Phone already exists in janitors: $cleanPhone");
        echo json_encode($response);
        exit;
    }

    error_log("[v0] Email is unique, proceeding with registration into janitors table");

    // Hash the password using bcrypt
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Generate employee ID — prefer generateEmployeeId() if defined, else fallback
    if (function_exists('generateEmployeeId')) {
        try {
            $employeeId = generateEmployeeId();
        } catch (Exception $e) {
            error_log("[v0] generateEmployeeId() error: " . $e->getMessage());
            $employeeId = 'JAN-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
        }
    } else {
        $employeeId = 'JAN-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    error_log("[v0] Generated employee ID: $employeeId");

    // Insert new janitor into janitors table
    $insertStmt = $pdo->prepare("
        INSERT INTO janitors (first_name, last_name, email, phone, password, status, employee_id, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

    $insertStmt->execute([
        $firstName,
        $lastName,
        $email,
        $cleanPhone,
        $hashedPassword,
        'active',
        $employeeId
    ]);

    $janitorId = $pdo->lastInsertId();
    error_log("[v0] Janitor inserted successfully with ID: $janitorId");

    // Log the registration activity into activity_logs (use janitor_id column)
    try {
        $logStmt = $pdo->prepare("
            INSERT INTO activity_logs (janitor_id, action, entity_type, entity_id, description, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $logStmt->execute([
            $janitorId,
            'register',
            'janitor',
            $janitorId,
            'New janitor registered successfully',
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        error_log("[v0] Activity logged successfully for janitor_id: $janitorId");
    } catch (Exception $e) {
        // don't fail registration if logging fails
        error_log("[v0] Activity log failed: " . $e->getMessage());
    }

    $response['success'] = true;
    $response['message'] = 'Registration successful! You can now log in.';
    $response['redirect'] = 'user-login.php';

    error_log("[v0] Registration completed successfully for email: $email");

} catch (PDOException $e) {
    error_log("[v0] PDOException: " . $e->getMessage());
    $response['errors']['general'] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    error_log("[v0] Exception: " . $e->getMessage());
    $response['errors']['general'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
exit;
?>  