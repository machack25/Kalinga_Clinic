<?php
session_start();
include("db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = trim($_POST['username']);
  $password = trim($_POST['password']);
  $roleAttempt = $_POST['role']; // "admin" or "super_admin"

  $stmt = $con->prepare("SELECT admin_id, password, role FROM admin WHERE username = ?");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 1) {
    $admin = $result->fetch_assoc();

    if (password_verify($password, $admin['password'])) {

      if ($admin['role'] === $roleAttempt) {
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $admin['role'];

        if ($admin['role'] === 'admin') {
          header("Location: doctor dashboard.php");
          exit();
        } elseif ($admin['role'] === 'super_admin') {
          header("Location: superadmindashboard.php");
          exit();
        }
      } else {
        $error = "Invalid username, password, or role.";
      }
    } else {
      $error = "Invalid username, password, or role.";
    }
 } else {
 $error = "Invalid username, password, or role.";
 }
}
?> 






<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login - Kalinga Medical Clinic</title>
  <link rel="stylesheet" href="loginadmin.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
  <section class="hero">
    <h1>Admin Access</h1>
    <p>Secure login for authorized personnel</p>
  </section>

  <div class="login-container">
    <img src="logoo.PNG" alt="Clinic Logo" class="login-logo">

    <form method="POST" action="loginadmin.php">
  <input type="text" name="username" placeholder="Admin Username" required>
  <input type="password" name="password" placeholder="Password" required>

  <button type="submit" name="role" value="admin" class="login-btn admin-btn">Login as Admin</button>
  <button type="submit" name="role" value="super_admin" class="login-btn super-admin-btn">Login as Super Admin</button>
</form>

    <a href="loginpage.php" class="back-btn">⬅ Back to User Login</a>
  </div>

  <script>
    function loginAsAdmin() {
      window.location.href = "Doctor Dashboard.php";
    }

    function loginAsSuperAdmin() {
      window.location.href = "loginsadmin.php";
    }
  </script>
</body>
</html>