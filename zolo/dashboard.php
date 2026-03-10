<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Include database connection
require_once 'config.php';

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];

// Get user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();

// Get user's purchased services
$stmt = $conn->prepare("SELECT * FROM user_services WHERE user_id = ? ORDER BY purchased_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$servicesResult = $stmt->get_result();
$purchasedServices = $servicesResult->fetch_all(MYSQLI_ASSOC);

// Count active services
$activeCount = count(array_filter($purchasedServices, function($s) {
    return $s['status'] === 'active';
}));

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Zolo</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }

        .dashboard-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .welcome-section h1 {
            font-size: 2rem;
            margin-bottom: 5px;
        }

        .welcome-section p {
            opacity: 0.9;
        }

        .user-stats {
            display: flex;
            gap: 30px;
        }

        .stat-card {
            background: rgba(255,255,255,0.2);
            padding: 15px 25px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-card .number {
            font-size: 2rem;
            font-weight: bold;
        }

        .stat-card .label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px 40px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .section-header h2 {
            color: #1a1a2e;
            font-size: 1.5rem;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .service-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: 0.3s;
        }

        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        }

        .service-card .icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .service-card h3 {
            color: #1a1a2e;
            margin-bottom: 10px;
        }

        .service-card p {
            color: #666;
            margin-bottom: 15px;
            font-size: 0.95rem;
        }

        .service-card .price {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 15px;
        }

        .buy-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }

        .buy-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .table-container table {
            width: 100%;
            border-collapse: collapse;
        }

        .table-container th,
        .table-container td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .table-container th {
            background: #f8f9fa;
            color: #1a1a2e;
            font-weight: 600;
        }

        .table-container tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-used {
            background: #f8d7da;
            color: #721c24;
        }

        .status-expired {
            background: #fff3cd;
            color: #856404;
        }

        .avail-btn {
            padding: 8px 16px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: 0.3s;
        }

        .avail-btn:hover {
            background: #219a52;
        }

        .avail-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .quick-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 12px 25px;
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }

        .action-btn:hover {
            background: #667eea;
            color: white;
        }

        .action-btn.primary {
            background: #667eea;
            color: white;
        }

        .action-btn.primary:hover {
            background: #764ba2;
            border-color: #764ba2;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state .icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #1a1a2e;
            margin-bottom: 10px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 40px;
            border-radius: 20px;
            max-width: 500px;
            width: 90%;
            text-align: center;
        }

        .modal-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }

        .modal-content h2 {
            color: #1a1a2e;
            margin-bottom: 15px;
        }

        .modal-btn {
            padding: 12px 30px;
            margin: 5px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            cursor: pointer;
        }

        .modal-btn.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        @media (max-width: 768px) {
            .dashboard-header .container {
                flex-direction: column;
                text-align: center;
            }
            .user-stats {
                justify-content: center;
            }
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
                <span>👤 Welcome, <?php echo htmlspecialchars($userName); ?></span>
                <span class="verified-badge">✓ Verified</span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>

    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="welcome-section">
                <h1>👋 Welcome back, <?php echo htmlspecialchars($userName); ?>!</h1>
                <p>Manage your services and track your purchases</p>
            </div>
            <div class="user-stats">
                <div class="stat-card">
                    <div class="number"><?php echo count($purchasedServices); ?></div>
                    <div class="label">Total Services</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo $activeCount; ?></div>
                    <div class="label">Active</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo count($purchasedServices) - $activeCount; ?></div>
                    <div class="label">Used</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Container -->
    <div class="dashboard-container">

              <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="index.php" class="action-btn">🏠 Home</a>
            <a href="dashboard.php" class="action-btn primary">📊 Dashboard</a>
            <a href="services.php" class="action-btn">🛒 Browse Services</a>
            <a href="checkpurchase.php" class="action-btn">📋 Check Purchase</a>
            <a href="mypurchase.php" class="action-btn">📦 My Purchases</a>
            <a href="extend_service.php" class="action-btn">⏰ Extend Service</a>
        </div>

        <!-- Available Services to Buy -->
        <div class="section-header">
            <h2>🛒 Available Services</h2>
        </div>

        <div class="services-grid" id="availableServices">
            <p style="color: #666;">Loading services...</p>
        </div>

        <!-- Purchased Services -->
        <div class="section-header">
            <h2>📦 My Purchased Services</h2>
        </div>

        <?php if (count($purchasedServices) > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Price</th>
                            <th>Purchased Date</th>
                            <th>Expires On</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                                           <?php foreach ($purchasedServices as $service): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($service['service_title']); ?></strong></td>
                                <td>$<?php echo number_format($service['service_price'], 2); ?></td>
                                <td><?php echo date('M d, Y', strtotime($service['purchased_at'])); ?></td>
                                <td>
                                    <span style="color: <?php 
                                        echo strtotime($service['expiration_date']) < time() ? 'red' : 'green'; 
                                    ?>">
                                        <?php echo date('M d, Y H:i', strtotime($service['expiration_date'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $service['status']; ?>">
                                        <?php echo ucfirst($service['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($service['status'] === 'active'): ?>
                                        <button class="avail-btn" onclick="availService(<?php echo $service['id']; ?>, '<?php echo htmlspecialchars($service['service_title']); ?>')">
                                            Avail Service
                                        </button>
                                        <button class="avail-btn" style="background: orange;" onclick="extendService(<?php echo $service['id']; ?>)">
                                            Extend
                                        </button>
                                    <?php elseif ($service['status'] === 'expired'): ?>
                                        <button class="avail-btn" style="background: orange;" onclick="extendService(<?php echo $service['id']; ?>)">
                                            Renew Service
                                        </button>
                                    <?php else: ?>
                                        <button class="avail-btn" disabled>
                                            <?php echo $service['status'] === 'used' ? 'Used' : 'Expired'; ?>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="icon">📦</div>
                <h3>No Services Purchased Yet</h3>
                <p>Browse our services and purchase one to get started!</p>
                <a href="services.php" class="buy-btn" style="display: inline-block; margin-top: 20px; width: auto;">
                    Browse Services
                </a>
            </div>
        <?php endif; ?>

    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 Zolo Digital Solutions. All rights reserved.</p>
    </footer>

    <!-- Avail Service Modal -->
    <div class="modal" id="availModal">
        <div class="modal-content">
            <div class="modal-icon">🎉</div>
            <h2>Service Availed!</h2>
            <p id="availMessage">You have successfully availed the service.</p>
            <button class="modal-btn primary" onclick="closeModal()">Great!</button>
        </div>
    </div>

    <script>
        // Load available services
        fetch('api.php?action=get_services')
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('availableServices');
                if (data.status === 'success' && data.services.length > 0) {
                    container.innerHTML = data.services.map(service => `
                        <div class="service-card">
                            <div class="icon">${service.icon}</div>
                            <h3>${service.title}</h3>
                            <p>${service.short_description}</p>
                            <div class="price">$${parseFloat(service.price).toFixed(2)}</div>
                            <button class="buy-btn" onclick="buyService(${service.id}, '${service.title}', ${service.price})">
                                Buy Now
                            </button>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = '<p style="color: #666;">No services available.</p>';
                }
            });

                        // Buy Service
        function buyService(serviceId, serviceTitle, servicePrice) {
            if (!confirm(`Purchase "${serviceTitle}" for $${servicePrice}?`)) return;
            fetch('api.php?action=buy_service', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ service_id: serviceId, service_title: serviceTitle, service_price: servicePrice })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const expDate = new Date(data.expiration_date);
                    const formattedDate = expDate.toLocaleString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: true
                    });
                    alert('✅ ' + data.message + '\n\nService expires on: ' + formattedDate + '\n\nDuration: ' + data.duration_days + ' days (2 months)');
                    location.reload();
                } else {
                    alert('❌ ' + data.message);
                }
            });
        }

        // Avail Service
        function availService(purchaseId, serviceTitle) {
            if (!confirm(`Avail "${serviceTitle}" service now?`)) return;
            fetch('api.php?action=avail_service', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ purchase_id: purchaseId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById('availMessage').innerHTML = `🎉 You have successfully availed <strong>${serviceTitle}</strong>!`;
                    document.getElementById('availModal').classList.add('show');
                } else {
                    alert('❌ ' + data.message);
                }
            });
        }

        // Extend Service
        function extendService(purchaseId) {
            const days = prompt("How many months to extend? (Default: 2 months / 60 days)", "60");
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

        // Check expiration on page load
        fetch('api.php?action=check_expiration');

        function closeModal() {
            document.getElementById('availModal').classList.remove('show');
            location.reload();
        }
    </script>

</body>
</html>