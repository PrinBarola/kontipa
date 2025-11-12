const bins = [
  {
    id: "BIN-001",
    location: "Main Street - Corner Store",
    type: "General",
    status: "full",
    lastEmptied: "2 hours ago",
    capacity: "95%",
    assignedTo: "John Doe",
  },
  {
    id: "BIN-002",
    location: "Park Avenue - Central Park",
    type: "Recyclable",
    status: "empty",
    lastEmptied: "30 mins ago",
    capacity: "10%",
    assignedTo: "Jane Smith",
  },
  {
    id: "BIN-003",
    location: "Downtown - Market Square",
    type: "Organic",
    status: "needs_attention",
    lastEmptied: "5 hours ago",
    capacity: "80%",
    assignedTo: "Bob Johnson",
  },
]

const janitors = [
  { id: 1, name: "John Doe", email: "john@example.com", phone: "+1 (555) 123-4567", bins: 5, status: "active" },
  { id: 2, name: "Jane Smith", email: "jane@example.com", phone: "+1 (555) 234-5678", bins: 4, status: "active" },
  { id: 3, name: "Bob Johnson", email: "bob@example.com", phone: "+1 (555) 345-6789", bins: 6, status: "inactive" },
]

const notifications = [
  {
    id: 1,
    type: "critical",
    message: "Bin BIN-001 is FULL - Immediate action required",
    time: "Just now",
    read: false,
  },
  {
    id: 2,
    type: "warning",
    message: "Bin BIN-003 capacity at 80% - Schedule emptying soon",
    time: "5 mins ago",
    read: false,
  },
  { id: 3, type: "info", message: "Bin BIN-002 emptied successfully", time: "30 mins ago", read: true },
]

const bootstrap = window.bootstrap

document.addEventListener("DOMContentLoaded", () => {
  console.log("[Admin] DOM Content Loaded - Initializing dashboard")
  initializeButtons()
  loadDashboardData()
  loadBinsTable()
  loadAllBinsTable()
  loadJanitorsTable()
  loadNotifications()
  updateNotificationCount()
  // try fetching authoritative unread count from server and poll
  refreshNotificationCount()
  setInterval(refreshNotificationCount, 30000)
  console.log("[Admin] Dashboard initialization complete")
})

function initializeButtons() {
  // Search inputs
  const searchBinsInput = document.getElementById("searchBinsInput")
  if (searchBinsInput) {
    searchBinsInput.addEventListener("input", function () {
      filterBinsTable(this.value)
    })
  }

  const searchJanitorsInput = document.getElementById("searchJanitorsInput")
  if (searchJanitorsInput) {
    searchJanitorsInput.addEventListener("input", function () {
      filterJanitorsTable(this.value)
    })
  }

  // Filter dropdowns
  const filterBinsItems = document.querySelectorAll("#filterBinsDropdown ~ .dropdown-menu a")
  filterBinsItems.forEach((item) => {
    item.addEventListener("click", function (e) {
      e.preventDefault()
      const filter = this.getAttribute("data-filter")
      filterBinsByStatus(filter)
    })
  })

  const filterJanitorsItems = document.querySelectorAll("#filterJanitorsDropdown ~ .dropdown-menu a")
  filterJanitorsItems.forEach((item) => {
    item.addEventListener("click", function (e) {
      e.preventDefault()
      const filter = this.getAttribute("data-filter")
      filterJanitorsByStatus(filter)
    })
  })

  const filterNotificationsItems = document.querySelectorAll("#filterNotificationsDropdown ~ .dropdown-menu a")
  filterNotificationsItems.forEach((item) => {
    item.addEventListener("click", function (e) {
      e.preventDefault()
      const filter = this.getAttribute("data-filter")
      filterNotificationsByType(filter)
    })
  })

  // Mark all read button
  const markAllReadBtn = document.getElementById("markAllReadBtn")
  if (markAllReadBtn) {
    markAllReadBtn.addEventListener("click", () => markAllNotificationsRead())
  }

  // Clear notifications button
  const clearNotificationsBtn = document.getElementById("clearNotificationsBtn")
  if (clearNotificationsBtn) {
    clearNotificationsBtn.addEventListener("click", () => clearAllNotifications())
  }

  // Password toggle buttons
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

  // Profile forms
  const personalInfoForm = document.getElementById("personalInfoForm")
  if (personalInfoForm) {
    setupPersonalInfoForm(personalInfoForm)
  }

  // DISABLED: changePasswordForm is now handled by profile.php with api/admin/change-password.php
  // const changePasswordForm = document.getElementById("changePasswordForm")
  // if (changePasswordForm) {
  //   setupChangePasswordForm(changePasswordForm)
  // }

  // Profile photo upload
  const changePhotoBtn = document.getElementById("changePhotoBtn")
  const photoInput = document.getElementById("photoInput")
  if (changePhotoBtn && photoInput) {
    changePhotoBtn.addEventListener("click", () => photoInput.click())
    photoInput.addEventListener("change", handlePhotoUpload)
  }

  initializeProfileTabs()
}

