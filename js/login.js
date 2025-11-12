;(() => {
  // ============================================
  // SCROLL PROGRESS INDICATOR
  // ============================================
  function initScrollProgress() {
    const progressBar = document.createElement("div")
    progressBar.className = "scroll-progress"
    document.body.appendChild(progressBar)

    function updateProgress() {
      const winScroll = document.body.scrollTop || document.documentElement.scrollTop
      const height = document.documentElement.scrollHeight - document.documentElement.clientHeight
      const scrolled = (winScroll / height) * 100
      progressBar.style.width = scrolled + "%"
    }

    window.addEventListener("scroll", updateProgress, { passive: true })
    updateProgress()
  }

  // ============================================
  // HEADER SCROLL EFFECT
  // ============================================
  function initHeaderScroll() {
    const header = document.querySelector(".header")
    if (!header) return

    let lastScroll = 0

    function handleScroll() {
      const currentScroll = window.pageYOffset

      if (currentScroll > 50) {
        header.classList.add("scrolled")
      } else {
        header.classList.remove("scrolled")
      }

      lastScroll = currentScroll
    }

    window.addEventListener("scroll", handleScroll, { passive: true })
  }

  // ============================================
  // PASSWORD VISIBILITY TOGGLE
  // ============================================
  function initPasswordToggle() {
    const togglePassword = document.getElementById("togglePassword")
    const passwordInput = document.getElementById("password")

    if (togglePassword && passwordInput) {
      togglePassword.addEventListener("click", (e) => {
        e.preventDefault()
        const icon = togglePassword.querySelector("i")

        if (passwordInput.type === "password") {
          passwordInput.type = "text"
          icon.classList.remove("fa-eye")
          icon.classList.add("fa-eye-slash")
        } else {
          passwordInput.type = "password"
          icon.classList.remove("fa-eye-slash")
          icon.classList.add("fa-eye")
        }
      })
    }
  }

  // ============================================
  // FORM VALIDATION & SUBMISSION
  // ============================================
  function initFormValidation() {
    const loginForm = document.getElementById("loginForm")
    const emailInput = document.getElementById("email")
    const passwordInput = document.getElementById("password")

    if (!loginForm) return

    // Real-time validation
    if (emailInput) {
      emailInput.addEventListener("blur", () => {
        validateEmail(emailInput)
      })
    }

    if (passwordInput) {
      passwordInput.addEventListener("blur", () => {
        validatePassword(passwordInput)
      })
    }

    // Form submission
    loginForm.addEventListener("submit", async (e) => {
      e.preventDefault()

      const email = emailInput.value.trim()
      const password = passwordInput.value.trim()

      // Validate inputs
      const emailValid = validateEmail(emailInput)
      const passwordValid = validatePassword(passwordInput)

      if (!emailValid || !passwordValid) {
        return
      }

      const formData = new FormData()
      formData.append("email", email)
      formData.append("password", password)

      try {
        console.log("[v0] Sending login request for email:", email)
        const response = await fetch("login-handler.php", {
          method: "POST",
          body: formData,
        })

        const data = await response.json()
        console.log("[v0] Login response received:", data)

        if (data.success) {
          console.log("[v0] Login successful. User role: unknown, Redirect URL:", data.redirect)
          showNotification(data.message, "success")

          if (data.redirect) {
            console.log("[v0] Redirecting to:", data.redirect)
            setTimeout(() => {
              window.location.href = data.redirect
            }, 100) // Small delay to ensure message displays
          }
        } else {
          console.log("[v0] Login failed with errors:", data.errors)
          // Show error messages
          if (data.errors.email) {
            setInputError(emailInput, data.errors.email)
            showNotification(data.errors.email, "error")
          }
          if (data.errors.password) {
            setInputError(passwordInput, data.errors.password)
            showNotification(data.errors.password, "error")
          }
          if (data.errors.general) {
            showNotification(data.errors.general, "error")
          }
        }
      } catch (error) {
        console.error("[v0] Login error:", error)
        showNotification("An error occurred during login. Please try again.", "error")
      }
    })
  }

  function validateEmail(input) {
    const email = input.value.trim()
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

    if (!email) {
      setInputError(input, "Email is required")
      return false
    } else if (!emailRegex.test(email)) {
      setInputError(input, "Please enter a valid email")
      return false
    } else {
      setInputSuccess(input)
      return true
    }
  }

  function validatePassword(input) {
    const password = input.value.trim()

    if (!password) {
      setInputError(input, "Password is required")
      return false
    } else if (password.length < 6) {
      setInputError(input, "Password must be at least 6 characters")
      return false
    } else {
      setInputSuccess(input)
      return true
    }
  }

  function setInputError(input, message) {
    input.classList.add("error")
    input.classList.remove("success")
  }

  function setInputSuccess(input) {
    input.classList.remove("error")
    input.classList.add("success")
  }

  function showNotification(message, type = "info") {
    console.log("[v0] Notification (" + type + "):", message)

    // Create or update notification element on page
    let notificationEl = document.getElementById("notificationMessage")
    if (!notificationEl) {
      notificationEl = document.createElement("div")
      notificationEl.id = "notificationMessage"
      notificationEl.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        font-size: 14px;
        z-index: 9999;
        animation: slideIn 0.3s ease;
      `
      document.body.appendChild(notificationEl)
    }

    notificationEl.textContent = message
    notificationEl.style.display = "block"

    if (type === "error") {
      notificationEl.style.backgroundColor = "#ff4757"
      notificationEl.style.color = "white"
    } else if (type === "success") {
      notificationEl.style.backgroundColor = "#2ed573"
      notificationEl.style.color = "white"
    } else {
      notificationEl.style.backgroundColor = "#a4b0bd"
      notificationEl.style.color = "white"
    }

    // Auto-hide after 3 seconds (for errors/info)
    if (type !== "success") {
      setTimeout(() => {
        notificationEl.style.display = "none"
      }, 3000)
    }
  }

  // ============================================
  // REVEAL ANIMATIONS
  // ============================================
  function initRevealAnimations() {
    const revealElements = document.querySelectorAll(".feature-item, .form-group, .btn-primary")

    const observerOptions = {
      threshold: 0.15,
      rootMargin: "0px 0px -50px 0px",
    }

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry, index) => {
        if (entry.isIntersecting) {
          setTimeout(() => {
            entry.target.style.opacity = "1"
            entry.target.style.transform = "translateY(0)"
          }, index * 100)

          observer.unobserve(entry.target)
        }
      })
    }, observerOptions)

    revealElements.forEach((el) => {
      el.style.opacity = "0"
      el.style.transform = "translateY(20px)"
      el.style.transition = "opacity 0.6s ease-out, transform 0.6s ease-out"
      observer.observe(el)
    })
  }

  // ============================================
  // MAGNETIC BUTTON EFFECT
  // ============================================
  function initMagneticButtons() {
    const buttons = document.querySelectorAll(".btn-primary, .social-btn")

    buttons.forEach((button) => {
      button.addEventListener("mousemove", (e) => {
        const rect = button.getBoundingClientRect()
        const x = e.clientX - rect.left - rect.width / 2
        const y = e.clientY - rect.top - rect.height / 2

        button.style.transform = `translate(${x * 0.1}px, ${y * 0.1}px)`
      })

      button.addEventListener("mouseleave", () => {
        button.style.transform = ""
      })
    })
  }

  // ============================================
  // FLOATING SHAPES PARALLAX
  // ============================================
  function initFloatingShapes() {
    const shapes = document.querySelectorAll(".circle, .background-circle")

    function handleParallax() {
      const scrolled = window.pageYOffset

      shapes.forEach((shape, index) => {
        const speed = 0.3 + index * 0.1
        const yPos = -(scrolled * speed)
        shape.style.transform = `translateY(${yPos}px)`
      })
    }

    window.addEventListener("scroll", handleParallax, { passive: true })
  }

  // ============================================
  // FEATURE ICON FLIP ANIMATION
  // ============================================
  function initFeatureIconFlip() {
    const featureItems = document.querySelectorAll(".feature-item")

    featureItems.forEach((item) => {
      item.addEventListener("mouseenter", () => {
        const icon = item.querySelector(".feature-icon-container i")
        if (icon) {
          icon.style.transform = "rotateY(360deg)"
        }
      })

      item.addEventListener("mouseleave", () => {
        const icon = item.querySelector(".feature-icon-container i")
        if (icon) {
          icon.style.transform = "rotateY(0deg)"
        }
      })
    })
  }

  // ============================================
  // FOOTER DYNAMIC TEXT
  // ============================================
  function initFooterText() {
    const footerText = document.getElementById("footerText")
    if (footerText) {
      const messages = [
        "Making waste management smarter, one bin at a time.",
        "Powered by IoT technology and sustainable innovation.",
        "Join us in creating cleaner, greener communities.",
        "Real-time monitoring for a cleaner tomorrow.",
      ]

      let currentIndex = 0

      function updateFooterText() {
        footerText.style.opacity = "0"

        setTimeout(() => {
          footerText.textContent = messages[currentIndex]
          footerText.style.opacity = "1"
          currentIndex = (currentIndex + 1) % messages.length
        }, 500)
      }

      footerText.textContent = messages[0]
      setInterval(updateFooterText, 5000)
    }
  }

  // ============================================
  // NAVIGATION ACTIVE STATE
  // ============================================
  function initActiveNav() {
    const navLinks = document.querySelectorAll(".nav-link")
    const currentPage = window.location.pathname.split("/").pop() || "index.php"

    navLinks.forEach((link) => {
      const href = link.getAttribute("href")
      if (href === currentPage) {
        link.style.color = "var(--primary-color)"
        link.style.fontWeight = "700"
      }
    })
  }

  // ============================================
  // INITIALIZATION
  // ============================================
  function init() {
    console.log("[Smart Trashbin] Initializing premium login animations...")

    initScrollProgress()
    initHeaderScroll()
    initPasswordToggle()
    initFormValidation()
    initRevealAnimations()
    initMagneticButtons()
    initFloatingShapes()
    initFeatureIconFlip()
    initFooterText()
    initActiveNav()

    console.log("[Smart Trashbin] All animations initialized successfully!")
  }

  // ============================================
  // RUN ON DOM READY
  // ============================================
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init)
  } else {
    init()
  }
})()
