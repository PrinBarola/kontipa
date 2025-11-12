<?php
// includes/get-dashboard-data.php
// Unified endpoint to return dashboard counts and optional bins list.
// Place this file at includes/get-dashboard-data.php (rename existing get-dahsboard-data.php if present)

ini_set('display_errors', 0); // set to 1 while debugging on dev only
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start(); // ensure session is active for isLoggedIn/isAdmin checks
}

require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

function respond($data) {
    echo json_encode($data);
    exit;
}

// Security: ensure the user is logged in and is admin
if (!function_exists('isLoggedIn') || !isLoggedIn() || !isAdmin()) {
    respond(['success' => false, 'message' => 'Unauthorized']);
}

$out = [
    'success' => true,
    'totalBins' => 0,
    'fullBins' => 0,
    'activeJanitors' => 0,
    'totalCollections' => 0,
    'collectionsToday' => 0,
    'bins' => []
];

try {
    // PDO path
    if (isset($pdo) && $pdo instanceof PDO) {
        $out['totalBins'] = (int)$pdo->query("SELECT COUNT(*) FROM bins")->fetchColumn();
        $out['fullBins'] = (int)$pdo->query("SELECT COUNT(*) FROM bins WHERE status = 'full'")->fetchColumn();

        // Determine janitors source (users table with role 'janitor' or janitors table)
        $hasUsers = false;
        try {
            $res = $pdo->query("SHOW TABLES LIKE 'users'");
            $hasUsers = $res && $res->rowCount() > 0;
        } catch (Exception $e) {
            $hasUsers = false;
        }

        if ($hasUsers) {
            $out['activeJanitors'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'janitor' AND status = 'active'")->fetchColumn();
        } else {
            $out['activeJanitors'] = (int)$pdo->query("SELECT COUNT(*) FROM janitors WHERE status = 'active'")->fetchColumn();
        }

        $out['totalCollections'] = (int)$pdo->query("SELECT COUNT(*) FROM collections")->fetchColumn();
        $out['collectionsToday'] = (int)$pdo->query("SELECT COUNT(*) FROM collections WHERE DATE(collected_at) = CURDATE()")->fetchColumn();

        // optional: fetch bins overview
        $stmt = $pdo->query("
            SELECT 
                b.bin_id AS id,
                COALESCE(b.bin_code, b.bin_id) AS bin_code,
                b.location,
                b.type,
                b.status,
                b.capacity,
                b.assigned_to,
                CONCAT(j.first_name, ' ', j.last_name) AS assigned_to_name,
                MAX(c.collected_at) AS last_emptied
            FROM bins b
            LEFT JOIN janitors j ON b.assigned_to = j.janitor_id
            LEFT JOIN collections c ON b.bin_id = c.bin_id
            GROUP BY b.bin_id
            ORDER BY FIELD(b.status, 'full','needs_attention','in_progress','empty','out_of_service'), b.capacity DESC
            LIMIT 500
        ");
        if ($stmt) {
            $out['bins'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } elseif (isset($conn)) {
        // mysqli path
        $res = $conn->query("SELECT COUNT(*) as c FROM bins");
        $out['totalBins'] = $res ? (int)$res->fetch_assoc()['c'] : 0;

        $res = $conn->query("SELECT COUNT(*) as c FROM bins WHERE status = 'full'");
        $out['fullBins'] = $res ? (int)$res->fetch_assoc()['c'] : 0;

        $res = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'janitor' AND status = 'active'");
        if ($res && $res->num_rows > 0) {
            $out['activeJanitors'] = (int)$res->fetch_assoc()['c'];
        } else {
            $res2 = $conn->query("SELECT COUNT(*) as c FROM janitors WHERE status = 'active'");
            $out['activeJanitors'] = $res2 ? (int)$res2->fetch_assoc()['c'] : 0;
        }

        $res = $conn->query("SELECT COUNT(*) as c FROM collections");
        $out['totalCollections'] = $res ? (int)$res->fetch_assoc()['c'] : 0;

        $res = $conn->query("SELECT COUNT(*) as c FROM collections WHERE DATE(collected_at) = CURDATE()");
        $out['collectionsToday'] = $res ? (int)$res->fetch_assoc()['c'] : 0;

        $binsRes = $conn->query("
            SELECT 
                b.bin_id AS id,
                COALESCE(b.bin_code, b.bin_id) AS bin_code,
                b.location,
                b.type,
                b.status,
                b.capacity,
                b.assigned_to,
                CONCAT(j.first_name, ' ', j.last_name) AS assigned_to_name,
                MAX(c.collected_at) AS last_emptied
            FROM bins b
            LEFT JOIN janitors j ON b.assigned_to = j.janitor_id
            LEFT JOIN collections c ON b.bin_id = c.bin_id
            GROUP BY b.bin_id
            ORDER BY FIELD(b.status, 'full','needs_attention','in_progress','empty','out_of_service'), b.capacity DESC
            LIMIT 500
        ");
        if ($binsRes) {
            while ($r = $binsRes->fetch_assoc()) $out['bins'][] = $r;
        }
    } else {
        respond(['success' => false, 'message' => 'No DB connection found']);
    }

    respond($out);
} catch (Exception $e) {
    respond(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
}
?>