function handlePhotoUpload(e) {
  const file = e.target.files[0]
  if (!file) return

  const allowedTypes = ["image/png", "image/jpeg"]
  const fileExtension = file.name.split(".").pop().toLowerCase()

  if (!allowedTypes.includes(file.type) || !["png", "jpg", "jpeg"].includes(fileExtension)) {
    showAlert("photoMessage", "Only PNG, JPG, and JPEG files are allowed", "error")
    return
  }

  if (file.size > 5 * 1024 * 1024) {
    showAlert("photoMessage", "File size must be less than 5MB", "error")
    return
  }

  const reader = new FileReader()
  reader.onload = (event) => {
    const profileImg = document.getElementById("profileImg")
    if (profileImg) profileImg.src = event.target.result
    showAlert("photoMessage", "Photo updated successfully!", "success")
  }
  reader.readAsDataURL(file)
}

function showAlert(elementId, message, type) {
  const element = document.getElementById(elementId)
  if (element) {
    element.textContent = message
    element.className = `validation-message ${type}`
    element.style.display = "block"
  }
}

function initializeProfileTabs() {
  const profileMenuItems = document.querySelectorAll(".profile-menu-item")
  profileMenuItems.forEach((item) => {
    item.addEventListener("click", function (e) {
      e.preventDefault()
      const targetId = this.getAttribute("href")
      profileMenuItems.forEach((i) => i.classList.remove("active"))
      this.classList.add("active")
      document.querySelectorAll(".tab-pane").forEach((pane) => {
        pane.classList.remove("show", "active")
      })
      const targetPane = document.querySelector(targetId)
      if (targetPane) targetPane.classList.add("show", "active")
    })
  })
}

function setupPersonalInfoForm(form) {
  form.addEventListener("submit", (e) => {
    e.preventDefault()
    const firstName = document.getElementById("firstName").value
    const lastName = document.getElementById("lastName").value
    const profileNameEl = document.getElementById("profileName")
    if (profileNameEl) profileNameEl.textContent = `${firstName} ${lastName}`
  })
}

function setupChangePasswordForm(form) {
  const newPasswordInput = document.getElementById("newPassword")
  if (newPasswordInput) {
    newPasswordInput.addEventListener("input", () => checkPasswordStrength(newPasswordInput.value))
  }
  form.addEventListener("submit", (e) => {
    e.preventDefault()
    form.reset()
  })
}

function checkPasswordStrength(password) {
  const strengthFill = document.querySelector(".strength-fill")
  if (!strengthFill) return
  let strength = 0
  if (/[a-z]/.test(password)) strength++
  if (/[A-Z]/.test(password)) strength++
  if (/\d/.test(password)) strength++
  if (/[@$!%*?&]/.test(password)) strength++
  if (password.length >= 8) strength++
  const percentage = (strength / 5) * 100
  strengthFill.style.width = percentage + "%"
  strengthFill.style.backgroundColor = strength <= 2 ? "#dc3545" : strength <= 3 ? "#ffc107" : "#198754"
}

function loadDashboardData() {
  const totalBinsEl = document.getElementById("totalBins")
  const fullBinsEl = document.getElementById("fullBins")
  const activeJanitorsEl = document.getElementById("activeJanitors")
  const collectionsTodayEl = document.getElementById("collectionsToday")

  if (totalBinsEl) totalBinsEl.textContent = bins.length
  if (fullBinsEl) fullBinsEl.textContent = bins.filter((b) => b.status === "full").length
  if (activeJanitorsEl) activeJanitorsEl.textContent = janitors.filter((j) => j.status === "active").length
  if (collectionsTodayEl) collectionsTodayEl.textContent = Math.floor(Math.random() * 10) + 5
}

