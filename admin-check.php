<?php
/**
 * Admin Account Checker & Reset Tool
 * This file helps diagnose and fix admin login issues.
 * 
 * Visit: http://localhost/System/admin-check.php
 */

require_once 'includes/config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$message = '';
$error = '';

// Check current admin accounts
$admins = [];
try {
    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->query("SELECT admin_id, first_name, last_name, email, status, password FROM admins ORDER BY admin_id");
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif (isset($conn)) {
        $result = $conn->query("SELECT admin_id, first_name, last_name, email, status, password FROM admins ORDER BY admin_id");
        $admins = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}

// Handle password reset
if ($action === 'reset_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_id = intval($_POST['admin_id'] ?? 0);
    $new_password = trim($_POST['new_password'] ?? '');
    
    if ($admin_id <= 0) {
        $error = "Invalid admin ID";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        try {
            if (isset($pdo) && $pdo instanceof PDO) {
                $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE admin_id = ?");
                $stmt->execute([$hashed, $admin_id]);
            } else {
                $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE admin_id = ?");
                $stmt->bind_param("si", $hashed, $admin_id);
                $stmt->execute();
                $stmt->close();
            }
            $message = "Password updated successfully! New hash: " . substr($hashed, 0, 50) . "...";
        } catch (Exception $e) {
            $error = "Update failed: " . $e->getMessage();
        }
    }
}

// Handle account activation
if ($action === 'activate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_id = intval($_POST['admin_id'] ?? 0);
    
    if ($admin_id <= 0) {
        $error = "Invalid admin ID";
    } else {
        try {
            if (isset($pdo) && $pdo instanceof PDO) {
                $stmt = $pdo->prepare("UPDATE admins SET status = 'active' WHERE admin_id = ?");
                $stmt->execute([$admin_id]);
            } else {
                $stmt = $conn->prepare("UPDATE admins SET status = 'active' WHERE admin_id = ?");
                $stmt->bind_param("i", $admin_id);
                $stmt->execute();
                $stmt->close();
            }
            $message = "Admin account activated successfully!";
        } catch (Exception $e) {
            $error = "Update failed: " . $e->getMessage();
        }
    }
}

// Handle creating default admin
if ($action === 'create_default' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = 'admin@gmail.com';
    $password = 'password'; // Default password
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        if (isset($pdo) && $pdo instanceof PDO) {
            $stmt = $pdo->prepare("INSERT INTO admins (first_name, last_name, email, password, status, employee_id) VALUES (?, ?, ?, ?, 'active', 'ADM-001') ON DUPLICATE KEY UPDATE password = VALUES(password), status = 'active'");
            $stmt->execute(['Admin', 'User', $email, $hashed]);
        } else {
            $stmt = $conn->prepare("INSERT INTO admins (first_name, last_name, email, password, status, employee_id) VALUES (?, ?, ?, ?, 'active', 'ADM-001')");
            $stmt->bind_param("ssss", $fn, $ln, $e, $h);
            $fn = 'Admin'; $ln = 'User'; $e = $email; $h = $hashed;
            $stmt->execute();
            $stmt->close();
        }
        $message = "Default admin account created/updated! Email: $email, Password: $password";
    } catch (Exception $e) {
        $error = "Insert failed: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Account Checker</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <style>
        body { padding: 20px; font-family: Arial, sans-serif; }
        .container { max-width: 900px; margin: 0 auto; }
        .alert { margin-bottom: 20px; }
        table { margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .btn { margin: 5px; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Account Checker & Diagnostic Tool</h1>
        <p>Use this tool to check and fix admin login issues.</p>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <h2>Current Admin Accounts</h2>
        <?php if (empty($admins)): ?>
            <p class="alert alert-warning">No admin accounts found in database.</p>
            <form method="POST" style="margin-top: 10px;">
                <input type="hidden" name="action" value="create_default">
                <button type="submit" class="btn btn-success">Create Default Admin Account</button>
            </form>
            <p style="margin-top: 10px; font-size: 14px;">
                <strong>Default Login:</strong> Email: <code>admin@gmail.com</code>, Password: <code>password</code>
            </p>
        <?php else: ?>
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Admin ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Password Hash (Preview)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($admin['admin_id']); ?></td>
                            <td><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($admin['email']); ?></td>
                            <td>
                                <span class="badge <?php echo $admin['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo htmlspecialchars($admin['status']); ?>
                                </span>
                            </td>
                            <td><code><?php echo substr(htmlspecialchars($admin['password']), 0, 30); ?>...</code></td>
                            <td>
                                <?php if ($admin['status'] !== 'active'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="activate">
                                        <input type="hidden" name="admin_id" value="<?php echo $admin['admin_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-warning">Activate</button>
                                    </form>
                                <?php endif; ?>
                                <button type="button" class="btn btn-sm btn-primary" onclick="showResetForm(<?php echo $admin['admin_id']; ?>)">Reset Password</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <hr>
        <h2>Test Login Verification</h2>
        <p>Enter email and password to test if the login would work:</p>
        <form method="POST" id="testForm" style="max-width: 400px;">
            <div class="form-group">
                <label for="testEmail">Email:</label>
                <input type="email" id="testEmail" name="email" class="form-control" placeholder="admin@gmail.com" required>
            </div>
            <div class="form-group">
                <label for="testPassword">Password:</label>
                <input type="password" id="testPassword" name="password" class="form-control" placeholder="password" required>
            </div>
            <button type="button" class="btn btn-info" onclick="testLogin()">Test Password Verify</button>
        </form>
        <div id="testResult"></div>

        <!-- Reset Password Modal -->
        <div id="resetModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
            <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:white; padding:30px; border-radius:8px; width:400px;">
                <h3>Reset Admin Password</h3>
                <form method="POST" id="resetForm">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" id="resetAdminId" name="admin_id">
                    <div class="form-group">
                        <label for="newPassword">New Password:</label>
                        <input type="password" id="newPassword" name="new_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Password</button>
                    <button type="button" class="btn btn-secondary" onclick="closeResetForm()">Cancel</button>
                </form>
            </div>
        </div>

        <script>
            function showResetForm(adminId) {
                document.getElementById('resetAdminId').value = adminId;
                document.getElementById('resetModal').style.display = 'block';
            }
            function closeResetForm() {
                document.getElementById('resetModal').style.display = 'none';
            }

            function testLogin() {
                const email = document.getElementById('testEmail').value;
                const password = document.getElementById('testPassword').value;
                
                // Simulate the check (in real scenario, this would call login-handler.php)
                alert('To test login, visit admin-login.php and enter the credentials.\n\nEmail: ' + email + '\nPassword: ' + password);
            }
        </script>
    </div>
</body>
</html>
