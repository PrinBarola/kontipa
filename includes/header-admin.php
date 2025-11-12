<?php
// Centralized janitor/admin header
?>
<!-- Admin Header (shared) -->
<!-- Custom control heights for admin management headers -->
<link rel="stylesheet" href="css/control-heights.css">
<!-- Shared admin styles for consistent animations and interactions -->
<link rel="stylesheet" href="css/admin-shared.css">
<script src="js/admin.js" defer></script>
<style>
  /* Header: ensure notification badge is visible and not clipped */
  .header .header-container .nav-buttons {
    display: flex;
    align-items: center;
    gap: 1.5rem;
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
  .nav-buttons .logout-link {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.35rem 0.6rem;
    border-radius: 6px;
    transition: background 0.12s ease;
  }
  .nav-buttons .logout-link:hover { background: rgba(0,0,0,0.04);} 
  .header { overflow: visible; }

  /* Fallback/high-specificity styles so notification bell and logout
     button look identical across admin pages even if external CSS
     isn't loaded yet. These mirror the janitor-dashboard styles. */
  .header .header-container .nav-buttons .nav-link {
    font-size: 15px;
    font-weight: 600;
    color: inherit;
    text-decoration: none;
    transition: transform 0.18s ease, background 0.12s ease, box-shadow 0.18s ease;
    position: relative;
    padding: 8px 12px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }

  .header .header-container .nav-buttons .notification-link {
    background: rgba(16, 185, 129, 0.08);
    color: #10b981;
    width: 40px;  /* adjust mo depende sa design */
    height: 30px; /* optional */
    justify-content: center; /* center icon */
  }

  .header .header-container .nav-buttons .notification-link:hover {
    background: rgba(16, 185, 129, 0.15);
    transform: translateY(-2px);
  }

  .header .header-container .nav-buttons .notification-link:hover .fa-bell {
    animation: shake 0.5s ease-in-out;
  }

  @keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-6px); }
    50% { transform: translateX(6px); }
    75% { transform: translateX(-6px); }
  }

  .header .header-container .nav-buttons .logout-link {
    background: linear-gradient(135deg, #10b981, #059669);
    color: #fff;
    box-shadow: 0 0 20px rgba(0, 153, 97, 0.3);
    width: 95px; /* adjust mo depende sa gusto mong haba */
    justify-content: center; /* para naka-center yung icon + text */
  }

  .header .header-container .nav-buttons .logout-link:hover {
    box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4);
  }
</style>

<div class="scroll-progress-container">
  <div class="scroll-progress-bar"></div>
</div>

<header class="header">
  <div class="header-container">
    <div class="logo-section">
      <div class="logo-wrapper">
        <svg class="animated-logo" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
          <rect x="30" y="35" width="40" height="50" rx="6" fill="#16a34a"/>
          <rect x="25" y="30" width="50" height="5" fill="#15803d"/>
          <rect x="40" y="20" width="20" height="8" rx="2" fill="#22c55e"/>
          <line x1="40" y1="45" x2="40" y2="80" stroke="#f0fdf4" stroke-width="3" />
          <line x1="50" y1="45" x2="50" y2="80" stroke="#f0fdf4" stroke-width="3" />
          <line x1="60" y1="45" x2="60" y2="80" stroke="#f0fdf4" stroke-width="3" />
        </svg>
      </div>
      <div class="logo-text-section">
        <h1 class="brand-name">Smart Trashbin</h1>
        <p class="header-subtitle">Intelligent Waste Management System</p>
      </div>
    </div>
    <nav class="nav-buttons">
      <a class="nav-link notification-link" href="notifications.php" id="notificationsBtn" role="button" aria-label="Notifications">
        <i class="fa-solid fa-bell" aria-hidden="true"></i>
        <span class="badge rounded-pill bg-danger notification-badge" id="notificationCount" style="display:none;">0</span>
      </a>
      <a id="logoutBtn" class="nav-link logout-link" href="#" onclick="showLogoutModal(event)" title="Logout">
        <i class="fa-solid fa-right-from-bracket"></i>
        <span class="logout-text">Logout</span>
      </a>
    </nav>
  </div>
