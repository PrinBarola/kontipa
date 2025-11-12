<?php
// Centralized admin footer + notifications/logout modals + shared info modals
?>

<!-- Notifications Modal -->
<div class="modal fade" id="notificationsModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-bell me-2"></i>Notifications</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <div id="notificationsPanel">
          <div class="text-center py-4 text-muted">
            <i class="fas fa-inbox" style="font-size: 40px; opacity: 0.5;"></i>
            <p class="mt-2">No notifications</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Premium-style Logout Modal (shared) -->
<div class="premium-modal" id="logoutModal">
  <div class="premium-modal-overlay" onclick="closeLogoutModal()"></div>
  <div class="premium-modal-content">
    <div class="premium-modal-header">
      <div class="modal-icon-wrapper"><i class="fa-solid fa-right-from-bracket"></i></div>
      <h3 class="modal-title">Confirm Logout</h3>
      <p class="modal-subtitle">Are you sure you want to logout?</p>
    </div>
    <div class="premium-modal-footer">
      <button class="btn-modal btn-cancel" onclick="closeLogoutModal()"><i class="fa-solid fa-times me-2"></i>Cancel</button>
      <button class="btn-modal btn-confirm" onclick="confirmLogout()"><i class="fa-solid fa-check me-2"></i>Yes, Logout</button>
    </div>
  </div>
</div>

<!-- Shared Info Modals (Privacy/Terms/Support) -->
<?php include_once __DIR__ . '/info-modals.php'; ?>

<!-- Footer markup -->
<div class="footer">
  <div class="footer-content">
    <div class="footer-links">
      <a href="#" onclick="openInfoModal('privacyModal'); return false;">Privacy Policy</a>
      <span class="separator">•</span>
      <a href="#" onclick="openInfoModal('termsModal'); return false;">Terms of Service</a>
      <span class="separator">•</span>
      <a href="#" onclick="openInfoModal('supportModal'); return false;">Support</a>
    </div>
    <p class="footer-text" id="footerText">Making waste management smarter, one bin at a time.</p>
    <p class="footer-copyright">&copy; 2025 Smart Trashbin. All rights reserved.</p>
  </div>
</div>
