<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Contact Smart Trashbin - Get in touch with our team for intelligent waste management solutions. Reach out via email, phone, or visit our office.">
    <meta name="keywords" content="contact, smart trashbin, waste management, IoT, support, inquiry">
    <title>Contact Us - Smart Trashbin | Intelligent Waste Management</title>
    <link rel="stylesheet" href="css/contact.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div id="scrollProgress" class="scroll-progress"></div>
    <!-- Navigation Header -->
    <header class="header">
        <div class="header-container">
            <a href="index.php" class="logo-section">
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
            </a>
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
    <section class="hero-contact">
        <div class="hero-content">
            <h2 class="hero-title">Get In Touch</h2>
            <p class="hero-subtitle">Have questions? We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
        </div>
        <div class="hero-background">
            <div class="floating-shape shape-1"></div>
            <div class="floating-shape shape-2"></div>
            <div class="floating-shape shape-3"></div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section">
        <div class="container">
            <div class="contact-container">
                <!-- Contact Information -->
                <div class="contact-info">
                    <h2>Contact Information</h2>
                    <p>Fill out the form and our team will get back to you within 24 hours.</p>
                    
                    <div class="contact-details">
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-text">
                                <h3>Email</h3>
                                <p>support@smarttrashbin.com</p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="contact-text">
                                <h3>Phone</h3>
                                <p>+1 (555) 123-4567</p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="contact-text">
                                <h3>Office</h3>
                                <p>Batangas State University</p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="contact-text">
                                <h3>Business Hours</h3>
                                <p>Mon-Fri: 7:00 AM - 7:00 PM</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Form -->
                <div class="contact-form">
                    <h3>Send us a Message</h3>
                    <p>We'll get back to you as soon as possible.</p>
                    
                    <form action="send-email.php" method="POST">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" placeholder="Enter your name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" placeholder="Enter your email address" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" placeholder="How can we help?" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" placeholder="Tell us more about your inquiry..." required></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i>
                            Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="team-section">
  <div class="container">
    <h2 class="section-title">Meet Our Team</h2>
    <p class="section-description">
      Our dedicated team of professionals working to make waste management smarter and more sustainable.
    </p>

    <div class="team-grid">
      <div class="team-card">
        <div class="team-avatar">
          <img src="images/barola.jpeg" alt="Barola, Prin M.">
        </div>
        <h3>Barola, Prin M.</h3>
        <p class="role">Lead Developer</p>
        <p class="description"><i class="fas fa-envelope"></i> p.barola@smarttrashbin.com</p>
        <p class="description"><i class="fas fa-phone"></i> +63 912 345 6789</p>
        <p class="description"><i class="fab fa-facebook"></i> <a href="https://facebook.com/prin.barola">facebook.com/prin.barola</a></p>
      </div>

      <div class="team-card">
        <div class="team-avatar">
          <img src="images/espaldon.jpeg" alt="Espaldon, Steven Lenard E.">
        </div>
        <h3>Espaldon, Steven Lenard E.</h3>
        <p class="role">Full Stack Developer</p>
        <p class="description"><i class="fas fa-envelope"></i> s.espaldon@smarttrashbin.com</p>
        <p class="description"><i class="fas fa-phone"></i> +63 923 456 7890</p>
        <p class="description"><i class="fab fa-facebook"></i> <a href="https://facebook.com/steven.espaldon">facebook.com/steven.espaldon</a></p>
      </div>

      <div class="team-card">
        <div class="team-avatar">
          <img src="images/fruelda.jpeg" alt="Fruelda, Michael Frodge F.">
        </div>
        <h3>Fruelda, Michael Frodge F.</h3>
        <p class="role">Backend Developer</p>
        <p class="description"><i class="fas fa-envelope"></i> m.fruelda@smarttrashbin.com</p>
        <p class="description"><i class="fas fa-phone"></i> +63 934 567 8901</p>
        <p class="description"><i class="fab fa-facebook"></i> <a href="https://facebook.com/michael.fruelda">facebook.com/michael.fruelda</a></p>
      </div>

      <div class="team-card">
        <div class="team-avatar">
          <img src="images/noblefranca.jpg" alt="Noblefranca, Joven M.">
        </div>
        <h3>Noblefranca, Joven M.</h3>
        <p class="role">UI/UX Designer</p>
        <p class="description"><i class="fas fa-envelope"></i> j.noblefranca@smarttrashbin.com</p>
        <p class="description"><i class="fas fa-phone"></i> +63 945 678 9012</p>
        <p class="description"><i class="fab fa-facebook"></i> <a href="https://facebook.com/joven.noblefranca">facebook.com/joven.noblefranca</a></p>
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
       <span id="footerText" class="footer-text"></span>
      <p class="footer-copyright">&copy; 2025 Smart Trashbin. All rights reserved.</p>
    </div>
  </div>

    <!-- Added Role Selection Modal for login button functionality -->
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

    <script src="js/contact.js"></script>
    <script src="js/scroll-progress.js"></script>

    <?php include 'includes/info-modals.php'; ?>

    <?php
    // PHP Contact Form Handler
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = htmlspecialchars(trim($_POST['name']));
        $email = htmlspecialchars(trim($_POST['email']));
        $subject = htmlspecialchars(trim($_POST['subject']));
        $message = htmlspecialchars(trim($_POST['message']));
        
        // Validate inputs
        if (!empty($name) && !empty($email) && !empty($subject) && !empty($message)) {
            // Email configuration
            $to = "support@smarttrashbin.com";
            $email_subject = "Contact Form: " . $subject;
            $email_body = "Name: $name\n";
            $email_body .= "Email: $email\n\n";
            $email_body .= "Message:\n$message\n";
            
            $headers = "From: $email\r\n";
            $headers .= "Reply-To: $email\r\n";
            
            // Send email
            if (mail($to, $email_subject, $email_body, $headers)) {
                echo "<script>alert('Thank you! Your message has been sent successfully.');</script>";
            } else {
                echo "<script>alert('Sorry, there was an error sending your message. Please try again.');</script>";
            }
        } else {
            echo "<script>alert('Please fill in all required fields.');</script>";
        }
    }
    ?>
</body>
</html>
