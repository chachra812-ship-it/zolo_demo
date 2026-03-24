<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>✅ Registration Successful | Zolo</title>
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
        .success-container { 
            background: white; 
            padding: 60px 40px; 
            border-radius: 25px; 
            text-align: center; 
            max-width: 500px; 
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3); 
            animation: slideUp 0.8s ease-out;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .success-icon { 
            font-size: 6rem; 
            background: linear-gradient(135deg, #48bb78, #38a169); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent; 
            background-clip: text;
            margin-bottom: 20px; 
        }
        .success-container h1 { 
            color: #1a1a2e; 
            font-size: 2.5rem; 
            margin-bottom: 15px; 
        }
        .success-container p { 
            color: #666; 
            font-size: 1.2rem; 
            margin-bottom: 30px; 
            line-height: 1.6; 
        }
        .success-details { 
            background: #f8f9ff; 
            border: 2px solid #e1e8ff; 
            border-radius: 15px; 
            padding: 25px; 
            margin: 30px 0; 
            text-align: left; 
        }
        .success-details h3 { 
            color: #667eea; 
            margin-bottom: 15px; 
            font-size: 1.3rem; 
        }
        .detail-row { 
            display: flex; 
            justify-content: space-between; 
            margin-bottom: 10px; 
            padding: 8px 0; 
            border-bottom: 1px solid #e8ecff; 
        }
        .detail-label { font-weight: 600; color: #4a5568; }
        .detail-value { color: #2d3748; }
        .btn-group { display: flex; gap: 15px; margin-top: 30px; }
        .btn { 
            flex: 1; 
            padding: 15px 25px; 
            border: none; 
            border-radius: 12px; 
            font-size: 1.1rem; 
            font-weight: 600; 
            cursor: pointer; 
            text-decoration: none; 
            display: inline-block; 
            transition: all 0.3s; 
            text-align: center; 
        }
        .btn-primary { 
            background: linear-gradient(135deg, #48bb78, #38a169); 
            color: white; 
        }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(72, 187, 120, 0.4); }
        .btn-secondary { 
            background: linear-gradient(135deg, #667eea, #764ba2); 
            color: white; 
        }
        .btn-secondary:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4); }
        .countdown { 
            background: #fff3cd; 
            color: #856404; 
            padding: 12px; 
            border-radius: 10px; 
            margin-top: 20px; 
            font-weight: 600; 
        }
        @media (max-width: 768px) {
            .btn-group { flex-direction: column; }
            .success-container { padding: 40px 25px; }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">✅</div>
        <h1>Registration Successful!</h1>
        <p>Your account has been created and verified successfully. Welcome to Zolo!</p>
        
        <?php if (isset($_SESSION['user_email'])): ?>
        <div class="success-details">
            <h3>📋 Account Details</h3>
            <div class="detail-row">
                <span class="detail-label">Email:</span>
                <span class="detail-value"><?= htmlspecialchars($_SESSION['user_email']) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status:</span>
                <span class="detail-value" style="color: #48bb78;">✅ Verified</span>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="btn-group">
            <a href="login.php" class="btn btn-primary">🚀 Go to Login</a>
            <a href="index.php" class="btn btn-secondary">🏠 Home</a>
        </div>
        
        <div class="countdown">
            ⏱️ Auto-redirecting to login in <span id="countdown">5</span> seconds...
        </div>
    </div>

    <script>
        let timeLeft = 5;
        const countdown = document.getElementById('countdown');
        
        const timer = setInterval(() => {
            timeLeft--;
            countdown.textContent = timeLeft;
            
            if (timeLeft <= 0) {
                clearInterval(timer);
                window.location.href = 'login.php';
            }
        }, 1000);
    </script>
</body>
</html>