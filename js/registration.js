// ============================================
// SMART TRASHBIN - REGISTRATION PAGE SCRIPTS
// Full updated file with AJAX submission to register-handler.php
// ============================================

(function() {
  'use strict';

  // SCROLL PROGRESS
  function initScrollProgress() {
    const progressBar = document.createElement('div');
    progressBar.className = 'scroll-progress';
    document.body.appendChild(progressBar);
    function updateProgress() {
      const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
      const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
      const scrolled = height > 0 ? (winScroll / height) * 100 : 0;
      progressBar.style.width = scrolled + '%';
    }
    window.addEventListener('scroll', updateProgress, { passive: true });
    updateProgress();
  }

  // HEADER SCROLL EFFECT
  function initHeaderScroll() {
    const header = document.querySelector('.header');
    if (!header) return;
    function handleScroll() {
      const currentScroll = window.pageYOffset;
      if (currentScroll > 50) header.classList.add('scrolled'); else header.classList.remove('scrolled');
    }
    window.addEventListener('scroll', handleScroll, { passive: true });
  }

  // PASSWORD TOGGLE
  function initPasswordToggle() {
    const toggleButtons = document.querySelectorAll('.toggle-password');
    toggleButtons.forEach(toggle => {
      const input = toggle.parentElement.querySelector('input');
      toggle.addEventListener('click', (e) => {
        e.preventDefault();
        const icon = toggle.querySelector('i');
        if (!input) return;
        if (input.type === 'password') {
          input.type = 'text';
          icon.classList.remove('fa-eye');
          icon.classList.add('fa-eye-slash');
        } else {
          input.type = 'password';
          icon.classList.remove('fa-eye-slash');
          icon.classList.add('fa-eye');
        }
      });
    });
  }

  // FORM VALIDATION & SUBMISSION
  function initFormValidation() {
    const registrationForm = document.getElementById('registrationForm');
    if (!registrationForm) return;

    const firstNameInput = document.getElementById('firstName');
    const lastNameInput = document.getElementById('lastName');
    const emailInput = document.getElementById('regEmail');
    const phoneInput = document.getElementById('phone');
    const passwordInput = document.getElementById('regPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');

    // local helpers same as before...
    function setInputError(input, errorElement, message) {
      if (!input) return;
      input.classList.add('error'); input.classList.remove('success');
      if (errorElement) { errorElement.textContent = message; errorElement.classList.add('show'); }
    }
    function setInputSuccess(input, errorElement) {
      if (!input) return;
      input.classList.remove('error'); input.classList.add('success');
      if (errorElement) { errorElement.classList.remove('show'); errorElement.textContent = ''; }
    }

    function validateFirstName(input) {
      const v = input.value.trim(); const err = document.getElementById('firstNameError');
      if (!v) { setInputError(input, err, 'First name is required'); return false; }
      if (!/^[a-zA-Z\s]+$/.test(v)) { setInputError(input, err, 'First name can only contain letters'); return false; }
      setInputSuccess(input, err); return true;
    }
    function validateLastName(input) {
      const v = input.value.trim(); const err = document.getElementById('lastNameError');
      if (!v) { setInputError(input, err, 'Last name is required'); return false; }
      if (!/^[a-zA-Z\s]+$/.test(v)) { setInputError(input, err, 'Last name can only contain letters'); return false; }
      setInputSuccess(input, err); return true;
    }
    function validateEmail(input) {
      const v = input.value.trim(); const err = document.getElementById('emailError');
      const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!v) { setInputError(input, err, 'Email is required'); return false; }
      if (!re.test(v)) { setInputError(input, err, 'Please enter a valid email address'); return false; }
      setInputSuccess(input, err); return true;
    }
    function validatePhone(input) {
      const v = input.value.trim(); const err = document.getElementById('phoneError');
      const clean = v.replace(/\D/g, '');
      if (!v) { setInputError(input, err, 'Phone number is required'); return false; }
      if (clean.length !== 11) { setInputError(input, err, 'Phone number must be exactly 11 digits'); return false; }
      setInputSuccess(input, err); return true;
    }
    function validatePassword(input) {
      const v = input.value; const err = document.getElementById('passwordError');
      if (!v) { setInputError(input, err, 'Password is required'); return false; }
      if (v.length < 6) { setInputError(input, err, 'Password must be at least 6 characters'); return false; }
      if (!/[A-Z]/.test(v)) { setInputError(input, err, 'Password must contain an uppercase letter'); return false; }
      if (!/[a-z]/.test(v)) { setInputError(input, err, 'Password must contain a lowercase letter'); return false; }
      if (!/[0-9]/.test(v)) { setInputError(input, err, 'Password must contain a number'); return false; }
      if (!/[^a-zA-Z0-9]/.test(v)) { setInputError(input, err, 'Password must contain a special character'); return false; }
      setInputSuccess(input, err); return true;
    }
    function validateConfirmPassword(pInput, cInput) {
      const p = pInput.value; const c = cInput.value; const err = document.getElementById('confirmPasswordError');
      if (!c) { setInputError(cInput, err, 'Please confirm your password'); return false; }
      if (p !== c) { setInputError(cInput, err, 'Passwords do not match'); return false; }
      setInputSuccess(cInput, err); return true;
    }

    // events
    // Debounce helper
    function debounce(fn, wait) {
      let t;
      return function(...args) {
        clearTimeout(t);
        t = setTimeout(() => fn.apply(this, args), wait);
      };
    }

    async function checkUnique(type, value) {
      try {
        const fd = new FormData(); fd.append('type', type); fd.append('value', value);
        const res = await fetch('check-unique.php', { method: 'POST', body: fd });
        const data = await res.json();
        return data;
      } catch (err) {
        console.error('checkUnique error', err);
        return { exists: false, error: 'Network error' };
      }
    }

    if (firstNameInput) {
      firstNameInput.addEventListener('blur', () => validateFirstName(firstNameInput));
      firstNameInput.addEventListener('input', () => validateFirstName(firstNameInput));
    }
    if (lastNameInput) {
      lastNameInput.addEventListener('blur', () => validateLastName(lastNameInput));
      lastNameInput.addEventListener('input', () => validateLastName(lastNameInput));
    }
    if (emailInput) {
      emailInput.addEventListener('blur', async () => {
        if (!validateEmail(emailInput)) return;
        const data = await checkUnique('email', emailInput.value.trim());
        if (data && data.exists) {
          setInputError(emailInput, document.getElementById('emailError'), 'This email is already registered');
        }
      });
      emailInput.addEventListener('input', () => { document.getElementById('emailError').textContent = ''; emailInput.classList.remove('error'); });
    }
    if (phoneInput) {
      phoneInput.addEventListener('blur', async () => {
        phoneInput.value = phoneInput.value.replace(/\D/g, '');
        if (!validatePhone(phoneInput)) return;
        const data = await checkUnique('phone', phoneInput.value.trim());
        if (data && data.exists) {
          setInputError(phoneInput, document.getElementById('phoneError'), 'This phone number is already registered');
        }
      });
      phoneInput.addEventListener('input', () => { phoneInput.value = phoneInput.value.replace(/\D/g, ''); validatePhone(phoneInput); });
    }
    if (passwordInput) { passwordInput.addEventListener('blur', () => validatePassword(passwordInput)); passwordInput.addEventListener('input', () => validatePassword(passwordInput)); }
    if (confirmPasswordInput) { confirmPasswordInput.addEventListener('blur', () => validateConfirmPassword(passwordInput, confirmPasswordInput)); confirmPasswordInput.addEventListener('input', () => validateConfirmPassword(passwordInput, confirmPasswordInput)); }

    function showNotification(msg, type='info') {
      let n = document.getElementById('notificationMessage');
      if (!n) {
        n = document.createElement('div'); n.id = 'notificationMessage';
        n.style.cssText = 'position:fixed;top:20px;right:20px;padding:12px;border-radius:8px;font-weight:600;z-index:9999;';
        document.body.appendChild(n);
      }
      n.textContent = msg;
      n.style.backgroundColor = type === 'success' ? '#dcfce7' : '#fee2e2';
      n.style.color = type === 'success' ? '#166534' : '#991b1b';
      setTimeout(() => n.remove(), 4000);
    }

    // submission
    registrationForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const firstNameValid = firstNameInput ? validateFirstName(firstNameInput) : true;
      const lastNameValid = lastNameInput ? validateLastName(lastNameInput) : true;
      const emailValid = emailInput ? validateEmail(emailInput) : true;
      const phoneValid = phoneInput ? validatePhone(phoneInput) : true;
      const passwordValid = passwordInput ? validatePassword(passwordInput) : true;
      const confirmPasswordValid = confirmPasswordInput ? validateConfirmPassword(passwordInput, confirmPasswordInput) : true;

      if (!firstNameValid || !lastNameValid || !emailValid || !phoneValid || !passwordValid || !confirmPasswordValid) {
        showNotification('Please fix the highlighted errors', 'error');
        return;
      }

      const terms = registrationForm.querySelector('input[name="terms"]');
      if (!terms || !terms.checked) {
        showNotification('Please agree to the Terms of Service', 'error');
        return;
      }

      // disable submit
      const submitBtn = registrationForm.querySelector('button[type="submit"]');
      if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Please wait...'; }

      const fd = new FormData();
      fd.append('firstName', firstNameInput.value.trim());
      fd.append('lastName', lastNameInput.value.trim());
      fd.append('email', emailInput.value.trim());
      fd.append('phone', phoneInput.value.trim());
      fd.append('password', passwordInput.value);
      fd.append('confirmPassword', confirmPasswordInput.value);

      try {
        const res = await fetch('register-handler.php?debug=1', {
          method: 'POST',
          body: fd,
          credentials: 'same-origin'
        });

        const text = await res.text();
        let data;
        try { data = JSON.parse(text); } catch (err) {
          console.error('Invalid JSON response:', text);
          showNotification('Registration failed. Invalid server response', 'error');
          if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Create Account'; }
          return;
        }

        if (data.success) {
          showNotification(data.message || 'Registration successful', 'success');
          setTimeout(() => { window.location.href = data.redirect || 'user-login.php'; }, 800);
        } else {
          if (data.errors) {
            if (data.errors.firstName) setInputError(firstNameInput, document.getElementById('firstNameError'), data.errors.firstName);
            if (data.errors.lastName) setInputError(lastNameInput, document.getElementById('lastNameError'), data.errors.lastName);
            if (data.errors.email) setInputError(emailInput, document.getElementById('emailError'), data.errors.email);
            if (data.errors.phone) setInputError(phoneInput, document.getElementById('phoneError'), data.errors.phone);
            if (data.errors.password) setInputError(passwordInput, document.getElementById('passwordError'), data.errors.password);
            if (data.errors.confirmPassword) setInputError(confirmPasswordInput, document.getElementById('confirmPasswordError'), data.errors.confirmPassword);
            if (data.errors.general) showNotification(data.errors.general, 'error');
            if (data.debug) console.debug('Server debug:', data.debug);
          } else {
            showNotification('Registration failed. Please try again.', 'error');
          }
          if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Create Account'; }
        }
      } catch (err) {
        console.error('Registration fetch error:', err);
        showNotification('Registration failed. Network or server error.', 'error');
        if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Create Account'; }
      }
    });
  }

  // reveal, floating, footer, nav (same as before)
  function initRevealAnimations() {
    const revealElements = document.querySelectorAll('.benefit-item, .form-group, .btn-primary');
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry, idx) => {
        if (entry.isIntersecting) {
          setTimeout(() => {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
          }, idx * 100);
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.15, rootMargin: '0px 0px -50px 0px' });
    revealElements.forEach(el => {
      el.style.opacity = '0'; el.style.transform = 'translateY(20px)';
      el.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
      observer.observe(el);
    });
  }

  function initFloatingShapes() {
    const shapes = document.querySelectorAll('.circle, .background-circle');
    function handleParallax() {
      const scrolled = window.pageYOffset;
      shapes.forEach((shape, index) => {
        const speed = 0.3 + (index * 0.1);
        const yPos = -(scrolled * speed);
        shape.style.transform = `translateY(${yPos}px)`;
      });
    }
    window.addEventListener('scroll', handleParallax, { passive: true });
  }

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

  function init() {
    console.log('[Smart Trashbin] Initializing registration page...');
    initScrollProgress();
    initHeaderScroll();
    initPasswordToggle();
    initFormValidation();
    initRevealAnimations();
    initFloatingShapes();
    initFooterText();
    initActiveNav();
    console.log('[Smart Trashbin] Registration page initialized successfully!');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();