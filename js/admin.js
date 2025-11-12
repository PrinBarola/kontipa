// ============================================
// SMART TRASHBIN - ADMIN SHARED FUNCTIONALITY
// Handles common features across admin pages
// ============================================

(function() {
  'use strict';

  // ============================================
  // SCROLL PROGRESS INDICATOR
  // ============================================
  function initScrollProgress() {
    const progressBar = document.querySelector('.scroll-progress-bar');
    if (!progressBar) return;

    let ticking = false;

    function update() {
      const windowHeight = window.innerHeight;
      const documentHeight = document.documentElement.scrollHeight - windowHeight;
      const scrolled = window.scrollY;
      const progress = (scrolled / documentHeight) * 100;
      progressBar.style.width = `${progress}%`;
      ticking = false;
    }

    function onScroll() {
      if (!ticking) {
        window.requestAnimationFrame(update);
        ticking = true;
      }
    }

    // Handle resize
    let resizeTimeout;
    function onResize() {
      clearTimeout(resizeTimeout);
      resizeTimeout = setTimeout(() => update(), 100);
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onResize, { passive: true });
    
    // Initial update
    update();
  }

  // Initialize when DOM is ready
  function init() {
    initScrollProgress();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();