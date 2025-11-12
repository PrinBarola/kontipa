<?php
require_once 'includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: admin-login.php');
    exit;
}

// Check if user is admin
if (!isAdmin()) {
    header('Location: janitor-dashboard.php');
    exit;
}

// ----------------- ADDED: AJAX endpoint to return dashboard stats -----------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_dashboard_stats') {
    // Return bins (same ordering as Bin Management) and active janitors count
    $dashboard_bins = [];
    $bins_query = "SELECT bins.*, CONCAT(j.first_name, ' ', j.last_name) AS janitor_name
                   FROM bins
                   LEFT JOIN janitors j ON bins.assigned_to = j.janitor_id
                   ORDER BY
                     CASE WHEN (bins.status = 'full' OR (bins.capacity IS NOT NULL AND bins.capacity >= 100)) THEN 0 ELSE 1 END,
                     bins.capacity DESC,
                     bins.created_at DESC
                   LIMIT 1000";
    $bins_res = $conn->query($bins_query);
    if ($bins_res) {
        while ($r = $bins_res->fetch_assoc()) $dashboard_bins[] = $r;
    }

    $activeJanitors = 0;
    $r = $conn->query("SELECT COUNT(*) AS c FROM janitors WHERE status = 'active'");
    if ($r && $row = $r->fetch_assoc()) $activeJanitors = intval($row['c'] ?? 0);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'bins' => $dashboard_bins,
        'totalBins' => count($dashboard_bins),
        'fullBins' => count(array_filter($dashboard_bins, function($b){ return ($b['status'] === 'full' || (isset($b['capacity']) && intval($b['capacity']) >= 100)); })),
        'activeJanitors' => $activeJanitors
    ]);
    exit;
}
// ----------------- end AJAX endpoint -----------------

// ---------------------- ADDED: fetch initial stats & bins ----------------------
$totalBins = 0;
$fullBins = 0;
$dashboard_bins = [];
$activeJanitors = 0;

// safe best-effort queries
try {
    $res = $conn->query("SELECT COUNT(*) AS c FROM bins");
    if ($res) {
        $row = $res->fetch_assoc();
        $totalBins = intval($row['c'] ?? 0);
    }

    $res = $conn->query("SELECT COUNT(*) AS c FROM bins WHERE status = 'full' OR (capacity IS NOT NULL AND capacity >= 100)");
    if ($res) {
        $row = $res->fetch_assoc();
        $fullBins = intval($row['c'] ?? 0);
    }

    $r = $conn->query("SELECT COUNT(*) AS c FROM janitors WHERE status = 'active'");
    if ($r && $row = $r->fetch_assoc()) $activeJanitors = intval($row['c'] ?? 0);

    // Get bins list, put full bins first
    $bins_query = "SELECT bins.*, CONCAT(j.first_name, ' ', j.last_name) AS janitor_name
                   FROM bins
                   LEFT JOIN janitors j ON bins.assigned_to = j.janitor_id
                   ORDER BY
                     CASE WHEN (bins.status = 'full' OR (bins.capacity IS NOT NULL AND bins.capacity >= 100)) THEN 0 ELSE 1 END,
                     bins.capacity DESC,
                     bins.created_at DESC
                   LIMIT 200";
    $bins_res = $conn->query($bins_query);
    if ($bins_res) {
        while ($r = $bins_res->fetch_assoc()) $dashboard_bins[] = $r;
    }
} catch (Exception $e) {
    // ignore and keep defaults
}
// ---------------------- end added section ----------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - Trashbin Management</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <!-- Use janitor dashboard styles for admin to unify look -->
  <link rel="stylesheet" href="css/janitor-dashboard.css">
  <style>
    /* Header: ensure notification badge is visible and not clipped */
    .header .header-container .nav-buttons {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }
    .nav-buttons .nav-link {
      position: relative;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      color: inherit;
      text-decoration: none;
    }
    .nav-buttons .notification-badge,
    .nav-buttons #notificationCount {
      position: absolute !important;
      top: -6px !important;
      right: -6px !important;
      z-index: 9999 !important;
      min-width: 20px;
      height: 20px;
      padding: 0 6px;
      font-size: 12px;
      display: inline-flex !important;
      align-items: center;
      justify-content: center;
    }
    /* Make logout visually similar to janitor header */
    .nav-buttons .logout-link {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      padding: 0.35rem 0.6rem;
      border-radius: 6px;
      transition: background 0.12s ease;
    }
    .nav-buttons .logout-link:hover { background: rgba(0,0,0,0.04);} 
    /* Ensure header icons are not clipped by overflow */
    .header { overflow: visible; }
   /* Ensure dropdown menus inside the bins table are not clipped by the card
     and render above the table. This keeps the status-management menu
     visible outside the table area (matches screenshot behavior). */
   .card { overflow: visible; }
   .card .dropdown-menu { position: absolute; z-index: 9999; }
  </style>
