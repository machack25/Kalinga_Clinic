<?php 
session_start();
include("db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: loginpage.php");
    exit();
}

$patient_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'update_profile') {
        // Get all new fields from the form (REMOVED: $details)
        $fullname = $_POST['fullname'];
        $username = $_POST['username'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $birthdate = !empty($_POST['birthdate']) ? $_POST['birthdate'] : null;
        $age = !empty($_POST['age']) ? intval($_POST['age']) : null;
        // REMOVED: $details = $_POST['details'];

        // Updated query to match all fields (REMOVED: details=?)
        $stmt = $con->prepare("UPDATE users SET full_name=?, username=?, email=?, phone=?, birthdate=?, age=? WHERE id=?");
        $stmt->bind_param("sssssii", $fullname, $username, $email, $phone, $birthdate, $age, $patient_id); // REMOVED: 's' for details

        if ($stmt->execute()) {
            echo json_encode(['status'=>'success', 'message' => 'Profile updated successfully!']);
        } else {
            // Check for duplicate email or username
            if ($con->errno == 1062) {
                if (strpos($con->error, 'email') !== false) {
                    echo json_encode(['status'=>'error', 'message' => 'This email is already in use.']);
                } else if (strpos($con->error, 'username') !== false) {
                    echo json_encode(['status'=>'error', 'message' => 'This username is already taken.']);
                } else {
                    echo json_encode(['status'=>'error', 'message' => 'Failed to update profile. (Duplicate entry)']);
                }
            } else {
                echo json_encode(['status'=>'error', 'message' => 'Failed to update profile.']);
            }
        }
        exit();
    }

    if ($_POST['action'] === 'change_password') {
        // (This logic is unchanged)
        $current = $_POST['currentPassword'];
        $new = $_POST['newPassword'];

        $stmt = $con->prepare("SELECT password FROM users WHERE id=?");
        $stmt->bind_param("i", $patient_id);
        $stmt->execute();
        $stmt->bind_result($hashedPassword);
        $stmt->fetch();
        $stmt->close();

        if (password_verify($current, $hashedPassword)) {
            $newHashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $con->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param("si", $newHashed, $patient_id);
            if($stmt->execute()){
                echo json_encode(['status'=>'success', 'message' => 'Password changed successfully!']);
            } else {
                echo json_encode(['status'=>'error','message'=>'Failed to update password.']);
            }
        } else {
            echo json_encode(['status'=>'error','message'=>'Current password incorrect.']);
        }
        exit();
    }
}

