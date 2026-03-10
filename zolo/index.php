<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? '';
$isVerified = $_SESSION['is_verified'] ?? 0;
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
        
        while ($row = $result->fetch_assoc()) {
            echo '<a href="service.php?slug=' . $row['slug'] . '" class="service-card">';
            echo '<div class="service-icon">' . $row['icon'] . '</div>';
            echo '<h3>' . $row['title'] . '</h3>';
            echo '<p>' . $row['short_description'] . '</p>';
            echo '<div class="price-tag" style="font-size: 0.9rem; padding: 5px 15px; margin-bottom: 10px;">$' . number_format($row['price'], 2) . '</div>';
            echo '<span class="service-link">Learn More →</span>';
            echo '</a>';
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
                <?php if ($row['featured_image']): ?>
                    <img src="<?php echo $row['featured_image']; ?>" alt="<?php echo htmlspecialchars($row['title']); ?>" class="blog-image">
                <?php else: ?>
                    <div class="blog-image" style="display: flex; align-items: center; justify-content: center; font-size: 3rem; background: #eee;">📝</div>
                <?php endif; ?>
                <div class="blog-content">
                    <div class="blog-meta">
                        <?php echo date('M d, Y', strtotime($row['created_at'])); ?> | 
                        <?php echo htmlspecialchars($row['author_name']); ?>
                        <?php if ($row['category_name']): ?>
                            | <span style="color: #667eea;"><?php echo htmlspecialchars($row['category_name']); ?></span>
                        <?php endif; ?>
                    </div>
                    <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                    <p><?php echo htmlspecialchars($row['excerpt']); ?></p>
                    <a href="blog_single.php?id=<?php echo $row['id']; ?>" class="read-more">Read More →</a>
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
        
        <?php $conn->close(); ?>
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