function loadBinsTable() {
  const tbody = document.getElementById("binsTableBody")
  if (!tbody) return
  tbody.innerHTML = ""
  if (bins.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No bins found</td></tr>'
    return
  }
  bins.forEach((bin) => {
    const row = document.createElement("tr")
    row.innerHTML = `
      <td>${bin.id}</td>
      <td>${bin.location}</td>
      <td>${getStatusBadge(bin.status)}</td>
      <td class="d-none d-md-table-cell">${bin.lastEmptied}</td>
      <td class="d-none d-lg-table-cell">${bin.assignedTo}</td>
      <td class="text-end"><button class="btn btn-sm btn-primary" onclick="editBin('${bin.id}')">Edit</button></td>
    `
    tbody.appendChild(row)
  })
}

function loadAllBinsTable() {
  const tbody = document.getElementById("allBinsTableBody")
  if (!tbody) return
  tbody.innerHTML = ""
  if (bins.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No bins found</td></tr>'
    return
  }
  bins.forEach((bin) => {
    const row = document.createElement("tr")
    row.innerHTML = `
      <td>${bin.id}</td>
      <td>${bin.location}</td>
      <td>${bin.type}</td>
      <td>${getStatusBadge(bin.status)}</td>
      <td class="d-none d-lg-table-cell">${bin.capacity}</td>
      <td class="d-none d-md-table-cell">${bin.assignedTo}</td>
      <td class="text-end"><button class="btn btn-sm btn-primary" onclick="editBin('${bin.id}')">Edit</button></td>
    `
    tbody.appendChild(row)
  })
}

function loadJanitorsTable() {
  const tbody = document.getElementById("janitorsTableBody")
  if (!tbody) return
  tbody.innerHTML = ""
  if (janitors.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No janitors found</td></tr>'
    return
  }
  janitors.forEach((j) => {
    const row = document.createElement("tr")
    row.innerHTML = `
      <td>${j.name}</td>
      <td>${j.email}</td>
      <td class="d-none d-md-table-cell">${j.phone}</td>
      <td class="d-none d-lg-table-cell">${j.bins} bins</td>
      <td>${getStatusBadge(j.status)}</td>
      <td class="text-end"><button class="btn btn-sm btn-primary" onclick="openEditJanitorModal(${j.id})">Edit</button></td>
    `
    tbody.appendChild(row)
  })
}

function loadNotifications() {
  const tbody = document.getElementById("notificationsTableBody")
  if (!tbody) return
  tbody.innerHTML = ""
  if (notifications.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No notifications found</td></tr>'
    return
  }
  notifications.forEach((notif) => {
    const row = document.createElement("tr")
    row.innerHTML = `
      <td>${notif.time}</td>
      <td>BIN-${String(notif.id).padStart(3, "0")}</td>
      <td class="d-none d-md-table-cell">Location</td>
      <td>${getAlertTypeBadge(notif.type)}</td>
      <td class="d-none d-lg-table-cell">${notif.read ? '<span class="badge badge-info">Read</span>' : '<span class="badge badge-danger">Unread</span>'}</td>
      <td class="text-end"><button class="btn btn-sm btn-primary" onclick="markNotificationRead(${notif.id})">Mark Read</button></td>
    `
    tbody.appendChild(row)
  })
}

function updateNotificationCount() {
  const unreadCount = notifications.filter((n) => !n.read).length
  const badge = document.getElementById("notificationCount")
  if (!badge) return
  badge.textContent = unreadCount
  badge.style.display = unreadCount > 0 ? "block" : "none"
}

async function refreshNotificationCount() {
  const badge = document.getElementById('notificationCount')
  if (!badge) return
  try {
    const resp = await fetch('api/get-notifications.php', { credentials: 'same-origin' })
    if (!resp.ok) throw new Error('Network response not ok')
    const data = await resp.json()
    const count = (data && (data.unread_count ?? data.unreadCount ?? data.count)) || 0
    if (count > 0) {
      badge.textContent = String(count)
      badge.style.display = 'block'
    } else {
      badge.style.display = 'none'
    }
    return
  } catch (err) {
    console.warn('[Dashboard] refreshNotificationCount failed, falling back to local notifications', err)
    updateNotificationCount()
  }
}

