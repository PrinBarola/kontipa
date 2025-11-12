<?php
require_once 'includes/config.php';

// Only allow logged-in users to download reports (admins and janitors)
if (!isLoggedIn()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized', true, 401);
    echo "Unauthorized";
    exit;
}

$reportId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($reportId <= 0) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request', true, 400);
    echo "Missing or invalid report id";
    exit;
}

try {
    // Locate report row (PDO preferred)
    $fileRel = null;
    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->prepare("SELECT file_path, report_name FROM reports WHERE report_id = ? LIMIT 1");
        $stmt->execute([$reportId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $fileRel = $row['file_path'] ?? null;
            $reportName = $row['report_name'] ?? ("report_{$reportId}");
        }
    } else {
        $res = $conn->query("SELECT file_path, report_name FROM reports WHERE report_id = " . intval($reportId) . " LIMIT 1");
        if ($res && $r = $res->fetch_assoc()) {
            $fileRel = $r['file_path'] ?? null;
            $reportName = $r['report_name'] ?? ("report_{$reportId}");
        }
    }

    if (empty($fileRel)) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
        echo "Report file not found";
        exit;
    }

    // Normalize path: stored file_path may be relative; ensure inside generated/reports
    $baseDir = realpath(__DIR__ . '/generated/reports');
    if ($baseDir === false) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
        echo "Server misconfiguration: reports directory missing";
        exit;
    }

    // Calculate absolute path
    $candidate = realpath(__DIR__ . '/' . ltrim($fileRel, '/\\'));
    if ($candidate === false || strpos($candidate, $baseDir) !== 0) {
        // not found or outside allowed folder
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
        echo "Report file not available";
        exit;
    }

    if (!is_file($candidate) || !is_readable($candidate)) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
        echo "Report file missing";
        exit;
    }

    // Determine filename for download
    $ext = pathinfo($candidate, PATHINFO_EXTENSION);
    $safeName = preg_replace('/[^A-Za-z0-9_\-\. ]+/', '_', ($reportName ?: "report_{$reportId}"));
    if (strtolower($ext) !== 'csv' && strtolower($ext) !== 'txt') {
        // force csv extension for Excel compatibility
        $downloadFilename = $safeName . '.csv';
    } else {
        $downloadFilename = $safeName . '.' . $ext;
    }

    // Send headers and stream file
    // Use CSV content type for .csv, otherwise generic binary
    $mime = 'application/octet-stream';
    if (strtolower($ext) === 'csv') $mime = 'text/csv; charset=UTF-8';
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . basename($downloadFilename) . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($candidate));
    // flush output buffers
    while (ob_get_level()) ob_end_clean();
    readfile($candidate);
    exit;
} catch (Exception $e) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
    error_log("[download-report] " . $e->getMessage());
    echo "Server error";
    exit;
}