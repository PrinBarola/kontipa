// ============================================
// SMART TRASHBIN - PREMIUM INDEX PAGE ANIMATIONS
// Enhanced with about.js animation patterns
// ============================================

(function() {
  'use strict';

  // ============================================
  // SCROLL PROGRESS INDICATOR
  // ============================================
  function initScrollProgress() {
    const progressBar = document.createElement('div');
    progressBar.className = 'scroll-progress';
    document.body.appendChild(progressBar);

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

    function handleScroll() {
      const currentScroll = window.pageYOffset;

      if (currentScroll > 50) {
        header.classList.add('scrolled');
      } else {
        header.classList.remove('scrolled');
      }
    }

    window.addEventListener('scroll', handleScroll, { passive: true });
  }

  // ============================================
  // SLIDESHOW FUNCTIONALITY
  // ============================================
  let currentSlide = 0;
  const slides = document.querySelectorAll('.slide');
  const indicators = document.querySelectorAll('.indicator');
  const totalSlides = slides.length;

  function showSlide(index) {
    slides.forEach((slide, i) => {
      slide.classList.remove('active');
      indicators[i].classList.remove('active');
    });

    currentSlide = (index + totalSlides) % totalSlides;
    slides[currentSlide].classList.add('active');
    indicators[currentSlide].classList.add('active');
  }

  window.nextSlide = function() {
    showSlide(currentSlide + 1);
  };

  window.previousSlide = function() {
    showSlide(currentSlide - 1);
  };

  window.goToSlide = function(index) {
    showSlide(index);
  };

  // Auto-play slideshow
  function initSlideshow() {
    if (slides.length === 0) return;
    
    setInterval(() => {
      nextSlide();
    }, 5000);
  }

  // ============================================
  // INTERSECTION OBSERVER - REVEAL ANIMATIONS
  // ============================================
  function initRevealAnimations() {
    const revealElements = document.querySelectorAll(
      'section, .bin-card, .feature-item'
    );

    const observerOptions = {
      threshold: 0.15,
      rootMargin: '0px 0px -100px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          
          // Add stagger effect for cards
          if (entry.target.classList.contains('bin-card') ||
              entry.target.classList.contains('feature-item')) {
            const parent = entry.target.parentElement;
            const index = Array.from(parent.children).indexOf(entry.target);
            entry.target.style.transitionDelay = `${index * 0.1}s`;
          }

          observer.unobserve(entry.target);
        }
      });
    }, observerOptions);

    revealElements.forEach(el => {
      observer.observe(el);
    });
  }

  // ============================================
  // PARALLAX FLOATING CIRCLES
  // ============================================
  function initParallax() {
    const circles = document.querySelectorAll('.circle');

    function handleParallax() {
      const scrolled = window.pageYOffset;

      circles.forEach((circle, index) => {
        const speed = 0.5 + (index * 0.2);
        const yPos = -(scrolled * speed);
        circle.style.transform = `translateY(${yPos}px)`;
      });
    }

    window.addEventListener('scroll', handleParallax, { passive: true });
  }

  // ============================================
  // CARD HOVER 3D TILT EFFECT
  // ============================================
  function init3DTilt() {
    const cards = document.querySelectorAll('.bin-card, .feature-item');

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
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';

    navLinks.forEach(link => {
      const href = link.getAttribute('href');
      if (href === currentPage) {
        link.style.color = 'var(--primary-color)';
        link.style.fontWeight = '700';
      }
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
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', init);
      return;
    }

    console.log('🚀 Initializing Premium Animations...');

    // Initialize all features
    initPageLoad();
    initScrollProgress();
    initHeaderScroll();
    initSlideshow();
    initRevealAnimations();
    initParallax();
    init3DTilt();
    initMagneticButtons();
    initSmoothScroll();
    initFooterText();
    initActiveNav();
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

