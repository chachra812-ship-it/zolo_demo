<?php
session_start();
require_once 'config.php';

// COMPLETE Services Array - 6 Services with Icons
$services = [
    ['id'=>1, 'title'=>'Web Development', 'slug'=>'web-development', 'icon'=>'🌐', 'short_description'=>'Professional responsive websites', 'price'=>1500.00, 'duration_hours'=>40],
    ['id'=>2, 'title'=>'SEO Optimization', 'slug'=>'seo-optimization', 'icon'=>'🔍', 'short_description'=>'Complete SEO services', 'price'=>800.00, 'duration_hours'=>20],
    ['id'=>3, 'title'=>'Digital Marketing', 'slug'=>'digital-marketing', 'icon'=>'📈', 'short_description'=>'Complete digital marketing', 'price'=>1200.00, 'duration_hours'=>30],
    // 🔥 3 NEW SERVICES
    ['id'=>4, 'title'=>'Mobile App Development', 'slug'=>'mobile-app-development', 'icon'=>'📱', 'short_description'=>'Custom mobile applications', 'price'=>2500.00, 'duration_hours'=>60],
    ['id'=>5, 'title'=>'UI/UX Design', 'slug'=>'ui-ux-design', 'icon'=>'🎨', 'short_description'=>'Professional UI/UX design', 'price'=>900.00, 'duration_hours'=>25],
    ['id'=>6, 'title'=>'E-commerce Setup', 'slug'=>'ecommerce-setup', 'icon'=>'🛒', 'short_description'=>'Full e-commerce solution', 'price'=>2000.00, 'duration_hours'=>45]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services | Zolo</title>
    <style>
        /* COMPLETE CSS - Copy everything below */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); 
            min-height: 100vh; 
            line-height: 1.6; 
            color: #333;
        }
        
        /* Top Bar */
        .top-bar { 
            background: #1a1a2e; 
            color: white; 
            padding: 10px 0; 
            font-size: 0.9rem; 
        }
        .top-bar-container { 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 0 20px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        .login-btn-small, .register-btn-small, .logout-btn { 
            color: white; 
            text-decoration: none; 
            padding: 6px 14px; 
            margin-left: 10px; 
            border-radius: 20px; 
            transition: 0.3s; 
            font-weight: 500; 
        }
        .login-btn-small:hover, .register-btn-small:hover { background: rgba(255,255,255,0.1); }
        .logout-btn { background: #dc3545; padding: 8px 16px; }
        .logout-btn:hover { background: #c82333; }
        
        /* Header */
        .services-page-header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            padding: 80px 20px; 
            text-align: center; 
            position: relative; 
            overflow: hidden; 
        }
        .services-page-header::before { 
            content: ''; 
            position: absolute; 
            top: 0; 
            left: 0; 
            right: 0; 
            bottom: 0; 
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>'); 
        }
        .services-page-header h1 { 
            font-size: 3rem; 
            margin-bottom: 15px; 
            position: relative; 
            z-index: 2; 
            text-shadow: 0 2px 10px rgba(0,0,0,0.3); 
        }
        .services-page-header p { 
            font-size: 1.3rem; 
            opacity: 0.95; 
            position: relative; 
            z-index: 2; 
            max-width: 600px; 
            margin: 0 auto; 
        }
        
        /* Container */
        .services-container { 
            max-width: 1300px; 
            margin: 0 auto; 
            padding: 60px 20px; 
        }
        
        /* Login Notice */
        .login-notice { 
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); 
            border: 2px solid #ffd700; 
            padding: 25px; 
            border-radius: 20px; 
            text-align: center; 
            margin-bottom: 50px; 
            box-shadow: 0 10px 30px rgba(255,215,0,0.2); 
        }
        .login-notice strong { color: #856404; }
        .login-notice a { 
            color: #667eea; 
            font-weight: 700; 
            text-decoration: none; 
            padding: 8px 16px; 
            border-radius: 25px; 
            transition: 0.3s; 
            display: inline-block; 
            margin: 0 5px; 
        }
        .login-notice a:hover { background: rgba(102,126,234,0.1); transform: translateY(-2px); }
        
        /* Services Grid */
        .services-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); 
            gap: 35px; 
            margin-top: 20px; 
        }
        
        /* Service Card */
        .service-card { 
            background: rgba(255,255,255,0.95); 
            backdrop-filter: blur(15px); 
            padding: 40px 30px; 
            border-radius: 25px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.15); 
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
            border: 1px solid rgba(255,255,255,0.2); 
            position: relative; 
            overflow: hidden; 
        }
        .service-card::before { 
            content: ''; 
            position: absolute; 
            top: 0; 
            left: 0; 
            right: 0; 
            height: 5px; 
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb, #667eea); 
            background-size: 300% 100%; 
            animation: gradientShift 3s ease infinite; 
        }
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        .service-card:hover { 
            transform: translateY(-15px) scale(1.02); 
            box-shadow: 0 35px 80px rgba(0,0,0,0.25); 
        }
        
        /* Icon */
        .service-card .icon { 
            font-size: 5rem; 
            margin-bottom: 25px; 
            display: block; 
            text-shadow: 0 4px 15px rgba(0,0,0,0.2); 
            animation: float 3s ease-in-out infinite; 
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        /* Content */
        .service-card h3 { 
            color: #1a1a2e; 
            margin-bottom: 18px; 
            font-size: 1.6rem; 
            font-weight: 700; 
            letter-spacing: -0.5px; 
        }
        .service-card p { 
            color: #64748b; 
            margin-bottom: 25px; 
            font-size: 1.05rem; 
            line-height: 1.7; 
        }
        
        /* Price */
        .service-card .price { 
            font-size: 2.8rem; 
            font-weight: 800; 
            background: linear-gradient(135deg, #28a745, #20c997); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent; 
            background-clip: text; 
            margin-bottom: 15px; 
            text-shadow: none; 
        }
        
        /* Duration */
        .service-card .duration { 
            color: #94a3b8; 
            font-size: 1rem; 
            font-weight: 500; 
            margin-bottom: 25px; 
            background: rgba(102,126,234,0.1); 
            padding: 8px 16px; 
            border-radius: 25px; 
            display: inline-block; 
        }
        
        /* Buy Button */
        .buy-btn { 
            width: 100%; 
            padding: 18px 30px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            border: none; 
            border-radius: 50px; 
            font-size: 1.15rem; 
            font-weight: 700; 
            cursor: pointer; 
            transition: all 0.4s; 
            box-shadow: 0 10px 30px rgba(102,126,234,0.4); 
            position: relative; 
            overflow: hidden; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
        }
        .buy-btn::before { 
            content: ''; 
            position: absolute; 
            top: 0; 
            left: -100%; 
            width: 100%; 
            height: 100%; 
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent); 
            transition: left 0.5s; 
        }
        .buy-btn:hover::before { left: 100%; }
        .buy-btn:hover { 
            transform: translateY(-4px); 
            box-shadow: 0 20px 50px rgba(102,126,234,0.6); 
        }
        .buy-btn:active { transform: translateY(-2px); }
        .buy-btn:disabled { 
            opacity: 0.6; 
            cursor: not-allowed; 
            background: #94a3b8; 
            box-shadow: none; 
        }
        
        /* Login Notice Links */
        .login-notice a { 
            color: #667eea; 
            font-weight: 700; 
            text-decoration: none; 
            padding: 10px 20px; 
            border-radius: 25px; 
            transition: 0.3s; 
            display: inline-block; 
            margin: 0 8px; 
            border: 2px solid transparent; 
        }
        .login-notice a:hover { 
            background: rgba(102,126,234,0.1); 
            border-color: #667eea; 
            transform: translateY(-2px); 
        }
        
        /* Footer */
        footer { 
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); 
            color: #94a3b8; 
            text-align: center; 
            padding: 40px 20px; 
            margin-top: 80px; 
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .services-grid { grid-template-columns: 1fr; gap: 25px; }
            .services-page-header h1 { font-size: 2rem; }
            .services-page-header p { font-size: 1.1rem; }
            .services-container { padding: 40px 15px; }
        }
    </style>
