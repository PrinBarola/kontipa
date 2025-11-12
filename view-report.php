<?php
require_once 'includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: admin-login.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo "Invalid report id.";
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT report_id, report_name, report_type, generated_by, date_from, date_to, report_data, format, status, file_path, created_at FROM reports WHERE report_id = ? LIMIT 1");
    $stmt->execute([$id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("[view-report.php] DB error: " . $e->getMessage());
    $report = false;
}

if (!$report) {
    http_response_code(404);
    echo "Report not found.";
    exit;
}

// decode metadata
$report_data = $report['report_data'] ? json_decode($report['report_data'], true) : null;
$description = $report_data['description'] ?? null;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>View Report - <?php echo htmlspecialchars($report['report_name']); ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/janitor-dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <?php include_once __DIR__ . '/includes/header-admin.php'; ?>

  <main class="content container py-4">
    <div class="d-flex align-items-center mb-3">
      <a href="reports.php" class="btn btn-outline-secondary me-2"><i class="fa fa-arrow-left"></i> Back</a>
      <h1 class="h4 mb-0">Report Details</h1>
    </div>

    <div class="card mb-4">
      <div class="card-body">
        <h3 class="card-title"><?php echo htmlspecialchars($report['report_name']); ?></h3>
        <p class="text-muted mb-2"><strong>Type:</strong> <?php echo htmlspecialchars($report['report_type']); ?></p>
        <p class="text-muted mb-2"><strong>Format:</strong> <?php echo htmlspecialchars($report['format'] ?? 'pdf'); ?></p>
        <p class="text-muted mb-2"><strong>Status:</strong> <?php echo htmlspecialchars($report['status']); ?></p>
        <p class="text-muted mb-2"><strong>Requested:</strong> <?php echo htmlspecialchars($report['created_at']); ?></p>
        <?php if ($report['date_from'] || $report['date_to']): ?>
          <p class="text-muted mb-2"><strong>Date Range:</strong>
            <?php echo $report['date_from'] ? htmlspecialchars($report['date_from']) : '-'; ?>
            —
            <?php echo $report['date_to'] ? htmlspecialchars($report['date_to']) : '-'; ?>
          </p>
        <?php endif; ?>

        <?php if ($description): ?>
          <div class="mb-3">
            <h6>Description</h6>
            <p class="small text-muted"><?php echo nl2br(htmlspecialchars($description)); ?></p>
          </div>
        <?php endif; ?>

        <?php if ($report['file_path'] && file_exists(__DIR__ . '/' . $report['file_path'])): ?>
          <a href="download-report.php?id=<?php echo $report['report_id']; ?>" class="btn btn-primary">
            <i class="fa fa-download me-1"></i> Download Report
          </a>
        <?php else: ?>
          <div class="alert alert-info mb-0">
            Report file not yet generated or missing. Status: <?php echo htmlspecialchars($report['status']); ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <?php include_once __DIR__ . '/includes/footer-admin.php'; ?>
  <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>