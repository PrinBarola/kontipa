<?php
// test-db.php - DEBUG ONLY. Remove when done.
// Place at project root and open in browser while logged in as admin.

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/json');

$out = [
  'ok' => false,
  'session' => isset($_SESSION) ? $_SESSION : null,
  'isLoggedIn_fn_exists' => function_exists('isLoggedIn'),
  'isAdmin_fn_exists' => function_exists('isAdmin'),
  'isLoggedIn_result' => null,
  'isAdmin_result' => null,
  'pdo' => isset($pdo),
  'mysqli' => isset($conn),
  'errors' => []
];

try {
  // If functions exist, call them (may throw)
  if (function_exists('isLoggedIn')) {
    try { $out['isLoggedIn_result'] = isLoggedIn(); } catch (Exception $e) { $out['errors'][] = 'isLoggedIn error: '.$e->getMessage(); }
  }
  if (function_exists('isAdmin')) {
    try { $out['isAdmin_result'] = isAdmin(); } catch (Exception $e) { $out['errors'][] = 'isAdmin error: '.$e->getMessage(); }
  }

  if (isset($pdo) && $pdo instanceof PDO) {
    $out['ok'] = true;
    $out['totalBins'] = (int)$pdo->query("SELECT COUNT(*) FROM bins")->fetchColumn();
    $out['fullBins'] = (int)$pdo->query("SELECT COUNT(*) FROM bins WHERE status = 'full'")->fetchColumn();
    // try users then janitors
    $hasUsers = false;
    try {
      $r = $pdo->query("SHOW TABLES LIKE 'users'"); $hasUsers = $r && $r->rowCount() > 0;
    } catch(Exception $e){}
    if ($hasUsers) {
      $out['activeJanitors'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'janitor' AND status = 'active'")->fetchColumn();
    } else {
      $out['activeJanitors'] = (int)$pdo->query("SELECT COUNT(*) FROM janitors WHERE status = 'active'")->fetchColumn();
    }
    $out['collectionsToday'] = (int)$pdo->query("SELECT COUNT(*) FROM collections WHERE DATE(collected_at) = CURDATE()")->fetchColumn();
  } elseif (isset($conn)) {
    $out['ok'] = true;
    $res = $conn->query("SELECT COUNT(*) as c FROM bins"); $out['totalBins'] = $res ? (int)$res->fetch_assoc()['c'] : null;
    $res = $conn->query("SELECT COUNT(*) as c FROM bins WHERE status = 'full'"); $out['fullBins'] = $res ? (int)$res->fetch_assoc()['c'] : null;
    $res = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'janitor' AND status = 'active'");
    if ($res && $res->num_rows > 0) {
      $out['activeJanitors'] = (int)$res->fetch_assoc()['c'];
    } else {
      $res2 = $conn->query("SELECT COUNT(*) as c FROM janitors WHERE status = 'active'"); $out['activeJanitors'] = $res2 ? (int)$res2->fetch_assoc()['c'] : null;
    }
    $res = $conn->query("SELECT COUNT(*) as c FROM collections WHERE DATE(collected_at) = CURDATE()"); $out['collectionsToday'] = $res ? (int)$res->fetch_assoc()['c'] : null;
  } else {
    $out['errors'][] = "No DB connection (\$pdo or \$conn missing)";
  }
} catch (Exception $e) {
  $out['errors'][] = $e->getMessage();
}

echo json_encode($out, JSON_PRETTY_PRINT);