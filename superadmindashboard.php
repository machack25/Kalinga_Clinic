<?php
session_start();
include("db.php");

$con->query("SET time_zone = '+08:00'");

if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: loginadmin.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

$stmt = $con->prepare("SELECT * FROM admin WHERE admin_id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

if (!$admin) {
    session_destroy();
    header("Location: loginadmin.php");
    exit();
}

$full_name = $admin['full_name'];
$role      = $admin['role'];

function fetch_all_doctors($con) {
    $result = $con->query("
        SELECT d.doctor_id AS id, d.doctor_name AS name, d.specialization AS details,
               GROUP_CONCAT( DISTINCT
                   CONCAT(
                       CASE da.day_of_week
                           WHEN 1 THEN 'Mon' WHEN 2 THEN 'Tue' WHEN 3 THEN 'Wed'
                           WHEN 4 THEN 'Thu' WHEN 5 THEN 'Fri' WHEN 6 THEN 'Sat'
                           WHEN 7 THEN 'Sun'
                       END,
                       ' ', TIME_FORMAT(da.start_time, '%h:%i%p'), '-', TIME_FORMAT(da.end_time, '%h:%i%p')
                   ) ORDER BY da.day_of_week SEPARATOR '<br>'
               ) AS schedule
        FROM doctor d
        LEFT JOIN doctor_availability da ON d.doctor_id = da.doctor_id
        GROUP BY d.doctor_id, d.doctor_name, d.specialization
    ");
    return $result->fetch_all(MYSQLI_ASSOC);
}

$all_doctors = fetch_all_doctors($con);

// UPDATED: Fetched 'details' column for medical history
$users_result = $con->query("SELECT id, full_name, username, phone, email, role, created_at, birthdate, age, details FROM users");
$users = $users_result->fetch_all(MYSQLI_ASSOC);

$doctorCount = count($all_doctors);
$userCount = count($users);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Super Admin Dashboard</title>
  <link rel="stylesheet" href="superadmindashboard.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display.swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>

  <div id="mobile-overlay" class="mobile-overlay"></div>

  <div class="sidebar">
    <div class="sidebar-header">
      <div class="logo-container">
        <img src="logoo.PNG" alt="Kalinga Medical Clinic" class="sidebar-logo">
        <div class="logo-text">
          <h2>Kalinga Medical</h2>
          <p>Clinic Management</p>
        </div>
      </div>
    </div>
    <div class="user-profile">
      <div class="user-avatar"><i class="fas fa-user-shield"></i></div>
      <div class="user-info">
        <h3><?php echo htmlspecialchars($full_name); ?></h3>
        <span class="user-role"><?php echo ucfirst(str_replace('_', ' ', $role)); ?></span>
      </div>
    </div>
    <nav class="sidebar-nav">
      <a href="superadmindashboard.php" class="nav-item active"><i class="fas fa-home"></i><span>Dashboard</span></a>
      <a href="superadweeklyreports.php" class="nav-item"><i class="fas fa-clipboard-list"></i><span>Reports</span></a>
      <a href="aichatlogs.php" class="nav-item"><i class="fas fa-robot"></i><span>AI Chat Records</span></a>
      <a href="superadinventory.php" class="nav-item"><i class="fas fa-pills"></i><span>Medicine Inventory</span></a>
      <a href="superadappointment.php" class="nav-item"><i class="fas fa-calendar-check"></i><span>Appointments</span></a>
      <a href="superadaccounts.php" class="nav-item"><i class="fas fa-users"></i><span>Accounts</span></a>
    </nav>
    <div class="sidebar-footer">
      <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>
  </div>

  <div class="main-content">
    <header class="page-header">
      <div class="header-content">
        <div class="header-left">
           <button id="menu-toggle" class="menu-toggle"><i class="fas fa-bars"></i></button>
          <div class="title-group">
            <h1 class="page-title"><i class="fas fa-home"></i> Welcome back, <?php echo htmlspecialchars($full_name); ?></h1>
            <p class="page-subtitle"><?php echo ucfirst(str_replace('_', ' ', $role)); ?> Dashboard - Manage your clinic operations</p>
          </div>
        </div>
      </div>
    </header>
    <div class="content-section">
      <div class="dashboard-grid">
        <div class="dashboard-card" onclick="openModal('booking')">
          <div class="card-icon booking-card-icon">
            <i class="fas fa-bell"></i>
          </div>
          <div class="card-content">
            <h3>Booking Requests</h3>
            <h1 id="bookingCount">...</h1>
            <p>Awaiting confirmation</p>
          </div>
        </div>
        <div class="dashboard-card" onclick="openModal('doctor')">
          <div class="card-icon"><i class="fas fa-user-md"></i></div>
          <div class="card-content"><h3>Clinic Doctors</h3><h1 id="doctorCount"><?php echo $doctorCount; ?></h1><p>View and manage doctors</p></div>
        </div>
        <div class="dashboard-card" onclick="openModal('user')">
          <div class="card-icon user-card-icon"><i class="fas fa-users"></i></div>
          <div class="card-content"><h3>Total Users</h3><h1 id="userCount"><?php echo $userCount; ?></h1><p>Registered patient accounts</p></div>
        </div>
      </div>
    </div>
  </div>

  <div id="bookingModal" class="modal" style="display:none;">
    <div class="modal-overlay" onclick="closeModal('booking')"></div>
    <div class="modal-content hospital-modal">
      <div class="modal-header booking-modal-header">
        <div class="modal-icon"><i class="fas fa-bell"></i></div>
        <h2 class="modal-title">Booking Requests</h2>
        <button class="close-btn" onclick="closeModal('booking')"><i class="fas fa-times"></i></button>
      </div>
      <div class="modal-body">
        <div id="bookingList" class="booking-list"></div>
      </div>
    </div>
  </div>

  <div id="doctorModal" class="modal" style="display:none;"><div class="modal-overlay" onclick="closeModal('doctor')"></div><div class="modal-content hospital-modal"><div class="modal-header"><div class="modal-icon"><i class="fas fa-user-md"></i></div><h2 class="modal-title">Clinic Doctors</h2><button class="add-btn" onclick="showAddForm('doctor')"><i class="fas fa-plus"></i> Add New</button><button class="close-btn" onclick="closeModal('doctor')"><i class="fas fa-times"></i></button></div><div class="modal-body"><div id="doctorList" class="content-list"></div></div></div></div>

  <div id="userModal" class="modal" style="display:none;">
    <div class="modal-overlay" onclick="closeModal('user')"></div>
    <div class="modal-content hospital-modal">
      <div id="userListContainer">
        <div class="modal-header user-modal-header">
          <div class="modal-icon"><i class="fas fa-users"></i></div>
          <h2 class="modal-title">Registered Patients</h2>
          <button class="close-btn" onclick="closeModal('user')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="search-bar-container">
                <i class="fas fa-search"></i>
                <input type="text" id="userSearchInput" onkeyup="renderUserList()" placeholder="Search by name, email, or phone...">
            </div>
            <div id="userList" class="user-list"></div>
        </div>
      </div>
      <div id="userDetailsContainer" style="display:none;">
        <div class="folder-container">
            <div class="folder-tab">
                <button class="back-btn" onclick="showUserList()"><i class="fas fa-arrow-left"></i> Back to List</button>
                <h3 id="detailFullName"></h3>
            </div>
            <div class="folder-content">
                <h4>Patient Information</h4>
                <div class="detail-grid">
                    <div class="detail-item"><label><i class="fas fa-user-circle"></i> Username</label><span id="detailUsername"></span></div>
                    <div class="detail-item"><label><i class="fas fa-envelope"></i> Email Address</label><span id="detailEmail"></span></div>
                    <div class="detail-item"><label><i class="fas fa-phone"></i> Phone Number</label><span id="detailPhone"></span></div>
                    <div class="detail-item"><label><i class="fas fa-calendar-plus"></i> Date Registered</label><span id="detailCreatedAt"></span></div>
                    <div class="detail-item"><label><i class="fas fa-birthday-cake"></i> Birthdate</label><span id="detailBirthdate"></span></div>
                    <div class="detail-item"><label><i class="fas fa-user-check"></i> Age</label><span id="detailAge"></span></div>
                </div>
                
                <h4 class="details-header">Medical History</h4>
                <div class="detail-item">
                    <label><i class="fas fa-notes-medical"></i> Patient-provided details (allergies, family history, etc.)</label>
                    <span id="detailDetails">N/A</span>
                </div>
            </div>
        </div>
      </div>
    </div>
  </div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const mobileOverlay = document.getElementById('mobile-overlay');
    
    menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('active');
        mobileOverlay.classList.toggle('active');
    });
    mobileOverlay.addEventListener('click', () => {
        sidebar.classList.remove('active');
        mobileOverlay.classList.remove('active');
    });

    // REMOVED: window.all_staff
    window.all_doctors = <?php echo json_encode($all_doctors); ?>;
    window.users = <?php echo json_encode($users); ?>;
    window.bookingRequests = []; // ADDED

    fetchBookingData(); // ADDED: Fetch bookings on load
});