function getStatusBadge(status) {
  const badges = {
    full: '<span class="badge badge-danger">Full</span>',
    empty: '<span class="badge badge-success">Empty</span>',
    needs_attention: '<span class="badge badge-warning">Needs Attention</span>',
    active: '<span class="badge badge-success">Active</span>',
    inactive: '<span class="badge badge-danger">Inactive</span>',
  }
  return badges[status] || '<span class="badge">Unknown</span>'
}

function getAlertTypeBadge(type) {
  const badges = {
    critical: '<span class="badge badge-danger">Critical</span>',
    warning: '<span class="badge badge-warning">Warning</span>',
    info: '<span class="badge badge-info">Info</span>',
  }
  return badges[type] || '<span class="badge">Unknown</span>'
}

function filterBinsTable(searchTerm) {
  const tbody = document.getElementById("allBinsTableBody")
  if (!tbody) return
  tbody.querySelectorAll("tr").forEach((row) => {
    row.style.display = row.textContent.toLowerCase().includes(searchTerm.toLowerCase()) ? "" : "none"
  })
}

function filterJanitorsTable(searchTerm) {
  const tbody = document.getElementById("janitorsTableBody")
  if (!tbody) return
  tbody.querySelectorAll("tr").forEach((row) => {
    row.style.display = row.textContent.toLowerCase().includes(searchTerm.toLowerCase()) ? "" : "none"
  })
}

function filterBinsByStatus(status) {
  const filtered = status === "all" ? bins : bins.filter((b) => b.status === status)
  const tbody = document.getElementById("allBinsTableBody")
  if (!tbody) return
  tbody.innerHTML = ""
  if (filtered.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No bins found</td></tr>'
    return
  }
  filtered.forEach((bin) => {
    const row = document.createElement("tr")
    row.innerHTML = `
      <td>${bin.id}</td>
      <td>${bin.location}</td>
      <td>${bin.type}</td>
      <td>${getStatusBadge(bin.status)}</td>
      <td class="d-none d-lg-table-cell">${bin.capacity}</td>
      <td class="d-none d-md-table-cell">${bin.assignedTo}</td>
      <td class="text-end"><button class="btn btn-sm btn-primary" onclick="editBin('${bin.id}')">Edit</button></td>
    `
    tbody.appendChild(row)
  })
}

function filterJanitorsByStatus(status) {
  const filtered = status === "all" ? janitors : janitors.filter((j) => j.status === status)
  const tbody = document.getElementById("janitorsTableBody")
  if (!tbody) return
  tbody.innerHTML = ""
  if (filtered.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No janitors found</td></tr>'
    return
  }
  filtered.forEach((j) => {
    const row = document.createElement("tr")
    row.innerHTML = `
      <td>${j.name}</td>
      <td>${j.email}</td>
      <td class="d-none d-md-table-cell">${j.phone}</td>
      <td class="d-none d-lg-table-cell">${j.bins} bins</td>
      <td>${getStatusBadge(j.status)}</td>
      <td class="text-end"><button class="btn btn-sm btn-primary" onclick="openEditJanitorModal(${j.id})">Edit</button></td>
    `
    tbody.appendChild(row)
  })
}

function filterNotificationsByType(type) {
  const filtered = type === "all" ? notifications : notifications.filter((n) => n.type === type)
  const tbody = document.getElementById("notificationsTableBody")
  if (!tbody) return
  tbody.innerHTML = ""
  if (filtered.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No notifications found</td></tr>'
    return
  }
  filtered.forEach((notif) => {
    const row = document.createElement("tr")
    row.innerHTML = `
      <td>${notif.time}</td>
      <td>BIN-${String(notif.id).padStart(3, "0")}</td>
      <td class="d-none d-md-table-cell">Location</td>
      <td>${getAlertTypeBadge(notif.type)}</td>
      <td class="d-none d-lg-table-cell">${notif.read ? '<span class="badge badge-info">Read</span>' : '<span class="badge badge-danger">Unread</span>'}</td>
      <td class="text-end"><button class="btn btn-sm btn-primary" onclick="markNotificationRead(${notif.id})">Mark Read</button></td>
    `
    tbody.appendChild(row)
  })
}

function markAllNotificationsRead() {
  notifications.forEach((n) => (n.read = true))
  loadNotifications()
  updateNotificationCount()
  alert("All notifications marked as read")
}

