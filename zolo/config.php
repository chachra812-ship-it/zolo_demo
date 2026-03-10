<?php
// config.php - Database Connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "zolo_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");
?>