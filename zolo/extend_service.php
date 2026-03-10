<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM user_services WHERE user_id = ? ORDER BY expiration_date ASC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$services = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Extend Service | Zolo</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .extend-container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .extend-card { background: white; padding: 20px; margin: 10px 0; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .extend-btn { background: orange; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .extend-btn:hover { background: #e67e22; }
        .expired { border-left: 5px solid red; }
        .active { border-left: 5px solid green; }
    </style>
</head>
<body>
    <div class="extend-container">
        <h2>⏰ Extend Your Services</h2>
        
        <?php foreach ($services as $service): ?>
            <div class="extend-card <?php echo $service['status']; ?>">
                <h3><?php echo $service['service_title']; ?></h3>
                <p><strong>Status:</strong> <?php echo $service['status']; ?></p>
                <p><strong>Purchased:</strong> <?php echo date('M d, Y', strtotime($service['purchased_at'])); ?></p>
                <p><strong>Expires:</strong> 
                    <span style="color: <?php 
                        echo strtotime($service['expiration_date']) < time() ? 'red' : 'green'; 
                    ?>">
                        <?php echo date('M d, Y H:i', strtotime($service['expiration_date'])); ?>
                    </span>
                </p>
                <p><strong>Days Remaining:</strong> 
                    <?php 
                    $daysLeft = floor((strtotime($service['expiration_date']) - time()) / 86400);
                    echo $daysLeft . " days";
                    ?>
                </p>
                <button class="extend-btn" onclick="extendService(<?php echo $service['id']; ?>)">
                    Extend Service
                </button>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        function extendService(purchaseId) {
            const days = prompt("How many days to extend? (Default: 30)", "30");
            if (!days || days <= 0) return;

            fetch('api.php?action=extend_service', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ purchase_id: purchaseId, extend_days: parseInt(days) })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ ' + data.message);
                }
            });
        }
    </script>
</body>
</html>