function clearAllNotifications() {
  if (confirm("Clear all notifications?")) {
    notifications.length = 0
    loadNotifications()
    updateNotificationCount()
  }
}

function markNotificationRead(notifId) {
  const notif = notifications.find((n) => n.id === notifId)
  if (notif) notif.read = true
  loadNotifications()
  updateNotificationCount()
}

function saveNewBin() {
  const binId = document.getElementById("binId").value
  const location = document.getElementById("binLocation").value
  const type = document.getElementById("binType").value
  const capacity = document.getElementById("binCapacity").value

  if (!binId || !location || !type || !capacity) {
    alert("Please fill all fields")
    return
  }

  if (bins.find((b) => b.id === binId)) {
    alert("Bin ID already exists")
    return
  }

  bins.push({
    id: binId,
    location: location,
    type: type,
    status: "empty",
    lastEmptied: "Never",
    capacity: capacity + "%",
    assignedTo: "Unassigned",
  })

  const modal = bootstrap.Modal.getInstance(document.getElementById("addBinModal"))
  modal.hide()
  loadAllBinsTable()
  loadBinsTable()
  loadDashboardData()
  document.getElementById("addBinForm").reset()
  alert("Bin added successfully!")
}

function saveNewJanitor() {
  const name = document.getElementById("janitorName").value
  const email = document.getElementById("janitorEmail").value
  const phone = document.getElementById("janitorPhone").value
  const status = document.getElementById("janitorStatus").value

  if (!name || !email || !phone || !status) {
    alert("Please fill all fields")
    return
  }

  if (janitors.find((j) => j.email === email)) {
    alert("Email already exists")
    return
  }

  const newId = janitors.length > 0 ? Math.max(...janitors.map((j) => j.id)) + 1 : 1
  janitors.push({
    id: newId,
    name: name,
    email: email,
    phone: phone,
    bins: 0,
    status: status,
  })

  const modal = bootstrap.Modal.getInstance(document.getElementById("addJanitorModal"))
  modal.hide()
  loadJanitorsTable()
  loadDashboardData()
  document.getElementById("addJanitorForm").reset()
  alert("Janitor added successfully!")
}

window.openEditJanitorModal = (janitorId) => {
  const janitor = janitors.find((j) => j.id === janitorId)
  if (!janitor) return
  document.getElementById("editJanitorId").value = janitor.id
  document.getElementById("editJanitorName").value = janitor.name
  document.getElementById("editJanitorEmail").value = janitor.email
  document.getElementById("editJanitorPhone").value = janitor.phone
  document.getElementById("editJanitorStatus").value = janitor.status
  const modal = new bootstrap.Modal(document.getElementById("editJanitorModal"))
  modal.show()
}

window.saveJanitorEdit = () => {
  const janitorId = Number.parseInt(document.getElementById("editJanitorId").value)
  const index = janitors.findIndex((j) => j.id === janitorId)
  if (index === -1) return
  janitors[index].name = document.getElementById("editJanitorName").value
  janitors[index].email = document.getElementById("editJanitorEmail").value
  janitors[index].phone = document.getElementById("editJanitorPhone").value
  janitors[index].status = document.getElementById("editJanitorStatus").value
  const modal = bootstrap.Modal.getInstance(document.getElementById("editJanitorModal"))
  modal.hide()
  loadJanitorsTable()
  loadDashboardData()
  alert("Janitor updated successfully!")
}

function editBin(binId) {
  alert("Edit bin: " + binId)
}

window.generateReport = () => {
  const reportName = document.getElementById("reportName").value
  const reportType = document.getElementById("reportType").value
  if (!reportName || !reportType) {
    alert("Please fill all fields")
    return
  }
  const modal = bootstrap.Modal.getInstance(document.getElementById("createReportModal"))
  modal.hide()
  alert("Report generated successfully!")
}

window.exportReport = () => {
  alert("Exporting report...")
}

window.showProfileTab = (tabName) => {
  const tabPanes = document.querySelectorAll(".tab-pane")
  tabPanes.forEach((pane) => pane.classList.remove("show", "active"))
  const targetPane = document.getElementById(tabName)
  if (targetPane) targetPane.classList.add("show", "active")
}

window.filterDashboard = (period) => {
  alert("Filtering dashboard for: " + period)
}