</header>
<script>
  // Defensive wiring: ensure header buttons open the shared notifications/logout modals
  document.addEventListener('DOMContentLoaded', function() {

    try {
      // Use a capturing delegated click listener to intercept clicks on header buttons
      // This ensures the confirmation modal is shown first and prevents other handlers
      document.addEventListener('click', function(e) {
        const notifBtn = e.target.closest && e.target.closest('#notificationsBtn');
        const logoutBtn = e.target.closest && e.target.closest('#logoutBtn');

        if (notifBtn) {
          // If the page contains a client-side alerts section (SPA-like janitor dashboard), open it inline.
          // Otherwise redirect to the notifications page (admin flow).
          try {
            if (document.getElementById('alertsSection')) {
              // prefer an existing global handler if present
              if (typeof openNotificationsModal === 'function') { openNotificationsModal(e); return; }
              if (typeof showSection === 'function') { showSection('alerts'); return; }
              // fallback: show the alerts section by id
              document.getElementById('alertsSection').style.display = '';
              return;
            }
            // default admin behavior: navigate to notifications.php
            window.location.href = 'notifications.php';
            return;
          } catch(err) { console.warn('notifications handler failed', err); }
        }

        if (logoutBtn) {
          e.preventDefault();
          e.stopImmediatePropagation();
          try {
            if (typeof showLogoutModal === 'function') { showLogoutModal(e); return; }
            if (typeof showModalById === 'function') { showModalById('logoutModal'); return; }
            const modalEl = document.getElementById('logoutModal');
            if (modalEl) { modalEl.classList.add('active'); document.body.style.overflow = 'hidden'; return; }
            if (window.bootstrap && window.bootstrap.Modal) {
              try { new window.bootstrap.Modal(document.getElementById('logoutModal')).show(); return; } catch(err) { console.warn('bootstrap logout show failed', err); }
            }
            window.location.href = 'logout.php';
          } catch(err) { console.warn('logout handler failed', err); window.location.href = 'logout.php'; }
        }
      }, true /* capture */);
    } catch(err) { console.warn('Header modal wiring failed', err); }

    // Sidebar active-link fixer: mark the current page's sidebar item active based on URL
    try {
      const sidebarItems = document.querySelectorAll('.sidebar-item');
      if (sidebarItems && sidebarItems.length) {
        // derive current filename (e.g., 'bins.php' or 'profile.php')
        const current = window.location.pathname.split('/').pop();
        sidebarItems.forEach(item => {
          try { item.classList.remove('active'); } catch(e) {}
          const href = (item.getAttribute('href') || '').split('?')[0].split('#')[0];
          if (!href || href === '#') return;
          const target = href.split('/').pop();
          if (target === current) {
            item.classList.add('active');
          }
        });
      }
    } catch(e) { console.warn('Sidebar active fixer failed', e); }

      // Ensure modal buttons inside #logoutModal are wired even if inline onclick fails
      try {
        const logoutModal = document.getElementById('logoutModal');
        if (logoutModal) {
          const btnCancel = logoutModal.querySelector('.btn-cancel');
          const btnConfirm = logoutModal.querySelector('.btn-confirm');
          if (btnCancel) {
            btnCancel.addEventListener('click', function(e) {
              e.preventDefault();
              e.stopPropagation();
              if (typeof closeLogoutModal === 'function') return closeLogoutModal();
              // fallback: hide modal
              logoutModal.classList.remove('active');
              document.body.style.overflow = '';
            });
          }
          if (btnConfirm) {
            btnConfirm.addEventListener('click', function(e) {
              e.preventDefault();
              e.stopPropagation();
              if (typeof confirmLogout === 'function') return confirmLogout();
              // fallback: navigate to index
              window.location.href = 'index.php';
            });
          }
        }
      } catch(err) { console.warn('Logout modal button wiring failed', err); }
  });
</script>
