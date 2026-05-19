<?php
session_start();
include("db.php");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Enable error reporting
date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['admin_id'])) {
    header("Location: loginadmin.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

// Fetch Admin Info
$stmt = $con->prepare("SELECT full_name, role FROM admin WHERE admin_id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close(); // Close statement

if (!$admin) {
    session_destroy();
    header("Location: loginadmin.php");
    exit();
}

$full_name = $admin['full_name'];
$role      = $admin['role'];

// Fetch Appointments
$query = "
    SELECT
        a.appointment_id, a.appointment_date, a.appointment_time, a.reason, a.status,
        u.full_name AS patient_name, d.doctor_name,
        a.patient_id, a.doctor_id
    FROM appointment a
    JOIN users u ON a.patient_id = u.id
    JOIN doctor d ON a.doctor_id = d.doctor_id
    ORDER BY a.appointment_date DESC, a.appointment_time ASC
";
$result = $con->query($query);
$appointments = [];
while ($row = $result->fetch_assoc()) {
    $date = $row['appointment_date'];
    if (!isset($appointments[$date])) {
        $appointments[$date] = [];
    }
    $appointments[$date][] = [
        "id"         => $row['appointment_id'],
        "patient_id" => $row['patient_id'],
        "doctor_id"  => $row['doctor_id'],
        "time"       => date("h:i A", strtotime($row['appointment_time'])),
        "time_24hr"  => date("H:i", strtotime($row['appointment_time'])), // Added for comparison
        "patient"    => $row['patient_name'],
        "doctor"     => $row['doctor_name'],
        "reason"     => $row['reason'],
        "status"     => $row['status']
    ];
}
$result->close(); // Close result set

// Fetch Doctors
$all_doctors_list = [];
$doc_result = $con->query("SELECT doctor_id, doctor_name FROM doctor ORDER BY doctor_name");
while ($doc_row = $doc_result->fetch_assoc()) {
    $all_doctors_list[] = $doc_row;
}
$doc_result->close(); // Close result set

// Fetch Doctor Schedules
$all_doctor_schedules = [];
$sched_result = $con->query("SELECT doctor_id, day_of_week, start_time, end_time FROM doctor_availability");
while ($sched_row = $sched_result->fetch_assoc()) {
    $all_doctor_schedules[$sched_row['doctor_id']][(int)$sched_row['day_of_week']] = [
        'start_time' => $sched_row['start_time'],
        'end_time'   => $sched_row['end_time']
    ];
}
$sched_result->close(); // Close result set

// Fetch Patients
$all_patients_list = [];
$patient_result = $con->query("SELECT id, full_name FROM users WHERE role = 'user' ORDER BY full_name");
while ($p_row = $patient_result->fetch_assoc()) {
    $all_patients_list[] = $p_row;
}
$patient_result->close(); // Close result set

$con->close(); // Close connection after fetching all initial data
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - Appointments | Kalinga Medical Clinic</title>
  <link rel="stylesheet" href="superadappointment.css"> <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
  <div id="mobile-overlay" class="mobile-overlay"></div>

  <div class="sidebar">
    <div class="sidebar-header">
      <div class="logo-container">
        <img src="logoo.PNG" alt="Kalinga Medical Clinic" class="sidebar-logo">
        <div class="logo-text"><h2>Kalinga Medical</h2><p>Clinic Management</p></div>
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
       <?php if ($role === 'super_admin'): ?>
            <a href="superadmindashboard.php" class="nav-item"><i class="fas fa-home"></i><span>Dashboard</span></a>
            <a href="superadweeklyreports.php" class="nav-item"><i class="fas fa-clipboard-list"></i><span>Reports</span></a>
            <a href="aichatlogs.php" class="nav-item"><i class="fas fa-robot"></i><span>AI Chat Records</span></a>
            <a href="superadinventory.php" class="nav-item"><i class="fas fa-pills"></i><span>Medicine Inventory</span></a>
            <a href="superadappointment.php" class="nav-item active"><i class="fas fa-calendar-check"></i><span>Appointments</span></a>
            <a href="superadaccounts.php" class="nav-item"><i class="fas fa-users"></i><span>Accounts</span></a>
       <?php else: // Assuming 'admin' role links to different pages ?>
            <a href="doctor dashboard.php" class="nav-item"><i class="fas fa-home"></i><span>Dashboard</span></a>
            <a href="adminhistory.php" class="nav-item"><i class="fas fa-clipboard-list"></i><span>Patient Records</span></a>
            <a href="admininventory.php" class="nav-item"><i class="fas fa-pills"></i><span>Medicine Inventory</span></a>
            <a href="adminappoint.php" class="nav-item active"><i class="fas fa-calendar-check"></i><span>Appointments</span></a>
       <?php endif; ?>
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
            <h1 class="page-title"><i class="fas fa-calendar-check"></i> Manage Appointments</h1>
            <p class="page-subtitle">Schedule and manage all patient appointments</p>
          </div>
        </div>
        <div class="header-actions">
          <button class="btn-primary" onclick="openAddModal()"><i class="fas fa-plus"></i><span> Add Appointment</span></button>
        </div>
      </div>
    </header>

    <div class="content-section">
      <div class="records-container">
        <div class="records-header">
          <h2 class="records-title"><i class="fas fa-calendar-alt"></i> Appointment Schedule</h2>
        </div>
        <div class="appointments-container">
          <div class="search-section">
            <div class="search-container">
              <div class="search-input-group">
                <i class="fas fa-calendar"></i>
                <input type="date" id="calendarDate" onchange="viewAppointmentsByDate()">
              </div>
              <div class="filter-options">
                <select id="doctorFilter" onchange="viewAppointmentsByDate()" class="filter-select">
                  <option value="all">All Doctors</option>
                  <?php
                    foreach ($all_doctors_list as $doc) {
                        echo "<option value='" . htmlspecialchars($doc['doctor_name']) . "'>" . htmlspecialchars($doc['doctor_name']) . "</option>";
                    }
                  ?>
                </select>
              </div>
            </div>
          </div>
          <div class="appointments-grid" id="appointmentsList">
            <div class="empty-state">
              <div class="empty-icon"><i class="fas fa-calendar-alt"></i></div>
              <h3>Select a Date</h3>
              <p>Choose a date to view appointments for that day.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal" id="addAppointmentModal" style="display:none;">
    <div class="modal-overlay" onclick="closeAddModal()"></div>
    <div class="modal-content hospital-modal">
        <div class="modal-header">
            <div class="modal-icon"><i class="fas fa-calendar-plus"></i></div>
            <h2 class="modal-title">Add New Appointment</h2>
            <button class="close-btn" onclick="closeAddModal()"><i class="fas fa-times"></i></button>
        </div>
        <form id="addAppointmentForm" class="hospital-form">
          <div class="modal-body">
            <div class="form-section">
                <h3 class="section-title">Patient Information</h3>
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-user"></i> Select Patient</label>
                    <select id="newPatient" name="patient" class="form-select" required>
                        <option value="" disabled selected>Choose a patient...</option>
                        <?php foreach ($all_patients_list as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['full_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-section">
                <h3 class="section-title">Appointment Details</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-calendar"></i> Date</label>
                        <input type="date" id="newDate" name="date" class="form-input" required min="<?php echo date('Y-m-d'); ?>" onchange="updateAvailableDoctors()">
                    </div>
                     <div class="form-group">
                        <label class="form-label"><i class="fas fa-clock"></i> Select an available time slot:</label>
                        <div id="newTimeSlotContainer" class="time-slot-container">
                            <p class="helper-text">Please select a date first.</p>
                        </div>
                        <input type="hidden" id="selectedTimeSlot" name="time" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-user-md"></i> Doctor</label>
                    <select id="newDoctor" name="doctor" class="form-select" required onchange="generateAndTimeSlotsForDoctor()">
                        <option value="">-- Choose Date First --</option>
                    </select>
                </div>
            </div>
            <div class="form-section">
                <h3 class="section-title">Additional Information</h3>
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-file-medical"></i> Reason</label>
                    <textarea id="newReason" name="reason" class="form-textarea" placeholder="Enter reason for appointment..." required></textarea>
                </div>
            </div>
          </div>
          <div class="form-actions">
              <button type="button" class="btn-secondary" onclick="closeAddModal()">Cancel</button>
              <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save Appointment</button>
          </div>
        </form>
    </div>
</div>

<div class="modal" id="consultationModal" style="display:none;">
    <div class="modal-overlay" onclick="closeConsultationModal()"></div>
    <div class="modal-content hospital-modal">
        <div class="modal-header">
            <div class="modal-icon"><i class="fas fa-notes-medical"></i></div>
            <h2 class="modal-title">Add Consultation Details</h2>
            <button class="close-btn" onclick="closeConsultationModal()"><i class="fas fa-times"></i></button>
        </div>
        <form id="consultationForm" class="hospital-form">
            <input type="hidden" id="consultationAppointmentId" name="appointment_id">
            <input type="hidden" id="consultationPatientId" name="patient_id">
            <input type="hidden" id="consultationDoctorId" name="doctor_id">
            <div class="modal-body">
                <div class="form-section">
                    <h3 class="section-title">Consultation For: <span id="consultationPatientName" style="font-weight: normal;"></span></h3>
                    <p>with Dr. <span id="consultationDoctorName"></span></p>
                </div>
                <div class="form-section">
                    <h3 class="section-title">Findings</h3>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-stethoscope"></i> Diagnosis</label>
                        <textarea id="consultationDiagnosis" name="diagnosis" class="form-textarea" placeholder="Enter diagnosis..." required></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-prescription-bottle-alt"></i> Prescription</label>
                        <textarea id="consultationPrescription" name="prescription" class="form-textarea" placeholder="Enter prescription details..."></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-file-alt"></i> Notes</label>
                        <textarea id="consultationNotes" name="notes" class="form-textarea" placeholder="Enter additional notes..."></textarea>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeConsultationModal()">Cancel</button>
                <button type="submit" class="btn-primary"><i class="fas fa-check-circle"></i> Complete Appointment</button>
            </div>
        </form>
    </div>
</div>


<script>
    const allDoctors = <?php echo json_encode($all_doctors_list); ?>;
    const allSchedules = <?php echo json_encode($all_doctor_schedules); ?>;
    let allAppointments = <?php echo json_encode($appointments); ?>;
    const BOOKING_INTERVAL = 30; // In minutes

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

      document.getElementById("addAppointmentForm").addEventListener("submit", handleAddFormSubmit);
      document.getElementById("consultationForm").addEventListener("submit", handleConsultationFormSubmit);

      // Set default date to today
      const today = new Date().toISOString().split('T')[0];
      document.getElementById('calendarDate').value = today;
      viewAppointmentsByDate(); // Load today's appointments initially
    });

    // --- UPDATED Function ---
    async function updateAvailableDoctors() {
        const dateInput = document.getElementById("newDate").value;
        const doctorSelect = document.getElementById("newDoctor");
        const timeSlotContainer = document.getElementById("newTimeSlotContainer"); // Target container div
        const hiddenTimeInput = document.getElementById("selectedTimeSlot"); // Target hidden input


        // Reset doctor and time slots
        doctorSelect.innerHTML = '<option value="">-- Choose Date --</option>';
        timeSlotContainer.innerHTML = '<p class="helper-text">Please select a date first.</p>'; // Reset time slot display
        hiddenTimeInput.value = ''; // Clear hidden time
        doctorSelect.disabled = true;

        if (!dateInput) {
            return; // Exit if no date is selected
        }

        const date = new Date(dateInput + 'T00:00:00');
        const dayOfWeekJS = date.getDay();
        const dayOfWeekDB = (dayOfWeekJS === 0) ? 7 : dayOfWeekJS;

        // Filter doctors available on the selected DAY
        const availableDoctorsToday = allDoctors.filter(doctor => {
            const doctorSchedule = allSchedules[doctor.doctor_id];
            // Check if schedule exists AND the specific day exists within that schedule
            return doctorSchedule && typeof doctorSchedule === 'object' && doctorSchedule[dayOfWeekDB];
        });


        if (availableDoctorsToday.length === 0) {
            doctorSelect.innerHTML = '<option value="">-- No Doctors Available --</option>';
            timeSlotContainer.innerHTML = '<p class="helper-text error">No doctors available on this day.</p>'; // Update time slot message
        } else {
            doctorSelect.innerHTML = '<option value="" disabled selected>-- Select Available Doctor --</option>';
            availableDoctorsToday.forEach(doctor => {
                const option = new Option(doctor.doctor_name, doctor.doctor_id);
                doctorSelect.appendChild(option);
            });
            doctorSelect.disabled = false;
            // Update placeholder text for time slots, prompt doctor selection
            timeSlotContainer.innerHTML = '<p class="helper-text">Please select a doctor.</p>';
        }
    }

    // --- UPDATED Function to generate time slot RADIO BUTTONS ---
    async function generateAndTimeSlotsForDoctor() {
        const doctorId = document.getElementById("newDoctor").value;
        const selectedDate = document.getElementById("newDate").value;
        const timeSlotContainer = document.getElementById("newTimeSlotContainer"); // Target the container div
        const hiddenTimeInput = document.getElementById("selectedTimeSlot"); // Target hidden input

        timeSlotContainer.innerHTML = '<p class="helper-text loading">Loading slots...</p>';
        hiddenTimeInput.value = ''; // Clear hidden input

        if (!doctorId || !selectedDate) {
            timeSlotContainer.innerHTML = '<p class="helper-text">Please select a date and doctor first.</p>';
            return;
        }

        const date = new Date(selectedDate + 'T00:00:00');
        const dayOfWeekJS = date.getDay();
        const dayOfWeekDB = (dayOfWeekJS === 0) ? 7 : dayOfWeekJS;
        const availability = allSchedules[doctorId]?.[dayOfWeekDB];

        if (!availability) {
            timeSlotContainer.innerHTML = '<p class="helper-text error">Doctor is unavailable on this day.</p>';
            return;
        }

        const allSlots = generateTimeSlots(availability.start_time, availability.end_time);
        if (allSlots.length === 0) {
             timeSlotContainer.innerHTML = '<p class="helper-text">No time slots defined for this doctor\'s schedule.</p>';
             return;
        }

        try {
            // Fetch booked slots for this doctor on this date
            // *** IMPORTANT: Changed to use get_admin_booked_slots.php ***
            const response = await fetch(`get_admin_booked_slots.php?doctor_id=${doctorId}&date=${selectedDate}`);
            if (!response.ok) {
                // Try to read error message if response is JSON
                let errorMsg = 'Network error fetching booked slots.';
                try {
                    const errorData = await response.json();
                    errorMsg = errorData.error || errorMsg;
                } catch(e) { /* Ignore if response is not JSON */ }
                throw new Error(errorMsg + ` (Status: ${response.status})`);
            }
            const bookedSlots = await response.json();

            // Check if the response was actually an error object disguised as success
             if (bookedSlots.error) {
                throw new Error(bookedSlots.error);
             }


            // Populate the container with radio buttons
            timeSlotContainer.innerHTML = ''; // Clear loading/previous message
            let hasAvailable = false;
            allSlots.forEach(slot => {
                const isBooked = bookedSlots.includes(slot);
                const slotEl = document.createElement('div');
                slotEl.className = 'time-slot';

                const input = document.createElement('input');
                input.type = 'radio';
                input.name = 'time_slot_radio'; // Use a different name for radios
                input.id = `new_slot-${slot.replace(':','')}`; // Unique ID for modal
                input.value = slot;
                input.disabled = isBooked;
                input.required = true; // Make selection required
                input.onchange = () => {
                    hiddenTimeInput.value = slot; // Update hidden input on change
                };

                const label = document.createElement('label');
                label.htmlFor = `new_slot-${slot.replace(':','')}`; // Match input ID
                label.textContent = new Date(`1970-01-01T${slot}:00`).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });

                if (isBooked) {
                    label.title = 'This slot is already booked or pending confirmation.';
                    slotEl.classList.add('disabled');
                } else {
                    hasAvailable = true;
                }
                slotEl.appendChild(input);
                slotEl.appendChild(label);
                timeSlotContainer.appendChild(slotEl);
            });

            if(!hasAvailable) {
                 timeSlotContainer.innerHTML = '<p class="helper-text">No available time slots for this doctor on this day.</p>';
            }

        } catch (error) {
            console.error("Error loading time slots:", error);
            timeSlotContainer.innerHTML = `<p class="helper-text error">Could not load time slots. ${error.message || 'Please try again.'}</p>`;
        }
    }


    // --- Helper to generate slots array ---
    function generateTimeSlots(start, end) {
        const slots = [];
        if (!start || !end || start >= end) {
            console.warn("Invalid start/end times for slot generation:", start, end);
            return slots;
        }
        try {
            let currentTime = new Date(`1970-01-01T${start}`);
            const endTime = new Date(`1970-01-01T${end}`);
            let safety = 0;
            while (currentTime < endTime && safety < 100) { // Limit iterations
                slots.push(currentTime.toTimeString().substring(0, 5)); // "HH:MM" format
                currentTime.setMinutes(currentTime.getMinutes() + BOOKING_INTERVAL);
                safety++;
            }
             if (safety >= 100) console.error("Exceeded maximum iterations generating time slots.");
        } catch (e) {
             console.error("Error generating time slots:", e);
        }
        return slots;
    }


    // --- Renders appointments on the grid ---
    function viewAppointmentsByDate() {
        const dateInput = document.getElementById("calendarDate").value;
        const doctorFilter = document.getElementById("doctorFilter").value;
        const appointmentsList = document.getElementById("appointmentsList");

        if (!dateInput) {
            appointmentsList.innerHTML = `<div class="empty-state"><div class="empty-icon"><i class="fas fa-calendar-day"></i></div><h3>Select a Date</h3><p>Choose a date to view scheduled appointments.</p></div>`;
            return;
        }

        const selectedDateAppointments = allAppointments[dateInput] || [];
        const filteredAppointments = (doctorFilter === "all") ?
            selectedDateAppointments :
            selectedDateAppointments.filter(appt => appt.doctor === doctorFilter);

        // Sort by time before rendering
        filteredAppointments.sort((a, b) => {
             const timeA = a.time_24hr;
             const timeB = b.time_24hr;
             return timeA.localeCompare(timeB);
        });


        if (filteredAppointments.length === 0) {
            appointmentsList.innerHTML = `<div class="empty-state"><div class="empty-icon"><i class="fas fa-calendar-times"></i></div><h3>No Appointments</h3><p>No appointments found for this date and filter.</p></div>`;
        } else {
            appointmentsList.innerHTML = ''; // Clear previous list
            filteredAppointments.forEach(appointment => {
                const statusClass = appointment.status.toLowerCase().replace(/\s+/g, '-'); // Replace spaces too
                const isActionable = ['Scheduled', 'Pending'].includes(appointment.status);

                const apptCard = document.createElement("div");
                apptCard.className = `appointment-card status-${statusClass}`;
                apptCard.innerHTML = `
                  <div class="card-header">
                    <div class="card-icon"><i class="fas fa-user-clock"></i></div>
                    <div class="card-title">
                      <h3>${htmlspecialchars(appointment.patient)}</h3>
                      <span class="appointment-time">${appointment.time}</span>
                    </div>
                    ${isActionable ? `
                    <div class="card-actions">
                      <button class="action-btn approve-btn" onclick="openConsultationModal(${appointment.id})" title="Add Consultation / Complete"><i class="fas fa-check"></i></button>
                      <button class="action-btn cancel-btn" onclick="updateStatus(${appointment.id}, 'Cancelled')" title="Cancel"><i class="fas fa-times"></i></button>
                    </div>` : ''}
                  </div>
                  <div class="card-content">
                    <div class="appointment-info">
                      <div class="info-item"><i class="fas fa-user-md"></i><span class="info-label">Doctor:</span><span class="info-value">${htmlspecialchars(appointment.doctor)}</span></div>
                      <div class="info-item"><i class="fas fa-file-medical"></i><span class="info-label">Reason:</span><span class="info-value">${htmlspecialchars(appointment.reason)}</span></div>
                      <div class="info-item"><i class="fas fa-info-circle"></i><span class="info-label">Status:</span><span class="status-badge status-${statusClass}">${htmlspecialchars(appointment.status)}</span></div>
                    </div>
                  </div>`;
                appointmentsList.appendChild(apptCard);
            });
        }
    }

    // Function to escape HTML special characters
    function htmlspecialchars(str) {
        if (typeof str !== 'string') return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return str.replace(/[&<>"']/g, m => map[m]);
    }


    // --- Handles status updates (currently only Cancelled) ---
    function updateStatus(apptId, newStatus) {
        if (newStatus !== 'Cancelled') {
            console.error("This function should only be used for cancellation.");
            return;
        }
        if (!confirm(`Are you sure you want to mark this appointment as ${newStatus}?`)) return;

        fetch("update_status.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `id=${apptId}&status=${newStatus}`
        })
        .then(res => {
            if (!res.ok) { throw new Error(`HTTP error! status: ${res.status}`); }
            return res.json();
        })
        .then(data => {
            if (data.success) {
                alert(`Appointment ${newStatus}. The page will reload.`);
                location.reload(); // Reload to reflect changes
            } else {
                alert(`Failed to update status: ${data.message || 'Unknown error'}`);
            }
        })
        .catch(error => {
            console.error('Error updating status:', error);
            alert('An error occurred while updating the status.');
        });
    }

    // --- Modal Control Functions ---
    function openAddModal() {
        document.getElementById("addAppointmentForm").reset(); // Clear form
        document.getElementById("newDoctor").innerHTML = '<option value="">-- Choose Date First --</option>'; // Reset doctor list
        document.getElementById("newTimeSlotContainer").innerHTML = '<p class="helper-text">Please select a date first.</p>'; // Reset time list display
        document.getElementById("selectedTimeSlot").value = ''; // Clear hidden time input
        document.getElementById("newDoctor").disabled = true;
        document.getElementById("addAppointmentModal").style.display = "flex";
    }

    function closeAddModal() {
        document.getElementById("addAppointmentModal").style.display = "none";
    }

    // --- UPDATED Function to get value from hidden input ---
    function handleAddFormSubmit(e) {
        e.preventDefault();
        const patient = document.getElementById("newPatient").value;
        const doctor = document.getElementById("newDoctor").value;
        const date = document.getElementById("newDate").value;
        const timeSlot = document.getElementById("selectedTimeSlot").value; // Get value from hidden input
        const reason = document.getElementById("newReason").value;

        if (!patient || !doctor || !date || !timeSlot || !reason) {
            alert("Please fill out all required fields, including selecting a time slot.");
            return;
        }

        fetch("add_appointment.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `patient=${patient}&doctor=${doctor}&date=${date}&time=${timeSlot}&reason=${encodeURIComponent(reason)}`
        })
        .then(res => {
            if (!res.ok) { throw new Error(`HTTP error! status: ${res.status}`); }
            return res.json();
        })
        .then(data => {
            if (data.success) {
                alert("Appointment added successfully!");
                location.reload();
            } else {
                alert(`Error: ${data.message || 'An unknown error occurred.'}`);
            }
        })
        .catch(error => {
            console.error("Error submitting appointment:", error);
            alert("A network or server error occurred. Please try again.");
        });
    }


    function openConsultationModal(apptId) {
        let appointmentDetails = null;
        for (const date in allAppointments) {
            const found = allAppointments[date].find(appt => appt.id == apptId); // Loose comparison ok here
            if (found) {
                appointmentDetails = found;
                break;
            }
        }

        if (appointmentDetails) {
            document.getElementById('consultationAppointmentId').value = appointmentDetails.id;
            document.getElementById('consultationPatientId').value = appointmentDetails.patient_id;
            document.getElementById('consultationDoctorId').value = appointmentDetails.doctor_id;
            document.getElementById('consultationPatientName').innerText = appointmentDetails.patient;
            document.getElementById('consultationDoctorName').innerText = appointmentDetails.doctor;
            document.getElementById('consultationDiagnosis').value = ''; // Clear previous entries
            document.getElementById('consultationPrescription').value = '';
            document.getElementById('consultationNotes').value = '';
            document.getElementById('consultationModal').style.display = 'flex';
        } else {
            console.error("Appointment details not found for ID:", apptId, "in data:", allAppointments);
            alert('Error: Could not find appointment details. Data might be out of sync.');
        }
    }

    function closeConsultationModal() {
        document.getElementById('consultationModal').style.display = 'none';
    }

    function handleConsultationFormSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const data = new URLSearchParams(formData);

        // Client-side check for diagnosis
        if (!formData.get('diagnosis') || formData.get('diagnosis').trim() === '') {
            alert('Diagnosis cannot be empty.');
            return;
        }

        fetch("add_consultation.php", {
            method: "POST",
            body: data
        })
        .then(res => {
             if (!res.ok) { throw new Error(`HTTP error! status: ${res.status}`); }
             return res.json();
         })
        .then(response => {
            if (response.success) {
                alert("Consultation saved and appointment marked as complete. The page will now reload.");
                location.reload(); // Reload to show updated status
            } else {
                alert(`Error: ${response.message || 'An unknown error occurred.'}`);
            }
        })
        .catch(error => {
            console.error('Error submitting consultation:', error);
            alert('An error occurred while submitting the consultation form.');
        });
    }
</script>
</body>
</html>