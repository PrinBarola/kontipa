<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Smart Trashbin - Intelligent Waste Management</title>
    <link rel="stylesheet" href="css/about.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div id="scrollProgress" class="scroll-progress"></div>
    <!-- Navigation Header - Same header as index.php with center nav -->
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
            <h2 class="hero-title">About Smart Trashbin</h2>
            <p class="hero-subtitle">Revolutionizing waste management through intelligent IoT technology and real-time monitoring</p>
        </div>
        <div class="hero-background">
            <div class="floating-shape shape-1"></div>
            <div class="floating-shape shape-2"></div>
            <div class="floating-shape shape-3"></div>
        </div>
    </section>

    <!-- Mission Section -->
    <section class="mission">
        <div class="container">
            <h2 class="section-title">Our Mission</h2>
            <div class="mission-content">
                <p>At Smart Trashbin, we believe that effective waste management is fundamental to creating cleaner, healthier environments. Our mission is to empower organizations and communities with intelligent technology that transforms how waste is monitored, managed, and disposed of.</p>
                <p>We're committed to reducing manual effort, preventing overflowing bins, and promoting environmental responsibility through accurate, real-time monitoring and user-friendly reporting.</p>
            </div>
        </div>
    </section>

    <!-- Core Values Section -->
    <section class="values">
        <div class="container">
            <h2 class="section-title">Core Values</h2>
            <div class="values-grid">
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <h3>Innovation</h3>
                    <p>We continuously innovate to bring cutting-edge IoT and AI solutions that make waste management smarter and more efficient.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <h3>Sustainability</h3>
                    <p>Environmental responsibility is at our core. We help organizations reduce waste and promote sustainable practices.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>User-Centric</h3>
                    <p>We design with users in mind, creating intuitive interfaces that work for everyone from facility managers to janitors.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h3>Reliability</h3>
                    <p>Enterprise-grade security and 24/7 system reliability ensure your waste management never misses a beat.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3>Transparency</h3>
                    <p>Real-time data and comprehensive reporting give you complete visibility into your waste management operations.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3>Partnership</h3>
                    <p>We work closely with our clients to understand their needs and deliver solutions that truly make a difference.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works">
        <div class="container">
            <h2 class="section-title">How It Works</h2>
            <div class="process-grid">
                <div class="process-step">
                    <div class="step-number">01</div>
                    <div class="step-icon">
                        <i class="fas fa-microchip"></i>
                    </div>
                    <h3>Smart Sensors</h3>
                    <p>IoT sensors installed in waste bins continuously monitor fill levels and send real-time data to our cloud platform.</p>
                </div>
                <div class="process-step">
                    <div class="step-number">02</div>
                    <div class="step-icon">
                        <i class="fas fa-cloud"></i>
                    </div>
                    <h3>Cloud Processing</h3>
                    <p>Our intelligent algorithms process data in real-time, analyzing patterns and predicting when bins need attention.</p>
                </div>
                <div class="process-step">
                    <div class="step-number">03</div>
                    <div class="step-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h3>Smart Notifications</h3>
                    <p>Users and janitors receive instant alerts and can access a comprehensive dashboard to manage collections efficiently.</p>
                </div>
                <div class="process-step">
                    <div class="step-number">04</div>
                    <div class="step-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Analytics & Insights</h3>
                    <p>Access detailed reports and analytics to optimize waste management strategies and reduce operational costs.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class="benefits">
        <div class="container">
            <h2 class="section-title">Key Benefits</h2>
            <div class="benefits-grid">
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-hourglass-end"></i>
                    </div>
                    <h3>Reduce Manual Effort</h3>
                    <p>Eliminate unnecessary bin checks and guesswork. Janitors know exactly which bins need attention, saving time and resources.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h3>Prevent Overflowing</h3>
                    <p>Real-time monitoring ensures bins are emptied before they overflow, maintaining cleanliness and hygiene standards.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <h3>Cost Savings</h3>
                    <p>Optimize collection routes and reduce unnecessary trips, leading to significant operational cost reductions.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-earth-americas"></i>
                    </div>
                    <h3>Environmental Impact</h3>
                    <p>Promote sustainability by optimizing waste collection and reducing unnecessary transportation emissions.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <h3>Data-Driven Insights</h3>
                    <p>Access detailed analytics and reports to understand waste patterns and make informed management decisions.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-expand-alt"></i>
                    </div>
                    <h3>Scalability</h3>
                    <p>Our system scales effortlessly from small facilities to large enterprises with hundreds of bins.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Technology Stack Section -->
    <section class="technology">
        <div class="container">
            <h2 class="section-title">Our Technology</h2>
            <p class="section-subtitle">Built on cutting-edge IoT and cloud infrastructure</p>
            <div class="tech-grid">
                <div class="tech-item">
                    <div class="tech-icon">
                        <i class="fas fa-wifi"></i>
                    </div>
                    <h3>IoT Connectivity</h3>
                    <p>Advanced wireless sensors with long-range connectivity and low power consumption for reliable monitoring.</p>
                </div>
                <div class="tech-item">
                    <div class="tech-icon">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h3>AI & Machine Learning</h3>
                    <p>Intelligent algorithms that learn from patterns and predict bin capacity needs with high accuracy.</p>
                </div>
                <div class="tech-item">
                    <div class="tech-icon">
                        <i class="fas fa-server"></i>
                    </div>
                    <h3>Cloud Infrastructure</h3>
                    <p>Scalable cloud platform with 99.9% uptime guarantee and enterprise-grade security protocols.</p>
                </div>
                <div class="tech-item">
                    <div class="tech-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Mobile & Web Apps</h3>
                    <p>Responsive applications accessible on any device, providing real-time access to bin status and alerts.</p>
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

    <script src="js/about.js"></script>
    <script src="js/scroll-progress.js"></script>
    <?php include 'includes/info-modals.php'; ?>
    
</body>
</html>
