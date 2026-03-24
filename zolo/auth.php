<?php
// auth.php - FIXED VERSION for zolo_db
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Check database connection
if ($conn->connect_error) {
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
        
        // Generate username from email/name
        $username = strtolower(str_replace([' ', '.', '@'], '_', $email));
        
        // Generate 6-digit OTP
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Store OTP in session temporarily
        $_SESSION['pending_registration'] = [
            'full_name' => $name,  // FIXED: matches zolo_db column
            'username' => $username,
            'email' => $email,
            'password' => $hashedPassword,
            'otp' => $otp,
            'otp_time' => time()
        ];

        echo json_encode([
            'status' => 'otp_sent', 
            'message' => 'OTP generated successfully!',
            'otp' => $otp,
            'email' => $email
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data received']);
    }
}

// VERIFY OTP ACTION - REDIRECT TO SUCCESS PAGE
elseif ($action === 'verify_otp') {
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    if ($data && isset($_SESSION['pending_registration'])) {
        $enteredOTP = trim($data['otp']);
        $pending = $_SESSION['pending_registration'];
        
        if (time() - $pending['otp_time'] > 600) {
            echo json_encode(['status' => 'error', 'message' => 'OTP expired!']);
            unset($_SESSION['pending_registration']);
            exit;
        }
        
        if ($enteredOTP === $pending['otp']) {
            // Insert user
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, role, status) VALUES (?, ?, ?, ?, 'customer', 'active')");
            $stmt->bind_param("ssss", $pending['username'], $pending['email'], $pending['password'], $pending['full_name']);
            
            if ($stmt->execute()) {
                $_SESSION['user_email'] = $pending['email']; // For success page
                unset($_SESSION['pending_registration']);
                
                echo json_encode([
                    'status' => 'success', 
                    'message' => 'Registration successful!',
                    'redirect' => 'registration-success.php'
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Registration failed: ' . $conn->error]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid OTP']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Session expired']);
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
            'message' => '✅ New OTP generated!',
            'otp' => $newOTP
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No pending registration!']);
    }
}

// LOGIN ACTION - FIXED FOR zolo_db
elseif ($action === 'login') {
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    if ($data) {
        $email = trim($data['email']);
        $password = $data['password'];

        // DEBUG: Log email being searched
        error_log("Login attempt for email: " . $email);

        // FIXED QUERY - Matches zolo_db EXACTLY
        $stmt = $conn->prepare("SELECT id, username, email, password, full_name, role, status FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        error_log("Login query result rows: " . $result->num_rows);

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            error_log("User found: " . print_r($user, true));

            // Check account status
            if ($user['status'] !== 'active') {
                echo json_encode(['status' => 'error', 'message' => 'Account is inactive! Contact support.']);
                exit;
            }

            // Verify password
            if (password_verify($password, $user['password'])) {
                // SUCCESS LOGIN
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['is_verified'] = 1;
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();

                echo json_encode([
                    'status' => 'success', 
                    'message' => 'Login successful! Welcome back ' . $user['full_name'] . '!',
                    'user' => [
                        'id' => $user['id'],
                        'name' => $user['full_name'],
                        'email' => $user['email'],
                        'role' => $user['role']
                    ]
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => '❌ Wrong password!']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => '❌ Email not found in database!']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid login data!']);
    }
}
// LOGOUT ACTION
elseif ($action === 'logout') {
    session_destroy();
    echo json_encode(['status' => 'success', 'message' => 'Logged out!']);
}

$conn->close();
?>