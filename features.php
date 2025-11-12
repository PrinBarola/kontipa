<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Features - Smart Trashbin System</title>
    <link rel="stylesheet" href="css/features.css">
    <link rel="stylesheet" href="css/header.css">
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

    <!-- Hero Section -->
    <section class="hero hero-about">
        <div class="hero-content">
            <h2 class="hero-title">Advanced Features</h2>
            <p class="hero-subtitle">Explore the cutting-edge technology behind Smart Trashbin and how it revolutionizes waste management</p>
        </div>
        <div class="hero-background">
            <div class="floating-shape shape-1"></div>
            <div class="floating-shape shape-2"></div>
            <div class="floating-shape shape-3"></div>
        </div>
    </section>

    <!-- System Overview Section -->
    <section class="overview">
        <div class="container">
            <h2 class="section-title">Complete System Architecture</h2>
            <p class="section-subtitle">Understanding the Smart Trashbin ecosystem</p>
            
            <div class="overview-grid">
                <div class="overview-card">
                    <div class="card-icon">
                        <i class="fas fa-microchip"></i>
                    </div>
                    <h3>Hardware Layer</h3>
                    <p>IoT sensors and Arduino microcontrollers deployed in waste bins continuously monitor fill levels with precision ultrasonic and weight-based sensors.</p>
                </div>
                <div class="overview-card">
                    <div class="card-icon">
                        <i class="fas fa-wifi"></i>
                    </div>
                    <h3>Connectivity Layer</h3>
                    <p>IoT devices communicate via WiFi and cellular networks with encrypted protocols to ensure real-time data transmission and system security.</p>
                </div>
                <div class="overview-card">
                    <div class="card-icon">
                        <i class="fas fa-cloud"></i>
                    </div>
                    <h3>Cloud Processing</h3>
                    <p>Data is processed on secure cloud servers using advanced algorithms to calculate capacity, predict maintenance needs, and generate insights.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How Sensors Work Section -->
    <section class="how-it-works">
        <div class="container">
            <h2 class="section-title">Sensor Technology & Signal Processing</h2>
            <div class="process-grid">
                <div class="process-step">
                    <div class="step-number">01</div>
                    <div class="step-icon">
                        <i class="fas fa-tape"></i>
                    </div>
                    <h3>Ultrasonic Distance Sensors</h3>
                    <p>Mounted at the top of bins, these sensors measure the distance from the lid to waste surface, calculating fill percentage in real-time without contact.</p>
                </div>
                <div class="process-step">
                    <div class="step-number">02</div>
                    <div class="step-icon">
                        <i class="fas fa-weight"></i>
                    </div>
                    <h3>Load Cell Sensors</h3>
                    <p>Precision weight sensors track the actual mass of waste in bins, providing accurate capacity measurements and detecting unusual weight patterns.</p>
                </div>
                <div class="process-step">
                    <div class="step-number">03</div>
                    <div class="step-icon">
                        <i class="fas fa-temperature-high"></i>
                    </div>
                    <h3>Environmental Sensors</h3>
                    <p>Temperature and humidity sensors monitor bin conditions, alerting staff to potential hazards or decomposition issues in organic waste.</p>
                </div>
                <div class="process-step">
                    <div class="step-number">04</div>
                    <div class="step-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3>Signal Transmission</h3>
                    <p>Arduino boards process sensor data and transmit encrypted signals every 5-15 minutes to cloud servers via WiFi or cellular networks.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Arduino Integration Section -->
    <section class="benefits">
        <div class="container">
            <h2 class="section-title">Arduino & Hardware Integration</h2>
            <div class="benefits-grid">
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-microchip"></i>
                    </div>
                    <h3>Arduino Microcontroller</h3>
                    <p>The brain of each bin, Arduino boards collect sensor inputs, process data locally, and communicate with our cloud infrastructure securely.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-wifi"></i>
                    </div>
                    <h3>WiFi/Cellular Modules</h3>
                    <p>IoT communication modules (WiFi or 4G/5G) enable real-time data transmission from bins to servers with redundancy for reliability.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-battery-full"></i>
                    </div>
                    <h3>Power Management</h3>
                    <p>Long-life batteries or solar charging systems power IoT devices for 6-12 months, with low-power modes to extend operational life.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Secure Protocols</h3>
                    <p>Data transmission uses encrypted protocols (HTTPS, MQTT over SSL) ensuring that all signals from bins to servers are protected from tampering.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <h3>Firmware Updates</h3>
                    <p>Remote firmware updates enable us to improve sensor accuracy, add new features, and patch security vulnerabilities without manual intervention.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-link"></i>
                    </div>
                    <h3>Modular Design</h3>
                    <p>Each component is modular, allowing easy replacement of sensors, modules, or batteries without removing the entire bin system.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Data Flow & Dashboard Update Section -->
    <section class="technology">
        <div class="container">
            <h2 class="section-title">Real-Time Dashboard Updates</h2>
            <p class="section-subtitle">How sensor signals update the admin and janitor dashboards</p>
            <div class="tech-grid">
                <div class="tech-item">
                    <div class="tech-icon">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <h3>Sensor Data Transmission</h3>
                    <p>Every 5-15 minutes, Arduino devices transmit sensor readings (distance, weight, temperature) to our cloud API endpoints securely.</p>
                </div>
                <div class="tech-item">
                    <div class="tech-icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <h3>Cloud Data Processing</h3>
                    <p>Server processes raw sensor data, calculates fill percentages (0-100%), and updates the MySQL database with current bin status in real-time.</p>
                </div>
                <div class="tech-item">
                    <div class="tech-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h3>Smart Notifications</h3>
                    <p>When bins reach 75% (threshold alert), 100% (full), or drop below 10% (emptied), the system automatically sends notifications to assigned janitors.</p>
                </div>
                <div class="tech-item">
                    <div class="tech-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Dashboard Refresh</h3>
                    <p>Admin and janitor dashboards update via AJAX every 30 seconds, displaying live bin status, capacity, location, and collection history.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Feature Highlights Section -->
    <section class="features">
        <div class="container">
            <h2 class="section-title">Key Features</h2>
            <p class="section-subtitle">Comprehensive capabilities for modern waste management</p>
            
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
                    <h3>Real-Time Monitoring</h3>
                    <p>Live capacity updates every 5 minutes with sensor-based accuracy. Know exactly which bins need attention right now.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h3>Instant Alerts</h3>
                    <p>Automatic notifications when bins are 75% full, completely full, or have been emptied. Janitors know exactly what to do.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-map-location-dot"></i>
                    </div>
                    <h3>GPS Location Tracking</h3>
                    <p>Every bin's location is recorded and mapped, helping janitors quickly locate and service nearby bins efficiently.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <h3>Historical Analytics</h3>
                    <p>Track bin usage patterns over time to predict peak collection periods and optimize maintenance schedules.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Team Management</h3>
                    <p>Assign bins to specific janitors, track their work, and generate performance reports for payroll and accountability.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Mobile App Access</h3>
                    <p>Full access to all features via mobile-responsive interface. Check bin status and manage tasks from anywhere.</p>
                </div>
            </div>
        </div>
    </section>

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

    <script src="js/features.js"></script>
    <script src="js/scroll-progress.js"></script>
    <?php include 'includes/info-modals.php'; ?>
</body>
</html>
            