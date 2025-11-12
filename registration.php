<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Smart Trashbin - Sign Up</title>
  <link rel="stylesheet" href="css/registration.css">
  <link rel="stylesheet" href="css/header.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <div id="scrollProgress" class="scroll-progress"></div>

  <!-- Navigation Header -->
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
        <a href="user-login.php" class="btn btn-signup">
          <i class="fas fa-sign-in-alt"></i> Login
        </a>
      </nav>
    </div>
  </header>

  <!-- Registration Container -->
  <div class="registration-container">
    <!-- Background floating circles -->
    <div class="background-circle background-circle-1"></div>
    <div class="background-circle background-circle-2"></div>
    <div class="background-circle background-circle-3"></div>

    <div class="registration-wrapper">
      <!-- Left Side - Info -->
      <div class="registration-info">
        <!-- Decorative circles -->
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
        <div class="circle circle-3"></div>

        <div class="info-content">
          <div class="logo-circle">
            <i class="fas fa-trash-alt"></i>
          </div>
          <h1>Join Smart Trashbin</h1>
          <p>Start managing waste efficiently today</p>
          <div class="benefits-list">
  <div class="benefit-item">
    <div class="benefit-icon-container"><i class="fa-solid fa-gear"></i></div>
    <span>Setup</span>
  </div>
  <div class="benefit-item">
    <div class="benefit-icon-container"><i class="fa-solid fa-headset"></i></div>
    <span>24/7 Support</span>
  </div>
  <div class="benefit-item">
    <div class="benefit-icon-container"><i class="fa-solid fa-shield-halved"></i></div>
    <span>Secure & Reliable</span>
  </div>
  <div class="benefit-item">
    <div class="benefit-icon-container"><i class="fa-solid fa-bolt"></i></div>
    <span>Real-time Updates</span>
  </div>
</div>

        </div>
      </div>

      <!-- Right Side - Registration Form -->
      <div class="registration-form-section">
        <div class="form-container">
          <!-- Centered header with green "Sign Up Here" -->
          <div class="signup-header">
            <h3><span class="header-highlight">Sign Up Here</span></h3>
            <p>Create your account in just a few steps</p>
          </div>

          <!-- Registration Form -->
          <form id="registrationForm" class="auth-form">
            <div class="form-row">
              <div class="form-group">
                <label for="firstName">First Name</label>
                <div class="input-wrapper">
                  <i class="fas fa-user"></i>
                  <input type="text" id="firstName" name="firstName" placeholder="Enter your first name" required>
                </div>
                <div class="error-message" id="firstNameError"></div>
              </div>
              <div class="form-group">
                <label for="lastName">Last Name</label>
                <div class="input-wrapper">
                  <i class="fas fa-user"></i>
                  <input type="text" id="lastName" name="lastName" placeholder="Enter your last name" required>
                </div>
                <div class="error-message" id="lastNameError"></div>
              </div>
            </div>

            <div class="form-group">
              <label for="regEmail">Email Address</label>
              <div class="input-wrapper">
                <i class="fas fa-envelope"></i>
                <input type="email" id="regEmail" name="email" placeholder="Enter your email address" required>
              </div>
              <div class="error-message" id="emailError"></div>
            </div>

            <div class="form-group">
              <label for="phone">Phone Number</label>
              <div class="input-wrapper">
                <i class="fas fa-phone"></i>
                <input type="tel" id="phone" name="phone" placeholder="Enter your 11-digit phone number" required>
              </div>
              <div class="error-message" id="phoneError"></div>
            </div>

            <div class="form-group">
              <label for="regPassword">Password</label>
              <div class="input-wrapper">
                <i class="fas fa-lock"></i>
                <input type="password" id="regPassword" name="password" placeholder="Enter your password" required>
                <button type="button" class="toggle-password" id="toggleRegPassword">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
              <div class="error-message" id="passwordError"></div>
            </div>

            <div class="form-group">
              <label for="confirmPassword">Confirm Password</label>
              <div class="input-wrapper">
                <i class="fas fa-lock"></i>
                <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm your password" required>
                <button type="button" class="toggle-password" id="toggleConfirmPassword">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
              <div class="error-message" id="confirmPasswordError"></div>
            </div>

            <label class="terms-checkbox">
              <input type="checkbox" name="terms" required>
              <span>I agree to the <a href="#" onclick="openInfoModal('termsModal'); return false;">Terms of Service</a> and <a href="#" onclick="openInfoModal('privacyModal'); return false;">Privacy Policy</a></span>
            </label>

            <button type="submit" class="btn-primary">Create Account</button>
          </form>

          <!-- Sign In Link -->
          <div class="auth-footer">
            <p>Already have an account? <a href="user-login.php">Sign in here</a></p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <div class="footer">
    <div class="footer-content">
      <div class="footer-links">
        <a href="#" onclick="openInfoModal('privacyModal'); return false;">Privacy Policy</a>
                <span class="separator">•</span>
                <a href="#" onclick="openInfoModal('termsModal'); return false;">Terms of Service</a>
                <span class="separator">•</span>
                <a href="#" onclick="openInfoModal('supportModal'); return false;">Support</a>
      </div>
      <p class="footer-text" id="footerText"></p>
      <p class="footer-copyright">
        &copy; 2025 Smart Trashbin. All rights reserved.
      </p>
    </div>
  </div>

  <script src="js/registration.js"></script>
  <script src="js/scroll-progress.js"></script>
  <?php include 'includes/info-modals.php'; ?>
</body>
</html>
