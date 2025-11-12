// ============================================
// PREMIUM ANIMATION SYSTEM
// Smart Trashbin - Enhanced Features Page
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
      'section, .overview-card, .process-step, .benefit-card, .tech-item, .feature-item'
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
          
          // Add stagger effect for cards
          if (entry.target.classList.contains('overview-card') ||
              entry.target.classList.contains('process-step') ||
              entry.target.classList.contains('benefit-card') ||
              entry.target.classList.contains('tech-item') ||
              entry.target.classList.contains('feature-item')) {
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
    const cards = document.querySelectorAll('.overview-card, .process-step, .benefit-card, .tech-item, .feature-item');

    cards.forEach(card => {
      card.addEventListener('mousemove', (e) => {
        const rect = card.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        const centerX = rect.width / 2;
        const centerY = rect.height / 2;

        const rotateX = (y - centerY) / 10;
        const rotateY = (centerX - x) / 10;

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
    const buttons = document.querySelectorAll('.btn');

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
  // COUNTER ANIMATION (for stats if added)
  // ============================================
  function animateCounter(element, target, duration = 2000) {
    let start = 0;
    const increment = target / (duration / 16);
    
    function updateCounter() {
      start += increment;
      if (start < target) {
        element.textContent = Math.floor(start).toLocaleString();
        requestAnimationFrame(updateCounter);
      } else {
        element.textContent = target.toLocaleString();
      }
    }
    
    updateCounter();
  }

  // ============================================
  // TYPING EFFECT FOR HERO SUBTITLE
  // ============================================
  function initTypingEffect() {
    const subtitle = document.querySelector('.hero-subtitle');
    if (!subtitle) return;

    const text = subtitle.textContent;
    subtitle.textContent = '';
    subtitle.style.opacity = '1';

    let index = 0;
    function type() {
      if (index < text.length) {
        subtitle.textContent += text.charAt(index);
        index++;
        setTimeout(type, 30);
      }
    }

    // Start typing after a short delay
    setTimeout(type, 500);
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
      
      // Initial text
      footerText.textContent = messages[0];
      
      // Rotate messages every 5 seconds
      setInterval(updateFooterText, 5000);
    }
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
  // ICON PULSE ANIMATION
  // ============================================
  function initIconPulse() {
    const icons = document.querySelectorAll('.card-icon, .step-icon, .benefit-icon, .tech-icon, .feature-icon');
    
    icons.forEach(icon => {
      setInterval(() => {
        icon.style.animation = 'pulse 0.5s ease';
        setTimeout(() => {
          icon.style.animation = '';
        }, 500);
      }, 5000 + Math.random() * 3000);
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
          console.log(`⚡ Features Page loaded in ${pageLoadTime}ms`);
        }, 0);
      });
    }
  }

  // ============================================
  // SECTION ENTRANCE ANIMATIONS
  // ============================================
  function initSectionAnimations() {
    const sections = document.querySelectorAll('section');
    
    const sectionObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }
      });
    }, {
      threshold: 0.1
    });

    sections.forEach(section => {
      section.style.opacity = '0';
      section.style.transform = 'translateY(30px)';
      section.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
      sectionObserver.observe(section);
    });
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

    console.log('🚀 Initializing Premium Features Page Animations...');

    // Initialize all features
    initPageLoad();
    initScrollProgress();
    initHeaderScroll();
    initRevealAnimations();
    initParallax();
    init3DTilt();
    initMagneticButtons();
    initSmoothScroll();
    initFooterText();
    initActiveNav();
    initBackToTop();
    initIconPulse();
    initSectionAnimations();
    logPerformance();

    // Optional: Uncomment if you want typing effect
    // initTypingEffect();

    console.log('✨ All Features page animations initialized successfully!');
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
