// Dashboard Functions
async function loadDashboardData() {
  try {
    const response = await fetch("includes/get-dashboard-data.php")
    const data = await response.json()

    if (data.success) {
      document.getElementById("totalBins").textContent = data.totalBins
      document.getElementById("fullBins").textContent = data.fullBins
      document.getElementById("activeJanitors").textContent = data.activeJanitors
      document.getElementById("collectionsToday").textContent = data.collectionsToday

      loadBinsTable(data.bins)
    }
  } catch (error) {
    console.error("Error loading dashboard data:", error)
  }
}

function loadBinsTable(bins) {
  const tbody = document.getElementById("binsTableBody")
  tbody.innerHTML = ""

  if (bins.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No bins found</td></tr>'
    return
  }

  bins.forEach((bin) => {
    const row = `
            <tr>
                <td><span class="badge bg-primary">${bin.bin_code}</span></td>
                <td>${bin.location}</td>
                <td><span class="badge bg-${getStatusColor(bin.status)}">${bin.status}</span></td>
                <td class="d-none d-md-table-cell">${bin.last_emptied ? new Date(bin.last_emptied).toLocaleDateString() : "Never"}</td>
                <td class="d-none d-lg-table-cell">${bin.assigned_to || "Unassigned"}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-primary" onclick="editBin(${bin.bin_id})">Edit</button>
                </td>
            </tr>
        `
    tbody.innerHTML += row
  })
}

function loadAllBins(status = "all") {
  const tbody = document.getElementById("allBinsTableBody")
  if (!tbody) {
    console.error("allBinsTableBody element not found")
    return
  }

  tbody.innerHTML =
    '<tr><td colspan="7" class="text-center py-4"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span></td></tr>'

  const statusParam = status && status !== "all" ? `?status=${status}` : ""
  const endpoint = `includes/get-bins.php${statusParam}`

  fetch(endpoint)
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      return response.text().then((text) => {
        try {
          return JSON.parse(text)
        } catch (e) {
          throw new Error("Server returned invalid JSON. Check if bins table exists in database.")
        }
      })
    })
    .then((data) => {
      if (data.success) {
        displayBinsTable(data.data)
      } else {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">No bins found. Add one to get started.</td></tr>`
      }
    })
    .catch((error) => {
      tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">No bins found. Add one to get started.</td></tr>`
    })
}

function displayBinsTable(bins) {
  const tbody = document.getElementById("allBinsTableBody")
  if (!tbody) return

  tbody.innerHTML = ""

  if (bins.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No bins found</td></tr>'
    return
  }

  bins.forEach((bin) => {
    const statusBadge = getStatusBadge(bin.status)
    const assignedTo = bin.assigned_to_name || "Unassigned"
    const row = document.createElement("tr")
    row.innerHTML = `
      <td>${bin.bin_code}</td>
      <td>${bin.location}</td>
      <td>${bin.type}</td>
      <td>${statusBadge}</td>
      <td>${bin.capacity}%</td>
      <td>${assignedTo}</td>
      <td class="text-end">
        <button class="btn btn-sm btn-primary" onclick="editBin(${bin.bin_id})">Edit</button>
        <button class="btn btn-sm btn-outline-danger" onclick="deleteBin(${bin.bin_id})">Delete</button>
      </td>
    `
    tbody.appendChild(row)
  })
}

function getStatusBadge(status) {
  const badges = {
    full: '<span class="badge bg-danger">Full</span>',
    empty: '<span class="badge bg-success">Empty</span>',
    needs_attention: '<span class="badge bg-warning">Needs Attention</span>',
    in_progress: '<span class="badge bg-info">In Progress</span>',
    out_of_service: '<span class="badge bg-secondary">Out of Service</span>',
  }
  return badges[status] || '<span class="badge bg-secondary">Unknown</span>'
}

function loadJanitorsForBinForm() {
  const dropdown = document.getElementById("binAssignedJanitor")
  if (!dropdown) {
    console.error("binAssignedJanitor element not found")
    return
  }

  fetch("includes/get-janitors.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.success && data.data) {
        dropdown.innerHTML = '<option value="">Select Janitor (Optional)</option>'
        data.data.forEach((janitor) => {
          const option = document.createElement("option")
          option.value = janitor.user_id
          option.textContent = `${janitor.first_name} ${janitor.last_name}`
          dropdown.appendChild(option)
        })
      }
    })
    .catch((error) => {
      console.error("Error loading janitors:", error)
    })
}

