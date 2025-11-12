<!-- Shared Info Modals: Privacy, Terms, Support -->
<style>
.info-modal {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 10000;
  animation: fadeIn 0.3s ease-out;
}

.info-modal.active {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.info-modal-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
}

.info-modal-content {
  position: relative;
  background: white;
  border-radius: 24px;
  max-width: 800px;
  width: 90%;
  max-height: 85vh;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  animation: slideUpModal 0.4s cubic-bezier(0.16, 1, 0.3, 1);
  display: flex;
  flex-direction: column;
}

@keyframes slideUpModal {
  from {
    opacity: 0;
    transform: translateY(40px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.info-modal-header {
  background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
  padding: 32px;
  color: white;
  position: relative;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.info-modal-close {
  position: absolute;
  top: 24px;
  right: 24px;
  width: 40px;
  height: 40px;
  border: none;
  background: rgba(255, 255, 255, 0.2);
  color: white;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  transition: all 0.3s ease;
  backdrop-filter: blur(10px);
}

.info-modal-close:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: rotate(90deg) scale(1.1);
}

.info-modal-title {
  font-size: 28px;
  font-weight: 700;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 16px;
}

.info-modal-title i {
  font-size: 32px;
}

.info-modal-body {
  padding: 32px;
  overflow-y: auto;
  flex: 1;
}

.info-modal-body::-webkit-scrollbar {
  width: 8px;
}

.info-modal-body::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.info-modal-body::-webkit-scrollbar-thumb {
  background: var(--primary-color);
  border-radius: 4px;
}

.info-modal-body::-webkit-scrollbar-thumb:hover {
  background: var(--primary-dark);
}

.info-modal-body h3 {
  color: var(--text-dark);
  font-size: 20px;
  font-weight: 600;
  margin-top: 24px;
  margin-bottom: 12px;
}

.info-modal-body h3:first-child {
  margin-top: 0;
}

.info-modal-body p {
  color: var(--text-light);
  line-height: 1.8;
  margin-bottom: 16px;
}

.info-modal-body ul {
  color: var(--text-light);
  line-height: 1.8;
  margin-bottom: 16px;
  padding-left: 24px;
}

.info-modal-body li {
  margin-bottom: 8px;
}

.info-modal-body strong {
  color: var(--text-dark);
  font-weight: 600;
}

@media (max-width: 768px) {
  .info-modal-content {
    width: 95%;
    max-height: 90vh;
  }

  .info-modal-header {
    padding: 24px;
  }

  .info-modal-title {
    font-size: 22px;
  }

  .info-modal-title i {
    font-size: 24px;
  }

  .info-modal-body {
    padding: 24px;
  }

  .info-modal-close {
    top: 16px;
    right: 16px;
    width: 36px;
    height: 36px;
    font-size: 18px;
  }
}

</style>

<!-- Privacy Policy Modal -->
<div class="info-modal" id="privacyModal">
    <div class="info-modal-overlay" onclick="closeInfoModal()"></div>
    <div class="info-modal-content">
      <div class="info-modal-header">
        <h2 class="info-modal-title">
          <i class="fa-solid fa-shield-halved"></i>
          Privacy Policy
        </h2>
        <button class="info-modal-close" onclick="closeInfoModal()">
          <i class="fa-solid fa-times"></i>
        </button>
      </div>
      <div class="info-modal-body">
        <p><strong>Effective Date:</strong> November 2025</p>
        
        <h3>1. Information We Collect</h3>
        <p>Smart Trashbin collects information necessary to provide efficient waste management services:</p>
        <ul>
          <li><strong>User Account Information:</strong> Name, email address, employee ID, and role designation</li>
          <li><strong>Bin Usage Data:</strong> Fill levels, collection times, location data, and waste type categorization</li>
          <li><strong>System Activity:</strong> Task completion records, maintenance logs, and alert notifications</li>
          <li><strong>Device Information:</strong> IP addresses, browser types, and device identifiers for system security</li>
        </ul>

        <h3>2. How We Use Your Information</h3>
        <p>We use collected data to:</p>
        <ul>
          <li>Monitor and optimize waste collection routes and schedules</li>
          <li>Track bin capacity and trigger timely collection alerts</li>
          <li>Generate reports and analytics for facility management</li>
          <li>Maintain system security and prevent unauthorized access</li>
          <li>Improve our smart waste management services</li>
        </ul>

        <h3>3. Data Security</h3>
        <p>We implement industry-standard security measures to protect your data, including encryption, secure servers, and regular security audits. Access to personal information is restricted to authorized personnel only.</p>

        <h3>4. Data Retention</h3>
        <p>We retain operational data for as long as necessary to provide services and comply with legal requirements. Historical data is anonymized after the retention period.</p>

        <h3>5. Your Rights</h3>
        <p>You have the right to access, correct, or delete your personal information. Contact your system administrator to exercise these rights.</p>

        <h3>6. Changes to This Policy</h3>
        <p>We may update this privacy policy periodically. Users will be notified of significant changes through the system dashboard.</p>

        <p><strong>Contact:</strong> For privacy concerns, contact your facility administrator or email support@smarttrashbin.com</p>
      </div>
    </div>
  </div>

  <!-- Terms of Service Modal -->
  <div class="info-modal" id="termsModal">
    <div class="info-modal-overlay" onclick="closeInfoModal()"></div>
    <div class="info-modal-content">
      <div class="info-modal-header">
        <h2 class="info-modal-title">
          <i class="fa-solid fa-file-contract"></i>
          Terms of Service
        </h2>
        <button class="info-modal-close" onclick="closeInfoModal()">
          <i class="fa-solid fa-times"></i>
        </button>
      </div>
      <div class="info-modal-body">
        <p><strong>Last Updated:</strong> November 2025</p>
        
        <h3>1. Acceptance of Terms</h3>
        <p>By accessing and using the Smart Trashbin Management System, you agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use the system.</p>

        <h3>2. User Responsibilities</h3>
        <p>As a system user, you agree to:</p>
        <ul>
          <li>Maintain the confidentiality of your login credentials</li>
          <li>Use the system only for authorized waste management purposes</li>
          <li>Accurately report bin status and maintenance activities</li>
          <li>Promptly respond to system alerts and notifications</li>
          <li>Not attempt to bypass security measures or access unauthorized areas</li>
        </ul>

        <h3>3. System Usage</h3>
        <p>The Smart Trashbin system is provided for efficient waste collection and facility management. Users must:</p>
        <ul>
          <li>Follow proper procedures for updating bin status</li>
          <li>Report system issues or malfunctions immediately</li>
          <li>Use the system in accordance with your facility's waste management policies</li>
        </ul>

        <h3>4. Service Availability</h3>
        <p>While we strive to maintain 99.9% uptime, we do not guarantee uninterrupted service. Scheduled maintenance will be communicated in advance when possible.</p>

        <h3>5. Data Accuracy</h3>
        <p>Users are responsible for ensuring the accuracy of data entered into the system. Smart Trashbin is not liable for decisions made based on inaccurate user-provided information.</p>

        <h3>6. Prohibited Activities</h3>
        <p>The following activities are strictly prohibited:</p>
        <ul>
          <li>Sharing account credentials with unauthorized personnel</li>
          <li>Attempting to hack, exploit, or reverse-engineer the system</li>
          <li>Uploading malicious code or harmful content</li>
          <li>Interfering with other users' access to the system</li>
        </ul>

        <h3>7. Termination</h3>
        <p>We reserve the right to suspend or terminate access for users who violate these terms or engage in activities that compromise system security.</p>

        <h3>8. Limitation of Liability</h3>
        <p>Smart Trashbin provides the system "as is" and is not liable for indirect damages resulting from system use or downtime.</p>

        <h3>9. Contact Information</h3>
        <p>For questions about these terms, contact your system administrator.</p>
      </div>
    </div>
  </div>

  <!-- Support Modal -->
  <div class="info-modal" id="supportModal">
    <div class="info-modal-overlay" onclick="closeInfoModal()"></div>
    <div class="info-modal-content">
      <div class="info-modal-header">
        <h2 class="info-modal-title">
          <i class="fa-solid fa-headset"></i>
          Support Center
        </h2>
        <button class="info-modal-close" onclick="closeInfoModal()">
          <i class="fa-solid fa-times"></i>
        </button>
      </div>
      <div class="info-modal-body">
        <h3>Need Help?</h3>
        <p>We're here to support your waste management operations 24/7. Choose the option that best suits your needs:</p>

        <h3><i class="fa-solid fa-phone"></i> Emergency Support</h3>
        <p>For urgent issues affecting waste collection operations:</p>
        <ul>
          <li><strong>Hotline:</strong> 1-800-SMART-BIN (24/7)</li>
          <li><strong>Response Time:</strong> Within 15 minutes</li>
          <li><strong>Use for:</strong> System outages, sensor malfunctions, critical alerts</li>
        </ul>

        <h3><i class="fa-solid fa-envelope"></i> Email Support</h3>
        <p>For non-urgent inquiries and questions:</p>
        <ul>
          <li><strong>Email:</strong> smartrashbin.system@gmail.com</li>
          <li><strong>Response Time:</strong> Within 4 business hours</li>
          <li><strong>Use for:</strong> Account issues, feature requests, general questions</li>
        </ul>

        <h3><i class="fa-solid fa-book"></i> Knowledge Base</h3>
        <p>Access our comprehensive guides and FAQs:</p>
        <ul>
          <li>How to update bin status</li>
          <li>Understanding alert notifications</li>
          <li>Troubleshooting sensor issues</li>
          <li>Generating reports and analytics</li>
        </ul>

        <h3><i class="fa-solid fa-comments"></i> Live Chat</h3>
        <p>Available Monday-Friday, 8:00 AM - 6:00 PM</p>
        <p>Click the chat icon in the bottom right corner of your dashboard.</p>

        <h3><i class="fa-solid fa-wrench"></i> Technical Support</h3>
        <p>For system administrators and technical issues:</p>
        <ul>
          <li><strong>Email:</strong> smartrashbin.system@gmail.com</li>
        </ul>

        <h3><i class="fa-solid fa-lightbulb"></i> Training & Resources</h3>
        <p>New to Smart Trashbin? Access our training materials:</p>
        <ul>
          <li>Video tutorials for janitors and administrators</li>
          <li>Best practices for waste management</li>
          <li>System optimization guides</li>
        </ul>

        <h3><i class="fa-solid fa-bug"></i> Report a Bug</h3>
        <p>Help us improve! Report system bugs or unexpected behavior:</p>
        <ul>
          <li><strong>Email:</strong> smartrashbin.system@gmail.com</li>
          <li>Include: Screenshot, error message, steps to reproduce</li>
        </ul>

        <p><strong>System Status:</strong> Check real-time system status at status.smarttrashbin.com</p>
      </div>
    </div>
  </div>

  <script>
  // Open/close helpers (defensive) — keeps behavior consistent across pages
  function openInfoModal(modalId) {
    try {
      var modal = document.getElementById(modalId);
      if (modal) {
        modal.classList.add('active');
        // trap scroll while modal open
        document.body.style.overflow = 'hidden';
        // move focus into the modal for accessibility
        var focusable = modal.querySelector('button, a, input, [tabindex]');
        if (focusable) focusable.focus();
      }
    } catch (e) { console.error(e); }
  }
  function closeInfoModal() {
    try {
      var modals = document.querySelectorAll('.info-modal.active');
      modals.forEach(function(m){ m.classList.remove('active'); });
      document.body.style.overflow = '';
    } catch (e) { console.error(e); }
  }
  // Close on ESC
  document.addEventListener('keydown', function(e){ if(e.key==='Escape'){ closeInfoModal(); } });
  </script>
