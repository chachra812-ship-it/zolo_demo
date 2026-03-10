<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "zolo_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h1>✅ Database Connection Successful</h1>";
echo "<p>Database: $dbname</p>";

// Check tables
echo "<h2>Tables in Database:</h2>";
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    echo "<p>📁 " . $row[0] . "</p>";
}

// Check purchases table
echo "<h2>Purchases Table Structure:</h2>";
$result = $conn->query("DESCRIBE purchases");
if ($result) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ Purchases table does not exist!</p>";
}

// Check if there are any purchases
echo "<h2>Current Purchases:</h2>";
$result = $conn->query("SELECT * FROM purchases");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Service ID</th><th>Expiry Date</th><th>Status</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . $row['service_id'] . "</td>";
        echo "<td>" . $row['expiry_date'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>ℹ️ No purchases in database yet.</p>";
}

$conn->close();
?>