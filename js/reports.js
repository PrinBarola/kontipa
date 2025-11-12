/* js/reports.js — debug version: logs raw server response so we can see errors */
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    if (typeof loadReports === 'function') loadReports();
  });

  window.generateReport = async function generateReport() {
    const nameEl = document.getElementById('reportName');
    const typeEl = document.getElementById('reportType');
    const fromDateEl = document.getElementById('reportFromDate');
    const toDateEl = document.getElementById('reportToDate');

    const name = nameEl ? nameEl.value.trim() : '';
    const type = typeEl ? typeEl.value : '';
    const fromDate = fromDateEl ? fromDateEl.value || null : null;
    const toDate = toDateEl ? toDateEl.value || null : null;

    if (!name || !type) {
      alert('Please enter a report name and type.');
      return;
    }

    const payload = { name, type, from_date: fromDate, to_date: toDate };

    try {
      const resp = await fetch('api/create_report.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
        credentials: 'same-origin'
      });

      console.log('create_report status', resp.status, resp.statusText);

      const raw = await resp.text();
      console.log('create_report raw response:', raw);

      // Attempt to parse JSON (if it is JSON)
      let data = null;
      try {
        data = JSON.parse(raw);
      } catch (err) {
        console.error('Failed to parse JSON from create_report response:', err);
        alert('Server returned invalid JSON. See console (Network/Response) for details.');
        return;
      }

      if (!data.success) {
        console.error('create_report error payload:', data);
        alert('Failed to create report: ' + (data.message || 'Unknown error'));
        return;
      }

      // close modal
      const modalEl = document.getElementById('createReportModal');
      if (modalEl) {
        const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modalInstance.hide();
      }

      // reset form
      const form = document.getElementById('createReportForm');
      if (form) form.reset();

      // refresh UI
      if (typeof loadReports === 'function') await loadReports();

      alert('Report created successfully.');
    } catch (err) {
      console.error('Network or unexpected error creating report:', err);
      alert('Network or unexpected error creating report — see console.');
    }
  };

  // keep loadReports simplified (you already have a full version). This is a minimal safe loader.
  window.loadReports = async function loadReports() {
    try {
      const resp = await fetch('api/reports.php', { method: 'GET', credentials: 'same-origin' });
      const data = await resp.json();
      if (!data.success) {
        console.error('api/reports error:', data);
        return;
      }
      // update stat elements by id if present
      if (data.stats) {
        if (document.getElementById('stat-collections')) document.getElementById('stat-collections').textContent = data.stats.collectionsThisMonth ?? 0;
        if (document.getElementById('stat-pending')) document.getElementById('stat-pending').textContent = data.stats.pendingCount ?? 0;
        if (document.getElementById('stat-completed')) document.getElementById('stat-completed').textContent = data.stats.completedThisMonth ?? 0;
        if (document.getElementById('stat-reports')) document.getElementById('stat-reports').textContent = data.stats.reportsCount ?? 0;
      }
      // populate reports table (simple)
      const tbody = document.getElementById('reportsTableBody');
      if (!tbody) return;
      tbody.innerHTML = '';
      const reports = data.reports || [];
      if (reports.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No reports found</td></tr>';
      } else {
        for (const r of reports) {
          const tr = document.createElement('tr');
          const created = r.created_at ? new Date(r.created_at.replace(' ', 'T')).toLocaleString() : '-';
          const status = r.status ? r.status : 'unknown';
          tr.innerHTML = `<td>${escapeHtml(r.name||'Unnamed')}</td>
                          <td class="d-none d-md-table-cell">${escapeHtml(r.type||'')}</td>
                          <td class="d-none d-lg-table-cell">${created}</td>
                          <td><span class="badge ${status.toLowerCase()==='completed'?'bg-success':(status.toLowerCase()==='pending'?'bg-warning text-dark':'bg-secondary')}">${escapeHtml(status)}</span></td>
                          <td class="text-end"><a href="view-report.php?id=${r.report_id}" class="btn btn-sm btn-outline-primary">View</a></td>`;
          tbody.appendChild(tr);
        }
      }
    } catch (err) {
      console.error('loadReports error', err);
    }
  };

  function escapeHtml(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
  }
})();