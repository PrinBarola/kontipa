<?php
// api/dashboard-stats.php
// Returns counts for dashboard: total bins, full bins, active janitors, total collections, collections today
// Uses either PDO ($pdo) or mysqli ($conn) from includes/config.php

require_once '../includes/config.php';
header('Content-Type: application/json');

if (!function_exists('isLoggedIn') || !isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$out = [
    'success' => true,
    'totalBins' => 0,
    'fullBins' => 0,
    'activeJanitors' => 0,
    'totalCollections' => 0,
    'collectionsToday' => 0,
];

try {
    // PDO path
    if (isset($pdo) && $pdo instanceof PDO) {
        $out['totalBins'] = (int)$pdo->query("SELECT COUNT(*) FROM bins")->fetchColumn();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bins WHERE status = :s");
        $stmt->execute([':s' => 'full']);
        $out['fullBins'] = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM janitors WHERE status = :s");
        $stmt->execute([':s' => 'active']);
        $out['activeJanitors'] = (int)$stmt->fetchColumn();

        $out['totalCollections'] = (int)$pdo->query("SELECT COUNT(*) FROM collections")->fetchColumn();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM collections WHERE DATE(collected_at) = CURDATE()");
        $stmt->execute();
        $out['collectionsToday'] = (int)$stmt->fetchColumn();
    } elseif (isset($conn) && $conn instanceof mysqli) {
        // mysqli path
        $r = $conn->query("SELECT COUNT(*) AS cnt FROM bins");
        $out['totalBins'] = $r ? (int)$r->fetch_assoc()['cnt'] : 0; if ($r) $r->free();

        $r = $conn->query("SELECT COUNT(*) AS cnt FROM bins WHERE status = 'full'");
        $out['fullBins'] = $r ? (int)$r->fetch_assoc()['cnt'] : 0; if ($r) $r->free();

        $r = $conn->query("SELECT COUNT(*) AS cnt FROM janitors WHERE status = 'active'");
        $out['activeJanitors'] = $r ? (int)$r->fetch_assoc()['cnt'] : 0; if ($r) $r->free();

        $r = $conn->query("SELECT COUNT(*) AS cnt FROM collections");
        $out['totalCollections'] = $r ? (int)$r->fetch_assoc()['cnt'] : 0; if ($r) $r->free();

        $r = $conn->query("SELECT COUNT(*) AS cnt FROM collections WHERE DATE(collected_at) = CURDATE()");
        $out['collectionsToday'] = $r ? (int)$r->fetch_assoc()['cnt'] : 0; if ($r) $r->free();
    } else {
        echo json_encode(['success' => false, 'error' => 'No database connection available']);
        exit;
    }

    echo json_encode($out);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}