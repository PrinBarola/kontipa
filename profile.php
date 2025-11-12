<?php
require_once 'includes/config.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// redirect if not logged in
if (!isLoggedIn()) {
    header('Location: user-login.php');
    exit;
}

$userid = getCurrentUserId();
// prefer the explicit helper which checks session keys (admin_id / janitor_id)
$role = getCurrentUserType() ?? (isAdmin() ? 'admin' : 'janitor');

// If a caller provides an explicit scope, prefer it to avoid accidental cross-table updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scope'])) {
  $scope = trim($_POST['scope']);
  if ($scope === 'janitor') $role = 'janitor';
  elseif ($scope === 'admin') $role = 'admin';
}

// Allow a caller to explicitly pass user id (useful for AJAX from dashboards). Only use as fallback.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && is_numeric($_POST['user_id'])) {
    $maybeId = intval($_POST['user_id']);
    if ($maybeId > 0) $userid = $maybeId;
}

// Enable DEV_MODE while debugging. Set false in production.
if (!defined('DEV_MODE')) define('DEV_MODE', true);

// escape helper
function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Try to detect which table and id column hold the user row.
 * Returns array with keys: table, idField, row (assoc) on success, null on failure.
 * Tries several common table and id field names so mismatches (admin vs admins / admin_id vs id) are handled.
 */
function detectUserRecord($userid, $role) {
    global $pdo, $conn;
    $candidates = [];
    if ($role === 'admin') {
        $candidates = [
            ['table'=>'admins','id'=>'admin_id'],
            ['table'=>'admin','id'=>'admin_id'],
            ['table'=>'admins','id'=>'id'],
            ['table'=>'admin','id'=>'id'],
        ];
    } else {
        $candidates = [
            ['table'=>'janitors','id'=>'janitor_id'],
            ['table'=>'janitor','id'=>'janitor_id'],
            ['table'=>'janitors','id'=>'id'],
            ['table'=>'janitor','id'=>'id'],
        ];
    }

    foreach ($candidates as $c) {
        $table = $c['table'];
        $idField = $c['id'];
        try {
            if (isset($pdo) && $pdo instanceof PDO) {
                $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE {$idField} = ? LIMIT 1");
                if ($stmt === false) continue;
                $stmt->execute([$userid]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) return ['table'=>$table, 'idField'=>$idField, 'row'=>$row];
            } elseif (isset($conn)) {
                $stmt = $conn->prepare("SELECT * FROM {$table} WHERE {$idField} = ? LIMIT 1");
                if (!$stmt) continue;
                $stmt->bind_param("i", $userid);
                $stmt->execute();
                $res = $stmt->get_result();
                $row = $res ? $res->fetch_assoc() : null;
                $stmt->close();
                if ($row) return ['table'=>$table, 'idField'=>$idField, 'row'=>$row];
            }
        } catch (Exception $ex) {
            // ignore and try next candidate
            error_log("[profile.php] detectUserRecord: probe {$table}.{$idField} failed: " . $ex->getMessage());
        }
    }
    return null;
}

// Helper: run count queries used by quick stats
function runCountQuery($sql) {
    global $pdo, $conn;
    try {
        if (isset($pdo) && $pdo instanceof PDO) {
            $stmt = $pdo->query($sql);
            if ($stmt === false) return null;
            return (int)$stmt->fetchColumn();
        } elseif (isset($conn)) {
            $res = $conn->query($sql);
            if ($res === false) return null;
            $row = $res->fetch_row();
            return (int)($row[0] ?? 0);
        }
    } catch (Exception $ex) {
        error_log('[profile.php] runCountQuery error: ' . $ex->getMessage());
    }
    return null;
}

// Quick stats
$totalBins = runCountQuery("SELECT COUNT(*) FROM bins");
if ($totalBins === null) $totalBins = 0;

$activeCandidates = [
    "SELECT COUNT(*) FROM janitors WHERE is_active = 1",
    "SELECT COUNT(*) FROM janitors WHERE active = 1",
    "SELECT COUNT(*) FROM janitors WHERE status = 'active'",
    "SELECT COUNT(*) FROM janitors WHERE status = 1",
    "SELECT COUNT(*) FROM janitors"
];
$activeJanitors = null;
foreach ($activeCandidates as $sql) {
    $res = runCountQuery($sql);
    if ($res !== null) { $activeJanitors = $res; break; }
}
if ($activeJanitors === null) $activeJanitors = 0;

