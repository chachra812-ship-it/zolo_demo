<?php
session_start();

// If already logged in, redirect to home
if (isset($_SESSION['user_id']) && $_SESSION['is_verified'] == 1) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Zolo</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { 
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); 
            min-height: 100vh; 
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .auth-container {
            background: white;
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
        }
        .auth-container h1 {
            text-align: center;
            color: #1a1a2e;
            margin-bottom: 10px;
        }
        .auth-container > p {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }
        .auth-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        .auth-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        .auth-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        .auth-switch {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        .auth-switch a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .success-msg {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        .error-msg {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>

    <!-- Top Bar -->
    <div class="top-bar">
        <div class="top-bar-container">
            <div class="top-bar-left">
                📧 info@zolo.com | 📞 +1 234 567 890
            </div>
            <div class="top-bar-right">
                <a href="login.php" class="login-btn-small">Login</a>
                <a href="register.php" class="register-btn-small">Register</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="auth-container">
            <h1>Welcome Back</h1>
            <p>Login to your Zolo account</p>
            
            <div id="message"></div>
            
            <form id="loginForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="john@example.com">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••">
                </div>

                <button type="submit" class="auth-btn" id="submitBtn">Login</button>
            </form>

            <div class="auth-switch">
                Don't have an account? <a href="register.php">Register here</a>
            </div>
        </div>
    </div>
    <!-- Google Sign-In Script -->
<script src="https://accounts.google.com/gsi/client" async defer></script>

<div id="g_id_onload"
     data-client_id="1025800005830-0o4rd60o6tmmnnh3kvlbb8gs0m97f7qe.apps.googleusercontent.com"
     data-login_uri="http://localhost/zolo/google-verify.php"
     data-auto_prompt="false">
</div>

<div class="g_id_signin"
     data-type="standard"
     data-size="large"
     data-theme="outline"
     data-text="sign_in_with"
     data-shape="rectangular">
</div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const btn = document.getElementById('submitBtn');
            const messageDiv = document.getElementById('message');

            btn.innerText = "Logging in...";
            btn.disabled = true;

            const formData = {
                email: document.getElementById('email').value,
                password: document.getElementById('password').value
            };

            fetch('auth.php?action=login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    messageDiv.innerHTML = '<div class="success-msg">' + data.message + '</div>';
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1000);
                } else {
                    messageDiv.innerHTML = '<div class="error-msg">' + data.message + '</div>';
                    btn.innerText = "Login";
                    btn.disabled = false;
                }
            })
            .catch(error => {
                messageDiv.innerHTML = '<div class="error-msg">Something went wrong! Please try again.</div>';
                btn.innerText = "Login";
                btn.disabled = false;
            });
        });
    </script>

</body>
</html>