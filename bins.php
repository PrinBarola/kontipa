<?php
require_once 'includes/config.php';

// ✅ Check if admin is logged in
if (!isLoggedIn() || !isAdmin()) {
    header('Location: admin-login.php');
    exit;
}

// ✅ Correct query: get all active janitors from janitors table
$janitors_query = "
    SELECT 
        janitor_id, 
        CONCAT(first_name, ' ', last_name) AS full_name
    FROM janitors
    WHERE status = 'active'
    ORDER BY first_name
";

$janitors_result = $conn->query($janitors_query);
$janitors = [];
if ($janitors_result) {
    while ($row = $janitors_result->fetch_assoc()) {
        $janitors[] = $row;
    }
}

// ==================================================
// ✅ UPDATED POST HANDLERS
// ==================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        $action = $_POST['action'];

        // ✅ ADD NEW BIN
        if ($action === 'add_bin') {

            $bin_code = $_POST['bin_code'] ?? '';
            $location = $_POST['location'] ?? '';
            $type = $_POST['bin_type'] ?? '';
            $capacity = $_POST['capacity'] ?? '';
            $status = $_POST['status'] ?? '';
            $assigned_to = $_POST['assigned_janitor'] ?? null;
            if ($assigned_to === '') $assigned_to = null;

            if (empty($bin_code) || empty($location) || empty($type) || empty($status)) {
                throw new Exception('Required fields are missing');
            }

            $stmt = $conn->prepare("
                INSERT INTO bins 
                    (bin_code, location, type, capacity, status, assigned_to, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            if (!$stmt) throw new Exception($conn->error);

            $stmt->bind_param("ssssss", $bin_code, $location, $type, $capacity, $status, $assigned_to);
            $stmt->execute();
            $new_bin_id = $stmt->insert_id;
            $stmt->close();

            // Insert notification for the new bin (uses PDO when available; falls back to mysqli)
            try {
                $creatorAdminId = getCurrentUserId() ?: null;
                $assignedJanitor = $assigned_to !== '' ? $assigned_to : null;
                $notificationType = 'success';
                $title = "New Bin Added";
                $message = "Bin '{$bin_code}' was added" . (!empty($location) ? " at {$location}" : "") . ".";

                if (isset($pdo) && $pdo instanceof PDO) {
                    $stmtN = $pdo->prepare("
                        INSERT INTO notifications (admin_id, janitor_id, bin_id, notification_type, title, message, created_at)
                        VALUES (:admin_id, :janitor_id, :bin_id, :type, :title, :message, NOW())
                    ");
                    $stmtN->execute([
                        ':admin_id' => $creatorAdminId,
                        ':janitor_id' => $assignedJanitor,
                        ':bin_id' => (int)$new_bin_id,
                        ':type' => $notificationType,
                        ':title' => $title,
                        ':message' => $message
                    ]);
                } else {
                    // fallback to mysqli
                    if ($conn->query("SHOW TABLES LIKE 'notifications'")->num_rows > 0) {
                        $stmtN = $conn->prepare("
                            INSERT INTO notifications (admin_id, janitor_id, bin_id, notification_type, title, message, created_at)
                            VALUES (?, ?, ?, ?, ?, ?, NOW())
                        ");
                        if ($stmtN) {
                            // Note: bind_param with NULL works by passing null variables.
                            $adminParam = $creatorAdminId !== null ? (int)$creatorAdminId : null;
                            $janitorParam = $assignedJanitor !== null ? (int)$assignedJanitor : null;
                            $binParam = (int)$new_bin_id;
                            $stmtN->bind_param("iiisss", $adminParam, $janitorParam, $binParam, $notificationType, $title, $message);
                            $stmtN->execute();
                            $stmtN->close();
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("[bins] notification insert failed: " . $e->getMessage());
                // Continue even if notification insertion fails
            }

            // ✅ FIXED: join with janitors table
            $stmt = $conn->prepare("
                SELECT 
                    b.*, 
                    CONCAT(j.first_name, ' ', j.last_name) AS janitor_name
                FROM bins b
                LEFT JOIN janitors j ON b.assigned_to = j.janitor_id
                WHERE b.bin_id = ?
            ");
            $stmt->bind_param("i", $new_bin_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $new_bin = $result->fetch_assoc();
            $stmt->close();

            echo json_encode(['success' => true, 'bin' => $new_bin]);
            exit;
        }

        // ✅ REASSIGN JANITOR
        if ($action === 'reassign_janitor') {
            $bin_id = intval($_POST['bin_id'] ?? 0);
            $janitor = $_POST['janitor_id'] ?? null;

            if ($janitor === '') $janitor = null;

            $stmt = $conn->prepare("
                UPDATE bins 
                SET assigned_to = ?, updated_at = NOW() 
                WHERE bin_id = ?
            ");
            $stmt->bind_param("si", $janitor, $bin_id);
            $stmt->execute();
            $stmt->close();

            echo json_encode(['success' => true]);
            exit;
        }

        // ✅ TOGGLE ACTIVE / DISABLED
        if ($action === 'toggle_active') {
            $bin_id = intval($_POST['bin_id'] ?? 0);
            $active = intval($_POST['active'] ?? 1);
            $new_status = $active ? 'empty' : 'disabled';

            $stmt = $conn->prepare("
                UPDATE bins 
                SET status = ?, updated_at = NOW() 
                WHERE bin_id = ?
            ");
            $stmt->bind_param("si", $new_status, $bin_id);
            $stmt->execute();
            $stmt->close();

            echo json_encode(['success' => true, 'status' => $new_status]);
            exit;
        }

        // ✅ EDIT BIN DETAILS
        if ($action === 'edit_bin') {
            $bin_id = intval($_POST['bin_id'] ?? 0);
            $bin_code = $_POST['bin_code'] ?? '';
            $location = $_POST['location'] ?? '';
            $type = $_POST['type'] ?? '';

            if (empty($bin_code) || empty($location) || empty($type)) {
                throw new Exception('Required fields are missing');
            }

            $stmt = $conn->prepare("
                UPDATE bins 
                SET bin_code = ?, location = ?, type = ?, updated_at = NOW()
                WHERE bin_id = ?
            ");
            $stmt->bind_param("sssi", $bin_code, $location, $type, $bin_id);
            $stmt->execute();
            $stmt->close();

            echo json_encode(['success' => true]);
            exit;
        }

        // Edit bin status (manual override)
        if ($action === 'edit_status') {
            $bin_id = intval($_POST['bin_id'] ?? 0);
            $status = $_POST['status'] ?? '';

            $valid_statuses = ['empty', 'half_full', 'full', 'needs_attention'];
            if (!in_array($status, $valid_statuses)) {
                throw new Exception('Invalid status value');
            }

            $capacity_map = [
                'empty' => 10,
                'half_full' => 50,
                'full' => 90,
                'needs_attention' => null
            ];
            
            $capacity = $capacity_map[$status];
            
            if ($capacity !== null) {
                $stmt = $conn->prepare("UPDATE bins SET status = ?, capacity = ?, updated_at = NOW() WHERE bin_id = ?");
                $stmt->bind_param("sii", $status, $capacity, $bin_id);
            } else {
                $stmt = $conn->prepare("UPDATE bins SET status = ?, updated_at = NOW() WHERE bin_id = ?");
                $stmt->bind_param("si", $status, $bin_id);
            }
            
            $stmt->execute();
            $stmt->close();

            echo json_encode(['success' => true, 'status' => $status]);
            exit;
        }

        // Delete bin (admin-only)
        if ($action === 'delete_bin') {
            $bin_id = intval($_POST['bin_id'] ?? 0);

            $stmt = $conn->prepare("DELETE FROM bins WHERE bin_id = ?");
            if (!$stmt) throw new Exception($conn->error);
            $stmt->bind_param("i", $bin_id);
            if (!$stmt->execute()) throw new Exception($stmt->error);
            $stmt->close();

            echo json_encode(['success' => true]);
            exit;
        }

        // Send notification (updated to new schema: admin_id, janitor_id, bin_id, notification_type, title, message)
        if ($action === 'send_notification') {
            $bin_id = intval($_POST['bin_id'] ?? 0);
            $message = $_POST['message'] ?? 'Please check the bin.';
            $title = "Manual Notification";
            $notificationType = 'info';

            try {
                $creatorAdminId = getCurrentUserId() ?: null;
                // determine assigned janitor for bin
                $res = $conn->query("SELECT assigned_to FROM bins WHERE bin_id = " . (int)$bin_id);
                $assigned = ($res && $r = $res->fetch_assoc()) ? $r['assigned_to'] : null;

                if (isset($pdo) && $pdo instanceof PDO) {
                    $stmtN = $pdo->prepare("
                        INSERT INTO notifications (admin_id, janitor_id, bin_id, notification_type, title, message, created_at)
                        VALUES (:admin_id, :janitor_id, :bin_id, :type, :title, :message, NOW())
                    ");
                    $stmtN->execute([
                        ':admin_id' => $creatorAdminId,
                        ':janitor_id' => $assigned,
                        ':bin_id' => $bin_id,
                        ':type' => $notificationType,
                        ':title' => $title,
                        ':message' => $message
                    ]);
                } else {
                    if ($conn->query("SHOW TABLES LIKE 'notifications'")->num_rows > 0) {
                        $stmtN = $conn->prepare("
                            INSERT INTO notifications (admin_id, janitor_id, bin_id, notification_type, title, message, created_at)
                            VALUES (?, ?, ?, ?, ?, ?, NOW())
                        ");
                        if ($stmtN) {
                            $adminParam = $creatorAdminId !== null ? (int)$creatorAdminId : null;
                            $janitorParam = $assigned !== null ? (int)$assigned : null;
                            $stmtN->bind_param("iiisss", $adminParam, $janitorParam, $bin_id, $notificationType, $title, $message);
                            $stmtN->execute();
                            $stmtN->close();
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("[bins] send_notification failed: " . $e->getMessage());
            }

            echo json_encode(['success' => true]);
            exit;
        }

        // Calibrate sensor (best-effort: update last_calibrated if column exists; otherwise return success)
        if ($action === 'calibrate_sensor') {
            $bin_id = intval($_POST['bin_id'] ?? 0);
            if ($conn->query("SHOW COLUMNS FROM `bins` LIKE 'last_calibrated'")->num_rows > 0) {
                $stmt = $conn->prepare("UPDATE bins SET last_calibrated = NOW(), updated_at = NOW() WHERE bin_id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $bin_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            echo json_encode(['success' => true]);
            exit;
        }

        // Unknown action
        throw new Exception('Unknown action');

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// ----------------- UPDATED: GET endpoints for details/history -----------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    if ($_GET['action'] === 'load_bins') {
        $filter = $_GET['filter'] ?? 'all';

        // Update join to use janitors table
        $bins_query = "SELECT bins.*, CONCAT(j.first_name, ' ', j.last_name) AS janitor_name 
                       FROM bins 
                       LEFT JOIN janitors j ON bins.assigned_to = j.janitor_id";
        if ($filter !== 'all') {
            $bins_query .= " WHERE bins.status = ?";
        }
        // ORDER: put FULL bins (or capacity >=100) first, then by capacity desc, then newest
        $bins_query .= " ORDER BY 
            CASE 
                WHEN (bins.status = 'full' OR (bins.capacity IS NOT NULL AND bins.capacity >= 100)) THEN 0
                ELSE 1
            END,
            bins.capacity DESC,
            bins.created_at DESC";

        $stmt = $conn->prepare($bins_query);
        if (!$stmt) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $conn->error]);
            exit;
        }
        // fixed: added missing parentheses around the if condition
        if ($filter !== 'all') {
            $stmt->bind_param('s', $filter);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $bins = [];
        while ($row = $result->fetch_assoc()) {
            $bins[] = $row;
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'bins' => $bins]);
        exit;
    }

    // Get details for a single bin
    if ($_GET['action'] === 'get_details') {
        $bin_id = intval($_GET['bin_id'] ?? 0);
        $stmt = $conn->prepare("
            SELECT b.*, CONCAT(j.first_name, ' ', j.last_name) AS janitor_name 
            FROM bins b 
            LEFT JOIN janitors j ON b.assigned_to = j.janitor_id 
            WHERE b.bin_id = ?
        ");
        if (!$stmt) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $conn->error]);
            exit;
        }
        $stmt->bind_param("i", $bin_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $bin = $res->fetch_assoc();
        $stmt->close();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'bin' => $bin]);
        exit;
    }

    // Get history (best-effort -- returns empty array if no history table)
    if ($_GET['action'] === 'get_history') {
        $bin_id = intval($_GET['bin_id'] ?? 0);
        $history = [];
        if ($conn->query("SHOW TABLES LIKE 'bin_history'")->num_rows > 0) {
            $stmt = $conn->prepare("SELECT * FROM bin_history WHERE bin_id = ? ORDER BY created_at DESC LIMIT 200");
            if ($stmt) {
                $stmt->bind_param("i", $bin_id);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($r = $result->fetch_assoc()) $history[] = $r;
                $stmt->close();
            }
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'history' => $history]);
        exit;
    }
}

// Fetch all bins for initial render (optional fallback)
$bins = [];
$bins_query = "SELECT bins.*, CONCAT(j.first_name, ' ', j.last_name) AS janitor_name 
               FROM bins 
               LEFT JOIN janitors j ON bins.assigned_to = j.janitor_id 
               ORDER BY 
                CASE 
                    WHEN (bins.status = 'full' OR (bins.capacity IS NOT NULL AND bins.capacity >= 100)) THEN 0
                    ELSE 1
                END,
                bins.capacity DESC,
                bins.created_at DESC";
$bins_result = $conn->query($bins_query);
if ($bins_result) {
    while ($row = $bins_result->fetch_assoc()) {
        $bins[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bins Management - Trashbin Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Bootstrap Icons added for better action menu styling -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/janitor-dashboard.css">
    <style>
      .header { overflow: visible; }
      .header .header-container .nav-buttons { display:flex; align-items:center; gap:0.75rem; }
      .nav-buttons .nav-link { position: relative; display:inline-flex; align-items:center; gap:0.5rem; }
      .nav-buttons .notification-badge, .nav-buttons #notificationCount { position:absolute !important; top:-6px !important; right:-6px !important; z-index:9999 !important; min-width:20px; height:20px; padding:0 6px; font-size:12px; display:inline-flex !important; align-items:center; justify-content:center; }
      .nav-buttons .logout-link { display:inline-flex; align-items:center; gap:0.4rem; padding:0.35rem 0.6rem; border-radius:6px; }
    </style>
    <!-- Enhanced CSS for dropdown positioning with proper z-index and overflow handling -->
    <style>
    /* ============================================
       FIXED DROPDOWN & TABLE STYLING
       ============================================ */

    /* Soft button styles */
    .btn-soft-primary { 
      background-color: rgba(13, 110, 253, 0.1); 
      color: #0d6efd; 
      border: 1px solid rgba(13, 110, 253, 0.2); 
    }
    .btn-soft-primary:hover { 
      background-color: rgba(13, 110, 253, 0.2); 
      color: #0d6efd;
    }

    .btn-soft-secondary { 
      background-color: rgba(108, 117, 125, 0.1); 
      color: #6c757d; 
      border: 1px solid rgba(108, 117, 125, 0.2); 
    }
    .btn-soft-secondary:hover { 
      background-color: rgba(108, 117, 125, 0.2); 
      color: #6c757d;
    }

    .btn-soft-dark { 
      background-color: rgba(33, 37, 41, 0.1); 
      color: #212529; 
      border: 1px solid rgba(33, 37, 41, 0.2); 
    }
    .btn-soft-dark:hover { 
      background-color: rgba(33, 37, 41, 0.2); 
      color: #212529;
    }

    /* ============================================
       TABLE & OVERFLOW FIXES
       ============================================ */

    /* Remove overflow restrictions on containers */
    .table-responsive {
      overflow: visible !important;
    }

    .card,
    .card-body {
      overflow: visible !important;
    }

    /* Table cell styling with proper text handling */
    .table td {
      vertical-align: middle;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 200px;
      position: relative;
    }

    /* Allow overflow for action column */
    .table td:last-child {
      overflow: visible !important;
      white-space: nowrap;
      min-width: 140px;
      max-width: none;
      z-index: auto;
    }

    /* Ensure table rows don't clip content and create proper stacking */
    .table tbody tr {
      position: relative;
      z-index: 1;
    }

    /* When dropdown is open, elevate the parent row (supported in modern browsers) */
    .table tbody tr:has(.dropdown-menu.show) {
      z-index: 1051 !important;
      position: relative;
    }

    /* ============================================
       ACTION BUTTONS & DROPDOWN POSITIONING
       ============================================ */

    /* Action buttons container */
    .action-buttons { 
      position: relative;
      display: flex; 
      gap: 0.5rem;
      align-items: center;
      justify-content: flex-end;
      flex-wrap: nowrap;
    }

    /* Button group positioning */
    .action-buttons .btn-group {
      position: static;
    }

    /* Dropdown toggle button */
    .action-buttons .dropdown-toggle {
      min-width: 40px;
    }

    /* CRITICAL: Dropdown menu positioning */
    .action-buttons .dropdown-menu {
      position: absolute !important;
      top: 100% !important;
      right: 0 !important;
      left: auto !important;
      z-index: 1050 !important;
      margin-top: 0.25rem !important;
      min-width: 260px;
      max-width: 300px;
      border: 1px solid rgba(0, 0, 0, 0.15);
      border-radius: 0.375rem;
      box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.175);
      background-color: #fff;
      transform: none !important;
      will-change: transform;
    }

    /* Ensure dropdown shows properly */
    .dropdown-menu.show {
      display: block !important;
      opacity: 1 !important;
      visibility: visible !important;
      transform: translateY(0) !important;
    }

    /* Dropdown items styling */
    .dropdown-menu .dropdown-item {
      padding: 0.5rem 1rem;
      font-size: 0.9rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .dropdown-menu .dropdown-item:hover {
      background-color: #f8f9fa;
    }

    .dropdown-menu .dropdown-item i {
      width: 20px;
      display: inline-block;
      text-align: center;
    }

    /* Dropdown headers */
    .dropdown-menu .dropdown-header {
      padding: 0.5rem 1rem;
      font-size: 0.75rem;
      font-weight: 600;
      color: #6c757d;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    /* Dropdown dividers */
    .dropdown-menu .dropdown-divider {
      margin: 0.5rem 0;
    }

    /* Danger text in dropdown */
    .dropdown-menu .text-danger {
      color: #dc3545 !important;
    }

    .dropdown-menu .text-danger:hover {
      background-color: #fff5f5;
      color: #dc3545 !important;
    }

    /* ============================================
       BUTTON HOVER EFFECTS
       ============================================ */

    .action-buttons button {
      transition: all 0.2s ease-in-out;
    }

    .action-buttons button:hover {
      transform: translateY(-1px);
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .action-buttons button:active {
      transform: translateY(0);
    }

    /* ============================================
       RESPONSIVE ADJUSTMENTS
       ============================================ */

    @media (max-width: 768px) {
      .table td {
        max-width: 150px;
      }

      .action-buttons {
        gap: 0.25rem;
      }

      .action-buttons .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
      }

      .action-buttons .dropdown-menu {
        min-width: 220px;
      }
    }

    /* ============================================
       Z-INDEX HIERARCHY
       ============================================ */

    .navbar {
      z-index: 1030;
    }

    .sidebar {
      z-index: 1020;
    }

    .modal {
      z-index: 1055;
    }

    .modal-backdrop {
      z-index: 1050;
    }

    .dropdown-menu {
      z-index: 1050 !important;
    }

    /* ============================================
       ADDITIONAL FIXES
       ============================================ */

    /* Fix for Bootstrap's transform interference */
    .btn-group > .dropdown-menu {
      transform: none !important;
    }

    /* Ensure proper stacking context */
    .table tbody tr:hover {
      z-index: 1;
    }

    /* Fix for dropdown appearing behind modals */
    body.modal-open .dropdown-menu {
      z-index: 1056 !important;
    }

    /* CRITICAL: Ensure dropdowns always appear above table rows */
    .table .dropdown-menu.show {
      z-index: 1051 !important;
    }

    /* Create stacking context for action cells when dropdown is open */
    .table tbody tr td:last-child:has(.dropdown-menu.show) {
      z-index: 1051 !important;
    }

    /* Smooth dropdown animation */
    .dropdown-menu {
      transition: opacity 0.15s ease-in-out;
    }

    .dropdown-menu:not(.show) {
      opacity: 0;
      pointer-events: none;
    }
  </style>
  </head>
  <body>
    <div id="scrollProgress" class="scroll-progress"></div>
    <?php include_once __DIR__ . '/includes/header-admin.php'; ?>

    <div class="dashboard">
      <!-- Animated Background Circles -->
      <div class="background-circle background-circle-1"></div>
      <div class="background-circle background-circle-2"></div>
      <div class="background-circle background-circle-3"></div>
      <!-- Sidebar -->
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
        <div class="section-header flex-column flex-md-row">
          <div>
            <h1 class="page-title">Trashbins Management</h1>
            <p class="page-subtitle">Manage all trashbins in the system</p>
          </div>
          <div class="d-flex gap-2 flex-column flex-md-row mt-3 mt-md-0">
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
              <input type="text" class="form-control border-start-0 ps-0" id="searchBinsInput" placeholder="Search bins...">
            </div>
            <div class="dropdown">
              <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="filterBinsDropdown" data-bs-toggle="dropdown">
                <i class="fas fa-filter me-1"></i>Filter
              </button>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="filterBinsDropdown">
                <li><a class="dropdown-item" href="#" data-filter="all">All Bins</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#" data-filter="full">Full</a></li>
                <li><a class="dropdown-item" href="#" data-filter="empty">Empty</a></li>
                <li><a class="dropdown-item" href="#" data-filter="needs_attention">Needs Attention</a></li>
              </ul>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBinModal">
              <i class="fas fa-plus me-1"></i>Add Bins
            </button>
          </div>
        </div>

        <div class="card">
          <div class="card-body p-0">
            <div class="table-responsive" style="overflow: visible !important;">
              <table class="table mb-0">
                <thead>
                  <tr>
                    <th>Bin ID</th>
                    <th>Location</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th class="d-none d-lg-table-cell">Capacity</th>
                    <th class="d-none d-md-table-cell">Assigned To</th>
                    <th class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody id="allBinsTableBody">
                  <tr>
                    <td colspan="7" class="text-center py-4 text-muted">No bins found</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </main>
    </div>

    <!-- Add Bin Modal -->
    <div class="modal fade" id="addBinModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="fas fa-trash-can me-2"></i>Add New Bin</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form id="addBinForm">
              <!-- Removed binId field - bin_id auto-generates in database -->
              <div class="mb-3">
                <label for="binCode" class="form-label">Bin Code</label>
                <input type="text" class="form-control" id="binCode" required>
              </div>
              <div class="mb-3">
                <label for="binLocation" class="form-label">Location</label>
                <input type="text" class="form-control" id="binLocation" required>
              </div>
              <div class="mb-3">
                <label for="binType" class="form-label">Bin Type</label>
                <select class="form-select" id="binType" required>
                  <option value="">Select type</option>
                  <option value="General">General Waste</option>
                  <option value="Recyclable">Recyclable</option>
                  <option value="Organic">Organic</option>
                </select>
              </div>
              <div class="mb-3">
                <label for="binCapacity" class="form-label">Capacity (%)</label>
                <input type="number" class="form-control" id="binCapacity" min="0" max="100" value="0" required>
              </div>
              <div class="mb-3">
                <label for="binStatus" class="form-label">Status</label>
                <select class="form-select" id="binStatus" required>
                  <option value="">Select status</option>
                  <option value="empty">Empty</option>  
                  <option value="empty">Half Full</option>
                  <option value="full">Full</option>
                  <option value="needs_attention">Needs Attention</option>
                </select>
              </div>
              <!-- Added Assign Janitor field to the form -->
              <div class="mb-3">
                <label for="binAssignedJanitor" class="form-label">Assign Janitor</label>
                <select class="form-select" id="binAssignedJanitor">
                  <option value="">Select janitor (optional)</option>
                  <?php foreach ($janitors as $janitor): ?>
                    <option value="<?php echo $janitor['janitor_id']; ?>"><?php echo htmlspecialchars($janitor['full_name']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="saveNewBin()">Save Bin</button>
          </div>
        </div>
      </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="viewDetailsModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-eye me-2"></i>Bin Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">Loading...</div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Reassign Janitor Modal -->
    <div class="modal fade" id="reassignModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-person-badge me-2"></i>Reassign Janitor</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form id="reassignForm">
              <input type="hidden" name="bin_id" value="">
              <div class="mb-3">
                <label class="form-label">Select Janitor</label>
                <select class="form-select" name="janitor_id">
                  <option value="">Unassigned</option>
                  <?php foreach ($janitors as $janitor): ?>
                    <option value="<?php echo $janitor['janitor_id']; ?>"><?php echo htmlspecialchars($janitor['full_name']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button id="reassignSaveBtn" class="btn btn-primary">Save</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Toggle Activate/Deactivate Modal -->
    <div class="modal fade" id="toggleModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-exclamation-circle me-2"></i>Confirm Action</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p id="toggleText">Are you sure?</p>
          </div>
          <div class="modal-footer">
            <form id="toggleForm">
              <input type="hidden" name="bin_id" value="">
              <input type="hidden" name="active" value="">
            </form>
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button id="toggleConfirmBtn" class="btn btn-warning">Confirm</button>
          </div>
        </div>
      </div>
    </div>

    <!-- History Modal -->
    <div class="modal fade" id="historyModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-clock-history me-2"></i>Bin History</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">Loading...</div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Send Notification Modal -->
    <div class="modal fade" id="notifyModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-bell-fill me-2"></i>Send Notification</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form id="notifyForm">
              <input type="hidden" name="bin_id" value="">
              <div class="mb-3">
                <label class="form-label">Message</label>
                <textarea class="form-control" name="message" rows="3" placeholder="Enter message for janitor..." required></textarea>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button id="notifySendBtn" class="btn btn-primary">Send</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Calibrate Sensor Modal -->
    <div class="modal fade" id="calibrateModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-sliders me-2"></i>Calibrate Sensor</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form id="calibrateForm">
              <input type="hidden" name="bin_id" value="">
              <p>This will request the microcontroller to recalibrate the sensor readings. Proceed?</p>
              <div class="alert alert-info small">
                <i class="bi bi-info-circle me-2"></i>Calibration may take a few moments. The sensor will stop reporting updates temporarily.
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button id="calibrateBtn" class="btn btn-primary">Calibrate</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Bin Details Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Bin Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form id="editForm">
              <input type="hidden" name="bin_id" value="">
              <div class="mb-3">
                <label class="form-label">Bin Code</label>
                <input class="form-control" name="bin_code" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Location</label>
                <input class="form-control" name="location" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Type</label>
                <select class="form-select" name="type" required>
                  <option value="">Select type</option>
                  <option value="General">General Waste</option>
                  <option value="Recyclable">Recyclable</option>
                  <option value="Organic">Organic</option>
                </select>
              </div>
              <div class="alert alert-info small">
                <i class="bi bi-info-circle me-2"></i><strong>Note:</strong> Status and Capacity are sensor-driven and managed by the microcontroller.
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button id="editSaveBtn" class="btn btn-primary">Save Changes</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Bin Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Delete Bin</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form id="deleteForm">
              <input type="hidden" name="bin_id" value="">
            </form>
            <div class="alert alert-danger">
              <i class="bi bi-exclamation-triangle me-2"></i><strong>Warning:</strong> Deleting a bin is irreversible. This action cannot be undone.
            </div>
            <p>Are you sure you want to delete this bin?</p>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button id="deleteConfirmBtn" class="btn btn-danger">Delete Permanently</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Status Modal -->
    <div class="modal fade" id="editStatusModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Edit Bin Status</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form id="editStatusForm">
              <input type="hidden" name="bin_id" value="">
              <div class="mb-3">
                <label class="form-label">Current Status</label>
                <input type="text" class="form-control" id="currentStatus" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label">New Status <span class="text-danger">*</span></label>
                <select class="form-select" name="status" required>
                  <option value="">Select new status</option>
                  <option value="empty">Empty (0-20%)</option>
                  <option value="half_full">Half Full (21-70%)</option>
                  <option value="full">Full (71-100%)</option>
                  <option value="needs_attention">Needs Attention</option>
                </select>
              </div>
              <div class="alert alert-warning small">
                <i class="bi bi-exclamation-circle me-2"></i><strong>Note:</strong> Manual status changes will override sensor readings. The microcontroller may update this again based on actual sensor data.
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button id="editStatusSaveBtn" class="btn btn-primary">Update Status</button>
          </div>
        </div>
      </div>
    </div>
        <?php include_once __DIR__ . '/includes/footer-admin.php'; ?>

        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/database.js"></script>
    <script src="js/dashboard.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
          function refreshBinsTable(filter = 'all') {
              $.ajax({
                  url: 'bins.php',
                  method: 'GET',
                  data: { action: 'load_bins', filter: filter },
                  dataType: 'json',
                  success: function(response) {
                      if (!response || !response.success) return;
                      const tbody = $('#allBinsTableBody');
                      tbody.empty();

                      if (!response.bins || response.bins.length === 0) {
                          tbody.html('<tr><td colspan="7" class="text-center py-4 text-muted">No bins found</td></tr>');
                          return;
                      }

                      response.bins.forEach(bin => {
                          const statusClass = getStatusBadgeClass(bin.status);
                          const statusText = getStatusDisplayText(bin.status);
                          tbody.append(`
                              <tr>
                                  <td><strong>${bin.bin_code}</strong></td>
                                  <td>${bin.location}</td>
                                  <td>${bin.type}</td>
                                  <td><span class="badge bg-${statusClass}">${statusText}</span></td>
                                  <td class="d-none d-lg-table-cell">${bin.capacity}%</td>
                                  <td class="d-none d-md-table-cell">${bin.janitor_name || 'Unassigned'}</td>
                                  <td>
                                      <div class="action-buttons">
                                          <button class="btn btn-soft-primary btn-sm" title="View Details" onclick="openViewDetails(${bin.bin_id})">
                                              <i class="bi bi-eye"></i>
                                          </button>
                                          <button class="btn btn-soft-secondary btn-sm" title="Reassign Janitor" onclick="openReassign(${bin.bin_id})">
                                              <i class="bi bi-person-badge"></i>
                                          </button>
                      <div class="btn-group">
                        <!-- Ensure dropdown uses the viewport as boundary so it won't be clipped by table/card -->
                        <button type="button" class="btn btn-soft-dark btn-sm dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" data-bs-offset="0,10" data-bs-display="dynamic" aria-expanded="false" title="More Actions">
                                                  <i class="bi bi-three-dots-vertical"></i>
                                              </button>
                                              <!-- Added data-bs-offset to position dropdown properly -->
                                              <ul class="dropdown-menu dropdown-menu-end" data-bs-offset="0,10">
                                                  <li class="dropdown-header">Status Management</li>
                                                  <li><a class="dropdown-item" href="#" onclick="openEditStatus(event, ${bin.bin_id});"><i class="bi bi-pencil-fill me-2"></i>Edit Status</a></li>
                                                  <li><a class="dropdown-item" href="#" onclick="confirmToggleActive(event, ${bin.bin_id}, '${bin.status}');"><i class="bi bi-slash-circle me-2"></i>` + (bin.status === 'disabled' ? 'Reactivate Bin' : 'Deactivate Bin') + `</a></li>
                                                  <li><a class="dropdown-item" href="#" onclick="openHistory(event, ${bin.bin_id});"><i class="bi bi-clock-history me-2"></i>View History</a></li>
                                                  <li><hr class="dropdown-divider"></li>
                                                  <li class="dropdown-header">Sensor Management</li>
                                                  <li><a class="dropdown-item" href="#" onclick="openCalibrate(event, ${bin.bin_id});"><i class="bi bi-sliders me-2"></i>Calibrate Sensor</a></li>
                                                  <li><hr class="dropdown-divider"></li>
                                                  <li class="dropdown-header">Notifications</li>
                                                  <li><a class="dropdown-item" href="#" onclick="openNotify(event, ${bin.bin_id});"><i class="bi bi-bell-fill me-2"></i>Send Notification</a></li>
                                                  <li><hr class="dropdown-divider"></li>
                                                  <li class="dropdown-header">Bin Information</li>
                                                  <li><a class="dropdown-item" href="#" onclick="openEditBin(event, ${bin.bin_id});"><i class="bi bi-pencil-square me-2"></i>Edit Details</a></li>
                                                  <li><hr class="dropdown-divider"></li>
                                                  <li><a class="dropdown-item text-danger" href="#" onclick="confirmDelete(event, ${bin.bin_id});"><i class="bi bi-trash me-2"></i>Delete Bin</a></li>
                                              </ul>
                                          </div>
                                      </div>
                                  </td>
                              </tr>
                          `);
                      });
                  },
                  error: function(xhr, status, err) {
                      console.error('Failed to load bins', err);
                  }
              });
          }

          window.openViewDetails = function(binId) {
              $.get('bins.php', { action: 'get_details', bin_id: binId }, function(resp) {
                  if (!resp || !resp.success) { alert('Failed to load details'); return; }
                  const bin = resp.bin || {};
                  $('#viewDetailsModal .modal-body').html(`
                      <div class="row mb-3">
                          <div class="col-md-6"><p><strong>Bin Code:</strong><br>${bin.bin_code || 'N/A'}</p></div>
                          <div class="col-md-6"><p><strong>Type:</strong><br>${bin.type || 'N/A'}</p></div>
                      </div>
                      <div class="row mb-3">
                          <div class="col-md-6"><p><strong>Location:</strong><br>${bin.location || 'N/A'}</p></div>
                          <div class="col-md-6"><p><strong>Assigned To:</strong><br>${bin.janitor_name || 'Unassigned'}</p></div>
                      </div>
                      <div class="row mb-3">
                          <div class="col-md-6"><p><strong>Status:</strong><br><span class="badge bg-info">${bin.status || 'N/A'}</span></p></div>
                          <div class="col-md-6"><p><strong>Capacity:</strong><br>${bin.capacity || 0}%</p></div>
                      </div>
                      <hr>
                      <div class="alert alert-info small"><i class="bi bi-info-circle me-2"></i>Status and capacity are managed by the microcontroller in real-time.</div>
                  `);
                  new bootstrap.Modal(document.getElementById('viewDetailsModal')).show();
              }, 'json');
          };

          window.openReassign = function(binId) {
              $('#reassignForm [name="bin_id"]').val(binId);
              $('#reassignForm select[name="janitor_id"]').val('');
              new bootstrap.Modal(document.getElementById('reassignModal')).show();
          };

          $('#reassignSaveBtn').on('click', function() {
              const binId = $('#reassignForm [name="bin_id"]').val();
              const janitor = $('#reassignForm select[name="janitor_id"]').val();
              $.post('bins.php', { action: 'reassign_janitor', bin_id: binId, janitor_id: janitor }, function(resp) {
                  if (resp && resp.success) {
                      $('#reassignModal').modal('hide');
                      refreshBinsTable();
                  } else {
                      alert((resp && resp.error) ? resp.error : 'Failed to reassign janitor');
                  }
              }, 'json');
          });

          window.confirmToggleActive = function(e, binId, currentStatus) {
              e.preventDefault();
              const deactivate = currentStatus !== 'disabled';
              $('#toggleForm [name="bin_id"]').val(binId);
              $('#toggleForm [name="active"]').val(deactivate ? 0 : 1);
              $('#toggleText').text(deactivate ? 'Deactivate this bin? The sensor will stop sending updates and the bin will be ignored.' : 'Reactivate this bin? The sensor will resume sending updates.');
              new bootstrap.Modal(document.getElementById('toggleModal')).show();
          };

          $('#toggleConfirmBtn').on('click', function() {
              const binId = $('#toggleForm [name="bin_id"]').val();
              const active = $('#toggleForm [name="active"]').val();
              $.post('bins.php', { action: 'toggle_active', bin_id: binId, active: active }, function(resp) {
                  if (resp && resp.success) {
                      $('#toggleModal').modal('hide');
                      refreshBinsTable();
                  } else {
                      alert((resp && resp.error) ? resp.error : 'Operation failed');
                  }
              }, 'json');
          });

          window.openHistory = function(e, binId) {
              e.preventDefault();
              $('#historyModal .modal-body').html('<p class="text-muted"><i class="bi bi-hourglass-split me-2"></i>Loading history...</p>');
              new bootstrap.Modal(document.getElementById('historyModal')).show();
              $.get('bins.php', { action: 'get_history', bin_id: binId }, function(resp) {
                  if (!resp || !resp.success) {
                      $('#historyModal .modal-body').html('<p class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Failed to load history.</p>');
                      return;
                  }
                  const items = resp.history || [];
                  if (items.length === 0) {
                      $('#historyModal .modal-body').html('<p class="text-muted"><i class="bi bi-info-circle me-2"></i>No history available</p>');
                      return;
                  }
                  let html = '<ul class="list-group">';
                  items.forEach(it => {
                      html += `<li class="list-group-item"><strong>${it.status ?? 'N/A'}</strong> <span class="badge bg-secondary">${it.created_at ?? 'N/A'}</span><div class="small text-muted mt-1">${it.note ?? 'No notes'}</div></li>`;
                  });
                  html += '</ul>';
                  $('#historyModal .modal-body').html(html);
              }, 'json');
          };

          window.openNotify = function(e, binId) {
              e.preventDefault();
              $('#notifyForm [name="bin_id"]').val(binId);
              $('#notifyForm textarea[name="message"]').val('');
              new bootstrap.Modal(document.getElementById('notifyModal')).show();
          };

          $('#notifySendBtn').on('click', function() {
              const binId = $('#notifyForm [name="bin_id"]').val();
              const msg = $('#notifyForm textarea[name="message"]').val().trim();
              if (!msg) { alert('Please enter a message'); return; }
              $.post('bins.php', { action: 'send_notification', bin_id: binId, message: msg }, function(resp) {
                  if (resp && resp.success) {
                      $('#notifyModal').modal('hide');
                      alert('Notification sent to assigned janitor.');
                      refreshBinsTable();
                  } else {
                      alert((resp && resp.error) ? resp.error : 'Failed to send notification');
                  }
              }, 'json');
          });

          window.openCalibrate = function(e, binId) {
              e.preventDefault();
              $('#calibrateForm [name="bin_id"]').val(binId);
              new bootstrap.Modal(document.getElementById('calibrateModal')).show();
          };

          $('#calibrateBtn').on('click', function() {
              const binId = $('#calibrateForm [name="bin_id"]').val();
              $.post('bins.php', { action: 'calibrate_sensor', bin_id: binId }, function(resp) {
                  if (resp && resp.success) {
                      $('#calibrateModal').modal('hide');
                      alert('Calibration command sent to microcontroller.');
                  } else {
                      alert((resp && resp.error) ? resp.error : 'Failed to calibrate sensor');
                  }
              }, 'json');
          });

          window.openEditBin = function(e, binId) {
              e.preventDefault();
              $.get('bins.php', { action: 'get_details', bin_id: binId }, function(resp) {
                  if (!resp || !resp.success) { alert('Failed to load bin'); return; }
                  const b = resp.bin || {};
                  $('#editForm [name="bin_id"]').val(b.bin_id || '');
                  $('#editForm [name="bin_code"]').val(b.bin_code || '');
                  $('#editForm [name="location"]').val(b.location || '');
                  $('#editForm [name="type"]').val(b.type || '');
                  new bootstrap.Modal(document.getElementById('editModal')).show();
              }, 'json');
          };

          $('#editSaveBtn').on('click', function() {
              const data = {
                  action: 'edit_bin',
                  bin_id: $('#editForm [name="bin_id"]').val(),
                  bin_code: $('#editForm [name="bin_code"]').val().trim(),
                  location: $('#editForm [name="location"]').val().trim(),
                  type: $('#editForm [name="type"]').val()
              };
              if (!data.bin_code || !data.location || !data.type) { alert('Please complete all required fields'); return; }
              $.post('bins.php', data, function(resp) {
                  if (resp && resp.success) {
                      $('#editModal').modal('hide');
                      refreshBinsTable();
                  } else {
                      alert((resp && resp.error) ? resp.error : 'Failed to save details');
                  }
              }, 'json');
          });

          window.confirmDelete = function(e, binId) {
              e.preventDefault();
              $('#deleteForm [name="bin_id"]').val(binId);
              new bootstrap.Modal(document.getElementById('deleteModal')).show();
          };

          $('#deleteConfirmBtn').on('click', function() {
              const binId = $('#deleteForm [name="bin_id"]').val();
              $.post('bins.php', { action: 'delete_bin', bin_id: binId }, function(resp) {
                  if (resp && resp.success) {
                      $('#deleteModal').modal('hide');
                      refreshBinsTable();
                      alert('Bin deleted successfully.');
                  } else {
                      alert((resp && resp.error) ? resp.error : 'Failed to delete bin');
                  }
              }, 'json');
          });

          function saveNewBin() {
              const formData = {
                  action: 'add_bin',
                  bin_code: document.getElementById('binCode').value.trim(),
                  location: document.getElementById('binLocation').value.trim(),
                  bin_type: document.getElementById('binType').value,
                  capacity: document.getElementById('binCapacity').value,
                  status: document.getElementById('binStatus').value,
                  assigned_janitor: document.getElementById('binAssignedJanitor').value || null
              };

              if (!formData.bin_code || !formData.location || !formData.bin_type || !formData.status) {
                  alert('Please fill in all required fields');
                  return;
              }

              $.ajax({
                  url: window.location.pathname,
                  method: 'POST', 
                  data: formData,
                  dataType: 'json',
                  success: function(response) {
                      if (response && response.success && response.bin) {
                          refreshBinsTable();
                          document.getElementById('addBinForm').reset();
                          $('#addBinModal').modal('hide');
                          alert('Bin added successfully!');
                      } else {
                          alert((response && response.error) ? response.error : 'Failed to add bin');
                      }
                  },
                  error: function(xhr, status, err) {
                      console.error('Error saving bin:', err);
                      alert('Error occurred while saving bin');
                  }
              });
          }

          window.saveNewBin = saveNewBin;

          // Search functionality
          const searchInput = document.getElementById('searchBinsInput');
          if (searchInput) {
              searchInput.addEventListener('keyup', function() {
                  const searchTerm = this.value.toLowerCase();
                  document.querySelectorAll('#allBinsTableBody tr').forEach(row => {
                      const text = row.textContent.toLowerCase();
                      row.style.display = text.includes(searchTerm) ? '' : 'none';
                  });
              });
          }

          // Filter functionality
          document.querySelectorAll('#filterBinsDropdown + .dropdown-menu .dropdown-item').forEach(item => {
              item.addEventListener('click', function(e) {
                  e.preventDefault();
                  const filter = this.getAttribute('data-filter') || 'all';
                  refreshBinsTable(filter);
              });
          });

          // Initial load
          refreshBinsTable();
      });

      // { ADDED JS: openEditStatus + save handler (place inside the same DOMContentLoaded scope where other window.* functions and handlers live) }
      window.openEditStatus = function(e, binId) {
          e.preventDefault();
          $.get('bins.php', { action: 'get_details', bin_id: binId }, function(resp) {
              if (!resp || !resp.success) {
                  alert('Failed to load bin details');
                  return;
              }
              const bin = resp.bin || {};
              $('#editStatusForm [name="bin_id"]').val(bin.bin_id || '');
              $('#currentStatus').val((bin.status || 'unknown').toUpperCase());
              $('#editStatusForm select[name="status"]').val('');
              new bootstrap.Modal(document.getElementById('editStatusModal')).show();
          }, 'json');
      };

      // ensure jQuery is available: register handler after DOM ready (this file already sets handlers similarly)
      $('#editStatusSaveBtn').on('click', function() {
          const binId = $('#editStatusForm [name="bin_id"]').val();
          const newStatus = $('#editStatusForm select[name="status"]').val();

          if (!newStatus) {
              alert('Please select a new status');
              return;
          }

          $.post('bins.php', {
              action: 'edit_status',
              bin_id: binId,
              status: newStatus
          }, function(resp) {
              if (resp && resp.success) {
                  $('#editStatusModal').modal('hide');
                  if (typeof refreshBinsTable === 'function') refreshBinsTable();
                  alert('Status updated successfully!');
              } else {
                  alert((resp && resp.error) ? resp.error : 'Failed to update status');
              }
          }, 'json');
      });

      function getStatusBadgeClass(status) {
          switch(status) {
              case 'full': return 'danger';
              case 'empty': return 'success';
              case 'half_full': return 'warning';
              case 'needs_attention': return 'info';
              case 'disabled': return 'secondary';
              default: return 'secondary';
          }
      }

      function getStatusDisplayText(status) {
          switch(status) {
              case 'full': return 'Full';
              case 'empty': return 'Empty';
              case 'half_full': return 'Half Full';
              case 'needs_attention': return 'Needs Attention';
              case 'disabled': return 'Disabled';
              default: return status;
          }
      }
    </script>
  <!-- Janitor dashboard JS for header/footer modal helpers -->
  <script src="js/janitor-dashboard.js"></script>
  <!-- JS fallback: ensure dropdowns inside tables are not clipped by ancestor overflow -->
  <script>
      (function(){
        // When a dropdown is about to be shown, temporarily set overflow: visible on nearest containers
        $(document).on('show.bs.dropdown', '.dropdown', function () {
          try {
            var $toggle = $(this);
            // collect ancestors that commonly clip overflow
            var $ancestors = $toggle.parents().filter(function(){
              return $(this).is('.card, .card-body, .table-responsive, .table, .content');
            });
            // always include the immediate parent as a safe default
            $ancestors = $ancestors.add($toggle.parent());
            // store original overflow and set visible
            $ancestors.each(function(){
              var $el = $(this);
              // only store once
              if ($el.data('orig-overflow') === undefined) {
                $el.data('orig-overflow', $el.css('overflow'));
                $el.css('overflow', 'visible');
              }
            });
          } catch(e) { console.warn('dropdown overflow fix show failed', e); }
        });

        // Restore overflow when dropdown hides
        $(document).on('hide.bs.dropdown', '.dropdown', function () {
          try {
            var $toggle = $(this);
            var $ancestors = $toggle.parents().filter(function(){
              return $(this).is('.card, .card-body, .table-responsive, .table, .content');
            });
            $ancestors = $ancestors.add($toggle.parent());
            $ancestors.each(function(){
              var $el = $(this);
              var orig = $el.data('orig-overflow');
              if (orig !== undefined) {
                $el.css('overflow', orig);
                $el.removeData('orig-overflow');
              }
            });
          } catch(e) { console.warn('dropdown overflow fix hide failed', e); }
        });
      })();
  </script>
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