window.saveNewBin = () => {
  const binCode = document.getElementById("binCode").value.trim()
  const location = document.getElementById("binLocation").value.trim()
  const type = document.getElementById("binType").value
  const capacity = document.getElementById("binCapacity").value
  const status = document.getElementById("binStatus").value
  const assignedTo = document.getElementById("binAssignedJanitor").value

  if (!binCode || !location || !type || !capacity || !status) {
    alert("Please fill in all required fields")
    return
  }

  fetch("includes/add-bin.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      bin_code: binCode,
      location: location,
      type: type,
      capacity: Number.parseInt(capacity),
      status: status,
      assigned_to: assignedTo || null,
    }),
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      return response.json()
    })
    .then((data) => {
      if (data.success) {
        alert(`Bin "${binCode}" added successfully!`)

        const modalElement = document.getElementById("addBinModal")
        const modal = window.bootstrap.Modal.getInstance(modalElement)
        if (modal) {
          modal.hide()
        }

        // Reset form
        document.getElementById("addBinForm").reset()

        // Reload bins table after a short delay to ensure database insert completes
        setTimeout(() => {
          loadAllBins()
        }, 500)
      } else {
        alert(`Error: ${data.message}`)
      }
    })
    .catch((error) => {
      alert("Error adding bin: " + error.message)
    })
}

window.editBin = (binId) => {
  alert("Edit functionality will be implemented")
}

window.deleteBin = (binId) => {
  if (!confirm("Are you sure you want to delete this bin?")) return
  alert("Delete functionality will be implemented")
}

window.saveNewJanitor = () => {
  const firstName = document.getElementById("janitorFirstName").value.trim()
  const lastName = document.getElementById("janitorLastName").value.trim()
  const email = document.getElementById("janitorEmail").value.trim()
  const phone = document.getElementById("janitorPhone").value.trim()
  const status = document.getElementById("janitorStatus").value

  if (!firstName || !lastName || !email || !phone || !status) {
    alert("Please fill in all required fields")
    return
  }

  fetch("includes/add-janitor.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      first_name: firstName,
      last_name: lastName,
      email: email,
      phone: phone,
      status: status,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        alert("Janitor added successfully!")

        // Close modal
        const modal = window.bootstrap.Modal.getInstance(document.getElementById("addJanitorModal"))
        modal.hide()

        // Reset form
        document.getElementById("addJanitorForm").reset()

        // Reload janitors table
        loadAllJanitors()

        loadJanitorsForBinForm()
      } else {
        alert(`Error: ${data.message}`)
      }
    })
    .catch((error) => {
      console.error("Error:", error)
      alert("Error adding janitor")
    })
}

function loadAllJanitors(filter = "all") {
  const tbody = document.getElementById("janitorsTableBody")
  if (!tbody) return

  const filterParam = filter && filter !== "all" ? `?status=${filter}` : ""

  fetch(`includes/get-janitors.php${filterParam}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        displayJanitorsTable(data.data)
      } else {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">Error: ${data.message}</td></tr>`
      }
    })
    .catch((error) => {
      console.error("Error loading janitors:", error)
      tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-danger">Error loading janitors</td></tr>'
    })
}

function displayJanitorsTable(janitors) {
  const tbody = document.getElementById("janitorsTableBody")
  if (!tbody) return

  tbody.innerHTML = ""

  if (janitors.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No janitors found</td></tr>'
    return
  }

  janitors.forEach((janitor) => {
    const statusBadge =
      janitor.status === "active"
        ? '<span class="badge bg-success">Active</span>'
        : '<span class="badge bg-secondary">Inactive</span>'
    const row = document.createElement("tr")
    row.innerHTML = `
      <td>${janitor.first_name} ${janitor.last_name}</td>
      <td>${janitor.email}</td>
      <td class="d-none d-md-table-cell">${janitor.phone}</td>
      <td class="d-none d-lg-table-cell">${janitor.assigned_bins || 0}</td>
      <td>${statusBadge}</td>
      <td class="text-end">
        <button class="btn btn-sm btn-primary" onclick="editJanitor(${janitor.user_id})">Edit</button>
        <button class="btn btn-sm btn-outline-danger" onclick="deleteJanitor(${janitor.user_id})">Delete</button>
      </td>
    `
    tbody.appendChild(row)
  })
}

window.editJanitor = (janitorId) => {
  alert("Edit functionality will be implemented")
}

