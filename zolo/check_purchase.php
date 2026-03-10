<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "zolo_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h1>🔍 Purchase Debug Page</h1>";
echo "<hr>";

// Check if user is logged in
echo "<h2>Session Check</h2>";
echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "</p>";
echo "<p>User Name: " . ($_SESSION['user_name'] ?? 'NOT SET') . "</p>";
echo "<p>User Email: " . ($_SESSION['user_email'] ?? 'NOT SET') . "</p>";
echo "<hr>";

// Check purchases table
echo "<h2>Purchases Table</h2>";
$stmt = $conn->query("SELECT * FROM purchases");
if ($stmt->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Service ID</th><th>Purchase Date</th><th>Expiry Date</th><th>Status</th></tr>";
    while ($row = $stmt->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . $row['service_id'] . "</td>";
        echo "<td>" . $row['purchase_date'] . "</td>";
        echo "<td>" . $row['expiry_date'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No purchases found in database!</p>";
}
echo "<hr>";

// Check services table
echo "<h2>Services Table</h2>";
$stmt = $conn->query("SELECT * FROM services");
if ($stmt->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Title</th><th>Slug</th><th>Price</th><th>Icon</th></tr>";
    while ($row = $stmt->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['title'] . "</td>";
        echo "<td>" . $row['slug'] . "</td>";
        echo "<td>" . $row['price'] . "</td>";
        echo "<td>" . $row['icon'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No services found in database!</p>";
}
echo "<hr>";

// Check users table
echo "<h2>Users Table</h2>";
$stmt = $conn->query("SELECT * FROM users");
if ($stmt->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Is Verified</th></tr>";
    while ($row = $stmt->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . $row['is_verified'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No users found in database!</p>";
}
echo "<hr>";

// Check current user's purchases
if (isset($_SESSION['user_id'])) {
    echo "<h2>Current User's Purchases</h2>";
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM purchases WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Service ID</th><th>Expiry Date</th><th>Status</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['service_id'] . "</td>";
            echo "<td>" . $row['expiry_date'] . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No purchases found for this user!</p>";
    }
}

$conn->close();
?>