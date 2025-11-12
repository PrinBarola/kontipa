<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Smart Trashbin | Intelligent Waste Management</title>

    <!-- Match the same styling setup as contact.php -->
    <link rel="stylesheet" href="css/contact.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* Optional: add a small custom thank-you section style */
        .thankyou-section {
            min-height: 80vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            background: #f0fdf4;
            color: #166534;
            padding: 50px 20px;
        }
        .thankyou-section h1 {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        .thankyou-section p {
            font-size: 1.2rem;
            margin-bottom: 30px;
        }
        .thankyou-section a.btn {
            background-color: #16a34a;
            color: #fff;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }
        .thankyou-section a.btn:hover {
            background-color: #15803d;
        }
    </style>
</head>

<body>
    <!-- Reuse header navigation (optional) -->
    <header class="header">
        <div class="header-container">
            <a href="index.php" class="logo-section">
                <div class="logo-wrapper">
                    <svg class="animated-logo" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                        <rect x="30" y="35" width="40" height="50" rx="6" fill="#16a34a"/>
                        <rect x="25" y="30" width="50" height="5" fill="#15803d"/>
                        <rect x="40" y="20" width="20" height="8" rx="2" fill="#22c55e"/>
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
        </div>
    </header>

    <!-- Thank You Section -->
    <section class="thankyou-section">
        <h1><i class="fas fa-check-circle"></i> Thank You!</h1>
        <p>Your message has been successfully sent. Our team will get back to you soon.</p>
        <a href="contact.php" class="btn"><i class="fas fa-arrow-left"></i> Go Back</a>
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
    </footer>
</body>
</html>
