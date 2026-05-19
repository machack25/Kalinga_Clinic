<?php
session_start();
include("db.php");

$error_message = "";

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $user_name          = trim($_POST['username']);
    $p_num              = trim($_POST['phone']);
    $full_name          = trim($_POST['full_name']);
    $birthdate          = trim($_POST['birthdate']);         // <-- ADDED -->
    $age                = trim($_POST['age']);               // <-- ADDED -->
    $email              = trim($_POST['email']);
    $password           = $_POST['password'];
    $c_password         = $_POST['c_password'];
    $verification_code  = trim($_POST['verification_code']);

    
    $session_code = isset($_SESSION['verification_code']) ? $_SESSION['verification_code'] : null;
    $session_email = isset($_SESSION['registration_email']) ? $_SESSION['registration_email'] : null;

    // <-- MODIFIED: Added birthdate and age to the empty check -->
    if (empty($user_name) || empty($p_num) || empty($full_name) || empty($birthdate) || empty($age) || empty($email) || empty($password) || empty($verification_code)) {
        $error_message = "Please fill all required fields.";
    } elseif (empty($session_code) || $verification_code != $session_code) {
        $error_message = "Invalid or expired verification code.";
    } elseif (empty($session_email) || $email != $session_email) {
        $error_message = "Email mismatch. Please start registration over.";
    
    } elseif (strlen($p_num) !== 10 || !ctype_digit($p_num)) {
        $error_message = "Phone number must be exactly 10 digits.";
    
    // <-- ADDED: Age validation -->
    } elseif (!filter_var($age, FILTER_VALIDATE_INT, ["options" => ["min_range" => 0, "max_range" => 120]])) {
        $error_message = "Please enter a valid age.";
        
    } elseif ($password !== $c_password) {
        $error_message = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error_message = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
        $error_message = "Password must contain at least one special character.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $formatted_phone = '+63' . $p_num;
        $validated_age = (int)$age; // <-- ADDED: Cast age to integer

        // <-- MODIFIED: Updated INSERT query -->
        $stmt = $con->prepare("INSERT INTO users (username, phone, full_name, birthdate, age, email, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        // <-- MODIFIED: Updated bind_param string to "ssssiss" and added variables -->
        $stmt->bind_param("ssssiss", $user_name, $formatted_phone, $full_name, $birthdate, $validated_age, $email, $hashed_password);

        if ($stmt->execute()) {
            
            unset($_SESSION['verification_code']);
            unset($_SESSION['registration_email']);
            
            echo "<script>alert('Successfully Registered'); window.location='loginpage.php';</script>";
            exit();
        } else {
            $error_message = ($stmt->errno == 1062) ? "An account with this email already exists." : "Error: Could not register.";
        }
        $stmt->close();
    }

    if (!empty($error_message)) {
        echo "<script>alert('" . addslashes($error_message) . "');</script>";
    }
}
?>
    
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register - Kalinga Medical Clinic</title>
  <link rel="stylesheet" href="register.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
  <section class="hero">
    <h1>Register for Your Account</h1>
    <p>Join Kalinga Medical Clinic for quality healthcare</p>
  </section>

  <div class="register-container">
    <img src="logoo.PNG" alt="Clinic Logo" class="register-logo">
    
    <form id="registerForm" method="POST" action="">
      <div id="emailStep">
        <div class="form-grid" style="grid-template-columns: 1fr;">
          <input type="email" name="email" id="email" placeholder="Enter Your Email Address" class="form-full" required>
        </div>
        <button type="button" id="sendCodeBtn" class="register-btn" style="background: linear-gradient(45deg, #007bff, #0056b3); margin-top: 10px;">Send Verification Code</button>
        <div id="emailError" class="error-message" style="text-align: center; margin-top: 15px;"></div>
      </div>

      <div id="fullFormStep" style="display: none;">
        <div class="form-grid">
          <input type="email" id="emailDisplay" class="form-full" readonly style="background-color: #e9ecef; border: 1px solid #ced4da;">
          <input type="text" name="verification_code" placeholder="6-Digit Code from Email" maxlength="6" class="form-full" required>
          <input type="text" name="username" placeholder="Username" required>
          <div class="phone-wrapper">
            <span class="phone-prefix">+63</span>
            <input type="tel" name="phone" id="phone" placeholder="9123456789" maxlength="10" required>
          </div>
          <input type="text" name="full_name" placeholder="Full Name" class="form-full" required>
          
          <input type="text" name="birthdate" placeholder="Birth Date" onfocus="(this.type='date')" onblur="if(!this.value) this.type='text'" class="form-full" required>
          <input type="number" name="age" id="age" placeholder="Age" min="0" max="120" class="form-full" required>
          <input type="password" name="password" id="password" placeholder="Password (min 8 chars, 1 upper, 1 special)" required>
          <input type="password" name="c_password" id="confirmPassword" placeholder="Confirm Password" required>
        </div>
        <div id="passwordError" class="error-message"></div>
        <div class="terms">
          <label>
            <input type="checkbox" id="termsCheck" required disabled>
            I agree to the <a id="openTerms">Terms & Conditions</a>
          </label>
        </div>
        <button type="submit" class="register-btn">Create Account</button>
      </div>
    </form>
  </div>

  <div class="modal" id="termsModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Terms & Conditions</h2>
            <span class="close-btn" id="closeModal">&times;</span>
        </div>
        <div class="modal-body">
            <p><strong>Last updated: October 11, 2025</strong></p>
            <p>Please read these terms and conditions carefully before using Our Service.</p>

           
            <p>Welcome to Kalinga Medical Clinic. These Terms and Conditions govern your use of our online registration and patient portal services. By creating an account and using the Service, you agree to be bound by these Terms. If you disagree with any part of the terms, then you may not access the Service.</p>
            
            <p>When you create an account with us, you must provide information that is accurate, complete, and current at all times. Failure to do so constitutes a breach of the Terms, which may result in immediate termination of your account on our Service. You are responsible for safeguarding the password that you use to access the Service and for any activities or actions under your password. You agree not to disclose your password to any third party.</p>
            
            <p><strong>Data Collection and Use:</strong> To use this Service, you must provide personal information including your full name, email address, phone number, birthdate, and age. By creating an account, you consent to Kalinga Medical Clinic collecting, storing, and using this information to:
            <ul style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px;">
              <li style="margin-bottom: 5px;">Create and manage your secure patient profile.</li>
              <li style="margin-bottom: 5px;">Schedule and manage your appointments.</li>
              <li style="margin-bottom: 5px;">Contact you with important updates about your appointments or services.</li>
            </ul>
            Your personal health information will be handled with the utmost confidentiality and is protected in compliance with the Data Privacy Act of 2012 (RA 10173) of the Philippines. We will not share your information with third parties without your explicit consent, except as required by law.</p>
            <p>The information provided through this Service is for informational purposes only and is not a substitute for professional medical advice, diagnosis, or treatment. Always seek the advice of your physician or other qualified health provider with any questions you may have regarding a medical condition.</p>

            <p>We may terminate or suspend your account immediately, without prior notice or liability, for any reason whatsoever, including without limitation if you breach the Terms. Upon termination, your right to use the Service will immediately cease.</p>

            <p>These Terms shall be governed and construed in accordance with the laws of the Republic of the Philippines, without regard to its conflict of law provisions.</p>
            
            <p>We reserve the right, at our sole discretion, to modify or replace these Terms at any time. We will provide at least 30 days' notice prior to any new terms taking effect. By continuing to access or use our Service after those revisions become effective, you agree to be bound by the revised terms.</p>
            
            <p>If you have any questions about these Terms, please contact us through our official clinic channels.</p>
        </div>
        <div class="modal-footer">
            <button type="button" id="acceptTermsBtn">I Understand</button> </div>
    </div>
  </div>

  <script>
    // ... (all existing const definitions are correct)
    const sendCodeBtn = document.getElementById("sendCodeBtn");
    const emailField = document.getElementById("email");
    const emailDisplayField = document.getElementById("emailDisplay");
    const emailStep = document.getElementById("emailStep");
    const fullFormStep = document.getElementById("fullFormStep");
    const emailError = document.getElementById("emailError");
    
    // ... (existing sendCodeBtn.addEventListener is correct)
    sendCodeBtn.addEventListener("click", async function() {
        const email = emailField.value;
        if (!email || !/^\S+@\S+\.\S+$/.test(email)) {
            emailError.textContent = "Please enter a valid email address.";
            return;
        }

        sendCodeBtn.disabled = true;
        sendCodeBtn.textContent = "Sending...";
        emailError.textContent = "";
        
        const formData = new FormData();
        formData.append('email', email);

        try {
            const response = await fetch('send_verification.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.success) {
                alert('Verification code sent! Please check your email inbox (and spam folder).');
                emailStep.style.display = 'none';
                emailDisplayField.value = email; 
                const hiddenEmailInput = document.createElement('input');
                hiddenEmailInput.type = 'hidden';
                hiddenEmailInput.name = 'email';
                hiddenEmailInput.value = email;
                document.getElementById('registerForm').appendChild(hiddenEmailInput);
                
                fullFormStep.style.display = 'block';
            } else {
                emailError.textContent = "Error: " + result.message;
                sendCodeBtn.disabled = false;
                sendCodeBtn.textContent = "Send Verification Code";
            }
        } catch (error) {
            emailError.textContent = "A network error occurred. Please try again.";
            sendCodeBtn.disabled = false;
            sendCodeBtn.textContent = "Send Verification Code";
        }
    });

    // ... (existing registerForm.addEventListener is correct)
    document.getElementById("registerForm").addEventListener("submit", function(e) {
        if (fullFormStep.style.display === 'none') { 
             e.preventDefault();
             return;
        }

        const password = document.getElementById("password").value;
        const confirmPassword = document.getElementById("confirmPassword").value;
        const phone = document.getElementById("phone").value;
        const errorDiv = document.getElementById("passwordError");
        let errorMessage = "";

        if (phone.length !== 10 || !/^\d+$/.test(phone)) errorMessage = "Phone number must be exactly 10 digits (e.g., 9123456789).";
        else if (password.length < 8) errorMessage = "Password must be at least 8 characters long.";
        else if (!/[A-Z]/.test(password)) errorMessage = "Password must contain at least one uppercase letter.";
        else if (!/[!@#$%^&*()_+\-=\[\]{};':\"\\|,.<>\/?]/.test(password)) errorMessage = "Password must contain at least one special character.";
        else if (password !== confirmPassword) errorMessage = "Passwords do not match.";
        
        errorDiv.textContent = errorMessage;
        if (errorMessage) {
            e.preventDefault();
            return;
        }

        if (!document.getElementById("termsCheck").checked) {
            e.preventDefault();
            alert("⚠️ You must agree to the Terms & Conditions before registering.");
        }
    });

    
    const termsModal = document.getElementById("termsModal");
    const openTerms = document.getElementById("openTerms");
    const closeModal = document.getElementById("closeModal");
    const acceptTermsBtn = document.getElementById("acceptTermsBtn");

    openTerms.onclick = (e) => { 
        e.preventDefault();
        termsModal.style.display = "flex"; 
    }

    const closeTheModal = () => { 
        termsModal.style.display = "none"; 
    }
    
    closeModal.onclick = closeTheModal;
    
    // <-- MODIFIED: Updated acceptTermsBtn click handler -->
    acceptTermsBtn.onclick = () => {
        document.getElementById("termsCheck").disabled = false; // Enable the checkbox
        document.getElementById("termsCheck").checked = true;   // Check it for the user
        closeTheModal(); // Close the modal
    } 

    window.onclick = (event) => { 
        if (event.target == termsModal) {
            closeTheModal();
        }
    }
  </script>
</body>
</html>