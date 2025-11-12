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
    const passwordInput = document.getElementById("pw")

    if (togglePassword && passwordInput) {
      togglePassword.addEventListener("click", (e) => {
        e.preventDefault()
        const icon = togglePassword.querySelector("i")

        if (passwordInput.type === "password") {
          passwordInput.type = "text"
          icon.classList.remove("fa-eye")
          icon.classList.add("fa-eye-slash")
          togglePassword.classList.add("active")
        } else {
          passwordInput.type = "password"
          icon.classList.remove("fa-eye-slash")
          icon.classList.add("fa-eye")
          togglePassword.classList.remove("active")
        }
      })
    }

    // Confirm password toggle
    const toggleConfirmPassword = document.getElementById("toggleConfirmPassword")
    const confirmPasswordInput = document.getElementById("confirm-pw")

    if (toggleConfirmPassword && confirmPasswordInput) {
      toggleConfirmPassword.addEventListener("click", (e) => {
        e.preventDefault()
        const icon = toggleConfirmPassword.querySelector("i")

        if (confirmPasswordInput.type === "password") {
          confirmPasswordInput.type = "text"
          icon.classList.remove("fa-eye")
          icon.classList.add("fa-eye-slash")
          toggleConfirmPassword.classList.add("active")
        } else {
          confirmPasswordInput.type = "password"
          icon.classList.remove("fa-eye-slash")
          icon.classList.add("fa-eye")
          toggleConfirmPassword.classList.remove("active")
        }
      })
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
    const buttons = document.querySelectorAll(".btn-primary")

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
  // MESSAGE DISPLAY HELPER
  // ============================================
  function showMessage(text, type = "info", msgId = "msg-email") {
    const msgEl = document.getElementById(msgId)
    if (!msgEl) return

    msgEl.textContent = text
    msgEl.className = "step-message show " + type

    // Auto hide after 5 seconds for success
    if (type === "success") {
      setTimeout(() => {
        msgEl.className = "step-message"
      }, 5000)
    }
  }

  // ============================================
  // INITIALIZATION
  // ============================================
  function init() {
    console.log("[Smart Trashbin] Initializing forgot password page...")

    initScrollProgress()
    initHeaderScroll()
    initPasswordToggle()
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

  // Export showMessage for global use
  window.showForgotPasswordMessage = showMessage
})()

