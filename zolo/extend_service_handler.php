<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

// ✅ ADD: Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];  // ✅ ADD: Get user ID

$input = json_decode(file_get_contents('php://input'), true);
$purchase_id = intval($input['purchase_id'] ?? 0);

if ($purchase_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
    exit;
}

$new_expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
// ✅ ADD: user_id check for security
$stmt = $conn->prepare("UPDATE user_purchases SET expiration_date = ?, status = 'active' WHERE id = ? AND user_id = ?");
$stmt->bind_param("sii", $new_expiry, $purchase_id, $userId);  // ✅ Changed "si" to "sii"

if ($stmt->execute() && $stmt->affected_rows > 0) {  // ✅ Added affected_rows check
    echo json_encode([
        'status' => 'success', 
        'message' => '✅ Extended 30 days! New expiry: ' . date('M d, Y', strtotime($new_expiry))
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => '❌ Service not found or already extended']);
}

$conn->close();
?>