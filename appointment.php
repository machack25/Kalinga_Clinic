<?php
session_start();
include("db.php");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['user_id'])) {
    header("Location: loginpage.php");
    exit();
}

$patient_id = $_SESSION['user_id'];

$userStmt = $con->prepare("SELECT full_name FROM users WHERE id = ?");
$userStmt->bind_param("i", $patient_id);
$userStmt->execute();
$userStmt->bind_result($full_name);
$userStmt->fetch();
$userStmt->close();

$doctor_list = [];
$availabilities = [];

$availResult = $con->query("SELECT d.doctor_id, d.doctor_name, d.specialization, da.day_of_week, da.start_time, da.end_time
                           FROM doctor d
                           LEFT JOIN doctor_availability da ON d.doctor_id = da.doctor_id
                           ORDER BY d.doctor_name, da.day_of_week");

while ($row = $availResult->fetch_assoc()) {
    $doctor_id = $row['doctor_id'];
    if (!isset($doctor_list[$doctor_id])) {
        $doctor_list[$doctor_id] = [
            'name' => $row['doctor_name'],
            'specialization' => $row['specialization']
        ];
        $availabilities[$doctor_id] = [];
    }
    if ($row['day_of_week'] !== null) {
        $availabilities[$doctor_id][(int)$row['day_of_week']] = [
            'start' => $row['start_time'],
            'end'   => $row['end_time']
        ];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $doctor_id        = isset($_POST['doctor']) ? intval($_POST['doctor']) : 0;
    $appointment_date = isset($_POST['date']) ? $_POST['date'] : '';
    $appointment_time = isset($_POST['time_slot']) ? $_POST['time_slot'] . ":00" : '';
    $reason           = isset($_POST['reason']) ? trim($_POST['reason']) : '';

    if (empty($doctor_id) || empty($appointment_date) || empty($appointment_time) || empty($reason)) {
        $_SESSION['error'] = "Please fill out all fields.";
    
    } elseif ($appointment_date < date('Y-m-d')) {
         $_SESSION['error'] = "You cannot book an appointment on a past date.";
    
    } else {
        try {
            $checkStmt = $con->prepare("SELECT COUNT(*) FROM appointment 
                                        WHERE doctor_id = ? 
                                        AND appointment_date = ? 
                                        AND appointment_time = ? 
                                        AND status IN ('Pending', 'Scheduled')");
            $checkStmt->bind_param("iss", $doctor_id, $appointment_date, $appointment_time);
            $checkStmt->execute();
            $checkStmt->bind_result($conflictCount);
            $checkStmt->fetch();
            $checkStmt->close();

            if ($conflictCount > 0) {
                $_SESSION['error'] = "Sorry, that time slot was just booked. Please select another.";
            } else {
                $insertStmt = $con->prepare("INSERT INTO appointment (patient_id, doctor_id, appointment_date, appointment_time, reason, status) 
                                             VALUES (?, ?, ?, ?, ?, 'Pending')");
                $insertStmt->bind_param("iisss", $patient_id, $doctor_id, $appointment_date, $appointment_time, $reason);
                $insertStmt->execute();
                $insertStmt->close();
                $_SESSION['success'] = "Booking request sent! You will be notified upon confirmation.";
            }
        } catch (mysqli_sql_exception $e) {
            $_SESSION['error'] = "A database error occurred. Please try again.";
        }
    }
    header("Location: appointment.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment - Kalinga Medical Clinic</title>
    <link rel="stylesheet" href="appointment.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>

    <button class="menu-toggle" id="menuToggle" aria-label="Open menu">
        <i class="fas fa-bars"></i>
    </button>

    <div class="sidebar" id="sidebar">
        <img src="logoo.PNG" alt="Logo" class="sidebar-logo">
        <h3><?php echo htmlspecialchars($full_name); ?></h3>
        <nav class="menu">
            <a href="patient dashboard.php"><i class="fas fa-home"></i><span>Home</span></a>
            <a href="History.php"><i class="fas fa-history"></i><span>History</span></a>
            <a href="appointment.php" class="active"><i class="fas fa-calendar-alt"></i><span>Appointment</span></a>
            <a href="Account.php"><i class="fas fa-user-circle"></i><span>Account</span></a>
        </nav>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>

    <div class="main-container">
        <div class="overlay" id="overlay"></div>
        <main class="main-content">
            <header class="header">
                <div class="header-title">
                    <h2>Book an Appointment</h2>
                    <p>Follow the steps below to schedule your consultation.</p>
                </div>
            </header>
            
            <?php
            if (isset($_SESSION['success'])) {
                echo '<div class="message-banner success"><i class="fas fa-check-circle"></i> '.htmlspecialchars($_SESSION['success']).'</div>';
                unset($_SESSION['success']);
            }
            if (isset($_SESSION['error'])) {
                echo '<div class="message-banner error"><i class="fas fa-times-circle"></i> '.htmlspecialchars($_SESSION['error']).'</div>';
                unset($_SESSION['error']);
            }
            ?>

            <div class="booking-wrapper">
                <form id="appointmentForm" method="POST" action="appointment.php" class="appointment-form">
                    
                    <div class="form-step">
                        <div class="step-header">
                            <span class="step-number">1</span>
                            <h4>Select a Doctor</h4>
                        </div>
                        <div class="form-group">
                            <select id="doctor" name="doctor" required onchange="onDoctorSelect()">
                                <option value="" disabled selected>-- Choose a Doctor --</option>
                                <?php foreach($doctor_list as $id => $details): ?>
                                    <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($details['name']); ?> (<?php echo htmlspecialchars($details['specialization']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div id="schedule-section" class="form-step hidden">
                        <div class="step-header">
                            <span class="step-number">2</span>
                            <h4>Select Date & Time</h4>
                        </div>
                        <div id="doctor-schedule-display" class="doctor-schedule-display">
                            </div>
                        <div class="form-group">
                            <label for="date">Select an available date:</label>
                            <input type="date" id="date" name="date" required onchange="loadTimeSlots()" min="<?php echo date('Y-m-d'); ?>">
                            <small id="date-helper-text" class="helper-text error"></small>
                        </div>
                        <div class="form-group">
                            <label>Select an available time slot:</label>
                            <div id="time-slot-container" class="time-slot-container">
                                <p class="helper-text">Please select a date first.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div id="reason-section" class="form-step hidden">
                        <div class="step-header">
                             <span class="step-number">3</span>
                            <h4>Reason for Appointment</h4>
                        </div>
                        <div class="form-group">
                            <textarea id="reason" name="reason" rows="4" placeholder="Briefly describe your health concern..." required></textarea>
                        </div>
                    </div>

                    <button type="submit" id="submitBtn" class="submit-btn" disabled>Send Booking Request</button>
                </form>
            </div>
        </main>
    </div>

<script>
const doctorAvailabilities = <?php echo json_encode($availabilities); ?>;
const BOOKING_INTERVAL = 30; // In minutes

// Form sections
const doctorSelect = document.getElementById('doctor');
const scheduleSection = document.getElementById('schedule-section');
const reasonSection = document.getElementById('reason-section');
const dateInput = document.getElementById('date');
const timeSlotContainer = document.getElementById('time-slot-container');
const submitBtn = document.getElementById('submitBtn');
const dateHelperText = document.getElementById('date-helper-text');
const scheduleDisplay = document.getElementById('doctor-schedule-display');

function onDoctorSelect() {
    // Reset subsequent steps
    scheduleSection.classList.remove('hidden');
    reasonSection.classList.add('hidden');
    dateInput.value = '';
    timeSlotContainer.innerHTML = '<p class="helper-text">Please select a date.</p>';
    submitBtn.disabled = true;
    dateHelperText.textContent = '';
    
    // Display the selected doctor's schedule
    const doctorId = doctorSelect.value;
    const availability = doctorAvailabilities[doctorId];
    scheduleDisplay.innerHTML = ''; // Clear previous schedule

    if (!availability || Object.keys(availability).length === 0) {
        scheduleDisplay.innerHTML = '<p class="schedule-unavailable">This doctor has no schedule set.</p>';
        return;
    }
    
    const dayNames = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
    const dayMap = {1: "Mon", 2: "Tue", 3: "Wed", 4: "Thu", 5: "Fri", 6: "Sat", 7: "Sun"};

    let scheduleHTML = '<h5>Weekly Schedule:</h5><div class="schedule-grid">';
    dayNames.forEach((day, index) => {
        const dbDay = index + 1;
        const daySchedule = availability[dbDay];
        
        scheduleHTML += `<div class="schedule-day ${daySchedule ? 'available' : ''}">
            <div class="day-name">${day}</div>`;
        
        if (daySchedule) {
            const start = new Date(`1970-01-01T${daySchedule.start}`).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
            const end = new Date(`1970-01-01T${daySchedule.end}`).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
            scheduleHTML += `<div class="day-time">${start} - ${end}</div>`;
        } else {
            scheduleHTML += `<div class="day-time">Off</div>`;
        }
        scheduleHTML += `</div>`;
    });
    scheduleHTML += '</div>';
    scheduleDisplay.innerHTML = scheduleHTML;
}

async function loadTimeSlots() {
    const doctorId = doctorSelect.value;
    const selectedDate = dateInput.value;
    timeSlotContainer.innerHTML = '<p class="helper-text loading">Loading slots...</p>';
    reasonSection.classList.add('hidden');
    submitBtn.disabled = true;
    dateHelperText.textContent = '';

    if (!doctorId || !selectedDate) {
        timeSlotContainer.innerHTML = '<p class="helper-text">An error occurred. Please re-select the doctor and date.</p>';
        return;
    }

    const dateObj = new Date(selectedDate + 'T00:00:00');
    const jsDay = dateObj.getDay();
    const dbDay = (jsDay === 0) ? 7 : jsDay;
    const availability = doctorAvailabilities[doctorId]?.[dbDay];

    if (!availability) {
        timeSlotContainer.innerHTML = ''; // Clear loading text
        dateHelperText.textContent = 'Doctor is unavailable on this day. Please pick another date.';
        return;
    }

    const allSlots = generateTimeSlots(availability.start, availability.end);
    
    try {
        const response = await fetch(`get_booked_slots.php?doctor_id=${doctorId}&date=${selectedDate}`);
        if (!response.ok) throw new Error('Network error.');
        const bookedSlots = await response.json();
        renderTimeSlots(allSlots, bookedSlots);

    } catch (error) {
        console.error("Error fetching booked slots:", error);
        timeSlotContainer.innerHTML = '<p class="helper-text error">Could not load time slots. Please try again.</p>';
    }
}

function generateTimeSlots(start, end) {
    const slots = [];
    let currentTime = new Date(`1970-01-01T${start}`);
    const endTime = new Date(`1970-01-01T${end}`);
    while (currentTime < endTime) {
        slots.push(currentTime.toTimeString().substring(0, 5));
        currentTime.setMinutes(currentTime.getMinutes() + BOOKING_INTERVAL);
    }
    return slots;
}

function renderTimeSlots(allSlots, bookedSlots) {
    timeSlotContainer.innerHTML = '';
    let hasAvailableSlots = false;
    allSlots.forEach(slot => {
        const isBooked = bookedSlots.includes(slot);
        const slotEl = document.createElement('div');
        slotEl.className = 'time-slot';
        
        const input = document.createElement('input');
        input.type = 'radio';
        input.name = 'time_slot';
        input.id = `slot-${slot}`;
        input.value = slot;
        input.disabled = isBooked;
        input.onchange = () => {
            reasonSection.classList.remove('hidden');
            submitBtn.disabled = false;
        };
        
        const label = document.createElement('label');
        label.htmlFor = `slot-${slot}`;
        label.textContent = new Date(`1970-01-01T${slot}`).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

        if (isBooked) {
            label.title = 'This slot is already booked or pending confirmation.';
            slotEl.classList.add('disabled');
        } else {
            hasAvailableSlots = true;
        }
        slotEl.appendChild(input);
        slotEl.appendChild(label);
        timeSlotContainer.appendChild(slotEl);
    });

    if (!hasAvailableSlots) {
        timeSlotContainer.innerHTML = '<p class="helper-text">No available time slots for this doctor on this day. Please try another date.</p>';
    }
}

document.addEventListener("DOMContentLoaded", function() {
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    function toggleMenu() {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
    }
    if (menuToggle) menuToggle.addEventListener('click', toggleMenu);
    if (overlay) overlay.addEventListener('click', toggleMenu);
});
</script>
</body>
</html>