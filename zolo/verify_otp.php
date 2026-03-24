<?php
session_start();

// If already verified and logged in, redirect
if (isset($_SESSION['user_id']) && $_SESSION['is_verified'] == 1) {
    header("Location: index.php");
    exit;
}

// Check if there's a pending registration
if (!isset($_SESSION['pending_registration'])) {
    header("Location: register.php");
    exit;
}
// Handle AJAX verification (add this)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'verify_otp') {
    header('Content-Type: application/json');
    
    $input = json_decode(file_get_contents('php://input'), true);
    $otp = trim($input['otp'] ?? '');
    
    $pending = $_SESSION['pending_registration'];
    
    if (time() - $pending['otp_time'] > 300) {
        unset($_SESSION['pending_registration']);
        echo json_encode(['status' => 'error', 'message' => 'OTP expired']);
        exit;
    }
    
    if ($otp !== $pending['otp']) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid OTP']);
        exit;
    }
    
    echo json_encode(['status' => 'success', 'message' => 'Verified!']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP | Zolo</title>
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
        .verify-container h1 { color: #1a1a2e; margin-bottom: 10px; }
        .verify-container p { color: #666; margin-bottom: 25px; line-height: 1.6; }
        
        .otp-inputs {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 30px 0;
        }
        .otp-input {
            width: 50px;
            height: 60px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            outline: none;
            transition: 0.3s;
        }
        .otp-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 10px rgba(102, 126, 234, 0.3);
        }
        
        .verify-btn {
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
        .verify-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        .verify-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        .resend-link {
            display: inline-block;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            cursor: pointer;
        }
        .resend-link:hover {
            text-decoration: underline;
        }
        .resend-link.disabled {
            color: #999;
            cursor: not-allowed;
        }
        
        .success-msg {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .error-msg {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .otp-display {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 25px;
            border-radius: 15px;
            margin: 20px 0;
        }
        .otp-display .otp-code {
            font-size: 2.5rem;
            font-weight: bold;
            color: white;
            letter-spacing: 15px;
        }
        .otp-display small {
            color: rgba(255,255,255,0.9);
            display: block;
            margin-top: 10px;
            font-size: 0.9rem;
        }
        .otp-display .email-display {
            color: #ffd700;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

    <div class="verify-container">
        <div class="verify-icon">🔐</div>
        <h1>Verify Your Email</h1>
        <p>Enter the 6-digit OTP displayed below to complete registration.</p>
        
        <div id="message"></div>
        
        <!-- OTP Display (Shows OTP on Screen) -->
        <div class="otp-display">
            <div class="email-display">📧 <?php echo htmlspecialchars($_SESSION['pending_registration']['email']); ?></div>
            <span class="otp-code"><?php echo $_SESSION['pending_registration']['otp']; ?></span>
            <small>Enter this OTP in the boxes below</small>
        </div>
        
        <form id="otpForm">
            <div class="otp-inputs">
                <input type="text" class="otp-input" maxlength="1" data-index="1">
                <input type="text" class="otp-input" maxlength="1" data-index="2">
                <input type="text" class="otp-input" maxlength="1" data-index="3">
                <input type="text" class="otp-input" maxlength="1" data-index="4">
                <input type="text" class="otp-input" maxlength="1" data-index="5">
                <input type="text" class="otp-input" maxlength="1" data-index="6">
            </div>
            
            <button type="submit" class="verify-btn" id="verifyBtn">Verify OTP</button>
        </form>
        
        <div id="resendSection">
            <a href="#" class="resend-link" id="resendBtn">Resend OTP</a>
            <div id="countdown" style="margin-top: 10px; color: #666;"></div>
        </div>
        
        <div style="margin-top: 20px;">
            <a href="register.php" style="color: #666; text-decoration: none;">← Start Over</a>
        </div>
    </div>

    <script>
        // OTP Input Handling
        const otpInputs = document.querySelectorAll('.otp-input');
        const form = document.getElementById('otpForm');
        const messageDiv = document.getElementById('message');
        const verifyBtn = document.getElementById('verifyBtn');
        
        otpInputs.forEach((input, index) => {
            // Handle input
            input.addEventListener('input', function(e) {
                const value = this.value;
                
                // Only allow numbers
                if (!/^\d*$/.test(value)) {
                    this.value = '';
                    return;
                }
                
                if (value.length === 1) {
                    if (index < otpInputs.length - 1) {
                        otpInputs[index + 1].focus();
                    }
                }
            });
            
            // Handle backspace
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && this.value === '') {
                    if (index > 0) {
                        otpInputs[index - 1].focus();
                    }
                }
            });
            
            // Handle paste
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const pasteData = e.clipboardData.getData('text');
                const numbers = pasteData.replace(/\D/g, '');
                
                if (numbers.length > 0) {
                    otpInputs.forEach((inp, i) => {
                        if (numbers[i]) {
                            inp.value = numbers[i];
                            if (i < otpInputs.length - 1) {
                                otpInputs[i + 1].focus();
                            }
                        }
                    });
                }
            });
        });
        
        // Focus first input on load
        otpInputs[0].focus();
        
        // Form submission
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            let otp = '';
            otpInputs.forEach(input => otp += input.value);
            
            if (otp.length !== 6) {
                messageDiv.innerHTML = '<div class="error-msg">Please enter all 6 digits!</div>';
                return;
            }
            
            verifyBtn.innerText = "Verifying...";
            verifyBtn.disabled = true;
            
            const formData = {
                otp: otp
            };
            
            fetch('auth.php?action=verify_otp', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    messageDiv.innerHTML = '<div class="success-msg">✅ ' + data.message + '</div>';
                    verifyBtn.innerText = "Success!";
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 1500);
                } else {
                    messageDiv.innerHTML = '<div class="error-msg">❌ ' + data.message + '</div>';
                    verifyBtn.innerText = "Verify OTP";
                    verifyBtn.disabled = false;
                    // Clear inputs
                    otpInputs.forEach(input => {
                        input.value = '';
                        input.style.borderColor = '#e74c3c';
                    });
                    setTimeout(() => {
                        otpInputs.forEach(input => input.style.borderColor = '#e0e0e0');
                    }, 2000);
                    otpInputs[0].focus();
                }
            })
            .catch(error => {
                messageDiv.innerHTML = '<div class="error-msg">Something went wrong! Please try again.</div>';
                verifyBtn.innerText = "Verify OTP";
                verifyBtn.disabled = false;
            });
        });
        
        // Resend OTP
        document.getElementById('resendBtn').addEventListener('click', function(e) {
            e.preventDefault();
            
            if (this.classList.contains('disabled')) return;
            
            const resendBtn = this;
            resendBtn.classList.add('disabled');
            resendBtn.innerText = "Generating...";
            
            fetch('auth.php?action=resend_otp', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({})
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'otp_sent') {
                    messageDiv.innerHTML = '<div class="success-msg">✅ ' + data.message + '</div>';
                    // Update displayed OTP
                    document.querySelector('.otp-code').innerText = data.otp;
                    
                    // Start countdown
                    let countdown = 30;
                    const countdownDiv = document.getElementById('countdown');
                    countdownDiv.innerText = `Resend available in ${countdown} seconds`;
                    
                    const timer = setInterval(() => {
                        countdown--;
                        countdownDiv.innerText = `Resend available in ${countdown} seconds`;
                        if (countdown <= 0) {
                            clearInterval(timer);
                            resendBtn.classList.remove('disabled');
                            resendBtn.innerText = "Resend OTP";
                            countdownDiv.innerText = '';
                        }
                    }, 1000);
                } else {
                    messageDiv.innerHTML = '<div class="error-msg">❌ ' + data.message + '</div>';
                    resendBtn.classList.remove('disabled');
                    resendBtn.innerText = "Resend OTP";
                }
            })
            .catch(error => {
                messageDiv.innerHTML = '<div class="error-msg">Something went wrong!</div>';
                resendBtn.classList.remove('disabled');
                resendBtn.innerText = "Resend OTP";
            });
        });
    </script>

</body>
</html>