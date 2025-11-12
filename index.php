<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Trashbin - Intelligent Waste Management System</title>
  <link rel="stylesheet" href="css/index.css">
  <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <a href="#" onclick="openRoleModal(event)" class="btn btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <a href="registration.php" class="btn btn-signup">
                    <i class="fas fa-user-plus"></i> Sign Up
                </a>
            </nav>
        </div>
    </header>

    <!-- Hero Slideshow Section -->
    <section class="hero-slideshow-container">
  <div class="slides-wrapper">
    <!-- Slide 1: Main Intro -->
    <div class="slide slide-1 active" style="background-image: url('images/background.png');">
      <div class="slide-overlay"></div>
      <div class="slideshow-text">
        <div class="slide-icon">
          <i class="fas fa-trash-alt"></i>
        </div>
        <h2>Smart Trashbin with Level Monitoring</h2>
        <p style="font-size: 1.2rem; margin-bottom: 25px;">
          Real-time monitoring, automated alerts, and comprehensive reporting for efficient waste collection
        </p>
        <div class="slideshow-buttons">
          <a href="#" onclick="openRoleModal(event)" class="btn btn-primary">
            <i class="fas fa-rocket"></i> Get Started
          </a>
          <a href="about.php" class="btn btn-secondary">
            <i class="fas fa-info-circle"></i> Learn More
          </a>
        </div>
      </div>
    </div>

    <!-- Slide 2: Real-time Monitoring -->
    <div class="slide slide-2" style="background-image: url('images/background.png');">
      <div class="slide-overlay"></div>
      <div class="slideshow-text">
        <div class="slide-icon">
          <i class="fas fa-wifi"></i>
        </div>
        <h2>Real-time Monitoring</h2>
        <p style="font-size: 1.2rem;">
          Track waste bin status with live updates and instant visibility across all locations. Know exactly when bins are empty, half-full, or need immediate attention.
        </p>
      </div>
    </div>

    <!-- Slide 3: Automated Alerts -->
    <div class="slide slide-3" style="background-image: url('images/background.png');">
      <div class="slide-overlay"></div>
      <div class="slideshow-text">
        <div class="slide-icon">
          <i class="fas fa-bell"></i>
        </div>
        <h2>Automated Alerts</h2>
        <p style="font-size: 1.2rem;">
          Receive instant notifications when bins reach capacity. Never miss a collection opportunity with intelligent alert management and smart scheduling.
        </p>
      </div>
    </div>

    <!-- Slide 4: Comprehensive Reporting -->
    <div class="slide slide-4" style="background-image: url('images/background.png');">
      <div class="slide-overlay"></div>
      <div class="slideshow-text">
        <div class="slide-icon">
          <i class="fas fa-chart-line"></i>
        </div>
        <h2>Comprehensive Reporting</h2>
        <p style="font-size: 1.2rem;">
          Efficient waste collection analytics and detailed insights. Access comprehensive reports to understand waste patterns and optimize collection strategies across all facilities.
        </p>
      </div>
    </div>

    <!-- Slide 5: Environmental Impact -->
    <div class="slide slide-5" style="background-image: url('images/background.png');">
      <div class="slide-overlay"></div>
      <div class="slideshow-text">
        <div class="slide-icon">
          <i class="fas fa-leaf"></i>
        </div>
        <h2>Environmental Impact</h2>
        <p style="font-size: 1.2rem;">
          Reduce carbon footprint by optimizing collection routes. Prevent overflowing bins and promote a cleaner, healthier environment for sustainable waste management.
        </p>
      </div>
    </div>
  </div>

  <!-- Navigation Arrows -->
  <button class="slide-nav slide-nav-prev" onclick="previousSlide()">
    <i class="fas fa-chevron-left"></i>
  </button>
  <button class="slide-nav slide-nav-next" onclick="nextSlide()">
    <i class="fas fa-chevron-right"></i>
  </button>

  <!-- Slide Indicators -->
  <div class="slide-indicators">
    <span class="indicator active" onclick="goToSlide(0)"></span>
    <span class="indicator" onclick="goToSlide(1)"></span>
    <span class="indicator" onclick="goToSlide(2)"></span>
    <span class="indicator" onclick="goToSlide(3)"></span>
    <span class="indicator" onclick="goToSlide(4)"></span>
  </div>
