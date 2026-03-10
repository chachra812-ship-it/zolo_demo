<?php
// update_content.php
header('Content-Type: application/json'); // Tell the client we're sending JSON

// Assume you have a database connection (replace with your setup)
$servername = "localhost";
$username = "your_db_user";
$password = "your_db_password";
$dbname = "your_database";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["error" => "Database connection failed"]));
}

// Get JSON input from the request body
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(["error" => "Invalid JSON"]);
    exit;
}

// Example: Update a "homepage_content" table with fields like 'title' and 'body'
// Sanitize inputs to prevent SQL injection
$title = mysqli_real_escape_string($conn, $input['title']);
$body = mysqli_real_escape_string($conn, $input['body']);

$sql = "UPDATE homepage_content SET title='$title', body='$body' WHERE id=1"; // Assuming a single row for simplicity

if ($conn->query($sql) === TRUE) {
    echo json_encode(["success" => "Content updated successfully"]);
} else {
    echo json_encode(["error" => "Update failed: " . $conn->error]);
}

$conn->close();
?>