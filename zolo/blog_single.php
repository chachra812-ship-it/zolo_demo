<?php
session_start();

require_once 'config.php';

// Get post ID
$post_id = intval($_GET['id'] ?? 0);

// Get post details
$post = $conn->query("SELECT bp.*, bc.name as category_name 
    FROM blog_posts bp 
    LEFT JOIN blog_categories bc ON bp.category_id = bc.id 
    WHERE bp.id = $post_id AND bp.status = 'published'")->fetch_assoc();

// Get sections
$sections = []; // ✅ Empty array - no error

// Get related posts
$related_posts = $conn->query("SELECT bp.* FROM blog_posts bp 
    WHERE bp.status = 'published' AND bp.id != $post_id 
    ORDER BY bp.created_at DESC LIMIT 3")->fetch_all(MYSQLI_ASSOC);

$conn->close();

if (!$post) {
    header("Location: blog.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?> | Zolo Blog</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .single-post-header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            padding: 60px 20px;
            text-align: center;
        }

        .single-post-header h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .single-post-meta {
            opacity: 0.9;
            font-size: 1rem;
        }

        .single-post-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        /* Featured Image */
        .featured-image-container {
            margin: -60px auto 30px;
            max-width: 900px;
            padding: 0 20px;
        }

        .featured-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        /* Post Content */
        .post-content {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.08);
            margin-bottom: 40px;
        }

        .post-category {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 6px 18px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        .post-meta {
            color: #999;
            font-size: 0.9rem;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        /* Sections */
        .post-sections {
            margin-top: 30px;
        }

        .section-block {
            margin-bottom: 40px;
        }

        .section-block:last-child {
            margin-bottom: 0;
        }

        .section-title {
            color: #1a1a2e;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }

        .section-content {
            color: #555;
            line-height: 1.8;
            font-size: 1.05rem;
        }

        .section-image {
            max-width: 100%;
            border-radius: 15px;
            margin: 20px 0;
        }

        /* Related Posts */
        .related-posts {
            margin-top: 50px;
        }

        .related-posts h2 {
            color: #1a1a2e;
            margin-bottom: 25px;
            text-align: center;
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .related-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: 0.3s;
            text-decoration: none;
        }

        .related-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        }

        .related-card-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background: #eee;
        }

        .related-card-body {
            padding: 20px;
        }

        .related-card-title {
            color: #1a1a2e;
            font-size: 1.1rem;
            margin-bottom: 10px;
        }

        .related-card-date {
            color: #999;
            font-size: 0.85rem;
        }

        /* Back Button */
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .single-post-header h1 {
                font-size: 1.8rem;
            }
            .featured-image {
                height: 250px;
            }
            .post-content {
                padding: 25px;
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
                <?php if (isset($_SESSION['user_id'])): ?>
<span>👤 <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>                    <a href="dashboard.php">Dashboard</a>
                    <a href="blog_manager.php">📝 Blog Manager</a>
                    <a href="logout.php" class="logout-btn">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="login-btn-small">Login</a>
                    <a href="register.php" class="register-btn-small">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Post Header -->
    <div class="single-post-header">
        <?php if ($post['category_name']): ?>
            <span class="post-category"><?php echo htmlspecialchars($post['category_name']); ?></span>
        <?php endif; ?>
        <h1><?php echo htmlspecialchars($post['title']); ?></h1>
        <div class="single-post-meta">
            By <?php echo htmlspecialchars($post['author_name'] ?? 'Anonymous'); ?>
            <?php echo date('F d, Y', strtotime($post['created_at'])); ?>
        </div>
    </div>

    <!-- Featured Image -->
    <?php if ($post['featured_image']): ?>
        <div class="featured-image-container">
            <img src="<?php echo $post['featured_image']; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="featured-image">
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="single-post-container">
        <a href="blog.php" class="back-link">← Back to Blog</a>
        
        <div class="post-content">
            <!-- Excerpt -->
            <?php if ($post['excerpt']): ?>
                <p style="font-size: 1.1rem; color: #666; margin-bottom: 20px; font-style: italic;">
                    <?php echo htmlspecialchars($post['excerpt']); ?>
                </p>
            <?php endif; ?>

            <!-- Post Sections -->
            <div class="post-sections">
                <?php if (count($sections) > 0): ?>
                    <?php foreach ($sections as $section): ?>
                        <div class="section-block">
                            <?php if ($section['section_title']): ?>
                                <h2 class="section-title"><?php echo htmlspecialchars($section['section_title']); ?></h2>
                            <?php endif; ?>
                            <div class="section-content">
                                <?php echo nl2br(htmlspecialchars($section['section_content'])); ?>
                            </div>
                            <?php if ($section['section_image']): ?>
                                <img src="<?php echo $section['section_image']; ?>" alt="Section Image" class="section-image">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #666;">No additional content sections.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Related Posts -->
        <?php if (count($related_posts) > 0): ?>
            <div class="related-posts">
                <h2>📰 Related Posts</h2>
                <div class="related-grid">
                    <?php foreach ($related_posts as $related): ?>
                        <a href="blog_single.php?id=<?php echo $related['id']; ?>" class="related-card">
                            <?php if ($related['featured_image']): ?>
                                <img src="<?php echo $related['featured_image']; ?>" alt="<?php echo htmlspecialchars($related['title']); ?>" class="related-card-image">
                            <?php else: ?>
                                <div class="related-card-image" style="display: flex; align-items: center; justify-content: center; font-size: 2rem;">📝</div>
                            <?php endif; ?>
                            <div class="related-card-body">
                                <h3 class="related-card-title"><?php echo htmlspecialchars($related['title']); ?></h3>
                                <span class="related-card-date"><?php echo date('M d, Y', strtotime($related['created_at'])); ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 Zolo Digital Solutions. All rights reserved.</p>
    </footer>

</body>
</html>