const dayMap = { 1: 'Monday', 2: 'Tuesday', 3: 'Wednesday', 4: 'Thursday', 5: 'Friday', 6: 'Saturday', 7: 'Sunday' };
const dayOptions = Object.entries(dayMap).map(([v, n]) => `<option value="${v}">${n}</option>`).join('');

// ADDED: Function to fetch booking data
function fetchBookingData() {
    fetch("fetch_bookings.php")
        .then(res => res.ok ? res.json() : Promise.reject('Network response was not ok'))
        .then(data => {
            window.bookingRequests = data;
            document.getElementById("bookingCount").innerText = window.bookingRequests.length;
            renderBookingList(); // Re-render the list in case modal is open
        })
        .catch(error => {
            console.error('Failed to fetch bookings:', error);
            document.getElementById("bookingCount").innerText = "Error";
        });
}

function openModal(type) {
    document.getElementById(type + 'Modal').style.display = 'flex';
    if (type === 'doctor') {
        renderList(type);
    } else if (type === 'user') {
        // UPDATED: Logic for new user modal
        document.getElementById('userSearchInput').value = '';
        showUserList(); 
        renderUserList();
    } else if (type === 'booking') {
        // ADDED: Logic for booking modal
        renderBookingList();
    }
}

function closeModal(type) {
    document.getElementById(type + 'Modal').style.display = 'none';
}

