<?php
require_once 'includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Janitors Management - Trashbin Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/janitor-dashboard.css">
  <!-- header styles are included in shared header include -->
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
      <div class="section-header flex-column flex-md-row">
        <div>
          <h1 class="page-title">Users Management</h1>
          <p class="page-subtitle">Manage janitors and their assignments</p>
        </div>
        <div class="d-flex gap-2 flex-column flex-md-row mt-3 mt-md-0">
          <div class="input-group">
            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
            <input type="text" class="form-control border-start-0 ps-0" id="searchJanitorsInput" placeholder="Search janitors...">
          </div>
          <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="filterJanitorsDropdown" data-bs-toggle="dropdown">
              <i class="fas fa-filter me-1"></i>Filter
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="filterJanitorsDropdown">
              <li><a class="dropdown-item" href="#" data-filter="all">All Janitors</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="#" data-filter="active">Active</a></li>
              <li><a class="dropdown-item" href="#" data-filter="inactive">Inactive</a></li>
            </ul>
          </div>
          <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addJanitorModal">
            <i class="fas fa-plus me-1"></i>Add Janitor
          </button>
        </div>
      </div>

      <div class="card">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table mb-0">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th class="d-none d-md-table-cell">Phone</th>
                  <th class="d-none d-lg-table-cell">Bins</th>
                  <th>Status</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody id="janitorsTableBody">
                <tr>
                  <td colspan="6" class="text-center py-4 text-muted">No janitors found</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
  </div>

  <!-- Add Janitor Modal -->
  <div class="modal fade" id="addJanitorModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add New Janitor</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="addJanitorForm">
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="janitorFirstName" class="form-label">First Name</label>
                  <input type="text" class="form-control" id="janitorFirstName" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="janitorLastName" class="form-label">Last Name</label>
                  <input type="text" class="form-control" id="janitorLastName" required>
                </div>
              </div>
            </div>
            <div class="mb-3">
              <label for="janitorEmail" class="form-label">Email</label>
              <input type="email" class="form-control" id="janitorEmail" required>
            </div>
            <div class="mb-3">
              <label for="janitorPhone" class="form-label">Phone</label>
              <input type="tel" class="form-control" id="janitorPhone" required>
            </div>
            <div class="mb-3">
              <label for="janitorStatus" class="form-label">Status</label>
              <select class="form-select" id="janitorStatus" required>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="saveNewJanitor()">Save Janitor</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Janitor Modal -->
  <div class="modal fade" id="editJanitorModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Janitor</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="editJanitorForm">
            <input type="hidden" id="editJanitorId">
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">First Name</label>
                  <input type="text" class="form-control" id="editJanitorFirstName" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Last Name</label>
                  <input type="text" class="form-control" id="editJanitorLastName" required>
                </div>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" id="editJanitorEmail" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Phone</label>
              <input type="tel" class="form-control" id="editJanitorPhone" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Status</label>
              <select class="form-select" id="editJanitorStatus" required>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="saveJanitorEdit()">Save Changes</button>
        </div>
      </div>
    </div>
  </div>
      <?php include_once __DIR__ . '/includes/footer-admin.php'; ?>

      <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="js/bootstrap.bundle.min.js"></script>
  <script src="js/database.js"></script>
  <script src="js/dashboard.js"></script>
  <script>
    function loadAllJanitors(filter = 'all') {
      fetch(`api/get-janitors.php?filter=${filter}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            displayJanitors(data.janitors);
          } else {
            console.error('Error loading janitors:', data.message);
          }
        })
        .catch(error => console.error('Fetch error:', error));
    }

    function displayJanitors(janitors) {
      const tbody = document.getElementById('janitorsTableBody');

      if (!Array.isArray(janitors) || janitors.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No janitors found</td></tr>';
        return;
      }

      // Use janitor_id (DB) everywhere
      tbody.innerHTML = janitors.map(janitor => `
        <tr>
          <td>
            <strong>${escapeHtml(janitor.first_name)} ${escapeHtml(janitor.last_name)}</strong>
          </td>
          <td>${escapeHtml(janitor.email)}</td>
          <td class="d-none d-md-table-cell">${escapeHtml(janitor.phone)}</td>
          <td class="d-none d-lg-table-cell">
            <span class="badge bg-info">${janitor.assigned_bins || 0}</span>
          </td>
          <td>
            <span class="badge ${janitor.status === 'active' ? 'bg-success' : 'bg-secondary'}">
              ${janitor.status ? (janitor.status.charAt(0).toUpperCase() + janitor.status.slice(1)) : ''}
            </span>
          </td>
          <td class="text-end">
            <button class="btn btn-sm btn-outline-primary" onclick="editJanitor(${janitor.janitor_id}, '${jsEscape(janitor.first_name)}', '${jsEscape(janitor.last_name)}', '${jsEscape(janitor.email)}', '${jsEscape(janitor.phone)}', '${jsEscape(janitor.status)}')">
              <i class="fas fa-edit"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" onclick="deleteJanitor(${janitor.janitor_id})">
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>
      `).join('');
    }

    function saveNewJanitor() {
      const formData = {
        first_name: document.getElementById('janitorFirstName').value.trim(),
        last_name: document.getElementById('janitorLastName').value.trim(),
        email: document.getElementById('janitorEmail').value,
        phone: document.getElementById('janitorPhone').value,
        status: document.getElementById('janitorStatus').value
      };

      fetch('api/add-janitor.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            document.getElementById('addJanitorForm').reset();
            const modal = bootstrap.Modal.getInstance(document.getElementById('addJanitorModal'));
            modal.hide();
            loadAllJanitors();
            alert('Janitor added successfully');
          } else {
            alert('Error: ' + data.message);
          }
        })
        .catch(error => console.error('Error:', error));
    }

    function editJanitor(janitorId, firstName, lastName, email, phone, status) {
      document.getElementById('editJanitorId').value = janitorId;
      document.getElementById('editJanitorFirstName').value = firstName;
      document.getElementById('editJanitorLastName').value = lastName;
      document.getElementById('editJanitorEmail').value = email;
      document.getElementById('editJanitorPhone').value = phone;
      document.getElementById('editJanitorStatus').value = status;

      const modal = new bootstrap.Modal(document.getElementById('editJanitorModal'));
      modal.show();
    }

    function saveJanitorEdit() {
      const formData = {
        janitor_id: document.getElementById('editJanitorId').value,
        first_name: document.getElementById('editJanitorFirstName').value.trim(),
        last_name: document.getElementById('editJanitorLastName').value.trim(),
        email: document.getElementById('editJanitorEmail').value,
        phone: document.getElementById('editJanitorPhone').value,
        status: document.getElementById('editJanitorStatus').value
      };

      fetch('api/edit-janitor.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('editJanitorModal'));
            modal.hide();
            loadAllJanitors();
            alert('Janitor updated successfully');
          } else {
            alert('Error: ' + data.message);
          }
        })
        .catch(error => console.error('Error:', error));
    }

    function deleteJanitor(janitorId) {
      if (confirm('Are you sure you want to delete this janitor?')) {
        fetch('api/delete-janitor.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ janitor_id: janitorId })
        })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              loadAllJanitors();
              alert('Janitor deleted successfully');
            } else {
              alert('Error: ' + data.message);
            }
          })
          .catch(error => console.error('Error:', error));
      }
    }

    // small helper to escape HTML
    function escapeHtml(s) {
      if (!s) return '';
      return s.replace(/[&<>"']/g, function(m) {
        return ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;', "'":'&#39;' })[m];
      });
    }

    // helper to escape strings for single-quoted JS inline args
    function jsEscape(s) {
      if (s === undefined || s === null) return '';
      return String(s).replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/\n/g, '\\n').replace(/\r/g, '');
    }

    document.addEventListener('DOMContentLoaded', function() {
      loadAllJanitors();

      // Search functionality
      document.getElementById('searchJanitorsInput').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        document.querySelectorAll('#janitorsTableBody tr').forEach(row => {
          const text = row.textContent.toLowerCase();
          row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
      });

      // Filter dropdown wiring
      document.querySelectorAll('#filterJanitorsDropdown + .dropdown-menu .dropdown-item').forEach(item => {
        item.addEventListener('click', function(e) {
          e.preventDefault();
          const filter = this.getAttribute('data-filter');
          loadAllJanitors(filter);
        });
      });
    });
  </script>
  <!-- Janitor dashboard JS for header/footer modal helpers -->
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