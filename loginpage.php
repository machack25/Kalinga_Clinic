<?php
session_start();
include("db.php");

$error_message = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim(strtolower($_POST['email']));
    $password = $_POST['password'];

    $stmt = $con->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        
        $password_valid = password_get_info($row['password'])['algo']
            ? password_verify($password, $row['password'])
            : ($password === $row['password']);

        if ($password_valid) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            header("Location: patient dashboard.php");
            exit();
        } else {
            $error_message = "Invalid email or password. Please try again.";
        }
    } else {
        $error_message = "Invalid email or password. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kalinga Medical Clinic</title>
    <link rel="stylesheet" href="loginpage.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="hero">
        <h1>Secure Account Login</h1>
        <p>Access your patient dashboard</p>
    </header>

    <main class="login-container">
        <img src="logoo.PNG" alt="Clinic Logo" class="login-logo">

        <form id="loginForm" method="POST" action="" novalidate>

            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="input-group">
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="input-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>

            <div class="options">
                <label class="remember"><input type="checkbox"> Remember me</label>
                <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
            </div>

            <button type="submit" class="login-btn">Login</button>
            
            <div class="admin-login-section">
                <a href="loginadmin.php" class="admin-link">Login as Admin or Doctor</a>
            </div>
        </form>
    </main>

</body>
</html>