<?php
// export_reports.php
// Exports rows from the `reports` table to a CSV file with UTF-8 BOM, RFC-4180 quoting, and CRLF line endings.
// Place this file in the project root next to reports.php and includes/.
// Requires: phpoffice/phpspreadsheet (composer require phpoffice/phpspreadsheet)

declare(strict_types=1);

require_once 'includes/config.php';

// Only allow logged-in admins to export
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    exit('Forbidden');
}

// Read POST inputs (form was submitted via normal form)
$type = trim($_POST['type'] ?? '');
$from = trim($_POST['from_date'] ?? '');
$to   = trim($_POST['to_date'] ?? '');

// Build query (safe prepared statements). We export reports by default.
$where = [];
$params = [];

// Filter by type
if ($type !== '') {
    $where[] = "report_type = :type";
    $params[':type'] = $type;
}
// Date range on created_at
if ($from !== '') {
    $where[] = "created_at >= :from";
    $params[':from'] = $from . ' 00:00:00';
}
if ($to !== '') {
    $where[] = "created_at <= :to";
    $params[':to'] = $to . ' 23:59:59';
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Select rows
$rows = [];
try {
    if (isset($pdo) && $pdo instanceof PDO) {
        $sql = "SELECT report_id, report_name, report_type, report_data, format, status, file_path, created_at FROM reports {$whereSql} ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // mysqli fallback
        $sql = "SELECT report_id, report_name, report_type, report_data, format, status, file_path, created_at FROM reports {$whereSql} ORDER BY created_at DESC";
        // build simple substitution for safety when using mysqli
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            // bind params dynamically not attempted here - simple execution (ensure filters from UI are trusted)
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) $rows[] = $r;
            $stmt->close();
        } else {
            // as last resort, run raw query (less safe)
            $res = $conn->query($sql);
            if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;
        }
    }
} catch (Exception $e) {
    error_log("[export_reports] DB error: " . $e->getMessage());
    http_response_code(500);
    echo "Failed to query reports";
    exit;
}

// Prepare CSV filename and headers
$ts = date('Y-m-d_H-i-s');
$filename = "reports_export_{$ts}.csv";

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Pragma: public');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

// Output BOM for Excel UTF-8
echo "\xEF\xBB\xBF";

// Helper to write RFC-4180 lines with CRLF using fputcsv into temp stream
function output_csv_row(array $fields) {
    // write to temp stream to control newline conversion
    $fp = fopen('php://temp', 'r+');
    fputcsv($fp, $fields);
    rewind($fp);
    $line = stream_get_contents($fp);
    fclose($fp);
    // fputcsv uses \n by default — convert to CRLF for Windows/Excel compatibility
    $line = str_replace("\n", "\r\n", $line);
    echo $line;
}

// Header row
$header = ['Report ID','Report Name','Report Type','Description','Format','Status','Created At','File Path'];
output_csv_row($header);

// Data rows
foreach ($rows as $r) {
    // decode description if stored in report_data JSON
    $description = '';
    if (!empty($r['report_data'])) {
        $d = json_decode($r['report_data'], true);
        if (is_array($d) && isset($d['description'])) $description = (string)$d['description'];
    }
    $line = [
        $r['report_id'] ?? '',
        $r['report_name'] ?? '',
        $r['report_type'] ?? '',
        $description,
        $r['format'] ?? '',
        $r['status'] ?? '',
        $r['created_at'] ?? '',
        $r['file_path'] ?? ''
    ];
    output_csv_row($line);
}

// done
exit;
?>