</head>
<body>
  <div id="scrollProgress" class="scroll-progress"></div>
  <?php include_once __DIR__ . '/includes/header-admin.php'; ?>

  <div class="dashboard">
    <!-- Animated Background Circles -->
    <div class="background-circle background-circle-1"></div>
    <div class="background-circle background-circle-2"></div>
    <div class="background-circle background-circle-3"></div>

    <!-- Sidebar -->
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

    <!-- Main Content -->
    <main class="content">
        <div class="section-header">
        <div>
          <h1 class="page-title">Dashboard</h1>
          <p class="page-subtitle">Welcome back! Here's your system overview.</p>
        </div>
        <!-- period buttons removed per request -->
      </div>

      <!-- Stats Cards -->
      <div class="row g-3 g-md-4 mb-4 mb-md-5">
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-icon">
              <i class="fa-solid fa-trash-alt"></i>
            </div>
            <div class="stat-content">
              <h6>Total Bins</h6>
              <h2 id="totalBins"><?php echo intval($totalBins); ?></h2>
              <small>Active</small>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-icon warning">
              <i class="fa-solid fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
              <h6>Full Bins</h6>
              <h2 id="fullBins"><?php echo intval($fullBins); ?></h2>
              <small>Needs attention</small>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-icon success">
              <i class="fa-solid fa-users"></i>
            </div>
            <div class="stat-content">
              <h6>Active Janitors</h6>
              <h2 id="activeJanitors"><?php echo intval($activeJanitors); ?></h2>
              <small>On duty</small>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-icon">
              <i class="fa-solid fa-truck"></i>
            </div>
            <div class="stat-content">
              <h6>Collections</h6>
              <h2 id="collectionsToday">0</h2>
              <small>Today</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Bins Overview -->
      <div class="card">
        <div class="card-header">
          <div class="d-flex align-items-center justify-content-between">
            <h5 class="mb-0 text-success"><i class="fas fa-trash-can me-2"></i>Bins Overview</h5>
            <a href="bins.php" class="view-all-link"><span>View All</span></a>
          </div>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table mb-0">
              <thead>
                <tr>
                  <th>Bin ID</th>
                  <th>Location</th>
                  <th>Status</th>
                  <th class="d-none d-md-table-cell">Last Emptied</th>
                  <th class="d-none d-lg-table-cell">Assigned To</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody id="binsTableBody">
                <?php if (empty($dashboard_bins)): ?>
                <tr>
                  <td colspan="6" class="text-center py-4 text-muted">No bins found</td>
                </tr>
                <?php else: ?>
                  <?php foreach ($dashboard_bins as $b): ?>
                    <tr>
                      <td><strong><?php echo htmlspecialchars($b['bin_code'] ?? $b['bin_id']); ?></strong></td>
                      <td><?php echo htmlspecialchars($b['location'] ?? ''); ?></td>
                      <td>
                        <?php
                          $s = $b['status'] ?? '';
                          $display = match($s) {
                            'full' => 'Full',
                            'empty' => 'Empty',
                            'half_full' => 'Half Full',
                            'needs_attention' => 'Needs Attention',
                            'disabled' => 'Disabled',
                            default => $s
                          };
                          // badge class
                          $badge = ($s === 'full') ? 'danger' : (($s === 'empty') ? 'success' : (($s === 'half_full') ? 'warning' : 'secondary'));
                        ?>
                        <span class="badge bg-<?php echo $badge; ?>"><?php echo htmlspecialchars($display); ?></span>
                      </td>
                      <td class="d-none d-md-table-cell"><?php echo htmlspecialchars($b['last_emptied'] ?? $b['updated_at'] ?? 'N/A'); ?></td>
                      <td class="d-none d-lg-table-cell"><?php echo htmlspecialchars($b['janitor_name'] ?? 'Unassigned'); ?></td>
                      <td class="text-end">
                        <button type="button" class="btn btn-sm btn-soft-primary" onclick="openViewDetails(event, <?php echo intval($b['bin_id']); ?>)">View</button>
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

  <!-- Edit Janitor Modal -->
  <div class="modal fade" id="editJanitorModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
      <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Edit Janitor</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="editJanitorForm">
            <input type="hidden" id="editJanitorId">
            <!-- Split full name into first name and last name fields -->
            <div class="form-row">
              <div class="form-group mb-3">
                <label class="form-label">First Name</label>
                <input type="text" class="form-control" id="editJanitorFirstName" required>
              </div>
              <div class="form-group mb-3">
                <label class="form-label">Last Name</label>
                <input type="text" class="form-control" id="editJanitorLastName" required>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="saveJanitorEdit()">
            <i class="fas fa-save me-1"></i>Save Changes
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Create Report Modal -->
  <div class="modal fade" id="createReportModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-file-alt me-2"></i>Create New Report</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
          <form id="createReportForm">
            <div class="form-group mb-3">
              <label class="form-label">Report Name</label>
              <input type="text" class="form-control" id="reportName" placeholder="Enter report name" required>
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Report Type</label>
              <select class="form-control form-select" id="reportType" required>
                <option value="collections">Collections</option>
                <option value="performance">Performance</option>
                <option value="status">Status</option>
              </select>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="createReport()">
            <i class="fas fa-file-alt me-1"></i>Create Report
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Add New Janitor Modal -->
  <div class="modal fade" id="addJanitorModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Janitor</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="addJanitorForm">
            <!-- Split full name into separate first name and last name fields -->
            <div class="form-row">
              <div class="form-group mb-3">
                <label class="form-label">First Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="newJanitorFirstName" placeholder="Enter first name" required>
              </div>
              <div class="form-group mb-3">
                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="newJanitorLastName" placeholder="Enter last name" required>
              </div>
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Email Address <span class="text-danger">*</span></label>
              <input type="email" class="form-control" id="newJanitorEmail" placeholder="Enter email address" required>
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Phone Number <span class="text-danger">*</span></label>
              <input type="tel" class="form-control" id="newJanitorPhone" placeholder="Enter phone number" required>
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Status <span class="text-danger">*</span></label>
              <select class="form-control form-select" id="newJanitorStatus" required>
                <option value="">Select status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Assigned Bins</label>
              <input type="number" class="form-control" id="newJanitorBins" placeholder="Number of bins" value="0" min="0">
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="saveNewJanitor()">
            <i class="fas fa-save me-1"></i>Add Janitor
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Add New Bin Modal -->
  <div class="modal fade" id="addBinModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-trash-can me-2"></i>Add New Bin</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="addBinForm">
            <div class="form-group mb-3">
              <label class="form-label">Bin ID <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="newBinId" placeholder="e.g., BIN-001" required>
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Location <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="newBinLocation" placeholder="Enter bin location" required>
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Type <span class="text-danger">*</span></label>
              <select class="form-control form-select" id="newBinType" required>
                <option value="">Select type</option>
                <option value="General">General</option>
                <option value="Recyclable">Recyclable</option>
                <option value="Organic">Organic</option>
              </select>
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Capacity (%) <span class="text-danger">*</span></label>
              <input type="number" class="form-control" id="newBinCapacity" placeholder="0-100" required>
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Status <span class="text-danger">*</span></label>
              <select class="form-control form-select" id="newBinStatus" required>
                <option value="">Select status</option>
                <option value="empty">Empty</option>
                <option value="full">Full</option>
                <option value="needs_attention">Needs Attention</option>
              </select>
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Assigned To</label>
              <input type="text" class="form-control" id="newBinAssignedTo" placeholder="Janitor name (optional)">
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="saveNewBin()">
            <i class="fas fa-save me-1"></i>Add Bin
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Bin Modal -->
  <div class="modal fade" id="editBinModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Bin</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="editBinForm">
            <input type="hidden" id="editBinId">
            <div class="form-group mb-3">
              <label class="form-label">Bin ID</label>
              <input type="text" class="form-control" id="editBinIdDisplay" readonly style="background-color: #f5f5f5; cursor: not-allowed;">
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Location</label>
              <input type="text" class="form-control" id="editBinLocation" required>
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Type</label>
              <select class="form-control form-select" id="editBinType" required>
                <option value="General">General</option>
                <option value="Recyclable">Recyclable</option>
                <option value="Organic">Organic</option>
              </select>
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Status</label>
              <select class="form-control form-select" id="editBinStatus" required>
                <option value="empty">Empty</option>
                <option value="full">Full</option>
                <option value="needs_attention">Needs Attention</option>
              </select>
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Assigned To</label>
              <input type="text" class="form-control" id="editBinAssignedTo" placeholder="Janitor name">
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="saveBinEdit()">
            <i class="fas fa-save me-1"></i>Save Changes
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- View Report Detail Modal -->
  <div class="modal fade" id="viewReportDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-file-alt me-2"></i>Report Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="reportDetailContent">
          <!-- Content loaded dynamically -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

    <!-- View Bin Details Modal (used by 'View' buttons to load details via AJAX) -->
    <div class="modal fade" id="viewDetailsModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="fas fa-trash-can me-2"></i>Bin Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body" id="viewDetailsContent">
            <!-- Bin details loaded via AJAX -->
            <div class="text-center text-muted py-4">Loading...</div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

  <?php include_once __DIR__ . '/includes/footer-admin.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="js/bootstrap.bundle.min.js"></script>
  <script src="js/database.js"></script>
  <script src="js/dashboard.js"></script>

  <!-- ADDED: client-side refresh to reflect bins.php/admin-dashboard stats -->
  <script>
    // Refresh dashboard counts and bins table by calling this page's AJAX endpoint
    function loadDashboardData() {
      $.get(window.location.pathname, { action: 'get_dashboard_stats' }, function(resp) {
        if (!resp || !resp.success) return;
        const bins = resp.bins || [];
        // update counts from server-calculated values (keeps consistent with Bin Management)
        $('#totalBins').text(resp.totalBins || bins.length);
        $('#fullBins').text(resp.fullBins || bins.filter(b => (b.status === 'full' || (b.capacity && parseInt(b.capacity) >= 100))).length);
        $('#activeJanitors').text(resp.activeJanitors || 0);

        // rebuild table body
        const tbody = $('#binsTableBody');
        tbody.empty();
        if (!bins.length) {
          tbody.html('<tr><td colspan="6" class="text-center py-4 text-muted">No bins found</td></tr>');
          return;
        }
          bins.forEach(b => {
          const statusMap = {
            'full': ['danger', 'Full'],
            'empty': ['success', 'Empty'],
            'half_full': ['warning', 'Half Full'],
            'needs_attention': ['info', 'Needs Attention'],
            'disabled': ['secondary', 'Disabled']
          };
          const meta = statusMap[b.status] || ['secondary', b.status || 'N/A'];
          const lastEmptied = b.last_emptied || b.updated_at || 'N/A';
          const janitor = b.janitor_name || 'Unassigned';
          tbody.append(`
            <tr>
              <td><strong>${b.bin_code || b.bin_id}</strong></td>
              <td>${b.location || ''}</td>
              <td><span class="badge bg-${meta[0]}">${meta[1]}</span></td>
              <td class="d-none d-md-table-cell">${lastEmptied}</td>
              <td class="d-none d-lg-table-cell">${janitor}</td>
              <td class="text-end"><button type="button" class="btn btn-sm btn-soft-primary" onclick="openViewDetails(event, ${b.bin_id})">View</button></td>
            </tr>
          `);
        });
      }, 'json').fail(function() {
        // silent
      });
    }

    document.addEventListener('DOMContentLoaded', function() {
      // initial load already rendered by PHP; ensure periodic refresh and immediate sync
      loadDashboardData();
      setInterval(loadDashboardData, 30000); // refresh every 30s
    });
  </script>
  <script>
    // Open the bin details modal and load content via AJAX (prevents navigating to raw JSON)
    function openViewDetails(e, binId) {
      if (e && e.preventDefault) e.preventDefault();
      const modalEl = document.getElementById('viewDetailsModal');
      const contentEl = document.getElementById('viewDetailsContent');
      if (!modalEl || !contentEl) return;
      contentEl.innerHTML = '<div class="text-center text-muted py-4">Loading...</div>';
      // fetch details endpoint (bins.php?action=get_details&bin_id=...) and handle JSON or HTML
      fetch('bins.php?action=get_details&bin_id=' + encodeURIComponent(binId), { credentials: 'same-origin' })
        .then(response => {
          if (!response.ok) throw new Error('Network response was not ok');
          const ct = response.headers.get('content-type') || '';
          if (ct.indexOf('application/json') !== -1) {
            return response.json().then(data => ({ type: 'json', data }));
          }
          return response.text().then(text => ({ type: 'text', data: text }));
        })
        .then(result => {
          if (result.type === 'json') {
            const data = result.data;
            if (data && data.success && data.html) {
              contentEl.innerHTML = data.html;
            } else if (data && data.success && (data.bin || data.data)) {
              // support both { success: true, bin: {...} } and { success: true, data: {...} }
              const d = data.bin || data.data;
              contentEl.innerHTML = `
                <div class="row mb-3">
                  <div class="col-md-6"><p><strong>Bin Code:</strong><br>${escapeHtml(d.bin_code || d.bin_id || '')}</p></div>
                  <div class="col-md-6"><p><strong>Type:</strong><br>${escapeHtml(d.type || 'N/A')}</p></div>
                </div>
                <div class="row mb-3">
                  <div class="col-md-6"><p><strong>Location:</strong><br>${escapeHtml(d.location || 'N/A')}</p></div>
                  <div class="col-md-6"><p><strong>Assigned To:</strong><br>${escapeHtml(d.janitor_name || 'Unassigned')}</p></div>
                </div>
                <div class="row mb-3">
                  <div class="col-md-6"><p><strong>Status:</strong><br><span class="badge bg-info">${escapeHtml(d.status || 'N/A')}</span></p></div>
                  <div class="col-md-6"><p><strong>Capacity:</strong><br>${escapeHtml((d.capacity !== undefined && d.capacity !== null) ? d.capacity + '%' : 'N/A')}</p></div>
                </div>
                <hr>
                <div class="alert alert-info small"><i class="fas fa-info-circle me-2"></i>Status and capacity are managed by the microcontroller in real-time.</div>
              `;
            } else {
              contentEl.innerHTML = '<div class="text-muted">No details available</div>';
            }
          } else {
            // plain text/HTML
            contentEl.innerHTML = result.data;
          }
        })
        .catch(err => {
          contentEl.innerHTML = '<div class="text-danger">Failed to load details</div>';
          console.warn('openViewDetails error', err);
        })
        .finally(() => {
          try { var b = new bootstrap.Modal(modalEl); b.show(); } catch (e) { /* ignore */ }
        });
    }

    function escapeHtml(str) {
      if (str === null || str === undefined) return '';
      return String(str).replace(/[&"'<>]/g, function (s) {
        return ({'&':'&amp;','"':'&quot;','\'':'&#39;','<':'&lt;','>':'&gt;'})[s];
      });
    }
  </script>
  <!-- Janitor dashboard JS to provide header/footer modal handlers (logout, info modals, etc.) -->
  <script src="js/janitor-dashboard.js"></script>

  <!-- Fallback wiring: ensure notification bell and logout open the janitor modals and do not navigate -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      try {
        const notifBtn = document.getElementById('notificationsBtn');
        if (notifBtn) {
          // prevent accidental navigation and ensure modal opens
          notifBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (typeof openNotificationsModal === 'function') {
              openNotificationsModal(e);
            } else if (typeof showModalById === 'function') {
              showModalById('notificationsModal');
            }
          });
        }

        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
          logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (typeof showLogoutModal === 'function') {
              showLogoutModal(e);
            } else if (typeof showModalById === 'function') {
              showModalById('logoutModal');
            } else {
              // fallback to navigate to logout if no modal available
              window.location.href = 'logout.php';
            }
          });
        }
      } catch (err) {
        // swallow errors - these are non-critical fallback handlers
        console.warn('Admin header fallback handlers error', err);
      }
    });
  </script>
    <script src="js/scroll-progress.js"></script>
    <script src="js/password-toggle.js"></script>
</body>
</html>
