<?php
session_start();
include("db.php");

$token = $_GET['token'] ?? null;
$error = "";
$token_valid = false;

if ($token) {
    $stmt = $con->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $token_valid = true;
        $user = $result->fetch_assoc();
        $user_id = $user['id'];
    } else {
        $error = "This password reset link is invalid or has expired.";
    }
} else {
    $error = "No reset token provided.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $token_valid) {
    $password = $_POST['password'];
    $c_password = $_POST['c_password'];

    if ($password === $c_password && strlen($password) >= 8) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $con->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        $stmt->execute();

        echo "<script>alert('Password has been reset successfully!'); window.location='loginpage.php';</script>";
        exit();
    } else {
        $error = "Passwords do not match or are less than 8 characters.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>Reset Password - Kalinga Medical Clinic</title>
 <link rel="stylesheet" href="loginpage.css">
 <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
 <section class="hero"><h1>Set New Password</h1><p>Please enter your new password below</p></section>
 <div class="login-container">
  <img src="logoo.png" alt="Clinic Logo" class="login-logo">
  
  <?php if ($token_valid): ?>
    <?php if (!empty($error)): ?>
        <p style="text-align: center; color: red;"><?php echo $error; ?></p>
    <?php endif; ?>
    <form method="POST" action="">
      <input type="password" name="password" placeholder="New Password" required minlength="8">
      <input type="password" name="c_password" placeholder="Confirm New Password" required>
      <button type="submit" class="login-btn">Reset Password</button>
    </form>
  <?php else: ?>
    <p style="text-align: center; color: red;"><?php echo $error; ?></p>
    <a href="forgot_password.php" style="display: block; text-align: center; margin-top: 20px;">Request a new link</a>
  <?php endif; ?>
 </div>
</body>
</html>