<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
// ✅ FIXED: user_purchases table + correct columns
$stmt = $conn->prepare("SELECT * FROM user_purchases WHERE user_id = ? ORDER BY expiration_date ASC");
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
        
        <?php if (empty($services)): ?>
            <div style="text-align:center; padding:40px; color:#666;">
                <h3>No Services Found</h3>
                <p>You don't have any active services to extend.</p>
                <a href="dashboard.php" class="extend-btn">← Back to Dashboard</a>
            </div>
        <?php else: ?>
            <?php foreach ($services as $service): 
                $isExpired = strtotime($service['expiration_date']) < time();
                $statusClass = $isExpired ? 'expired' : ($service['status'] === 'used' ? 'used' : 'active');
            ?>
                <div class="extend-card <?= $statusClass ?>">
                    <h3><?= htmlspecialchars($service['service_title']) ?></h3>
                    <p><strong>ID:</strong> #<?= $service['id'] ?></p>
                    <p><strong>Price:</strong> $<?= number_format($service['service_price'], 2) ?></p>
                    <p><strong>Status:</strong> 
                        <span style="color: <?= $isExpired ? 'red' : ($statusClass === 'used' ? 'orange' : 'green') ?>">
                            <?= ucfirst($statusClass) ?>
                        </span>
                    </p>
                    <p><strong>Purchased:</strong> <?= date('M d, Y', strtotime($service['created_at'])) ?></p>
                    <p><strong>Expires:</strong> 
                        <span style="color: <?= $isExpired ? 'red' : 'green' ?>">
                            <?= date('M d, Y H:i', strtotime($service['expiration_date'])) ?>
                        </span>
                    </p>
                    <?php if (!$isExpired): ?>
                    <p><strong>Days Left:</strong> 
                        <?= floor((strtotime($service['expiration_date']) - time()) / 86400) ?> days
                    </p>
                    <?php endif; ?>
                    
                    <?php if ($statusClass !== 'used'): ?>
                        <button class="extend-btn" onclick="extendService(<?= $service['id'] ?>, '<?= addslashes($service['service_title']) ?>')">
    ⏰ Extend +30 Days (<?= $service['service_title'] ?>)
</button>
                    <?php else: ?>
                        <button class="extend-btn" disabled style="background: #6c757d;">✔️ Already Used</button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div style="text-align:center; margin-top:30px;">
            <a href="dashboard.php" class="extend-btn" style="background:#667eea;">← Dashboard</a>
        </div>
    </div>

    <script>
function extendService(purchaseId) {
    if (!confirm('⏰ Extend service +30 days?\n\nDatabase will be updated with new expiry date.')) {
        return;
    }
    
    // Show loading state
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '⏳ Extending...';
    
    fetch('extend_service_handler.php', {  // ✅ Your existing file
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({purchase_id: purchaseId})
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success' || data.success) {  // Support both response formats
            alert('✅ ' + (data.message || 'Service extended successfully!'));
            location.reload();  // Refresh to show new expiry date
        } else {
            alert('❌ ' + (data.message || 'Failed to extend service'));
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('❌ Network error - check browser console');
    })
    .finally(() => {
        // Reset button
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}
</script>
</body>
</html>