// UPDATED: This function now ONLY renders doctors
function renderList(type, dataToRender = null) {
    const container = document.getElementById(type + 'List');
    const data = dataToRender ?? (type === 'doctor' ? window.all_doctors : []);
    container.innerHTML = "";
    if (!data || data.length === 0) {
        container.innerHTML = `<div class="empty-state"><h3>No ${type} found.</h3><p>Click "Add New" to create one.</p></div>`;
        return;
    }
    data.forEach(person => {
        const card = document.createElement('div');
        card.className = 'person-card';
        const safeName = person.name.replace(/'/g, "\\'").replace(/"/g, '&quot;');
        
        card.innerHTML = `
          <div class="card-header">
            <div class="card-icon"><i class="fas fa-user-md"></i></div>
            <div class="card-title">
              <h3>${person.name}</h3>
              <span class="person-role">${person.details}</span>
            </div>
            <div class="card-actions">
              <button class="action-btn edit-btn" onclick="showEditForm('${type}', ${person.id})" title="Edit Details"><i class="fas fa-edit"></i></button>
              <button class="action-btn delete-btn" onclick="deletePerson('${type}', ${person.id}, '${safeName}')" title="Delete"><i class="fas fa-trash"></i></button>
            </div>
          </div>
          <div class="card-content">
            <div class="schedule-info">
              <div class="info-item"><i class="fas fa-calendar-alt"></i><span class="info-label">Schedule:</span><div class="info-value">${person.schedule || 'Not Set'}</div></div>
            </div>
          </div>`;
        container.appendChild(card);
    });
}

