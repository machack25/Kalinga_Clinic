<?php
session_start();
include("db.php");

if (!isset($_SESSION['admin_id'])) {
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="Doctor Dashboard.css">
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
      <div class="user-avatar">
        <i class="fas fa-user-shield"></i>
      </div>
      <div class="user-info">
        <h3><?php echo htmlspecialchars($full_name); ?></h3>
        <span class="user-role"><?php echo ucfirst(str_replace('_', ' ', $role)); ?></span>
      </div>
    </div>

    <nav class="sidebar-nav">
      <a href="doctor dashboard.php" class="nav-item active">
        <i class="fas fa-home"></i>
        <span>Dashboard</span>
      </a>
      <a href="adminhistory.php" class="nav-item">
        <i class="fas fa-clipboard-list"></i>
        <span>Patient Records</span>
      </a>
      <a href="admininventory.php" class="nav-item">
        <i class="fas fa-pills"></i>
        <span>Medicine Inventory</span>
      </a>
      <a href="adminappoint.php" class="nav-item">
        <i class="fas fa-calendar-check"></i>
        <span>Appointments</span>
      </a>
    </nav>

    <div class="sidebar-footer">
      <a href="logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i>
        <span>Logout</span>
      </a>
    </div>
  </div>

  <div class="main-content">
    <header class="page-header">
      <div class="header-content">
        <div class="header-left">
           <button id="menu-toggle" class="menu-toggle">
            <i class="fas fa-bars"></i>
          </button>
          <div class="title-group">
            <h1 class="page-title">
              <i class="fas fa-home"></i>
              Welcome back, <?php echo htmlspecialchars($full_name); ?>
            </h1>
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
          <div class="card-icon">
            <i class="fas fa-user-md"></i>
          </div>
          <div class="card-content">
            <h3>Available Doctors</h3>
            <h1 id="doctorCount">...</h1>
            <p>Ready for consultations</p>
          </div>
        </div>
        
        <div class="dashboard-card" onclick="openModal('user')">
          <div class="card-icon user-card-icon">
            <i class="fas fa-users"></i>
          </div>
          <div class="card-content">
            <h3>Registered Patients</h3>
            <h1 id="userCount">...</h1>
            <p>Total user accounts</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="doctorModal" class="modal" style="display:none;">
    <div class="modal-overlay" onclick="closeModal('doctor')"></div>
    <div class="modal-content hospital-modal">
      <div class="modal-header">
        <div class="modal-icon"><i class="fas fa-user-md"></i></div>
        <h2 class="modal-title">Available Doctors</h2>
        <button class="close-btn" onclick="closeModal('doctor')"><i class="fas fa-times"></i></button>
      </div>
      <div class="modal-body">
        <div id="doctorList" class="doctor-list"></div>
      </div>
    </div>
  </div>

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
                <input type="text" id="userSearchInput" onkeyup="renderUserList()" placeholder="Search by name or email...">
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


  <script>
    const userRole = '<?php echo $role; ?>';

    // Global data stores
    window.doctors = [];
    window.users = [];
    window.bookingRequests = []; // <-- ADDED

    // --- MODIFIED: Added function to fetch all data ---
    function fetchDashboardData() {
        // Fetch Doctor Availability
        fetch("fetch_availability.php")
            .then(res => res.ok ? res.json() : Promise.reject('Network response was not ok'))
            .then(data => {
                window.doctors = data.doctors;
                document.getElementById("doctorCount").innerText = window.doctors.length;
            })
            .catch(error => {
                console.error('Failed to fetch availability:', error);
                document.getElementById("doctorCount").innerText = "Error";
            });

        // Fetch All Users
        fetch("fetch_users.php")
            .then(res => res.ok ? res.json() : Promise.reject('Network response was not ok'))
            .then(data => {
                window.users = data;
                document.getElementById("userCount").innerText = window.users.length;
            })
            .catch(error => {
                console.error('Failed to fetch users:', error);
                document.getElementById("userCount").innerText = "Error";
            });

        // <-- ADDED: Fetch Booking Requests -->
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

      fetchDashboardData(); // Initial data fetch
    });

    function openModal(type) {
      const modalId = type + 'Modal';
      document.getElementById(modalId).style.display = 'flex';
      
      if (type === 'doctor') {
        renderDoctorList();
      } else if (type === 'user') {
        document.getElementById('userSearchInput').value = '';
        showUserList(); 
        renderUserList();
      } else if (type === 'booking') { // <-- ADDED
        renderBookingList();
      }
    }

    function closeModal(type) {
      const modalId = type + 'Modal';
      document.getElementById(modalId).style.display = 'none';
    }

    function getDayName(dayNumber) {
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        return days[dayNumber % 7];
    }
    
    function renderDoctorList() {
      // ... (This function is unchanged from your previous version)
      let container = document.getElementById('doctorList');
      let data = window.doctors;
      container.innerHTML = "";
      if (!data || data.length === 0) {
        container.innerHTML = `<div class="empty-state"><div class="empty-icon"><i class="fas fa-user-md"></i></div><h3>No Doctors Available</h3><p>No doctors are currently on duty.</p></div>`;
        return;
      }
      data.forEach((person, index) => {
        const card = document.createElement('div');
        card.className = 'person-card';
        const name = person.doctor_name;
        const role = person.specialization;
        const dayValue = person.day_of_week;
        const dayDisplay = getDayName(dayValue);
        card.innerHTML = `
          <div class="card-header">
            <div class="card-icon"><i class="fas fa-user-md"></i></div>
            <div class="card-title">
              <h3>${name}</h3>
              <span class="person-role">${role}</span>
            </div>
          </div>
          <div class="card-content">
            <div class="schedule-info">
              <div class="info-item">
                <i class="fas fa-calendar-day"></i><span class="info-label">Day:</span><span class="info-value">${dayDisplay}</span>
              </div>
              <div class="info-item">
                <i class="fas fa-clock"></i><span class="info-label">Time:</span><span class="info-value">${person.start_time.substring(0, 5)} - ${person.end_time.substring(0, 5)}</span>
              </div>
            </div>
          </div>`;
        container.appendChild(card);
      });
    }

    function renderUserList() {
      // ... (This function is unchanged from your previous version)
      const container = document.getElementById('userList');
      const searchTerm = document.getElementById('userSearchInput').value.toLowerCase();
      container.innerHTML = "";
      const filteredUsers = window.users.filter(user => 
          user.full_name.toLowerCase().includes(searchTerm) || 
          user.email.toLowerCase().includes(searchTerm)
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
      // ... (This function is unchanged from your previous version)
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
      // ... (This function is unchanged from your previous version)
      document.getElementById('userDetailsContainer').style.display = 'none';
      document.getElementById('userListContainer').style.display = 'block';
    }

    // --- ADDED: Functions for Booking Modal ---

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
                    <button class="booking-btn cancel-btn" onclick="manageBooking(${booking.appointment_id}, 'cancel')">Cancel</button>
                    <button class="booking-btn confirm-btn" onclick="manageBooking(${booking.appointment_id}, 'confirm')">Confirm</button>
                </div>
            `;
            container.appendChild(item);
        });
    }

    async function manageBooking(appointmentId, action) {
        // Disable buttons to prevent double-click
        const buttons = document.querySelectorAll(`#booking-${appointmentId} .booking-btn`);
        buttons.forEach(btn => btn.disabled = true);

        try {
            const response = await fetch('manage_booking.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    appointment_id: appointmentId,
                    action: action
                })
            });

            const result = await response.json();

            if (result.success) {
                // Remove item from UI
                document.getElementById(`booking-${appointmentId}`).remove();
                
                // Refresh global data and counts
                fetchDashboardData();
            } else {
                alert(`Error: ${result.message}`);
                buttons.forEach(btn => btn.disabled = false); // Re-enable buttons on failure
            }
        } catch (error) {
            console.error('Failed to manage booking:', error);
            alert('A network error occurred. Please try again.');
            buttons.forEach(btn => btn.disabled = false);
        }
    }

  </script>

</body>
</html>