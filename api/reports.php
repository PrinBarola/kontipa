<?php
// api/reports.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Collections this calendar month
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS cnt
        FROM collections
        WHERE YEAR(created_at) = YEAR(CURRENT_DATE())
          AND MONTH(created_at) = MONTH(CURRENT_DATE())
    ");
    $stmt->execute();
    $collectionsThisMonth = (int)$stmt->fetchColumn();

    // Pending count (collections.status = 'pending')
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM collections WHERE status = :status");
    $stmt->execute([':status' => 'pending']);
    $pendingCount = (int)$stmt->fetchColumn();

    // Completed this month
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS cnt
        FROM collections
        WHERE status = 'completed'
          AND YEAR(created_at) = YEAR(CURRENT_DATE())
          AND MONTH(created_at) = MONTH(CURRENT_DATE())
    ");
    $stmt->execute();
    $completedThisMonth = (int)$stmt->fetchColumn();

    // Reports total count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reports");
    $stmt->execute();
    $reportsCount = (int)$stmt->fetchColumn();

    // Recent reports (latest 50)
    $stmt = $pdo->prepare("
        SELECT report_id, name, type, from_date, to_date, status, created_at
        FROM reports
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $stmt->execute();
    $recentReports = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'stats' => [
            'collectionsThisMonth' => $collectionsThisMonth,
            'pendingCount' => $pendingCount,
            'completedThisMonth' => $completedThisMonth,
            'reportsCount' => $reportsCount
        ],
        'reports' => $recentReports
    ]);
    exit;

} catch (PDOException $e) {
    error_log("[api/reports] PDOException: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}