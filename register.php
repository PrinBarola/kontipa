<?php
require_once 'includes/config.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    header('Location: user-login.php');
    exit;
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');
        $email = trim($_POST['email'] ?? '');

        // Basic validation
        if (empty($username) || empty($password) || empty($confirm_password) || empty($email)) {
            throw new Exception('Please fill in all fields');
        }

        if ($password !== $confirm_password) {
            throw new Exception('Passwords do not match');
        }

        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            throw new Exception('Username or email already exists');
        }

        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert the new user into the database
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, role, created_at) VALUES (?, ?, ?, 'user', NOW())");
        $stmt->bind_param("sss", $username, $hashed_password, $email);

        if (!$stmt->execute()) {
            throw new Exception('Failed to register user: ' . $stmt->error);
        }

        // Get the newly created user's ID
        $new_user_id = $stmt->insert_id;

        // Automatically log in the user
        $_SESSION['user_id'] = $new_user_id;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = 'user';
        
        // Redirect to dashboard or appropriate page
        header('Location: dashboard.php');
        exit;
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register - Trashbin Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/admin-dashboard.css">
</head>
<body>
  <?php include_once __DIR__ . '/includes/header.php'; ?>

  <div class="container mt-5 pt-5">
    <div class="row justify-content-center">
      <div class="col-lg-6 col-md-8">
        <div class="card">
          <div class="card-header text-center">
            <h4 class="mb-0">Create an Account</h4>
          </div>
          <div class="card-body">
            <?php if (!empty($error_message)): ?>
              <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
              <a href="#" onclick="openInfoModal('privacyModal'); return false;">Privacy Policy</a>
                      <span class="separator">•</span>
                      <a href="#" onclick="openInfoModal('termsModal'); return false;">Terms of Service</a>
                      <span class="separator">•</span>
                      <a href="#" onclick="openInfoModal('supportModal'); return false;">Support</a>
                <input type="text" class="form-control" id="username" name="username" required>
              </div>
              <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
              </div>
              <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
              </div>
              <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
              </div>
              <div class="d-grid">
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-user-plus"></i> Register
                </button>
              </div>
            </form>
          </div>
          <div class="card-footer text-center">
            <small class="text-muted">Already have an account? <a href="login.php">Login here</a>.</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="js/bootstrap.bundle.min.js"></script>
  <?php include 'includes/info-modals.php'; ?>
</body>
</html>