<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
        exit;
    }

    $verification_code = rand(100000, 999999);
    $_SESSION['verification_code'] = $verification_code;
    $_SESSION['registration_email'] = $email;

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
        $mail->Subject = 'Your Verification Code for Kalinga Medical Clinic';
        $mail->Body    = "Hello,<br><br>Your verification code is: <b>$verification_code</b><br><br>Thank you,<br>Kalinga Medical Clinic";

        $mail->send();
        echo json_encode(['success' => true, 'message' => 'Verification code sent!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => "Could not send email. Please check your API key."]);
    }
}
?>