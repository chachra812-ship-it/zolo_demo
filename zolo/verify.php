<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "zolo_db";

$conn = new mysqli($servername, $username, $password, $dbname);

$message = "";
$messageType = "";

if (isset($_GET['code']) && isset($_GET['id'])) {
    $code = $_GET['code'];
    $id = $_GET['id'];
    
    // Check if code matches
    // Check if code matches
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND verification_code = ?");
$stmt->bind_param("is", $id, $code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    if ($user['is_verified'] == 1) {
        $message = "Your email is already verified! You can login now.";
        $messageType = "info";
    } else {
        // Update verification status
        $update = $conn->prepare("UPDATE users SET is_verified = 1, verification_code = NULL, verified_at = NOW() WHERE id = ?");
        $update->bind_param("i", $id);
        
        if ($update->execute()) {
            $message = "✅ Email verified successfully! You can now login.";
            $messageType = "success";
        } else {
            $message = "❌ Verification failed. Please try again.";
            $messageType = "error";
        }
    }
} else 
    $message = "❌ Invalid verification code!";
    $messageType = "error";
}