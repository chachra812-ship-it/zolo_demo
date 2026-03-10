<?php
// purchase_handler.php

header('Content-Type: application/json');
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "zolo_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Please login to purchase']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get JSON data
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    exit;
}

$service_id = $data['service_id'] ?? 0;
$duration = $data['duration'] ?? 30;

// Validate service exists
$stmt = $conn->prepare("SELECT id, price FROM services WHERE id = ?");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Service not found']);
    exit;
}

$service = $result->fetch_assoc();

// Check if already purchased
$stmt = $conn->prepare("SELECT id FROM purchases WHERE user_id = ? AND service_id = ? AND status = 'active'");
$stmt->bind_param("ii", $user_id, $service_id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();

if ($existing) {
    echo json_encode(['status' => 'error', 'message' => 'You already have an active subscription for this service']);
    exit;
}

// Calculate expiry date
$expiry_date = date('Y-m-d H:i:s', strtotime("+$duration days"));

// Insert purchase record
$stmt = $conn->prepare("INSERT INTO purchases (user_id, service_id, expiry_date, status) VALUES (?, ?, ?, 'active')");
$stmt->bind_param("iis", $user_id, $service_id, $expiry_date);

if ($stmt->execute()) {
    $purchase_id = $stmt->insert_id;
    echo json_encode([
        'status' => 'success',
        'message' => 'Purchase successful! Service activated.',
        'purchase_id' => $purchase_id,
        'expiry_date' => $expiry_date,
        'duration' => $duration
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Purchase failed. Please try again.']);
}

$stmt->close();
$conn->close();
?>