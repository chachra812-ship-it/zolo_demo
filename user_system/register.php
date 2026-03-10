<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = "Password must include at least one uppercase letter (A-Z).";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = "Password must include at least one number (0-9).";
    } else {
        // Generate 6-digit OTP
        $otp = rand(100000, 999999);
        
        // Attempt to send OTP via mail() (may not work locally)
        $subject = 'Your OTP for Registration';
        $message = "Your OTP is: $otp. It expires in 10 minutes.";
        $headers = 'From: yourwork@gmail.com' . "\r\n" .  // Replace with your Gmail
                   'Reply-To: yourwork@gmail.com' . "\r\n" .
                   'X-Mailer: PHP/' . phpversion();
        
        $mailSent = mail($email, $subject, $message, $headers);
        
        if ($mailSent) {
            $success = "OTP sent to your email. Check your inbox (and spam folder).";
        } else {
            // Demo fallback: Show OTP on page if email fails
            $error = "Email sending failed (common in local setups). For demo, your OTP is: <strong>$otp</strong>. Use it below.";
        }
        
        // Store OTP and data in session regardless
        $_SESSION['otp'] = $otp;
        $_SESSION['otp_time'] = time(); // Timestamp for expiration
        $_SESSION['reg_username'] = $username;
        $_SESSION['reg_email'] = $email;
        $_SESSION['reg_password'] = password_hash($password, PASSWORD_DEFAULT);
        
        // Don't redirect yet—show message and form for OTP entry
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
</head>
<body>
    <h2>Register</h2>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <?php if (isset($success)) echo "<p style='color:green;'>$success</p>"; ?>
    
    <?php if (!isset($_SESSION['otp'])): // Show form if OTP not generated yet ?>
        <form method="POST">
            <label>Username: <input type="text" name="username" required></label><br>
            <label>Email: <input type="email" name="email" required></label><br>
            <p>Password must include:</p>
            <ul>
                <li>At least 6 characters</li>
                <li>At least one uppercase letter (A-Z)</li>
                <li>At least one number (0-9)</li>
            </ul>
            <label>Password: <input type="password" name="password" required></label><br>
            <label>Confirm Password: <input type="password" name="confirm_password" required></label><br>
            <button type="submit">Register</button>
        </form>
    <?php else: // Show OTP verification form after generation ?>
        <p>Enter the 6-digit OTP sent to your email (or shown above if email failed).</p>
        <form method="POST" action="verify_otp.php">
            <label>OTP: <input type="text" name="otp" maxlength="6" required></label><br>
            <button type="submit">Verify</button>
        </form>
        <p><a href="register.php">Back to Register</a></p>
    <?php endif; ?>
    
    <p>Already have an account? <a href="login.php">Login</a></p>
</body>
</html>