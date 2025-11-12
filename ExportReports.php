<?php
// ExportReports.php
// Exports the "reports" table to an Excel (.xls) file using PhpSpreadsheet.

session_start();

// SECURITY NOTE:
// Uncomment and update this if your system has an admin login session variable.
// if (!isset($_SESSION['admin_logged_in'])) {
//     echo "<p class='error'>You must be logged in as an admin to export.</p>";
//     exit;
// }

require 'vendor/autoload.php';
require 'db_connection.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

// Change this table name if your reports table is named differently.
$tableName = 'reports';

// Handle export request
if (isset($_POST['export'])) {
    try {
        $result = $conn->query("SELECT * FROM `$tableName`");
        if (!$result) {
            throw new Exception("Database query failed: " . $conn->error);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Dynamically write header row using the result field names
        $fields = $result->fetch_fields();
        $col = 1;
        foreach ($fields as $field) {
            $sheet->setCellValueByColumnAndRow($col, 1, $field->name);
            $col++;
        }

        // Write data rows
        $rowCount = 2;
        while ($row = $result->fetch_assoc()) {
            $col = 1;
            foreach ($fields as $field) {
                $sheet->setCellValueByColumnAndRow($col, $rowCount, $row[$field->name]);
                $col++;
            }
            $rowCount++;
        }

        $writer = new Xls($spreadsheet);

        // Set headers for Excel download
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="reports_export_' . date('Y-m-d_H-i-s') . '.xls"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        if (ob_get_length()) {
            ob_end_clean();
        }

        $writer->save('php://output');
        $conn->close();
        exit;
    } catch (Exception $e) {
        error_log($e->getMessage());
        $error_message = "An error occurred while exporting: " . htmlspecialchars($e->getMessage());
    } finally {
        if ($conn) $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Reports to Excel</title>
    <link rel="stylesheet" href="styles14.css">
</head>
<body>
<header>
    <div class="logo">
        <img src="image/bsulogo.png" alt="BSU Logo" class="bsu-logo">
        <img src="image/logo.png" alt="Student Success Hub Logo">
        <span>Student Success Hub</span>
    </div>
    <nav class="nav">
        <a href="HomePageForAdmin.php" class="logout-btn">Home</a>
        <a href="LogOut.php" class="logout-btn">Log Out</a>
    </nav>
</header>

<main>
    <div class="container">
        <h1>Export Reports to Excel</h1>
        <p class="description">This will export current rows from the reports table to an Excel file. Ensure you have permission to export these records.</p>

        <?php
        if (!empty($error_message)) {
            echo "<p class='error'>$error_message</p>";
        }
        ?>

        <form method="post">
            <div class="button-container">
                <a href="HomePageForAdmin.php" class="proceed-btn">GO BACK</a>
                <button type="submit" name="export" class="proceed-btn">Export Reports to Excel</button>
            </div>
        </form>
    </div>
</main>

<footer>
    <p>&copy; 2024 Student Success Hub. All rights reserved.</p>
    <a href="https://www.facebook.com/guidanceandcounselinglipa">Office of Guidance and Counseling - Batstateu Lipa (Ogc Lipa) Facebook Page</a>
    <p>Email: ogc.lipa@g.batstate-u.edu.ph</p>
</footer>
</body>
</html>
