<?php
require_once 'includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    header('HTTP/1.1 403 Forbidden');
    exit('Forbidden');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    exit('Invalid report id');
}

try {
    $stmt = $pdo->prepare("SELECT report_id, report_name, file_path, format, status FROM reports WHERE report_id = ? LIMIT 1");
    $stmt->execute([$id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("[download-report.php] DB error: " . $e->getMessage());
    $report = false;
}

if (!$report) {
    http_response_code(404);
    exit('Report not found');
}

// Only allow download if report completed (sa iyong requirement)
if (strtolower($report['status'] ?? '') !== 'completed') {
    http_response_code(403);
    exit('Report not ready for download');
}

$filePath = $report['file_path'] ?? null;
if (!$filePath) {
    http_response_code(404);
    exit('No file available for this report');
}

// Prevent path traversal: create absolute path and ensure it is within project directory
$baseDir = realpath(__DIR__); // project root adjust if needed
$absPath = realpath($baseDir . '/' . ltrim($filePath, '/\\'));

if ($absPath === false || strpos($absPath, $baseDir) !== 0) {
    error_log("[download-report.php] Invalid file path for report {$id}: {$filePath}");
    http_response_code(403);
    exit('Invalid file path');
}

if (!is_file($absPath) || !is_readable($absPath)) {
    http_response_code(404);
    exit('File not found');
}

// Determine filename to send
$filename = basename($absPath);

// Content-Type guess
$mime = mime_content_type($absPath) ?: 'application/octet-stream';

// Force download headers
header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($absPath));

flush();
$fp = fopen($absPath, 'rb');
if ($fp) {
    while (!feof($fp)) {
        echo fread($fp, 8192);
        flush();
    }
    fclose($fp);
}
exit;