function showAddForm(type) {
    const container = document.getElementById(type + 'List');
    const typeTitle = type.charAt(0).toUpperCase() + type.slice(1);
    const detailsLabel = type === 'staff' ? 'Position' : 'Specialization';

    container.innerHTML = `
        <form id="addPersonForm" class="hospital-form">
            <div class="form-section">
                <h3 class="section-title">${typeTitle} Information</h3>
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-user"></i> Full Name</label>
                    <input type="text" id="addName" class="form-input" placeholder="Enter full name" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-briefcase"></i> ${detailsLabel}</label>
                    <input type="text" id="addDetails" class="form-input" placeholder="Enter ${detailsLabel.toLowerCase()}" required>
                </div>
            </div>
            <div class="form-section">
                <h3 class="section-title">Availability Schedules</h3>
                <div id="schedule-container"></div>
                <button type="button" class="btn-add-schedule" onclick="addScheduleEntry('schedule-container')">
                    <i class="fas fa-plus-circle"></i> Add Schedule Slot
                </button>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="renderList('${type}')">Cancel</button>
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>`;
    addScheduleEntry('schedule-container'); 
    document.getElementById('addPersonForm').addEventListener('submit', (e) => {
        e.preventDefault();
        addPerson(type);
    });
}

function addScheduleEntry(containerId, schedule = null) {
    const container = document.getElementById(containerId);
    const entry = document.createElement('div');
    entry.className = 'schedule-entry-row';
    entry.innerHTML = `
        <div class="form-group">
            <select class="form-select schedule-day" required>
                <option value="" disabled selected>Day</option>
                ${dayOptions}
            </select>
        </div>
        <div class="form-group">
            <input type="time" class="form-input schedule-start" required>
        </div>
        <div class="form-group">
            <input type="time" class="form-input schedule-end" required>
        </div>
        <button type="button" class="btn-remove-schedule" onclick="this.parentElement.remove()"><i class="fas fa-trash"></i></button>
    `;
    if (schedule) {
        entry.querySelector('.schedule-day').value = schedule.day_of_week;
        entry.querySelector('.schedule-start').value = schedule.start_time.substring(0, 5);
        entry.querySelector('.schedule-end').value = schedule.end_time.substring(0, 5);
        if(schedule.availability_id) entry.dataset.id = schedule.availability_id;
    }
    container.appendChild(entry);
}

