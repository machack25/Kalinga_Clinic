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


$delete_query = $con->prepare("DELETE FROM chat_logs WHERE created_at < NOW() - INTERVAL 3 DAY");
$delete_query->execute();
$delete_query->close();



$logs_query = $con->prepare("
    SELECT
        cl.log_id,
        cl.patient_id,
        u.full_name AS patient_name,
        cl.user_message,
        cl.bot_response,
        cl.created_at
    FROM
        chat_logs AS cl
    JOIN
        users AS u ON cl.patient_id = u.id
    ORDER BY
        u.full_name ASC, cl.created_at DESC
");
$logs_query->execute();
$all_logs = $logs_query->get_result()->fetch_all(MYSQLI_ASSOC);
$logs_query->close();

$grouped_logs = [];
foreach ($all_logs as $log) {
    $patient_id = $log['patient_id'];
    if (!isset($grouped_logs[$patient_id])) {
        $grouped_logs[$patient_id] = [
            'patient_name' => $log['patient_name'],
            'logs' => []
        ];
    }
    $grouped_logs[$patient_id]['logs'][] = $log;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Superadmin - AI Chat Logs | Kalinga Medical Clinic</title>
  <link rel="stylesheet" href="aichatlogs.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
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
      <a href="superadmindashboard.php" class="nav-item"><i class="fas fa-home"></i><span>Dashboard</span></a>
      <a href="superadweeklyreports.php" class="nav-item"><i class="fas fa-clipboard-list"></i><span>Reports</span></a>
      <a href="aichatlogs.php" class="nav-item active"><i class="fas fa-robot"></i><span>AI Chat Records</span></a>
      <a href="superadinventory.php" class="nav-item"><i class="fas fa-pills"></i><span>Medicine Inventory</span></a>
      <a href="superadappointment.php" class="nav-item"><i class="fas fa-calendar-check"></i><span>Appointments</span></a>
      <a href="superadaccounts.php" class="nav-item"><i class="fas fa-users"></i><span>Accounts</span></a>
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
          <button id="menu-toggle" class="menu-toggle"><i class="fas fa-bars"></i></button>
          <div class="title-group">
            <h1 class="page-title">
              <i class="fas fa-robot"></i>
              AI Chat Logs
            </h1>
            <p class="page-subtitle">Review conversations between patients and the AI assistant</p>
          </div>
        </div>
      </div>
    </header>

    <div class="content-section">
      <div class="search-section">
        <div class="search-input-group">
          <i class="fas fa-search"></i>
          <input type="text" id="searchChatInput" placeholder="Search by patient name..." onkeyup="searchChatLogs()">
        </div>
      </div>

      <div class="chat-logs-container">
        <div class="chat-logs-header">
          <h3><i class="fas fa-history"></i> Conversation History</h3>
        </div>
        <div class="chat-logs-grid" id="chatLogsList">
          <?php if (empty($grouped_logs)): ?>
            <div class="empty-state"><p>No chat logs found.</p></div>
          <?php else: ?>
            <?php foreach ($grouped_logs as $patient_id => $group): ?>
              <div class="chat-log-card" 
                   data-patient-name="<?php echo htmlspecialchars($group['patient_name']); ?>" 
                   data-logs='<?php echo htmlspecialchars(json_encode($group['logs'])); ?>'>
                <div class="chat-log-header">
                  <h4><i class="fas fa-user"></i> <?php echo htmlspecialchars($group['patient_name']); ?></h4>
                  <span><?php echo count($group['logs']); ?> entries</span>
                </div>
                <div class="chat-log-content">
                  <p><strong>Last Message:</strong> "<?php echo htmlspecialchars($group['logs'][0]['user_message']); ?>"</p>
                </div>
                <div class="chat-log-actions">
                  <button class="btn-secondary view-btn" onclick="viewChat(this)">View Conversation</button>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div id="chatModal" class="modal">
    <div class="modal-overlay" onclick="closeChatModal()"></div>
    <div class="modal-content hospital-modal">
      <div class="modal-header">
        <div class="modal-icon"><i class="fas fa-comments"></i></div>
        <h2 id="modalTitle" class="modal-title">Chat Conversation</h2>
        <button class="close-btn" onclick="closeChatModal()"><i class="fas fa-times"></i></button>
      </div>
      <div class="modal-body">
        <div class="chat-conversation-container" id="chatConversation">
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
    });
  
    function searchChatLogs() {
      let input = document.getElementById("searchChatInput").value.toLowerCase();
      let cards = document.querySelectorAll(".chat-log-card");
      cards.forEach(card => {
        let name = card.dataset.patientName.toLowerCase();
        card.style.display = name.includes(input) ? "block" : "none";
      });
    }

    function viewChat(button) {
      const card = button.closest('.chat-log-card');
      const patientName = card.dataset.patientName;
      const logs = JSON.parse(card.dataset.logs);

      const modal = document.getElementById("chatModal");
      const conversationContainer = document.getElementById("chatConversation");
      const modalTitle = document.getElementById("modalTitle");

      modalTitle.innerText = `Chat with ${patientName}`;
      conversationContainer.innerHTML = ''; 

      logs.slice().reverse().forEach(log => {
        const userMessageDiv = document.createElement('div');
        userMessageDiv.className = 'chat-message patient';
        userMessageDiv.innerHTML = `
            <div class="message-bubble"><strong>You:</strong> ${escapeHTML(log.user_message)}</div>
            <div class="message-time">${new Date(log.created_at).toLocaleString()}</div>
        `;
        
        const botMessageDiv = document.createElement('div');
        botMessageDiv.className = 'chat-message ai';
        botMessageDiv.innerHTML = `
            <div class="message-bubble"><strong>AI:</strong> ${escapeHTML(log.bot_response)}</div>
            <div class="message-time">${new Date(log.created_at).toLocaleString()}</div>
        `;
        
        conversationContainer.appendChild(userMessageDiv);
        conversationContainer.appendChild(botMessageDiv);
      });

      modal.style.display = "flex";
    }

    function closeChatModal() {
      document.getElementById("chatModal").style.display = "none";
    }

    function escapeHTML(str) {
        const p = document.createElement("p");
        p.appendChild(document.createTextNode(str));
        return p.innerHTML;
    }
  </script>
</body>
</html>