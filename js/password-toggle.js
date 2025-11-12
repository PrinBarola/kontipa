/**
 * Password Visibility Toggle - ULTRA SIMPLE VERSION
 * Immediately attaches handlers to all password toggle buttons
 * No dependencies, super reliable
 */

console.log('[password-toggle.js] Script loading at:', new Date().toLocaleTimeString());

// INSTANT execution - don't wait for anything
(function setupPasswordToggle() {
  console.log('[password-toggle.js] Setting up password toggles NOW');

  function attachToggle(btn) {
    // Get the icon inside the button
    const icon = btn.querySelector('i');
    const targetId = btn.getAttribute('data-target');
    
    if (!icon) {
      console.error('[toggle] Button has no icon!', btn);
      return;
    }
    
    if (!targetId) {
      console.error('[toggle] Button has no data-target!', btn);
      return;
    }

    // Remove any existing onclick to prevent duplicates
    btn.onclick = null;

    // Attach click handler using addEventListener (more reliable)
    btn.addEventListener('click', function(event) {
      event.preventDefault();
      event.stopPropagation();
      event.stopImmediatePropagation();
      
      console.log('[toggle] Clicked! Finding input:', targetId);
      
      // Find the password input
      const input = document.querySelector(targetId);
      if (!input) {
        console.error('[toggle] Could not find input:', targetId);
        return false;
      }

      console.log('[toggle] Current type:', input.type);

      // Toggle
      if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fa-solid fa-eye-slash';
        console.log('[toggle] ✓ SHOWING password');
      } else {
        input.type = 'password';
        icon.className = 'fa-solid fa-eye';
        console.log('[toggle] ✓ HIDING password');
      }

      return false;
    }, false);

    console.log('[toggle] ✓ Attached to button for', targetId);
  }

  function initializeToggles() {
    // Find all toggle buttons and attach handlers NOW
    const buttons = document.querySelectorAll('.password-toggle-btn');
    console.log('[password-toggle.js] Found', buttons.length, 'buttons');
    
    if (buttons.length === 0) {
      console.warn('[password-toggle.js] No password toggle buttons found yet');
      return false;
    }

    let attached = 0;
    buttons.forEach((btn, i) => {
      // Skip if already has a listener
      if (btn.__passwordToggleAttached) {
        console.log('[toggle] Button', i, 'already has handler, skipping');
        return;
      }
      console.log('[toggle] Processing button', i);
      attachToggle(btn);
      btn.__passwordToggleAttached = true;
      attached++;
    });

    console.log('[password-toggle.js] Attached handlers to', attached, 'buttons');
    return attached > 0;
  }

  // Try immediately
  if (!initializeToggles()) {
    // If no buttons found, try again after a tiny delay
    setTimeout(() => {
      console.log('[password-toggle.js] First attempt failed, retrying after 50ms');
      if (!initializeToggles()) {
        // Try one more time
        setTimeout(() => {
          console.log('[password-toggle.js] Second attempt failed, retrying after 150ms');
          initializeToggles();
        }, 150);
      }
    }, 50);
  }

  // Also setup a MutationObserver to catch dynamically added buttons
  try {
    const observer = new MutationObserver((mutations) => {
      // Check if any new password-toggle-btn buttons were added
      let hasNewButtons = false;
      mutations.forEach(mutation => {
        if (mutation.addedNodes.length > 0) {
          mutation.addedNodes.forEach(node => {
            if (node.nodeType === Node.ELEMENT_NODE) {
              if (node.classList?.contains('password-toggle-btn')) {
                hasNewButtons = true;
              } else if (node.querySelectorAll) {
                const newButtons = node.querySelectorAll('.password-toggle-btn:not([data-toggle-attached])');
                if (newButtons.length > 0) {
                  hasNewButtons = true;
                }
              }
            }
          });
        }
      });

      if (hasNewButtons) {
        console.log('[password-toggle.js] New toggle buttons detected, re-initializing');
        initializeToggles();
      }
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true
    });
    console.log('[password-toggle.js] MutationObserver active');
  } catch (e) {
    console.warn('[password-toggle.js] MutationObserver not available:', e);
  }

  // Export for manual use
  window.togglePasswordViz = function() {
    console.log('[password-toggle.js] Manual re-initialization called');
    initializeToggles();
  };
})();
