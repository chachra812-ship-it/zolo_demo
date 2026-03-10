<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "zolo_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$userId = $_SESSION['user_id'];

// Get all purchases with service details
$query = "
    SELECT p.id, p.user_id, p.service_id, p.purchase_date, p.expiry_date, p.status,
           s.title, s.slug, s.icon, s.price
    FROM purchases p 
    JOIN services s ON p.service_id = s.id 
    WHERE p.user_id = ? 
    ORDER BY p.purchase_date DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$purchases = $stmt->get_result();

// Check for expired services and update status
$now = date('Y-m-d H:i:s');
while ($purchase = $purchases->fetch_assoc()) {
    if ($purchase['expiry_date'] < $now && $purchase['status'] == 'active') {
        $update = $conn->prepare("UPDATE purchases SET status = 'expired' WHERE id = ?");
        $update->bind_param("i", $purchase['id']);
        $update->execute();
    }
}

// Refresh purchases after checking expiry
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$purchases = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Purchases | Zolo</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .debug-info {
            background: #fff3cd;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #ffeaa7;
        }
        .debug-info h3 {
            color: #856404;
            margin-bottom: 10px;
        }
        .debug-info p {
            color: #856404;
            margin: 5px 0;
        }
    </style>
</head>
<body>

    <!-- Top Bar -->
    <div class="top-bar">
        <div class="top-bar-container">
            <div class="top-bar-left">
                📧 info@zolo.com | 📞 +1 234 567 890
            </div>
            <div class="top-bar-right">
                <span>👤 <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="my-purchases.php" class="login-btn-small">My Purchases</a>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>

    <!-- Header -->
    <header>
        <div class="nav-container">
            <a href="index.php" class="logo">Zolo<span>.</span></a>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="index.php#services">Services</a></li>
                    <li><a href="index.php#blog">Blog</a></li>
                    <li><a href="index.php#contact">Contact</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container">
        <!-- Debug Info -->
        <div class="debug-info">
            <h3>🔍 Debug Information</h3>
            <p><strong>User ID:</strong> <?php echo $userId; ?></p>
            <p><strong>Total Purchases:</strong> <?php echo $purchases->num_rows; ?></p>
            <p><strong>Active Purchases:</strong> <?php 
                $active = 0;
                $purchases->data_seek(0);
                while ($p = $purchases->fetch_assoc()) {
                    if ($p['status'] == 'active') $active++;
                }
                echo $active; 
            ?></p>
            <p><a href="check_purchases.php" style="color: #667eea;">Run Full Debug Check</a></p>
        </div>

        <div class="purchase-section">
            <h1>🛒 My Purchases</h1>
            <p>View and manage your purchased services</p>
        </div>

        <?php if ($purchases->num_rows > 0): ?>
            <div class="purchases-grid">
                <?php 
                $purchases->data_seek(0);
                while ($purchase = $purchases->fetch_assoc()): 
                    $now = date('Y-m-d H:i:s');
                    $isExpired = $purchase['expiry_date'] < $now;
                    $statusClass = $isExpired ? 'expired' : '';
                    $statusBadge = $isExpired ? 'status-expired' : 'status-active';
                    $statusText = $isExpired ? 'Expired' : 'Active';
                    
                    // Calculate days left
                    $daysLeft = floor((strtotime($purchase['expiry_date']) - time()) / 86400);
                    $daysClass = $isExpired ? 'expired' : '';
                ?>
                    <div class="purchase-card <?php echo $statusClass; ?>">
                        <div class="service-icon"><?php echo $purchase['icon']; ?></div>
                        <h3><?php echo htmlspecialchars($purchase['title']); ?></h3>
                        <span class="status-badge <?php echo $statusBadge; ?>">
                            <?php echo $statusText; ?>
                        </span>
                        <div class="expiry-info">
                            <p>Purchased: <span><?php echo date('M d, Y', strtotime($purchase['purchase_date'])); ?></span></p>
                            <p>Expires: <span><?php echo date('M d, Y', strtotime($purchase['expiry_date'])); ?></span></p>
                            <?php if (!$isExpired): ?>
                                <p class="days-left <?php echo $daysClass; ?>">
                                    <?php echo $daysLeft; ?> days left
                                </p>
                            <?php else: ?>
                                <p class="days-left expired">
                                    Service has expired
                                </p>
                            <?php endif; ?>
                        </div>
                        <a href="service.php?slug=<?php echo $purchase['slug']; ?>" class="purchase-btn" style="width: 100%; display: block; text-align: center; text-decoration: none;">
                            <?php echo $isExpired ? 'Renew Now' : 'View Service'; ?>
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="purchase-section" style="text-align: center; padding: 60px;">
                <div style="font-size: 4rem; margin-bottom: 20px;">🛒</div>
                <h2>No Purchases Yet</h2>
                <p>You haven't purchased any services yet.</p>
                <a href="index.php#services" class="purchase-btn" style="margin-top: 20px; display: inline-block;">Browse Services</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 Zolo Digital Solutions. All rights reserved.</p>
    </footer>

</body>
</html>