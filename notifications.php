<?php
require_once 'includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: admin-login.php');
    exit;
}

// helper to escape output
function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Notifications loader with DB-backed notifications + fallback from bins/janitors/contact tables.
 * Per-row action is "Read" which marks DB notification as read (or creates a read notification for synthetic entries).
 */

// --------------------
// AJAX action handlers
// --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $action = $_POST['action'];

        // Mark all as read
        if ($action === 'mark_all_read') {
            if (isset($pdo) && $pdo instanceof PDO) {
                $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE is_read = 0");
                $stmt->execute();
            } else {
                $conn->query("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE is_read = 0");
            }
            echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
            exit;
        }

        // Mark single notification as read or create+mark for synthetic items
        if ($action === 'mark_read') {
            // If notification_id provided -> update
            if (!empty($_POST['notification_id'])) {
                $id = (int)$_POST['notification_id'];
                if ($id <= 0) throw new Exception('Invalid notification id');

                if (isset($pdo) && $pdo instanceof PDO) {
                    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE notification_id = ?");
                    $stmt->execute([$id]);
                } else {
                    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE notification_id = ?");
                    if (!$stmt) throw new Exception($conn->error);
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $stmt->close();
                }

                echo json_encode(['success' => true, 'message' => 'Notification marked as read', 'notification_id' => $id]);
                exit;
            }

            // Otherwise client sent synthetic data (bin_id/janitor_id/title/message) -> insert notification marked read
            $bin_id = isset($_POST['bin_id']) && $_POST['bin_id'] !== '' ? intval($_POST['bin_id']) : null;
            $janitor_id = isset($_POST['janitor_id']) && $_POST['janitor_id'] !== '' ? intval($_POST['janitor_id']) : null;
            $title = trim($_POST['title'] ?? '');
            $message = trim($_POST['message'] ?? '');
            $notification_type = $_POST['notification_type'] ?? 'info';
            $creatorAdminId = getCurrentUserId() ?: null;

            if (empty($title) && empty($message) && !$bin_id && !$janitor_id) {
                throw new Exception('Missing data to create notification');
            }

            if (isset($pdo) && $pdo instanceof PDO) {
                $stmt = $pdo->prepare("
                    INSERT INTO notifications (admin_id, janitor_id, bin_id, notification_type, title, message, is_read, created_at, read_at)
                    VALUES (:admin_id, :janitor_id, :bin_id, :type, :title, :message, 1, NOW(), NOW())
                ");
                $stmt->execute([
                    ':admin_id' => $creatorAdminId,
                    ':janitor_id' => $janitor_id,
                    ':bin_id' => $bin_id,
                    ':type' => $notification_type,
                    ':title' => $title ?: ($bin_id ? "Bin #{$bin_id} activity" : 'Notification'),
                    ':message' => $message ?: ''
                ]);
                $newId = (int)$pdo->lastInsertId();
            } else {
                if ($conn->query("SHOW TABLES LIKE 'notifications'")->num_rows === 0) {
                    throw new Exception('notifications table not found');
                }
                $stmt = $conn->prepare("
                    INSERT INTO notifications (admin_id, janitor_id, bin_id, notification_type, title, message, is_read, created_at, read_at)
                    VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
                ");
                if (!$stmt) throw new Exception($conn->error);
                $adminParam = $creatorAdminId !== null ? (int)$creatorAdminId : null;
                $janitorParam = $janitor_id !== null ? (int)$janitor_id : null;
                $binParam = $bin_id !== null ? (int)$bin_id : null;
                $typeParam = $notification_type;
                $titleParam = $title ?: ($bin_id ? "Bin #{$bin_id} activity" : 'Notification');
                $messageParam = $message ?: '';
                $stmt->bind_param("iiisss", $adminParam, $janitorParam, $binParam, $typeParam, $titleParam, $messageParam);
                $stmt->execute();
                $newId = $stmt->insert_id;
                $stmt->close();
            }

            echo json_encode(['success' => true, 'message' => 'Notification created and marked read', 'notification_id' => $newId]);
            exit;
        }

        // Clear all notifications (delete)
        if ($action === 'clear_all') {
            if (isset($pdo) && $pdo instanceof PDO) {
                $stmt = $pdo->prepare("DELETE FROM notifications");
                $stmt->execute();
            } else {
                $conn->query("DELETE FROM notifications");
            }
            echo json_encode(['success' => true, 'message' => 'All notifications cleared']);
            exit;
        }

        throw new Exception('Unknown action');
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// --------------------
// Load notifications and fallback (bins, janitors, contact tables)
// --------------------
$notifications = []; // unified list

function push_notification_array(&$list, $id, $type, $title, $message, $created_at, $is_read = 0, $admin_id = null, $janitor_id = null, $bin_id = null, $bin_code = null, $janitor_name = null) {
    $list[] = [
        'notification_id' => $id,
        'notification_type' => $type,
        'title' => $title,
        'message' => $message,
        'created_at' => $created_at,
        'is_read' => $is_read,
        'admin_id' => $admin_id,
        'janitor_id' => $janitor_id,
        'bin_id' => $bin_id,
        'bin_code' => $bin_code,
        'janitor_name' => $janitor_name,
        'admin_name' => null
    ];
}

// Utility: check if a column exists in a table (works for PDO or mysqli)
function column_exists_in_table($table, $column) {
    global $pdo, $conn;
    try {
        if (isset($pdo) && $pdo instanceof PDO) {
            $r = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE " . $pdo->quote($column));
            return ($r && $r->rowCount() > 0);
        } else {
            $r = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
            return ($r && $r->num_rows > 0);
        }
    } catch (Exception $e) {
        return false;
    }
}

// Try to detect password change via common tables (password_resets / password_history) for a janitor at a given timestamp
function janitor_password_changed_at($janitor_id, $timestamp) {
    global $pdo, $conn;
    if (!$janitor_id || !$timestamp) return false;
    $date = date('Y-m-d H:i:s', strtotime($timestamp));
    $candidateTables = ['password_resets', 'password_history', 'janitor_password_changes'];
    foreach ($candidateTables as $tbl) {
        try {
            if (isset($pdo) && $pdo instanceof PDO) {
                $r = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($tbl));
                if (!($r && $r->rowCount() > 0)) continue;
                // Try to find an entry matching janitor_id and same or near timestamp (allow small tolerance)
                $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM {$tbl} WHERE (janitor_id = :jid OR user_id = :jid) AND (created_at BETWEEN :t1 AND :t2) LIMIT 1");
                $t1 = date('Y-m-d H:i:s', strtotime($date) - 5);
                $t2 = date('Y-m-d H:i:s', strtotime($date) + 5);
                $stmt->execute([':jid' => $janitor_id, ':t1' => $t1, ':t2' => $t2]);
                $c = (int)$stmt->fetchColumn();
                if ($c > 0) return true;
            } else {
                $r = $conn->query("SHOW TABLES LIKE '{$tbl}'");
                if (!($r && $r->num_rows > 0)) continue;
                $t1 = date('Y-m-d H:i:s', strtotime($date) - 5);
                $t2 = date('Y-m-d H:i:s', strtotime($date) + 5);
                $qr = $conn->query("SELECT COUNT(*) AS c FROM {$tbl} WHERE (janitor_id = " . intval($janitor_id) . " OR user_id = " . intval($janitor_id) . ") AND (created_at BETWEEN '{$t1}' AND '{$t2}') LIMIT 1");
                if ($qr) {
                    $row = $qr->fetch_assoc();
                    if (!empty($row['c']) && intval($row['c']) > 0) return true;
                }
            }
        } catch (Exception $e) {
            // ignore and continue
        }
    }
    return false;
}

try {
    // check notifications table existence
    $hasNotificationsTable = false;
    if (isset($pdo) && $pdo instanceof PDO) {
        $r = $pdo->query("SHOW TABLES LIKE 'notifications'");
        $hasNotificationsTable = ($r && $r->rowCount() > 0);
    } else {
        $r = $conn->query("SHOW TABLES LIKE 'notifications'");
        $hasNotificationsTable = ($r && $r->num_rows > 0);
    }

    // 1) load DB notifications if exists
    if ($hasNotificationsTable) {
        if (isset($pdo) && $pdo instanceof PDO) {
            $stmt = $pdo->query("
                SELECT
                    n.notification_id,
                    n.admin_id,
                    n.janitor_id,
                    n.bin_id,
                    n.notification_type,
                    n.title,
                    n.message,
                    n.is_read,
                    n.created_at,
                    n.read_at,
                    b.bin_code,
                    CONCAT(j.first_name, ' ', j.last_name) AS janitor_name
                FROM notifications n
                LEFT JOIN bins b ON n.bin_id = b.bin_id
                LEFT JOIN janitors j ON n.janitor_id = j.janitor_id
                ORDER BY n.created_at DESC
                LIMIT 1000
            ");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $adminStmt = $pdo->prepare("SELECT * FROM admins WHERE admin_id = ? LIMIT 1");
            foreach ($rows as $row) {
                $row['admin_name'] = null;
                if (!empty($row['admin_id'])) {
                    try {
                        $adminStmt->execute([(int)$row['admin_id']]);
                        $adminRow = $adminStmt->fetch(PDO::FETCH_ASSOC);
                        if ($adminRow) {
                            if (!empty($adminRow['username'])) $row['admin_name'] = $adminRow['username'];
                            elseif (!empty($adminRow['first_name']) || !empty($adminRow['last_name'])) $row['admin_name'] = trim(($adminRow['first_name'] ?? '') . ' ' . ($adminRow['last_name'] ?? ''));
                            elseif (!empty($adminRow['name'])) $row['admin_name'] = $adminRow['name'];
                            else $row['admin_name'] = 'Admin #' . ($adminRow['admin_id'] ?? $row['admin_id']);
                        }
                    } catch (Exception $e) {
                        $row['admin_name'] = 'Admin #' . (int)$row['admin_id'];
                    }
                }
                $notifications[] = $row;
            }
        } else {
            $res = $conn->query("
                SELECT
                    n.notification_id,
                    n.admin_id,
                    n.janitor_id,
                    n.bin_id,
                    n.notification_type,
                    n.title,
                    n.message,
                    n.is_read,
                    n.created_at,
                    n.read_at,
                    b.bin_code,
                    CONCAT(j.first_name, ' ', j.last_name) AS janitor_name
                FROM notifications n
                LEFT JOIN bins b ON n.bin_id = b.bin_id
                LEFT JOIN janitors j ON n.janitor_id = j.janitor_id
                ORDER BY n.created_at DESC
                LIMIT 1000
            ");
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $row['admin_name'] = null;
                    if (!empty($row['admin_id'])) {
                        if ($stmtA = $conn->prepare("SELECT * FROM admins WHERE admin_id = ? LIMIT 1")) {
                            $stmtA->bind_param("i", $row['admin_id']);
                            $stmtA->execute();
                            $r2 = $stmtA->get_result()->fetch_assoc();
                            if ($r2) {
                                if (!empty($r2['username'])) $row['admin_name'] = $r2['username'];
                                elseif (!empty($r2['first_name']) || !empty($r2['last_name'])) $row['admin_name'] = trim(($r2['first_name'] ?? '') . ' ' . ($r2['last_name'] ?? ''));
                                elseif (!empty($r2['name'])) $row['admin_name'] = $r2['name'];
                                else $row['admin_name'] = 'Admin #' . ($r2['admin_id'] ?? $row['admin_id']);
                            }
                            $stmtA->close();
                        }
                    }
                    $notifications[] = $row;
                }
            }
        }
    }

    // Build present sets
    $presentBins = [];
    $presentJanitors = [];
    $presentMessages = [];
    foreach ($notifications as $n) {
        if (!empty($n['bin_id'])) $presentBins[(int)$n['bin_id']] = true;
        if (!empty($n['janitor_id'])) $presentJanitors[(int)$n['janitor_id']] = true;
        if (!empty($n['title']) || !empty($n['message'])) {
            $presentMessages[md5(($n['title'] ?? '') . '::' . ($n['message'] ?? ''))] = true;
        }
    }

    // 2) recent bins
    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->query("SELECT bin_id, bin_code, location, created_at FROM bins ORDER BY created_at DESC LIMIT 50");
        $bins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $bins = [];
        $res = $conn->query("SELECT bin_id, bin_code, location, created_at FROM bins ORDER BY created_at DESC LIMIT 50");
        if ($res) while ($r = $res->fetch_assoc()) $bins[] = $r;
    }
    foreach ($bins as $b) {
        $bid = (int)$b['bin_id'];
        $finger = md5("bin_created::" . ($b['bin_code'] ?? '') . '::' . ($b['created_at'] ?? ''));
        if (!isset($presentBins[$bid]) && !isset($presentMessages[$finger])) {
            push_notification_array(
                $notifications,
                null,
                'new_bin',
                "New bin added: " . ($b['bin_code'] ?? "Bin #{$bid}"),
                "A new bin (" . ($b['bin_code'] ?? "Bin #{$bid}") . ")" . (!empty($b['location']) ? " at {$b['location']}" : "") . ".",
                $b['created_at'] ?? null
            );
            $presentBins[$bid] = true;
            $presentMessages[$finger] = true;
        }
    }

    // 3) recent janitors
    // Detect password changes using multiple heuristics; the user requested to rely on reset_token_hash if present.
    $hasPasswordChangedColumn = column_exists_in_table('janitors', 'password_changed_at');
    $hasResetHashColumn = column_exists_in_table('janitors', 'reset_token_hash');

    if (isset($pdo) && $pdo instanceof PDO) {
        $selectCols = "janitor_id, first_name, last_name, email, created_at, updated_at";
        if ($hasPasswordChangedColumn) $selectCols .= ", password_changed_at";
        if ($hasResetHashColumn) $selectCols .= ", reset_token_hash";
        $stmt = $pdo->query("SELECT {$selectCols} FROM janitors ORDER BY GREATEST(created_at, IFNULL(updated_at, '1970-01-01')) DESC LIMIT 50");
        $janitors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $selectCols = "janitor_id, first_name, last_name, email, created_at, updated_at";
        if ($hasPasswordChangedColumn) $selectCols .= ", password_changed_at";
        if ($hasResetHashColumn) $selectCols .= ", reset_token_hash";
        $janitors = [];
        $res = $conn->query("SELECT {$selectCols} FROM janitors ORDER BY COALESCE(updated_at, created_at) DESC LIMIT 50");
        if ($res) while ($r = $res->fetch_assoc()) $janitors[] = $r;
    }

    foreach ($janitors as $j) {
        $jid = (int)$j['janitor_id'];
        $full = trim(($j['first_name'] ?? '') . ' ' . ($j['last_name'] ?? ''));
        if ($full === '') $full = $j['email'] ?? "Janitor #{$jid}";

        $createdFinger = md5("janitor_created::{$jid}::" . ($j['created_at'] ?? ''));
        if (!isset($presentJanitors[$jid]) && !isset($presentMessages[$createdFinger])) {
            push_notification_array($notifications, null, 'new_janitor', "New janitor account: " . $full, "A new janitor account was created: " . $full . ".", $j['created_at'] ?? null);
            $presentJanitors[$jid] = true;
            $presentMessages[$createdFinger] = true;
        }

        if (!empty($j['updated_at']) && $j['updated_at'] !== $j['created_at']) {
            $updatedFinger = md5("janitor_updated::{$jid}::" . ($j['updated_at'] ?? ''));

            // Determine whether the update was a password change or a profile update.
            $passwordChanged = false;

            // 1) If the janitors table has a dedicated password_changed_at column and it matches updated_at
            if ($hasPasswordChangedColumn && !empty($j['password_changed_at'])) {
                $pc = date('Y-m-d H:i:s', strtotime($j['password_changed_at']));
                $ua = date('Y-m-d H:i:s', strtotime($j['updated_at']));
                if ($pc === $ua) {
                    $passwordChanged = true;
                }
            }

            // 2) If a reset_token_hash column exists, and there's a reset_token_hash present at update time,
            //    treat that as an indicator that a password-reset/change occurred (per request).
            //    Heuristic: prefer this indicator if reset_token_hash is non-empty and updated_at changed.
            if (!$passwordChanged && $hasResetHashColumn) {
                // If reset_token_hash exists in row and updated_at != created_at, assume password change occurred.
                // Note: this is a heuristic because we cannot access historical value here; it's based on the user's instruction.
                if (!empty($j['reset_token_hash']) && (!empty($j['updated_at']) && $j['updated_at'] !== $j['created_at'])) {
                    $passwordChanged = true;
                }
            }

            // 3) Fallback: try to detect via common password tables (password_resets, password_history, janitor_password_changes)
            if (!$passwordChanged) {
                if (janitor_password_changed_at($jid, $j['updated_at'])) {
                    $passwordChanged = true;
                }
            }

            if (!isset($presentMessages[$updatedFinger])) {
                if ($passwordChanged) {
                    // Security notification: password changed
                    push_notification_array(
                        $notifications,
                        null,
                        'security',
                        "Changed password: " . $full,
                        "{$full} changed their password.",
                        $j['updated_at'] ?? null,
                        0,
                        null,
                        $jid
                    );
                } else {
                    // Regular profile update
                    push_notification_array($notifications, null, 'warning', "Janitor profile updated: " . $full, "{$full} updated their profile.", $j['updated_at'] ?? null, 0, null, $jid);
                }
                $presentMessages[$updatedFinger] = true;
            }
        }
    }

    // 4) common contact/message tables
    $messageTables = ['contact_messages', 'messages', 'contacts', 'inquiries', 'contact'];
    foreach ($messageTables as $tbl) {
        $exists = false;
        if (isset($pdo) && $pdo instanceof PDO) {
            $r = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($tbl));
            $exists = ($r && $r->rowCount() > 0);
        } else {
            $r = $conn->query("SHOW TABLES LIKE '{$tbl}'");
            $exists = ($r && $r->num_rows > 0);
        }
        if ($exists) {
            if (isset($pdo) && $pdo instanceof PDO) {
                $stmt = $pdo->query("SELECT id, name, email, subject, message, created_at FROM {$tbl} ORDER BY created_at DESC LIMIT 50");
                $msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $msgs = [];
                $res = $conn->query("SELECT id, name, email, subject, message, created_at FROM {$tbl} ORDER BY created_at DESC LIMIT 50");
                if ($res) while ($r2 = $res->fetch_assoc()) $msgs[] = $r2;
            }
            foreach ($msgs as $m) {
                $mid = $m['id'] ?? null;
                $finger = md5("contact::" . ($m['subject'] ?? '') . '::' . ($m['message'] ?? '') . '::' . ($m['created_at'] ?? ''));
                if (!isset($presentMessages[$finger])) {
                    $title = !empty($m['subject']) ? "Message: " . $m['subject'] : "New contact message from " . ($m['name'] ?? ($m['email'] ?? 'Guest'));
                    $body = trim(($m['message'] ?? '') . (!empty($m['email']) ? "\nFrom: {$m['email']}" : ''));
                    push_notification_array($notifications, null, 'info', $title, $body, $m['created_at'] ?? null);
                    $presentMessages[$finger] = true;
                }
            }
            break;
        }
    }

    // sort by created_at desc
    usort($notifications, function($a, $b) {
        $ta = strtotime($a['created_at'] ?? '1970-01-01 00:00:00');
        $tb = strtotime($b['created_at'] ?? '1970-01-01 00:00:00');
        return $tb <=> $ta;
    });

} catch (Exception $e) {
    error_log("[notifications] error loading notifications: " . $e->getMessage());
    $notifications = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Notifications - Trashbin Admin</title>
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/janitor-dashboard.css">
  <!-- header styles are included in shared header include -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    #notifToastContainer { position: fixed; top: 1rem; right: 1rem; z-index: 2000; }
  </style>
</head>
<body>
<?php include_once __DIR__ . '/includes/header-admin.php'; ?>

<div id="notifToastContainer" aria-live="polite" aria-atomic="true"></div>

<div class="dashboard">
  <!-- Animated Background Circles -->
  <div class="background-circle background-circle-1"></div>
  <div class="background-circle background-circle-2"></div>
  <div class="background-circle background-circle-3"></div>
  <!-- Sidebar (restored full menu) -->
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

  <main class="content">
    <div class="section-header d-flex justify-content-between align-items-start">
      <div>
        <h1 class="page-title">Notifications & Logs</h1>
        <p class="page-subtitle">System notifications and activity logs</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-secondary" id="markAllReadBtn"><i class="fas fa-check-double me-1"></i>Mark All as Read</button>
        <button class="btn btn-sm btn-outline-danger" id="clearNotificationsBtn"><i class="fas fa-trash-alt me-1"></i>Clear All</button>
      </div>
    </div>

    <div class="card mt-3">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table mb-0">
            <thead>
              <tr>
                <th>Time</th>
                <th>Type</th>
                <th>Title</th>
                <th class="d-none d-md-table-cell">Message</th>
                <th class="d-none d-lg-table-cell">Target</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody id="notificationsTableBody">
              <?php if (empty($notifications)): ?>
                <tr><td colspan="6" class="text-center py-4 text-muted">No notifications found</td></tr>
              <?php else: foreach ($notifications as $n):
                $time = $n['created_at'] ?? null;
                $timeDisplay = $time ? e(date('Y-m-d H:i', strtotime($time))) : '-';
                $type = e($n['notification_type'] ?? 'info');
                $title = e($n['title'] ?? '');
                $message = e($n['message'] ?? '');
                $target = '-';
                $nid = !empty($n['notification_id']) ? (int)$n['notification_id'] : null;
                if (!empty($n['bin_id'])) {
                  $target = e($n['bin_code'] ?? ("Bin #{$n['bin_id']}"));
                } elseif (!empty($n['janitor_id'])) {
                  $target = e($n['janitor_name'] ?? ("Janitor #{$n['janitor_id']}"));
                } elseif (!empty($n['admin_id'])) {
                  $target = e($n['admin_name'] ?? ("Admin #{$n['admin_id']}"));
                }
                $isRead = (int)($n['is_read'] ?? 0) === 1;
              ?>
              <tr data-id="<?php echo $nid !== null ? $nid : ''; ?>"
                  data-bin-id="<?php echo e($n['bin_id'] ?? ''); ?>"
                  data-janitor-id="<?php echo e($n['janitor_id'] ?? ''); ?>"
                  data-title="<?php echo e($n['title'] ?? ''); ?>"
                  data-message="<?php echo e($n['message'] ?? ''); ?>"
                  class="<?php echo $isRead ? 'table-light' : ''; ?>">
                <td><?php echo $timeDisplay; ?></td>
                <td><?php echo ucfirst($type); ?></td>
                <td><?php echo $title; ?></td>
                <td class="d-none d-md-table-cell"><small class="text-muted"><?php echo $message; ?></small></td>
                <td class="d-none d-lg-table-cell"><?php echo $target; ?></td>
                <td class="text-end">
                  <?php if ($nid !== null && !$isRead): ?>
                    <button class="btn btn-sm btn-success mark-read-btn" data-id="<?php echo $nid; ?>"><i class="fas fa-check me-1"></i>Read</button>
                  <?php elseif ($nid !== null && $isRead): ?>
                    <span class="text-muted small">Read</span>
                  <?php else: ?>
                    <button class="btn btn-sm btn-success mark-read-btn" data-id="" data-bin-id="<?php echo e($n['bin_id'] ?? ''); ?>" data-janitor-id="<?php echo e($n['janitor_id'] ?? ''); ?>" data-title="<?php echo e($n['title'] ?? ''); ?>" data-message="<?php echo e($n['message'] ?? ''); ?>"><i class="fas fa-check me-1"></i>Read</button>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>
  <?php include_once __DIR__ . '/includes/footer-admin.php'; ?>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
<script>
(function() {
  function showToast(message, type = 'info') {
    const id = 'toast-' + Math.random().toString(36).slice(2,9);
    const bg = {
      info: 'bg-info text-white',
      success: 'bg-success text-white',
      danger: 'bg-danger text-white',
      warning: 'bg-warning text-dark'
    }[type] || 'bg-secondary text-white';
    const toastHtml = `
      <div id="${id}" class="toast ${bg}" role="status" aria-live="polite" aria-atomic="true" data-bs-delay="3000">
        <div class="d-flex">
          <div class="toast-body">${message}</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>`;
    $('#notifToastContainer').append(toastHtml);
    const toastEl = document.getElementById(id);
    const toast = new bootstrap.Toast(toastEl);
    toast.show();
    toastEl.addEventListener('hidden.bs.toast', function () { toastEl.remove(); });
  }

  // Mark single notification read (works for DB-backed and synthetic)
  $(document).on('click', '.mark-read-btn', function (e) {
    e.preventDefault();
    const $btn = $(this);
    const $row = $btn.closest('tr');
    const id = $btn.data('id') || '';
    const binId = $btn.attr('data-bin-id') || $row.attr('data-bin-id') || '';
    const janitorId = $btn.attr('data-janitor-id') || $row.attr('data-janitor-id') || '';
    const title = $btn.attr('data-title') || $row.attr('data-title') || '';
    const message = $btn.attr('data-message') || $row.attr('data-message') || '';

    $btn.prop('disabled', true).text('Marking...');

    const payload = { action: 'mark_read' };
    if (id) {
      payload.notification_id = id;
    } else {
      if (binId) payload.bin_id = binId;
      if (janitorId) payload.janitor_id = janitorId;
      payload.title = title || '';
      payload.message = message || '';
      payload.notification_type = 'info';
    }

    $.post('notifications.php', payload, function (resp) {
      if (resp && resp.success) {
        const newId = resp.notification_id || null;
        if (newId) {
          $row.attr('data-id', newId);
        }
        $row.addClass('table-light');
        $btn.remove();
        showToast(resp.message || 'Marked as read', 'success');
      } else {
        showToast((resp && resp.message) ? resp.message : 'Failed to mark notification', 'danger');
        $btn.prop('disabled', false).html('<i class="fas fa-check me-1"></i>Read');
      }
    }, 'json').fail(function(xhr) {
      const msg = xhr && xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Server error';
      showToast(msg, 'danger');
      $btn.prop('disabled', false).html('<i class="fas fa-check me-1"></i>Read');
    });
  });

  // Mark all as read
  $('#markAllReadBtn').on('click', function () {
    if (!confirm('Mark all notifications as read?')) return;
    const $btn = $(this);
    $btn.prop('disabled', true).text('Marking all...');
    $.post('notifications.php', { action: 'mark_all_read' }, function (resp) {
      if (resp && resp.success) {
        $('#notificationsTableBody tr').each(function () {
          const id = $(this).attr('data-id');
          $(this).addClass('table-light');
          $(this).find('.mark-read-btn').remove();
        });
        showToast(resp.message || 'All notifications marked as read', 'success');
      } else {
        showToast((resp && resp.message) ? resp.message : 'Failed to mark all', 'danger');
      }
    }, 'json').always(function () {
      $btn.prop('disabled', false).html('<i class="fas fa-check-double me-1"></i>Mark All as Read');
    }).fail(function() { showToast('Server error', 'danger'); });
  });

  // Clear all
  $('#clearNotificationsBtn').on('click', function () {
    if (!confirm('Clear all notifications? This will delete them permanently.')) return;
    const $btn = $(this);
    $btn.prop('disabled', true).text('Clearing...');
    $.post('notifications.php', { action: 'clear_all' }, function (resp) {
      if (resp && resp.success) {
        $('#notificationsTableBody').html('<tr><td colspan="6" class="text-center py-4 text-muted">No notifications found</td></tr>');
        showToast(resp.message || 'Notifications cleared', 'success');
      } else {
        showToast((resp && resp.message) ? resp.message : 'Failed to clear notifications', 'danger');
      }
    }, 'json').always(function () {
      $btn.prop('disabled', false).html('<i class="fas fa-trash-alt me-1"></i>Clear All');
    }).fail(function() { showToast('Server error', 'danger'); });
  });
})();
</script>
<!-- Janitor dashboard JS for header/footer modal helpers -->
<script src="js/janitor-dashboard.js"></script>

<!-- Fallback wiring: ensure notification bell and logout open the janitor modals and do not navigate -->
<script>
  document.addEventListener('DOMContentLoaded', function() {
    try {
      const notifBtn = document.getElementById('notificationsBtn');
      if (notifBtn) {
        notifBtn.addEventListener('click', function(e) {
          e.preventDefault();
          if (typeof openNotificationsModal === 'function') openNotificationsModal(e);
          else if (typeof showModalById === 'function') showModalById('notificationsModal');
        });
      }

      const logoutBtn = document.getElementById('logoutBtn');
      if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
          e.preventDefault();
          if (typeof showLogoutModal === 'function') showLogoutModal(e);
          else if (typeof showModalById === 'function') showModalById('logoutModal');
          else window.location.href = 'logout.php';
        });
      }
    } catch (err) {
      console.warn('Header fallback handlers error', err);
    }
  });
</script>
</body>
</html>