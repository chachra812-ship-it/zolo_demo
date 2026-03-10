<?php
session_start();

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Clear any pending registration
unset($_SESSION['pending_registration']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Zolo</title>
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
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b6d4fe;
            color: #0c5494;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .otp-notice {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            color: white;
        }
        .otp-notice strong {
            font-size: 1.2rem;
            display: block;
            margin-bottom: 5px;
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
            <h1>Create Account</h1>
            <p>Join Zolo and start growing your business</p>
            
            <div id="message"></div>
            
            <form id="registerForm">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required placeholder="John Doe">
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="john@example.com">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••" minlength="6">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="••••••••">
                </div>

                <div class="info-box">
                    📧 A 6-digit OTP will be displayed on the next screen for verification.
                </div>

                <button type="submit" class="auth-btn" id="submitBtn">Register</button>
            </form>

            <div class="auth-switch">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const btn = document.getElementById('submitBtn');
            const password = document.getElementById('password').value;
            const confirm_password = document.getElementById('confirm_password').value;
            const messageDiv = document.getElementById('message');

            if (password !== confirm_password) {
                messageDiv.innerHTML = '<div class="error-msg">Passwords do not match!</div>';
                return;
            }

            if (password.length < 6) {
                messageDiv.innerHTML = '<div class="error-msg">Password must be at least 6 characters!</div>';
                return;
            }

            btn.innerText = "Generating OTP...";
            btn.disabled = true;

            const formData = {
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                password: password
            };

            fetch('auth.php?action=register', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'otp_sent') {
                    messageDiv.innerHTML = '<div class="success-msg">✅ OTP Generated! Redirecting...</div>';
                    document.getElementById('registerForm').reset();
                    
                    // Store OTP in sessionStorage for display on next page
                    sessionStorage.setItem('otp', data.otp);
                    sessionStorage.setItem('otp_email', data.email);
                    
                    setTimeout(() => {
                        window.location.href = 'verify_otp.php';
                    }, 1000);
                } else {
                    messageDiv.innerHTML = '<div class="error-msg">❌ ' + data.message + '</div>';
                    btn.innerText = "Register";
                    btn.disabled = false;
                }
            })
            .catch(error => {
                messageDiv.innerHTML = '<div class="error-msg">Something went wrong! Please try again.</div>';
                btn.innerText = "Register";
                btn.disabled = false;
            });
        });
    </script>

</body>
</html>