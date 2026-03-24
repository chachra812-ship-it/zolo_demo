<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_plan = 'none'; // Default

// Get current user plan
$stmt = $conn->prepare("SELECT plan FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $user_plan = $row['plan'];
}

// Handle plan purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_plan'])) {
    $plan = $_POST['plan'];
    $prices = ['pro' => 9.99, 'gold' => 19.99, 'premium' => 29.99];
    
    if (isset($prices[$plan])) {
        // Update user plan
        $stmt = $conn->prepare("UPDATE users SET plan = ? WHERE id = ?");
        $stmt->bind_param("si", $plan, $user_id);
        $stmt->execute();
        
        // Clear old purchases
        $conn->query("DELETE FROM user_purchases WHERE user_id = $user_id");
        
        // Auto-assign services by plan
        $services = match($plan) {
            'pro' => [1,2,3],
            'gold' => [1,2,3,4,5,6,7,8],
            'premium' => range(1,15)
        };
        
        foreach ($services as $service_id) {
            $stmt = $conn->prepare("
                INSERT INTO user_purchases (user_id, service_id, service_title, service_price, created_at, expiration_date, status) 
                SELECT ?, s.id, s.title, s.price, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 'active' 
                FROM services s WHERE s.id = ?
            ");
            $stmt->bind_param("ii", $user_id, $service_id);
            $stmt->execute();
        }
        
        $user_plan = $plan;
        $success_msg = "✅ $plan plan activated! Check dashboard.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Plans | Zolo</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 40px;
            box-shadow: 0 30px 80px rgba(0,0,0,0.3);
        }
        .header {
            text-align: center;
            margin-bottom: 50px;
        }
        .header h1 {
            font-size: 3rem;
            color: #1a1a2e;
            margin-bottom: 20px;
        }
        .current-plan {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 25px;
            border-radius: 20px;
            text-align: center;
            margin-bottom: 40px;
        }
        .current-plan h3 { font-size: 2rem; margin-bottom: 10px; }
        .current-plan p { font-size: 1.2rem; opacity: 0.9; }
        
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
        }
        .plan-card {
            background: white;
            border-radius: 25px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            transition: all 0.4s;
            border: 3px solid transparent;
            position: relative;
            overflow: hidden;
        }
        .plan-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        .plan-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 30px 80px rgba(0,0,0,0.25);
        }
        .plan-pro { border-color: #28a745; }
        .plan-gold { border-color: #ffc107; }
        .plan-premium { border-color: #007bff; }
        .plan-name {
            font-size: 2.2rem;
            font-weight: 900;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .plan-price {
            font-size: 3.5rem;
            font-weight: 900;
            color: #28a745;
            margin: 20px 0;
        }
        .plan-features {
            list-style: none;
            margin: 30px 0;
            padding: 0;
        }
        .plan-features li {
            padding: 15px 0;
            font-size: 1.1rem;
            border-bottom: 1px solid #eee;
        }
        .plan-features li:last-child { border-bottom: none; }
        .plan-features li::before {
            content: "✅";
            margin-right: 15px;
            font-size: 1.2rem;
        }
        .buy-form {
            margin-top: 30px;
        }
        .buy-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 20px 50px;
            border-radius: 50px;
            font-size: 1.3rem;
            font-weight: 900;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s;
            width: 100%;
            box-shadow: 0 10px 30px rgba(102,126,234,0.4);
        }
        .buy-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 50px rgba(102,126,234,0.6);
        }
        .buy-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .success-msg {
            background: #d4edda;
            color: #155724;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 30px;
            border: 2px solid #28a745;
        }
        @media (max-width: 768px) {
            .plans-grid { grid-template-columns: 1fr; }
            .header h1 { font-size: 2rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Choose Your Plan</h1>
            <p>Unlock premium services with our subscription plans</p>
        </div>

        <?php if (isset($success_msg)): ?>
            <div class="success-msg">
                <h3>🎉 <?= $success_msg ?></h3>
                <p>Redirecting to dashboard in 3 seconds...</p>
            </div>
        <?php endif; ?>

        <!-- Current Plan Badge -->
        <div class="current-plan">
            <h3>Current Plan: <strong><?= ucfirst($user_plan) ?></strong></h3>
            <?php if ($user_plan !== 'none'): ?>
                <p>Manage your services in <a href="dashboard.php" style="color: #fff; text-decoration: underline;">Dashboard</a></p>
            <?php endif; ?>
        </div>

        <div class="plans-grid">
            <!-- PRO Plan -->
            <div class="plan-card plan-pro">
                <div class="plan-name">PRO</div>
                <div class="plan-price">$9<span style="font-size: 1.5rem;">.99</span></div>
                <ul class="plan-features">
                    <li>✅ 3 Essential Services</li>
                    <li>✅ 30 Days Full Access</li>
                    <li>✅ Avail & Extend Services</li>
                    <li>✅ Basic Support</li>
                </ul>
                <form method="POST" class="buy-form">
                    <button type="submit" name="buy_plan" value="pro" class="buy-btn" 
                            <?= $user_plan === 'pro' ? 'disabled' : '' ?>>
                        <?= $user_plan === 'pro' ? '✅ PRO Active' : 'Buy PRO Now' ?>
                    </button>
                    <input type="hidden" name="plan" value="pro">
                </form>
            </div>

            <!-- GOLD Plan -->
            <div class="plan-card plan-gold">
                <div class="plan-name">GOLD</div>
                <div class="plan-price">$19<span style="font-size: 1.5rem;">.99</span></div>
                <ul class="plan-features">
                    <li>✅ 8 Premium Services</li>
                    <li>✅ 30 Days Full Access</li>
                    <li>✅ Priority Support</li>
                    <li>✅ Avail & Extend All</li>
                </ul>
                <form method="POST" class="buy-form">
                    <button type="submit" name="buy_plan" value="gold" class="buy-btn" 
                            <?= $user_plan === 'gold' ? 'disabled' : '' ?>>
                        <?= $user_plan === 'gold' ? '✅ GOLD Active' : 'Upgrade to GOLD' ?>
                    </button>
                    <input type="hidden" name="plan" value="gold">
                </form>
            </div>

            <!-- PREMIUM Plan -->
            <div class="plan-card plan-premium">
                <div class="plan-name">PREMIUM</div>
                <div class="plan-price">$29<span style="font-size: 1.5rem;">.99</span></div>
                <ul class="plan-features">
                    <li>✅ ALL Services (15+)</li>
                    <li>✅ 30 Days Unlimited</li>
                    <li>✅ 24/7 VIP Support</li>
                    <li>✅ Priority Everything</li>
                </ul>
                <form method="POST" class="buy-form">
                    <button type="submit" name="buy_plan" value="premium" class="buy-btn" 
                            <?= $user_plan === 'premium' ? 'disabled' : '' ?>>
                        <?= $user_plan === 'premium' ? '✅ PREMIUM Active' : 'Get PREMIUM' ?>
                    </button>
                    <input type="hidden" name="plan" value="premium">
                </form>
            </div>
        </div>

        <div style="text-align: center; margin-top: 50px;">
            <a href="dashboard.php" class="buy-btn" style="width: auto; padding: 15px 40px; background: #28a745;">
                📊 Go to Dashboard
            </a>
        </div>
    </div>
</body>
</html>