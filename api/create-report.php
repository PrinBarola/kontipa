<?php
// api/create_report.php (robust debug-safe version)
// Replaces output with guaranteed JSON so the client won't get "invalid JSON".
// Make sure to remove this debug exposure in production.

declare(strict_types=1);

// Start output buffering to capture any accidental output (warnings, whitespace, BOM, etc.)
ob_start();

require_once __DIR__ . '/../includes/config.php';

// Capture anything emitted by includes/config.php or other includes
$preOutput = ob_get_clean();

// Force JSON response and no extra output afterwards
header('Content-Type: application/json; charset=utf-8');

// Helper to always respond as JSON
function jsonExit(array $payload, int $status = 200): void {
    http_response_code($status);
    // Ensure nothing else is output after this
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonExit(['success' => false, 'message' => 'Method not allowed'], 405);
}

// Auth check
if (!isLoggedIn() || !isAdmin()) {
    jsonExit([
        'success' => false,
        'message' => 'Unauthorized (not logged in or not admin).',
        // helpful debug for dev
        'session' => [
            'isLoggedIn' => isLoggedIn(),
            'isAdmin' => isAdmin(),
            'currentUserId' => getCurrentUserId(),
            'currentUserType' => getCurrentUserType()
        ],
        'preOutput' => $preOutput !== '' ? $preOutput : null
    ], 403);
}

// Accept JSON or form-encoded
$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $input = $decoded;
    }
}

$name = trim((string)($input['name'] ?? ''));
$type = trim((string)($input['type'] ?? ''));
$fromDate = !empty($input['from_date']) ? $input['from_date'] : null;
$toDate = !empty($input['to_date']) ? $input['to_date'] : null;

if ($name === '' || $type === '') {
    jsonExit(['success' => false, 'message' => 'Missing required fields: name and type are required.', 'preOutput' => $preOutput], 400);
}

try {
    // Primary insert: try with optional date columns
    $sql = "INSERT INTO reports (name, type, from_date, to_date, status, created_at)
            VALUES (:name, :type, :from_date, :to_date, :status, NOW())";
    $stmt = $pdo->prepare($sql);
    $status = 'pending';
    $stmt->execute([
        ':name' => $name,
        ':type' => $type,
        ':from_date' => $fromDate,
        ':to_date' => $toDate,
        ':status' => $status
    ]);

    $id = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare("SELECT report_id, name, type, from_date, to_date, status, created_at FROM reports WHERE report_id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);

    jsonExit(['success' => true, 'report' => $report, 'preOutput' => $preOutput], 200);

} catch (PDOException $e) {
    // Log server-side
    error_log("[api/create_report] PDOException: " . $e->getMessage());

    // Try fallback minimal insert if schema missing date columns
    try {
        $sql2 = "INSERT INTO reports (name, type, status, created_at) VALUES (:name, :type, :status, NOW())";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute([':name' => $name, ':type' => $type, ':status' => 'pending']);
        $id2 = (int)$pdo->lastInsertId();
        $stmt = $pdo->prepare("SELECT report_id, name, type, status, created_at FROM reports WHERE report_id = :id LIMIT 1");
        $stmt->execute([':id' => $id2]);
        $report2 = $stmt->fetch(PDO::FETCH_ASSOC);
        jsonExit(['success' => true, 'report' => $report2, 'note' => 'Inserted with fallback columns.', 'preOutput' => $preOutput], 200);
    } catch (PDOException $e2) {
        error_log("[api/create_report-fallback] PDOException: " . $e2->getMessage());
        // Return detailed error in development only
        $env = getenv('APP_ENV') ?: 'development';
        $errorMsg = ($env === 'development') ? $e2->getMessage() : 'Database error';
        jsonExit([
            'success' => false,
            'message' => 'Insert failed',
            'error' => $errorMsg,
            'preOutput' => $preOutput
        ], 500);
    }
}