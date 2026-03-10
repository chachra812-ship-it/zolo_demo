<?php
$host = 'localhost';
$user = 'root';  // Replace with your MySQL username
$pass = '';  // Replace with your MySQL password
$db = 'homepage_db';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>