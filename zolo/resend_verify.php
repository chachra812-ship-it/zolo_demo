<?php
session_start();
require_once 'config.php';

$message = "";
$messageType = "";
$showForm = true;

// Handle resend request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    
    if (!$email) {
        $message = "❌ Please enter a valid email address!";
        $messageType = "error";
    } else {
        // Check if user exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            if ($user['is_verified'] == 1) {
                $message = "ℹ️ This email is already verified! You can login.";
                $messageType = "info";
                $showForm = false;
            } else {
                // Generate new verification code
                $verification_code = bin2hex(random_bytes(32));
                
                $update = $conn->prepare("UPDATE users SET verification_code = ? WHERE id = ?");
                $update->bind_param("si", $verification_code, $user['id']);
                
                if ($update->execute()) {
                    // Send verification email
                    $verifyLink = "http://localhost/zolo/verify.php?code=$verification_code&id=" . $user['id'];
                    
                    $subject = "Zolo - Email Verification (Resend)";
                    $message_email = "
                    <!DOCTYPE html>
                    <html>
                    <head><title>Email Verification</title></head>
                    <body style='font-family: Arial, sans-serif; padding: 20px; background: #f5f7fa;'>
                        <div style='max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1);'>
                            <h2 style='color: #667eea; text-align: center;'>Zolo - Verify Your Email 📧</h2>
                            <p>Hello <strong>" . htmlspecialchars($user['name']) . "</strong>,</p>
                            <p>We received a request to resend your verification email. Please click the button below:</p>
                            <div style='text-align: center; margin: 30px 0;'>
                                <a href='$verifyLink' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 40px; text-decoration: none; border-radius: 50px; display: inline-block; font-weight: bold;'>Verify Email</a>
                            </div>
                            <p style='color: #666; font-size: 14px;'>Or copy this link:</p>
                            <p style='word-break: break-all; color: #667eea; font-size: 12px;'>$verifyLink</p>
                        </div>
                    </body>
                    </html>
                    ";
                    
                    $headers = "MIME-Version: 1.0\r\n";
                    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
                    $headers .= "From: Zolo <noreply@zolo.com>\r\n";
                    
                    $mailSent = mail($email, $subject, $message_email, $headers);
                    
                    $message = "✅ Verification email sent! Please check your inbox.";
                    $messageType = "success";
                    $showForm = false;
                }
            }
        } else {
            $message = "❌ Email not found! Please register first.";
            $messageType = "error";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resend Verification | Zolo</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { 
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); 
            min-height: 100vh; 
            display: flex; 
            justify-content: center; 
            align-items: center;
            margin: 0;
            padding: 20px;
        }
        .verify-container { 
            background: white; 
            padding: 50px; 
            border-radius: 20px; 
            text-align: center; 
            max-width: 500px; 
            width: 100%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3); 
        }
        .verify-icon { font-size: 4rem; margin-bottom: 20px; }
        .verify-container h1 { color: #1a1a2e; margin-bottom: 15px; }
        .verify-container p { color: #666; margin-bottom: 25px; }
        .verify-btn { 
            display: inline-block; 
            padding: 15px 40px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            text-decoration: none; 
            border-radius: 50px; 
            font-weight: 600; 
            transition: 0.3s;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        .verify-btn:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4); 
        }
        .success { color: #27ae60; }
        .error { color: #e74c3c; }
        .info { color: #3498db; }
        .resend-form input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            margin-bottom: 20px;
            outline: none;
        }
        .resend-form input:focus {
            border-color: #667eea;
        }
        .alert-msg {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .alert-info { background: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>

    <div class="verify-container">
        <?php if ($message): ?>
            <div class="verify-icon">
                <?php if ($messageType === 'success'): ?>✅
                <?php elseif ($messageType === 'error'): ?>❌
                <?php else: ?>ℹ️<?php endif; ?>
            </div>
            <h1 class="<?php echo $messageType; ?>"><?php echo $message; ?></h1>
            
            <?php if ($messageType === 'success'): ?>
                <p>Check your email and click the verification link.</p>
            <?php endif; ?>
            
            <div>
                <?php if ($messageType !== 'success'): ?>
                    <a href="resend_verify.php" class="verify-btn">Try Again</a>
                <?php endif; ?>
                <a href="index.php" class="verify-btn" style="background: #666;">Back to Home</a>
            </div>
        <?php else: ?>
            <div class="verify-icon">📧</div>
            <h1>Resend Verification Email</h1>
            <p>Enter your email address to receive a new verification link.</p>
            
            <form method="POST" class="resend-form">
                <input type="email" name="email" placeholder="Enter your email" required>
                <button type="submit" class="verify-btn" style="width: 100%;">Send Verification Email</button>
            </form>
            
            <p style="margin-top: 20px;">
                <a href="login.php" style="color: #667eea;">Back to Login</a>
            </p>
        <?php endif; ?>
    </div>

</body>
</html>