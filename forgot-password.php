<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Smart Trashbin - Forgot Password</title>
  <link rel="stylesheet" href="css/forgot-password.css">
  <link rel="stylesheet" href="css/header.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
  <!-- PREMIUM HEADER -->
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

  <!-- FORGOT PASSWORD CONTAINER -->
  <div class="forgot-password-container">
    <div class="background-circle background-circle-1"></div>
    <div class="background-circle background-circle-2"></div>
    <div class="background-circle background-circle-3"></div>

    <div class="forgot-password-wrapper">
      <!-- BRANDING SECTION -->
      <div class="forgot-password-branding">
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
        <div class="circle circle-3"></div>

        <div class="branding-content">
          <div class="branding-box">
            <div class="logo-circle">
              <i class="fas fa-key"></i>
            </div>
            <h1>Password Recovery</h1>
            <p>Secure & Easy Account Recovery</p>
          </div>

          <div class="features-list">
            <div class="feature-item">
              <div class="feature-icon-container"><i class="fas fa-shield-alt"></i></div>
              <span>Secure Verification</span>
            </div>
            <div class="feature-item">
              <div class="feature-icon-container"><i class="fas fa-envelope"></i></div>
              <span>Email OTP System</span>
            </div>
            <div class="feature-item">
              <div class="feature-icon-container"><i class="fas fa-lock"></i></div>
              <span>Encrypted Process</span>
            </div>
          </div>
        </div>
      </div>

      <!-- FORM SECTION -->
      <div class="forgot-password-form-section">
        <div class="form-container">
          <!-- STEP INDICATORS -->
          <div class="step-indicators">
            <div class="step-indicator active" data-step="1">
              <div class="step-number">1</div>
              <div class="step-label">Email</div>
            </div>
            <div class="step-indicator" data-step="2">
              <div class="step-number">2</div>
              <div class="step-label">Verify</div>
            </div>
            <div class="step-indicator" data-step="3">
              <div class="step-number">3</div>
              <div class="step-label">Reset</div>
            </div>
          </div>

          <!-- STEP 1: EMAIL -->
          <div id="step-email" class="step active">
            <div class="form-header">
              <h2>Forgot Password</h2>
              <p>Enter your email to receive a verification code</p>
            </div>

            <!-- MESSAGE DISPLAY -->
            <div id="msg-email" class="step-message"></div>

            <div class="form-group">
              <label for="email">Email Address</label>
              <div class="input-wrapper">
                <input type="email" id="email" placeholder="Enter your email" required>
              </div>
            </div>

            <button type="button" class="btn-primary" id="btn-send-otp" onclick="sendOtp()">
              <div class="spinner" id="spinner-send"></div>
              <span class="btn-text"><i class="fas fa-paper-plane"></i> Send OTP</span>
            </button>

            <div class="back-link">
              <p>Remember your password? <a href="user-login.php">Sign in</a></p>
            </div>
          </div>

          <!-- STEP 2: OTP -->
          <div id="step-otp" class="step">
            <div class="form-header">
              <h2>Enter OTP</h2>
              <p>We've sent a 6-digit code to your email</p>
            </div>

            <!-- MESSAGE DISPLAY -->
            <div id="msg-otp" class="step-message"></div>

            <div class="form-group">
              <label for="otp">Verification Code</label>
              <div class="input-wrapper">
                <input type="text" id="otp" placeholder="Enter 6-digit OTP" maxlength="6" required>
              </div>
            </div>

            <button type="button" class="btn-primary" id="btn-verify-otp" onclick="verify()">
              <div class="spinner" id="spinner-verify"></div>
              <span class="btn-text"><i class="fas fa-check-circle"></i> Verify Code</span>
            </button>

            <div class="back-link">
              <p>Didn't receive code? <a href="#" onclick="sendOtp(); return false;">Resend OTP</a></p>
            </div>
          </div>

          <!-- STEP 3: RESET PASSWORD -->
          <div id="step-reset" class="step">
            <div class="form-header">
              <h2>Reset Password</h2>
              <p>Enter your new password</p>
            </div>

            <!-- MESSAGE DISPLAY -->
            <div id="msg-reset" class="step-message"></div>

            <div class="form-group">
              <label for="pw">Create Password</label>
              <div class="input-wrapper">
                <input type="password" id="pw" placeholder="Enter new password" required>
                <button type="button" class="toggle-password" id="togglePassword">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>

            <div class="form-group">
              <label for="confirm-pw">Confirm Password</label>
              <div class="input-wrapper">
                <input type="password" id="confirm-pw" placeholder="Re-enter new password" required>
                <button type="button" class="toggle-password" id="toggleConfirmPassword">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>

           <button type="button" class="btn-primary" id="btn-reset-pw" onclick="resetPw()">
  <div class="spinner" id="spinner-reset"></div>
  <span class="btn-text"><i class="fas fa-lock"></i> Reset Password</span>
