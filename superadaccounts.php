<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

if (!$admin || $admin['role'] !== 'super_admin') {
    session_destroy();
    header("Location: loginadmin.php");
    exit();
}

$full_name = $admin['full_name'];
$role      = $admin['role'];

$adminAccounts = $con->query("SELECT admin_id AS id, username, email, full_name, role FROM admin")->fetch_all(MYSQLI_ASSOC);

$userAccounts = $con->query("SELECT id, username, email, phone, full_name, role FROM users WHERE role='user'")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Superadmin - User Management | Kalinga Medical Clinic</title>
  <link rel="stylesheet" href="superadaccounts.css">
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
      <a href="superadmindashboard.php" class="nav-item"><i class="fas fa-home"></i><span>Dashboard</span></a>
      <a href="superadweeklyreports.php" class="nav-item"><i class="fas fa-clipboard-list"></i><span>Reports</span></a>
      <a href="aichatlogs.php" class="nav-item"><i class="fas fa-robot"></i><span>AI Chat Records</span></a>
      <a href="superadinventory.php" class="nav-item"><i class="fas fa-pills"></i><span>Medicine Inventory</span></a>
      <a href="superadappointment.php" class="nav-item"><i class="fas fa-calendar-check"></i><span>Appointments</span></a>
      <a href="superadaccounts.php" class="nav-item active"><i class="fas fa-users"></i><span>Accounts</span></a>
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
            <h1 class="page-title"><i class="fas fa-users-cog"></i> Account Management</h1>
            <p class="page-subtitle">Add, edit, or remove user and admin accounts</p>
          </div>
        </div>
        <div class="header-actions">
          <button class="btn-primary" onclick="openAddModal()"><i class="fas fa-plus"></i><span> Add Account</span></button>
        </div>
      </div>
    </header>

    <div class="content-section">
        <div class="search-section">
            <div class="search-input-group">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search by name, username, or email..." onkeyup="searchUsers()">
            </div>
        </div>

      <div class="table-container">
        <h3 class="table-title">Admin Accounts</h3>
        <div class="table-wrapper">
          <table id="adminTable">
            <thead><tr><th>Username</th><th>Email</th><th>Full Name</th><th>Role</th><th>Actions</th></tr></thead>
            <tbody>
              <?php foreach ($adminAccounts as $account): ?>
              <tr data-id="<?php echo $account['id']; ?>">
                <td data-label="Username"><?php echo htmlspecialchars($account['username']); ?></td>
                <td data-label="Email"><?php echo htmlspecialchars($account['email']); ?></td>
                <td data-label="Full Name"><?php echo htmlspecialchars($account['full_name']); ?></td>
                <td data-label="Role"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $account['role']))); ?></td>
                <td data-label="Actions">
                  <button class="action-btn edit-btn" onclick="editUser(this, 'admin')"><i class="fas fa-edit"></i> Edit</button>
                  <button class="action-btn delete-btn" onclick="deleteUser(this, 'admin')"><i class="fas fa-trash"></i> Delete</button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="table-container">
        <h3 class="table-title">User Accounts</h3>
        <div class="table-wrapper">
          <table id="userTable">
            <thead><tr><th>Username</th><th>Email</th><th>Full Name</th><th>Phone</th><th>Actions</th></tr></thead>
            <tbody>
              <?php foreach ($userAccounts as $account): ?>
              <tr data-id="<?php echo $account['id']; ?>">
                <td data-label="Username"><?php echo htmlspecialchars($account['username']); ?></td>
                <td data-label="Email"><?php echo htmlspecialchars($account['email']); ?></td>
                <td data-label="Full Name"><?php echo htmlspecialchars($account['full_name']); ?></td>
                <td data-label="Phone"><?php echo htmlspecialchars($account['phone']); ?></td>
                <td data-label="Actions">
                  <button class="action-btn edit-btn" onclick="editUser(this, 'user')"><i class="fas fa-edit"></i> Edit</button>
                  <button class="action-btn delete-btn" onclick="deleteUser(this, 'user')"><i class="fas fa-trash"></i> Delete</button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div id="userModal" class="modal" style="display:none;">
    <div class="modal-overlay" onclick="closeUserModal()"></div>
    <div class="modal-content hospital-modal">
      <div class="modal-header">
        <div class="modal-icon"><i class="fas fa-user-plus"></i></div>
        <h2 class="modal-title" id="modalTitle">Add Account</h2>
        <button class="close-btn" onclick="closeUserModal()"><i class="fas fa-times"></i></button>
      </div>
       <form id="userForm" class="hospital-form">
        <div class="modal-body">
          <input type="hidden" id="userId" name="id">
          <input type="hidden" id="accountType" name="accountType">

          <div class="form-group">
            <label class="form-label" for="role">Account Type</label>
            <select id="role" name="role" class="form-select" required>
              {/* Options populated by JS */}
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="username">Username</label>
            <input type="text" id="username" name="username" class="form-input" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" class="form-input" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="email">Email</label>
            <input type="email" id="email" name="email" class="form-input" required>
          </div>
          <div class="form-group" id="phone-group">
            <label class="form-label" for="phone">Phone Number</label>
            <input type="tel" id="phone" name="phone" class="form-input">
          </div>
          <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <input type="password" id="password" name="password" class="form-input" placeholder="Leave blank to keep unchanged">
          </div>
        </div>
        <div class="form-actions">
            <button type="button" class="btn-secondary" onclick="closeUserModal()">Cancel</button>
            <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save Account</button>
        </div>
      </form>
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

    document.getElementById("userForm").addEventListener("submit", handleFormSubmit);
    document.getElementById("role").addEventListener("change", togglePhoneField);
});