</head>
<body>

    <!-- Top Bar -->
    <div class="top-bar">
        <div class="top-bar-container">
            <div class="top-bar-left">📧 info@zolo.com | 📞 +1 234 567 890</div>
            <div class="top-bar-right">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span>👤 Welcome, <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User') ?></span>
                    <a href="dashboard.php" class="login-btn-small">Dashboard</a>
                    <a href="auth.php?action=logout" class="logout-btn">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="login-btn-small">Login</a>
                    <a href="register.php" class="register-btn-small">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="services-page-header">
        <h1>🚀 Premium Services</h1>
        <p>Transform your business with our world-class digital solutions</p>
    </div>

    <!-- Services Container -->
    <div class="services-container">
        <?php if (!isset($_SESSION['user_id'])): ?>
        <div class="login-notice">
            <strong>🔐 Login to Unlock:</strong> Book services & access dashboard features
            <br>
            <a href="login.php">Login Now</a>
            <a href="register.php">Create Account</a>
        </div>
        <?php endif; ?>

        <div class="services-grid">
            <?php foreach ($services as $service): ?>
            <div class="service-card">
                <div class="icon"><?= htmlspecialchars($service['icon']) ?></div>
                <h3><?= htmlspecialchars($service['title']) ?></h3>
                <p><?= htmlspecialchars($service['short_description']) ?></p>
                <div class="price">$<?= number_format($service['price'], 2) ?></div>
                <div class="duration"><?= $service['duration_hours'] ?>h delivery</div>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <button class="buy-btn" onclick="bookService(<?= $service['id'] ?>, '<?= addslashes($service['title']) ?>', <?= $service['price'] ?>)">
                        Book Service
                    </button>
                <?php else: ?>
                    <a href="login.php" class="buy-btn" style="text-decoration: none; display: block; text-align: center;">
                        Login to Book
                    </a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <p>© 2024 Zolo Digital Solutions. Professional services for your success 🚀</p>
    </footer>

    <script>
    function bookService(id, title, price) {
        if (confirm(`📋 Book "${title}"\n💰 $${price.toLocaleString()}\n⏱️ ${id <= 3 ? 'Fast' : 'Premium'} delivery\n\nConfirm booking?`)) {
            alert(`✅ "${title}" successfully booked!\n\nView in Dashboard →`);
            window.location.href = 'dashboard.php';
        }
    }
    </script>
    <script>
const urlParams = new URLSearchParams(window.location.search);
const serviceId = urlParams.get('buy');

if (serviceId) {
    // Find service details (you must match IDs)
    const services = {
        1: { name: "Web Development", price: 1500 },
        2: { name: "SEO Optimization", price: 800 },
        3: { name: "Digital Marketing", price: 1200 },
        4: { name: "Mobile App", price: 2500 },
        5: { name: "UI/UX Design", price: 900 },
        6: { name: "E-commerce", price: 2000 }
    };

    if (services[serviceId]) {
        openPurchaseModal(serviceId, services[serviceId].name, services[serviceId].price);
    }
}
</script>

</body>
</html>