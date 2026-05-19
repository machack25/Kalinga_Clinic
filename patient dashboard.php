<?php
session_start();
include("db.php");
date_default_timezone_set('Asia/Manila');

// --- NEW: Get current time in Manila for accurate comparisons ---
$current_time_manila = date('Y-m-d H:i:s');

if (!isset($_SESSION['user_id'])) {
    header("Location: loginpage.php");
    exit();
}
if (!isset($_SESSION['session_start_time'])) {
    $_SESSION['session_start_time'] = $current_time_manila;
}

$patient_id = $_SESSION['user_id'];

$stmt = $con->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) {
    session_destroy();
    header("Location: loginpage.php");
    exit();
}
$full_name = $user['full_name'];

// --- UPDATED: AUTO-CANCEL OVERDUE APPOINTMENTS ---
// This logic now uses the PHP 'Asia/Manila' time, which is more reliable.
try {
    $auto_cancel_stmt = $con->prepare("
        UPDATE appointment
        SET status = 'Cancelled'
        WHERE patient_id = ?
          AND status = 'Scheduled'
          AND CONCAT(appointment_date, ' ', appointment_time) < DATE_SUB(?, INTERVAL 25 MINUTE)
    ");
    $auto_cancel_stmt->bind_param("is", $patient_id, $current_time_manila);
    $auto_cancel_stmt->execute();
    $auto_cancel_stmt->close();
} catch (Exception $e) {
    // Log error or handle it gracefully
}
// --- END AUTO-CANCEL ---


// --- NOTIFICATION LOGIC ---
$notifications = [];
// Use the $current_time_manila variable defined at the top
$last_check_time = isset($_SESSION['last_notification_check']) ? $_SESSION['last_notification_check'] : $_SESSION['session_start_time'];

$today_date = date('Y-m-d');
$tomorrow_date = date('Y-m-d', strtotime('tomorrow'));
$appointment_stmt = $con->prepare("
    SELECT appointment_date, appointment_time FROM appointment
    WHERE patient_id = ? AND status = 'Scheduled' AND appointment_date IN (?, ?)
    ORDER BY appointment_date ASC, appointment_time ASC
");
$appointment_stmt->bind_param("iss", $patient_id, $today_date, $tomorrow_date);
$appointment_stmt->execute();
$appointments_result = $appointment_stmt->get_result();
while ($appointment = $appointments_result->fetch_assoc()) {
    $time_str = date("h:i A", strtotime($appointment['appointment_time']));
    $message = ($appointment['appointment_date'] == $today_date)
        ? "You have an appointment today at " . $time_str . "."
        : "You have an appointment tomorrow at " . $time_str . ".";
    $notifications[] = ['type' => 'appointment', 'message' => $message];
}

$cancelled_stmt = $con->prepare("
    SELECT appointment_date, appointment_time
    FROM appointment
    WHERE patient_id = ?
    AND status = 'Cancelled'
    AND CONVERT_TZ(updated_at, '+00:00', '+08:00') > ?
");
$cancelled_stmt->bind_param("is", $patient_id, $last_check_time);
$cancelled_stmt->execute();
$cancelled_result = $cancelled_stmt->get_result();
while ($cancelled_appt = $cancelled_result->fetch_assoc()) {
    $date_str = date("F j, Y", strtotime($cancelled_appt['appointment_date']));
    $time_str = date("h:i A", strtotime($cancelled_appt['appointment_time']));
    $notifications[] = [
        'type' => 'cancellation',
        'message' => "Your appointment for " . $date_str . " at " . $time_str . " has been cancelled." // Simplified message
    ];
}

$confirmed_stmt = $con->prepare("
    SELECT appointment_date, appointment_time, queue_number
    FROM appointment
    WHERE patient_id = ?
    AND status = 'Scheduled'
    AND CONVERT_TZ(updated_at, '+00:00', '+08:00') > ?
");
$confirmed_stmt->bind_param("is", $patient_id, $last_check_time);
$confirmed_stmt->execute();
$confirmed_result = $confirmed_stmt->get_result();
while ($confirmed_appt = $confirmed_result->fetch_assoc()) {
    $date_str = date("F j, Y", strtotime($confirmed_appt['appointment_date']));
    $time_str = date("h:i A", strtotime($confirmed_appt['appointment_time']));
    $queue = $confirmed_appt['queue_number'];
    $notifications[] = [
        'type' => 'confirmation',
        'message' => "Your booking for $date_str at $time_str is confirmed! Your queue number is #$queue."
    ];
}

$sql = "SELECT COUNT(*) as update_count FROM inventory WHERE CONVERT_TZ(last_updated, '+00:00', '+08:00') > ?";
$inventory_stmt = $con->prepare($sql);
$inventory_stmt->bind_param("s", $last_check_time);
$inventory_stmt->execute();
$inventory_updates = $inventory_stmt->get_result()->fetch_assoc()['update_count'];
if ($inventory_updates > 0) {
    $notifications[] = [
        'type' => 'inventory',
        'message' => "The medicine inventory has been updated."
    ];
}

$appointment_reminder_message = null; 
foreach ($notifications as $notification) {
    if ($notification['type'] === 'appointment') {
        $appointment_reminder_message = $notification['message'];
        break; 
    }
}

$_SESSION['last_notification_check'] = $current_time_manila; // Use the variable
$notification_count = count($notifications);
// --- END NOTIFICATION LOGIC ---

// Get Available Doctors
$dayOfWeekNumeric = date('N');
$now = date('H:i:s');
$availableDoctors = [];
$doctorQuery = $con->prepare("
    SELECT d.doctor_name AS full_name, d.specialization FROM doctor d
    JOIN doctor_availability da ON d.doctor_id = da.doctor_id
    WHERE da.day_of_week = ? AND ? BETWEEN da.start_time AND da.end_time
");
$doctorQuery->bind_param("is", $dayOfWeekNumeric, $now);
$doctorQuery->execute();
$doctorResult = $doctorQuery->get_result();
while ($row = $doctorResult->fetch_assoc()) {
    $availableDoctors[] = ['name' => $row['full_name'], 'specialization' => $row['specialization']];
}
$availableDoctorCount = count($availableDoctors);

// --- Fetch Upcoming Appointments for new card/modal ---
$appointments_sql = $con->prepare("
    SELECT a.appointment_id, a.appointment_date, a.appointment_time, d.doctor_name, a.reason
    FROM appointment a
    JOIN doctor d ON a.doctor_id = d.doctor_id
    WHERE a.patient_id = ? AND a.appointment_date >= CURDATE() AND a.status = 'Scheduled'
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
");
$appointments_sql->bind_param("i", $patient_id);
$appointments_sql->execute();
$result = $appointments_sql->get_result();
$upcoming_appointments = $result->fetch_all(MYSQLI_ASSOC);
$appointments_sql->close();
$upcomingAppointmentCount = count($upcoming_appointments);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Patient's Interface</title>
  <link rel="stylesheet" href="patient dashboard.css">
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
      <a href="patient dashboard.php" class="active"><i class="fas fa-home"></i><span>Home</span></a>
      <a href="History.php"><i class="fas fa-history"></i><span>History</span></a>
      <a href="appointment.php"><i class="fas fa-calendar-alt"></i><span>Appointment</span></a>
      <a href="Account.php"><i class="fas fa-user-circle"></i><span>Account</span></a>
    </nav>
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
  </div>

  <div class="main-container">
    <div class="overlay" id="overlay"></div>
    <main class="main-content">
      <header class="header">
        <div class="header-title">
          <h2>Welcome back, <?php echo htmlspecialchars($full_name); ?>.</h2>
          <p>Here's your health summary for today.</p>
        </div>

        <div class="notification-bell" id="notificationBell">
          <i class="fas fa-bell"></i>
          <?php if ($notification_count > 0): ?>
            <span class="badge"><?php echo $notification_count; ?></span>
          <?php endif; ?>
          <div class="notification-panel" id="notificationPanel">
            <div class="panel-header">Notifications</div>
            <div class="panel-body">
              <?php if ($notification_count > 0): ?>
                <ul>
                  <?php foreach ($notifications as $notification): ?>
                    <?php
                      $iconClass = 'fas fa-info-circle'; $title = 'General Update';
                      if ($notification['type'] === 'appointment') { $iconClass = 'fas fa-calendar-check'; $title = 'Appointment Reminder'; }
                      elseif ($notification['type'] === 'inventory') { $iconClass = 'fas fa-pills'; $title = 'Inventory Update'; }
                      elseif ($notification['type'] === 'cancellation') { $iconClass = 'fas fa-calendar-times'; $title = 'Appointment Cancellation'; }
                      elseif ($notification['type'] === 'confirmation') { $iconClass = 'fas fa-check-circle'; $title = 'Booking Confirmed!'; }
                    ?>
                    <li>
                      <div class="notification-icon"><i class="<?php echo $iconClass; ?>"></i></div>
                      <div class="notification-content">
                          <span class="notification-title"><?php echo $title; ?></span>
                          <p><?php echo htmlspecialchars($notification['message']); ?></p>
                      </div>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?> <p>No new notifications.</p> <?php endif; ?>
            </div>
          </div>
        </div>
      </header>
      
      <?php
      if (isset($_SESSION['status_message'])) {
          $type = $_SESSION['status_type'] ?? 'info';
          // Using the same class names as History.php
          echo '<div class="message-banner ' . htmlspecialchars($type) . '"><i class="fas ' . ($type === 'success' ? 'fa-check-circle' : 'fa-times-circle') . '"></i> '.htmlspecialchars($_SESSION['status_message']).'</div>';
          unset($_SESSION['status_message']);
          unset($_SESSION['status_type']);
      }
      ?>
      <section class="dashboard">
        <div class="card" id="staffCard">
          <h3>Available Doctors</h3>
          <div class="icon"><i class="fas fa-user-md"></i></div>
          <h1><?php echo $availableDoctorCount; ?></h1>
        </div>

        <div class="card" id="appointmentsCard">
          <h3>Upcoming Appointments</h3>
          <div class="icon"><i class="fas fa-calendar-check"></i></div>
          <h1><?php echo $upcomingAppointmentCount; ?></h1>
        </div>

        <a href="inventory.php" class="card">
          <h3>Medicine Inventory</h3>
          <div class="icon"><i class="fas fa-pills"></i></div>
        </a>
      </section>
    </main>
  </div>
  
  <div id="staffModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeModal('staff')">&times;</span>
      <h2>Available Doctors</h2>
      <ul>
        <?php if (!empty($availableDoctors)): ?>
            <?php foreach($availableDoctors as $doctor): ?>
              <li><?php echo htmlspecialchars($doctor['name']); ?> (<?php echo htmlspecialchars($doctor['specialization']); ?>)</li>
            <?php endforeach; ?>
        <?php else: ?> <li>No doctors are currently on duty.</li> <?php endif; ?>
      </ul>
    </div>
  </div>

  <div id="appointmentsModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeModal('appointments')">&times;</span>
      <h2>Upcoming Appointments</h2>
      <div class="card-list" id="upcomingAppointmentsList">
          </div>
    </div>
  </div>

  <div class="floating-chat-icon" onclick="toggleChat()"><i class="fas fa-comments"></i></div>
  <div class="floating-chat-widget" id="chatWidget">
    <div class="chat-header">
        <div class="chat-header-info">
            <div class="chat-avatar"><i class="fas fa-robot"></i></div>
            <div classs="chat-info">
                <h4>Kalinga Chat Bot</h4>
                <span class="chat-status">Online</span>
            </div>
        </div>
        <button class="chat-close" onclick="toggleChat()"><i class="fas fa-times"></i></button>
    </div>
    <div class="chat-messages" id="chatMessages">
        <div class="message bot-message">
            <div class="message-avatar"><i class="fas fa-robot"></i></div>
            <div class="message-content">
                <p>Hello! I'm your Chat assistant. How can I help you today? You can ask about appointments, history, or available doctors.</p>
                <span class="message-time">Just now</span>
            </div>
        </div>
    </div>
    <div class="chat-input-container">
        <div class="chat-input-wrapper">
            <input type="text" id="chatInput" placeholder="Type your message..." onkeypress="handleChatKeyPress(event)">
            <button class="chat-send-btn" onclick="sendMessage()"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
  </div>

  <?php if ($appointment_reminder_message): ?>
  <div id="reminderModal" class="modal">
     <div class="modal-content reminder-content">
      <span class="close" id="closeReminderModal">&times;</span>
      <div class="modal-icon"><i class="fas fa-calendar-check"></i></div>
      <h2 class="modal-title">Appointment Reminder</h2>
      <p class="modal-text"><?php echo htmlspecialchars($appointment_reminder_message); ?></p>
      <button class="modal-button" id="reminderOkButton">Okay, I understand</button>
    </div>
  </div>
  <?php endif; ?>

<script>
    const upcomingAppointmentsData = <?php echo json_encode($upcoming_appointments); ?>;

    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');

        function toggleMenu() {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        }
        if (menuToggle) menuToggle.addEventListener('click', toggleMenu);
        if (overlay) overlay.addEventListener('click', toggleMenu);

        const notificationBell = document.getElementById('notificationBell');
        const notificationPanel = document.getElementById('notificationPanel');
        if (notificationBell) {
            notificationBell.addEventListener('click', (e) => {
                e.stopPropagation();
                notificationPanel.classList.toggle('show');
            });
        }

        document.getElementById("staffCard").onclick = () => openModal('staff');
        document.getElementById("appointmentsCard").onclick = () => openModal('appointments');

        const reminderModal = document.getElementById('reminderModal');
        if (reminderModal) {
            window.addEventListener('load', () => setTimeout(() => { reminderModal.style.display = 'flex'; }, 500));
            document.getElementById('closeReminderModal').onclick = () => reminderModal.style.display = "none";
            document.getElementById('reminderOkButton').onclick = () => reminderModal.style.display = "none";
        }

        window.addEventListener('click', function(event) {
            if (notificationPanel && !notificationBell.contains(event.target)) notificationPanel.classList.remove('show');
            if (event.target.classList.contains('modal')) event.target.style.display = "none";
            const chatWidget = document.getElementById('chatWidget');
            const chatIcon = document.querySelector('.floating-chat-icon');
            if (chatWidget.classList.contains('chat-open') && !chatWidget.contains(event.target) && !chatIcon.contains(event.target)) {
               toggleChat();
            }
        });

        document.getElementById('chatMessages').scrollTop = document.getElementById('chatMessages').scrollHeight;
    });

    function openModal(type) {
        document.getElementById(`${type}Modal`).style.display = "flex";
        if (type === 'appointments') {
            renderUpcomingAppointments();
        }
    }

    function closeModal(type) {
        document.getElementById(`${type}Modal`).style.display = "none";
    }

    function renderUpcomingAppointments() {
        const container = document.getElementById('upcomingAppointmentsList');
        if (upcomingAppointmentsData.length === 0) {
            container.innerHTML = `<div class="empty-state"><i class="fas fa-calendar-times"></i><p>No upcoming appointments.</p></div>`;
            return;
        }

        container.innerHTML = upcomingAppointmentsData.map(a => `
            <div class="card-item">
                <h4>${a.reason}</h4>
                <p><i class="fas fa-calendar-day"></i> <strong>Date:</strong> ${new Date(a.appointment_date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })}</p>
                <p><i class="fas fa-clock"></i> <strong>Time:</strong> ${new Date(`1970-01-01T${a.appointment_time}`).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}</p>
                <p><i class="fas fa-user-md"></i> <strong>Doctor:</strong> ${a.doctor_name}</p>
                <button class="cancel-btn" onclick="cancelAppointment(${a.appointment_id})">Cancel Appointment</button>
            </div>
        `).join('');
    }

    function cancelAppointment(appointmentId) {
        if (confirm("Are you sure you want to cancel this appointment?")) {
            window.location.href = "cancel_appointment.php?id=" + appointmentId;
        }
    }

    function toggleChat() {
      const chatWidget = document.getElementById('chatWidget');
      chatWidget.classList.toggle('chat-open');
      if (chatWidget.classList.contains('chat-open')) {
          document.getElementById('chatInput').focus();
          document.getElementById('chatMessages').scrollTop = document.getElementById('chatMessages').scrollHeight;
      }
    }

    function handleChatKeyPress(event) {
        if (event.key === 'Enter') sendMessage();
    }

    async function sendMessage() {
        const input = document.getElementById('chatInput');
        const message = input.value.trim();
        if (message === '') return;
        addMessage(message, 'user');
        input.value = '';
        addMessage("...", 'bot', true);
        try {
            const formData = new FormData();
            formData.append('message', message);
            const response = await fetch('chat_service.php', { method: 'POST', body: formData });
            const result = await response.json();
            updateBotMessage(result.reply);
        } catch (error) {
            console.error("Chat Error:", error);
            updateBotMessage("Sorry, I'm having trouble connecting right now.");
        }
    }

    function addMessage(text, sender, isTyping = false) {
        const messagesContainer = document.getElementById('chatMessages');
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${sender}-message`;
        const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute:'2-digit' });
        let contentHTML;
        if (sender === 'user') {
            contentHTML = `<div class="message-content"><p>${text}</p><span class="message-time">${time}</span></div><div class="message-avatar"><i class="fas fa-user"></i></div>`;
        } else {
            const typingClass = isTyping ? 'typing' : '';
            contentHTML = `<div class="message-avatar"><i class="fas fa-robot"></i></div><div class="message-content ${typingClass}"><p>${text}</p><span class="message-time">${time}</span></div>`;
        }
        messageDiv.innerHTML = contentHTML;
        messagesContainer.appendChild(messageDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    function updateBotMessage(newText) {
        const typingMessage = document.querySelector('.bot-message .message-content.typing');
        if (typingMessage) {
            typingMessage.querySelector('p').innerHTML = newText;
            typingMessage.classList.remove('typing');
        } else {
             addMessage(newText, 'bot');
        }
        const messagesContainer = document.getElementById('chatMessages');
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

</script>

</body>
</html>