</button>

            <div class="back-link">
              <p>Password reset successful? <a href="user-login.php">Sign in now</a></p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- PREMIUM FOOTER -->
  <div class="footer">
    <div class="footer-content">
      <div class="footer-links">
        <a href="#" onclick="openInfoModal('privacyModal'); return false;">Privacy Policy</a> <span class="separator">•</span>
        <a href="#" onclick="openInfoModal('termsModal'); return false;">Terms of Service</a> <span class="separator">•</span>
        <a href="#" onclick="openInfoModal('supportModal'); return false;">Support</a>
      </div>
      <span id="footerText" class="footer-text"></span>
      <p class="footer-copyright">&copy; 2025 Smart Trashbin. All rights reserved.</p>
    </div>
  </div>

  <!-- Include shared info modals (Privacy / Terms / Support) -->
  <?php require_once 'includes/info-modals.php'; ?>

  <!-- JAVASCRIPT -->
  <script src="js/forgot-password.js"></script>
  <script>
    // ✅ ENHANCED WITH LOADING ANIMATION & SUCCESS DELAY
let email = "";

async function sendOtp() {
  email = document.getElementById("email").value;

  if (!email || !email.includes("@")) {
    showForgotPasswordMessage("Please enter a valid email address", "error", "msg-email");
    return;
  }

  // Show loading spinner
  const btn = document.getElementById("btn-send-otp");
  const spinner = document.getElementById("spinner-send");
  btn.disabled = true;
  btn.classList.add("loading");
  spinner.classList.add("show");

  try {
    const res = await fetch("check_and_send_otp.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ email }),
    });
    const data = await res.json();

    // Hide loading spinner
    btn.disabled = false;
    btn.classList.remove("loading");
    spinner.classList.remove("show");

    showForgotPasswordMessage(data.msg, data.ok ? "success" : "error", "msg-email");

    if (data.ok) {
      // Wait 2 seconds to show success message before proceeding
      setTimeout(() => {
        showStep("step-otp");
        updateStepIndicator(2);
      }, 2000);
    }
  } catch (error) {
    // Hide loading spinner on error
    btn.disabled = false;
    btn.classList.remove("loading");
    spinner.classList.remove("show");
    showForgotPasswordMessage("Connection error. Please try again.", "error", "msg-email");
  }
}

async function verify() {
  const otp = document.getElementById("otp").value;

  if (!otp || otp.length !== 6) {
    showForgotPasswordMessage("Please enter a valid 6-digit OTP", "error", "msg-otp");
    return;
  }

  // Show loading spinner
  const btn = document.getElementById("btn-verify-otp");
  const spinner = document.getElementById("spinner-verify");
  btn.disabled = true;
  btn.classList.add("loading");
  spinner.classList.add("show");

  try {
    const res = await fetch("verify_otp.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ email, otp }),
    });
    const data = await res.json();

    // Hide loading spinner
    btn.disabled = false;
    btn.classList.remove("loading");
    spinner.classList.remove("show");

    showForgotPasswordMessage(data.msg, data.ok ? "success" : "error", "msg-otp");

    if (data.ok) {
      // Wait 2 seconds to show success message before proceeding
      setTimeout(() => {
        showStep("step-reset");
        updateStepIndicator(3);
      }, 2000);
    }
  } catch (error) {
    // Hide loading spinner on error
    btn.disabled = false;
    btn.classList.remove("loading");
    spinner.classList.remove("show");
    showForgotPasswordMessage("Connection error. Please try again.", "error", "msg-otp");
  }
}

async function resetPw() {
  const new_password = document.getElementById("pw").value;
  const confirm_password = document.getElementById("confirm-pw").value;

const pwRule = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{6,}$/;

if (!pwRule.test(new_password)) {
  showForgotPasswordMessage(
    "Password must be at least 6 characters long and must contain uppercase, lowercase, number, and special character.",
    "error",
    "msg-reset"
  );
  return;
}

  if (new_password !== confirm_password) {
    showForgotPasswordMessage("Passwords do not match", "error", "msg-reset");
    return;
  }

  // ✅ Show loading spinner
  const btn = document.getElementById("btn-reset-pw");
  const spinner = document.getElementById("spinner-reset");
  btn.disabled = true;
  btn.classList.add("loading");
  spinner.classList.add("show");

  try {
    const res = await fetch("reset-password.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ email, new_password }),
    });
    const data = await res.json();

    // ✅ Hide loading spinner
    btn.disabled = false;
    btn.classList.remove("loading");
    spinner.classList.remove("show");

    showForgotPasswordMessage(data.msg, data.ok ? "success" : "error", "msg-reset");

    if (data.ok) {
      setTimeout(() => (location.href = "user-login.php"), 1500);
    }
  } catch (error) {
    // ❌ Hide loading spinner on error
    btn.disabled = false;
    btn.classList.remove("loading");
    spinner.classList.remove("show");
    showForgotPasswordMessage("Connection error. Please try again.", "error", "msg-reset");
  }
}

// ✅ Show next step only
function showStep(stepId) {
  document.getElementById("step-email").classList.remove("active");
  document.getElementById("step-otp").classList.remove("active");
  document.getElementById("step-reset").classList.remove("active");
  document.getElementById(stepId).classList.add("active");
}

// ✅ Update step indicators
function updateStepIndicator(currentStep) {
  const indicators = document.querySelectorAll(".step-indicator");
  indicators.forEach((indicator, index) => {
    const stepNum = index + 1;
    if (stepNum < currentStep) {
      indicator.classList.add("completed");
      indicator.classList.remove("active");
    } else if (stepNum === currentStep) {
      indicator.classList.add("active");
      indicator.classList.remove("completed");
    } else {
      indicator.classList.remove("active", "completed");
    }
  });
}
  </script>
  <script src="js/scroll-progress.js"></script>
</body>
</html>
