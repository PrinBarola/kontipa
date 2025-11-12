// ============================================
// PREMIUM ANIMATION SYSTEM
// Smart Trashbin - Enhanced Contact Page
// ============================================

(function() {
  'use strict';

  // ============================================
  // SCROLL PROGRESS INDICATOR
  // ============================================
  function initScrollProgress() {
    // Create progress bar element
    const progressBar = document.createElement('div');
    progressBar.className = 'scroll-progress';
    document.body.appendChild(progressBar);

    // Update progress on scroll
    function updateProgress() {
      const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
      const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
      const scrolled = (winScroll / height) * 100;
      progressBar.style.width = scrolled + '%';
    }

    window.addEventListener('scroll', updateProgress, { passive: true });
    updateProgress();
  }

  // ============================================
  // HEADER SCROLL EFFECT
  // ============================================
  function initHeaderScroll() {
    const header = document.querySelector('.header');
    if (!header) return;

    let lastScroll = 0;

    function handleScroll() {
      const currentScroll = window.pageYOffset;

      if (currentScroll > 50) {
        header.classList.add('scrolled');
      } else {
        header.classList.remove('scrolled');
      }

      lastScroll = currentScroll;
    }

    window.addEventListener('scroll', handleScroll, { passive: true });
  }

  // ============================================
  // INTERSECTION OBSERVER - REVEAL ANIMATIONS
  // ============================================
  function initRevealAnimations() {
    const revealElements = document.querySelectorAll(
      'section, .team-card, .contact-info, .contact-form, .contact-item'
    );

    const observerOptions = {
      threshold: 0.15,
      rootMargin: '0px 0px -100px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          // Add visible class to trigger CSS animations
          entry.target.classList.add('visible');
          
          // Add stagger effect for team cards
          if (entry.target.classList.contains('team-card')) {
            const parent = entry.target.parentElement;
            const index = Array.from(parent.children).indexOf(entry.target);
            entry.target.style.transitionDelay = `${index * 0.1}s`;
          }

          // Unobserve after animation
          observer.unobserve(entry.target);
        }
      });
    }, observerOptions);

    revealElements.forEach(el => {
      observer.observe(el);
    });
  }

  // ============================================
  // PARALLAX FLOATING SHAPES
  // ============================================
  function initParallax() {
    const shapes = document.querySelectorAll('.floating-shape');
    if (shapes.length === 0) return;

    function handleParallax() {
      const scrolled = window.pageYOffset;

      shapes.forEach((shape, index) => {
        const speed = 0.5 + (index * 0.2);
        const yPos = -(scrolled * speed);
        shape.style.transform = `translateY(${yPos}px)`;
      });
    }

    window.addEventListener('scroll', handleParallax, { passive: true });
  }

  // ============================================
  // CARD HOVER 3D TILT EFFECT
  // ============================================
  function init3DTilt() {
    const cards = document.querySelectorAll('.team-card, .contact-item');

    cards.forEach(card => {
      card.addEventListener('mousemove', (e) => {
        const rect = card.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        const centerX = rect.width / 2;
        const centerY = rect.height / 2;

        const rotateX = (y - centerY) / 15;
        const rotateY = (centerX - x) / 15;

        card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-8px) scale(1.02)`;
      });

      card.addEventListener('mouseleave', () => {
        card.style.transform = '';
      });
    });
  }

  // ============================================
  // MAGNETIC BUTTON EFFECT
  // ============================================
  function initMagneticButtons() {
    const buttons = document.querySelectorAll('.btn, .btn-primary');

    buttons.forEach(button => {
      button.addEventListener('mousemove', (e) => {
        const rect = button.getBoundingClientRect();
        const x = e.clientX - rect.left - rect.width / 2;
        const y = e.clientY - rect.top - rect.height / 2;

        button.style.transform = `translate(${x * 0.2}px, ${y * 0.2}px)`;
      });

      button.addEventListener('mouseleave', () => {
        button.style.transform = '';
      });
    });
  }

  // ============================================
  // SMOOTH SCROLL FOR ANCHOR LINKS
  // ============================================
  function initSmoothScroll() {
    const links = document.querySelectorAll('a[href^="#"]');

    links.forEach(link => {
      link.addEventListener('click', (e) => {
        const href = link.getAttribute('href');
        
        if (href !== '#' && href !== '#privacy' && href !== '#terms' && href !== '#support') {
          e.preventDefault();
          const target = document.querySelector(href);

          if (target) {
            const offsetTop = target.offsetTop - 80;
            window.scrollTo({
              top: offsetTop,
              behavior: 'smooth'
            });
          }
        }
      });
    });
  }

  // ============================================
  // FORM VALIDATION & ENHANCEMENT
  // ============================================
  function initFormEnhancement() {
    const form = document.querySelector('.contact-form form');
    if (!form) return;

    const inputs = form.querySelectorAll('input, textarea');

    // Add floating label effect
    inputs.forEach(input => {
      input.addEventListener('focus', () => {
        input.parentElement.classList.add('focused');
      });

      input.addEventListener('blur', () => {
        if (!input.value) {
          input.parentElement.classList.remove('focused');
        }
      });
    });

    // Form submission with validation
    form.addEventListener('submit', function(e) {
      let isValid = true;

      inputs.forEach(input => {
        if (input.hasAttribute('required') && !input.value.trim()) {
          isValid = false;
          input.style.borderColor = '#ef4444';
          
          setTimeout(() => {
            input.style.borderColor = '';
          }, 2000);
        }
      });

      if (!isValid) {
        e.preventDefault();
        
        // Scroll to first invalid field
        const firstInvalid = form.querySelector('input:invalid, textarea:invalid');
        if (firstInvalid) {
          firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
          firstInvalid.focus();
        }
      }
    });
  }

  // ============================================
  // FOOTER DYNAMIC TEXT
  // ============================================
  function initFooterText() {
    const footerText = document.getElementById('footerText');
    if (!footerText) return;

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
    
    // Initial text
    footerText.textContent = messages[0];
    
    // Rotate messages every 5 seconds
    setInterval(updateFooterText, 5000);
  }

  // ============================================
  // PAGE LOAD ANIMATION
  // ============================================
  function initPageLoad() {
    document.body.style.opacity = '0';
    
    window.addEventListener('load', () => {
      setTimeout(() => {
        document.body.style.transition = 'opacity 0.6s ease';
        document.body.style.opacity = '1';
      }, 100);
    });
  }

  // ============================================
  // NAVIGATION ACTIVE STATE
  // ============================================
  function initActiveNav() {
    const navLinks = document.querySelectorAll('.nav-link');
    const currentPage = window.location.pathname.split('/').pop() || 'index.php';

    navLinks.forEach(link => {
      const href = link.getAttribute('href');
      if (href === currentPage) {
        link.style.color = 'var(--primary-color)';
        link.style.fontWeight = '700';
      }
    });
  }

  // ============================================
  // LOGO ANIMATION
  // ============================================
  function initLogoAnimation() {
    const logo = document.querySelector('.animated-logo');
    if (!logo) return;

    const lines = logo.querySelectorAll('line');
    
    logo.addEventListener('mouseenter', () => {
      lines.forEach((line, index) => {
        setTimeout(() => {
          line.style.transform = 'scaleY(1.2)';
          line.style.transition = 'transform 0.3s ease';
        }, index * 50);
      });
    });

    logo.addEventListener('mouseleave', () => {
      lines.forEach(line => {
        line.style.transform = 'scaleY(1)';
      });
    });
  }

  // ============================================
  // PERFORMANCE MONITORING
  // ============================================
  function logPerformance() {
    if ('performance' in window) {
      window.addEventListener('load', () => {
        setTimeout(() => {
          const perfData = performance.timing;
          const pageLoadTime = perfData.loadEventEnd - perfData.navigationStart;
          console.log(`⚡ Page loaded in ${pageLoadTime}ms`);
        }, 0);
      });
    }
  }

  // ============================================
  // INITIALIZE ALL ANIMATIONS
  // ============================================
  function init() {
    // Check if DOM is ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', init);
      return;
    }

    console.log('🚀 Initializing Contact Page Premium Animations...');

    // Initialize all features
    initPageLoad();
    initScrollProgress();
    initHeaderScroll();
    initRevealAnimations();
    initParallax();
    init3DTilt();
    initMagneticButtons();
    initSmoothScroll();
    initFormEnhancement();
    initFooterText();
    initActiveNav();
    initLogoAnimation();
    initBackToTop();
    logPerformance();

    console.log('✨ All animations initialized successfully!');
  }

  // Start initialization
  init();

})();

// ============================================
// BACK TO TOP BUTTON
// ============================================
function initBackToTop() {
  const backToTopBtn = document.createElement('button');
  backToTopBtn.className = 'back-to-top';
  backToTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
  document.body.appendChild(backToTopBtn);

  window.addEventListener('scroll', () => {
    backToTopBtn.classList.toggle('show', window.scrollY > 300);
  });

  backToTopBtn.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
}

// ============================================
// ROLE SELECTION MODAL
// ============================================

window.openRoleModal = function(event) {
  event.preventDefault();
  const modal = document.getElementById('roleModal');
  if (modal) {
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
};

window.closeRoleModal = function() {
  const modal = document.getElementById('roleModal');
  if (modal) {
    modal.classList.remove('active');
    document.body.style.overflow = '';
  }
};

// Close modal when clicking outside
document.addEventListener('DOMContentLoaded', function() {
  const modal = document.getElementById('roleModal');
  if (modal) {
    modal.addEventListener('click', function(event) {
      if (event.target === modal) {
        closeRoleModal();
      }
    });
  }
  
  // Close modal on ESC key
  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
      closeRoleModal();
    }
  });
});

