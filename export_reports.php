<?php
// export_reports.php
// Exports rows from the `reports` table to an Excel (.xlsx) file.
// Place this file in the project root next to reports.php and includes/.
// Requires: phpoffice/phpspreadsheet (composer require phpoffice/phpspreadsheet)

declare(strict_types=1);

require_once 'includes/config.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Only allow logged-in admins to export
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    exit('Forbidden');
}

// Read optional filters from POST
$filterType = isset($_POST['type']) && $_POST['type'] !== '' ? trim($_POST['type']) : null;
$filterFrom = isset($_POST['from_date']) && $_POST['from_date'] !== '' ? trim($_POST['from_date']) : null;
$filterTo   = isset($_POST['to_date']) && $_POST['to_date'] !== '' ? trim($_POST['to_date']) : null;

try {
    // Build query with optional filters (prepared statements)
    $sql = "SELECT * FROM `reports` WHERE 1=1";
    $params = [];

    if ($filterType !== null) {
        $sql .= " AND `type` = :type";
        $params[':type'] = $filterType;
    }

    if ($filterFrom !== null) {
        // ensure from date is valid
        $fromDate = date('Y-m-d 00:00:00', strtotime($filterFrom));
        $sql .= " AND `created_at` >= :from";
        $params[':from'] = $fromDate;
    }

    if ($filterTo !== null) {
        // ensure to date is valid
        // include entire day by ending at 23:59:59
        $toDate = date('Y-m-d 23:59:59', strtotime($filterTo));
        $sql .= " AND `created_at` <= :to";
        $params[':to'] = $toDate;
    }

    $sql .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('[export_reports] DB error: ' . $e->getMessage());
    http_response_code(500);
    exit('Database error while preparing export.');
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

if (!empty($rows)) {
    // Header row using keys from first row
    $headers = array_keys($rows[0]);
    foreach ($headers as $i => $h) {
        $sheet->setCellValueByColumnAndRow($i + 1, 1, $h);
    }

    // Data rows
    $rnum = 2;
    foreach ($rows as $row) {
        $cnum = 1;
        foreach ($headers as $h) {
            $val = isset($row[$h]) ? $row[$h] : '';
            // Avoid writing arrays/objects
            if (is_array($val) || is_object($val)) $val = json_encode($val, JSON_UNESCAPED_UNICODE);
            $sheet->setCellValueByColumnAndRow($cnum, $rnum, $val);
            $cnum++;
        }
        $rnum++;
    }
} else {
    // No rows: place a clear message in the sheet
    $sheet->setCellValue('A1', 'No records found for the selected filters.');
}

$writer = new Xlsx($spreadsheet);
$filename = 'reports_export_' . date('Y-m-d_H-i-s') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: public');

// Clean output buffer to avoid corrupting the xlsx file
if (ob_get_length()) {
    ob_end_clean();
}

try {
    $writer->save('php://output');
    exit;
} catch (Exception $e) {
    error_log('[export_reports] Writer error: ' . $e->getMessage());
    http_response_code(500);
    exit('Failed to produce export file.');
}