<?php
require_once 'includes/config.php';

// Require janitor
if (!isLoggedIn()) {
    header('Location: user-login.php');
    exit;
}
if (!isJanitor()) {
    header('Location: admin-dashboard.php');
    exit;
}

$janitorId = intval($_SESSION['janitor_id'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Task History - Janitor</title>
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/janitor-dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .table-responsive { overflow: visible; }
    .page-actions { display:flex; gap:.5rem; align-items:center; }
  </style>
</head>
<body>  
<?php include_once __DIR__ . '/includes/header-admin.php'; ?>

<main class="content container py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h1 class="page-title">Task History</h1>
      <p class="page-subtitle">View your completed and ongoing tasks.</p>
    </div>
    <div class="page-actions">
      <button id="refreshHistoryBtn" class="btn btn-outline-secondary"><i class="fas fa-sync-alt me-1"></i> Refresh</button>
      <button id="downloadCsvBtn" class="btn btn-sm btn-primary"><i class="fas fa-download me-1"></i>Download CSV</button>
    </div>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table mb-0">
          <thead>
            <tr>
              <th>Date & Time</th>
              <th>Bin</th>
              <th class="d-none d-md-table-cell">Location</th>
              <th>Action</th>
              <th>Status</th>
              <th class="text-end">Details</th>
            </tr>
          </thead>
          <tbody id="taskHistoryBody">
            <tr><td colspan="6" class="text-center py-4 text-muted">Loading...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<?php include_once __DIR__ . '/includes/footer-admin.php'; ?>

<script>
(function(){
  const JANITOR_ID = <?php echo $janitorId; ?>;

  function escapeHtml(s) {
    if (s === null || s === undefined) return '';
    return String(s)
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;')
      .replaceAll("'",'&#039;');
  }

  // load assigned bins (so Task History mirrors Assigned Bins)
  async function loadTaskHistory() {
    const tbody = document.getElementById('taskHistoryBody');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">Loading...</td></tr>';
    try {
      const resp = await fetch('janitor-dashboard.php?action=get_dashboard_stats', { credentials: 'same-origin' });
      if (!resp.ok) throw new Error('Network error');
      const json = await resp.json();
      tbody.innerHTML = '';
      const bins = (json && json.bins) ? json.bins : [];
      if (!Array.isArray(bins) || bins.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No assigned bins found</td></tr>';
        return;
      }
      bins.forEach(b => {
        const dt = escapeHtml(b.last_emptied || b.updated_at || b.created_at || 'N/A');
        const binLabel = escapeHtml(b.bin_code || ('Bin #' + (b.bin_id || 'N/A')));
        const loc = escapeHtml(b.location || '');
        const actionLabel = 'Assigned';
        const status = escapeHtml(b.status || '-');
        tbody.insertAdjacentHTML('beforeend', `
          <tr data-bin-id="${parseInt(b.bin_id||0,10)}">
            <td>${dt}</td>
            <td><strong>${binLabel}</strong></td>
            <td class="d-none d-md-table-cell">${loc}</td>
            <td>${actionLabel}</td>
            <td>${status}</td>
            <td class="text-end">
              <button class="btn btn-sm btn-danger delete-history-btn" data-bin-id="${parseInt(b.bin_id||0,10)}">Delete</button>
            </td>
          </tr>
        `);
      });
    } catch (err) {
      console.warn('Failed to load assigned bins', err);
      document.getElementById('taskHistoryBody').innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">Failed to load data</td></tr>';
    }
  }

  // Delete history handler (only action allowed)
  document.addEventListener('click', function(e) {
    const btn = e.target.closest && e.target.closest('.delete-history-btn');
    if (!btn) return;
    e.preventDefault();
    const binId = parseInt(btn.getAttribute('data-bin-id') || 0, 10);
    if (!binId) return;
    if (!confirm('Delete task history for this bin? This will remove your history entries for the bin.')) return;

    btn.disabled = true;
    btn.textContent = 'Deleting...';

    const payload = new URLSearchParams();
    payload.append('action', 'delete_task_history');
    payload.append('bin_id', binId);

    fetch('janitor-dashboard.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: payload.toString()
    }).then(r => r.json()).then(data => {
      if (data && data.success) {
        // remove row from table to avoid flooding
        const row = btn.closest('tr');
        if (row) row.remove();
        alert(data.message || 'History cleared');
      } else {
        btn.disabled = false;
        btn.textContent = 'Delete';
        alert((data && data.message) ? data.message : 'Failed to delete history');
      }
    }).catch(err => {
      console.warn('Delete error', err);
      btn.disabled = false;
      btn.textContent = 'Delete';
      alert('Server error while deleting history');
    });
  });

  // CSV download unchanged
  function downloadCsv() {
    const rows = Array.from(document.querySelectorAll('#taskHistoryBody tr'))
      .filter(r => r.querySelectorAll('td').length)
      .map(r => Array.from(r.querySelectorAll('td')).map(td => td.textContent.trim()));
    if (!rows.length) { alert('No data to download'); return; }
    const csv = rows.map(r => r.map(c => `"${(c||'').replace(/"/g,'""')}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'task-history-<?php echo date('Ymd'); ?>.csv';
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
  }

  document.addEventListener('DOMContentLoaded', function(){
    document.getElementById('refreshHistoryBtn').addEventListener('click', function(e){
      e.preventDefault();
      loadTaskHistory();
    });
    document.getElementById('downloadCsvBtn').addEventListener('click', function(e){
      e.preventDefault();
      downloadCsv();
    });
    // initial load
    loadTaskHistory();
  });
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
