<?php
/**
 * Standalone Admin Login Handler
 * Fetches password directly from admins table and verifies it.
 * Does NOT share code with janitor login.
 */

require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'message' => ''];

// Ensure POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

// Validate inputs
if ($email === '') {
    $response['message'] = 'Email is required';
    echo json_encode($response);
    exit;
}

if ($password === '') {
    $response['message'] = 'Password is required';
    echo json_encode($response);
    exit;
}

try {
    // Fetch admin from admins table ONLY
    $admin = null;
    
    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->prepare("SELECT admin_id, first_name, last_name, email, password, status FROM admins WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        if (!isset($conn)) throw new Exception('No DB connection');
        $stmt = $conn->prepare("SELECT admin_id, first_name, last_name, email, password, status FROM admins WHERE email = ? LIMIT 1");
        if (!$stmt) throw new Exception($conn->error ?: 'Prepare failed');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $admin = $res ? $res->fetch_assoc() : null;
        $stmt->close();
    }

    if (!$admin) {
        error_log('[admin-login] No admin found with email: ' . $email);
        $response['message'] = 'Invalid email or password';
        echo json_encode($response);
        exit;
    }

    // Check if account is active
    if ($admin['status'] !== 'active') {
        error_log('[admin-login] Admin ' . $admin['admin_id'] . ' is not active: ' . $admin['status']);
        $response['message'] = 'Account is not active';
        echo json_encode($response);
        exit;
    }

    // Verify password
    $stored_hash = $admin['password'] ?? '';
    $verified = false;

    if ($stored_hash === '') {
        error_log('[admin-login] Admin ' . $admin['admin_id'] . ' has no password hash');
        $response['message'] = 'Invalid email or password';
        echo json_encode($response);
        exit;
    }

    // Try password_verify first (for bcrypt/argon2 hashes)
    if (password_verify($password, $stored_hash)) {
        $verified = true;
        error_log('[admin-login] Admin ' . $admin['admin_id'] . ' login successful (password_verify)');
    }
    // Try SHA-256 (64 hex characters)
    elseif (strlen($stored_hash) === 64 && preg_match('/^[a-f0-9]{64}$/i', $stored_hash)) {
        $computed_sha256 = hash('sha256', $password);
        if ($computed_sha256 === $stored_hash) {
            $verified = true;
            error_log('[admin-login] Admin ' . $admin['admin_id'] . ' login successful (sha256)');
            
            // Rehash with password_hash for better security
            try {
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                if (isset($pdo) && $pdo instanceof PDO) {
                    $update_stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE admin_id = ?");
                    $update_stmt->execute([$new_hash, $admin['admin_id']]);
                } else {
                    if (isset($conn)) {
                        $update_stmt = $conn->prepare("UPDATE admins SET password = ? WHERE admin_id = ?");
                        if ($update_stmt) {
                            $update_stmt->bind_param('si', $new_hash, $admin['admin_id']);
                            $update_stmt->execute();
                            $update_stmt->close();
                        }
                    }
                }
            } catch (Exception $e) {
                error_log('[admin-login] Failed to rehash password: ' . $e->getMessage());
            }
        }
    }
    // Try MD5 as fallback (for legacy accounts - 32 hex characters)
    elseif (strlen($stored_hash) === 32 && preg_match('/^[a-f0-9]{32}$/i', $stored_hash)) {
        if (md5($password) === $stored_hash) {
            $verified = true;
            error_log('[admin-login] Admin ' . $admin['admin_id'] . ' login successful (md5)');
            
            // Rehash with password_hash for security
            try {
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                if (isset($pdo) && $pdo instanceof PDO) {
                    $update_stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE admin_id = ?");
                    $update_stmt->execute([$new_hash, $admin['admin_id']]);
                } else {
                    if (isset($conn)) {
                        $update_stmt = $conn->prepare("UPDATE admins SET password = ? WHERE admin_id = ?");
                        if ($update_stmt) {
                            $update_stmt->bind_param('si', $new_hash, $admin['admin_id']);
                            $update_stmt->execute();
                            $update_stmt->close();
                        }
                    }
                }
            } catch (Exception $e) {
                error_log('[admin-login] Failed to rehash password: ' . $e->getMessage());
            }
        }
    }

    if (!$verified) {
        error_log('[admin-login] Admin ' . $admin['admin_id'] . ' failed password verification');
        $response['message'] = 'Invalid email or password';
        echo json_encode($response);
        exit;
    }

    // Success! Create session
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    session_regenerate_id(true);

    $_SESSION['admin_id'] = $admin['admin_id'];
    $_SESSION['role'] = 'admin';
    $_SESSION['name'] = trim(($admin['first_name'] ?? '') . ' ' . ($admin['last_name'] ?? ''));

    $response['success'] = true;
    $response['message'] = 'Welcome Back, Admin';
    $response['redirect'] = 'admin-dashboard.php';

    error_log('[admin-login] Admin ' . $admin['admin_id'] . ' session created');

} catch (Exception $e) {
    error_log('[admin-login] Exception: ' . $e->getMessage());
    $response['message'] = 'Server error: ' . $e->getMessage();
}

echo json_encode($response);
exit;

?>
