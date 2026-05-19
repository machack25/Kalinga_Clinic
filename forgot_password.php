<?php
session_start();
include("db.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    $stmt = $con->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $user_id = $user['id'];

        $token = bin2hex(random_bytes(32));

        
        $stmt = $con->prepare("UPDATE users SET reset_token = ?, reset_token_expires = NOW() + INTERVAL 30 MINUTE WHERE id = ?");
        $stmt->bind_param("si", $token, $user_id);
        $stmt->execute();

        
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;
        
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.sendgrid.net';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'apikey';
            $mail->Password   = getenv('SENDGRID_API_KEY');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('kalingaclinic01@gmail.com', 'Kalinga Medical Clinic');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body    = "Hello,<br><br>You requested a password reset. Click the link below to set a new password. This link is valid for 30 minutes.<br><br><a href='$reset_link'>Reset Password</a><br><br>If you did not request this, please ignore this email.";
            
            $mail->send();
        } catch (Exception $e) {
            error_log("Mailer Error: " . $mail->ErrorInfo);
        }
    }
    $message = "If an account with that email exists, a password reset link has been sent.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>Forgot Password - Kalinga Medical Clinic</title>
 <link rel="stylesheet" href="loginpage.css">
 <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
 <section class="hero"><h1>Password Reset</h1><p>Enter your email to receive a reset link</p></section>
 <div class="login-container">
  <img src="logoo.png" alt="Clinic Logo" class="login-logo">
  <?php if (!empty($message)): ?>
    <p style="text-align: center; color: green;"><?php echo $message; ?></p>
  <?php endif; ?>
  <form method="POST" action="">
    <input type="email" name="email" placeholder="Enter your email address" required>
    <button type="submit" class="login-btn">Send Reset Link</button>
  </form>
  <a href="loginpage.php" style="display: block; text-align: center; margin-top: 20px;">Back to Login</a>
 </div>
</body>
</html>