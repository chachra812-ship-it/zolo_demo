<?php
// submit.php

// Set headers for JSON
header('Content-Type: application/json');

// Database connection variables
$servername = "localhost";
$username = "root";
$password = "";  // Default XAMPP password is empty
$dbname = "restaurant_db";

// Get the raw POST data
$json_data = file_get_contents('php://input');

// Decode JSON data
$data = json_decode($json_data, true);

// Check if data is received
if ($data) {
    try {
        // Create connection
        $conn = new mysqli($servername, $username, $password, $dbname);

        // Check connection
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        // Prepare and bind (prevents SQL injection)
        $stmt = $conn->prepare("INSERT INTO contacts (name, email, phone) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $phone);

        // Set parameters
        $name = htmlspecialchars($data['name']);
        $email = htmlspecialchars($data['email']);
        $phone = htmlspecialchars($data['phone']);

        // Execute
        if ($stmt->execute()) {
            // Return success JSON response
            echo json_encode([
                'status' => 'success',
                'message' => "Thank you, $name! We have received your request."
            ]);
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        // Close connections
        $stmt->close();
        $conn->close();

    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid data received.'
    ]);
}
?>