function searchUsers() {
    const input = document.getElementById("searchInput").value.toLowerCase();
    document.querySelectorAll("#adminTable tbody tr, #userTable tbody tr").forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(input) ? "" : "none";
    });
}

function openAddModal() {
    document.getElementById("modalTitle").innerText = "Add New Account";
    const form = document.getElementById("userForm");
    form.reset();
    document.getElementById("userId").value = "";
    document.getElementById("password").placeholder = "Password (required)";
    document.getElementById("password").required = true;
    
    const roleSelect = document.getElementById("role");
    roleSelect.innerHTML = `
        <option value="user" selected>Patient</option>
        <option value="admin">Admin</option>
        <option value="super_admin">Super Admin</option>
    `;
    
    togglePhoneField(); 
    document.getElementById("userModal").style.display = "flex";
}

function closeUserModal() {
    document.getElementById("userModal").style.display = "none";
}

function togglePhoneField() {
    const role = document.getElementById("role").value;
    const phoneGroup = document.getElementById("phone-group");
    phoneGroup.style.display = (role === 'user') ? 'flex' : 'none';
}

function editUser(button, type) {
    const row = button.closest("tr");
    document.getElementById("modalTitle").innerText = "Edit Account";
    document.getElementById("userId").value = row.dataset.id;
    document.getElementById("accountType").value = type;
    document.getElementById("password").placeholder = "Leave blank to keep unchanged";
    document.getElementById("password").required = false;

    const roleSelect = document.getElementById("role");
    
    if (type === 'admin') {
        roleSelect.innerHTML = `<option value="admin">Admin</option><option value="super_admin">Super Admin</option>`;
        document.getElementById("username").value = row.cells[0].textContent;
        document.getElementById("email").value = row.cells[1].textContent;
        document.getElementById("full_name").value = row.cells[2].textContent;
        roleSelect.value = row.cells[3].textContent.toLowerCase().replace(' ', '_');
    } else { 
        roleSelect.innerHTML = `<option value="user">Patient</option>`;
        document.getElementById("username").value = row.cells[0].textContent;
        document.getElementById("email").value = row.cells[1].textContent;
        document.getElementById("full_name").value = row.cells[2].textContent;
        document.getElementById("phone").value = row.cells[3].textContent;
        roleSelect.value = 'user';
    }
    
    togglePhoneField();
    document.getElementById("userModal").style.display = "flex";
}

async function deleteUser(button, type) {
    const row = button.closest("tr");
    const id = row.dataset.id;
    if (!confirm(`Are you sure you want to delete this ${type}? This action cannot be undone.`)) return;

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);
    formData.append('accountType', type);
    
    try {
        const response = await fetch('user_actions.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (result.success) {
            alert(result.message);
            row.remove();
        } else {
            alert("Error: " + result.message);
        }
    } catch (error) {
        alert("An error occurred. Please check the console.");
    }
}

async function handleFormSubmit(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const id = formData.get("id");
    const action = id ? 'edit' : 'add';
    formData.append('action', action);
    
    
    if (action === 'add') {
        const role = formData.get('role');
        const accountType = (role === 'admin' || role === 'super_admin') ? 'admin' : 'user';
        formData.set('accountType', accountType);
    }
    
    try {
        const response = await fetch('user_actions.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (result.success) {
            alert(result.message);
            location.reload();
        } else {
            alert("Error: " + result.message);
        }
    } catch (error) {
        alert("An error occurred. Please check the console.");
    }
}
</script>
</body>
</html>