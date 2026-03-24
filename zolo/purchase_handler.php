<?php
session_start();
// STOP ALL OUTPUT BEFORE THIS
ob_clean();
header('Content-Type: application/json');

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}



$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

switch($input['action'] ?? '') {
    case 'buy':
        $_SESSION['purchases'][] = [
            'id' => rand(1000,9999),
            'service_id' => $input['service_id'],
            'service_title' => $input['title'],
            'service_price' => $input['price'],
            'created_at' => date('Y-m-d H:i:s'),
            'expiration_date' => date('Y-m-d H:i:s', strtotime('+60 days')),
            'current_status' => 'active'
        ];
        echo json_encode(['success' => true, 'message' => 'Purchased!']);
        break;
    case 'avail':
        echo json_encode(['success' => true]);
        break;
    case 'extend':
        echo json_encode(['success' => true]);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>