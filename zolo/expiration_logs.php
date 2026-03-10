<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM expiration_logs WHERE user_id = ? ORDER BY expired_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Expiration Logs | Zolo</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-container">
        <h2>📋 Expiration History</h2>
        <table class="table-container">
            <tr><th>Service</th><th>Old Status</th><th>New Status</th><th>Expired At</th></tr>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td><?php echo $log['service_title']; ?></td>
                <td><?php echo $log['old_status']; ?></td>
                <td><?php echo $log['new_status']; ?></td>
                <td><?php echo $log['expired_at']; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>