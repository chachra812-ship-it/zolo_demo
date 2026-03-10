<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $entered_otp = trim($_POST['otp']);
    
    if (empty($entered_otp)) {
        $error = "Please enter the OTP.";
    } elseif (!isset($_SESSION['otp']) || !isset($_SESSION['otp_time'])) {
        $error = "OTP session expired. Please register again.";
    } elseif (time() - $_SESSION['otp_time'] > 600) { // 10 minutes expiration
        $error = "OTP has expired. Please register again.";
        unset($_SESSION['otp'], $_SESSION['otp_time']);
    } elseif ($entered_otp != $_SESSION['otp']) {
        $error = "Invalid OTP. Please try again.";
    } else {
        // OTP valid: Save user to database
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        try {
            $stmt->execute([$_SESSION['reg_username'], $_SESSION['reg_email'], $_SESSION['reg_password']]);
            
            // Clean up session
            unset($_SESSION['otp'], $_SESSION['otp_time'], $_SESSION['reg_username'], $_SESSION['reg_email'], $_SESSION['reg_password']);
            
            // Log user in automatically
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$_SESSION['reg_email']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $_SESSION['user_id'] = $user['id'];
            
            header("Location: dashboard.php");
            exit;
        } catch (PDOException $e) {
            $error = "Registration failed. Username or email may already exist.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify OTP</title>
</head>
<body>
    <h2>Verify Your Email</h2>
    <p>Enter the 6-digit OTP sent to your email.</p>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="POST">
        <label>OTP: <input type="text" name="otp" maxlength="6" required></label><br>
        <button type="submit">Verify</button>
    </form>
    <p><a href="register.php">Back to Register</a></p>
</body>
</html>