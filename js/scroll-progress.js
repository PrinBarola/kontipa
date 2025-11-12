// Scroll progress bar script
// Updates element #scrollProgress width based on scroll position
(function () {
  const progressEl = document.getElementById('scrollProgress');
  if (!progressEl) return;

  let ticking = false;

  function update() {
    const doc = document.documentElement;
    const scrollTop = (window.pageYOffset || doc.scrollTop) || 0;
    const scrollHeight = Math.max(doc.scrollHeight, document.body.scrollHeight || 0);
    const clientHeight = window.innerHeight || doc.clientHeight;
    const total = Math.max(scrollHeight - clientHeight, 1);
    const pct = Math.min(100, Math.max(0, (scrollTop / total) * 100));
    progressEl.style.width = pct + '%';
    ticking = false;
  }

  function onScroll() {
    if (!ticking) {
      window.requestAnimationFrame(update);
      ticking = true;
    }
  }

  // handle resize / content changes
  let resizeTimeout;
  function onResize() {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => update(), 120);
  }

  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', onResize);
  // initial update
  document.addEventListener('DOMContentLoaded', update);
  // also try after load
  window.addEventListener('load', update);
})();