window.deleteJanitor = (janitorId) => {
  if (!confirm("Are you sure you want to delete this janitor?")) return
  alert("Delete functionality will be implemented")
}

// Notifications Functions
async function loadNotifications(filter = "all") {
  try {
    const response = await fetch(`includes/get-notifications.php?filter=${filter}`)
    const data = await response.json()

    if (data.success) {
      const tbody = document.getElementById("notificationsTableBody")
      tbody.innerHTML = ""

      if (data.notifications.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No notifications found</td></tr>'
        return
      }

      data.notifications.forEach((notif) => {
        const row = `
                    <tr>
                        <td>${new Date(notif.created_at).toLocaleString()}</td>
                        <td>${notif.bin_code || "N/A"}</td>
                        <td class="d-none d-md-table-cell">${notif.location || "N/A"}</td>
                        <td><span class="badge bg-${getNotificationColor(notif.notification_type)}">${notif.notification_type}</span></td>
                        <td class="d-none d-lg-table-cell"><span class="badge bg-${notif.is_read ? "secondary" : "warning"}">
                            ${notif.is_read ? "Read" : "Unread"}
                        </span></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" onclick="viewNotification(${notif.notification_id})">View</button>
                        </td>
                    </tr>
                `
        tbody.innerHTML += row
      })

      if (data.unread_count > 0) {
        const notifBadge = document.getElementById("notificationCount")
        notifBadge.textContent = data.unread_count
        notifBadge.style.display = "block"
      }
    }
  } catch (error) {
    console.error("Error loading notifications:", error)
  }
}

// Reports Functions
async function loadReports() {
  try {
    const response = await fetch("includes/get-reports.php")
    const data = await response.json()

    if (data.success) {
      const tbody = document.getElementById("reportsTableBody")
      tbody.innerHTML = ""

      if (data.reports.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No reports found</td></tr>'
        return
      }

      data.reports.forEach((report) => {
        const row = `
                    <tr>
                        <td>${report.report_name}</td>
                        <td class="d-none d-md-table-cell"><span class="badge bg-info">${report.report_type}</span></td>
                        <td class="d-none d-lg-table-cell">${new Date(report.created_at).toLocaleDateString()}</td>
                        <td><span class="badge bg-${report.status === "completed" ? "success" : "warning"}">${report.status}</span></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" onclick="downloadReport(${report.report_id})">Download</button>
                        </td>
                    </tr>
                `
        tbody.innerHTML += row
      })
    }
  } catch (error) {
    console.error("Error loading reports:", error)
  }
}

// Profile Functions
async function loadProfile() {
  try {
    const response = await fetch("includes/get-profile.php")
    const data = await response.json()

    if (data.success) {
      const user = data.user
      document.getElementById("profileName").textContent = `${user.first_name} ${user.last_name}`
      document.getElementById("firstName").value = user.first_name
      document.getElementById("lastName").value = user.last_name
      document.getElementById("email").value = user.email
      document.getElementById("phoneNumber").value = user.phone
    }
  } catch (error) {
    console.error("Error loading profile:", error)
  }
}

async function updateProfile(e) {
  e.preventDefault()
  const data = {
    first_name: document.getElementById("firstName").value,
    last_name: document.getElementById("lastName").value,
    email: document.getElementById("email").value,
    phone: document.getElementById("phoneNumber").value,
  }

  try {
    const response = await fetch("includes/update-profile.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data),
    })
    const result = await response.json()

    if (result.success) {
      alert("Profile updated successfully!")
      loadProfile()
    } else {
      alert("Error: " + result.message)
    }
  } catch (error) {
    console.error("Error updating profile:", error)
    alert("Error updating profile")
  }
}

// Helper Functions
function getStatusColor(status) {
  const colors = {
    empty: "success",
    needs_attention: "warning",
    full: "danger",
    in_progress: "info",
    out_of_service: "secondary",
  }
  return colors[status] || "secondary"
}

function getNotificationColor(type) {
  const colors = {
    critical: "danger",
    warning: "warning",
    info: "info",
    success: "success",
  }
  return colors[type] || "secondary"
}

function viewNotification(notifId) {
  console.log("View notification:", notifId)
}

function downloadReport(reportId) {
  console.log("Download report:", reportId)
}

function showProfileTab(tab) {
  document.querySelectorAll(".tab-pane").forEach((el) => el.classList.remove("show", "active"))
  document.getElementById(tab).classList.add("show", "active")
}