// Updated query to fetch all new fields (REMOVED: details)
$stmt = $con->prepare("SELECT full_name, username, email, phone, birthdate, age FROM users WHERE id=?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$stmt->bind_result($full_name, $username, $email, $phone, $birthdate, $age); // REMOVED: $details
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account - Kalinga Medical Clinic</title>
    <link rel="stylesheet" href="account.css">
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
            <a href="appointment.php"><i class="fas fa-calendar-alt"></i><span>Appointment</span></a>
            <a href="Account.php" class="active"><i class="fas fa-user-circle"></i><span>Account</span></a>
        </nav>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>

    <div class="main-container">
        <div class="overlay" id="overlay"></div>
        <main class="main-content">
            <header class="header">
                <div class="header-title">
                    <h2>Account Settings</h2>
                    <p>Manage your profile and password.</p>
                </div>
            </header>

            <div class="account-grid">
                <section class="profile-section">
                    <h3><i class="fas fa-user-edit"></i> Profile Information</h3>
                    <form id="accountForm">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="fullname">Full Name</label>
                                <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($full_name); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="birthdate">Birthdate</label>
                                <input type="date" id="birthdate" name="birthdate" value="<?php echo htmlspecialchars($birthdate); ?>">
                            </div>
                            <div class="form-group">
                                <label for="age">Age</label>
                                <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($age); ?>" readonly>
                            </div>
                        </div>
                        
                        <button type="submit" class="form-btn">Save Changes</button>
                    </form>
                </section>

                <section class="settings-section">
                    <h3><i class="fas fa-cog"></i> Account Preferences</h3>
                    <button class="form-btn secondary" onclick="openPasswordModal()">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </section>
            </div>
        </main>
    </div>

    <div class="modal" id="passwordModal">
        <div class="modal-content">
            <span class="close" id="closePasswordModal">&times;</span>
            <h2><i class="fas fa-lock"></i> Reset Password</h2>
            <form id="passwordForm">
                <div class="form-group">
                    <label for="currentPassword">Current Password</label>
                    <input type="password" id="currentPassword" required>
                </div>
                <div class="form-group">
                    <label for="newPassword">New Password</label>
                    <input type="password" id="newPassword" required>
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Confirm New Password</label>
                    <input type="password" id="confirmPassword" required>
                </div>
                <button type="submit" class="form-btn">Update Password</button>
            </form>
        </div>
    </div>
    
    <div id="notification" class="notification"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const passwordModal = document.getElementById("passwordModal");
    const closePasswordModalBtn = document.getElementById("closePasswordModal");
    const birthdateInput = document.getElementById('birthdate');
    const ageInput = document.getElementById('age');

    function toggleMenu() {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
    }

    if (menuToggle) menuToggle.addEventListener('click', toggleMenu);
    if (overlay) overlay.addEventListener('click', toggleMenu);

    
    window.openPasswordModal = function() {
        passwordModal.style.display = "flex";
    }
    if (closePasswordModalBtn) {
        closePasswordModalBtn.onclick = function() {
            passwordModal.style.display = "none";
        }
    }
    window.onclick = function(event) {
        if (event.target == passwordModal) {
            passwordModal.style.display = "none";
        }
    }

    // --- Function to Calculate Age ---
    function calculateAge() {
        if (birthdateInput.value) {
            const birthDate = new Date(birthdateInput.value);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const m = today.getMonth() - birthDate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            ageInput.value = age > 0 ? age : 0;
        } else {
            ageInput.value = '';
        }
    }
    
    if(birthdateInput) {
        birthdateInput.addEventListener('change', calculateAge);
    }
    
    function showNotification(message, type = 'success') {
        const notification = document.getElementById('notification');
        notification.textContent = message;
        notification.className = `notification show ${type}`;
        setTimeout(() => {
            notification.className = 'notification';
        }, 3000);
    }

    
    const accountForm = document.getElementById("accountForm");
    if(accountForm) {
        accountForm.addEventListener("submit", async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'update_profile');

            try {
                const response = await fetch('account.php', { method: 'POST', body: formData });
                const data = await response.json();
                
                showNotification(data.message, data.status);
                if (data.status === 'success') {
                    // Update header name without full reload
                    document.querySelector('.sidebar h3').textContent = formData.get('fullname');
                    // Optionally, wait a moment then reload to make sure sidebar is fully fresh
                    setTimeout(() => window.location.reload(), 1000);
                }
            } catch (error) {
                showNotification('An unexpected error occurred.', 'error');
                console.error('Error:', error);
            }
        });
    }

    const passwordForm = document.getElementById("passwordForm");
    if(passwordForm) {
        passwordForm.addEventListener("submit", async function(e) {
            e.preventDefault();
            const newPass = document.getElementById("newPassword").value;
            const confirmPass = document.getElementById("confirmPassword").value;
            
            if (newPass !== confirmPass) {
                showNotification("New passwords do not match!", 'error');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'change_password');
            formData.append('currentPassword', document.getElementById('currentPassword').value);
            formData.append('newPassword', newPass);

            try {
                const response = await fetch('account.php', { method: 'POST', body: formData });
                const data = await response.json();
                
                showNotification(data.message, data.status);
                if (data.status === 'success') {
                    passwordModal.style.display = "none";
                    this.reset();
                }
            } catch (error) {
                showNotification('An unexpected error occurred.', 'error');
                console.error('Error:', error);
            }
        });
    }
});
</script>

</body>
</html>