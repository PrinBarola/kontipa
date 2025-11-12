<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Smart Trashbin - Admin Login</title>
  <link rel="stylesheet" href="css/login.css">
  <link rel="stylesheet" href="css/header.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
  <div id="scrollProgress" class="scroll-progress"></div>
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
      <nav class="nav-center">
        <a href="index.php" class="nav-link">Home</a>
        <a href="about.php" class="nav-link">About</a>
        <a href="contact.php" class="nav-link">Contact</a>
        <a href="features.php" class="nav-link">Features</a>
      </nav>
      <nav class="nav-buttons">
        <a href="registration.php" class="btn btn-signup">
          <i class="fas fa-user-plus"></i> Sign Up
        </a>
      </nav>
    </div>
  </header>

  <div class="login-container">
    <div class="background-circle background-circle-1"></div>
    <div class="background-circle background-circle-2"></div>
    <div class="background-circle background-circle-3"></div>

    <div class="login-wrapper">
      <div class="login-branding">
        <div class="branding-content">
          <div class="branding-box">
            <div class="logo-circle">
              <i class="fas fa-trash-alt"></i>
            </div>
            <h1>Smart Trashbin</h1>
            <p>Intelligent Waste Management System</p>
          </div>

          <div class="features-list">
            <div class="feature-item">
              <div class="feature-icon-container"><i class="fas fa-chart-line"></i></div>
              <span>Real-time Monitoring</span>
            </div>
            <div class="feature-item">
              <div class="feature-icon-container"><i class="fas fa-bell"></i></div>
              <span>Automated Alerts</span>
            </div>
            <div class="feature-item">
              <div class="feature-icon-container"><i class="fas fa-chart-bar"></i></div>
              <span>Analytics Dashboard</span>
            </div>
          </div>
        </div>
      </div>

      <div class="login-form-section">
        <div class="form-container">
          <div class="form-header">
            <h2><span class="header-highlight">Admin Login</span></h2>
            <p>Access the administrative panel</p>
          </div>

          <form id="loginForm" class="auth-form">
            <input type="hidden" name="role" value="admin">

            <div class="form-group">
              <label for="email">Email Address</label>
              <div class="input-wrapper">
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
              </div>
              <span class="error-message" id="emailError"></span>
            </div>

            <div class="form-group">
              <label for="password">Password</label>
              <div class="input-wrapper">
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
                <button type="button" class="toggle-password" id="togglePassword"><i class="fas fa-eye"></i></button>
              </div>
              <span class="error-message" id="passwordError"></span>
            </div>

            <div class="form-options">
              <label class="remember-me"><input type="checkbox" name="remember" id="remember"><span>Remember me</span></label>
              <a href="forgot-password.php" class="forgot-link">Forgot Password?</a>
            </div>

            <button type="submit" class="btn-primary">Sign In</button>
            <div class="auth-footer"><p>Don't have an account? <a href="registration.php">Sign up now</a></p></div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="footer">
    <div class="footer-content">
      <div class="footer-links">
        <a href="#" onclick="openInfoModal('privacyModal'); return false;">Privacy Policy</a>
                <span class="separator">•</span>
                <a href="#" onclick="openInfoModal('termsModal'); return false;">Terms of Service</a>
                <span class="separator">•</span>
                <a href="#" onclick="openInfoModal('supportModal'); return false;">Support</a>
      </div>
       <span id="footerText" class="footer-text"></span>
      <p class="footer-copyright">&copy; 2025 Smart Trashbin. All rights reserved.</p>
    </div>
  </div>

  <!-- JS (uses standalone admin-login endpoint) -->
  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const loginForm = document.getElementById("loginForm");
      loginForm.addEventListener("submit", async e => {
        e.preventDefault();
        const formData = new FormData(loginForm);
        try {
          console.log('[admin-login] Sending login request...');
          const response = await fetch("api/admin-login.php", { method: "POST", body: formData });
          const data = await response.json();
          console.log('[admin-login] Response:', data);
          if (data.success) {
            showNotification(data.message || "Welcome!", "success");
            setTimeout(() => { window.location.href = data.redirect || "admin-dashboard.php"; }, 1500);
          } else {
            showNotification(data.message || "Login failed", "error");
          }
        } catch (err) {
          console.error('[admin-login] Fetch error:', err);
          showNotification("Login failed. Please try again.", "error");
        }
      });
    });

    function showNotification(msg, type) {
      let n = document.getElementById("notificationMessage");
      if (!n) {
        n = document.createElement("div");
        n.id = "notificationMessage";
        n.style.cssText = "position:fixed;top:20px;right:20px;padding:16px;border-radius:8px;font-weight:600;z-index:9999;";
        document.body.appendChild(n);
      }
      n.textContent = msg;
      n.style.backgroundColor = type === "success" ? "#dcfce7" : "#fee2e2";
      n.style.color = type === "success" ? "#166534" : "#991b1b";
      n.style.borderLeft = type === "success" ? "4px solid #16a34a" : "4px solid #dc2626";
      n.style.display = "block";
      setTimeout(() => n.remove(), 4000);
    }

     // ============================================
  // FOOTER DYNAMIC TEXT
  // ============================================
  function initFooterText() {
    const footerText = document.getElementById('footerText');
    if (footerText) {
      const messages = [
        'Making waste management smarter, one bin at a time.',
        'Powered by IoT technology and sustainable innovation.',
        'Join us in creating cleaner, greener communities.',
        'Real-time monitoring for a cleaner tomorrow.'
      ];

      let currentIndex = 0;

      function updateFooterText() {
        footerText.style.opacity = '0';

        setTimeout(() => {
          footerText.textContent = messages[currentIndex];
          footerText.style.opacity = '1';
          currentIndex = (currentIndex + 1) % messages.length;
        }, 500);
      }

      footerText.textContent = messages[0];
      setInterval(updateFooterText, 5000);
    }
  }

  document.addEventListener('DOMContentLoaded', initFooterText);

  // ===============================
// PASSWORD TOGGLE FUNCTION
// ===============================
const togglePassword = document.getElementById("togglePassword");
const passwordInput = document.getElementById("password");

togglePassword.addEventListener("click", function () {
  const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
  passwordInput.setAttribute("type", type);

  // Palit icon eye <-> eye-slash
  this.querySelector("i").classList.toggle("fa-eye");
  this.querySelector("i").classList.toggle("fa-eye-slash");
});
  </script>
  <script src="js/scroll-progress.js"></script>
  <?php include 'includes/info-modals.php'; ?>
</body>
</html>