<?php
session_start();

require_once 'config.php';

// Get the Google token
$id_token = $_POST['credential'] ?? $_GET['credential'] ?? '';

if (empty($id_token)) {
    die("No credential received");
}

// Verify the Google token (you need to verify it properly in production)
// For now, we'll decode the JWT to get user info
// NOTE: In production, use proper Google API verification

$client_id = "1025800005830-0o4rd60o6tmmnnh3kvlbb8gs0m97f7qe.apps.googleusercontent.com";

// Decode the JWT (this is for demonstration - use proper verification in production)
$parts = explode('.', $id_token);
if (count($parts) !== 3) {
    die("Invalid token format");
}

$payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

if (!$payload) {
    die("Invalid token payload");
}

// Get user info from Google
$google_email = $payload['email'] ?? '';
$google_name = $payload['name'] ?? '';
$google_picture = $payload['picture'] ?? '';

if (empty($google_email)) {
    die("Could not get email from Google");
}

// Check if user already exists
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $google_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // User exists - update info and mark as verified
    $user = $result->fetch_assoc();
    
    // ALWAYS mark as verified for Google users
    $update = $conn->prepare("UPDATE users SET is_verified = 1, verified_at = NOW() WHERE id = ?");
    $update->bind_param("i", $user['id']);
    $update->execute();
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $google_name;
    $_SESSION['user_email'] = $google_email;
    $_SESSION['is_verified'] = 1;
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    $_SESSION['login_type'] = 'google';
    
} else {
    // Create new user
    $google_password = password_hash('google_' . time(), PASSWORD_DEFAULT); // Random password for Google users
    
    $insert = $conn->prepare("INSERT INTO users (name, email, password, is_verified, verified_at) VALUES (?, ?, ?, 1, NOW())");
    $insert->bind_param("sss", $google_name, $google_email, $google_password);
    
    if ($insert->execute()) {
        $user_id = $insert->insert_id;
        
        // Set session
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $google_name;
        $_SESSION['user_email'] = $google_email;
        $_SESSION['is_verified'] = 1;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['login_type'] = 'google';
    } else {
        die("Error creating user");
    }
}

$conn->close();

// Redirect to dashboard
header("Location: dashboard.php");
exit;
?>