// Load user basic record (best-effort, for display)
$user = ['first_name'=>'','last_name'=>'','email'=>'','phone'=>'','created_at'=>''];
$detected = detectUserRecord($userid, $role);
if ($detected) {
    $user = $detected['row'] + $user;
}

// Handle AJAX actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'update_profile') {
            // keep existing behavior (same as your prior code)
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name  = trim($_POST['last_name'] ?? '');
            $email      = trim($_POST['email'] ?? '');
            $phone      = trim($_POST['phone'] ?? '');

            if ($first_name === '' || $last_name === '' || $email === '') {
                throw new Exception('First name, last name and email are required.');
            }

            // Validate first name: no numbers allowed
            if (preg_match('/\d/', $first_name)) {
                throw new Exception('First name cannot contain numbers.');
            }

            // Validate last name: no numbers allowed
            if (preg_match('/\d/', $last_name)) {
                throw new Exception('Last name cannot contain numbers.');
            }

            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email address format.');
            }

            // Validate phone: must be exactly 11 digits if provided
            if ($phone !== '') {
                if (!preg_match('/^\d{11}$/', preg_replace('/\D/', '', $phone))) {
                    throw new Exception('Phone number must be exactly 11 digits.');
                }
            }

            // uniqueness check for email and phone, then update (admin/janitor)
            if ($role === 'admin') {
                if (isset($pdo) && $pdo instanceof PDO) {
                    // Check email uniqueness
                    $stmt = $pdo->prepare("SELECT admin_id FROM admins WHERE email = ? AND admin_id != ? LIMIT 1");
                    $stmt->execute([$email, $userid]);
                    if ($stmt->fetch()) throw new Exception('Email address is already in use by another admin.');
                    
                    // Check phone uniqueness if provided
                    if ($phone !== '') {
                        $stmt = $pdo->prepare("SELECT admin_id FROM admins WHERE phone = ? AND admin_id != ? LIMIT 1");
                        $stmt->execute([$phone, $userid]);
                        if ($stmt->fetch()) throw new Exception('Phone number is already in use by another admin.');
                    }
                    
                    $stmt = $pdo->prepare("UPDATE admins SET first_name = :fn, last_name = :ln, email = :email, phone = :phone, updated_at = NOW() WHERE admin_id = :id");
                    $stmt->execute([':fn'=>$first_name,':ln'=>$last_name,':email'=>$email,':phone'=>$phone,':id'=>$userid]);
                } else {
                    // Check email uniqueness
                    $stmt = $conn->prepare("SELECT admin_id FROM admins WHERE email = ? AND admin_id != ? LIMIT 1");
                    $stmt->bind_param("si",$email,$userid);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($res && $res->num_rows>0) { $stmt->close(); throw new Exception('Email address is already in use by another admin.'); }
                    $stmt->close();
                    
                    // Check phone uniqueness if provided
                    if ($phone !== '') {
                        $stmt = $conn->prepare("SELECT admin_id FROM admins WHERE phone = ? AND admin_id != ? LIMIT 1");
                        $stmt->bind_param("si",$phone,$userid);
                        $stmt->execute();
                        $res = $stmt->get_result();
                        if ($res && $res->num_rows>0) { $stmt->close(); throw new Exception('Phone number is already in use by another admin.'); }
                        $stmt->close();
                    }
                    
                    $stmt = $conn->prepare("UPDATE admins SET first_name = ?, last_name = ?, email = ?, phone = ?, updated_at = NOW() WHERE admin_id = ?");
                    $stmt->bind_param("ssssi",$first_name,$last_name,$email,$phone,$userid);
                    $stmt->execute();
                    if ($stmt->errno) { $err=$stmt->error; $stmt->close(); throw new Exception('DB error: '.$err); }
                    $stmt->close();
                }
            } else {
                if (isset($pdo) && $pdo instanceof PDO) {
                    // Check email uniqueness
                    $stmt = $pdo->prepare("SELECT janitor_id FROM janitors WHERE email = ? AND janitor_id != ? LIMIT 1");
                    $stmt->execute([$email,$userid]);
                    if ($stmt->fetch()) throw new Exception('Email address is already in use by another janitor.');
                    
                    // Check phone uniqueness if provided
                    if ($phone !== '') {
                        $stmt = $pdo->prepare("SELECT janitor_id FROM janitors WHERE phone = ? AND janitor_id != ? LIMIT 1");
                        $stmt->execute([$phone, $userid]);
                        if ($stmt->fetch()) throw new Exception('Phone number is already in use by another janitor.');
                    }
                    
          $stmt = $pdo->prepare("UPDATE janitors SET first_name = :fn, last_name = :ln, email = :email, phone = :phone, updated_at = NOW() WHERE janitor_id = :id");
          $ok = $stmt->execute([':fn'=>$first_name,':ln'=>$last_name,':email'=>$email,':phone'=>$phone,':id'=>$userid]);
          // get affected rows when possible
          $affected = method_exists($stmt, 'rowCount') ? $stmt->rowCount() : null;
                } else {
                    // Check email uniqueness
                    $stmt = $conn->prepare("SELECT janitor_id FROM janitors WHERE email = ? AND janitor_id != ? LIMIT 1");
                    $stmt->bind_param("si",$email,$userid);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($res && $res->num_rows>0) { $stmt->close(); throw new Exception('Email address is already in use by another janitor.'); }
                    $stmt->close();
                    
                    // Check phone uniqueness if provided
                    if ($phone !== '') {
                        $stmt = $conn->prepare("SELECT janitor_id FROM janitors WHERE phone = ? AND janitor_id != ? LIMIT 1");
                        $stmt->bind_param("si",$phone,$userid);
                        $stmt->execute();
                        $res = $stmt->get_result();
                        if ($res && $res->num_rows>0) { $stmt->close(); throw new Exception('Phone number is already in use by another janitor.'); }
                        $stmt->close();
                    }
                    
          $stmt = $conn->prepare("UPDATE janitors SET first_name = ?, last_name = ?, email = ?, phone = ?, updated_at = NOW() WHERE janitor_id = ?");
          $stmt->bind_param("ssssi",$first_name,$last_name,$email,$phone,$userid);
          $ok = $stmt->execute();
          $affected = $conn->affected_rows;
          if ($stmt->errno) { $err=$stmt->error; $stmt->close(); throw new Exception('DB error: '.$err); }
          $stmt->close();
                }
            }

      if (session_status() !== PHP_SESSION_ACTIVE) session_start();
      $_SESSION['name'] = trim($first_name . ' ' . $last_name);

      $resp = ['success'=>true,'message'=>'Profile updated successfully'];
      if (defined('DEV_MODE') && DEV_MODE) {
        // include helpful debug info for diagnosis
        $det = detectUserRecord($userid, $role);
        $resp['debug'] = [
          'role' => $role,
          'userid' => $userid,
          'detected_table' => $det ? $det['table'] : null,
          'detected_idField' => $det ? $det['idField'] : null,
          'affected_rows' => isset($affected) ? $affected : null
        ];
      }

      echo json_encode($resp);
      exit;
        }

        if ($action === 'change_password') {
            // Collect and validate input
            $current_password = trim($_POST['current_password'] ?? '');
            $new_password = trim($_POST['new_password'] ?? '');
            $confirm_password = trim($_POST['confirm_password'] ?? '');

            if ($current_password === '' || $new_password === '' || $confirm_password === '') {
                throw new Exception('All password fields are required.');
            }
            if ($new_password !== $confirm_password) {
                throw new Exception('New password and confirmation do not match.');
            }
            if (strlen($new_password) < 8) {
                throw new Exception('New password must be at least 8 characters.');
            }

            // DIAGNOSTICS container
            $diag = [
                'userid'=>$userid,
                'role'=>$role,
                'found_table'=>null,
                'found_idField'=>null,
                'stored_found'=>false,
                'stored_len'=>null,
                'verify_methods'=>[],
                'db_prepare_ok'=>null,
                'db_execute_ok'=>null,
                'db_error'=>null,
                'affected_rows'=>null,
            ];

            // Detect exact table/id that contain this user (handles admin vs admins, admin_id vs id)
            $detected = detectUserRecord($userid, $role);
            if (!$detected) {
                throw new Exception('Unable to find your user row in admins/janitors tables. Table or id field may be different.');
            }

            $table = $detected['table'];
            $idField = $detected['idField'];
            $diag['found_table'] = $table;
            $diag['found_idField'] = $idField;
            $stored = $detected['row']['password'] ?? '';

            if ($stored !== '') {
                $diag['stored_found'] = true;
                $diag['stored_len'] = strlen($stored);
            } else {
                throw new Exception('Stored password not found for your account.');
            }

            // Verify current password against common formats: password_verify (bcrypt/argon2), SHA-256 hex, MD5
            $verified = false;
            if (password_verify($current_password, $stored)) {
                $verified = true;
                $diag['verify_methods'][] = 'password_verify';
            } else {
                if (is_string($stored) && preg_match('/^[0-9a-f]{64}$/i', $stored)) {
                    if (hash('sha256', $current_password) === $stored) {
                        $verified = true;
                        $diag['verify_methods'][] = 'sha256';
                    }
                }
                if (!$verified && is_string($stored) && strlen($stored) === 32 && md5($current_password) === $stored) {
                    $verified = true;
                    $diag['verify_methods'][] = 'md5';
                }
            }

            if (!$verified) {
                throw new Exception('Current password is incorrect.');
            }

            // Hash new password using SHA-256 hex (per your request)
            $newHash = hash('sha256', $new_password);
            if ($newHash === '' || !preg_match('/^[0-9a-f]{64}$/i', $newHash)) {
                throw new Exception('Failed to compute SHA-256 hash for new password.');
            }
            $diag['new_hash_preview'] = substr($newHash,0,12) . '...';

            // Update the specific discovered table/idField
            try {
                if (isset($pdo) && $pdo instanceof PDO) {
                    $stmt = $pdo->prepare("UPDATE {$table} SET password = :h, updated_at = NOW() WHERE {$idField} = :id");
                    $diag['db_prepare_ok'] = $stmt !== false;
                    if ($stmt === false) {
                        $diag['db_error'] = implode(' | ', $pdo->errorInfo());
                        throw new Exception('DB prepare failed (PDO).');
                    }
                    $ok = $stmt->execute([':h'=>$newHash, ':id'=>$userid]);
                    $diag['db_execute_ok'] = (bool)$ok;
                    if ($ok === false) {
                        $diag['db_error'] = implode(' | ', $stmt->errorInfo());
                        throw new Exception('DB execute failed (PDO).');
                    }
                    $diag['affected_rows'] = $stmt->rowCount();
                } else {
                    if (!isset($conn)) throw new Exception('No DB connection ($conn not set).');
                    $stmt = $conn->prepare("UPDATE {$table} SET password = ?, updated_at = NOW() WHERE {$idField} = ?");
                    $diag['db_prepare_ok'] = $stmt !== false;
                    if (!$stmt) {
                        $diag['db_error'] = $conn->error ?? 'mysqli prepare error';
                        throw new Exception('DB prepare failed (mysqli).');
                    }
                    $stmt->bind_param("si", $newHash, $userid);
                    $ok = $stmt->execute();
                    $diag['db_execute_ok'] = (bool)$ok;
                    if ($ok === false || $stmt->errno) {
                        $diag['db_error'] = $stmt->error;
                        $stmt->close();
                        throw new Exception('DB execute failed (mysqli).');
                    }
                    $diag['affected_rows'] = $conn->affected_rows;
                    $stmt->close();
                }
            } catch (Exception $ex) {
                // Attach DB diag and rethrow to be returned to client
                $diag['db_exception'] = $ex->getMessage();
                throw new Exception('Failed to update password: ' . $ex->getMessage());
            }

            $resp = ['success'=>true, 'message'=>'Password updated successfully'];
            if (DEV_MODE) $resp['diag'] = $diag;
            echo json_encode($resp);
            exit;
        }

        throw new Exception('Unknown action');
    } catch (Exception $e) {
        error_log('[profile.php] action=' . ($action ?? '') . ' error: ' . $e->getMessage());
        $msg = $e->getMessage();
        if (!DEV_MODE) {
            if ($action === 'change_password') $msg = 'Unable to update password. Please try again.';
            elseif ($action === 'update_profile') $msg = 'Unable to update profile. Please try again.';
            else $msg = 'Request failed.';
        }
        http_response_code(400);
        $out = ['success'=>false,'message'=>$msg];
        if (DEV_MODE) $out['dev'] = true;
        echo json_encode($out);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile - Trashbin Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/janitor-dashboard.css">
  <!-- header styles are included in shared header include -->
</head>
<body>
  <div id="scrollProgress" class="scroll-progress"></div>
  <?php include_once __DIR__ . '/includes/header-admin.php'; ?>

    <div class="dashboard">
      <!-- Animated Background Circles -->
      <div class="background-circle background-circle-1"></div>
      <div class="background-circle background-circle-2"></div>
      <div class="background-circle background-circle-3"></div>

  <aside class="sidebar" id="sidebar">
      <div class="sidebar-header d-none d-md-block">
        <h6 class="sidebar-title">Menu</h6>
      </div>
      <a href="admin-dashboard.php" class="sidebar-item active">
        <i class="fa-solid fa-chart-pie"></i><span>Dashboard</span>
      </a>
      <a href="bins.php" class="sidebar-item">
        <i class="fa-solid fa-trash-alt"></i><span>Trashbins</span>
      </a>
      <a href="janitors.php" class="sidebar-item">
        <i class="fa-solid fa-users"></i><span>Maintenance Staff</span>
      </a>
      <a href="reports.php" class="sidebar-item">
        <i class="fa-solid fa-chart-line"></i><span>Reports</span>
      </a>
      <a href="notifications.php" class="sidebar-item">
        <i class="fa-solid fa-bell"></i><span>Notifications</span>
      </a>
      <a href="#" class="sidebar-item">
        <i class="fa-solid fa-gear"></i><span>Settings</span>
      </a>
      <a href="profile.php" class="sidebar-item">
        <i class="fa-solid fa-user"></i><span>My Profile</span>
      </a>
    </aside>

    <!-- Main Content -->
    <main class="content">
      <div class="section-header">
        <div>
          <h1 class="page-title">My Profile</h1>
          <p class="page-subtitle">Manage your personal information and settings</p>
        </div>
      </div>

      <div class="profile-container">
        <!-- Profile Header Card -->
        <div class="profile-header-card">
          <div class="profile-header-content">
            <div class="profile-picture-wrapper">
              <?php
                $displayName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                if ($displayName === '') $displayName = ($user['email'] ?? 'User');
                
                // Check if user has saved profile picture in database
                $profilePicSrc = '';
                if (!empty($user['profile_picture'])) {
                    $profilePicSrc = e($user['profile_picture']);
                } else {
                    // Fallback to avatar generator
                    $profilePicSrc = 'https://ui-avatars.com/api/?name=' . urlencode($displayName) . '&background=0D6EFD&color=fff&size=150';
                }
              ?>
              <img id="profileImg" src="<?php echo $profilePicSrc; ?>" 
                   alt="Profile Picture" class="profile-picture">
              <input type="file" id="photoInput" accept=".png,.jpg,.jpeg" style="display: none;">
              <button type="button" class="profile-edit-btn" id="changePhotoBtn" title="Change Photo">
                <i class="fa-solid fa-camera"></i>
              </button>
            </div>
            <div class="profile-info">
              <h2 class="profile-name" id="profileName"><?php echo e($displayName); ?></h2>
              <p class="profile-role" id="profileRole"><?php echo $role === 'admin' ? 'System Administrator' : 'Maintenance Staff'; ?></p>
              <div id="photoMessage" class="validation-message"></div>
            </div>
          </div>
        </div>

        <!-- Profile Content Grid -->
        <div class="profile-content-grid">
          <!-- Left Column -->
          <div class="profile-sidebar">
            <div class="profile-stats-card">
              <h6 class="stats-title">Quick Stats</h6>
              <div class="stat-item">
                <span class="stat-label">Total Bins</span>
                <span class="stat-value"><?php echo e($totalBins); ?></span>
              </div>
              <div class="stat-item">
                <span class="stat-label">Active Janitors</span>
                <span class="stat-value"><?php echo e($activeJanitors); ?></span>
              </div>
              <div class="stat-item">
                <span class="stat-label">Member Since</span>
                <span class="stat-value"><?php echo e(date('Y', strtotime($user['created_at'] ?? date('Y-m-d')))); ?></span>
              </div>
            </div>

            <div class="profile-menu-card">
              <h6 class="menu-title">Settings</h6>
              <a href="#personal-info" class="profile-menu-item active" onclick="showProfileTab('personal-info', this); return false;">
                <i class="fa-solid fa-user"></i>
                <span>Personal Info</span>
              </a>
              <a href="#change-password" class="profile-menu-item" onclick="showProfileTab('change-password', this); return false;">
                <i class="fa-solid fa-key"></i>
                <span>Change Password</span>
              </a>
            </div>
          </div>

          <!-- Right Column -->
          <div class="profile-main">
            <div class="tab-content">
              <!-- Personal Information Tab -->
              <div class="tab-pane fade show active" id="personal-info">
                <div class="profile-form-card">
                  <div class="form-card-header">
                    <h5><i class="fa-solid fa-user-circle me-2"></i>Personal Information</h5>
                  </div>
                  <div class="form-card-body">
                    <div id="personalInfoAlert" class="validation-message" style="display:none;"></div>
                    <form id="personalInfoForm">
                      <input type="hidden" name="action" value="update_profile">
                      <div class="form-row">
                        <div class="form-group">
                          <label class="form-label">First Name</label>
                          <input type="text" class="form-control" id="firstName" name="first_name" value="<?php echo e($user['first_name'] ?? ''); ?>" required>
                          <div class="validation-message"></div>
                        </div>
                        <div class="form-group">
                          <label class="form-label">Last Name</label>
                          <input type="text" class="form-control" id="lastName" name="last_name" value="<?php echo e($user['last_name'] ?? ''); ?>" required>
                          <div class="validation-message"></div>
                        </div>
                      </div>
                      <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo e($user['email'] ?? ''); ?>" required>
                        <div class="validation-message"></div>
                      </div>
                      <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phoneNumber" name="phone" value="<?php echo e($user['phone'] ?? ''); ?>">
                        <div class="validation-message"></div>
                      </div>
                      <button type="submit" class="btn btn-primary btn-lg" id="saveProfileBtn">
                        <i class="fa-solid fa-save me-2"></i>Save
                      </button>
                    </form>
                  </div>
                </div>
              </div>

              <!-- Change Password Tab -->
              <div class="tab-pane fade" id="change-password">
                <div class="profile-form-card">
                  <div class="form-card-header">
                    <h5><i class="fa-solid fa-lock me-2"></i>Change Password</h5>
                  </div>
                  <div class="form-card-body">
                    <div id="passwordAlert" class="validation-message" style="display:none;"></div>
                    <form id="changePasswordForm">
                      <input type="hidden" name="action" value="change_password">
                      <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <div class="password-input-container">
                          <input type="password" class="form-control password-input" id="currentPassword" name="current_password" placeholder="Enter current password" required>
                          <button type="button" class="password-toggle-btn" data-target="#currentPassword">
                            <i class="fa-solid fa-eye"></i>
                          </button>
                        </div>
                        <div class="validation-message"></div>
                      </div>
                      <div class="form-group">
                        <label class="form-label">New Password</label>
                        <div class="password-input-container">
                          <input type="password" class="form-control password-input" id="newPassword" name="new_password" placeholder="Enter new password" required>
                          <button type="button" class="password-toggle-btn" data-target="#newPassword">
                            <i class="fa-solid fa-eye"></i>
                          </button>
                        </div>
                      </div>
                      <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <div class="password-input-container">
                          <input type="password" class="form-control password-input" id="confirmNewPassword" name="confirm_password" placeholder="Confirm new password" required>
                          <button type="button" class="password-toggle-btn" data-target="#confirmNewPassword">
                            <i class="fa-solid fa-eye"></i>
                          </button>
                        </div>
                        <div class="validation-message"></div>
                      </div>
                      <button type="submit" class="btn btn-primary btn-lg" id="changePasswordBtn">
                        <i class="fa-solid fa-lock me-2"></i>Update
                      </button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
  <?php include_once __DIR__ . '/includes/footer-admin.php'; ?>

  <script src="js/password-toggle.js"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="js/bootstrap.bundle.min.js"></script>
  <script src="js/dashboard.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Change photo
      const changePhotoBtn = document.getElementById('changePhotoBtn');
      const photoInput = document.getElementById('photoInput');
      const profileImg = document.getElementById('profileImg');
      const photoMessage = document.getElementById('photoMessage');

      if (changePhotoBtn) {
        changePhotoBtn.addEventListener('click', () => photoInput.click());
      }

      // Handle photo upload
      if (photoInput) {
        photoInput.addEventListener('change', function() {
          const file = this.files[0];
          if (!file) return;

          const formData = new FormData();
          formData.append('profile_picture', file);

          photoMessage.textContent = 'Uploading...';
          photoMessage.className = 'validation-message';
          photoMessage.style.display = 'block';

          fetch('upload_profile_picture.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              // Update image src with cache buster to prevent caching issues
              const timestamp = new Date().getTime();
              profileImg.src = data.path + '?t=' + timestamp;
              photoMessage.textContent = '✓ Profile picture updated!';
              photoMessage.className = 'validation-message text-success';
              photoInput.value = ''; // Reset file input
            } else {
              photoMessage.textContent = '✗ ' + (data.message || 'Upload failed');
              photoMessage.className = 'validation-message text-danger';
            }
          })
          .catch(err => {
            photoMessage.textContent = '✗ Upload error: ' + err;
            photoMessage.className = 'validation-message text-danger';
          });
        });
      }

      // Toggle password visibility - using dedicated js/password-toggle.js instead

      // Personal info submit
      $('#personalInfoForm').on('submit', function(e) {
        e.preventDefault();
        $('#saveProfileBtn').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Saving');
        const data = $(this).serialize();
        $.post('profile.php', data, function(resp) {
          if (resp && resp.success) {
            const alertEl = $('#personalInfoAlert').removeClass().addClass('validation-message text-success').text(resp.message).removeAttr('title').show();
            const newName = $('#firstName').val().trim() + ' ' + $('#lastName').val().trim();
            $('#profileName').text(newName);
          } else {
            $('#personalInfoAlert').removeClass().addClass('validation-message text-danger').text(resp.message || 'Update failed').removeAttr('title').show();
          }
        }, 'json').fail(function(xhr){
          let msg = 'Server error';
          try { msg = xhr.responseJSON.message || msg; } catch(e){}
          $('#personalInfoAlert').removeClass().addClass('validation-message text-danger').text(msg).removeAttr('title').show();
        }).always(function(){
          $('#saveProfileBtn').prop('disabled', false).html('<i class="fa-solid fa-save me-2"></i>Save');
        });
      });

      // Change password submit
      $('#changePasswordForm').on('submit', function(e) {
        e.preventDefault();
        $('#changePasswordBtn').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Updating');
        const data = $(this).serialize();
        $.post('profile.php', data, function(resp) {
          if (resp && resp.success) {
            $('#passwordAlert').removeClass().addClass('validation-message text-success').text(resp.message).removeAttr('title').show();
            $('#changePasswordForm')[0].reset();
            if (resp.diag) console.log('diag:', resp.diag);
          } else {
            $('#passwordAlert').removeClass().addClass('validation-message text-danger').text(resp.message || 'Password change failed').removeAttr('title').show();
            if (resp && resp.diag) console.log('diag error:', resp.diag);
          }
        }, 'json').fail(function(xhr){
          let msg = 'Server error';
          try { msg = xhr.responseJSON.message || msg; } catch(e){}
          $('#passwordAlert').removeClass().addClass('validation-message text-danger').text(msg).removeAttr('title').show();
        }).always(function(){
          $('#changePasswordBtn').prop('disabled', false).html('<i class="fa-solid fa-lock me-2"></i>Update');
        });
      });
    });

    function showProfileTab(tabName, el) {
      document.querySelectorAll('.tab-pane').forEach(tab => tab.classList.remove('show','active'));
      const tab = document.getElementById(tabName);
      if (tab) tab.classList.add('show','active');
      document.querySelectorAll('.profile-menu-item').forEach(item => item.classList.remove('active'));
      if (el) el.classList.add('active');
    }
  </script>
  <!-- Janitor dashboard JS for header/footer modal helpers -->
  <script src="js/janitor-dashboard.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      try {
        const notifBtn = document.getElementById('notificationsBtn');
        if (notifBtn) notifBtn.addEventListener('click', function(e){ e.preventDefault(); if (typeof openNotificationsModal === 'function') openNotificationsModal(e); else if (typeof showModalById === 'function') showModalById('notificationsModal'); });
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) logoutBtn.addEventListener('click', function(e){ e.preventDefault(); if (typeof showLogoutModal === 'function') showLogoutModal(e); else if (typeof showModalById === 'function') showModalById('logoutModal'); else window.location.href='logout.php'; });
      } catch(err) { console.warn('Header fallback handlers error', err); }
    });
  </script>
  <script src="js/scroll-progress.js"></script>
</body>
</html>