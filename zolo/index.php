<?php
session_start();

// 👇 GLOBAL DB CONNECTION - PUT AT TOP (line 2)
function getDB() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli("localhost", "root", "", "zolo_db");
        if ($conn->connect_error) die("DB Error: " . $conn->connect_error);
        $conn->set_charset("utf8mb4");
    }
    return $conn;
}

$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? '';
$isVerified = $_SESSION['is_verified'] ?? 0;

// Plans using global connection
$plans_conn = getDB();
$plans_sql = "SELECT * FROM plans ORDER BY price ASC";
$plans_result = $plans_conn->query($plans_sql);
$plans = [];
if ($plans_result) {
    while($plan = $plans_result->fetch_assoc()) {
        $plans[] = $plan;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zolo | Digital Marketing Solutions</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- Top Bar with Login/Register -->
    <div class="top-bar">
        <div class="top-bar-container">
            <div class="top-bar-left">
                📧 info@zolo.com | 📞 +1 234 567 890
            </div>
            <div class="top-bar-right">
                <?php if ($isLoggedIn): ?>
                    <span>👤 Welcome, <?php echo $userName; ?></span>
                    <?php if ($isVerified): ?>
                        <span class="verified-badge">✓ Verified</span>
                    <?php else: ?>
                        <span class="verification-badge">⚠ Unverified</span>
                    <?php endif; ?>
                    <a href="logout.php" class="logout-btn">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="login-btn-small">Login</a>
                    <a href="register.php" class="register-btn-small">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

  <!-- Main Header -->
<div class="header-main">
    <div class="nav-container">
        <a href="index.php" class="logo">Zolo<span>.</span></a>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="index.php#services">Services</a></li>
                <li><a href="services.php">All Services</a></li>
                <li><a href="blog.php">Blogs</a></li>
                <li><a href="subscription.php" class="my-plans-btn">⭐ My Plans</a></li>
                <li><a href="index.php#contact">Contact</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</div>

    <!-- Hero Section -->
    <section class="hero">
        <h1>Elevate Your Digital <span>Presence</span></h1>
        <p>We provide comprehensive digital marketing solutions to help your business grow.</p>
        
        <!-- Search Bar -->
        <form action="search.php" method="GET" class="search-container">
            <input type="text" name="q" placeholder="Search services, blog posts..." required>
            <button type="submit">Search</button>
        </form>
    </section>
        

    <!-- 🔥 COMPLETE PREMIUM PLANS - SELF CONTAINED -->
    <style>
    .plans-hero{background:linear-gradient(135deg,#0f0f23 0%,#1a1a3e 70%,#2d2d5f 100%);padding:100px 20px;color:#fff;text-align:center;position:relative;overflow:hidden}
    .plans-hero h2{font-size:3rem;font-weight:800;background:linear-gradient(45deg,#ffd700,#ffed4e,#fff);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:20px}
    .plans-hero p{font-size:1.3rem;opacity:.9;max-width:600px;margin:0 auto 0}
    .plans-grid{background:#f8f9ff;padding:60px 20px;display:grid;grid-template-columns:repeat(auto-fit,minmax(350px,1fr));gap:30px;max-width:1200px;margin:0 auto}
    .plan-card{background:linear-gradient(145deg,rgba(255,255,255,.1),rgba(255,255,255,.02));backdrop-filter:blur(15px);border:1px solid rgba(255,255,255,.15);border-radius:20px;padding:40px 30px;transition:all .3s ease;box-shadow:0 15px 35px rgba(0,0,0,.1);position:relative;overflow:hidden}
    .plan-card:hover{transform:translateY(-15px);box-shadow:0 30px 60px rgba(0,0,0,.2)}
    .plan-card.gold{background:linear-gradient(145deg,rgba(255,215,0,.2),rgba(255,237,78,.1));border-color:#ffd700;transform:scale(1.03)}
    .popular-badge{position:absolute;top:15px;right:15px;background:linear-gradient(45deg,#ff6b6b,#ff5252);color:#fff;padding:6px 16px;border-radius:25px;font-size:.8rem;font-weight:700;box-shadow:0 4px 12px rgba(255,107,107,.4)}
    .plan-icon{font-size:4rem;margin-bottom:20px;text-shadow:0 4px 8px rgba(0,0,0,.3)}
    .plan-name{font-size:2rem;font-weight:800;margin-bottom:10px;color:#fff}
    .plan-price{font-size:3.5rem;font-weight:900;background:linear-gradient(45deg,#ffd700,#ffed4e);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;display:inline-block}
    .price-year{font-size:1.1rem;opacity:.8;margin-left:8px}
    .save-badge{background:linear-gradient(45deg,#4ecdc4,#44a08d);color:#fff;padding:4px 12px;border-radius:15px;font-size:.8rem;font-weight:600;margin-left:12px;box-shadow:0 2px 8px rgba(78,205,196,.4)}
    .features{list-style:none;padding:0;margin:25px 0}
    .features li{padding:12px 0;font-size:1.1rem;border-bottom:1px solid rgba(255,255,255,.1);display:flex;align-items:center}
    .features li::before{content:'✅';color:#4ecdc4;font-size:1.3rem;margin-right:12px;min-width:20px}
    .plan-btn{background:linear-gradient(45deg,#ff6b6b,#ff5252);color:#fff!important;border:none;padding:16px 40px;border-radius:50px;font-size:1.2rem;font-weight:700;cursor:pointer;transition:all .3s ease;text-decoration:none!important;display:block;text-align:center;text-transform:uppercase;letter-spacing:.5px;margin-top:20px;box-shadow:0 8px 25px rgba(255,107,107,.4)}
    .plan-btn:hover{background:linear-gradient(45deg,#ff5252,#ff4757);transform:translateY(-2px);box-shadow:0 15px 35px rgba(255,107,107,.6)}
    @media(max-width:768px){.plans-grid{grid-template-columns:1fr;gap:25px;padding:40px 15px}.plan-price{font-size:2.8rem}}
    </style>

    <section class="plans-hero">
        <h2>Annual Subscription Plans</h2>
        <p>Save up to 40% with annual billing. Premium features unlocked instantly!</p>
    </section>

    <section class="plans-grid">
        <?php foreach($plans as $index => $plan): ?>
        <div class="plan-card <?php echo $plan['name']=='Gold'?'gold':''; ?>">
            <?php if($plan['name']=='Gold'): ?>
            <div class="popular-badge">🔥 BEST VALUE</div>
            <?php endif; ?>
            
            <div class="plan-icon">
                <?php 
                $icons = ['Pro'=>'⭐', 'Gold'=>'⭐⭐', 'Premium'=>'⭐⭐⭐'];
                echo $icons[$plan['name']] ?? '⭐';
                ?>
            </div>
            
            <div class="plan-name"><?php echo $plan['name']; ?> Annual</div>
            
            <div style="margin:25px 0 30px">
                <span class="plan-price">₹<?php echo number_format($plan['price']); ?></span>
                <span class="price-year">/year</span>
                <?php if($plan['name']=='Gold'): ?>
                <span class="save-badge">SAVE 40%</span>
                <?php endif; ?>
            </div>
            
            <ul class="features">
                <li><?php echo $plan['services_allowed']>=999?'Unlimited Services Access':'Any '.$plan['services_allowed'].' Services'; ?></li>
                <li>Blog Writing & Publishing</li>
                <?php if($plan['blog_access']=='full'): ?>
                <li><strong>Full Blog Management (Edit/Delete)</strong></li>
                <?php endif; ?>
                <li>365 Days Premium Access</li>
                <li>Priority 24/7 Support</li>
                <li>Monthly Performance Reports</li>
                <li>Cancel Anytime</li>
            </ul>
            
            <?php if($isLoggedIn): ?>
                <a href="subscription.php" class="plan-btn">
                    GET <?php echo strtoupper($plan['name']); ?> ANNUAL
                </a>
            <?php else: ?>
                <a href="login.php" class="plan-btn">LOGIN TO UNLOCK</a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </section>



    <!-- Main Content -->
    <div class="container"></div>

    <!-- Main Content -->
    <div class="container">
        
        <!-- Unverified Warning -->
        <?php if ($isLoggedIn && !$isVerified): ?>
        <div class="alert-box alert-warning">
            <strong>⚠️ Email Not Verified!</strong> Please verify your email to access all features. 
            <br>
            <a href="resend_verify.php" class="resend-link">Resend Verification Email</a>
        </div>
        <?php endif; ?>

<!-- Services Section -->
<section id="services">
    <div class="section-title">
        <h2>Our Services</h2>
        <p>Comprehensive digital solutions tailored to your business needs</p>
    </div>
    
    <div class="services-grid">
        <?php
        $conn = new mysqli("localhost", "root", "", "zolo_db");
        
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        $result = $conn->query("SELECT * FROM services LIMIT 6");
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Specific icons based on service title or slug
                $service_title = strtolower($row['title'] ?? '');
                $service_slug = strtolower($row['slug'] ?? '');
                
                $icon = '⭐'; // Default fallback
                
                // Assign specific icons based on service type
                if (stripos($service_title, 'seo') !== false || stripos($service_slug, 'seo') !== false) {
                    $icon = '🔍';
                } elseif (stripos($service_title, 'social') !== false || stripos($service_slug, 'social') !== false) {
                    $icon = '📱';
                } elseif (stripos($service_title, 'ppc') !== false || stripos($service_slug, 'ppc') !== false) {
                    $icon = '💰';
                } elseif (stripos($service_title, 'email') !== false || stripos($service_slug, 'email') !== false) {
                    $icon = '📧';
                } elseif (stripos($service_title, 'content') !== false || stripos($service_slug, 'content') !== false) {
                    $icon = '✍️';
                } elseif (stripos($service_title, 'web') !== false || stripos($service_slug, 'web') !== false) {
                    $icon = '🌐';
                } elseif (stripos($service_title, 'design') !== false || stripos($service_slug, 'design') !== false) {
                    $icon = '🎨';
                } elseif (stripos($service_title, 'video') !== false || stripos($service_slug, 'video') !== false) {
                    $icon = '🎥';
                } elseif (stripos($service_title, 'analytics') !== false || stripos($service_slug, 'analytics') !== false) {
                    $icon = '📊';
                } else {
                    $icon = '🚀'; // Generic business icon
                }
                
                echo '<a href="service.php?slug=' . htmlspecialchars($row['slug'] ?? '') . '" class="service-card">';
                echo '<div class="service-icon">' . $icon . '</div>'; // ✅ Line 103 - Specific icons!
                echo '<h3>' . htmlspecialchars($row['title'] ?? 'Service') . '</h3>';
                echo '<p>' . htmlspecialchars($row['short_description'] ?? 'No description available') . '</p>';
                echo '<div class="price-tag" style="font-size: 0.9rem; padding: 5px 15px; margin-bottom: 10px;">$' . number_format($row['price'] ?? 0, 2) . '</div>';
                echo '<span class="service-link">Learn More →</span>';
                echo '</a>';
            }
        } else {
            // Fallback services if no data in database
            $fallback_services = [
                ['SEO Optimization', '🔍 Boost your search rankings', 299],
                ['Social Media Marketing', '📱 Grow your audience', 199],
                ['PPC Advertising', '💰 Get instant traffic', 499],
                ['Email Marketing', '📧 Convert leads to sales', 149],
                ['Content Writing', '✍️ Engage your audience', 99],
                ['Web Design', '🌐 Modern responsive sites', 999]
            ];
            
            foreach ($fallback_services as $service) {
                echo '<a href="service.php" class="service-card">';
                echo '<div class="service-icon">' . $service[1][0] . '</div>';
                echo '<h3>' . $service[0] . '</h3>';
                echo '<p>' . $service[1] . '</p>';
                echo '<div class="price-tag" style="font-size: 0.9rem; padding: 5px 15px; margin-bottom: 10px;">$' . number_format($service[2], 2) . '</div>';
                echo '<span class="service-link">Learn More →</span>';
                echo '</a>';
            }
        }
        
        $conn->close();
        ?>
    </div>
</section>
       <!-- Blog Section -->
<section id="blog">
    <div class="section-title">
        <h2>Latest Blog Posts</h2>
        <p>Insights and tips from our digital marketing experts</p>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="blog_manager.php" style="display: inline-block; margin-top: 10px; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 20px;">➕ Add New Blog</a>
        <?php endif; ?>
    </div>
    
    <div class="blog-grid">
        <?php
        require_once 'config.php';
        
        // Get latest 6 published blog posts
        $result = $conn->query("SELECT bp.*, bc.name as category_name 
            FROM blog_posts bp 
            LEFT JOIN blog_categories bc ON bp.category_id = bc.id 
            WHERE bp.status = 'published' 
            ORDER BY bp.created_at DESC LIMIT 6");
        
        if ($result && $result->num_rows > 0):
            while ($row = $result->fetch_assoc()): 
        ?>
            <div class="blog-card">
                <?php if (!empty($row['featured_image'])): ?>
                    <img src="<?php echo htmlspecialchars($row['featured_image']); ?>" alt="<?php echo htmlspecialchars($row['title'] ?? 'Blog Post'); ?>" class="blog-image">
                <?php else: ?>
                    <div class="blog-image" style="display: flex; align-items: center; justify-content: center; font-size: 3rem; background: #eee;">📝</div>
                <?php endif; ?>
                <div class="blog-content">
                    <div class="blog-meta">
                        <?php echo date('M d, Y', strtotime($row['created_at'] ?? 'now')); ?> | 
                        <?php echo htmlspecialchars($row['author_name'] ?? 'Anonymous'); ?> <!-- ✅ Fixed author_name -->
                        <?php if (!empty($row['category_name'])): ?>
                            | <span style="color: #667eea;"><?php echo htmlspecialchars($row['category_name']); ?></span>
                        <?php endif; ?>
                    </div>
                    <h3><?php echo htmlspecialchars($row['title'] ?? 'Untitled Post'); ?></h3>
                    <p><?php echo htmlspecialchars(($row['excerpt'] ?? $row['content'] ?? 'No description available.')); ?></p> <!-- ✅ Fixed excerpt -->
                    <a href="blog_single.php?id=<?php echo htmlspecialchars($row['id'] ?? 0); ?>" class="read-more">Read More →</a>
                </div>
            </div>
        <?php 
            endwhile;
        else:
        ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #666;">
                <p style="font-size: 3rem;">📝</p>
                <h3>No Blog Posts Yet</h3>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="blog_manager.php" style="color: #667eea;">Create your first blog post!</a>
                <?php else: ?>
                    <p>Check back later for new content!</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($conn)) $conn->close(); ?>
    </div>
    
    <!-- View All Blog Button -->
    <div style="text-align: center; margin-top: 30px;">
        <a href="blog.php" class="read-more-btn" style="display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 50px; font-weight: 600;">
            View All Posts →
        </a>
    </div>
</section>
        <!-- Contact Form Section -->
        <section id="contact" class="contact-section">
            <h2>Get In Touch</h2>
            <p>Have a project in mind? Let's discuss how we can help your business grow.</p>
            
            <form id="contactForm" autocomplete="off">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required placeholder="John Doe">
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="john@example.com">
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" required placeholder="+1 234 567 890">
                </div>

                <div class="form-group">
                    <label for="message">Your Message</label>
                    <textarea id="message" name="message" required placeholder="Tell us about your project..."></textarea>
                </div>

                <button type="submit" id="submitBtn" class="submit-btn">Send Message</button>
            </form>
        </section>

    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 Zolo Digital Solutions. All rights reserved.</p>
    </footer>

    <!-- AJAX Script -->
    <script>
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const btn = document.getElementById('submitBtn');
            const originalText = btn.innerText;
            
            btn.innerText = "Sending...";
            btn.disabled = true;

            const formData = {
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                phone: document.getElementById('phone').value,
                message: document.getElementById('message').value
            };

            fetch('submit.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById('contactForm').reset();
                    btn.innerText = "Sent!";
                    setTimeout(() => {
                        window.location.href = 'thankyou.php';
                    }, 1000);
                } else {
                    alert('Error: ' + data.message);
                    btn.innerText = originalText;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Something went wrong!');
                btn.innerText = originalText;
                btn.disabled = false;
            });
        });
    </script>

</body>
</html>