</section>

    <!-- Bin Status Display -->
    <section class="bin-showcase">
        <div class="animated-bg-circles">
            <div class="circle circle-1"></div>
            <div class="circle circle-2"></div>
            <div class="circle circle-3"></div>
        </div>
        <div class="container">
            <h2 class="section-title">Bin Status Indicators</h2>
            <p class="section-subtitle">Visual representation of waste bin capacity levels with realistic trash animation</p>
            
            <div class="bins-grid">
                <!-- Empty Bin -->
                <div class="bin-card bin-empty">
                    <div class="bin-container">
                        <div class="bin-visual">
                            <div class="bin-fill" style="height: 10%;"></div>
                            <div class="bin-percentage">10%</div>
                        </div>
                    </div>
                    <div class="bin-status-badge">
                        <i class="fas fa-check-circle"></i> Empty
                    </div>
                    <p class="bin-location">Building A - Floor 1</p>
                </div>

                <!-- Half-Full Bin -->
                <div class="bin-card bin-half">
                    <div class="bin-container">
                        <div class="bin-visual">
                            <div class="bin-fill" style="height: 50%;"></div>
                            <div class="bin-percentage">50%</div>
                        </div>
                    </div>
                    <div class="bin-status-badge">
                        <i class="fas fa-exclamation-circle"></i> Half-Full
                    </div>
                    <p class="bin-location">Building A - Floor 2</p>
                </div>

                <!-- Full Bin -->
                <div class="bin-card bin-full">
                    <div class="bin-container">
                        <div class="bin-visual">
                            <div class="bin-fill" style="height: 100%;"></div>
                            <div class="bin-percentage">100%</div>
                        </div>
                    </div>
                    <div class="bin-status-badge">
                        <i class="fas fa-times-circle"></i> Full
                    </div>
                    <p class="bin-location">Building B - Floor 1</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="animated-bg-circles-features">
            <div class="circle circle-f1"></div>
            <div class="circle circle-f2"></div>
            <div class="circle circle-f3"></div>
        </div>
        <div class="container">
            <h2 class="section-title">Why Choose Smart Trashbin?</h2>
            <p class="section-subtitle">Industry-leading features for modern waste management</p>
            
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
                    <h3>Instant Alerts</h3>
                    <p>Get real-time notifications when bins reach capacity, ensuring timely collection and maintenance.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>User-Friendly Interface</h3>
                    <p>Intuitive dashboard designed for both facility managers and janitors to manage waste efficiently.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3>Detailed Reporting</h3>
                    <p>Access comprehensive analytics to understand waste patterns and optimize collection strategies.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Secure & Reliable</h3>
                    <p>Enterprise-grade security ensures your data is protected with 24/7 system availability.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-expand"></i>
                    </div>
                    <h3>Scalable Solution</h3>
                    <p>Seamlessly scale from small facilities to large enterprises with hundreds of bins.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <h3>Easy Integration</h3>
                    <p>Simple setup and integration with existing systems. Get up and running in minutes.</p>
                </div>
            </div>
        </div>
    </section>

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

    <!-- Role Selection Modal -->
    <div id="roleModal" class="modal-overlay">
      <div class="modal-container">
        <div class="modal-content">
          <button class="modal-close" onclick="closeRoleModal()">&times;</button>
          <h2 class="modal-title">Select Your Role</h2>
          <p class="modal-subtitle">Choose how you want to access the system</p>
          
          <div class="role-options">
            <a href="user-login.php" class="role-card role-user">
              <div class="role-icon">
                <i class="fas fa-user-circle"></i>
              </div>
              <h3>User Login</h3>
              <p>Access as a regular user</p>
            </a>
            
            <a href="admin-login.php" class="role-card role-admin">
              <div class="role-icon">
                <i class="fas fa-shield-alt"></i>
              </div>
              <h3>Admin Login</h3>
              <p>Access as an administrator</p>
            </a>
          </div>
        </div>
      </div>
    </div>

  <script src="js/animation.js"></script>
  <script src="js/scroll-progress.js"></script>
  <?php include 'includes/info-modals.php'; ?>
</body>
</html>
