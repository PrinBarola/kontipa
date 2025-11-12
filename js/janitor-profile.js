// Validation Rules
const validationRules = {
  email: {
    pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
    message: "Please enter a valid email format",
  },
  name: {
    pattern: /^[a-zA-Z\s'-]+$/,
    message: "Name can only contain letters, spaces, hyphens, and apostrophes",
  },
  phoneNumber: {
    pattern: /^\d{11}$/,
    message: "Phone number must be exactly 11 digits",
  },
  password: {
    pattern: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[a-zA-Z\d@$!%*?&]{5,}$/,
    message: "Password must have uppercase, lowercase, number, special symbol, and be 5+ characters",
  },
}

// Utility function to show messages
function showMessage(element, message, type) {
  const messageEl = element.nextElementSibling
  if (messageEl && messageEl.classList.contains("validation-message")) {
    messageEl.textContent = message
    messageEl.className = `validation-message ${type}`

    if (type === "error") {
      element.classList.add("is-invalid")
      element.classList.remove("is-valid")
    } else if (type === "success") {
      element.classList.add("is-valid")
      element.classList.remove("is-invalid")
    }
  }
}

function clearMessage(element) {
  const messageEl = element.nextElementSibling
  if (messageEl && messageEl.classList.contains("validation-message")) {
    messageEl.textContent = ""
    messageEl.className = "validation-message"
    element.classList.remove("is-invalid", "is-valid")
  }
}

// Validation functions
function validateEmail(email) {
  return validationRules.email.pattern.test(email)
}

function validateName(name) {
  return validationRules.name.pattern.test(name) && name.trim().length > 0
}

function validatePhoneNumber(phone) {
  const digitsOnly = phone.replace(/\D/g, "")
  return digitsOnly.length === 11
}

function validatePassword(password) {
  return validationRules.password.pattern.test(password)
}

function validatePasswordMatch(password, confirmPassword) {
  return password === confirmPassword && password.length > 0
}

// Password strength checker
function checkPasswordStrength(password) {
  let strength = 0
  const strengthFill = document
    .querySelector("#newPassword")
    .parentElement.parentElement.querySelector(".strength-fill")

  if (!password) {
    strengthFill.style.width = "0%"
    return
  }

  if (/[a-z]/.test(password)) strength++
  if (/[A-Z]/.test(password)) strength++
  if (/\d/.test(password)) strength++
  if (/[@$!%*?&]/.test(password)) strength++
  if (password.length >= 8) strength++

  const percentage = (strength / 5) * 100
  strengthFill.style.width = percentage + "%"

  if (strength <= 2) {
    strengthFill.style.backgroundColor = "#dc3545"
  } else if (strength <= 3) {
    strengthFill.style.backgroundColor = "#ffc107"
  } else {
    strengthFill.style.backgroundColor = "#198754"
  }
}

// Show alert message
function showAlert(containerId, message, type) {
  const alertEl = document.getElementById(containerId)
  alertEl.className = `alert alert-message show alert-${type}`
  alertEl.textContent = message
  alertEl.style.display = "block"

  setTimeout(() => {
    alertEl.classList.remove("show")
    alertEl.style.display = "none"
  }, 5000)
}

// Personal Information Form Validation
const personalInfoForm = document.getElementById("personalInfoForm")
if (personalInfoForm) {
  const firstNameInput = document.getElementById("firstName")
  const lastNameInput = document.getElementById("lastName")
  const emailInput = document.getElementById("email")
  const phoneInput = document.getElementById("phoneNumber")

  // Real-time validation
  firstNameInput.addEventListener("blur", () => {
    if (firstNameInput.value.trim()) {
      if (validateName(firstNameInput.value)) {
        showMessage(firstNameInput, "Valid name", "success")
      } else {
        showMessage(firstNameInput, validationRules.name.message, "error")
      }
    } else {
      clearMessage(firstNameInput)
    }
  })

  lastNameInput.addEventListener("blur", () => {
    if (lastNameInput.value.trim()) {
      if (validateName(lastNameInput.value)) {
        showMessage(lastNameInput, "Valid name", "success")
      } else {
        showMessage(lastNameInput, validationRules.name.message, "error")
      }
    } else {
      clearMessage(lastNameInput)
    }
  })

  emailInput.addEventListener("blur", () => {
    if (emailInput.value.trim()) {
      if (validateEmail(emailInput.value)) {
        showMessage(emailInput, "Valid email", "success")
      } else {
        showMessage(emailInput, validationRules.email.message, "error")
      }
    } else {
      clearMessage(emailInput)
    }
  })

  phoneInput.addEventListener("blur", () => {
    if (phoneInput.value.trim()) {
      if (validatePhoneNumber(phoneInput.value)) {
        showMessage(phoneInput, "Valid phone number", "success")
      } else {
        showMessage(phoneInput, validationRules.phoneNumber.message, "error")
      }
    } else {
      clearMessage(phoneInput)
    }
  })

  // Form submission
  personalInfoForm.addEventListener("submit", (e) => {
    e.preventDefault()

    let isValid = true

    if (!validateName(firstNameInput.value)) {
      showMessage(firstNameInput, validationRules.name.message, "error")
      isValid = false
    }

    if (!validateName(lastNameInput.value)) {
      showMessage(lastNameInput, validationRules.name.message, "error")
      isValid = false
    }

    if (!validateEmail(emailInput.value)) {
      showMessage(emailInput, validationRules.email.message, "error")
      isValid = false
    }

    if (phoneInput.value.trim() && !validatePhoneNumber(phoneInput.value)) {
      showMessage(phoneInput, validationRules.phoneNumber.message, "error")
      isValid = false
    }

    if (isValid) {
      showAlert("personalInfoAlert", "Personal information updated successfully!", "success")
      console.log("Form submitted with valid data")
    } else {
      showAlert("personalInfoAlert", "Please fix the errors above", "danger")
    }
  })
}

// Change Password Form Validation
const changePasswordForm = document.getElementById("changePasswordForm")
if (changePasswordForm) {
  const currentPasswordInput = document.getElementById("currentPassword")
  const newPasswordInput = document.getElementById("newPassword")
  const confirmPasswordInput = document.getElementById("confirmNewPassword")

  // Real-time password strength check
  newPasswordInput.addEventListener("input", () => {
    checkPasswordStrength(newPasswordInput.value)
  })

  // Real-time validation
  currentPasswordInput.addEventListener("blur", () => {
    if (currentPasswordInput.value.trim()) {
      // In real app, this would verify against stored password
      showMessage(currentPasswordInput, "Password verified", "success")
    } else {
      clearMessage(currentPasswordInput)
    }
  })

  newPasswordInput.addEventListener("blur", () => {
    if (newPasswordInput.value.trim()) {
      if (validatePassword(newPasswordInput.value)) {
        showMessage(newPasswordInput, "Strong password", "success")
      } else {
        showMessage(newPasswordInput, validationRules.password.message, "error")
      }
    } else {
      clearMessage(newPasswordInput)
    }
  })

  confirmPasswordInput.addEventListener("blur", () => {
    if (confirmPasswordInput.value.trim()) {
      if (validatePasswordMatch(newPasswordInput.value, confirmPasswordInput.value)) {
        showMessage(confirmPasswordInput, "Passwords match", "success")
      } else {
        showMessage(confirmPasswordInput, "Passwords do not match", "error")
      }
    } else {
      clearMessage(confirmPasswordInput)
    }
  })

  // Form submission
  changePasswordForm.addEventListener("submit", (e) => {
    e.preventDefault()

    let isValid = true

    if (!currentPasswordInput.value.trim()) {
      showMessage(currentPasswordInput, "Current password is required", "error")
      isValid = false
    }

    if (!validatePassword(newPasswordInput.value)) {
      showMessage(newPasswordInput, validationRules.password.message, "error")
      isValid = false
    }

    if (!validatePasswordMatch(newPasswordInput.value, confirmPasswordInput.value)) {
      showMessage(confirmPasswordInput, "Passwords do not match", "error")
      isValid = false
    }

    if (isValid) {
      showAlert("passwordAlert", "Password updated successfully!", "success")
      changePasswordForm.reset()
      clearMessage(currentPasswordInput)
      clearMessage(newPasswordInput)
      clearMessage(confirmPasswordInput)
      document.querySelector(".strength-fill").style.width = "0%"
    } else {
      showAlert("passwordAlert", "Please fix the errors above", "danger")
    }
  })
}

// Photo Upload Validation
const changePhotoBtn = document.getElementById("changePhotoBtn")
const photoInput = document.getElementById("photoInput")
const photoMessage = document.getElementById("photoMessage")
const profileImg = document.getElementById("profileImg")

if (changePhotoBtn && photoInput) {
  changePhotoBtn.addEventListener("click", () => {
    photoInput.click()
  })

  photoInput.addEventListener("change", (e) => {
    const file = e.target.files[0]

    if (!file) return

    // Validate file type
    const allowedTypes = ["image/png", "image/jpeg"]
    const allowedExtensions = ["png", "jpg", "jpeg"]
    const fileExtension = file.name.split(".").pop().toLowerCase()

    if (!allowedTypes.includes(file.type) || !allowedExtensions.includes(fileExtension)) {
      photoMessage.textContent = "Only PNG, JPG, and JPEG files are allowed"
      photoMessage.className = "validation-message error"
      photoInput.value = ""
      return
    }

    // Validate file size (max 5MB)
    const maxSize = 5 * 1024 * 1024
    if (file.size > maxSize) {
      photoMessage.textContent = "File size must be less than 5MB"
      photoMessage.className = "validation-message error"
      photoInput.value = ""
      return
    }

    // Read and display the image
    const reader = new FileReader()
    reader.onload = (event) => {
      profileImg.src = event.target.result
      photoMessage.textContent = "Photo updated successfully!"
      photoMessage.className = "validation-message success"

      setTimeout(() => {
        photoMessage.className = "validation-message"
        photoMessage.textContent = ""
      }, 3000)
    }
    reader.readAsDataURL(file)
  })
}

// Logout functionality
document.getElementById("logoutBtn").addEventListener("click", (e) => {
  e.preventDefault()
  if (confirm("Are you sure you want to logout?")) {
    window.location.href = "logout.php"
  }
})

// Activate the current tab in the URL hash
document.addEventListener("DOMContentLoaded", () => {
  const urlHash = window.location.hash
  if (urlHash) {
    const tabTrigger = document.querySelector(`[href="${urlHash}"]`)
    if (tabTrigger && window.bootstrap) {
      const tab = new window.bootstrap.Tab(tabTrigger)
      tab.show()
    }
  }

  const passwordToggleBtns = document.querySelectorAll(".password-toggle-btn")

  passwordToggleBtns.forEach((btn) => {
    btn.addEventListener("click", function (e) {
      e.preventDefault()
      const targetId = this.getAttribute("data-target")
      const passwordInput = document.querySelector(targetId)
      const icon = this.querySelector("i")

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
  })
})
