<?php
require_once 'includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: admin-login.php');
    exit;
}

/**
 * Configuration
 */
$FILL_THRESHOLD = 90; // fallback threshold for fill_level checks
$DEBUG = false;       // set to true for lightweight diagnostics (dev only)

/**
 * Helper: run a COUNT query safely and return integer result (or null on error)
 */
function runCountQuery(PDO $pdo, string $sql, array $params = []): ?int {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) return 0;
        $first = array_values($row)[0] ?? 0;
        return (int)$first;
    } catch (Exception $e) {
        error_log("[reports.php] Query failed: " . $e->getMessage() . " -- SQL: " . $sql);
        return null;
    }
}

// ----------------- AJAX: create_report (synchronous generation example) -----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_report') {
    header('Content-Type: application/json; charset=utf-8');

    // Map incoming form fields to your DB schema
    $report_name = trim($_POST['name'] ?? '');
    $report_type = trim($_POST['type'] ?? '');
    $date_from   = trim($_POST['from_date'] ?? '') ?: null; // expected format YYYY-MM-DD
    $date_to     = trim($_POST['to_date'] ?? '') ?: null;
    $description = trim($_POST['description'] ?? '') ?: null;
    $format      = trim($_POST['format'] ?? 'pdf'); // pdf|excel|csv
    $generated_by = getCurrentUserId() ?: null; // MUST be existing admins.admin_id
    $initial_status = 'generating'; // temporary status until we set completed/failed
    $file_path    = null; // will be set after generation

    if ($report_name === '' || $report_type === '') {
        echo json_encode(['success' => false, 'message' => 'Report name and type are required.']);
        exit;
    }

    // Build report_data JSON (store description & any metadata)
    $report_data = [
        'description' => $description !== '' ? $description : null,
        'requested_by_ip' => $_SERVER['REMOTE_ADDR'] ?? null
    ];
    $report_data_json = json_encode($report_data);

    try {
        $pdo->beginTransaction();

        // Insert initial row (status = generating)
        $sql = "
            INSERT INTO reports
                (report_name, report_type, generated_by, date_from, date_to, report_data, format, status, file_path, created_at)
            VALUES
                (:report_name, :report_type, :generated_by, :date_from, :date_to, :report_data, :format, :status, :file_path, NOW())
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':report_name'  => $report_name,
            ':report_type'  => $report_type,
            ':generated_by' => $generated_by,
            ':date_from'    => $date_from,
            ':date_to'      => $date_to,
            ':report_data'  => $report_data_json,
            ':format'       => in_array($format, ['pdf','excel','csv']) ? $format : 'pdf',
            ':status'       => $initial_status,
            ':file_path'    => $file_path
        ]);

        $reportId = (int)$pdo->lastInsertId();

        // Synchronous simple generation (example): create CSV for csv/excel, fallback to txt for pdf
        // In production replace with real generator (PDF library, PhpSpreadsheet, etc.)
        $generatedDirRel = 'generated/reports';
        $generatedDirAbs = __DIR__ . '/' . $generatedDirRel;
        if (!is_dir($generatedDirAbs)) {
            mkdir($generatedDirAbs, 0755, true);
        }

        // File extension mapping
        $ext = 'csv';
        if ($format === 'pdf') $ext = 'pdf'; // here we will put text contents with .pdf extension (replace with real pdf gen)
        if ($format === 'excel') $ext = 'csv'; // simple excel-compatible CSV; replace with xlsx generator if needed
        $filename = "report_{$reportId}." . $ext;
        $absPath = $generatedDirAbs . '/' . $filename;
        $relPath = $generatedDirRel . '/' . $filename; // stored in DB

        // Example content (header + metadata). Replace with proper report content.
        $contentLines = [];
        $contentLines[] = "Report ID,Report Name,Report Type,Requested By,Date From,Date To,Created At";
        $contentLines[] = "{$reportId},\"{$report_name}\",{$report_type},{$generated_by}," . ($date_from ?: '-') . "," . ($date_to ?: '-') . "," . date('Y-m-d H:i:s');
        if ($description) {
            $contentLines[] = "";
            $contentLines[] = "Description:";
            $contentLines[] = str_replace(["\r\n","\n","\r"], ' ', $description);
        }
        $fileContent = implode("\n", $contentLines);

        // Write file
        $written = file_put_contents($absPath, $fileContent);
        if ($written === false) {
            // generation failed
            // update status to failed
            $upd = $pdo->prepare("UPDATE reports SET status = 'failed' WHERE report_id = ?");
            $upd->execute([$reportId]);
            $pdo->commit(); // commit the status change
            echo json_encode(['success' => false, 'message' => 'Failed to generate report file.']);
            exit;
        }

        // update DB row with completed status and file_path
        $upd = $pdo->prepare("UPDATE reports SET status = 'completed', file_path = :file_path WHERE report_id = :id");
        $upd->execute([':file_path' => $relPath, ':id' => $reportId]);

        // fetch updated row
        $select = $pdo->prepare("
            SELECT report_id, report_name, report_type, generated_by, date_from, date_to, report_data, format, status, file_path, created_at
            FROM reports
            WHERE report_id = ?
            LIMIT 1
        ");
        $select->execute([$reportId]);
        $row = $select->fetch(PDO::FETCH_ASSOC);

        $pdo->commit();

        // prepare client-friendly row for JS UI compatibility
        $row['report_data'] = $row['report_data'] ? json_decode($row['report_data'], true) : null;
        $clientRow = [
            'report_id'  => $row['report_id'],
            'name'       => $row['report_name'],
            'type'       => $row['report_type'],
            'description'=> $row['report_data']['description'] ?? null,
            'created_at' => $row['created_at'],
            'status'     => $row['status'],
            'file_path'  => $row['file_path'],
            'raw'        => $row
        ];

        echo json_encode(['success' => true, 'report' => $clientRow]);
        exit;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("[reports.php] create_report PDOException: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to create report. Check server logs.', 'error' => $e->getMessage()]);
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("[reports.php] create_report Exception: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to create report.']);
        exit;
    }
}
// ----------------- end create_report handler -----------------

// ---------------------------
// Real-time stats from DB (server-side initial values)
// ---------------------------
$collectionsThisMonth = 0;
$pendingCount = 0;       // full bins (bins.status = 'full')
$completedThisMonth = 0; // bins updated to empty this month OR collections completed
$reportsCount = 0;

// compute current month boundaries (MySQL DATETIME)
$monthStart = date('Y-m-01 00:00:00');
$monthEnd   = date('Y-m-t 23:59:59');

// 1) Collections this month - try created_at then collection_date
$sql = "SELECT COUNT(*) AS cnt FROM collections WHERE created_at BETWEEN :start AND :end";
$res = runCountQuery($pdo, $sql, [':start' => $monthStart, ':end' => $monthEnd]);
if ($res === null) {
    $sql2 = "SELECT COUNT(*) AS cnt FROM collections WHERE collection_date BETWEEN :start AND :end";
    $res = runCountQuery($pdo, $sql2, [':start' => $monthStart, ':end' => $monthEnd]);
}
$collectionsThisMonth = $res ?? 0;

// 2) Pending = number of FULL bins from bins table (strictly by status = 'full')
$sqlPending = "SELECT COUNT(*) AS cnt FROM bins WHERE status = 'full'";
$res = runCountQuery($pdo, $sqlPending);
if ($res === null) {
    // fallback: try using fill_level or level_percent if status not present
    $sqlPending2 = "SELECT COUNT(*) AS cnt FROM bins WHERE status = 'full' OR (fill_level IS NOT NULL AND fill_level >= :threshold)";
    $res = runCountQuery($pdo, $sqlPending2, [':threshold' => $FILL_THRESHOLD]);
}
$pendingCount = $res ?? 0;

// 3) Completed this month - try bins changed to empty within month, else fallback to collections.status = 'completed'
$sqlCompletedBins = "SELECT COUNT(*) AS cnt FROM bins WHERE status = 'empty' AND (status_updated_at BETWEEN :start AND :end OR updated_at BETWEEN :start AND :end)";
$res = runCountQuery($pdo, $sqlCompletedBins, [':start' => $monthStart, ':end' => $monthEnd]);
if ($res === null) {
    $sqlCompletedBins2 = "SELECT COUNT(*) AS cnt FROM bins WHERE status = 'empty' AND updated_at BETWEEN :start AND :end";
    $res = runCountQuery($pdo, $sqlCompletedBins2, [':start' => $monthStart, ':end' => $monthEnd]);
}
if ($res === null) {
    $sqlCompletedCollections = "SELECT COUNT(*) AS cnt FROM collections WHERE status = 'completed' AND created_at BETWEEN :start AND :end";
    $res = runCountQuery($pdo, $sqlCompletedCollections, [':start' => $monthStart, ':end' => $monthEnd]);
}
$completedThisMonth = $res ?? 0;

// 4) Reports total (simple count)
$sqlReports = "SELECT COUNT(*) AS cnt FROM reports";
$res = runCountQuery($pdo, $sqlReports);
$reportsCount = $res ?? 0;

// Fetch recent reports (server-side rendering fallback)
// Select using actual schema, then map to keys expected by the front end
$reports = [];
try {
    $stmt = $pdo->query("
        SELECT
            report_id,
            report_name,
            report_type,
            report_data,
            format,
            status,
            created_at
        FROM reports
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $data = $r['report_data'] ? json_decode($r['report_data'], true) : null;
        $reports[] = [
            'report_id'  => $r['report_id'],
            'name'       => $r['report_name'] ?? '',
            'type'       => $r['report_type'] ?? '',
            'description'=> $data['description'] ?? null,
            'format'     => $r['format'] ?? null,
            'status'     => $r['status'] ?? null,
            'created_at' => $r['created_at'] ?? null,
            'raw'        => $r
        ];
    }
} catch (Exception $e) {
    error_log("[reports.php] Failed to load reports: " . $e->getMessage());
    $reports = [];
}

// DEBUG lightweight output to page if enabled (safe for local/dev only)
if ($DEBUG) {
    echo "<!-- DEBUG: monthStart={$monthStart}, monthEnd={$monthEnd}, collectionsThisMonth={$collectionsThisMonth}, pending={$pendingCount}, completedThisMonth={$completedThisMonth}, reports={$reportsCount} -->\n";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reports & Analytics - Trashbin Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/janitor-dashboard.css">
</head>
<body>
  <div id="scrollProgress" class="scroll-progress"></div>
  <?php include_once __DIR__ . '/includes/header-admin.php'; ?>

  <div class="dashboard">
    <div class="background-circle background-circle-1"></div>
    <div class="background-circle background-circle-2"></div>
    <div class="background-circle background-circle-3"></div>

    <aside class="sidebar" id="sidebar">
      <div class="sidebar-header d-none d-md-block">
        <h6 class="sidebar-title">Menu</h6>
      </div>
      <a href="admin-dashboard.php" class="sidebar-item active">
        <i class="fa-solid fa-chart-pie"></i><span>Dashboard</span>
      </a>
      <a href="bins.php" class="sidebar-item">
        <i class="fa-solid fa-trash-alt"></i><span>Trashbins</span>
      </a>
      <a href="janitors.php" class="sidebar-item">
        <i class="fa-solid fa-users"></i><span>Maintenance Staff</span>
      </a>
      <a href="reports.php" class="sidebar-item">
        <i class="fa-solid fa-chart-line"></i><span>Reports</span>
      </a>
      <a href="notifications.php" class="sidebar-item">
        <i class="fa-solid fa-bell"></i><span>Notifications</span>
      </a>
      <a href="#" class="sidebar-item">
        <i class="fa-solid fa-gear"></i><span>Settings</span>
      </a>
      <a href="profile.php" class="sidebar-item">
        <i class="fa-solid fa-user"></i><span>My Profile</span>
      </a>
    </aside>

    <main class="content">
      <div class="section-header flex-column flex-md-row">
        <div>
          <h1 class="page-title">Reports & Analytics</h1>
          <p class="page-subtitle">View system reports and analytics</p>
        </div>
        <div class="d-flex gap-2 flex-column flex-md-row mt-3 mt-md-0">
          <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#createReportModal">
            <i class="fas fa-plus me-1"></i>Create Report
          </button>
          <button id="btnExport" class="btn btn-primary" onclick="exportReport()">
            <i class="fas fa-download me-1"></i>Export
          </button>
        </div>
      </div>

      <!-- Report Stats -->
      <div class="row g-3 g-md-4 mb-4 mb-md-5">
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-trash-can"></i></div>
            <div class="stat-content">
              <h6>Collections</h6>
              <h2 id="stat-collections"><?php echo htmlspecialchars($collectionsThisMonth, ENT_QUOTES, 'UTF-8'); ?></h2>
              <small>This month</small>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-icon warning"><i class="fas fa-clock"></i></div>
            <div class="stat-content">
              <h6>Pending</h6>
              <h2 id="stat-pending"><?php echo htmlspecialchars($pendingCount, ENT_QUOTES, 'UTF-8'); ?></h2>
              <small>Need action</small>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
            <div class="stat-content">
              <h6>Completed</h6>
              <h2 id="stat-completed"><?php echo htmlspecialchars($completedThisMonth, ENT_QUOTES, 'UTF-8'); ?></h2>
              <small>This month</small>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar"></i></div>
            <div class="stat-content">
              <h6>Reports</h6>
              <h2 id="stat-reports"><?php echo htmlspecialchars($reportsCount, ENT_QUOTES, 'UTF-8'); ?></h2>
              <small>Generated</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Reports -->
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Recent Reports</h5>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table mb-0">
              <thead>
                <tr>
                  <th>Report Name</th>
                  <th class="d-none d-md-table-cell">Description</th>
                  <th class="d-none d-md-table-cell">Type</th>
                  <th class="d-none d-lg-table-cell">Date Created</th>
                  <th>Status</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody id="reportsTableBody">
                <?php if (empty($reports)): ?>
                  <tr>
                    <td colspan="6" class="text-center py-4 text-muted">No reports found</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($reports as $r): ?>
                    <?php
                      $id = (int)($r['report_id'] ?? 0);
                      $name = htmlspecialchars($r['name'] ?? 'Unnamed Report', ENT_QUOTES, 'UTF-8');
                      $description = htmlspecialchars($r['description'] ?? '', ENT_QUOTES, 'UTF-8');
                      $type = htmlspecialchars($r['type'] ?? '', ENT_QUOTES, 'UTF-8');
                      $created = $r['created_at'] ?? null;
                      $dateFormatted = $created ? date('M d, Y H:i', strtotime($created)) : '-';
                      $status = htmlspecialchars($r['status'] ?? 'unknown', ENT_QUOTES, 'UTF-8');
                      $statusClass = 'badge bg-secondary';
                      if (strtolower($status) === 'completed') $statusClass = 'badge bg-success';
                      if (strtolower($status) === 'generating' || strtolower($status) === 'pending') $statusClass = 'badge bg-warning text-dark';
                      if (strtolower($status) === 'failed') $statusClass = 'badge bg-danger';
                    ?>
                    <tr>
                      <td><?php echo $name; ?></td>
                      <td class="d-none d-md-table-cell"><?php echo $description ?: '-'; ?></td>
                      <td class="d-none d-md-table-cell"><?php echo ucfirst($type); ?></td>
                      <td class="d-none d-lg-table-cell"><?php echo $dateFormatted; ?></td>
                      <td><span class="<?php echo $statusClass; ?>"><?php echo ucfirst($status); ?></span></td>
                      <td class="text-end">
                        <div class="btn-group" role="group" aria-label="Actions">
                          <a href="view-report.php?id=<?php echo $id; ?>" class="btn btn-sm btn-outline-primary">View</a>
                          <a href="download-report.php?id=<?php echo $id; ?>" class="btn btn-sm btn-outline-secondary">Download</a>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
  </div>

  <!-- Create Report Modal -->
  <div class="modal fade" id="createReportModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Create New Report</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="createReportForm">
            <div class="mb-3">
              <label class="form-label">Report Name</label>
              <input type="text" class="form-control" id="reportName" placeholder="Enter report name" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Report Type</label>
              <select class="form-select" id="reportType" required>
                <option value="">Select type</option>
                <option value="collections">Collections Report</option>
                <option value="performance">Janitor Performance</option>
                <option value="status">Bin Status Report</option>
                <option value="revenue">Revenue Report</option>
                <option value="custom">Custom Report</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Description</label>
              <textarea class="form-control" id="reportDescription" rows="3" placeholder="Optional description"></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label">From Date</label>
              <input type="date" class="form-control" id="reportFromDate">
            </div>
            <div class="mb-3">
              <label class="form-label">To Date</label>
              <input type="date" class="form-control" id="reportToDate">
            </div>

            <div class="mb-3">
              <label class="form-label">Format</label>
              <select class="form-select" id="reportFormat">
                <option value="pdf">PDF</option>
                <option value="excel">Excel</option>
                <option value="csv">CSV</option>
              </select>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="generateReport()">Generate Report</button>
        </div>
      </div>
    </div>
  </div>

  <?php include_once __DIR__ . '/includes/footer-admin.php'; ?>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="js/bootstrap.bundle.min.js"></script>
  <script src="js/database.js"></script>
  <script src="js/dashboard.js"></script>
  <script src="js/reports.js"></script>

  <script>
    function createReportCardHtml(report) {
      const createdAt = report.created_at ? new Date(report.created_at).toLocaleString() : '';
      const status = (report.status || 'generating').toLowerCase();
      let statusClass = 'badge bg-secondary';
      if (status === 'completed') statusClass = 'badge bg-success';
      if (status === 'generating' || status === 'pending') statusClass = 'badge bg-warning text-dark';
      if (status === 'failed') statusClass = 'badge bg-danger';
      const desc = report.description ? `<div class="small text-muted mt-1">${escapeHtml(report.description.length > 120 ? report.description.substring(0,120) + '...' : report.description)}</div>` : '';

      return `
        <div class="col-12 col-md-4" id="report-card-${report.report_id}">
          <div class="card h-100">
            <div class="card-body d-flex flex-column">
              <h5 class="card-title mb-2">${escapeHtml(report.name)}</h5>
              <p class="mb-1"><strong>Type:</strong> ${escapeHtml(report.type)}</p>
              <p class="text-muted small mb-2">${escapeHtml(createdAt)}</p>
              ${desc}
              <div class="mt-auto d-flex justify-content-between align-items-center">
                <span class="${statusClass}">${escapeHtml(report.status || 'generating')}</span>
                <div>
                  <a href="view-report.php?id=${report.report_id}" class="btn btn-sm btn-outline-primary">View</a>
                  <a href="download-report.php?id=${report.report_id}" class="btn btn-sm btn-outline-secondary">Download</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      `;
    }

    function escapeHtml(s) {
      if (!s) return '';
      return s.replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'", '&#039;');
    }

    function generateReport() {
      const nameEl = document.getElementById('reportName');
      const typeEl = document.getElementById('reportType');
      const fromEl = document.getElementById('reportFromDate');
      const toEl = document.getElementById('reportToDate');
      const descEl = document.getElementById('reportDescription');
      const formatEl = document.getElementById('reportFormat');

      const name = nameEl ? nameEl.value.trim() : '';
      const type = typeEl ? typeEl.value : '';
      const fromDate = fromEl ? fromEl.value : '';
      const toDate = toEl ? toEl.value : '';
      const description = descEl ? descEl.value.trim() : '';
      const format = formatEl ? formatEl.value : 'pdf';

      if (!name || !type) {
        alert('Please enter report name and select type.');
        return;
      }

      const formData = new FormData();
      formData.append('action', 'create_report');
      formData.append('name', name);
      formData.append('type', type);
      if (fromDate) formData.append('from_date', fromDate);
      if (toDate) formData.append('to_date', toDate);
      if (description) formData.append('description', description);
      if (format) formData.append('format', format);

      const modalEl = document.getElementById('createReportModal');
      const btn = modalEl ? modalEl.querySelector('.btn-primary') : null;
      if (btn) { btn.disabled = true; btn.dataset.orig = btn.innerHTML; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Creating...'; }

      fetch('reports.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      })
      .then(r => {
        if (!r.ok) throw new Error('Network response was not ok');
        return r.json();
      })
      .then(data => {
        if (!data || !data.success) {
          alert((data && data.message) ? data.message : 'Failed to create report');
          console.error(data && data.error ? data.error : null);
          return;
        }
        const report = data.report;

        const container = document.getElementById('createdReportsContainer');
        if (container) {
          container.insertAdjacentHTML('afterbegin', createReportCardHtml(report));
        }
        const statReports = document.getElementById('stat-reports');
        if (statReports) statReports.textContent = (parseInt(statReports.textContent||'0',10) + 1).toString();

        const tbody = document.getElementById('reportsTableBody');
        if (tbody) {
          const tr = document.createElement('tr');
          const createdFmt = report.created_at ? new Date(report.created_at).toLocaleString() : '-';
          tr.innerHTML = `
            <td>${escapeHtml(report.name)}</td>
            <td class="d-none d-md-table-cell">${escapeHtml(report.description || '-')}</td>
            <td class="d-none d-md-table-cell">${escapeHtml(report.type)}</td>
            <td class="d-none d-lg-table-cell">${escapeHtml(createdFmt)}</td>
            <td><span class="badge bg-warning text-dark">${escapeHtml(report.status)}</span></td>
            <td class="text-end">
              <div class="btn-group" role="group" aria-label="Actions">
                <a href="view-report.php?id=${report.report_id}" class="btn btn-sm btn-outline-primary">View</a>
                <a href="download-report.php?id=${report.report_id}" class="btn btn-sm btn-outline-secondary">Download</a>
              </div>
            </td>
          `;
          if (tbody.firstElementChild && tbody.firstElementChild.querySelector('.text-center')) {
            tbody.innerHTML = '';
          }
          tbody.prepend(tr);
        }

        if (document.getElementById('createReportForm')) document.getElementById('createReportForm').reset();

        try {
          const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
          modalInstance.hide();
        } catch(e) {
          if (modalEl) modalEl.classList.remove('show');
        }
      })
      .catch(err => {
        console.error('create report error', err);
        alert('Failed to create report');
      })
      .finally(() => {
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = btn.dataset.orig || 'Generate Report';
        }
      });
    }

    function exportReport() {
      const type = document.getElementById('reportType') ? document.getElementById('reportType').value : '';
      const fromDate = document.getElementById('reportFromDate') ? document.getElementById('reportFromDate').value : '';
      const toDate = document.getElementById('reportToDate') ? document.getElementById('reportToDate').value : '';

      const form = document.createElement('form');
      form.method = 'POST';
      form.action = 'export_reports.php';
      form.style.display = 'none';

      if (type) {
        const inputType = document.createElement('input');
        inputType.type = 'hidden';
        inputType.name = 'type';
        inputType.value = type;
        form.appendChild(inputType);
      }

      if (fromDate) {
        const inputFrom = document.createElement('input');
        inputFrom.type = 'hidden';
        inputFrom.name = 'from_date';
        inputFrom.value = fromDate;
        form.appendChild(inputFrom);
      }

      if (toDate) {
        const inputTo = document.createElement('input');
        inputTo.type = 'hidden';
        inputTo.name = 'to_date';
        inputTo.value = toDate;
        form.appendChild(inputTo);
      }

      document.body.appendChild(form);

      const btn = document.getElementById('btnExport');
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Preparing...';
      }

      form.submit();

      setTimeout(function() {
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = '<i class="fas fa-download me-1"></i>Export';
        }
        form.remove();
      }, 1500);
    }
  </script>

  <script src="js/janitor-dashboard.js"></script>
  <script src="js/scroll-progress.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      try {
        const notifBtn = document.getElementById('notificationsBtn');
        if (notifBtn) notifBtn.addEventListener('click', function(e){ e.preventDefault(); if (typeof openNotificationsModal === 'function') openNotificationsModal(e); else if (typeof showModalById === 'function') showModalById('notificationsModal'); });
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) logoutBtn.addEventListener('click', function(e){ e.preventDefault(); if (typeof showLogoutModal === 'function') showLogoutModal(e); else if (typeof showModalById === 'function') showModalById('logoutModal'); else window.location.href='logout.php'; });
      } catch(err) { console.warn('Header fallback handlers error', err); }
    });
  </script>
</body>
</html> 