async function addPerson(type) {
    const name = document.getElementById('addName').value;
    const details = document.getElementById('addDetails').value;
    
    const schedules = [];
    document.querySelectorAll('#schedule-container .schedule-entry-row').forEach(row => {
        const day = row.querySelector('.schedule-day').value;
        const start_time = row.querySelector('.schedule-start').value;
        const end_time = row.querySelector('.schedule-end').value;
        if (day && start_time && end_time) {
            schedules.push({ day, start_time, end_time });
        }
    });

    if (!name || !details || schedules.length === 0) {
        alert('Please fill out all fields and add at least one schedule.');
        return;
    }

    const payload = { type, name, details, schedules };

    try {
        const response = await fetch('add_person.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        const result = await response.json();
        if (result.success) {
            alert('Successfully added!');
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Add person error:', error);
        alert('An error occurred while saving.');
    }
}

async function showEditForm(type, personId) {
    const container = document.getElementById(type + 'List');
    container.innerHTML = '<div class="empty-state"><h3><i class="fas fa-spinner fa-spin"></i> Loading...</h3></div>';

    try {
        const response = await fetch(`fetch_person_details.php?type=${type}&id=${personId}`);
        const result = await response.json();
        if (!result.success) throw new Error(result.message);

        const person = result.data;
        const detailsLabel = type === 'staff' ? 'Position' : 'Specialization';
        
        container.innerHTML = `
            <form id="editPersonForm" class="hospital-form">
                <input type="hidden" id="personId" value="${person.id}">
                <div class="form-section">
                    <h3 class="section-title">Edit ${person.name}</h3>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-user"></i> Full Name</label>
                        <input type="text" id="editName" class="form-input" value="${person.name}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-briefcase"></i> ${detailsLabel}</label>
                        <input type="text" id="editDetails" class="form-input" value="${person.details}" required>
                    </div>
                </div>
                <div class="form-section">
                    <h3 class="section-title">Manage Schedules</h3>
                    <div id="schedule-container"></div>
                    <button type="button" class="btn-add-schedule" onclick="addScheduleEntry('schedule-container')">
                        <i class="fas fa-plus-circle"></i> Add New Schedule
                    </button>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="renderList('${type}')">Cancel</button>
                    <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                </div>
            </form>`;
        
        window.originalSchedules = person.schedules;
        if(person.schedules && person.schedules.length > 0) {
            person.schedules.forEach(s => addScheduleEntry('schedule-container', s));
        } else {
             addScheduleEntry('schedule-container');
        }

        document.getElementById('editPersonForm').addEventListener('submit', (e) => {
            e.preventDefault();
            savePersonChanges(type, person.id);
        });

    } catch (error) {
        container.innerHTML = `<div class="empty-state"><h3>Error loading details.</h3><p>${error.message}</p></div>`;
    }
}

async function savePersonChanges(type, personId) {
    const name = document.getElementById('editName').value;
    const details = document.getElementById('editDetails').value;
    
    const updatedSchedules = [];
    const newSchedules = [];
    const currentScheduleIds = new Set();

    document.querySelectorAll('#schedule-container .schedule-entry-row').forEach(row => {
        const id = row.dataset.id ? parseInt(row.dataset.id) : null;
        const day = row.querySelector('.schedule-day').value;
        const start = row.querySelector('.schedule-start').value;
        const end = row.querySelector('.schedule-end').value;

        if (id) {
            currentScheduleIds.add(id);
            updatedSchedules.push({ availability_id: id, start_time: start, end_time: end });
        } else if (day && start && end) {
            newSchedules.push({ day_of_week: day, start_time: start, end_time: end });
        }
    });

    const deletedScheduleIds = window.originalSchedules
        .map(s => s.availability_id)
        .filter(id => !currentScheduleIds.has(id));

    const payload = { type, id: personId, name, details, updatedSchedules, newSchedules, deletedScheduleIds };

    try {
        const response = await fetch('update_availability.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        const result = await response.json();

        if (result.success) {
            alert('Changes saved successfully!');
            location.reload();
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Save changes error:', error);
        alert('Failed to save changes: ' + error.message);
    }
}


async function deletePerson(type, personId, personName) {
    if (!confirm(`Are you sure you want to delete ${personName}? This will also remove all their associated schedules.`)) {
        return;
    }

    try {
        const response = await fetch('delete_person.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type: type, id: personId })
        });
        const result = await response.json();
        if (result.success) {
            alert(`${personName} has been deleted.`);
            location.reload(); 
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Delete person error:', error);
        alert('Failed to delete: ' + error.message);
    }
}

// --- ADDED: Functions for Booking Modal (Copied from doctor dashboard) ---
function renderBookingList() {
    const container = document.getElementById('bookingList');
    container.innerHTML = ""; // Clear list

    if (!window.bookingRequests || window.bookingRequests.length === 0) {
        container.innerHTML = `<div class="empty-state"><div class="empty-icon"><i class="fas fa-check-circle"></i></div><h3>All Caught Up!</h3><p>There are no new booking requests.</p></div>`;
        return;
    }

    window.bookingRequests.forEach(booking => {
        const item = document.createElement('div');
        item.className = 'booking-item';
        item.id = `booking-${booking.appointment_id}`; // Add ID for easy removal

        const apptDate = new Date(booking.appointment_date + 'T' + booking.appointment_time).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        const apptTime = new Date(booking.appointment_date + 'T' + booking.appointment_time).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

        item.innerHTML = `
            <div class="booking-item-details">
                <span class="booking-patient-name">${booking.patient_name}</span>
                <span class="booking-doctor-name">w/ ${booking.doctor_name}</span>
                <span class="booking-time"><i class="fas fa-calendar-alt"></i> ${apptDate} at ${apptTime}</span>
                <p class="booking-reason"><i class="fas fa-info-circle"></i> ${booking.reason || 'No reason provided.'}</p>
            </div>
            <div class="booking-item-actions">
                <button class="booking-btn cancel-btn" onclick="manageBooking(${booking.appointment_id}, 'Cancelled')">Cancel</button>
                <button class="booking-btn confirm-btn" onclick="manageBooking(${booking.appointment_id}, 'Scheduled')">Confirm</button>
            </div>
        `;
        container.appendChild(item);
    });
}

async function manageBooking(appointmentId, action) {
    const buttons = document.querySelectorAll(`#booking-${appointmentId} .booking-btn`);
    buttons.forEach(btn => btn.disabled = true);
    
    // Note: The action for confirm is 'Scheduled', for cancel it's 'Cancelled'
    // This matches the update_status.php script logic if we reuse it,
    // or a new manage_booking.php script.
    
    // Using 'manage_booking.php' as per doctor dashboard
    try {
        const response = await fetch('manage_booking.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                appointment_id: appointmentId,
                // Sending 'confirm' or 'cancel' as the action
                action: action === 'Scheduled' ? 'confirm' : 'cancel' 
            })
        });

        const result = await response.json();

        if (result.success) {
            document.getElementById(`booking-${appointmentId}`).remove();
            fetchBookingData(); // Refresh global data and counts
        } else {
            alert(`Error: ${result.message}`);
            buttons.forEach(btn => btn.disabled = false);
        }
    } catch (error) {
        console.error('Failed to manage booking:', error);
        alert('A network error occurred. Please try again.');
        buttons.forEach(btn => btn.disabled = false);
    }
}

// --- ADDED/UPDATED: Functions for new User Modal (Copied from doctor dashboard) ---
function renderUserList() {
  const container = document.getElementById('userList');
  const searchTerm = document.getElementById('userSearchInput').value.toLowerCase();
  container.innerHTML = "";
  
  const filteredUsers = window.users.filter(user => 
      user.full_name.toLowerCase().includes(searchTerm) || 
      user.email.toLowerCase().includes(searchTerm) ||
      (user.phone && user.phone.toLowerCase().includes(searchTerm))
  );
  
  if (filteredUsers.length === 0) {
      container.innerHTML = `<div class="empty-state"><div class="empty-icon"><i class="fas fa-user-slash"></i></div><h3>No Patients Found</h3><p>No patients match your search criteria.</p></div>`;
      return;
  }
  
  filteredUsers.forEach(user => {
      const item = document.createElement('div');
      item.className = 'user-list-item';
      item.onclick = () => showUserDetails(user.id);
      item.innerHTML = `
          <div class="user-item-avatar"><i class="fas fa-user"></i></div>
          <div class="user-item-info">
              <span class="user-item-name">${user.full_name}</span>
              <span class="user-item-email">${user.email}</span>
          </div>
          <div class="user-item-arrow"><i class="fas fa-chevron-right"></i></div>
      `;
      container.appendChild(item);
  });
}

function showUserDetails(userId) {
  const user = window.users.find(u => u.id === userId);
  if (!user) return;
  document.getElementById('detailFullName').innerText = user.full_name;
  document.getElementById('detailUsername').innerText = user.username || 'N/A';
  document.getElementById('detailEmail').innerText = user.email;
  document.getElementById('detailPhone').innerText = user.phone || 'N/A';
  const registrationDate = new Date(user.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
  document.getElementById('detailCreatedAt').innerText = registrationDate;
  let birthdateStr = 'N/A';
  if (user.birthdate) {
      birthdateStr = new Date(user.birthdate).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
  }
  document.getElementById('detailBirthdate').innerText = birthdateStr;
  document.getElementById('detailAge').innerText = user.age ? user.age : 'N/A';
  
  // ADDED: Populate the details field
  document.getElementById('detailDetails').innerText = user.details && user.details.trim() ? user.details : 'N/A';

  document.getElementById('userListContainer').style.display = 'none';
  document.getElementById('userDetailsContainer').style.display = 'block';
}

function showUserList() {
  document.getElementById('userDetailsContainer').style.display = 'none';
  document.getElementById('userListContainer').style.display = 'block';
}

// REMOVED: searchUser() function, as it's replaced by renderUserList()
</script>
</body>
</html>