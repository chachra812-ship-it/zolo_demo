<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'buy_service':
        handleBuyService();
        break;
    case 'avail_service':
        handleAvailService();
        break;
    case 'extend_service':
        handleExtendService();
        break;
    case 'get_services':
        getServices();
        break;
    case 'check_expiration':
        checkExpiration();
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}

function handleBuyService() {
    global $conn;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Please login first']);
        return;
    }
    
    $user_id = $_SESSION['user_id'];
    $service_id = $input['service_id'];
    $service_title = $input['service_title'];
    $service_price = $input['service_price'];
    
    try {
        // Insert into user_purchases
        $stmt = $conn->prepare("
            INSERT INTO user_purchases (user_id, service_id, service_title, service_price, expiration_date) 
            VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 60 DAY))
        ");
        $stmt->bind_param("isis", $user_id, $service_id, $service_title, $service_price);
        $stmt->execute();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Service purchased successfully!',
            'purchase_id' => $conn->insert_id,
            'expiration_date' => date('Y-m-d H:i:s', strtotime('+60 days'))
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Purchase failed: ' . $e->getMessage()]);
    }
}

function handleAvailService() {
    global $conn;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $purchase_id = $input['purchase_id'];
    
    $stmt = $conn->prepare("UPDATE user_purchases SET status = 'used' WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $purchase_id, $_SESSION['user_id']);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Service marked as used!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Service not found or already used']);
    }
}

function handleExtendService() {
    global $conn;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $purchase_id = $input['purchase_id'];
    $extend_days = $input['extend_days'] ?? 60;
    
    $stmt = $conn->prepare("
        UPDATE user_purchases 
        SET expiration_date = DATE_ADD(expiration_date, INTERVAL ? DAY), status = 'active' 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("iii", $extend_days, $purchase_id, $_SESSION['user_id']);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $newExpiry = date('M d, Y', strtotime("+{$extend_days} days"));
        echo json_encode([
            'status' => 'success', 
            'message' => "✅ Extended by {$extend_days} days!",
            'new_expiry' => $newExpiry
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Extension failed']);
    }
}

function getServices() {
    global $conn;
    
    $result = $conn->query("SELECT * FROM services ORDER BY id ASC");
    $services = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'status' => 'success', 
        'services' => $services
    ]);
}

function checkExpiration() {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) return;
    
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("
        UPDATE user_purchases 
        SET status = 'expired' 
        WHERE user_id = ? AND expiration_date < NOW() AND status = 'active'
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    echo json_encode(['status' => 'checked']);
}

$conn->close();
?>