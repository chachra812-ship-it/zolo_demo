<?php
session_start();

// Include database connection
require_once 'config.php';

// Get all services
$result = $conn->query("SELECT * FROM services ORDER BY id ASC");
$services = $result->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services | Zolo</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .services-page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }

        .services-page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .services-page-header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .services-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .service-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: 0.3s;
            text-align: center;
        }

        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .service-card .icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }

        .service-card h3 {
            color: #1a1a2e;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }

        .service-card p {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .service-card .price {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 20px;
        }

        .buy-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }

        .buy-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .buy-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .login-notice {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 30px;
        }

        .login-notice a {
            color: #667eea;
            font-weight: 600;
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
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span>👤 Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <a href="dashboard.php" class="login-btn-small">Dashboard</a>
                    <a href="logout.php" class="logout-btn">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="login-btn-small">Login</a>
                    <a href="register.php" class="register-btn-small">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="services-page-header">
        <h1>🚀 Our Services</h1>
        <p>Choose from our range of digital marketing solutions</p>
    </div>

    <!-- Services Container -->
    <div class="services-container">
        
        <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="login-notice">
                <strong>📝 Note:</strong> You need to <a href="login.php">login</a> or <a href="register.php">register</a> to purchase services.
            </div>
        <?php endif; ?>

        <div class="services-grid">
            <?php foreach ($services as $service): ?>
                <div class="service-card">
                    <div class="icon"><?php echo $service['icon']; ?></div>
                    <h3><?php echo htmlspecialchars($service['title']); ?></h3>
                    <p><?php echo htmlspecialchars($service['short_description']); ?></p>
                    <div class="price">$<?php echo number_format($service['price'], 2); ?></div>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <button class="buy-btn" onclick="buyService(<?php echo $service['id']; ?>, '<?php echo htmlspecialchars($service['title']); ?>', <?php echo $service['price']; ?>)">
                            Buy Now
                        </button>
                    <?php else: ?>
                        <a href="login.php" class="buy-btn" style="display: block; text-decoration: none;">
                            Login to Buy
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 Zolo Digital Solutions. All rights reserved.</p>
    </footer>

    <!-- JavaScript -->
    <script>
        function buyService(serviceId, serviceTitle, servicePrice) {
            if (!confirm(`Purchase "${serviceTitle}" for $${servicePrice}?`)) {
                return;
            }

            fetch('api.php?action=buy_service', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    service_id: serviceId,
                    service_title: serviceTitle,
                    service_price: servicePrice
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('✅ ' + data.message + '\n\nYou can now avail this service from your dashboard!');
                    window.location.href = 'dashboard.php';
                } else {
                    alert('❌ ' + data.message);
                }
            })
            .catch(error => {
                alert('Something went wrong!');
                console.error('Error:', error);
            });
        }
    </script>

</body>
</html>