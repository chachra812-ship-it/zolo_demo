<?php
// api.php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Set timezone for accurate date/time
date_default_timezone_set('UTC'); // Change to your timezone if needed

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Please login first"]);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_services':
        $stmt = $conn->prepare("SELECT * FROM services WHERE status = 'active'");
        $stmt->execute();
        $result = $stmt->get_result();
        $services = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(["status" => "success", "services" => $services]);
        break;

    case 'buy_service':
        $data = json_decode(file_get_contents("php://input"), true);
        $serviceId = $data['service_id'];
        $serviceTitle = $conn->real_escape_string($data['service_title']);
        $servicePrice = $data['service_price'];

        // Get service duration (default 60 days = 2 months)
        $stmt = $conn->prepare("SELECT duration_days FROM services WHERE id = ?");
        $stmt->bind_param("i", $serviceId);
        $stmt->execute();
        $serviceResult = $stmt->get_result();
        $serviceData = $serviceResult->fetch_assoc();
        $duration = $serviceData['duration_days'] ?? 60;

        // Calculate expiration date (2 months from purchase)
        $purchasedAt = date('Y-m-d H:i:s');
        $expirationDate = date('Y-m-d H:i:s', strtotime("+{$duration} days"));

        $stmt = $conn->prepare("INSERT INTO user_services (user_id, service_id, service_title, service_price, status, purchased_at, expiration_date) VALUES (?, ?, ?, ?, 'active', ?, ?)");
        $stmt->bind_param("isssss", $userId, $serviceId, $serviceTitle, $servicePrice, $purchasedAt, $expirationDate);
        
        if ($stmt->execute()) {
            echo json_encode([
                "status" => "success", 
                "message" => "Service purchased successfully!",
                "expiration_date" => $expirationDate,
                "duration_days" => $duration
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to purchase service"]);
        }
        break;

    case 'avail_service':
        $data = json_decode(file_get_contents("php://input"), true);
        $purchaseId = $data['purchase_id'];

        $stmt = $conn->prepare("UPDATE user_services SET status = 'used' WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $purchaseId, $userId);
        
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Service availed successfully!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to avail service"]);
        }
        break;

    case 'check_expiration':
        // Check and expire services automatically
        $stmt = $conn->prepare("SELECT * FROM user_services WHERE status = 'active' AND expiration_date < NOW() AND user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $expiredServices = $stmt->get_result();

        while ($service = $expiredServices->fetch_assoc()) {
            // Log the expiration
            $logStmt = $conn->prepare("INSERT INTO expiration_logs (user_id, service_id, service_title, old_status, new_status) VALUES (?, ?, ?, 'active', 'expired')");
            $logStmt->bind_param("isss", $userId, $service['service_id'], $service['service_title']);
            $logStmt->execute();

            // Update status
            $updateStmt = $conn->prepare("UPDATE user_services SET status = 'expired' WHERE id = ?");
            $updateStmt->bind_param("i", $service['id']);
            $updateStmt->execute();
        }

        echo json_encode(["status" => "success", "message" => "Expiration check completed"]);
        break;

    case 'extend_service':
        $data = json_decode(file_get_contents("php://input"), true);
        $purchaseId = $data['purchase_id'];
        $extendDays = $data['extend_days'] ?? 60; // Default 60 days (2 months)

        $stmt = $conn->prepare("SELECT * FROM user_services WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $purchaseId, $userId);
        $stmt->execute();
        $service = $stmt->get_result()->fetch_assoc();

        if ($service) {
            $newExpiration = date('Y-m-d H:i:s', strtotime("+{$extendDays} days", strtotime($service['expiration_date'])));
            $updateStmt = $conn->prepare("UPDATE user_services SET expiration_date = ? WHERE id = ?");
            $updateStmt->bind_param("si", $newExpiration, $purchaseId);
            
            if ($updateStmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Service extended successfully!"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to extend service"]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Service not found"]);
        }
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Invalid action"]);
        break;
}

$conn->close();
?>