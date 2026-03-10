<?php
// auth.php - Authentication Handler with OTP Display on Screen
session_start();

require_once 'config.php';

header('Content-Type: application/json');

// Check database connection
if (!$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

$action = $_GET['action'] ?? '';

// REGISTER ACTION WITH OTP (DISPLAY ON SCREEN)
if ($action === 'register') {
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    if ($data) {
        $name = trim($data['name']);
        $email = filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL);
        $password = $data['password'];

        if (!$email) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid email format!']);
            exit;
        }

        if (strlen($password) < 6) {
            echo json_encode(['status' => 'error', 'message' => 'Password must be at least 6 characters!']);
            exit;
        }

        // Check if email already exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $checkResult = $check->get_result();

        if ($checkResult->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Email already registered!']);
            exit;
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Generate 6-digit OTP
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Store OTP in session temporarily
        $_SESSION['pending_registration'] = [
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword,
            'otp' => $otp,
            'otp_time' => time()
        ];

        // Return success with OTP (display on screen)
        echo json_encode([
            'status' => 'otp_sent', 
            'message' => 'OTP generated successfully!',
            'otp' => $otp, // OTP displayed on screen
            'email' => $email
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data received']);
    }
}

// VERIFY OTP ACTION
elseif ($action === 'verify_otp') {
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    if ($data && isset($_SESSION['pending_registration'])) {
        $enteredOTP = trim($data['otp']);
        $pending = $_SESSION['pending_registration'];
        
        // Check if OTP expired (10 minutes)
        if (time() - $pending['otp_time'] > 600) {
            echo json_encode(['status' => 'error', 'message' => 'OTP expired! Please register again.']);
            unset($_SESSION['pending_registration']);
            exit;
        }
        
        // Verify OTP
        if ($enteredOTP === $pending['otp']) {
            // Insert user into database
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, is_verified, verified_at) VALUES (?, ?, ?, 1, NOW())");
            $stmt->bind_param("sss", $pending['name'], $pending['email'], $pending['password']);
            
            if ($stmt->execute()) {
                // Clear pending registration
                unset($_SESSION['pending_registration']);
                
                echo json_encode([
                    'status' => 'success', 
                    'message' => 'Email verified successfully! You can now login.'
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Registration failed! Please try again.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid OTP! Please try again.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Session expired! Please register again.']);
    }
}

// RESEND OTP ACTION
elseif ($action === 'resend_otp') {
    if (isset($_SESSION['pending_registration'])) {
        $pending = $_SESSION['pending_registration'];
        
        // Generate new OTP
        $newOTP = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $_SESSION['pending_registration']['otp'] = $newOTP;
        $_SESSION['pending_registration']['otp_time'] = time();
        
        echo json_encode([
            'status' => 'otp_sent', 
            'message' => 'New OTP generated!',
            'otp' => $newOTP // New OTP displayed on screen
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Session expired! Please register again.']);
    }
}

// LOGIN ACTION
// Replace the LOGIN ACTION section in auth.php with this:

// LOGIN ACTION
elseif ($action === 'login') {
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    if ($data) {
        $email = filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL);
        $password = $data['password'];

        if (!$email) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid email format!']);
            exit;
        }

        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                
                // ALWAYS allow login - auto-verify if not verified
                $isVerified = $user['is_verified'];
                
                // Auto-verify unverified users on login for testing
                if ($isVerified == 0) {
                    $update = $conn->prepare("UPDATE users SET is_verified = 1, verified_at = NOW() WHERE id = ?");
                    $update->bind_param("i", $user['id']);
                    $update->execute();
                    $isVerified = 1;
                }
                
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['is_verified'] = $isVerified;
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                
                // Close session properly
                session_write_close();
                
                echo json_encode(['status' => 'success', 'message' => 'Login successful! Redirecting...']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid password!']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Email not registered!']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data received']);
    }
}

// LOGOUT ACTION
elseif ($action === 'logout') {
    $_SESSION = array();
    session_destroy();
    echo json_encode(['status' => 'success', 'message' => 'Logged out successfully']);
}

$conn->close();
?>