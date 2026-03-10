<?php
// ajax_handler.php - Handles AJAX requests from admin.php
include 'config.php';
header('Content-Type: application/json'); // Respond with JSON

// Get JSON input (for non-file forms) or handle FormData
$input = json_decode(file_get_contents('php://input'), true);

// Handle different actions based on a 'action' field in the request
if (isset($_POST['action']) || isset($input['action'])) {
    $action = $_POST['action'] ?? $input['action'];
    
    if ($action == 'update_homepage') {
        // Update logo and text (handle file upload via FormData)
        $welcome_text = $_POST['welcome_text'] ?? $input['welcome_text'];
        $logo_path = 'default_logo.png'; // Default
        
        if (!empty($_FILES['logo']['name'])) {
            $logo_path = basename($_FILES['logo']['name']);
            move_uploaded_file($_FILES['logo']['tmp_name'], 'uploads/' . $logo_path);
        }
        
        $conn->query("UPDATE homepage SET logo_path='$logo_path', welcome_text='$welcome_text' WHERE id=1");
        echo json_encode(["success" => "Homepage updated successfully"]);
        
    } elseif ($action == 'upload_photo') {
        // Upload photo
        if (!empty($_FILES['photo']['name'])) {
            $photo_path = basename($_FILES['photo']['name']);
            move_uploaded_file($_FILES['photo']['tmp_name'], 'uploads/' . $photo_path);
            $conn->query("INSERT INTO photos (photo_path) VALUES ('$photo_path')");
            echo json_encode(["success" => "Photo uploaded successfully"]);
        } else {
            echo json_encode(["error" => "No photo selected"]);
        }
        
    } elseif ($action == 'delete_photo') {
        // Delete photo
        $id = $_POST['photo_id'] ?? $input['photo_id'];
        $result = $conn->query("SELECT photo_path FROM photos WHERE id=$id");
        $photo = $result->fetch_assoc();
        unlink('uploads/' . $photo['photo_path']);
        $conn->query("DELETE FROM photos WHERE id=$id");
        echo json_encode(["success" => "Photo deleted successfully"]);
        
    } elseif ($action == 'add_service') {
        // Add service
        $name = $_POST['service_name'] ?? $input['service_name'];
        $desc = $_POST['service_description'] ?? $input['service_description'];
        $conn->query("INSERT INTO services (service_name, description) VALUES ('$name', '$desc')");
        echo json_encode(["success" => "Service added successfully"]);
        
    } elseif ($action == 'delete_service') {
        // Delete service
        $id = $_POST['service_id'] ?? $input['service_id'];
        $conn->query("DELETE FROM services WHERE id=$id");
        echo json_encode(["success" => "Service deleted successfully"]);
        
    } else {
        echo json_encode(["error" => "Invalid action"]);
    }
} else {
    echo json_encode(["error" => "No action specified"]);
}

$conn->close();
?>