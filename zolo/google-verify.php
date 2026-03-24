<?php
session_start();
require_once 'config.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id']) && $_SESSION['logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}

// Handle Google callback (code parameter)
if (isset($_GET['code'])) {
    // SIMULATED Google response (Replace with real API later)
    $googleUser = [
        'email' => isset($_GET['email']) ? $_GET['email'] : 'demo.user@gmail.com',
        'name' => isset($_GET['name']) ? $_GET['name'] : 'Demo Google User',
        'picture' => 'https://via.placeholder.com/100'
    ];
    
    try {
        // Check if user exists
        $stmt = $conn->prepare("SELECT id, username, full_name FROM users WHERE email = ?");
        $stmt->bind_param("s", $googleUser['email']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Login existing user
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_email'] = $googleUser['email'];
            $_SESSION['full_name'] = $user['full_name'];
        } else {
            // Create new user
            $username = strtolower(str_replace([' ', '.', '@'], '_', $googleUser['email']));
            $full_name = $googleUser['name'];
            
            $stmt = $conn->prepare("INSERT INTO users (username, email, full_name, role, status, is_verified) VALUES (?, ?, ?, 'customer', 'active', 1)");
            $stmt->bind_param("sss", $username, $googleUser['email'], $full_name);
            $stmt->execute();
            
            $_SESSION['user_id'] = $conn->insert_id;
            $_SESSION['username'] = $username;
            $_SESSION['user_email'] = $googleUser['email'];
            $_SESSION['full_name'] = $full_name;
        }
        
        // Set login session
        $_SESSION['role'] = 'customer';
        $_SESSION['is_verified'] = 1;
        $_SESSION['logged_in'] = true;
        $_SESSION['provider'] = 'google';
        $_SESSION['login_time'] = time();
        
        // Redirect to dashboard
        header("Location: dashboard.php?login=google_success");
        exit;
        
    } catch (Exception $e) {
        $error = "Login failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Login | Zolo</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            padding: 20px;
        }
        .login-container { 
            background: rgba(255,255,255,0.95); 
            backdrop-filter: blur(10px);
            padding: 60px 40px; 
            border-radius: 25px; 
            box-shadow: 0 25px 50px rgba(0,0,0,0.2); 
            width: 100%; 
            max-width: 450px; 
            text-align: center; 
            border: 1px solid rgba(255,255,255,0.2);
        }
        .logo { font-size: 3rem; margin-bottom: 20px; }
        h1 { color: #1a1a2e; margin-bottom: 15px; font-size: 2.2rem; }
        .subtitle { color: #666; margin-bottom: 40px; font-size: 1.1rem; }
        .google-btn { 
            width: 100%; 
            padding: 18px; 
            background: linear-gradient(135deg, #4285f4 0%, #34a853 50%, #fbbc05 75%, #ea4335 100%); 
            color: white; 
            border: none; 
            border-radius: 12px; 
            font-size: 1.2rem; 
            font-weight: 600; 
            cursor: pointer; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 15px; 
            margin-bottom: 25px; 
            transition: all 0.3s; 
            text-decoration: none;
            box-shadow: 0 8px 25px rgba(66, 133, 244, 0.4);
        }
        .google-btn:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 15px 35px rgba(66, 133, 244, 0.6); 
        }
        .google-icon { font-size: 1.5rem; }
        .divider { 
            display: flex; 
            align-items: center; 
            margin: 30px 0; 
            color: #999; 
        }
        .divider::before, .divider::after { 
            content: ''; 
            flex: 1; 
            height: 1px; 
            background: #e0e0e0; 
        }
        .divider span { padding: 0 20px; font-size: 0.9rem; }
        .email-login { 
            color: #667eea; 
            text-decoration: none; 
            font-weight: 600; 
            font-size: 1.1rem; 
        }
        .error { 
            background: #fee; 
            color: #c33; 
            padding: 15px; 
            border-radius: 10px; 
            margin-bottom: 25px; 
            border-left: 4px solid #c33; 
        }
        @media (max-width: 480px) {
            .login-container { padding: 40px 25px; margin: 10px; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">🌐</div>
        <h1>Login with Google</h1>
        <p class="subtitle">Fast, secure login with your Google account</p>
        
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- SIMPLIFIED Google Login - Click to test -->
        <a href="?code=success&email=test@gmail.com&name=Test%20Google%20User" class="google-btn">
            <span class="google-icon">👤</span>
            Continue with Google
        </a>
        
        <div class="divider">
            <span>or</span>
        </div>
        
        <a href="login.php" class="email-login">📧 Login with Email</a>
        <p style="margin-top: 25px; color: #999; font-size: 0.9rem;">
            Don't have account? <a href="register.php" style="color: #667eea;">Register here</a>
        </p>
    </div>

    <?php if (isset($_GET['debug'])): ?>
    <script>
        // Debug info
        console.log('Google Login Debug:', {
            code: '<?= $_GET['code'] ?? 'none' ?>',
            email: '<?= $_GET['email'] ?? 'none' ?>',
            session: <?= json_encode($_SESSION) ?>
        });
    </script>
    <?php endif; ?>
</body>
</html>