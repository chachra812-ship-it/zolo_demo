<?php
// cron_expiration.php - Run this every hour via cron
session_start();
require_once 'config.php';

// Check and expire services
$stmt = $conn->prepare("SELECT * FROM user_services WHERE status = 'active' AND expiration_date < NOW()");
$stmt->execute();
$expiredServices = $stmt->get_result();

while ($service = $expiredServices->fetch_assoc()) {
    // Log the expiration
    $logStmt = $conn->prepare("INSERT INTO expiration_logs (user_id, service_id, service_title, old_status, new_status) VALUES (?, ?, ?, 'active', 'expired')");
    $logStmt->bind_param("isss", $service['user_id'], $service['service_id'], $service['service_title']);
    $logStmt->execute();

    // Update status
    $updateStmt = $conn->prepare("UPDATE user_services SET status = 'expired' WHERE id = ?");
    $updateStmt->bind_param("i", $service['id']);
    $updateStmt->execute();
}

$conn->close();
?>